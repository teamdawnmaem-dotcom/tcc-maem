@extends('layouts.appChecker')

@section('title', 'Real-Time Attendance Records - Tagoloan Community College')
@section('reports-active', 'active')

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
            position: fixed;
            top: 130px;
            right: 40px;
            z-index: 100;
        }

        .search-input {
            width: 700px;
            padding: 8px;
            font-size: 14px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .add-btn {
            padding: 8px 24px;
            font-size: 14px;
            border: none;
            border-radius: 4px;
            background-color: #2ecc71;
            color: #fff;
            cursor: pointer;
            font-weight: bold;
        }

        .teaching-load-table-container {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.22), 0 1.5px 8px rgba(0, 0, 0, 0.12);
            overflow: hidden;
            overflow-x: auto;
        }

        .teaching-load-table {
            width: 100%;
            min-width: 1400px;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .teaching-load-table th {
            background: #8B0000;
            color: #fff;
            padding: 16px 8px;
            font-size: 0.9rem;
            font-weight: bold;
            border: none;
            text-align: center;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            min-height: 50px;
        }

        .teaching-load-table td {
            padding: 16px 8px;
            text-align: center;
            font-size: 0.85rem;
            border: none;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            vertical-align: middle;
            min-height: 45px;
        }

        .teaching-load-table tr:nth-child(even) {
            background: #fff;
        }

        .teaching-load-table tr:nth-child(odd) {
            background: #fbeeee;
        }

        .teaching-load-table tr:hover {
            background: #fff2e6;
        }
        
        /* Column width adjustments */
        .teaching-load-table th:nth-child(1), .teaching-load-table td:nth-child(1) { width: 10%; } /* Date */
        .teaching-load-table th:nth-child(2), .teaching-load-table td:nth-child(2) { width: 12%; } /* Faculty Name */
        .teaching-load-table th:nth-child(3), .teaching-load-table td:nth-child(3) { width: 12%; } /* Department */
        .teaching-load-table th:nth-child(4), .teaching-load-table td:nth-child(4) { width: 8%; } /* Course Code */
        .teaching-load-table th:nth-child(5), .teaching-load-table td:nth-child(5) { width: 12%; } /* Subject */
        .teaching-load-table th:nth-child(6), .teaching-load-table td:nth-child(6) { width: 8%; } /* Day */
        .teaching-load-table th:nth-child(7), .teaching-load-table td:nth-child(7) { 
            width: 14%; 
            white-space: normal; 
            word-wrap: break-word;
            line-height: 1.2;
        } /* Time Schedule */
        .teaching-load-table th:nth-child(8), .teaching-load-table td:nth-child(8) { width: 6%; } /* Time In */
        .teaching-load-table th:nth-child(9), .teaching-load-table td:nth-child(9) { width: 6%; } /* Time Out */
        .teaching-load-table th:nth-child(10), .teaching-load-table td:nth-child(10) { width: 8%; } /* Time Duration */
        .teaching-load-table th:nth-child(11), .teaching-load-table td:nth-child(11) { width: 8%; } /* Room Name */
        .teaching-load-table th:nth-child(12), .teaching-load-table td:nth-child(12) { width: 6%; } /* Building No */
        .teaching-load-table th:nth-child(13), .teaching-load-table td:nth-child(13) { width: 6%; } /* Status */
        .teaching-load-table th:nth-child(14), .teaching-load-table td:nth-child(14) { width: 8%; } /* Remarks */

        /* Filter Styles - Clean & Neat Design */
        .filter-section {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            border: 1px solid #e9ecef;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
            padding: 30px;
            margin-bottom: 25px;
            position: relative;
            overflow: hidden;
        }

        .filter-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #8B0000, #6d0000);
        }

        .filter-header {
            display: flex;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f1f3f4;
        }

        .filter-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #2c3e50;
            margin: 0;
            display: flex;
            align-items: center;
        }

        .filter-title::before {
            content: '🔍';
            margin-right: 10px;
            font-size: 1.1rem;
        }

        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
            align-items: end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            position: relative;
            min-width: 0;
        }

        .filter-label {
            font-size: 0.9rem;
            color: #495057;
            margin-bottom: 8px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .filter-input,
        .filter-select {
            padding: 12px 16px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 0.95rem;
            background: #ffffff;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            color: #495057;
            font-weight: 500;
        }

        .filter-input:focus,
        .filter-select:focus {
            outline: none;
            border-color: #8B0000;
            box-shadow: 0 0 0 3px rgba(139, 0, 0, 0.1);
            transform: translateY(-1px);
        }

        .filter-input::placeholder {
            color: #adb5bd;
            font-weight: 400;
        }

        .filter-actions {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: end;
            justify-content: flex-start;
            margin-top: 20px;
            flex: 0 0 auto;
        }

        .filter-btn,
        .clear-btn,
        .print-btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            position: relative;
            overflow: hidden;
        }

        .filter-btn {
            background: linear-gradient(135deg, #8B0000, #6d0000);
            color: #fff;
            box-shadow: 0 4px 15px rgba(139, 0, 0, 0.3);
        }

        .filter-btn:hover {
            background: linear-gradient(135deg, #6d0000, #5a0000);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(139, 0, 0, 0.4);
        }

        .clear-btn {
            background: linear-gradient(135deg, #6c757d, #5a6268);
            color: #fff;
            box-shadow: 0 4px 15px rgba(108, 117, 125, 0.3);
        }

        .clear-btn:hover {
            background: linear-gradient(135deg, #5a6268, #495057);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(108, 117, 125, 0.4);
        }

        .print-btn {
            background: linear-gradient(135deg, #2ecc71, #27ae60);
            color: #fff;
            box-shadow: 0 4px 15px rgba(46, 204, 113, 0.3);
        }

        .print-btn:hover {
            background: linear-gradient(135deg, #27ae60, #229954);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(46, 204, 113, 0.4);
        }

        .search-section {
            display: flex;
            gap: 20px;
            align-items: end;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #f1f3f4;
        }

        .search-group {
            flex: 1;
            min-width: 0;
        }

        .search-actions {
            display: flex;
            gap: 15px;
            align-items: end;
            flex-shrink: 0;
        }

        @media (max-width: 768px) {
            .filter-grid {
                flex-direction: column;
                align-items: stretch;
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

        /* Remarks color coding */
        .teaching-load-table .remarks-on-leave {
            color: #dc3545 !important;
            font-weight: bold !important;
        }

        .teaching-load-table .remarks-on-pass-slip {
            color: #ff8c00 !important;
            font-weight: bold !important;
        }
    </style>
@endsection

@section('content')
    <div class="faculty-header">
        <div class="faculty-title-group">
            <div class="faculty-title">Real-Time Attendance Records</div>
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
            <div class="filter-actions">
                <button class="filter-btn" onclick="applyFilters()">Apply Filters</button>
                <button class="clear-btn" onclick="clearFilters()">Clear All</button>
            </div>
        </div>

        <div class="search-section">
            <div class="search-group">
                <input type="text" class="search-input" id="searchInput" placeholder="Search by faculty name, course code, subject, room, building...">
            </div>
            <div class="search-actions">
                <form id="printForm" method="GET" action="{{ route('checker.attendance.records.print') }}"
                    target="_blank">
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
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($record->record_date)->format('F j, Y') }}</td>
                        <td>{{ $record->faculty->faculty_fname }} {{ $record->faculty->faculty_lname }}</td>
                        <td>{{ $record->faculty->faculty_department }}</td>
                        <td>{{ $record->teachingLoad->teaching_load_course_code }}</td>
                        <td>{{ $record->teachingLoad->teaching_load_subject }}</td>
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
                        <td colspan="13" style="text-align:center; padding:20px;">No attendance records found</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection

@section('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.29/jspdf.plugin.autotable.min.js"></script>
    <script>
        // Current filters object
        let currentFilters = {};

        // Set current date as default filter
        function setCurrentDateFilter() {
            const today = new Date();
            const todayString = today.toISOString().split('T')[0]; // Format: YYYY-MM-DD
            
            // Set both start and end date to today
            document.getElementById('startDate').value = todayString;
            document.getElementById('endDate').value = todayString;
            
            // Update current filters with correct parameter names
            currentFilters.startDate = todayString;
            currentFilters.endDate = todayString;
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

        // Load attendance records
        async function loadRecords() {
            try {
                const params = new URLSearchParams({
                    ...currentFilters
                });

                const response = await fetch(`/checker/attendance-records?${params}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const data = await response.json();

                if (data.success) {
                    displayRecords(data.data);
                } else {
                    showError('Failed to load attendance records');
                }
            } catch (error) {
                console.error('Error loading records:', error);
                showError('Error loading attendance records: ' + error.message);
            }
        }

        // Display records in table
        function displayRecords(records) {
            const tbody = document.querySelector('.teaching-load-table tbody');
            if (records.length === 0) {
                tbody.innerHTML = '<tr><td colspan="13" class="no-records">No records found</td></tr>';
                return;
            }
            
        tbody.innerHTML = records.map(record => `
            <tr>
                <td>${formatDate(record.record_time_in)}</td>
                <td>${record.faculty.faculty_fname} ${record.faculty.faculty_lname}</td>
                <td>${record.faculty.faculty_department}</td>
                <td>${record.teachingLoad.teaching_load_course_code}</td>
                <td>${record.teachingLoad.teaching_load_subject}</td>
                <td>${record.teachingLoad.teaching_load_day_of_week}</td>
                <td>${formatTime(record.teachingLoad.teaching_load_time_in)} to ${formatTime(record.teachingLoad.teaching_load_time_out)}</td>
                <td>${formatTimeIn(record)}</td>
                <td>${formatTimeOut(record)}</td>
                <td>${formatDuration(record)}</td>
                <td>${record.camera.room.room_name}</td>
                <td>${record.camera.room.room_building_no}</td>
                <td>${record.record_status}</td>
                <td>${formatRemarks(record.record_remarks)}</td>
            </tr>
        `).join('');
        }

        // Helper functions
        function formatTime(timeString) {
            return new Date(timeString).toLocaleTimeString('en-US', { 
                hour: 'numeric', 
                minute: '2-digit',
                hour12: true 
            });
        }

        function formatDate(dateString) {
            return new Date(dateString).toLocaleDateString('en-US', { 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            });
        }

        function formatTimeIn(record) {
            const remarks = record.record_remarks ? record.record_remarks.toUpperCase().trim() : '';
            if (remarks === 'ON LEAVE' || remarks === 'WITH PASS SLIP') {
                return '<span style="color: #999;">N/A</span>';
            }
            return formatTime(record.record_time_in);
        }

        function formatTimeOut(record) {
            const remarks = record.record_remarks ? record.record_remarks.toUpperCase().trim() : '';
            if (remarks === 'ON LEAVE' || remarks === 'WITH PASS SLIP') {
                return '<span style="color: #999;">N/A</span>';
            }
            if (record.record_time_out) {
                return formatTime(record.record_time_out);
            }
            return '<span style="color: #999;">N/A</span>';
        }

        function formatDuration(record) {
            const remarks = record.record_remarks ? record.record_remarks.toUpperCase().trim() : '';
            if (remarks === 'ON LEAVE' || remarks === 'WITH PASS SLIP') {
                return '<span style="color: #999;">0</span>';
            }
            if (record.time_duration_seconds > 0) {
                const minutes = Math.floor(record.time_duration_seconds / 60);
                const remainingSeconds = record.time_duration_seconds % 60;
                return `${minutes}m ${remainingSeconds}s`;
            }
            return '<span style="color: #999;">0</span>';
        }

        function formatRemarks(remarks) {
            if (!remarks) return '';
            
            const upperRemarks = remarks.toUpperCase();
            if (upperRemarks === 'ON LEAVE') {
                return `<span class="remarks-on-leave">${remarks}</span>`;
            } else if (upperRemarks === 'ON PASS SLIP') {
                return `<span class="remarks-on-pass-slip">${remarks}</span>`;
            }
            return remarks;
        }

        function showError(message) {
            console.error(message);
            // You can add a toast notification here if needed
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

// Search on Enter key
document.querySelector('.search-input').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        searchLogs();
    }
});


        // Populate filter fields from URL parameters on page load
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

        // Sync hidden print inputs with current filters
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

        // Initialize filters from URL on page load
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

        document.getElementById('printForm').addEventListener('submit', syncPrintInputs);
    </script>
@endsection
