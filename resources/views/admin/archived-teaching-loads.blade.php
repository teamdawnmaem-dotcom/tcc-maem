@extends('layouts.appAdmin')

@section('title', 'Archived Teaching Loads - Tagoloan Community College')
@section('files-active', 'active')
@section('teaching-load-active', 'active')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/archived-teaching-loads.css') }}">
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
                        <span style="font-size: 1.5rem; margin-right: 10px;">â„¹ï¸</span>
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
                    <button type="button" class="modal-btn" id="confirmRestoreBtn" data-archive-id="" style="background-color: #28a745; color: white; margin-right: 10px; position: relative; padding-left: 40px; cursor: pointer;">â†» Restore</button>
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
                         <span style="font-size: 1.5rem; margin-right: 10px;">âš ï¸</span>
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

