@extends('layouts.appAdmin')

@section('title', 'Real-Time Attendance Records - Tagoloan Community College')
@section('reports-active', 'active')
@section('attendance-records-active', 'active')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/attendance-records.css') }}">
@endsection

@section('content')
<div class="faculty-header">
    <div class="faculty-title-group">
        <div class="faculty-title">Attendance Records</div>
        <div class="faculty-subtitle"></div>
    </div>
    <div class="faculty-actions-row">
        <!-- Search and Print moved to filter section -->
    </div>
</div>

<!-- Filter Section -->
<div class="filter-section">
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
            <label class="filter-label">Department</label>
            <select class="filter-select" id="departmentFilter">
                    <option value="">All Departments</option>
                <option value="College of Information Technology">College of Information Technology</option>
                <option value="College of Library and Information Science">College of Library and Information Science</option>
                <option value="College of Criminology">College of Criminology</option>
                <option value="College of Arts and Sciences">College of Arts and Sciences</option>
                <option value="College of Hospitality Management">College of Hospitality Management</option>
                <option value="College of Sociology">College of Sociology</option>
                <option value="College of Engineering">College of Engineering</option>
                <option value="College of Education">College of Education</option>
                <option value="College of Business Administration">College of Business Administration</option>
            </select>
        </div>
            <div class="filter-group">
                <label class="filter-label">Instructor</label>
                <select class="filter-select" id="instructorFilter">
                    <option value="">All Instructors</option>
                </select>
            </div>
        <div class="filter-group">
            <label class="filter-label">Course Code</label>
            <select class="filter-select" id="courseCodeFilter">
                <option value="">All Course Codes</option>
            </select>
        </div>
        <div class="filter-group">
            <label class="filter-label">Subject</label>
            <select class="filter-select" id="subjectFilter">
                <option value="">All Subjects</option>
            </select>
        </div>
        <div class="filter-group">
            <label class="filter-label">Day of Week</label>
            <select class="filter-select" id="dayFilter">
                <option value="">All Days</option>
                <option value="Monday">Monday</option>
                <option value="Tuesday">Tuesday</option>
                <option value="Wednesday">Wednesday</option>
                <option value="Thursday">Thursday</option>
                <option value="Friday">Friday</option>
                <option value="Saturday">Saturday</option>
                <option value="Sunday">Sunday</option>
            </select>
        </div>
        <div class="filter-group">
            <label class="filter-label">Room</label>
            <select class="filter-select" id="roomFilter">
                <option value="">All Rooms</option>
            </select>
        </div>
        <div class="filter-group">
            <label class="filter-label">Building</label>
            <select class="filter-select" id="buildingFilter">
                <option value="">All Buildings</option>
            </select>
        </div>
        <div class="filter-group">
            <label class="filter-label">Status</label>
            <select class="filter-select" id="statusFilter">
                <option value="">All Status</option>
                <option value="Present">Present</option>
                <option value="Absent">Absent</option>
                <option value="Late">Late</option>
            </select>
        </div>
            <div class="filter-group">
                <label class="filter-label">Remarks</label>
                <select class="filter-select" id="remarksFilter">
                    <option value="">All Remarks</option>
                    <option value="Present">Present</option>
                    <option value="Absent">Absent</option>
                    <option value="Late">Late</option>
                    <option value="On Leave">On Leave</option>
                    <option value="With Pass Slip">With Pass Slip</option>
            </select>
        </div>
        <div class="filter-group">
            <button class="filter-btn" onclick="applyFilters()">Apply Filters</button>
        </div>
        <div class="filter-group">
            <button class="clear-btn" onclick="clearFilters()">Clear All</button>
        </div>
    </div>
    
    <div class="search-section">
        <div class="search-group">
            <input type="text" class="search-input" id="searchInput" placeholder="Search by faculty name, course code, subject, room, building...">
        </div>
        <div class="search-actions">
            <form id="printForm" method="GET" action="{{ route('admin.attendance.records.print') }}" target="_blank">
                <input type="hidden" name="startDate" id="printStartDate">
                <input type="hidden" name="endDate" id="printEndDate">
                <input type="hidden" name="department" id="printDepartment">
                    <input type="hidden" name="instructor" id="printInstructor">
                    <input type="hidden" name="courseCode" id="printCourseCode">
                    <input type="hidden" name="subject" id="printSubject">
                    <input type="hidden" name="day" id="printDay">
                    <input type="hidden" name="room" id="printRoom">
                    <input type="hidden" name="building" id="printBuilding">
                    <input type="hidden" name="status" id="printStatus">
                    <input type="hidden" name="remarks" id="printRemarks">
                <input type="hidden" name="search" id="printSearch">
                <button type="submit" class="print-btn">Print Report</button>
            </form>
            <form id="oldReportForm" method="GET" action="{{ route('admin.attendance.sheet.print') }}" target="_blank">
                <input type="hidden" name="startDate" id="sheetStartDate">
                <input type="hidden" name="endDate" id="sheetEndDate">
                <input type="hidden" name="department" id="sheetDepartment">
                <input type="hidden" name="instructor" id="sheetInstructor">
                <input type="hidden" name="course_code" id="sheetCourseCode">
                <input type="hidden" name="subject" id="sheetSubject">
                <input type="hidden" name="day" id="sheetDay">
                <input type="hidden" name="room" id="sheetRoom">
                <input type="hidden" name="building" id="sheetBuilding">
                <input type="hidden" name="status" id="sheetStatus">
                <input type="hidden" name="remarks" id="sheetRemarks">
                <input type="hidden" name="search" id="sheetSearch">
                <button type="submit" class="old-report-btn">OLD report Format</button>
            </form>
            
        </div>
    </div>
