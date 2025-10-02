@extends('layouts.appChecker')

@section('title', 'Pass Management - Tagoloan Community College')
@section('files-active', 'active')
@section('pass-active', 'active')

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
            position: relative;
            padding-bottom: 18px;
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

        .modal-form-group input.valid,
        .modal-form-group select.valid,
        .modal-form-group textarea.valid {
            border-color: #2ecc71;
            box-shadow: 0 0 0 2px rgba(46, 204, 113, 0.1);
        }

        .modal-form-group input.invalid,
        .modal-form-group select.invalid,
        .modal-form-group textarea.invalid {
            border-color: #ff3636;
            box-shadow: 0 0 0 2px rgba(255, 54, 54, 0.1);
        }

        .validation-message {
            position: absolute;
            left: 130px;
            right: 12px;
            bottom: 0;
            font-size: 0.85rem;
            color: #ff3636;
            pointer-events: none;
            padding-left: 12px;
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

        #deleteModal .modal-btn {
            flex: 1;
            width: 50%;
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

        /* Slip Modal */
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
            <div class="faculty-title">Pass Management</div>
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
                        <th>Date</th>
                        <th>Itinerary</th>
                        <th>Departure Time</th>
                        <th>Arrival Time</th>
                        <th>Attachment</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($passes as $pass)
                        <tr>

                            <td>{{ $pass->faculty->faculty_fname }} {{ $pass->faculty->faculty_lname }}</td>
                            <td>{{ $pass->faculty->faculty_department }}</td>
                            <td>{{ $pass->lp_purpose }}</td>
                            <td>{{ \Carbon\Carbon::parse($pass->pass_slip_date)->format('F j, Y') }}</td>
                            <td>{{ $pass->pass_slip_itinerary }}</td>
                            <td>{{ \Carbon\Carbon::createFromFormat('H:i:s', $pass->pass_slip_departure_time)->format('g:i a') }}</td>
                            <td>{{ \Carbon\Carbon::createFromFormat('H:i:s', $pass->pass_slip_arrival_time)->format('g:i a') }}</td>
                            <td>
                                @if ($pass->lp_image)
                                    <button class="view-slip-btn"
                                        onclick="viewSlip('{{ asset('storage/' . $pass->lp_image) }}')">View</button>
                                @else
                                    N/A
                                @endif
                            </td>
                            <td>
                                <div class="action-btns">
                                    <button class="edit-btn"
                                        onclick="openUpdateModal(
                                {{ $pass->lp_id }},
                                {{ $pass->faculty_id }},
                                '{{ addslashes($pass->lp_purpose) }}',
                                '{{ addslashes($pass->pass_slip_itinerary) }}',
                                '{{ $pass->pass_slip_date }}',
                                '{{ $pass->pass_slip_departure_time }}',
                                '{{ $pass->pass_slip_arrival_time }}')">
                                        &#9998;
                                    </button>
                                    <button class="delete-btn"
                                        onclick="openDeleteModal({{ $pass->lp_id }})">&#128465;</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" style="text-align:center; font-style:italic; color:#666;">
                                No Pass Slip Records found.
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
            <form style="padding: 0;" action="{{ route('checker.passes.store') }}" method="POST"
                enctype="multipart/form-data">
                @csrf
                <div class="modal-header"
                    style="
                background-color: #8B0000; color: white; padding: 18px 24px; font-size: 24px; font-weight: bold; width: 100%; margin: 0; display: flex; align-items: center; justify-content: center; text-align: center; letter-spacing: 0.5px; border-top-left-radius: 8px; border-top-right-radius: 8px;">
                    ADD PASS
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
                        #addModal .modal-form-group select {
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
                            width: 50%;
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
                        @error('faculty_id')
                            <div class="validation-message">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="modal-form-group">
                        <label>Department</label>
                        <input type="text" id="facultyDepartment" name="faculty_department" value="" readonly>
                    </div>

                    <div class="modal-form-group"><label>Purpose</label><input type="text" name="lp_purpose">
                        @error('lp_purpose')
                            <div class="validation-message">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="modal-form-group"><label>Itinerary</label><input type="text" name="pass_slip_itinerary">
                    </div>
                    <div class="modal-form-group"v><label>Date</label><input type="date" name="pass_slip_date"></div>
                    <div class="modal-form-group"><label>Departure</label><input type="time"
                            name="pass_slip_departure_time">
                        @error('pass_slip_departure_time')
                            <div class="validation-message">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="modal-form-group"v><label>Arrival</label><input type="time"
                            name="pass_slip_arrival_time">
                        @error('pass_slip_arrival_time')
                            <div class="validation-message">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="modal-form-group"><label>Slip Image</label><input type="file" name="lp_image"
                            accept="image/*" id="add_lp_image">
                        <div class="validation-message" id="add_lp_image_error"></div>
                        @error('lp_image')
                            <div class="validation-message">{{ $message }}</div>
                        @enderror
                    </div>
                    @if ($errors->has('pass_slip_date'))
                        <div class="server-error pass-date-error"
                            style="color:#ff3636; text-align:center; margin-top:6px; margin-bottom:6px; font-weight:600;">
                            {{ $errors->first('pass_slip_date') }}
                        </div>
                    @endif
                    <div class="logic-error"
                        style="display:none; color:#ff3636; text-align:center; margin-top:6px; margin-bottom:6px; font-weight:600;">
                    </div>
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
            <form style="padding: 0;" id="editForm" method="POST" enctype="multipart/form-data">
                @csrf @method('PUT')
                <div class="modal-header"
                    style="
                background-color: #8B0000; color: white; padding: 18px 24px; font-size: 24px; font-weight: bold; width: 100%; margin: 0; display: flex; align-items: center; justify-content: center; text-align: center; letter-spacing: 0.5px; border-top-left-radius: 8px; border-top-right-radius: 8px;">
                    UPDATE PASS
                </div>
                <div class="modal-form" style="width:100%; padding: 24px 24px 24px;">
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
                        #editModal .modal-form-group select {
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

                        #editModal .modal-btn {
                            flex: 1;
                            width: 50%;
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
                        <input type="text" id="edit_faculty_department" name="faculty_department" value=""
                            readonly>
                    </div>

                    <div class="modal-form-group"><label>Purpose</label><input type="text" name="lp_purpose"
                            id="edit_lp_purpose"></div>
                    <div class="modal-form-group"><label>Itinerary</label><input type="text"
                            name="pass_slip_itinerary" id="edit_pass_slip_itinerary"></div>
                    <div class="modal-form-group"><label>Date</label><input type="date" name="pass_slip_date"
                            id="edit_pass_slip_date"></div>
                    <div class="modal-form-group"><label>Departure</label><input type="time"
                            name="pass_slip_departure_time" id="edit_pass_slip_departure_time"></div>
                    <div class="modal-form-group"><label>Arrival</label><input type="time"
                            name="pass_slip_arrival_time" id="edit_pass_slip_arrival_time"></div>
                    <div class="modal-form-group"><label>Slip Image</label><input type="file" name="lp_image"
                            accept="image/*" id="edit_lp_image">
                        <div class="validation-message" id="edit_lp_image_error"></div>
                    </div>
                    <div class="logic-error"
                        style="display:none; color:#ff3636; text-align:center; margin:6px 0; font-weight:600;"></div>
                    @if (session('open_modal') === 'editModal' && $errors->has('pass_slip_date'))
                        <div class="server-error pass-date-error"
                            style="color:#ff3636; text-align:center; margin-top:6px; margin-bottom:6px; font-weight:600;">
                            {{ $errors->first('pass_slip_date') }}
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

    <!-- Delete Modal -->
    <div id="deleteModal" class="modal-overlay" style="display:none;">
        <div class="modal-box">
            <div class="modal-header delete">DELETE PASS</div>

            <div style="text-align: center; margin: 0px 0;">
                <div style="font-size: 4rem; color: #ff3636; margin-bottom: 20px;">⚠️</div>
                <div style="font-size: 1.2rem; color: #333; margin-bottom: 10px; font-weight: bold;">Are you sure?</div>
                <div style="font-size: 1rem; color: #666; line-height: 1.5;">
                    This action cannot be undone. The pass record will be permanently deleted.
                </div>
            </div>

            <form id="deleteForm" method="POST">
                @csrf
                @method('DELETE')
                <div class="modal-buttons">
                    <button type="submit" class="modal-btn delete"
                        style="background: #ff3636; color: #fff;">Delete</button>
                    <button type="button" class="modal-btn cancel" style="background: #6c757d; color: #fff;"
                        onclick="closeModal('deleteModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Slip Viewer Modal -->
    <div id="slipModal" class="modal-overlay" style="display:none;">
        <div class="modal-box">
            <button class="close" onclick="closeModal('slipModal')" title="Close">
                <span>&times;</span>
            </button>
            <div class="modal-header">Pass Slip</div>
            <div class="slip-content">
                <img id="slipImage" src="" alt="Pass Slip">
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const facultySelect = document.getElementById('facultySelect');
            const facultyDepartment = document.getElementById('facultyDepartment');

            facultySelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                const department = selectedOption.getAttribute('data-department') || '';
                facultyDepartment.value = department;
            });
        });

        // Re-open Add modal and show server-side validation errors (e.g., instructor on leave)
        document.addEventListener('DOMContentLoaded', function() {
            @if (session('open_modal') === 'addModal' || $errors->any())
                openModal('addModal');
                const sel = document.getElementById('facultySelect');
                const dept = document.getElementById('facultyDepartment');
                if (sel && dept) {
                    const opt = sel.options[sel.selectedIndex];
                    dept.value = opt ? (opt.getAttribute('data-department') || '') : '';
                }
            @endif
        });

        function openModal(id) {
            document.getElementById(id).style.display = 'flex';
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
            
            // Remove server error shown at the bottom
            modal.querySelectorAll('.server-error').forEach(function(node) {
                node.remove();
            });
            // Hide modal
            modal.style.display = 'none';
        }

        function viewSlip(url) {
            document.getElementById('slipImage').src = url;
            openModal('slipModal');
        }

        // Auto-fill Department on Edit modal
        document.addEventListener('DOMContentLoaded', function() {
            const editFacultySelect = document.getElementById('edit_faculty_id');
            const editFacultyDepartment = document.getElementById('edit_faculty_department');

            // When user changes faculty dropdown
            editFacultySelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                const department = selectedOption.getAttribute('data-department') || '';
                editFacultyDepartment.value = department;
            });
        });

        // Pre-fill when opening Update modal
        function openUpdateModal(lp_id, faculty_id, purpose, itinerary, date, departure, arrival, department) {
            openModal('editModal');
            document.getElementById('edit_faculty_id').value = faculty_id;

            // Fill department for the pre-selected faculty
            const facultySelect = document.getElementById('edit_faculty_id');
            const selectedOption = facultySelect.querySelector(`option[value="${faculty_id}"]`);
            document.getElementById('edit_faculty_department').value = selectedOption ? selectedOption.getAttribute(
                'data-department') : '';

            document.getElementById('edit_lp_purpose').value = purpose;
            document.getElementById('edit_pass_slip_itinerary').value = itinerary;
            document.getElementById('edit_pass_slip_date').value = date;
            
            // Convert readable time back to 24-hour format for form inputs
            document.getElementById('edit_pass_slip_departure_time').value = convertTo24Hour(departure);
            document.getElementById('edit_pass_slip_arrival_time').value = convertTo24Hour(arrival);
            
            document.getElementById('editForm').action = '/checker/passes/' + lp_id;
        }

        // Convert readable time format (e.g., "10:30am") to 24-hour format (e.g., "10:30:00")
        function convertTo24Hour(timeStr) {
            if (!timeStr) return '';
            
            // Remove any extra spaces and convert to lowercase
            timeStr = timeStr.trim().toLowerCase();
            
            // Check if it's already in 24-hour format (contains :)
            if (timeStr.includes(':') && !timeStr.includes('am') && !timeStr.includes('pm')) {
                return timeStr.includes(':') && timeStr.split(':').length === 2 ? timeStr + ':00' : timeStr;
            }
            
            // Parse 12-hour format
            const match = timeStr.match(/(\d{1,2}):(\d{2})\s*(am|pm)/);
            if (!match) return timeStr;
            
            let hours = parseInt(match[1]);
            const minutes = match[2];
            const period = match[3];
            
            // Convert to 24-hour format
            if (period === 'am') {
                if (hours === 12) hours = 0;
            } else { // pm
                if (hours !== 12) hours += 12;
            }
            
            // Format with leading zeros
            return `${hours.toString().padStart(2, '0')}:${minutes}:00`;
        }


        function openDeleteModal(id) {
            openModal('deleteModal');
            document.getElementById('deleteForm').action = '/checker/passes/' + id;
        }


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
                        `<td colspan="9" style="text-align:center; padding:20px; color:#999; font-style:italic;">No results found</td>`;
                    tbody.appendChild(noResultsRow);
                }
            } else {
                if (noResultsRow) noResultsRow.remove();
            }
        });

        // =========================
        // Client-side Validation (Pass forms)
        // =========================
        (function() {
            function trim(v) {
                return (v || '').trim();
            }

            function isNotEmpty(v) {
                return trim(v).length > 0;
            }

            function minLen(v, n) {
                return trim(v).length >= n;
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
                const show = el.dataset.touched === 'true' || window.passSubmitAttempt === true;
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
                const show = el.dataset.touched === 'true' || window.passSubmitAttempt === true;
                m.textContent = show ? (msg || '') : '';
            }

            // Function to check for overlapping pass slip requests
            function checkPassOverlap(facultyId, date, departureTime, arrivalTime, excludeId = null) {
                const rows = document.querySelectorAll('.teaching-load-table tbody tr');
                
                for (let row of rows) {
                    // Skip empty rows and no-results rows
                    if (row.classList.contains('no-results') || row.cells.length < 9) continue;
                    
                    const cells = row.cells;
                    const facultyName = cells[0].textContent.trim();
                    const rowDate = cells[3].textContent.trim();
                    const rowDeparture = cells[5].textContent.trim();
                    const rowArrival = cells[6].textContent.trim();
                    
                    // Skip if not the same faculty or same date
                    if (facultyName !== getFacultyNameById(facultyId) || rowDate !== date) continue;
                    
                    // Skip if this is the record being edited
                    if (excludeId) {
                        const editBtn = row.querySelector('.edit-btn');
                        if (editBtn && editBtn.getAttribute('onclick').includes(excludeId)) continue;
                    }
                    
                    // Check for exact duplicate (same date and times)
                    if (departureTime === rowDeparture && arrivalTime === rowArrival) {
                        return `This faculty already has a pass slip with the exact same time (${rowDeparture} to ${rowArrival}) on ${date}.`;
                    }
                    
                    // Check for time overlap
                    if (timesOverlap(departureTime, arrivalTime, rowDeparture, rowArrival)) {
                        return `This faculty already has a pass slip from ${rowDeparture} to ${rowArrival} on ${date}.`;
                    }
                }
                
                return null;
            }

            // Function to check if two time ranges overlap
            function timesOverlap(start1, end1, start2, end2) {
                // Convert time strings to minutes for easier comparison
                const timeToMinutes = (timeStr) => {
                    const [hours, minutes] = timeStr.split(':').map(Number);
                    return hours * 60 + minutes;
                };
                
                const start1Min = timeToMinutes(start1);
                const end1Min = timeToMinutes(end1);
                const start2Min = timeToMinutes(start2);
                const end2Min = timeToMinutes(end2);
                
                // Two time ranges overlap if one starts before the other ends
                return start1Min < end2Min && start2Min < end1Min;
            }

            // Function to get faculty name by ID
            function getFacultyNameById(facultyId) {
                const facultySelect = document.querySelector('#addModal [name="faculty_id"], #edit_faculty_id');
                const option = facultySelect.querySelector(`option[value="${facultyId}"]`);
                return option ? option.textContent.trim() : '';
            }

            // Function to check for conflicts with leave requests on the same date
            function checkLeaveConflict(facultyId, date) {
                // This would need to check the leave management table, but since we're on the pass management page,
                // we'll just return null for now. In a real implementation, you might want to make an AJAX call
                // to check for leave conflicts or include leave data in the pass management page.
                return null;
            }

            function validateAdd() {
                const fac = document.querySelector('#addModal [name="faculty_id"]');
                const pur = document.querySelector('#addModal [name="lp_purpose"]');
                const itin = document.querySelector('#addModal [name="pass_slip_itinerary"]');
                const date = document.querySelector('#addModal [name="pass_slip_date"]');
                const dep = document.querySelector('#addModal [name="pass_slip_departure_time"]');
                const arr = document.querySelector('#addModal [name="pass_slip_arrival_time"]');
                const img = document.querySelector('#addModal [name="lp_image"]');
                const vFac = isNotEmpty(fac && fac.value);
                const vPur = isNotEmpty(pur && pur.value);
                const vItin = isNotEmpty(itin && itin.value);
                const vDate = isNotEmpty(date && date.value);
                const vDep = isNotEmpty(dep && dep.value);
                const vArr = isNotEmpty(arr && arr.value);
                const vImg = isNotEmpty(img && img.value) && validateImageSize(img);
                // Logical date/time validation: date cannot be in the past, and departure < arrival
                let logicOk = true;
                const logicBox = document.querySelector('#addModal .logic-error');
                if (logicBox) logicBox.style.display = 'none';
                if (vDate) {
                    const today = new Date();
                    today.setHours(0, 0, 0, 0);
                    const theDate = new Date(date.value);
                    if (theDate < today) {
                        logicOk = false;
                        if (logicBox) {
                            logicBox.textContent = 'Date cannot be in the past.';
                            logicBox.style.display = 'block';
                        }
                    }
                }
                if (logicOk && vDep && vArr) {
                    // Compare times (same day context)
                    const depTime = dep.value;
                    const arrTime = arr.value;
                    if (depTime && arrTime && depTime >= arrTime) {
                        logicOk = false;
                        if (logicBox) {
                            logicBox.textContent = 'Arrival time must be later than departure time.';
                            logicBox.style.display = 'block';
                        }
                    }
                }
                
                // Check for overlapping requests
                if (logicOk && vFac && vDate && vDep && vArr) {
                    const overlapError = checkPassOverlap(fac.value, date.value, dep.value, arr.value);
                    if (overlapError) {
                        logicOk = false;
                        if (logicBox) {
                            logicBox.textContent = overlapError;
                            logicBox.style.display = 'block';
                        }
                    }
                    
                    // Check for conflicts with leave requests on the same date
                    if (logicOk) {
                        const leaveConflict = checkLeaveConflict(fac.value, date.value);
                        if (leaveConflict) {
                            logicOk = false;
                            if (logicBox) {
                                logicBox.textContent = leaveConflict;
                                logicBox.style.display = 'block';
                            }
                        }
                    }
                }
                
                setValidity(fac, vFac);
                setMessage(fac, vFac ? '' : 'Faculty is required');
                setValidity(pur, vPur);
                setMessage(pur, vPur ? '' : 'Purpose is required');
                setValidity(itin, vItin);
                setMessage(itin, vItin ? '' : 'Itinerary is required');
                setValidity(date, vDate);
                setMessage(date, vDate ? '' : 'Date is required');
                setValidity(dep, vDep);
                setMessage(dep, vDep ? '' : 'Departure time is required');
                setValidity(arr, vArr);
                setMessage(arr, vArr ? '' : 'Arrival time is required');
                setValidity(img, vImg);
                setMessage(img, vImg ? '' : (isNotEmpty(img && img.value) ? 'Image size must be less than 2MB' : 'Slip image is required'));
                return vFac && vPur && vItin && vDate && vDep && vArr && vImg && logicOk;
            }

            function validateEdit() {
                const fac = document.getElementById('edit_faculty_id');
                const pur = document.getElementById('edit_lp_purpose');
                const itin = document.getElementById('edit_pass_slip_itinerary');
                const date = document.getElementById('edit_pass_slip_date');
                const dep = document.getElementById('edit_pass_slip_departure_time');
                const arr = document.getElementById('edit_pass_slip_arrival_time');
                const vFac = isNotEmpty(fac && fac.value);
                const vPur = isNotEmpty(pur && pur.value);
                const vItin = isNotEmpty(itin && itin.value);
                const vDate = isNotEmpty(date && date.value);
                const vDep = isNotEmpty(dep && dep.value);
                const vArr = isNotEmpty(arr && arr.value);
                // Logical date/time validation
                let logicOk = true;
                const logicBox = document.querySelector('#editModal .logic-error');
                if (logicBox) logicBox.style.display = 'none';
                if (vDate) {
                    const today = new Date();
                    today.setHours(0, 0, 0, 0);
                    const theDate = new Date(date.value);
                    if (theDate < today) {
                        logicOk = false;
                        if (logicBox) {
                            logicBox.textContent = 'Date cannot be in the past.';
                            logicBox.style.display = 'block';
                        }
                    }
                }
                if (logicOk && vDep && vArr) {
                    if (dep.value && arr.value && dep.value >= arr.value) {
                        logicOk = false;
                        if (logicBox) {
                            logicBox.textContent = 'Arrival time must be later than departure time.';
                            logicBox.style.display = 'block';
                        }
                    }
                }
                
                // Check for overlapping requests (excluding current record being edited)
                if (logicOk && vFac && vDate && vDep && vArr) {
                    const editForm = document.getElementById('editForm');
                    const currentId = editForm.action.split('/').pop();
                    const overlapError = checkPassOverlap(fac.value, date.value, dep.value, arr.value, currentId);
                    if (overlapError) {
                        logicOk = false;
                        if (logicBox) {
                            logicBox.textContent = overlapError;
                            logicBox.style.display = 'block';
                        }
                    }
                    
                    // Check for conflicts with leave requests on the same date
                    if (logicOk) {
                        const leaveConflict = checkLeaveConflict(fac.value, date.value);
                        if (leaveConflict) {
                            logicOk = false;
                            if (logicBox) {
                                logicBox.textContent = leaveConflict;
                                logicBox.style.display = 'block';
                            }
                        }
                    }
                }
                
                setValidity(fac, vFac);
                setMessage(fac, vFac ? '' : 'Faculty is required');
                setValidity(pur, vPur);
                setMessage(pur, vPur ? '' : 'Purpose is required');
                setValidity(itin, vItin);
                setMessage(itin, vItin ? '' : 'Itinerary is required');
                setValidity(date, vDate);
                setMessage(date, vDate ? '' : 'Date is required');
                setValidity(dep, vDep);
                setMessage(dep, vDep ? '' : 'Departure time is required');
                setValidity(arr, vArr);
                setMessage(arr, vArr ? '' : 'Arrival time is required');
                return vFac && vPur && vItin && vDate && vDep && vArr && logicOk;
            }

            ['#addModal [name="faculty_id"]', '#addModal [name="lp_purpose"]', '#addModal [name="pass_slip_itinerary"]',
                '#addModal [name="pass_slip_date"]', '#addModal [name="pass_slip_departure_time"]',
                '#addModal [name="pass_slip_arrival_time"]', '#addModal [name="lp_image"]'
            ].forEach(sel => {
                const el = document.querySelector(sel);
                if (!el) return;
                const evt = el.tagName === 'SELECT' ? 'change' : 'input';
                el.addEventListener(evt, validateAdd);
                el.addEventListener('blur', () => {
                    el.dataset.touched = 'true';
                    validateAdd();
                });
            });
            ['#edit_faculty_id', '#edit_lp_purpose', '#edit_pass_slip_itinerary', '#edit_pass_slip_date',
                '#edit_pass_slip_departure_time', '#edit_pass_slip_arrival_time'
            ].forEach(sel => {
                const el = document.querySelector(sel);
                if (!el) return;
                const evt = el.tagName === 'SELECT' ? 'change' : 'input';
                el.addEventListener(evt, validateEdit);
                el.addEventListener('blur', () => {
                    el.dataset.touched = 'true';
                    validateEdit();
                });
            });

            (function() {
                const addForm = document.querySelector('#addModal form');
                if (addForm) {
                    addForm.addEventListener('submit', function(e) {
                        window.passSubmitAttempt = true;
                        if (!validateAdd()) {
                            e.preventDefault();
                        }
                    });
                }
                const editForm = document.getElementById('editForm');
                if (editForm) {
                    editForm.addEventListener('submit', function(e) {
                        window.passSubmitAttempt = true;
                        if (!validateEdit()) {
                            e.preventDefault();
                        }
                    });
                }
            })();
        })();
    </script>
@endsection
