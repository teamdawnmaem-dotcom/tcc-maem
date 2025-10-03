#!/usr/bin/env python3
"""
recognition/service.py
aiohttp + aiortc service for:
 - accept browser SDP offers at POST /offer/{camera_id}
 - stream RTSP -> WebRTC
 - run face recognition periodically with visual feedback
 - post attendance to Laravel API (deduplicated)
 - expose /status and /health
 - extract faculty embeddings from stored images (disk paths) on-demand
 - handle leave/pass slip integration for attendance remarks
"""
from aiohttp import web
import aiohttp_cors
import os, json, datetime, threading, asyncio
from aiortc import RTCPeerConnection, RTCSessionDescription, VideoStreamTrack
import cv2
import numpy as np
import face_recognition
import requests
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

STORAGE_PATH = os.getenv("LARAVEL_STORAGE_PATH", "../storage/app/public")

MATCH_THRESHOLD = float(os.getenv("MATCH_THRESHOLD", "0.6"))

# caches
_cameras_cache = {}
_schedules_cache = {}
_presence_accumulator = {}
_faculty_embeddings = {}
_recognition_results = {}
_last_overlays = {}  # camera_id -> {"boxes": [ (left, top, right, bottom, (b,g,r), label) ], "expires_at": ts}
_rtsp_tracks = {}
_track_lock = threading.Lock()
_background_threads = {}
_late_tracking = {}  # Track late status for each schedule
_processing_queue = {}  # Queue for async processing
_recognition_tracking = {}  # Track first and last recognition times for each faculty/schedule
_active_connections = {}  # Track active WebRTC connections per camera
_shared_captures = {}  # Shared video captures per camera
_shared_frames = {}  # Latest frames per camera for sharing
_frame_lock = threading.Lock()  # Lock for frame sharing
_face_tracking_history = {}  # Track face positions for smoothing

# Face recognition settings
RECOGNITION_INTERVAL = 0.5  # seconds between recognition attempts (WebRTC)
BACKGROUND_RECOGNITION_INTERVAL = 5.0  # seconds for background processing
PRESENCE_THRESHOLD = 1800  # 30 minutes in seconds
LATE_THRESHOLD = 1800  # 30 minutes in seconds for late marking

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
        r = requests.get(CAMERAS_ENDPOINT, timeout=8)
        r.raise_for_status()
        cams = r.json()
        _cameras_cache.clear()
        for c in cams:
            cid = int(c["camera_id"])
            _cameras_cache[cid] = {
                "camera_id": cid,
                "room_no": c.get("room_no"),
                "camera_live_feed": c.get("camera_live_feed")
            }
        return cams
    except Exception as e:
        print("fetch_cameras error:", e)
        return []