</div>

<div class="teaching-load-table-container">
    <table class="teaching-load-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Faculty Name</th>
                <th>Department</th>
                <th>Course code</th>
                <th>Subject</th>
                <th>Class Section</th>
                <th>Day</th>
                <th>Time Schedule</th>
                <th>Time in</th>
                <th>Time out</th>
                <th>Time duration</th>
                <th>Room name</th>
                <th>Building no.</th>
                <th>Status</th>
                <th>Remarks</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($records as $record)
                <tr onclick="viewRecordDetails({{ $record->record_id }})" class="record-row" data-record-id="{{ $record->record_id }}">
                    <td>{{ \Carbon\Carbon::parse($record->record_date)->format('F j, Y') }}</td>
                    <td>{{ $record->faculty->faculty_fname }} {{ $record->faculty->faculty_lname }}</td>
                    <td>{{ $record->faculty->faculty_department }}</td>
                    <td>{{ $record->teachingLoad->teaching_load_course_code }}</td>
                    <td>{{ $record->teachingLoad->teaching_load_subject }}</td>
                    <td>{{ $record->teachingLoad->teaching_load_class_section }}</td>
                    <td>{{ $record->teachingLoad->teaching_load_day_of_week }}</td>
                    <td>{{ \Carbon\Carbon::parse($record->teachingLoad->teaching_load_time_in)->format('h:i A') }} to {{ \Carbon\Carbon::parse($record->teachingLoad->teaching_load_time_out)->format('h:i A') }}</td>
                    <td>
                        @if(strtoupper(trim($record->record_remarks)) === 'ON LEAVE' || strtoupper(trim($record->record_remarks)) === 'WITH PASS SLIP' || !$record->record_time_in)
                            <span style="color: #999;">N/A</span>
                        @else
                            {{ \Carbon\Carbon::parse($record->record_time_in)->format('h:i A') }}
                        @endif
                    </td>
                    <td>
                        @if(strtoupper(trim($record->record_remarks)) === 'ON LEAVE' || strtoupper(trim($record->record_remarks)) === 'WITH PASS SLIP' || !$record->record_time_out)
                            <span style="color: #999;">N/A</span>
                        @else
                            {{ \Carbon\Carbon::parse($record->record_time_out)->format('h:i A') }}
                        @endif
                    </td>
                    <td>
                        @if(strtoupper(trim($record->record_remarks)) === 'ON LEAVE' || strtoupper(trim($record->record_remarks)) === 'WITH PASS SLIP')
                            <span style="color: #999;">0</span>
                        @elseif($record->time_duration_seconds == 0)
                            <span style="color: #999;">0</span>
                        @else
                            {{ intval($record->time_duration_seconds / 60) }}m {{ $record->time_duration_seconds % 60 }}s
                        @endif
                    </td>
                    <td>{{ $record->camera->room->room_name }}</td>
                    <td>{{ $record->camera->room->room_building_no }}</td>
                    <td>{{ $record->record_status }}</td>
                    <td>
                        @if(strtoupper(trim($record->record_remarks)) === 'ON LEAVE')
                            <span class="remarks-on-leave">{{ $record->record_remarks }}</span>
                        @elseif(strtoupper(trim($record->record_remarks)) === 'WITH PASS SLIP')
                            <span class="remarks-on-pass-slip">{{ $record->record_remarks }}</span>
                        @else
                            {{ $record->record_remarks }}
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="15" style="text-align:center; padding:20px;">No attendance records found</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- Record Details Modal -->
<div id="recordDetailsModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header-custom">
            ATTENDANCE RECORD DETAILS
            <button class="modal-close" onclick="closeRecordModal()">&times;</button>
        </div>
        <div class="modal-content" id="recordDetailsContent">
            <div style="text-align: center; padding: 40px;">
                <div class="spinner-border" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
                <p style="margin-top: 16px; color: #666;">Loading record details...</p>
            </div>
        </div>
    </div>
