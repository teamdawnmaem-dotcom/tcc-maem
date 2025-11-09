@extends('layouts.appAdmin')

@section('title', 'Recognition Logs - Tagoloan Community College')
@section('recognition-logs-active', 'active')
@section('monitoring-active', 'active')
@section('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/recognition-logs.css') }}">
@endsection

@section('content')
<div class="faculty-header">
    <div class="faculty-title-group">
        <div class="faculty-title">Recognition Logs</div>
        
    </div>
</div>

<!-- Filter Section -->
<div class="filter-section">
    <div class="filter-header">
        <h3 class="filter-title">Filter Recognition Logs</h3>
    </div>
    
    <div class="filter-grid">
        <div class="filter-group">
            <label class="filter-label">Start Date</label>
            <input type="date" class="filter-input" id="startDate">
        </div>
        <div class="filter-group">
            <label class="filter-label">End Date</label>
            <input type="date" class="filter-input" id="endDate">
        </div>
        <div class="filter-group">
            <label class="filter-label">Status</label>
            <select class="filter-select" id="statusFilter">
                <option value="">All Status</option>
                <option value="recognized">Recognized</option>
                <option value="unknown_face">Unknown Face</option>
            </select>
        </div>
        <div class="filter-group">
            <label class="filter-label">Instructor</label>
            <select class="filter-select" id="instructorFilter">
                <option value="">All Instructors</option>
                <!-- Will be populated by JavaScript -->
            </select>
        </div>
        <div class="filter-group">
            <label class="filter-label">Room Name</label>
            <select class="filter-select" id="roomFilter">
                <option value="">All Rooms</option>
                <!-- Will be populated by JavaScript -->
            </select>
        </div>
        <div class="filter-group">
            <label class="filter-label">Building</label>
            <select class="filter-select" id="buildingFilter">
                <option value="">All Buildings</option>
                <!-- Will be populated by JavaScript -->
            </select>
        </div>
        <div class="filter-group">
            <label class="filter-label">Camera</label>
            <select class="filter-select" id="cameraFilter">
                <option value="">All Cameras</option>
                <!-- Will be populated by JavaScript -->
            </select>
        </div>
        <div class="filter-group">
            <button class="filter-btn" onclick="applyFilters()">Apply Filters</button>
            <button class="clear-btn" onclick="clearFilters()">Clear All</button>
        </div>
    </div>
    
    <div class="search-section">
        <div class="search-group">
            <input type="text" class="search-input" id="searchInput" placeholder="Search by faculty name, room, camera, building, or status...">
        </div>
        <div class="search-actions">
            <button class="filter-btn" onclick="searchLogs()">Search</button>
        </div>
    </div>
</div>

<div class="recognition-logs-table-container">
    <table class="recognition-logs-table">
        <thead>
            <tr>
                <th>Time</th>
                <th>Camera</th>
                <th>Room</th>
                <th>Building</th>
                <th>Instructor</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody id="logsTableBody">
            @forelse ($logs as $log)
                <tr>
                    <td>{{ \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $log->recognition_time)->format('F j, Y - g:i:sa') }}</td>
                    <td>{{ $log->camera_name ?? 'N/A' }}</td>
                    <td>{{ $log->room_name ?? 'N/A' }}</td>
                    <td>{{ $log->building_no ?? 'N/A' }}</td>
                    <td>{{ $log->faculty_name ?? 'Unknown' }}</td>
                    <td><span class="status-{{ $log->status }}">{{ $log->status }}</span></td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="no-records">No records found</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- Scrollable table - no pagination needed -->
@endsection

@section('scripts')
<script>
let currentFilters = {};

// Auto-refresh variables
let refreshInterval;
let isPageVisible = true;

// Load initial data
document.addEventListener('DOMContentLoaded', function() {
    // Set current date as default filter
    setCurrentDateFilter();
    
    loadFilters();
    populateFiltersFromURL();
    
    // Suppress global loader for this page
    window.suppressLoader = true;
    
    // Start auto-refresh every second
    startAutoRefresh();
});

