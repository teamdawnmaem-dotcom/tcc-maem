@extends('layouts.appdeptHead')

@section('title', 'Camera Management - Tagoloan Community College')
@section('monitoring-active', 'active')
@section('cameras-active', 'active')

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
            padding: 16px 0;
            font-size: 1.1rem;
            font-weight: bold;
            border: none;
        }

        /* Keep table header visible while scrolling */
        .faculty-table thead th {
            position: sticky;
            top: 0;
            z-index: 2;
        }

        .faculty-table td {
            padding: 12px 0;
            text-align: center;
            font-size: 1rem;
            border: none;
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

        /* Make only the table area scroll vertically */
        .faculty-table-scroll {
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
            width: 40px;
            height: 32px;
            border-radius: 6px;
            border: 2px solid #111;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            font-weight: bold;
            background: #fff;
            transition: box-shadow 0.2s;
            box-shadow: none;
            outline: none;
            padding: 0;
            cursor: pointer;
        }

        .edit-btn {
            background: #7cc6fa;
            color: #fff;
            border: none;
        }

        .delete-btn {
            background: #ff3636;
            color: #fff;
            border: none;
        }

        .edit-btn:active,
        .delete-btn:active {
            box-shadow: 0 0 0 2px #2222;
        }

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
            width: 400px;
            /* match other modals */
            max-width: 95vw;
            padding: 0;
            /* content handles its own padding to allow full-bleed header */
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.22), 0 1.5px 8px rgba(0, 0, 0, 0.12);
            display: flex;
            flex-direction: column;
            align-items: center;
            overflow: hidden;
            /* clip header radius */
        }

        .modal-header {
            font-size: 2rem;
            font-weight: bold;
            color: #8B0000;
            text-align: center;
            margin-bottom: 28px;
        }

        /* Adjust spacing for Delete Camera header */
        #deleteCameraModal .modal-header {
            margin-top: 25px;
            margin-bottom: 0;
        }

        .modal-img-box {
            border: 2px dashed #222;
            border-radius: 10px;
            width: 220px;
            height: 180px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 18px;
        }

        .modal-plus {
            font-size: 3.5rem;
            color: #111;
            font-weight: bold;
        }

        .modal-form {
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: stretch;
        }

        .modal-form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
            position: relative;
            padding-bottom: 18px;
        }

        .modal-form-group label {
            margin-bottom: 6px;
            font-size: 1rem;
        }

        .modal-form-group input,
        .modal-form-group select {
            width: 100%;
            padding: 10px 12px;
            font-size: 1rem;
            border: 1px solid #bbb;
            border-radius: 5px;
        }

        .modal-form-group input.valid,
        .modal-form-group select.valid {
            border-color: #2ecc71;
            box-shadow: 0 0 0 2px rgba(46, 204, 113, 0.1);
        }

        .modal-form-group input.invalid,
        .modal-form-group select.invalid {
            border-color: #ff3636;
            box-shadow: 0 0 0 2px rgba(255, 54, 54, 0.1);
        }

        .validation-message {
            position: absolute;
            left: 130px;
            right: 10px;
            bottom: -10px;
            font-size: 0.8rem;
            color: #ff3636;
            pointer-events: none;
            padding-left: 10px;
            line-height: 1.1;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .validation-message.show {
            opacity: 1;
        }

        .modal-btn {
            width: 100%;
            padding: 14px 0;
            font-size: 1.1rem;
            font-weight: bold;
            border: none;
            border-radius: 6px;
            margin-top: 14px;
            cursor: pointer;
        }

        .modal-btn.add {
            background: #2ecc71;
            color: #fff;
        }

        .modal-btn.update {
            background: #7cc6fa;
            color: #fff;
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

        /* Delete button style to match user account */
        .modal-btn.delete {
            background: transparent;
            color: #ff3636;
            border: 2px solid #ff3636;
        }

        .modal-btn.delete:hover {
            background: #ff3636;
            color: #fff;
        }

        .modal-row {
            display: flex;
            gap: 18px;
            width: 100%;
        }

        .modal-form-group.half {
            flex: 1;
        }

        .modal-form-group select {
            width: 100%;
            padding: 6px 10px;
            font-size: 1rem;
            border: 1px solid #bbb;
            border-radius: 5px;
        }

        .modal-form-group input[readonly] {
            background: #eee;
            color: #888;
        }

        .form-row {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
            align-items: center;
        }

        .form-row.single {
            flex-direction: column;
            align-items: stretch;
        }

        .form-label {
            font-weight: bold;
            color: #333;
            min-width: 120px;
            font-size: 0.95rem;
        }

        .form-input {
            flex: 1;
            padding: 10px 12px;
            border: 1px solid #bbb;
            border-radius: 5px;
            font-size: 0.95rem;
            outline: none;
            transition: border-color 0.3s;
        }

        .form-input:focus {
            border-color: #8B0000;
        }

        .form-input[readonly] {
            background: #eee;
            color: #666;
        }

        .form-select {
            flex: 1;
            padding: 10px 12px;
            border: 1px solid #bbb;
            border-radius: 5px;
            font-size: 0.95rem;
            outline: none;
            background: #fff;
            cursor: pointer;
        }

        .form-select:focus {
            border-color: #8B0000;
        }

        .form-buttons {
            display: flex;
            gap: 10px;
            margin-top: 25px;
        }

        .form-btn {
            flex: 1;
            padding: 12px 20px;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s;
        }

        .form-btn.add {
            background: #28a745;
            color: #fff;
        }

        .form-btn.update {
            background: #7cc6fa;
            color: #fff;
        }

        .form-btn.delete {
            background: #ff3636;
            color: #fff;
        }

        .form-btn:hover {
            opacity: 0.9;
        }

        .modal-buttons {
            display: flex;
            gap: 12px;
            justify-content: center;
            margin-top: 12px;
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
            font-size: 1rem;
            color: #222;
        }

        .modal-form-group input,
        .modal-form-group select {
            flex: 1;
            width: 100%;
            padding: 10px 12px;
            font-size: 1rem;
            border: 1px solid #bbb;
            border-radius: 5px;
        }

        .modal-buttons {
            display: flex;
            gap: 12px;
            justify-content: center;
            margin-top: 10px;
        }

        .modal-btn {
            width: 50%;
            padding: 14px 0;
            font-size: 1.1rem;
            font-weight: bold;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }

        .modal-btn.add {
            color: #fff;
            background: #2ecc71;
        }

        .modal-btn.add:hover {
            background: #27ae60;
        }

        .modal-btn.update {
            color: #fff;
            background: #3498db;
        }

        .modal-btn.update:hover {
            background: #5bb3f5;
            color: #fff;
        }

        .modal-btn.cancel {
            color: #fff;
            background: #ff3636;
        }

        .modal-btn.cancel:hover {
            background: #d32f2f;
        }
    </style>
@endsection

@section('content')
    @include('partials.flash')
    @include('partials.flashupdate')
    @include('partials.flashdelete')

    <div class="faculty-header">
        <div class="faculty-title-group">
            <div class="faculty-title">Camera Management</div>
            <div class="faculty-subtitle"></div>
        </div>
        <div class="faculty-actions-row">
            <input type="text" class="search-input" placeholder="Search..." id="cameraSearch">
            <button class="add-btn" onclick="openModal('addCameraModal')">Add</button>
        </div>
    </div>

    <div class="faculty-table-container">
        <div class="faculty-table-scroll">
        <table class="faculty-table">
            <thead>
                <tr>
                    <th>Camera ID</th>
                    <th>Camera Name</th>
                    <th>Camera IP Address</th>
                    <th>Username</th>
                    <th>Password</th>
                    <th>Room No.</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="cameraTable">
                @forelse($cameras as $camera)
                    <tr>
                        <td>{{ $camera->camera_id }}</td>
                        <td>{{ $camera->camera_name }}</td>
                        <td>{{ $camera->camera_ip_address }}</td>
                        <td>{{ $camera->camera_username }}</td>
                        <td>{{ $camera->camera_password }}</td>
                        <td>{{ $camera->room_no ?? 'N/A' }}</td>
                        <td>
                            <div class="action-btns">
                                <button class="edit-btn"
                                    onclick="openUpdateModal({{ $camera->camera_id }}, '{{ $camera->camera_name }}', '{{ $camera->camera_ip_address }}', '{{ $camera->camera_username }}', '{{ $camera->camera_password }}', '{{ $camera->room_no }}')">&#9998;</button>
                                <button class="delete-btn"
                                    onclick="openDeleteModal({{ $camera->camera_id }})">&#128465;</button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align:center; font-style:italic; color:#666;">
                            No Registered Camera found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>

    <!-- Add Camera Modal -->
    <div id="addCameraModal" class="modal-overlay" style="display:none;">
        <div class="modal-box" style="padding: 0; overflow: hidden; border-radius: 8px;">
            <form method="POST" action="{{ route('deptHead.camera.store') }}" style="padding: 0;">
                @csrf
                <div class="modal-header"
                    style="
                    background-color: #8B0000; color: white; padding: 18px 24px; font-size: 24px; font-weight: bold; width: 100%; margin: 0; display: flex; align-items: center; justify-content: center; text-align: center; letter-spacing: 0.5px; border-top-left-radius: 8px; border-top-right-radius: 8px;">
                    ADD CAMERA
                </div>
                <div class="modal-form" style="padding: 24px 24px 24px;">
                    <style>
                        #addCameraModal .modal-form-group {
                            display: flex;
                            align-items: center;
                            gap: 6px;
                            margin-bottom: 4px;
                            padding-bottom: 6px;
                            position: relative;
                        }

                        #addCameraModal .modal-form-group label {
                            min-width: 130px;
                            margin-bottom: 0;
                            font-size: 1rem;
                            text-align: left;
                        }

                        #addCameraModal .modal-form-group input,
                        #addCameraModal .modal-form-group select {
                            flex: 1;
                            width: 100%;
                            padding: 10px 12px;
                            font-size: 1rem;
                            border: 1px solid #bbb;
                            border-radius: 5px;
                        }

                        #addCameraModal .validation-message {
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

                        #addCameraModal .modal-buttons {
                            display: flex;
                            gap: 12px;
                            justify-content: center;
                            margin-top: 12px;
                        }

                        #addCameraModal .modal-btn.add {
                            background: transparent;
                            border: 2px solid #2e7d32;
                            color: #2e7d32;
                        }

                        #addCameraModal .modal-btn.add:hover {
                            background: #2e7d32;
                            color: #fff;
                            border-color: #2e7d32;
                        }
                    </style>
                    <div class="modal-form-group">
                        <label>Camera Name :</label>
                        <input type="text" name="camera_name">
                        <div class="validation-message"></div>
                    </div>
                    <div class="modal-form-group">
                        <label>Camera IP Address :</label>
                        <input type="text" name="camera_ip_address">
                        <div class="validation-message"></div>
                    </div>
                    <div class="modal-form-group">
                        <label>Username :</label>
                        <input type="text" name="camera_username">
                        <div class="validation-message"></div>
                    </div>
                    <div class="modal-form-group">
                        <label>Password :</label>
                        <input type="password" name="camera_password">
                        <div class="validation-message"></div>
                    </div>
                    <div class="modal-form-group">
                        <label>Room No. :</label>
                        <select name="room_no">
                            <option value="">Select Room</option>
                            @foreach ($rooms as $room)
                                <option value="{{ $room->room_no }}">{{ $room->room_name }}</option>
                            @endforeach
                        </select>
                        <div class="validation-message"></div>
                    </div>
                    <div class="room-conflict-error"
                        style="display:none; color:#ff3636; text-align:center; margin-top:6px; margin-bottom:6px; font-weight:600;">
                    </div>
                    <div class="modal-buttons">
                        <button type="submit" class="modal-btn add">Add</button>
                        <button type="button" class="modal-btn cancel"
                            onclick="closeModal('addCameraModal')">Cancel</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Update Camera Modal -->
    <div id="updateCameraModal" class="modal-overlay" style="display:none;">
        <div class="modal-box" style="padding: 0; overflow: hidden; border-radius: 8px;">
            <form method="POST" id="updateCameraForm" style="padding: 0;">
                @csrf
                @method('PUT')
                <input type="hidden" name="camera_id" id="updateCameraId">
                <div class="modal-header"
                    style="
                    background-color: #8B0000; color: white; padding: 18px 24px; font-size: 24px; font-weight: bold; width: 100%; margin: 0; display: flex; align-items: center; justify-content: center; text-align: center; letter-spacing: 0.5px; border-top-left-radius: 8px; border-top-right-radius: 8px;">
                    UPDATE CAMERA
                </div>
                <div class="modal-form" style="padding: 24px 24px 24px;">
                    <style>
                        #updateCameraModal .modal-form-group {
                            display: flex;
                            align-items: center;
                            gap: 6px;
                            margin-bottom: 4px;
                            padding-bottom: 6px;
                            position: relative;
                        }

                        #updateCameraModal .modal-form-group label {
                            min-width: 130px;
                            margin-bottom: 0;
                            font-size: 1rem;
                            text-align: left;
                        }

                        #updateCameraModal .modal-form-group input,
                        #updateCameraModal .modal-form-group select {
                            flex: 1;
                            width: 100%;
                            padding: 10px 12px;
                            font-size: 1rem;
                            border: 1px solid #bbb;
                            border-radius: 5px;
                        }

                        #updateCameraModal .validation-message {
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

                        #updateCameraModal .modal-buttons {
                            display: flex;
                            gap: 12px;
                            justify-content: center;
                            margin-top: 12px;
                        }

                        #updateCameraModal .modal-btn.update {
                            background: #7cc6fa;
                            border: 2px solid #7cc6fa;
                            color: #fff;
                        }

                        #updateCameraModal .modal-btn.update:hover {
                            background: #5bb3f5;
                            color: #fff;
                            border-color: #5bb3f5;
                        }
                    </style>
                    <div class="modal-form-group">
                        <label>Camera Name :</label>
                        <input type="text" name="camera_name" id="updateCameraName">
                        <div class="validation-message"></div>
                    </div>
                    <div class="modal-form-group">
                        <label>Camera IP Address :</label>
                        <input type="text" name="camera_ip_address" id="updateCameraIP">
                        <div class="validation-message"></div>
                    </div>
                    <div class="modal-form-group">
                        <label>Username :</label>
                        <input type="text" name="camera_username" id="updateCameraUsername">
                        <div class="validation-message"></div>
                    </div>
                    <div class="modal-form-group">
                        <label>Password :</label>
                        <input type="password" name="camera_password" id="updateCameraPassword">
                        <div class="validation-message"></div>
                    </div>
                    <div class="modal-form-group">
                        <label>Room No. :</label>
                        <select name="room_no" id="updateCameraRoom">
                            <option value="">Select Room</option>
                            @foreach ($rooms as $room)
                                <option value="{{ $room->room_no }}">{{ $room->room_name }}</option>
                            @endforeach
                        </select>
                        <div class="validation-message"></div>
                    </div>
                    <div class="room-conflict-error"
                        style="display:none; color:#ff3636; text-align:center; margin-top:6px; margin-bottom:6px; font-weight:600;">
                    </div>
                    <div class="modal-buttons">
                        <button type="submit" class="modal-btn update">Update</button>
                        <button type="button" class="modal-btn cancel"
                            onclick="closeModal('updateCameraModal')">Cancel</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Camera Modal -->
    <div id="deleteCameraModal" class="modal-overlay" style="display:none;">
        <form id="deleteCameraForm" method="POST" class="modal-box">
            @csrf
            @method('DELETE')
            <div class="modal-header delete">DELETE CAMERA</div>
            <div style="padding: 20px 24px 24px;">
                <div style="text-align: center; margin: 30px 0;">
                    <div style="font-size: 4rem; color: #ff3636; margin-bottom: 20px;">⚠️</div>
                    <div style="font-size: 1.2rem; color: #333; margin-bottom: 10px; font-weight: bold;">Are you sure?
                    </div>
                    <div style="font-size: 1rem; color: #666; line-height: 1.5;">This action cannot be undone. The camera
                        and its live feed will be permanently deleted.</div>
                </div>
                <div class="modal-buttons" style="margin-top: 12px;">
                    <button type="submit" class="modal-btn delete">Delete Camera</button>
                    <button type="button" class="modal-btn cancel"
                        onclick="closeModal('deleteCameraModal')">Cancel</button>
                </div>
            </div>
        </form>
    </div>
