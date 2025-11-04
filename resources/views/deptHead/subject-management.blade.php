@extends('layouts.appdeptHead')

@section('title', 'Subject Management - Tagoloan Community College')
@section('files-active', 'active')
@section('subject-active', 'active')

@section('styles')
    <style>
        .faculty-header {
            margin-bottom: 0;
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
            margin-bottom: 0;
        }

        .faculty-actions-row {
            display: flex;
            gap: 8px;
            margin-top: 16px;
            margin-bottom: 32px;
            width: 100%;
            align-items: stretch;
        }

        .search-input {
            padding: 10px 12px;
            font-size: 14px;
            border: 1px solid #ccc;
            border-radius: 4px;
            width: 75%;
            flex: 0 0 75%;
            min-width: 0;
            box-sizing: border-box;
        }

        .add-btn {
            padding: 10px 24px;
            font-size: 14px;
            border: none;
            border-radius: 4px;
            background-color: #2ecc71;
            color: #fff;
            cursor: pointer;
            font-weight: bold;
            width: 25%;
            flex: 0 0 25%;
            white-space: nowrap;
        }

        .subject-table-container {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.22), 0 1.5px 8px rgba(0, 0, 0, 0.12);
            overflow: hidden;
        }

        .subject-table {
            width: 100%;
            border-collapse: collapse;
        }

        .subject-table th {
            background: #8B0000;
            color: #fff;
            padding: 12.8px 0;
            font-size: 0.88rem;
            font-weight: bold;
            border: none;
        }

        .subject-table thead th {
            position: sticky;
            top: 0;
            z-index: 2;
        }

        .subject-table td {
            padding: 9.6px 0;
            text-align: center;
            font-size: 0.8rem;
            border: none;
        }

        .subject-table tr:nth-child(even) {
            background: #fff;
        }

        .subject-table tr:nth-child(odd) {
            background: #fbeeee;
        }

        .subject-table tr:hover {
            background: #fff2e6;
        }

        .subject-table-scroll {
            max-height: 536px;
            overflow-y: auto;
            width: 100%;
        }

        /* Match action buttons style from teaching load */
        .action-btns {
            display: flex;
            gap: 6.4px;
            justify-content: center;
            align-items: center;
        }

        .edit-btn,
        .delete-btn {
            width: 32px;
            height: 25.6px;
            border-radius: 4.8px;
            border: 2px solid #111;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.88rem;
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

        /* Mobile responsive design for 430px width */
        @media (max-width: 430px) {
            /* Faculty Header */
            .faculty-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 16px;
                margin-top: -40px;
                margin-bottom: 24px;
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

            /* Actions Row - Side by side on mobile */
            .faculty-actions-row {
                position: relative;
                top: 0;
                right: 0;
                width: 100%;
                flex-direction: row;
                gap: 8px;
                z-index: 1;
                margin: 0;
                padding: 0;
            }

            .search-input {
                width: 75% !important;
                flex: 0 0 calc(75% - 4px);
                padding: 10px 12px;
                margin-top: -50px;
                margin-bottom: 60px;
                font-size: 0.9rem;
                border-radius: 6px;
                margin: 0;
            }

            .add-btn {
                width: 25%;
                flex: 0 0 calc(25% - 4px);
                padding: 10px 8px;
                font-size: 0.85rem;
                border-radius: 6px;
                font-weight: bold;
                white-space: nowrap;
                margin: 0;
            }

            /* Table Container - Ensure alignment with actions row */
            .subject-table-container {
                border-radius: 8px;
                overflow: hidden;
                margin-top: 1000px;
                width: 100%;
                margin: 0;
            }

            .subject-table-scroll {
                max-height: 50vh;
                overflow-x: auto;
                overflow-y: auto;
                -webkit-overflow-scrolling: touch;
            }

            .subject-table {
                min-width: 650px; /* Minimum width to maintain readability */
            }

            .subject-table th {
                padding: 10px 8px;
                font-size: 0.75rem;
                white-space: nowrap;
            }

            .subject-table td {
                padding: 8px 6px;
                font-size: 0.75rem;
                white-space: nowrap;
            }

            /* Empty state message */
            .subject-table td[colspan] {
                font-size: 0.75rem;
                padding: 20px 12px;
            }

            /* Action Buttons */
            .action-btns {
                gap: 6px;
            }

            .edit-btn,
            .delete-btn {
                width: 36px;
                height: 30px;
                font-size: 1rem;
            }

            /* Modals - Mobile Optimized */
            .modal-overlay {
                padding: 10px;
            }

            .modal-box {
                width: 95vw !important;
                max-width: 95vw !important;
                padding: 20px 16px !important;
                margin: 0;
            }

            /* Add Subject Modal - Mobile */
            #addSubjectModal .modal-box {
                width: 95vw !important;
                max-width: 75vw !important;
                height: 50vh !important;
                max-height: 50vh !important;
                display: flex !important;
                flex-direction: column !important;
                overflow: hidden !important;
                padding: 0 !important;
            }

            #addSubjectModal .modal-header {
                font-size: 1.1rem !important;
                padding: 12px 16px !important;
                flex-shrink: 0 !important;
                position: sticky !important;
                top: 0 !important;
                z-index: 10 !important;
            }

            #addSubjectModal .modal-form {
                flex: 1 !important;
                overflow-y: auto !important;
                overflow-x: hidden !important;
                -webkit-overflow-scrolling: touch !important;
                padding: 16px 16px !important;
                width: 100% !important;
                box-sizing: border-box !important;
            }

            #addSubjectModal .modal-form-group {
                flex-direction: column;
                align-items: flex-start;
                gap: 6px;
                margin-bottom: 16px;
                padding-bottom: 20px;
            }

            #addSubjectModal .modal-form-group label {
                min-width: auto;
                width: 100%;
                margin-bottom: 4px;
                font-size: 0.75rem;
            }

            #addSubjectModal .modal-form-group input,
            #addSubjectModal .modal-form-group select {
                width: 100%;
                padding: 10px 12px;
                font-size: 0.9rem;
            }

            #addSubjectModal .validation-message {
                position: relative;
                left: 0;
                right: 0;
                bottom: 0;
                padding-left: 0;
                margin-top: 4px;
            }

            #addSubjectModal .modal-buttons {
                flex-direction: column;
                gap: 10px;
                margin-top: 16px;
            }

            #addSubjectModal .modal-btn {
                width: 100% !important;
                padding: 12px !important;
                font-size: 0.9rem !important;
            }

            /* Update Subject Modal */
            #updateSubjectModal .modal-box {
                width: 95vw !important;
                max-width: 95vw !important;
                transform: scale(1) !important;
                padding: 0 !important;
            }

            #updateSubjectModal .modal-header {
                font-size: 1.1rem !important;
                padding: 12px 16px !important;
            }

            #updateSubjectModal .modal-form {
                padding: 16px !important;
            }

            #updateSubjectModal .modal-form-group {
                flex-direction: column;
                align-items: flex-start;
                gap: 6px;
                margin-bottom: 16px;
                padding-bottom: 20px;
            }

            #updateSubjectModal .modal-form-group label {
                min-width: auto;
                width: 100%;
                margin-bottom: 4px;
                font-size: 0.75rem;
            }

            #updateSubjectModal .modal-form-group input,
            #updateSubjectModal .modal-form-group select {
                width: 100%;
                padding: 10px 12px;
                font-size: 0.9rem;
            }

            #updateSubjectModal .validation-message {
                position: relative;
                left: 0;
                right: 0;
                bottom: 0;
                padding-left: 0;
                margin-top: 4px;
            }

            #updateSubjectModal .modal-buttons {
                flex-direction: column;
                gap: 10px;
                margin-top: 16px;
            }

            #updateSubjectModal .modal-btn {
                width: 100% !important;
                padding: 12px !important;
                font-size: 0.9rem !important;
            }

            /* Delete Subject Modal */
            #deleteSubjectModal .modal-box {
                width: 90vw !important;
                max-width: 90vw !important;
                padding: 24px 20px !important;
                transform: scale(1) !important;
            }

            #deleteSubjectModal .modal-header {
                font-size: 1.1rem !important;
                margin-bottom: 16px !important;
            }

            #deleteSubjectModal .modal-buttons {
                flex-direction: column;
                gap: 10px;
                margin-top: 20px !important;
            }

            #deleteSubjectModal .modal-btn {
                width: 100% !important;
                padding: 12px !important;
                font-size: 0.9rem !important;
            }

            /* General Modal Styles */
            .modal-header {
                font-size: 1.1rem !important;
            }

            .modal-form-group {
                flex-direction: column;
            }

            .modal-form-group label {
                min-width: auto;
                width: 100%;
            }

            .modal-form-group input,
            .modal-form-group select,
            .modal-form-group textarea {
                width: 100%;
            }
        }
    </style>
