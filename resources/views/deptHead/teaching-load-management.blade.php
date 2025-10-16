@extends('layouts.appdeptHead')

@section('title', 'Teaching Load Management - Tagoloan Community College')
@section('files-active', 'active')
@section('teaching-load-active', 'active')

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
            width: 500px;
            max-width: 98vw;
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
            left: 90px;
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

        .modal-btn.add {
            background: #2ecc71;
            color: #fff;
        }

        .modal-btn.update {
            background: #7cc6fa;
            color: #fff;
        }

        .modal-btn.cancel {
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
        }

        .modal-form {
            width: 100%;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px 24px;
            margin-bottom: 18px;
        }

        .modal-form-group {
            display: flex;
            flex-direction: row;
            align-items: center;
            gap: 10px;
            margin-bottom: 0;
        }

        .modal-form-group label {
            min-width: 90px;
            text-align: left;
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



        .modal-buttons {
            display: flex;
            gap: 18px;
            grid-column: 1 / span 2;
        }

        .modal-btn {
            width: 50%;
            padding: 12px 0;
            font-size: 1.1rem;
            font-weight: bold;
            border: 2px solid #bbb;
            border-radius: 6px;
            cursor: pointer;
            background: #fff;
            transition: background 0.2s, color 0.2s;
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

        .modal-btn.update {
            color: #fff;
            background: #7cc6fa;
            border-color: #7cc6fa;
        }

        .modal-btn.update:hover {
            background: #5bb3f5;
            color: #fff;
            border-color: #5bb3f5;
        }

        .modal-btn.add {
            color: #fff;
            background: #2ecc71;
            border-color: #2ecc71;
        }

        .modal-btn.add:hover {
            background: #27ae60;
            border-color: #27ae60;
        }

        /* Delete button styling to match user account design */
        .modal-btn.delete {
            background: transparent;
            color: #ff3636;
            border: 2px solid #ff3636;
            width: 60% !important;
            white-space: normal;
            overflow: show;
            text-overflow: clip;
            padding: 12px 20px !important;
        }

        .modal-btn.delete:hover {
            background: #ff3636;
            color: #fff;
        }

        .modal-btn.cancel {
            width: 60% !important;
            padding: 12px 20px !important;
        }

        /* --- Clean, two-column modal form layout --- */
        .modal-box {
            align-items: center;
            width: 100%;
            max-width: 600px;
            background: #fff;
        }

        .modal-form {
            width: 100%;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px 24px;
            margin-bottom: 0;
        }

        .modal-form-group {
            display: flex;
            flex-direction: row;
            align-items: center;
            gap: 10px;
            margin-bottom: 0;
        }

        .modal-form-group label {
            min-width: 90px;
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
            grid-column: 1 / span 2;
            margin-top: 10px;
        }

        .modal-btn {
            width: 60%;
            padding: 12px 0;
            font-size: 1.1rem;
            font-weight: bold;
            border: 2px solid #bbb;
            border-radius: 6px;
            cursor: pointer;
            background: #fff;
            transition: background 0.2s, color 0.2s;
        }

        .modal-btn.cancel {
            color: #fff;
            background: #ff3636;
            border-color: #ff3636;
        }

        .modal-btn.cancel:hover {
            background: #d32f2f;
            border-color: #d32f2f;
        }

        .modal-btn.update {
            color: #fff;
            background: #3498db;
            border-color: #3498db;
        }

        .modal-btn.update:hover {
            background: #5bb3f5;
            color: #fff;
            border-color: #5bb3f5;
        }

        .modal-btn.add {
            color: #fff;
            background: #2ecc71;
            border-color: #2ecc71;
        }

        .modal-btn.add:hover {
            background: #27ae60;
            border-color: #27ae60;
        }
    </style>
@endsection

@section('content')
    @include('partials.flash')
    @include('partials.flashupdate')
    @include('partials.flashdelete')
    <div class="faculty-header">
        <div class="faculty-title-group">
            <div class="faculty-title">Teaching Load Management</div>
            <div class="faculty-subtitle"></div>
        </div>
        <div class="faculty-actions-row">
            <input type="text" class="search-input" placeholder="Search...">
            <button class="add-btn" onclick="openModal('addTeachingLoadModal')">Add</button>
        </div>
    </div>

    <div class="teaching-load-table-container">
        <div class="teaching-load-table-scroll">
            <table class="teaching-load-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Instructor</th>
                        <th>Course Code</th>
                        <th>Subject</th>
                        <th>Class Section</th>
                        <th>Day</th>
                        <th>Time In</th>
                        <th>Time Out</th>
                        <th>Room No.</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($teachingLoads as $load)
                        @php
                            // Parse class section to get individual components
                            $classSection = $load->teaching_load_class_section ?? '';
                            $department = '';
                            $year = '';
                            $section = '';
                            
                            if ($classSection) {
                                $match = preg_match('/^([A-Z]+)\s+(\d+)([A-Z]+)$/', $classSection, $matches);
                                if ($match) {
                                    $department = $matches[1];
                                    $year = $matches[2];
                                    $section = $matches[3];
                                }
                            }
                        @endphp
                        <tr data-id="{{ $load->teaching_load_id }}" 
                            data-department="{{ $department }}" 
                            data-year="{{ $year }}" 
                            data-section="{{ $section }}">
                            <td>{{ $load->teaching_load_id }}</td>
                            <td class="faculty" data-id="{{ $load->faculty_id }}">
                                {{ $load->faculty->faculty_fname }} {{ $load->faculty->faculty_lname }}
                            </td>
                            <td class="course">{{ $load->teaching_load_course_code }}</td>
                            <td class="subject">{{ $load->teaching_load_subject }}</td>
                            <td class="class-section">{{ $load->teaching_load_class_section }}</td>
                            <td class="day">{{ $load->teaching_load_day_of_week }}</td>
                            <td class="time-in">{{ \Carbon\Carbon::createFromFormat('H:i:s', $load->teaching_load_time_in)->format('g:i a') }}</td>
                            <td class="time-out">{{ \Carbon\Carbon::createFromFormat('H:i:s', $load->teaching_load_time_out)->format('g:i a') }}</td>
                            <td class="room">{{ $load->room_no }}</td>
                            <td>
                                <div class="action-btns">
                                    <button class="edit-btn"
                                        onclick="openUpdateModal({{ $load->teaching_load_id }})">&#9998;</button>
                                    <button class="delete-btn"
                                        onclick="openDeleteModal({{ $load->teaching_load_id }})">&#128465;</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" style="text-align:center; font-style:italic; color:#666;">
                                No Registered Teaching Load found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add Modal -->
    <div id="addTeachingLoadModal" class="modal-overlay" style="display:none;">
        <div class="modal-box" style="padding: 0; overflow: hidden; border-radius: 8px;">
            <form action="{{ route('deptHead.teaching-load.store') }}" method="POST" style="padding: 0;">
                @csrf
                <div class="modal-header"
                    style="
                background-color: #8B0000; color: white; padding: 18px 24px; font-size: 24px; font-weight: bold; width: 100%; margin: 0; display: flex; align-items: center; justify-content: center; text-align: center; letter-spacing: 0.5px; border-top-left-radius: 8px; border-top-right-radius: 8px;">
                    ADD TEACHING LOAD
                </div>
                <div class="modal-form" style="padding: 24px 24px 24px;">
                    <style>
                        #addTeachingLoadModal .modal-form {
                            display: flex;
                            flex-direction: column;
                            gap: 20px;
                            margin-bottom: 0;
                        }

                        #addTeachingLoadModal .form-section {
                            display: grid;
                            grid-template-columns: 1fr 1fr;
                            gap: 18px 24px;
                        }

                        #addTeachingLoadModal .form-section.full-width {
                            grid-template-columns: 1fr;
                        }

                        #addTeachingLoadModal .section-title {
                            grid-column: 1 / span 2;
                            font-size: 1.2rem;
                            font-weight: bold;
                            color: #8B0000;
                            margin-bottom: 10px;
                            padding-bottom: 8px;
                            border-bottom: 2px solid #8B0000;
                        }

                        #addTeachingLoadModal .form-section.full-width .section-title {
                            grid-column: 1 / span 2;
                        }

                        #addTeachingLoadModal .modal-form-group {
                            display: flex;
                            align-items: center;
                            gap: 6px;
                            margin-bottom: 0;
                            padding-bottom: 1px;
                            position: relative;
                        }

                        #addTeachingLoadModal .modal-form-group.full-width {
                            grid-column: 1 / span 2;
                        }

                        #addTeachingLoadModal .modal-form-group label {
                            min-width: 130px;
                            margin-bottom: 0;
                            font-size: 1rem;
                            text-align: left;
                        }

                        #addTeachingLoadModal .modal-form-group input,
                        #addTeachingLoadModal .modal-form-group select {
                            flex: 1;
                            width: 100%;
                            padding: 10px 12px;
                            font-size: 1rem;
                            border: 1px solid #bbb;
                            border-radius: 5px;
                        }

                        #addTeachingLoadModal .validation-message {
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

                        #addTeachingLoadModal .modal-buttons {
                            display: flex;
                            gap: 12px;
                            justify-content: center;
                            margin-top: 12px;
                        }

                        #addTeachingLoadModal .modal-btn.add {
                            background: transparent;
                            border: 2px solid #2e7d32;
                            color: #2e7d32;
                        }

                        #addTeachingLoadModal .modal-btn.add:hover {
                            background: #2e7d32;
                            color: #fff;
                            border-color: #2e7d32;
                        }
                    </style>

                    <!-- Instructor Information Section -->
                    <div class="form-section full-width">
                        <div class="section-title">Instructor Information</div>
                        <div class="modal-form-group full-width">
                            <label>Instructor :</label>
                            <select name="faculty_id">
                                <option value="">Select Instructor</option>
                                @foreach ($faculties as $faculty)
                                    <option value="{{ $faculty->faculty_id }}">{{ $faculty->faculty_fname }}
                                        {{ $faculty->faculty_lname }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="section-title"></div>
                        <div class="modal-form-group full-width">
                            <label>Course Department:</label>
                            <select name="department" id="addDeptSelect">
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
                        <div class="modal-form-group full-width">
                            <label>Course & Subject :</label>
                            <select name="subject_combo" id="addSubjectCombo" disabled>
                                <option value="">Select Course & Subject</option>
                                @foreach(($subjectsOptions ?? collect()) as $opt)
                                    <option value="{{ $opt->code }}|{{ $opt->name }}" data-code="{{ $opt->code }}" data-name="{{ $opt->name }}" data-dept="{{ $opt->department }}">{{ $opt->code }} - {{ $opt->name }}</option>
                                @endforeach
                            </select>
                            <input type="hidden" name="teaching_load_course_code" id="addCourseCodeHidden">
                            <input type="hidden" name="teaching_load_subject" id="addSubjectHidden">
                        </div>
                    </div>

                    <!-- Class Information Section -->
                    <div class="form-section">
                        <div class="section-title">Class Information</div>
                        <div class="modal-form-group">
                            <label>Class Department:</label>
                            <select name="tl_department_short" id="tl_department_short_add">
                                <option value="">Select Department</option>
                                <option value="BSIT">BSIT</option>
                                <option value="BSEd">BSEd</option>
                                <option value="BSBA">BSBA</option>
                                <option value="BSHM">BSHM</option>
                                <option value="BSCrim">BSCrim</option>
                                <option value="ADMIN">ADMIN</option>
                                <option value="CLIS">CLIS</option>
                                <option value="CAS">CAS</option>
                                <option value="SOC">SOC</option>
                                <option value="COE">COE</option>
                            </select>
                        </div>
                        <div class="modal-form-group">
                            <label>Year :</label>
                            <select name="tl_year_level" id="tl_year_level_add">
                                <option value="">Select Year</option>
                                <option value="1">1</option>
                                <option value="2">2</option>
                                <option value="3">3</option>
                                <option value="4">4</option>
                            </select>
                        </div>
                        <div class="modal-form-group">
                            <label>Section :</label>
                            <select name="tl_section" id="tl_section_add">
                                <option value="">Select Section</option>
                                <option value="A">A</option>
                                <option value="B">B</option>
                                <option value="C">C</option>
                                <option value="D">D</option>
                                <option value="E">E</option>
                                <option value="F">F</option>
                                <option value="G">G</option>
                            </select>
                        </div>
                        <div class="modal-form-group">
                            <label>Day of Week :</label>
                            <select name="teaching_load_day_of_week">
                                <option value="">Select Day</option>
                                @foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $day)
                                    <option value="{{ $day }}">{{ $day }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="modal-form-group">
                            <label>Time In :</label>
                            <input type="time" name="teaching_load_time_in">
                        </div>
                        <div class="modal-form-group">
                            <label>Time Out :</label>
                            <input type="time" name="teaching_load_time_out">
                        </div>
                        <div class="modal-form-group">
                            <label>Room :</label>
                            <select name="room_no">
                                <option value="">Select Room</option>
                                @foreach ($rooms as $room)
                                    <option value="{{ $room->room_no }}">{{ $room->room_no }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="time-logic-error"
                        style="display:none; color:#ff3636; text-align:center; margin-top:6px; margin-bottom:6px; font-weight:600; grid-column: 1 / span 2;">
                    </div>
                    <div class="modal-buttons">
                        <button type="submit" class="modal-btn add">Add</button>
                        <button type="button" class="modal-btn cancel"
                            onclick="closeModal('addTeachingLoadModal')">Cancel</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Update Modal -->
    <div id="updateTeachingLoadModal" class="modal-overlay" style="display:none;">
        <div class="modal-box" style="padding: 0; overflow: hidden; border-radius: 8px;">
            <form id="updateForm" method="POST" style="padding: 0;">
                @csrf
                @method('PUT')
                <div class="modal-header"
                    style="
                background-color: #8B0000; color: white; padding: 18px 24px; font-size: 24px; font-weight: bold; width: 100%; margin: 0; display: flex; align-items: center; justify-content: center; text-align: center; letter-spacing: 0.5px; border-top-left-radius: 8px; border-top-right-radius: 8px;">
                    UPDATE TEACHING LOAD
                </div>
                <div class="modal-form" style="padding: 24px 24px 24px;">
                    <style>
                        #updateTeachingLoadModal .modal-form {
                            display: flex;
                            flex-direction: column;
                            gap: 20px;
                            margin-bottom: 0;
                        }

                        #updateTeachingLoadModal .form-section {
                            display: grid;
                            grid-template-columns: 1fr 1fr;
                            gap: 18px 24px;
                        }

                        #updateTeachingLoadModal .form-section.full-width {
                            grid-template-columns: 1fr;
                        }

                        #updateTeachingLoadModal .section-title {
                            grid-column: 1 / span 2;
                            font-size: 1.2rem;
                            font-weight: bold;
                            color: #8B0000;
                            margin-bottom: 10px;
                            padding-bottom: 8px;
                            border-bottom: 2px solid #8B0000;
                        }

                        #updateTeachingLoadModal .form-section.full-width .section-title {
                            grid-column: 1;
                        }

                        #updateTeachingLoadModal .modal-form-group {
                            display: flex;
                            align-items: center;
                            gap: 6px;
                            margin-bottom: 0;
                            padding-bottom: 6px;
                            position: relative;
                        }

                        #updateTeachingLoadModal .modal-form-group.full-width {
                            grid-column: 1 / span 2;
                        }

                        #updateTeachingLoadModal .modal-form-group label {
                            min-width: 130px;
                            margin-bottom: 0;
                            font-size: 1rem;
                            text-align: left;
                        }

                        #updateTeachingLoadModal .modal-form-group input,
                        #updateTeachingLoadModal .modal-form-group select {
                            flex: 1;
                            width: 100%;
                            padding: 10px 12px;
                            font-size: 1rem;
                            border: 1px solid #bbb;
                            border-radius: 5px;
                        }

                        #updateTeachingLoadModal .validation-message {
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

                        #updateTeachingLoadModal .modal-buttons {
                            display: flex;
                            gap: 12px;
                            justify-content: center;
                            margin-top: 12px;
                        }

                        /* Update button: match Add button green styling */
                        #updateTeachingLoadModal .modal-btn.update {
                            background: #7cc6fa;
                            border: 2px solid #7cc6fa;
                            color: #fff;
                            transition: background-color 0.15s ease, color 0.15s ease, border-color 0.15s ease;
                        }

                        #updateTeachingLoadModal .modal-btn.update:hover {
                            background: #5bb3f5;
                            color: #fff;
                            border-color: #5bb3f5;
                        }
                    </style>

                    <!-- Instructor Information Section -->
                    <div class="form-section full-width">
                        <div class="section-title">Instructor Information</div>
                        <div class="modal-form-group full-width">
                            <label>Instructor :</label>
                            <select name="faculty_id">
                                <option value="">Select Instructor</option>
                                @foreach ($faculties as $faculty)
                                    <option value="{{ $faculty->faculty_id }}">{{ $faculty->faculty_fname }}
                                        {{ $faculty->faculty_lname }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="modal-form-group full-width">
                            <label>Department :</label>
                            <select name="department" id="updateDeptSelect">
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
                        <div class="modal-form-group full-width">
                            <label>Course & Subject :</label>
                            <select name="subject_combo" id="updateSubjectCombo">
                                <option value="">Select Course & Subject</option>
                                @foreach(($subjectsOptions ?? collect()) as $opt)
                                    <option value="{{ $opt->code }}|{{ $opt->name }}" data-code="{{ $opt->code }}" data-name="{{ $opt->name }}">{{ $opt->code }} - {{ $opt->name }}</option>
                                @endforeach
                            </select>
                            <input type="hidden" name="teaching_load_course_code" id="updateCourseCodeHidden">
                            <input type="hidden" name="teaching_load_subject" id="updateSubjectHidden">
                        </div>
                    </div>

                    <!-- Class Information Section -->
                    <div class="form-section">
                        <div class="section-title">Class Information</div>
                        <div class="modal-form-group">
                            <label>Department :</label>
                            <select name="tl_department_short" id="tl_department_short_update">
                                <option value="">Select Department</option>
                                <option value="BSIT">BSIT</option>
                                <option value="BSEd">BSEd</option>
                                <option value="BSBA">BSBA</option>
                                <option value="BSHM">BSHM</option>
                                <option value="BSCrim">BSCrim</option>
                                <option value="ADMIN">ADMIN</option>
                                <option value="CLIS">CLIS</option>
                                <option value="CAS">CAS</option>
                                <option value="SOC">SOC</option>
                                <option value="COE">COE</option>
                            </select>
                        </div>
                        <div class="modal-form-group">
                            <label>Year :</label>
                            <select name="tl_year_level" id="tl_year_level_update">
                                <option value="">Select Year</option>
                                <option value="1">1</option>
                                <option value="2">2</option>
                                <option value="3">3</option>
                                <option value="4">4</option>
                            </select>
                        </div>
                        <div class="modal-form-group">
                            <label>Section :</label>
                            <select name="tl_section" id="tl_section_update">
                            <option value="">Select Section</option>
                                <option value="A">A</option>
                                <option value="B">B</option>
                                <option value="C">C</option>
                                <option value="D">D</option>
                                <option value="E">E</option>
                                <option value="F">F</option>
                                <option value="G">G</option>
                            </select>
                        </div>
                        <div class="modal-form-group">
                            <label>Day of Week :</label>
                            <select name="teaching_load_day_of_week">
                                @foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $day)
                                    <option value="{{ $day }}">{{ $day }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="modal-form-group">
                            <label>Time In :</label>
                            <input type="time" name="teaching_load_time_in">
                        </div>
                        <div class="modal-form-group">
                            <label>Time Out :</label>
                            <input type="time" name="teaching_load_time_out">
                        </div>
                        <div class="modal-form-group">
                            <label>Room :</label>
                            <select name="room_no">
                                <option value="">Select Room</option>
                                @foreach ($rooms as $room)
                                    <option value="{{ $room->room_no }}">{{ $room->room_no }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="time-logic-error"
                        style="display:none; color:#ff3636; text-align:center; margin-top:6px; margin-bottom:6px; font-weight:600; grid-column: 1 / span 2;">
                    </div>
                    <div class="modal-buttons">
                        <button type="submit" class="modal-btn update">Update</button>
                        <button type="button" class="modal-btn cancel"
                            onclick="closeModal('updateTeachingLoadModal')">Cancel</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <!-- Delete Teaching Load Modal -->
    <div id="deleteTeachingLoadModal" class="modal-overlay" style="display:none;">
        <form id="deleteForm" method="POST" class="modal-box">
            @csrf
            @method('DELETE')
            <div class="modal-header delete">DELETE TEACHING LOAD</div>

            <!-- Warning Icon and Message -->
            <div style="text-align: center; margin:0 px 0;">
                <div style="font-size: 4rem; color: #ff3636; margin-bottom: 20px;">⚠️</div>
                <div style="font-size: 1.2rem; color: #333; margin-bottom: 10px; font-weight: bold;">Are you sure?</div>
                <div style="font-size: 1rem; color: #666; line-height: 1.5;">
                    This action cannot be undone. The teaching load will be permanently deleted.
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="modal-buttons" style="display: flex; gap: 12px; justify-content: center; margin-top: 12px;">
                <button type="submit" class="modal-btn delete" style="min-width: 250px;">Delete Teaching Load</button>
                <button type="button" class="modal-btn cancel" style="min-width: 250px;"
                    onclick="closeModal('deleteTeachingLoadModal')">Cancel</button>
            </div>
        </form>
    </div>


@endsection

@section('scripts')
    <script>
        function openModal(id) {
            document.getElementById(id).style.display = 'flex';
            
            // Initialize button states when opening modals
            if (id === 'addTeachingLoadModal') {
                updateAddButtonState(false); // Start with disabled state
            } else if (id === 'updateTeachingLoadModal') {
                updateUpdateButtonState(false); // Start with disabled state
            }
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

            // Clear time logic error
            const timeLogicError = form.querySelector('.time-logic-error');
            if (timeLogicError) {
                timeLogicError.style.display = 'none';
                timeLogicError.textContent = '';
            }

            window.tlSubmitAttempt = false;
        }

        function closeModal(id) {
            const modal = document.getElementById(id);
            if (modal) modal.style.display = 'none';
            if (id === 'addTeachingLoadModal' || id === 'updateTeachingLoadModal') {
                resetModalForm(id);
            }
        }

        // Update Modal
        function openUpdateModal(id) {
            const row = document.querySelector(`tr[data-id='${id}']`);
            const form = document.getElementById('updateForm');
            form.action = `/deptHead/teaching-load/${id}`;
            
            // Set course & subject from row
            const courseCode = row.querySelector('.course').innerText;
            const subjectName = row.querySelector('.subject').innerText;
            form.querySelector('[name="teaching_load_course_code"]').value = courseCode;
            form.querySelector('[name="teaching_load_subject"]').value = subjectName;
            const combo = document.getElementById('updateSubjectCombo');
            if (combo) {
                const val = `${courseCode}|${subjectName}`;
                const opt = Array.from(combo.options).find(o => o.value === val);
                combo.value = opt ? val : '';
            }
            
            // Get individual department, year, and section from data attributes
            const department = row.dataset.department || '';
            const year = row.dataset.year || '';
            const section = row.dataset.section || '';
            
            // Set the form fields with the individual values
            form.querySelector('[name="tl_department_short"]').value = department;
            form.querySelector('[name="tl_year_level"]').value = year;
            form.querySelector('[name="tl_section"]').value = section;
            
            form.querySelector('[name="teaching_load_day_of_week"]').value = row.querySelector('.day').innerText;
            // Convert readable time back to 24-hour format for form inputs
            const timeInText = row.querySelector('.time-in').innerText;
            const timeOutText = row.querySelector('.time-out').innerText;
            
            // Convert from readable format (e.g., "10:30am") to 24-hour format (e.g., "10:30:00")
            const timeIn24h = convertTo24Hour(timeInText);
            const timeOut24h = convertTo24Hour(timeOutText);
            
            form.querySelector('[name="teaching_load_time_in"]').value = timeIn24h;
            form.querySelector('[name="teaching_load_time_out"]').value = timeOut24h;
            form.querySelector('[name="room_no"]').value = row.querySelector('.room').innerText;
            form.querySelector('[name="faculty_id"]').value = row.querySelector('.faculty').dataset.id;
            openModal('updateTeachingLoadModal');
            
            // Validate the pre-filled form and update button state
            setTimeout(() => {
                validateUpdate();
            }, 100);
        }

        // Delete Modal
        function openDeleteModal(id) {
            const form = document.getElementById('deleteForm');
            form.action = `/deptHead/teaching-load/${id}`;
            openModal('deleteTeachingLoadModal');
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
                        `<td colspan="10" style="text-align:center; padding:20px; color:#999; font-style:italic;">No results found</td>`;
                    tbody.appendChild(noResultsRow);
                }
            } else {
                if (noResultsRow) noResultsRow.remove();
            }
        });

        // Close + reset when clicking outside (overlay)
        document.addEventListener('click', function(e) {
            if (e.target.classList && e.target.classList.contains('modal-overlay')) {
                const overlayId = e.target.id;
                e.target.style.display = 'none';
                if (overlayId === 'addTeachingLoadModal' || overlayId === 'updateTeachingLoadModal') {
                    resetModalForm(overlayId);
                }
            }
        });

        // =========================
        // Time Overlap Validation
        // =========================
        function checkTimeOverlap(dayOfWeek, timeIn, timeOut, roomNo, excludeId = null) {
            // This will be called by the backend validation, but we can add basic client-side checks
            if (!dayOfWeek || !timeIn || !timeOut || !roomNo) {
                return { hasOverlap: false };
            }
            
            // Basic client-side validation: time in must be before time out
            if (timeIn >= timeOut) {
                return { 
                    hasOverlap: true, 
                    message: 'Time out must be later than time in.' 
                };
            }
            
            return { hasOverlap: false };
        }

        // Real-time overlap checking with existing teaching loads
        function checkRealTimeOverlap(dayOfWeek, timeIn, timeOut, roomNo, excludeId = null) {
            if (!dayOfWeek || !timeIn || !timeOut || !roomNo) {
                return { hasOverlap: false, message: '' };
            }
            
            // Get all existing teaching loads from the table
            const tableRows = document.querySelectorAll('.teaching-load-table tbody tr');
            let conflictMessage = '';
            
            for (let row of tableRows) {
                // Skip if this is the row we're updating (excludeId)
                if (excludeId && row.dataset.id === excludeId.toString()) {
                    continue;
                }
                
                const rowDay = row.querySelector('.day')?.textContent?.trim();
                const rowRoom = row.querySelector('.room')?.textContent?.trim();
                const rowTimeIn = row.querySelector('.time-in')?.textContent?.trim();
                const rowTimeOut = row.querySelector('.time-out')?.textContent?.trim();
                const rowCourse = row.querySelector('.course')?.textContent?.trim();
                
                // Check if same day and room
                if (rowDay === dayOfWeek && rowRoom === roomNo) {
                    // Convert times to comparable format
                    const newStart = convertTimeToMinutes(timeIn);
                    const newEnd = convertTimeToMinutes(timeOut);
                    const existingStart = convertTimeToMinutes(rowTimeIn);
                    const existingEnd = convertTimeToMinutes(rowTimeOut);
                    
                    // Check for overlap: newStart < existingEnd AND existingStart < newEnd
                    if (newStart < existingEnd && existingStart < newEnd) {
                        conflictMessage = `Time conflict with existing schedule: ${rowCourse} (${rowTimeIn} - ${rowTimeOut})`;
                        return { hasOverlap: true, message: conflictMessage };
                    }
                }
            }
            
            return { hasOverlap: false, message: '' };
        }

        // Convert time string to minutes for comparison
        function convertTimeToMinutes(timeStr) {
            if (!timeStr) return 0;
            
            // Handle different time formats
            let time = timeStr.toLowerCase().trim();
            
            // If it's in 12-hour format (e.g., "1:30pm")
            if (time.includes('am') || time.includes('pm')) {
                const match = time.match(/(\d{1,2}):(\d{2})\s*(am|pm)/);
                if (match) {
                    let hours = parseInt(match[1]);
                    const minutes = parseInt(match[2]);
                    const period = match[3];
                    
                    if (period === 'pm' && hours !== 12) hours += 12;
                    if (period === 'am' && hours === 12) hours = 0;
                    
                    return hours * 60 + minutes;
                }
            }
            
            // If it's in 24-hour format (e.g., "13:30")
            const match = time.match(/(\d{1,2}):(\d{2})/);
            if (match) {
                const hours = parseInt(match[1]);
                const minutes = parseInt(match[2]);
                return hours * 60 + minutes;
            }
            
            return 0;
        }

        // =========================
        // Client-side Validation (Teaching Load forms)
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

            function setValidity(el, ok) {
                if (!el) return;
                const show = el.dataset.touched === 'true' || window.tlSubmitAttempt === true;
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
                const show = el.dataset.touched === 'true' || window.tlSubmitAttempt === true;
                m.textContent = show ? (msg || '') : '';
            }

            function updateAddButtonState(isValid) {
                const addButton = document.querySelector('#addTeachingLoadModal .modal-btn.add');
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
                const updateButton = document.querySelector('#updateTeachingLoadModal .modal-btn.update');
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

            function validateAdd() {
                const course = document.querySelector("#addTeachingLoadModal [name='teaching_load_course_code']");
                const subject = document.querySelector("#addTeachingLoadModal [name='teaching_load_subject']");
                const combo = document.getElementById('addSubjectCombo');
                const day = document.querySelector("#addTeachingLoadModal [name='teaching_load_day_of_week']");
                const tin = document.querySelector("#addTeachingLoadModal [name='teaching_load_time_in']");
                const tout = document.querySelector("#addTeachingLoadModal [name='teaching_load_time_out']");
                const room = document.querySelector("#addTeachingLoadModal [name='room_no']");
                const instr = document.querySelector("#addTeachingLoadModal [name='faculty_id']");
                const deptShort = document.querySelector("#addTeachingLoadModal [name='tl_department_short']");
                const yearLevel = document.querySelector("#addTeachingLoadModal [name='tl_year_level']");
                const section = document.querySelector("#addTeachingLoadModal [name='tl_section']");
                const vCombo = isNotEmpty(combo && combo.value);
                const vCourse = isNotEmpty(course && course.value) && minLen(course && course.value, 2);
                const vSubject = isNotEmpty(subject && subject.value) && minLen(subject && subject.value, 2);
                const vDay = isNotEmpty(day && day.value);
                const vTin = isNotEmpty(tin && tin.value);
                const vTout = isNotEmpty(tout && tout.value);
                const vRoom = isNotEmpty(room && room.value);
                const vInstr = isNotEmpty(instr && instr.value);
                const vDeptShort = isNotEmpty(deptShort && deptShort.value);
                const vYearLevel = isNotEmpty(yearLevel && yearLevel.value);
                const vSection = isNotEmpty(section && section.value);
                
                // Time validation logic: time in must be earlier than time out
                let timeLogicOk = true;
                const timeLogicBox = document.querySelector('#addTeachingLoadModal .time-logic-error');
                if (timeLogicBox) timeLogicBox.style.display = 'none';
                
                if (vTin && vTout) {
                    const timeIn = tin.value;
                    const timeOut = tout.value;
                    if (timeIn && timeOut && timeIn >= timeOut) {
                        timeLogicOk = false;
                        if (timeLogicBox) {
                            timeLogicBox.textContent = 'Time out must be later than time in.';
                            timeLogicBox.style.display = 'block';
                        }
                    }
                }
                
                // Check for potential time overlap (basic client-side check)
                let overlapOk = true;
                if (vDay && vTin && vTout && vRoom && timeLogicOk) {
                    const overlapCheck = checkRealTimeOverlap(day.value, tin.value, tout.value, room.value);
                    if (overlapCheck.hasOverlap) {
                        overlapOk = false;
                        if (timeLogicBox) {
                            timeLogicBox.textContent = overlapCheck.message;
                            timeLogicBox.style.display = 'block';
                        }
                    }
                }
                
                setValidity(combo, vCombo);
                setMessage(combo, vCombo ? '' : 'Course & Subject is required');
                setValidity(day, vDay);
                setMessage(day, vDay ? '' : 'Day is required');
                setValidity(tin, vTin);
                setMessage(tin, vTin ? '' : 'Time in is required');
                setValidity(tout, vTout);
                setMessage(tout, vTout ? '' : 'Time out is required');
                setValidity(room, vRoom);
                setMessage(room, vRoom ? '' : 'Room is required');
                setValidity(instr, vInstr);
                setMessage(instr, vInstr ? '' : 'Instructor is required');
                setValidity(deptShort, vDeptShort);
                setMessage(deptShort, vDeptShort ? '' : 'Department is required');
                setValidity(yearLevel, vYearLevel);
                setMessage(yearLevel, vYearLevel ? '' : 'Year level is required');
                setValidity(section, vSection);
                setMessage(section, vSection ? '' : 'Section is required');
                
                const isValid = vCombo && vCourse && vSubject && vDay && vTin && vTout && vRoom && vInstr && vDeptShort && vYearLevel && vSection && timeLogicOk && overlapOk;
                updateAddButtonState(isValid);
                
                return isValid;
            }

            function validateUpdate() {
                const form = document.getElementById('updateForm');
                const course = form.querySelector("[name='teaching_load_course_code']");
                const subject = form.querySelector("[name='teaching_load_subject']");
                const combo = document.getElementById('updateSubjectCombo');
                const day = form.querySelector("[name='teaching_load_day_of_week']");
                const tin = form.querySelector("[name='teaching_load_time_in']");
                const tout = form.querySelector("[name='teaching_load_time_out']");
                const room = form.querySelector("[name='room_no']");
                const instr = form.querySelector("[name='faculty_id']");
                const deptShort = form.querySelector("[name='tl_department_short']");
                const yearLevel = form.querySelector("[name='tl_year_level']");
                const section = form.querySelector("[name='tl_section']");
                const vCombo = isNotEmpty(combo && combo.value);
                const vCourse = isNotEmpty(course && course.value) && minLen(course && course.value, 2);
                const vSubject = isNotEmpty(subject && subject.value) && minLen(subject && subject.value, 2);
                const vDay = isNotEmpty(day && day.value);
                const vTin = isNotEmpty(tin && tin.value);
                const vTout = isNotEmpty(tout && tout.value);
                const vRoom = isNotEmpty(room && room.value);
                const vInstr = isNotEmpty(instr && instr.value);
                const vDeptShort = isNotEmpty(deptShort && deptShort.value);
                const vYearLevel = isNotEmpty(yearLevel && yearLevel.value);
                const vSection = isNotEmpty(section && section.value);
                
                // Time validation logic: time in must be earlier than time out
                let timeLogicOk = true;
                const timeLogicBox = document.querySelector('#updateTeachingLoadModal .time-logic-error');
                if (timeLogicBox) timeLogicBox.style.display = 'none';
                
                if (vTin && vTout) {
                    const timeIn = tin.value;
                    const timeOut = tout.value;
                    if (timeIn && timeOut && timeIn >= timeOut) {
                        timeLogicOk = false;
                        if (timeLogicBox) {
                            timeLogicBox.textContent = 'Time out must be later than time in.';
                            timeLogicBox.style.display = 'block';
                        }
                    }
                }
                
                // Check for potential time overlap (basic client-side check)
                let overlapOk = true;
                if (vDay && vTin && vTout && vRoom && timeLogicOk) {
                    // Get the current teaching load ID being edited
                    const form = document.getElementById('updateForm');
                    const currentId = form ? form.action.split('/').pop() : null;
                    const overlapCheck = checkRealTimeOverlap(day.value, tin.value, tout.value, room.value, currentId);
                    if (overlapCheck.hasOverlap) {
                        overlapOk = false;
                        if (timeLogicBox) {
                            timeLogicBox.textContent = overlapCheck.message;
                            timeLogicBox.style.display = 'block';
                        }
                    }
                }
                
                setValidity(combo, vCombo);
                setMessage(combo, vCombo ? '' : 'Course & Subject is required');
                setValidity(day, vDay);
                setMessage(day, vDay ? '' : 'Day is required');
                setValidity(tin, vTin);
                setMessage(tin, vTin ? '' : 'Time in is required');
                setValidity(tout, vTout);
                setMessage(tout, vTout ? '' : 'Time out is required');
                setValidity(room, vRoom);
                setMessage(room, vRoom ? '' : 'Room is required');
                setValidity(instr, vInstr);
                setMessage(instr, vInstr ? '' : 'Instructor is required');
                setValidity(deptShort, vDeptShort);
                setMessage(deptShort, vDeptShort ? '' : 'Department is required');
                setValidity(yearLevel, vYearLevel);
                setMessage(yearLevel, vYearLevel ? '' : 'Year level is required');
                setValidity(section, vSection);
                setMessage(section, vSection ? '' : 'Section is required');
                
                const isValid = vCombo && vCourse && vSubject && vDay && vTin && vTout && vRoom && vInstr && vDeptShort && vYearLevel && vSection && timeLogicOk && overlapOk;
                updateUpdateButtonState(isValid);
                
                return isValid;
            }

            // Real-time bindings
            ['subject_combo', 'teaching_load_course_code', 'teaching_load_subject', 'teaching_load_day_of_week', 'teaching_load_time_in',
                'teaching_load_time_out', 'room_no', 'faculty_id', 'tl_department_short', 'tl_year_level', 'tl_section'
            ].forEach(name => {
                const el = document.querySelector(`#addTeachingLoadModal [name='${name}']`);
                if (!el) return;
                const evt = el.tagName === 'SELECT' ? 'change' : 'input';
                el.addEventListener(evt, validateAdd);
                el.addEventListener('blur', () => {
                    el.dataset.touched = 'true';
                    validateAdd();
                });
            });
            ['subject_combo', 'teaching_load_course_code', 'teaching_load_subject', 'teaching_load_day_of_week', 'teaching_load_time_in',
                'teaching_load_time_out', 'room_no', 'faculty_id', 'tl_department_short', 'tl_year_level', 'tl_section'
            ].forEach(name => {
                const el = document.querySelector(`#updateTeachingLoadModal [name='${name}']`);
                if (!el) return;
                const evt = el.tagName === 'SELECT' ? 'change' : 'input';
                el.addEventListener(evt, validateUpdate);
                el.addEventListener('blur', () => {
                    el.dataset.touched = 'true';
                    validateUpdate();
                });
            });

            (function() {
                const addForm = document.querySelector('#addTeachingLoadModal form');
                if (addForm) {
                    addForm.addEventListener('submit', function(e) {
                        window.tlSubmitAttempt = true;
                        if (!validateAdd()) {
                            e.preventDefault();
                            showError('Validation Error', 'Please fix all errors before submitting. Check for time conflicts and complete all required fields.');
                        }
                    });
                }
                const updForm = document.getElementById('updateForm');
                if (updForm) {
                    updForm.addEventListener('submit', function(e) {
                        window.tlSubmitAttempt = true;
                        if (!validateUpdate()) {
                            e.preventDefault();
                            showError('Validation Error', 'Please fix all errors before submitting. Check for time conflicts and complete all required fields.');
                        }
                    });
                }
            })();
        })();

        // Sync course/subject hidden inputs when selecting combo
        (function(){
            const addCombo = document.getElementById('addSubjectCombo');
            const addCode = document.getElementById('addCourseCodeHidden');
            const addSubj = document.getElementById('addSubjectHidden');
            function syncAdd(){
                const val = (addCombo && addCombo.value) || '';
                if(!val){ addCode.value=''; addSubj.value=''; return; }
                const parts = val.split('|');
                addCode.value = parts[0] || '';
                addSubj.value = parts[1] || '';
            }
            if(addCombo){ addCombo.addEventListener('change', syncAdd); }

            const updCombo = document.getElementById('updateSubjectCombo');
            const updCode = document.getElementById('updateCourseCodeHidden');
            const updSubj = document.getElementById('updateSubjectHidden');
            function syncUpd(){
                const val = (updCombo && updCombo.value) || '';
                if(!val){ updCode.value=''; updSubj.value=''; return; }
                const parts = val.split('|');
                updCode.value = parts[0] || '';
                updSubj.value = parts[1] || '';
            }
            if(updCombo){ updCombo.addEventListener('change', syncUpd); }
        })();

        // Dynamic subject options based on selected department (Add modal)
        (function(){
            // Department of Class controls filtering; Class Department is record-only
            const deptSelect = document.getElementById('addDeptSelect');
            const subjectCombo = document.getElementById('addSubjectCombo');
            const allOptions = subjectCombo ? Array.from(subjectCombo.querySelectorAll('option')).slice(1) : [];

            // Map legacy full department names to short codes used by teaching load
            const departmentAliasToShort = {
                'college of information technology': 'BSIT',
                'college of education': 'BSEd',
                'college of business administration': 'BSBA',
                'college of hospitality management': 'BSHM',
                'college of criminology': 'BSCrim',
                'department of admin': 'ADMIN',
                'college of library and information science': 'CLIS',
                'college of arts and sciences': 'CAS',
                'college of sociology': 'SOC',
                'college of engineering': 'COE',
                // Direct mapping for new format
                'bsit': 'BSIT',
                'bsed': 'BSEd',
                'bsba': 'BSBA',
                'bshm': 'BSHM',
                'bscrim': 'BSCrim',
                'admin': 'ADMIN',
                'clis': 'CLIS',
                'cas': 'CAS',
                'soc': 'SOC',
                'coe': 'COE'
            };

            function toShortCode(value){
                if(!value) return '';
                const v = String(value).trim();
                if (/^(BSIT|BSEd|BSBA|BSHM|BSCrim|ADMIN|CLIS|CAS|SOC|COE)$/i.test(v)) return v.toUpperCase();
                const mapped = departmentAliasToShort[v.toLowerCase()];
                return mapped || v; // fallback to original if no mapping
            }

            function filterSubjects(){
                const deptShort = toShortCode(deptSelect && deptSelect.value);
                if(!subjectCombo) return;
                subjectCombo.innerHTML = '<option value="">Select Course & Subject</option>';
                subjectCombo.disabled = !deptShort;
                if(!deptShort) return;
                const matches = allOptions.filter(o => toShortCode(o.getAttribute('data-dept')) === deptShort);
                if(matches.length === 0){
                    const opt = document.createElement('option');
                    opt.value = '';
                    opt.textContent = 'No subjects found for selected department';
                    opt.disabled = true;
                    subjectCombo.appendChild(opt);
                    return;
                }
                matches.forEach(o => subjectCombo.appendChild(o.cloneNode(true)));
            }
            function handleDeptChange(){
                // clear any previously selected subject and hidden fields
                if(subjectCombo){ subjectCombo.value=''; }
                const addCode = document.getElementById('addCourseCodeHidden');
                const addSubj = document.getElementById('addSubjectHidden');
                if(addCode) addCode.value = '';
                if(addSubj) addSubj.value = '';
                filterSubjects();
            }

            if(deptSelect){ deptSelect.addEventListener('change', handleDeptChange); }
            // initialize
            filterSubjects();
        })();
    </script>
@endsection
