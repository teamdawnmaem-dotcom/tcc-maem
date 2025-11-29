@extends('layouts.appChecker')

@section('title', 'Faculty Account Management - Tagoloan Community College')
@section('checker-account-active', 'active')

@section('styles')
    <style>
        /* ====== Header & Actions ====== */
        /* Desktop: Match User Account Management layout */
        .faculty-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 32px;
            position: relative;
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
            width: 420px;
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
            padding: 6.4px 12.8px;
            font-size: 0.72rem;
            border: none;
            border-radius: 4px;
            background-color: #8B0000;
            color: #fff;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
            width: auto;
            height: auto;
        }

        .view-btn:hover {
            background-color: #6d0000;
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

        /* Delete Modal - Scaled button styles (80%) */
        #deleteFacultyModal .modal-btn {
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

        #deleteFacultyModal .modal-btn.delete {
            padding: 11.2px 0;
            border-radius: 4.8px;
        }

        #deleteFacultyModal .modal-btn.cancel {
            padding: 11.2px 0;
            border-radius: 4.8px;
        }

        #deleteFacultyModal .modal-buttons {
            gap: 9.6px;
            margin-top: 14.4px;
        }

        #deleteFacultyModal .modal-header {
            font-size: 1.152rem;
            margin-bottom: 16px;
        }

        #deleteFacultyModal .modal-box {
            padding: 25.6px;
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

        /* Slide-down animation for Add/Update Faculty Modals (mobile only) */
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

        /* Slide-up animation for Add/Update Faculty Modals (mobile only) */
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

        #addFacultyModal .modal-box.slide-up {
            animation: slideUp 0.3s ease-out !important;
        }

        #updateFacultyModal .modal-box.slide-up {
            animation: slideUp 0.3s ease-out !important;
        }

        /* ====== Mobile Responsive Styles ====== */
        
        /* Tablet and below (768px) */
        @media (max-width: 768px) {
            /* Header adjustments */
            .faculty-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 16px;
                position: relative;
            }

            .faculty-title {
                font-size: 1.5rem;
            }

            .faculty-subtitle {
                font-size: 0.75rem;
                margin-bottom: 16px;
            }

            /* Actions row - override absolute positioning for tablet/mobile */
            .faculty-actions-row {
                position: relative !important;
                top: 0 !important;
                right: 0 !important;
                width: 100%;
                flex-direction: column;
                gap: 12px;
                z-index: 1 !important;
            }

            .search-input {
                width: 100%;
                padding: 10px 12px;
                font-size: 14px;
                border-radius: 4px;
            }

            .add-btn {
                width: 100%;
                padding: 12px;
                font-size: 14px;
                border-radius: 4px;
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
            .view-btn {
                padding: 6px 12px !important;
                font-size: 0.75rem !important;
                width: auto;
                height: auto;
            }

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

            /* Delete Faculty Modal - Mobile Compact */
            #deleteFacultyModal .modal-box {
                width: 85vw !important;
                max-width: 85vw !important;
                padding: 20px 16px !important;
                transform: scale(1) !important;
            }

            #deleteFacultyModal .modal-header {
                font-size: 1rem !important;
                margin-bottom: 12px !important;
            }

            /* Warning Icon and Message - More Compact */
            #deleteFacultyModal .modal-box > div[style*="text-align: center"] {
                margin: 0 !important;
            }

            #deleteFacultyModal .modal-box > div[style*="text-align: center"] > div:first-of-type {
                font-size: 2.5rem !important;
                margin-bottom: 12px !important;
            }

            #deleteFacultyModal .modal-box > div[style*="text-align: center"] > div:nth-of-type(2) {
                font-size: 0.85rem !important;
                margin-bottom: 6px !important;
            }

            #deleteFacultyModal .modal-box > div[style*="text-align: center"] > div:nth-of-type(3) {
                font-size: 0.75rem !important;
                line-height: 1.4 !important;
            }

            #deleteFacultyModal .modal-buttons {
                display: flex !important;
                flex-direction: row !important;
                justify-content: center !important;
                align-items: center !important;
                gap: 0.75rem !important;
                margin-top: 16px !important;
            }

            #deleteFacultyModal .modal-btn {
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

            /* View images modal */
            .images-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .view-images-container {
                max-height: 300px;
                padding: 8px;
            }

            /* Fixed-size image frame for consistent sizing */
            .image-item {
                width: 100%;
                height: 120px;
                min-height: 120px;
                max-height: 120px;
                display: flex;
                align-items: center;
                justify-content: center;
                background: #f5f5f5;
                border: 2px solid #e0e0e0;
                border-radius: 8px;
                overflow: hidden;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
                transition: transform 0.2s ease;
                position: relative;
            }

            .image-item:hover {
                transform: scale(1.05);
            }

            /* Images centered and scaled proportionally inside frame */
            .image-item img {
                width: 100%;
                height: 100%;
                object-fit: contain;
                object-position: center;
                border-radius: 6px;
            }

            /* View images modal padding */
            #viewImagesModal > .modal-box > div {
                padding: 16px !important;
            }

            /* Teaching loads modal */
            #viewTeachingLoadsModal.modal-overlay {
                padding: 10px !important;
                align-items: flex-start !important;
                padding-top: 20px !important;
            }

            #viewTeachingLoadsModal .modal-box {
                width: calc(100vw - 20px) !important;
                max-width: calc(100vw - 20px) !important;
                margin: 0 auto !important;
                padding: 0 !important;
                max-height: 90vh !important;
                overflow-y: auto !important;
            }

            #viewTeachingLoadsModal .modal-header-custom {
                font-size: 1rem !important;
                padding: 10px 12px !important;
            }

            #teachingLoadsContainer {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                max-height: 50vh !important;
            }

            #teachingLoadsTable {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }

            #teachingLoadsTable table {
                min-width: 0 !important;
                width: 100% !important;
                font-size: 0.75rem !important;
                table-layout: auto;
            }

            #teachingLoadsTable th,
            #teachingLoadsTable td {
                padding: 6px 3px !important;
                font-size: 0.7rem !important;
                white-space: normal !important;
                word-break: break-word;
                line-height: 1.3;
            }

            #teachingLoadsTable th {
                font-size: 0.7rem !important;
            }

            #facultyInfo {
                padding: 10px 8px !important;
                margin-bottom: 12px !important;
                flex-direction: column !important;
                align-items: flex-start !important;
            }

            #facultyInfo > div:first-child {
                width: 100% !important;
                margin-bottom: 10px !important;
            }

            #facultyInfo > div:last-child {
                width: 100% !important;
                margin-left: 0 !important;
                justify-content: flex-start !important;
            }

            #facultyName {
                font-size: 0.95rem !important;
            }

            #facultyDepartment {
                font-size: 0.85rem !important;
            }

            /* Teaching loads modal padding */
            #viewTeachingLoadsModal > .modal-box > div {
                padding: 10px 8px !important;
            }

            /* Close button - wider on mobile */
            #viewTeachingLoadsModal .modal-btn.cancel {
                width: 50% !important;
                min-width: 120px !important;
                padding: 10px 16px !important;
                font-size: 0.85rem !important;
            }

            #noTeachingLoads {
                padding: 16px 8px !important;
                font-size: 0.8rem !important;
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
            .view-btn {
                padding: 6px 12px !important;
                font-size: 0.75rem !important;
                width: auto;
                height: auto;
            }

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

            /* Fixed-size image frame for consistent sizing */
            .image-item {
                width: 100%;
                height: 120px;
                min-height: 120px;
                max-height: 120px;
                display: flex;
                align-items: center;
                justify-content: center;
                background: #f5f5f5;
                border: 2px solid #e0e0e0;
                border-radius: 8px;
                overflow: hidden;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
                transition: transform 0.2s ease;
                position: relative;
            }

            .image-item:hover {
                transform: scale(1.05);
            }

            /* Images centered and scaled proportionally inside frame */
            .image-item img {
                width: 100%;
                height: 100%;
                object-fit: contain;
                object-position: center;
                border-radius: 6px;
            }

            .view-images-container {
                max-height: 250px;
                padding: 8px;
            }

            /* Teaching loads modal - small phones */
            #viewTeachingLoadsModal.modal-overlay {
                padding: 5px !important;
                align-items: flex-start !important;
                padding-top: 10px !important;
            }

            #viewTeachingLoadsModal .modal-box {
                width: calc(100vw - 10px) !important;
                max-width: calc(100vw - 10px) !important;
                margin: 0 auto !important;
                border-radius: 8px !important;
                max-height: 95vh !important;
            }

            #viewTeachingLoadsModal .modal-header-custom {
                font-size: 0.9rem !important;
                padding: 8px 10px !important;
            }

            #viewTeachingLoadsModal > .modal-box > div {
                padding: 8px 6px !important;
            }

            #teachingLoadsContainer {
                max-height: 55vh !important;
            }

            #teachingLoadsTable table {
                min-width: 0 !important;
                width: 100% !important;
                font-size: 0.65rem !important;
            }

            #teachingLoadsTable th,
            #teachingLoadsTable td {
                padding: 5px 2px !important;
                font-size: 0.65rem !important;
                white-space: normal !important;
                word-break: break-word;
                line-height: 1.2;
            }

            #teachingLoadsTable th {
                font-size: 0.65rem !important;
            }

            #facultyInfo {
                padding: 8px 6px !important;
                margin-bottom: 10px !important;
                flex-direction: column !important;
                align-items: flex-start !important;
            }

            #facultyInfo > div:first-child {
                width: 100% !important;
                margin-bottom: 8px !important;
            }

            #facultyInfo > div:last-child {
                width: 100% !important;
                margin-left: 0 !important;
                justify-content: flex-start !important;
            }

            #facultyName {
                font-size: 0.85rem !important;
            }

            #facultyDepartment {
                font-size: 0.75rem !important;
            }

            #viewTeachingLoadsModal .modal-btn.cancel {
                width: 60% !important;
                min-width: 100px !important;
                padding: 8px 14px !important;
                font-size: 0.8rem !important;
            }

            #noTeachingLoads {
                padding: 12px 6px !important;
                font-size: 0.75rem !important;
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

        /* Mobile Card Layout - max-width: 430px */
        @media (max-width: 430px) {
            /* Header adjustments for mobile */
            .faculty-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 16px;
                margin-bottom: 20px;
                position: relative;
            }

            /* Search bar and Add button - horizontal, centered, evenly spaced */
            .faculty-actions-row {
                position: relative !important;
                top: 0 !important;
                right: 0 !important;
                width: 100%;
                flex-direction: row !important;
                justify-content: center !important;
                align-items: center !important;
                gap: 12px !important;
                margin-bottom: 16px !important;
                z-index: 1 !important;
            }

            .search-input {
                flex: 1 !important;
                min-width: 0 !important;
                max-width: none !important;
                width: auto !important;
                padding: 10px 12px !important;
                font-size: 0.9rem !important;
            }

            .add-btn {
                flex: 0 0 auto !important;
                width: auto !important;
                min-width: 120px !important;
                padding: 11.2px 20px !important;
                font-size: 0.88rem !important;
                border-radius: 4.8px !important;
                min-height: 44px !important;
                box-sizing: border-box !important;
                white-space: nowrap !important;
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
                cursor: pointer;
            }

            .faculty-table tr:hover {
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15), 0 2px 4px rgba(0, 0, 0, 0.1);
                transform: translateY(-1px);
                background: #fff2e6;
            }

            .faculty-table tr:last-child {
                margin-bottom: 0;
            }

            .faculty-table td {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 10px 0;
                font-size: 0.8rem;
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
                min-width: 110px;
                font-size: 0.75rem;
            }

            .faculty-table td:not(:last-child) {
                border-bottom: 1px solid #f5f5f5;
            }

            /* Right-align action buttons in cards */
            /* For Images row: right-align View Image button */
            .faculty-table td[data-label="Images"] {
                justify-content: space-between;
            }

            .faculty-table td[data-label="Images"] .view-btn {
                margin-left: auto;
            }

            /* For Action row: right-align the button group */
            .faculty-table td[data-label="Action"] {
                justify-content: space-between;
            }

            .faculty-table td[data-label="Action"] .action-btns {
                justify-content: flex-end;
                gap: 6px;
                margin-left: auto;
            }

            /* Resize all action buttons to match - View, Edit, Delete */
            .faculty-table td .view-btn {
                padding: 6px 12px !important;
                font-size: 0.75rem !important;
                width: auto;
                height: auto;
                flex-shrink: 0;
            }

            .faculty-table td .edit-btn,
            .faculty-table td .delete-btn {
                width: 32px;
                height: 28px;
                font-size: 0.9rem;
                flex-shrink: 0;
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

            /* Add Faculty Modal - Mobile */
            #addFacultyModal.modal-overlay {
                padding: 0 !important;
                align-items: flex-start !important;
                justify-content: center !important;
            }

            #addFacultyModal .modal-box {
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

            #addFacultyModal .modal-box form {
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                box-sizing: border-box !important;
            }

            #addFacultyModal .modal-header {
                font-size: 1rem !important;
                padding: 10px 14px !important;
                flex-shrink: 0 !important;
                width: 100% !important;
                box-sizing: border-box !important;
                border-top-left-radius: 0 !important;
                border-top-right-radius: 0 !important;
            }

            #addFacultyModal .modal-form {
                overflow: visible !important;
                padding: 12px 14px !important;
                width: 100% !important;
                box-sizing: border-box !important;
                margin: 0 !important;
            }

            #addFacultyModal .modal-form-group {
                flex-direction: column !important;
                align-items: flex-start !important;
                gap: 2px !important;
                margin-bottom: 6px !important;
                padding-bottom: 10px !important;
            }

            #addFacultyModal .modal-form-group label {
                min-width: auto !important;
                width: 100% !important;
                margin-bottom: 2px !important;
                font-size: 0.7rem !important;
            }

            #addFacultyModal .modal-form-group input,
            #addFacultyModal .modal-form-group select,
            #addFacultyModal .modal-form-group textarea {
                width: 100% !important;
                padding: 8px 10px !important;
                font-size: 0.85rem !important;
            }

            #addFacultyModal .validation-message {
                position: relative !important;
                left: 0 !important;
                right: 0 !important;
                bottom: 0 !important;
                padding-left: 0 !important;
                margin-top: 2px !important;
                font-size: 0.65rem !important;
            }

            #addFacultyModal .modal-buttons {
                flex-direction: row !important;
                justify-content: center !important;
                align-items: center !important;
                gap: 0.75rem !important;
                margin-top: 12px !important;
            }

            #addFacultyModal .modal-btn {
                flex: 1 !important;
                max-width: none !important;
                padding: 10px !important;
                font-size: 0.85rem !important;
                min-height: 44px !important;
            }

            /* Update Faculty Modal - Mobile */
            #updateFacultyModal.modal-overlay {
                padding: 0 !important;
                align-items: flex-start !important;
                justify-content: center !important;
            }

            #updateFacultyModal .modal-box {
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

            #updateFacultyModal .modal-box form {
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                box-sizing: border-box !important;
            }

            #updateFacultyModal .modal-header {
                font-size: 1rem !important;
                padding: 10px 14px !important;
                flex-shrink: 0 !important;
                width: 100% !important;
                box-sizing: border-box !important;
                border-top-left-radius: 0 !important;
                border-top-right-radius: 0 !important;
            }

            #updateFacultyModal .modal-form {
                overflow: visible !important;
                padding: 12px 14px !important;
                width: 100% !important;
                box-sizing: border-box !important;
                margin: 0 !important;
            }

            #updateFacultyModal .modal-form-group {
                flex-direction: column !important;
                align-items: flex-start !important;
                gap: 2px !important;
                margin-bottom: 6px !important;
                padding-bottom: 10px !important;
            }

            #updateFacultyModal .modal-form-group label {
                min-width: auto !important;
                width: 100% !important;
                margin-bottom: 2px !important;
                font-size: 0.7rem !important;
            }

            #updateFacultyModal .modal-form-group input,
            #updateFacultyModal .modal-form-group select,
            #updateFacultyModal .modal-form-group textarea {
                width: 100% !important;
                padding: 8px 10px !important;
                font-size: 0.85rem !important;
            }

            #updateFacultyModal .validation-message {
                position: relative !important;
                left: 0 !important;
                right: 0 !important;
                bottom: 0 !important;
                padding-left: 0 !important;
                margin-top: 2px !important;
                font-size: 0.65rem !important;
            }

            #updateFacultyModal .modal-buttons {
                flex-direction: row !important;
                justify-content: center !important;
                align-items: center !important;
                gap: 0.75rem !important;
                margin-top: 12px !important;
            }

            #updateFacultyModal .modal-btn {
                flex: 1 !important;
                max-width: none !important;
                padding: 10px !important;
                font-size: 0.85rem !important;
                min-height: 44px !important;
            }

            /* Delete Faculty Modal - Mobile Compact */
            #deleteFacultyModal .modal-box {
                width: 85vw !important;
                max-width: 85vw !important;
                padding: 20px 16px !important;
                transform: scale(1) !important;
            }

            #deleteFacultyModal .modal-header {
                font-size: 1rem !important;
                margin-bottom: 12px !important;
            }

            /* Warning Icon and Message - More Compact */
            #deleteFacultyModal .modal-box > div[style*="text-align: center"] {
                margin: 0 !important;
            }

            #deleteFacultyModal .modal-box > div[style*="text-align: center"] > div:first-of-type {
                font-size: 2.5rem !important;
                margin-bottom: 12px !important;
            }

            #deleteFacultyModal .modal-box > div[style*="text-align: center"] > div:nth-of-type(2) {
                font-size: 0.85rem !important;
                margin-bottom: 6px !important;
            }

            #deleteFacultyModal .modal-box > div[style*="text-align: center"] > div:nth-of-type(3) {
                font-size: 0.75rem !important;
                line-height: 1.4 !important;
            }

            #deleteFacultyModal .modal-buttons {
                display: flex !important;
                flex-direction: row !important;
                justify-content: center !important;
                align-items: center !important;
                gap: 0.75rem !important;
                margin-top: 16px !important;
            }

            #deleteFacultyModal .modal-btn {
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
                    
                    </tr>
                </thead>
                <tbody>
                    @forelse($faculties as $faculty)
                        <tr class="faculty-row" data-faculty-id="{{ $faculty->faculty_id }}" style="cursor: pointer;">
                            <td data-label="Faculty ID">{{ $faculty->faculty_id }}</td>
                            <td data-label="First Name">{{ $faculty->faculty_fname }}</td>
                            <td data-label="Last Name">{{ $faculty->faculty_lname }}</td>
                            <td data-label="Department">{{ $faculty->faculty_department }}</td>
                            <td data-label="Images">
                                <button class="view-btn"
                                    onclick='event.stopPropagation(); openViewImageModal(@json($faculty->faculty_images))'>View</button>
                           
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="text-align:center; font-style:italic; color:#666;">
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
                            gap: 4px;
                            margin-bottom: 2px;
                            padding-bottom: 4px;
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
                            bottom: -8px;
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
                            gap: 4px;
                            margin-bottom: 2px;
                            padding-bottom: 4px;
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
                            bottom: -8px;
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
        <form id="deleteFacultyForm" method="POST" class="modal-box" style="transform: scale(0.8); transform-origin: center;">
            @csrf
            @method('DELETE')
            <div class="modal-header delete">DELETE FACULTY</div>

            <!-- Warning Icon and Message -->
            <div style="text-align: center; margin:0 px 0;">
                <div style="font-size: 3.2rem; color: #ff3636; margin-bottom: 16px;">⚠️</div>
                <div style="font-size: 0.96rem; color: #333; margin-bottom: 8px; font-weight: bold;">Are you sure?</div>
                <div style="font-size: 0.8rem; color: #666; line-height: 1.5;">This action cannot be undone.<br> The faculty will be
                    permanently deleted.</div>
            </div>

            <!-- Action Buttons -->
            <div class="modal-buttons">
                <button type="submit" class="modal-btn delete">Delete</button>
                <button type="button" class="modal-btn cancel" onclick="closeModal('deleteFacultyModal')">
                    Cancel
                </button>
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
        <div class="modal-box" style="width: 730px; max-width: 95vw; padding: 0; overflow: hidden; border-radius: 8px;">
            <div class="modal-header-custom" style="margin-bottom: 0;">FACULTY TEACHING LOADS</div>
            <div style="padding: 12px 6px;">
                <div id="facultyInfo" style="margin-bottom: 15px; padding: 10px 6px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #8B0000; display: flex; justify-content: space-between; align-items: flex-start;">
                    <div style="flex: 1;">
                        <h3 id="facultyName" style="margin: 0 0 5px 0; color: #8B0000; font-size: 1.2rem;"></h3>
                        <p id="facultyDepartment" style="margin: 0; color: #666; font-size: 1rem;"></p>
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px; margin-left: 16px;">
                        <label for="teachingLoadsDatePicker" style="margin: 0; color: #333; font-size: 0.9rem; font-weight: 500; white-space: nowrap;">Date:</label>
                        <input type="date" id="teachingLoadsDatePicker" style="padding: 6px 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem; color: #333; background: #fff; cursor: pointer; min-width: 140px;" value="{{ date('Y-m-d') }}">
                    </div>
                </div>
                <div id="teachingLoadsContainer" style="max-height: 400px; overflow-y: auto;">
                    <div id="teachingLoadsTable" style="display: none; overflow: hidden; border-radius: 8px;">
                        <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem; border-radius: 8px; overflow: hidden;">
                            <thead>
                                <tr style="background: #8B0000; color: white;">
                                    <th style="padding: 6px 2px; text-align: center; border: none; border-top-left-radius: 8px;">Course Code</th>
                                    <th style="padding: 6px 2px; text-align: center; border: none;">Subject</th>
                                    <th style="padding: 6px 2px; text-align: center; border: none;">Class Section</th>
                                    <th style="padding: 6px 2px; text-align: center; border: none;">Day</th>
                                    <th style="padding: 6px 2px; text-align: center; border: none;">Time In</th>
                                    <th style="padding: 6px 2px; text-align: center; border: none;">Time Out</th>
                                    <th style="padding: 6px 2px; text-align: center; border: none;">Room</th>
                                    <th style="padding: 6px 2px; text-align: center; border: none; border-top-right-radius: 8px;">Status</th>
                                </tr>
                            </thead>
                            <tbody id="teachingLoadsTableBody">
                            </tbody>
                        </table>
                    </div>
                    <div id="noTeachingLoads" style="text-align: center; padding: 20px 6px; color: #666; font-style: italic; display: none;">
                        No teaching loads assigned to this faculty member.
                    </div>
                </div>
                <div style="margin-top: 15px; text-align: center;">
                    <button type="button" class="modal-btn cancel" onclick="closeModal('viewTeachingLoadsModal')" style="width: 20%;">Close</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        function openModal(id) {
            const modal = document.getElementById(id);
            if (!modal) return;

            // For Add Faculty Modal and Update Faculty Modal, ensure slide-up class is removed for slide-down animation
            if (id === 'addFacultyModal' || id === 'updateFacultyModal') {
                const modalBox = modal.querySelector('.modal-box');
                if (modalBox) {
                    modalBox.classList.remove('slide-up');
                }
            }

            modal.style.display = 'flex';
        }

        function resetModalForm(modalId) {
            const modal = document.getElementById(modalId);
            if (!modal) return;
            const form = modal.querySelector('form');
            if (!form) return;

            const fields = form.querySelectorAll('input, select, textarea');
            fields.forEach(function(el) {
                if (el.type === 'hidden') {
                    return;
                }
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
            if (!modal) return;

            // For Add Faculty Modal and Update Faculty Modal, add slide-up animation on mobile
            if (id === 'addFacultyModal' || id === 'updateFacultyModal') {
                const modalBox = modal.querySelector('.modal-box');
                if (modalBox) {
                    // Add slide-up animation class
                    modalBox.classList.add('slide-up');
                    
                    // Wait for animation to complete, then hide modal
                    setTimeout(() => {
                        modal.style.display = 'none';
                        modalBox.classList.remove('slide-up');
                        resetModalForm(id);
                    }, 300); // Match animation duration
                    return;
                }
            }

            // For other modals or if animation element not found, hide immediately
            modal.style.display = 'none';
            if (id === 'addFacultyModal' || id === 'updateFacultyModal') {
                resetModalForm(id);
            }
            
            // Reset date picker to current date when teaching loads modal is closed
            if (id === 'viewTeachingLoadsModal') {
                const datePicker = document.getElementById('teachingLoadsDatePicker');
                if (datePicker) {
                    datePicker.value = new Date().toISOString().split('T')[0];
                }
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
            
            // Set default date to today
            const datePicker = document.getElementById('teachingLoadsDatePicker');
            if (datePicker && !datePicker.value) {
                datePicker.value = new Date().toISOString().split('T')[0];
            }
            
            // Show loading state
            document.getElementById('teachingLoadsTable').style.display = 'none';
            document.getElementById('noTeachingLoads').style.display = 'none';
            
            // Function to fetch teaching loads with date
            const fetchTeachingLoads = (selectedDate) => {
                const dateParam = selectedDate ? `?date=${selectedDate}` : '';
                return fetch(`/api/faculty/${facultyId}/teaching-loads${dateParam}`)
                .then(response => response.json())
                .then(data => {
                    const tableBody = document.getElementById('teachingLoadsTableBody');
                    tableBody.innerHTML = '';
                    
                    if (data.length > 0) {
                        data.forEach((load, index) => {
                            const isLastRow = index === data.length - 1;
                            // Add teaching load row
                            const row = document.createElement('tr');
                            if (!isLastRow) {
                                row.style.borderBottom = '1px solid #eee';
                            }
                            row.style.background = index % 2 === 0 ? '#fff' : '#f9f9f9';
                            
                            // Determine status display using combined status/remarks logic
                            let statusDisplay = '';
                            let statusStyle = 'padding: 6px 2px; text-align: center; font-weight: 500;';
                            
                            // Use record_status and record_remarks if available, otherwise fall back to status and remarks
                            const recordStatus = (load.record_status || load.status || '').toUpperCase().trim();
                            const recordRemarks = (load.record_remarks || load.remarks || '').toUpperCase().trim();
                            const rawRemarks = (load.record_remarks || load.remarks || '').trim();
                            
                            if (load.status === 'On Going(Faculty Detected)') {
                                // Faculty detected via recognition log
                                statusDisplay = 'On Going(Faculty Detected)';
                                statusStyle += ' color: #28a745; font-weight: bold;';
                            } else if (load.status === 'On Going(No Faculty Detected)') {
                                // No faculty detected
                                statusDisplay = 'On Going(No Faculty Detected)';
                                statusStyle += ' color: #ff9800; font-weight: bold;';
                            } else if (recordStatus === 'PRESENT') {
                                // Present status - show "Present" regardless of remarks (handles "Present (Wrong Room)" cases)
                                statusDisplay = 'Present';
                                statusStyle += ' color: #28a745; font-weight: bold;';
                            } else if (recordStatus === 'LATE') {
                                // Late status - show "Late" regardless of remarks (handles "Late (Wrong Room)" cases)
                                statusDisplay = 'Late';
                                statusStyle += ' color: #ff8c00; font-weight: bold;';
                            } else if (recordStatus === 'ABSENT') {
                                if (recordRemarks === 'ABSENT') {
                                    statusDisplay = 'Absent';
                                    statusStyle += ' color: #dc3545; font-weight: bold;';
                                } else if (recordRemarks === 'ON LEAVE') {
                                    statusDisplay = '<span style="color: #dc3545;">Absent but</span> <span style="color: #ff8c00;">On Leave</span>';
                                    statusStyle = 'padding: 6px 2px; text-align: center; font-weight: 500;';
                                } else if (recordRemarks === 'WITH PASS SLIP') {
                                    statusDisplay = '<span style="color: #dc3545;">Absent but</span> <span style="color: #ff8c00;">With Pass Slip</span>';
                                    statusStyle = 'padding: 6px 2px; text-align: center; font-weight: 500;';
                                } else {
                                    statusDisplay = 'Absent';
                                    statusStyle += ' color: #dc3545; font-weight: bold;';
                                }
                            } else if (load.status || load.remarks) {
                                // Other statuses - default gray
                                statusDisplay = load.status || load.remarks || '';
                                statusStyle += ' color: #333; font-size: 0.85rem; font-weight: 500;';
                            } else {
                                // No status - outside time range or not matching day
                                statusDisplay = '-';
                                statusStyle += ' color: #999;';
                            }
                            
                            row.innerHTML = `
                                <td style="padding: 6px 2px; text-align: center; ${isLastRow ? 'border-bottom-left-radius: 8px;' : ''}">${load.teaching_load_course_code}</td>
                                <td style="padding: 6px 2px; text-align: center;">${load.teaching_load_subject}</td>
                                <td style="padding: 6px 2px; text-align: center;">${load.teaching_load_class_section}</td>
                                <td style="padding: 6px 2px; text-align: center; font-weight: bold; color: #8B0000;">${load.teaching_load_day_of_week}</td>
                                <td style="padding: 6px 2px; text-align: center;">${formatTime(load.teaching_load_time_in)}</td>
                                <td style="padding: 6px 2px; text-align: center;">${formatTime(load.teaching_load_time_out)}</td>
                                <td style="padding: 6px 2px; text-align: center;">${load.room_name || load.room_no}</td>
                                <td style="${statusStyle} ${isLastRow ? 'border-bottom-right-radius: 8px;' : ''}">${statusDisplay}</td>
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
            };
            
            // Initial fetch with current date
            fetchTeachingLoads(datePicker.value);
            
            // Add event listener for date picker changes
            datePicker.addEventListener('change', function() {
                // Show loading state
                document.getElementById('teachingLoadsTable').style.display = 'none';
                document.getElementById('noTeachingLoads').style.display = 'none';
                fetchTeachingLoads(this.value);
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
                // Use closeModal function to handle animations properly
                closeModal(overlayId);
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