@endsection

@section('content')
    @include('partials.flash')
    @include('partials.flashupdate')
    @include('partials.flashdelete')

    <div class="faculty-header">
        <div class="faculty-title-group">
            <div class="faculty-title">Subject Management</div>
            <div class="faculty-subtitle"></div>
        </div>
    </div>
    <div class="faculty-actions-row">
        <input type="text" class="search-input" id="subjectSearch" placeholder="Search...">
        <button class="add-btn" onclick="openModal && openModal('addSubjectModal')">Add</button>
    </div>

    <div class="subject-table-container">
        <div class="subject-table-scroll">
            <table class="subject-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Subject Code</th>
                        <th>Subject Description</th>
                        <th>Department</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @php($__subjects = $subjects ?? [])
                    @forelse($__subjects as $idx => $subject)
                        <tr data-id="{{ data_get($subject, 'subject_id', data_get($subject, 'id')) }}">
                            <td data-label="No">{{ $idx + 1 }}</td>
                            <td data-label="Subject Code" class="subject-code">{{ data_get($subject, 'subject_code', '') }}</td>
                            <td data-label="Subject Description" class="subject-desc">{{ data_get($subject, 'subject_description', '') }}</td>
                            <td data-label="Department" class="subject-dept">{{ data_get($subject, 'department', '') }}</td>
                            <td data-label="Action">
                                <div class="action-btns">
                                    <button class="edit-btn"
                                        onclick="openUpdateModal('{{ data_get($subject, 'subject_id', data_get($subject, 'id')) }}')">&#9998;</button>
                                    <button class="delete-btn"
                                        onclick="openDeleteModal('{{ data_get($subject, 'subject_id', data_get($subject, 'id')) }}')">&#128465;</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="text-align:center; font-style:italic; color:#666;">
                                No Registered Subject found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Add Subject Modal -->
    <div id="addSubjectModal" class="modal-overlay" style="display:none;">
        <div class="modal-box" style="padding: 0; overflow: hidden; border-radius: 8px;">
            <form method="POST" action="{{ route('deptHead.subjects.store') }}" style="padding: 0;">
                @csrf
                <div class="modal-header"
                    style="background-color: #8B0000; color: white; padding: 14.4px 19.2px; font-size: 19.2px; font-weight: bold; width: 100%; margin: 0; display: flex; align-items: center; justify-content: center; text-align: center; letter-spacing: 0.4px; border-top-left-radius: 6.4px; border-top-right-radius: 6.4px;">
                    ADD SUBJECT</div>
                <div class="modal-form" style="padding: 24px 24px 24px;">
                    <style>
                        /* Scoped to Add Subject modal to mirror Faculty styles */
                        #addSubjectModal .modal-form-group {
                            display: flex;
                            flex-direction: row;
                            align-items: center;
                            gap: 6px;
                            margin-bottom: 4px;
                            padding-bottom: 6px;
                            position: relative;
                        }

                        #addSubjectModal .modal-form-group label {
                            min-width: 104px;
                            text-align: left;
                            margin-bottom: 0;
                            font-size: 0.8rem;
                            color: #222;
                        }

                        #addSubjectModal .modal-form-group input,
                        #addSubjectModal .modal-form-group select {
                            flex: 1;
                            width: 100%;
                            padding: 8px 9.6px;
                            font-size: 0.8rem;
                            border: 1px solid #bbb;
                            border-radius: 4px;
                        }

                        #addSubjectModal .validation-message {
                            font-size: 0.64rem;
                            left: 104px;
                            right: 8px;
                            bottom: -8px;
                            padding-left: 8px;
                            line-height: 1.1;
                            position: absolute;
                            color: #ff3636;
                            pointer-events: none;
                        }

                        #addSubjectModal .modal-buttons {
                            display: flex;
                            gap: 9.6px;
                            justify-content: center;
                            margin-top: 9.6px;
                        }

                        #addSubjectModal .modal-btn.add {
                            background: transparent !important;
                            border: 2px solid #2e7d32 !important;
                            color: #2e7d32 !important;
                        }

                        #addSubjectModal .modal-btn.add:hover {
                            background: #2e7d32 !important;
                            color: #fff !important;
                            border-color: #2e7d32 !important;
                        }

                        #addSubjectModal .modal-btn.cancel {
                            background: #fff;
                            color: #800000;
                            border: 1.6px solid #800000;
                            border-radius: 6.4px;
                        }

                        #addSubjectModal .modal-btn.cancel:hover {
                            background: #800000;
                            color: #fff;
                        }
                    </style>

                    <div class="modal-form-group">
                        <label>Subject Code :</label>
                        <input type="text" name="subject_code" placeholder="">
                        <div class="validation-message"></div>
                    </div>
                    <div class="modal-form-group">
                        <label>Description :</label>
                        <input type="text" name="subject_description" placeholder="">
                        <div class="validation-message"></div>
                    </div>
                    <div class="modal-form-group">
                        <label>Department :</label>
                        <select name="department">
                            <option value="">Select Department</option>
                            <option value="Department of Admin">Department of Admin</option>
                            <option value="College of Information Technology">College of Information Technology</option>
                            <option value="College of Library and Information Science">College of Library and Information
                                Science</option>
                            <option value="College of Criminology">College of Criminology</option>
                            <option value="College of Arts and Sciences">College of Arts and Sciences</option>
                            <option value="College of Hospitality Management">College of Hospitality Management</option>
                            <option value="College of Sociology">College of Sociology</option>
                            <option value="College of Engineering">College of Engineering</option>
                            <option value="College of Education">College of Education</option>
                            <option value="College of Business Administration">College of Business Administration</option>
                        </select>
                        <div class="validation-message"></div>
                    </div>
                    <div class="logic-error"
                        style="display:none; color:#ff3636; text-align:center; margin:6px 0; font-weight:600;"></div>
                    <div class="modal-buttons">
                        <button type="submit" class="modal-btn add">Add</button>
                        <button type="button" class="modal-btn cancel"
                            onclick="closeModal('addSubjectModal')">Cancel</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Update Subject Modal -->
    <div id="updateSubjectModal" class="modal-overlay" style="display:none;">
        <div class="modal-box" style="padding: 0; overflow: hidden; border-radius: 8px;">
            <form method="POST" id="updateSubjectForm" style="padding: 0;">
                @csrf
                @method('PUT')
                <div class="modal-header"
                    style="background-color: #8B0000; color: white; padding: 14.4px 19.2px; font-size: 19.2px; font-weight: bold; width: 100%; margin: 0; display: flex; align-items: center; justify-content: center; text-align: center; letter-spacing: 0.4px; border-top-left-radius: 6.4px; border-top-right-radius: 6.4px;">
                    UPDATE SUBJECT</div>
                <div class="modal-form" style="padding: 24px 24px 24px;">
                    <style>
                        /* Scoped to Update Subject modal to mirror Faculty styles */
                        #updateSubjectModal .modal-form-group {
                            display: flex;
                            flex-direction: row;
                            align-items: center;
                            gap: 6px;
                            margin-bottom: 4px;
                            padding-bottom: 6px;
                            position: relative;
                        }

                        #updateSubjectModal .modal-form-group label {
                            min-width: 104px;
                            text-align: left;
                            margin-bottom: 0;
                            font-size: 0.8rem;
                            color: #222;
                        }

                        #updateSubjectModal .modal-form-group input,
                        #updateSubjectModal .modal-form-group select {
                            flex: 1;
                            width: 100%;
                            padding: 8px 9.6px;
                            font-size: 0.8rem;
                            border: 1px solid #bbb;
                            border-radius: 4px;
                        }

                        #updateSubjectModal .validation-message {
                            font-size: 0.64rem;
                            left: 104px;
                            right: 8px;
                            bottom: -8px;
                            padding-left: 8px;
                            line-height: 1.1;
                            position: absolute;
                            color: #ff3636;
                            pointer-events: none;
                        }

                        #updateSubjectModal .modal-buttons {
                            display: flex;
                            gap: 9.6px;
                            justify-content: center;
                            margin-top: 9.6px;
                        }

                        #updateSubjectModal .modal-btn.add {
                            background: transparent !important;
                            border: 2px solid #7cc6fa !important;
                            color: #7cc6fa !important;
                        }

                        #updateSubjectModal .modal-btn.add:hover {
                            background: #7cc6fa !important;
                            color: #fff !important;
                            border-color: #7cc6fa !important;
                        }

                        #updateSubjectModal .modal-btn.cancel {
                            background: #fff;
                            color: #800000;
                            border: 1.6px solid #800000;
                            border-radius: 6.4px;
                        }

                        #updateSubjectModal .modal-btn.cancel:hover {
                            background: #800000;
                            color: #fff;
                        }
                    </style>

                    <div class="modal-form-group">
                        <label>Subject Code :</label>
                        <input type="text" name="subject_code">
                        <div class="validation-message"></div>
                    </div>
                    <div class="modal-form-group">
                        <label>Description :</label>
                        <input type="text" name="subject_description">
                        <div class="validation-message"></div>
                    </div>
                    <div class="modal-form-group">
                        <label>Department :</label>
                        <select name="department">
                            <option value="">Select Department</option>
                            <option value="Department of Admin">Department of Admin</option>
                            <option value="College of Information Technology">College of Information Technology</option>
                            <option value="College of Library and Information Science">College of Library and Information
                                Science</option>
                            <option value="College of Criminology">College of Criminology</option>
                            <option value="College of Arts and Sciences">College of Arts and Sciences</option>
                            <option value="College of Hospitality Management">College of Hospitality Management</option>
                            <option value="College of Sociology">College of Sociology</option>
                            <option value="College of Engineering">College of Engineering</option>
                            <option value="College of Education">College of Education</option>
                            <option value="College of Business Administration">College of Business Administration</option>
                        </select>
                        <div class="validation-message"></div>
                    </div>
                    <div class="logic-error"
                        style="display:none; color:#ff3636; text-align:center; margin:6px 0; font-weight:600;"></div>
                    <div class="modal-buttons">
                        <button type="submit" class="modal-btn add">Update</button>
                        <button type="button" class="modal-btn cancel"
                            onclick="closeModal('updateSubjectModal')">Cancel</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Subject Modal -->
    <div id="deleteSubjectModal" class="modal-overlay" style="display:none;">
        <form id="deleteSubjectForm" method="POST" class="modal-box">
            @csrf
            @method('DELETE')
            <div class="modal-header delete">DELETE SUBJECT</div>
            <style>
                /* Scoped hover styles for Delete Subject modal buttons */
                #deleteSubjectModal .modal-btn.delete:hover {
                    background: #ff3636 !important;
                    color: #fff !important;
                    border-color: #ff3636 !important;
                }

                #deleteSubjectModal .modal-btn.cancel:hover {
                    background: #800000 !important;
                    color: #fff !important;
                    border-color: #800000 !important;
                }
            </style>

            <!-- Warning Icon and Message -->
            <div style="text-align: center; margin: 0 0 10px 0;">
                <div style="font-size: 4rem; color: #ff3636; margin-bottom: 20px;">⚠️</div>
                <div style="font-size: 1.2rem; color: #333; margin-bottom: 10px; font-weight: bold;">Are you sure?</div>
                <div style="font-size: 1rem; color: #666; line-height: 1.5;">
                    This action cannot be undone. The subject will be permanently deleted.
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="modal-buttons" style="display: flex; gap: 9.6px; justify-content: center; margin-top: 9.6px;">
                <button type="submit" class="modal-btn delete"
                    style="min-width: 128px; background: transparent; color: #ff3636; border: 1.6px solid #ff3636; border-radius: 8px; transition: background-color 0.15s ease, color 0.15s ease;">Delete
                    Subject</button>
                <button type="button" class="modal-btn cancel"
                    style="min-width: 128px; background: #fff; color: #800000; border: 1.6px solid #800000; border-radius: 6.4px;"
                    onclick="closeModal('deleteSubjectModal')">Cancel</button>
            </div>
        </form>
    </div>
