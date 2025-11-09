#!/usr/bin/env python3
"""
recognition/service.py
aiohttp + aiortc service for:
 - accept browser SDP offers at POST /offer/{camera_id}
 - stream RTSP -> WebRTC
 - run face recognition periodically with visual feedback
 - post attendance to Laravel API (deduplicated)
 - expose /status and /health
 - handle leave/pass slip integration for attendance remarks
"""
from aiohttp import web
import aiohttp_cors
import os, json, datetime, threading, asyncio, subprocess
from aiortc import RTCPeerConnection, RTCSessionDescription, VideoStreamTrack
import cv2
import numpy as np
import requests
import insightface
from insightface.app import FaceAnalysis
from dotenv import load_dotenv
import time

# -------------------
# Load Laravel .env
# -------------------
env_path = os.getenv("ENV_PATH", "../.env") 
if os.path.exists(env_path):
    load_dotenv(env_path)

API_BASE = os.getenv("API_BASE", "http://127.0.0.1:8000/api")
CAMERAS_ENDPOINT = f"{API_BASE}/cameras"
SCHEDULE_ENDPOINT = f"{API_BASE}/schedule"
FACULTY_EMBEDDINGS_ENDPOINT = f"{API_BASE}/faculty-embeddings"
ATTENDANCE_ENDPOINT = f"{API_BASE}/attendance"
ATTENDANCE_CHECK_ENDPOINT = f"{API_BASE}/attendance/check"
STREAM_RECORDING_ENDPOINT = f"{API_BASE}/stream-recordings"

# Optional API key for authenticated endpoints
API_KEY = os.getenv("API_KEY", None)

# Request headers - include API key if configured
def get_request_headers():
    """Get headers for API requests, including optional API key."""
    headers = {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    }
    if API_KEY:
        headers['Authorization'] = f'Bearer {API_KEY}'
    return headers

MATCH_THRESHOLD = float(os.getenv("MATCH_THRESHOLD", "0.6"))

# Initialize InsightFace with built-in RetinaFace
print("Initializing InsightFace with built-in RetinaFace...")
try:
    # Initialize InsightFace with RetinaFace detector
    face_app = FaceAnalysis(
        name='buffalo_l', 
        providers=['CPUExecutionProvider'],
        allowed_modules=['detection', 'recognition']  # Use both detection and recognition
    )
    face_app.prepare(ctx_id=0, det_size=(640, 640))
    print("✅ InsightFace with RetinaFace initialized")
except Exception as e:
    print(f"❌ Error initializing InsightFace models: {e}")
    face_app = None

# caches
_cameras_cache = {}
_schedules_cache = {}
_presence_accumulator = {}
_faculty_embeddings = {}
_faculty_data_cache = {}  # Cache for faculty data to avoid repeated API calls
_recognition_results = {}
_recognition_logs = {}  # Track multiple recognition logs per camera
_last_overlays = {}  # camera_id -> {"boxes": [ (left, top, right, bottom, (b,g,r), label) ], "expires_at": ts}
_rtsp_tracks = {}
_track_lock = threading.Lock()
_background_threads = {}
_late_tracking = {}  # Track late status for each schedule
_processing_queue = {}  # Queue for async processing
_recognition_tracking = {}  # Track first and last recognition times for each faculty/schedule
_snapshot_storage_path = os.getenv("SNAPSHOT_STORAGE_PATH", "../storage/app/public/attendance_snapshots")
_active_connections = {}  # Track active WebRTC connections per camera
_shared_captures = {}  # Shared video captures per camera
_shared_frames = {}  # Latest frames per camera for sharing
_frame_lock = threading.Lock()  # Lock for frame sharing
_face_tracking_history = {}  # Track face positions for smoothing
_persistent_bounding_boxes = {}  # Track persistent bounding boxes per camera
_stream_recorders = {}  # Track video recorders per camera
_recording_threads = {}  # Track recording threads per camera

# Face recognition settings
RECOGNITION_INTERVAL = 1  # seconds between recognition attempts (WebRTC)
BACKGROUND_RECOGNITION_INTERVAL = 1  # seconds for background processing
PRESENCE_THRESHOLD = 1800  # 30 minutes in seconds
LATE_THRESHOLD = 900  # 15 minutes in seconds for late marking

# Stream recording settings
RECORDING_INTERVAL = 60  # 1 minute in seconds
RECORDING_STORAGE_PATH = os.getenv("RECORDING_STORAGE_PATH", "../storage/app/public/stream_recordings")

# Performance optimization settings
FRAME_SCALE_FACTOR = 0.6  # Scale factor for faster processing
MAX_FRAME_SIZE = 720  # Maximum frame dimension before scaling
ENABLE_ASYNC_PROCESSING = True  # Enable asynchronous processing

# Simple video streaming settings
VIDEO_FPS = 15  # Target FPS for video streaming
FRAME_BUFFER_SIZE = 1  # Minimal buffer for low latency
DRAIN_FRAMES_LIMIT = 2  # Minimal frame draining

# -------------------
# Low-latency RTSP/FFmpeg defaults
# -------------------
# Reduce buffering and force TCP to mitigate packet loss; set a short socket timeout
os.environ.setdefault(
    "OPENCV_FFMPEG_CAPTURE_OPTIONS",
    "rtsp_transport;tcp|max_delay;0|buffer_size;1024|stimeout;5000000"
)

# -------------------
# Fetch data from Laravel
# -------------------
def fetch_cameras():
    try:
        headers = get_request_headers()
        r = requests.get(CAMERAS_ENDPOINT, headers=headers, timeout=8)
        
        # Log response details for debugging
        if r.status_code != 200:
            print(f"fetch_cameras HTTP {r.status_code}: {r.text}")
        
        r.raise_for_status()
        cams = r.json()
        _cameras_cache.clear()
        for c in cams:
            cid = int(c["camera_id"])
            _cameras_cache[cid] = {
                "camera_id": cid,
                "room_no": c.get("room_no"),
                "room_name": c.get("room_name"),
                "room_building_no": c.get("room_building_no"),
                "camera_live_feed": c.get("camera_live_feed")
            }
        return cams
    except requests.exceptions.HTTPError as e:
        print(f"fetch_cameras HTTP error: {e}")
        if hasattr(e.response, 'text'):
            print(f"Response: {e.response.text}")
        return []
    except Exception as e:
        print(f"fetch_cameras error: {e}")
        return []

def fetch_today_schedule():
    try:
        headers = get_request_headers()
        r = requests.get(SCHEDULE_ENDPOINT, headers=headers, timeout=8)
        r.raise_for_status()
        schedules = r.json()
        _schedules_cache.clear()
        for s in schedules:
            room = str(s["room_no"])
            entry = {
                "teaching_load_id": s.get("teaching_load_id"),
                "faculty_id": s.get("faculty_id"),
                "time_in": s.get("teaching_load_time_in"),
                "time_out": s.get("teaching_load_time_out"),
                "teaching_load_class_section": s.get("teaching_load_class_section")
            }
            _schedules_cache.setdefault(room, []).append(entry)
        return schedules
    except Exception as e:
        print(f"fetch_today_schedule error: {e}")
        return []

# -------------------
# Current schedule helpers
# -------------------
def _to_minutes(time_str: str):
    try:
        hh, mm, *rest = str(time_str).split(":")
        return int(hh) * 60 + int(mm)
    except Exception:
        return None

def get_current_schedule_for_room(room_no: str):
    """Return the schedule dict for the current time (Asia/Manila) in given room, else None."""
    import datetime
    import pytz
    tz = pytz.timezone("Asia/Manila")
    now = datetime.datetime.now(tz)
    now_min = now.hour * 60 + now.minute
    room_key = str(room_no)
    entries = _schedules_cache.get(room_key, [])
    for s in entries:
        start = _to_minutes(s.get("time_in"))
        end = _to_minutes(s.get("time_out"))
        if start is None or end is None:
            continue
        if start <= now_min <= end:
            return s
    return None

# -------------------
# Leave/Pass slip checking
# -------------------
def check_faculty_status(faculty_id: int, date: str, time_in: str, time_out: str):
    """Check if faculty is on leave or has pass slip for the given date and time range."""
    try:
        # Check leave status
        headers = get_request_headers()
        leave_url = f"{API_BASE}/faculty-leave-status"
        leave_payload = {
            "faculty_id": faculty_id,
            "date": date,
            "time_in": time_in,
            "time_out": time_out
        }
        leave_response = requests.post(leave_url, json=leave_payload, headers=headers, timeout=5)
        if leave_response.status_code == 200:
            leave_data = leave_response.json()
            if leave_data.get("on_leave", False):
                return "On leave"
        
        # Check pass slip status
        pass_url = f"{API_BASE}/faculty-pass-status"
        pass_payload = {
            "faculty_id": faculty_id,
            "date": date,
            "time_in": time_in,
            "time_out": time_out
        }
        pass_response = requests.post(pass_url, json=pass_payload, headers=headers, timeout=5)
        if pass_response.status_code == 200:
            pass_data = pass_response.json()
            if pass_data.get("has_pass", False):
                return "With pass slip"
        
        return None
    except Exception as e:
        print(f"Error checking faculty status: {e}")
        return None

# -------------------
# Late threshold tracking
# -------------------
def initialize_late_tracking():
    """Initialize late tracking for all current schedules."""
    import pytz
    tz = pytz.timezone("Asia/Manila")
    now = datetime.datetime.now(tz)
    now_min = now.hour * 60 + now.minute
    
    for room_no, schedules in _schedules_cache.items():
        for sched in schedules:
            start_time = _to_minutes(sched.get("time_in"))
            if start_time is None:
                continue
            
            # Check if schedule is currently active
            end_time = _to_minutes(sched.get("time_out"))
            if end_time is None:
                continue
                
            if start_time <= now_min <= end_time:
                load_id = sched.get("teaching_load_id")
                faculty_id = sched.get("faculty_id")
                key = (room_no, load_id)
                
                # Initialize late tracking if not exists
                if key not in _late_tracking:
                    _late_tracking[key] = {
                        "start_time": start_time,
                        "late_threshold_reached": False,
                        "late_marked": False
                    }
                
                # Initialize recognition tracking for this schedule
                # Find camera for this room
                camera_id = None
                for cam_id, cam_data in _cameras_cache.items():
                    if str(cam_data.get("room_no")) == str(room_no):
                        camera_id = cam_id
                        break
                
                if camera_id:
                    recognition_key = (camera_id, faculty_id, load_id)
                    # Only initialize if not already tracking
                    if recognition_key not in _recognition_tracking:
                        _recognition_tracking[recognition_key] = {
                            "time_in": None,
                            "time_out": None,
                            "last_seen": 0,
                            "total_duration": 0,
                            "first_recognition_time": None
                        }

def check_late_threshold():
    """Check if any schedules have passed the late threshold (15 minutes from start)."""
    import pytz
    tz = pytz.timezone("Asia/Manila")
    now = datetime.datetime.now(tz)
    now_min = now.hour * 60 + now.minute
    date_str = now.strftime("%Y-%m-%d")
    
    for key, late_info in _late_tracking.items():
        room_no, load_id = key
        start_time = late_info["start_time"]
        
        # Check if 15 minutes have passed since class start
        if not late_info["late_threshold_reached"] and (now_min - start_time) >= 15:
            # Check if faculty has been recognized at all
            acc = _presence_accumulator.get(key)
            if not acc or acc["seconds"] == 0:
                # Faculty was not recognized in the first 15 minutes
                late_info["late_threshold_reached"] = True
                print(f"DEBUG: Late threshold reached for room {room_no}, load {load_id} - instructor was not recognized in first 15 minutes")
            else:
                # Faculty was recognized within first 15 minutes, so not late
                print(f"DEBUG: Faculty was recognized within first 15 minutes for room {room_no}, load {load_id} - not marked as late")
            # Don't post attendance here - let schedule end function determine final status

# -------------------
# Recognition time tracking
# -------------------
def save_snapshot(frame, camera_id: int, faculty_id: int, teaching_load_id: int, snapshot_type: str):
    """Save a snapshot image when faculty is first recognized (time_in) or last seen (time_out)."""
    try:
        import pytz
        from datetime import datetime as dt
        
        # Create snapshot storage directory if it doesn't exist
        os.makedirs(_snapshot_storage_path, exist_ok=True)
        
        # Generate filename with timestamp
        tz = pytz.timezone("Asia/Manila")
        now = dt.now(tz)
        timestamp = now.strftime("%Y%m%d_%H%M%S")
        filename = f"camera_{camera_id}_faculty_{faculty_id}_load_{teaching_load_id}_{snapshot_type}_{timestamp}.jpg"
        filepath = os.path.join(_snapshot_storage_path, filename)
        
        # Save frame as JPEG
        cv2.imwrite(filepath, frame)
        
        # Return relative path for database storage
        relative_path = f"attendance_snapshots/{filename}"
        print(f"DEBUG: Saved {snapshot_type} snapshot: {relative_path}")
        return relative_path
    except Exception as e:
        print(f"ERROR: Failed to save snapshot: {e}")
        return None

