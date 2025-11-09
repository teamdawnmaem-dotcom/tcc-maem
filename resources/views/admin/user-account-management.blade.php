@extends('layouts.appAdmin')

@section('title', 'User Account Management - Tagoloan Community College')
@section('checker-account-active', 'active')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/user-account-management.css') }}">
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
        <div class="modal-box" style="padding: 0; overflow: hidden; border-radius: 8px; width: 450px; max-width: 95vw;">
            <form action="{{ route('admin.users.store') }}" method="POST" style="padding: 0;">
                @csrf
                <!-- Full-width Maroon Header -->
                <div class="modal-header"
                    style="
    background-color: #8B0000; 
    color: white; 
    padding: 14.4px 24px;
   
    font-size: 19.2px; 
    font-weight: bold; 
    width: 100%;
    margin: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    letter-spacing: 0.4px;
    border-top-left-radius: 6.4px;
    border-top-right-radius: 6.4px;
">
                    ADD USER
                </div>

                <div class="modal-form" style="padding: 20px 24px; margin: 0; width: 100%; box-sizing: border-box;">
                    <style>
                        /* Scope spacing adjustments to Add User modal only */
                        #addUserModal .modal-form-group {
                            gap: 4.8px;
                            margin-bottom: 3.2px;
                            /* override global 12px */
                            padding-bottom: 4.8px;
                            /* override global 18px */
                        }

                        #addUserModal .modal-form-group input,
                        #addUserModal .modal-form-group select,
                        #addUserModal .modal-form-group textarea {
                            padding: 8px 9.6px;
                            /* 80% fields */
                            font-size: 0.8rem;
                        }

                        #addUserModal .validation-message {
                            font-size: 0.64rem;
                            left: 104px;
                            /* align under input start (label min-width) */
                            right: 8px;
                            bottom: -8px;
                            /* sit within reserved padding */
                            padding-left: 8px;
                            /* align with input text padding */
                            line-height: 1.1;
                        }

                        #addUserModal .modal-buttons {
                            margin-top: 9.6px;
                        }

                        /* Add button: green border by default, green background on hover */
                        #addUserModal .modal-btn.add {
                            background: transparent;
                            border: 1.6px solid #2e7d32;
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
                        /* Buttons scale (padding/font) */
                        #addUserModal .modal-buttons .modal-btn {
                            padding: 11.2px 0;
                            font-size: 0.88rem;
                            border-radius: 4.8px;
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
            <div class="modal-box" style="padding: 0; overflow: hidden; border-radius: 8px; transform: scale(0.8); transform-origin: center; display: flex; flex-direction: column; max-height: 50vh;">

                <!-- Maroon Header -->
                <div class="modal-header"
                    style="
        background-color: #8B0000;
        color: white;
            padding: 14.4px 19.2px;
        font-size: 19.2px;
        font-weight: bold;
        width: 100%;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            letter-spacing: 0.4px;
            border-top-left-radius: 6.4px;
            border-top-right-radius: 6.4px;
        flex-shrink: 0;
        ">
                    UPDATE USER</div>

                    <div class="modal-form" style="padding: 20px 24px; margin: 0; width: 100%; box-sizing: border-box;">
                    <style>
                        /* Scope spacing and sizing to Update User modal */
                        #updateUserModal .modal-form-group {
                            display: flex;
                            flex-direction: row;
                            align-items: center;
                            gap: 9.6px;
                            margin-bottom: 12px;
                            padding-bottom: 16px;
                            position: relative;
                        }

                        #updateUserModal .modal-form-group label {
                            min-width: 104px;
                            text-align: left;
                            margin-bottom: 0;
                            font-size: 0.8rem;
                        }

                        #updateUserModal .modal-form-group input,
                        #updateUserModal .modal-form-group select,
                        #updateUserModal .modal-form-group textarea {
                            flex: 1;
                            width: 100%;
                            padding: 8px 9.6px;
                            font-size: 0.8rem;
                            border: 1px solid #bbb;
                            border-radius: 4px;
                            box-sizing: border-box;
                        }

                        /* Update User Inputs - Proper styling */
                        #updateUserModal .update-user-inputs {
                            flex: 1;
                            width: 100%;
                            height: 40px;
                            padding: 8px 9.6px;
                            font-size: 0.8rem;
                            border: 1px solid #bbb;
                            border-radius: 4px;
                            box-sizing: border-box;
                            background-color: #fff;
                        }

                        #updateUserModal .update-user-inputs:focus {
                            outline: none;
                            border-color: #8B0000;
                            box-shadow: 0 0 0 2px rgba(139, 0, 0, 0.1);
                        }

                        #updateUserModal .validation-message {
                            font-size: 0.64rem;
                            left: 104px;
                            right: 8px;
                            bottom: 0;
                            padding-left: 8px;
                            line-height: 1.1;
                        }

                        #updateUserModal .modal-buttons {
                            display: flex;
                            gap: 9.6px;
                            justify-content: center;
                            margin-top: 9.6px;
                        }

                        /* Update Modal - Scaled button styles (80%) */
                        #updateUserModal .modal-btn {
                            width: 160px;
                            padding: 11.2px 0;
                            font-size: 0.88rem;
                            border-radius: 4.8px;
                        }

                        #updateUserModal .modal-btn.cancel {
                            padding: 8px 16px;
                        }
                    </style>
                    <!-- Role -->

                    <div class="modal-form-group">
                        <label for="update-role">Role :</label>
                        <select name="user_role" id="update-role" class="update-user-inputs">
                            <option value="">Select Role</option>
                            <option value="Department Head">Department Head</option>
                            <option value="Checker">Checker</option>
                        </select>
                    </div>
                    <!-- Department -->
                    <div class="modal-form-group">
                        <label for="update-department">Department :</label>
                        <select name="user_department" id="update-department" class="update-user-inputs">
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
                        <input name="user_fname" type="text" id="update-fname" placeholder="ex. Juan"  class="update-user-inputs">
                    </div>
                    <!-- Last Name -->
                    <div class="modal-form-group">
                        <label for="update-lname">Last Name :</label>
                        <input name="user_lname" type="text" id="update-lname" class="update-user-inputs">
                    </div>
                    <!-- Username -->
                    <div class="modal-form-group">
                        <label for="username">Username :</label>
                        <input name="username" type="text" id="update-username" class="update-user-inputs">
                    </div>
                    <!-- Password -->
                    <div class="modal-form-group">
                        <label for="update-password">Password :</label>
                        <input name="user_password" type="password" id="update-password" class="update-user-inputs" >
                    </div>

                    <div class="modal-buttons">
                        <button type="submit" class="modal-btn update">Update</button>
                        <button type="button" class="modal-btn cancel" onclick="closeModal('updateUserModal')" >
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>


    <!-- Delete User Modal -->
    <div id="deleteUserModal" class="modal-overlay" style="display:none;">
        <form id="deleteUserForm" method="POST" class="modal-box" style="transform: scale(0.8); transform-origin: center;">
            @csrf
            @method('DELETE')
            <div class="modal-header delete">DELETE USER</div>

            <!-- Warning Icon and Message -->
            <div style="text-align: center; margin:0 px 0;">
                <div style="font-size: 3.2rem; color: #ff3636; margin-bottom: 16px;">⚠️</div>
                <div style="font-size: 0.96rem; color: #333; margin-bottom: 8px; font-weight: bold;">Are you sure?</div>
                <div style="font-size: 0.8rem; color: #666; line-height: 1.5;">This action cannot be undone. The user will be
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