@endsection

@section('scripts')
    <script>
        (function() {
            const searchInput = document.getElementById('subjectSearch');
            if (!searchInput) return;
            searchInput.addEventListener('input', function() {
                const term = (this.value || '').toLowerCase();
                const rows = document.querySelectorAll('.subject-table tbody tr');
                let anyVisible = false;
                rows.forEach(row => {
                    if (row.classList.contains('no-results')) return;
                    const text = row.textContent.toLowerCase();
                    if (text.includes(term)) {
                        row.style.display = '';
                        anyVisible = true;
                    } else {
                        row.style.display = 'none';
                    }
                });

                const tbody = document.querySelector('.subject-table tbody');
                if (!tbody) return;
                let noRow = tbody.querySelector('.no-results');
                if (!anyVisible) {
                    if (!noRow) {
                        noRow = document.createElement('tr');
                        noRow.classList.add('no-results');
                        noRow.innerHTML =
                            `<td colspan="5" style="text-align:center; padding:20px; color:#999; font-style:italic;">No results found</td>`;
                        tbody.appendChild(noRow);
                    }
                } else {
                    if (noRow) noRow.remove();
                }
            });
        })();

        function openModal(id) {
            const el = document.getElementById(id);
            if (el) el.style.display = 'flex';
            
            // Initialize button states
            if (id === 'addSubjectModal') {
                updateAddButtonState(false);
                validateAdd();
            } else if (id === 'updateSubjectModal') {
                updateUpdateButtonState(false);
                validateUpdate();
            }
        }

        function closeModal(id) {
            const el = document.getElementById(id);
            if (el) el.style.display = 'none';
        }

        function openUpdateModal(id) {
            const row = document.querySelector(`tr[data-id='${id}']`);
            if (!row) return openModal('updateSubjectModal');
            const form = document.getElementById('updateSubjectForm');
            if (form) {
                form.action = `/deptHead/subjects/${id}`;
                const codeEl = form.querySelector("[name='subject_code']");
                const descEl = form.querySelector("[name='subject_description']");
                const deptEl = form.querySelector("[name='department']");
                const origCode = (row.querySelector('.subject-code')?.innerText || '').trim();
                const origDesc = (row.querySelector('.subject-desc')?.innerText || '').trim();
                const origDept = (row.querySelector('.subject-dept')?.innerText || '').trim();
                codeEl.value = origCode;
                codeEl.dataset.original = origCode;
                descEl.value = origDesc;
                descEl.dataset.original = origDesc;
                deptEl.value = origDept;
                deptEl.dataset.original = origDept;
            }
            openModal('updateSubjectModal');
        }

        function openDeleteModal(id) {
            const form = document.getElementById('deleteSubjectForm');
            if (form) {
                form.action = `/deptHead/subjects/${id}`;
            }
            openModal('deleteSubjectModal');
        }

        // Close modals when clicking on overlay
        document.addEventListener('click', function(e) {
            if (e.target.classList && e.target.classList.contains('modal-overlay')) {
                e.target.style.display = 'none';
            }
        });

        // =========================
        // Client-side Validation (Subject Add/Update) + SweetAlert2 feedback
        // =========================
        (function() {
            // Load SweetAlert2 if not present
            (function ensureSwal() {
                if (window.Swal) return;
                var s = document.createElement('script');
                s.src = 'https://cdn.jsdelivr.net/npm/sweetalert2@11';
                document.head.appendChild(s);
            })();

            // SweetAlert2 helpers for consistent feedback
            function showError(title, text) {
                if (!window.Swal) return;
                Swal.fire({ icon: 'error', title: title || 'Error', text: text || '', confirmButtonColor: '#8B0000' });
            }

            function showInfo(title, text) {
                if (!window.Swal) return;
                Swal.fire({ icon: 'info', title: title || 'Info', text: text || '', confirmButtonColor: '#8B0000' });
            }

            async function confirmDelete(options) {
                if (!window.Swal) return { isConfirmed: true };
                return await Swal.fire({
                    icon: 'warning',
                    title: (options && options.title) || 'Delete Subject?',
                    text: (options && options.text) || 'This action cannot be undone.',
                    showCancelButton: true,
                    confirmButtonText: (options && options.confirmText) || 'Delete',
                    cancelButtonText: (options && options.cancelText) || 'Cancel',
                    confirmButtonColor: '#ff3636',
                    cancelButtonColor: '#800000'
                });
            }

            function trim(v) {
                return (v || '').trim();
            }

            function isNotEmpty(v) {
                return trim(v).length > 0;
            }

            function setValidity(el, ok) {
                if (!el) return;
                const show = el.dataset.touched === 'true' || window.smSubmitAttempt === true;
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
                const show = el.dataset.touched === 'true' || window.smSubmitAttempt === true;
                m.textContent = show ? (msg || '') : '';
                m.style.color = '#ff3636';
                m.style.fontSize = '0.85rem';
                m.style.marginTop = '2px';
            }

            function validateAdd() {
                const code = document.querySelector("#addSubjectModal [name='subject_code']");
                const desc = document.querySelector("#addSubjectModal [name='subject_description']");
                const dept = document.querySelector("#addSubjectModal [name='department']");
                const vCode = isNotEmpty(code && code.value);
                const vDesc = isNotEmpty(desc && desc.value);
                const vDept = isNotEmpty(dept && dept.value);
                
                // Check for duplicate subjects (check stored result)
                let duplicateOk = true;
                const logicBox = document.querySelector('#addSubjectModal .logic-error');
                if (logicBox) logicBox.style.display = 'none';
                
                if (vCode && vDesc && vDept) {
                    const duplicateMessage = window.lastDuplicateCheckAdd || null;
                    if (duplicateMessage) {
                        duplicateOk = false;
                        if (logicBox) {
                            logicBox.textContent = duplicateMessage;
                            logicBox.style.display = 'block';
                        }
                    }
                }
                
                setValidity(code, vCode);
                setMessage(code, vCode ? '' : 'Subject code is required');
                setValidity(desc, vDesc);
                setMessage(desc, vDesc ? '' : 'Description is required');
                setValidity(dept, vDept);
                setMessage(dept, vDept ? '' : 'Department is required');
                
                const isValid = vCode && vDesc && vDept && duplicateOk;
                updateAddButtonState(isValid);
                return isValid;
            }

            // Real-time duplicate checking functions
            async function checkDuplicateAdd() {
                const code = document.querySelector("#addSubjectModal [name='subject_code']");
                const desc = document.querySelector("#addSubjectModal [name='subject_description']");
                const dept = document.querySelector("#addSubjectModal [name='department']");
                
                if (!code || !desc || !dept || !code.value || !desc.value || !dept.value) {
                    window.lastDuplicateCheckAdd = null;
                    // Clear logic error box
                    const logicBox = document.querySelector('#addSubjectModal .logic-error');
                    if (logicBox) {
                        logicBox.style.display = 'none';
                        logicBox.textContent = '';
                    }
                    return null;
                }

                try {
                    const response = await fetch('/deptHead/subjects/check-duplicate', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            subject_code: code.value,
                            subject_description: desc.value,
                            department: dept.value
                        })
                    });

                    const data = await response.json();
                    const duplicateMessage = data.is_duplicate ? data.message : null;
                    window.lastDuplicateCheckAdd = duplicateMessage;
                    validateAdd(); // Re-validate after getting result
                    return duplicateMessage;
                } catch (error) {
                    console.error('Error checking duplicate:', error);
                    window.lastDuplicateCheckAdd = null;
                    return null;
                }
            }

            async function checkDuplicateUpdate() {
                const code = document.querySelector("#updateSubjectModal [name='subject_code']");
                const desc = document.querySelector("#updateSubjectModal [name='subject_description']");
                const dept = document.querySelector("#updateSubjectModal [name='department']");
                const form = document.getElementById('updateSubjectForm');
                const currentId = form ? form.action.split('/').pop() : null;
                
                if (!code || !desc || !dept || !code.value || !desc.value || !dept.value) {
                    window.lastDuplicateCheckUpdate = null;
                    // Clear logic error box
                    const logicBox = document.querySelector('#updateSubjectModal .logic-error');
                    if (logicBox) {
                        logicBox.style.display = 'none';
                        logicBox.textContent = '';
                    }
                    return null;
                }

                try {
                    const response = await fetch('/deptHead/subjects/check-duplicate', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            subject_code: code.value,
                            subject_description: desc.value,
                            department: dept.value,
                            exclude_id: currentId
                        })
                    });

                    const data = await response.json();
                    const duplicateMessage = data.is_duplicate ? data.message : null;
                    window.lastDuplicateCheckUpdate = duplicateMessage;
                    validateUpdate(); // Re-validate after getting result
                    return duplicateMessage;
                } catch (error) {
                    console.error('Error checking duplicate:', error);
                    window.lastDuplicateCheckUpdate = null;
                    return null;
                }
            }

            function bindRealTime(modalId) {
                ['subject_code', 'subject_description', 'department'].forEach(name => {
                    const el = document.querySelector(`${modalId} [name='${name}']`);
                    if (!el) return;
                    const evt = el.tagName === 'SELECT' ? 'change' : 'input';
                    el.addEventListener(evt, function() {
                        if (modalId === '#addSubjectModal') {
                            validateAdd();
                            // Trigger duplicate check when all fields are filled
                            const code = document.querySelector("#addSubjectModal [name='subject_code']");
                            const desc = document.querySelector("#addSubjectModal [name='subject_description']");
                            const dept = document.querySelector("#addSubjectModal [name='department']");
                            if (code && desc && dept && code.value && desc.value && dept.value) {
                                checkDuplicateAdd();
                            }
                        } else if (modalId === '#updateSubjectModal') {
                            validateUpdate();
                            // Trigger duplicate check when all fields are filled
                            const code = document.querySelector("#updateSubjectModal [name='subject_code']");
                            const desc = document.querySelector("#updateSubjectModal [name='subject_description']");
                            const dept = document.querySelector("#updateSubjectModal [name='department']");
                            if (code && desc && dept && code.value && desc.value && dept.value) {
                                checkDuplicateUpdate();
                            }
                        }
                    });
                    el.addEventListener('blur', function() {
                        this.dataset.touched = 'true';
                        if (modalId === '#addSubjectModal') {
                            validateAdd();
                        } else if (modalId === '#updateSubjectModal') {
                            validateUpdate();
                        }
                    });
                });
            }
            bindRealTime('#addSubjectModal');
            bindRealTime('#updateSubjectModal');

            // Button state management functions
            function updateAddButtonState(isValid) {
                const addButton = document.querySelector('#addSubjectModal .modal-btn.add');
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

            function updateUpdateButtonState(isValid) {
                const updateButton = document.querySelector('#updateSubjectModal .modal-btn.add');
                if (updateButton) {
                    updateButton.disabled = !isValid;
                    if (isValid) {
                        updateButton.style.opacity = '1';
                        updateButton.style.cursor = 'pointer';
                    } else {
                        updateButton.style.opacity = '0.6';
                        updateButton.style.cursor = 'not-allowed';
                    }
                }
            }

            function validateUpdate() {
                const code = document.querySelector("#updateSubjectModal [name='subject_code']");
                const desc = document.querySelector("#updateSubjectModal [name='subject_description']");
                const dept = document.querySelector("#updateSubjectModal [name='department']");
                const vCode = isNotEmpty(code && code.value);
                const vDesc = isNotEmpty(desc && desc.value);
                const vDept = isNotEmpty(dept && dept.value);
                
                // Check for duplicate subjects (check stored result)
                let duplicateOk = true;
                const logicBox = document.querySelector('#updateSubjectModal .logic-error');
                if (logicBox) logicBox.style.display = 'none';
                
                if (vCode && vDesc && vDept) {
                    const duplicateMessage = window.lastDuplicateCheckUpdate || null;
                    if (duplicateMessage) {
                        duplicateOk = false;
                        if (logicBox) {
                            logicBox.textContent = duplicateMessage;
                            logicBox.style.display = 'block';
                        }
                    }
                }
                
                setValidity(code, vCode);
                setMessage(code, vCode ? '' : 'Subject code is required');
                setValidity(desc, vDesc);
                setMessage(desc, vDesc ? '' : 'Description is required');
                setValidity(dept, vDept);
                setMessage(dept, vDept ? '' : 'Department is required');
                
                const isValid = vCode && vDesc && vDept && duplicateOk;
                updateUpdateButtonState(isValid);
                return isValid;
            }

            const addForm = document.querySelector('#addSubjectModal form');
            if (addForm) {
                addForm.addEventListener('submit', function(e) {
                    window.smSubmitAttempt = true;
                    if (!validateAdd()) {
                        e.preventDefault();
                        showError('Incomplete fields', 'Please fill out Subject Code, Description, and Department.');
                    } else {
                        // optional success hint prior to submit
                    }
                });
            }

            // Update form: block submission if nothing changed
            (function() {
                const updForm = document.getElementById('updateSubjectForm');
                if (!updForm) return;
                updForm.addEventListener('submit', function(e) {
                    window.smSubmitAttempt = true;
                    if (!validateUpdate()) {
                        e.preventDefault();
                        showError('Validation Error', 'Please fix all errors before submitting.');
                        return;
                    }
                    
                    const code = updForm.querySelector("[name='subject_code']");
                    const desc = updForm.querySelector("[name='subject_description']");
                    const dept = updForm.querySelector("[name='department']");
                    const unchanged = (trim(code.value) === trim(code.dataset.original || '')) &&
                                      (trim(desc.value) === trim(desc.dataset.original || '')) &&
                                      (trim(dept.value) === trim(dept.dataset.original || ''));
                    if (unchanged) {
                        e.preventDefault();
                        showInfo('No changes detected', 'Update at least one field before submitting.');
                        return;
                    }
                });
            })();

            // Delete Subject - no additional confirmation needed (modal already has confirmation)
            (function() {
                const delForm = document.getElementById('deleteSubjectForm');
                if (!delForm) return;
                delForm.addEventListener('submit', function(e) {
                    // Allow normal form submission - the modal already provides confirmation
                    // No need for additional SweetAlert2 confirmation
                });
            })();
        })();
    </script>
@endsection