def track_recognition_time(camera_id: int, faculty_id: int, teaching_load_id: int, frame=None):
    """Track first and last recognition times for faculty and capture snapshots."""
    import pytz
    tz = pytz.timezone("Asia/Manila")
    now = datetime.datetime.now(tz)
    now_str = now.strftime("%Y-%m-%d %H:%M:%S")
    
    key = (camera_id, faculty_id, teaching_load_id)
    print(f"DEBUG: track_recognition_time called for key {key} at {now_str}")
    
    if key not in _recognition_tracking:
        # Initialize tracking structure
        _recognition_tracking[key] = {
            "time_in": None,
            "time_out": None,
            "last_seen": 0,
            "total_duration": 0,
            "first_recognition_time": None,
            "time_in_snapshot": None,
            "time_out_snapshot": None
        }
        print(f"DEBUG: Initialized tracking for key {key}")
    
    tracking = _recognition_tracking[key]
    
    # If this is the first actual recognition (time_in is None), set it and capture snapshot
    if tracking["time_in"] is None:
        tracking["time_in"] = now_str
        tracking["first_recognition_time"] = now_str
        print(f"DEBUG: Set first recognition time: {now_str}")
        
        # Capture time_in snapshot if frame is available
        if frame is not None:
            snapshot_path = save_snapshot(frame, camera_id, faculty_id, teaching_load_id, "time_in")
            if snapshot_path:
                tracking["time_in_snapshot"] = snapshot_path
    else:
        print(f"DEBUG: Updating time_out from {tracking['time_out']} to {now_str}")
    
    # Always update time_out and duration, and capture snapshot for last seen
    tracking["time_out"] = now_str
    tracking["last_seen"] = now.timestamp()
    
    # Capture time_out snapshot if frame is available (always update on each recognition)
    if frame is not None:
        snapshot_path = save_snapshot(frame, camera_id, faculty_id, teaching_load_id, "time_out")
        if snapshot_path:
            tracking["time_out_snapshot"] = snapshot_path
    
    # Calculate total duration from first recognition
    if tracking["time_in"]:
        first_time = datetime.datetime.strptime(tracking["time_in"], "%Y-%m-%d %H:%M:%S")
        first_time = tz.localize(first_time)
        duration_seconds = int((now - first_time).total_seconds())
        tracking["total_duration"] = duration_seconds
        print(f"DEBUG: Updated duration to {duration_seconds} seconds")

def get_recognition_times(camera_id: int, faculty_id: int, teaching_load_id: int):
    """Get recognition times for a faculty/schedule."""
    key = (camera_id, faculty_id, teaching_load_id)
    tracking_data = _recognition_tracking.get(key, {
        "time_in": None,
        "time_out": None,
        "total_duration": 0,
        "time_in_snapshot": None,
        "time_out_snapshot": None
    })
    
    print(f"DEBUG: get_recognition_times for key {key}: {tracking_data}")
    return tracking_data

# -------------------
# Presence accumulation (30-minute threshold)
# -------------------
def record_presence_tick(camera_id: int, detected_faculty_id: int):
    """Accumulate presence seconds for the faculty assigned to this room and post attendance at 30 mins.
    This should be called by the recognition loop whenever a face match is confirmed.
    """
    cam = _cameras_cache.get(int(camera_id))
    if not cam:
        return
    room_no = str(cam.get("room_no"))
    sched = get_current_schedule_for_room(room_no)
    if not sched:
        return
    # Only accumulate if the detected faculty matches the scheduled faculty
    if int(detected_faculty_id) != int(sched.get("faculty_id")):
        return

    load_id = int(sched.get("teaching_load_id"))
    key = (room_no, load_id)
    
    # Get current frame for snapshot capture
    current_frame = get_shared_frame(camera_id)
    
    # Track recognition times (first and last seen) with frame for snapshot
    track_recognition_time(camera_id, detected_faculty_id, load_id, frame=current_frame)
    
    acc = _presence_accumulator.get(key)
    # Use Asia/Manila timezone for timestamp
    import pytz
    tz = pytz.timezone("Asia/Manila")
    now_ts = datetime.datetime.now(tz).timestamp()
    if not acc:
        acc = {"seconds": 0.0, "last_ts": now_ts, "faculty_id": detected_faculty_id}
        _presence_accumulator[key] = acc
    # Add elapsed since last tick, capped to a reasonable frame gap
    delta = max(0.0, min(5.0, now_ts - acc["last_ts"]))
    acc["seconds"] += delta
    acc["last_ts"] = now_ts

    # Don't post attendance immediately when 30 minutes is reached
    # Attendance will be posted when the schedule ends via check_schedule_end_and_mark_absent()
    # Just accumulate the presence time for later evaluation
    print(f"DEBUG: Accumulated {acc['seconds']} seconds for faculty {detected_faculty_id} in room {room_no}")

def check_schedule_end_and_mark_absent():
    """Check if any schedules have ended and mark absent if 30min threshold not met."""
    import pytz
    tz = pytz.timezone("Asia/Manila")
    now = datetime.datetime.now(tz)
    now_min = now.hour * 60 + now.minute
    date_str = now.strftime("%Y-%m-%d")
    
    for room_no, schedules in _schedules_cache.items():
        for sched in schedules:
            end_time = _to_minutes(sched.get("time_out"))
            if end_time is None:
                continue
            
            # Check if schedule just ended (within last 5 minutes)
            if end_time <= now_min <= end_time + 5:
                faculty_id = sched.get("faculty_id")
                load_id = sched.get("teaching_load_id")
                key = (room_no, load_id)
                acc = _presence_accumulator.get(key)
                
                # Check leave/pass slip status first
                faculty_status = check_faculty_status(
                    faculty_id, 
                    date_str, 
                    sched.get("time_in"), 
                    sched.get("time_out")
                )
                
                # Determine attendance status based on presence and leave/pass slip status
                if faculty_status:
                    # Faculty is on leave or has pass slip
                    record_status = "Absent"
                    record_remarks = faculty_status
                elif not acc or acc["seconds"] < PRESENCE_THRESHOLD:
                    # Not enough presence accumulated
                    record_status = "Absent"
                    record_remarks = "Absent"
                    accumulated_time = acc["seconds"] if acc else 0
                    print(f"DEBUG: Faculty only accumulated {accumulated_time} seconds (need {PRESENCE_THRESHOLD})")
                else:
                    # Sufficient presence accumulated (≥30 minutes)
                    # Check if late threshold was reached (not recognized in first 15 minutes)
                    late_info = _late_tracking.get(key, {})
                    late_threshold_reached = late_info.get("late_threshold_reached", False)
                    print(f"DEBUG: Faculty accumulated {acc['seconds']} seconds. Late threshold reached: {late_threshold_reached}")
                    
                    if late_threshold_reached:
                        record_status = "Late"
                        record_remarks = "Late"
                        print(f"DEBUG: Faculty accumulated {acc['seconds']} seconds but was late (not recognized in first 15 min)")
                    else:
                        record_status = "Present"
                        record_remarks = "Present"
                        print(f"DEBUG: Faculty accumulated {acc['seconds']} seconds and was on time")
                
                # Ensure remarks is never empty or None
                if not record_remarks or record_remarks.strip() == "":
                    record_remarks = record_status  # Use the same as status
                    print(f"DEBUG: WARNING - Empty remarks detected, setting to '{record_remarks}'")
                
                print(f"DEBUG: Schedule ended - Status: {record_status}, Remarks: {record_remarks}")
                
                # Find camera for this room
                camera_id = None
                for cam_id, cam_data in _cameras_cache.items():
                    if str(cam_data.get("room_no")) == str(room_no):
                        camera_id = cam_id
                        break
                
                if camera_id:
                    # Get time fields - check if there was any recognition at all
                    # If there was some recognition but not enough time, use actual times
                    # If no recognition at all, use N/A
                    has_recognition = False
                    if acc and acc["seconds"] > 0:
                        # There was some recognition, use actual times
                        has_recognition = True
                    
                    time_fields = get_attendance_time_fields(camera_id, faculty_id, load_id, was_detected=has_recognition)
                    
                    print(f"DEBUG: Before payload creation - record_status='{record_status}', record_remarks='{record_remarks}'")
                    
                    # Final validation before payload creation
                    if not record_remarks or record_remarks.strip() == "":
                        record_remarks = record_status  # Use the same as status
                        print(f"DEBUG: WARNING - Empty remarks detected before payload creation, setting to '{record_remarks}'")
                    
                    payload = {
                        "faculty_id": int(faculty_id),
                        "teaching_load_id": load_id,
                        "camera_id": camera_id,
                        "record_status": record_status,
                        "record_remarks": record_remarks,
                        "teaching_load_class_section": sched.get("teaching_load_class_section"),
                        **time_fields
                    }
                    
                    print(f"DEBUG: Posting attendance for schedule end - Status: {record_status}, Remarks: {record_remarks}")
                    print(f"DEBUG: Time fields: {time_fields}")
                    print(f"DEBUG: Full payload: {payload}")
                    print(f"DEBUG: Payload record_remarks type: {type(payload['record_remarks'])}, value: '{payload['record_remarks']}'")
                    
                    threading.Thread(target=_post_attendance_dedup, args=(payload,), daemon=True).start()
                
                # Clean up accumulator
                if key in _presence_accumulator:
                    del _presence_accumulator[key]

def fetch_faculty_embeddings():
    try:
        headers = get_request_headers()
        r = requests.get(FACULTY_EMBEDDINGS_ENDPOINT, headers=headers, timeout=10)
        r.raise_for_status()
        data = r.json()
        _faculty_embeddings.clear()
        _faculty_data_cache.clear()  # Clear and rebuild faculty data cache
        
        for f in data:
            fid = int(f["faculty_id"])
            
            # Cache faculty data for name lookup
            # The API returns faculty_name as concatenated string, so we need to handle it differently
            faculty_name = f.get("faculty_name", "")
            if faculty_name:
                # Split the concatenated name back into first and last name
                name_parts = faculty_name.strip().split(" ", 1)
                faculty_fname = name_parts[0] if len(name_parts) > 0 else ""
                faculty_lname = name_parts[1] if len(name_parts) > 1 else ""
            else:
                faculty_fname = ""
                faculty_lname = ""
            
            _faculty_data_cache[fid] = {
                "faculty_fname": faculty_fname,
                "faculty_lname": faculty_lname,
                "faculty_id": fid
            }
            
            print(f"DEBUG: Cached faculty {fid}: {faculty_fname} {faculty_lname}")
            
            emb = f.get("faculty_face_embedding")
            if not emb:
                continue
            try:
                arr_list = json.loads(emb) if isinstance(emb, str) else emb
                emb_arrays = [np.array(e) for e in arr_list] if isinstance(arr_list[0], list) else [np.array(arr_list)]
                
                # Check if embeddings are compatible with current InsightFace format (512-dim)
                if emb_arrays and len(emb_arrays[0]) != 512:
                    print(f"⚠️  Faculty {fid} has incompatible embeddings ({len(emb_arrays[0])} dim). Skipping...")
                    continue
                
                _faculty_embeddings[fid] = emb_arrays
            except Exception as e:
                print("embedding parse error:", fid, e)
        print("Loaded faculty embeddings:", list(_faculty_embeddings.keys()))
        print("Loaded faculty data cache:", list(_faculty_data_cache.keys()))
        return data
    except Exception as e:
        print("fetch_faculty_embeddings error:", e)
        return []

def get_faculty_name(faculty_id):
    """Get faculty full name by ID using cached data."""
    try:
        if not faculty_id or faculty_id == "Unknown" or faculty_id == "unknown_face":
            return "Unknown"
        
        # Convert to int for lookup
        try:
            faculty_id_int = int(faculty_id)
        except (ValueError, TypeError):
            return f"Faculty {faculty_id}"
        
        # Check cached faculty data first
        print(f"DEBUG: Looking for faculty {faculty_id_int} in cache. Available: {list(_faculty_data_cache.keys())}")
        if faculty_id_int in _faculty_data_cache:
            faculty_data = _faculty_data_cache[faculty_id_int]
            first_name = faculty_data.get("faculty_fname", "").strip()
            last_name = faculty_data.get("faculty_lname", "").strip()
            print(f"DEBUG: Found faculty data: {faculty_data}")
            print(f"DEBUG: First name: '{first_name}', Last name: '{last_name}'")
            
            if first_name and last_name:
                result = f"{first_name} {last_name}"
                print(f"DEBUG: Returning full name: {result}")
                return result
            elif first_name:
                print(f"DEBUG: Returning first name only: {first_name}")
                return first_name
            elif last_name:
                print(f"DEBUG: Returning last name only: {last_name}")
                return last_name
            else:
                print(f"DEBUG: No names found, returning Faculty {faculty_id}")
                return f"Faculty {faculty_id}"
        
        # If not in cache, try to refresh faculty data
        print(f"Faculty {faculty_id} not in cache, refreshing faculty data...")
        try:
            fetch_faculty_embeddings()  # This will update the cache
            if faculty_id_int in _faculty_data_cache:
                faculty_data = _faculty_data_cache[faculty_id_int]
                first_name = faculty_data.get("faculty_fname", "").strip()
                last_name = faculty_data.get("faculty_lname", "").strip()
                
                if first_name and last_name:
                    return f"{first_name} {last_name}"
                elif first_name:
                    return first_name
                elif last_name:
                    return last_name
        except Exception as e:
            print(f"Error refreshing faculty data for ID {faculty_id}: {e}")
        
        # Final fallback
        return f"Faculty {faculty_id}"
    except Exception as e:
        print(f"Error getting faculty name for ID {faculty_id}: {e}")
        return f"Faculty {faculty_id}" if faculty_id else "Unknown"

