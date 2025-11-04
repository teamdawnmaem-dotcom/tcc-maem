@extends('layouts.appAdmin')

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
			gap: 10px;
		}

        .camera-grid {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
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
			box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
		}

        .no-schedule-image {
            width: 1.2in;
            height: 1.2in;
			object-fit: cover;
            border-radius: 6.4px;
            border: 1.6px solid #ccc;
			box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
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

        .recognition-status {
            margin-top: 16px;
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
            max-height: 240px;
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
	</style>
@endsection

@section('content')
<div class="faculty-header">
	<div class="faculty-title-group">
		<div class="faculty-title">Live Camera</div>
		<div class="faculty-subtitle">Feed</div>
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

	const scheduleRefreshMs = 30000; // refresh schedule every 30s
	let scheduleIntervalId = null;
	
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
	
	// Load recordings for a camera (grid view)
	function loadCameraRecordings(cameraId) {
		const cameraRecordings = recordingsByCamera[cameraId] || [];
		if (cameraRecordings.length === 0) {
			const video = document.getElementById(`recording-player-${cameraId}`);
			const noFeed = document.getElementById(`no-recording-message-${cameraId}`);
			const info = document.getElementById(`recording-info-${cameraId}`);
			if (video) video.style.display = 'none';
			if (noFeed) noFeed.style.display = 'flex';
			if (info) info.style.display = 'none';
			return;
		}
		
		const playlist = cameraRecordings.map(r => buildVideoUrl(r)).filter(url => url);
		if (playlist.length === 0) return;
		
		const video = document.getElementById(`recording-player-${cameraId}`);
		const noFeed = document.getElementById(`no-recording-message-${cameraId}`);
		const info = document.getElementById(`recording-info-${cameraId}`);
		const counter = document.getElementById(`recording-counter-${cameraId}`);
		
		if (!video) return;
		
		video.dataset.playlist = JSON.stringify(playlist);
		video.dataset.currentIndex = '0';
		video.src = playlist[0];
		video.load();
		
		if (noFeed) noFeed.style.display = 'none';
		video.style.display = 'block';
		if (info) info.style.display = 'block';
		if (counter) counter.textContent = `1 / ${playlist.length}`;
		
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
	function loadDetailRecordings(cameraId) {
		const cameraRecordings = recordingsByCamera[cameraId] || [];
		currentDetailCameraId = cameraId;
		currentDetailPlaylist = cameraRecordings.map(r => buildVideoUrl(r)).filter(url => url);
		currentDetailIndex = 0;
		
		const video = document.getElementById('recording-player-detail');
		const noFeed = document.getElementById('no-recording-message-detail');
		const info = document.getElementById('recording-info-detail');
		const counter = document.getElementById('recording-counter-detail');
		
		if (currentDetailPlaylist.length === 0) {
			if (video) video.style.display = 'none';
			if (noFeed) noFeed.style.display = 'flex';
			if (info) info.style.display = 'none';
			return;
		}
		
		if (!video) return;
		
		video.src = currentDetailPlaylist[0];
		video.load();
		
		if (noFeed) noFeed.style.display = 'none';
		video.style.display = 'block';
		if (info) info.style.display = 'block';
		if (counter) counter.textContent = `1 / ${currentDetailPlaylist.length}`;
		
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

    window.addEventListener("DOMContentLoaded", () => {
        // Load recordings for all cameras in grid view
        cameras.forEach(cam => {
            loadCameraRecordings(cam.camera_id);
        });
        
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
		// expects HH:MM or HH:MM:SS
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

		// initial populate
		updateSchedulePanel(camera);
		// refresh periodically without reload
		if (scheduleIntervalId) clearInterval(scheduleIntervalId);
		scheduleIntervalId = setInterval(() => updateSchedulePanel(camera), scheduleRefreshMs);

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
</script>
@endsection