def fetch_today_schedule():
    try:
        r = requests.get(SCHEDULE_ENDPOINT, timeout=8)
        r.raise_for_status()
        schedules = r.json()
        _schedules_cache.clear()
        for s in schedules:
            room = str(s["room_no"])
            entry = {
                "teaching_load_id": s.get("teaching_load_id"),
                "faculty_id": s.get("faculty_id"),
                "time_in": s.get("teaching_load_time_in"),
                "time_out": s.get("teaching_load_time_out")
            }
            _schedules_cache.setdefault(room, []).append(entry)
        return schedules
    except Exception as e:
        print("fetch_today_schedule error:", e)
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
        leave_url = f"{API_BASE}/faculty-leave-status"
        leave_payload = {
            "faculty_id": faculty_id,
            "date": date,
            "time_in": time_in,
            "time_out": time_out
        }
        leave_response = requests.post(leave_url, json=leave_payload, timeout=5)
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
        pass_response = requests.post(pass_url, json=pass_payload, timeout=5)
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
    """Check if any schedules have passed the late threshold (30 minutes from start)."""
    import pytz
    tz = pytz.timezone("Asia/Manila")
    now = datetime.datetime.now(tz)
    now_min = now.hour * 60 + now.minute
    date_str = now.strftime("%Y-%m-%d")
    
    for key, late_info in _late_tracking.items():
        room_no, load_id = key
        start_time = late_info["start_time"]
        
        # Check if 30 minutes have passed since class start
        if not late_info["late_threshold_reached"] and (now_min - start_time) >= 30:
            late_info["late_threshold_reached"] = True
            
            # Check if faculty has been recognized
            acc = _presence_accumulator.get(key)
            if not acc or acc["seconds"] < PRESENCE_THRESHOLD:
                # Mark as late
                sched = None
                for s in _schedules_cache.get(room_no, []):
                    if s.get("teaching_load_id") == load_id:
                        sched = s
                        break
                
                if sched:
                    faculty_id = sched.get("faculty_id")
                    
                    # Check leave/pass slip status
                    faculty_status = check_faculty_status(
                        faculty_id, 
                        date_str, 
                        sched.get("time_in"), 
                        sched.get("time_out")
                    )
                    
                    if faculty_status:
                        record_status = "Absent"
                        record_remarks = faculty_status
                    else:
                        record_status = "Absent"
                        record_remarks = "Absent"
                    
                    # Find camera for this room
                    camera_id = None
                    for cam_id, cam_data in _cameras_cache.items():
                        if str(cam_data.get("room_no")) == str(room_no):
                            camera_id = cam_id
                            break
                    
                    if camera_id:
                        # Get schedule information for the new fields
                        schedule_info = None
                        for s in _schedules_cache.get(room_no, []):
                            if s.get("teaching_load_id") == load_id:
                                schedule_info = s
                                break
                        
                        # Get time fields - instructor was not detected (late/absent)
                        time_fields = get_attendance_time_fields(camera_id, faculty_id, load_id, was_detected=False)
                        
                        payload = {
                            "faculty_id": int(faculty_id),
                            "teaching_load_id": load_id,
                            "camera_id": camera_id,
                            "record_status": record_status,
                            "record_remarks": record_remarks,
                            **time_fields
                        }
                        threading.Thread(target=_post_attendance_dedup, args=(payload,), daemon=True).start()
                        late_info["late_marked"] = True

# -------------------
# Recognition time tracking
# -------------------
def track_recognition_time(camera_id: int, faculty_id: int, teaching_load_id: int):
    """Track first and last recognition times for faculty."""
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
            "first_recognition_time": None
        }
        print(f"DEBUG: Initialized tracking for key {key}")
    
    tracking = _recognition_tracking[key]
    
    # If this is the first actual recognition (time_in is None), set it
    if tracking["time_in"] is None:
        tracking["time_in"] = now_str
        tracking["first_recognition_time"] = now_str
        print(f"DEBUG: Set first recognition time: {now_str}")
    else:
        print(f"DEBUG: Updating time_out from {tracking['time_out']} to {now_str}")
    
    # Always update time_out and duration
    tracking["time_out"] = now_str
    tracking["last_seen"] = now.timestamp()
    
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
        "total_duration": 0
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
    
    # Track recognition times (first and last seen)
    track_recognition_time(camera_id, detected_faculty_id, load_id)
    
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

    # If reached 30 minutes (1800 seconds), post attendance
    if acc["seconds"] >= PRESENCE_THRESHOLD:
        # Check leave/pass slip status
        import pytz
        tz = pytz.timezone("Asia/Manila")
        now = datetime.datetime.now(tz)
        date_str = now.strftime("%Y-%m-%d")
        
        faculty_status = check_faculty_status(
            detected_faculty_id, 
            date_str, 
            sched.get("time_in"), 
            sched.get("time_out")
        )
        
        if faculty_status:
            record_status = "Absent"
            record_remarks = faculty_status
        else:
            # Check if late threshold was reached
            late_info = _late_tracking.get(key, {})
            if late_info.get("late_threshold_reached", False):
                record_status = "Late"
                record_remarks = "Late"
            else:
                record_status = "Present"
                record_remarks = "Present"
        
        # Get time fields - instructor was detected (present/late)
        time_fields = get_attendance_time_fields(camera_id, detected_faculty_id, load_id, was_detected=True)
        
        print(f"Posting attendance - Time in: {time_fields['record_time_in']}, Time out: {time_fields['record_time_out']}, Duration: {time_fields['time_duration_seconds']}s")
        
        payload = {
            "faculty_id": int(detected_faculty_id),
            "teaching_load_id": load_id,
            "camera_id": camera_id,
            "record_status": record_status,
            "record_remarks": record_remarks,
            **time_fields
        }
        threading.Thread(target=_post_attendance_dedup, args=(payload,), daemon=True).start()
        # Reset accumulator to avoid repeated posts
        acc["seconds"] = 0.0

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
                
                # If not enough presence accumulated, mark absent
                if not acc or acc["seconds"] < PRESENCE_THRESHOLD:
                    # Check leave/pass slip status
                    faculty_status = check_faculty_status(
                        faculty_id, 
                        date_str, 
                        sched.get("time_in"), 
                        sched.get("time_out")
                    )
                    
                    if faculty_status:
                        record_status = "Absent"
                        record_remarks = faculty_status
                    else:
                        record_status = "Absent"
                        record_remarks = "Absent"
                    
                    # Find camera for this room
                    camera_id = None
                    for cam_id, cam_data in _cameras_cache.items():
                        if str(cam_data.get("room_no")) == str(room_no):
                            camera_id = cam_id
                            break
                    
                    if camera_id:
                        # Use Asia/Manila timezone for timestamp
                        import pytz
                        tz = pytz.timezone("Asia/Manila")
                        now = datetime.datetime.now(tz)
                        
                        # Get schedule information for the new fields
                        schedule_info = None
                        for s in _schedules_cache.get(room_no, []):
                            if s.get("teaching_load_id") == load_id:
                                schedule_info = s
                                break
                        
                        # Get time fields - instructor was not detected (absent)
                        time_fields = get_attendance_time_fields(camera_id, faculty_id, load_id, was_detected=False)
                        
                        payload = {
                            "faculty_id": int(faculty_id),
                            "teaching_load_id": load_id,
                            "camera_id": camera_id,
                            "record_status": record_status,
                            "record_remarks": record_remarks,
                            **time_fields
                        }
                        threading.Thread(target=_post_attendance_dedup, args=(payload,), daemon=True).start()
                
                # Clean up accumulator
                if key in _presence_accumulator:
                    del _presence_accumulator[key]

