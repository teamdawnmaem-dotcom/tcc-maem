@extends('layouts.appAdmin')

@section('title', 'Archived Attendance Records - Tagoloan Community College')
@section('archived-attendance-active', 'active')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/archived-attendance-records.css') }}">
@endsection

@section('content')
    @include('partials.flash')
    
    <div class="faculty-header">
        <div class="faculty-title-group">
            <div class="faculty-title">Archived Attendance Records</div>
            <div class="faculty-subtitle"></div>
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
            <div class="search-group" style="flex: 0 0 auto;">
                <a href="{{ route('admin.attendance.records.archived.print') }}" class="filter-btn" style="text-decoration: none; display: inline-block;">
                    Print PDF
                </a>
            </div>
        </div>
    </div>

    <!-- Desktop Table View -->
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

    <!-- Mobile Card View -->
    <div class="mobile-card-container">
        @forelse($archivedRecords as $record)
            @php
                // Prepare data for mobile card
                $facultyName = $record->faculty ? $record->faculty->faculty_fname . ' ' . $record->faculty->faculty_lname : 'Unknown Faculty';
                $department = $record->faculty ? $record->faculty->faculty_department : 'Unknown Department';
                $courseCode = $record->teachingLoadArchive ? $record->teachingLoadArchive->teaching_load_course_code : 'N/A';
                $subject = $record->teachingLoadArchive ? $record->teachingLoadArchive->teaching_load_subject : 'N/A';
                $classSection = $record->teachingLoadArchive ? $record->teachingLoadArchive->teaching_load_class_section : 'N/A';
                $day = $record->teachingLoadArchive ? $record->teachingLoadArchive->teaching_load_day_of_week : 'N/A';
                
                if($record->teachingLoadArchive) {
                    try {
                        $scheduleIn = \Carbon\Carbon::parse($record->teachingLoadArchive->teaching_load_time_in)->format('g:i A');
                        $scheduleOut = \Carbon\Carbon::parse($record->teachingLoadArchive->teaching_load_time_out)->format('g:i A');
                        $schedule = $scheduleIn . ' - ' . $scheduleOut;
                    } catch(\Exception $e) {
                        $schedule = ($record->teachingLoadArchive->teaching_load_time_in ?? 'N/A') . ' - ' . ($record->teachingLoadArchive->teaching_load_time_out ?? 'N/A');
                    }
                } else {
                    $schedule = 'N/A';
                }
                
                try {
                    $recordDate = \Carbon\Carbon::parse($record->record_date)->format('M d, Y');
                } catch(\Exception $e) {
                    $recordDate = $record->record_date;
                }
                
                try {
                    $archivedDate = \Carbon\Carbon::parse($record->archived_at)->format('M d, Y');
                } catch(\Exception $e) {
                    $archivedDate = $record->archived_at;
                }
                
                $timeIn = 'N/A';
                if(!(strtoupper(trim($record->record_remarks)) === 'ON LEAVE' || strtoupper(trim($record->record_remarks)) === 'WITH PASS SLIP' || !$record->record_time_in)) {
                    try {
                        $timeIn = \Carbon\Carbon::parse($record->record_time_in)->format('g:i a');
                    } catch(\Exception $e) {
                        $timeIn = $record->record_time_in;
                    }
                }
                
                $timeOut = 'N/A';
                if(!(strtoupper(trim($record->record_remarks)) === 'ON LEAVE' || strtoupper(trim($record->record_remarks)) === 'WITH PASS SLIP' || !$record->record_time_out)) {
                    try {
                        $timeOut = \Carbon\Carbon::parse($record->record_time_out)->format('g:i a');
                    } catch(\Exception $e) {
                        $timeOut = $record->record_time_out;
                    }
                }
                
                $duration = '0m 0s';
                if(!(strtoupper(trim($record->record_remarks)) === 'ON LEAVE' || strtoupper(trim($record->record_remarks)) === 'WITH PASS SLIP')) {
                    if($record->time_duration_seconds != 0) {
                        $duration = intval($record->time_duration_seconds / 60) . 'm ' . ($record->time_duration_seconds % 60) . 's';
                    }
                } else {
                    $duration = '0';
                }
                
                $room = $record->camera && $record->camera->room ? $record->camera->room->room_name : 'Unknown Room';
                $building = $record->camera && $record->camera->room ? $record->camera->room->room_building_no : 'Unknown Building';
                $status = $record->record_status;
                $remarks = $record->record_remarks;
                $remarksClass = '';
                if(strtoupper(trim($remarks)) === 'ON LEAVE') {
                    $remarksClass = 'remarks-on-leave';
                } elseif(strtoupper(trim($remarks)) === 'WITH PASS SLIP') {
                    $remarksClass = 'remarks-on-pass-slip';
                }
            @endphp
            <div class="mobile-card" 
                 data-school-year="{{ $record->school_year }}" 
                 data-semester="{{ $record->semester }}" 
                 data-faculty="{{ $facultyName }}">
                <div class="mobile-card-header">
                    <div class="mobile-card-archive-badge">
                        {{ $record->school_year }} • {{ $record->semester }}
                    </div>
                    <div class="mobile-card-faculty">{{ $facultyName }}</div>
                    <div class="mobile-card-department">{{ $department }}</div>
                    <small style="color: #999; font-size: 0.7rem;">Archived: {{ $archivedDate }}</small>
                </div>
                <div class="mobile-card-body">
                    <div class="mobile-card-row">
                        <div class="mobile-card-label">Course Code</div>
                        <div class="mobile-card-value">{{ $courseCode }}</div>
                    </div>
                    <div class="mobile-card-row">
                        <div class="mobile-card-label">Subject</div>
                        <div class="mobile-card-value">{{ $subject }}</div>
                    </div>
                    <div class="mobile-card-row">
                        <div class="mobile-card-label">Class Section</div>
                        <div class="mobile-card-value">{{ $classSection }}</div>
                    </div>
                    <div class="mobile-card-row">
                        <div class="mobile-card-label">Day</div>
                        <div class="mobile-card-value">{{ $day }}</div>
                    </div>
                    <div class="mobile-card-row">
                        <div class="mobile-card-label">Schedule</div>
                        <div class="mobile-card-value">{{ $schedule }}</div>
                    </div>
                    <div class="mobile-card-row">
                        <div class="mobile-card-label">Date</div>
                        <div class="mobile-card-value">{{ $recordDate }}</div>
                    </div>
                    <div class="mobile-card-row">
                        <div class="mobile-card-label">Time In</div>
                        <div class="mobile-card-value" style="{{ $timeIn === 'N/A' ? 'color: #999;' : '' }}">{{ $timeIn }}</div>
                    </div>
                    <div class="mobile-card-row">
                        <div class="mobile-card-label">Time Out</div>
                        <div class="mobile-card-value" style="{{ $timeOut === 'N/A' ? 'color: #999;' : '' }}">{{ $timeOut }}</div>
                    </div>
                    <div class="mobile-card-row">
                        <div class="mobile-card-label">Duration</div>
                        <div class="mobile-card-value" style="{{ ($duration === '0' || $duration === '0m 0s') ? 'color: #999;' : '' }}">{{ $duration }}</div>
                    </div>
                    <div class="mobile-card-row">
                        <div class="mobile-card-label">Room</div>
                        <div class="mobile-card-value">{{ $room }}</div>
                    </div>
                    <div class="mobile-card-row">
                        <div class="mobile-card-label">Building</div>
                        <div class="mobile-card-value">{{ $building }}</div>
                    </div>
                    <div class="mobile-card-row">
                        <div class="mobile-card-label">Status</div>
                        <div class="mobile-card-value">{{ $status }}</div>
                    </div>
                    <div class="mobile-card-row">
                        <div class="mobile-card-label">Remarks</div>
                        <div class="mobile-card-value">
                            @if($remarksClass)
                                <span class="{{ $remarksClass }}">{{ $remarks }}</span>
                            @else
                                {{ $remarks }}
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="mobile-card" style="text-align: center; padding: 40px;">
                <p style="font-style: italic; color: #666; margin: 0;">No archived attendance records found.</p>
            </div>
        @endforelse
    </div>

    

