@extends('layouts.appdeptHead')

@section('title', 'Faculty Account Management - Tagoloan Community College')
@section('files-active', 'active')
@section('faculty-account-active', 'active')

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

        /* Keep table header visible while scrolling */
        .faculty-table thead th {
            position: sticky;
            top: 0;
            z-index: 2;
        }

        .faculty-table td {
            padding: 12px 8px;
            text-align: center;
            font-size: 1rem;
            border: none;
            vertical-align: middle;
        }

        .faculty-table th:nth-child(5),
        .faculty-table td:nth-child(5) {
            width: 80px;
        }

        .faculty-table th:nth-child(6),
        .faculty-table td:nth-child(6) {
            width: 120px;
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

        /* ====== Action Buttons ====== */
        .action-btns {
            display: flex;
            gap: 8px;
            justify-content: center;
            align-items: center;
        }

        .view-btn,
        .edit-btn,
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

        .view-btn {
            background: #666;
            color: #fff;
        }

        .edit-btn {
            background: #7cc6fa;
            color: #fff;
        }

        .delete-btn {
            background: #ff3636;
            color: #fff;
        }

        .view-btn:active,
        .edit-btn:active,
        .delete-btn:active {
            box-shadow: 0 0 0 2px #2222;
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
            padding: 40px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.22), 0 1.5px 8px rgba(0, 0, 0, 0.12);
            display: flex;
            flex-direction: column;
            align-items: center;
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
            margin-bottom: 20px;
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
            font-size: 1rem;
            color: #222;
            margin-bottom: 0;
        }

        .modal-form-group input,
        .modal-form-group select,
        .modal-form-group textarea {
            flex: 1;
            padding: 10px 12px;
            border-radius: 5px;
            border: 1px solid #bbb;
            font-size: 1rem;
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

        .modal-btn {
            width: 100%;
            padding: 12px 0;
            font-size: 1rem;
            font-weight: bold;
            border-radius: 10px;
            cursor: pointer;
            margin-top: 10px;
        }

        .modal-btn.add {
            background-color: #50e25d;
            color: #fff;
            border: none;
        }

        .modal-btn.add:hover {
            background-color: #45cc52;
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
        }

        .modal-btn.cancel:hover {
            background: #800000;
            color: #fff;
        }

        /* ====== Image Upload Styles ====== */
        .modal-img-box {
            border: 2px dashed #222;
            border-radius: 10px;
            width: 100%;
            height: 200px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            position: relative;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #fafafa;
        }

        .modal-img-box.dragover {
            border-color: #8B0000;
            background: #fff5f5;
            transform: scale(1.02);
        }

        .modal-img-box img {
            max-width: 100%;
            max-height: 100%;
            border-radius: 8px;
            object-fit: cover;
        }

        .upload-text {
            text-align: center;
            color: #666;
            font-size: 0.9rem;
        }

        /* ====== View Images Modal Styles ====== */
        .view-images-container {
            max-height: 500px;
            overflow-y: auto;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background: #f9f9f9;
        }

        .images-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-bottom: 10px;
        }

        .image-item {
            position: relative;
            width: 100%;
            height: 120px;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s ease;
        }

        .image-item:hover {
            transform: scale(1.05);
        }

        .image-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 8px;
        }

        .image-counter {
            position: absolute;
            top: 5px;
            right: 5px;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 0.8rem;
            font-weight: bold;
        }

        /* Responsive design for smaller screens */
        @media (max-width: 768px) {
            .images-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .view-images-container {
                max-height: 400px;
            }

            .image-item {
                height: 100px;
            }
        }

        @media (max-width: 480px) {
            .images-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 8px;
            }

            .image-item {
                height: 80px;
            }
        }

        /* ====== Delete Modal Warning Styles ====== */
        .delete-warning {
            text-align: center;
            margin: 30px 0;
        }

        .warning-icon {
            font-size: 4rem;
            color: #ff3636;
            margin-bottom: 20px;
        }

        .warning-title {
            font-size: 1.2rem;
            color: #333;
            margin-bottom: 10px;
            font-weight: bold;
        }

        .warning-message {
            font-size: 1rem;
            color: #666;
            line-height: 1.5;
        }
    </style>