def fetch_faculty_embeddings():
    try:
        r = requests.get(FACULTY_EMBEDDINGS_ENDPOINT, timeout=10)
        r.raise_for_status()
        data = r.json()
        _faculty_embeddings.clear()
        for f in data:
            fid = int(f["faculty_id"])
            emb = f.get("faculty_face_embedding")
            if not emb:
                continue
            try:
                arr_list = json.loads(emb) if isinstance(emb, str) else emb
                emb_arrays = [np.array(e) for e in arr_list] if isinstance(arr_list[0], list) else [np.array(arr_list)]
                _faculty_embeddings[fid] = emb_arrays
            except Exception as e:
                print("embedding parse error:", fid, e)
        print("Loaded faculty embeddings:", list(_faculty_embeddings.keys()))
        return data
    except Exception as e:
        print("fetch_faculty_embeddings error:", e)
        return []

def get_faculty_name(faculty_id):
    """Get faculty full name by ID."""
    try:
        if not faculty_id:
            return "Unknown"
        
        # Try to get faculty name from the faculty data that was fetched
        # Check if we have faculty data from the embeddings endpoint
        try:
            r = requests.get(FACULTY_EMBEDDINGS_ENDPOINT, timeout=5)
            if r.status_code == 200:
                faculty_data = r.json()
                for faculty in faculty_data:
                    if int(faculty.get("faculty_id")) == int(faculty_id):
                        # Try to get first name and last name
                        first_name = faculty.get("faculty_fname", "")
                        last_name = faculty.get("faculty_lname", "")
                        if first_name and last_name:
                            return f"{first_name} {last_name}"
                        elif first_name:
                            return first_name
                        elif last_name:
                            return last_name
                        else:
                            return f"Faculty {faculty_id}"
        except Exception as e:
            print(f"Error fetching faculty data for ID {faculty_id}: {e}")
        
        # Fallback to simple format
        return f"Faculty {faculty_id}"
    except Exception as e:
        print(f"Error getting faculty name for ID {faculty_id}: {e}")
        return f"Faculty {faculty_id}" if faculty_id else "Unknown"

