@extends('layouts.appdeptHead')

@section('title', 'Faculty Account Management - Tagoloan Community College')
@section('files-active', 'active')
@section('faculty-account-active', 'active')

@section('styles')
    <style>

       
        /* ====== Header & Actions ====== */
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
            margin-bottom: 24px;
        }

        .faculty-actions-row {
            display: flex;
            gap: 8px;
            position: absolute;
            top: 104px;
            right: 32px;
            z-index: 100;
        }

        .search-input {
            padding: 6.4px;
            font-size: 11.2px;
            border: 1px solid #ccc;
            border-radius: 3.2px;
            width: 320px;
        }

        .add-btn {
            padding: 6.4px 19.2px;
            font-size: 11.2px;
            border: none;
            border-radius: 3.2px;
            background-color: #2ecc71;
            color: #fff;
            cursor: pointer;
            font-weight: bold;
        }

        /* ====== Table Styles ====== */
        .faculty-table-container {
            background: #fff;
            border-radius: 8px;
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
            padding: 12px;
            font-size: 0.9rem;
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
            padding: 12px;
            text-align: center;
            font-size: 0.85rem;
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

        .faculty-row:hover {
            background: #e8f4fd !important;
            transform: scale(1.01);
            transition: all 0.2s ease;
        }

        /* Disable hover transform on touch devices */
        @media (hover: none) {
            .faculty-row:hover {
                transform: none;
            }
        }

        /* Make only the table area scroll vertically */
        .faculty-table-scroll {
            max-height: 536px;
            overflow-y: auto;
            width: 100%;
            -webkit-overflow-scrolling: touch;
        }

        /* ====== Action Buttons ====== */
        .action-btns {
            display: flex;
            gap: 6px;
            justify-content: center;
            align-items: center;
        }

        .view-btn,
        .edit-btn,
        .delete-btn {
            width: 35px;
            height: 28px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
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
            border-radius: 8px;
            width: 360px;
            max-width: 95vw;
            padding: 0px;
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
            border-top-left-radius: 9.6px;
            border-top-right-radius: 9.6px;
            padding: 12px 16px;
            font-size: 1.2rem;
            letter-spacing: 0.8px;
            width: 105%;
            margin-bottom: 16px;
        }

        .modal-form-group {
            display: flex;
            flex-direction: row;
            align-items: center;
            gap: 9.6px;
            margin-bottom: 9.6px;
            position: relative;
            padding-bottom: 14.4px;
        }

        .modal-form-group label {
            min-width: 104px;
            text-align: left;
            font-size: 0.8rem;
            color: #222;
            margin-bottom: 0;
        }

        .modal-form-group input,
        .modal-form-group select,
        .modal-form-group textarea {
            flex: 1;
            padding: 8px 9.6px;
            border-radius: 4px;
            border: 1px solid #bbb;
            font-size: 0.8rem;
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
            left: 104px;
            right: 9.6px;
            bottom: 0;
            font-size: 0.68rem;
            color: #ff3636;
            pointer-events: none;
            padding-left: 9.6px;
        }

        .modal-btn {
            width: 100%;
            padding: 9.6px 0;
            font-size: 0.8rem;
            font-weight: bold;
            border-radius: 8px;
            cursor: pointer;
            margin-top: 8px;
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

        /* ====== Mobile Responsive Styles ====== */
        
        /* Tablet and below (768px) */
        @media (max-width: 768px) {
            /* Header adjustments */

            
            .faculty-title {
                font-size: 1.5rem;
            }

            .faculty-subtitle {
                font-size: 0.75rem;
                margin-bottom: 16px;
            }

            /* Actions row - stack on small screens */
            .faculty-actions-row {
                position: relative;
                top: 0;
                right: 0;
                width: 100%;
                flex-direction: row;
                align-items: center;
                gap: 8px;
                z-index: 1;
                padding-bottom: 20px;
            }

            .search-input {
                flex: 0 0 calc(75% - 4px);
                width: calc(75% - 4px);
                padding: 10px 12px;
                font-size: 0.9rem;
                border-radius: 6px;
                box-sizing: border-box;
                margin: 0;
            }

            .add-btn {
                flex: 0 0 calc(25% - 4px);
                width: calc(25% - 4px);
                padding: 12px;
                font-size: 0.9rem;
                border-radius: 6px;
                font-weight: bold;
                text-align: center;
                margin: 0;
            }

            /* Table adjustments */
            .faculty-table-container {
                border-radius: 8px;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }

            .faculty-table-scroll {
                max-height: 500px;
                overflow-x: auto;
                overflow-y: auto;
                -webkit-overflow-scrolling: touch;
            }

            .faculty-table {
                min-width: 600px; /* Ensure table doesn't compress too much */
                font-size: 0.8rem;
            }

            .faculty-table th,
            .faculty-table td {
                padding: 8px 6px;
                font-size: 0.75rem;
            }

            /* Action buttons - smaller on mobile */
            .view-btn,
            .edit-btn,
            .delete-btn {
                width: 32px;
                height: 26px;
                font-size: 0.9rem;
            }

            .action-btns {
                gap: 4px;
            }

            /* Modal adjustments */
            .modal-box {
                width: 90vw !important;
                max-height: 90vh;
                overflow-y: auto;
            }

            .modal-header {
                font-size: 18px !important;
                padding: 14px 16px !important;
            }

            /* Modal form groups - stack on mobile */
            #addFacultyModal .modal-form-group,
            #updateFacultyModal .modal-form-group {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
                margin-bottom: 16px;
                padding-bottom: 20px;
            }

            #addFacultyModal .modal-form-group label,
            #updateFacultyModal .modal-form-group label {
                min-width: 100%;
                font-size: 0.9rem;
                margin-bottom: 4px;
            }

            #addFacultyModal .modal-form-group input,
            #addFacultyModal .modal-form-group select,
            #addFacultyModal .modal-form-group textarea,
            #updateFacultyModal .modal-form-group input,
            #updateFacultyModal .modal-form-group select,
            #updateFacultyModal .modal-form-group textarea {
                width: 100%;
                font-size: 0.9rem;
                padding: 10px;
            }

            #addFacultyModal .validation-message,
            #updateFacultyModal .validation-message {
                left: 0;
                right: 0;
                bottom: 0;
                padding-left: 0;
                position: absolute;
            }

            .modal-form {
                padding: 16px !important;
            }

            .modal-buttons {
                flex-direction: column;
                gap: 8px;
            }

            .modal-btn {
                width: 100%;
            }

            /* Delete modal buttons on mobile */
            #deleteFacultyModal .modal-buttons {
                flex-direction: column;
            }

            #deleteFacultyModal .modal-btn {
                width: 100%;
                min-width: auto !important;
            }

            /* View images modal */
            .images-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .view-images-container {
                max-height: 300px;
                padding: 8px;
            }

            .image-item {
                height: 100px;
            }

            /* View images modal padding */
            #viewImagesModal > .modal-box > div {
                padding: 16px !important;
            }

            /* Teaching loads modal */
            #viewTeachingLoadsModal .modal-box {
                width: 95vw !important;
            }

            #teachingLoadsContainer {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }

            #teachingLoadsTable table {
                min-width: 700px;
                font-size: 0.8rem;
            }

            #teachingLoadsTable th,
            #teachingLoadsTable td {
                padding: 8px 4px;
                font-size: 0.75rem;
            }

            #facultyInfo {
                padding: 12px !important;
            }

            #facultyName {
                font-size: 1rem !important;
            }

            #facultyDepartment {
                font-size: 0.9rem !important;
            }

            /* Teaching loads modal padding */
            #viewTeachingLoadsModal > .modal-box > div {
                padding: 16px !important;
            }
        }

        /* Mobile phones (480px and below) */
        @media (max-width: 480px) {
            /* Header */
            .faculty-title {
                font-size: 1.3rem;
            }

            .faculty-subtitle {
                font-size: 0.7rem;
                margin-bottom: 12px;
            }

            /* Table - more compact */
            .faculty-table {
                min-width: 550px;
                font-size: 0.7rem;
            }

            .faculty-table th,
            .faculty-table td {
                padding: 6px 4px;
                font-size: 0.7rem;
            }

            /* Action buttons - even smaller */
            .view-btn,
            .edit-btn,
            .delete-btn {
                width: 28px;
                height: 24px;
                font-size: 0.85rem;
            }

            /* Modal adjustments for small phones */
            .modal-box {
                width: 95vw !important;
                margin: 10px;
            }

            .modal-header {
                font-size: 16px !important;
                padding: 12px 14px !important;
            }

            .modal-form {
                padding: 12px !important;
            }

            #addFacultyModal .modal-form-group label,
            #updateFacultyModal .modal-form-group label {
                font-size: 0.85rem;
            }

            #addFacultyModal .modal-form-group input,
            #addFacultyModal .modal-form-group select,
            #addFacultyModal .modal-form-group textarea,
            #updateFacultyModal .modal-form-group input,
            #updateFacultyModal .modal-form-group select,
            #updateFacultyModal .modal-form-group textarea {
                font-size: 0.85rem;
                padding: 8px;
            }

            /* Images grid - single column on very small screens */
            .images-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 8px;
            }

            .image-item {
                height: 80px;
            }

            .view-images-container {
                max-height: 250px;
                padding: 8px;
            }

            /* Teaching loads table - more compact */
            #teachingLoadsTable table {
                min-width: 600px;
                font-size: 0.7rem;
            }

            #teachingLoadsTable th,
            #teachingLoadsTable td {
                padding: 6px 3px;
                font-size: 0.7rem;
            }

            /* Delete modal warning */
            .delete-warning {
                margin: 20px 0;
            }

            .warning-icon {
                font-size: 3rem;
                margin-bottom: 15px;
            }

            .warning-title {
                font-size: 1rem;
            }

            .warning-message {
                font-size: 0.9rem;
            }
        }

        /* Very small phones (360px and below) */
        @media (max-width: 360px) {
            .faculty-title {
                font-size: 1.2rem;
            }

            .faculty-table {
                min-width: 500px;
            }

            .modal-header {
                font-size: 14px !important;
                padding: 10px 12px !important;
            }

            #addFacultyModal .modal-form-group label,
            #updateFacultyModal .modal-form-group label {
                font-size: 0.8rem;
            }

            #addFacultyModal .modal-form-group input,
            #addFacultyModal .modal-form-group select,
            #updateFacultyModal .modal-form-group input,
            #updateFacultyModal .modal-form-group select {
                font-size: 0.8rem;
                padding: 8px;
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
    </div>
    <div class="faculty-actions-row">
        <input type="text" class="search-input" placeholder="Search...">
        <button class="add-btn" onclick="openModal('addFacultyModal')">Add</button>
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
                        <tr class="faculty-row" data-faculty-id="{{ $faculty->faculty_id }}" style="cursor: pointer;">
                            <td>{{ $faculty->faculty_id }}</td>
                            <td>{{ $faculty->faculty_fname }}</td>
                            <td>{{ $faculty->faculty_lname }}</td>
                            <td>{{ $faculty->faculty_department }}</td>
                            <td>
                                <button class="view-btn"
                                    onclick='event.stopPropagation(); openViewImageModal(@json($faculty->faculty_images))'>&#128065;</button>
                            </td>
                            <td>
                                <div class="action-btns">
                                    <button class="edit-btn"
                                        onclick="event.stopPropagation(); openUpdateModal({{ $faculty->faculty_id }})">&#9998;</button>
                                    <button class="delete-btn"
                                        onclick="event.stopPropagation(); openDeleteModal({{ $faculty->faculty_id }})">&#128465;</button>
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
        <div class="modal-box" style="padding: 0; overflow: hidden; border-radius: 8px; width: 432px; max-width: 95vw;">
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
        <div class="modal-box" style="padding: 0; overflow: hidden; border-radius: 8px; width: 432px; max-width: 95vw;">
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
                <button type="submit" class="modal-btn delete" style="min-width: 140px; width: auto;">Delete Faculty</button>
                <button type="button" class="modal-btn cancel" style="min-width: 140px; width: auto;"
                    onclick="closeModal('deleteFacultyModal')">Cancel</button>
            </div>
        </form>
    </div>


    <!-- View Images Modal -->
    <div id="viewImagesModal" class="modal-overlay" style="display:none;">
        <div class="modal-box" style="width: 640px; max-width: 95vw; padding: 0; overflow: hidden; border-radius: 8px;">
            <div class="modal-header-custom" style="margin-bottom: 0;">FACULTY IMAGES</div>
            <div style="padding: 20px;">
                <div class="view-images-container">
                    <div id="viewImagesContainer" class="images-grid"></div>
                </div>
                <div style="margin-top: 20px; text-align: center;">
                    <button type="button" class="modal-btn cancel" onclick="closeModal('viewImagesModal')">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- View Teaching Loads Modal -->
    <div id="viewTeachingLoadsModal" class="modal-overlay" style="display:none;">
        <div class="modal-box" style="width: 900px; max-width: 95vw; padding: 0; overflow: hidden; border-radius: 8px;">
            <div class="modal-header-custom" style="margin-bottom: 0;">FACULTY TEACHING LOADS</div>
            <div style="padding: 20px;">
                <div id="facultyInfo" style="margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #8B0000;">
                    <h3 id="facultyName" style="margin: 0 0 5px 0; color: #8B0000; font-size: 1.2rem;"></h3>
                    <p id="facultyDepartment" style="margin: 0; color: #666; font-size: 1rem;"></p>
                </div>
                <div id="teachingLoadsContainer" style="max-height: 400px; overflow-y: auto;">
                    <div id="teachingLoadsTable" style="display: none;">
                        <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
                            <thead>
                                <tr style="background: #8B0000; color: white;">
                                    <th style="padding: 10px; text-align: center; border: none;">Course Code</th>
                                    <th style="padding: 10px; text-align: center; border: none;">Subject</th>
                                    <th style="padding: 10px; text-align: center; border: none;">Class Section</th>
                                    <th style="padding: 10px; text-align: center; border: none;">Day</th>
                                    <th style="padding: 10px; text-align: center; border: none;">Time In</th>
                                    <th style="padding: 10px; text-align: center; border: none;">Time Out</th>
                                    <th style="padding: 10px; text-align: center; border: none;">Room</th>
                                </tr>
                            </thead>
                            <tbody id="teachingLoadsTableBody">
                            </tbody>
                        </table>
                    </div>
                    <div id="noTeachingLoads" style="text-align: center; padding: 40px; color: #666; font-style: italic; display: none;">
                        No teaching loads assigned to this faculty member.
                    </div>
                </div>
                <div style="margin-top: 20px; text-align: center;">
                    <button type="button" class="modal-btn cancel" onclick="closeModal('viewTeachingLoadsModal')">Close</button>
                </div>
            </div>
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

        // Function to open teaching loads modal
        function openTeachingLoadsModal(facultyId, facultyName, facultyDepartment) {
            // Set faculty info
            document.getElementById('facultyName').textContent = facultyName;
            document.getElementById('facultyDepartment').textContent = facultyDepartment;
            
            // Show loading state
            document.getElementById('teachingLoadsTable').style.display = 'none';
            document.getElementById('noTeachingLoads').style.display = 'none';
            
            // Fetch teaching loads
            fetch(`/api/faculty/${facultyId}/teaching-loads`)
                .then(response => response.json())
                .then(data => {
                    const tableBody = document.getElementById('teachingLoadsTableBody');
                    tableBody.innerHTML = '';
                    
                    if (data.length > 0) {
                        let currentDay = '';
                        data.forEach((load, index) => {
                            // Add day separator if it's a new day
                            if (load.teaching_load_day_of_week !== currentDay) {
                                currentDay = load.teaching_load_day_of_week;
                                
                                // Add day header row
                                const dayHeaderRow = document.createElement('tr');
                                dayHeaderRow.style.background = '#f8f9fa';
                                dayHeaderRow.style.borderTop = '2px solid #8B0000';
                                dayHeaderRow.innerHTML = `
                                    <td colspan="7" style="padding: 12px; text-align: center; font-weight: bold; color: #8B0000; font-size: 1.1rem;">
                                        ${load.teaching_load_day_of_week}
                                    </td>
                                `;
                                tableBody.appendChild(dayHeaderRow);
                            }
                            
                            // Add teaching load row
                            const row = document.createElement('tr');
                            row.style.borderBottom = '1px solid #eee';
                            row.style.background = index % 2 === 0 ? '#fff' : '#f9f9f9';
                            row.innerHTML = `
                                <td style="padding: 8px; text-align: center;">${load.teaching_load_course_code}</td>
                                <td style="padding: 8px; text-align: center;">${load.teaching_load_subject}</td>
                                <td style="padding: 8px; text-align: center;">${load.teaching_load_class_section}</td>
                                <td style="padding: 8px; text-align: center; font-weight: bold; color: #8B0000;">${load.teaching_load_day_of_week}</td>
                                <td style="padding: 8px; text-align: center;">${formatTime(load.teaching_load_time_in)}</td>
                                <td style="padding: 8px; text-align: center;">${formatTime(load.teaching_load_time_out)}</td>
                                <td style="padding: 8px; text-align: center;">${load.room_name || load.room_no}</td>
                            `;
                            tableBody.appendChild(row);
                        });
                        document.getElementById('teachingLoadsTable').style.display = 'block';
                    } else {
                        document.getElementById('noTeachingLoads').style.display = 'block';
                    }
                })
                .catch(error => {
                    console.error('Error fetching teaching loads:', error);
                    document.getElementById('noTeachingLoads').style.display = 'block';
                    document.getElementById('noTeachingLoads').textContent = 'Error loading teaching loads.';
                });
            
            openModal('viewTeachingLoadsModal');
        }

        // Helper function to format time
        function formatTime(timeString) {
            if (!timeString) return '';
            try {
                const time = new Date('2000-01-01 ' + timeString);
                return time.toLocaleTimeString('en-US', { 
                    hour: 'numeric', 
                    minute: '2-digit', 
                    hour12: true 
                });
            } catch (e) {
                return timeString;
            }
        }
        // Add event listeners for faculty row clicks
        document.addEventListener('DOMContentLoaded', function() {
            const facultyRows = document.querySelectorAll('.faculty-row');
            facultyRows.forEach(row => {
                row.addEventListener('click', function(e) {
                    // Don't trigger if clicking on buttons
                    if (e.target.closest('.action-btns') || e.target.closest('.view-btn')) {
                        return;
                    }
                    
                    const facultyId = this.dataset.facultyId;
                    const facultyName = this.cells[1].textContent + ' ' + this.cells[2].textContent;
                    const facultyDepartment = this.cells[3].textContent;
                    
                    openTeachingLoadsModal(facultyId, facultyName, facultyDepartment);
                });
            });
        });

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