// Load logs data via AJAX (background fetch)
async function loadLogs() {
    try {
        // Use current applied filters, not form values
        const filters = { ...currentFilters };
        
        const params = new URLSearchParams(filters);

        const response = await fetch(`/admin/recognition-logs?${params}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        
        if (result.success && result.data) {
            displayLogs(result.data);
        }
    } catch (error) {
        console.error('Error loading logs:', error);
        // Don't show error to user for background refresh
    }
}

// Display logs in the table
function displayLogs(logs) {
    const tableBody = document.getElementById('logsTableBody');
    
    if (logs.length === 0) {
        tableBody.innerHTML = '<tr><td colspan="6" class="no-records">No records found</td></tr>';
        return;
    }
    
    tableBody.innerHTML = logs.map(log => {
        // Format the recognition time to readable format
        const formattedTime = formatDateTime(log.recognition_time);
        
        return `
        <tr>
            <td>${formattedTime}</td>
            <td>${log.camera_name || 'N/A'}</td>
            <td>${log.room_name || 'N/A'}</td>
            <td>${log.building_no || 'N/A'}</td>
            <td>${log.faculty_name || 'Unknown'}</td>
            <td><span class="status-${log.status}">${log.status}</span></td>
        </tr>
        `;
    }).join('');
}

// Format datetime to readable format (e.g., "September 30, 2025 - 4:11:17pm")
function formatDateTime(dateTimeString) {
    if (!dateTimeString) return 'N/A';
    
    try {
        let year, month, day, hours, minutes, seconds;
        
        // Handle different datetime formats
        if (dateTimeString.includes('T')) {
            // ISO format: "2025-09-30T16:11:17.000000Z"
            const isoParts = dateTimeString.split('T');
            const datePart = isoParts[0]; // "2025-09-30"
            const timePart = isoParts[1].split('.')[0]; // "16:11:17"
            
            [year, month, day] = datePart.split('-').map(Number);
            [hours, minutes, seconds] = timePart.split(':').map(Number);
        } else {
            // Standard format: "2025-09-30 16:11:17"
            const parts = dateTimeString.split(' ');
            const datePart = parts[0]; // "2025-09-30"
            const timePart = parts[1]; // "16:11:17"
            
            [year, month, day] = datePart.split('-').map(Number);
            [hours, minutes, seconds] = timePart.split(':').map(Number);
        }
        
        // Month names
        const monthNames = [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'
        ];
        
        // Format: "September 30, 2025 - 4:11:17pm"
        const monthName = monthNames[month - 1];
        const minutesStr = String(minutes).padStart(2, '0');
        const secondsStr = String(seconds).padStart(2, '0');
        
        // Convert to 12-hour format
        const ampm = hours >= 12 ? 'pm' : 'am';
        const displayHours = hours % 12 || 12;
        
        return `${monthName} ${day}, ${year} - ${displayHours}:${minutesStr}:${secondsStr}${ampm}`;
    } catch (error) {
        console.error('Error formatting date:', error);
        return dateTimeString; // Return original if formatting fails
    }
}

// Auto-refresh functions
function startAutoRefresh() {
    // Clear any existing interval
    if (refreshInterval) {
        clearInterval(refreshInterval);
    }
    
    // Start auto-refresh every second
    refreshInterval = setInterval(() => {
        if (isPageVisible) {
            loadLogs();
        }
    }, 1000);
    
    // Handle page visibility changes
    document.addEventListener('visibilitychange', function() {
        isPageVisible = !document.hidden;
    });
}

function stopAutoRefresh() {
    if (refreshInterval) {
        clearInterval(refreshInterval);
        refreshInterval = null;
    }
}

// Set current date as default filter
function setCurrentDateFilter() {
    const today = new Date();
    const todayString = today.toISOString().split('T')[0]; // Format: YYYY-MM-DD
    
    // Set both start and end date to today
    document.getElementById('startDate').value = todayString;
    document.getElementById('endDate').value = todayString;
    
    // Update current filters
    currentFilters.start_date = todayString;
    currentFilters.end_date = todayString;
}

// Load filter options
async function loadFilters() {
    try {
        // Load instructors from faculty table
        const instructorsResponse = await fetch('/api/faculty');
        const instructors = await instructorsResponse.json();
        const instructorSelect = document.getElementById('instructorFilter');
        instructors.forEach(instructor => {
            const option = document.createElement('option');
            option.value = instructor.faculty_id;
            option.textContent = `${instructor.faculty_fname} ${instructor.faculty_lname}`;
            instructorSelect.appendChild(option);
        });

        // Load rooms from room table
        const roomsResponse = await fetch('/api/rooms');
        const rooms = await roomsResponse.json();
        const roomSelect = document.getElementById('roomFilter');
        rooms.forEach(room => {
            const option = document.createElement('option');
            option.value = room.room_name;
            option.textContent = room.room_name;
            roomSelect.appendChild(option);
        });

        // Load cameras from camera table
        const camerasResponse = await fetch('/api/cameras');
        const cameras = await camerasResponse.json();
        const cameraSelect = document.getElementById('cameraFilter');
        cameras.forEach(camera => {
            const option = document.createElement('option');
            option.value = camera.camera_id;
            option.textContent = camera.camera_name || `Camera ${camera.camera_id}`;
            cameraSelect.appendChild(option);
        });

        // Load buildings from room table
        const buildings = [...new Set(rooms.map(room => room.room_building_no))];
        const buildingSelect = document.getElementById('buildingFilter');
        buildings.forEach(building => {
            const option = document.createElement('option');
            option.value = building;
            option.textContent = building;
            buildingSelect.appendChild(option);
        });
    } catch (error) {
        console.error('Error loading filters:', error);
    }
}



// Apply filters
function applyFilters() {
    // Update current filters (excluding search)
    currentFilters = {
        start_date: document.getElementById('startDate').value,
        end_date: document.getElementById('endDate').value,
        status: document.getElementById('statusFilter').value,
        faculty_id: document.getElementById('instructorFilter').value,
        room_name: document.getElementById('roomFilter').value,
        building_no: document.getElementById('buildingFilter').value,
        camera_id: document.getElementById('cameraFilter').value
    };
    
    // Remove empty filters
    Object.keys(currentFilters).forEach(key => {
        if (!currentFilters[key]) {
            delete currentFilters[key];
        }
    });
    
    // Update URL without page reload
    const url = new URL(window.location);
    Object.keys(currentFilters).forEach(key => {
        url.searchParams.set(key, currentFilters[key]);
    });
    window.history.pushState({}, '', url);
    
    // Load filtered data
    loadLogs();
}

// Clear all filters
function clearFilters() {
    // Clear all filter inputs
    document.getElementById('startDate').value = '';
    document.getElementById('endDate').value = '';
    document.getElementById('statusFilter').value = '';
    document.getElementById('instructorFilter').value = '';
    document.getElementById('roomFilter').value = '';
    document.getElementById('buildingFilter').value = '';
    document.getElementById('cameraFilter').value = '';
    document.getElementById('searchInput').value = '';
    
    // Clear current filters
    currentFilters = {};
    
    // Update URL without page reload
    window.history.pushState({}, '', window.location.pathname);
    
    // Load all data
    loadLogs();
}

// Search logs
function searchLogs() {
    // Get search input value
    const searchValue = document.getElementById('searchInput').value;
    
    // Update current filters with search
    currentFilters.search = searchValue;
    
    // Remove search from filters if empty
    if (!currentFilters.search) {
        delete currentFilters.search;
    }
    
    // Update URL without page reload
    const url = new URL(window.location);
    if (currentFilters.search) {
        url.searchParams.set('search', currentFilters.search);
    } else {
        url.searchParams.delete('search');
    }
    window.history.pushState({}, '', url);
    
    // Load filtered data
    loadLogs();
}


// Populate filter fields from URL parameters
function populateFiltersFromURL() {
    const urlParams = new URLSearchParams(window.location.search);
    document.getElementById('startDate').value = urlParams.get('start_date') || '';
    document.getElementById('endDate').value = urlParams.get('end_date') || '';
    document.getElementById('statusFilter').value = urlParams.get('status') || '';
    document.getElementById('instructorFilter').value = urlParams.get('faculty_id') || '';
    document.getElementById('roomFilter').value = urlParams.get('room_name') || '';
    document.getElementById('buildingFilter').value = urlParams.get('building_no') || '';
    document.getElementById('cameraFilter').value = urlParams.get('camera_id') || '';
    document.getElementById('searchInput').value = urlParams.get('search') || '';
    
    // Also populate currentFilters from URL parameters
    currentFilters = {
        start_date: urlParams.get('start_date') || '',
        end_date: urlParams.get('end_date') || '',
        status: urlParams.get('status') || '',
        faculty_id: urlParams.get('faculty_id') || '',
        room_name: urlParams.get('room_name') || '',
        building_no: urlParams.get('building_no') || '',
        camera_id: urlParams.get('camera_id') || '',
        search: urlParams.get('search') || ''
    };
    
    // Remove empty filters
    Object.keys(currentFilters).forEach(key => {
        if (!currentFilters[key]) {
            delete currentFilters[key];
        }
    });
}

// Handle search input enter key
document.getElementById('searchInput').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        searchLogs();
    }
});
</script>
@endsection