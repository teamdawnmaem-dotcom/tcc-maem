@extends('layouts.appChecker')

@section('title', 'Leave Management - Tagoloan Community College')
@section('files-active', 'active')
@section('leave-active', 'active')

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

        /* Slide animations for Add Leave Modal */
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

        #addModal .modal-box.slide-up {
            animation: slideUp 0.3s ease-out !important;
        }

        #editModal .modal-box.slide-up {
            animation: slideUp 0.3s ease-out !important;
        }

        #deleteModal .modal-box.slide-up {
            animation: slideUp 0.3s ease-out !important;
        }

        #slipModal .modal-box.slide-up {
            animation: slideUp 0.3s ease-out !important;
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

            /* Add Modal - Mobile positioning and animation */
            #addModal.modal-overlay {
                padding: 0 !important;
                align-items: flex-start !important;
                justify-content: center !important;
            }

            #addModal .modal-box {
                width: 100vw !important;
                max-width: 100vw !important;
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
                margin: 0 !important;
                padding: 0 !important;
                box-sizing: border-box !important;
            }

            #addModal .modal-header {
                font-size: 1.1rem !important;
                padding: 12px 16px !important;
                border-top-left-radius: 0 !important;
                border-top-right-radius: 0 !important;
            }

            #addModal .modal-content {
                padding: 20px 24px !important;
                overflow: visible !important;
                max-height: none !important;
            }

            #addModal .modal-form-group {
                flex-direction: row;
                align-items: center;
                gap: 9.6px;
                margin-bottom: 9.6px;
                padding-bottom: 14.4px;
                width: 100%;
                box-sizing: border-box;
                position: relative;
            }

            #addModal .modal-form-group label {
                min-width: 104px;
                max-width: 104px;
                flex-shrink: 0;
                margin-bottom: 0;
                font-size: 0.85rem;
                color: #222;
            }

            #addModal .modal-form-group input,
            #addModal .modal-form-group select,
            #addModal .modal-form-group textarea {
                flex: 1;
                min-width: 0;
                width: 100%;
                padding: 10px 12px;
                font-size: 0.9rem;
                box-sizing: border-box;
                height: auto;
                line-height: 1.5;
            }

            #addModal .validation-message {
                position: absolute;
                left: 104px;
                right: 9.6px;
                bottom: -8px;
                padding-left: 9.6px;
                font-size: 0.75rem;
            }

            #addModal .modal-buttons {
                display: flex;
                flex-direction: row;
                justify-content: center;
                align-items: center;
                gap: 1rem;
                margin-top: 12px;
            }

            #addModal .modal-btn {
                flex: 1;
                max-width: 200px;
                padding: 11.2px 0 !important;
                font-size: 0.88rem !important;
                min-height: 44px;
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
                margin: 0 !important;
                padding: 0 !important;
                box-sizing: border-box !important;
            }

            #editModal .modal-header {
                font-size: 1.1rem !important;
                padding: 12px 16px !important;
                border-top-left-radius: 0 !important;
                border-top-right-radius: 0 !important;
            }

            #editModal .modal-content {
                padding: 20px 24px !important;
                overflow: visible !important;
                max-height: none !important;
            }

            #editModal .modal-form-group {
                flex-direction: row;
                align-items: center;
                gap: 9.6px;
                margin-bottom: 9.6px;
                padding-bottom: 14.4px;
                width: 100%;
                box-sizing: border-box;
                position: relative;
            }

            #editModal .modal-form-group label {
                min-width: 104px;
                max-width: 104px;
                flex-shrink: 0;
                margin-bottom: 0;
                font-size: 0.85rem;
                color: #222;
            }

            #editModal .modal-form-group input,
            #editModal .modal-form-group select,
            #editModal .modal-form-group textarea {
                flex: 1;
                min-width: 0;
                width: 100%;
                padding: 10px 12px;
                font-size: 0.9rem;
                box-sizing: border-box;
                height: auto;
                line-height: 1.5;
            }

            #editModal .validation-message {
                position: absolute;
                left: 104px;
                right: 9.6px;
                bottom: -8px;
                padding-left: 9.6px;
                font-size: 0.75rem;
            }

            #editModal .modal-buttons {
                display: flex;
                flex-direction: row;
                justify-content: center;
                align-items: center;
                gap: 1rem;
                margin-top: 12px;
            }

            #editModal .modal-btn {
                flex: 1;
                max-width: 200px;
                padding: 11.2px 0 !important;
                font-size: 0.88rem !important;
                min-height: 44px;
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

            #deleteModal .modal-box > div[style*="text-align: center"] > div:first-of-type {
                font-size: 2.5rem !important;
                margin-bottom: 12px !important;
            }

            #deleteModal .modal-box > div[style*="text-align: center"] > div:nth-of-type(2) {
                font-size: 0.85rem !important;
                margin-bottom: 6px !important;
            }

            #deleteModal .modal-box > div[style*="text-align: center"] > div:nth-of-type(3) {
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

            #deleteModal .modal-btn.delete:hover {
                background: #d32f2f !important;
                border-color: #d32f2f !important;
            }

            #deleteModal .modal-btn.cancel {
                background: #fff !important;
                color: #800000 !important;
                border: 2px solid #800000 !important;
            }

            #deleteModal .modal-btn.cancel:hover {
                background: #800000 !important;
                color: #fff !important;
            }

            #deleteModal .modal-box.slide-up {
                animation: slideUp 0.3s ease-out !important;
            }

            /* Slip Modal - Mobile positioning and animation */
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

            #slipModal .close:hover {
                background: rgba(139, 0, 0, 0.9) !important;
            }

            #slipModal .modal-box.slide-up {
                animation: slideUp 0.3s ease-out !important;
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
            <div class="faculty-title">Leave Management</div>
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
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Attachment</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($leaves as $leave)
                        <tr>
                            <td data-label="Faculty Name">{{ $leave->faculty->faculty_fname }} {{ $leave->faculty->faculty_lname }}</td>
                            <td data-label="Department">{{ $leave->faculty->faculty_department }}</td>
                            <td data-label="Purpose">{{ $leave->lp_purpose }}</td>
                            <td data-label="Start Date">{{ \Carbon\Carbon::parse($leave->leave_start_date)->format('F j, Y') }}</td>
                            <td data-label="End Date">{{ \Carbon\Carbon::parse($leave->leave_end_date)->format('F j, Y') }}</td>
                            <td data-label="Attachment">
                                @if ($leave->lp_image)
                                    <button class="view-slip-btn"
                                        onclick="viewSlip('{{ asset('storage/' . $leave->lp_image) }}')">View</button>
                                @else
                                    N/A
                                @endif
                            </td>
                            <td data-label="Actions">
                                <div class="action-btns">
                                    <button class="edit-btn" data-id="{{ $leave->lp_id }}"
                                        data-faculty="{{ $leave->faculty_id }}" data-purpose="{{ $leave->lp_purpose }}"
                                        data-start="{{ $leave->leave_start_date }}"
                                        data-end="{{ $leave->leave_end_date }}" onclick="openUpdateLeaveModal(this)">
                                        &#9998;
                                    </button>
                                    <button class="delete-btn"
                                        onclick="openDeleteModal({{ $leave->lp_id }})">&#128465;</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align:center; font-style:italic; color:#666;">
                                No Leave Records found.
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
                enctype="multipart/form-data" action="{{ route('checker.leaves.store') }}">
                @csrf
                <input type="hidden" name="lp_type" value="Leave">

                <div class="modal-header"
                    style="background-color: #8B0000; color: white; padding: 14.4px 19.2px; font-size: 19.2px; font-weight: bold; width: 100%; margin: 0; display: flex; align-items: center; justify-content: center; text-align: center; letter-spacing: 0.4px; border-top-left-radius: 8px; border-top-right-radius: 8px;">
                    ADD LEAVE
                </div>

                <div class="modal-content" style="padding: 19.2px 19.2px 19.2px;">
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
                        #addModal .modal-form-group select,
                        #addModal .modal-form-group textarea {
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
                    </div>
                    <div class="modal-form-group">
                        <label>Department</label>
                        <input type="text" id="facultyDepartment" readonly>
                    </div>
                    <div class="modal-form-group"><label>Purpose</label><input type="text" name="lp_purpose"
                            id="add_lp_purpose"></div>
                    <div class="modal-form-group"><label>Start Date</label><input type="date" name="leave_start_date"
                            id="add_leave_start_date"></div>
                    <div class="modal-form-group"><label>End Date</label><input type="date" name="leave_end_date"
                            id="add_leave_end_date"></div>
                    <div class="modal-form-group"><label>Leave Form</label><input type="file" name="lp_image"
                            accept="image/*" id="add_lp_image">
                        <div class="validation-message" id="add_lp_image_error"></div>
                    </div>

                    <div class="logic-error"
                        style="display:none; color:#ff3636; text-align:center; margin:6px 0; font-weight:600;"></div>
                    @php($addDateError = $errors->first('leave_start_date') ?: $errors->first('leave_end_date'))
                    @if ($addDateError)
                        <div class="server-error" style="color:#ff3636; text-align:center; margin:6px 0; font-weight:600;">
                            {{ $addDateError }}
                        </div>
                    @endif

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
            <form class="modal-form" id="editForm" method="POST"
                enctype="multipart/form-data">
                @csrf @method('PUT')
                <input type="hidden" name="lp_type" value="Leave">

                <div class="modal-header"
                    style="background-color: #8B0000; color: white; padding: 14.4px 19.2px; font-size: 19.2px; font-weight: bold; width: 100%; margin: 0; display: flex; align-items: center; justify-content: center; text-align: center; letter-spacing: 0.4px; border-top-left-radius: 8px; border-top-right-radius: 8px;">
                    UPDATE LEAVE
                </div>

                <div class="modal-content" style="padding: 19.2px 19.2px 19.2px;">
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
                        #editModal .modal-form-group select,
                        #editModal .modal-form-group textarea {
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
                        <input type="text" id="edit_faculty_department" readonly>
                    </div>
                    <div class="modal-form-group"><label>Purpose</label><input type="text" name="lp_purpose"
                            id="edit_lp_purpose"></div>
                    <div class="modal-form-group"><label>Start Date</label><input type="date" name="leave_start_date"
                            id="edit_leave_start_date"></div>
                    <div class="modal-form-group"><label>End Date</label><input type="date" name="leave_end_date"
                            id="edit_leave_end_date"></div>
                    <div class="modal-form-group"><label>Leave Form</label><input type="file" name="lp_image"
                            accept="image/*" id="edit_lp_image">
                        <div class="validation-message" id="edit_lp_image_error"></div>
                        
                    </div>

                    <div class="logic-error"
                        style="display:none; color:#ff3636; text-align:center; margin:6px 0; font-weight:600;"></div>
                    @php($editDateError = $errors->first('leave_start_date') ?: $errors->first('leave_end_date'))
                    @if ($editDateError)
                        <div class="server-error" style="color:#ff3636; text-align:center; margin:6px 0; font-weight:600;">
                            {{ $editDateError }}
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


    <!-- Delete Leave Modal -->
    <div id="deleteModal" class="modal-overlay" style="display:none;">
        <form id="deleteForm" method="POST" class="modal-box">
            @csrf
            @method('DELETE')
            <div class="modal-header delete">DELETE LEAVE</div>

            <!-- Warning Icon and Message -->
            <div style="text-align: center; margin:0 px 0;">
                <div style="font-size: 4rem; color: #ff3636; margin-bottom: 20px;">⚠️</div>
                <div style="font-size: 1.2rem; color: #333; margin-bottom: 10px; font-weight: bold;">Are you sure?</div>
                <div style="font-size: 1rem; color: #666; line-height: 1.5;">
                    This action cannot be undone. The leave record will be permanently deleted.
                </div>
            </div>

            <!-- Action Buttons -->
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
            <div class="modal-header">Leave Slip</div>
            <div class="slip-content">
                <img id="slipImage" src="" alt="Leave Slip">
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        // Faculty department binding for add
        document.addEventListener('DOMContentLoaded', function() {
            const facultySelect = document.getElementById('facultySelect');
            const facultyDepartment = document.getElementById('facultyDepartment');
            facultySelect.addEventListener('change', function() {
                const department = this.options[this.selectedIndex].getAttribute('data-department') || '';
                facultyDepartment.value = department;
            });
        });

        function openModal(id) {
            const modal = document.getElementById(id);
            if (!modal) return;
            
            // For Add, Edit, Delete, and Slip Leave Modals, ensure slide-up class is removed for slide-down animation
            if (id === 'addModal' || id === 'editModal' || id === 'deleteModal' || id === 'slipModal') {
                const modalBox = modal.querySelector('.modal-box');
                if (modalBox) {
                    modalBox.classList.remove('slide-up');
                }
            }
            
            modal.style.display = 'flex';
            
            // Initialize button states
            if (id === 'addModal') {
                updateAddButtonState(false);
                validateAddLeave();
            } else if (id === 'editModal') {
                updateEditButtonState(false);
                validateEditLeave();
            }
        }

        function closeModal(id) {
            const modal = document.getElementById(id);
            if (!modal) return;
            
            // For Add, Edit, Delete, and Slip Leave Modals, add slide-up animation on mobile
            if (id === 'addModal' || id === 'editModal' || id === 'deleteModal' || id === 'slipModal') {
                const modalBox = modal.querySelector('.modal-box');
                if (modalBox) {
                    // Add slide-up animation class
                    modalBox.classList.add('slide-up');
                    
                    // Wait for animation to complete, then hide modal
                    setTimeout(() => {
                        modal.style.display = 'none';
                        modalBox.classList.remove('slide-up');
                        
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
                        
                        // Clear validation for edit modal (keep values)
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
                    }, 300); // Match animation duration
                    return;
                }
            }
            
            // Clear form and validation for add modal (if no animation)
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
            
            // Hide modal
            modal.style.display = 'none';
        }

        function viewSlip(url) {
            document.getElementById('slipImage').src = url;
            openModal('slipModal');
        }

        function openUpdateLeaveModal(button) {
            openModal('editModal');

            // Read data attributes
            const id = button.getAttribute('data-id');
            const facultyId = button.getAttribute('data-faculty');
            const purpose = button.getAttribute('data-purpose');
            const startDate = button.getAttribute('data-start');
            const endDate = button.getAttribute('data-end');

            // Fill form
            document.getElementById('edit_faculty_id').value = facultyId;

            // Auto-fill department based on faculty
            const facultySelect = document.getElementById('edit_faculty_id');
            const selectedOption = facultySelect.querySelector(`option[value="${facultyId}"]`);
            document.getElementById('edit_faculty_department').value = selectedOption ?
                selectedOption.getAttribute('data-department') :
                '';

            document.getElementById('edit_lp_purpose').value = purpose;
            document.getElementById('edit_leave_start_date').value = startDate;
            document.getElementById('edit_leave_end_date').value = endDate;

            // Set action URL
            document.getElementById('editForm').action = '/checker/leaves/' + id;
        }

        function openDeleteModal(id) {
            openModal('deleteModal');
            document.getElementById('deleteForm').action = '/checker/leaves/' + id;
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
        // Show-once-touched validation (Leave)
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
            const show = el.dataset.touched === 'true' || window.leaveSubmitAttempt === true;
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
            const show = el.dataset.touched === 'true' || window.leaveSubmitAttempt === true;
            m.textContent = show ? (msg || '') : '';
        }

        // Real-time leave overlap checking functions
        async function checkLeaveOverlapAdd() {
            const facultySelect = document.querySelector('#addModal [name="faculty_id"]');
            const startDateField = document.querySelector('#addModal [name="leave_start_date"]');
            const endDateField = document.querySelector('#addModal [name="leave_end_date"]');
            const logicBox = document.querySelector('#addModal .logic-error');
            
            if (!facultySelect || !startDateField || !endDateField || 
                !facultySelect.value || !startDateField.value || !endDateField.value) {
                if (logicBox) {
                    // Only clear if the current message is about leave overlap
                    const currentMessage = logicBox.textContent;
                    if (currentMessage && currentMessage.includes('already has a leave request')) {
                        logicBox.style.display = 'none';
                        logicBox.textContent = '';
                    }
                }
                return;
            }

            try {
                const response = await fetch('/checker/leaves/check-leave-overlap', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        faculty_id: facultySelect.value,
                        start_date: startDateField.value,
                        end_date: endDateField.value
                    })
                });

                const data = await response.json();
                
                if (logicBox) {
                    if (data.has_overlap) {
                        logicBox.textContent = data.message;
                        logicBox.style.display = 'block';
                    } else {
                        // Only clear if the current message is about leave overlap
                        const currentMessage = logicBox.textContent;
                        if (currentMessage && currentMessage.includes('already has a leave request')) {
                            logicBox.style.display = 'none';
                            logicBox.textContent = '';
                        }
                    }
                }
                
                // Trigger validation after leave overlap check
                validateAddLeave();
            } catch (error) {
                console.error('Error checking leave overlap:', error);
            }
        }

        async function checkLeaveOverlapEdit() {
            const facultySelect = document.querySelector('#edit_faculty_id');
            const startDateField = document.querySelector('#edit_leave_start_date');
            const endDateField = document.querySelector('#edit_leave_end_date');
            const logicBox = document.querySelector('#editModal .logic-error');
            const editForm = document.getElementById('editForm');
            const currentId = editForm ? editForm.action.split('/').pop() : null;
            
            if (!facultySelect || !startDateField || !endDateField || 
                !facultySelect.value || !startDateField.value || !endDateField.value) {
                if (logicBox) {
                    // Only clear if the current message is about leave overlap
                    const currentMessage = logicBox.textContent;
                    if (currentMessage && currentMessage.includes('already has a leave request')) {
                        logicBox.style.display = 'none';
                        logicBox.textContent = '';
                    }
                }
                return;
            }

            try {
                const response = await fetch('/checker/leaves/check-leave-overlap', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        faculty_id: facultySelect.value,
                        start_date: startDateField.value,
                        end_date: endDateField.value,
                        exclude_id: currentId
                    })
                });

                const data = await response.json();
                
                if (logicBox) {
                    if (data.has_overlap) {
                        logicBox.textContent = data.message;
                        logicBox.style.display = 'block';
                    } else {
                        // Only clear if the current message is about leave overlap
                        const currentMessage = logicBox.textContent;
                        if (currentMessage && currentMessage.includes('already has a leave request')) {
                            logicBox.style.display = 'none';
                            logicBox.textContent = '';
                        }
                    }
                }
                
                // Trigger validation after leave overlap check
                validateEditLeave();
            } catch (error) {
                console.error('Error checking leave overlap:', error);
            }
        }

        function validateAddLeave() {
            const fac = document.getElementById('facultySelect');
            const pur = document.getElementById('add_lp_purpose');
            const sdt = document.getElementById('add_leave_start_date');
            const edt = document.getElementById('add_leave_end_date');
            const img = document.querySelector('#addModal [name="lp_image"]');
            const vFac = isNotEmpty(fac && fac.value);
            const vPur = isNotEmpty(pur && pur.value);
            const vSdt = isNotEmpty(sdt && sdt.value);
            const vEdt = isNotEmpty(edt && edt.value);
            const vImg = isNotEmpty(img && img.value) && validateImageSize(img);
            // Logic: start date <= end date; start not in past (unless purpose is Emergency or Sick Leave)
            let logicOk = true;
            const logicBox = document.querySelector('#addModal .logic-error');
            if (logicBox) logicBox.style.display = 'none';
            if (vSdt) {
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                const start = new Date(sdt.value);
                // Check if purpose allows past dates (case-insensitive)
                const purposeValue = (pur && pur.value) ? pur.value.trim().toLowerCase() : '';
                const allowsPastDate = purposeValue === 'emergency' || purposeValue === 'sick leave';
                
                if (start < today && !allowsPastDate) {
                    logicOk = false;
                    if (logicBox) {
                        logicBox.textContent = 'Start date cannot be in the past.';
                        logicBox.style.display = 'block';
                    }
                }
            }
            if (logicOk && vSdt && vEdt) {
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
            
            // Check for leave overlaps (synchronous check like date validation)
            if (logicOk && vFac && vSdt && vEdt) {
                const conflictMessage = logicBox ? logicBox.textContent : '';
                if (conflictMessage && conflictMessage.includes('already has a leave request')) {
                    logicOk = false;
                    if (logicBox) {
                        logicBox.textContent = conflictMessage;
                        logicBox.style.display = 'block';
                    }
                } else {
                    // Clear overlap messages if no longer valid
                    if (logicBox && logicBox.textContent.includes('already has a leave request')) {
                        logicBox.style.display = 'none';
                        logicBox.textContent = '';
                    }
                }
            }
            
            setValidity(fac, vFac);
            setMessage(fac, vFac ? '' : 'Faculty is required');
            setValidity(pur, vPur);
            setMessage(pur, vPur ? '' : 'Purpose is required');
            setValidity(sdt, vSdt);
            setMessage(sdt, vSdt ? '' : 'Start date is required');
            setValidity(edt, vEdt);
            setMessage(edt, vEdt ? '' : 'End date is required');
            setValidity(img, vImg);
            setMessage(img, vImg ? '' : (isNotEmpty(img && img.value) ? 'Image size must be less than 2MB' : 'Leave form is required'));
            
            const isValid = vFac && vPur && vSdt && vEdt && vImg && logicOk;
            updateAddButtonState(isValid);
            return isValid;
        }

        function validateEditLeave() {
            const fac = document.getElementById('edit_faculty_id');
            const pur = document.getElementById('edit_lp_purpose');
            const sdt = document.getElementById('edit_leave_start_date');
            const edt = document.getElementById('edit_leave_end_date');
            const img = document.getElementById('edit_lp_image');
            const vFac = isNotEmpty(fac && fac.value);
            const vPur = isNotEmpty(pur && pur.value);
            const vSdt = isNotEmpty(sdt && sdt.value);
            const vEdt = isNotEmpty(edt && edt.value);
            const vImg = !img || !img.files || img.files.length === 0 || validateImageSize(img);
            // Logic: start date <= end date; start not in past (unless purpose is Emergency or Sick Leave)
            let logicOk = true;
            const logicBox = document.querySelector('#editModal .logic-error');
            if (logicBox) logicBox.style.display = 'none';
            if (vSdt) {
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                const start = new Date(sdt.value);
                // Check if purpose allows past dates (case-insensitive)
                const purposeValue = (pur && pur.value) ? pur.value.trim().toLowerCase() : '';
                const allowsPastDate = purposeValue === 'emergency' || purposeValue === 'sick leave';
                
                if (start < today && !allowsPastDate) {
                    logicOk = false;
                    if (logicBox) {
                        logicBox.textContent = 'Start date cannot be in the past.';
                        logicBox.style.display = 'block';
                    }
                }
            }
            if (logicOk && vSdt && vEdt) {
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
            
            // Check for leave overlaps (synchronous check like date validation)
            if (logicOk && vFac && vSdt && vEdt) {
                const conflictMessage = logicBox ? logicBox.textContent : '';
                if (conflictMessage && conflictMessage.includes('already has a leave request')) {
                    logicOk = false;
                    if (logicBox) {
                        logicBox.textContent = conflictMessage;
                        logicBox.style.display = 'block';
                    }
                } else {
                    // Clear overlap messages if no longer valid
                    if (logicBox && logicBox.textContent.includes('already has a leave request')) {
                        logicBox.style.display = 'none';
                        logicBox.textContent = '';
                    }
                }
            }
            
            setValidity(fac, vFac);
            setMessage(fac, vFac ? '' : 'Faculty is required');
            setValidity(pur, vPur);
            setMessage(pur, vPur ? '' : 'Purpose is required');
            setValidity(sdt, vSdt);
            setMessage(sdt, vSdt ? '' : 'Start date is required');
            setValidity(edt, vEdt);
            setMessage(edt, vEdt ? '' : 'End date is required');
            setValidity(img, vImg);
            setMessage(img, vImg ? '' : 'Image size must be less than 2MB');
            
            const isValid = vFac && vPur && vSdt && vEdt && vImg && logicOk;
            updateEditButtonState(isValid);
            return isValid;
        }

        ['#facultySelect', '#add_lp_purpose', '#add_leave_start_date', '#add_leave_end_date', '#addModal [name="lp_image"]'].forEach(sel => {
            const el = document.querySelector(sel);
            if (!el) return;
            const evt = el.tagName === 'SELECT' ? 'change' : 'input';
            el.addEventListener(evt, function() {
                validateAddLeave();
                // Also check for leave overlaps when faculty or dates change
                if (sel.includes('facultySelect') || sel.includes('leave_start_date') || sel.includes('leave_end_date')) {
                    checkLeaveOverlapAdd();
                }
            });
            el.addEventListener('blur', () => {
                el.dataset.touched = 'true';
                validateAddLeave();
            });
        });
        ['#edit_faculty_id', '#edit_lp_purpose', '#edit_leave_start_date', '#edit_leave_end_date', '#edit_lp_image'].forEach(sel => {
            const el = document.querySelector(sel);
            if (!el) return;
            const evt = el.tagName === 'SELECT' ? 'change' : 'input';
            el.addEventListener(evt, function() {
                validateEditLeave();
                // Also check for leave overlaps when faculty or dates change
                if (sel.includes('faculty_id') || sel.includes('leave_start_date') || sel.includes('leave_end_date')) {
                    checkLeaveOverlapEdit();
                }
            });
            el.addEventListener('blur', () => {
                el.dataset.touched = 'true';
                validateEditLeave();
            });
        });

        (function() {
            const addForm = document.getElementById('addForm');
            if (addForm) {
                addForm.addEventListener('submit', function(e) {
                    window.leaveSubmitAttempt = true;
                    if (!validateAddLeave()) {
                        e.preventDefault();
                    }
                });
            }
            const editForm = document.getElementById('editForm');
            if (editForm) {
                editForm.addEventListener('submit', function(e) {
                    window.leaveSubmitAttempt = true;
                    if (!validateEditLeave()) {
                        e.preventDefault();
                    }
                });
            }
        })();

        // =========================
        // Responsive Table Search with "No results found"
        // =========================
        document.querySelector('.search-input').addEventListener('input', function() {
            let searchTerm = this.value.toLowerCase();
            let rows = document.querySelectorAll('.teaching-load-table tbody tr');
            let anyVisible = false;
            let isMobile = window.innerWidth <= 430;

            rows.forEach(row => {
                // Skip the "no results" row if it exists
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

            // Handle "no results" row
            let tbody = document.querySelector('.teaching-load-table tbody');
            let noResultsRow = tbody.querySelector('.no-results');

            if (!anyVisible) {
                if (!noResultsRow) {
                    noResultsRow = document.createElement('tr');
                    noResultsRow.classList.add('no-results');
                    noResultsRow.innerHTML =
                        `<td colspan="7" style="text-align:center; padding:20px; color:#999; font-style:italic;">No results found</td>`;
                    tbody.appendChild(noResultsRow);
                }
                // Ensure no-results row is visible
                noResultsRow.style.display = isMobile ? 'block' : '';
            } else {
                if (noResultsRow) noResultsRow.remove();
            }
        });
    </script>
@endsection
