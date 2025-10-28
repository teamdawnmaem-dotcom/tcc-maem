@extends('layouts.appadmin')

@section('title', 'Archived Attendance Records - Tagoloan Community College')
@section('reports-active', 'active')
@section('attendance-records-active', 'active')

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
            position: absolute;
            top: 130px;
            right: 40px;
            z-index: 100;
        }

        .view-archive-btn {
            background-color: #6c757d;
            color: white;
            padding: 8px 24px;
            font-size: 14px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
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
            min-width: 2000px;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .teaching-load-table th {
            background: #8B0000;
            color: #fff;
            padding: 12px 8px;
            font-size: 0.9rem;
            font-weight: bold;
            border: none;
            text-align: center;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .teaching-load-table td {
            padding: 12px 8px;
            text-align: center;
            font-size: 0.85rem;
            border: none;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            vertical-align: middle;
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

        .teaching-load-table-scroll {
            max-height: 670px;
            overflow-y: auto;
            width: 100%;
        }

        /* Column width adjustments for comprehensive details */
        .teaching-load-table th:nth-child(1), .teaching-load-table td:nth-child(1) { width: 8%; } /* Archive Info */
        .teaching-load-table th:nth-child(2), .teaching-load-table td:nth-child(2) { width: 10%; } /* Faculty */
        .teaching-load-table th:nth-child(3), .teaching-load-table td:nth-child(3) { width: 8%; } /* Department */
        .teaching-load-table th:nth-child(4), .teaching-load-table td:nth-child(4) { width: 6%; } /* Course Code */
        .teaching-load-table th:nth-child(5), .teaching-load-table td:nth-child(5) { width: 8%; } /* Subject */
        .teaching-load-table th:nth-child(6), .teaching-load-table td:nth-child(6) { width: 6%; } /* Class Section */
        .teaching-load-table th:nth-child(7), .teaching-load-table td:nth-child(7) { width: 5%; } /* Day */
        .teaching-load-table th:nth-child(8), .teaching-load-table td:nth-child(8) { width: 8%; } /* Schedule */
        .teaching-load-table th:nth-child(9), .teaching-load-table td:nth-child(9) { width: 6%; } /* Date */
        .teaching-load-table th:nth-child(10), .teaching-load-table td:nth-child(10) { width: 5%; } /* Time In */
        .teaching-load-table th:nth-child(11), .teaching-load-table td:nth-child(11) { width: 5%; } /* Time Out */
        .teaching-load-table th:nth-child(12), .teaching-load-table td:nth-child(12) { width: 6%; } /* Duration */
        .teaching-load-table th:nth-child(13), .teaching-load-table td:nth-child(13) { width: 6%; } /* Room */
        .teaching-load-table th:nth-child(14), .teaching-load-table td:nth-child(14) { width: 5%; } /* Building */
        .teaching-load-table th:nth-child(15), .teaching-load-table td:nth-child(15) { width: 5%; } /* Status */
        .teaching-load-table th:nth-child(16), .teaching-load-table td:nth-child(16) { width: 7%; } /* Remarks */


        /* Search functionality */
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

        .search-input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 0.95rem;
            background: #ffffff;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            color: #495057;
            font-weight: 500;
        }

        .search-input:focus {
            outline: none;
            border-color: #8B0000;
            box-shadow: 0 0 0 3px rgba(139, 0, 0, 0.1);
            transform: translateY(-1px);
        }

        .search-input::placeholder {
            color: #adb5bd;
            font-weight: 400;
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

        /* Filter Styles */
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

        .filter-btn,
        .clear-btn {
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
            white-space: nowrap;
            min-width: 150px;
            width: auto;
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

        .archive-info {
            text-align: center;
        }

        .archive-info h4 {
            margin: 0 0 5px 0;
            color: #8B0000;
            font-size: 1.1rem;
        }

        .archive-info p {
            margin: 0 0 5px 0;
            color: #666;
            font-size: 0.9rem;
        }

        .archive-info small {
            color: #999;
            font-size: 0.8rem;
        }

        /* Remarks color coding */
        .remarks-on-leave {
            color: #dc3545 !important;
            font-weight: bold !important;
        }

        .remarks-on-pass-slip {
            color: #ff8c00 !important;
            font-weight: bold !important;
        }
    </style>
@endsection

@section('content')
    @include('partials.flash')
    
    <div class="faculty-header">
        <div class="faculty-title-group">
            <div class="faculty-title">Archived Attendance Records</div>
            <div class="faculty-subtitle"></div>
        </div>
        <div class="faculty-actions-row">
            <a href="{{ route('admin.attendance.records') }}" class="view-archive-btn">Back to Current</a>
        </div>
    </div>

    @if($errors->any())
        <div style="background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
            <strong>Error:</strong>
            <ul style="margin: 5px 0 0 20px;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Filter Section -->
    <div class="filter-section">
        <div class="filter-grid">
            <div class="filter-group">
                <label class="filter-label">School Year</label>
                <select class="filter-select" id="schoolYearFilter">
                    <option value="">All School Years</option>
                    @foreach($archivedRecords->pluck('school_year')->filter()->unique()->sort() as $schoolYear)
                        <option value="{{ $schoolYear }}">{{ $schoolYear }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filter-group">
                <label class="filter-label">Semester</label>
                <select class="filter-select" id="semesterFilter">
                    <option value="">All Semesters</option>
                    @foreach($archivedRecords->pluck('semester')->filter()->unique()->sort() as $semester)
                        <option value="{{ $semester }}">{{ $semester }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filter-group">
                <label class="filter-label">Faculty</label>
                <select class="filter-select" id="facultyFilter">
                    <option value="">All Faculty</option>
                    @foreach($archivedRecords->pluck('faculty')->unique()->filter() as $faculty)
                        <option value="{{ $faculty->faculty_fname }} {{ $faculty->faculty_lname }}">
                            {{ $faculty->faculty_fname }} {{ $faculty->faculty_lname }}
                        </option>
                    @endforeach
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
                <input type="text" class="search-input" id="searchInput" placeholder="Search by faculty name, course code, subject, room, building, department...">
            </div>
        </div>
    </div>

    <div class="teaching-load-table-container">
        <div class="teaching-load-table-scroll">
            <table class="teaching-load-table">
                <thead>
                    <tr>
                        <th>Archive Info</th>
                        <th>Faculty</th>
                        <th>Department</th>
                        <th>Course Code</th>
                        <th>Subject</th>
                        <th>Class Section</th>
                        <th>Day</th>
                        <th>Schedule</th>
                        <th>Date</th>
                        <th>Time In</th>
                        <th>Time Out</th>
                        <th>Duration</th>
                        <th>Room</th>
                        <th>Building</th>
                        <th>Status</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($archivedRecords as $record)
                        <tr data-school-year="{{ $record->school_year }}" data-semester="{{ $record->semester }}" data-faculty="{{ $record->faculty ? $record->faculty->faculty_fname . ' ' . $record->faculty->faculty_lname : 'Unknown Faculty' }}">
                            <td>
                                <div class="archive-info">
                                    <h4>{{ $record->school_year }}</h4>
                                    <p>{{ $record->semester }}</p>
                                    <small style="color: #999;">
                                        @php
                                            try {
                                                $archivedDate = \Carbon\Carbon::parse($record->archived_at)->format('M d, Y');
                                            } catch(\Exception $e) {
                                                $archivedDate = $record->archived_at;
                                            }
                                        @endphp
                                        {{ $archivedDate }}
                                    </small>
                                </div>
                            </td>
                            <td>{{ $record->faculty ? $record->faculty->faculty_fname . ' ' . $record->faculty->faculty_lname : 'Unknown Faculty' }}</td>
                            <td>{{ $record->faculty ? $record->faculty->faculty_department : 'Unknown Department' }}</td>
                            <td>{{ $record->teachingLoadArchive ? $record->teachingLoadArchive->teaching_load_course_code : 'N/A' }}</td>
                            <td>{{ $record->teachingLoadArchive ? $record->teachingLoadArchive->teaching_load_subject : 'N/A' }}</td>
                            <td>{{ $record->teachingLoadArchive ? $record->teachingLoadArchive->teaching_load_class_section : 'N/A' }}</td>
                            <td>{{ $record->teachingLoadArchive ? $record->teachingLoadArchive->teaching_load_day_of_week : 'N/A' }}</td>
                            <td>
                                @if($record->teachingLoadArchive)
                                    @php
                                        try {
                                            $scheduleIn = \Carbon\Carbon::parse($record->teachingLoadArchive->teaching_load_time_in)->format('g:i A');
                                            $scheduleOut = \Carbon\Carbon::parse($record->teachingLoadArchive->teaching_load_time_out)->format('g:i A');
                                        } catch(\Exception $e) {
                                            $scheduleIn = $record->teachingLoadArchive->teaching_load_time_in;
                                            $scheduleOut = $record->teachingLoadArchive->teaching_load_time_out;
                                        }
                                    @endphp
                                    {{ $scheduleIn }} - {{ $scheduleOut }}
                                @else
                                    N/A
                                @endif
                            </td>
                            <td>
                                @php
                                    try {
                                        $recordDate = \Carbon\Carbon::parse($record->record_date)->format('M d, Y');
                                    } catch(\Exception $e) {
                                        $recordDate = $record->record_date;
                                    }
                                @endphp
                                {{ $recordDate }}
                            </td>
                            <td>
                                @if(strtoupper(trim($record->record_remarks)) === 'ON LEAVE' || strtoupper(trim($record->record_remarks)) === 'WITH PASS SLIP' || !$record->record_time_in)
                                    <span style="color: #999;">N/A</span>
                                @else
                                    @php
                                        try {
                                            $timeIn = \Carbon\Carbon::parse($record->record_time_in)->format('g:i a');
                                        } catch(\Exception $e) {
                                            $timeIn = $record->record_time_in;
                                        }
                                    @endphp
                                    {{ $timeIn }}
                                @endif
                            </td>
                            <td>
                                @if(strtoupper(trim($record->record_remarks)) === 'ON LEAVE' || strtoupper(trim($record->record_remarks)) === 'WITH PASS SLIP' || !$record->record_time_out)
                                    <span style="color: #999;">N/A</span>
                                @else
                                    @php
                                        try {
                                            $timeOut = \Carbon\Carbon::parse($record->record_time_out)->format('g:i a');
                                        } catch(\Exception $e) {
                                            $timeOut = $record->record_time_out;
                                        }
                                    @endphp
                                    {{ $timeOut }}
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
                            <td>{{ $record->camera && $record->camera->room ? $record->camera->room->room_name : 'Unknown Room' }}</td>
                            <td>{{ $record->camera && $record->camera->room ? $record->camera->room->room_building_no : 'Unknown Building' }}</td>
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
                            <td colspan="16" style="text-align:center; font-style:italic; color:#666; padding: 40px;">
                                No archived attendance records found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Filter functionality
        function applyFilters() {
            const schoolYear = document.getElementById('schoolYearFilter').value;
            const semester = document.getElementById('semesterFilter').value;
            const faculty = document.getElementById('facultyFilter').value;
            
            const rows = document.querySelectorAll('.teaching-load-table tbody tr');
            
            rows.forEach(row => {
                const rowSchoolYear = row.dataset.schoolYear;
                const rowSemester = row.dataset.semester;
                const rowFaculty = row.dataset.faculty;
                
                let show = true;
                
                if (schoolYear && rowSchoolYear !== schoolYear) show = false;
                if (semester && rowSemester !== semester) show = false;
                if (faculty && rowFaculty !== faculty) show = false;
                
                row.style.display = show ? '' : 'none';
            });
        }

        function clearFilters() {
            document.getElementById('schoolYearFilter').value = '';
            document.getElementById('semesterFilter').value = '';
            document.getElementById('facultyFilter').value = '';
            
            const rows = document.querySelectorAll('.teaching-load-table tbody tr');
            rows.forEach(row => {
                row.style.display = '';
            });
        }

        // Search functionality
        function searchRecords() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const rows = document.querySelectorAll('.teaching-load-table tbody tr');
            let anyVisible = false;

            rows.forEach(row => {
                // Skip the "no results" row if it exists
                if (row.classList.contains('no-results')) return;

                let text = row.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    row.style.display = '';
                    anyVisible = true;
                } else {
                    row.style.display = 'none';
                }
            });

            // Handle "no results" row
            let tbody = document.querySelector('.teaching-load-table tbody');
            let noResultsRow = tbody.querySelector('.no-results');

            if (!anyVisible) {
                if (!noResultsRow) {
                    noResultsRow = document.createElement('tr');
                    noResultsRow.classList.add('no-results');
                    noResultsRow.innerHTML =
                        `<td colspan="16" style="text-align:center; padding:20px; color:#999; font-style:italic;">No results found</td>`;
                    tbody.appendChild(noResultsRow);
                }
            } else {
                if (noResultsRow) noResultsRow.remove();
            }
        }

        // Search on input
        document.getElementById('searchInput').addEventListener('input', searchRecords);

        // Search on Enter key
        document.getElementById('searchInput').addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                searchRecords();
            }
        });
    </script>
@endsection