# -------------------
# Generate embeddings from faculty image paths
# -------------------
def update_faculty_embeddings_from_images(faculty_id=None):
    """
    Compute embeddings from stored faculty images.
    If faculty_id is provided, only update that faculty.
    """
    try:
        r = requests.get(FACULTY_EMBEDDINGS_ENDPOINT, timeout=15)
        r.raise_for_status()
        faculty_data = r.json()

        for f in faculty_data:
            fid = f.get("faculty_id")
            if faculty_id and fid != faculty_id:
                continue

            # Get faculty images - handle both string and already parsed JSON
            faculty_images = f.get("faculty_images", "[]")
            if isinstance(faculty_images, str):
                try:
                    image_paths = json.loads(faculty_images)
                except json.JSONDecodeError:
                    print(f"Invalid JSON for faculty_images for faculty_id {fid}: {faculty_images}")
                    continue
            else:
                image_paths = faculty_images

            if not image_paths or not isinstance(image_paths, list):
                print(f"No valid image paths for faculty_id {fid}")
                continue

            embeddings_list = []
            print(f"Processing {len(image_paths)} images for faculty_id {fid}")

            for img_path in image_paths:
                try:
                    # Handle both relative and absolute paths
                    if os.path.isabs(img_path):
                        full_path = img_path
                    else:
                        full_path = os.path.join(STORAGE_PATH, img_path)
                    
                    print(f"Processing image: {full_path}")
                    
                    if not os.path.exists(full_path):
                        print(f"File not found: {full_path}")
                        continue
                    
                    # Load and process image
                    img = face_recognition.load_image_file(full_path)
                    print(f"Image loaded successfully: {img.shape}")
                    
                    # Try different face detection models
                    encodings = face_recognition.face_encodings(img, model="cnn")
                    if not encodings:
                        # Fallback to HOG model if CNN fails
                        encodings = face_recognition.face_encodings(img, model="hog")
                    
                    if encodings:
                        embeddings_list.extend(encodings)
                        print(f"Found {len(encodings)} face(s) in {img_path}")
                    else:
                        print(f"No faces detected in {img_path}")
                        
                except Exception as e:
                    print(f"Error processing image {img_path} for faculty_id {fid}: {e}")

            if embeddings_list:
                emb_list_json = [emb.tolist() for emb in embeddings_list]
                payload = {"faculty_id": fid, "faculty_face_embedding": json.dumps(emb_list_json)}
                try:
                    # Use PUT method to update embeddings
                    r_post = requests.put(FACULTY_EMBEDDINGS_ENDPOINT, json=payload, timeout=10)
                    if r_post.status_code in (200, 201):
                        print(f"Successfully updated embeddings for faculty_id {fid} with {len(embeddings_list)} face(s)")
                        _faculty_embeddings[fid] = embeddings_list
                    else:
                        print(f"Failed to update embeddings for faculty_id {fid}: {r_post.status_code} - {r_post.text}")
                except Exception as e:
                    print(f"Error posting embeddings for faculty_id {fid}: {e}")
            else:
                print(f"No valid faces found for faculty_id {fid}")

    except Exception as e:
        print("update_faculty_embeddings_from_images error:", e)

# -------------------
# Simple frame processing
# -------------------

# -------------------
# face recognition processing
# -------------------
def detect_faces_optimized(frame):
	"""Optimized face detection with better performance."""
	try:
		# Convert BGR to RGB for face_recognition
		rgb_frame = cv2.cvtColor(frame, cv2.COLOR_BGR2RGB)
		
		# Use HOG model for faster and more reliable detection
		face_locations = face_recognition.face_locations(rgb_frame, model="hog")
		
		if not face_locations:
			return [], []
		
		# Only compute encodings for detected faces
		face_encodings = face_recognition.face_encodings(rgb_frame, face_locations)
		return face_locations, face_encodings
	except Exception as e:
		print(f"Error in face detection: {e}")
		return [], []

def match_faculty_optimized(face_encoding):
	"""Optimized faculty matching with early exit."""
	try:
		best_match = None
		best_distance = float('inf')
		
		for faculty_id, faculty_embeddings in _faculty_embeddings.items():
			if not faculty_embeddings:
				continue
			
			# Compare with all embeddings for this faculty
			distances = face_recognition.face_distance(faculty_embeddings, face_encoding)
			min_distance = min(distances)
			
			if min_distance < best_distance and min_distance < MATCH_THRESHOLD:
				best_distance = min_distance
				best_match = faculty_id
				
				# Early exit if we find a very good match
				if min_distance < MATCH_THRESHOLD * 0.5:
					break
		
		return best_match, best_distance
	except Exception as e:
		print(f"Error in faculty matching: {e}")
		return None, float('inf')

