@extends('layouts.appChecker')

@section('title', 'Leave Management - Tagoloan Community College')
@section('files-active', 'active')
@section('leave-active', 'active')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/checker/leave-management.css') }}">
@endsection

@section('content')
    @include('partials.flash')
    @include('partials.flashupdate')
    @include('partials.flashdelete')


    <div class="faculty-header">
        <div class="faculty-title-group">
            <div class="faculty-title">Leave Management</div>
            <div class="faculty-subtitle"></div>
        </div>
        <div class="faculty-actions-row">
            <input type="text" class="search-input" placeholder="Search...">
            <button class="add-btn" onclick="openModal('addModal')">Add</button>
        </div>
    </div>

    <div class="teaching-load-table-container">
        <div class="teaching-load-table-scroll">
            <table class="teaching-load-table">
                <thead>
                    <tr>
                        <th>Faculty Name</th>
                        <th>Department</th>
                        <th>Purpose</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Attachment</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($leaves as $leave)
                        <tr>
                            <td>{{ $leave->faculty->faculty_fname }} {{ $leave->faculty->faculty_lname }}</td>
                            <td>{{ $leave->faculty->faculty_department }}</td>
                            <td>{{ $leave->lp_purpose }}</td>
                            <td>{{ \Carbon\Carbon::parse($leave->leave_start_date)->format('F j, Y') }}</td>
                            <td>{{ \Carbon\Carbon::parse($leave->leave_end_date)->format('F j, Y') }}</td>
                            <td>
                                @if ($leave->lp_image)
                                    <button class="view-slip-btn"
                                        onclick="viewSlip('{{ asset('storage/' . $leave->lp_image) }}')">View</button>
                                @else
                                    N/A
                                @endif
                            </td>
                            <td>
                                <div class="action-btns">
                                    <button class="edit-btn" data-id="{{ $leave->lp_id }}"
                                        data-faculty="{{ $leave->faculty_id }}" data-purpose="{{ $leave->lp_purpose }}"
                                        data-start="{{ $leave->leave_start_date }}"
                                        data-end="{{ $leave->leave_end_date }}" onclick="openUpdateLeaveModal(this)">
                                        &#9998;
                                    </button>
                                    <button class="delete-btn"
                                        onclick="openDeleteModal({{ $leave->lp_id }})">&#128465;</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align:center; font-style:italic; color:#666;">
                                No Leave Records found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add Modal -->
    <div id="addModal" class="modal-overlay" style="display:none;">
        <div class="modal-box">
            <form class="modal-form" id="addForm" method="POST"
                enctype="multipart/form-data" action="{{ route('checker.leaves.store') }}">
                @csrf
                <input type="hidden" name="lp_type" value="Leave">

                <div class="modal-header"
                    style="background-color: #8B0000; color: white; padding: 14.4px 19.2px; font-size: 19.2px; font-weight: bold; width: 100%; margin: 0; display: flex; align-items: center; justify-content: center; text-align: center; letter-spacing: 0.4px; border-top-left-radius: 8px; border-top-right-radius: 8px;">
                    ADD LEAVE
                </div>

                <div class="modal-content" style="padding: 19.2px 19.2px 19.2px;">
                    
                    <div class="modal-field">
                        <label class="modal-label">Faculty</label>
                        <select class="modal-select" name="faculty_id" id="facultySelect">
                            <option value="">-- Select Faculty --</option>
                            @foreach ($faculties as $faculty)
                                <option value="{{ $faculty->faculty_id }}"
                                    data-department="{{ $faculty->faculty_department }}">
                                    {{ $faculty->faculty_fname }} {{ $faculty->faculty_lname }}
                                </option>
                            @endforeach
                        </select>
                        <div class="validation-message"></div>
                    </div>
                    <div class="modal-field">
                        <label class="modal-label">Department</label>
                        <input class="modal-input" type="text" id="facultyDepartment" readonly>
                        <div class="validation-message"></div>
                    </div>
                    <div class="modal-field">
                        <label class="modal-label">Purpose</label>
                        <input class="modal-input" type="text" name="lp_purpose" id="add_lp_purpose">
                        <div class="validation-message"></div>
                    </div>
                    <div class="modal-field">
                        <label class="modal-label">Start Date</label>
                        <input class="modal-input" type="date" name="leave_start_date" id="add_leave_start_date">
                        <div class="validation-message"></div>
                    </div>
                    <div class="modal-field">
                        <label class="modal-label">End Date</label>
                        <input class="modal-input" type="date" name="leave_end_date" id="add_leave_end_date">
                        <div class="validation-message"></div>
                    </div>
                    <div class="modal-field">
                        <label class="modal-label">Slip Image</label>
                        <input class="modal-file" type="file" name="lp_image" accept="image/*" id="add_lp_image">
                        <div class="validation-message" id="add_lp_image_error"></div>
                    </div>

                    <div class="logic-error"
                        style="display:none; color:#ff3636; text-align:center; margin:6px 0; font-weight:600;"></div>
                    @php($addDateError = $errors->first('leave_start_date') ?: $errors->first('leave_end_date'))
                    @if ($addDateError)
                        <div class="server-error" style="color:#ff3636; text-align:center; margin:6px 0; font-weight:600;">
                            {{ $addDateError }}
                        </div>
                    @endif

                    <div class="modal-buttons">
                        <button type="submit" class="modal-btn add">Add</button>
                        <button type="button" class="modal-btn cancel" onclick="closeModal('addModal')">Cancel</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal-overlay" style="display:none;">
        <div class="modal-box">
            <form class="modal-form" id="editForm" method="POST"
                enctype="multipart/form-data">
                @csrf @method('PUT')
                <input type="hidden" name="lp_type" value="Leave">

                <div class="modal-header"
                    style="background-color: #8B0000; color: white; padding: 14.4px 19.2px; font-size: 19.2px; font-weight: bold; width: 100%; margin: 0; display: flex; align-items: center; justify-content: center; text-align: center; letter-spacing: 0.4px; border-top-left-radius: 8px; border-top-right-radius: 8px;">
                    UPDATE LEAVE
                </div>

                <div class="modal-content" style="padding: 19.2px 19.2px 19.2px;">
                    

                    <div class="modal-form-group">
                        <label>Faculty</label>
                        <select name="faculty_id" id="edit_faculty_id">
                            <option value="">-- Select Faculty --</option>
                            @foreach ($faculties as $faculty)
                                <option value="{{ $faculty->faculty_id }}"
                                    data-department="{{ $faculty->faculty_department }}">
                                    {{ $faculty->faculty_fname }} {{ $faculty->faculty_lname }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="modal-form-group">
                        <label>Department</label>
                        <input type="text" id="edit_faculty_department" readonly>
                    </div>
                    <div class="modal-form-group"><label>Purpose</label><input type="text" name="lp_purpose"
                            id="edit_lp_purpose"></div>
                    <div class="modal-form-group"><label>Start Date</label><input type="date" name="leave_start_date"
                            id="edit_leave_start_date"></div>
                    <div class="modal-form-group"><label>End Date</label><input type="date" name="leave_end_date"
                            id="edit_leave_end_date"></div>
                    <div class="modal-form-group"><label>Slip Image</label><input type="file" name="lp_image"
                            accept="image/*" id="edit_lp_image">
                        <div class="validation-message" id="edit_lp_image_error"></div>
                        
                    </div>

                    <div class="logic-error"
                        style="display:none; color:#ff3636; text-align:center; margin:6px 0; font-weight:600;"></div>
                    @php($editDateError = $errors->first('leave_start_date') ?: $errors->first('leave_end_date'))
                    @if ($editDateError)
                        <div class="server-error" style="color:#ff3636; text-align:center; margin:6px 0; font-weight:600;">
                            {{ $editDateError }}
                        </div>
                    @endif

                    <div class="modal-buttons">
                        <button type="submit" class="modal-btn add">Update</button>
                        <button type="button" class="modal-btn cancel" onclick="closeModal('editModal')">Cancel</button>
                    </div>
                </div>
            </form>
        </div>
    </div>


    <!-- Delete Leave Modal -->
    <div id="deleteModal" class="modal-overlay" style="display:none;">
        <form id="deleteForm" method="POST" class="modal-box">
            @csrf
            @method('DELETE')
            <div class="modal-header delete">DELETE LEAVE</div>

            <!-- Warning Icon and Message -->
            <div style="text-align: center; margin:0 px 0;">
                <div style="font-size: 4rem; color: #ff3636; margin-bottom: 20px;">âš ï¸</div>
                <div style="font-size: 1.2rem; color: #333; margin-bottom: 10px; font-weight: bold;">Are you sure?</div>
                <div style="font-size: 1rem; color: #666; line-height: 1.5;">
                    This action cannot be undone. The leave record will be permanently deleted.
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="modal-buttons">
                <button type="submit" class="modal-btn delete">Delete</button>
                <button type="button" class="modal-btn cancel" onclick="closeModal('deleteModal')">Cancel</button>
            </div>
        </form>
    </div>


    <!-- Slip Viewer Modal -->
    <div id="slipModal" class="modal-overlay" style="display:none;">
        <div class="modal-box">
            <button class="close" onclick="closeModal('slipModal')" title="Close">
                <span>&times;</span>
            </button>
            <div class="modal-header">Leave Slip</div>
            <div class="slip-content">
                <img id="slipImage" src="" alt="Leave Slip">
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        // Faculty department binding for add
        document.addEventListener('DOMContentLoaded', function() {
            const facultySelect = document.getElementById('facultySelect');
            const facultyDepartment = document.getElementById('facultyDepartment');
            facultySelect.addEventListener('change', function() {
                const department = this.options[this.selectedIndex].getAttribute('data-department') || '';
                facultyDepartment.value = department;
            });
        });

        function openModal(id) {
            document.getElementById(id).style.display = 'flex';
            // Initialize button states
            if (id === 'addModal') {
                updateAddButtonState(false);
                validateAddLeave();
            } else if (id === 'editModal') {
                updateEditButtonState(false);
                validateEditLeave();
            }
        }

        function closeModal(id) {
            const modal = document.getElementById(id);
            if (!modal) return;
            
            // Clear form and validation for add modal
            if (id === 'addModal') {
                const form = modal.querySelector('form');
                if (form) {
                    // Reset form
                    form.reset();
                    
                    // Clear validation states
                    const inputs = form.querySelectorAll('input, select, textarea');
                    inputs.forEach(input => {
                        input.classList.remove('valid', 'invalid');
                        input.dataset.touched = 'false';
                    });
                    
                    // Clear validation messages
                    const messages = form.querySelectorAll('.validation-message');
                    messages.forEach(msg => msg.textContent = '');
                    
                    // Hide logic error
                    const logicError = form.querySelector('.logic-error');
                    if (logicError) {
                        logicError.style.display = 'none';
                        logicError.textContent = '';
                    }
                }
            }
            
            // Hide validation for edit modal (keep values)
            if (id === 'editModal') {
                const form = modal.querySelector('form');
                if (form) {
                    // Clear validation states
                    const inputs = form.querySelectorAll('input, select, textarea');
                    inputs.forEach(input => {
                        input.classList.remove('valid', 'invalid');
                        input.dataset.touched = 'false';
                    });
                    
                    // Clear validation messages
                    const messages = form.querySelectorAll('.validation-message');
                    messages.forEach(msg => msg.textContent = '');
                    
                    // Hide logic error
                    const logicError = form.querySelector('.logic-error');
                    if (logicError) {
                        logicError.style.display = 'none';
                        logicError.textContent = '';
                    }
                }
            }
            
            // Hide modal
            modal.style.display = 'none';
        }

        function viewSlip(url) {
            document.getElementById('slipImage').src = url;
            openModal('slipModal');
        }

        function openUpdateLeaveModal(button) {
            openModal('editModal');

            // Read data attributes
            const id = button.getAttribute('data-id');
            const facultyId = button.getAttribute('data-faculty');
            const purpose = button.getAttribute('data-purpose');
            const startDate = button.getAttribute('data-start');
            const endDate = button.getAttribute('data-end');

            // Fill form
            document.getElementById('edit_faculty_id').value = facultyId;

            // Auto-fill department based on faculty
            const facultySelect = document.getElementById('edit_faculty_id');
            const selectedOption = facultySelect.querySelector(`option[value="${facultyId}"]`);
            document.getElementById('edit_faculty_department').value = selectedOption ?
                selectedOption.getAttribute('data-department') :
                '';

            document.getElementById('edit_lp_purpose').value = purpose;
            document.getElementById('edit_leave_start_date').value = startDate;
            document.getElementById('edit_leave_end_date').value = endDate;

            // Set action URL
            document.getElementById('editForm').action = '/checker/leaves/' + id;
        }

        function openDeleteModal(id) {
            openModal('deleteModal');
            document.getElementById('deleteForm').action = '/checker/leaves/' + id;
        }

        // =========================
        // Button state management functions
        // =========================
        function updateAddButtonState(isValid) {
            const addButton = document.querySelector('#addModal .modal-btn.add');
            if (addButton) {
                addButton.disabled = !isValid;
                if (isValid) {
                    addButton.style.opacity = '1';
                    addButton.style.cursor = 'pointer';
                } else {
                    addButton.style.opacity = '0.6';
                    addButton.style.cursor = 'not-allowed';
                }
            }
        }

        function updateEditButtonState(isValid) {
            const editButton = document.querySelector('#editModal .modal-btn.add');
            if (editButton) {
                editButton.disabled = !isValid;
                if (isValid) {
                    editButton.style.opacity = '1';
                    editButton.style.cursor = 'pointer';
                } else {
                    editButton.style.opacity = '0.6';
                    editButton.style.cursor = 'not-allowed';
                }
            }
        }

        // =========================
        // Show-once-touched validation (Leave)
        // =========================
        function trim(v) {
            return (v || '').trim();
        }

        function isNotEmpty(v) {
            return trim(v).length > 0;
        }

        function validateImageSize(fileInput) {
            if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
                return true; // No files selected is valid
            }
            
            const maxSize = 2 * 1024 * 1024; // 2MB in bytes
            for (let i = 0; i < fileInput.files.length; i++) {
                if (fileInput.files[i].size > maxSize) {
                    return false;
                }
            }
            return true;
        }

        function setValidity(el, ok) {
            if (!el) return;
            const show = el.dataset.touched === 'true' || window.leaveSubmitAttempt === true;
            el.classList.remove('valid', 'invalid');
            if (!show) return;
            el.classList.add(ok ? 'valid' : 'invalid');
        }

        function setMessage(el, msg) {
            if (!el) return;
            const g = el.closest('.modal-field');
            if (!g) return;
            let m = g.querySelector('.validation-message');
            if (!m) {
                m = document.createElement('div');
                m.className = 'validation-message';
                g.appendChild(m);
            }
            const show = el.dataset.touched === 'true' || window.leaveSubmitAttempt === true;
            m.textContent = show ? (msg || '') : '';
        }

        // Real-time leave overlap checking functions
        async function checkLeaveOverlapAdd() {
            const facultySelect = document.querySelector('#addModal [name="faculty_id"]');
            const startDateField = document.querySelector('#addModal [name="leave_start_date"]');
            const endDateField = document.querySelector('#addModal [name="leave_end_date"]');
            const logicBox = document.querySelector('#addModal .logic-error');
            
            if (!facultySelect || !startDateField || !endDateField || 
                !facultySelect.value || !startDateField.value || !endDateField.value) {
                if (logicBox) {
                    // Only clear if the current message is about leave overlap
                    const currentMessage = logicBox.textContent;
                    if (currentMessage && currentMessage.includes('already has a leave request')) {
                        logicBox.style.display = 'none';
                        logicBox.textContent = '';
                    }
                }
                return;
            }

            try {
                const response = await fetch('/checker/leaves/check-leave-overlap', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        faculty_id: facultySelect.value,
                        start_date: startDateField.value,
                        end_date: endDateField.value
                    })
                });

                const data = await response.json();
                
                if (logicBox) {
                    if (data.has_overlap) {
                        logicBox.textContent = data.message;
                        logicBox.style.display = 'block';
                    } else {
                        // Only clear if the current message is about leave overlap
                        const currentMessage = logicBox.textContent;
                        if (currentMessage && currentMessage.includes('already has a leave request')) {
                            logicBox.style.display = 'none';
                            logicBox.textContent = '';
                        }
                    }
                }
                
                // Trigger validation after leave overlap check
                validateAddLeave();
            } catch (error) {
                console.error('Error checking leave overlap:', error);
            }
        }

        async function checkLeaveOverlapEdit() {
            const facultySelect = document.querySelector('#edit_faculty_id');
            const startDateField = document.querySelector('#edit_leave_start_date');
            const endDateField = document.querySelector('#edit_leave_end_date');
            const logicBox = document.querySelector('#editModal .logic-error');
            const editForm = document.getElementById('editForm');
            const currentId = editForm ? editForm.action.split('/').pop() : null;
            
            if (!facultySelect || !startDateField || !endDateField || 
                !facultySelect.value || !startDateField.value || !endDateField.value) {
                if (logicBox) {
                    // Only clear if the current message is about leave overlap
                    const currentMessage = logicBox.textContent;
                    if (currentMessage && currentMessage.includes('already has a leave request')) {
                        logicBox.style.display = 'none';
                        logicBox.textContent = '';
                    }
                }
                return;
            }

            try {
                const response = await fetch('/checker/leaves/check-leave-overlap', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        faculty_id: facultySelect.value,
                        start_date: startDateField.value,
                        end_date: endDateField.value,
                        exclude_id: currentId
                    })
                });

                const data = await response.json();
                
                if (logicBox) {
                    if (data.has_overlap) {
                        logicBox.textContent = data.message;
                        logicBox.style.display = 'block';
                    } else {
                        // Only clear if the current message is about leave overlap
                        const currentMessage = logicBox.textContent;
                        if (currentMessage && currentMessage.includes('already has a leave request')) {
                            logicBox.style.display = 'none';
                            logicBox.textContent = '';
                        }
                    }
                }
                
                // Trigger validation after leave overlap check
                validateEditLeave();
            } catch (error) {
                console.error('Error checking leave overlap:', error);
            }
        }

        function validateAddLeave() {
            const fac = document.getElementById('facultySelect');
            const pur = document.getElementById('add_lp_purpose');
            const sdt = document.getElementById('add_leave_start_date');
            const edt = document.getElementById('add_leave_end_date');
            const img = document.querySelector('#addModal [name="lp_image"]');
            const vFac = isNotEmpty(fac && fac.value);
            const vPur = isNotEmpty(pur && pur.value);
            const vSdt = isNotEmpty(sdt && sdt.value);
            const vEdt = isNotEmpty(edt && edt.value);
            const vImg = isNotEmpty(img && img.value) && validateImageSize(img);
            // Logic: start date <= end date; start not in past (unless purpose is Emergency or Sick Leave)
            let logicOk = true;
            const logicBox = document.querySelector('#addModal .logic-error');
            if (logicBox) logicBox.style.display = 'none';
            if (vSdt) {
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                const start = new Date(sdt.value);
                // Check if purpose allows past dates (case-insensitive)
                const purposeValue = (pur && pur.value) ? pur.value.trim().toLowerCase() : '';
                const allowsPastDate = purposeValue === 'emergency' || purposeValue === 'sick leave';
                
                if (start < today && !allowsPastDate) {
                    logicOk = false;
                    if (logicBox) {
                        logicBox.textContent = 'Start date cannot be in the past.';
                        logicBox.style.display = 'block';
                    }
                }
            }
            if (logicOk && vSdt && vEdt) {
                const start = new Date(sdt.value);
                const end = new Date(edt.value);
                if (start > end) {
                    logicOk = false;
                    if (logicBox) {
                        logicBox.textContent = 'End date must be the same or later than start date.';
                        logicBox.style.display = 'block';
                    }
                }
            }
            
            // Check for leave overlaps (synchronous check like date validation)
            if (logicOk && vFac && vSdt && vEdt) {
                const conflictMessage = logicBox ? logicBox.textContent : '';
                if (conflictMessage && conflictMessage.includes('already has a leave request')) {
                    logicOk = false;
                    if (logicBox) {
                        logicBox.textContent = conflictMessage;
                        logicBox.style.display = 'block';
                    }
                } else {
                    // Clear overlap messages if no longer valid
                    if (logicBox && logicBox.textContent.includes('already has a leave request')) {
                        logicBox.style.display = 'none';
                        logicBox.textContent = '';
                    }
                }
            }
            
            setValidity(fac, vFac);
            setMessage(fac, vFac ? '' : 'Faculty is required');
            setValidity(pur, vPur);
            setMessage(pur, vPur ? '' : 'Purpose is required');
            setValidity(sdt, vSdt);
            setMessage(sdt, vSdt ? '' : 'Start date is required');
            setValidity(edt, vEdt);
            setMessage(edt, vEdt ? '' : 'End date is required');
            setValidity(img, vImg);
            setMessage(img, vImg ? '' : (isNotEmpty(img && img.value) ? 'Image size must be less than 2MB' : 'Slip image is required'));
            
            const isValid = vFac && vPur && vSdt && vEdt && vImg && logicOk;
            updateAddButtonState(isValid);
            return isValid;
        }

        function validateEditLeave() {
            const fac = document.getElementById('edit_faculty_id');
            const pur = document.getElementById('edit_lp_purpose');
            const sdt = document.getElementById('edit_leave_start_date');
            const edt = document.getElementById('edit_leave_end_date');
            const img = document.getElementById('edit_lp_image');
            const vFac = isNotEmpty(fac && fac.value);
            const vPur = isNotEmpty(pur && pur.value);
            const vSdt = isNotEmpty(sdt && sdt.value);
            const vEdt = isNotEmpty(edt && edt.value);
            const vImg = !img || !img.files || img.files.length === 0 || validateImageSize(img);
            // Logic: start date <= end date; start not in past (unless purpose is Emergency or Sick Leave)
            let logicOk = true;
            const logicBox = document.querySelector('#editModal .logic-error');
            if (logicBox) logicBox.style.display = 'none';
            if (vSdt) {
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                const start = new Date(sdt.value);
                // Check if purpose allows past dates (case-insensitive)
                const purposeValue = (pur && pur.value) ? pur.value.trim().toLowerCase() : '';
                const allowsPastDate = purposeValue === 'emergency' || purposeValue === 'sick leave';
                
                if (start < today && !allowsPastDate) {
                    logicOk = false;
                    if (logicBox) {
                        logicBox.textContent = 'Start date cannot be in the past.';
                        logicBox.style.display = 'block';
                    }
                }
            }
            if (logicOk && vSdt && vEdt) {
                const start = new Date(sdt.value);
                const end = new Date(edt.value);
                if (start > end) {
                    logicOk = false;
                    if (logicBox) {
                        logicBox.textContent = 'End date must be the same or later than start date.';
                        logicBox.style.display = 'block';
                    }
                }
            }
            
            // Check for leave overlaps (synchronous check like date validation)
            if (logicOk && vFac && vSdt && vEdt) {
                const conflictMessage = logicBox ? logicBox.textContent : '';
                if (conflictMessage && conflictMessage.includes('already has a leave request')) {
                    logicOk = false;
                    if (logicBox) {
                        logicBox.textContent = conflictMessage;
                        logicBox.style.display = 'block';
                    }
                } else {
                    // Clear overlap messages if no longer valid
                    if (logicBox && logicBox.textContent.includes('already has a leave request')) {
                        logicBox.style.display = 'none';
                        logicBox.textContent = '';
                    }
                }
            }
            
            setValidity(fac, vFac);
            setMessage(fac, vFac ? '' : 'Faculty is required');
            setValidity(pur, vPur);
            setMessage(pur, vPur ? '' : 'Purpose is required');
            setValidity(sdt, vSdt);
            setMessage(sdt, vSdt ? '' : 'Start date is required');
            setValidity(edt, vEdt);
            setMessage(edt, vEdt ? '' : 'End date is required');
            setValidity(img, vImg);
            setMessage(img, vImg ? '' : 'Image size must be less than 2MB');
            
            const isValid = vFac && vPur && vSdt && vEdt && vImg && logicOk;
            updateEditButtonState(isValid);
            return isValid;
        }

        ['#facultySelect', '#add_lp_purpose', '#add_leave_start_date', '#add_leave_end_date', '#addModal [name="lp_image"]'].forEach(sel => {
            const el = document.querySelector(sel);
            if (!el) return;
            const evt = el.tagName === 'SELECT' ? 'change' : 'input';
            el.addEventListener(evt, function() {
                validateAddLeave();
                // Also check for leave overlaps when faculty or dates change
                if (sel.includes('facultySelect') || sel.includes('leave_start_date') || sel.includes('leave_end_date')) {
                    checkLeaveOverlapAdd();
                }
            });
            el.addEventListener('blur', () => {
                el.dataset.touched = 'true';
                validateAddLeave();
            });
        });
        ['#edit_faculty_id', '#edit_lp_purpose', '#edit_leave_start_date', '#edit_leave_end_date', '#edit_lp_image'].forEach(sel => {
            const el = document.querySelector(sel);
            if (!el) return;
            const evt = el.tagName === 'SELECT' ? 'change' : 'input';
            el.addEventListener(evt, function() {
                validateEditLeave();
                // Also check for leave overlaps when faculty or dates change
                if (sel.includes('faculty_id') || sel.includes('leave_start_date') || sel.includes('leave_end_date')) {
                    checkLeaveOverlapEdit();
                }
            });
            el.addEventListener('blur', () => {
                el.dataset.touched = 'true';
                validateEditLeave();
            });
        });

        (function() {
            const addForm = document.getElementById('addForm');
            if (addForm) {
                addForm.addEventListener('submit', function(e) {
                    window.leaveSubmitAttempt = true;
                    if (!validateAddLeave()) {
                        e.preventDefault();
                    }
                });
            }
            const editForm = document.getElementById('editForm');
            if (editForm) {
                editForm.addEventListener('submit', function(e) {
                    window.leaveSubmitAttempt = true;
                    if (!validateEditLeave()) {
                        e.preventDefault();
                    }
                });
            }
        })();

        // =========================
        // Responsive Table Search with "No results found"
        // =========================
        document.querySelector('.search-input').addEventListener('input', function() {
            let searchTerm = this.value.toLowerCase();
            let rows = document.querySelectorAll('.teaching-load-table tbody tr');
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
                        `<td colspan="7" style="text-align:center; padding:20px; color:#999; font-style:italic;">No results found</td>`;
                    tbody.appendChild(noResultsRow);
                }
            } else {
                if (noResultsRow) noResultsRow.remove();
            }
        });
    </script>
@endsection
