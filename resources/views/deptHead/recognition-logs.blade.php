@extends('layouts.appdeptHead')

@section('title', 'Recognition Logs - Tagoloan Community College')
@section('recognition-logs-active', 'active')
@section('monitoring-active', 'active')
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

    .recognition-logs-table-container {
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 8px 32px rgba(0,0,0,0.22), 0 1.5px 8px rgba(0,0,0,0.12);
        overflow: hidden;
        max-height: 56vh;
        overflow-y: auto;
    }
    .recognition-logs-table {
        width: 100%;
        border-collapse: collapse;
    }
    .recognition-logs-table th {
        background: #8B0000;
        color: #fff;
        padding: 16px 12px;
        text-align: left;
        font-weight: 600;
        font-size: 0.76rem;
        letter-spacing: 0.4px;
        text-transform: uppercase;
        position: sticky;
        top: 0;
        z-index: 10;
    }
    .recognition-logs-table td {
        padding: 14.4px 12px;
        border-bottom: 1px solid #f1f3f4;
        font-size: 0.72rem;
        color: #495057;
        vertical-align: middle;
    }
    .recognition-logs-table tbody tr:hover {
        background: #f8f9fa;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .recognition-logs-table tbody tr:last-child td {
        border-bottom: none;
    }

    .filter-section {
        background: #fff;
        border-radius: 9.6px;
        padding: 24px;
        margin-bottom: 24px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        border: 1px solid #e9ecef;
    }

    .filter-header {
        margin-bottom: 20px;
        padding-bottom: 12px;
        border-bottom: 1.6px solid #f1f3f4;
    }

    .filter-title {
        font-size: 1.12rem;
        font-weight: 600;
        color: #2c3e50;
        margin: 0;
    }

    .filter-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 16px;
        margin-bottom: 20px;
    }

    .filter-group {
        display: flex;
        flex-direction: column;
    }

    .filter-group:has(button) {
        display: flex;
        flex-direction: row;
        gap: 12px;
        align-items: end;
    }

    .filter-label {
        font-weight: 600;
        color: #495057;
        margin-bottom: 6.4px;
        font-size: 0.72rem;
    }

    .filter-input, .filter-select {
        padding: 9.6px 12.8px;
        border: 1.6px solid #e9ecef;
        border-radius: 6.4px;
        font-size: 0.76rem;
        background: #ffffff;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        color: #495057;
        font-weight: 500;
    }

    .filter-input:focus, .filter-select:focus {
        outline: none;
        border-color: #8B0000;
        box-shadow: 0 0 0 2.4px rgba(139, 0, 0, 0.1);
        transform: translateY(-1px);
    }

    .filter-actions {
        display: flex;
        gap: 12px;
        align-items: center;
        justify-content: center;
        flex-wrap: wrap;
        margin-bottom: 16px;
    }

    .filter-btn, .clear-btn {
        padding: 12px 25.6px;
        border: none;
        border-radius: 6.4px;
        font-weight: 600;
        font-size: 0.76rem;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        text-transform: uppercase;
        letter-spacing: 0.4px;
        white-space: nowrap;
        min-width: 160px;
        width: auto;
    }

    .filter-btn {
        background: linear-gradient(135deg, #8B0000, #A52A2A);
        color: #fff;
        box-shadow: 0 4px 15px rgba(139, 0, 0, 0.3);
    }

    .filter-btn:hover {
        background: linear-gradient(135deg, #A52A2A, #8B0000);
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(139, 0, 0, 0.4);
    }

    .clear-btn {
        background: linear-gradient(135deg, #6c757d, #495057);
        color: #fff;
        box-shadow: 0 4px 15px rgba(108, 117, 125, 0.3);
    }

    .clear-btn:hover {
        background: linear-gradient(135deg, #495057, #343a40);
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(108, 117, 125, 0.4);
    }

    .search-section {
        display: flex;
        gap: 16px;
        align-items: end;
        margin-top: 16px;
        padding-top: 16px;
        border-top: 1.6px solid #f1f3f4;
    }

    .search-group {
        flex: 1;
        min-width: 0;
    }

    .search-input {
        width: 100%;
        padding: 9.6px 12.8px;
        border: 1.6px solid #e9ecef;
        border-radius: 6.4px;
        font-size: 0.76rem;
        background: #ffffff;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        color: #495057;
        font-weight: 500;
    }

    .search-input:focus {
        outline: none;
        border-color: #8B0000;
        box-shadow: 0 0 0 2.4px rgba(139, 0, 0, 0.1);
        transform: translateY(-1px);
    }

    .search-actions {
        display: flex;
        gap: 12px;
        align-items: end;
        flex-shrink: 0;
    }

    /* Status badges */
    .status-recognized {
        background: #28a745;
        color: white;
        padding: 3.2px 6.4px;
        border-radius: 3.2px;
        font-size: 0.64rem;
        font-weight: bold;
    }

    .status-unknown_face {
        background: #dc3545;
        color: white;
        padding: 3.2px 6.4px;
        border-radius: 3.2px;
        font-size: 0.64rem;
        font-weight: bold;
    }

    .status-processing {
        background: #ffc107;
        color: #212529;
        padding: 3.2px 6.4px;
        border-radius: 3.2px;
        font-size: 0.64rem;
        font-weight: bold;
    }

    .no-records {
        text-align: center;
        padding: 32px;
        color: #6c757d;
        font-size: 0.88rem;
        font-style: italic;
    }

    .loading {
        text-align: center;
        padding: 32px;
        color: #8B0000;
        font-size: 0.88rem;
    }

    @media (max-width: 768px) {
        .filter-grid {
            grid-template-columns: 1fr;
        }
        
        .filter-actions {
            flex-direction: row;
            align-items: stretch;
            justify-content: center;
        }
        
        .search-section {
            flex-direction: column;
            align-items: stretch;
        }
        
        .search-group {
            min-width: auto;
        }
        
        .search-actions {
            flex-direction: row;
            justify-content: center;
            margin-top: 15px;
        }
    }

    /* Mobile responsive design for 430px width */
    @media (max-width: 430px) {
        .faculty-header {
            margin-bottom: 20px;
        }

        .faculty-title {
            font-size: 1.4rem;
        }

        .faculty-subtitle {
            font-size: 0.7rem;
            margin-bottom: 16px;
        }

        .filter-section {
            padding: 16px;
            margin-bottom: 16px;
            border-radius: 8px;
        }

        .filter-header {
            margin-bottom: 16px;
            padding-bottom: 10px;
        }

        .filter-title {
            font-size: 0.95rem;
        }

        .filter-grid {
            grid-template-columns: 1fr;
            gap: 12px;
            margin-bottom: 16px;
        }

        .filter-group {
            margin-bottom: 0;
        }

        .filter-label {
            font-size: 0.7rem;
            margin-bottom: 5px;
        }

        .filter-input, .filter-select {
            padding: 10px 12px;
            font-size: 0.75rem;
            border-radius: 5px;
        }

        .filter-group:has(button) {
            flex-direction: column;
            gap: 8px;
            align-items: stretch;
        }

        .filter-btn, .clear-btn {
            width: 100%;
            min-width: auto;
            padding: 11px 20px;
            font-size: 0.74rem;
            margin: 0;
        }

        .search-section {
            margin-top: 12px;
            padding-top: 12px;
            gap: 12px;
        }

        .search-input {
            padding: 10px 12px;
            font-size: 0.75rem;
            border-radius: 5px;
        }

        .search-input::placeholder {
            font-size: 0.7rem;
        }

        .search-actions {
            width: 100%;
            margin-top: 0;
        }

        .search-actions .filter-btn {
            width: 100%;
        }

        .recognition-logs-table-container {
            border-radius: 8px;
            max-height: 65vh;
            overflow-y: auto;
            overflow-x: hidden;
            -webkit-overflow-scrolling: touch;
            box-shadow: 0 4px 16px rgba(0,0,0,0.15);
            padding: 8px;
            background: #f8f9fa;
        }

        /* Add scroll indicator */
        .recognition-logs-table-container::-webkit-scrollbar {
            width: 6px;
        }

        .recognition-logs-table-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }

        .recognition-logs-table-container::-webkit-scrollbar-thumb {
            background: #8B0000;
            border-radius: 3px;
        }

        .recognition-logs-table-container::-webkit-scrollbar-thumb:hover {
            background: #A52A2A;
        }

        /* Convert table to card layout on mobile */
        .recognition-logs-table {
            width: 100%;
            display: block;
        }

        .recognition-logs-table thead {
            display: none;
        }

        .recognition-logs-table tbody {
            display: block;
        }

        .recognition-logs-table tr {
            display: block;
            margin-bottom: 12px;
            background: #fff;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .recognition-logs-table td {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #f1f3f4;
            text-align: right;
            font-size: 0.72rem;
        }

        .recognition-logs-table td:last-child {
            border-bottom: none;
        }

        .recognition-logs-table td::before {
            content: attr(data-label);
            font-weight: 600;
            font-size: 0.68rem;
            color: #495057;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin-right: auto;
            text-align: left;
            padding-right: 12px;
            flex-shrink: 0;
        }

        .status-recognized,
        .status-unknown_face,
        .status-processing {
            padding: 4px 8px;
            font-size: 0.62rem;
            border-radius: 4px;
            display: inline-block;
            white-space: nowrap;
        }

        .no-records {
            padding: 24px 16px;
            font-size: 0.8rem;
            background: #fff;
            border-radius: 8px;
            margin: 12px;
        }

        .loading {
            padding: 24px 16px;
            font-size: 0.8rem;
            background: #fff;
            border-radius: 8px;
            margin: 12px;
        }
    }
</style>
@endsection

@section('content')
<div class="faculty-header">
    <div class="faculty-title-group">
        <div class="faculty-title">Recognition Logs</div>
        <div class="faculty-subtitle">  </div>
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
                    <td data-label="Time">{{ \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $log->recognition_time)->format('F j, Y - g:i:sa') }}</td>
                    <td data-label="Camera">{{ $log->camera_name ?? 'N/A' }}</td>
                    <td data-label="Room">{{ $log->room_name ?? 'N/A' }}</td>
                    <td data-label="Building">{{ $log->building_no ?? 'N/A' }}</td>
                    <td data-label="Instructor">{{ $log->faculty_name ?? 'Unknown' }}</td>
                    <td data-label="Status"><span class="status-{{ $log->status }}">{{ $log->status }}</span></td>
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

        const response = await fetch(`/deptHead/recognition-logs?${params}`, {
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
            <td data-label="Time">${formattedTime}</td>
            <td data-label="Camera">${log.camera_name || 'N/A'}</td>
            <td data-label="Room">${log.room_name || 'N/A'}</td>
            <td data-label="Building">${log.building_no || 'N/A'}</td>
            <td data-label="Instructor">${log.faculty_name || 'Unknown'}</td>
            <td data-label="Status"><span class="status-${log.status}">${log.status}</span></td>
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