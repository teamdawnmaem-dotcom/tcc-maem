@extends('layouts.appChecker')

@section('title', 'Live Camera Feed - Tagoloan Community College')
@section('monitoring-active', 'active')
@section('live-camera-active', 'active')

@section('styles')
    <style>
        .faculty-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 32px;
        }

        .faculty-title-group {
            display: flex;
            flex-direction: column;
        }

        .faculty-title {
            font-size: 1.84rem;
            font-weight: bold;
            color: #6d0000;
        }

        .faculty-subtitle {
            font-size: 0.8rem;
            color: #666;
            margin-bottom: 24px;            
        }

        .faculty-actions-row {
            display: flex;
            gap: 8px;
        }

        .camera-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 16px;
            margin-top: 16px;
        }

        .camera-feed {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.22), 0 1.5px 8px rgba(0, 0, 0, 0.12);
            overflow: hidden;
            cursor: pointer;
            transition: transform 0.2s;
            display: flex;
            flex-direction: column;
            min-height: 200px;
        }

        .camera-feed.no-feed-available {
            cursor: not-allowed;
            opacity: 0.6;
            pointer-events: none;
        }

        .camera-feed:hover {
            transform: translateY(-2px);
        }

        .camera-label {
            background: #8B0000;
            color: #fff;
            padding: 12px;
            font-size: 0.88rem;
            font-weight: bold;
            text-align: center;
            position: relative;
            z-index: 10;
            display: block !important;
        }

        .no-feed {
            height: 160px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #999;
            font-size: 0.8rem;
            flex: 1;
            min-height: 160px;
        }

        #webrtc-player-detail {
            width: 100%;
            height: 100%;
            object-fit: contain;
            flex: 1;
            background: #000;
        }

        .camera-feed video {
            width: 100%;
            height: 160px;
            object-fit: contain;
            flex: 1;
            background: #000;
        }

        #video-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 0;
        }

        .no-feed-icon {
            font-size: 2.4rem;
            margin-bottom: 8px;
            color: #ccc;
        }

        .camera-feed-container {
            display: flex;
            gap: 16px;
            margin-top: 16px;
            height: calc(100vh - 160px);
            min-height: 400px;
        }

        .main-camera-feed {
            flex: 2;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.22), 0 1.5px 8px rgba(0, 0, 0, 0.12);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .details-panel {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 16px;
            position: relative;
            height: 100%;
        }

        .combined-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.22), 0 1.5px 8px rgba(0, 0, 0, 0.12);
            overflow: hidden;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .lab-header {
            background: #8B0000;
            color: #fff;
            padding: 12px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: bold;
            font-size: 0.88rem;
        }

        .combined-card-content {
            padding: 20px;
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
        }

        .schedule-title {
            font-size: 1.04rem;
            font-weight: bold;
            color: #8B0000;
            margin-bottom: 16px;
            text-align: center;
            flex-shrink: 0;
        }

        .schedule-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 9.6px;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
            flex-shrink: 0;
        }

        .faculty-image-container {
            display: flex;
            justify-content: center;
            margin-bottom: 15px;
        }

        .faculty-image {
            width: 1.2in;
            height: 1.2in;
            object-fit: cover;
            border-radius: 6.4px;
            border: 1.6px solid #8B0000;
            box-shadow: 0 3.2px 6.4px rgba(0, 0, 0, 0.1);
        }

        .no-schedule-image {
            width: 1.2in;
            height: 1.2in;
            object-fit: cover;
            border-radius: 6.4px;
            border: 2px solid #ccc;
            box-shadow: 0 3.2px 6.4px rgba(0, 0, 0, 0.1);
            background: #f5f5f5;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #999;
            font-size: 0.64rem;
            text-align: center;
        }

        .schedule-label {
            font-weight: bold;
            color: #333;
            min-width: 104px;
        }

        .schedule-value {
            color: #666;
            text-align: right;
        }

        .back-btn {
            background: #8B0000;
            color: #fff;
            border: none;
            border-radius: 4.8px;
            padding: 9.6px 16px;
            font-size: 0.8rem;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s;
            position: absolute;
            top: 104px;
            right: 32px;
            z-index: 100;
        }

        .back-btn:hover {
            background: #6d0000;
        }

        .attendance-section {
            margin-top: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.22), 0 1.5px 8px rgba(0, 0, 0, 0.12);
            overflow: hidden;
            display: none;
        }

        .attendance-title {
            background: #8B0000;
            color: #fff;
            padding: 12px;
            font-size: 0.88rem;
            font-weight: bold;
            text-align: center;
        }

        .recognition-status {
            margin-top: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.22), 0 1.5px 8px rgba(0, 0, 0, 0.12);
            overflow: hidden;
            display: none;
            max-height: 320px;
        }

        .recognition-title {
            background: #8B0000;
            color: #fff;
            padding: 12px;
            font-size: 0.88rem;
            font-weight: bold;
            text-align: center;
        }

        .attendance-table {
            width: 100%;
            border-collapse: collapse;
        }

        .attendance-table-container {
            max-height: 300px;
            overflow-y: auto;
        }

        .scheduled-faculty {
            color: #28a745;
            font-weight: bold;
        }

        .unscheduled-faculty {
            color: #ff8c00;
            font-weight: bold;
        }

        .attendance-table th {
            background: #f5f5f5;
            color: #333;
            padding: 9.6px;
            font-size: 0.72rem;
            font-weight: bold;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .attendance-table td {
            padding: 9.6px;
            font-size: 0.72rem;
            border-bottom: 1px solid #eee;
        }

        .attendance-table tr:nth-child(even) {
            background: #f9f9f9;
        }

        .search-input {
            padding: 6.4px;
            font-size: 11.2px;
            border: 1px solid #ccc;
            border-radius: 3.2px;
        }

        .search-btn {
            padding: 6.4px 9.6px;
            font-size: 11.2px;
            border: 1px solid #bbb;
            border-radius: 3.2px;
            background-color: #fff;
            color: #222;
            cursor: pointer;
        }
    </style>
@endsection

@section('content')
<div class="faculty-header">
    <div class="faculty-title-group">
        <div class="faculty-title">Live Camera Feed</div>
        <div class="faculty-subtitle"></div>
    </div>
</div>

<!-- Camera Grid -->
<div id="cameraGridView" class="camera-grid">
    @forelse($cameras as $camera)
        <div class="camera-feed" onclick="showCameraDetail('{{ $camera->camera_id }}')">
            <div class="camera-label">{{ $camera->room_name }}</div>
            
            <!-- Grid view recording player -->
            <div id="video-container-{{ $camera->camera_id }}" style="flex: 1; min-height: 160px; background: #000; position: relative;">
                <video 
                    id="recording-player-{{ $camera->camera_id }}" 
                    autoplay 
                    playsinline 
                    muted 
                    loop
                    style="width:100%; height:100%; object-fit: contain; display: none;"
                    onended="playNextRecording('{{ $camera->camera_id }}')"
                    onerror="handleRecordingError(this, '{{ $camera->camera_id }}')"
                ></video>
                
                <div class="no-feed" id="no-recording-message-{{ $camera->camera_id }}">
                    <div class="no-feed-icon">&#10005;</div>
                    <div>No Recording</div>
                </div>
                
                <div style="position: absolute; top: 10px; right: 10px; background: rgba(0,0,0,0.7); color: white; padding: 5px 12px; border-radius: 20px; font-size: 0.85em; font-weight: 600; z-index: 10; display: none;" id="recording-info-{{ $camera->camera_id }}">
                    <span id="recording-counter-{{ $camera->camera_id }}">0 / 0</span>
                </div>
            </div>
        </div>
    @empty
        <div style="grid-column: 1 / -1; background:#fff; border-radius:10px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.08); padding: 40px; text-align:center; color:#777;">
            <div style="font-size:3rem; color:#ccc; line-height:1; margin-bottom:10px;">&#9432;</div>
            No cameras added yet
        </div>
    @endforelse
</div>

<!-- Camera Detail View -->
<div id="cameraDetailView" class="camera-feed-container" style="display: none;">
    <button class="back-btn" onclick="showCameraGrid()">Return</button>

    <div class="main-camera-feed">
        <div class="camera-label" id="main-camera-label">Camera Feed</div>
        <div id="video-container" style="flex: 1; min-height: 0; background: #000; position: relative;">
            
            <!-- Detail view recording player -->
            <video 
                id="recording-player-detail" 
                autoplay 
                playsinline 
                controls 
                style="width:100%; height:100%; object-fit: contain; display: none;"
                onended="playNextRecordingDetail()"
                onerror="handleRecordingErrorDetail(this)"
            ></video>
            
            <div class="no-feed" id="no-recording-message-detail">
                <div class="no-feed-icon">&#10005;</div>
                <div>No Recording</div>
            </div>
            
            <!-- Playlist controls overlay -->
            <div style="position: absolute; top: 10px; right: 10px; display: flex; gap: 10px; z-index: 10;">
                <button onclick="playPreviousRecordingDetail()" style="background: rgba(139,0,0,0.8); color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; font-weight: bold;">⏮️ Previous</button>
                <button onclick="playNextRecordingDetail()" style="background: rgba(139,0,0,0.8); color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; font-weight: bold;">Next ⏭️</button>
                <button onclick="restartPlaylistDetail()" style="background: rgba(139,0,0,0.8); color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; font-weight: bold;">🔄 Restart</button>
            </div>
            
            <div style="position: absolute; top: 10px; left: 10px; background: rgba(0,0,0,0.7); color: white; padding: 5px 12px; border-radius: 20px; font-size: 0.85em; font-weight: 600; z-index: 10; display: none;" id="recording-info-detail">
                <span id="recording-counter-detail">0 / 0</span>
            </div>
        </div>
    </div>
    
    <div class="details-panel">
        <div class="combined-card">
            <div class="lab-header">
                <span id="lab-building">LAB / BUILDING</span>
            </div>
            <div class="combined-card-content">
                <div class="schedule-title">SCHEDULE</div>
                <div class="faculty-image-container">
                    <img id="faculty-image" class="faculty-image" src="" alt="Faculty Image" style="display: none;">
                    <div id="no-schedule-image" class="no-schedule-image" style="display: none;">
                        No Schedule
                    </div>
                </div>
                <div class="schedule-item">
                    <span class="schedule-label">INSTRUCTOR:</span>
                    <span class="schedule-value" id="schedule-instructor"></span>
                </div>
                <div class="schedule-item">
                    <span class="schedule-label">COURSE:</span>
                    <span class="schedule-value" id="schedule-course"></span>
                </div>
                <div class="schedule-item">
                    <span class="schedule-label">CLASS SECTION:</span>
                    <span class="schedule-value" id="schedule-class-section"></span>
                </div>
                <div class="schedule-item">
                    <span class="schedule-label">DEPARTMENT:</span>
                    <span class="schedule-value" id="schedule-department"></span>
                </div>
                <div class="schedule-item">
                    <span class="schedule-label">DAY AND TIME:</span>
                    <span class="schedule-value" id="schedule-time"></span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Current Recognition Status -->
<div class="recognition-status" id="recognition-status-section">
    <div class="recognition-title">Current Recognition Status</div>
    <div class="attendance-table-container">
        <table class="attendance-table">
            <thead>
                <tr>
                    <th>Time</th>
                    <th>Camera</th>
                    <th>Faculty</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody id="recognition-logs-body">
                <tr>
                    <td colspan="4" style="text-align: center; padding: 40px; color: #999; font-style: italic;">
                        Waiting for data...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const cameras = @json($cameras);
    const teachingLoads = @json($teachingLoads);
    const faculties = @json($faculties);
    const recordings = @json($recordings);
    
    console.log('Faculties data loaded:', faculties);
    console.log('Recordings loaded:', recordings.length);
    if (recordings.length > 0) {
        console.log('Sample recording:', recordings[0]);
        console.log('Sample recording start_time:', recordings[0].start_time, 'type:', typeof recordings[0].start_time);
    }

    const scheduleRefreshMs = 30000; // refresh schedule every 30s
    const recordingRefreshMs = 1000; // refresh recordings every 1s
    let scheduleIntervalId = null;
    let recordingRefreshIntervalId = null;
    
    // Track last known recording IDs to detect new recordings
    let lastKnownRecordingIds = new Set(recordings.map(r => r.recording_id));
    
    // Track current schedule for each camera to detect schedule changes
    const currentScheduleByCamera = {};
    
    // Group recordings by camera_id
    const recordingsByCamera = {};
    const recordingsById = {};
    let currentDetailCameraId = null;
    let currentDetailPlaylist = [];
    let currentDetailIndex = 0;
    
    // Initialize recordings data
    recordings.forEach(recording => {
        if (!recordingsByCamera[recording.camera_id]) {
            recordingsByCamera[recording.camera_id] = [];
        }
        recordingsByCamera[recording.camera_id].push(recording);
        recordingsById[recording.recording_id] = recording;
    });
    
    // Sort recordings by start_time (oldest first for sequential playback)
    Object.keys(recordingsByCamera).forEach(cameraId => {
        recordingsByCamera[cameraId].sort((a, b) => {
            return new Date(a.start_time) - new Date(b.start_time);
        });
    });
    
    // Build video URL helper (from test-recordings.html)
    function buildVideoUrl(recording) {
        let url = '';
        
        if (recording.filepath) {
            let normalizedPath = recording.filepath.trim();
            if (normalizedPath.startsWith('/')) {
                normalizedPath = normalizedPath.substring(1);
            }
            
            if (normalizedPath.includes('stream_recordings')) {
                if (!normalizedPath.startsWith('stream_recordings/')) {
                    const parts = normalizedPath.split('stream_recordings/');
                    if (parts.length > 1) {
                        normalizedPath = `stream_recordings/${parts[parts.length - 1]}`;
                    }
                }
                url = `${window.location.origin}/storage/${normalizedPath}`;
            } else if (normalizedPath.startsWith('storage/')) {
                url = `${window.location.origin}/${normalizedPath}`;
            } else {
                url = `${window.location.origin}/storage/stream_recordings/${normalizedPath}`;
            }
        }
        
        if (!url && recording.filename) {
            url = `${window.location.origin}/storage/stream_recordings/${recording.filename}`;
        }
        
        if (!url && recording.recording_id) {
            url = `${window.location.origin}/api/stream-recordings/${recording.recording_id}/stream`;
        }
        
        return url;
    }
    
    // Filter recordings by current date and teaching load time
    // Based on start_time column from tbl_stream_recordings
    function filterRecordingsBySchedule(recordings, cameraId) {
        // Get current teaching load for the camera's room
        const camera = cameras.find(cam => cam.camera_id == cameraId);
        if (!camera) {
            console.log(`[filterRecordingsBySchedule] Camera ${cameraId} not found`);
            return { recordings: [], hasActiveSchedule: false };
        }
        
        const currentLoad = getCurrentLoadForRoom(camera.room_no);
        
        // If there's no active schedule, return empty with flag
        if (!currentLoad) {
            console.log(`[filterRecordingsBySchedule] No active schedule for camera ${cameraId}, room ${camera.room_no}`);
            return { recordings: [], hasActiveSchedule: false };
        }
        
        console.log(`[filterRecordingsBySchedule] Active schedule found for camera ${cameraId}:`, currentLoad);
        console.log(`[filterRecordingsBySchedule] Total recordings for camera: ${recordings.length}`);
        
        // Get current date in Asia/Manila timezone (proper method)
        const now = new Date();
        const manilaDateStr = now.toLocaleDateString('en-CA', { timeZone: 'Asia/Manila' }); // Returns YYYY-MM-DD format
        const todayDateStr = manilaDateStr; // Already in YYYY-MM-DD format
        
        console.log(`[filterRecordingsBySchedule] Today's date (Asia/Manila): ${todayDateStr}`);
        
        // Filter recordings by current date using start_time from tbl_stream_recordings
        // start_time is datetime type - Laravel serializes as ISO 8601 format (e.g., 2025-11-05T10:33:37.000000Z)
        // or MySQL format (e.g., 2025-11-05 10:33:37)
        let filteredRecordings = recordings.filter(recording => {
            // Check if start_time exists (from tbl_stream_recordings.start_time column)
            if (!recording.start_time) {
                console.log(`[filterRecordingsBySchedule] Recording ${recording.recording_id} has no start_time`);
                return false;
            }
            
            // Parse start_time - datetime type from database
            // IMPORTANT: Python service stores time as "YYYY-MM-DD HH:MM:SS" in Asia/Manila timezone
            // Laravel serializes it as ISO 8601 UTC (with 'Z'), but the original value is Asia/Manila
            let recordingDate;
            if (typeof recording.start_time === 'string') {
                // Check if it's ISO format (contains 'T')
                if (recording.start_time.includes('T')) {
                    // ISO 8601 format (e.g., 2025-11-06T15:08:00.000000Z)
                    // Problem: Python stores "2025-11-06 15:08:00" as Asia/Manila time
                    // Laravel serializes it as "2025-11-06T15:08:00Z" (treating as UTC)
                    // JavaScript then converts UTC to local timezone, causing date shifts
                    // Solution: Extract UTC components and treat them as Asia/Manila time directly
                    const utcDate = new Date(recording.start_time);
                    
                    // Get UTC time components (these represent the original Asia/Manila time)
                    const utcYear = utcDate.getUTCFullYear();
                    const utcMonth = utcDate.getUTCMonth();
                    const utcDay = utcDate.getUTCDate();
                    
                    // Create a date object using UTC components but interpret as local (Asia/Manila)
                    // This effectively treats the UTC time as if it were already in Asia/Manila
                    recordingDate = new Date(utcYear, utcMonth, utcDay);
                } else if (recording.start_time.match(/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/)) {
                    // MySQL format (e.g., 2025-11-05 10:33:37) - already in Asia/Manila timezone
                    const [datePart, timePart] = recording.start_time.split(' ');
                    const [year, month, day] = datePart.split('-').map(Number);
                    // Create date treating the time as Asia/Manila timezone (local time)
                    recordingDate = new Date(year, month - 1, day);
                } else {
                    recordingDate = new Date(recording.start_time);
                }
            } else {
                recordingDate = new Date(recording.start_time);
            }
            
            // Extract date directly from the date components (treating as Asia/Manila)
            // Since we've already adjusted for timezone, we can use local date methods
            const recordingDateStr = `${recordingDate.getFullYear()}-${String(recordingDate.getMonth() + 1).padStart(2, '0')}-${String(recordingDate.getDate()).padStart(2, '0')}`;
            
            // Match recordings from today's date
            const matches = recordingDateStr === todayDateStr;
            if (!matches) {
                console.log(`[filterRecordingsBySchedule] Recording ${recording.recording_id} date mismatch: ${recordingDateStr} !== ${todayDateStr}`);
            }
            return matches;
        });
        
        console.log(`[filterRecordingsBySchedule] Recordings after date filter: ${filteredRecordings.length}`);
        
        // Get today's day of week in Asia/Manila timezone
        const todayDayOfWeek = now.toLocaleString('en-US', { weekday: 'long', timeZone: 'Asia/Manila' });
        console.log(`[filterRecordingsBySchedule] Today's day of week: ${todayDayOfWeek}, Teaching load day: ${currentLoad.teaching_load_day_of_week}`);
        
        // Filter by day of week - ensure recording's day matches teaching load's day
        if (filteredRecordings.length > 0) {
            const beforeDayFilter = filteredRecordings.length;
            filteredRecordings = filteredRecordings.filter(recording => {
                if (!recording.start_time) return false;
                
                // Parse start_time to get the day of week
                // IMPORTANT: Use same UTC component extraction as date comparison
                let recordingDate;
                if (typeof recording.start_time === 'string') {
                    if (recording.start_time.includes('T')) {
                        // ISO format - extract UTC components and treat as Asia/Manila
                        const utcDate = new Date(recording.start_time);
                        const utcYear = utcDate.getUTCFullYear();
                        const utcMonth = utcDate.getUTCMonth();
                        const utcDay = utcDate.getUTCDate();
                        const utcHours = utcDate.getUTCHours();
                        const utcMinutes = utcDate.getUTCMinutes();
                        const utcSeconds = utcDate.getUTCSeconds();
                        // Create date using UTC components but interpret as local (Asia/Manila)
                        recordingDate = new Date(utcYear, utcMonth, utcDay, utcHours, utcMinutes, utcSeconds || 0);
                    } else if (recording.start_time.match(/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/)) {
                        // MySQL format - already in Asia/Manila timezone
                        const [datePart, timePart] = recording.start_time.split(' ');
                        const [year, month, day] = datePart.split('-').map(Number);
                        const [hour, minute, second] = timePart.split(':').map(Number);
                        recordingDate = new Date(year, month - 1, day, hour, minute, second || 0);
                    } else {
                        recordingDate = new Date(recording.start_time);
                    }
                } else {
                    recordingDate = new Date(recording.start_time);
                }
                
                // Get recording's day of week using local date methods (already adjusted for timezone)
                const recordingDayOfWeek = recordingDate.toLocaleString('en-US', { weekday: 'long' });
                
                // Match day of week with teaching load's day of week
                const dayMatches = recordingDayOfWeek === currentLoad.teaching_load_day_of_week;
                if (!dayMatches) {
                    console.log(`[filterRecordingsBySchedule] Recording ${recording.recording_id} day mismatch: ${recordingDayOfWeek} !== ${currentLoad.teaching_load_day_of_week}`);
                }
                return dayMatches;
            });
            console.log(`[filterRecordingsBySchedule] Recordings after day of week filter: ${filteredRecordings.length} (was ${beforeDayFilter})`);
        }
        
        // Filter by time range (teaching load time) using start_time
        const timeIn = toMinutes(currentLoad.teaching_load_time_in);
        const timeOut = toMinutes(currentLoad.teaching_load_time_out);
        
        console.log(`[filterRecordingsBySchedule] Time range: ${currentLoad.teaching_load_time_in} - ${currentLoad.teaching_load_time_out} (${timeIn} - ${timeOut} minutes)`);
        
        if (timeIn != null && timeOut != null && filteredRecordings.length > 0) {
            const beforeTimeFilter = filteredRecordings.length;
            filteredRecordings = filteredRecordings.filter(recording => {
                // Ensure start_time exists (from tbl_stream_recordings.start_time column)
                if (!recording.start_time) return false;
                
                console.log(`[filterRecordingsBySchedule] Processing recording ${recording.recording_id}, raw start_time: ${recording.start_time}, type: ${typeof recording.start_time}`);
                
                // Parse start_time - datetime type from database
                // IMPORTANT: Python service stores time as "YYYY-MM-DD HH:MM:SS" in Asia/Manila timezone
                // Laravel serializes it as ISO 8601 UTC (with 'Z'), but the original value is Asia/Manila
                let recordingDate;
                if (typeof recording.start_time === 'string') {
                    // Check if it's ISO format (contains 'T')
                    if (recording.start_time.includes('T')) {
                        // ISO 8601 format (e.g., 2025-11-06T15:08:00.000000Z)
                        // Problem: Python stores "2025-11-06 15:08:00" as Asia/Manila time
                        // Laravel serializes it as "2025-11-06T15:08:00Z" (treating as UTC)
                        // JavaScript then converts UTC to local timezone, making it 23:08 in Asia/Manila
                        // Solution: Extract UTC components and treat them as Asia/Manila time directly
                        const utcDate = new Date(recording.start_time);
                        
                        // Get UTC time components (these represent the original Asia/Manila time)
                        const utcHours = utcDate.getUTCHours();
                        const utcMinutes = utcDate.getUTCMinutes();
                        const utcSeconds = utcDate.getUTCSeconds();
                        const utcYear = utcDate.getUTCFullYear();
                        const utcMonth = utcDate.getUTCMonth();
                        const utcDay = utcDate.getUTCDate();
                        
                        // Create a date object using UTC components but interpret as local (Asia/Manila)
                        // This effectively treats the UTC time as if it were already in Asia/Manila
                        recordingDate = new Date(utcYear, utcMonth, utcDay, utcHours, utcMinutes, utcSeconds || 0);
                        
                        console.log(`[filterRecordingsBySchedule] Recording ${recording.recording_id} ISO format: UTC components ${utcYear}-${utcMonth+1}-${utcDay} ${utcHours}:${utcMinutes} treated as Manila time`);
                    } else if (recording.start_time.match(/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/)) {
                        // MySQL format (e.g., 2025-11-05 10:33:37) - already in Asia/Manila timezone
                        const [datePart, timePart] = recording.start_time.split(' ');
                        const [year, month, day] = datePart.split('-').map(Number);
                        const [hour, minute, second] = timePart.split(':').map(Number);
                        // Create date treating the time as Asia/Manila timezone (local time)
                        recordingDate = new Date(year, month - 1, day, hour, minute, second || 0);
                        console.log(`[filterRecordingsBySchedule] Recording ${recording.recording_id} MySQL format parsed as: ${year}-${month}-${day} ${hour}:${minute}:${second}`);
                    } else {
                        recordingDate = new Date(recording.start_time);
                    }
                } else {
                    recordingDate = new Date(recording.start_time);
                }
                
                // Extract time directly from the date components (treating as Asia/Manila)
                // Since we've already adjusted for timezone, we can use local time methods
                const recordingHours = recordingDate.getHours();
                const recordingMins = recordingDate.getMinutes();
                const recordingTimeStr = `${String(recordingHours).padStart(2, '0')}:${String(recordingMins).padStart(2, '0')}`;
                const recordingMinutes = recordingHours * 60 + recordingMins;
                
                // Also log the raw date object for debugging
                console.log(`[filterRecordingsBySchedule] Recording ${recording.recording_id} parsed date: ${recordingDate.toISOString()}, Manila time: ${recordingTimeStr} (${recordingMinutes} min)`);
                
                // Check if recording start_time falls within the teaching load time range
                const inRange = recordingMinutes != null && recordingMinutes >= timeIn && recordingMinutes <= timeOut;
                if (!inRange) {
                    console.log(`[filterRecordingsBySchedule] Recording ${recording.recording_id} time ${recordingTimeStr} (${recordingMinutes} min) outside range ${timeIn}-${timeOut}`);
                } else {
                    console.log(`[filterRecordingsBySchedule] Recording ${recording.recording_id} time ${recordingTimeStr} (${recordingMinutes} min) WITHIN range ${timeIn}-${timeOut} ✓`);
                }
                return inRange;
            });
            console.log(`[filterRecordingsBySchedule] Recordings after time filter: ${filteredRecordings.length} (was ${beforeTimeFilter})`);
        }
        
        console.log(`[filterRecordingsBySchedule] Final filtered recordings: ${filteredRecordings.length}`);
        return { recordings: filteredRecordings, hasActiveSchedule: true };
    }
    
    // Load recordings for a camera (grid view)
    function loadCameraRecordings(cameraId, preservePlayback = false) {
        const cameraRecordings = recordingsByCamera[cameraId] || [];
        console.log(`[loadCameraRecordings] Loading recordings for camera ${cameraId}, total: ${cameraRecordings.length}, preservePlayback: ${preservePlayback}`);
        
        // Filter recordings by current date and teaching load time
        const result = filterRecordingsBySchedule(cameraRecordings, cameraId);
        const filteredRecordings = result.recordings;
        const hasActiveSchedule = result.hasActiveSchedule;
        
        console.log(`[loadCameraRecordings] Filtered result: ${filteredRecordings.length} recordings, hasActiveSchedule: ${hasActiveSchedule}`);
        
        const video = document.getElementById(`recording-player-${cameraId}`);
        const noFeed = document.getElementById(`no-recording-message-${cameraId}`);
        const info = document.getElementById(`recording-info-${cameraId}`);
        
        if (!hasActiveSchedule) {
            if (video) video.style.display = 'none';
            if (noFeed) {
                noFeed.style.display = 'flex';
                noFeed.innerHTML = '<div class="no-feed-icon">✕</div><div>No Active Schedule</div>';
            }
            if (info) info.style.display = 'none';
            return;
        }
        
        if (filteredRecordings.length === 0) {
            if (video) video.style.display = 'none';
            if (noFeed) {
                noFeed.style.display = 'flex';
                noFeed.innerHTML = '<div class="no-feed-icon">&#10005;</div><div>No Recording</div>';
            }
            if (info) info.style.display = 'none';
            return;
        }
        
        const playlist = filteredRecordings.map(r => buildVideoUrl(r)).filter(url => url);
        if (playlist.length === 0) return;
        
        const counter = document.getElementById(`recording-counter-${cameraId}`);
        
        if (!video) return;
        
        // Check if video is currently playing and we want to preserve playback
        const isPlaying = !video.paused && !video.ended && video.currentTime > 0;
        const currentSrc = video.src;
        const currentIndex = preservePlayback && isPlaying ? parseInt(video.dataset.currentIndex) || 0 : 0;
        
        // If preserving playback and video is playing, only update playlist without interrupting
        if (preservePlayback && isPlaying && currentSrc) {
            // Check if current video is still in the new playlist
            const currentSrcInPlaylist = playlist.indexOf(currentSrc);
            if (currentSrcInPlaylist !== -1) {
                // Current video is still valid, just update playlist and counter
                video.dataset.playlist = JSON.stringify(playlist);
                video.dataset.currentIndex = currentSrcInPlaylist.toString();
                if (counter) counter.textContent = `${currentSrcInPlaylist + 1} / ${playlist.length}`;
                console.log(`[loadCameraRecordings] Preserved playback for camera ${cameraId}, updated playlist`);
                return;
            }
        }
        
        // Normal load: set new playlist and start from beginning (or preserved index)
        video.dataset.playlist = JSON.stringify(playlist);
        video.dataset.currentIndex = currentIndex.toString();
        video.src = playlist[currentIndex];
        video.load();
        
        if (noFeed) noFeed.style.display = 'none';
        video.style.display = 'block';
        if (info) info.style.display = 'block';
        if (counter) counter.textContent = `${currentIndex + 1} / ${playlist.length}`;
        
        video.muted = true;
        const playPromise = video.play();
        if (playPromise !== undefined) {
            playPromise.catch(e => {
                console.log(`Auto-play blocked for camera ${cameraId}:`, e.message);
            });
        }
    }
    
    // Play next recording in grid view
    function playNextRecording(cameraId) {
        const video = document.getElementById(`recording-player-${cameraId}`);
        if (!video) return;
        
        const currentIndex = parseInt(video.dataset.currentIndex) || 0;
        const playlist = JSON.parse(video.dataset.playlist || '[]');
        
        if (currentIndex < playlist.length - 1) {
            const nextIndex = currentIndex + 1;
            video.dataset.currentIndex = nextIndex;
            video.src = playlist[nextIndex];
            video.load();
            
            const counter = document.getElementById(`recording-counter-${cameraId}`);
            if (counter) counter.textContent = `${nextIndex + 1} / ${playlist.length}`;
            
            video.muted = true;
            video.play().catch(e => console.log('Play blocked:', e.message));
        } else {
            // Loop back to start
            video.dataset.currentIndex = '0';
            video.src = playlist[0];
            video.load();
            const counter = document.getElementById(`recording-counter-${cameraId}`);
            if (counter) counter.textContent = `1 / ${playlist.length}`;
            video.muted = true;
            video.play().catch(e => console.log('Play blocked:', e.message));
        }
    }
    
    // Handle recording error
    function handleRecordingError(videoElement, cameraId) {
        console.error(`Error loading recording for camera ${cameraId}`);
        const noFeed = document.getElementById(`no-recording-message-${cameraId}`);
        if (noFeed) noFeed.style.display = 'flex';
        if (videoElement) videoElement.style.display = 'none';
        
        // Try next recording
        setTimeout(() => playNextRecording(cameraId), 2000);
    }
    
    // Load recordings for detail view
    function loadDetailRecordings(cameraId, preservePlayback = false) {
        const cameraRecordings = recordingsByCamera[cameraId] || [];
        
        // Filter recordings by current date and teaching load time
        const result = filterRecordingsBySchedule(cameraRecordings, cameraId);
        const filteredRecordings = result.recordings;
        const hasActiveSchedule = result.hasActiveSchedule;
        
        currentDetailCameraId = cameraId;
        const newPlaylist = filteredRecordings.map(r => buildVideoUrl(r)).filter(url => url);
        
        const video = document.getElementById('recording-player-detail');
        const noFeed = document.getElementById('no-recording-message-detail');
        const info = document.getElementById('recording-info-detail');
        const counter = document.getElementById('recording-counter-detail');
        
        if (!hasActiveSchedule) {
            if (video) video.style.display = 'none';
            if (noFeed) {
                noFeed.style.display = 'flex';
                noFeed.innerHTML = '<div class="no-feed-icon">✕</div><div>No Active Schedule</div>';
            }
            if (info) info.style.display = 'none';
            return;
        }
        
        if (newPlaylist.length === 0) {
            if (video) video.style.display = 'none';
            if (noFeed) {
                noFeed.style.display = 'flex';
                noFeed.innerHTML = '<div class="no-feed-icon">&#10005;</div><div>No Recording</div>';
            }
            if (info) info.style.display = 'none';
            return;
        }
        
        if (!video) return;
        
        // Check if video is currently playing and we want to preserve playback
        const isPlaying = !video.paused && !video.ended && video.currentTime > 0;
        const currentSrc = video.src;
        
        // If preserving playback and video is playing, only update playlist without interrupting
        if (preservePlayback && isPlaying && currentSrc && currentDetailPlaylist.length > 0) {
            // Check if current video is still in the new playlist
            const currentSrcInPlaylist = newPlaylist.indexOf(currentSrc);
            if (currentSrcInPlaylist !== -1) {
                // Current video is still valid, just update playlist and counter
                currentDetailPlaylist = newPlaylist;
                currentDetailIndex = currentSrcInPlaylist;
                if (counter) counter.textContent = `${currentSrcInPlaylist + 1} / ${currentDetailPlaylist.length}`;
                console.log(`[loadDetailRecordings] Preserved playback, updated playlist`);
                return;
            }
        }
        
        // Normal load: set new playlist and start from beginning (or preserved index)
        currentDetailPlaylist = newPlaylist;
        currentDetailIndex = preservePlayback && isPlaying && currentSrc ? 
            Math.max(0, Math.min(currentDetailIndex, newPlaylist.length - 1)) : 0;
        
        video.src = currentDetailPlaylist[currentDetailIndex];
        video.load();
        
        if (noFeed) noFeed.style.display = 'none';
        video.style.display = 'block';
        if (info) info.style.display = 'block';
        if (counter) counter.textContent = `${currentDetailIndex + 1} / ${currentDetailPlaylist.length}`;
        
        video.muted = false; // Audio ON for detail view
        const playPromise = video.play();
        if (playPromise !== undefined) {
            playPromise.catch(e => {
                console.log('Auto-play blocked for detail view:', e.message);
            });
        }
    }
    
    // Play next recording in detail view
    function playNextRecordingDetail() {
        if (currentDetailPlaylist.length === 0) return;
        
        if (currentDetailIndex < currentDetailPlaylist.length - 1) {
            currentDetailIndex++;
        } else {
            currentDetailIndex = 0; // Loop back
        }
        
        const video = document.getElementById('recording-player-detail');
        const counter = document.getElementById('recording-counter-detail');
        
        if (video) {
            video.src = currentDetailPlaylist[currentDetailIndex];
            video.load();
            if (counter) counter.textContent = `${currentDetailIndex + 1} / ${currentDetailPlaylist.length}`;
            video.play().catch(e => console.log('Play blocked:', e.message));
        }
    }
    
    // Play previous recording in detail view
    function playPreviousRecordingDetail() {
        if (currentDetailPlaylist.length === 0) return;
        
        if (currentDetailIndex > 0) {
            currentDetailIndex--;
        } else {
            currentDetailIndex = currentDetailPlaylist.length - 1; // Loop to end
        }
        
        const video = document.getElementById('recording-player-detail');
        const counter = document.getElementById('recording-counter-detail');
        
        if (video) {
            video.src = currentDetailPlaylist[currentDetailIndex];
            video.load();
            if (counter) counter.textContent = `${currentDetailIndex + 1} / ${currentDetailPlaylist.length}`;
            video.play().catch(e => console.log('Play blocked:', e.message));
        }
    }
    
    // Restart playlist in detail view
    function restartPlaylistDetail() {
        if (currentDetailPlaylist.length === 0) return;
        
        currentDetailIndex = 0;
        const video = document.getElementById('recording-player-detail');
        const counter = document.getElementById('recording-counter-detail');
        
        if (video) {
            video.src = currentDetailPlaylist[0];
            video.load();
            if (counter) counter.textContent = `1 / ${currentDetailPlaylist.length}`;
            video.play().catch(e => console.log('Play blocked:', e.message));
        }
    }
    
    // Handle recording error in detail view
    function handleRecordingErrorDetail(videoElement) {
        console.error('Error loading recording in detail view');
        const noFeed = document.getElementById('no-recording-message-detail');
        if (noFeed) noFeed.style.display = 'flex';
        if (videoElement) videoElement.style.display = 'none';
        
        // Try next recording
        setTimeout(() => playNextRecordingDetail(), 2000);
    }

    // Fetch new recordings from API
    async function fetchNewRecordings() {
        try {
            const response = await fetch('/api/stream-recordings');
            if (!response.ok) {
                console.error('[fetchNewRecordings] Failed to fetch recordings:', response.status);
                return false;
            }
            
            const data = await response.json();
            const newRecordings = data.data || data; // Handle paginated or direct array response
            
            // Check if there are new recordings
            const currentRecordingIds = new Set(newRecordings.map(r => r.recording_id));
            const hasNewRecordings = Array.from(currentRecordingIds).some(id => !lastKnownRecordingIds.has(id));
            
            if (hasNewRecordings) {
                console.log('[fetchNewRecordings] New recordings detected, updating...');
                
                // Update recordingsByCamera with new data
                Object.keys(recordingsByCamera).forEach(cameraId => {
                    recordingsByCamera[cameraId] = [];
                });
                
                newRecordings.forEach(recording => {
                    if (!recordingsByCamera[recording.camera_id]) {
                        recordingsByCamera[recording.camera_id] = [];
                    }
                    recordingsByCamera[recording.camera_id].push(recording);
                    recordingsById[recording.recording_id] = recording;
                });
                
                // Sort recordings by start_time (oldest first for sequential playback)
                Object.keys(recordingsByCamera).forEach(cameraId => {
                    recordingsByCamera[cameraId].sort((a, b) => {
                        return new Date(a.start_time) - new Date(b.start_time);
                    });
                });
                
                // Update last known IDs
                lastKnownRecordingIds = currentRecordingIds;
                
                // Reload recordings for all cameras in grid view (preserve playback if playing)
                cameras.forEach(cam => {
                    loadCameraRecordings(cam.camera_id, true); // preservePlayback = true
                });
                
                // If detail view is open, reload its recordings too (preserve playback if playing)
                if (currentDetailCameraId) {
                    loadDetailRecordings(currentDetailCameraId, true); // preservePlayback = true
                }
                
                return true;
            }
            
            return false;
        } catch (error) {
            console.error('[fetchNewRecordings] Error fetching recordings:', error);
            return false;
        }
    }

    // Check for schedule changes and reload recordings if schedule changed
    function checkScheduleChanges() {
        let scheduleChanged = false;
        
        cameras.forEach(cam => {
            const currentLoad = getCurrentLoadForRoom(cam.room_no);
            const currentScheduleId = currentLoad ? currentLoad.teaching_load_id : null;
            const previousScheduleId = currentScheduleByCamera[cam.camera_id];
            
            // Check if schedule changed
            if (currentScheduleId !== previousScheduleId) {
                console.log(`[checkScheduleChanges] Schedule changed for camera ${cam.camera_id}: ${previousScheduleId} -> ${currentScheduleId}`);
                currentScheduleByCamera[cam.camera_id] = currentScheduleId;
                scheduleChanged = true;
                
                // Reload recordings for this camera (don't preserve playback when schedule changes)
                loadCameraRecordings(cam.camera_id, false);
            }
        });
        
        // If detail view is open, check its schedule too
        if (currentDetailCameraId) {
            const camera = cameras.find(cam => cam.camera_id == currentDetailCameraId);
            if (camera) {
                const currentLoad = getCurrentLoadForRoom(camera.room_no);
                const currentScheduleId = currentLoad ? currentLoad.teaching_load_id : null;
                const previousScheduleId = currentScheduleByCamera[`detail-${currentDetailCameraId}`];
                
                if (currentScheduleId !== previousScheduleId) {
                    console.log(`[checkScheduleChanges] Schedule changed for detail view camera ${currentDetailCameraId}: ${previousScheduleId} -> ${currentScheduleId}`);
                    currentScheduleByCamera[`detail-${currentDetailCameraId}`] = currentScheduleId;
                    scheduleChanged = true;
                    
                    // Reload recordings for detail view (don't preserve playback when schedule changes)
                    loadDetailRecordings(currentDetailCameraId, false);
                    updateSchedulePanel(camera);
                }
            }
        }
        
        return scheduleChanged;
    }

    window.addEventListener("DOMContentLoaded", () => {
        // Initialize current schedule tracking for all cameras
        cameras.forEach(cam => {
            const currentLoad = getCurrentLoadForRoom(cam.room_no);
            currentScheduleByCamera[cam.camera_id] = currentLoad ? currentLoad.teaching_load_id : null;
        });
        
        // Load recordings for all cameras in grid view
        cameras.forEach(cam => {
            loadCameraRecordings(cam.camera_id);
        });
        
        // Check for schedule changes every 5 seconds (more frequent than 30s to catch transitions)
        setInterval(() => {
            checkScheduleChanges();
        }, 5000);
        
        // Reload recordings periodically to update with current schedule (preserve playback)
        setInterval(() => {
            cameras.forEach(cam => {
                loadCameraRecordings(cam.camera_id, true); // preservePlayback = true
            });
        }, scheduleRefreshMs);
        
        // Fetch new recordings every second
        recordingRefreshIntervalId = setInterval(() => {
            fetchNewRecordings();
        }, recordingRefreshMs);
        
        // Update schedule panel periodically
        if (scheduleIntervalId) clearInterval(scheduleIntervalId);
    });

    async function fetchRecognitionStatus() {
        // Recognition status fetching removed - not needed for recordings view
        // This can be re-enabled if needed in the future
    }
	
	// Note: Recognition status fetching is now handled in DOMContentLoaded

	// Helpers to pick current schedule by time within Asia/Manila
	function minutesSinceMidnightAsiaManila(date) {
        const opts = { timeZone: 'Asia/Manila', hour12: false, hour: '2-digit', minute: '2-digit' };
        const parts = new Intl.DateTimeFormat('en-GB', opts).formatToParts(date);
        const h = parseInt(parts.find(p => p.type === 'hour').value, 10);
        const m = parseInt(parts.find(p => p.type === 'minute').value, 10);
        return h * 60 + m;
    }

    function toMinutes(timeStr) {
        if (!timeStr) return null;
        const [hh, mm] = timeStr.split(':');
        const h = parseInt(hh, 10);
        const m = parseInt(mm, 10);
        return h * 60 + m;
    }

    function getCurrentLoadForRoom(roomNo) {
        const now = new Date();
        const dayName = now.toLocaleString('en-US', { weekday: 'long', timeZone: 'Asia/Manila' });
        const nowMin = minutesSinceMidnightAsiaManila(now);
        const todaysLoads = teachingLoads.filter(tl => tl.room_no == roomNo && tl.teaching_load_day_of_week === dayName);
        return todaysLoads.find(tl => {
            const start = toMinutes(tl.teaching_load_time_in);
            const end = toMinutes(tl.teaching_load_time_out);
            return start != null && end != null && nowMin >= start && nowMin <= end;
        }) || null;
    }

    function updateSchedulePanel(camera) {
        console.log('updateSchedulePanel called for camera:', camera);
        const load = getCurrentLoadForRoom(camera.room_no);
        console.log('Current load for room', camera.room_no, ':', load);
        
        if (load) {
            const faculty = faculties.find(f => f.faculty_id == load.faculty_id);
            console.log('Found faculty:', faculty);
            
            // Format times to 12-hour format
            const timeIn = new Date(`2000-01-01T${load.teaching_load_time_in}`).toLocaleTimeString('en-US', { 
                hour: 'numeric', 
                minute: '2-digit',
                hour12: true 
            });
            const timeOut = new Date(`2000-01-01T${load.teaching_load_time_out}`).toLocaleTimeString('en-US', { 
                hour: 'numeric', 
                minute: '2-digit',
                hour12: true 
            });
            document.getElementById('schedule-time').textContent = `${load.teaching_load_day_of_week}, ${timeIn} - ${timeOut}`;
            document.getElementById('schedule-instructor').textContent = `${faculty.faculty_fname} ${faculty.faculty_lname}`;
            document.getElementById('schedule-course').textContent = `${load.teaching_load_course_code} ${load.teaching_load_subject}`;
            document.getElementById('schedule-class-section').textContent = load.teaching_load_class_section ?? '-';
            document.getElementById('schedule-department').textContent = faculty.faculty_department ?? '';
            
            // Update faculty image
            updateFacultyImage(faculty);
        } else {
            console.log('No current schedule found for room:', camera.room_no);
            document.getElementById('schedule-time').textContent = 'No schedule';
            document.getElementById('schedule-instructor').textContent = '-';
            document.getElementById('schedule-course').textContent = '-';
            document.getElementById('schedule-class-section').textContent = '-';
            document.getElementById('schedule-department').textContent = '-';
            
            // Hide faculty image and show no schedule
            const facultyImage = document.getElementById('faculty-image');
            const noScheduleImage = document.getElementById('no-schedule-image');
            facultyImage.style.display = 'none';
            noScheduleImage.style.display = 'flex';
        }
    }

    function updateFacultyImage(faculty) {
        console.log('updateFacultyImage called with faculty:', faculty);
        const facultyImage = document.getElementById('faculty-image');
        const noScheduleImage = document.getElementById('no-schedule-image');
        
        if (!facultyImage || !noScheduleImage) {
            console.error('Faculty image elements not found');
            return;
        }
        
        console.log('Faculty object structure:', JSON.stringify(faculty, null, 2));
        console.log('Faculty images type:', typeof faculty.faculty_images);
        console.log('Faculty images value:', faculty.faculty_images);
        console.log('Faculty images length:', faculty.faculty_images ? faculty.faculty_images.length : 'No faculty_images property');
        
        // Handle different possible structures for faculty_images
        let facultyImages = null;
        if (faculty && faculty.faculty_images) {
            if (Array.isArray(faculty.faculty_images)) {
                facultyImages = faculty.faculty_images;
            } else if (typeof faculty.faculty_images === 'string') {
                try {
                    facultyImages = JSON.parse(faculty.faculty_images);
                } catch (e) {
                    console.error('Failed to parse faculty_images JSON:', e);
                    facultyImages = null;
                }
            }
        }
        
        console.log('Processed faculty images:', facultyImages);
        
        if (faculty && facultyImages && facultyImages.length > 0) {
            console.log('Faculty has images:', facultyImages);
            // Get the first image from the array
            const firstImage = facultyImages[0];
            console.log('Using first image:', firstImage);
            
            // Ensure the image path is correct
            let imagePath = firstImage;
            if (!imagePath.startsWith('/')) {
                imagePath = `/storage/${imagePath}`;
            } else {
                imagePath = `/storage${imagePath}`;
            }
            
            console.log('Final image path:', imagePath);
            
            // Set the image source
            facultyImage.src = imagePath;
            
            // Add error handling for image loading
            facultyImage.onerror = function() {
                console.error('Failed to load faculty image:', imagePath);
                facultyImage.style.display = 'none';
                noScheduleImage.style.display = 'flex';
            };
            
            facultyImage.onload = function() {
                console.log('Faculty image loaded successfully:', imagePath);
                facultyImage.style.display = 'block';
                noScheduleImage.style.display = 'none';
            };
            
            // Show the image immediately
            facultyImage.style.display = 'block';
            noScheduleImage.style.display = 'none';
        } else {
            console.log('No faculty images available, showing no schedule image');
            console.log('Faculty object:', faculty);
            console.log('Faculty images:', faculty ? faculty.faculty_images : 'No faculty object');
            console.log('Processed faculty images:', facultyImages);
            facultyImage.style.display = 'none';
            noScheduleImage.style.display = 'flex';
        }
    }

    function showCameraDetail(cameraId) {
        document.getElementById('cameraGridView').style.display = 'none';
        document.getElementById('cameraDetailView').style.display = 'flex';
        document.getElementById('recognition-status-section').style.display = 'block';

        const camera = cameras.find(cam => cam.camera_id == cameraId);
        document.getElementById('main-camera-label').textContent = camera.camera_name;
        document.getElementById('main-camera-label').style.display = 'block';
        document.getElementById('lab-building').textContent = `ROOM: ${camera.room_name} / BUILDING: ${camera.room_building_no}`;

        // Initialize schedule tracking for detail view
        const currentLoad = getCurrentLoadForRoom(camera.room_no);
        currentScheduleByCamera[`detail-${cameraId}`] = currentLoad ? currentLoad.teaching_load_id : null;

        // initial populate and periodic refresh
        updateSchedulePanel(camera);
        if (scheduleIntervalId) clearInterval(scheduleIntervalId);
        scheduleIntervalId = setInterval(() => {
            updateSchedulePanel(camera);
            // Check for schedule changes and reload if needed
            const currentLoad = getCurrentLoadForRoom(camera.room_no);
            const currentScheduleId = currentLoad ? currentLoad.teaching_load_id : null;
            const previousScheduleId = currentScheduleByCamera[`detail-${cameraId}`];
            
            if (currentScheduleId !== previousScheduleId) {
                console.log(`[showCameraDetail] Schedule changed: ${previousScheduleId} -> ${currentScheduleId}`);
                currentScheduleByCamera[`detail-${cameraId}`] = currentScheduleId;
                // Reload recordings when schedule changes (don't preserve playback)
                loadDetailRecordings(cameraId, false);
            } else {
                // Reload recordings when schedule updates (in case time range changes) - preserve playback
                loadDetailRecordings(cameraId, true); // preservePlayback = true
            }
        }, scheduleRefreshMs);

        // Load recordings for detail view
        loadDetailRecordings(cameraId);
    }

    function showCameraGrid() {
        document.getElementById('cameraGridView').style.display = 'grid';
        document.getElementById('cameraDetailView').style.display = 'none';
        document.getElementById('recognition-status-section').style.display = 'none';
        if (scheduleIntervalId) {
            clearInterval(scheduleIntervalId);
            scheduleIntervalId = null;
        }
    }
    
    // Cleanup intervals on page unload
    window.addEventListener('beforeunload', () => {
        if (scheduleIntervalId) clearInterval(scheduleIntervalId);
        if (recordingRefreshIntervalId) clearInterval(recordingRefreshIntervalId);
    });
</script>
@endsection