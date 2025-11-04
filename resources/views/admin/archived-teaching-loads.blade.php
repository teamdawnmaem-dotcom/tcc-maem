@extends('layouts.appAdmin')

@section('title', 'Archived Teaching Loads - Tagoloan Community College')
@section('files-active', 'active')
@section('teaching-load-active', 'active')

@section('styles')
    <style>
        /* ====== Header & Actions ====== */
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
            position: fixed;
            top: 104px;
            right: 32px;
            z-index: 100;
        }

        .search-input {
            padding: 6.4px;
            font-size: 11.2px;
            border: 1px solid #ccc;
            border-radius: 3.2px;
            width: 320px;
        }

        .back-btn {
            padding: 6px 19px;
            font-size: 11.2px;
            border: none;
            border-radius: 3.2px;
            background-color: #6c757d;
            color: #fff;
            cursor: pointer;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
        }

        /* ====== Table Styles ====== */
        .faculty-table-container {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.22), 0 1.5px 8px rgba(0, 0, 0, 0.12);
            overflow: hidden;
        }

        .faculty-table {
            width: 100%;
            border-collapse: collapse;
        }

        .faculty-table th {
            background: #8B0000;
            color: #fff;
            padding: 12.8px 6.4px;
            font-size: 0.88rem;
            font-weight: bold;
            border: none;
            text-align: center;
            vertical-align: middle;
        }

        .faculty-table thead th {
            position: sticky;
            top: 0;
            z-index: 2;
        }

        .faculty-table td {
            padding: 0px 0px 0px 0px;
            text-align: center;
            font-size: 0.8rem;
            border: none;
            vertical-align: middle;
        }

        .faculty-table tr:nth-child(even) {
            background: #fff;
        }

        .faculty-table tr:nth-child(odd) {
            background: #fbeeee;
        }

        .faculty-table tr:hover {
            background: #fff2e6;
        }

        .faculty-table-scroll {
            max-height: 536px;
            overflow-y: auto;
            width: 100%;
        }

        /* ====== Action Buttons ====== */
        .action-btns {
            display: flex;
            gap: 8px;
            justify-content: center;
            align-items: center;
        }

        .restore-btn,
        .delete-btn {
            width: 32px;
            height: 25.6px;
            border-radius: 4.8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.88rem;
            font-weight: bold;
            background: #fff;
            border: none;
            cursor: pointer;
        }

        .restore-btn {
            background: #28a745;
            color: #fff;
            position: relative;
        }

        .restore-btn::before {
            content: '↻';
            font-size: 1.3rem;
            font-weight: bold;
            display: inline-block;
            transform: rotate(-45deg);
        }

        .delete-btn {
            background: #ff3636;
            color: #fff;
            font-size: 1.1rem;
            font-weight: bold;
        }

        .restore-btn:active,
        .delete-btn:active {
            box-shadow: 0 0 0 2px #2222;
        }

        /* ====== Archive Info ====== */
        .archive-info {
            background: #f8f9fa;
            border-left: 3.2px solid #8B0000;
            padding: 8px 12px;
            margin-bottom: 16px;
            border-radius: 0 6.4px 6.4px 0;
        }

        .archive-info h4 {
            margin: 0 0 5px 0;
            color: #8B0000;
            font-size: 0.88rem;
        }

        .archive-info p {
            margin: 0;
            color: #666;
            font-size: 0.72rem;
        }

        /* ====== Filter Styles ====== */
        .filter-section {
            background: #f8f9fa;
            padding: 12px;
            border-radius: 6.4px;
            margin-bottom: 16px;
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .filter-group label {
            font-size: 0.72rem;
            font-weight: bold;
            color: #333;
        }

         .filter-group select {
             padding: 6.4px 9.6px;
             border: 1px solid #ccc;
             border-radius: 3.2px;
             font-size: 0.72rem;
         }

         /* ====== Modal Styles ====== */
         .modal-overlay {
             position: fixed;
             top: 0;
             left: 0;
             right: 0;
             bottom: 0;
             background: rgba(0, 0, 0, 0.3);
             display: flex;
             align-items: center;
             justify-content: center;
             z-index: 9999;
         }

         .modal-box {
             background: #fff;
             border-radius: 8px;
             width: 360px;
             max-width: 95vw;
             padding: 0;
             box-shadow: 0 8px 32px rgba(0, 0, 0, 0.22), 0 1.5px 8px rgba(0, 0, 0, 0.12);
             display: flex;
             flex-direction: column;
             align-items: center;
             overflow: hidden;
             position: relative;
             z-index: 10000;
             pointer-events: auto;
         }

         .modal-header-custom {
             background-color: #8B0000;
             color: #fff;
             font-weight: bold;
             text-align: center;
             border-top-left-radius: 9.6px;
             border-top-right-radius: 9.6px;
             padding: 12px 16px;
             font-size: 1.2rem;
             letter-spacing: 0.8px;
             width: 100%;
             margin-bottom: 0;
         }

         .modal-btn {
             width: auto;
             padding: 9.6px 19px;
             font-size: 0.8rem;
             font-weight: bold;
             border-radius: 8px;
             cursor: pointer;
             margin-top: 10px;
             border: none;
         }

         .modal-btn.cancel {
             background: #fff;
             color: #800000;
             border: 2px solid #800000;
             border-radius: 8px;
         }

         .modal-btn.cancel:hover {
             background: #800000;
             color: #fff;
         }

         /* Mobile Responsive Design for phones (max-width: 430px) */
         @media (max-width: 430px) {
             /* Faculty Header */
             .faculty-header {
                 flex-direction: column;
                 align-items: flex-start;
                 gap: 16px;
                 margin-bottom: 20px;
                 position: relative;
             }

             .faculty-title-group {
                 width: 100%;
             }

             .faculty-title {
                 font-size: 1.4rem;
                 margin-bottom: 4px;
             }

             .faculty-subtitle {
                 font-size: 0.75rem;
                 margin-bottom: 0;
             }

             /* Faculty Actions Row */
             .faculty-actions-row {
                 position: relative;
                 top: 0;
                 right: 0;
                 width: 100%;
                 flex-direction: column;
                 gap: 10px;
                 z-index: 1;
             }

             .search-input {
                 width: 100% !important;
                 padding: 10px 12px;
                 font-size: 0.9rem;
                 border-radius: 6px;
                 box-sizing: border-box;
             }

             .back-btn {
                 width: 100%;
                 padding: 12px;
                 font-size: 0.9rem;
                 border-radius: 6px;
                 font-weight: bold;
                 text-align: center;
                 display: block;
             }

             /* Filter Section */
             .filter-section {
                 flex-direction: column;
                 align-items: stretch;
                 gap: 12px;
                 padding: 16px 12px;
                 margin-bottom: 16px;
                 border-radius: 8px;
             }

             .filter-group {
                 width: 100%;
             }

             .filter-group label {
                 font-size: 0.7rem;
                 margin-bottom: 6px;
             }

             .filter-group select {
                 width: 100%;
                 padding: 10px 12px;
                 font-size: 0.85rem;
                 border-radius: 6px;
                 box-sizing: border-box;
             }

             /* Archive Info */
             .archive-info {
                 padding: 10px 10px;
                 font-size: 0.7rem;
             }

             .archive-info h4 {
                 font-size: 0.8rem;
                 margin-bottom: 4px;
             }

             .archive-info p {
                 font-size: 0.7rem;
                 margin: 2px 0;
             }

             .archive-info small {
                 font-size: 0.65rem;
             }

             /* Table Container */
             .faculty-table-container {
                 border-radius: 8px;
                 overflow: hidden;
             }

             .faculty-table-scroll {
                 max-height: 50vh;
                 overflow-x: auto;
                 overflow-y: auto;
                 -webkit-overflow-scrolling: touch;
             }

             .faculty-table {
                 min-width: 1000px; /* Minimum width to maintain readability */
             }

             .faculty-table th {
                 padding: 10px 6px;
                 font-size: 0.7rem;
                 white-space: nowrap;
             }

             .faculty-table td {
                 padding: 8px 6px;
                 font-size: 0.7rem;
                 white-space: nowrap;
             }

             /* Empty state message */
             .faculty-table td[colspan] {
                 font-size: 0.75rem;
                 padding: 20px 12px;
             }

             /* Action Buttons */
             .action-btns {
                 gap: 6px;
             }

             .restore-btn,
             .delete-btn {
                 width: 32px;
                 height: 28px;
                 font-size: 0.9rem;
             }

             /* Attendance Count Badge */
             .attendance-count {
                 font-size: 0.75rem !important;
                 padding: 3px 6px !important;
             }

             /* Modals - Mobile Optimized */
             .modal-overlay {
                 padding: 10px;
             }

             .modal-box {
                 width: 95vw !important;
                 max-width: 95vw !important;
                 padding: 0 !important;
                 margin: 0;
             }

             /* Restore Modal */
             #restoreModal .modal-box {
                 width: 95vw !important;
                 max-width: 95vw !important;
             }

             #restoreModal .modal-header-custom {
                 font-size: 1.1rem !important;
                 padding: 12px 16px !important;
             }

             #restoreModal > div {
                 padding: 16px !important;
             }

             #restoreModal .modal-btn {
                 width: 100% !important;
                 padding: 12px !important;
                 font-size: 0.9rem !important;
                 margin: 5px 0 !important;
             }

             /* Delete Modal */
             #deleteModal .modal-box {
                 width: 95vw !important;
                 max-width: 95vw !important;
             }

             #deleteModal .modal-header-custom {
                 font-size: 1.1rem !important;
                 padding: 12px 16px !important;
             }

             #deleteModal > div {
                 padding: 16px !important;
             }

             #deleteModal .modal-btn {
                 width: 100% !important;
                 padding: 12px !important;
                 font-size: 0.9rem !important;
                 margin: 5px 0 !important;
             }

             /* Modal Content Adjustments */
             .modal-box > div {
                 padding: 16px !important;
             }

             .modal-box div[style*="padding"] {
                 padding: 12px !important;
             }

             /* Info boxes in modals */
             .modal-box div[style*="background"] {
                 padding: 12px !important;
                 margin-bottom: 12px !important;
             }

             .modal-box div[style*="background"] span {
                 font-size: 1.2rem !important;
             }

             .modal-box div[style*="background"] strong {
                 font-size: 0.85rem !important;
             }

             .modal-box div[style*="background"] p {
                 font-size: 0.8rem !important;
             }

             /* Modal buttons container */
             .modal-box div[style*="text-align: center"] {
                 flex-direction: column !important;
                 gap: 10px !important;
             }

             .modal-box div[style*="text-align: center"] button {
                 width: 100% !important;
                 margin: 0 !important;
             }
         }
     </style>
 @endsection