def smooth_face_position(camera_id, face_location, faculty_id):
	"""Smooth face position to reduce jitter and improve tracking."""
	try:
		key = f"{camera_id}_{faculty_id}" if faculty_id else f"{camera_id}_unknown"
		
		# Initialize tracking history if not exists
		if key not in _face_tracking_history:
			_face_tracking_history[key] = {
				"positions": [],
				"max_history": 5  # Keep last 5 positions
			}
		
		history = _face_tracking_history[key]
		top, right, bottom, left = face_location
		
		# Add current position to history
		history["positions"].append((top, right, bottom, left))
		
		# Keep only recent positions
		if len(history["positions"]) > history["max_history"]:
			history["positions"].pop(0)
		
		# Calculate smoothed position using weighted average
		if len(history["positions"]) >= 2:
			# Weight recent positions more heavily
			weights = [i + 1 for i in range(len(history["positions"]))]
			total_weight = sum(weights)
			
			smoothed_top = sum(pos[0] * weight for pos, weight in zip(history["positions"], weights)) / total_weight
			smoothed_right = sum(pos[1] * weight for pos, weight in zip(history["positions"], weights)) / total_weight
			smoothed_bottom = sum(pos[2] * weight for pos, weight in zip(history["positions"], weights)) / total_weight
			smoothed_left = sum(pos[3] * weight for pos, weight in zip(history["positions"], weights)) / total_weight
			
			return (int(smoothed_top), int(smoothed_right), int(smoothed_bottom), int(smoothed_left))
		else:
			# Not enough history, return original position
			return face_location
			
	except Exception as e:
		print(f"Error smoothing face position: {e}")
		return face_location

def draw_stable_bounding_box(frame, face_location, faculty_id, is_scheduled, presence_info, faculty_name=None):
	"""Draw a stable bounding box that stays on the face without flickering."""
	try:
		top, right, bottom, left = face_location
		
		# Get frame dimensions
		frame_height, frame_width = frame.shape[:2]
		
		# Ensure coordinates are within frame bounds
		left = max(0, min(left, frame_width - 1))
		right = max(0, min(right, frame_width - 1))
		top = max(0, min(top, frame_height - 1))
		bottom = max(0, min(bottom, frame_height - 1))
		
		# Ensure valid rectangle
		if top >= bottom or left >= right:
			return
		
		# Set colors and thickness
		thickness = 3
		
		if faculty_id:
			if is_scheduled:
				color = (0, 255, 0)  # Green for correct faculty
			else:
				color = (0, 165, 255)  # Orange for wrong faculty
			
			# Draw main bounding box
			cv2.rectangle(frame, (left, top), (right, bottom), color, thickness)
			
			# Draw corner markers for better visibility
			corner_size = 8
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
			
			# Draw faculty name label
			if faculty_name and faculty_name != "Unknown":
				label = faculty_name
			else:
				label = f"Faculty {faculty_id}"
			
			if is_scheduled:
				label += " (Scheduled)"
			else:
				label += " (Not Scheduled)"
			
			# Draw label with background
			font_scale = 0.6
			font_thickness = 2
			(text_width, text_height), baseline = cv2.getTextSize(label, cv2.FONT_HERSHEY_SIMPLEX, font_scale, font_thickness)
			
			# Position text above the bounding box
			text_x = max(5, min(left, frame_width - text_width - 5))
			text_y = max(text_height + 5, min(top - 10, frame_height - 5))
			
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
				
				(presence_width, presence_height), _ = cv2.getTextSize(presence_text, cv2.FONT_HERSHEY_SIMPLEX, 0.5, 1)
				presence_x = max(5, min(left, frame_width - presence_width - 5))
				presence_y = min(frame_height - 5, bottom + 20)
				
				# Draw presence background
				cv2.rectangle(frame, (presence_x - 2, presence_y - presence_height - 2), 
							 (presence_x + presence_width + 2, presence_y + 2), (0, 0, 0), -1)
				
				# Draw presence text
				cv2.putText(frame, presence_text, (presence_x, presence_y), 
						   cv2.FONT_HERSHEY_SIMPLEX, 0.5, color, 1)
		else:
			# Draw bounding box for unknown face
			cv2.rectangle(frame, (left, top), (right, bottom), (0, 0, 255), thickness)  # Red
			
			# Draw corner markers
			corner_size = 8
			cv2.line(frame, (left, top), (left + corner_size, top), (0, 0, 255), thickness)
			cv2.line(frame, (left, top), (left, top + corner_size), (0, 0, 255), thickness)
			cv2.line(frame, (right, top), (right - corner_size, top), (0, 0, 255), thickness)
			cv2.line(frame, (right, top), (right, top + corner_size), (0, 0, 255), thickness)
			cv2.line(frame, (left, bottom), (left + corner_size, bottom), (0, 0, 255), thickness)
			cv2.line(frame, (left, bottom), (left, bottom - corner_size), (0, 0, 255), thickness)
			cv2.line(frame, (right, bottom), (right - corner_size, bottom), (0, 0, 255), thickness)
			cv2.line(frame, (right, bottom), (right, bottom - corner_size), (0, 0, 255), thickness)
			
			# Draw "Unknown" label
			text_x = max(5, min(left, frame_width - 80))
			text_y = max(20, min(top - 10, frame_height - 5))
			
			# Draw text background
			cv2.rectangle(frame, (text_x - 2, text_y - 20), (text_x + 80, text_y + 2), (0, 0, 0), -1)
			
			# Draw text
			cv2.putText(frame, "Unknown", (text_x, text_y), 
					   cv2.FONT_HERSHEY_SIMPLEX, 0.6, (0, 0, 255), 2)
			
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
				# Get detailed information from teaching load and related data
				teaching_load_url = f"{API_BASE}/teaching-load-details"
				payload = {
					"teaching_load_id": teaching_load_id,
					"faculty_id": faculty_id,
					"camera_id": camera_id
				}
				
				# Try to get detailed information
				details_response = requests.post(teaching_load_url, json=payload, timeout=3)
				if details_response.status_code == 200:
					details = details_response.json()
					room_name = details.get("room_name", f"Room {cam.get('room_no')}")
					building_no = details.get("building_no", "Unknown")
					camera_name = details.get("camera_name", f"Camera {camera_id}")
					faculty_full_name = details.get("faculty_full_name", faculty_name or "Unknown")
				else:
					# Fallback to basic information
					room_name = f"Room {cam.get('room_no')}"
					building_no = "Unknown"
					camera_name = f"Camera {camera_id}"
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
				
				# Post recognition log
				response = requests.post(f"{API_BASE}/recognition-logs", json=log_data, timeout=5)
				if response.status_code not in [200, 201]:
					print(f"Failed to log recognition event: {response.status_code}")
			except Exception as e:
				print(f"Error posting recognition log: {e}")
		
		# Post asynchronously to avoid blocking recognition
		threading.Thread(target=post_log, daemon=True).start()
		
	except Exception as e:
		print(f"Error in log_recognition_event: {e}")