@endsection

@section('content')
    @include('partials.flash')
    @include('partials.flashupdate')
    @include('partials.flashdelete')
    <div class="faculty-header">
        <div class="faculty-title-group">
            <div class="faculty-title">Faculty Management</div>
            <div class="faculty-subtitle"></div>
        </div>
        <div class="faculty-actions-row">
            <input type="text" class="search-input" placeholder="Search...">
            <button class="add-btn" onclick="openModal('addFacultyModal')">Add</button>
        </div>
    </div>

    <div class="faculty-table-container">
        <div class="faculty-table-scroll">
            <table class="faculty-table">
                <thead>
                    <tr>
                        <th>Faculty ID</th>
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Department</th>
                        <th>Images</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($faculties as $faculty)
                        <tr>
                            <td>{{ $faculty->faculty_id }}</td>
                            <td>{{ $faculty->faculty_fname }}</td>
                            <td>{{ $faculty->faculty_lname }}</td>
                            <td>{{ $faculty->faculty_department }}</td>
                            <td>
                                <button class="view-btn"
                                    onclick='openViewImageModal(@json($faculty->faculty_images))'>&#128065;</button>
                            </td>
                            <td>
                                <div class="action-btns">
                                    <button class="edit-btn"
                                        onclick="openUpdateModal({{ $faculty->faculty_id }})">&#9998;</button>
                                    <button class="delete-btn"
                                        onclick="openDeleteModal({{ $faculty->faculty_id }})">&#128465;</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align:center; font-style:italic; color:#666;">
                                No Registered Faculty found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add Faculty Modal -->
    <div id="addFacultyModal" class="modal-overlay" style="display:none;">
        <div class="modal-box" style="padding: 0; overflow: hidden; border-radius: 8px;">
            <form method="POST" enctype="multipart/form-data" action="{{ route('deptHead.faculty.store') }}"
                style="padding: 0;">
                @csrf
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
                    ADD FACULTY</div>

                <div class="modal-form" style="padding: 24px 24px 24px;">
                    <style>
                        /* Scoped to Add Faculty modal */
                        #addFacultyModal .modal-form-group {
                            display: flex;
                            flex-direction: row;
                            align-items: center;
                            gap: 6px;
                            margin-bottom: 4px;
                            padding-bottom: 6px;
                            position: relative;
                        }

                        #addFacultyModal .modal-form-group label {
                            min-width: 130px;
                            text-align: left;
                            margin-bottom: 0;
                            font-size: 1rem;
                            color: #222;
                        }

                        #addFacultyModal .modal-form-group input,
                        #addFacultyModal .modal-form-group select,
                        #addFacultyModal .modal-form-group textarea {
                            flex: 1;
                            width: 100%;
                            padding: 10px 12px;
                            font-size: 1rem;
                            border: 1px solid #bbb;
                            border-radius: 5px;
                        }

                        #addFacultyModal .validation-message {
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

                        #addFacultyModal .modal-buttons {
                            display: flex;
                            gap: 12px;
                            justify-content: center;
                            margin-top: 12px;
                        }

                        #addFacultyModal .modal-btn.add {
                            background: transparent !important;
                            border: 2px solid #2e7d32 !important;
                            color: #2e7d32 !important;
                        }

                        #addFacultyModal .modal-btn.add:hover {
                            background: #2e7d32 !important;
                            color: #fff !important;
                            border-color: #2e7d32 !important;
                        }
                    </style>

                    <div class="modal-form-group">
                        <label for="faculty_fname">First Name :</label>
                        <input type="text" id="faculty_fname" name="faculty_fname">
                    </div>
                    <div class="modal-form-group">
                        <label for="faculty_lname">Last Name :</label>
                        <input type="text" id="faculty_lname" name="faculty_lname">
                    </div>
                    <div class="modal-form-group">
                        <label for="faculty_department">Department :</label>
                        <select id="faculty_department" name="faculty_department">
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
                    </div>
                    <div class="modal-form-group">
                        <label for="faculty_images">Images :</label>
                        <input type="file" id="faculty_images" name="faculty_images[]" accept="image/*" multiple>
                        <div class="validation-message" id="faculty_images_error"></div>
                    </div>

                    <div class="modal-buttons">
                        <button type="submit" class="modal-btn add">Add</button>
                        <button type="button" class="modal-btn cancel"
                            onclick="closeModal('addFacultyModal')">Cancel</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Update Faculty Modal -->
    <div id="updateFacultyModal" class="modal-overlay" style="display:none;">
        <div class="modal-box" style="padding: 0; overflow: hidden; border-radius: 8px;">
            <form id="updateFacultyForm" method="POST" enctype="multipart/form-data" style="padding: 0;">
                @csrf
                @method('PUT')
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
                    UPDATE FACULTY</div>

                <div class="modal-form" style="padding: 24px 24px 24px;">
                    <style>
                        /* Scoped to Update Faculty modal */
                        #updateFacultyModal .modal-form-group {
                            display: flex;
                            flex-direction: row;
                            align-items: center;
                            gap: 6px;
                            margin-bottom: 4px;
                            padding-bottom: 6px;
                            position: relative;
                        }

                        #updateFacultyModal .modal-form-group label {
                            min-width: 130px;
                            text-align: left;
                            margin-bottom: 0;
                            font-size: 1rem;
                        }

                        #updateFacultyModal .modal-form-group input,
                        #updateFacultyModal .modal-form-group select,
                        #updateFacultyModal .modal-form-group textarea {
                            flex: 1;
                            width: 100%;
                            padding: 10px 12px;
                            font-size: 1rem;
                            border: 1px solid #bbb;
                            border-radius: 5px;
                        }

                        #updateFacultyModal .validation-message {
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

                        #updateFacultyModal .modal-buttons {
                            display: flex;
                            gap: 12px;
                            justify-content: center;
                            margin-top: 12px;
                        }

                        #updateFacultyModal .modal-btn.add {
                            background: transparent !important;
                            border: 2px solid #7cc6fa !important;
                            color: #7cc6fa !important;
                        }

                        #updateFacultyModal .modal-btn.add:hover {
                            background: #7cc6fa !important;
                            color: #fff !important;
                            border-color: #7cc6fa !important;
                        }
                    </style>

                    <div class="modal-form-group">
                        <label for="update_faculty_fname">First Name :</label>
                        <input type="text" id="update_faculty_fname" name="faculty_fname">
                    </div>
                    <div class="modal-form-group">
                        <label for="update_faculty_lname">Last Name :</label>
                        <input type="text" id="update_faculty_lname" name="faculty_lname">
                    </div>
                    <div class="modal-form-group">
                        <label for="update_faculty_department">Department :</label>
                        <select id="update_faculty_department" name="faculty_department">
                            <option value="">Select Department</option>
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
                    </div>
                    <div class="modal-form-group">
                        <label for="update_faculty_images">Images :</label>
                        <input type="file" id="update_faculty_images" name="faculty_images[]" accept="image/*"
                            multiple>
                        <div class="validation-message" id="update_faculty_images_error"></div>
                    </div>
                    <div class="modal-buttons">
                        <button type="submit" class="modal-btn add">Update</button>
                        <button type="button" class="modal-btn cancel"
                            onclick="closeModal('updateFacultyModal')">Cancel</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <!-- Delete Faculty Modal -->
    <div id="deleteFacultyModal" class="modal-overlay" style="display:none;">
        <form id="deleteFacultyForm" method="POST" class="modal-box">
            @csrf
            @method('DELETE')
            <div class="modal-header delete">DELETE FACULTY</div>

            <!-- Warning Icon and Message -->
            <div style="text-align: center; margin:0 px 0;">
                <div style="font-size: 4rem; color: #ff3636; margin-bottom: 20px;">⚠️</div>
                <div style="font-size: 1.2rem; color: #333; margin-bottom: 10px; font-weight: bold;">Are you sure?</div>
                <div style="font-size: 1rem; color: #666; line-height: 1.5;">
                    This action cannot be undone. The faculty will be permanently deleted.
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="modal-buttons" style="display: flex; gap: 12px; justify-content: center; margin-top: 12px;">
                <button type="submit" class="modal-btn delete" style="min-width: 140px;">Delete Faculty</button>
                <button type="button" class="modal-btn cancel" style="min-width: 140px;"
                    onclick="closeModal('deleteFacultyModal')">Cancel</button>
            </div>
        </form>
    </div>


    <!-- View Images Modal -->
    <div id="viewImagesModal" class="modal-overlay" style="display:none;">
        <div class="modal-box" style="width: 600px; max-width: 95vw;">
            <div class="modal-header-custom">FACULTY IMAGES</div>
            <div class="view-images-container">
                <div id="viewImagesContainer" class="images-grid"></div>
            </div>
            <button type="button" class="modal-btn cancel" onclick="closeModal('viewImagesModal')">Close</button>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        function openModal(id) {
            document.getElementById(id).style.display = 'flex';
        }

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

            window.facultySubmitAttempt = false;
        }

        function closeModal(id) {
            const modal = document.getElementById(id);
            if (modal) modal.style.display = 'none';
            if (id === 'addFacultyModal' || id === 'updateFacultyModal') {
                resetModalForm(id);
            }
        }

        function openUpdateModal(id) {
            let faculty = @json($faculties).find(f => f.faculty_id === id);
            if (faculty) {
                document.getElementById('update_faculty_fname').value = faculty.faculty_fname;
                document.getElementById('update_faculty_lname').value = faculty.faculty_lname;
                document.getElementById('update_faculty_department').value = faculty.faculty_department;
                document.getElementById('updateFacultyForm').action = `/deptHead/faculty/${id}`;
                openModal('updateFacultyModal');
            }
        }

        function openDeleteModal(id) {
            document.getElementById('deleteFacultyForm').action = `/deptHead/faculty/${id}`;
            openModal('deleteFacultyModal');
        }

        function openViewImageModal(images) {
            let imagePaths = typeof images === 'string' ? JSON.parse(images) : images;
            let container = document.getElementById('viewImagesContainer');
            container.innerHTML = ''; // clear previous images

            imagePaths.forEach((path, index) => {
                let imageItem = document.createElement('div');
                imageItem.className = 'image-item';

                let img = document.createElement('img');
                img.src = '/storage/' + path;
                img.alt = `Faculty Image ${index + 1}`;
                img.loading = 'lazy';

                let counter = document.createElement('div');
                counter.className = 'image-counter';
                counter.textContent = index + 1;

                imageItem.appendChild(img);
                imageItem.appendChild(counter);
                container.appendChild(imageItem);
            });

            openModal('viewImagesModal');
        }
        // Close + reset when clicking outside (overlay)
        document.addEventListener('click', function(e) {
            if (e.target.classList && e.target.classList.contains('modal-overlay')) {
                const overlayId = e.target.id;
                e.target.style.display = 'none';
                if (overlayId === 'addFacultyModal' || overlayId === 'updateFacultyModal') {
                    resetModalForm(overlayId);
                }
            }
        });
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
                        `<td colspan="6" style="text-align:center; padding:20px; color:#999; font-style:italic;">No results found</td>`;
                    tbody.appendChild(noResultsRow);
                }
            } else {
                if (noResultsRow) noResultsRow.remove();
            }
        });

        // =========================
        // Client-side Validation (Faculty forms)
        // =========================
        (function() {
            function trim(v) {
                return (v || '').trim();
            }

            function isNotEmpty(v) {
                return trim(v).length > 0;
            }

            function isLetters(v) {
                return /^[-' a-zA-Z]+$/.test(trim(v));
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
                const shouldDisplay = el.dataset.touched === 'true' || window.facultySubmitAttempt === true;
                el.classList.remove('valid', 'invalid');
                if (!shouldDisplay) return;
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
                const shouldDisplay = el.dataset.touched === 'true' || window.facultySubmitAttempt === true;
                m.textContent = shouldDisplay ? (msg || '') : '';
            }

        
            function validateAdd() {
                const fn = document.getElementById('faculty_fname');
                const ln = document.getElementById('faculty_lname');
                const dp = document.getElementById('faculty_department');
                const img = document.getElementById('faculty_images');
                
                // Basic field validations
                const vfn = isNotEmpty(fn && fn.value) && isLetters(fn && fn.value);
                const vln = isNotEmpty(ln && ln.value) && isLetters(ln && ln.value);
                const vdp = isNotEmpty(dp && dp.value);
                
                // Image validation - check if images are selected and valid size
                const hasImages = img && img.files && img.files.length > 0;
                const validImageSize = validateImageSize(img);
                const vimg = hasImages && validImageSize;
                
                // Set field validations
                setValidity(fn, vfn);
                setMessage(fn, vfn ? '' : (isNotEmpty(fn && fn.value) ? 'First name is invalid' : 'First name is required'));
                
                setValidity(ln, vln);
                setMessage(ln, vln ? '' : (isNotEmpty(ln && ln.value) ? 'Last name is invalid' : 'Last name is required'));
                
                setValidity(dp, vdp);
                setMessage(dp, vdp ? '' : 'Department is required');
                
                // Set image validation with proper error messages
                setValidity(img, vimg);
                if (!hasImages) {
                    setMessage(img, 'Image is required');
                } else if (!validImageSize) {
                    setMessage(img, 'Image size must be less than 2MB');
                } else {
                    setMessage(img, '');
                }
                
                return vfn && vln && vdp && vimg;
            }

            function validateUpdate() {
                const fn = document.getElementById('update_faculty_fname');
                const ln = document.getElementById('update_faculty_lname');
                const dp = document.getElementById('update_faculty_department');
                const img = document.getElementById('update_faculty_images');
                const vfn = isNotEmpty(fn && fn.value) && isLetters(fn && fn.value);
                const vln = isNotEmpty(ln && ln.value) && isLetters(ln && ln.value);
                const vdp = isNotEmpty(dp && dp.value);
                const vimg = validateImageSize(img);
                setValidity(fn, vfn);
                setMessage(fn, vfn ? '' : (isNotEmpty(fn && fn.value) ? 'First name is invalid' :
                    'First name is required'));
                setValidity(ln, vln);
                setMessage(ln, vln ? '' : (isNotEmpty(ln && ln.value) ? 'Last name is invalid' :
                    'Last name is required'));
                setValidity(dp, vdp);
                setMessage(dp, vdp ? '' : 'Department is required');
                setValidity(img, vimg);
                setMessage(img, vimg ? '' : 'Image size must be less than 2MB');
                return vfn && vln && vdp && vimg;
            }

            ['#faculty_fname', '#faculty_lname', '#faculty_department', '#faculty_images'].forEach(sel => {
                const el = document.querySelector(sel);
                if (!el) return;
                const evt = el.tagName === 'SELECT' ? 'change' : (el.type === 'file' ? 'change' : 'input');
                el.addEventListener(evt, validateAdd);
                el.addEventListener('blur', () => {
                    el.dataset.touched = 'true';
                    validateAdd();
                });
            });
            ['#update_faculty_fname', '#update_faculty_lname', '#update_faculty_department', '#update_faculty_images'].forEach(sel => {
                const el = document.querySelector(sel);
                if (!el) return;
                const evt = el.tagName === 'SELECT' ? 'change' : (el.type === 'file' ? 'change' : 'input');
                el.addEventListener(evt, validateUpdate);
                el.addEventListener('blur', () => {
                    el.dataset.touched = 'true';
                    validateUpdate();
                });
            });

            (function() {
                const addForm = document.querySelector('#addFacultyModal form');
                if (addForm) {
                    addForm.addEventListener('submit', function(e) {
                        window.facultySubmitAttempt = true;
                        if (!validateAdd()) {
                            e.preventDefault();
                        }
                    });
                }
                const updForm = document.getElementById('updateFacultyForm');
                if (updForm) {
                    updForm.addEventListener('submit', function(e) {
                        window.facultySubmitAttempt = true;
                        if (!validateUpdate()) {
                            e.preventDefault();
                        }
                    });
                }
            })();
        })();
    </script>
@endsection
