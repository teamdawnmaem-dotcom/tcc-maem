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
            margin-bottom: 40px;
        }

        .faculty-title-group {
            display: flex;
            flex-direction: column;
        }

        .faculty-title {
            font-size: 2.3rem;
            font-weight: bold;
            color: #6d0000;
        }

        .faculty-subtitle {
            font-size: 1rem;
            color: #666;
            margin-bottom: 30px;            
        }

        .faculty-actions-row {
            display: flex;
            gap: 10px;
        }

        .camera-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .camera-feed {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.22), 0 1.5px 8px rgba(0, 0, 0, 0.12);
            overflow: hidden;
            cursor: pointer;
            transition: transform 0.2s;
            display: flex;
            flex-direction: column;
            min-height: 250px;
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
            padding: 15px;
            font-size: 1.1rem;
            font-weight: bold;
            text-align: center;
            position: relative;
            z-index: 10;
            display: block !important;
        }

        .no-feed {
            height: 200px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #999;
            font-size: 1rem;
            flex: 1;
            min-height: 200px;
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
            height: 200px;
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
            font-size: 3rem;
            margin-bottom: 10px;
            color: #ccc;
        }

        .camera-feed-container {
            display: flex;
            gap: 20px;
            margin-top: 20px;
            height: calc(100vh - 200px);
            min-height: 500px;
        }

        .main-camera-feed {
            flex: 2;
            background: #fff;
            border-radius: 10px;
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
            gap: 20px;
            position: relative;
            height: 100%;
        }

        .combined-card {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.22), 0 1.5px 8px rgba(0, 0, 0, 0.12);
            overflow: hidden;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .lab-header {
            background: #8B0000;
            color: #fff;
            padding: 15px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: bold;
            font-size: 1.1rem;
        }

        .combined-card-content {
            padding: 25px;
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
        }

        .schedule-title {
            font-size: 1.3rem;
            font-weight: bold;
            color: #8B0000;
            margin-bottom: 20px;
            text-align: center;
            flex-shrink: 0;
        }

        .schedule-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
            flex-shrink: 0;
        }

        .faculty-image-container {
            display: flex;
            justify-content: center;
            margin-bottom: 15px;
        }

        .faculty-image {
            width: 1.5in;
            height: 1.5in;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid #8B0000;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .no-schedule-image {
            width: 1.5in;
            height: 1.5in;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid #ccc;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            background: #f5f5f5;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #999;
            font-size: 0.8rem;
            text-align: center;
        }

        .schedule-label {
            font-weight: bold;
            color: #333;
            min-width: 130px;
        }

        .schedule-value {
            color: #666;
            text-align: right;
        }

        .back-btn {
            background: #8B0000;
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 12px 20px;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s;
            position: absolute;
            top: 130px;
            right: 40px;
            z-index: 100;
        }

        .back-btn:hover {
            background: #6d0000;
        }

        .attendance-section {
            margin-top: 20px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.22), 0 1.5px 8px rgba(0, 0, 0, 0.12);
            overflow: hidden;
            display: none;
        }

        .attendance-title {
            background: #8B0000;
            color: #fff;
            padding: 15px;
            font-size: 1.1rem;
            font-weight: bold;
            text-align: center;
        }

        .recognition-status {
            margin-top: 20px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.22), 0 1.5px 8px rgba(0, 0, 0, 0.12);
            overflow: hidden;
            display: none;
            max-height: 400px;
        }

        .recognition-title {
            background: #8B0000;
            color: #fff;
            padding: 15px;
            font-size: 1.1rem;
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
            padding: 12px;
            font-size: 0.9rem;
            font-weight: bold;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .attendance-table td {
            padding: 12px;
            font-size: 0.9rem;
            border-bottom: 1px solid #eee;
        }

        .attendance-table tr:nth-child(even) {
            background: #f9f9f9;
        }

        .search-input {
            padding: 8px;
            font-size: 14px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .search-btn {
            padding: 8px 12px;
            font-size: 14px;
            border: 1px solid #bbb;
            border-radius: 4px;
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
            
            <!-- Grid feed (muted autoplay) -->
            <video id="webrtc-player-{{ $camera->camera_id }}" autoplay playsinline muted style="width:100%; display:none;"></video>
            
            <div class="no-feed" id="no-feed-message-{{ $camera->camera_id }}">
                <div class="no-feed-icon">&#10005;</div>
                <div>No Live Feed</div>
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
        <div class="no-feed" id="video-container">
            
            <!-- Detail feed (audio ON) -->
            <video id="webrtc-player-detail" autoplay playsinline controls style="width:100%; display:none;"></video>
            
            <div class="no-feed" id="no-feed-message-detail">
                <div class="no-feed-icon">&#10005;</div>
                <div>No Live Feed</div>
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
    
    console.log('Faculties data loaded:', faculties);
    console.log('First faculty sample:', faculties.length > 0 ? faculties[0] : 'No faculties');

    //const WEBSOCKET_HOST = `http://${window.location.hostname}:5000`;
    const WEBSOCKET_HOST = `https://workspacevps.cloud/camera/api`;
    const pcs = {}; // RTCPeerConnections per camera
    const reconnectInterval = 1000; // 1 second before retry
    const scheduleRefreshMs = 30000; // refresh schedule every 30s
    let scheduleIntervalId = null;
    let recognitionServiceAvailable = true;
    let recognitionRetryCount = 0;
    const maxRecognitionRetries = 3;

    async function startWebRTC(camera, detail = false) {
        const videoId = detail ? "webrtc-player-detail" : `webrtc-player-${camera.camera_id}`;
        const noFeedId = detail ? "no-feed-message-detail" : `no-feed-message-${camera.camera_id}`;

        const video = document.getElementById(videoId);
        const noFeed = document.getElementById(noFeedId);

        if (!video || !noFeed) {
            console.error("Video or noFeed element not found:", videoId, noFeedId);
            return;
        }

        video.style.display = "none";
        noFeed.style.display = "flex";

        try {
            // Check if we already have a working connection for this camera
            if (pcs[camera.camera_id] && pcs[camera.camera_id].connectionState === 'connected') {
                console.log(`Reusing existing connection for camera ${camera.camera_id}`);
                // If we have a working connection, try to reuse the stream
                if (pcs[camera.camera_id].getReceivers && pcs[camera.camera_id].getReceivers().length > 0) {
                    const receivers = pcs[camera.camera_id].getReceivers();
                    if (receivers.length > 0 && receivers[0].track) {
                        const stream = new MediaStream([receivers[0].track]);
                        video.srcObject = stream;
                        video.style.display = "block";
                        noFeed.style.display = "none";
                        console.log("Reused existing stream for camera:", camera.camera_name);
                        return;
                    }
                }
            }

            // Clean up previous PC if exists and not connected
            if (pcs[camera.camera_id]) {
                try { 
                    if (pcs[camera.camera_id].connectionState !== 'connected') {
                        pcs[camera.camera_id].close();
                    }
                } catch(e) {}
                delete pcs[camera.camera_id];
            }

            const pc = new RTCPeerConnection();
            pcs[camera.camera_id] = pc;

            pc.ontrack = function(event) {
                console.log("WebRTC track received for camera:", camera.camera_name, "Detail mode:", detail);
                video.srcObject = event.streams[0];
                video.style.display = "block";
                noFeed.style.display = "none";
                
                // Remove disabled class when feed is available
                const cameraElement = document.querySelector(`[onclick="showCameraDetail('${camera.camera_id}')"]`);
                if (cameraElement) {
                    cameraElement.classList.remove('no-feed-available');
                }
                
                // Ensure video is properly loaded
                video.onloadedmetadata = function() {
                    console.log("Video metadata loaded for camera:", camera.camera_name);
                };
                
                video.oncanplay = function() {
                    console.log("Video can play for camera:", camera.camera_name);
                };
            };

            pc.onconnectionstatechange = () => {
                console.log("WebRTC connection state:", pc.connectionState, "for camera:", camera.camera_name);
                if (pc.connectionState === "connected") {
                    console.log("WebRTC connection established for camera:", camera.camera_name);
                } else if (pc.connectionState === "failed" || pc.connectionState === "disconnected") {
                    console.warn("WebRTC connection failed, reconnecting camera:", camera.camera_name);
                    // Show no feed message while reconnecting
                    video.style.display = "none";
                    noFeed.style.display = "flex";
                    setTimeout(() => startWebRTC(camera, detail), reconnectInterval);
                }
            };

            const offer = await pc.createOffer({ offerToReceiveVideo: true });
            await pc.setLocalDescription(offer);

           const response = await fetch(`${WEBSOCKET_HOST}/offer/${camera.camera_id}?mode=${detail ? "detail" : "grid"}`, {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ sdp: offer.sdp, type: offer.type })
            });

            if (!response.ok) throw new Error(`Server returned ${response.status}`);
            const answer = await response.json();

            await pc.setRemoteDescription({ sdp: answer.sdp, type: answer.type });
            console.log("WebRTC stream started for camera:", camera.camera_name);

        } catch (err) {
            console.error("WebRTC error:", err);
            video.style.display = "none";
            noFeed.style.display = "flex";
            
            // Add disabled class when no feed is available
            if (!detail) {
                const cameraElement = document.querySelector(`[onclick="showCameraDetail('${camera.camera_id}')"]`);
                if (cameraElement) {
                    cameraElement.classList.add('no-feed-available');
                }
            }

            // retry after interval
            setTimeout(() => startWebRTC(camera, detail), reconnectInterval);
        }
    }

    // Check if recognition service is available
    async function checkRecognitionServiceHealth() {
        try {
            const response = await fetch(`${WEBSOCKET_HOST}/health`);
            if (response.ok) {
                console.log('Recognition service is healthy');
                return true;
            } else {
                console.warn('Recognition service health check failed:', response.status);
                return false;
            }
        } catch (err) {
            console.error('Recognition service is not available:', err);
            return false;
        }
    }

    window.addEventListener("DOMContentLoaded", async () => {
        // Immediately disable all camera feeds on page load
        cameras.forEach(cam => {
            const cameraElement = document.querySelector(`[onclick="showCameraDetail('${cam.camera_id}')"]`);
            if (cameraElement) {
                cameraElement.classList.add('no-feed-available');
            }
        });
        
        // Check if recognition service is available first
        const isHealthy = await checkRecognitionServiceHealth();
        if (!isHealthy) {
            console.warn('Recognition service is not available, some features may not work');
        }
        
        // Start WebRTC for all cameras immediately
        cameras.forEach(cam => startWebRTC(cam, false));
        fetchRecognitionStatus();
        
        // Also start background recognition status fetching
        setInterval(fetchRecognitionStatus, 2000);
    });

    async function fetchRecognitionStatus() {
        try {
            console.log('Fetching recognition status from:', `${WEBSOCKET_HOST}/status`);
            const response = await fetch(`${WEBSOCKET_HOST}/status`);
            
            console.log('Response status:', response.status, response.statusText);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const data = await response.json();
            console.log('Recognition service response:', data);
            
            // Reset retry count on successful response
            recognitionRetryCount = 0;
            recognitionServiceAvailable = true;
            
            const tbody = document.getElementById("recognition-logs-body");
            if (!tbody) {
                console.error('recognition-logs-body element not found');
                return;
            }
            
            tbody.innerHTML = "";
            
            // Get recognition logs (multiple entries)
            const recognitionLogs = data.recognition_logs || [];
            console.log('Recognition logs:', recognitionLogs);
            console.log('Number of logs:', recognitionLogs.length);
            
            if (recognitionLogs.length === 0) {
                tbody.innerHTML = `<tr>
                    <td colspan="4" style="text-align: center; padding: 40px; color: #999; font-style: italic;">
                        No recognition data available. Make sure the recognition service is running.
                    </td>
                </tr>`;
                return;
            }
            
            // Process recognition logs
            let hasResults = false;
            console.log('Total recognition logs received:', recognitionLogs.length);
            recognitionLogs.forEach((log) => {
                console.log('Processing recognition log:', log);
                console.log('Faculty name from backend:', log.faculty_name);
                console.log('Faculty ID from backend:', log.faculty_id);
                
                const camera = cameras.find(cam => cam.camera_id == log.camera_id);
                const cameraName = camera ? camera.camera_name : `Camera ${log.camera_id}`;
                
                // Format timestamp
                const logTime = new Date(log.timestamp);
                const timeStr = logTime.toLocaleString('en-US', {
                    timeZone: 'Asia/Manila',
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit',
                    hour12: true
                });
                
                let facultyName = log.faculty_name || 'Unknown';
                let status = log.status || 'Unknown';
                let distance = log.distance ? log.distance.toFixed(2) : 'N/A';
                let isScheduled = false;
                
                // Ensure we have a proper faculty name (not just ID or "Faculty X" format)
                if ((facultyName === 'Unknown' || facultyName.startsWith('Faculty ')) && log.faculty_id && log.faculty_id !== null) {
                    // Try to find faculty in the faculties array
                    const faculty = faculties.find(f => f.faculty_id == log.faculty_id);
                    if (faculty) {
                        facultyName = `${faculty.faculty_fname} ${faculty.faculty_lname}`;
                        console.log(`Resolved faculty name for ID ${log.faculty_id}: ${facultyName}`);
                    } else {
                        console.log(`Faculty ID ${log.faculty_id} not found in faculties array`);
                        facultyName = `Faculty ${log.faculty_id}`;
                    }
                }
                
                // Check if this faculty is scheduled for this camera's room
                if (log.faculty_id && log.faculty_id !== null && camera) {
                    const currentLoad = getCurrentLoadForRoom(camera.room_no);
                    if (currentLoad && currentLoad.faculty_id == log.faculty_id) {
                        isScheduled = true;
                    }
                }
                
                // Add scheduling identifier
                let facultyDisplay = facultyName;
                if (log.faculty_id && log.faculty_id !== null) {
                    if (isScheduled) {
                        facultyDisplay = `<span class="scheduled-faculty">${facultyName} (Scheduled)</span>`;
                    } else {
                        facultyDisplay = `<span class="unscheduled-faculty">${facultyName} (Not Scheduled)</span>`;
                    }
                }
                
                const row = `<tr>
                    <td>${timeStr}</td>
                    <td>${cameraName}</td>
                    <td>${facultyDisplay}</td>
                    <td>${status}</td>
                </tr>`;
                tbody.innerHTML += row;
                hasResults = true;
            });
            
            // If no recognition results, show waiting message
            if (!hasResults) {
                tbody.innerHTML = `<tr>
                    <td colspan="4" style="text-align: center; padding: 40px; color: #999; font-style: italic;">
                        Waiting for data...
                    </td>
                </tr>`;
            }
            
        } catch (err) {
            console.error("Error fetching recognition status:", err);
            console.error("Error details:", {
                message: err.message,
                stack: err.stack,
                name: err.name
            });
            
            recognitionRetryCount++;
            if (recognitionRetryCount >= maxRecognitionRetries) {
                recognitionServiceAvailable = false;
                console.warn('Recognition service appears to be unavailable after', maxRecognitionRetries, 'retries');
            }
            
            const tbody = document.getElementById("recognition-logs-body");
            if (tbody) {
                let errorMessage = "Error loading data...";
                if (err.message.includes("Failed to fetch")) {
                    errorMessage = "Cannot connect to recognition service. Please check if the service is running.";
                } else if (err.message.includes("HTTP")) {
                    errorMessage = `Server error: ${err.message}`;
                }
                
                if (!recognitionServiceAvailable) {
                    errorMessage += " (Service appears to be down)";
                }
                
                tbody.innerHTML = `<tr>
                    <td colspan="4" style="text-align: center; padding: 40px; color: #999; font-style: italic;">
                        ${errorMessage}
                    </td>
                </tr>`;
            }
        }
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

        // initial populate and periodic refresh
        updateSchedulePanel(camera);
        if (scheduleIntervalId) clearInterval(scheduleIntervalId);
        scheduleIntervalId = setInterval(() => updateSchedulePanel(camera), scheduleRefreshMs);

        // Start WebRTC for detail view - check if we can reuse existing connection
        setTimeout(() => {
            startWebRTC(camera, true);
        }, 50); // Reduced delay for faster response
        
        // Fetch recognition status immediately when entering detail view
        fetchRecognitionStatus();
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
</script>
@endsection