@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Filter functionality - works with both table and mobile cards
        function applyFilters() {
            // Trigger search which now includes filter checks
            searchRecords();
        }

        function clearFilters() {
            document.getElementById('schoolYearFilter').value = '';
            document.getElementById('semesterFilter').value = '';
            document.getElementById('facultyFilter').value = '';
            
            // Clear table rows (desktop)
            const rows = document.querySelectorAll('.teaching-load-table tbody tr');
            rows.forEach(row => {
                row.style.display = '';
            });
            
            // Clear mobile cards
            const cards = document.querySelectorAll('.mobile-card');
            cards.forEach(card => {
                card.style.display = '';
            });
            
            // Also trigger search to refresh view
            searchRecords();
        }

        // Search functionality - works with both table and mobile cards
        function searchRecords() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const schoolYear = document.getElementById('schoolYearFilter').value;
            const semester = document.getElementById('semesterFilter').value;
            const faculty = document.getElementById('facultyFilter').value;
            
            // Search in table rows (desktop)
            const rows = document.querySelectorAll('.teaching-load-table tbody tr');
            let anyTableVisible = false;

            rows.forEach(row => {
                if (row.classList.contains('no-results')) return;

                // Check filters first
                const rowSchoolYear = row.dataset.schoolYear;
                const rowSemester = row.dataset.semester;
                const rowFaculty = row.dataset.faculty;
                
                let passesFilter = true;
                if (schoolYear && rowSchoolYear !== schoolYear) passesFilter = false;
                if (semester && rowSemester !== semester) passesFilter = false;
                if (faculty && rowFaculty !== faculty) passesFilter = false;
                
                // Then check search term
                let passesSearch = true;
                if (searchTerm) {
                    let text = row.textContent.toLowerCase();
                    passesSearch = text.includes(searchTerm);
                }
                
                if (passesFilter && passesSearch) {
                    row.style.display = '';
                    anyTableVisible = true;
                } else {
                    row.style.display = 'none';
                }
            });

            // Handle "no results" row for table
            let tbody = document.querySelector('.teaching-load-table tbody');
            if (tbody) {
                let noResultsRow = tbody.querySelector('.no-results');
                if (!anyTableVisible && (searchTerm || schoolYear || semester || faculty)) {
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
            
            // Search in mobile cards
            const cards = document.querySelectorAll('.mobile-card');
            let anyCardVisible = false;
            const mobileContainer = document.querySelector('.mobile-card-container');
            
            cards.forEach(card => {
                if (card.classList.contains('no-results-mobile')) return;

                // Check filters first
                const cardSchoolYear = card.dataset.schoolYear;
                const cardSemester = card.dataset.semester;
                const cardFaculty = card.dataset.faculty;
                
                let passesFilter = true;
                if (schoolYear && cardSchoolYear !== schoolYear) passesFilter = false;
                if (semester && cardSemester !== semester) passesFilter = false;
                if (faculty && cardFaculty !== faculty) passesFilter = false;
                
                // Then check search term
                let passesSearch = true;
                if (searchTerm) {
                    let text = card.textContent.toLowerCase();
                    passesSearch = text.includes(searchTerm);
                }
                
                if (passesFilter && passesSearch) {
                    card.style.display = '';
                    anyCardVisible = true;
                } else {
                    card.style.display = 'none';
                }
            });
            
            // Handle "no results" message for mobile
            if (mobileContainer) {
                let noResultsCard = mobileContainer.querySelector('.no-results-mobile');
                if (!anyCardVisible && (searchTerm || schoolYear || semester || faculty)) {
                    if (!noResultsCard) {
                        noResultsCard = document.createElement('div');
                        noResultsCard.classList.add('mobile-card', 'no-results-mobile');
                        noResultsCard.style.textAlign = 'center';
                        noResultsCard.style.padding = '40px';
                        noResultsCard.innerHTML = '<p style="font-style: italic; color: #666; margin: 0;">No results found</p>';
                        mobileContainer.appendChild(noResultsCard);
                    }
                } else {
                    if (noResultsCard) noResultsCard.remove();
                }
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
        
        // Apply filters on page load if filters are set
        document.addEventListener('DOMContentLoaded', function() {
            const schoolYear = document.getElementById('schoolYearFilter').value;
            const semester = document.getElementById('semesterFilter').value;
            const faculty = document.getElementById('facultyFilter').value;
            
            if (schoolYear || semester || faculty) {
                applyFilters();
            }
        });
    </script>
@endsection