@endsection

@section('scripts')
    <script>
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'flex';
            
            // Initialize button states when opening modals
            if (modalId === 'addCameraModal') {
                updateAddButtonState(false); // Start with disabled state
                // Trigger validation for any pre-filled values
                setTimeout(() => {
                    validateAdd();
                }, 100);
            } else if (modalId === 'updateCameraModal') {
                updateUpdateButtonState(false); // Start with disabled state
                // Trigger validation for any pre-filled values
                setTimeout(() => {
                    validateUpdate();
                }, 100);
            }
        }

        // Handle backend validation errors
        function displayBackendErrors() {
            // Check if there are any validation errors from the server
            const openModalValue = '{{ session("open_modal") }}';
            console.log('Backend errors check:', {
                openModal: openModalValue,
                hasRoomError: {{ $errors->has('room_no') ? 'true' : 'false' }},
                roomError: '{{ $errors->first("room_no") }}'
            });
            
            if (openModalValue) {
                openModal(openModalValue);
                
                // Display backend errors in the form fields
                @if($errors->has('room_no'))
                    const roomField = document.querySelector(`#${openModalValue} [name="room_no"]`);
                    if (roomField) {
                        // Mark the field as touched so validation messages show
                        roomField.dataset.touched = 'true';
                        roomField.classList.add('invalid');
                        setMessage(roomField, '{{ $errors->first("room_no") }}');
                        console.log('Displayed room error:', '{{ $errors->first("room_no") }}');
                    }
                @endif
                
                @if($errors->has('camera_name'))
                    const nameField = document.querySelector(`#${openModalValue} [name="camera_name"]`);
                    if (nameField) {
                        nameField.dataset.touched = 'true';
                        nameField.classList.add('invalid');
                        setMessage(nameField, '{{ $errors->first("camera_name") }}');
                    }
                @endif
                
                @if($errors->has('camera_ip_address'))
                    const ipField = document.querySelector(`#${openModalValue} [name="camera_ip_address"]`);
                    if (ipField) {
                        ipField.dataset.touched = 'true';
                        ipField.classList.add('invalid');
                        setMessage(ipField, '{{ $errors->first("camera_ip_address") }}');
                    }
                @endif
                
                @if($errors->has('camera_username'))
                    const userField = document.querySelector(`#${openModalValue} [name="camera_username"]`);
                    if (userField) {
                        userField.dataset.touched = 'true';
                        userField.classList.add('invalid');
                        setMessage(userField, '{{ $errors->first("camera_username") }}');
                    }
                @endif
                
                @if($errors->has('camera_password'))
                    const passField = document.querySelector(`#${openModalValue} [name="camera_password"]`);
                    if (passField) {
                        passField.dataset.touched = 'true';
                        passField.classList.add('invalid');
                        setMessage(passField, '{{ $errors->first("camera_password") }}');
                    }
                @endif
                
                // Update button states after displaying backend errors
                setTimeout(() => {
                    if (openModalValue === 'addCameraModal') {
                        validateAdd();
                    } else if (openModalValue === 'updateCameraModal') {
                        validateUpdate();
                    }
                }, 50);
            }
        }

        // Call this when the page loads
        document.addEventListener('DOMContentLoaded', function() {
            displayBackendErrors();
            
            // Show backend error alert if there are validation errors
            @if($errors->any())
                setTimeout(() => {
                    if (window.Swal) {
                        Swal.fire({ 
                            icon: 'error', 
                            title: 'Validation Error', 
                            text: 'Please check the form fields for errors and fix them before submitting.',
                            confirmButtonColor: '#8B0000' 
                        });
                    }
                }, 500);
            @endif
        });

        function resetModalForm(modalId) {
            const modal = document.getElementById(modalId);
            if (!modal) return;
            const form = modal.querySelector('form');
            if (!form) return;

            const fields = form.querySelectorAll('input, select, textarea');
            fields.forEach(function(el) {
                if (el.tagName === 'SELECT') {
                    el.value = '';
                } else if (el.type === 'checkbox' || el.type === 'radio') {
                    el.checked = false;
                } else {
                    el.value = '';
                }
                el.classList.remove('valid', 'invalid');
                el.dataset.touched = 'false';
            });

            form.querySelectorAll('.validation-message').forEach(function(msg) {
                msg.textContent = '';
            });

            // Clear room conflict error
            const roomConflictBox = modal.querySelector('.room-conflict-error');
            if (roomConflictBox) {
                roomConflictBox.style.display = 'none';
                roomConflictBox.textContent = '';
            }

            window.camSubmitAttempt = false;
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) modal.style.display = 'none';
            if (modalId === 'addCameraModal' || modalId === 'updateCameraModal') {
                resetModalForm(modalId);
            }
        }

        function openUpdateModal(id, name, ip, username, password, room_no) {
            openModal('updateCameraModal');
            document.getElementById('updateCameraId').value = id;
            document.getElementById('updateCameraName').value = name;
            document.getElementById('updateCameraIP').value = ip;
            document.getElementById('updateCameraUsername').value = username;
            document.getElementById('updateCameraPassword').value = password;
            document.getElementById('updateCameraRoom').value = room_no;
            document.getElementById('updateCameraForm').action = '/deptHead/cameras/' + id;
            
            // Mark all fields as touched so validation messages show immediately
            const roomField = document.getElementById('updateCameraRoom');
            if (roomField) {
                roomField.dataset.touched = 'true';
            }
            
            // Validate the pre-filled form and update button state
            setTimeout(() => {
                validateUpdate();
            }, 100);
        }

        function openDeleteModal(id) {
            openModal('deleteCameraModal');
            document.getElementById('deleteCameraForm').action = '/deptHead/cameras/' + id;
        }

        // =========================
        // Responsive Table Search with "No results found"
        // =========================
        document.querySelector('.search-input').addEventListener('input', function() {
            let searchTerm = this.value.toLowerCase();
            let rows = document.querySelectorAll('.faculty-table tbody tr');
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
            let tbody = document.querySelector('.faculty-table tbody');
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

        // =========================
        // Client-side Validation (Camera forms)
        // =========================
        (function() {
            // Ensure SweetAlert2 is available
            (function ensureSwal() {
                if (window.Swal) return;
                var s = document.createElement('script');
                s.src = 'https://cdn.jsdelivr.net/npm/sweetalert2@11';
                document.head.appendChild(s);
            })();

            function showError(title, text) {
                if (!window.Swal) return;
                Swal.fire({ icon: 'error', title: title || 'Error', text: text || '', confirmButtonColor: '#8B0000' });
            }

            function trim(v) {
                return (v || '').trim();
            }

            function isNotEmpty(v) {
                return trim(v).length > 0;
            }

            function minLen(v, n) {
                return trim(v).length >= n;
            }

            function isIP(v) {
                return /^(\d{1,3}\.){3}\d{1,3}$/.test(trim(v));
            }

            function setValidity(el, ok) {
                if (!el) return;
                const show = el.dataset.touched === 'true' || window.camSubmitAttempt === true;
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
                const show = el.dataset.touched === 'true' || window.camSubmitAttempt === true;
                m.textContent = show ? (msg || '') : '';
                if (show && msg) {
                    m.classList.add('show');
                } else {
                    m.classList.remove('show');
                }
            }

            function updateAddButtonState(isValid) {
                const addButton = document.querySelector('#addCameraModal .modal-btn.add');
                if (addButton) {
                    if (isValid) {
                        addButton.disabled = false;
                        addButton.style.opacity = '1';
                        addButton.style.cursor = 'pointer';
                        addButton.textContent = 'Add';
                    } else {
                        addButton.disabled = true;
                        addButton.style.opacity = '0.6';
                        addButton.style.cursor = 'not-allowed';
                        addButton.textContent = 'Add';
                    }
                }
            }

            function updateUpdateButtonState(isValid) {
                const updateButton = document.querySelector('#updateCameraModal .modal-btn.update');
                if (updateButton) {
                    if (isValid) {
                        updateButton.disabled = false;
                        updateButton.style.opacity = '1';
                        updateButton.style.cursor = 'pointer';
                        updateButton.textContent = 'Update';
                    } else {
                        updateButton.disabled = true;
                        updateButton.style.opacity = '0.6';
                        updateButton.style.cursor = 'not-allowed';
                        updateButton.textContent = 'Add';
                    }
                }
            }

            function checkRoomAvailability(roomNo) {
                if (!roomNo) return { available: true, message: '' };
                
                console.log('Checking room availability for:', roomNo);
                
                // Get all existing cameras from the table
                const tableRows = document.querySelectorAll('.faculty-table tbody tr');
                console.log('Found', tableRows.length, 'table rows');
                
                for (let row of tableRows) {
                    // Skip the "no results" row if it exists
                    if (row.classList.contains('no-results')) continue;
                    
                    const roomCell = row.cells[5]; // Room No. is the 6th column (index 5)
                    const cameraNameCell = row.cells[1]; // Camera Name is the 2nd column (index 1)
                    const cameraIdCell = row.cells[0]; // Camera ID is the 1st column (index 0)
                    
                    const roomText = roomCell ? roomCell.textContent.trim() : '';
                    const cameraName = cameraNameCell ? cameraNameCell.textContent.trim() : 'Unknown Camera';
                    const cameraId = cameraIdCell ? cameraIdCell.textContent.trim() : 'Unknown ID';
                    
                    console.log('Checking row - Room:', roomText, 'Camera:', cameraName, 'ID:', cameraId);
                    
                    if (roomText === roomNo) {
                        console.log('Room conflict found!');
                        return { 
                            available: false, 
                            message: `Room conflict with existing camera: ${cameraName} (ID: ${cameraId})` 
                        };
                    }
                }
                console.log('No room conflict found');
                return { available: true, message: '' };
            }

            function validateAdd() {
                const name = document.querySelector("[name='camera_name']");
                const ip = document.querySelector("[name='camera_ip_address']");
                const user = document.querySelector("[name='camera_username']");
                const pass = document.querySelector("[name='camera_password']");
                const room = document.querySelector("[name='room_no']");
                const vName = isNotEmpty(name && name.value);
                const vIP = isNotEmpty(ip && ip.value) && isIP(ip && ip.value);
                const vUser = isNotEmpty(user && user.value) && minLen(user && user.value, 3);
                const vPass = isNotEmpty(pass && pass.value) && minLen(pass && pass.value, 6);
                const vRoom = isNotEmpty(room && room.value);
                
                // Check room availability
                let roomAvailable = true;
                const roomConflictBox = document.querySelector('#addCameraModal .room-conflict-error');
                if (roomConflictBox) roomConflictBox.style.display = 'none';
                
                if (vRoom) {
                    console.log('Validating room:', room.value);
                    const roomCheck = checkRoomAvailability(room.value);
                    roomAvailable = roomCheck.available;
                    console.log('Room check result:', roomCheck);
                    if (!roomAvailable) {
                        console.log('Setting room conflict message:', roomCheck.message);
                        if (roomConflictBox) {
                            roomConflictBox.textContent = roomCheck.message;
                            roomConflictBox.style.display = 'block';
                        }
                        setValidity(room, false);
                        setMessage(room, '');
                    } else {
                        console.log('Setting room as valid');
                        if (roomConflictBox) roomConflictBox.style.display = 'none';
                        setValidity(room, true);
                        setMessage(room, '');
                    }
                } else {
                    console.log('Room is empty, setting as required');
                    if (roomConflictBox) roomConflictBox.style.display = 'none';
                    setValidity(room, false);
                    setMessage(room, 'Room is required');
                }
                
                setValidity(name, vName);
                setMessage(name, vName ? '' : 'Camera name is required');
                setValidity(ip, vIP);
                setMessage(ip, vIP ? '' : (isNotEmpty(ip && ip.value) ? 'Invalid IP address' :
                    'IP address is required'));
                setValidity(user, vUser);
                setMessage(user, vUser ? '' : (isNotEmpty(user && user.value) ?
                    'Username must be at least 3 characters' : 'Username is required'));
                setValidity(pass, vPass);
                setMessage(pass, vPass ? '' : (isNotEmpty(pass && pass.value) ?
                    'Password must be at least 6 characters' : 'Password is required'));
                
                const isValid = vName && vIP && vUser && vPass && vRoom && roomAvailable;
                updateAddButtonState(isValid);
                
                return isValid;
            }

            function checkRoomAvailabilityForUpdate(roomNo, excludeCameraId) {
                if (!roomNo) return { available: true, message: '' };
                
                // Get all existing cameras from the table
                const tableRows = document.querySelectorAll('.faculty-table tbody tr');
                for (let row of tableRows) {
                    // Skip the "no results" row if it exists
                    if (row.classList.contains('no-results')) continue;
                    
                    const cameraIdCell = row.cells[0]; // Camera ID is the 1st column (index 0)
                    const roomCell = row.cells[5]; // Room No. is the 6th column (index 5)
                    const cameraNameCell = row.cells[1]; // Camera Name is the 2nd column (index 1)
                    
                    // Skip the camera being updated
                    if (cameraIdCell && cameraIdCell.textContent.trim() === excludeCameraId.toString()) {
                        continue;
                    }
                    
                    if (roomCell && roomCell.textContent.trim() === roomNo) {
                        const cameraName = cameraNameCell ? cameraNameCell.textContent.trim() : 'Unknown Camera';
                        const cameraId = cameraIdCell ? cameraIdCell.textContent.trim() : 'Unknown ID';
                        return { 
                            available: false, 
                            message: `Room conflict with existing camera: ${cameraName} (ID: ${cameraId})` 
                        };
                    }
                }
                return { available: true, message: '' };
            }

            function validateUpdate() {
                const name = document.getElementById('updateCameraName');
                const ip = document.getElementById('updateCameraIP');
                const user = document.getElementById('updateCameraUsername');
                const pass = document.getElementById('updateCameraPassword');
                const room = document.getElementById('updateCameraRoom');
                const cameraId = document.getElementById('updateCameraId');
                const vName = isNotEmpty(name && name.value);
                const vIP = isNotEmpty(ip && ip.value) && isIP(ip && ip.value);
                const vUser = isNotEmpty(user && user.value) && minLen(user && user.value, 3);
                const vPass = isNotEmpty(pass && pass.value) && minLen(pass && pass.value, 6);
                const vRoom = isNotEmpty(room && room.value);
                
                // Check room availability (excluding current camera)
                let roomAvailable = true;
                const roomConflictBox = document.querySelector('#updateCameraModal .room-conflict-error');
                if (roomConflictBox) roomConflictBox.style.display = 'none';
                
                if (vRoom && cameraId && cameraId.value) {
                    const roomCheck = checkRoomAvailabilityForUpdate(room.value, cameraId.value);
                    roomAvailable = roomCheck.available;
                    if (!roomAvailable) {
                        if (roomConflictBox) {
                            roomConflictBox.textContent = roomCheck.message;
                            roomConflictBox.style.display = 'block';
                        }
                        setValidity(room, false);
                        setMessage(room, '');
                    } else {
                        if (roomConflictBox) roomConflictBox.style.display = 'none';
                        setValidity(room, true);
                        setMessage(room, '');
                    }
                } else if (!vRoom) {
                    if (roomConflictBox) roomConflictBox.style.display = 'none';
                    setValidity(room, false);
                    setMessage(room, 'Room is required');
                } else {
                    if (roomConflictBox) roomConflictBox.style.display = 'none';
                    setValidity(room, true);
                    setMessage(room, '');
                }
                
                setValidity(name, vName);
                setMessage(name, vName ? '' : 'Camera name is required');
                setValidity(ip, vIP);
                setMessage(ip, vIP ? '' : (isNotEmpty(ip && ip.value) ? 'Invalid IP address' :
                    'IP address is required'));
                setValidity(user, vUser);
                setMessage(user, vUser ? '' : (isNotEmpty(user && user.value) ?
                    'Username must be at least 3 characters' : 'Username is required'));
                setValidity(pass, vPass);
                setMessage(pass, vPass ? '' : (isNotEmpty(pass && pass.value) ?
                    'Password must be at least 6 characters' : 'Password is required'));
                
                const isValid = vName && vIP && vUser && vPass && vRoom && roomAvailable;
                updateUpdateButtonState(isValid);
                
                return isValid;
            }

            ['[name="camera_name"]', '[name="camera_ip_address"]', '[name="camera_username"]',
                '[name="camera_password"]', '[name="room_no"]'
            ].forEach(sel => {
                const el = document.querySelector(`#addCameraModal ${sel}`);
                if (!el) return;
                const evt = el.tagName === 'SELECT' ? 'change' : 'input';
                el.addEventListener(evt, () => {
                    // Mark field as touched immediately when user interacts
                    el.dataset.touched = 'true';
                    validateAdd();
                });
                el.addEventListener('blur', () => {
                    el.dataset.touched = 'true';
                    validateAdd();
                });
            });
            ['#updateCameraName', '#updateCameraIP', '#updateCameraUsername', '#updateCameraPassword',
                '#updateCameraRoom'
            ].forEach(sel => {
                const el = document.querySelector(sel);
                if (!el) return;
                const evt = el.tagName === 'SELECT' ? 'change' : 'input';
                el.addEventListener(evt, () => {
                    // Mark field as touched immediately when user interacts
                    el.dataset.touched = 'true';
                    validateUpdate();
                });
                el.addEventListener('blur', () => {
                    el.dataset.touched = 'true';
                    validateUpdate();
                });
            });

            (function() {
                const addForm = document.querySelector('#addCameraModal form');
                if (addForm) {
                    addForm.addEventListener('submit', function(e) {
                        window.camSubmitAttempt = true;
                        if (!validateAdd()) {
                            e.preventDefault();
                            showError('Validation Error', 'Please fix all errors before submitting. Check for room conflicts and complete all required fields.');
                        }
                    });
                }
                const updForm = document.getElementById('updateCameraForm');
                if (updForm) {
                    updForm.addEventListener('submit', function(e) {
                        window.camSubmitAttempt = true;
                        if (!validateUpdate()) {
                            e.preventDefault();
                            showError('Validation Error', 'Please fix all errors before submitting. Check for room conflicts and complete all required fields.');
                        }
                    });
                }
            })();
        })();

        // Close + reset when clicking outside (overlay)
        document.addEventListener('click', function(e) {
            if (e.target.classList && e.target.classList.contains('modal-overlay')) {
                const overlayId = e.target.id;
                e.target.style.display = 'none';
                if (overlayId === 'addCameraModal' || overlayId === 'updateCameraModal') {
                    resetModalForm(overlayId);
                }
            }
        });
    </script>
@endsection
