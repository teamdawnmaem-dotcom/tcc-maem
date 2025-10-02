@extends('layouts.appAdmin')

@section('title', 'User Account Management - Tagoloan Community College')
@section('accounts-active', 'active')
@section('checker-account-active', 'active')

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

        .user-table-container {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.22), 0 1.5px 8px rgba(0, 0, 0, 0.12);
            overflow: hidden;
        }

        /* Make only the table area scroll vertically */
        .user-table-scroll {
            max-height: 670px;
            overflow-y: auto;
            width: 100%;
        }

        .user-table {
            width: 100%;
            border-collapse: collapse;
        }

        .user-table th {
            background: #8B0000;
            color: #fff;
            padding: 16px 0;
            font-size: 1.1rem;
            font-weight: bold;
            border: none;
        }

        /* Keep table header visible while scrolling */
        .user-table thead th {
            position: sticky;
            top: 0;
            z-index: 2;
        }

        .user-table td {
            padding: 12px 0;
            text-align: center;
            font-size: 1rem;
            border: none;
        }

        .user-table tr:nth-child(even) {
            background: #fff;
        }

        .user-table tr:nth-child(odd) {
            background: #fbeeee;
        }

        .user-table tr:hover {
            background: #fff2e6;
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

        /* Updated Modal Styles to match the image */
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
            padding: 40px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.22), 0 1.5px 8px rgba(0, 0, 0, 0.12);
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .modal-header {
            font-size: 1.8rem;
            font-weight: bold;
            color: #8B0000;
            text-align: center;
            margin-bottom: 25px;
            width: 100%;
        }


        /* Dropdown Styles */
        .modal-form-group select {
            width: 100%;
            padding: 10px 12px;
            font-size: 1rem;
            border: 1px solid #bbb;
            border-radius: 5px;
            background-color: #fff;
            cursor: pointer;
        }

        .modal-form-group select:focus {
            outline: none;
            border-color: #8B0000;
            box-shadow: 0 0 0 2px rgba(139, 0, 0, 0.1);
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
            flex-direction: row;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
            position: relative;
            /* allow absolutely-positioned messages without shifting layout */
            padding-bottom: 18px;
            /* reserve space for validation message */
        }

        .modal-buttons {
            display: flex;
            gap: 12px;
            justify-content: center;
            margin-top: 18px;
        }

        .modal-box {
            align-items: center;
            width: 100%;
            max-width: 450px;
            padding-left: 30px;
            padding-right: 30px;
            padding-bottom: 30px;
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
            width: 200px;
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


        .modal-btn.add {
            background: #2ecc71;
            color: #fff;
        }

        .modal-btn.update {
            background: #7cc6fa;
            color: #fff;
        }

        .modal-btn.delete {
            background: transparent;
            color: #ff3636;
            border: 2px solid #ff3636;
            transition: background-color 0.15s ease, color 0.15s ease;
        }

        .modal-btn.delete:hover {
            background: #ff3636;
            color: #fff;
        }

        .modal-btn.cancel {
            background: #fff;
            color: #800000;
            border: 2px solid #800000;
            border-radius: 8px;
            padding: 10px 20px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .modal-btn.cancel:hover {
            background: #800000;
            color: #fff;
        }

        /* Client-side validation states (non-intrusive, no layout shift) */
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

        /* Inline validation message positioned under the input without affecting layout */
        .validation-message {
            position: absolute;
            left: 130px;
            /* align under the input (label min-width is 130px) */
            right: 12px;
            /* match input horizontal padding */
            bottom: 0;
            /* sit within reserved padding space */
            font-size: 0.85rem;
            color: #ff3636;
            pointer-events: none;
            padding-left: 12px;
            /* align message text with input text */
        }
    </style>
@endsection

@section('content')
    @include('partials.flash')
    @include('partials.flashupdate')
    @include('partials.flashdelete')
    <div class="faculty-header">
        <div class="faculty-title-group">
            <div class="faculty-title">User Management</div>
            <div class="faculty-subtitle"></div>
        </div>

        <div class="faculty-actions-row">
            <input type="text" class="search-input" placeholder="Search...">
            <button class="add-btn" onclick="openModal('addUserModal')">Add</button>
        </div>
    </div>
    <div class="user-table-container">
        <div class="user-table-scroll">
        <table class="user-table">
            <thead>
                <tr>
                    <th>User ID</th>
                    <th>Full Name</th>
                    <th>Username</th>
                    <th>Department</th>
                    <th>Role</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users->whereIn('user_role', ['Checker', 'Department Head']) as $user)
                    <tr>
                        <td>{{ $user->user_id }}</td>
                        <td>{{ $user->user_fname }} {{ $user->user_lname }}</td>
                        <td>{{ $user->username }}</td>
                        <td>{{ $user->user_department }}</td>
                        <td>{{ $user->user_role }}</td>
                        <td>
                            <div class="action-btns">
                                <button class="edit-btn" data-id="{{ $user->user_id }}" data-fname="{{ $user->user_fname }}"
                                    data-lname="{{ $user->user_lname }}" data-department="{{ $user->user_department }}"
                                    data-role="{{ $user->user_role }}" data-username="{{ $user->username }}"
                                    onclick="openUpdateUserModal(this)">
                                    &#9998;
                                </button>

                                <button class="delete-btn" onclick="openDeleteUserModal({{ $user->user_id }})">
                                    &#128465;
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align:center; font-style:italic; color:#666;">
                            No Registered Users found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>

    <!-- Add User Modal -->

    <div id="addUserModal" class="modal-overlay" style="display:none;">
        <div class="modal-box" style="padding: 0; overflow: hidden; border-radius: 8px;">
            <form action="{{ route('admin.users.store') }}" method="POST" style="padding: 0;">
                @csrf
                <!-- Full-width Maroon Header -->
                <div class="modal-header"
                    style="
    background-color: #8B0000; 
    color: white; 
    padding: 18px 24px;
    font-size: 24px; 
    font-weight: bold; 
    width: 100%;
    margin: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    letter-spacing: 0.5px;
    border-top-left-radius: 8px;
    border-top-right-radius: 8px;
">
                    ADD USER
                </div>

                <div class="modal-form" style="padding: 24px 24px 24px;">
                    <style>
                        /* Scope spacing adjustments to Add User modal only */
                        #addUserModal .modal-form-group {
                            gap: 6px;
                            margin-bottom: 4px;
                            /* override global 12px */
                            padding-bottom: 6px;
                            /* override global 18px */
                        }

                        #addUserModal .modal-form-group input,
                        #addUserModal .modal-form-group select,
                        #addUserModal .modal-form-group textarea {
                            padding: 10px 12px;
                            /* bigger fields */
                            font-size: 1rem;
                        }

                        #addUserModal .validation-message {
                            font-size: 0.8rem;
                            left: 130px;
                            /* align under input start (label min-width) */
                            right: 10px;
                            bottom: -10px;
                            /* sit within reserved padding */
                            padding-left: 10px;
                            /* align with input text padding */
                            line-height: 1.1;
                        }

                        #addUserModal .modal-buttons {
                            margin-top: 12px;
                        }

                        /* Add button: green border by default, green background on hover */
                        #addUserModal .modal-btn.add {
                            background: transparent;
                            border: 2px solid #2e7d32;
                            /* green border */
                            color: #2e7d32;
                            transition: background-color 0.15s ease, color 0.15s ease, border-color 0.15s ease;
                        }

                        #addUserModal .modal-btn.add:hover {
                            background: #2e7d32;
                            /* green bg on hover */
                            color: #fff;
                            border-color: #2e7d32;
                        }

                        /* Update button: match Add button green styling */
                        #updateUserModal .modal-btn.update {
                            background: #7cc6fa;
                            border: 2px solid #7cc6fa;
                            color: #fff;
                            transition: background-color 0.15s ease, color 0.15s ease, border-color 0.15s ease;
                        }

                        #updateUserModal .modal-btn.update:hover {
                            background: #5bb3f5;
                            color: #fff;
                            border-color: #5bb3f5;
                        }

                        #updateUserModal .modal-btn.update:disabled {
                            background: transparent;
                            border: 2px solid #cccccc;
                            color: #cccccc;
                            cursor: not-allowed;
                            opacity: 0.7;
                        }
                    </style>
                    <div class="modal-form-group">
                        <label for="user_id">User ID :</label>
                        <input name="user_id" type="text" id="user_id" inputmode="numeric" pattern="\\d*"
                            oninput="this.value = this.value.replace(/[^0-9]/g, ''); validateAddFields();">
                    </div>
                    <div class="modal-form-group">
                        <label for="role">Role :</label>
                        <select name="user_role" id="role">
                            <option value="">Select Role</option>
                            <option value="Department Head">Department Head</option>
                            <option value="Checker">Checker</option>
                        </select>
                    </div>

                    <div class="modal-form-group">
                        <label for="department">Department :</label>
                        <select name="user_department" id="department">
                            <option value="">Select Department</option>
                            <option value="Department of Admin">Department of Admin</option>
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
                    </div>
                    
                    <div class="modal-form-group">
                        <label for="fname">First Name :</label>
                        <input name="user_fname" type="text" id="fname">
                    </div>
                    <div class="modal-form-group">
                        <label for="lname">Last Name :</label>
                        <input name="user_lname" type="text" id="lname">
                    </div>
                    <div class="modal-form-group">
                        <label for="username">Username :</label>
                        <input name="username" type="text" id="username">
                    </div>
                    <div class="modal-form-group">
                        <label for="password">Password :</label>
                        <input name="user_password" type="password" id="password">
                    </div>

                    <div class="modal-buttons">
                        <button type="submit" class="modal-btn add">Add</button>
                        <button type="button" class="modal-btn cancel" onclick="closeModal('addUserModal')">
                            Cancel
                        </button>
                    </div>
                </div>
        </div>
        </form>
    </div>



    <!-- Update User Modal -->

    <div id="updateUserModal" class="modal-overlay" style="display:none;">
        <form id="updateUserForm" method="POST" style="padding: 0;">
            @csrf
            @method('PUT')
            <div class="modal-box" style="padding: 0; overflow: hidden; border-radius: 8px;">

                <!-- Maroon Header -->
                <div class="modal-header"
                    style="
        background-color: #8B0000;
        color: white;
            padding: 18px 24px;
        font-size: 24px;
        font-weight: bold;
        width: 100%;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            letter-spacing: 0.5px;
            border-top-left-radius: 8px;
            border-top-right-radius: 8px;
        ">
                    UPDATE USER</div>

                <div class="modal-form" style="padding: 24px 24px 24px;">
                    <style>
                        /* Scope spacing and sizing to Update User modal */
                        #updateUserModal .modal-form-group {
                            display: flex;
                            flex-direction: row;
                            align-items: center;
                            gap: 6px;
                            margin-bottom: 4px;
                            padding-bottom: 6px;
                            position: relative;
                        }

                        #updateUserModal .modal-form-group label {
                            min-width: 130px;
                            text-align: left;
                            margin-bottom: 0;
                            font-size: 1rem;
                        }

                        #updateUserModal .modal-form-group input,
                        #updateUserModal .modal-form-group select,
                        #updateUserModal .modal-form-group textarea {
                            flex: 1;
                            width: 100%;
                            padding: 10px 12px;
                            font-size: 1rem;
                            border: 1px solid #bbb;
                            border-radius: 5px;
                        }

                        #updateUserModal .validation-message {
                            font-size: 0.8rem;
                            left: 130px;
                            right: 10px;
                            bottom: -10px;
                            padding-left: 10px;
                            line-height: 1.1;
                        }

                        #updateUserModal .modal-buttons {
                            display: flex;
                            gap: 12px;
                            justify-content: center;
                            margin-top: 12px;
                        }
                    </style>
                    <!-- Role -->
                    <div class="modal-form-group">
                        <label for="update-role">Role :</label>
                        <select name="user_role" id="update-role">
                            <option value="">Select Role</option>
                            <option value="Department Head">Department Head</option>
                            <option value="Checker">Checker</option>
                        </select>
                    </div>
                    <!-- Department -->
                    <div class="modal-form-group">
                        <label for="update-department">Department :</label>
                        <select name="user_department" id="update-department">
                           <option value="">Select Department</option>
                            <option value="Department of Admin">Department of Admin</option>
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
                    </div>
                    <!-- First Name -->
                    <div class="modal-form-group">
                        <label for="update-fname">First Name :</label>
                        <input name="user_fname" type="text" id="update-fname" placeholder="ex. Juan">
                    </div>
                    <!-- Last Name -->
                    <div class="modal-form-group">
                        <label for="update-lname">Last Name :</label>
                        <input name="user_lname" type="text" id="update-lname">
                    </div>
                    <!-- Username -->
                    <div class="modal-form-group">
                        <label for="username">Username :</label>
                        <input name="username" type="text" id="update-username">
                    </div>
                    <!-- Password -->
                    <div class="modal-form-group">
                        <label for="update-password">Password :</label>
                        <input name="user_password" type="password" id="update-password">
                    </div>

                    <div class="modal-buttons">
                        <button type="submit" class="modal-btn update">Update</button>
                        <button type="button" class="modal-btn cancel" onclick="closeModal('updateUserModal')">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>


    <!-- Delete User Modal -->
    <div id="deleteUserModal" class="modal-overlay" style="display:none;">
        <form id="deleteUserForm" method="POST" class="modal-box">
            @csrf
            @method('DELETE')
            <div class="modal-header delete">DELETE USER</div>

            <!-- Warning Icon and Message -->
            <div style="text-align: center; margin:0 px 0;">
                <div style="font-size: 4rem; color: #ff3636; margin-bottom: 20px;">⚠️</div>
                <div style="font-size: 1.2rem; color: #333; margin-bottom: 10px; font-weight: bold;">Are you sure?</div>
                <div style="font-size: 1rem; color: #666; line-height: 1.5;">This action cannot be undone. The user will be
                    permanently deleted.</div>
            </div>

            <!-- Action Buttons -->
            <div class="modal-buttons">
                <button type="submit" class="modal-btn delete">Delete
                    User</button>
                <button type="button" class="modal-btn cancel" onclick="closeModal('deleteUserModal')">
                    Cancel
                </button>
            </div>
        </form>
    </div>