# -------------------
# Face tracking history cleanup
# -------------------
def cleanup_face_tracking_history():
	"""Clean up old face tracking history to prevent memory leaks."""
	try:
		import time
		current_time = time.time()
		cleanup_threshold = 300  # 5 minutes
		
		keys_to_remove = []
		for key, history in _face_tracking_history.items():
			# Check if this tracking entry is old and has no recent activity
			if hasattr(history, 'last_activity'):
				if current_time - history.get('last_activity', 0) > cleanup_threshold:
					keys_to_remove.append(key)
			elif len(history.get('positions', [])) == 0:
				# Remove empty tracking entries
				keys_to_remove.append(key)
		
		for key in keys_to_remove:
			del _face_tracking_history[key]
		
		if keys_to_remove:
			print(f"Cleaned up {len(keys_to_remove)} old face tracking entries")
	except Exception as e:
		print(f"Error cleaning up face tracking history: {e}")

def update_persistent_bounding_box(camera_id, face_location, faculty_id, is_scheduled, presence_info, faculty_name=None):
	"""Update persistent bounding box for a camera. Supports multiple faces per camera."""
	try:
		import time
		current_time = time.time()
		
		# Initialize camera entry if not exists
		if camera_id not in _persistent_bounding_boxes:
			_persistent_bounding_boxes[camera_id] = {}
		
		# Create a unique key for this face (faculty_id + face position)
		# Use faculty_id if available, otherwise use face position as identifier
		if faculty_id and faculty_id != "Unknown" and faculty_id != "unknown_face":
			face_key = f"faculty_{faculty_id}"
		else:
			# For unknown faces, use face position as identifier with tolerance
			top, right, bottom, left = face_location
			center_x = (left + right) // 2
			center_y = (top + bottom) // 2
			# Round to nearest 50 pixels to group nearby faces
			rounded_x = (center_x // 50) * 50
			rounded_y = (center_y // 50) * 50
			face_key = f"unknown_{rounded_x}_{rounded_y}"
		
		# Get existing bounding box for this specific face if it exists
		existing_bbox = _persistent_bounding_boxes[camera_id].get(face_key, {})
		
		# Calculate confidence based on detection consistency
		confidence = 1.0  # Start with full confidence for new detection
		if existing_bbox:
			# If we have an existing bounding box, maintain high confidence
			time_since_last = current_time - existing_bbox.get("last_seen", current_time)
			if time_since_last < 2.0:  # Less than 2 seconds since last detection
				confidence = min(1.0, existing_bbox.get("confidence", 0.5) + 0.2)
			else:
				confidence = 0.9  # High confidence even for gaps (face is still there)
		
		# Update or create bounding box entry for this specific face
		_persistent_bounding_boxes[camera_id][face_key] = {
			"face_location": face_location,
			"faculty_id": faculty_id,
			"is_scheduled": is_scheduled,
			"presence_info": presence_info,
			"faculty_name": faculty_name,
			"last_seen": current_time,
			"confidence": confidence,
			"detection_count": existing_bbox.get("detection_count", 0) + 1,
			"face_key": face_key
		}
		
		print(f"DEBUG: Updated persistent bounding box for camera {camera_id}, face_key: {face_key}")
		
	except Exception as e:
		print(f"Error updating persistent bounding box: {e}")

def get_persistent_bounding_boxes(camera_id):
	"""Get all persistent bounding boxes for a camera. Returns all faces detected."""
	try:
		import time
		current_time = time.time()
		
		if camera_id not in _persistent_bounding_boxes:
			return []
		
		# Get all faces for this camera
		camera_faces = _persistent_bounding_boxes[camera_id]
		active_faces = []
		faces_to_remove = []
		
		# Check each face for timeout
		for face_key, bbox_data in camera_faces.items():
			time_since_last = current_time - bbox_data.get("last_seen", 0)
			
			# Remove bounding box if face has been gone for a shorter time (3 seconds)
			# This ensures bounding boxes disappear quickly when faces move away
			extended_timeout = 3.0  # 3 seconds - remove if face is gone
			
			# Check if bounding box has timed out (face completely gone)
			if time_since_last > extended_timeout:
				# Face has been gone for a very long time, mark for removal
				faces_to_remove.append(face_key)
			else:
				# Face is still active, add to active faces
				active_faces.append(bbox_data)
		
		# Remove timed out faces
		for face_key in faces_to_remove:
			del camera_faces[face_key]
			print(f"DEBUG: Removed timed out face: {face_key}")
		
		print(f"DEBUG: Camera {camera_id} has {len(active_faces)} active faces")
		return active_faces
		
	except Exception as e:
		print(f"Error getting persistent bounding boxes: {e}")
		return []

def cleanup_persistent_bounding_boxes():
	"""Clean up old persistent bounding boxes only when faces are truly gone."""
	try:
		import time
		current_time = time.time()
		cleanup_timeout = 10.0  # 10 seconds - clean up if face is completely gone
		
		cameras_to_remove = []
		for camera_id, camera_faces in _persistent_bounding_boxes.items():
			faces_to_remove = []
			
			# Check each face in this camera
			for face_key, bbox_data in camera_faces.items():
				if current_time - bbox_data.get("last_seen", 0) > cleanup_timeout:
					faces_to_remove.append(face_key)
			
			# Remove timed out faces
			for face_key in faces_to_remove:
				del camera_faces[face_key]
			
			# If no faces left for this camera, mark camera for removal
			if not camera_faces:
				cameras_to_remove.append(camera_id)
		
		# Remove empty cameras
		for camera_id in cameras_to_remove:
			del _persistent_bounding_boxes[camera_id]
		
		if cameras_to_remove:
			print(f"Cleaned up {len(cameras_to_remove)} empty cameras (all faces gone for >60s)")
	except Exception as e:
		print(f"Error cleaning up persistent bounding boxes: {e}")

def add_recognition_log(camera_id, faculty_id, faculty_name, status, distance, teaching_load_id=None):
	"""Add a recognition log entry for multiple face tracking."""
	try:
		import time
		import pytz
		
		if camera_id not in _recognition_logs:
			_recognition_logs[camera_id] = []
		
		# Get current time in Asia/Manila timezone
		tz = pytz.timezone("Asia/Manila")
		now = datetime.datetime.now(tz)
		
		# Ensure we have the full faculty name
		full_faculty_name = faculty_name
		if faculty_id and faculty_id != "Unknown" and faculty_id != "unknown_face":
			# Try to get the full name from the faculty name function
			print(f"Getting faculty name for ID: {faculty_id}")
			full_name = get_faculty_name(faculty_id)
			print(f"Retrieved faculty name: {full_name}")
			if full_name and full_name != f"Faculty {faculty_id}":
				full_faculty_name = full_name
			elif faculty_name and faculty_name != "Unknown":
				full_faculty_name = faculty_name
			else:
				full_faculty_name = f"Faculty {faculty_id}"
		
		# Create log entry
		log_entry = {
			"timestamp": now.isoformat(),
			"faculty_id": faculty_id,
			"faculty_name": full_faculty_name,
			"status": status,
			"distance": distance,
			"teaching_load_id": teaching_load_id,
			"camera_id": camera_id
		}
		
		# Add to logs (keep last 50 entries per camera to prevent memory issues)
		_recognition_logs[camera_id].append(log_entry)
		if len(_recognition_logs[camera_id]) > 50:
			_recognition_logs[camera_id] = _recognition_logs[camera_id][-50:]
		
		print(f"Added recognition log for camera {camera_id}: {full_faculty_name} ({status})")
		
	except Exception as e:
		print(f"Error adding recognition log: {e}")

def get_recognition_logs(camera_id=None, limit=20):
	"""Get recognition logs for a specific camera or all cameras."""
	try:
		if camera_id:
			# Return logs for specific camera
			logs = _recognition_logs.get(camera_id, [])
			return logs[-limit:] if limit else logs
		else:
			# Return logs for all cameras, sorted by timestamp
			all_logs = []
			for cam_id, logs in _recognition_logs.items():
				for log in logs:
					log['camera_id'] = cam_id
					all_logs.append(log)
			
			# Sort by timestamp (newest first)
			all_logs.sort(key=lambda x: x['timestamp'], reverse=True)
			return all_logs[:limit] if limit else all_logs
		
	except Exception as e:
		print(f"Error getting recognition logs: {e}")
		return []

def cleanup_old_recognition_logs():
	"""Clean up old recognition logs to prevent memory buildup."""
	try:
		import time
		import pytz
		
		tz = pytz.timezone("Asia/Manila")
		current_time = datetime.datetime.now(tz)
		cutoff_time = current_time - datetime.timedelta(hours=1)  # Keep logs for 1 hour
		
		for camera_id in list(_recognition_logs.keys()):
			# Filter out old logs
			_recognition_logs[camera_id] = [
				log for log in _recognition_logs[camera_id]
				if datetime.datetime.fromisoformat(log['timestamp'].replace('Z', '+00:00')) > cutoff_time
			]
			
			# Remove empty camera entries
			if not _recognition_logs[camera_id]:
				del _recognition_logs[camera_id]
		
	except Exception as e:
		print(f"Error cleaning up recognition logs: {e}")

# -------------------
# Simple frame processing
# -------------------

# -------------------
# face recognition processing
# -------------------
def detect_faces_optimized(frame):
	"""Optimized face detection using InsightFace's built-in RetinaFace."""
	try:
		if face_app is None:
			print("Face models not initialized")
			return [], []
		
		# Convert BGR to RGB for InsightFace
		rgb_frame = cv2.cvtColor(frame, cv2.COLOR_BGR2RGB)
		
		# Use InsightFace's built-in RetinaFace for detection and recognition
		faces = face_app.get(rgb_frame)
		
		if not faces:
			return [], []
		
		face_locations = []
		face_encodings = []
		
		# Get frame dimensions
		frame_height, frame_width = frame.shape[:2]
		
		# Process each detected face
		for face in faces:
			# Get bounding box coordinates
			bbox = face.bbox
			x1, y1, x2, y2 = bbox[0], bbox[1], bbox[2], bbox[3]
			
			# Debug logging for raw coordinates
			print(f"DEBUG: Raw InsightFace bbox: {bbox}")
			print(f"DEBUG: Raw coordinates - x1:{x1}, y1:{y1}, x2:{x2}, y2:{y2}")
			
			# Check if coordinates are normalized (0-1 range) or pixel coordinates
			if x1 <= 1.0 and y1 <= 1.0 and x2 <= 1.0 and y2 <= 1.0:
				# Convert normalized coordinates to pixel coordinates
				x1 = int(x1 * frame_width)
				y1 = int(y1 * frame_height)
				x2 = int(x2 * frame_width)
				y2 = int(y2 * frame_height)
				print(f"DEBUG: Converted from normalized to pixel coordinates")
			else:
				# Coordinates are already in pixel format, just convert to int
				x1, y1, x2, y2 = int(x1), int(y1), int(x2), int(y2)
				print(f"DEBUG: Using pixel coordinates directly")
			
			# Ensure coordinates are within frame bounds
			x1 = max(0, min(x1, frame_width - 1))
			y1 = max(0, min(y1, frame_height - 1))
			x2 = max(x1 + 1, min(x2, frame_width))
			y2 = max(y1 + 1, min(y2, frame_height))
			
			# Convert to (top, right, bottom, left) format for compatibility
			# InsightFace bbox format: [x_min, y_min, x_max, y_max] where (x_min,y_min) is top-left, (x_max,y_max) is bottom-right
			top, right, bottom, left = y1, x2, y2, x1
			
			# Debug logging for coordinate verification
			print(f"DEBUG: Final coordinates - x1:{x1}, y1:{y1}, x2:{x2}, y2:{y2}")
			print(f"DEBUG: Converted to (top, right, bottom, left): ({top}, {right}, {bottom}, {left})")
			print(f"DEBUG: Frame dimensions: {frame_width}x{frame_height}")
			print(f"DEBUG: Face size - width:{x2-x1}, height:{y2-y1}")
			
			face_locations.append((top, right, bottom, left))
			
			# Get the embedding directly from InsightFace
			embedding = face.embedding
			face_encodings.append(embedding)
		
		return face_locations, face_encodings 
	except Exception as e:
		print(f"Error in face detection: {e}")
		return [], []

def match_faculty_optimized(face_encoding):
	"""Optimized faculty matching with early exit using cosine similarity."""
	try:
		best_match = None
		best_similarity = -1.0  # Start with lowest similarity
		
		# Normalize the input encoding
		face_encoding_norm = face_encoding / np.linalg.norm(face_encoding)
		
		for faculty_id, faculty_embeddings in _faculty_embeddings.items():
			if not faculty_embeddings:
				continue
			
			# Calculate cosine similarity with all embeddings for this faculty
			max_similarity = -1.0
			for faculty_embedding in faculty_embeddings:
				# Check embedding dimension compatibility
				if len(face_encoding) != len(faculty_embedding):
					print(f"Embedding dimension mismatch: face={len(face_encoding)}, faculty={len(faculty_embedding)}")
					# Skip this faculty if dimensions don't match
					continue
				
				# Normalize faculty embedding
				faculty_embedding_norm = faculty_embedding / np.linalg.norm(faculty_embedding)
				# Calculate cosine similarity
				similarity = np.dot(face_encoding_norm, faculty_embedding_norm)
				max_similarity = max(max_similarity, similarity)
			
			# Only proceed if we found a valid similarity
			if max_similarity > -1.0:
				# Convert similarity to distance (1 - similarity)
				distance = 1.0 - max_similarity
				
				# Check if this is a better match (lower distance = higher similarity)
				if distance < (1.0 - MATCH_THRESHOLD) and max_similarity > best_similarity:
					best_similarity = max_similarity
					best_match = faculty_id
					
					# Early exit if we find a very good match
					if max_similarity > 0.9:  # Very high similarity
						break
		
		# Convert similarity back to distance for compatibility
		# Handle inf values for JSON serialization
		if best_similarity > -1.0:
			best_distance = 1.0 - best_similarity
			# Ensure distance is finite for JSON serialization
			if not np.isfinite(best_distance):
				best_distance = 1.0
		else:
			best_distance = 1.0  # Use 1.0 instead of inf
		return best_match, best_distance
	except Exception as e:
		print(f"Error in faculty matching: {e}")
		return None, 1.0  # Return 1.0 instead of inf

def smooth_face_position(camera_id, face_location, faculty_id):
	"""Smooth face position to reduce jitter and improve tracking."""
	try:
		key = f"{camera_id}_{faculty_id}" if faculty_id else f"{camera_id}_unknown"
		
		# Initialize tracking history if not exists
		if key not in _face_tracking_history:
			_face_tracking_history[key] = {
				"positions": [],
				"max_history": 3,  # Reduced to 3 for more responsive tracking
				"last_valid_position": None
			}
		
		history = _face_tracking_history[key]
		top, right, bottom, left = face_location
		
		# Validate face location coordinates
		if not _is_valid_face_location(face_location):
			# If current position is invalid, use last valid position
			if history["last_valid_position"]:
				return history["last_valid_position"]
			else:
				return face_location
		
		# Add current position to history
		history["positions"].append((top, right, bottom, left))
		history["last_valid_position"] = face_location
		history["last_activity"] = time.time()  # Track last activity time
		
		# Keep only recent positions
		if len(history["positions"]) > history["max_history"]:
			history["positions"].pop(0)
		
		# Calculate smoothed position using weighted average with confidence
		if len(history["positions"]) >= 2:
			# Use weighted average (more recent positions get higher weight)
			weights = [i + 1 for i in range(len(history["positions"]))]
			total_weight = sum(weights)
			
			smoothed_top = sum(pos[0] * weight for pos, weight in zip(history["positions"], weights)) / total_weight
			smoothed_right = sum(pos[1] * weight for pos, weight in zip(history["positions"], weights)) / total_weight
			smoothed_bottom = sum(pos[2] * weight for pos, weight in zip(history["positions"], weights)) / total_weight
			smoothed_left = sum(pos[3] * weight for pos, weight in zip(history["positions"], weights)) / total_weight
			
			# Apply confidence-based smoothing (only smooth if change is reasonable)
			current_pos = face_location
			smoothed_location = (int(smoothed_top), int(smoothed_right), int(smoothed_bottom), int(smoothed_left))
			
			# Check if the smoothed position is too different from current (prevent large jumps)
			max_change = 50  # Maximum pixels change allowed
			top_diff = abs(smoothed_location[0] - current_pos[0])
			right_diff = abs(smoothed_location[1] - current_pos[1])
			bottom_diff = abs(smoothed_location[2] - current_pos[2])
			left_diff = abs(smoothed_location[3] - current_pos[3])
			
			if (top_diff < max_change and right_diff < max_change and 
				bottom_diff < max_change and left_diff < max_change and
				_is_valid_face_location(smoothed_location)):
				return smoothed_location
			else:
				# If change is too large or invalid, use current position
				return face_location
		else:
			# Not enough history, return original position
			return face_location
			
	except Exception as e:
		print(f"Error smoothing face position: {e}")
		return face_location

def _is_valid_face_location(face_location):
	"""Check if face location coordinates are valid."""
	try:
		top, right, bottom, left = face_location
		
		# Basic coordinate validation
		if not all(isinstance(coord, (int, float)) for coord in face_location):
			return False
		
		# Check for reasonable face dimensions
		if top >= bottom or left >= right:
			return False
		
		# Check for minimum face size
		face_width = right - left
		face_height = bottom - top
		if face_width < 20 or face_height < 20:
			return False
		
		# Check for maximum face size (reasonable limits)
		if face_width > 1000 or face_height > 1000:
			return False
		
		# Check for non-negative coordinates
		if any(coord < 0 for coord in face_location):
			return False
		
		return True
	except Exception:
		return False

def draw_stable_bounding_box(frame, face_location, faculty_id, is_scheduled, presence_info, faculty_name=None):
	"""Draw a stable bounding box that stays on the face without flickering."""
	try:
		# Validate face location first
		if not _is_valid_face_location(face_location):
			return
		
		top, right, bottom, left = face_location
		
		# Debug logging for drawing coordinates
		print(f"DEBUG: Drawing bounding box - Input coordinates: ({top}, {right}, {bottom}, {left})")
		print(f"DEBUG: Drawing bounding box - Faculty ID: {faculty_id}, Scheduled: {is_scheduled}")
		
		# Get frame dimensions
		frame_height, frame_width = frame.shape[:2]
		
		# Clamp coordinates to frame bounds with proper validation
		left = max(0, min(int(left), frame_width - 1))
		right = max(left + 1, min(int(right), frame_width))
		top = max(0, min(int(top), frame_height - 1))
		bottom = max(top + 1, min(int(bottom), frame_height))
		
		# Debug logging for clamped coordinates
		print(f"DEBUG: Drawing bounding box - Clamped coordinates: ({top}, {right}, {bottom}, {left})")
		print(f"DEBUG: Drawing bounding box - Frame dimensions: {frame_width}x{frame_height}")
		
		# Final validation - ensure we have a valid rectangle
		if top >= bottom or left >= right:
			print(f"DEBUG: Invalid rectangle - top:{top}, right:{right}, bottom:{bottom}, left:{left}")
			return
		
		# Ensure minimum size for visibility
		if (right - left) < 10 or (bottom - top) < 10:
			return
		
		# Set colors and thickness
		thickness = 2  # Slightly reduced for better performance
		
		if faculty_id:
			if is_scheduled:
				color = (0, 255, 0)  # Green for correct faculty
			else:
				color = (0, 165, 255)  # Orange for wrong faculty
			
			# Draw main bounding box with rounded corners for better visibility
			cv2.rectangle(frame, (left, top), (right, bottom), color, thickness)
			
			# Draw corner markers for better tracking
			corner_size = min(12, (right - left) // 4, (bottom - top) // 4)
			# Top-left
			cv2.line(frame, (left, top), (left + corner_size, top), color, thickness)
			cv2.line(frame, (left, top), (left, top + corner_size), color, thickness)
			# Top-right
			cv2.line(frame, (right, top), (right - corner_size, top), color, thickness)
			cv2.line(frame, (right, top), (right, top + corner_size), color, thickness)
			# Bottom-left
			cv2.line(frame, (left, bottom), (left + corner_size, bottom), color, thickness)
			cv2.line(frame, (left, bottom), (left, bottom - corner_size), color, thickness)
			# Bottom-right
			cv2.line(frame, (right, bottom), (right - corner_size, bottom), color, thickness)
			cv2.line(frame, (right, bottom), (right, bottom - corner_size), color, thickness)
			
			# Draw faculty name label with better positioning
			if faculty_name and faculty_name != "Unknown":
				label = faculty_name
			else:
				label = f"Faculty {faculty_id}"
			
			if is_scheduled:
				label += " (Scheduled)"
			else:
				label += " (Not Scheduled)"
			
			# Draw label with background
			font_scale = 0.5  # Slightly smaller for better fit
			font_thickness = 1
			(text_width, text_height), baseline = cv2.getTextSize(label, cv2.FONT_HERSHEY_SIMPLEX, font_scale, font_thickness)
			
			# Position text above the bounding box with better bounds checking
			text_x = max(5, min(left, frame_width - text_width - 5))
			text_y = max(text_height + 5, min(top - 5, frame_height - 5))
			
			# Ensure text fits within frame
			if text_x + text_width < frame_width and text_y - text_height > 0:
				# Draw text background
				cv2.rectangle(frame, (text_x - 2, text_y - text_height - 2), 
							 (text_x + text_width + 2, text_y + 2), (0, 0, 0), -1)
				
				# Draw text
				cv2.putText(frame, label, (text_x, text_y), 
						   cv2.FONT_HERSHEY_SIMPLEX, font_scale, color, font_thickness)
			
			# Draw presence info for scheduled faculty
			if presence_info and is_scheduled:
				accumulated_minutes = int(presence_info["seconds"] / 60)
				presence_text = f"Time: {accumulated_minutes}min / 30min"
				
				(presence_width, presence_height), _ = cv2.getTextSize(presence_text, cv2.FONT_HERSHEY_SIMPLEX, 0.4, 1)
				presence_x = max(5, min(left, frame_width - presence_width - 5))
				presence_y = min(frame_height - 5, bottom + 15)
				
				# Ensure presence text fits within frame
				if presence_x + presence_width < frame_width and presence_y - presence_height > 0:
					# Draw presence background
					cv2.rectangle(frame, (presence_x - 2, presence_y - presence_height - 2), 
								 (presence_x + presence_width + 2, presence_y + 2), (0, 0, 0), -1)
					
					# Draw presence text
					cv2.putText(frame, presence_text, (presence_x, presence_y), 
							   cv2.FONT_HERSHEY_SIMPLEX, 0.4, color, 1)
		else:
			# Draw bounding box for unknown face
			cv2.rectangle(frame, (left, top), (right, bottom), (0, 0, 255), thickness)  # Red
			
			# Draw corner markers
			corner_size = min(12, (right - left) // 4, (bottom - top) // 4)
			cv2.line(frame, (left, top), (left + corner_size, top), (0, 0, 255), thickness)
			cv2.line(frame, (left, top), (left, top + corner_size), (0, 0, 255), thickness)
			cv2.line(frame, (right, top), (right - corner_size, top), (0, 0, 255), thickness)
			cv2.line(frame, (right, top), (right, top + corner_size), (0, 0, 255), thickness)
			cv2.line(frame, (left, bottom), (left + corner_size, bottom), (0, 0, 255), thickness)
			cv2.line(frame, (left, bottom), (left, bottom - corner_size), (0, 0, 255), thickness)
			cv2.line(frame, (right, bottom), (right - corner_size, bottom), (0, 0, 255), thickness)
			cv2.line(frame, (right, bottom), (right, bottom - corner_size), (0, 0, 255), thickness)
			
			# Draw "Unknown" label with better positioning
			text_x = max(5, min(left, frame_width - 80))
			text_y = max(15, min(top - 5, frame_height - 5))
			
			# Ensure text fits within frame
			if text_x + 80 < frame_width and text_y - 15 > 0:
				# Draw text background
				cv2.rectangle(frame, (text_x - 2, text_y - 15), (text_x + 80, text_y + 2), (0, 0, 0), -1)
				
				# Draw text
				cv2.putText(frame, "Unknown", (text_x, text_y), 
						   cv2.FONT_HERSHEY_SIMPLEX, 0.5, (0, 0, 255), 1)
			
	except Exception as e:
		print(f"Error drawing stable bounding box: {e}")

def draw_face_overlay(frame, face_location, faculty_id, distance, is_scheduled, presence_info, faculty_name=None):
	"""Draw optimized face overlay with better tracking and frame bounds."""
	try:
		top, right, bottom, left = face_location
		
		# Get frame dimensions
		frame_height, frame_width = frame.shape[:2]
		
		# Ensure coordinates are within frame bounds with padding
		padding = 5  # Add small padding to prevent edge cases
		top = max(padding, min(top, frame_height - padding - 1))
		bottom = max(padding, min(bottom, frame_height - padding - 1))
		left = max(padding, min(left, frame_width - padding - 1))
		right = max(padding, min(right, frame_width - padding - 1))
		
		# Ensure valid rectangle with minimum size
		min_size = 20
		if bottom - top < min_size:
			center_y = (top + bottom) // 2
			top = max(padding, center_y - min_size // 2)
			bottom = min(frame_height - padding - 1, center_y + min_size // 2)
		
		if right - left < min_size:
			center_x = (left + right) // 2
			left = max(padding, center_x - min_size // 2)
			right = min(frame_width - padding - 1, center_x + min_size // 2)
		
		# Final validation
		if top >= bottom or left >= right:
			return None
			
		thickness = 3  # Increased thickness for better visibility
		
		if faculty_id:
			if is_scheduled:
				color = (0, 255, 0)  # Green for correct faculty
			else:
				color = (0, 165, 255)  # Orange for wrong faculty
			
			# Draw bounding box with rounded corners for better visibility
			cv2.rectangle(frame, (left, top), (right, bottom), color, thickness)
			
			# Add corner markers for better tracking
			corner_size = 10
			# Top-left corner
			cv2.line(frame, (left, top), (left + corner_size, top), color, thickness)
			cv2.line(frame, (left, top), (left, top + corner_size), color, thickness)
			# Top-right corner
			cv2.line(frame, (right, top), (right - corner_size, top), color, thickness)
			cv2.line(frame, (right, top), (right, top + corner_size), color, thickness)
			# Bottom-left corner
			cv2.line(frame, (left, bottom), (left + corner_size, bottom), color, thickness)
			cv2.line(frame, (left, bottom), (left, bottom - corner_size), color, thickness)
			# Bottom-right corner
			cv2.line(frame, (right, bottom), (right - corner_size, bottom), color, thickness)
			cv2.line(frame, (right, bottom), (right, bottom - corner_size), color, thickness)
			
			# Draw faculty info with background - use full name if available
			if faculty_name and faculty_name != "Unknown":
				label = faculty_name
			else:
				label = f"Faculty ID: {faculty_id}"
			
			if is_scheduled:
				label += " (Scheduled)"
			else:
				label += " (Not Scheduled)"
			
			# Calculate text size and position
			font_scale = 0.6
			font_thickness = 2
			(text_width, text_height), baseline = cv2.getTextSize(label, cv2.FONT_HERSHEY_SIMPLEX, font_scale, font_thickness)
			
			# Ensure text is within frame bounds
			text_x = max(5, min(left, frame_width - text_width - 5))
			text_y = max(text_height + 5, min(top - 10, frame_height - 5))
			
			# Draw text background
			cv2.rectangle(frame, (text_x - 2, text_y - text_height - 2), 
						 (text_x + text_width + 2, text_y + 2), (0, 0, 0), -1)
			
			# Draw text
			cv2.putText(frame, label, (text_x, text_y), 
					   cv2.FONT_HERSHEY_SIMPLEX, font_scale, color, font_thickness)
			
			# Draw presence accumulation info only for scheduled faculty
			if presence_info and is_scheduled:
				accumulated_minutes = int(presence_info["seconds"] / 60)
				
				timer_text = f"Accumulated: {accumulated_minutes}min / 30min"
				(timer_width, timer_height), _ = cv2.getTextSize(timer_text, cv2.FONT_HERSHEY_SIMPLEX, 0.5, 1)
				
				# Position timer text below the bounding box
				timer_x = max(5, min(left, frame_width - timer_width - 5))
				timer_y = min(frame_height - 5, bottom + 20)
				
				# Draw timer background
				cv2.rectangle(frame, (timer_x - 2, timer_y - timer_height - 2), 
							 (timer_x + timer_width + 2, timer_y + 2), (0, 0, 0), -1)
				
				# Draw timer text
				cv2.putText(frame, timer_text, (timer_x, timer_y), 
						   cv2.FONT_HERSHEY_SIMPLEX, 0.5, color, 1)
				
				# Progress bar
				bar_width = min(200, right - left)
				bar_height = 10
				bar_x = max(5, min(left, frame_width - bar_width - 5))
				bar_y = min(frame_height - 15, bottom + 40)
				
				# Ensure progress bar is within frame bounds
				if bar_y + bar_height < frame_height:
					# Background bar
					cv2.rectangle(frame, (bar_x, bar_y), (bar_x + bar_width, bar_y + bar_height), (50, 50, 50), -1)
					
					# Progress bar
					progress = min(1.0, presence_info["seconds"] / PRESENCE_THRESHOLD)
					progress_width = int(bar_width * progress)
					cv2.rectangle(frame, (bar_x, bar_y), (bar_x + progress_width, bar_y + bar_height), color, -1)
		else:
			# Draw bounding box for unrecognized face
			cv2.rectangle(frame, (left, top), (right, bottom), (0, 0, 255), thickness)  # Red
			
			# Add corner markers
			corner_size = 10
			cv2.line(frame, (left, top), (left + corner_size, top), (0, 0, 255), thickness)
			cv2.line(frame, (left, top), (left, top + corner_size), (0, 0, 255), thickness)
			cv2.line(frame, (right, top), (right - corner_size, top), (0, 0, 255), thickness)
			cv2.line(frame, (right, top), (right, top + corner_size), (0, 0, 255), thickness)
			cv2.line(frame, (left, bottom), (left + corner_size, bottom), (0, 0, 255), thickness)
			cv2.line(frame, (left, bottom), (left, bottom - corner_size), (0, 0, 255), thickness)
			cv2.line(frame, (right, bottom), (right - corner_size, bottom), (0, 0, 255), thickness)
			cv2.line(frame, (right, bottom), (right, bottom - corner_size), (0, 0, 255), thickness)
			
			# Draw "Unknown" text with background
			text_y = max(20, min(top - 10, frame_height - 5))
			text_x = max(5, min(left, frame_width - 100))
			
			# Draw text background
			cv2.rectangle(frame, (text_x - 2, text_y - 20), (text_x + 80, text_y + 2), (0, 0, 0), -1)
			
			# Draw text
			cv2.putText(frame, "Unknown", (text_x, text_y), 
					   cv2.FONT_HERSHEY_SIMPLEX, 0.6, (0, 0, 255), 2)
		
		return (left, top, right, bottom, color if faculty_id else (0, 0, 255), 
				label if faculty_id else "Unknown")
	except Exception as e:
		print(f"Error drawing face overlay: {e}")
		return None

def log_recognition_event(camera_id, faculty_id, faculty_name, status, distance, teaching_load_id=None):
	"""Log recognition event to database."""
	try:
		cam = _cameras_cache.get(camera_id)
		if not cam:
			return
		
		# Get detailed information from Laravel API
		import requests
		import threading
		
		def post_log():
			try:
				# Only try to get detailed information if we have a valid teaching_load_id
				# and the faculty_id is not None/Unknown
				if teaching_load_id and faculty_id and faculty_id != "Unknown" and faculty_id != "unknown_face":
					# Get detailed information from teaching load and related data
					teaching_load_url = f"{API_BASE}/teaching-load-details"
					payload = {
						"teaching_load_id": teaching_load_id,
						"faculty_id": faculty_id,
						"camera_id": camera_id
					}
					
					# Try to get detailed information
					headers = get_request_headers()
					details_response = requests.post(teaching_load_url, json=payload, headers=headers, timeout=3)
					if details_response.status_code == 200:
						details = details_response.json()
						room_name = details.get("room_name", cam.get("room_name", f"Room {cam.get('room_no')}"))
						building_no = details.get("building_no", cam.get("room_building_no", "Unknown"))
						camera_name = details.get("camera_name", f"Camera {camera_id}")
						faculty_full_name = details.get("faculty_full_name", faculty_name or "Unknown")
					else:
						# Fallback to basic information
						room_name = cam.get("room_name", f"Room {cam.get('room_no')}")
						building_no = cam.get("room_building_no", "Unknown")
						camera_name = f"Camera {camera_id}"
						# Always try to get faculty full name, even for unscheduled faculty
						if faculty_id and faculty_id != "Unknown" and faculty_id != "unknown_face":
							faculty_full_name = get_faculty_name(faculty_id)
						else:
							faculty_full_name = faculty_name or "Unknown"
				else:
					# For unknown faces or unscheduled faculty, use basic information
					room_name = cam.get("room_name", f"Room {cam.get('room_no')}")
					building_no = cam.get("room_building_no", "Unknown")
					camera_name = f"Camera {camera_id}"
					# Always try to get faculty full name, even for unscheduled faculty
					if faculty_id and faculty_id != "Unknown" and faculty_id != "unknown_face":
						faculty_full_name = get_faculty_name(faculty_id)
					else:
						faculty_full_name = faculty_name or "Unknown"
				
				# Prepare log data with Philippine timezone
				import pytz
				tz = pytz.timezone("Asia/Manila")
				now_ph = datetime.datetime.now(tz)
				
				log_data = {
					"recognition_time": now_ph.strftime("%Y-%m-%d %H:%M:%S"),
					"camera_name": camera_name,
					"room_name": room_name,
					"building_no": building_no,
					"faculty_name": faculty_full_name,
					"status": status,
					"distance": distance,
					"faculty_id": faculty_id,
					"camera_id": camera_id,
					"teaching_load_id": teaching_load_id
				}
				
				# Post recognition log (use public endpoint, no API key needed)
				headers = {
					'Content-Type': 'application/json',
					'Accept': 'application/json'
				}
				response = requests.post(f"{API_BASE}/recognition-logs", json=log_data, headers=headers, timeout=5)
				if response.status_code not in [200, 201]:
					print(f"Failed to log recognition event: {response.status_code} - {response.text[:200] if hasattr(response, 'text') else 'No response'}")
			except Exception as e:
				print(f"Error posting recognition log: {e}")
		
		# Post asynchronously to avoid blocking recognition
		threading.Thread(target=post_log, daemon=True).start()
		
	except Exception as e:
		print(f"Error in log_recognition_event: {e}")

def process_frame_for_recognition(frame, camera_id, scale_factor=1.0):
	"""Optimized frame processing for face recognition with persistent bounding boxes."""
	try:
		# Get camera and schedule info
		cam = _cameras_cache.get(camera_id)
		if not cam:
			return frame
		
		room_no = str(cam.get("room_no"))
		sched = get_current_schedule_for_room(room_no)
		
		# Get presence accumulator for this room/schedule
		presence_info = None
		if sched:
			load_id = int(sched.get("teaching_load_id"))
			key = (room_no, load_id)
			presence_info = _presence_accumulator.get(key)
		
		# Detect faces with optimized function
		face_locations, face_encodings = detect_faces_optimized(frame)
		
		# Track which faces were detected in this frame
		current_frame_faces = set()
		
		# Process detected faces and update persistent bounding boxes
		detected_faces = []
		for face_encoding, face_location in zip(face_encodings, face_locations):
			# Match faculty with optimized function
			best_match, best_distance = match_faculty_optimized(face_encoding)
			
			# Check if this is the scheduled faculty
			is_scheduled = sched and best_match and int(best_match) == int(sched.get("faculty_id"))
			
			# Get faculty full name for display
			faculty_full_name = get_faculty_name(best_match)
			
			# Record presence tick for scheduled faculty
			if is_scheduled:
				record_presence_tick(camera_id, best_match)
			
			# Log recognition event
			faculty_name = faculty_full_name if best_match else "Unknown"
			status = "recognized" if best_match else "unknown_face"
			
			# Only use teaching_load_id if the detected faculty matches the scheduled faculty
			# This prevents logging the scheduled faculty's info for unknown faces or wrong faculty
			teaching_load_id = None
			if sched and best_match and is_scheduled:
				teaching_load_id = sched.get("teaching_load_id")
			
			# Add to recognition logs for multiple face tracking
			add_recognition_log(
				camera_id=camera_id,
				faculty_id=best_match,
				faculty_name=faculty_name,
				status=status,
				distance=best_distance,
				teaching_load_id=teaching_load_id
			)
			
			# Also log to database (existing function)
			log_recognition_event(
				camera_id=camera_id,
				faculty_id=best_match,
				faculty_name=faculty_name,
				status=status,
				distance=best_distance,
				teaching_load_id=teaching_load_id
			)
			
			# Scale face location coordinates back to original frame size
			if scale_factor != 1.0:
				top, right, bottom, left = face_location
				# Ensure coordinates are within frame bounds
				scaled_face_location = (
					max(0, int(top / scale_factor)),
					max(0, int(right / scale_factor)),
					max(0, int(bottom / scale_factor)),
					max(0, int(left / scale_factor))
				)
				print(f"DEBUG: Coordinate scaling - Original: ({top}, {right}, {bottom}, {left})")
				print(f"DEBUG: Coordinate scaling - Scale factor: {scale_factor}")
				print(f"DEBUG: Coordinate scaling - Scaled: {scaled_face_location}")
			else:
				scaled_face_location = face_location
				print(f"DEBUG: No scaling needed - Using original coordinates: {face_location}")
			
			# Apply smoothing to reduce jitter
			smoothed_location = smooth_face_position(camera_id, scaled_face_location, best_match)
			
			# Update persistent bounding box
			update_persistent_bounding_box(
				camera_id, 
				smoothed_location, 
				best_match, 
				is_scheduled, 
				presence_info, 
				faculty_full_name
			)
			
			# Track this face as detected in current frame
			if best_match and best_match != "Unknown" and best_match != "unknown_face":
				current_frame_faces.add(f"faculty_{best_match}")
			else:
				# For unknown faces, use rounded position
				top, right, bottom, left = smoothed_location
				center_x = (left + right) // 2
				center_y = (top + bottom) // 2
				rounded_x = (center_x // 50) * 50
				rounded_y = (center_y // 50) * 50
				current_frame_faces.add(f"unknown_{rounded_x}_{rounded_y}")
			
			# Track detected faces for recognition results
			detected_faces.append({
				"faculty_id": best_match,
				"distance": best_distance,
				"is_scheduled": is_scheduled
			})
		
		# Clean up faces that were not detected in current frame
		if camera_id in _persistent_bounding_boxes:
			faces_to_remove = []
			for face_key in _persistent_bounding_boxes[camera_id].keys():
				if face_key not in current_frame_faces:
					faces_to_remove.append(face_key)
			
			for face_key in faces_to_remove:
				del _persistent_bounding_boxes[camera_id][face_key]
				print(f"DEBUG: Removed face not detected in current frame: {face_key}")
		
		# Draw all persistent bounding boxes (including those from previous frames)
		persistent_boxes = get_persistent_bounding_boxes(camera_id)
		for bbox_data in persistent_boxes:
			draw_stable_bounding_box(
				frame,
				bbox_data["face_location"],
				bbox_data["faculty_id"],
				bbox_data["is_scheduled"],
				bbox_data["presence_info"],
				bbox_data["faculty_name"]
			)
		
		# Update recognition results with latest detection
		if detected_faces:
			# Use the first detected face for recognition results
			first_face = detected_faces[0]
			import pytz
			tz = pytz.timezone("Asia/Manila")
			now = datetime.datetime.now(tz)
			_recognition_results[camera_id].update({
				"last_seen": now.isoformat(),
				"faculty_id": first_face["faculty_id"],
				"status": "recognized" if first_face["faculty_id"] else "unknown_face",
				"distance": first_face["distance"] if first_face["faculty_id"] else None,
				"teaching_load_id": sched.get("teaching_load_id") if sched else None,
				"timestamp": now.isoformat()
			})
		
		return frame
		
	except Exception as e:
		print(f"Error in face recognition processing: {e}")
		# Always return the original frame if processing fails
		return frame

# -------------------
# RTSP VideoTrack
# -------------------
class RTSPVideoTrack(VideoStreamTrack):
    def __init__(self, camera_id, camera_live_feed, room_no, connection_id=None):
        super().__init__()
        self.camera_id = int(camera_id)
        self.camera_live_feed = camera_live_feed
        self.room_no = str(room_no) if room_no else None
        self.connection_id = connection_id or f"{camera_id}_{int(time.time())}"
        self._last_recognition_time = 0
        self._cap = None  # Initialize video capture attribute

        # Initialize recognition results only once per camera
        if self.camera_id not in _recognition_results:
            _recognition_results[self.camera_id] = {
                "camera_id": self.camera_id,
                "room_no": self.room_no,
                "last_seen": None,
                "faculty_id": None,
                "status": "idle",
                "distance": None,
                "teaching_load_id": None,
                "timestamp": None
            }
        
        # Ensure shared capture exists
        get_or_create_shared_capture(self.camera_id, self.camera_live_feed)

    def _open(self):
        if self._cap is None or not self._cap.isOpened():
            # Prefer FFmpeg backend for better RTSP control (-1 lets OpenCV choose;  cv2.CAP_FFMPEG is more explicit)
            self._cap = cv2.VideoCapture(self.camera_live_feed, cv2.CAP_FFMPEG)
            try:
                # Set buffer size for smooth video
                self._cap.set(cv2.CAP_PROP_BUFFERSIZE, FRAME_BUFFER_SIZE)
                # Try to request a modest FPS from source
                self._cap.set(cv2.CAP_PROP_FPS, VIDEO_FPS)
                # Let camera use its native resolution for best quality
            except Exception as e:
                print(f"Camera {self.camera_id}: Error setting camera properties: {e}")

    def _read_frame(self):
        # Try to get shared frame first
        shared_frame = get_shared_frame(self.camera_id)
        if shared_frame is not None:
            return shared_frame
        
        # Fallback to direct capture if no shared frame available
        self._open()
        if not self._cap or not self._cap.isOpened():
            return None
        
        # Simple frame reading with minimal draining
        ret, frame = self._cap.read()
        if ret:
            # Drain only 1 frame to reduce lag
            ret2, frame2 = self._cap.read()
            if ret2:
                frame = frame2
        if not ret:
            return None
        
        # Process frame for face recognition at intervals
        current_time = time.time()
        if current_time - self._last_recognition_time >= RECOGNITION_INTERVAL:
            # Simple processing - only when needed
            should_process = self._should_process_frame()
            
            if should_process:
                try:
                    # Process on original frame without scaling to avoid coordinate issues
                    processed_frame = process_frame_for_recognition(frame, self.camera_id, 1.0)
                    if processed_frame is not None:
                        frame = processed_frame
                    
                    # Update shared frame for other connections
                    update_shared_frame(self.camera_id, frame)
                except Exception as e:
                    print(f"Error in frame processing for camera {self.camera_id}: {e}")
                    # Continue with original frame if processing fails
            
            self._last_recognition_time = current_time
        # Bounding boxes are drawn directly during face recognition processing
        
        return frame
    
    def _should_process_frame(self):
        """Simple conditional processing - always process for live feed."""
        try:
            # Always process frames to maintain live feed
            # Face recognition will only work when there's an active schedule
            return True
        except Exception as e:
            print(f"Error in conditional processing check: {e}")
            return True

    async def recv(self):
        from av import VideoFrame
        pts, time_base = await self.next_timestamp()
        loop = asyncio.get_event_loop()
        frame = await loop.run_in_executor(None, self._read_frame)
        if frame is None:
            frame = np.zeros((480, 640, 3), dtype=np.uint8)
        video_frame = VideoFrame.from_ndarray(frame, format="bgr24")
        video_frame.pts = pts
        video_frame.time_base = time_base
        return video_frame

# -------------------
# Time tracking helper functions
# -------------------
def get_attendance_time_fields(camera_id: int, faculty_id: int, teaching_load_id: int, was_detected: bool = False):
    """Get appropriate time fields based on whether instructor was detected."""
    print(f"DEBUG: get_attendance_time_fields called with was_detected={was_detected}")
    
    # Always check if we have actual recognition data first
    recognition_times = get_recognition_times(camera_id, faculty_id, teaching_load_id)
    print(f"DEBUG: Recognition times: {recognition_times}")
    
    # Check if we actually have recognition data (time_in is not None and not empty)
    has_actual_recognition = (
        recognition_times.get("time_in") and 
        recognition_times["time_in"] is not None and 
        recognition_times["time_in"] != "N/A" and
        recognition_times["time_in"] != ""
    )
    
    # If was_detected=True, we expect to have recognition data
    # If was_detected=False, we might still have some recognition data (insufficient time)
    if has_actual_recognition:
        print(f"DEBUG: Using actual recognition times: {recognition_times['time_in']}")
        return {
            "record_time_in": recognition_times["time_in"],
            "record_time_out": recognition_times["time_out"] or "N/A",
            "time_duration_seconds": recognition_times["total_duration"] or 0,
            "time_in_snapshot": recognition_times.get("time_in_snapshot"),
            "time_out_snapshot": recognition_times.get("time_out_snapshot")
        }
    else:
        # No actual recognition data available, use N/A
        print(f"DEBUG: No recognition data, using N/A")
        return {
            "record_time_in": "N/A",
            "record_time_out": "N/A", 
            "time_duration_seconds": 0,
            "time_in_snapshot": None,
            "time_out_snapshot": None
        }

# -------------------
# Attendance dedup
# -------------------
def _post_attendance_dedup(payload):
    try:
        # Validate payload before processing
        if "record_remarks" not in payload or not payload["record_remarks"] or payload["record_remarks"].strip() == "":
            # Use the record_status as the default remarks if remarks are empty
            default_remarks = payload.get("record_status", "Absent")
            payload["record_remarks"] = default_remarks
            print(f"DEBUG: WARNING - Empty remarks in payload, setting to '{default_remarks}'")
        
        print(f"DEBUG: Final payload validation - record_remarks='{payload.get('record_remarks', 'MISSING')}'")
        
        # Check if attendance already exists
        headers = get_request_headers()
        check_response = requests.post(ATTENDANCE_CHECK_ENDPOINT, json={
            "faculty_id": payload["faculty_id"],
            "teaching_load_id": payload["teaching_load_id"]
        }, headers=headers, timeout=5)
        
        print(f"Check response status: {check_response.status_code}")
        if check_response.status_code == 200:
            check_data = check_response.json()
            exists = check_data.get("exists", False)
            print(f"Attendance already exists: {exists}")
            if exists:
                print("Attendance already recorded, skipping...")
                return
        
        print(f"Posting attendance with payload: {payload}")
        
        # Post attendance
        headers = get_request_headers()
        post_response = requests.post(ATTENDANCE_ENDPOINT, json=payload, headers=headers, timeout=6)
        print(f"Attendance posted with status: {post_response.status_code}")
        
        if post_response.status_code not in [200, 201]:
            print(f"Error response: {post_response.text}")
            print(f"Error status code: {post_response.status_code}")
        else:
            print("Attendance successfully posted to database")
            print(f"Response: {post_response.text}")
            
    except Exception as e:
        print(f"post_attendance_dedup error: {e}")
        import traceback
        traceback.print_exc()

# -------------------
# Background camera processing (runs without WebRTC)
# -------------------
def _process_camera_feed_background(camera_id: int, camera_feed: str, room_no: str):
    last_recognition_time = 0.0
    consecutive_failures = 0
    max_failures = 5
    frame_count = 0

    try:
        print(f"🎬 Starting background thread for camera {camera_id} - Feed: {camera_feed}")
        # Use shared capture system
        cap = get_or_create_shared_capture(camera_id, camera_feed)
        if not cap or not cap.isOpened():
            print(f"❌ Could not open shared capture for camera {camera_id}: {camera_feed}")
            return

        print(f"✅ Background processing started for camera {camera_id} (Room {room_no})")

        while True:
            ret, frame = cap.read()
            if not ret:
                consecutive_failures += 1
                if consecutive_failures >= max_failures:
                    print(f"❌ Camera {camera_id} failed {max_failures} times, stopping background thread")
                    break
                print(f"⚠️  Camera {camera_id} read failed, retrying in 10s ({consecutive_failures}/{max_failures})")
                time.sleep(10)
                # Recreate shared capture
                cap = get_or_create_shared_capture(camera_id, camera_feed)
                if not cap or not cap.isOpened():
                    print(f"❌ Failed to recreate shared capture for camera {camera_id}")
                    break
                continue

            # Success path
            consecutive_failures = 0
            frame_count += 1
            
            # Log every 100 frames to show it's working
            if frame_count % 100 == 0:
                print(f"📹 Camera {camera_id}: {frame_count} frames captured")

            # Update shared frame for all connections
            update_shared_frame(camera_id, frame)

            now = time.time()
            if now - last_recognition_time >= BACKGROUND_RECOGNITION_INTERVAL:
                try:
                    # Process on original frame without scaling to avoid coordinate issues
                    processed_frame = process_frame_for_recognition(frame, camera_id, 1.0)
                    # Update shared frame with processed version
                    if processed_frame is not None:
                        update_shared_frame(camera_id, processed_frame)
                    else:
                        # If processing fails, use original frame
                        update_shared_frame(camera_id, frame)
                except Exception as e:
                    print(f"Error in background recognition for camera {camera_id}: {e}")
                    # Update with original frame if processing fails
                    update_shared_frame(camera_id, frame)
                last_recognition_time = now

            # Reduced sleep for better responsiveness
            time.sleep(0.02)

    except Exception as e:
        print(f"Background camera loop error (camera {camera_id}): {e}")
    finally:
        # Don't release shared capture as other connections might be using it
        pass

def _start_background_recognition():
    for cam_id, cam in _cameras_cache.items():
        if cam_id in _background_threads:
            continue
        feed = cam.get("camera_live_feed")
        room = cam.get("room_no")
        if not feed:
            continue
        t = threading.Thread(target=_process_camera_feed_background, args=(cam_id, feed, room), daemon=True)
        t.start()
        _background_threads[cam_id] = t
        print(f"🎬 Spawned background thread for camera {cam_id}")

# -------------------
# Shared video capture system
# -------------------
def get_or_create_shared_capture(camera_id, camera_feed):
    """Get or create a shared video capture for a camera."""
    with _frame_lock:
        if camera_id not in _shared_captures:
            print(f"Creating shared capture for camera {camera_id}")
            cap = cv2.VideoCapture(camera_feed, cv2.CAP_FFMPEG)
            if cap.isOpened():
                try:
                    cap.set(cv2.CAP_PROP_BUFFERSIZE, 1)
                    cap.set(cv2.CAP_PROP_FPS, VIDEO_FPS)
                except Exception as e:
                    print(f"Error setting camera properties: {e}")
                _shared_captures[camera_id] = cap
                _shared_frames[camera_id] = None
                print(f"✅ Shared capture created for camera {camera_id}")
            else:
                print(f"❌ Failed to create shared capture for camera {camera_id}")
                return None
        return _shared_captures[camera_id]

def update_shared_frame(camera_id, frame):
    """Update the shared frame for a camera."""
    try:
        with _frame_lock:
            if camera_id in _shared_frames and frame is not None:
                _shared_frames[camera_id] = frame.copy()
    except Exception as e:
        print(f"Error updating shared frame for camera {camera_id}: {e}")

def get_shared_frame(camera_id):
    """Get the latest shared frame for a camera."""
    with _frame_lock:
        return _shared_frames.get(camera_id)

# -------------------
# Stream Recording Functions
# -------------------
def record_stream_segment(camera_id, camera_feed, duration=60):
    """Record a stream segment for the specified duration (default 1 minute)."""
    import pytz
    from datetime import datetime as dt
    
    try:
        # Create recordings directory if it doesn't exist
        os.makedirs(RECORDING_STORAGE_PATH, exist_ok=True)
        
        # Generate filename with timestamp
        tz = pytz.timezone("Asia/Manila")
        now = dt.now(tz)
        timestamp = now.strftime("%Y%m%d_%H%M%S")
        filename = f"camera_{camera_id}_{timestamp}.mp4"
        filepath = os.path.join(RECORDING_STORAGE_PATH, filename)
        
        # Wait for shared frame to be available
        max_wait = 30  # 30 seconds timeout
        wait_count = 0
        while wait_count < max_wait:
            frame = get_shared_frame(camera_id)
            if frame is not None:
                break
            time.sleep(1)
            wait_count += 1
        
        if frame is None:
            print(f"Failed to get shared frame for camera {camera_id} after {max_wait} seconds")
            return None
        
        # Get video properties from the first frame
        height, width = frame.shape[:2]
        fps = 15  # Standard FPS for recordings
        
        # Always prefer FFmpeg for encoding (best media player compatibility)
        # Check if FFmpeg is available
        use_ffmpeg = False
        ffmpeg_process = None
        temp_file = None
        ffmpeg_path = None
        
        # Function to find FFmpeg in common locations
        def find_ffmpeg():
            """Find FFmpeg executable in common installation paths."""
            possible_paths = [
                'ffmpeg',  # Check PATH first (works after PATH refresh)
                'C:\\ProgramData\\chocolatey\\lib\\ffmpeg\\tools\\ffmpeg\\bin\\ffmpeg.exe',  # Chocolatey installation path
                'C:\\ffmpeg\\bin\\ffmpeg.exe',
                f'{os.environ.get("ProgramFiles", "C:\\Program Files")}\\ffmpeg\\bin\\ffmpeg.exe',
                f'{os.environ.get("ProgramFiles(x86)", "C:\\Program Files (x86)")}\\ffmpeg\\bin\\ffmpeg.exe',
                os.path.join(os.path.expanduser('~'), 'ffmpeg', 'bin', 'ffmpeg.exe'),
                'C:\\tools\\ffmpeg\\bin\\ffmpeg.exe',
            ]
            
            for path in possible_paths:
                try:
                    if path == 'ffmpeg':
                        # Try running from PATH
                        result = subprocess.run(
                            ['ffmpeg', '-version'],
                            stdout=subprocess.PIPE,
                            stderr=subprocess.PIPE,
                            timeout=2
                        )
                        if result.returncode == 0:
                            return 'ffmpeg'
                    else:
                        # Check if file exists
                        if os.path.exists(path):
                            # Verify it's actually FFmpeg
                            result = subprocess.run(
                                [path, '-version'],
                                stdout=subprocess.PIPE,
                                stderr=subprocess.PIPE,
                                timeout=2
                            )
                            if result.returncode == 0:
                                return path
                except:
                    continue
            return None
        
        try:
            # Try to find FFmpeg
            ffmpeg_path = find_ffmpeg()
            if ffmpeg_path:
                use_ffmpeg = True
                print(f"✅ FFmpeg found at: {ffmpeg_path}")
                print("✅ Using FFmpeg for direct H.264 encoding (maximum compatibility)")
            else:
                print("⚠️  FFmpeg not found - videos will be encoded with OpenCV (may not work in all players)")
                print("💡 Tip: Install FFmpeg and add it to PATH for better video compatibility")
                print("   Download from: https://ffmpeg.org/download.html")
        except Exception as e:
            print(f"⚠️  Error checking FFmpeg: {e}, falling back to OpenCV")
        
        if use_ffmpeg:
            # Use FFmpeg for direct H.264 encoding with maximum media player compatibility
            temp_file = filepath + '.tmp.mp4'
            
            # Simplified FFmpeg command for maximum compatibility
            # Using baseline profile instead of high for broader player support
            ffmpeg_cmd = [
                ffmpeg_path if ffmpeg_path else 'ffmpeg',
                '-y',  # Overwrite output file
                '-f', 'rawvideo',
                '-vcodec', 'rawvideo',
                '-s', f'{width}x{height}',
                '-pix_fmt', 'bgr24',
                '-r', str(fps),
                '-i', '-',  # Read from stdin
                '-c:v', 'libx264',  # H.264 codec
                '-preset', 'medium',  # Balance between speed and quality
                '-crf', '23',  # Quality setting (18-28 is reasonable)
                '-profile:v', 'baseline',  # Baseline profile for maximum compatibility
                '-level', '3.1',  # H.264 level 3.1 for broader compatibility
                '-pix_fmt', 'yuv420p',  # Required pixel format for all players
                '-movflags', '+faststart',  # Move metadata to beginning for streaming
                '-f', 'mp4',  # Force MP4 container
                '-threads', '0',  # Use all available CPU threads
                '-strict', '-2',  # Allow experimental codecs (sometimes needed)
                temp_file
            ]
            
            try:
                # Start FFmpeg process with better error handling
                ffmpeg_process = subprocess.Popen(
                    ffmpeg_cmd,
                    stdin=subprocess.PIPE,
                    stdout=subprocess.PIPE,
                    stderr=subprocess.PIPE,
                    bufsize=width * height * 3  # Buffer size for one frame
                )
                print(f"✅ Started FFmpeg process for H.264 encoding (Baseline profile, Level 3.1)")
            except Exception as e:
                print(f"⚠️  Failed to start FFmpeg: {e}, falling back to OpenCV")
                use_ffmpeg = False
                ffmpeg_process = None
        
        # Fallback to OpenCV if FFmpeg not available or failed
        out = None
        fourcc = None
        if not use_ffmpeg:
            # Try to create video writer with compatible codec
            # Use XVID or MJPG which are more widely supported than H.264 in OpenCV
            compatible_codecs = ['XVID', 'MJPG', 'mp4v']
            for codec_name in compatible_codecs:
                try:
                    fourcc = cv2.VideoWriter_fourcc(*codec_name)
                    out = cv2.VideoWriter(filepath, fourcc, fps, (width, height))
                    if out.isOpened():
                        print(f"✅ Using {codec_name} codec for recording (will re-encode to H.264)")
                        break
                    else:
                        out.release()
                        out = None
                except Exception as e:
                    print(f"⚠️  Codec {codec_name} not available: {e}")
                    if out: 
                        out.release()
                        out = None
                    continue
            
            if not out or not out.isOpened():
                print(f"❌ Failed to create video writer for camera {camera_id}")
                if ffmpeg_process:
                    ffmpeg_process.terminate()
                return None
        
        start_time = time.time()
        frames_recorded = 0
        last_frame = frame
        target_end_time = start_time + duration
        
        print(f"🎥 Starting recording for camera {camera_id}: {filename}")
        print(f"📊 Target duration: {duration} seconds ({duration/60:.1f} minutes)")
        
        # Calculate target number of frames for the duration
        # Ensure we get exactly the right number of frames for the duration
        target_frames = int(duration * fps)  # 180 * 15 = 2700 frames
        expected_frames = target_frames
        next_frame_time = start_time
        
        print(f"📊 Target: {target_frames} frames at {fps} fps = {duration} seconds ({duration/60:.2f} minutes)")
        
        try:
            # Record exactly the target number of frames
            while frames_recorded < target_frames:
                current_time = time.time()
                
                # Wait until it's time for the next frame (maintains proper timing)
                if current_time < next_frame_time:
                    sleep_duration = next_frame_time - current_time
                    if sleep_duration > 0:
                        time.sleep(sleep_duration)
                
                # Safety check: don't exceed maximum duration by too much
                if current_time >= target_end_time + 1.0:  # 1 second safety buffer
                    remaining = target_frames - frames_recorded
                    if remaining > 0:
                        print(f"⚠️  Time limit exceeded ({current_time - start_time:.1f}s) but {remaining} frames still needed")
                    break
                
                loop_start = time.time()
                
                # Get frame from shared frame buffer (non-blocking)
                frame = get_shared_frame(camera_id)
                if frame is None:
                    # Use last valid frame if shared frame is temporarily unavailable
                    frame = last_frame
                else:
                    last_frame = frame
                
                # Write frame to video
                if use_ffmpeg and ffmpeg_process:
                    # Write frame to FFmpeg stdin
                    try:
                        # Ensure frame is contiguous in memory
                        frame_bytes = frame.tobytes()
                        ffmpeg_process.stdin.write(frame_bytes)
                        # Flush occasionally to prevent buffer buildup
                        if frames_recorded % 30 == 0:  # Every ~2 seconds at 15fps
                            ffmpeg_process.stdin.flush()
                    except (BrokenPipeError, OSError, ValueError) as e:
                        print(f"⚠️  FFmpeg pipe error (frame {frames_recorded}): {e}")
                        # Check if FFmpeg process is still running
                        if ffmpeg_process.poll() is not None:
                            print(f"⚠️  FFmpeg process exited unexpectedly")
                            # Set flag to exit loop
                            use_ffmpeg = False
                            break
                else:
                    # Write frame using OpenCV
                    if out:
                        out.write(frame)
                
                frames_recorded += 1
                
                # Calculate when the next frame should be recorded (maintain exact FPS timing)
                frame_time = 1.0 / fps  # Time between frames: 1/15 = 0.0667 seconds
                next_frame_time = start_time + (frames_recorded * frame_time)
                
                # Calculate elapsed time for this iteration
                elapsed = time.time() - loop_start
                
                # The sleep at the start of the loop will handle timing, so we can exit here
                # but we'll also do a small sleep here to prevent CPU spinning
                remaining_sleep = max(0, frame_time - elapsed)
                if remaining_sleep > 0.001:  # Only sleep if more than 1ms needed
                    time.sleep(min(remaining_sleep, frame_time * 0.5))  # Sleep up to half frame time
        finally:
            # Properly close video writer or FFmpeg process
            if use_ffmpeg and ffmpeg_process:
                try:
                    ffmpeg_process.stdin.close()
                    # Wait for FFmpeg to finish and capture stderr
                    stdout, stderr = ffmpeg_process.communicate(timeout=30)
                    
                    # Check if FFmpeg completed successfully
                    if ffmpeg_process.returncode != 0:
                        error_msg = stderr.decode('utf-8', errors='ignore')[:500] if stderr else "Unknown error"
                        print(f"⚠️  FFmpeg encoding failed (code {ffmpeg_process.returncode}): {error_msg}")
                        # Don't rename temp file if encoding failed
                        if temp_file and os.path.exists(temp_file):
                            os.remove(temp_file)
                        return None
                    
                    # Rename temp file to final file if encoding succeeded
                    if temp_file and os.path.exists(temp_file):
                        if os.path.exists(filepath):
                            os.remove(filepath)
                        os.rename(temp_file, filepath)
                        print(f"✅ FFmpeg encoding completed successfully")
                    else:
                        print(f"⚠️  FFmpeg temp file not found, encoding may have failed")
                        return None
                        
                except subprocess.TimeoutExpired:
                    print(f"⚠️  FFmpeg timeout, terminating process")
                    ffmpeg_process.terminate()
                    try:
                        ffmpeg_process.wait(timeout=5)
                    except:
                        ffmpeg_process.kill()
                    if temp_file and os.path.exists(temp_file):
                        os.remove(temp_file)
                    return None
                except Exception as e:
                    print(f"⚠️  Error closing FFmpeg: {e}")
                    if temp_file and os.path.exists(temp_file):
                        if os.path.exists(filepath):
                            os.remove(filepath)
                        try:
                            os.rename(temp_file, filepath)
                        except:
                            os.remove(temp_file)
            elif out:
                out.release()
                # Give the system a moment to finalize the file
                time.sleep(0.5)
        
        # Verify file was created and get size
        if not os.path.exists(filepath):
            print(f"❌ Video file was not created: {filepath}")
            return None
        
        file_size = os.path.getsize(filepath)
        
        # Validate video file if FFmpeg is available
        if file_size > 0 and use_ffmpeg:
            try:
                # Quick validation using FFprobe
                ffprobe_cmd = [
                    'ffprobe', '-v', 'error', '-show_entries',
                    'format=duration,size,bit_rate', '-of', 'json',
                    filepath
                ]
                validation = subprocess.run(
                    ffprobe_cmd,
                    stdout=subprocess.PIPE,
                    stderr=subprocess.PIPE,
                    timeout=5
                )
                if validation.returncode == 0:
                    print(f"✅ Video file validated successfully")
                else:
                    print(f"⚠️  Video file validation warning (but file exists)")
            except:
                # FFprobe not available, skip validation
                pass
        
        # If not using FFmpeg directly, re-encode with FFmpeg for browser compatibility
        # This converts XVID/MJPG/mp4v to H.264 and ensures proper moov atom placement
        if not use_ffmpeg and file_size > 0:
            try:
                # Check if FFmpeg is available for re-encoding
                ffmpeg_check = subprocess.run(
                    ['ffmpeg', '-version'],
                    stdout=subprocess.PIPE,
                    stderr=subprocess.PIPE,
                    timeout=2
                )
                
                if ffmpeg_check.returncode == 0:
                    # Re-encode with FFmpeg to H.264 for browser compatibility
                    temp_reencode_file = filepath + '.reencode.tmp'
                    os.rename(filepath, temp_reencode_file)
                    
                    # Find FFmpeg path for re-encoding
                    reencode_ffmpeg_path = find_ffmpeg() if 'find_ffmpeg' in locals() else None
                    if not reencode_ffmpeg_path:
                        # Try simple PATH check
                        try:
                            subprocess.run(['ffmpeg', '-version'], stdout=subprocess.PIPE, stderr=subprocess.PIPE, timeout=2)
                            reencode_ffmpeg_path = 'ffmpeg'
                        except:
                            reencode_ffmpeg_path = 'ffmpeg'  # Fallback, may fail but worth trying
                    
                    # Re-encode with FFmpeg to ensure maximum media player compatibility
                    ffmpeg_cmd = [
                        reencode_ffmpeg_path if reencode_ffmpeg_path else 'ffmpeg', '-y', '-i', temp_reencode_file,
                        '-c:v', 'libx264', 
                        '-preset', 'medium',  # Better compatibility than 'fast'
                        '-crf', '23',
                        '-profile:v', 'baseline',  # Baseline profile for maximum compatibility
                        '-level', '3.1',  # H.264 level 3.1 for broader compatibility
                        '-pix_fmt', 'yuv420p',  # Ensure pixel format compatibility (required for most players)
                        '-movflags', '+faststart',  # Moves metadata to beginning for streaming
                        '-f', 'mp4',  # Force MP4 container format
                        '-an',  # No audio track (video-only)
                        filepath
                    ]
                    
                    result = subprocess.run(
                        ffmpeg_cmd,
                        stdout=subprocess.PIPE,
                        stderr=subprocess.PIPE,
                        timeout=60
                    )
                    
                    if result.returncode == 0:
                        # Remove temp file if re-encoding succeeded
                        if os.path.exists(temp_reencode_file):
                            os.remove(temp_reencode_file)
                        print(f"✅ Video re-encoded to H.264 for browser compatibility")
                        file_size = os.path.getsize(filepath)
                    else:
                        # If FFmpeg fails, restore original file
                        print(f"⚠️  FFmpeg re-encoding failed: {result.stderr.decode()[:200]}")
                        if os.path.exists(temp_reencode_file):
                            if os.path.exists(filepath):
                                os.remove(filepath)
                            os.rename(temp_reencode_file, filepath)
                else:
                    print(f"⚠️  FFmpeg not available for re-encoding, using original codec")
            except (subprocess.TimeoutExpired, FileNotFoundError, Exception) as e:
                # FFmpeg not available or failed, use original file
                print(f"⚠️  FFmpeg re-encoding failed (this is okay): {e}")
                if 'temp_reencode_file' in locals() and os.path.exists(temp_reencode_file):
                    if os.path.exists(filepath):
                        os.remove(filepath)
                    os.rename(temp_reencode_file, filepath)
        
        # Calculate actual recording duration
        actual_duration = time.time() - start_time
        expected_frames = int(fps * duration)
        
        print(f"✅ Recording completed for camera {camera_id}: {frames_recorded} frames, {file_size} bytes")
        print(f"📊 Actual duration: {actual_duration:.1f} seconds ({actual_duration/60:.2f} minutes)")
        print(f"📊 Expected duration: {duration} seconds ({duration/60:.2f} minutes)")
        print(f"📊 Frames recorded: {frames_recorded} / Expected: ~{expected_frames}")
        
        # Return recording info
        return {
            "camera_id": camera_id,
            "filename": filename,
            "filepath": filepath,
            "relative_path": f"stream_recordings/{filename}",
            "start_time": now.strftime("%Y-%m-%d %H:%M:%S"),
            "duration": int(actual_duration),
            "frames": frames_recorded,
            "file_size": file_size
        }
        
    except Exception as e:
        print(f"Error recording stream for camera {camera_id}: {e}")
        return None

def save_recording_to_database(recording_info):
    """Save recording information to database."""
    try:
        if not recording_info:
            return False
        
        payload = {
            "camera_id": recording_info["camera_id"],
            "filename": recording_info["filename"],
            "filepath": recording_info["relative_path"],
            "start_time": recording_info["start_time"],
            "duration": recording_info["duration"],
            "frames": recording_info["frames"],
            "file_size": recording_info["file_size"]
        }
        
        # Use headers WITHOUT API key for public stream-recordings endpoint
        # The public route is for creating new recordings (no recording_id needed)
        # The protected route is for syncing existing records (requires recording_id)
        headers = {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        }
        # Don't include API_KEY in headers - use the public endpoint
        
        print(f"📤 Sending recording to database: {payload['filename']}")
        print(f"📤 Payload: {payload}")
        
        response = requests.post(STREAM_RECORDING_ENDPOINT, json=payload, headers=headers, timeout=10)
        
        print(f"📥 Response status: {response.status_code}")
        print(f"📥 Response body: {response.text}")
        
        if response.status_code in [200, 201]:
            print(f"✅ Recording saved to database: {recording_info['filename']}")
            return True
        else:
            print(f"❌ Failed to save recording to database: {response.status_code} - {response.text}")
            # Try to parse error details
            try:
                error_data = response.json()
                if isinstance(error_data, dict):
                    error_msg = error_data.get('error', error_data.get('message', response.text))
                    print(f"❌ Error details: {error_msg}")
            except:
                pass
            return False
            
    except Exception as e:
        print(f"❌ Error saving recording to database: {e}")
        import traceback
        traceback.print_exc()
        return False

def continuous_stream_recording(camera_id, camera_feed):
    """Continuously record stream in 3-minute segments."""
    print(f"🎬 Starting continuous recording for camera {camera_id}")
    
    while True:
        try:
            # Check if camera is still active
            if camera_id not in _cameras_cache:
                print(f"Camera {camera_id} no longer in cache, stopping recording")
                break
            
            # Record segment
            recording_info = record_stream_segment(camera_id, camera_feed, RECORDING_INTERVAL)
            
            # Save to database
            if recording_info:
                save_recording_to_database(recording_info)
            
            # Small delay before next recording
            time.sleep(1)
            
        except Exception as e:
            print(f"Error in continuous recording for camera {camera_id}: {e}")
            time.sleep(5)  # Wait before retrying

def start_recording_for_camera(camera_id):
    """Start recording for a specific camera."""
    try:
        cam = _cameras_cache.get(int(camera_id))
        if not cam:
            print(f"Camera {camera_id} not found in cache")
            return False
        
        camera_feed = cam.get("camera_live_feed")
        if not camera_feed:
            print(f"No camera feed URL for camera {camera_id}")
            return False
        
        # Check if already recording
        if camera_id in _recording_threads and _recording_threads[camera_id].is_alive():
            print(f"Camera {camera_id} is already being recorded")
            return False
        
        # Start recording thread
        recording_thread = threading.Thread(
            target=continuous_stream_recording,
            args=(camera_id, camera_feed),
            daemon=True
        )
        recording_thread.start()
        _recording_threads[camera_id] = recording_thread
        
        print(f"✅ Started recording thread for camera {camera_id}")
        return True
        
    except Exception as e:
        print(f"Error starting recording for camera {camera_id}: {e}")
        return False

def start_all_camera_recordings():
    """Start recording for all cameras."""
    for camera_id in _cameras_cache.keys():
        start_recording_for_camera(camera_id)

# -------------------
# Get RTSP track
# -------------------
async def get_or_create_track(camera_id, connection_id=None):
    cam = _cameras_cache.get(int(camera_id))
    if not cam:
        raise Exception("camera not found")
    return RTSPVideoTrack(
        camera_id=int(camera_id),
        camera_live_feed=cam.get("camera_live_feed"),
        room_no=cam.get("room_no"),
        connection_id=connection_id
    )

# -------------------
# aiohttp endpoints
# -------------------
async def offer(request):
    camera_id = request.match_info.get("camera_id")
    body = await request.json()
    if not camera_id or "sdp" not in body:
        return web.json_response({"error": "camera_id or sdp missing"}, status=400)

    # Generate unique connection ID for this user
    connection_id = f"{camera_id}_{int(time.time() * 1000)}"
    
    pc = RTCPeerConnection()
    track = await get_or_create_track(int(camera_id), connection_id)
    pc.addTrack(track)
    
    # Track this connection
    camera_id_int = int(camera_id)
    if camera_id_int not in _active_connections:
        _active_connections[camera_id_int] = set()
    _active_connections[camera_id_int].add(connection_id)
    
    offer = RTCSessionDescription(sdp=body["sdp"], type=body.get("type", "offer"))
    await pc.setRemoteDescription(offer)
    answer = await pc.createAnswer()
    await pc.setLocalDescription(answer)
    return web.json_response({"sdp": pc.localDescription.sdp, "type": pc.localDescription.type})

def convert_tuple_keys_to_strings(data):
    """Recursively convert tuple keys to string keys for JSON serialization."""
    if isinstance(data, dict):
        result = {}
        for key, value in data.items():
            if isinstance(key, tuple):
                # Convert tuple key to string key
                str_key = "_".join(str(k) for k in key)
                result[str_key] = convert_tuple_keys_to_strings(value)
            else:
                result[str(key)] = convert_tuple_keys_to_strings(value)
        return result
    elif isinstance(data, list):
        return [convert_tuple_keys_to_strings(item) for item in data]
    else:
        return data

async def status(request):
    try:
        print(f"Status endpoint called. Presence accumulator keys: {list(_presence_accumulator.keys())}")
        print(f"Recognition results keys: {list(_recognition_results.keys())}")
        print(f"Schedules cache keys: {list(_schedules_cache.keys())}")
        
        # Convert all data structures to ensure no tuple keys
        results_json = convert_tuple_keys_to_strings(_recognition_results)
        schedules_json = convert_tuple_keys_to_strings(_schedules_cache)
        presence_accumulator_json = convert_tuple_keys_to_strings(_presence_accumulator)
        late_tracking_json = convert_tuple_keys_to_strings(_late_tracking)
        recognition_tracking_json = convert_tuple_keys_to_strings(_recognition_tracking)
        
        # Get recent recognition logs (last 50 entries)
        recognition_logs = get_recognition_logs(limit=50)
        
        return web.json_response({
            "results": results_json,
            "schedules": schedules_json,
            "presence_accumulator": presence_accumulator_json,
            "late_tracking": late_tracking_json,
            "recognition_tracking": recognition_tracking_json,
            "recognition_logs": recognition_logs
        })
    except Exception as e:
        print(f"Error in status endpoint: {e}")
        import traceback
        traceback.print_exc()
        return web.json_response({
            "error": "Internal server error",
            "details": str(e)
        }, status=500)

async def health(request):
    return web.json_response({"status": "ok"})

async def current_schedule(request):
    room_no = request.query.get("room_no")
    if not room_no:
        return web.json_response({"error": "room_no required"}, status=400)
    sched = get_current_schedule_for_room(room_no)
    return web.json_response({"room_no": room_no, "schedule": sched})

async def connection_stats(request):
    """Return statistics about active connections."""
    try:
        stats = {}
        for camera_id, connections in _active_connections.items():
            stats[camera_id] = {
                "active_connections": len(connections),
                "connection_ids": list(connections)
            }
        return web.json_response({
            "total_cameras": len(_active_connections),
            "total_connections": sum(len(conns) for conns in _active_connections.values()),
            "cameras": stats
        })
    except Exception as e:
        return web.json_response({"error": str(e)}, status=500)

async def cleanup_connection(request):
    """Clean up connection when user disconnects."""
    try:
        body = await request.json()
        camera_id = body.get("camera_id")
        connection_id = body.get("connection_id")
        
        if camera_id and connection_id:
            camera_id_int = int(camera_id)
            if camera_id_int in _active_connections:
                _active_connections[camera_id_int].discard(connection_id)
                
                # If no more connections for this camera, we can optionally clean up
                if not _active_connections[camera_id_int]:
                    print(f"No more connections for camera {camera_id}, keeping shared capture for reuse")
                    # Note: We keep the shared capture for potential reuse
                    
        return web.json_response({"status": "ok"})
    except Exception as e:
        return web.json_response({"error": str(e)}, status=500)

# -------------------
# Cleanup old recognition tracking data
# -------------------
def cleanup_old_recognition_tracking():
    """Clean up old recognition tracking data (older than 24 hours)."""
    import pytz
    tz = pytz.timezone("Asia/Manila")
    now = datetime.datetime.now(tz)
    cutoff_time = now.timestamp() - (24 * 60 * 60)  # 24 hours ago
    
    keys_to_remove = []
    for key, tracking in _recognition_tracking.items():
        if tracking.get("last_seen", 0) < cutoff_time:
            keys_to_remove.append(key)
    
    for key in keys_to_remove:
        del _recognition_tracking[key]
    
    if keys_to_remove:
        print(f"Cleaned up {len(keys_to_remove)} old recognition tracking entries")

# -------------------
# Background tasks
# -------------------
async def background_tasks():
    """Background tasks for schedule checking and data refresh."""
    while True:
        try:
            # Initialize late tracking for new schedules
            initialize_late_tracking()
            
            # Check for late threshold (15 minutes from class start)
            check_late_threshold()
            
            # Check for schedule endings and mark absent
            check_schedule_end_and_mark_absent()
            
            # Clean up old recognition tracking data
            cleanup_old_recognition_tracking()
            
            # Clean up old face tracking history
            cleanup_face_tracking_history()
            
            # Clean up old persistent bounding boxes
            cleanup_persistent_bounding_boxes()
            
            # Clean up old recognition logs
            cleanup_old_recognition_logs()
            
            # Refresh data every 5 minutes
            fetch_cameras()
            fetch_today_schedule()
            fetch_faculty_embeddings()

            # Ensure background recognition threads are running
            _start_background_recognition()
            
            # Ensure recording threads are running for all cameras
            start_all_camera_recordings()
            
        except Exception as e:
            print(f"Background task error: {e}")
        
        await asyncio.sleep(60)  # Check every minute

# -------------------
# Main
# -------------------
async def init_background_tasks(app):
    """Initialize background tasks when app starts."""
    asyncio.create_task(background_tasks())

def main():
    print("🚀 Starting TCC-MAEM Recognition Service...")
    
    # Log API configuration
    print(f"📡 API Base URL: {API_BASE}")
    if API_KEY:
        print(f"🔑 API Key: Configured (length: {len(API_KEY)})")
    else:
        print("⚠️  API Key: Not configured (using public routes)")
    
    # Fetch initial data
    print("📡 Fetching cameras...")
    fetch_cameras()
    print(f"✅ Loaded {len(_cameras_cache)} cameras")
    
    print("📅 Fetching today's schedule...")
    fetch_today_schedule()
    print(f"✅ Loaded schedules for {len(_schedules_cache)} rooms")
    
    print("👤 Fetching faculty embeddings...")
    fetch_faculty_embeddings()
    print(f"✅ Loaded embeddings for {len(_faculty_embeddings)} faculty members")
    
    # Start background recognition threads IMMEDIATELY (before web app starts)
    print("🎬 Starting background recognition threads...")
    _start_background_recognition()
    print(f"✅ Started {len(_background_threads)} background threads")

    app = web.Application()
    app.router.add_post("/offer/{camera_id}", offer)
    app.router.add_get("/status", status)
    app.router.add_get("/health", health)
    app.router.add_get("/current-schedule", current_schedule)
    app.router.add_get("/connection-stats", connection_stats)
    app.router.add_post("/cleanup-connection", cleanup_connection)

    # Start background tasks
    app.on_startup.append(init_background_tasks)

    cors = aiohttp_cors.setup(app, defaults={"*": aiohttp_cors.ResourceOptions(
        allow_credentials=True, expose_headers="*", allow_headers="*")})
    for route in list(app.router.routes()):
        cors.add(route)

    # Default port for WebRTC service
    port = int(os.getenv("RECOGNITION_PORT", "5000"))
    print(f"🌐 Starting web server on port {port}...")
    web.run_app(app, host="0.0.0.0", port=port)

if __name__ == "__main__":
    main()