</div>

<!-- Image Viewer Modal -->
<div id="imageViewerModal" class="image-viewer-modal">
    <div class="image-viewer-content">
        <button class="image-viewer-close" onclick="closeImageViewer()">&times;</button>
        <img id="viewerImage" src="" alt="Image Viewer">
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.29/jspdf.plugin.autotable.min.js"></script>
<script>
// Current filters object
let currentFilters = {};

// Load initial data
document.addEventListener('DOMContentLoaded', async function() {
    // Set current date as default filter
    setCurrentDateFilter();
    
    // Load filter options first
    await loadFilters();
    
    // Then populate filters from URL
    populateFiltersFromURL();
    
    // Suppress global loader for this page
    window.suppressLoader = true;
});

// Set current date as default filter (only if no URL parameters exist)
function setCurrentDateFilter() {
    const urlParams = new URLSearchParams(window.location.search);
    
    // Only set current date if no URL parameters exist (first load)
    if (urlParams.size === 0) {
        const today = new Date();
        const todayString = today.toISOString().split('T')[0]; // Format: YYYY-MM-DD
        
        // Set both start and end date to today
        document.getElementById('startDate').value = todayString;
        document.getElementById('endDate').value = todayString;
        
        // Update current filters with correct parameter names
        currentFilters.startDate = todayString;
        currentFilters.endDate = todayString;
    }
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

        // Load buildings from room table
        const buildings = [...new Set(rooms.map(room => room.room_building_no))];
        const buildingSelect = document.getElementById('buildingFilter');
        buildings.forEach(building => {
            const option = document.createElement('option');
            option.value = building;
            option.textContent = building;
            buildingSelect.appendChild(option);
        });

        // Load course codes and subjects from teaching loads
        const teachingLoadsResponse = await fetch('/api/teaching-loads');
        if (teachingLoadsResponse.ok) {
            const teachingLoads = await teachingLoadsResponse.json();
            
            // Course codes
            const courseCodes = [...new Set(teachingLoads.map(tl => tl.teaching_load_course_code))];
            const courseCodeSelect = document.getElementById('courseCodeFilter');
            courseCodes.forEach(code => {
                const option = document.createElement('option');
                option.value = code;
                option.textContent = code;
                courseCodeSelect.appendChild(option);
            });

            // Subjects
            const subjects = [...new Set(teachingLoads.map(tl => tl.teaching_load_subject))];
            const subjectSelect = document.getElementById('subjectFilter');
            subjects.forEach(subject => {
                const option = document.createElement('option');
                option.value = subject;
                option.textContent = subject;
                subjectSelect.appendChild(option);
            });
        }
        
        return true; // Indicate successful completion
    } catch (error) {
        console.error('Error loading filters:', error);
        return false;
    }
}