@endsection

@section('scripts')
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const notif = document.querySelector(".notif");

            if (notif) {
                // Auto close after 2 seconds
                setTimeout(() => {
                    notif.style.display = "none";
                }, 2000);

                // Close when clicking outside the notif-box
                notif.addEventListener("click", function(e) {
                    if (e.target === notif) {
                        notif.style.display = "none";
                    }
                });
            }
        });


        const baseUsersUrl = "{{ url('/admin/users') }}";
        
        // Store existing usernames and user IDs for validation
        const existingUsernames = @json($users->pluck('username')->toArray());
        const existingUserIds = @json($users->pluck('user_id')->toArray());
        


        function openAddUserModal() {
            document.getElementById('addUserModal').style.display = 'flex';
        }

        function openUpdateUserModal(button) {
            const user = {
                id: button.dataset.id,
                fname: button.dataset.fname,
                lname: button.dataset.lname,
                department: button.dataset.department,
                role: button.dataset.role,
                username: button.dataset.username,
            };

            const form = document.getElementById('updateUserForm');
            form.action = `${baseUsersUrl}/${user.id}`;
            const roleEl = document.getElementById('update-role');
            const deptEl = document.getElementById('update-department');
            const fnameEl = document.getElementById('update-fname');
            const lnameEl = document.getElementById('update-lname');
            const unameEl = document.getElementById('update-username');
            const passEl = document.getElementById('update-password');

            roleEl.value = user.role;
            deptEl.value = user.department;
            fnameEl.value = user.fname;
            lnameEl.value = user.lname;
            unameEl.value = user.username;
            passEl.value = '';

            // Store originals on the form for comparison
            form.dataset.origRole = user.role || '';
            form.dataset.origDept = user.department || '';
            form.dataset.origFname = user.fname || '';
            form.dataset.origLname = user.lname || '';
            form.dataset.origUname = user.username || '';

            // Disable update button initially (no changes yet)
            const updateBtn = document.querySelector('#updateUserModal .modal-btn.update');
            if (updateBtn) updateBtn.disabled = true;

            // Bind listeners once to update button disabled state
            if (!form.dataset.changeListenersBound) {
                [roleEl, deptEl, fnameEl, lnameEl, unameEl, passEl].forEach(function(el) {
                    if (!el) return;
                    const evt = el.tagName === 'SELECT' ? 'change' : 'input';
                    el.addEventListener(evt, function() {
                        updateUpdateButtonState();
                    });
                });
                form.dataset.changeListenersBound = 'true';
            }

            // Ensure initial state reflects current values
            updateUpdateButtonState();

            document.getElementById('updateUserModal').style.display = 'flex';
        }

        function updateUpdateButtonState() {
            const form = document.getElementById('updateUserForm');
            if (!form) return;
            const roleEl = document.getElementById('update-role');
            const deptEl = document.getElementById('update-department');
            const fnameEl = document.getElementById('update-fname');
            const lnameEl = document.getElementById('update-lname');
            const unameEl = document.getElementById('update-username');
            const passEl = document.getElementById('update-password');

            const changed = (
                (roleEl && roleEl.value !== (form.dataset.origRole || '')) ||
                (deptEl && deptEl.value !== (form.dataset.origDept || '')) ||
                (fnameEl && fnameEl.value !== (form.dataset.origFname || '')) ||
                (lnameEl && lnameEl.value !== (form.dataset.origLname || '')) ||
                (unameEl && unameEl.value !== (form.dataset.origUname || '')) ||
                (passEl && (passEl.value || '').length > 0)
            );

            const updateBtn = document.querySelector('#updateUserModal .modal-btn.update');
            if (updateBtn) updateBtn.disabled = !changed;
        }


        function openDeleteUserModal(id) {
            const form = document.getElementById('deleteUserForm');
            form.action = `${baseUsersUrl}/${id}`;
            document.getElementById('deleteUserModal').style.display = 'flex';
        }

        function resetModalForm(modalId) {
            const modal = document.getElementById(modalId);
            if (!modal) return;
            const form = modal.querySelector('form');
            if (!form) return;

            // Clear inputs/selects/textareas
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

            // Remove inline validation messages
            form.querySelectorAll('.validation-message').forEach(function(msg) {
                msg.textContent = '';
            });

            // Reset submit attempt flags
            window.isSubmitAttempt = false;

            // If update modal, also disable Update button again
            if (modalId === 'updateUserModal') {
                const updateBtn = modal.querySelector('.modal-btn.update');
                if (updateBtn) updateBtn.disabled = true;
            }
        }

        function closeModal(id) {
            // Hide first
            const modal = document.getElementById(id);
            if (modal) modal.style.display = 'none';

            // Reset forms only for add/update modals
            if (id === 'addUserModal' || id === 'updateUserModal') {
                resetModalForm(id);
            }
        }

        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal-overlay')) {
                // Close and reset if it's one of the user modals
                const overlayId = e.target.id;
                e.target.style.display = 'none';
                if (overlayId === 'addUserModal' || overlayId === 'updateUserModal') {
                    resetModalForm(overlayId);
                }
            }
        });

        // =========================
        // Responsive Table Search with "No results found"
        // =========================
        document.querySelector('.search-input').addEventListener('input', function() {
            let searchTerm = this.value.toLowerCase();
            let rows = document.querySelectorAll('.user-table tbody tr');
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
            let tbody = document.querySelector('.user-table tbody');
            let noResultsRow = tbody.querySelector('.no-results');

            if (!anyVisible) {
                if (!noResultsRow) {
                    noResultsRow = document.createElement('tr');
                    noResultsRow.classList.add('no-results');
                    noResultsRow.innerHTML =
                        `<td colspan="6" style="text-align:center; padding:20px; color:#999; font-style:italic;">No results found</td>`;
                    tbody.appendChild(noResultsRow);
                }
            } else {
                if (noResultsRow) noResultsRow.remove();
            }
        });

        // =========================
        // Client-side Form Validation (colors fields green/red)
        // =========================
        (function() { // Start IIFE (Immediately Invoked Function Expression) to avoid global scope pollution
            function trim(value) {
                return (value || '').trim();
            } // Helper function to remove whitespace from string values
            function isNotEmpty(value) {
                return trim(value).length > 0;
            } // Check if value exists and is not empty after trimming
            function minLen(value, n) {
                return trim(value).length >= n;
            } // Check if trimmed value meets minimum length requirement
            function isLetters(value) {
                return /^[-' a-zA-Z]+$/.test(trim(value));
            } // Validate that value contains only letters, spaces, hyphens, and apostrophes
            
            function isUsernameTaken(value, currentUsername = '') {
                const trimmedValue = trim(value);
                if (!trimmedValue) return false; // Don't check if empty
                if (trimmedValue === currentUsername) return false; // Don't check against current user's own username
                return existingUsernames.includes(trimmedValue);
            } // Check if username already exists in the system
            
            function isUserIdTaken(value, currentUserId = '') {
                const trimmedValue = trim(value);
                if (!trimmedValue) return false; // Don't check if empty
                if (trimmedValue === currentUserId) return false; // Don't check against current user's own ID
                
                // Convert to number for comparison since User IDs are typically numeric
                const numericValue = parseInt(trimmedValue);
                if (isNaN(numericValue)) return false; // If not a valid number, don't check
                
                // Check against all existing User IDs (both as strings and numbers)
                return existingUserIds.some(id => 
                    id == trimmedValue || 
                    id == numericValue || 
                    parseInt(id) == numericValue
                );
            } // Check if user ID already exists in the system

            function setValidity(el, valid) { // Function to apply visual validation styling to form elements
                if (!el) return; // Exit early if element doesn't exist
                const shouldDisplay = el.dataset.touched === 'true' || window.isSubmitAttempt ===
                    true; // Only show validation state if field was touched or form was submitted
                el.classList.remove('valid', 'invalid'); // Remove existing validation classes
                if (!shouldDisplay)
                    return; // remain neutral until touched or submit attempt - don't show validation state yet
                if (trim(el.value) === '' && el.dataset.optional === 'true')
                    return; // keep neutral if optional and empty - don't show error for empty optional fields
                el.classList.add(valid ? 'valid' :
                    'invalid'); // Add appropriate validation class (green for valid, red for invalid)
            }

            function setMessage(el, message) { // Function to display validation error messages
                if (!el) return; // Exit early if element doesn't exist
                let group = el.closest('.modal-form-group'); // Find the parent form group container
                if (!group) return; // Exit if no form group found
                let msg = group.querySelector('.validation-message'); // Look for existing validation message element
                if (!msg) { // If no validation message element exists, create one
                    msg = document.createElement('div'); // Create new div element
                    msg.className = 'validation-message'; // Add CSS class for styling
                    group.appendChild(msg); // Append the message element to the form group
                }
                const shouldDisplay = el.dataset.touched === 'true' || window.isSubmitAttempt ===
                    true; // Only show messages if field was touched or form was submitted
                if (!shouldDisplay) {
                    msg.textContent = '';
                    return;
                } // Clear message if shouldn't display yet
                msg.textContent = message || ''; // Set the validation message text (empty string if no message)
            }

            function validateAddFields() { // Validation function for the "Add User" form
                const role = document.getElementById('role'); // Get role select element
                const dept = document.getElementById('department'); // Get department select element
                const userId = document.getElementById('user_id'); // Get user ID input element
                const fname = document.getElementById('fname'); // Get first name input element
                const lname = document.getElementById('lname'); // Get last name input element
                const uname = document.getElementById('username'); // Get username input element
                const pass = document.getElementById('password'); // Get password input element

                const vRole = isNotEmpty(role && role.value); // Validate role is not empty
                const vDept = isNotEmpty(dept && dept.value); // Validate department is not empty
                const vUserId = isNotEmpty(userId && userId.value) && !isUserIdTaken(userId && userId.value); // Validate user ID is not empty and not taken
                const vFname = isNotEmpty(fname && fname.value) && isLetters(fname && fname
                    .value); // Validate first name is not empty and contains only letters
                const vLname = isNotEmpty(lname && lname.value) && isLetters(lname && lname
                    .value); // Validate last name is not empty and contains only letters
                const vUname = isNotEmpty(uname && uname.value) && minLen(uname && uname.value,
                    3) && !isUsernameTaken(uname && uname.value); // Validate username is not empty, at least 3 characters, and not taken
                const vPass = isNotEmpty(pass && pass.value) && minLen(pass && pass.value,
                    8); // Validate password is not empty and at least 8 characters

                setValidity(role, vRole); // Apply visual validation styling to role field
                setMessage(role, vRole ? '' : 'Role is required'); // Set validation message for role field

                setValidity(dept, vDept); // Apply visual validation styling to department field
                setMessage(dept, vDept ? '' : 'Department is required'); // Set validation message for department field

                setValidity(userId, vUserId); // Apply visual validation styling to user ID field
                setMessage(userId, vUserId ? '' : (isNotEmpty(userId && userId.value) ? 
                    'User ID is already existing' : 'User ID is required'
                )); // Set validation message for user ID field (different messages for empty vs taken)

                setValidity(fname, vFname); // Apply visual validation styling to first name field
                setMessage(fname, vFname ? '' : (isNotEmpty(fname && fname.value) ? 'First name is invalid' :
                    'First name is required'
                )); // Set validation message for first name field (different messages for empty vs invalid)

                setValidity(lname, vLname); // Apply visual validation styling to last name field
                setMessage(lname, vLname ? '' : (isNotEmpty(lname && lname.value) ? 'Last name is invalid' :
                    'Last name is required'
                )); // Set validation message for last name field (different messages for empty vs invalid)

                setValidity(uname, vUname); // Apply visual validation styling to username field
                setMessage(uname, vUname ? '' : (isNotEmpty(uname && uname.value) ? 
                    (isUsernameTaken(uname && uname.value) ? 'Username is taken' : 'Username must be at least 3 characters') : 
                    'Username is required'
                )); // Set validation message for username field (different messages for empty vs too short vs taken)

                setValidity(pass, vPass); // Apply visual validation styling to password field
                setMessage(pass, vPass ? '' : (isNotEmpty(pass && pass.value) ?
                    'Password must be at least 8 characters' : 'Password is required'
                )); // Set validation message for password field (different messages for empty vs too short)

                return vRole && vDept && vUserId && vFname && vLname && vUname &&
                    vPass; // Return true only if all fields are valid
            }

            function validateUpdateFields() { // Validation function for the "Update User" form
                const form = document.getElementById('updateUserForm'); // Get the update form
                const role = document.getElementById('update-role'); // Get update role select element
                const dept = document.getElementById('update-department'); // Get update department select element
                const fname = document.getElementById('update-fname'); // Get update first name input element
                const lname = document.getElementById('update-lname'); // Get update last name input element
                const uname = document.getElementById('update-username'); // Get update username input element
                const pass = document.getElementById('update-password'); // Get update password input element

                // Mark password as optional; only validate length if provided
                if (pass) pass.dataset.optional = 'true'; // Set password field as optional for update form

                const vRole = isNotEmpty(role && role.value); // Validate role is not empty
                const vDept = isNotEmpty(dept && dept.value); // Validate department is not empty
                const vFname = isNotEmpty(fname && fname.value) && isLetters(fname && fname
                    .value); // Validate first name is not empty and contains only letters
                const vLname = isNotEmpty(lname && lname.value) && isLetters(lname && lname
                    .value); // Validate last name is not empty and contains only letters
                const vUname = isNotEmpty(uname && uname.value) && minLen(uname && uname.value,
                    3) && !isUsernameTaken(uname && uname.value, form.dataset.origUname || ''); // Validate username is not empty, at least 3 characters, and not taken
                const vPass = !isNotEmpty(pass && pass.value) ? true : minLen(pass && pass.value,
                    8
                    ); // Validate password: if empty, it's valid (optional); if not empty, must be at least 8 characters

                setValidity(role, vRole); // Apply visual validation styling to role field
                setMessage(role, vRole ? '' : 'Role is required'); // Set validation message for role field

                setValidity(dept, vDept); // Apply visual validation styling to department field
                setMessage(dept, vDept ? '' : 'Department is required'); // Set validation message for department field

                setValidity(fname, vFname); // Apply visual validation styling to first name field
                setMessage(fname, vFname ? '' : (isNotEmpty(fname && fname.value) ? 'First name is invalid' :
                    'First name is required'
                )); // Set validation message for first name field (different messages for empty vs invalid)

                setValidity(lname, vLname); // Apply visual validation styling to last name field
                setMessage(lname, vLname ? '' : (isNotEmpty(lname && lname.value) ? 'Last name is invalid' :
                    'Last name is required'
                )); // Set validation message for last name field (different messages for empty vs invalid)

                setValidity(uname, vUname); // Apply visual validation styling to username field
                setMessage(uname, vUname ? '' : (isNotEmpty(uname && uname.value) ? 
                    (isUsernameTaken(uname && uname.value, form.dataset.origUname || '') ? 'Username is taken' : 'Username must be at least 3 characters') : 
                    'Username is required'
                )); // Set validation message for username field (different messages for empty vs too short vs taken)

                if (pass) { // Only validate password field if it exists
                    setValidity(pass, vPass); // Apply visual validation styling to password field
                    setMessage(pass, vPass ? '' :
                        'Password must be at least 8 characters'); // Set validation message for password field
                }

                return vRole && vDept && vFname && vLname && vUname &&
                    vPass; // Return true only if all fields are valid
            }

            function bindRealtimeValidation(selectors,
                validator) { // Function to attach real-time validation to form elements
                selectors.forEach(function(sel) { // Loop through each CSS selector provided
                    const el = document.querySelector(sel); // Find the element using the selector
                    if (!el) return; // Skip if element not found
                    const evt = el.tagName === 'SELECT' ? 'change' :
                        'input'; // Use 'change' event for select elements, 'input' for text inputs
                    el.addEventListener(evt, function() {
                        validator();
                    }); // Add event listener for real-time validation on input/change
                    el.addEventListener('blur',
                        function() { // Add event listener for validation when field loses focus
                            el.dataset.touched =
                                'true'; // Mark field as touched so validation styling will show
                            validator(); // Run validation function
                        });
                });
            }

            // Bind real-time validation for Add User
            bindRealtimeValidation([
                '#role', '#department', '#user_id', '#fname', '#lname', '#username', '#password'
            ], validateAddFields);

            // Bind real-time validation for Update User
            bindRealtimeValidation([
                '#update-role', '#update-department', '#update-fname', '#update-lname', '#update-username',
                '#update-password'
            ], validateUpdateFields);

            // Intercept Add flow: validate and submit the outer form programmatically
            (function() {
                const addModal = document.getElementById('addUserModal');
                if (!addModal) return;
                const addButton = addModal.querySelector('.modal-btn.add');
                const outerForm = addModal.querySelector('form[action]');
                if (addButton && outerForm) {
                    addButton.addEventListener('click', function(evt) {
                        evt.preventDefault();
                        window.isSubmitAttempt = true;
                        if (validateAddFields()) {
                            outerForm.submit();
                        }
                    });
                }
            })();

            // Intercept Update flow: validate and submit the outer form (id: updateUserForm)
            (function() {
                const updateModal = document.getElementById('updateUserModal');
                if (!updateModal) return;
                const updateButton = updateModal.querySelector('.modal-btn.update');
                const outerForm = document.getElementById('updateUserForm');
                if (updateButton && outerForm) {
                    updateButton.addEventListener('click', function(evt) {
                        evt.preventDefault();
                        window.isSubmitAttempt = true;
                        if (validateUpdateFields()) {
                            outerForm.submit();
                        }
                    });
                }
            })();
        })();
        // Auto-hide success flash if present
        (function() {
            const container = document.getElementById('flashContainer');
            const modal = document.getElementById('flashModal');
            if (container && modal) {
                setTimeout(() => {
                    modal.classList.remove('flash-enter');
                    modal.classList.add('flash-exit');
                    setTimeout(() => {
                        container.style.display = 'none';
                    }, 400);
                }, 3000);
            }
        })();
    </script>
@endsection
