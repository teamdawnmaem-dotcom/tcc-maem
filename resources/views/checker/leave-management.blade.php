@extends('layouts.appChecker')

@section('title', 'Leave Management - Tagoloan Community College')
@section('files-active', 'active')
@section('leave-active', 'active')

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

        .search-input {
            padding: 8px;
            font-size: 14px;
            border: 1px solid #ccc;
            border-radius: 4px;
            width: 400px;
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
        }

        .teaching-load-table {
            width: 100%;
            border-collapse: collapse;
        }

        .teaching-load-table th {
            background: #8B0000;
            color: #fff;
            padding: 16px 0;
            font-size: 1.1rem;
            font-weight: bold;
            border: none;
        }

        /* Keep table header visible while scrolling */
        .teaching-load-table thead th {
            position: sticky;
            top: 0;
            z-index: 2;
        }

        .teaching-load-table td {
            padding: 12px 0;
            text-align: center;
            font-size: 1rem;
            border: none;
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

        /* Make only the table area scroll vertically */
        .teaching-load-table-scroll {
            max-height: 670px;
            overflow-y: auto;
            width: 100%;
        }

        .action-btns {
            display: flex;
            gap: 8px;
            justify-content: center;
            align-items: center;
        }

        .edit-btn,
        .delete-btn {
            width: 60px;
            height: 32px;
            border-radius: 6px;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            font-weight: bold;
            cursor: pointer;
        }

        .edit-btn {
            background: #7cc6fa;
            color: #fff;
        }

        .delete-btn {
            background: #ff3636;
            color: #fff;
        }

        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.3);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 2000;
        }

        .modal-box {
            background: #fff;
            border-radius: 10px;
            width: 100%;
            max-width: 450px;
            padding: 40px 40px 30px 40px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.22), 0 1.5px 8px rgba(0, 0, 0, 0.12);
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .modal-header {
            font-size: 2rem;
            font-weight: bold;
            color: #8B0000;
            text-align: center;
            margin-bottom: 28px;
        }

        .modal-form {
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: stretch;
        }

        .modal-form-group {
            display: flex;
            flex-direction: row;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
        }

        .modal-form-group label {
            min-width: 130px;
            text-align: left;
            margin-bottom: 0;
            font-size: 1rem;
            color: #222;
        }

        .modal-form-group input,
        .modal-form-group textarea,
        .modal-form-group select {
            flex: 1;
            width: 100%;
            padding: 10px 12px;
            font-size: 1rem;
            border: 1px solid #bbb;
            border-radius: 5px;
        }

        .modal-form-group textarea {
            resize: vertical;
        }

        .modal-btn {
            width: 100%;
            padding: 14px 0;
            font-size: 1.1rem;
            font-weight: bold;
            border: none;
            border-radius: 6px;
            margin-top: 0;
            cursor: pointer;
        }

        .modal-buttons {
            display: flex;
            gap: 12px;
            justify-content: center;
            margin-top: 18px;
        }

        .modal-btn.cancel {
            background: #fff !important;
            color: #800000 !important;
            border: 2px solid #800000 !important;
            border-radius: 8px;
            padding: 10px 20px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .modal-btn.cancel:hover {
            background: #800000 !important;
            color: #fff !important;
        }

        .modal-btn.delete {
            background: #fff !important;
            color: #ff0000 !important;
            border: 2px solid #ff0000 !important;
            border-radius: 8px;
            padding: 10px 20px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .modal-btn.delete:hover {
            background: #ff0000 !important;
            color: #fff !important;
        }

        .view-slip-btn {
            padding: 8px 16px;
            font-size: 0.9rem;
            border: none;
            border-radius: 5px;
            background-color: #8B0000;
            color: #fff;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .view-slip-btn:hover {
            background-color: #6d0000;
        }

        #slipModal .modal-box {
            max-width: 900px !important;
            width: 95%;
            height: auto;
            padding: 0;
            position: relative;
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
        }

        #slipImage {
            max-width: 100%;
            max-height: 75vh;
            border-radius: 8px;
            object-fit: contain;
            display: block;
        }

        /* Close button styles for slip modal */
        #slipModal .close {
            position: absolute;
            top: 15px;
            right: 20px;
            z-index: 1000;
            width: 40px;
            height: 40px;
            background: rgba(0, 0, 0, 0.7);
            color: #fff;
            border: none;
            border-radius: 50%;
            font-size: 24px;
            font-weight: bold;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }

        #slipModal .close:hover {
            background: rgba(139, 0, 0, 0.9);
            transform: scale(1.1);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.4);
        }

        #slipModal .close:active {
            transform: scale(0.95);
        }

        /* Slip modal header */
        #slipModal .modal-header {
            background: transparent;
            color: #8B0000;
            padding: 15px 20px;
            margin: 0;
            font-size: 1.5rem;
            font-weight: bold;
            text-align: center;
        }

        /* Slip modal content */
        #slipModal .slip-content {
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
        }
    </style>
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
        <div class="modal-box" style="padding: 0; overflow: hidden; border-radius: 8px;">
            <form class="modal-form" style="width:100%; padding: 0;" id="addForm" method="POST"
                enctype="multipart/form-data" action="{{ route('checker.leaves.store') }}">
                @csrf
                <input type="hidden" name="lp_type" value="Leave">

                <div class="modal-header"
                    style="background-color: #8B0000; color: white; padding: 18px 24px; font-size: 24px; font-weight: bold; width: 100%; margin: 0; display: flex; align-items: center; justify-content: center; text-align: center; letter-spacing: 0.5px; border-top-left-radius: 8px; border-top-right-radius: 8px;">
                    ADD LEAVE
                </div>

                <div class="modal-form" style="padding: 24px 24px 24px;">
                    <style>
                        #addModal .modal-form-group {
                            display: flex;
                            align-items: center;
                            gap: 6px;
                            margin-bottom: 4px;
                            padding-bottom: 6px;
                            position: relative;
                        }

                        #addModal .modal-form-group label {
                            min-width: 130px;
                            margin-bottom: 0;
                            font-size: 1rem;
                            text-align: left;
                        }

                        #addModal .modal-form-group input,
                        #addModal .modal-form-group select,
                        #addModal .modal-form-group textarea {
                            flex: 1;
                            width: 100%;
                            padding: 10px 12px;
                            font-size: 1rem;
                            border: 1px solid #bbb;
                            border-radius: 5px;
                        }

                        #addModal .validation-message {
                            font-size: 0.8rem;
                            left: 130px;
                            right: 10px;
                            bottom: -10px;
                            padding-left: 10px;
                            line-height: 1.1;
                            position: absolute;
                            color: #ff3636;
                            pointer-events: none;
                        }

                        #addModal .modal-buttons {
                            display: flex;
                            gap: 12px;
                            justify-content: center;
                            margin-top: 12px;
                        }

                        #addModal .modal-btn {
                            flex: 1;
                        }

                        #addModal .modal-btn.add {
                            background: transparent !important;
                            border: 2px solid #28a745 !important;
                            color: #28a745 !important;
                        }

                        #addModal .modal-btn.add:hover {
                            background: #28a745 !important;
                            color: #fff !important;
                            border-color: #28a745 !important;
                        }
                    </style>

                    <div class="modal-form-group">
                        <label>Faculty</label>
                        <select name="faculty_id" id="facultySelect">
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
                        <input type="text" id="facultyDepartment" readonly>
                    </div>
                    <div class="modal-form-group"><label>Purpose</label><input type="text" name="lp_purpose"
                            id="add_lp_purpose"></div>
                    <div class="modal-form-group"><label>Start Date</label><input type="date" name="leave_start_date"
                            id="add_leave_start_date"></div>
                    <div class="modal-form-group"><label>End Date</label><input type="date" name="leave_end_date"
                            id="add_leave_end_date"></div>
                    <div class="modal-form-group"><label>Slip Image</label><input type="file" name="lp_image"
                            accept="image/*" id="add_lp_image">
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
        <div class="modal-box" style="padding: 0; overflow: hidden; border-radius: 8px;">
            <form class="modal-form" style="width:100%; padding: 0;" id="editForm" method="POST"
                enctype="multipart/form-data">
                @csrf @method('PUT')
                <input type="hidden" name="lp_type" value="Leave">

                <div class="modal-header"
                    style="background-color: #8B0000; color: white; padding: 18px 24px; font-size: 24px; font-weight: bold; width: 100%; margin: 0; display: flex; align-items: center; justify-content: center; text-align: center; letter-spacing: 0.5px; border-top-left-radius: 8px; border-top-right-radius: 8px;">
                    UPDATE LEAVE
                </div>

                <div class="modal-form" style="padding: 24px 24px 24px;">
                    <style>
                        #editModal .modal-form-group {
                            display: flex;
                            align-items: center;
                            gap: 6px;
                            margin-bottom: 4px;
                            padding-bottom: 6px;
                            position: relative;
                        }

                        #editModal .modal-form-group label {
                            min-width: 130px;
                            margin-bottom: 0;
                            font-size: 1rem;
                            text-align: left;
                        }

                        #editModal .modal-form-group input,
                        #editModal .modal-form-group select,
                        #editModal .modal-form-group textarea {
                            flex: 1;
                            width: 100%;
                            padding: 10px 12px;
                            font-size: 1rem;
                            border: 1px solid #bbb;
                            border-radius: 5px;
                        }

                        #editModal .validation-message {
                            font-size: 0.8rem;
                            left: 130px;
                            right: 10px;
                            bottom: -10px;
                            padding-left: 10px;
                            line-height: 1.1;
                            position: absolute;
                            color: #ff3636;
                            pointer-events: none;
                        }

                        #editModal .modal-buttons {
                            display: flex;
                            gap: 12px;
                            justify-content: center;
                            margin-top: 12px;
                        }

                        #editModal .modal-btn.add {
                            background: transparent !important;
                            border: 2px solid #7cc6fa !important;
                            color: #7cc6fa !important;
                        }

                        #editModal .modal-btn.add:hover {
                            background: #7cc6fa !important;
                            color: #fff !important;
                            border-color: #7cc6fa !important;
                        }
                    </style>

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
                <div style="font-size: 4rem; color: #ff3636; margin-bottom: 20px;">⚠️</div>
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
            const g = el.closest('.modal-form-group');
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
            // Logic: start date <= end date; start not in past
            let logicOk = true;
            const logicBox = document.querySelector('#addModal .logic-error');
            if (logicBox) logicBox.style.display = 'none';
            if (vSdt) {
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                const start = new Date(sdt.value);
                if (start < today) {
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
            // Logic: start date <= end date; start not in past
            let logicOk = true;
            const logicBox = document.querySelector('#editModal .logic-error');
            if (logicBox) logicBox.style.display = 'none';
            if (vSdt) {
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                const start = new Date(sdt.value);
                if (start < today) {
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
