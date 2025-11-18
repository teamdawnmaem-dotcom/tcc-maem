    @extends('layouts.appdeptHead')

    @section('title', 'Room Management - Tagoloan Community College')
    @section('monitoring-active', 'active')
    @section('rooms-active', 'active')

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
                padding: 6px 19px;
                font-size: 11.2px;
                border: none;
                border-radius: 3.2px;
                background-color: #2ecc71;
                color: #fff;
                cursor: pointer;
                font-weight: bold;
            }

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
                padding: 12.8px 0;
                font-size: 0.88rem;
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
                padding: 9.6px 0;
                text-align: center;
                font-size: 0.8rem;
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

            /* Delete Modal - Scaled button styles (80%) */
            #deleteRoomModal .modal-btn {
                width: 160px;
                padding: 11.2px 0;
                font-size: 0.88rem;
                border-radius: 4.8px;
                box-sizing: border-box;
                display: flex;
                align-items: center;
                justify-content: center;
                text-align: center;
                white-space: nowrap;
            }

            #deleteRoomModal .modal-btn.delete {
                padding: 11.2px 0;
                border-radius: 4.8px;
            }

            #deleteRoomModal .modal-btn.cancel {
                padding: 11.2px 0;
                border-radius: 4.8px;
            }

            #deleteRoomModal .modal-buttons {
                gap: 9.6px;
                margin-top: 14.4px;
            }

            #deleteRoomModal .modal-header {
                font-size: 1.152rem;
                margin-bottom: 16px;
            }

            #deleteRoomModal .modal-box {
                padding: 25.6px;
            }

            /* Slide-down animation for Add/Update Room Modals (mobile only) */
            @keyframes slideDown {
                from {
                    transform: translateY(-100%);
                    opacity: 0;
                }
                to {
                    transform: translateY(0);
                    opacity: 1;
                }
            }

            /* Slide-up animation for Add/Update Room Modals (mobile only) */
            @keyframes slideUp {
                from {
                    transform: translateY(0);
                    opacity: 1;
                }
                to {
                    transform: translateY(-100%);
                    opacity: 0;
                }
            }

            #addRoomModal .modal-box.slide-up {
                animation: slideUp 0.3s ease-out !important;
            }

            #updateRoomModal .modal-box.slide-up {
                animation: slideUp 0.3s ease-out !important;
            }

            /* Mobile responsive design for 430px width */
            @media (max-width: 430px) {
                /* Faculty Header */
                .faculty-header {
                    flex-direction: column;
                    align-items: flex-start;
                    gap: 16px;
                    margin-top: 0;
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

                /* Actions Row - Side by side on mobile (matching User Management) */
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

                /* Table Container - Card Layout on Mobile */
                .faculty-table-container {
                    border-radius: 8px;
                    overflow: visible;
                    background: transparent;
                    box-shadow: none;
                }

                .faculty-table-scroll {
                    max-height: none;
                    overflow: visible;
                    -webkit-overflow-scrolling: touch;
                }

                /* Hide table header on mobile */
                .faculty-table thead {
                    display: none;
                }

                /* Transform table rows into cards */
                .faculty-table {
                    width: 100%;
                    min-width: 0;
                    border-collapse: separate;
                    border-spacing: 0 12px;
                    display: block;
                }

                .faculty-table tbody {
                    display: block;
                }

                .faculty-table tr {
                    display: block;
                    background: #fff;
                    border-radius: 8px;
                    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1), 0 1px 2px rgba(0, 0, 0, 0.06);
                    margin-bottom: 12px;
                    padding: 16px;
                    box-sizing: border-box;
                    border: 1px solid #e0e0e0;
                    transition: box-shadow 0.2s ease, transform 0.2s ease;
                }

                .faculty-table tr:hover {
                    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15), 0 2px 4px rgba(0, 0, 0, 0.1);
                    transform: translateY(-1px);
                }

                .faculty-table tr:last-child {
                    margin-bottom: 0;
                }

                .faculty-table td {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    padding: 10px 0;
                    font-size: 0.85rem;
                    white-space: normal;
                    border: none;
                    text-align: left;
                    color: #222;
                }

                .faculty-table td:before {
                    content: attr(data-label);
                    font-weight: 600;
                    color: #555;
                    margin-right: 12px;
                    flex-shrink: 0;
                    min-width: 100px;
                    font-size: 0.8rem;
                }

                .faculty-table td:not([data-label="Action"]) {
                    border-bottom: 1px solid #f5f5f5;
                }

                .faculty-table td:last-child:not([data-label="Action"]) {
                    border-bottom: none;
                }

                /* Action column styling */
                .faculty-table td[data-label="Action"] {
                    justify-content: flex-end;
                    padding-top: 12px;
                    border-top: 1px solid #f0f0f0;
                    margin-top: 8px;
                }

                .faculty-table td[data-label="Action"]:before {
                    display: none;
                }

                /* Empty state message */
                .faculty-table td[colspan] {
                    display: block;
                    text-align: center;
                    font-size: 0.85rem;
                    padding: 40px 20px;
                    color: #666;
                    font-style: italic;
                }

                .faculty-table td[colspan]:before {
                    display: none;
                }

                /* Action Buttons */
                .action-btns {
                    gap: 8px;
                    justify-content: flex-end;
                }

                .edit-btn,
                .delete-btn {
                    width: 40px;
                    height: 36px;
                    font-size: 1.1rem;
                    border-radius: 6px;
                }

                /* Add Room Modal - Mobile */
                #addRoomModal.modal-overlay {
                    padding: 0 !important;
                    align-items: flex-start !important;
                    justify-content: center !important;
                }

                #addRoomModal .modal-box {
                    width: 100vw !important;
                    max-width: 100vw !important;
                    display: flex !important;
                    flex-direction: column !important;
                    overflow: hidden !important;
                    padding: 0 !important;
                    margin: 0 !important;
                    max-height: 100vh !important;
                    box-sizing: border-box !important;
                    border-radius: 0 !important;
                    border-top-left-radius: 0 !important;
                    border-top-right-radius: 0 !important;
                    animation: slideDown 0.3s ease-out !important;
                }

                #addRoomModal .modal-box form {
                    width: 100% !important;
                    margin: 0 !important;
                    padding: 0 !important;
                    box-sizing: border-box !important;
                }

                #addRoomModal .modal-header {
                    font-size: 1rem !important;
                    padding: 10px 14px !important;
                    flex-shrink: 0 !important;
                    width: 100% !important;
                    box-sizing: border-box !important;
                    border-top-left-radius: 0 !important;
                    border-top-right-radius: 0 !important;
                }

                #addRoomModal .modal-form {
                    overflow: visible !important;
                    padding: 12px 14px !important;
                    width: 100% !important;
                    box-sizing: border-box !important;
                    margin: 0 !important;
                }

                #addRoomModal .modal-form-group {
                    flex-direction: column !important;
                    align-items: flex-start !important;
                    gap: 2px !important;
                    margin-bottom: 6px !important;
                    padding-bottom: 10px !important;
                }

                #addRoomModal .modal-form-group label {
                    min-width: auto !important;
                    width: 100% !important;
                    margin-bottom: 2px !important;
                    font-size: 0.7rem !important;
                }

                #addRoomModal .modal-form-group input,
                #addRoomModal .modal-form-group select {
                    width: 100% !important;
                    padding: 8px 10px !important;
                    font-size: 0.85rem !important;
                }

                #addRoomModal .validation-message {
                    position: relative !important;
                    left: 0 !important;
                    right: 0 !important;
                    bottom: 0 !important;
                    padding-left: 0 !important;
                    margin-top: 2px !important;
                    font-size: 0.65rem !important;
                }

                #addRoomModal .modal-buttons {
                    flex-direction: row !important;
                    justify-content: center !important;
                    align-items: center !important;
                    gap: 0.75rem !important;
                    margin-top: 12px !important;
                }

                #addRoomModal .modal-btn {
                    flex: 1 !important;
                    max-width: none !important;
                    padding: 10px !important;
                    font-size: 0.85rem !important;
                    min-height: 44px !important;
                }

                /* Update Room Modal - Mobile */
                #updateRoomModal.modal-overlay {
                    padding: 0 !important;
                    align-items: flex-start !important;
                    justify-content: center !important;
                }

                #updateRoomModal .modal-box {
                    width: 100vw !important;
                    max-width: 100vw !important;
                    display: flex !important;
                    flex-direction: column !important;
                    overflow: hidden !important;
                    padding: 0 !important;
                    margin: 0 !important;
                    max-height: 100vh !important;
                    box-sizing: border-box !important;
                    border-radius: 0 !important;
                    border-top-left-radius: 0 !important;
                    border-top-right-radius: 0 !important;
                    animation: slideDown 0.3s ease-out !important;
                }

                #updateRoomModal .modal-box form {
                    width: 100% !important;
                    margin: 0 !important;
                    padding: 0 !important;
                    box-sizing: border-box !important;
                }

                #updateRoomModal .modal-header {
                    font-size: 1rem !important;
                    padding: 10px 14px !important;
                    flex-shrink: 0 !important;
                    width: 100% !important;
                    box-sizing: border-box !important;
                    border-top-left-radius: 0 !important;
                    border-top-right-radius: 0 !important;
                }

                #updateRoomModal .modal-form {
                    overflow: visible !important;
                    padding: 12px 14px !important;
                    width: 100% !important;
                    box-sizing: border-box !important;
                    margin: 0 !important;
                }

                #updateRoomModal .modal-form-group {
                    flex-direction: column !important;
                    align-items: flex-start !important;
                    gap: 2px !important;
                    margin-bottom: 6px !important;
                    padding-bottom: 10px !important;
                }

                #updateRoomModal .modal-form-group label {
                    min-width: auto !important;
                    width: 100% !important;
                    margin-bottom: 2px !important;
                    font-size: 0.7rem !important;
                }

                #updateRoomModal .modal-form-group input,
                #updateRoomModal .modal-form-group select {
                    width: 100% !important;
                    padding: 8px 10px !important;
                    font-size: 0.85rem !important;
                }

                #updateRoomModal .validation-message {
                    position: relative !important;
                    left: 0 !important;
                    right: 0 !important;
                    bottom: 0 !important;
                    padding-left: 0 !important;
                    margin-top: 2px !important;
                    font-size: 0.65rem !important;
                }

                #updateRoomModal .modal-buttons {
                    flex-direction: row !important;
                    justify-content: center !important;
                    align-items: center !important;
                    gap: 0.75rem !important;
                    margin-top: 12px !important;
                }

                #updateRoomModal .modal-btn {
                    flex: 1 !important;
                    max-width: none !important;
                    padding: 10px !important;
                    font-size: 0.85rem !important;
                    min-height: 44px !important;
                }

                /* Delete Room Modal - Mobile Compact */
                #deleteRoomModal .modal-box {
                    width: 85vw !important;
                    max-width: 85vw !important;
                    padding: 20px 16px !important;
                    transform: scale(1) !important;
                }

                #deleteRoomModal .modal-header {
                    font-size: 1rem !important;
                    margin-bottom: 12px !important;
                }

                /* Warning Icon and Message - More Compact */
                #deleteRoomModal .modal-box > div[style*="text-align: center"] {
                    margin: 0 !important;
                }

                #deleteRoomModal .modal-box > div[style*="text-align: center"] > div:first-of-type {
                    font-size: 2.5rem !important;
                    margin-bottom: 12px !important;
                }

                #deleteRoomModal .modal-box > div[style*="text-align: center"] > div:nth-of-type(2) {
                    font-size: 0.85rem !important;
                    margin-bottom: 6px !important;
                }

                #deleteRoomModal .modal-box > div[style*="text-align: center"] > div:nth-of-type(3) {
                    font-size: 0.75rem !important;
                    line-height: 1.4 !important;
                }

                #deleteRoomModal .modal-buttons {
                    display: flex !important;
                    flex-direction: row !important;
                    justify-content: center !important;
                    align-items: center !important;
                    gap: 0.75rem !important;
                    margin-top: 16px !important;
                }

                #deleteRoomModal .modal-btn {
                    flex: 1 !important;
                    max-width: 140px !important;
                    width: auto !important;
                    padding: 10px 16px !important;
                    font-size: 0.85rem !important;
                    min-height: 40px !important;
                    box-sizing: border-box !important;
                    display: flex !important;
                    align-items: center !important;
                    justify-content: center !important;
                    text-align: center !important;
                    white-space: nowrap !important;
                }
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

            

            .modal-header {
                font-size: 1.6rem;
                font-weight: bold;
                color: #8B0000;
                text-align: center;
                margin-bottom: 22.4px;
            }

            .modal-img-box {
                border: 2px dashed #222;
                border-radius: 8px;
                width: 176px;
                height: 144px;
                display: flex;
                align-items: center;
                justify-content: center;
                margin-bottom: 14.4px;
            }

            .modal-plus {
                font-size: 2.8rem;
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
                gap: 6.4px;
                position: relative;
                padding-bottom: 14.4px;
            }

            .modal-form-group label {
                margin-bottom: 4.8px;
                font-size: 0.8rem;
            }

            .modal-form-group input,
            .modal-form-group select {
                width: 100%;
                padding: 8px 9.6px;
                font-size: 0.8rem;
                border: 1px solid #bbb;
                border-radius: 4px;
            }
            .modal-form-group input.valid,
            .modal-form-group select.valid { border-color:#2ecc71; box-shadow:0 0 0 2px rgba(46,204,113,0.1); }
            .modal-form-group input.invalid,
            .modal-form-group select.invalid { border-color:#ff3636; box-shadow:0 0 0 2px rgba(255,54,54,0.1); }
            .validation-message { position:absolute; left:0; right:9.6px; bottom:0; font-size:0.68rem; color:#ff3636; pointer-events:none; padding-left:9.6px; }

            .modal-btn {
                width: 100%;
                padding: 11.2px 0;
                font-size: 0.88rem;
                font-weight: bold;
                border: none;
                border-radius: 4.8px;
                margin-top: 11.2px;
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
                background: #fff;
                color: #800000;
                border: 1.6px solid #800000;
                border-radius: 6.4px;
                padding: 8px 16px;
                cursor: pointer;
                transition: all 0.3s ease;
            }

            .modal-btn.cancel:hover {
                background: #800000;
                color: #fff;
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
                gap: 14.4px;
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
                gap: 9.6px;
                justify-content: center;
            }

            .modal-box {
                align-items: center;
                width: 320px;
                max-width: 95vw;
                background: #fff;
                padding: 0;
                overflow: hidden;
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
            #deleteRoomModal .modal-header { margin-top: 25px; margin-bottom: 0; }


            .modal-form-group label {
                min-width: 104px;
                text-align: left;
                font-size: 0.8rem;
                color: #222;
            }

            .modal-form-group input {
                flex: 1;
                width: 100%;
                padding: 8px 9.6px;
                font-size: 0.8rem;
                border: 1px solid #bbb;
                border-radius: 4px;
            }

            .modal-buttons {
                display: flex;
                gap: 9.6px;
                justify-content: center;
                margin-top: 8px;
            }

            .modal-btn {
                width: 50%;
                padding: 11.2px 0;
                font-size: 0.88rem;
                font-weight: bold;
                border: none;
                border-radius: 4.8px;
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
        </style>
    @endsection

    @section('content')
    @include('partials.flash')
    @include('partials.flashupdate')
    @include('partials.flashdelete')

        <div class="faculty-header">
            <div class="faculty-title-group">
                <div class="faculty-title">Room Management</div>
                <div class="faculty-subtitle"></div>
            </div>
            <div class="faculty-actions-row">
                <input type="text" class="search-input" id="roomSearch" placeholder="Search...">
                <button class="add-btn" onclick="openModal('addRoomModal')">Add</button>
            </div>
        </div>

        <div class="faculty-table-container">
            <div class="faculty-table-scroll">
            <table class="faculty-table">
                <thead>
                    <tr>
                        <th>Room No.</th>
                        <th>Room Name</th>
                        <th>Building No.</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="roomTable">
                    @forelse($rooms as $room)
                    <tr>
                        <td data-label="Room No.">{{ $room->room_no }}</td>
                        <td data-label="Room Name">{{ $room->room_name }}</td>
                        <td data-label="Building No.">{{ $room->room_building_no }}</td>
                        <td data-label="Action">
                            <div class="action-btns">
                                <button class="edit-btn" 
                                    onclick="openUpdateModal({{ $room->room_no }}, '{{ $room->room_name }}', '{{ $room->room_building_no }}')">&#9998;</button>
                                <button class="delete-btn" 
                                    onclick="openDeleteModal({{ $room->room_no }})">&#128465;</button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" style="text-align:center; font-style:italic; color:#666;">
                            No Registered Camera found.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
            </div>
        </div>

        <!-- Add Room Modal -->
        <div id="addRoomModal" class="modal-overlay" style="display:none;">
            <div class="modal-box" style="padding: 0; overflow: hidden; border-radius: 8px;">
                <form method="POST" action="{{ route('deptHead.room.store') }}" style="padding: 0;">
                    @csrf
                    <div class="modal-header" style="
                        background-color: #8B0000; color: white; padding: 18px 24px; font-size: 24px; font-weight: bold; width: 100%; margin: 0; display: flex; align-items: center; justify-content: center; text-align: center; letter-spacing: 0.5px; border-top-left-radius: 8px; border-top-right-radius: 8px;">
                        ADD ROOM
                    </div>
                    <div class="modal-form" style="padding: 24px 24px 24px;">
                        <style>
                            #addRoomModal .modal-form-group { display:flex; align-items:center; gap:4px; margin-bottom:2px; padding-bottom:4px; position:relative; }
                            #addRoomModal .modal-form-group label { min-width:130px; margin-bottom:0; font-size:1rem; text-align:left; }
                            #addRoomModal .modal-form-group input, #addRoomModal .modal-form-group select { flex:1; width:100%; padding:10px 12px; font-size:1rem; border:1px solid #bbb; border-radius:5px; }
                            #addRoomModal .validation-message { font-size:0.8rem; left:130px; right:10px; bottom:-6px; padding-left:10px; line-height:1.1; position:absolute; color:#ff3636; pointer-events:none; }
                            #addRoomModal .modal-buttons { display:flex; gap:12px; justify-content:center; margin-top:12px; }
                            #addRoomModal .modal-btn.add { background: transparent; border: 2px solid #2e7d32; color: #2e7d32; }
                            #addRoomModal .modal-btn.add:hover { background: #2e7d32; color: #fff; border-color: #2e7d32; }
                        </style>
                        <div class="modal-form-group">
                            <label>Room Name :</label>
                            <input type="text" name="room_name" placeholder="Enter room name">
                        </div>
                        <div class="modal-form-group">
                            <label>Building No. :</label>
                            <input type="text" name="room_building_no" placeholder="Enter building number">
                        </div>
                        <div class="modal-buttons">
                            <button type="submit" class="modal-btn add">Add</button>
                            <button type="button" class="modal-btn cancel" onclick="closeModal('addRoomModal')">Cancel</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Update Room Modal -->
        <div id="updateRoomModal" class="modal-overlay" style="display:none;">
            <div class="modal-box" style="padding: 0; overflow: hidden; border-radius: 8px;">
                <form method="POST" id="updateRoomForm" style="padding: 0;">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="room_no" id="updateRoomNo">
                    <div class="modal-header" style="
                        background-color: #8B0000; color: white; padding: 18px 24px; font-size: 24px; font-weight: bold; width: 100%; margin: 0; display: flex; align-items: center; justify-content: center; text-align: center; letter-spacing: 0.5px; border-top-left-radius: 8px; border-top-right-radius: 8px;">
                        UPDATE ROOM
                    </div>
                    <div class="modal-form" style="padding: 24px 24px 24px;">
                        <style>
                            #updateRoomModal .modal-form-group { display:flex; align-items:center; gap:4px; margin-bottom:2px; padding-bottom:4px; position:relative; }
                            #updateRoomModal .modal-form-group label { min-width:130px; margin-bottom:0; font-size:1rem; text-align:left; }
                            #updateRoomModal .modal-form-group input, #updateRoomModal .modal-form-group select { flex:1; width:100%; padding:10px 12px; font-size:1rem; border:1px solid #bbb; border-radius:5px; }
                            #updateRoomModal .validation-message { font-size:0.8rem; left:130px; right:10px; bottom:-6px; padding-left:10px; line-height:1.1; position:absolute; color:#ff3636; pointer-events:none; }
                            #updateRoomModal .modal-buttons { display:flex; gap:12px; justify-content:center; margin-top:12px; }
                            #updateRoomModal .modal-btn.update { background: #7cc6fa; color: #fff; border: 2px solid #7cc6fa; }
                            #updateRoomModal .modal-btn.update:hover { background: #5bb3f5; color: #fff; border-color: #5bb3f5; }
                        </style>
                        <div class="modal-form-group">
                            <label>Room Name :</label>
                            <input type="text" name="room_name" id="updateRoomName">
                        </div>
                        <div class="modal-form-group">
                            <label>Building No. :</label>
                            <input type="text" name="room_building_no" id="updateBuildingNo">
                        </div>
                        <div class="modal-buttons">
                            <button type="submit" class="modal-btn update">Update</button>
                            <button type="button" class="modal-btn cancel" onclick="closeModal('updateRoomModal')">Cancel</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Delete Room Modal -->
        <div id="deleteRoomModal" class="modal-overlay" style="display:none;">
            <form id="deleteRoomForm" method="POST" class="modal-box" style="transform: scale(0.8); transform-origin: center;">
                @csrf
                @method('DELETE')
                <div class="modal-header delete">DELETE ROOM</div>

                <!-- Warning Icon and Message -->
                <div style="text-align: center; margin:0 px 0;">
                    <div style="font-size: 3.2rem; color: #ff3636; margin-bottom: 16px;">⚠️</div>
                    <div style="font-size: 0.96rem; color: #333; margin-bottom: 8px; font-weight: bold;">Are you sure?</div>
                    <div style="font-size: 0.8rem; color: #666; line-height: 1.5;">This action cannot be undone.<br> The room and its associated camera and live feed will be
                        permanently deleted.</div>
                </div>

                <!-- Action Buttons -->
                <div class="modal-buttons">
                    <button type="submit" class="modal-btn delete">Delete</button>
                    <button type="button" class="modal-btn cancel" onclick="closeModal('deleteRoomModal')">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    @endsection

    @section('scripts')
    <script>
        // Store existing room names for validation
        const existingRoomNames = @json($rooms->pluck('room_name')->toArray());
        
        function openModal(modalId){
            const modal = document.getElementById(modalId);
            if (!modal) return;

            // For Add Room Modal and Update Room Modal, ensure slide-up class is removed for slide-down animation
            if (modalId === 'addRoomModal' || modalId === 'updateRoomModal') {
                const modalBox = modal.querySelector('.modal-box');
                if (modalBox) {
                    modalBox.classList.remove('slide-up');
                }
            }

            modal.style.display = 'flex';
        }
        function resetModalForm(modalId){
            const modal = document.getElementById(modalId);
            if(!modal) return;
            const form = modal.querySelector('form');
            if(!form) return;

        const fields = form.querySelectorAll('input, select, textarea');
        fields.forEach(function(el){
            if(el.type === 'hidden'){
                return;
            }
                if(el.tagName === 'SELECT'){
                    el.value = '';
                } else if (el.type === 'checkbox' || el.type === 'radio'){
                    el.checked = false;
                } else {
                    el.value = '';
                }
                el.classList.remove('valid','invalid');
                el.dataset.touched = 'false';
            });

            form.querySelectorAll('.validation-message').forEach(function(msg){
                msg.textContent = '';
            });

            window.roomSubmitAttempt = false;
        }
        function closeModal(modalId){
            const modal = document.getElementById(modalId);
            if (!modal) return;

            // For Add Room Modal and Update Room Modal, add slide-up animation on mobile
            if (modalId === 'addRoomModal' || modalId === 'updateRoomModal') {
                const modalBox = modal.querySelector('.modal-box');
                if (modalBox) {
                    // Add slide-up animation class
                    modalBox.classList.add('slide-up');
                    
                    // Wait for animation to complete, then hide modal
                    setTimeout(() => {
                        modal.style.display = 'none';
                        modalBox.classList.remove('slide-up');
                        resetModalForm(modalId);
                    }, 300); // Match animation duration
                    return;
                }
            }

            // For other modals or if animation element not found, hide immediately
            modal.style.display = 'none';
            if(modalId === 'addRoomModal' || modalId === 'updateRoomModal'){
                resetModalForm(modalId);
            }
        }

        function openUpdateModal(room_no, room_name, room_building_no){
            openModal('updateRoomModal');
            document.getElementById('updateRoomNo').value = room_no;
            document.getElementById('updateRoomName').value = room_name;
            document.getElementById('updateBuildingNo').value = room_building_no;
            document.getElementById('updateRoomForm').action = '/deptHead/rooms/' + room_no;
            
            // Store original room name for uniqueness validation
            const form = document.getElementById('updateRoomForm');
            form.dataset.origRoomName = room_name || '';
        }

        function openDeleteModal(room_no){
            openModal('deleteRoomModal');
            document.getElementById('deleteRoomForm').action = '/deptHead/rooms/' + room_no;
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
        if(row.classList.contains('no-results')) return;

        let text = row.textContent.toLowerCase();
        if(text.includes(searchTerm)){
            row.style.display = '';
            anyVisible = true;
        } else {
            row.style.display = 'none';
        }
    });

    // Handle "no results" row
    let tbody = document.querySelector('.faculty-table tbody');
    let noResultsRow = tbody.querySelector('.no-results');

    if(!anyVisible){
        if(!noResultsRow){
            noResultsRow = document.createElement('tr');
            noResultsRow.classList.add('no-results');
            noResultsRow.innerHTML = `<td colspan="4" style="text-align:center; padding:20px; color:#999; font-style:italic;">No results found</td>`;
            tbody.appendChild(noResultsRow);
        }
    } else {
        if(noResultsRow) noResultsRow.remove();
    }
});

// =========================
// Client-side Validation (Room forms)
// =========================
(function(){
    function trim(v){ return (v||'').trim(); }
    function isNotEmpty(v){ return trim(v).length>0; }
    function isRoomNameTaken(value, currentRoomName = '') {
        const trimmedValue = trim(value);
        if (!trimmedValue) return false; // Don't check if empty
        if (trimmedValue === currentRoomName) return false; // Don't check against current room's own name
        return existingRoomNames.includes(trimmedValue);
    }
    function setValidity(el, ok){ if(!el) return; const show = el.dataset.touched==='true' || window.roomSubmitAttempt===true; el.classList.remove('valid','invalid'); if(!show) return; el.classList.add(ok?'valid':'invalid'); }
    function setMessage(el, msg){ if(!el) return; const g=el.closest('.modal-form-group'); if(!g) return; let m=g.querySelector('.validation-message'); if(!m){ m=document.createElement('div'); m.className='validation-message'; g.appendChild(m);} const show = el.dataset.touched==='true' || window.roomSubmitAttempt===true; m.textContent= show ? (msg||'') : ''; }

    function validateAdd(){
        const name = document.querySelector("#addRoomModal [name='room_name']");
        const bno = document.querySelector("#addRoomModal [name='room_building_no']");
        const vName = isNotEmpty(name&&name.value) && !isRoomNameTaken(name&&name.value);
        const vBno = isNotEmpty(bno&&bno.value);
        setValidity(name,vName); setMessage(name,vName?'':(isNotEmpty(name&&name.value)?'Room name is already taken':'Room name is required'));
        setValidity(bno,vBno); setMessage(bno,vBno?'':'Building number is required');
        return vName && vBno;
    }

    function validateUpdate(){
        const name = document.getElementById('updateRoomName');
        const bno = document.getElementById('updateBuildingNo');
        const form = document.getElementById('updateRoomForm');
        const origRoomName = form ? form.dataset.origRoomName : '';
        const vName = isNotEmpty(name&&name.value) && !isRoomNameTaken(name&&name.value, origRoomName);
        const vBno = isNotEmpty(bno&&bno.value);
        setValidity(name,vName); setMessage(name,vName?'':(isNotEmpty(name&&name.value)?'Room name is already taken':'Room name is required'));
        setValidity(bno,vBno); setMessage(bno,vBno?'':'Building number is required');
        return vName && vBno;
    }

    ['#addRoomModal [name="room_name"]','#addRoomModal [name="room_building_no"]'].forEach(sel=>{
        const el=document.querySelector(sel); if(!el) return; const evt=el.tagName==='SELECT'?'change':'input'; el.addEventListener(evt, validateAdd); el.addEventListener('blur', ()=>{ el.dataset.touched='true'; validateAdd(); });
    });
    ['#updateRoomName','#updateBuildingNo'].forEach(sel=>{
        const el=document.querySelector(sel); if(!el) return; el.addEventListener('input', validateUpdate);
    });

    (function(){
        const addForm = document.querySelector('#addRoomModal form');
        if(addForm){ addForm.addEventListener('submit', function(e){ window.roomSubmitAttempt=true; if(!validateAdd()){ e.preventDefault(); }}); }
        const updForm = document.getElementById('updateRoomForm');
        if(updForm){ updForm.addEventListener('submit', function(e){ window.roomSubmitAttempt=true; if(!validateUpdate()){ e.preventDefault(); }}); }
    })();
})();

    // Close + reset when clicking outside (overlay)
    document.addEventListener('click', function(e){
        if(e.target.classList && e.target.classList.contains('modal-overlay')){
            const overlayId = e.target.id;
            // Use closeModal function to handle animations properly
            closeModal(overlayId);
        }
    });
    </script>
    @endsection