def process_frame_for_recognition(frame, camera_id, scale_factor=1.0):
	"""Optimized frame processing for face recognition."""
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
		
		# Debug: Print face detection info (commented out to avoid console spam)
		# if face_locations:
		#	print(f"DEBUG: Detected {len(face_locations)} faces in frame")
		
		# Process each detected face
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
			teaching_load_id = sched.get("teaching_load_id") if sched else None
			
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
			else:
				scaled_face_location = face_location
			
			# Apply smoothing to reduce jitter
			smoothed_location = smooth_face_position(camera_id, scaled_face_location, best_match)
			
			# Draw bounding box directly on frame for stable display
			draw_stable_bounding_box(frame, smoothed_location, best_match, is_scheduled, presence_info, faculty_full_name)
			
			# Update recognition results
			import pytz
			tz = pytz.timezone("Asia/Manila")
			now = datetime.datetime.now(tz)
			_recognition_results[camera_id].update({
				"last_seen": now.isoformat(),
				"faculty_id": best_match,
				"status": "recognized" if best_match else "unknown_face",
				"distance": best_distance if best_match else None,
				"teaching_load_id": sched.get("teaching_load_id") if sched else None,
				"timestamp": now.isoformat()
			})
		
		# Bounding boxes are drawn directly on the frame
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
                    # Use simple scaling with better face detection
                    h, w = frame.shape[:2]
                    scale = FRAME_SCALE_FACTOR if max(h, w) > MAX_FRAME_SIZE else 1.0
                    
                    if scale != 1.0:
                        # Resize frame for processing
                        small = cv2.resize(frame, (int(w * scale), int(h * scale)))
                        # Process on scaled frame
                        annotated_small = process_frame_for_recognition(small, self.camera_id, scale)
                        # Resize back to original size
                        if annotated_small is not None:
                            frame = cv2.resize(annotated_small, (w, h))
                    else:
                        # Process on original frame
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
    
    if has_actual_recognition:
        print(f"DEBUG: Using actual recognition times: {recognition_times['time_in']}")
        return {
            "record_time_in": recognition_times["time_in"],
            "record_time_out": recognition_times["time_out"] or "N/A",
            "time_duration_seconds": recognition_times["total_duration"] or 0
        }
    else:
        # No actual recognition data available, use N/A
        print(f"DEBUG: No recognition data, using N/A")
        return {
            "record_time_in": "N/A",
            "record_time_out": "N/A", 
            "time_duration_seconds": 0
        }

