@extends('layouts.appChecker')

@section('title', 'Official Matters - Tagoloan Community College')
@section('files-active', 'active')
@section('official-matters-active', 'active')

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
            max-width: 400px;
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

        /* Toggle Switch Styles */
        .toggle-switch {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 12px;
        }

        .toggle-switch label {
            min-width: auto;
            font-size: 0.85rem;
            font-weight: 500;
            color: #333;
        }

        .switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 24px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked + .slider {
            background-color: #8B0000;
        }

        input:checked + .slider:before {
            transform: translateX(26px);
        }

        /* Hide/show fields based on toggle */
        .faculty-mode {
            display: block;
        }

        .department-mode {
            display: none;
        }

        .toggle-on .faculty-mode {
            display: none;
        }

        .toggle-on .department-mode {
            display: block;
        }

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
        }

        #slipModal .modal-header {
            background: transparent;
            color: #8B0000;
            padding: 12px 16px;
            margin: 0;
            font-size: 1.2rem;
            font-weight: bold;
            text-align: center;
        }

        #slipModal .slip-content {
            padding: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
        }

        .validation-message {
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

        /* Slide animations for modals */
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

        .logic-error {
            display: none;
            color: #ff3636;
            text-align: center;
            margin: 6px 0;
            font-weight: 600;
        }

        .server-error {
            color: #ff3636;
            text-align: center;
            margin: 6px 0;
            font-weight: 600;
        }

        /* Validation styles */
        input.valid,
        select.valid,
        textarea.valid {
            border-color: #28a745;
        }

        input.invalid,
        select.invalid,
        textarea.invalid {
            border-color: #ff3636;
        }

        #addModal .modal-form-group,
        #editModal .modal-form-group {
            position: relative;
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

            /* Table Container - Card Layout on Mobile */
            .teaching-load-table-container {
                border-radius: 8px;
                overflow: visible;
                background: transparent;
                box-shadow: none;
            }

            .teaching-load-table-scroll {
                max-height: none;
                overflow: visible;
                -webkit-overflow-scrolling: touch;
            }

            /* Hide table header on mobile */
            .teaching-load-table thead {
                display: none;
            }

            /* Transform table rows into cards */
            .teaching-load-table {
                width: 100%;
                min-width: 0;
                border-collapse: separate;
                border-spacing: 0 12px;
                display: block;
            }

            .teaching-load-table tbody {
                display: block;
            }

            .teaching-load-table tr {
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

            .teaching-load-table tr:hover {
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15), 0 2px 4px rgba(0, 0, 0, 0.1);
                transform: translateY(-1px);
                background: #fff2e6;
            }

            .teaching-load-table tr:last-child {
                margin-bottom: 0;
            }

            .teaching-load-table td {
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

            .teaching-load-table td:before {
                content: attr(data-label);
                font-weight: 600;
                color: #555;
                margin-right: 12px;
                flex-shrink: 0;
                min-width: 110px;
                font-size: 0.75rem;
            }

            .teaching-load-table td:not(:last-child) {
                border-bottom: 1px solid #f5f5f5;
            }

            .teaching-load-table td[data-label="Actions"] {
                justify-content: flex-end;
                padding-top: 12px;
                border-top: 1px solid #f0f0f0;
                margin-top: 8px;
            }

            .teaching-load-table td[data-label="Actions"]:before {
                display: none;
            }

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

            .view-slip-btn {
                padding: 6px 12px !important;
                font-size: 0.75rem !important;
            }

            /* Empty state message */
            .teaching-load-table td[colspan] {
                display: block;
                text-align: center;
                font-size: 0.85rem;
                padding: 40px 20px;
                color: #666;
                font-style: italic;
            }

            .teaching-load-table td[colspan]:before {
                display: none;
            }

            /* Add Modal - Mobile positioning and animation */
            #addModal.modal-overlay {
                padding: 0 !important;
                align-items: flex-start !important;
                justify-content: center !important;
            }

            #addModal .modal-box {
                width: 100vw !important;
                max-width: 100vw !important;
                max-height: 100vh !important;
                height: 100vh !important;
                margin: 0 !important;
                border-radius: 0 !important;
                display: flex !important;
                flex-direction: column !important;
                overflow: hidden !important;
                padding: 0 !important;
                box-sizing: border-box !important;
                animation: slideDown 0.3s ease-out !important;
            }

            #addModal .modal-box form {
                width: 100% !important;
                height: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                box-sizing: border-box !important;
                display: flex !important;
                flex-direction: column !important;
                overflow: hidden !important;
            }

            #addModal .modal-header {
                font-size: 1rem !important;
                padding: 10px 16px !important;
                border-top-left-radius: 0 !important;
                border-top-right-radius: 0 !important;
                flex-shrink: 0 !important;
            }

            #addModal .modal-content {
                padding: 12px 16px 70px 16px !important;
                overflow-y: auto !important;
                overflow-x: hidden !important;
                flex: 1 !important;
                min-height: 0 !important;
                -webkit-overflow-scrolling: touch !important;
            }

            #addModal .toggle-switch {
                margin-bottom: 8px !important;
            }

            #addModal .toggle-switch label {
                font-size: 0.8rem !important;
            }

            #addModal .modal-form-group {
                flex-direction: row;
                align-items: center;
                gap: 8px;
                margin-bottom: 6px;
                padding-bottom: 10px;
                width: 100%;
                box-sizing: border-box;
                position: relative;
            }

            #addModal .modal-form-group label {
                min-width: 90px;
                max-width: 90px;
                flex-shrink: 0;
                margin-bottom: 0;
                font-size: 0.8rem;
                color: #222;
            }

            #addModal .modal-form-group input,
            #addModal .modal-form-group select,
            #addModal .modal-form-group textarea {
                flex: 1;
                min-width: 0;
                width: 100%;
                padding: 8px 10px;
                font-size: 0.85rem;
                box-sizing: border-box;
                min-height: 40px;
                line-height: 1.4;
            }

            #addModal .modal-form-group input[type="date"],
            #addModal .modal-form-group input[type="file"] {
                min-height: 40px;
                padding: 8px 10px;
            }

            #addModal .modal-form-group select {
                appearance: none;
                background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23333' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
                background-repeat: no-repeat;
                background-position: right 10px center;
                padding-right: 28px;
            }

            #addModal .validation-message {
                position: absolute;
                left: 90px;
                right: 8px;
                bottom: -6px;
                padding-left: 8px;
                font-size: 0.7rem;
            }

            #addModal .logic-error,
            #addModal .server-error {
                margin: 4px 0 !important;
                font-size: 0.75rem !important;
            }

            #addModal .modal-buttons {
                display: flex;
                flex-direction: row;
                justify-content: center;
                align-items: center;
                gap: 0.75rem;
                margin-top: 0;
                padding: 10px 16px;
                background: #fff;
                border-top: 1px solid #e0e0e0;
                flex-shrink: 0;
                position: sticky;
                bottom: 0;
                z-index: 10;
            }

            #addModal .modal-btn {
                flex: 1;
                max-width: 200px;
                padding: 10px 0 !important;
                font-size: 0.85rem !important;
                min-height: 40px;
                border-radius: 4.8px;
            }

            /* Add button: green background */
            #addModal .modal-btn.add {
                background: #2ecc71 !important;
                border: none !important;
                color: #fff !important;
                transition: background-color 0.15s ease, color 0.15s ease;
            }

            #addModal .modal-btn.add:hover {
                background: #27ae60 !important;
                color: #fff !important;
            }

            /* Cancel button: outlined red */
            #addModal .modal-btn.cancel {
                background: #fff !important;
                border: 2px solid #800000 !important;
                color: #800000 !important;
                transition: all 0.3s ease;
            }

            #addModal .modal-btn.cancel:hover {
                background: #800000 !important;
                color: #fff !important;
            }

            #addModal .modal-box.slide-up {
                animation: slideUp 0.3s ease-out !important;
            }

            /* Edit Modal - Mobile positioning and animation */
            #editModal.modal-overlay {
                padding: 0 !important;
                align-items: flex-start !important;
                justify-content: center !important;
            }

            #editModal .modal-box {
                width: 100vw !important;
                max-width: 100vw !important;
                max-height: 100vh !important;
                height: 100vh !important;
                margin: 0 !important;
                border-radius: 0 !important;
                display: flex !important;
                flex-direction: column !important;
                overflow: hidden !important;
                padding: 0 !important;
                box-sizing: border-box !important;
                animation: slideDown 0.3s ease-out !important;
            }

            #editModal .modal-box form {
                width: 100% !important;
                height: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                box-sizing: border-box !important;
                display: flex !important;
                flex-direction: column !important;
                overflow: hidden !important;
            }

            #editModal .modal-header {
                font-size: 1rem !important;
                padding: 10px 16px !important;
                border-top-left-radius: 0 !important;
                border-top-right-radius: 0 !important;
                flex-shrink: 0 !important;
            }

            #editModal .modal-content {
                padding: 12px 16px 70px 16px !important;
                overflow-y: auto !important;
                overflow-x: hidden !important;
                flex: 1 !important;
                min-height: 0 !important;
                -webkit-overflow-scrolling: touch !important;
            }

            #editModal .toggle-switch {
                margin-bottom: 8px !important;
            }

            #editModal .toggle-switch label {
                font-size: 0.8rem !important;
            }

            #editModal .modal-form-group {
                flex-direction: row;
                align-items: center;
                gap: 8px;
                margin-bottom: 6px;
                padding-bottom: 10px;
                width: 100%;
                box-sizing: border-box;
                position: relative;
            }

            #editModal .modal-form-group label {
                min-width: 90px;
                max-width: 90px;
                flex-shrink: 0;
                margin-bottom: 0;
                font-size: 0.8rem;
                color: #222;
            }

            #editModal .modal-form-group input,
            #editModal .modal-form-group select,
            #editModal .modal-form-group textarea {
                flex: 1;
                min-width: 0;
                width: 100%;
                padding: 8px 10px;
                font-size: 0.85rem;
                box-sizing: border-box;
                min-height: 40px;
                line-height: 1.4;
            }

            #editModal .modal-form-group input[type="date"],
            #editModal .modal-form-group input[type="file"] {
                min-height: 40px;
                padding: 8px 10px;
            }

            #editModal .modal-form-group select {
                appearance: none;
                background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23333' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
                background-repeat: no-repeat;
                background-position: right 10px center;
                padding-right: 28px;
            }

            #editModal .validation-message {
                position: absolute;
                left: 90px;
                right: 8px;
                bottom: -6px;
                padding-left: 8px;
                font-size: 0.7rem;
            }

            #editModal .logic-error,
            #editModal .server-error {
                margin: 4px 0 !important;
                font-size: 0.75rem !important;
            }

            #editModal .modal-buttons {
                display: flex;
                flex-direction: row;
                justify-content: center;
                align-items: center;
                gap: 0.75rem;
                margin-top: 0;
                padding: 10px 16px;
                background: #fff;
                border-top: 1px solid #e0e0e0;
                flex-shrink: 0;
                position: sticky;
                bottom: 0;
                z-index: 10;
            }

            #editModal .modal-btn {
                flex: 1;
                max-width: 200px;
                padding: 10px 0 !important;
                font-size: 0.85rem !important;
                min-height: 40px;
                border-radius: 4.8px;
            }

            /* Update button: blue background */
            #editModal .modal-btn.add {
                background: #7cc6fa !important;
                border: none !important;
                color: #fff !important;
                transition: background-color 0.15s ease, color 0.15s ease;
            }

            #editModal .modal-btn.add:hover {
                background: #5bb3f5 !important;
                color: #fff !important;
            }

            /* Cancel button: outlined red */
            #editModal .modal-btn.cancel {
                background: #fff !important;
                border: 2px solid #800000 !important;
                color: #800000 !important;
                transition: all 0.3s ease;
            }

            #editModal .modal-btn.cancel:hover {
                background: #800000 !important;
                color: #fff !important;
            }

            #editModal .modal-box.slide-up {
                animation: slideUp 0.3s ease-out !important;
            }

            /* Delete Modal - Mobile positioning and animation */
            #deleteModal.modal-overlay {
                padding: 0 !important;
                align-items: center !important;
                justify-content: center !important;
            }

            #deleteModal .modal-box {
                width: 85vw !important;
                max-width: 85vw !important;
                padding: 20px 16px !important;
                animation: slideDown 0.3s ease-out !important;
            }

            #deleteModal .modal-header {
                font-size: 1rem !important;
                margin-bottom: 12px !important;
            }

            #deleteModal .modal-box > div[style*="text-align: center"] {
                margin: 0 !important;
            }

            #deleteModal .modal-box > div[style*="text-align: center"] > div:first-child {
                font-size: 2.5rem !important;
                margin-bottom: 12px !important;
            }

            #deleteModal .modal-box > div[style*="text-align: center"] > div:nth-child(2) {
                font-size: 0.85rem !important;
                margin-bottom: 6px !important;
            }

            #deleteModal .modal-box > div[style*="text-align: center"] > div:nth-child(3) {
                font-size: 0.75rem !important;
                line-height: 1.4 !important;
            }

            #deleteModal .modal-buttons {
                display: flex !important;
                flex-direction: row !important;
                justify-content: center !important;
                align-items: center !important;
                gap: 0.75rem !important;
                margin-top: 16px !important;
            }

            #deleteModal .modal-btn {
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
                border-radius: 4.8px !important;
            }

            #deleteModal .modal-btn.delete {
                background: #ff3636 !important;
                color: #fff !important;
                border: 2px solid #ff3636 !important;
            }

            #deleteModal .modal-btn.cancel {
                background: #fff !important;
                color: #800000 !important;
                border: 2px solid #800000 !important;
            }

            #deleteModal .modal-box.slide-up {
                animation: slideUp 0.3s ease-out !important;
            }

            /* Slip Viewer Modal - Mobile positioning and animation */
            #slipModal.modal-overlay {
                padding: 0 !important;
                align-items: center !important;
                justify-content: center !important;
            }

            #slipModal .modal-box {
                width: 95vw !important;
                max-width: 95vw !important;
                max-height: 90vh !important;
                padding: 0 !important;
                margin: 0 !important;
                border-radius: 8px !important;
                display: flex !important;
                flex-direction: column !important;
                overflow: hidden !important;
                box-sizing: border-box !important;
                animation: slideDown 0.3s ease-out !important;
            }

            #slipModal .modal-header {
                font-size: 1rem !important;
                padding: 12px 16px !important;
                flex-shrink: 0 !important;
                border-bottom: 1px solid #e0e0e0 !important;
            }

            #slipModal .slip-content {
                padding: 16px !important;
                flex: 1 !important;
                overflow: auto !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                min-height: 0 !important;
            }

            #slipImage {
                max-width: 100% !important;
                max-height: calc(90vh - 120px) !important;
                width: auto !important;
                height: auto !important;
                object-fit: contain !important;
                border-radius: 4px !important;
            }

            #slipModal .close {
                width: 32px !important;
                height: 32px !important;
                font-size: 18px !important;
                top: 10px !important;
                right: 14px !important;
                background: rgba(0, 0, 0, 0.7) !important;
            }

            #slipModal .modal-box.slide-up {
                animation: slideUp 0.3s ease-out !important;
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
            <div class="faculty-title">Official Matters</div>
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
                        <th>Faculty/Department</th>
                        <th>Department</th>
                        <th>Purpose</th>
                        <th>Remarks</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Attachment</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($officialMatters as $matter)
                        <tr>
                            <td data-label="Faculty/Department">
                                @if ($matter->faculty)
                                    {{ $matter->faculty->faculty_fname }} {{ $matter->faculty->faculty_lname }}
                                @else
                                    {{ $matter->om_department }}
                                @endif
                            </td>
                            <td data-label="Department">
                                @if ($matter->faculty)
                                    {{ $matter->faculty->faculty_department }}
                                @else
                                    {{ $matter->om_department }}
                                @endif
                            </td>
                            <td data-label="Purpose">{{ $matter->om_purpose }}</td>
                            <td data-label="Remarks">{{ $matter->om_remarks }}</td>
                            <td data-label="Start Date">{{ \Carbon\Carbon::parse($matter->om_start_date)->format('F j, Y') }}</td>
                            <td data-label="End Date">{{ \Carbon\Carbon::parse($matter->om_end_date)->format('F j, Y') }}</td>
                            <td data-label="Attachment">
                                @if ($matter->om_attachment)
                                    <button class="view-slip-btn"
                                        onclick="viewSlip('{{ asset('storage/' . $matter->om_attachment) }}')">View</button>
                                @else
                                    N/A
                                @endif
                            </td>
                            <td data-label="Actions">
                                <div class="action-btns">
                                    <button class="edit-btn" data-id="{{ $matter->om_id }}"
                                        data-faculty="{{ $matter->faculty_id }}"
                                        data-department="{{ $matter->om_department }}"
                                        data-purpose="{{ $matter->om_purpose }}"
                                        data-remarks="{{ $matter->om_remarks }}"
                                        data-start="{{ $matter->om_start_date }}"
                                        data-end="{{ $matter->om_end_date }}"
                                        onclick="openUpdateModal(this)">
                                        &#9998;
                                    </button>
                                    <button class="delete-btn"
                                        onclick="openDeleteModal({{ $matter->om_id }})">&#128465;</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="text-align:center; font-style:italic; color:#666;">
                                No Official Matters Records found.
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
            <form class="modal-form" id="addForm" method="POST"
                enctype="multipart/form-data" action="{{ route('checker.official.matters.store') }}">
                @csrf
                <input type="hidden" name="is_department_mode" id="add_is_department_mode" value="0">

                <div class="modal-header"
                    style="background-color: #8B0000; color: white; padding: 14.4px 19.2px; font-size: 19.2px; font-weight: bold; width: 100%; margin: 0; display: flex; align-items: center; justify-content: center; text-align: center; letter-spacing: 0.4px; border-top-left-radius: 8px; border-top-right-radius: 8px;">
                    ADD OFFICIAL MATTER
                </div>

                <div class="modal-content" style="padding: 19.2px 19.2px 19.2px;">
                    <!-- Toggle Switch -->
                    <div class="toggle-switch">
                        <label>Select by Department</label>
                        <label class="switch">
                            <input type="checkbox" id="add_toggle_switch" onchange="toggleMode('add')">
                            <span class="slider"></span>
                        </label>
                    </div>

                    <!-- Faculty Mode -->
                    <div class="faculty-mode">
                        <div class="modal-form-group">
                            <label>Faculty</label>
                            <select name="faculty_id" id="add_faculty_id">
                                <option value="">-- Select Faculty --</option>
                                @foreach ($faculties as $faculty)
                                    <option value="{{ $faculty->faculty_id }}"
                                        data-department="{{ $faculty->faculty_department }}">
                                        {{ $faculty->faculty_fname }} {{ $faculty->faculty_lname }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="validation-message" id="add_faculty_id_error"></div>
                        </div>
                        <div class="modal-form-group">
                            <label>Department</label>
                            <input type="text" id="add_faculty_department" readonly>
                        </div>
                    </div>

                    <!-- Department Mode -->
                    <div class="department-mode">
                        <div class="modal-form-group">
                            <label>Department</label>
                            <select name="om_department" id="add_om_department">
                                <option value="">-- Select Department --</option>
                                <option value="All Instructor">All Instructor</option>
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
                            <div class="validation-message" id="add_om_department_error"></div>
                        </div>
                    </div>

                    <div class="modal-form-group">
                        <label>Purpose</label>
                        <input type="text" name="om_purpose" id="add_om_purpose">
                        <div class="validation-message" id="add_om_purpose_error"></div>
                    </div>

                    <div class="modal-form-group">
                        <label>Remarks</label>
                        <input type="text" name="om_remarks" id="add_om_remarks">
                        <div class="validation-message" id="add_om_remarks_error"></div>
                    </div>

                    <div class="modal-form-group">
                        <label>Start Date</label>
                        <input type="date" name="om_start_date" id="add_om_start_date">
                        <div class="validation-message" id="add_om_start_date_error"></div>
                    </div>

                    <div class="modal-form-group">
                        <label>End Date</label>
                        <input type="date" name="om_end_date" id="add_om_end_date">
                        <div class="validation-message" id="add_om_end_date_error"></div>
                    </div>

                    <div class="modal-form-group">
                        <label>Attachment</label>
                        <input type="file" name="om_attachment" accept="image/*" id="add_om_attachment">
                        <div class="validation-message" id="add_om_attachment_error"></div>
                    </div>

                    <div class="logic-error"></div>
                    @if ($errors->any())
                        <div class="server-error">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    <div class="modal-buttons">
                        <button type="submit" class="modal-btn add"
                            style="background: transparent !important; border: 2px solid #28a745 !important; color: #28a745 !important;">
                            Add
                        </button>
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
                @csrf
                @method('PUT')
                <input type="hidden" name="is_department_mode" id="edit_is_department_mode" value="0">

                <div class="modal-header"
                    style="background-color: #8B0000; color: white; padding: 14.4px 19.2px; font-size: 19.2px; font-weight: bold; width: 100%; margin: 0; display: flex; align-items: center; justify-content: center; text-align: center; letter-spacing: 0.4px; border-top-left-radius: 8px; border-top-right-radius: 8px;">
                    UPDATE OFFICIAL MATTER
                </div>

                <div class="modal-content" style="padding: 19.2px 19.2px 19.2px;">
                    <!-- Toggle Switch -->
                    <div class="toggle-switch">
                        <label>Select by Department</label>
                        <label class="switch">
                            <input type="checkbox" id="edit_toggle_switch" onchange="toggleMode('edit')">
                            <span class="slider"></span>
                        </label>
                    </div>

                    <!-- Faculty Mode -->
                    <div class="faculty-mode">
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
                            <div class="validation-message" id="edit_faculty_id_error"></div>
                        </div>
                        <div class="modal-form-group">
                            <label>Department</label>
                            <input type="text" id="edit_faculty_department" readonly>
                        </div>
                    </div>

                    <!-- Department Mode -->
                    <div class="department-mode">
                        <div class="modal-form-group">
                            <label>Department</label>
                            <select name="om_department" id="edit_om_department">
                                <option value="">-- Select Department --</option>
                                <option value="All Instructor">All Instructor</option>
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
                            <div class="validation-message" id="edit_om_department_error"></div>
                        </div>
                    </div>

                    <div class="modal-form-group">
                        <label>Purpose</label>
                        <input type="text" name="om_purpose" id="edit_om_purpose">
                        <div class="validation-message" id="edit_om_purpose_error"></div>
                    </div>

                    <div class="modal-form-group">
                        <label>Remarks</label>
                        <input type="text" name="om_remarks" id="edit_om_remarks">
                        <div class="validation-message" id="edit_om_remarks_error"></div>
                    </div>

                    <div class="modal-form-group">
                        <label>Start Date</label>
                        <input type="date" name="om_start_date" id="edit_om_start_date">
                        <div class="validation-message" id="edit_om_start_date_error"></div>
                    </div>

                    <div class="modal-form-group">
                        <label>End Date</label>
                        <input type="date" name="om_end_date" id="edit_om_end_date">
                        <div class="validation-message" id="edit_om_end_date_error"></div>
                    </div>

                    <div class="modal-form-group">
                        <label>Attachment</label>
                        <input type="file" name="om_attachment" accept="image/*" id="edit_om_attachment">
                        <div class="validation-message" id="edit_om_attachment_error"></div>
                    </div>

                    <div class="logic-error"></div>
                    @if ($errors->any())
                        <div class="server-error">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    <div class="modal-buttons">
                        <button type="submit" class="modal-btn add"
                            style="background: transparent !important; border: 2px solid #7cc6fa !important; color: #7cc6fa !important;">
                            Update
                        </button>
                        <button type="button" class="modal-btn cancel" onclick="closeModal('editModal')">Cancel</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Modal -->
    <div id="deleteModal" class="modal-overlay" style="display:none;">
        <form id="deleteForm" method="POST" class="modal-box">
            @csrf
            @method('DELETE')
            <div class="modal-header delete">DELETE OFFICIAL MATTER</div>
            <div style="text-align: center; margin: 0 px 0;">
                <div style="font-size: 4rem; color: #ff3636; margin-bottom: 20px;">⚠️</div>
                <div style="font-size: 1.2rem; color: #333; margin-bottom: 10px; font-weight: bold;">Are you sure?</div>
                <div style="font-size: 1rem; color: #666; line-height: 1.5;">
                    This action cannot be undone. The official matter record will be permanently deleted.
                </div>
            </div>
            <div class="modal-buttons">
                <button type="submit" class="modal-btn delete">Delete</button>
                <button type="button" class="modal-btn cancel" onclick="closeModal('deleteModal')">Cancel</button>
            </div>
        </form>
    </div>

    <!-- Slip Viewer Modal -->
    <div id="slipModal" class="modal-overlay" style="display:none;">
        <div class="modal-box">
            <button class="close" onclick="closeModal('slipModal')" title="Close">
                <span>&times;</span>
            </button>
            <div class="modal-header">Official Matter Attachment</div>
            <div class="slip-content">
                <img id="slipImage" src="" alt="Official Matter Attachment">
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        // =========================
        // Helper functions (defined first)
        // =========================
        function trim(v) {
            return (v || '').trim();
        }

        function isNotEmpty(v) {
            return trim(v).length > 0;
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
            const show = el.dataset.touched === 'true' || window.officialMatterSubmitAttempt === true;
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
            const show = el.dataset.touched === 'true' || window.officialMatterSubmitAttempt === true;
            m.textContent = show ? (msg || '') : '';
        }

        // =========================
        // Button state management functions
        // =========================
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

        // =========================
        // Validation functions
        // =========================
        function validateAddOfficialMatter() {
            const toggle = document.getElementById('add_toggle_switch');
            const isDepartmentMode = toggle && toggle.checked;
            
            const fac = document.getElementById('add_faculty_id');
            const dept = document.getElementById('add_om_department');
            const pur = document.getElementById('add_om_purpose');
            const rem = document.getElementById('add_om_remarks');
            const sdt = document.getElementById('add_om_start_date');
            const edt = document.getElementById('add_om_end_date');
            const img = document.getElementById('add_om_attachment');
            
            const vFac = !isDepartmentMode ? isNotEmpty(fac && fac.value) : true;
            const vDept = isDepartmentMode ? isNotEmpty(dept && dept.value) : true;
            const vPur = isNotEmpty(pur && pur.value);
            const vRem = isNotEmpty(rem && rem.value);
            const vSdt = isNotEmpty(sdt && sdt.value);
            const vEdt = isNotEmpty(edt && edt.value);
            const vImg = isNotEmpty(img && img.value) && validateImageSize(img);
            
            // Logic: start date <= end date
            let logicOk = true;
            const logicBox = document.querySelector('#addModal .logic-error');
            if (logicBox) logicBox.style.display = 'none';
            
            if (vSdt && vEdt) {
                const start = new Date(sdt.value);
                const end = new Date(edt.value);
                if (start > end) {
                    logicOk = false;
                    if (logicBox) {
                        logicBox.textContent = 'End date must be the same or later than start date.';
                        logicBox.style.display = 'block';
                    }
                }
            }
            
            setValidity(fac, vFac);
            setMessage(fac, vFac ? '' : 'Faculty is required');
            setValidity(dept, vDept);
            setMessage(dept, vDept ? '' : 'Department is required');
            setValidity(pur, vPur);
            setMessage(pur, vPur ? '' : 'Purpose is required');
            setValidity(rem, vRem);
            setMessage(rem, vRem ? '' : 'Remarks is required');
            setValidity(sdt, vSdt);
            setMessage(sdt, vSdt ? '' : 'Start date is required');
            setValidity(edt, vEdt);
            setMessage(edt, vEdt ? '' : 'End date is required');
            setValidity(img, vImg);
            setMessage(img, vImg ? '' : (isNotEmpty(img && img.value) ? 'Image size must be less than 2MB' : 'Attachment is required'));
            
            const isValid = vFac && vDept && vPur && vRem && vSdt && vEdt && vImg && logicOk;
            updateAddButtonState(isValid);
            return isValid;
        }

        function validateEditOfficialMatter() {
            const toggle = document.getElementById('edit_toggle_switch');
            const isDepartmentMode = toggle && toggle.checked;
            
            const fac = document.getElementById('edit_faculty_id');
            const dept = document.getElementById('edit_om_department');
            const pur = document.getElementById('edit_om_purpose');
            const rem = document.getElementById('edit_om_remarks');
            const sdt = document.getElementById('edit_om_start_date');
            const edt = document.getElementById('edit_om_end_date');
            const img = document.getElementById('edit_om_attachment');
            
            const vFac = !isDepartmentMode ? isNotEmpty(fac && fac.value) : true;
            const vDept = isDepartmentMode ? isNotEmpty(dept && dept.value) : true;
            const vPur = isNotEmpty(pur && pur.value);
            const vRem = isNotEmpty(rem && rem.value);
            const vSdt = isNotEmpty(sdt && sdt.value);
            const vEdt = isNotEmpty(edt && edt.value);
            const vImg = !img || !img.files || img.files.length === 0 || validateImageSize(img);
            
            // Logic: start date <= end date
            let logicOk = true;
            const logicBox = document.querySelector('#editModal .logic-error');
            if (logicBox) logicBox.style.display = 'none';
            
            if (vSdt && vEdt) {
                const start = new Date(sdt.value);
                const end = new Date(edt.value);
                if (start > end) {
                    logicOk = false;
                    if (logicBox) {
                        logicBox.textContent = 'End date must be the same or later than start date.';
                        logicBox.style.display = 'block';
                    }
                }
            }
            
            setValidity(fac, vFac);
            setMessage(fac, vFac ? '' : 'Faculty is required');
            setValidity(dept, vDept);
            setMessage(dept, vDept ? '' : 'Department is required');
            setValidity(pur, vPur);
            setMessage(pur, vPur ? '' : 'Purpose is required');
            setValidity(rem, vRem);
            setMessage(rem, vRem ? '' : 'Remarks is required');
            setValidity(sdt, vSdt);
            setMessage(sdt, vSdt ? '' : 'Start date is required');
            setValidity(edt, vEdt);
            setMessage(edt, vEdt ? '' : 'End date is required');
            setValidity(img, vImg);
            setMessage(img, vImg ? '' : 'Image size must be less than 2MB');
            
            const isValid = vFac && vDept && vPur && vRem && vSdt && vEdt && vImg && logicOk;
            updateEditButtonState(isValid);
            return isValid;
        }

        function toggleMode(mode) {
            const prefix = mode === 'add' ? 'add' : 'edit';
            const toggle = document.getElementById(`${prefix}_toggle_switch`);
            const isDepartmentMode = toggle.checked;
            const modal = document.getElementById(`${prefix}Modal`);
            const hiddenInput = document.getElementById(`${prefix}_is_department_mode`);

            hiddenInput.value = isDepartmentMode ? '1' : '0';

            if (isDepartmentMode) {
                modal.classList.add('toggle-on');
            } else {
                modal.classList.remove('toggle-on');
            }

            // Trigger validation after toggle change
            if (mode === 'add') {
                validateAddOfficialMatter();
            } else {
                validateEditOfficialMatter();
            }
        }

        function openModal(id) {
            const modal = document.getElementById(id);
            if (!modal) return;
            
            const modalBox = modal.querySelector('.modal-box');
            
            // Remove slide-up class to ensure slide-down animation
            if (modalBox && (id === 'addModal' || id === 'editModal' || id === 'deleteModal' || id === 'slipModal')) {
                modalBox.classList.remove('slide-up');
            }
            
            modal.style.display = 'flex';
            
            // Initialize button states
            if (id === 'addModal') {
                // Reset toggle
                document.getElementById('add_toggle_switch').checked = false;
                toggleMode('add');
                updateAddButtonState(false);
                validateAddOfficialMatter();
            } else if (id === 'editModal') {
                updateEditButtonState(false);
                validateEditOfficialMatter();
            }
        }

        function closeModal(id) {
            const modal = document.getElementById(id);
            if (!modal) return;
            
            const modalBox = modal.querySelector('.modal-box');
            
            // Add slide-up class for animation
            if (modalBox && (id === 'addModal' || id === 'editModal' || id === 'deleteModal' || id === 'slipModal')) {
                modalBox.classList.add('slide-up');
                
                // Hide modal after animation completes
                setTimeout(() => {
                    modal.style.display = 'none';
                    modalBox.classList.remove('slide-up');
                }, 300);
            } else {
                modal.style.display = 'none';
            }

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
                modal.classList.remove('toggle-on');
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
        }

        function viewSlip(url) {
            document.getElementById('slipImage').src = url;
            openModal('slipModal');
        }

        function openUpdateModal(button) {
            openModal('editModal');

            const id = button.getAttribute('data-id');
            const facultyId = button.getAttribute('data-faculty');
            const department = button.getAttribute('data-department');
            const purpose = button.getAttribute('data-purpose');
            const remarks = button.getAttribute('data-remarks');
            const startDate = button.getAttribute('data-start');
            const endDate = button.getAttribute('data-end');

            // Set toggle based on whether department is set
            const isDepartmentMode = department && !facultyId;
            document.getElementById('edit_toggle_switch').checked = isDepartmentMode;
            toggleMode('edit');

            if (isDepartmentMode) {
                document.getElementById('edit_om_department').value = department;
            } else {
                document.getElementById('edit_faculty_id').value = facultyId;
                const facultySelect = document.getElementById('edit_faculty_id');
                const selectedOption = facultySelect.querySelector(`option[value="${facultyId}"]`);
                if (selectedOption) {
                    document.getElementById('edit_faculty_department').value = 
                        selectedOption.getAttribute('data-department') || '';
                }
            }

            document.getElementById('edit_om_purpose').value = purpose;
            document.getElementById('edit_om_remarks').value = remarks;
            document.getElementById('edit_om_start_date').value = startDate;
            document.getElementById('edit_om_end_date').value = endDate;

            document.getElementById('editForm').action = '/checker/official-matters/' + id;
        }

        function openDeleteModal(id) {
            openModal('deleteModal');
            document.getElementById('deleteForm').action = '/checker/official-matters/' + id;
        }


        // Faculty department binding for add
        document.addEventListener('DOMContentLoaded', function() {
            const facultySelect = document.getElementById('add_faculty_id');
            const facultyDepartment = document.getElementById('add_faculty_department');
            if (facultySelect && facultyDepartment) {
                facultySelect.addEventListener('change', function() {
                    const department = this.options[this.selectedIndex].getAttribute('data-department') || '';
                    facultyDepartment.value = department;
                });
            }

            // Faculty department binding for edit
            const editFacultySelect = document.getElementById('edit_faculty_id');
            const editFacultyDepartment = document.getElementById('edit_faculty_department');
            if (editFacultySelect && editFacultyDepartment) {
                editFacultySelect.addEventListener('change', function() {
                    const department = this.options[this.selectedIndex].getAttribute('data-department') || '';
                    editFacultyDepartment.value = department;
                });
            }

            // Add event listeners for validation
            ['#add_faculty_id', '#add_om_department', '#add_om_purpose', '#add_om_remarks', '#add_om_start_date', '#add_om_end_date', '#add_om_attachment'].forEach(sel => {
                const el = document.querySelector(sel);
                if (!el) return;
                const evt = el.tagName === 'SELECT' ? 'change' : 'input';
                el.addEventListener(evt, function() {
                    validateAddOfficialMatter();
                });
                el.addEventListener('blur', () => {
                    el.dataset.touched = 'true';
                    validateAddOfficialMatter();
                });
            });

            // Toggle switch change listener
            const addToggle = document.getElementById('add_toggle_switch');
            if (addToggle) {
                addToggle.addEventListener('change', function() {
                    validateAddOfficialMatter();
                });
            }

            ['#edit_faculty_id', '#edit_om_department', '#edit_om_purpose', '#edit_om_remarks', '#edit_om_start_date', '#edit_om_end_date', '#edit_om_attachment'].forEach(sel => {
                const el = document.querySelector(sel);
                if (!el) return;
                const evt = el.tagName === 'SELECT' ? 'change' : 'input';
                el.addEventListener(evt, function() {
                    validateEditOfficialMatter();
                });
                el.addEventListener('blur', () => {
                    el.dataset.touched = 'true';
                    validateEditOfficialMatter();
                });
            });

            // Toggle switch change listener for edit
            const editToggle = document.getElementById('edit_toggle_switch');
            if (editToggle) {
                editToggle.addEventListener('change', function() {
                    validateEditOfficialMatter();
                });
            }

            // Form submission validation
            (function() {
                const addForm = document.getElementById('addForm');
                if (addForm) {
                    addForm.addEventListener('submit', function(e) {
                        window.officialMatterSubmitAttempt = true;
                        if (!validateAddOfficialMatter()) {
                            e.preventDefault();
                        }
                    });
                }
                const editForm = document.getElementById('editForm');
                if (editForm) {
                    editForm.addEventListener('submit', function(e) {
                        window.officialMatterSubmitAttempt = true;
                        if (!validateEditOfficialMatter()) {
                            e.preventDefault();
                        }
                    });
                }
            })();

            // Search functionality
            const searchInput = document.querySelector('.search-input');
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    let searchTerm = this.value.toLowerCase();
                    let rows = document.querySelectorAll('.teaching-load-table tbody tr');
                    let anyVisible = false;
                    let isMobile = window.innerWidth <= 430;

                    rows.forEach(row => {
                        if (row.classList.contains('no-results')) return;
                        let text = row.textContent.toLowerCase();
                        if (text.includes(searchTerm)) {
                            // Use block for mobile (cards), table-row for desktop
                            row.style.display = isMobile ? 'block' : '';
                            anyVisible = true;
                        } else {
                            row.style.display = 'none';
                        }
                    });

                    let tbody = document.querySelector('.teaching-load-table tbody');
                    let noResultsRow = tbody.querySelector('.no-results');

                    if (!anyVisible) {
                        if (!noResultsRow) {
                            noResultsRow = document.createElement('tr');
                            noResultsRow.classList.add('no-results');
                            noResultsRow.innerHTML =
                                `<td colspan="8" style="text-align:center; padding:20px; color:#999; font-style:italic;">No results found</td>`;
                            tbody.appendChild(noResultsRow);
                        }
                        // Ensure no-results row is visible
                        noResultsRow.style.display = isMobile ? 'block' : '';
                    } else {
                        if (noResultsRow) noResultsRow.remove();
                    }
                });
            }
        });
    </script>
@endsection