@section('content')
    @include('partials.flash')
    @include('partials.flashupdate')
    @include('partials.flashdelete')
    
    @if($errors->any())
        <div style="background: #f8d7da; color: #721c24; padding: 15px; margin-bottom: 20px; border-radius: 8px; border: 1px solid #f5c6cb;">
            <strong>Error:</strong>
            <ul style="margin: 5px 0 0 0; padding-left: 20px;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    
    <div class="faculty-header">
        <div class="faculty-title-group">
            <div class="faculty-title">Archived Teaching Loads</div>
            <br>
        </div>
        <div class="faculty-actions-row">
            <input type="text" class="search-input" placeholder="Search archived loads...">
            <a href="{{ route('admin.teaching.load.management') }}" class="back-btn">Back to Current</a>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="filter-section">
        <div class="filter-group">
            <label for="schoolYearFilter">School Year:</label>
            <select id="schoolYearFilter">
                <option value="">All Years</option>
                @foreach($archivedLoads->pluck('school_year')->unique()->sort()->reverse() as $year)
                    <option value="{{ $year }}">{{ $year }}</option>
                @endforeach
            </select>
        </div>
        <div class="filter-group">
            <label for="semesterFilter">Semester:</label>
            <select id="semesterFilter">
                <option value="">All Semesters</option>
                @foreach($archivedLoads->pluck('semester')->unique()->sort() as $semester)
                    <option value="{{ $semester }}">{{ $semester }}</option>
                @endforeach
            </select>
        </div>
        <div class="filter-group">
            <label for="facultyFilter">Faculty:</label>
            <select id="facultyFilter">
                <option value="">All Faculty</option>
                @foreach($archivedLoads->filter(function($load) { return $load->faculty; })->map(function($load) { return $load->faculty->faculty_fname . ' ' . $load->faculty->faculty_lname; })->unique() as $faculty)
                    <option value="{{ $faculty }}">{{ $faculty }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="faculty-table-container">
        <div class="faculty-table-scroll">
            <table class="faculty-table">
                <thead>
                    <tr>
                        <th>Archive Info</th>
                        <th>Faculty</th>
                        <th>Course Code</th>
                        <th>Subject</th>
                        <th>Class Section</th>
                        <th>Day</th>
                        <th>Time In</th>
                        <th>Time Out</th>
                        <th>Room</th>
                        <th>Attendance Records</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($archivedLoads as $load)
                        <tr data-school-year="{{ $load->school_year }}" data-semester="{{ $load->semester }}" data-faculty="{{ $load->faculty ? $load->faculty->faculty_fname . ' ' . $load->faculty->faculty_lname : 'Unknown Faculty' }}">
                            <td>
                                <div class="archive-info">
                                    <h4>{{ $load->school_year }}</h4>
                                    <p>{{ $load->semester }}</p>
                                    <small style="color: #999;">
                                        @php
                                            try {
                                                $archivedDate = \Carbon\Carbon::parse($load->archived_at)->format('M d, Y');
                                            } catch(\Exception $e) {
                                                $archivedDate = $load->archived_at;
                                            }
                                        @endphp
                                        {{ $archivedDate }}
                                    </small>
                                </div>
                            </td>
                            <td>{{ $load->faculty ? $load->faculty->faculty_fname . ' ' . $load->faculty->faculty_lname : 'Unknown Faculty' }}</td>
                            <td>{{ $load->teaching_load_course_code }}</td>
                            <td>{{ $load->teaching_load_subject }}</td>
                            <td>{{ $load->teaching_load_class_section }}</td>
                            <td>{{ $load->teaching_load_day_of_week }}</td>
                            <td>
                                @php
                                    try {
                                        $timeIn = \Carbon\Carbon::parse($load->teaching_load_time_in)->format('g:i a');
                                    } catch(\Exception $e) {
                                        $timeIn = $load->teaching_load_time_in;
                                    }
                                @endphp
                                {{ $timeIn }}
                            </td>
                            <td>
                                @php
                                    try {
                                        $timeOut = \Carbon\Carbon::parse($load->teaching_load_time_out)->format('g:i a');
                                    } catch(\Exception $e) {
                                        $timeOut = $load->teaching_load_time_out;
                                    }
                                @endphp
                                {{ $timeOut }}
                            </td>
                            <td>{{ $load->room->room_name ?? $load->room_no }}</td>
                            <td>
                                <span class="attendance-count" style="background: #e3f2fd; color: #1976d2; padding: 4px 8px; border-radius: 12px; font-size: 0.85rem; font-weight: bold;">
                                    {{ $load->attendance_records_count ?? 0 }} records
                                </span>
                            </td>
                            <td>
                                <div class="action-btns">
                                    <button class="restore-btn" onclick="openRestoreModal({{ $load->archive_id }}, '{{ $load->teaching_load_course_code }}', '{{ $load->teaching_load_subject }}', '{{ $load->faculty ? $load->faculty->faculty_fname . ' ' . $load->faculty->faculty_lname : 'Unknown Faculty' }}')" title="Restore"></button>
                                    <button class="delete-btn" onclick="openDeleteModal({{ $load->archive_id }}, '{{ $load->teaching_load_course_code }}', '{{ $load->teaching_load_subject }}', '{{ $load->faculty ? $load->faculty->faculty_fname . ' ' . $load->faculty->faculty_lname : 'Unknown Faculty' }}')" title="Permanently Delete">&#128465;</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" style="text-align:center; font-style:italic; color:#666; padding: 40px;">
                                No archived teaching loads found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        // Filter functionality
        document.addEventListener('DOMContentLoaded', function() {
            const schoolYearFilter = document.getElementById('schoolYearFilter');
            const semesterFilter = document.getElementById('semesterFilter');
            const facultyFilter = document.getElementById('facultyFilter');
            const searchInput = document.querySelector('.search-input');
            const rows = document.querySelectorAll('tbody tr');

            function filterRows() {
                const schoolYear = schoolYearFilter.value.toLowerCase();
                const semester = semesterFilter.value.toLowerCase();
                const faculty = facultyFilter.value.toLowerCase();
                const searchTerm = searchInput.value.toLowerCase();

                rows.forEach(row => {
                    if (row.classList.contains('no-results')) return;

                    const rowSchoolYear = row.dataset.schoolYear?.toLowerCase() || '';
                    const rowSemester = row.dataset.semester?.toLowerCase() || '';
                    const rowFaculty = row.dataset.faculty?.toLowerCase() || '';
                    const rowText = row.textContent.toLowerCase();

                    const schoolYearMatch = !schoolYear || rowSchoolYear.includes(schoolYear);
                    const semesterMatch = !semester || rowSemester.includes(semester);
                    const facultyMatch = !faculty || rowFaculty.includes(faculty);
                    const searchMatch = !searchTerm || rowText.includes(searchTerm);

                    if (schoolYearMatch && semesterMatch && facultyMatch && searchMatch) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });

                // Handle "no results" message
                const visibleRows = Array.from(rows).filter(row => 
                    !row.classList.contains('no-results') && row.style.display !== 'none'
                );

                let tbody = document.querySelector('tbody');
                let noResultsRow = tbody.querySelector('.no-results');

                if (visibleRows.length === 0) {
                    if (!noResultsRow) {
                        noResultsRow = document.createElement('tr');
                        noResultsRow.classList.add('no-results');
                        noResultsRow.innerHTML = '<td colspan="10" style="text-align:center; padding:20px; color:#999; font-style:italic;">No results found</td>';
                        tbody.appendChild(noResultsRow);
                    }
                } else {
                    if (noResultsRow) noResultsRow.remove();
                }
            }

             schoolYearFilter.addEventListener('change', filterRows);
             semesterFilter.addEventListener('change', filterRows);
             facultyFilter.addEventListener('change', filterRows);
             searchInput.addEventListener('input', filterRows);
         });

         // Close modals when clicking outside (only on overlay, not modal box)
         document.addEventListener('click', function(e) {
             if (e.target.classList && e.target.classList.contains('modal-overlay')) {
                 const overlayId = e.target.id;
                 e.target.style.display = 'none';
             }
         });

         // Prevent modal box clicks from closing the modal
         document.addEventListener('click', function(e) {
             if (e.target.closest && e.target.closest('.modal-box')) {
                 e.stopPropagation();
             }
         });

         // Modal functions
         function openModal(id) {
             const modal = document.getElementById(id);
             if (modal) {
                 modal.style.display = 'flex';
             }
         }

         function closeModal(id) {
             const modal = document.getElementById(id);
             if (modal) {
                 modal.style.display = 'none';
             }
         }

         // Initialize restore button event listener on page load
         document.addEventListener('DOMContentLoaded', function() {
             const restoreBtn = document.getElementById('confirmRestoreBtn');
             if (restoreBtn) {
                 restoreBtn.addEventListener('click', function(e) {
                     e.preventDefault();
                     e.stopPropagation();
                     
                     const archiveId = this.getAttribute('data-archive-id');
                     if (archiveId) {
                         restoreTeachingLoad(parseInt(archiveId));
                     } else {
                         console.error('Archive ID not found on restore button');
                         Swal.fire({
                             icon: 'error',
                             title: 'Error',
                             text: 'Unable to restore: Archive ID not found. Please try again.',
                             confirmButtonColor: '#8B0000',
                             confirmButtonText: 'OK'
                         });
                     }
                 });
             }

             // Initialize delete button event listener on page load
             const deleteBtn = document.getElementById('confirmDeleteBtn');
             if (deleteBtn) {
                 deleteBtn.addEventListener('click', function(e) {
                     e.preventDefault();
                     e.stopPropagation();
                     
                     const archiveId = this.getAttribute('data-archive-id');
                     if (archiveId) {
                         permanentlyDelete(parseInt(archiveId));
                     } else {
                         console.error('Archive ID not found on delete button');
                         Swal.fire({
                             icon: 'error',
                             title: 'Error',
                             text: 'Unable to delete: Archive ID not found. Please try again.',
                             confirmButtonColor: '#8B0000',
                             confirmButtonText: 'OK'
                         });
                     }
                 });
             }
         });

         function openRestoreModal(archiveId, courseCode, subject, faculty) {
             // Update modal content
             const courseCodeEl = document.getElementById('restoreCourseCode');
             const subjectEl = document.getElementById('restoreSubject');
             const facultyEl = document.getElementById('restoreFaculty');
             const restoreBtn = document.getElementById('confirmRestoreBtn');
             
             if (courseCodeEl) courseCodeEl.textContent = courseCode;
             if (subjectEl) subjectEl.textContent = subject;
             if (facultyEl) facultyEl.textContent = faculty;
             
             // Store archiveId in button's data attribute
             if (restoreBtn) {
                 restoreBtn.setAttribute('data-archive-id', archiveId);
             }
             
             openModal('restoreModal');
         }

         function openDeleteModal(archiveId, courseCode, subject, faculty) {
             // Update modal content
             const courseCodeEl = document.getElementById('deleteCourseCode');
             const subjectEl = document.getElementById('deleteSubject');
             const facultyEl = document.getElementById('deleteFaculty');
             const deleteBtn = document.getElementById('confirmDeleteBtn');
             
             if (courseCodeEl) courseCodeEl.textContent = courseCode;
             if (subjectEl) subjectEl.textContent = subject;
             if (facultyEl) facultyEl.textContent = faculty;
             
             // Store archiveId in button's data attribute
             if (deleteBtn) {
                 deleteBtn.setAttribute('data-archive-id', archiveId);
             }
             
             openModal('deleteModal');
         }

         function restoreTeachingLoad(archiveId) {
             if (!archiveId || isNaN(archiveId)) {
                 console.error('Invalid archive ID:', archiveId);
                 Swal.fire({
                     icon: 'error',
                     title: 'Error',
                     text: 'Invalid archive ID. Please try again.',
                     confirmButtonColor: '#8B0000',
                     confirmButtonText: 'OK'
                 });
                 return;
             }

             // Disable button during request
             const restoreBtn = document.getElementById('confirmRestoreBtn');
             if (restoreBtn) {
                 restoreBtn.disabled = true;
                 restoreBtn.style.opacity = '0.6';
                 restoreBtn.style.cursor = 'not-allowed';
             }

             const csrfToken = document.querySelector('meta[name="csrf-token"]');
             if (!csrfToken) {
                 console.error('CSRF token not found');
                 Swal.fire({
                     icon: 'error',
                     title: 'Error',
                     text: 'Security token not found. Please refresh the page.',
                     confirmButtonColor: '#8B0000',
                     confirmButtonText: 'OK'
                 });
                 if (restoreBtn) {
                     restoreBtn.disabled = false;
                     restoreBtn.style.opacity = '1';
                     restoreBtn.style.cursor = 'pointer';
                 }
                 return;
             }

             fetch(`/admin/teaching-load/restore/${archiveId}`, {
                 method: 'POST',
                 headers: {
                     'X-CSRF-TOKEN': csrfToken.getAttribute('content'),
                     'Content-Type': 'application/json',
                     'Accept': 'application/json',
                 },
             })
             .then(async (response) => {
                 const contentType = response.headers.get('content-type') || '';
                 let data = null;
                 
                 if (contentType.includes('application/json')) {
                     try {
                         data = await response.json();
                     } catch (e) {
                         console.error('Failed to parse JSON response:', e);
                         data = null;
                     }
                 } else {
                     // Try to get text response for debugging
                     const text = await response.text();
                     console.error('Non-JSON response received:', text);
                 }
                 
                 return { ok: response.ok, status: response.status, data };
             })
             .then(({ ok, status, data }) => {
                 // Re-enable button
                 if (restoreBtn) {
                     restoreBtn.disabled = false;
                     restoreBtn.style.opacity = '1';
                     restoreBtn.style.cursor = 'pointer';
                 }

                 closeModal('restoreModal');
                 
                 if (ok && data && data.success === true) {
                     const message = (data && data.message) ? data.message : 'Teaching load restored successfully';
                     Swal.fire({
                         icon: 'success',
                         title: 'Success!',
                         text: message,
                         confirmButtonColor: '#8B0000',
                         confirmButtonText: 'OK'
                     }).then(() => {
                         location.reload();
                     });
                 } else {
                     let errorMessage = 'Failed to restore teaching load';
                     if (data && data.message) {
                         errorMessage = data.message;
                     } else if (status === 404) {
                         errorMessage = 'Teaching load not found. It may have already been deleted.';
                     } else if (status === 403) {
                         errorMessage = 'You do not have permission to perform this action.';
                     } else if (status === 500) {
                         errorMessage = 'Server error occurred. Please try again later.';
                     }
                     
                     Swal.fire({
                         icon: 'error',
                         title: 'Error',
                         text: errorMessage,
                         confirmButtonColor: '#8B0000',
                         confirmButtonText: 'OK'
                     });
                 }
             })
             .catch(error => {
                 console.error('Restore error:', error);
                 
                 // Re-enable button
                 if (restoreBtn) {
                     restoreBtn.disabled = false;
                     restoreBtn.style.opacity = '1';
                     restoreBtn.style.cursor = 'pointer';
                 }
                 
                 closeModal('restoreModal');
                 Swal.fire({
                     icon: 'error',
                     title: 'Error',
                     text: 'Network error occurred. Please check your connection and try again.',
                     confirmButtonColor: '#8B0000',
                     confirmButtonText: 'OK'
                 });
             });
         }

         function permanentlyDelete(archiveId) {
             fetch(`/admin/teaching-load/archived/${archiveId}`, {
                 method: 'DELETE',
                 headers: {
                     'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                     'Content-Type': 'application/json',
                 },
             })
             .then(async (response) => {
                 const contentType = response.headers.get('content-type') || '';
                 let data = null;
                 if (contentType.includes('application/json')) {
                     try { data = await response.json(); } catch (_) { data = null; }
                 }
                 return { ok: response.ok, data };
             })
             .then(({ ok, data }) => {
                 closeModal('deleteModal');
                 if (ok && data && data.success === true) {
                     const message = (data && data.message) ? data.message : 'Archived teaching load deleted successfully';
                     Swal.fire({
                         icon: 'success',
                         title: 'Success!',
                         text: message,
                         confirmButtonColor: '#8B0000',
                         confirmButtonText: 'OK'
                     }).then(() => {
                         location.reload();
                     });
                 } else {
                     Swal.fire({
                         icon: 'error',
                         title: 'Error',
                         text: (data && data.message) ? data.message : 'Failed to delete archived teaching load',
                         confirmButtonColor: '#8B0000',
                         confirmButtonText: 'OK'
                     });
                 }
             })
             .catch(error => {
                 console.error('Error:', error);
                 closeModal('deleteModal');
                 Swal.fire({
                     icon: 'error',
                     title: 'Error',
                     text: 'Error deleting archived teaching load',
                     confirmButtonColor: '#8B0000',
                     confirmButtonText: 'OK'
                 });
             });
         }
     </script>

    <!-- Restore Modal -->
    <div id="restoreModal" class="modal-overlay" style="display:none;">
        <div class="modal-box" style="width: 450px; max-width: 95vw;">
            <div class="modal-header-custom">RESTORE TEACHING LOAD</div>
            <div style="padding: 20px;">
                <div style="margin-bottom: 20px; padding: 15px; background: #d1ecf1; border: 1px solid #bee5eb; border-radius: 8px;">
                    <div style="display: flex; align-items: center; margin-bottom: 10px;">
                        <span style="font-size: 1.5rem; margin-right: 10px;">ℹ️</span>
                        <strong style="color: #0c5460;">Restore Teaching Load</strong>
                    </div>
                       <p style="margin: 0; color: #0c5460; font-size: 0.9rem;">
                           This will restore the teaching load and all its attendance records back to the current schedule. 
                           Make sure there are no time conflicts before proceeding.
                       </p>
                </div>

                <div style="margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #28a745;">
                    <h4 id="restoreCourseCode" style="margin: 0 0 5px 0; color: #28a745; font-size: 1.1rem;"></h4>
                    <p id="restoreSubject" style="margin: 0 0 5px 0; color: #666; font-size: 1rem;"></p>
                    <p id="restoreFaculty" style="margin: 0; color: #666; font-size: 0.9rem;"></p>
                </div>

                <div style="margin-top: 20px; text-align: center;">
                    <button type="button" class="modal-btn" id="confirmRestoreBtn" data-archive-id="" style="background-color: #28a745; color: white; margin-right: 10px; position: relative; padding-left: 40px; cursor: pointer;">↻ Restore</button>
                    <button type="button" class="modal-btn cancel" onclick="closeModal('restoreModal')" style="cursor: pointer;">Cancel</button>
                </div>
            </div>
        </div>
    </div>

     <!-- Delete Modal -->
     <div id="deleteModal" class="modal-overlay" style="display:none;">
         <div class="modal-box" style="width: 450px; max-width: 95vw;">
             <div class="modal-header-custom" style="background-color: #dc3545;">PERMANENTLY DELETE</div>
             <div style="padding: 20px;">
                 <div style="margin-bottom: 20px; padding: 15px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 8px;">
                     <div style="display: flex; align-items: center; margin-bottom: 10px;">
                         <span style="font-size: 1.5rem; margin-right: 10px;">⚠️</span>
                         <strong style="color: #721c24;">Warning: This action cannot be undone!</strong>
                     </div>
                        <p style="margin: 0; color: #721c24; font-size: 0.9rem;">
                            This will permanently delete the archived teaching load and all its attendance records from the system. 
                            This action cannot be reversed.
                        </p>
                 </div>

                 <div style="margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #dc3545;">
                     <h4 id="deleteCourseCode" style="margin: 0 0 5px 0; color: #dc3545; font-size: 1.1rem;"></h4>
                     <p id="deleteSubject" style="margin: 0 0 5px 0; color: #666; font-size: 1rem;"></p>
                     <p id="deleteFaculty" style="margin: 0; color: #666; font-size: 0.9rem;"></p>
                 </div>

                 <div style="margin-top: 20px; text-align: center;">
                     <button type="button" class="modal-btn" id="confirmDeleteBtn" style="background-color: #ff3636; color: white; margin-right: 10px;">&#128465; Delete Permanently</button>
                     <button type="button" class="modal-btn cancel" onclick="closeModal('deleteModal')">Cancel</button>
                 </div>
             </div>
         </div>
     </div>
 @endsection