# -------------------
# Attendance dedup
# -------------------
def _post_attendance_dedup(payload):
    try:
        # Check if attendance already exists
        check_response = requests.post(ATTENDANCE_CHECK_ENDPOINT, json={
            "faculty_id": payload["faculty_id"],
            "teaching_load_id": payload["teaching_load_id"]
        }, timeout=5)
        
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
        post_response = requests.post(ATTENDANCE_ENDPOINT, json=payload, timeout=6)
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

    try:
        # Use shared capture system
        cap = get_or_create_shared_capture(camera_id, camera_feed)
        if not cap or not cap.isOpened():
            print(f"❌ Could not open shared capture for camera {camera_id}: {camera_feed}")
            return

        print(f"🎥 Background processing started for camera {camera_id} (Room {room_no})")

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

            # Update shared frame for all connections
            update_shared_frame(camera_id, frame)

            now = time.time()
            if now - last_recognition_time >= BACKGROUND_RECOGNITION_INTERVAL:
                try:
                    # Use simple scaling for background processing
                    h, w = frame.shape[:2]
                    scale = FRAME_SCALE_FACTOR if max(h, w) > MAX_FRAME_SIZE else 1.0
                    if scale != 1.0:
                        # Resize frame for processing
                        small = cv2.resize(frame, (int(w * scale), int(h * scale)))
                        # Process on scaled frame
                        processed_frame = process_frame_for_recognition(small, camera_id, scale)
                        # Update shared frame with processed version
                        if processed_frame is not None:
                            update_shared_frame(camera_id, cv2.resize(processed_frame, (w, h)))
                        else:
                            # If processing fails, use original frame
                            update_shared_frame(camera_id, frame)
                    else:
                        # Process on original frame
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
        
        return web.json_response({
            "results": results_json,
            "schedules": schedules_json,
            "presence_accumulator": presence_accumulator_json,
            "late_tracking": late_tracking_json,
            "recognition_tracking": recognition_tracking_json
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

async def update_embeddings(request):
    body = await request.json()
    faculty_id = body.get("faculty_id")
    
    if faculty_id:
        print(f"Triggering embedding update for specific faculty_id: {faculty_id}")
        threading.Thread(target=update_faculty_embeddings_from_images, args=(faculty_id,), daemon=True).start()
        return web.json_response({"status": "ok", "message": f"Embedding update triggered for faculty_id {faculty_id}"})
    else:
        print("Triggering embedding update for all faculty")
        threading.Thread(target=update_faculty_embeddings_from_images, args=(None,), daemon=True).start()
        return web.json_response({"status": "ok", "message": "Embedding update triggered for all faculty"})

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
            
            # Check for late threshold (30 minutes from class start)
            check_late_threshold()
            
            # Check for schedule endings and mark absent
            check_schedule_end_and_mark_absent()
            
            # Clean up old recognition tracking data
            cleanup_old_recognition_tracking()
            
            # Refresh data every 5 minutes
            fetch_cameras()
            fetch_today_schedule()
            fetch_faculty_embeddings()

            # Ensure background recognition threads are running
            _start_background_recognition()
            
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
    fetch_cameras()
    fetch_today_schedule()
    fetch_faculty_embeddings()

    app = web.Application()
    app.router.add_post("/offer/{camera_id}", offer)
    app.router.add_get("/status", status)
    app.router.add_get("/health", health)
    app.router.add_post("/update-embeddings", update_embeddings)
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
    web.run_app(app, host="0.0.0.0", port=port)

if __name__ == "__main__":
    main()