// Apply filters
function applyFilters() {
    // Get current filter values
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;
    const department = document.getElementById('departmentFilter').value;
    const instructor = document.getElementById('instructorFilter').value;
    const courseCode = document.getElementById('courseCodeFilter').value;
    const subject = document.getElementById('subjectFilter').value;
    const day = document.getElementById('dayFilter').value;
    const room = document.getElementById('roomFilter').value;
    const building = document.getElementById('buildingFilter').value;
    const status = document.getElementById('statusFilter').value;
    const remarks = document.getElementById('remarksFilter').value;
    const search = document.getElementById('searchInput').value;
    
    // Build URL with parameters
    const url = new URL(window.location);
    
    // Clear all existing parameters first
    url.search = '';
    
    // Add parameters only if they have values
    if (startDate) url.searchParams.set('startDate', startDate);
    if (endDate) url.searchParams.set('endDate', endDate);
    if (department) url.searchParams.set('department', department);
    if (instructor) url.searchParams.set('instructor', instructor);
    if (courseCode) url.searchParams.set('course_code', courseCode);
    if (subject) url.searchParams.set('subject', subject);
    if (day) url.searchParams.set('day', day);
    if (room) url.searchParams.set('room', room);
    if (building) url.searchParams.set('building', building);
    if (status) url.searchParams.set('status', status);
    if (remarks) url.searchParams.set('remarks', remarks);
    if (search) url.searchParams.set('search', search);
    
    // Reload page with new parameters
    window.location.href = url.toString();
}

// Clear all filters
function clearFilters() {
    // Reload page without any parameters
    window.location.href = window.location.pathname;
}

// Search logs
function searchLogs() {
    // Get search input value
    const searchValue = document.getElementById('searchInput').value;
    
    // Build URL with current parameters plus search
    const url = new URL(window.location);
    
    if (searchValue) {
        url.searchParams.set('search', searchValue);
    } else {
        url.searchParams.delete('search');
    }
    
    // Reload page with search parameter
    window.location.href = url.toString();
}

// Populate filter fields from URL parameters
function populateFiltersFromURL() {
    const urlParams = new URLSearchParams(window.location.search);
    
    document.getElementById('startDate').value = urlParams.get('startDate') || '';
    document.getElementById('endDate').value = urlParams.get('endDate') || '';
    document.getElementById('departmentFilter').value = urlParams.get('department') || '';
    document.getElementById('instructorFilter').value = urlParams.get('instructor') || '';
    document.getElementById('courseCodeFilter').value = urlParams.get('course_code') || '';
    document.getElementById('subjectFilter').value = urlParams.get('subject') || '';
    document.getElementById('dayFilter').value = urlParams.get('day') || '';
    document.getElementById('roomFilter').value = urlParams.get('room') || '';
    document.getElementById('buildingFilter').value = urlParams.get('building') || '';
    document.getElementById('statusFilter').value = urlParams.get('status') || '';
    document.getElementById('remarksFilter').value = urlParams.get('remarks') || '';
    document.getElementById('searchInput').value = urlParams.get('search') || '';
    
    // Also populate currentFilters from URL parameters
    currentFilters = {
        startDate: urlParams.get('startDate') || '',
        endDate: urlParams.get('endDate') || '',
        department: urlParams.get('department') || '',
        instructor: urlParams.get('instructor') || '',
        course_code: urlParams.get('course_code') || '',
        subject: urlParams.get('subject') || '',
        day: urlParams.get('day') || '',
        room: urlParams.get('room') || '',
        building: urlParams.get('building') || '',
        status: urlParams.get('status') || '',
        remarks: urlParams.get('remarks') || '',
        search: urlParams.get('search') || ''
    };
    
    // Remove empty filters
    Object.keys(currentFilters).forEach(key => {
        if (!currentFilters[key]) {
            delete currentFilters[key];
        }
    });
}

