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
            margin-bottom: 32px;
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

        .teaching-load-table-container {
            background: #fff;
            border-radius: 8px;
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
            padding: 12.8px 0;
            font-size: 0.88rem;
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
            padding: 9.6px 0;
            text-align: center;
            font-size: 0.8rem;
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
            max-height: 536px;
            overflow-y: auto;
            width: 100%;
        }

        .action-btns {
            display: flex;
            gap: 6.4px;
            justify-content: center;
            align-items: center;
        }

        .edit-btn,
        .delete-btn {
            width: 48px;
            height: 25.6px;
            border-radius: 4.8px;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.72rem;
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
            border-radius: 8px;
            width: 100%;
            max-width: 360px;
            padding: 0;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.22), 0 1.5px 8px rgba(0, 0, 0, 0.12);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .modal-header {
            font-size: 1.6rem;
            font-weight: bold;
            color: #8B0000;
            text-align: center;
            margin-bottom: 0;
        }

        .modal-form {
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: stretch;
        }

        .modal-content {
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: stretch;
        }

        #deleteModal .modal-box {
            padding: 32px 32px 24px 32px;
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
            margin-bottom: 0;
            font-size: 0.8rem;
            color: #222;
        }

        .modal-form-group input,
        .modal-form-group textarea,
        .modal-form-group select {
            flex: 1;
            width: 100%;
            padding: 8px 9.6px;
            font-size: 0.8rem;
            border: 1px solid #bbb;
            border-radius: 4px;
        }

        .modal-form-group input.valid,
        .modal-form-group select.valid,
        .modal-form-group textarea.valid {
            border-color: #2ecc71;
            box-shadow: 0 0 0 1.6px rgba(46, 204, 113, 0.1);
        }

        .modal-form-group input.invalid,
        .modal-form-group select.invalid,
        .modal-form-group textarea.invalid {
            border-color: #ff3636;
            box-shadow: 0 0 0 1.6px rgba(255, 54, 54, 0.1);
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

        .modal-form-group textarea {
            resize: vertical;
        }

        .modal-btn {
            width: 100%;
            padding: 11.2px 0;
            font-size: 0.88rem;
            font-weight: bold;
            border: none;
            border-radius: 4.8px;
            margin-top: 0;
            cursor: pointer;
        }

        .modal-buttons {
            display: flex;
            gap: 9.6px;
            justify-content: center;
            margin-top: 14.4px;
        }

        .modal-btn.cancel {
            background: #fff !important;
            color: #800000 !important;
            border: 2px solid #800000 !important;
            border-radius: 6.4px;
            padding: 8px 16px;
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
            border-radius: 6.4px;
            padding: 8px 16px;
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
            padding: 6.4px 12.8px;
            font-size: 0.72rem;
            border: none;
            border-radius: 4px;
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
            border-radius: 9.6px;
            overflow: hidden;
        }

        #slipImage {
            max-width: 100%;
            max-height: 75vh;
            border-radius: 6.4px;
            object-fit: contain;
            display: block;
        }

        /* Close button styles for slip modal */
        #slipModal .close {
            position: absolute;
            top: 12px;
            right: 16px;
            z-index: 1000;
            width: 32px;
            height: 32px;
            background: rgba(0, 0, 0, 0.7);
            color: #fff;
            border: none;
            border-radius: 50%;
            font-size: 19.2px;
            font-weight: bold;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            box-shadow: 0 3.2px 9.6px rgba(0, 0, 0, 0.3);
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
            padding: 12px 16px;
            margin: 0;
            font-size: 1.2rem;
            font-weight: bold;
            text-align: center;
        }

        /* Slip modal content */
        #slipModal .slip-content {
            padding: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
        }

        /* Mobile Responsive Design for phones (max-width: 430px) */
        @media (max-width: 430px) {
            /* Faculty Header */
            .faculty-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 16px;
                margin-bottom: 20px;
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

            /* Faculty Actions Row */
            .faculty-actions-row {
                position: relative;
                top: 0;
                right: 0;
                width: 100%;
                flex-direction: row;
                align-items: center;
                gap: 8px;
                z-index: 1;
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

            /* Table Container */
            .teaching-load-table-container {
                border-radius: 8px;
                overflow: hidden;
            }

            .teaching-load-table-scroll {
                max-height: 50vh;
                overflow-x: auto;
                overflow-y: auto;
                -webkit-overflow-scrolling: touch;
            }

            .teaching-load-table {
                min-width: 1000px; /* Minimum width to maintain readability */
            }

            .teaching-load-table th {
                padding: 10px 6px;
                font-size: 0.7rem;
                white-space: nowrap;
            }

            .teaching-load-table td {
                padding: 8px 6px;
                font-size: 0.7rem;
                white-space: nowrap;
            }

            /* Empty state message */
            .teaching-load-table td[colspan] {
                font-size: 0.75rem;
                padding: 20px 12px;
            }

            /* Action Buttons */
            .action-btns {
                gap: 6px;
            }

            .edit-btn,
            .delete-btn {
                width: 40px;
                height: 28px;
                font-size: 0.9rem;
            }

            .view-slip-btn {
                padding: 6px 10px !important;
                font-size: 0.7rem !important;
            }

            /* Modals - Mobile Optimized */
            .modal-overlay {
                padding: 10px;
                align-items: flex-start;
                padding-top: 60px;
            }

            .modal-box {
                width: 95vw !important;
                max-width: 95vw !important;
                padding: 0 !important;
                margin: 0;
                max-height: 85vh;
                overflow-y: auto;
            }

            /* Add Modal */
            #addModal .modal-box {
                width: 95vw !important;
                max-width: 95vw !important;
            }

            #addModal .modal-header {
                font-size: 1.1rem !important;
                padding: 12px 16px !important;
            }

            #addModal .modal-content {
                padding: 16px !important;
            }

            #addModal .modal-form-group {
                flex-direction: column;
                align-items: flex-start;
                gap: 6px;
                margin-bottom: 12px;
                padding-bottom: 18px;
            }

            #addModal .modal-form-group label {
                min-width: auto;
                width: 100%;
                margin-bottom: 4px;
                font-size: 0.85rem;
            }

            #addModal .modal-form-group input,
            #addModal .modal-form-group select,
            #addModal .modal-form-group textarea {
                width: 100%;
                padding: 10px 12px;
                font-size: 0.9rem;
            }

            #addModal .validation-message {
                position: relative;
                left: 0;
                right: 0;
                bottom: 0;
                padding-left: 0;
                margin-top: 4px;
            }

            #addModal .modal-buttons {
                flex-direction: column;
                gap: 10px;
                margin-top: 16px;
            }

            #addModal .modal-btn {
                width: 100% !important;
                padding: 12px !important;
                font-size: 0.9rem !important;
            }

            /* Edit Modal */
            #editModal .modal-box {
                width: 95vw !important;
                max-width: 95vw !important;
            }

            #editModal .modal-header {
                font-size: 1.1rem !important;
                padding: 12px 16px !important;
            }

            #editModal .modal-content {
                padding: 16px !important;
            }

            #editModal .modal-form-group {
                flex-direction: column;
                align-items: flex-start;
                gap: 6px;
                margin-bottom: 12px;
                padding-bottom: 18px;
            }

            #editModal .modal-form-group label {
                min-width: auto;
                width: 100%;
                margin-bottom: 4px;
                font-size: 0.85rem;
            }

            #editModal .modal-form-group input,
            #editModal .modal-form-group select,
            #editModal .modal-form-group textarea {
                width: 100%;
                padding: 10px 12px;
                font-size: 0.9rem;
            }

            #editModal .validation-message {
                position: relative;
                left: 0;
                right: 0;
                bottom: 0;
                padding-left: 0;
                margin-top: 4px;
            }

            #editModal .modal-buttons {
                flex-direction: column;
                gap: 10px;
                margin-top: 16px;
            }

            #editModal .modal-btn {
                width: 100% !important;
                padding: 12px !important;
                font-size: 0.9rem !important;
            }

            /* Delete Modal */
            #deleteModal .modal-box {
                width: 90vw !important;
                max-width: 90vw !important;
                padding: 24px 20px !important;
            }

            #deleteModal .modal-header {
                font-size: 1.1rem !important;
                margin-bottom: 16px !important;
            }

            #deleteModal .modal-buttons {
                flex-direction: column;
                gap: 10px;
                margin-top: 20px !important;
            }

            #deleteModal .modal-btn {
                width: 100% !important;
                padding: 12px !important;
                font-size: 0.9rem !important;
            }

            /* Slip Modal */
            #slipModal .modal-box {
                width: 95vw !important;
                max-width: 95vw !important;
                padding: 0 !important;
            }

            #slipModal .modal-header {
                font-size: 1rem !important;
                padding: 10px 16px !important;
            }

            #slipModal .slip-content {
                padding: 12px !important;
            }

            #slipImage {
                max-height: 60vh !important;
            }

            #slipModal .close {
                width: 28px !important;
                height: 28px !important;
                font-size: 16px !important;
                top: 8px !important;
                right: 12px !important;
            }

            /* Logic Error Messages */
            .logic-error {
                font-size: 0.8rem !important;
                padding: 8px 12px !important;
            }

            .server-error {
                font-size: 0.8rem !important;
                padding: 8px 12px !important;
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
                                '{{ $pass->pass_slip_arrival_time }}',
                                '{{ addslashes($pass->faculty->faculty_department) }}')">
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
        <div class="modal-box">
            <form class="modal-form" action="{{ route('checker.passes.store') }}" method="POST"
                enctype="multipart/form-data">
                @csrf
                <div class="modal-header"
                    style="background-color: #8B0000; color: white; padding: 14.4px 19.2px; font-size: 19.2px; font-weight: bold; width: 100%; margin: 0; display: flex; align-items: center; justify-content: center; text-align: center; letter-spacing: 0.4px; border-top-left-radius: 8px; border-top-right-radius: 8px;">
                    ADD PASS
                </div>
                <div class="modal-content" style="padding: 19.2px;">
                    <style>
                        #addModal .modal-form-group {
                            display: flex;
                            align-items: center;
                            gap: 4.8px;
                            margin-bottom: 3.2px;
                            padding-bottom: 4.8px;
                            position: relative;
                        }

                        #addModal .modal-form-group label {
                            min-width: 104px;
                            margin-bottom: 0;
                            font-size: 0.8rem;
                            text-align: left;
                        }

                        #addModal .modal-form-group input,
                        #addModal .modal-form-group select {
                            flex: 1;
                            width: 100%;
                            padding: 8px 9.6px;
                            font-size: 0.8rem;
                            border: 1px solid #bbb;
                            border-radius: 4px;
                        }

                        #addModal .validation-message {
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

                        #addModal .modal-buttons {
                            display: flex;
                            gap: 9.6px;
                            justify-content: center;
                            margin-top: 9.6px;
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
        <div class="modal-box">
            <form class="modal-form" id="editForm" method="POST" enctype="multipart/form-data">
                @csrf @method('PUT')
                <div class="modal-header"
                    style="background-color: #8B0000; color: white; padding: 14.4px 19.2px; font-size: 19.2px; font-weight: bold; width: 100%; margin: 0; display: flex; align-items: center; justify-content: center; text-align: center; letter-spacing: 0.4px; border-top-left-radius: 8px; border-top-right-radius: 8px;">
                    UPDATE PASS
                </div>
                <div class="modal-content" style="padding: 19.2px;">
                    <style>
                        #editModal .modal-form-group {
                            display: flex;
                            align-items: center;
                            gap: 4.8px;
                            margin-bottom: 3.2px;
                            padding-bottom: 4.8px;
                            position: relative;
                        }

                        #editModal .modal-form-group label {
                            min-width: 104px;
                            margin-bottom: 0;
                            font-size: 0.8rem;
                            text-align: left;
                        }

                        #editModal .modal-form-group input,
                        #editModal .modal-form-group select {
                            flex: 1;
                            width: 100%;
                            padding: 8px 9.6px;
                            font-size: 0.8rem;
                            border: 1px solid #bbb;
                            border-radius: 4px;
                        }

                        #editModal .validation-message {
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

                        #editModal .modal-buttons {
                            display: flex;
                            gap: 9.6px;
                            justify-content: center;
                            margin-top: 9.6px;
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
            // Initialize button states
            if (id === 'addModal') {
                updateAddButtonState(false);
                validateAdd();
            } else if (id === 'editModal') {
                updateEditButtonState(false);
                validateEdit();
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
            // Open modal directly without calling openModal to avoid conflicts
            document.getElementById('editModal').style.display = 'flex';
            
            // Fill all form fields
            document.getElementById('edit_faculty_id').value = faculty_id;
            document.getElementById('edit_faculty_department').value = department || '';
            document.getElementById('edit_lp_purpose').value = purpose || '';
            document.getElementById('edit_pass_slip_itinerary').value = itinerary || '';
            document.getElementById('edit_pass_slip_date').value = date || '';
            
            // Set time values directly (they're already in 24-hour format)
            document.getElementById('edit_pass_slip_departure_time').value = departure || '';
            document.getElementById('edit_pass_slip_arrival_time').value = arrival || '';
            
            // Set form action
            document.getElementById('editForm').action = '/checker/passes/' + lp_id;
            
            // Clear any existing validation states and messages
            const form = document.getElementById('editForm');
            if (form) {
                const inputs = form.querySelectorAll('input, select, textarea');
                inputs.forEach(input => {
                    input.classList.remove('valid', 'invalid');
                    input.dataset.touched = 'false';
                });
                
                const messages = form.querySelectorAll('.validation-message');
                messages.forEach(msg => msg.textContent = '');
                
                const logicError = form.querySelector('.logic-error');
                if (logicError) {
                    logicError.style.display = 'none';
                    logicError.textContent = '';
                }
            }
            
            // Initialize button state and trigger validation
            updateEditButtonState(false);
            setTimeout(() => {
                validateEdit();
            }, 100);
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
                if (logicBox) {
                    // Only clear non-leave conflict messages
                    const currentMessage = logicBox.textContent;
                    if (!currentMessage || !currentMessage.includes('on leave')) {
                        logicBox.style.display = 'none';
                        logicBox.textContent = '';
                    }
                }
                if (vDate) {
                    const today = new Date();
                    today.setHours(0, 0, 0, 0);
                    const theDate = new Date(date.value);
                    if (theDate < today) {
                        logicOk = false;
                        if (logicBox) {
                            const currentMessage = logicBox.textContent;
                            if (!currentMessage || !currentMessage.includes('on leave')) {
                                logicBox.textContent = 'Date cannot be in the past.';
                                logicBox.style.display = 'block';
                            }
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
                            const currentMessage = logicBox.textContent;
                            if (!currentMessage || !currentMessage.includes('on leave')) {
                                logicBox.textContent = 'Arrival time must be later than departure time.';
                                logicBox.style.display = 'block';
                            }
                        }
                    }
                }
                
                // Check for overlapping requests
                if (logicOk && vFac && vDate && vDep && vArr) {
                    const overlapError = checkPassOverlap(fac.value, date.value, dep.value, arr.value);
                    if (overlapError) {
                        logicOk = false;
                        if (logicBox) {
                            const currentMessage = logicBox.textContent;
                            if (!currentMessage || !currentMessage.includes('on leave')) {
                                logicBox.textContent = overlapError;
                                logicBox.style.display = 'block';
                            }
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
                
                // Check for leave conflicts and pass overlaps (synchronous check like departure/arrival time)
                if (logicOk && vFac && vDate) {
                    const conflictMessage = logicBox ? logicBox.textContent : '';
                    if (conflictMessage && (conflictMessage.includes('on leave') || conflictMessage.includes('already has a pass slip'))) {
                        logicOk = false;
                        if (logicBox) {
                            logicBox.textContent = conflictMessage;
                            logicBox.style.display = 'block';
                        }
                    } else {
                        // Clear conflict messages if no longer valid
                        if (logicBox && (logicBox.textContent.includes('on leave') || logicBox.textContent.includes('already has a pass slip'))) {
                            logicBox.style.display = 'none';
                            logicBox.textContent = '';
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
                
                const isValid = vFac && vPur && vItin && vDate && vDep && vArr && vImg && logicOk;
                updateAddButtonState(isValid);
                return isValid;
            }

            function validateEdit() {
                const fac = document.getElementById('edit_faculty_id');
                const pur = document.getElementById('edit_lp_purpose');
                const itin = document.getElementById('edit_pass_slip_itinerary');
                const date = document.getElementById('edit_pass_slip_date');
                const dep = document.getElementById('edit_pass_slip_departure_time');
                const arr = document.getElementById('edit_pass_slip_arrival_time');
                const img = document.getElementById('edit_lp_image');
                const vFac = isNotEmpty(fac && fac.value);
                const vPur = isNotEmpty(pur && pur.value);
                const vItin = isNotEmpty(itin && itin.value);
                const vDate = isNotEmpty(date && date.value);
                const vDep = isNotEmpty(dep && dep.value);
                const vArr = isNotEmpty(arr && arr.value);
                const vImg = !img || !img.files || img.files.length === 0 || validateImageSize(img);
                // Logical date/time validation
                let logicOk = true;
                const logicBox = document.querySelector('#editModal .logic-error');
                if (logicBox) {
                    // Only clear non-leave conflict messages
                    const currentMessage = logicBox.textContent;
                    if (!currentMessage || !currentMessage.includes('on leave')) {
                        logicBox.style.display = 'none';
                        logicBox.textContent = '';
                    }
                }
                if (vDate) {
                    const today = new Date();
                    today.setHours(0, 0, 0, 0);
                    const theDate = new Date(date.value);
                    if (theDate < today) {
                        logicOk = false;
                        if (logicBox) {
                            const currentMessage = logicBox.textContent;
                            if (!currentMessage || !currentMessage.includes('on leave')) {
                                logicBox.textContent = 'Date cannot be in the past.';
                                logicBox.style.display = 'block';
                            }
                        }
                    }
                }
                if (logicOk && vDep && vArr) {
                    if (dep.value && arr.value && dep.value >= arr.value) {
                        logicOk = false;
                        if (logicBox) {
                            const currentMessage = logicBox.textContent;
                            if (!currentMessage || !currentMessage.includes('on leave')) {
                                logicBox.textContent = 'Arrival time must be later than departure time.';
                                logicBox.style.display = 'block';
                            }
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
                            const currentMessage = logicBox.textContent;
                            if (!currentMessage || !currentMessage.includes('on leave')) {
                                logicBox.textContent = overlapError;
                                logicBox.style.display = 'block';
                            }
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
                
                // Check for leave conflicts and pass overlaps (synchronous check like departure/arrival time)
                if (logicOk && vFac && vDate) {
                    const conflictMessage = logicBox ? logicBox.textContent : '';
                    if (conflictMessage && (conflictMessage.includes('on leave') || conflictMessage.includes('already has a pass slip'))) {
                        logicOk = false;
                        if (logicBox) {
                            logicBox.textContent = conflictMessage;
                            logicBox.style.display = 'block';
                        }
                    } else {
                        // Clear conflict messages if no longer valid
                        if (logicBox && (logicBox.textContent.includes('on leave') || logicBox.textContent.includes('already has a pass slip'))) {
                            logicBox.style.display = 'none';
                            logicBox.textContent = '';
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
                setMessage(img, vImg ? '' : 'Image size must be less than 2MB');
                
                const isValid = vFac && vPur && vItin && vDate && vDep && vArr && vImg && logicOk;
                updateEditButtonState(isValid);
                return isValid;
            }

            ['#addModal [name="faculty_id"]', '#addModal [name="lp_purpose"]', '#addModal [name="pass_slip_itinerary"]',
                '#addModal [name="pass_slip_date"]', '#addModal [name="pass_slip_departure_time"]',
                '#addModal [name="pass_slip_arrival_time"]', '#addModal [name="lp_image"]'
            ].forEach(sel => {
                const el = document.querySelector(sel);
                if (!el) return;
                const evt = el.tagName === 'SELECT' ? 'change' : 'input';
                el.addEventListener(evt, function() {
                    validateAdd();
                    // Also check for conflicts when faculty, date, or times change
                    if (sel.includes('faculty_id') || sel.includes('pass_slip_date') || sel.includes('pass_slip_departure_time') || sel.includes('pass_slip_arrival_time')) {
                        checkLeaveConflictAdd();
                        checkPassOverlapAdd();
                    }
                });
                el.addEventListener('blur', () => {
                    el.dataset.touched = 'true';
                    validateAdd();
                });
            });

            // Add real-time leave conflict checking for date field
            const addDateField = document.querySelector('#addModal [name="pass_slip_date"]');
            if (addDateField) {
                addDateField.addEventListener('change', function() {
                    checkLeaveConflictAdd();
                    // Also check for pass overlaps when date changes
                    checkPassOverlapAdd();
                });
            }

            // Add real-time pass overlap checking for time fields
            const addDepartureField = document.querySelector('#addModal [name="pass_slip_departure_time"]');
            const addArrivalField = document.querySelector('#addModal [name="pass_slip_arrival_time"]');
            if (addDepartureField) {
                addDepartureField.addEventListener('change', checkPassOverlapAdd);
            }
            if (addArrivalField) {
                addArrivalField.addEventListener('change', checkPassOverlapAdd);
            }
            ['#edit_faculty_id', '#edit_lp_purpose', '#edit_pass_slip_itinerary', '#edit_pass_slip_date',
                '#edit_pass_slip_departure_time', '#edit_pass_slip_arrival_time', '#edit_lp_image'
            ].forEach(sel => {
                const el = document.querySelector(sel);
                if (!el) return;
                const evt = el.tagName === 'SELECT' ? 'change' : 'input';
                el.addEventListener(evt, function() {
                    validateEdit();
                    // Also check for conflicts when faculty, date, or times change
                    if (sel.includes('faculty_id') || sel.includes('pass_slip_date') || sel.includes('pass_slip_departure_time') || sel.includes('pass_slip_arrival_time')) {
                        checkLeaveConflictEdit();
                        checkPassOverlapEdit();
                    }
                });
                el.addEventListener('blur', () => {
                    el.dataset.touched = 'true';
                    validateEdit();
                });
            });

            // Add real-time leave conflict checking for edit date field
            const editDateField = document.querySelector('#edit_pass_slip_date');
            if (editDateField) {
                editDateField.addEventListener('change', function() {
                    checkLeaveConflictEdit();
                    // Also check for pass overlaps when date changes
                    checkPassOverlapEdit();
                });
            }

            // Add real-time pass overlap checking for edit time fields
            const editDepartureField = document.querySelector('#edit_pass_slip_departure_time');
            const editArrivalField = document.querySelector('#edit_pass_slip_arrival_time');
            if (editDepartureField) {
                editDepartureField.addEventListener('change', checkPassOverlapEdit);
            }
            if (editArrivalField) {
                editArrivalField.addEventListener('change', checkPassOverlapEdit);
            }

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

        // Button state management functions
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

        // Real-time pass overlap checking functions
        async function checkPassOverlapAdd() {
            const facultySelect = document.querySelector('#addModal [name="faculty_id"]');
            const dateField = document.querySelector('#addModal [name="pass_slip_date"]');
            const departureField = document.querySelector('#addModal [name="pass_slip_departure_time"]');
            const arrivalField = document.querySelector('#addModal [name="pass_slip_arrival_time"]');
            const logicBox = document.querySelector('#addModal .logic-error');
            
            if (!facultySelect || !dateField || !departureField || !arrivalField || 
                !facultySelect.value || !dateField.value || !departureField.value || !arrivalField.value) {
                return;
            }

            try {
                const response = await fetch('/checker/passes/check-pass-overlap', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        faculty_id: facultySelect.value,
                        date: dateField.value,
                        departure_time: departureField.value,
                        arrival_time: arrivalField.value
                    })
                });

                const data = await response.json();
                
                if (logicBox) {
                    if (data.has_overlap) {
                        logicBox.textContent = data.message;
                        logicBox.style.display = 'block';
                    } else {
                        // Only clear if the current message is about pass overlap
                        const currentMessage = logicBox.textContent;
                        if (currentMessage && currentMessage.includes('already has a pass slip')) {
                            logicBox.style.display = 'none';
                            logicBox.textContent = '';
                        }
                    }
                }
                
                // Trigger validation after pass overlap check
                validateAdd();
            } catch (error) {
                console.error('Error checking pass overlap:', error);
            }
        }

        async function checkPassOverlapEdit() {
            const facultySelect = document.querySelector('#edit_faculty_id');
            const dateField = document.querySelector('#edit_pass_slip_date');
            const departureField = document.querySelector('#edit_pass_slip_departure_time');
            const arrivalField = document.querySelector('#edit_pass_slip_arrival_time');
            const logicBox = document.querySelector('#editModal .logic-error');
            const editForm = document.getElementById('editForm');
            const currentId = editForm ? editForm.action.split('/').pop() : null;
            
            if (!facultySelect || !dateField || !departureField || !arrivalField || 
                !facultySelect.value || !dateField.value || !departureField.value || !arrivalField.value) {
                return;
            }

            // Get the original times from the button data attributes
            const editButton = document.querySelector(`button[onclick*="openUpdateModal"][data-id="${currentId}"]`);
            const originalDeparture = editButton ? editButton.getAttribute('data-departure') : null;
            const originalArrival = editButton ? editButton.getAttribute('data-arrival') : null;

            try {
                const response = await fetch('/checker/passes/check-pass-overlap', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        faculty_id: facultySelect.value,
                        date: dateField.value,
                        departure_time: departureField.value,
                        arrival_time: arrivalField.value,
                        exclude_id: currentId,
                        current_departure: originalDeparture,
                        current_arrival: originalArrival
                    })
                });

                const data = await response.json();
                
                if (logicBox) {
                    if (data.has_overlap) {
                        logicBox.textContent = data.message;
                        logicBox.style.display = 'block';
                    } else {
                        // Only clear if the current message is about pass overlap
                        const currentMessage = logicBox.textContent;
                        if (currentMessage && currentMessage.includes('already has a pass slip')) {
                            logicBox.style.display = 'none';
                            logicBox.textContent = '';
                        }
                    }
                }
                
                // Trigger validation after pass overlap check
                validateEdit();
            } catch (error) {
                console.error('Error checking pass overlap:', error);
            }
        }

        // Real-time leave conflict checking functions
        async function checkLeaveConflictAdd() {
            const facultySelect = document.querySelector('#addModal [name="faculty_id"]');
            const dateField = document.querySelector('#addModal [name="pass_slip_date"]');
            const logicBox = document.querySelector('#addModal .logic-error');
            
            if (!facultySelect || !dateField || !facultySelect.value || !dateField.value) {
                if (logicBox) {
                    logicBox.style.display = 'none';
                    logicBox.textContent = '';
                }
                return;
            }

            try {
                const response = await fetch('/checker/passes/check-leave-conflict', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        faculty_id: facultySelect.value,
                        date: dateField.value
                    })
                });

                const data = await response.json();
                
                if (logicBox) {
                    if (data.on_leave) {
                        logicBox.textContent = data.message;
                        logicBox.style.display = 'block';
                    } else {
                        // Only clear if the current message is about leave conflict
                        const currentMessage = logicBox.textContent;
                        if (currentMessage && currentMessage.includes('on leave')) {
                            logicBox.style.display = 'none';
                            logicBox.textContent = '';
                        }
                    }
                }
                
                // Trigger validation after leave conflict check
                validateAdd();
            } catch (error) {
                console.error('Error checking leave conflict:', error);
            }
        }

        async function checkLeaveConflictEdit() {
            const facultySelect = document.querySelector('#edit_faculty_id');
            const dateField = document.querySelector('#edit_pass_slip_date');
            const logicBox = document.querySelector('#editModal .logic-error');
            
            if (!facultySelect || !dateField || !facultySelect.value || !dateField.value) {
                if (logicBox) {
                    logicBox.style.display = 'none';
                    logicBox.textContent = '';
                }
                return;
            }

            try {
                const response = await fetch('/checker/passes/check-leave-conflict', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        faculty_id: facultySelect.value,
                        date: dateField.value
                    })
                });

                const data = await response.json();
                
                if (logicBox) {
                    if (data.on_leave) {
                        logicBox.textContent = data.message;
                        logicBox.style.display = 'block';
                    } else {
                        // Only clear if the current message is about leave conflict
                        const currentMessage = logicBox.textContent;
                        if (currentMessage && currentMessage.includes('on leave')) {
                            logicBox.style.display = 'none';
                            logicBox.textContent = '';
                        }
                    }
                }
                
                // Trigger validation after leave conflict check
                validateEdit();
            } catch (error) {
                console.error('Error checking leave conflict:', error);
            }
        }
    </script>
@endsection
