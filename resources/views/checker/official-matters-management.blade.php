@extends('layouts.appChecker')

@section('title', 'Official Matters - Tagoloan Community College')
@section('files-active', 'active')
@section('official-matters-active', 'active')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/checker/official-matters-management.css') }}">
@endsection

@section('content')
    @include('partials.flash')
    @include('partials.flashupdate')
    @include('partials.flashdelete')

    <div class="faculty-header">
        <div class="faculty-title-group">
            <div class="faculty-title">Official Matters</div>
            <div class="faculty-subtitle"></div>
        </div>
        <div class="faculty-actions-row">
            <input type="text" class="search-input" placeholder="Search...">
            <button class="add-btn" onclick="openModal('addModal')">Add</button>
        </div>
    </div>

    <!-- Desktop Table View -->
    <div class="teaching-load-table-container">
        <div class="teaching-load-table-scroll">
            <table class="teaching-load-table">
                <thead>
                    <tr>
                        <th>Faculty/Department</th>
                        <th>Department</th>
                        <th>Purpose</th>
                        <th>Remarks</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Attachment</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($officialMatters as $matter)
                        <tr>
                            <td>
                                @if ($matter->faculty)
                                    {{ $matter->faculty->faculty_fname }} {{ $matter->faculty->faculty_lname }}
                                @else
                                    {{ $matter->om_department }}
                                @endif
                            </td>
                            <td>
                                @if ($matter->faculty)
                                    {{ $matter->faculty->faculty_department }}
                                @else
                                    {{ $matter->om_department }}
                                @endif
                            </td>
                            <td>{{ $matter->om_purpose }}</td>
                            <td>{{ $matter->om_remarks }}</td>
                            <td>{{ \Carbon\Carbon::parse($matter->om_start_date)->format('F j, Y') }}</td>
                            <td>{{ \Carbon\Carbon::parse($matter->om_end_date)->format('F j, Y') }}</td>
                            <td>
                                @if ($matter->om_attachment)
                                    <button class="view-slip-btn"
                                        onclick="viewSlip('{{ asset('storage/' . $matter->om_attachment) }}')">View</button>
                                @else
                                    N/A
                                @endif
                            </td>
                            <td>
                                <div class="action-btns">
                                    <button class="edit-btn" data-id="{{ $matter->om_id }}"
                                        data-faculty="{{ $matter->faculty_id }}"
                                        data-department="{{ $matter->om_department }}"
                                        data-purpose="{{ $matter->om_purpose }}"
                                        data-remarks="{{ $matter->om_remarks }}"
                                        data-start="{{ $matter->om_start_date }}"
                                        data-end="{{ $matter->om_end_date }}"
                                        onclick="openUpdateModal(this)">
                                        &#9998;
                                    </button>
                                    <button class="delete-btn"
                                        onclick="openDeleteModal({{ $matter->om_id }})">&#128465;</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="text-align:center; font-style:italic; color:#666;">
                                No Official Matters Records found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Mobile Card View -->
    <div class="mobile-card-container">
        @forelse ($officialMatters as $matter)
            @php
                $facultyName = $matter->faculty ? $matter->faculty->faculty_fname . ' ' . $matter->faculty->faculty_lname : $matter->om_department;
                $department = $matter->faculty ? $matter->faculty->faculty_department : $matter->om_department;
                $purpose = $matter->om_purpose;
                $remarks = $matter->om_remarks;
                $startDate = \Carbon\Carbon::parse($matter->om_start_date)->format('F j, Y');
                $endDate = \Carbon\Carbon::parse($matter->om_end_date)->format('F j, Y');
                $attachmentUrl = $matter->om_attachment ? asset('storage/' . $matter->om_attachment) : null;
            @endphp
            <div class="mobile-card">
                <div class="mobile-card-header">
                    <div class="mobile-card-title">{{ $facultyName }}</div>
                    <div class="mobile-card-department">{{ $department }}</div>
                </div>
                <div class="mobile-card-body">
                    <div class="mobile-card-row">
                        <div class="mobile-card-label">Purpose</div>
                        <div class="mobile-card-value">{{ $purpose }}</div>
                    </div>
                    <div class="mobile-card-row">
                        <div class="mobile-card-label">Remarks</div>
                        <div class="mobile-card-value">{{ $remarks }}</div>
                    </div>
                    <div class="mobile-card-row">
                        <div class="mobile-card-label">Start Date</div>
                        <div class="mobile-card-value">{{ $startDate }}</div>
                    </div>
                    <div class="mobile-card-row">
                        <div class="mobile-card-label">End Date</div>
                        <div class="mobile-card-value">{{ $endDate }}</div>
                    </div>
                    @if ($attachmentUrl)
                        <div class="mobile-card-row">
                            <div class="mobile-card-label">Attachment</div>
                            <div class="mobile-card-value">
                                <button class="view-slip-btn" onclick="viewSlip('{{ $attachmentUrl }}')">View Attachment</button>
                            </div>
                        </div>
                    @else
                        <div class="mobile-card-row">
                            <div class="mobile-card-label">Attachment</div>
                            <div class="mobile-card-value" style="color: #999;">N/A</div>
                        </div>
                    @endif
                </div>
                <div class="mobile-card-actions">
                    <button class="edit-btn" data-id="{{ $matter->om_id }}"
                        data-faculty="{{ $matter->faculty_id }}"
                        data-department="{{ $matter->om_department }}"
                        data-purpose="{{ $matter->om_purpose }}"
                        data-remarks="{{ $matter->om_remarks }}"
                        data-start="{{ $matter->om_start_date }}"
                        data-end="{{ $matter->om_end_date }}"
                        onclick="openUpdateModal(this)">
                        Edit
                    </button>
                    <button class="delete-btn" onclick="openDeleteModal({{ $matter->om_id }})">Delete</button>
                </div>
            </div>
        @empty
            <div class="mobile-card" style="text-align: center; padding: 40px;">
                <p style="font-style: italic; color: #666; margin: 0;">No Official Matters Records found.</p>
            </div>
        @endforelse
    </div>

    <!-- Add Modal -->
    <div id="addModal" class="modal-overlay" style="display:none;">
        <div class="modal-box">
            <form class="modal-form" id="addForm" method="POST"
                enctype="multipart/form-data" action="{{ route('checker.official.matters.store') }}">
                @csrf
                <input type="hidden" name="is_department_mode" id="add_is_department_mode" value="0">

                <div class="modal-header"
                    style="background-color: #8B0000; color: white; padding: 14.4px 19.2px; font-size: 19.2px; font-weight: bold; width: 100%; margin: 0; display: flex; align-items: center; justify-content: center; text-align: center; letter-spacing: 0.4px; border-top-left-radius: 8px; border-top-right-radius: 8px;">
                    ADD OFFICIAL MATTER
                </div>

                <div class="modal-content" style="padding: 19.2px 19.2px 19.2px;">
                    <!-- Toggle Switch -->
                    <div class="toggle-switch">
                        <label>Select by Department</label>
                        <label class="switch">
                            <input type="checkbox" id="add_toggle_switch" onchange="toggleMode('add')">
                            <span class="slider"></span>
                        </label>
                    </div>

                    <!-- Faculty Mode -->
                    <div class="faculty-mode">
                        <div class="modal-form-group">
                            <label>Faculty</label>
                            <select name="faculty_id" id="add_faculty_id">
                                <option value="">-- Select Faculty --</option>
                                @foreach ($faculties as $faculty)
                                    <option value="{{ $faculty->faculty_id }}"
                                        data-department="{{ $faculty->faculty_department }}">
                                        {{ $faculty->faculty_fname }} {{ $faculty->faculty_lname }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="validation-message" id="add_faculty_id_error"></div>
                        </div>
                        <div class="modal-form-group">
                            <label>Department</label>
                            <input type="text" id="add_faculty_department" readonly>
                        </div>
                    </div>

                    <!-- Department Mode -->
                    <div class="department-mode">
                        <div class="modal-form-group">
                            <label>Department</label>
                            <select name="om_department" id="add_om_department">
                                <option value="">-- Select Department --</option>
                                <option value="All Instructor">All Instructor</option>
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
                            <div class="validation-message" id="add_om_department_error"></div>
                        </div>
                    </div>

                    <div class="modal-form-group">
                        <label>Purpose</label>
                        <input type="text" name="om_purpose" id="add_om_purpose">
                        <div class="validation-message" id="add_om_purpose_error"></div>
                    </div>

                    <div class="modal-form-group">
                        <label>Remarks</label>
                        <input type="text" name="om_remarks" id="add_om_remarks">
                        <div class="validation-message" id="add_om_remarks_error"></div>
                    </div>

                    <div class="modal-form-group">
                        <label>Start Date</label>
                        <input type="date" name="om_start_date" id="add_om_start_date">
                        <div class="validation-message" id="add_om_start_date_error"></div>
                    </div>

                    <div class="modal-form-group">
                        <label>End Date</label>
                        <input type="date" name="om_end_date" id="add_om_end_date">
                        <div class="validation-message" id="add_om_end_date_error"></div>
                    </div>

                    <div class="modal-form-group">
                        <label>Attachment</label>
                        <input type="file" name="om_attachment" accept="image/*" id="add_om_attachment">
                        <div class="validation-message" id="add_om_attachment_error"></div>
                    </div>

                    <div class="logic-error"></div>
                    @if ($errors->any())
                        <div class="server-error">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    <div class="modal-buttons">
                        <button type="submit" class="modal-btn add"
                            style="background: transparent !important; border: 2px solid #28a745 !important; color: #28a745 !important;">
                            Add
                        </button>
                        <button type="button" class="modal-btn cancel" onclick="closeModal('addModal')">Cancel</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal-overlay" style="display:none;">
        <div class="modal-box">
            <form class="modal-form" id="editForm" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <input type="hidden" name="is_department_mode" id="edit_is_department_mode" value="0">

                <div class="modal-header"
                    style="background-color: #8B0000; color: white; padding: 14.4px 19.2px; font-size: 19.2px; font-weight: bold; width: 100%; margin: 0; display: flex; align-items: center; justify-content: center; text-align: center; letter-spacing: 0.4px; border-top-left-radius: 8px; border-top-right-radius: 8px;">
                    UPDATE OFFICIAL MATTER
                </div>

                <div class="modal-content" style="padding: 19.2px 19.2px 19.2px;">
                    <!-- Toggle Switch -->
                    <div class="toggle-switch">
                        <label>Select by Department</label>
                        <label class="switch">
                            <input type="checkbox" id="edit_toggle_switch" onchange="toggleMode('edit')">
                            <span class="slider"></span>
                        </label>
                    </div>

                    <!-- Faculty Mode -->
                    <div class="faculty-mode">
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
                            <div class="validation-message" id="edit_faculty_id_error"></div>
                        </div>
                        <div class="modal-form-group">
                            <label>Department</label>
                            <input type="text" id="edit_faculty_department" readonly>
                        </div>
                    </div>

                    <!-- Department Mode -->
                    <div class="department-mode">
                        <div class="modal-form-group">
                            <label>Department</label>
                            <select name="om_department" id="edit_om_department">
                                <option value="">-- Select Department --</option>
                                <option value="All Instructor">All Instructor</option>
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
                            <div class="validation-message" id="edit_om_department_error"></div>
                        </div>
                    </div>

                    <div class="modal-form-group">
                        <label>Purpose</label>
                        <input type="text" name="om_purpose" id="edit_om_purpose">
                        <div class="validation-message" id="edit_om_purpose_error"></div>
                    </div>

                    <div class="modal-form-group">
                        <label>Remarks</label>
                        <input type="text" name="om_remarks" id="edit_om_remarks">
                        <div class="validation-message" id="edit_om_remarks_error"></div>
                    </div>

                    <div class="modal-form-group">
                        <label>Start Date</label>
                        <input type="date" name="om_start_date" id="edit_om_start_date">
                        <div class="validation-message" id="edit_om_start_date_error"></div>
                    </div>

                    <div class="modal-form-group">
                        <label>End Date</label>
                        <input type="date" name="om_end_date" id="edit_om_end_date">
                        <div class="validation-message" id="edit_om_end_date_error"></div>
                    </div>

                    <div class="modal-form-group">
                        <label>Attachment</label>
                        <input type="file" name="om_attachment" accept="image/*" id="edit_om_attachment">
                        <div class="validation-message" id="edit_om_attachment_error"></div>
                    </div>

                    <div class="logic-error"></div>
                    @if ($errors->any())
                        <div class="server-error">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    <div class="modal-buttons">
                        <button type="submit" class="modal-btn add"
                            style="background: transparent !important; border: 2px solid #7cc6fa !important; color: #7cc6fa !important;">
                            Update
                        </button>
                        <button type="button" class="modal-btn cancel" onclick="closeModal('editModal')">Cancel</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Modal -->
    <div id="deleteModal" class="modal-overlay" style="display:none;">
        <form id="deleteForm" method="POST" class="modal-box">
            @csrf
            @method('DELETE')
            <div class="modal-header delete">DELETE OFFICIAL MATTER</div>
            <div style="text-align: center; margin: 0 px 0;">
                <div style="font-size: 4rem; color: #ff3636; margin-bottom: 20px;">âš ï¸</div>
                <div style="font-size: 1.2rem; color: #333; margin-bottom: 10px; font-weight: bold;">Are you sure?</div>
                <div style="font-size: 1rem; color: #666; line-height: 1.5;">
                    This action cannot be undone. The official matter record will be permanently deleted.
                </div>
            </div>
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
            <div class="modal-header">Official Matter Attachment</div>
            <div class="slip-content">
                <img id="slipImage" src="" alt="Official Matter Attachment">
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        // =========================
        // Helper functions (defined first)
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
            const show = el.dataset.touched === 'true' || window.officialMatterSubmitAttempt === true;
            el.classList.remove('valid', 'invalid');
            if (!show) return;
            el.classList.add(ok ? 'valid' : 'invalid');
        }

        function setMessage(el, msg) {
            if (!el) return;
            const g = el.closest('.modal-form-group');
            if (!g) return;
            let m = g.querySelector('.validation-message');
            if (!m) {
                m = document.createElement('div');
                m.className = 'validation-message';
                g.appendChild(m);
            }
            const show = el.dataset.touched === 'true' || window.officialMatterSubmitAttempt === true;
            m.textContent = show ? (msg || '') : '';
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
        // Validation functions
        // =========================
        function validateAddOfficialMatter() {
            const toggle = document.getElementById('add_toggle_switch');
            const isDepartmentMode = toggle && toggle.checked;
            
            const fac = document.getElementById('add_faculty_id');
            const dept = document.getElementById('add_om_department');
            const pur = document.getElementById('add_om_purpose');
            const rem = document.getElementById('add_om_remarks');
            const sdt = document.getElementById('add_om_start_date');
            const edt = document.getElementById('add_om_end_date');
            const img = document.getElementById('add_om_attachment');
            
            const vFac = !isDepartmentMode ? isNotEmpty(fac && fac.value) : true;
            const vDept = isDepartmentMode ? isNotEmpty(dept && dept.value) : true;
            const vPur = isNotEmpty(pur && pur.value);
            const vRem = isNotEmpty(rem && rem.value);
            const vSdt = isNotEmpty(sdt && sdt.value);
            const vEdt = isNotEmpty(edt && edt.value);
            const vImg = isNotEmpty(img && img.value) && validateImageSize(img);
            
            // Logic: start date <= end date
            let logicOk = true;
            const logicBox = document.querySelector('#addModal .logic-error');
            if (logicBox) logicBox.style.display = 'none';
            
            if (vSdt && vEdt) {
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
            
            setValidity(fac, vFac);
            setMessage(fac, vFac ? '' : 'Faculty is required');
            setValidity(dept, vDept);
            setMessage(dept, vDept ? '' : 'Department is required');
            setValidity(pur, vPur);
            setMessage(pur, vPur ? '' : 'Purpose is required');
            setValidity(rem, vRem);
            setMessage(rem, vRem ? '' : 'Remarks is required');
            setValidity(sdt, vSdt);
            setMessage(sdt, vSdt ? '' : 'Start date is required');
            setValidity(edt, vEdt);
            setMessage(edt, vEdt ? '' : 'End date is required');
            setValidity(img, vImg);
            setMessage(img, vImg ? '' : (isNotEmpty(img && img.value) ? 'Image size must be less than 2MB' : 'Attachment is required'));
            
            const isValid = vFac && vDept && vPur && vRem && vSdt && vEdt && vImg && logicOk;
            updateAddButtonState(isValid);
            return isValid;
        }

        function validateEditOfficialMatter() {
            const toggle = document.getElementById('edit_toggle_switch');
            const isDepartmentMode = toggle && toggle.checked;
            
            const fac = document.getElementById('edit_faculty_id');
            const dept = document.getElementById('edit_om_department');
            const pur = document.getElementById('edit_om_purpose');
            const rem = document.getElementById('edit_om_remarks');
            const sdt = document.getElementById('edit_om_start_date');
            const edt = document.getElementById('edit_om_end_date');
            const img = document.getElementById('edit_om_attachment');
            
            const vFac = !isDepartmentMode ? isNotEmpty(fac && fac.value) : true;
            const vDept = isDepartmentMode ? isNotEmpty(dept && dept.value) : true;
            const vPur = isNotEmpty(pur && pur.value);
            const vRem = isNotEmpty(rem && rem.value);
            const vSdt = isNotEmpty(sdt && sdt.value);
            const vEdt = isNotEmpty(edt && edt.value);
            const vImg = !img || !img.files || img.files.length === 0 || validateImageSize(img);
            
            // Logic: start date <= end date
            let logicOk = true;
            const logicBox = document.querySelector('#editModal .logic-error');
            if (logicBox) logicBox.style.display = 'none';
            
            if (vSdt && vEdt) {
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
            
            setValidity(fac, vFac);
            setMessage(fac, vFac ? '' : 'Faculty is required');
            setValidity(dept, vDept);
            setMessage(dept, vDept ? '' : 'Department is required');
            setValidity(pur, vPur);
            setMessage(pur, vPur ? '' : 'Purpose is required');
            setValidity(rem, vRem);
            setMessage(rem, vRem ? '' : 'Remarks is required');
            setValidity(sdt, vSdt);
            setMessage(sdt, vSdt ? '' : 'Start date is required');
            setValidity(edt, vEdt);
            setMessage(edt, vEdt ? '' : 'End date is required');
            setValidity(img, vImg);
            setMessage(img, vImg ? '' : 'Image size must be less than 2MB');
            
            const isValid = vFac && vDept && vPur && vRem && vSdt && vEdt && vImg && logicOk;
            updateEditButtonState(isValid);
            return isValid;
        }

        function toggleMode(mode) {
            const prefix = mode === 'add' ? 'add' : 'edit';
            const toggle = document.getElementById(`${prefix}_toggle_switch`);
            const isDepartmentMode = toggle.checked;
            const modal = document.getElementById(`${prefix}Modal`);
            const hiddenInput = document.getElementById(`${prefix}_is_department_mode`);

            hiddenInput.value = isDepartmentMode ? '1' : '0';

            if (isDepartmentMode) {
                modal.classList.add('toggle-on');
            } else {
                modal.classList.remove('toggle-on');
            }

            // Trigger validation after toggle change
            if (mode === 'add') {
                validateAddOfficialMatter();
            } else {
                validateEditOfficialMatter();
            }
        }

        function openModal(id) {
            document.getElementById(id).style.display = 'flex';
            // Initialize button states
            if (id === 'addModal') {
                // Reset toggle
                document.getElementById('add_toggle_switch').checked = false;
                toggleMode('add');
                updateAddButtonState(false);
                validateAddOfficialMatter();
            } else if (id === 'editModal') {
                updateEditButtonState(false);
                validateEditOfficialMatter();
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
                modal.classList.remove('toggle-on');
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

            modal.style.display = 'none';
        }

        function viewSlip(url) {
            document.getElementById('slipImage').src = url;
            openModal('slipModal');
        }

        function openUpdateModal(button) {
            openModal('editModal');

            const id = button.getAttribute('data-id');
            const facultyId = button.getAttribute('data-faculty');
            const department = button.getAttribute('data-department');
            const purpose = button.getAttribute('data-purpose');
            const remarks = button.getAttribute('data-remarks');
            const startDate = button.getAttribute('data-start');
            const endDate = button.getAttribute('data-end');

            // Set toggle based on whether department is set
            const isDepartmentMode = department && !facultyId;
            document.getElementById('edit_toggle_switch').checked = isDepartmentMode;
            toggleMode('edit');

            if (isDepartmentMode) {
                document.getElementById('edit_om_department').value = department;
            } else {
                document.getElementById('edit_faculty_id').value = facultyId;
                const facultySelect = document.getElementById('edit_faculty_id');
                const selectedOption = facultySelect.querySelector(`option[value="${facultyId}"]`);
                if (selectedOption) {
                    document.getElementById('edit_faculty_department').value = 
                        selectedOption.getAttribute('data-department') || '';
                }
            }

            document.getElementById('edit_om_purpose').value = purpose;
            document.getElementById('edit_om_remarks').value = remarks;
            document.getElementById('edit_om_start_date').value = startDate;
            document.getElementById('edit_om_end_date').value = endDate;

            document.getElementById('editForm').action = '/checker/official-matters/' + id;
        }

        function openDeleteModal(id) {
            openModal('deleteModal');
            document.getElementById('deleteForm').action = '/checker/official-matters/' + id;
        }


        // Faculty department binding for add
        document.addEventListener('DOMContentLoaded', function() {
            const facultySelect = document.getElementById('add_faculty_id');
            const facultyDepartment = document.getElementById('add_faculty_department');
            if (facultySelect && facultyDepartment) {
                facultySelect.addEventListener('change', function() {
                    const department = this.options[this.selectedIndex].getAttribute('data-department') || '';
                    facultyDepartment.value = department;
                });
            }

            // Faculty department binding for edit
            const editFacultySelect = document.getElementById('edit_faculty_id');
            const editFacultyDepartment = document.getElementById('edit_faculty_department');
            if (editFacultySelect && editFacultyDepartment) {
                editFacultySelect.addEventListener('change', function() {
                    const department = this.options[this.selectedIndex].getAttribute('data-department') || '';
                    editFacultyDepartment.value = department;
                });
            }

            // Add event listeners for validation
            ['#add_faculty_id', '#add_om_department', '#add_om_purpose', '#add_om_remarks', '#add_om_start_date', '#add_om_end_date', '#add_om_attachment'].forEach(sel => {
                const el = document.querySelector(sel);
                if (!el) return;
                const evt = el.tagName === 'SELECT' ? 'change' : 'input';
                el.addEventListener(evt, function() {
                    validateAddOfficialMatter();
                });
                el.addEventListener('blur', () => {
                    el.dataset.touched = 'true';
                    validateAddOfficialMatter();
                });
            });

            // Toggle switch change listener
            const addToggle = document.getElementById('add_toggle_switch');
            if (addToggle) {
                addToggle.addEventListener('change', function() {
                    validateAddOfficialMatter();
                });
            }

            ['#edit_faculty_id', '#edit_om_department', '#edit_om_purpose', '#edit_om_remarks', '#edit_om_start_date', '#edit_om_end_date', '#edit_om_attachment'].forEach(sel => {
                const el = document.querySelector(sel);
                if (!el) return;
                const evt = el.tagName === 'SELECT' ? 'change' : 'input';
                el.addEventListener(evt, function() {
                    validateEditOfficialMatter();
                });
                el.addEventListener('blur', () => {
                    el.dataset.touched = 'true';
                    validateEditOfficialMatter();
                });
            });

            // Toggle switch change listener for edit
            const editToggle = document.getElementById('edit_toggle_switch');
            if (editToggle) {
                editToggle.addEventListener('change', function() {
                    validateEditOfficialMatter();
                });
            }

            // Form submission validation
            (function() {
                const addForm = document.getElementById('addForm');
                if (addForm) {
                    addForm.addEventListener('submit', function(e) {
                        window.officialMatterSubmitAttempt = true;
                        if (!validateAddOfficialMatter()) {
                            e.preventDefault();
                        }
                    });
                }
                const editForm = document.getElementById('editForm');
                if (editForm) {
                    editForm.addEventListener('submit', function(e) {
                        window.officialMatterSubmitAttempt = true;
                        if (!validateEditOfficialMatter()) {
                            e.preventDefault();
                        }
                    });
                }
            })();

            // Search functionality - works with both table and mobile cards
            const searchInput = document.querySelector('.search-input');
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    let searchTerm = this.value.toLowerCase();
                    
                    // Search in table rows (desktop)
                    let rows = document.querySelectorAll('.teaching-load-table tbody tr');
                    let anyTableVisible = false;

                    rows.forEach(row => {
                        if (row.classList.contains('no-results')) return;
                        let text = row.textContent.toLowerCase();
                        if (text.includes(searchTerm)) {
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
                        if (!anyTableVisible && searchTerm) {
                            if (!noResultsRow) {
                                noResultsRow = document.createElement('tr');
                                noResultsRow.classList.add('no-results');
                                noResultsRow.innerHTML =
                                    `<td colspan="8" style="text-align:center; padding:20px; color:#999; font-style:italic;">No results found</td>`;
                                tbody.appendChild(noResultsRow);
                            }
                        } else {
                            if (noResultsRow) noResultsRow.remove();
                        }
                    }
                    
                    // Search in mobile cards
                    let cards = document.querySelectorAll('.mobile-card');
                    let anyCardVisible = false;
                    const mobileContainer = document.querySelector('.mobile-card-container');
                    
                    cards.forEach(card => {
                        if (card.classList.contains('no-results-mobile')) return;
                        let text = card.textContent.toLowerCase();
                        if (text.includes(searchTerm)) {
                            card.style.display = '';
                            anyCardVisible = true;
                        } else {
                            card.style.display = 'none';
                        }
                    });
                    
                    // Handle "no results" message for mobile
                    if (mobileContainer) {
                        let noResultsCard = mobileContainer.querySelector('.no-results-mobile');
                        if (!anyCardVisible && searchTerm) {
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
                });
            }
        });
    </script>
@endsection