// Search on Enter key
document.querySelector('.search-input').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        searchLogs();
    }
});

    // Sync hidden print inputs with current URL parameters (what's actually displayed)
    function syncPrintInputs() {
        const urlParams = new URLSearchParams(window.location.search);
        
        document.getElementById('printStartDate').value = urlParams.get('startDate') || '';
        document.getElementById('printEndDate').value = urlParams.get('endDate') || '';
        document.getElementById('printDepartment').value = urlParams.get('department') || '';
        document.getElementById('printInstructor').value = urlParams.get('instructor') || '';
        document.getElementById('printCourseCode').value = urlParams.get('course_code') || '';
        document.getElementById('printSubject').value = urlParams.get('subject') || '';
        document.getElementById('printDay').value = urlParams.get('day') || '';
        document.getElementById('printRoom').value = urlParams.get('room') || '';
        document.getElementById('printBuilding').value = urlParams.get('building') || '';
        document.getElementById('printStatus').value = urlParams.get('status') || '';
        document.getElementById('printRemarks').value = urlParams.get('remarks') || '';
        document.getElementById('printSearch').value = urlParams.get('search') || '';
    }

    document.getElementById('printForm').addEventListener('submit', syncPrintInputs);

    function syncSheetInputs() {
        const urlParams = new URLSearchParams(window.location.search);
        document.getElementById('sheetStartDate').value = urlParams.get('startDate') || '';
        document.getElementById('sheetEndDate').value = urlParams.get('endDate') || '';
        document.getElementById('sheetDepartment').value = urlParams.get('department') || '';
        document.getElementById('sheetInstructor').value = urlParams.get('instructor') || '';
        document.getElementById('sheetCourseCode').value = urlParams.get('course_code') || '';
        document.getElementById('sheetSubject').value = urlParams.get('subject') || '';
        document.getElementById('sheetDay').value = urlParams.get('day') || '';
        document.getElementById('sheetRoom').value = urlParams.get('room') || '';
        document.getElementById('sheetBuilding').value = urlParams.get('building') || '';
        document.getElementById('sheetStatus').value = urlParams.get('status') || '';
        document.getElementById('sheetRemarks').value = urlParams.get('remarks') || '';
        document.getElementById('sheetSearch').value = urlParams.get('search') || '';
    }

    document.getElementById('oldReportForm').addEventListener('submit', syncSheetInputs);
    
    // Record Details Modal Functions
    function viewRecordDetails(recordId) {
        const modal = document.getElementById('recordDetailsModal');
        const content = document.getElementById('recordDetailsContent');
        
        // Show loading state
        content.innerHTML = `
            <div style="text-align: center; padding: 40px;">
                <div class="spinner-border" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
                <p style="margin-top: 16px; color: #666;">Loading record details...</p>
            </div>
        `;
        
        modal.classList.add('active');
        
        // Fetch record details
        fetch(`/api/attendance/${recordId}/details`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayRecordDetails(data.data);
                } else {
                    content.innerHTML = '<p style="color: #dc3545; text-align: center; padding: 40px;">Failed to load record details.</p>';
                }
            })
            .catch(error => {
                console.error('Error fetching record details:', error);
                content.innerHTML = '<p style="color: #dc3545; text-align: center; padding: 40px;">Error loading record details.</p>';
            });
    }
    
    function displayRecordDetails(data) {
        try {
            const record = data.record;
            const passSlip = data.pass_slip;
            const leaveSlip = data.leave_slip;
            const officialMatter = data.official_matter;
            
            const content = document.getElementById('recordDetailsContent');
            
            // Check if required data exists
            if (!record) {
                content.innerHTML = '<p style="color: #dc3545; text-align: center; padding: 40px;">Record data not found.</p>';
                return;
            }
            
            // Format date and times
            const recordDate = record.record_date ? 
                new Date(record.record_date).toLocaleDateString('en-US', { 
                    year: 'numeric', 
                    month: 'long', 
                    day: 'numeric' 
                }) : 'N/A';
            
            const timeIn = record.record_time_in ? 
                new Date(record.record_time_in).toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true }) : 
                'N/A';
            const timeOut = record.record_time_out ? 
                new Date(record.record_time_out).toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true }) : 
                'N/A';
            
            const duration = record.time_duration_seconds > 0 ? 
                `${Math.floor(record.time_duration_seconds / 60)}m ${record.time_duration_seconds % 60}s` : 
                '0';
            
            // Get remarks and determine which attachment to show
            const remarks = (record.record_remarks || '').toUpperCase().trim();
            const showPassSlip = remarks === 'WITH PASS SLIP' && passSlip;
            const showLeaveSlip = remarks === 'ON LEAVE' && leaveSlip;
            const showOfficialMatter = officialMatter && officialMatter.om_remarks && remarks === officialMatter.om_remarks.toUpperCase().trim();
            
            // Check if attendance came from recognition (has snapshots)
            const isFromRecognition = !!(record.time_in_snapshot || record.time_out_snapshot);
            
            // Determine what sections to show
            // Priority: If remarks exist, show attachments only. Otherwise, if from recognition, show recognition data.
            const showAttachmentsOnly = (remarks === 'ON LEAVE' || remarks === 'WITH PASS SLIP' || showOfficialMatter);
            const showRecognitionOnly = isFromRecognition && !showAttachmentsOnly;
            
            // Build HTML
            let html = '';
            
            // Only show Time Information and Snapshots if attendance came from recognition AND no attachments
            if (showRecognitionOnly) {
                html += `
                    <!-- Time Information -->
                    <div class="modal-section">
                        <div class="modal-section-title">Time Information</div>
                        <div class="modal-info-grid">
                            <div class="modal-info-item">
                                <div class="modal-info-label">Time In</div>
                                <div class="modal-info-value">${timeIn}</div>
                            </div>
                            <div class="modal-info-item">
                                <div class="modal-info-label">Time Out</div>
                                <div class="modal-info-value">${timeOut}</div>
                            </div>
                            <div class="modal-info-item">
                                <div class="modal-info-label">Duration</div>
                                <div class="modal-info-value">${duration}</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Snapshots -->
                    <div class="modal-section">
                        <div class="modal-section-title">Recognition Snapshots</div>
                        <div class="snapshot-container">
                            <div class="snapshot-item">
                                <div class="snapshot-label">Time In Snapshot</div>
                                ${record.time_in_snapshot ? 
                                    `<img src="/storage/${record.time_in_snapshot}" alt="Time In Snapshot" class="snapshot-image" onclick="viewImage('/storage/${record.time_in_snapshot}')">` : 
                                    '<p class="no-attachment">No snapshot available</p>'}
                            </div>
                            <div class="snapshot-item">
                                <div class="snapshot-label">Time Out Snapshot</div>
                                ${record.time_out_snapshot ? 
                                    `<img src="/storage/${record.time_out_snapshot}" alt="Time Out Snapshot" class="snapshot-image" onclick="viewImage('/storage/${record.time_out_snapshot}')">` : 
                                    '<p class="no-attachment">No snapshot available</p>'}
                            </div>
                        </div>
                    </div>
                `;
            }
            
            // Only show Attachments if remarks is "On Leave" or "With Pass Slip"
            if (showAttachmentsOnly) {
                html += `
                    <!-- Attachments -->
                    <div class="modal-section">
                        <div class="modal-section-title">Attachments</div>
                        <div class="attachment-container">
                            ${showPassSlip ? `
                                <div class="attachment-item">
                                    <div class="attachment-header">Pass Slip</div>
                                    <div class="attachment-details">
                                        <div class="modal-info-item">
                                            <div class="modal-info-label">Date</div>
                                            <div class="modal-info-value">${passSlip.pass_slip_date ? new Date(passSlip.pass_slip_date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) : 'N/A'}</div>
                                        </div>
                                        <div class="modal-info-item">
                                            <div class="modal-info-label">Departure Time</div>
                                            <div class="modal-info-value">${passSlip.pass_slip_departure_time || 'N/A'}</div>
                                        </div>
                                        <div class="modal-info-item">
                                            <div class="modal-info-label">Arrival Time</div>
                                            <div class="modal-info-value">${passSlip.pass_slip_arrival_time || 'N/A'}</div>
                                        </div>
                                        <div class="modal-info-item">
                                            <div class="modal-info-label">Itinerary</div>
                                            <div class="modal-info-value">${passSlip.pass_slip_itinerary || 'N/A'}</div>
                                        </div>
                                        <div class="modal-info-item">
                                            <div class="modal-info-label">Purpose</div>
                                            <div class="modal-info-value">${passSlip.lp_purpose || 'N/A'}</div>
                                        </div>
                                    </div>
                                    ${passSlip.lp_image ? `<img src="${passSlip.lp_image}" alt="Pass Slip" class="attachment-image" onclick="viewImage('${passSlip.lp_image}')">` : '<p class="no-attachment">No image available</p>'}
                                </div>
                            ` : ''}
                            
                            ${showLeaveSlip ? `
                                <div class="attachment-item">
                                    <div class="attachment-header">Leave Slip</div>
                                    <div class="attachment-details">
                                        <div class="modal-info-item">
                                            <div class="modal-info-label">Start Date</div>
                                            <div class="modal-info-value">${leaveSlip.leave_start_date ? new Date(leaveSlip.leave_start_date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) : 'N/A'}</div>
                                        </div>
                                        <div class="modal-info-item">
                                            <div class="modal-info-label">End Date</div>
                                            <div class="modal-info-value">${leaveSlip.leave_end_date ? new Date(leaveSlip.leave_end_date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) : 'N/A'}</div>
                                        </div>
                                        <div class="modal-info-item">
                                            <div class="modal-info-label">Purpose</div>
                                            <div class="modal-info-value">${leaveSlip.lp_purpose || 'N/A'}</div>
                                        </div>
                                    </div>
                                    ${leaveSlip.lp_image ? `<img src="${leaveSlip.lp_image}" alt="Leave Slip" class="attachment-image" onclick="viewImage('${leaveSlip.lp_image}')">` : '<p class="no-attachment">No image available</p>'}
                                </div>
                            ` : ''}
                            
                            ${showOfficialMatter ? `
                                <div class="attachment-item">
                                    <div class="attachment-header">Official Matter</div>
                                    <div class="attachment-details">
                                        <div class="modal-info-item">
                                            <div class="modal-info-label">Start Date</div>
                                            <div class="modal-info-value">${officialMatter.om_start_date ? new Date(officialMatter.om_start_date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) : 'N/A'}</div>
                                        </div>
                                        <div class="modal-info-item">
                                            <div class="modal-info-label">End Date</div>
                                            <div class="modal-info-value">${officialMatter.om_end_date ? new Date(officialMatter.om_end_date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) : 'N/A'}</div>
                                        </div>
                                        <div class="modal-info-item">
                                            <div class="modal-info-label">Purpose</div>
                                            <div class="modal-info-value">${officialMatter.om_purpose || 'N/A'}</div>
                                        </div>
                                        <div class="modal-info-item">
                                            <div class="modal-info-label">Remarks</div>
                                            <div class="modal-info-value">${officialMatter.om_remarks || 'N/A'}</div>
                                        </div>
                                    </div>
                                    ${officialMatter.om_attachment ? `<img src="${officialMatter.om_attachment}" alt="Official Matter" class="attachment-image" onclick="viewImage('${officialMatter.om_attachment}')">` : '<p class="no-attachment">No image available</p>'}
                                </div>
                            ` : ''}
                            
                            ${!showPassSlip && !showLeaveSlip && !showOfficialMatter ? '<p class="no-attachment">No attachments available</p>' : ''}
                        </div>
                    </div>
                    `;
            }
            
            // If no content was generated, show a message
            if (!html || html.trim() === '') {
                html = '<p style="color: #666; text-align: center; padding: 40px;">No details available for this record.</p>';
            }
            
            content.innerHTML = html;
        } catch (error) {
            console.error('Error displaying record details:', error);
            const content = document.getElementById('recordDetailsContent');
            content.innerHTML = '<p style="color: #dc3545; text-align: center; padding: 40px;">Error displaying record details. Please try again.</p>';
        }
    }
    
    function closeRecordModal() {
        const modal = document.getElementById('recordDetailsModal');
        if (modal) {
            modal.classList.remove('active');
        }
    }
    
    function viewImage(imageSrc) {
        const viewer = document.getElementById('imageViewerModal');
        const img = document.getElementById('viewerImage');
        if (viewer && img) {
            img.src = imageSrc;
            viewer.classList.add('active');
        }
    }
    
    function closeImageViewer() {
        const viewer = document.getElementById('imageViewerModal');
        if (viewer) {
            viewer.classList.remove('active');
        }
    }
    
    // Initialize modal event listeners once on page load
    document.addEventListener('DOMContentLoaded', function() {
        // Note: Click-outside-to-close is disabled as per user request
        // Only the X button can close the modal
        const recordModal = document.getElementById('recordDetailsModal');
        if (recordModal) {
            // Prevent closing when clicking outside
            recordModal.addEventListener('click', function(e) {
                // Stop event propagation to prevent any default closing behavior
                e.stopPropagation();
                // Only allow closing if clicking directly on the overlay background (not on modal-box)
                if (e.target === recordModal) {
                    // Do nothing - prevent closing
                    e.preventDefault();
                    return false;
                }
            });
            
            // Also prevent clicks inside modal-box from closing
            const modalBox = recordModal.querySelector('.modal-box');
            if (modalBox) {
                modalBox.addEventListener('click', function(e) {
                    e.stopPropagation();
                });
            }
        }
        
        const imageViewer = document.getElementById('imageViewerModal');
        if (imageViewer) {
            imageViewer.addEventListener('click', function(e) {
                // Only close if clicking directly on the overlay, not on image-viewer-content or its children
                if (e.target === imageViewer) {
                    closeImageViewer();
                }
            });
        }
    });
</script>
@endsection
