@extends('layouts.appdeptHead')

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
            padding: 8px;
            font-size: 14px;
            border: 1px solid #ccc;
            border-radius: 4px;
            width: 400px;
        }

        .back-btn {
            padding: 8px 24px;
            font-size: 14px;
            border: none;
            border-radius: 4px;
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
            border-radius: 10px;
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
            padding: 16px 8px;
            font-size: 1.1rem;
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
            font-size: 1rem;
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
            max-height: 670px;
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
            width: 40px;
            height: 32px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
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
            border-left: 4px solid #8B0000;
            padding: 10px 15px;
            margin-bottom: 20px;
            border-radius: 0 8px 8px 0;
        }

        .archive-info h4 {
            margin: 0 0 5px 0;
            color: #8B0000;
            font-size: 1.1rem;
        }

        .archive-info p {
            margin: 0;
            color: #666;
            font-size: 0.9rem;
        }

        /* ====== Filter Styles ====== */
        .filter-section {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .filter-group label {
            font-size: 0.9rem;
            font-weight: bold;
            color: #333;
        }

         .filter-group select {
             padding: 8px 12px;
             border: 1px solid #ccc;
             border-radius: 4px;
             font-size: 0.9rem;
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
             z-index: 1000;
         }

         .modal-box {
             background: #fff;
             border-radius: 10px;
             width: 450px;
             max-width: 95vw;
             padding: 0;
             box-shadow: 0 8px 32px rgba(0, 0, 0, 0.22), 0 1.5px 8px rgba(0, 0, 0, 0.12);
             display: flex;
             flex-direction: column;
             align-items: center;
             overflow: hidden;
         }

         .modal-header-custom {
             background-color: #8B0000;
             color: #fff;
             font-weight: bold;
             text-align: center;
             border-top-left-radius: 12px;
             border-top-right-radius: 12px;
             padding: 15px 20px;
             font-size: 1.5rem;
             letter-spacing: 1px;
             width: 100%;
             margin-bottom: 0;
         }

         .modal-btn {
             width: auto;
             padding: 12px 24px;
             font-size: 1rem;
             font-weight: bold;
             border-radius: 10px;
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
            <div class="faculty-subtitle">View and manage archived teaching loads from previous school years</div>
        </div>
        <div class="faculty-actions-row">
            <input type="text" class="search-input" placeholder="Search archived loads...">
            <a href="{{ route('deptHead.teaching.load.management') }}" class="back-btn">Back to Current</a>
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

         // Close modals when clicking outside
         document.addEventListener('click', function(e) {
             if (e.target.classList && e.target.classList.contains('modal-overlay')) {
                 const overlayId = e.target.id;
                 e.target.style.display = 'none';
             }
         });

         // Modal functions
         function openModal(id) {
             document.getElementById(id).style.display = 'flex';
         }

         function closeModal(id) {
             document.getElementById(id).style.display = 'none';
         }

         function openRestoreModal(archiveId, courseCode, subject, faculty) {
             document.getElementById('restoreCourseCode').textContent = courseCode;
             document.getElementById('restoreSubject').textContent = subject;
             document.getElementById('restoreFaculty').textContent = faculty;
             document.getElementById('confirmRestoreBtn').onclick = function() {
                 restoreTeachingLoad(archiveId);
             };
             openModal('restoreModal');
         }

         function openDeleteModal(archiveId, courseCode, subject, faculty) {
             document.getElementById('deleteCourseCode').textContent = courseCode;
             document.getElementById('deleteSubject').textContent = subject;
             document.getElementById('deleteFaculty').textContent = faculty;
             document.getElementById('confirmDeleteBtn').onclick = function() {
                 permanentlyDelete(archiveId);
             };
             openModal('deleteModal');
         }

         function restoreTeachingLoad(archiveId) {
             fetch(`/deptHead/teaching-load/restore/${archiveId}`, {
                 method: 'POST',
                 headers: {
                     'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                     'Content-Type': 'application/json',
                 },
             })
             .then(response => response.json())
             .then(data => {
                 closeModal('restoreModal');
                 if (data.success) {
                     Swal.fire({
                         icon: 'success',
                         title: 'Success!',
                         text: data.message,
                         confirmButtonColor: '#8B0000',
                         confirmButtonText: 'OK'
                     }).then(() => {
                         location.reload();
                     });
                 } else {
                     Swal.fire({
                         icon: 'error',
                         title: 'Error',
                         text: data.message || 'Failed to restore teaching load',
                         confirmButtonColor: '#8B0000',
                         confirmButtonText: 'OK'
                     });
                 }
             })
             .catch(error => {
                 console.error('Error:', error);
                 closeModal('restoreModal');
                 Swal.fire({
                     icon: 'error',
                     title: 'Error',
                     text: 'Error restoring teaching load',
                     confirmButtonColor: '#8B0000',
                     confirmButtonText: 'OK'
                 });
             });
         }

         function permanentlyDelete(archiveId) {
             fetch(`/deptHead/teaching-load/archived/${archiveId}`, {
                 method: 'DELETE',
                 headers: {
                     'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                     'Content-Type': 'application/json',
                 },
             })
             .then(response => response.json())
             .then(data => {
                 closeModal('deleteModal');
                 if (data.success) {
                     Swal.fire({
                         icon: 'success',
                         title: 'Success!',
                         text: data.message,
                         confirmButtonColor: '#8B0000',
                         confirmButtonText: 'OK'
                     }).then(() => {
                         location.reload();
                     });
                 } else {
                     Swal.fire({
                         icon: 'error',
                         title: 'Error',
                         text: data.message || 'Failed to delete archived teaching load',
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
                     <button type="button" class="modal-btn" id="confirmRestoreBtn" style="background-color: #28a745; color: white; margin-right: 10px; position: relative; padding-left: 40px;">↻ Restore</button>
                     <button type="button" class="modal-btn cancel" onclick="closeModal('restoreModal')">Cancel</button>
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

