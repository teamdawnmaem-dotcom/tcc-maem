@extends('layouts.appdeptHead')

@section('title', 'Subject Management - Tagoloan Community College')
@section('files-active', 'active')
@section('subject-active', 'active')

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

        .csv-btn {
            padding: 6px 19px;
            font-size: 11.2px;
            border: none;
            border-radius: 3.2px;
            background-color: #3498db;
            color: #fff;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.2s;
        }

        .csv-btn:hover {
            background-color: #2980b9;
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

        /* Legacy classes for mobile compatibility */
        .faculty-actions-container {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-top: 0;
            margin-bottom: 32px;
            width: 100%;
        }

        .search-row {
            width: 100%;
        }

        .buttons-row {
            display: flex;
            gap: 12px;
            width: 100%;
            align-items: stretch;
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

        /* Delete Modal - Scaled button styles (80%) */
        #deleteSubjectModal .modal-btn {
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

        #deleteSubjectModal .modal-btn.delete {
            padding: 11.2px 0;
            border-radius: 4.8px;
        }

        #deleteSubjectModal .modal-btn.cancel {
            padding: 11.2px 0;
            border-radius: 4.8px;
        }

        #deleteSubjectModal .modal-buttons {
            gap: 9.6px;
            margin-top: 14.4px;
        }

        #deleteSubjectModal .modal-header {
            font-size: 1.152rem;
            margin-bottom: 16px;
        }

        #deleteSubjectModal .modal-box {
            padding: 25.6px;
        }

        /* Slide-down animation for Add/Update Subject Modals (mobile only) */
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

        /* Slide-up animation for Add/Update Subject Modals (mobile only) */
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

        #addSubjectModal .modal-box.slide-up {
            animation: slideUp 0.3s ease-out !important;
        }

        #updateSubjectModal .modal-box.slide-up {
            animation: slideUp 0.3s ease-out !important;
        }

        /* Excel Upload Modal - Desktop Styles */
        #csvUploadModal.modal-overlay {
            padding: 0 !important;
        }

        #csvUploadModal .modal-box {
            padding: 0 !important;
            margin: 0 !important;
        }

        #csvUploadModal .modal-box form {
            padding: 0 !important;
            margin: 0 !important;
            width: 100% !important;
        }

        #csvUploadModal .modal-header {
            width: 100% !important;
            margin: 0 !important;
            padding-left: 0 !important;
            padding-right: 0 !important;
            box-sizing: border-box !important;
        }

        #csvUploadModal .modal-buttons {
            display: flex;
            gap: 12px;
            justify-content: center;
            margin-top: 20px;
        }

        #csvUploadModal .modal-btn.add {
            background: #2ecc71;
            color: #fff;
            border: 2px solid #2ecc71;
            border-radius: 6px;
            padding: 12px 0;
            font-size: 1rem;
            font-weight: bold;
            transition: background-color 0.2s, color 0.2s;
        }

        #csvUploadModal .modal-btn.add:hover {
            background: #27ae60;
            border-color: #27ae60;
        }

        #csvUploadModal .modal-btn.cancel {
            background: #fff;
            color: #800000;
            border: 2px solid #800000;
            border-radius: 6px;
            padding: 12px 0;
            font-size: 1rem;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        #csvUploadModal .modal-btn.cancel:hover {
            background: #800000;
            color: #fff;
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

            /* Actions Row - Mobile layout: Search bar on top, buttons below */
            .faculty-actions-row {
                position: relative;
                width: 100%;
                display: grid !important;
                grid-template-columns: 1fr 1fr;
                grid-template-rows: auto auto;
                gap: 12px;
                margin: 0 0 16px 0;
                padding: 0;
                top: auto;
                right: auto;
                z-index: 1;
            }

            .search-input {
                grid-row: 1;
                grid-column: 1 / -1;
                width: 100% !important;
                padding: 10px 12px !important;
                font-size: 0.9rem !important;
                border-radius: 6px;
                margin: 0;
                box-sizing: border-box;
            }

            /* Buttons - horizontal row below search bar */
            .faculty-actions-row .csv-btn {
                grid-row: 2;
                grid-column: 1;
                flex: 1 !important;
                min-width: 0 !important;
                padding: 11.2px 16px !important;
                font-size: 0.88rem !important;
                border-radius: 4.8px !important;
                font-weight: bold;
                white-space: nowrap;
                margin: 0;
                min-height: 44px;
                box-sizing: border-box;
            }

            .faculty-actions-row .add-btn {
                grid-row: 2;
                grid-column: 2;
                flex: 1 !important;
                min-width: 0 !important;
                padding: 11.2px 20px !important;
                font-size: 0.88rem !important;
                border-radius: 4.8px !important;
                font-weight: bold;
                white-space: nowrap;
                margin: 0;
                min-height: 44px;
                box-sizing: border-box;
            }

            /* Table Container - Card Layout on Mobile */
            .subject-table-container {
                border-radius: 8px;
                overflow: visible;
                background: transparent;
                box-shadow: none;
            }

            .subject-table-scroll {
                max-height: none;
                overflow: visible;
                -webkit-overflow-scrolling: touch;
            }

            /* Hide table header on mobile */
            .subject-table thead {
                display: none;
            }

            /* Transform table rows into cards */
            .subject-table {
                width: 100%;
                min-width: 0;
                border-collapse: separate;
                border-spacing: 0 12px;
                display: block;
            }

            .subject-table tbody {
                display: block;
            }

            .subject-table tr {
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

            .subject-table tr:hover {
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15), 0 2px 4px rgba(0, 0, 0, 0.1);
                transform: translateY(-1px);
            }

            .subject-table tr:last-child {
                margin-bottom: 0;
            }

            .subject-table td {
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

            .subject-table td:before {
                content: attr(data-label);
                font-weight: 600;
                color: #555;
                margin-right: 12px;
                flex-shrink: 0;
                min-width: 100px;
                font-size: 0.8rem;
            }

            .subject-table td:not([data-label="Action"]) {
                border-bottom: 1px solid #f5f5f5;
            }

            .subject-table td:last-child:not([data-label="Action"]) {
                border-bottom: none;
            }

            /* Action column styling */
            .subject-table td[data-label="Action"] {
                justify-content: flex-end;
                padding-top: 12px;
                border-top: 1px solid #f0f0f0;
                margin-top: 8px;
            }

            .subject-table td[data-label="Action"]:before {
                display: none;
            }

            /* Empty state message */
            .subject-table td[colspan] {
                display: block;
                text-align: center;
                font-size: 0.85rem;
                padding: 40px 20px;
                color: #666;
                font-style: italic;
            }

            .subject-table td[colspan]:before {
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

            /* Add Subject Modal - Mobile */
            #addSubjectModal.modal-overlay {
                padding: 0 !important;
                align-items: flex-start !important;
                justify-content: center !important;
            }

            #addSubjectModal .modal-box {
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

            #addSubjectModal .modal-box form {
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                box-sizing: border-box !important;
            }

            #addSubjectModal .modal-header {
                font-size: 1rem !important;
                padding: 10px 14px !important;
                flex-shrink: 0 !important;
                width: 100% !important;
                box-sizing: border-box !important;
                border-top-left-radius: 0 !important;
                border-top-right-radius: 0 !important;
            }

            #addSubjectModal .modal-form {
                overflow: visible !important;
                padding: 12px 14px !important;
                width: 100% !important;
                box-sizing: border-box !important;
                margin: 0 !important;
            }

            #addSubjectModal .modal-form-group {
                flex-direction: column !important;
                align-items: flex-start !important;
                gap: 2px !important;
                margin-bottom: 6px !important;
                padding-bottom: 10px !important;
            }

            #addSubjectModal .modal-form-group label {
                min-width: auto !important;
                width: 100% !important;
                margin-bottom: 2px !important;
                font-size: 0.7rem !important;
            }

            #addSubjectModal .modal-form-group input,
            #addSubjectModal .modal-form-group select {
                width: 100% !important;
                padding: 8px 10px !important;
                font-size: 0.85rem !important;
            }

            #addSubjectModal .validation-message {
                position: relative !important;
                left: 0 !important;
                right: 0 !important;
                bottom: 0 !important;
                padding-left: 0 !important;
                margin-top: 2px !important;
                font-size: 0.65rem !important;
            }

            #addSubjectModal .modal-buttons {
                flex-direction: row !important;
                justify-content: center !important;
                align-items: center !important;
                gap: 0.75rem !important;
                margin-top: 12px !important;
            }

            #addSubjectModal .modal-btn {
                flex: 1 !important;
                max-width: none !important;
                padding: 10px !important;
                font-size: 0.85rem !important;
                min-height: 44px !important;
            }

            /* Update Subject Modal - Mobile */
            #updateSubjectModal.modal-overlay {
                padding: 0 !important;
                align-items: flex-start !important;
                justify-content: center !important;
            }

            #updateSubjectModal .modal-box {
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

            #updateSubjectModal .modal-box form {
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                box-sizing: border-box !important;
            }

            #updateSubjectModal .modal-header {
                font-size: 1rem !important;
                padding: 10px 14px !important;
                flex-shrink: 0 !important;
                width: 100% !important;
                box-sizing: border-box !important;
                border-top-left-radius: 0 !important;
                border-top-right-radius: 0 !important;
            }

            #updateSubjectModal .modal-form {
                overflow: visible !important;
                padding: 12px 14px !important;
                width: 100% !important;
                box-sizing: border-box !important;
                margin: 0 !important;
            }

            #updateSubjectModal .modal-form-group {
                flex-direction: column !important;
                align-items: flex-start !important;
                gap: 2px !important;
                margin-bottom: 6px !important;
                padding-bottom: 10px !important;
            }

            #updateSubjectModal .modal-form-group label {
                min-width: auto !important;
                width: 100% !important;
                margin-bottom: 2px !important;
                font-size: 0.7rem !important;
            }

            #updateSubjectModal .modal-form-group input,
            #updateSubjectModal .modal-form-group select {
                width: 100% !important;
                padding: 8px 10px !important;
                font-size: 0.85rem !important;
            }

            #updateSubjectModal .validation-message {
                position: relative !important;
                left: 0 !important;
                right: 0 !important;
                bottom: 0 !important;
                padding-left: 0 !important;
                margin-top: 2px !important;
                font-size: 0.65rem !important;
            }

            #updateSubjectModal .modal-buttons {
                flex-direction: row !important;
                justify-content: center !important;
                align-items: center !important;
                gap: 0.75rem !important;
                margin-top: 12px !important;
            }

            #updateSubjectModal .modal-btn {
                flex: 1 !important;
                max-width: none !important;
                padding: 10px !important;
                font-size: 0.85rem !important;
                min-height: 44px !important;
            }

            /* Delete Subject Modal - Mobile Compact */
            #deleteSubjectModal .modal-box {
                width: 85vw !important;
                max-width: 85vw !important;
                padding: 20px 16px !important;
                transform: scale(1) !important;
            }

            #deleteSubjectModal .modal-header {
                font-size: 1rem !important;
                margin-bottom: 12px !important;
            }

            /* Warning Icon and Message - More Compact */
            #deleteSubjectModal .modal-box > div[style*="text-align: center"] {
                margin: 0 !important;
            }

            #deleteSubjectModal .modal-box > div[style*="text-align: center"] > div:first-of-type {
                font-size: 2.5rem !important;
                margin-bottom: 12px !important;
            }

            #deleteSubjectModal .modal-box > div[style*="text-align: center"] > div:nth-of-type(2) {
                font-size: 0.85rem !important;
                margin-bottom: 6px !important;
            }

            #deleteSubjectModal .modal-box > div[style*="text-align: center"] > div:nth-of-type(3) {
                font-size: 0.75rem !important;
                line-height: 1.4 !important;
            }

            #deleteSubjectModal .modal-buttons {
                display: flex !important;
                flex-direction: row !important;
                justify-content: center !important;
                align-items: center !important;
                gap: 0.75rem !important;
                margin-top: 16px !important;
            }

            #deleteSubjectModal .modal-btn {
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

            /* CSV Upload Modal - Mobile */
            #csvUploadModal.modal-overlay {
                padding: 0 !important;
                align-items: flex-start !important;
                justify-content: center !important;
            }

            #csvUploadModal .modal-box {
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
                transform: scale(1) !important;
            }

            #csvUploadModal .modal-box form {
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                box-sizing: border-box !important;
            }

            #csvUploadModal .modal-header {
                font-size: 1rem !important;
                padding: 10px 14px !important;
                flex-shrink: 0 !important;
                width: 100% !important;
                box-sizing: border-box !important;
                border-top-left-radius: 0 !important;
                border-top-right-radius: 0 !important;
            }

            /* Main content container - reduce padding */
            #csvUploadModal .modal-box form > div:not(.modal-header) {
                overflow-y: auto !important;
                overflow-x: hidden !important;
                -webkit-overflow-scrolling: touch !important;
                padding: 12px 14px !important;
                width: 100% !important;
                box-sizing: border-box !important;
                margin: 0 !important;
                max-height: calc(100vh - 60px) !important;
            }

            /* All direct children of content div - reduce margins */
            #csvUploadModal .modal-box form > div:not(.modal-header) > div {
                margin-bottom: 10px !important;
            }

            /* File upload section label */
            #csvUploadModal label {
                font-size: 0.7rem !important;
                margin-bottom: 2px !important;
                width: 100% !important;
                font-weight: bold !important;
            }

            /* File input */
            #csvUploadModal input[type="file"] {
                width: 100% !important;
                padding: 8px 10px !important;
                font-size: 0.85rem !important;
                border: 1px solid #bbb !important;
                border-radius: 4px !important;
                box-sizing: border-box !important;
            }

            /* File name display - tighter spacing */
            #csvUploadModal #csvFileName {
                margin-top: 4px !important;
                font-size: 0.75rem !important;
            }

            /* Download link container and link */
            #csvUploadModal .modal-box form > div:not(.modal-header) > div > div {
                margin-top: 4px !important;
            }

            #csvUploadModal .modal-box form > div:not(.modal-header) > div > div a {
                font-size: 0.75rem !important;
            }

            /* Info boxes - reduce padding and margins */
            #csvUploadModal .modal-box form > div:not(.modal-header) > div[style*="background-color: #f0f8ff"],
            #csvUploadModal .modal-box form > div:not(.modal-header) > div[style*="background-color: #fff3cd"] {
                padding: 10px 12px !important;
                margin-bottom: 10px !important;
            }

            /* Info box content - reduce font sizes */
            #csvUploadModal .modal-box form > div:not(.modal-header) > div[style*="background-color: #f0f8ff"] > div,
            #csvUploadModal .modal-box form > div:not(.modal-header) > div[style*="background-color: #fff3cd"] > div {
                font-size: 0.75rem !important;
                line-height: 1.4 !important;
            }

            /* Info box nested divs */
            #csvUploadModal .modal-box form > div:not(.modal-header) > div[style*="background-color: #f0f8ff"] > div > div,
            #csvUploadModal .modal-box form > div:not(.modal-header) > div[style*="background-color: #fff3cd"] > div > div {
                font-size: 0.7rem !important;
                margin-bottom: 2px !important;
            }

            /* Info box headers */
            #csvUploadModal .modal-box form > div:not(.modal-header) > div[style*="background-color: #f0f8ff"] > div[style*="font-weight: bold"],
            #csvUploadModal .modal-box form > div:not(.modal-header) > div[style*="background-color: #fff3cd"] > div strong {
                margin-bottom: 6px !important;
                font-size: 0.75rem !important;
            }

            /* List in warning box */
            #csvUploadModal .modal-box form > div:not(.modal-header) > div[style*="background-color: #fff3cd"] ul {
                margin: 4px 0 0 0 !important;
                padding-left: 18px !important;
            }

            #csvUploadModal .modal-box form > div:not(.modal-header) > div[style*="background-color: #fff3cd"] ul li {
                font-size: 0.7rem !important;
                margin-bottom: 2px !important;
            }

            #csvUploadModal .modal-buttons {
                display: flex !important;
                flex-direction: row !important;
                justify-content: center !important;
                align-items: center !important;
                gap: 0.75rem !important;
                margin-top: 12px !important;
                margin-left: auto !important;
                margin-right: auto !important;
                position: sticky !important;
                bottom: 0 !important;
                background: #fff !important;
                padding-top: 12px !important;
                padding-bottom: 12px !important;
                width: 100% !important;
                box-sizing: border-box !important;
            }

            #csvUploadModal .modal-btn {
                padding: 10px !important;
                font-size: 0.85rem !important;
                min-height: 44px !important;
                border-radius: 4px !important;
                font-weight: bold !important;
                transition: all 0.3s ease !important;
                flex-shrink: 0 !important;
            }

            /* Upload button - match teaching-load-management design */
            #csvUploadModal .modal-btn.add {
                background: #2ecc71 !important;
                border: 2px solid #2ecc71 !important;
                color: #fff !important;
                width: 50% !important;
            }

            #csvUploadModal .modal-btn.add:hover {
                background: #27ae60 !important;
                border-color: #27ae60 !important;
                color: #fff !important;
            }

            /* Cancel button - match teaching-load-management design */
            #csvUploadModal .modal-btn.cancel {
                background: #fff !important;
                border: 2px solid #800000 !important;
                color: #800000 !important;
                width: 50% !important;
            }

            #csvUploadModal .modal-btn.cancel:hover {
                background: #800000 !important;
                color: #fff !important;
                border-color: #800000 !important;
            }

            #csvUploadModal .modal-box.slide-up {
                animation: slideUp 0.3s ease-out !important;
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
        <div class="faculty-actions-row">
            <input type="text" class="search-input" id="subjectSearch" placeholder="Search...">
            <button class="csv-btn" onclick="openModal && openModal('csvUploadModal')">Excel Upload</button>
            <button class="add-btn" onclick="openModal && openModal('addSubjectModal')">Add</button>
        </div>
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
                            gap: 4px;
                            margin-bottom: 2px;
                            padding-bottom: 4px;
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
                            bottom: -6px;
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
                            gap: 4px;
                            margin-bottom: 2px;
                            padding-bottom: 4px;
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
                            bottom: -6px;
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
        <form id="deleteSubjectForm" method="POST" class="modal-box" style="transform: scale(0.8); transform-origin: center;">
            @csrf
            @method('DELETE')
            <div class="modal-header delete">DELETE SUBJECT</div>

            <!-- Warning Icon and Message -->
            <div style="text-align: center; margin:0 px 0;">
                <div style="font-size: 3.2rem; color: #ff3636; margin-bottom: 16px;">⚠️</div>
                <div style="font-size: 0.96rem; color: #333; margin-bottom: 8px; font-weight: bold;">Are you sure?</div>
                <div style="font-size: 0.8rem; color: #666; line-height: 1.5;">This action cannot be undone.<br> The subject will be
                    permanently deleted.</div>
            </div>

            <!-- Action Buttons -->
            <div class="modal-buttons">
                <button type="submit" class="modal-btn delete">Delete</button>
                <button type="button" class="modal-btn cancel" onclick="closeModal('deleteSubjectModal')">
                    Cancel
                </button>
            </div>
        </form>
    </div>

    <!-- Excel Upload Modal -->
    <div id="csvUploadModal" class="modal-overlay" style="display:none;">
        <div class="modal-box" style="padding: 0; overflow: hidden; border-radius: 8px; max-width: 800px; width: 90vw;">
            <form id="csvUploadForm" action="{{ route('deptHead.subjects.csv-upload') }}" method="POST" enctype="multipart/form-data" style="padding: 0; margin: 0;">
                @csrf
                <div class="modal-header"
                    style="background-color: #8B0000; color: white; padding: 18px 24px; font-size: 24px; font-weight: bold; width: 100%; margin: 0; display: flex; align-items: center; justify-content: center; text-align: center; letter-spacing: 0.5px; border-top-left-radius: 8px; border-top-right-radius: 8px; box-sizing: border-box; position: relative; left: 0; right: 0;">
                    SUBJECT EXCEL UPLOAD
                </div>
                <div style="padding: 24px;">
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; font-size: 1rem; color: #222; margin-bottom: 10px; font-weight: bold;">Upload Excel File:</label>
                        <input type="file" name="csv_file" id="csvFileInput" accept=".csv,.xlsx,.xls" required
                            style="width: 100%; padding: 10px; border: 2px solid #3498db; border-radius: 5px; font-size: 1rem;">
                        <div id="csvFileName" style="margin-top: 8px; font-size: 0.9rem; color: #3498db; font-weight: 500; display: none;"></div>
                        <div style="margin-top: 12px; text-align: center;">
                            <a href="{{ route('deptHead.subjects.excel-template') }}" 
                               style="display: inline-block; background-color: #3498db; color: white; padding: 12px 24px; font-size: 1.1rem; font-weight: bold; text-decoration: none; border-radius: 6px; transition: background-color 0.3s ease; box-shadow: 0 2px 8px rgba(52, 152, 219, 0.3);">
                                📥 Download Sample Excel Template
                            </a>
                        </div>
                        <style>
                            a[href*="excel-template"]:hover {
                                background-color: #2980b9 !important;
                                box-shadow: 0 4px 12px rgba(52, 152, 219, 0.4) !important;
                                transform: translateY(-2px);
                            }
                        </style>
                    </div>

                    <div style="background-color: #f0f8ff; border-left: 4px solid #3498db; padding: 15px; margin-bottom: 20px;">
                        <div style="font-size: 0.95rem; color: #333; margin-bottom: 10px; font-weight: bold;">CSV Format Instructions:</div>
                        <div style="font-size: 0.85rem; color: #666; line-height: 1.6;">
                            <div>• Column 1: Subject Code (max 100 characters)</div>
                            <div>• Column 2: Subject Description (max 255 characters)</div>
                            <div>• Column 3: Department (must match one of the valid departments)</div>
                        </div>
                    </div>

                    <div style="background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin-bottom: 20px;">
                        <div style="font-size: 0.9rem; color: #856404;">
                            <strong>Important Notes:</strong>
                            <ul style="margin: 8px 0 0 0; padding-left: 20px;">
                                <li>The CSV file should include headers in the first row</li>
                                <li>All 3 columns are required and cannot be empty</li>
                                <li>Department must be one of the valid departments listed in the system</li>
                                <li>Duplicate entries (same code, description, and department) will be rejected</li>
                                <li>Duplicate entries within the same CSV file will be rejected</li>
                            </ul>
                        </div>
                    </div>

                    <div class="modal-buttons" style="display: flex; gap: 12px; justify-content: center; margin-top: 20px;">
                        <button type="submit" class="modal-btn add" style="width: 50%;">Upload</button>
                        <button type="button" class="modal-btn cancel" style="width: 50%;"
                            onclick="closeModal('csvUploadModal')">Cancel</button>
                    </div>
                </div>
            </form>
        </div>
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
            const modal = document.getElementById(id);
            if (!modal) return;

            // For Add Subject Modal, Update Subject Modal, and CSV Upload Modal, ensure slide-up class is removed for slide-down animation
            if (id === 'addSubjectModal' || id === 'updateSubjectModal' || id === 'csvUploadModal') {
                const modalBox = modal.querySelector('.modal-box');
                if (modalBox) {
                    modalBox.classList.remove('slide-up');
                }
            }

            modal.style.display = 'flex';
            
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
            const modal = document.getElementById(id);
            if (!modal) return;

            // For Add Subject Modal, Update Subject Modal, and CSV Upload Modal, add slide-up animation on mobile
            if (id === 'addSubjectModal' || id === 'updateSubjectModal' || id === 'csvUploadModal') {
                const modalBox = modal.querySelector('.modal-box');
                if (modalBox) {
                    // Add slide-up animation class
                    modalBox.classList.add('slide-up');
                    
                    // Wait for animation to complete, then hide modal
                    setTimeout(() => {
                        modal.style.display = 'none';
                        modalBox.classList.remove('slide-up');
                    }, 300); // Match animation duration
                    
                    // Reset CSV upload form if closing CSV modal
                    if (id === 'csvUploadModal') {
                        const form = document.getElementById('csvUploadForm');
                        if (form) {
                            form.reset();
                            const csvFileName = document.getElementById('csvFileName');
                            if (csvFileName) csvFileName.style.display = 'none';
                            const submitButton = form.querySelector('button[type="submit"]');
                            if (submitButton) {
                                submitButton.disabled = false;
                                submitButton.textContent = 'Upload';
                                submitButton.style.opacity = '1';
                                submitButton.style.cursor = 'pointer';
                            }
                        }
                    }
                    return;
                }
            }

            // For other modals or if animation element not found, hide immediately
            modal.style.display = 'none';
            
            // Reset CSV upload form if closing CSV modal
            if (id === 'csvUploadModal') {
                const form = document.getElementById('csvUploadForm');
                if (form) {
                    form.reset();
                    const csvFileName = document.getElementById('csvFileName');
                    if (csvFileName) csvFileName.style.display = 'none';
                    const submitButton = form.querySelector('button[type="submit"]');
                    if (submitButton) {
                        submitButton.disabled = false;
                        submitButton.textContent = 'Upload';
                        submitButton.style.opacity = '1';
                        submitButton.style.cursor = 'pointer';
                    }
                }
            }
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
                const overlayId = e.target.id;
                // Use closeModal function to handle animations properly
                closeModal(overlayId);
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

            // Excel Upload Form Handler
            (function() {
                const csvUploadForm = document.getElementById('csvUploadForm');
                const csvFileInput = document.getElementById('csvFileInput');
                const csvFileName = document.getElementById('csvFileName');
                
                if (csvUploadForm) {
                    csvUploadForm.addEventListener('submit', function(e) {
                        const fileInput = csvFileInput;
                        const submitButton = csvUploadForm.querySelector('button[type="submit"]');
                        
                        if (!fileInput.files || fileInput.files.length === 0) {
                            e.preventDefault();
                            if (window.Swal) {
                                Swal.fire({
                                    icon: 'warning',
                                    title: 'No File Selected',
                                    text: 'Please select a CSV file to upload.',
                                    confirmButtonColor: '#8B0000'
                                });
                            }
                            return false;
                        }
                        
                        // Show loading state
                        if (submitButton) {
                            submitButton.disabled = true;
                            submitButton.textContent = 'Uploading...';
                            submitButton.style.opacity = '0.6';
                            submitButton.style.cursor = 'not-allowed';
                        }
                        
                        // Allow form to submit normally to reload page with new data
                        return true;
                    });
                }

                // Show selected file name
                if (csvFileInput) {
                    csvFileInput.addEventListener('change', function(e) {
                        const file = e.target.files[0];
                        if (file && csvFileName) {
                            csvFileName.textContent = 'Selected: ' + file.name;
                            csvFileName.style.display = 'block';
                        } else if (csvFileName) {
                            csvFileName.style.display = 'none';
                        }
                    });
                }

                // Reset CSV upload form when modal is closed
                function resetCsvUploadForm() {
                    const form = document.getElementById('csvUploadForm');
                    if (form) {
                        form.reset();
                        if (csvFileName) {
                            csvFileName.style.display = 'none';
                        }
                        const submitButton = form.querySelector('button[type="submit"]');
                        if (submitButton) {
                            submitButton.disabled = false;
                            submitButton.textContent = 'Upload';
                            submitButton.style.opacity = '1';
                            submitButton.style.cursor = 'pointer';
                        }
                    }
                }

                // Reset CSV form when modal is closed
                document.addEventListener('click', function(e) {
                    if (e.target && e.target.classList && e.target.classList.contains('modal-overlay') && e.target.id === 'csvUploadModal') {
                        resetCsvUploadForm();
                    }
                });
            })();

            // Handle Excel upload success/error messages
            (function() {
                @if(session('success') && str_contains(session('success'), 'Excel upload completed'))
                    const successMessage = @json(session('success'));
                    if (window.Swal && successMessage) {
                        // Parse and format the message for better readability
                        const lines = successMessage.split('\n').filter(line => line.trim() !== '');
                        let formattedHtml = '<div style="text-align: left; max-height: 500px; overflow-y: auto; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif;">';
                        
                        // Escape HTML to prevent XSS
                        const escapeHtml = (text) => {
                            const div = document.createElement('div');
                            div.textContent = text;
                            return div.innerHTML;
                        };
                        
                        let inSuccessSection = false;
                        let inErrorSection = false;
                        
                        lines.forEach((line, index) => {
                            const trimmedLine = line.trim();
                            if (!trimmedLine) return;
                            
                            // Main title
                            if (trimmedLine.includes('Excel upload completed')) {
                                formattedHtml += '<div style="font-size: 1.2rem; font-weight: bold; color: #333; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 2px solid #e0e0e0;">' + escapeHtml(trimmedLine) + '</div>';
                            }
                            // Success summary
                            else if (trimmedLine.includes('Successfully added')) {
                                formattedHtml += '<div style="font-size: 1rem; font-weight: bold; color: #2e7d32; margin: 20px 0 12px 0; padding: 10px; background-color: #e8f5e9; border-left: 4px solid #2e7d32; border-radius: 4px;">' + escapeHtml(trimmedLine) + '</div>';
                                inSuccessSection = true;
                                inErrorSection = false;
                            }
                            // Success Details header
                            else if (trimmedLine === 'Success Details:') {
                                formattedHtml += '<div style="font-size: 0.95rem; font-weight: bold; color: #2e7d32; margin: 15px 0 8px 0;">' + escapeHtml(trimmedLine) + '</div>';
                            }
                            // Error summary
                            else if (trimmedLine.includes('Errors:')) {
                                formattedHtml += '<div style="font-size: 1rem; font-weight: bold; color: #d32f2f; margin: 25px 0 12px 0; padding: 10px; background-color: #ffebee; border-left: 4px solid #d32f2f; border-radius: 4px; border-top: 1px solid #e0e0e0; padding-top: 15px;">' + escapeHtml(trimmedLine) + '</div>';
                                inSuccessSection = false;
                                inErrorSection = true;
                            }
                            // Error Details header
                            else if (trimmedLine === 'Error Details:') {
                                formattedHtml += '<div style="font-size: 0.95rem; font-weight: bold; color: #d32f2f; margin: 15px 0 8px 0;">' + escapeHtml(trimmedLine) + '</div>';
                            }
                            // Row details
                            else if (trimmedLine.startsWith('Row ')) {
                                const bgColor = inErrorSection ? '#ffebee' : '#e8f5e9';
                                const borderColor = inErrorSection ? '#d32f2f' : '#2e7d32';
                                const textColor = inErrorSection ? '#c62828' : '#1b5e20';
                                formattedHtml += '<div style="padding: 10px 12px; margin: 6px 0; background-color: ' + bgColor + '; border-left: 3px solid ' + borderColor + '; border-radius: 3px; font-size: 0.9rem; color: ' + textColor + '; line-height: 1.5; border-bottom: 1px solid rgba(0,0,0,0.05);">' + escapeHtml(trimmedLine) + '</div>';
                            }
                            // Other lines
                            else {
                                formattedHtml += '<div style="padding: 6px 0; font-size: 0.9rem; color: #666; line-height: 1.4;">' + escapeHtml(trimmedLine) + '</div>';
                            }
                        });
                        
                        formattedHtml += '</div>';
                        
                        Swal.fire({
                            icon: null,
                            title: 'Excel Upload Completed!',
                            html: formattedHtml,
                            confirmButtonColor: '#8B0000',
                            confirmButtonText: 'OK',
                            allowOutsideClick: false,
                            allowEscapeKey: true,
                            showCloseButton: true,
                            width: '750px'
                        });
                    }
                @endif

                @if($errors->has('csv_file'))
                    if (window.Swal) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Excel Upload Failed',
                            text: '{{ $errors->first("csv_file") }}',
                            confirmButtonColor: '#8B0000'
                        });
                    }
                @endif
            })();
        })();
    </script>
@endsection
