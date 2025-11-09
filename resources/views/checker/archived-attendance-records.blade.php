@extends('layouts.appChecker')

@section('title', 'Archived Attendance Records - Tagoloan Community College')
@section('reports-active', 'active')
@section('attendance-records-active', 'active')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/checker/archived-attendance-records.css') }}">
@endsection

@section('content')
    @include('partials.flash')
    
    <div class="faculty-header">
        <div class="faculty-title-group">
            <div class="faculty-title">Archived Attendance Records</div>
            <div class="faculty-subtitle"></div>
        </div>
        <div class="faculty-actions-row">
            <a href="{{ route('checker.attendance.records') }}" class="view-archive-btn">Back to Current</a>
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
