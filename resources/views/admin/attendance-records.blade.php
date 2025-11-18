@extends('layouts.appAdmin')

@section('title', 'Real-Time Attendance Records - Tagoloan Community College')
@section('reports-active', 'active')
@section('attendance-records-active', 'active')

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
        position: fixed;
        top: 104px;
        right: 32px;
        z-index: 100;
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
    .teaching-load-table-container {
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 8px 32px rgba(0,0,0,0.22), 0 1.5px 8px rgba(0,0,0,0.12);
        overflow: hidden;
        overflow-x: auto;
    }
    .teaching-load-table {
        width: 100%;
        min-width: 1120px;
        border-collapse: collapse;
        table-layout: fixed;
    }
    .teaching-load-table th {
        background: #8B0000;
        color: #fff;
        padding: 12.8px 6.4px;
        font-size: 0.72rem;
        font-weight: bold;
        border: none;
        text-align: center;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        min-height: 40px;
    }
    .teaching-load-table td {
        padding: 12.8px 6.4px;
        text-align: center;
        font-size: 0.68rem;
        border: none;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        vertical-align: middle;
        min-height: 36px;
    }
    .teaching-load-table tr:hover { background: #fff2e6; cursor: pointer; }
    
    /* Clickable row styling */
    .teaching-load-table tbody tr {
        cursor: pointer;
        transition: background-color 0.2s ease;
    }
    
    .teaching-load-table tbody tr:hover {
        background: #fff2e6 !important;
    }
    
    /* Column width adjustments */
    .teaching-load-table th:nth-child(1), .teaching-load-table td:nth-child(1) { width: 8%; } /* Date */
    .teaching-load-table th:nth-child(2), .teaching-load-table td:nth-child(2) { width: 10%; } /* Faculty Name */
    .teaching-load-table th:nth-child(3), .teaching-load-table td:nth-child(3) { width: 10%; } /* Department */
    .teaching-load-table th:nth-child(4), .teaching-load-table td:nth-child(4) { width: 7%; } /* Course Code */
    .teaching-load-table th:nth-child(5), .teaching-load-table td:nth-child(5) { width: 10%; } /* Subject */
    .teaching-load-table th:nth-child(6), .teaching-load-table td:nth-child(6) { width: 7%; } /* Class Section */
    .teaching-load-table th:nth-child(7), .teaching-load-table td:nth-child(7) { width: 6%; } /* Day */
    .teaching-load-table th:nth-child(8), .teaching-load-table td:nth-child(8) { 
        width: 12%; 
        white-space: normal; 
        word-wrap: break-word;
        line-height: 1.2;
    } /* Time Schedule */
    .teaching-load-table th:nth-child(9), .teaching-load-table td:nth-child(9) { width: 5%; } /* Time In */
    .teaching-load-table th:nth-child(10), .teaching-load-table td:nth-child(10) { width: 5%; } /* Time Out */
    .teaching-load-table th:nth-child(11), .teaching-load-table td:nth-child(11) { width: 7%; } /* Time Duration */
    .teaching-load-table th:nth-child(12), .teaching-load-table td:nth-child(12) { width: 6%; } /* Room Name */
    .teaching-load-table th:nth-child(13), .teaching-load-table td:nth-child(13) { width: 5%; } /* Building No */
    .teaching-load-table th:nth-child(14), .teaching-load-table td:nth-child(14) { width: 5%; } /* Status */
    .teaching-load-table th:nth-child(15), .teaching-load-table td:nth-child(15) { width: 7%; } /* Remarks */

    /* Filter Styles - Clean & Neat Design */
    .filter-section {
        background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
        border: 1px solid #e9ecef;
        border-radius: 12px;
        box-shadow: 0 6.4px 20px rgba(0,0,0,0.08);
        padding: 24px;
        margin-bottom: 20px;
        position: relative;
        overflow: hidden;
    }

    .filter-section::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #8B0000, #6d0000);
    }

    .filter-header {
        display: flex;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 12px;
        border-bottom: 1.6px solid #f1f3f4;
    }

    .filter-title {
        font-size: 0.96rem;
        font-weight: 600;
        color: #2c3e50;
        margin: 0;
        display: flex;
        align-items: center;
    }

    .filter-title::before {
        margin-right: 8px;
        font-size: 0.88rem;
    }

    .filter-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 12px;
        margin-bottom: 20px;
        align-items: end;
    }

    .filter-group {
        display: flex;
        flex-direction: column;
        position: relative;
        min-width: 0;
    }

    .filter-label {
        font-size: 0.72rem;
        color: #495057;
        margin-bottom: 6.4px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.4px;
    }

    .filter-input, .filter-select {
        padding: 9.6px 12.8px;
        border: 1.6px solid #e9ecef;
        border-radius: 6.4px;
        font-size: 0.76rem;
        background: #ffffff;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        color: #495057;
        font-weight: 500;
    }

    .filter-input:focus, .filter-select:focus {
        outline: none;
        border-color: #8B0000;
        box-shadow: 0 0 0 3px rgba(139, 0, 0, 0.1);
        transform: translateY(-1px);
    }

    .filter-input::placeholder {
        color: #adb5bd;
        font-weight: 400;
    }

    .filter-actions {
        display: flex;
        gap: 12px;
        flex-wrap: nowrap;
        align-items: center;
        justify-content: flex-end;
        margin-top: 16px;
        flex: 0 0 auto;
    }

    .filter-btn, .clear-btn, .print-btn, .old-report-btn, .archive-btn {
        padding: 12px 19px;
        border: none;
        border-radius: 6.4px;
        font-size: 0.72rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        text-transform: uppercase;
        letter-spacing: 0.4px;
        position: relative;
        overflow: hidden;
        white-space: nowrap;
        min-width: 120px;
        width: auto;
    }

    .filter-btn {
        background: linear-gradient(135deg, #8B0000, #6d0000);
        color: #fff;
        box-shadow: 0 4px 15px rgba(139, 0, 0, 0.3);
    }

    .filter-btn:hover {
        background: linear-gradient(135deg, #6d0000, #5a0000);
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(139, 0, 0, 0.4);
    }

    .clear-btn {
        background: linear-gradient(135deg, #6c757d, #5a6268);
        color: #fff;
        box-shadow: 0 4px 15px rgba(108, 117, 125, 0.3);
    }

    .clear-btn:hover {
        background: linear-gradient(135deg, #5a6268, #495057);
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(108, 117, 125, 0.4);
    }


    .print-btn {
        background: linear-gradient(135deg, #2ecc71, #27ae60);
        color: #fff;
        box-shadow: 0 4px 15px rgba(46, 204, 113, 0.3);
    }

    .print-btn:hover {
        background: linear-gradient(135deg, #27ae60, #229954);
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(46, 204, 113, 0.4);
    }

    .old-report-btn {
        background: linear-gradient(135deg, #f39c12, #e67e22);
        color: #fff;
        box-shadow: 0 4px 15px rgba(243, 156, 18, 0.3);
    }

    .old-report-btn:hover {
        background: linear-gradient(135deg, #e67e22, #d35400);
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(243, 156, 18, 0.4);
    }

    .archive-btn {
        background: linear-gradient(135deg, #9b59b6, #8e44ad);
        color: #fff;
        box-shadow: 0 4px 15px rgba(155, 89, 182, 0.3);
        text-decoration: none;
        display: inline-block;
    }

    .archive-btn:hover {
        background: linear-gradient(135deg, #8e44ad, #7d3c98);
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(155, 89, 182, 0.4);
        text-decoration: none;
        color: #fff;
    }

    .search-section {
        display: flex;
        gap: 16px;
        align-items: center;
        margin-top: 16px;
        padding-top: 16px;
        border-top: 1.6px solid #f1f3f4;
    }

    .search-group {
        flex: 1;
        min-width: 0;
    }

    .search-input {
        width: 100%;
        padding: 9.6px 12.8px;
        border: 1.6px solid #e9ecef;
        border-radius: 6.4px;
        font-size: 0.76rem;
        background: #ffffff;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        color: #495057;
        font-weight: 500;
        box-sizing: border-box;
    }

    .search-input:focus {
        outline: none;
        border-color: #8B0000;
        box-shadow: 0 0 0 2.4px rgba(139, 0, 0, 0.1);
        transform: translateY(-1px);
    }

    .search-actions {
        display: flex;
        gap: 12px;
        align-items: center;
        flex-shrink: 0;
    }

    /* Search button alignment */
    .search-actions .print-btn,
    .search-actions .old-report-btn {
        padding: 9.6px 19px;
        height: auto;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    @media (max-width: 768px) {
        .filter-grid {
            flex-direction: column;
            align-items: stretch;
        }
        
        .filter-actions {
            flex-direction: row;
            align-items: stretch;
            justify-content: center;
        }
        
        .search-section {
            flex-direction: column;
            align-items: stretch;
        }
        
        .search-group {
            min-width: auto;
        }
        
        .search-actions {
            flex-direction: row;
            justify-content: center;
            margin-top: 15px;
        }
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
            z-index: 1;
        }

        /* Filter Section */
        .filter-section {
            padding: 16px 12px;
            margin-bottom: 16px;
            border-radius: 8px;
        }

        .filter-header {
            margin-bottom: 16px;
            padding-bottom: 10px;
        }

        .filter-title {
            font-size: 0.9rem;
        }

        .filter-grid {
            grid-template-columns: 1fr;
            gap: 12px;
            margin-bottom: 16px;
        }

        .filter-group {
            width: 100%;
        }

        .filter-label {
            font-size: 0.7rem;
            margin-bottom: 6px;
        }

        .filter-input,
        .filter-select {
            padding: 10px 12px;
            font-size: 0.85rem;
            border-radius: 6px;
        }

        .filter-actions {
            flex-direction: column;
            gap: 10px;
            margin-top: 12px;
            justify-content: stretch;
        }

        .filter-btn,
        .clear-btn,
        .print-btn,
        .old-report-btn,
        .archive-btn {
            width: 100%;
            padding: 12px;
            font-size: 0.8rem;
            min-width: auto;
        }

        /* Search Section */
        .search-section {
            flex-direction: column;
            gap: 12px;
            align-items: stretch;
            margin-top: 12px;
            padding-top: 12px;
        }

        .search-group {
            width: 100%;
            min-width: auto;
        }

        .search-input {
            width: 100%;
            padding: 10px 12px;
            font-size: 0.85rem;
            border-radius: 6px;
            box-sizing: border-box;
        }

        .search-actions {
            flex-direction: column;
            gap: 10px;
            width: 100%;
            margin-top: 0;
        }

        .search-actions form {
            width: 100%;
        }

        .search-actions .print-btn,
        .search-actions .old-report-btn {
            width: 100%;
            padding: 12px;
            font-size: 0.8rem;
        }

        /* Table Container - Card Layout on Mobile */
        .teaching-load-table-container {
            border-radius: 8px;
            overflow: visible;
            background: transparent;
            box-shadow: none;
            max-height: none;
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
            cursor: pointer;
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
    }

    /* Remarks color coding */
    .teaching-load-table .remarks-on-leave {
        color: #dc3545 !important;
        font-weight: bold !important;
    }

    .teaching-load-table .remarks-on-pass-slip {
        color: #ff8c00 !important;
        font-weight: bold !important;
    }
    
    /* Modal Styles */
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 1000;
    }
    
    .modal-overlay.active {
        display: flex;
        pointer-events: auto;
    }
    
    .modal-overlay:not(.active) {
        pointer-events: none;
    }
    
    .modal-box {
        background: #fff;
        border-radius: 12px;
        width: 900px;
        height: 600px;
        overflow: hidden;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        position: relative;
        display: flex;
        flex-direction: column;
        pointer-events: auto;
        padding: 0;
        margin: 0;
    }
    
    .modal-header-custom {
        background-color: #8B0000;
        color: #fff;
        font-weight: bold;
        text-align: center;
        padding: 20px;
        font-size: 1.4rem;
        border-top-left-radius: 12px;
        border-top-right-radius: 12px;
        border-bottom-left-radius: 0;
        border-bottom-right-radius: 0;
        position: relative;
        top: 0;
        left: 0;
        right: 0;
        z-index: 10;
        width: 100%;
        box-sizing: border-box;
        margin: 0;
        flex-shrink: 0;
    }
    
    .modal-close {
        position: absolute;
        top: 10px;
        right: 10px;
        background: rgba(255, 255, 255, 0.2);
        border: none;
        color: #fff;
        font-size: 28px;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
        z-index: 10;
        line-height: 1;
    }
    
    .modal-close:hover {
        background: rgba(255, 255, 255, 0.3);
        transform: scale(1.1);
    }
    
    .modal-content {
        padding: 24px;
        flex: 1;
        overflow-y: auto;
        box-sizing: border-box;
        width: 100%;
        margin: 0;
        margin-top: 0;
    }
    
    .modal-section {
        margin-bottom: 24px;
        padding-bottom: 24px;
        border-bottom: 1px solid #e9ecef;
    }
    
    .modal-section:last-child {
        border-bottom: none;
    }
    
    .modal-section-title {
        font-size: 1.1rem;
        font-weight: bold;
        color: #8B0000;
        margin-bottom: 16px;
    }
    
    .modal-info-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 16px;
    }
    
    .modal-info-item {
        display: flex;
        flex-direction: column;
    }
    
    .modal-info-label {
        font-size: 0.85rem;
        color: #666;
        margin-bottom: 4px;
        font-weight: 600;
    }
    
    .modal-info-value {
        font-size: 0.95rem;
        color: #333;
    }
    
    .snapshot-container {
        display: flex;
        flex-direction: row;
        gap: 16px;
        align-items: flex-start;
    }
    
    .snapshot-item {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    
    .snapshot-label {
        font-size: 0.9rem;
        font-weight: 600;
        color: #8B0000;
    }
    
    .snapshot-image {
        width: 100%;
        max-width: 500px;
        border-radius: 8px;
        border: 2px solid #e9ecef;
        cursor: pointer;
        transition: transform 0.2s ease;
    }
    
    .snapshot-image:hover {
        transform: scale(1.02);
        border-color: #8B0000;
    }
    
    .attachment-container {
        display: flex;
        flex-direction: column;
        gap: 16px;
    }
    
    .attachment-item {
        padding: 16px;
        background: #f8f9fa;
        border-radius: 8px;
        border-left: 4px solid #8B0000;
    }
    
    .attachment-header {
        font-size: 1rem;
        font-weight: bold;
        color: #8B0000;
        margin-bottom: 12px;
    }
    
    .attachment-details {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
        margin-bottom: 12px;
    }
    
    .attachment-image {
        width: 100%;
        max-width: 400px;
        border-radius: 8px;
        border: 2px solid #e9ecef;
        cursor: pointer;
        transition: transform 0.2s ease;
    }
    
    .attachment-image:hover {
        transform: scale(1.02);
        border-color: #8B0000;
    }
    
    .no-attachment {
        color: #999;
        font-style: italic;
    }
    
    .spinner-border {
        display: inline-block;
        width: 2rem;
        height: 2rem;
        vertical-align: text-bottom;
        border: 0.25em solid currentColor;
        border-right-color: transparent;
        border-radius: 50%;
        animation: spinner-border 0.75s linear infinite;
    }
    
    @keyframes spinner-border {
        to { transform: rotate(360deg); }
    }
    
    .sr-only {
        position: absolute;
        width: 1px;
        height: 1px;
        padding: 0;
        margin: -1px;
        overflow: hidden;
        clip: rect(0, 0, 0, 0);
        white-space: nowrap;
        border-width: 0;
    }
    
    .image-viewer-modal {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.9);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 2000;
    }
    
    .image-viewer-modal.active {
        display: flex;
    }
    
    .image-viewer-content {
        max-width: 95%;
        max-height: 95vh;
        position: relative;
    }
    
    .image-viewer-content img {
        max-width: 100%;
        max-height: 95vh;
        border-radius: 8px;
    }
    
    .image-viewer-close {
        position: absolute;
        top: -40px;
        right: 0;
        background: rgba(255, 255, 255, 0.2);
        border: none;
        color: #fff;
        font-size: 32px;
        width: 44px;
        height: 44px;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    /* Desktop view only - Modal alignment fixes */
    @media (min-width: 769px) {
        #recordDetailsModal .modal-box {
            overflow: hidden;
            padding: 0;
            margin: 0;
        }
        
        #recordDetailsModal .modal-header-custom {
            width: 100%;
            box-sizing: border-box;
            margin: 0;
            padding: 20px;
            flex-shrink: 0;
            border-top-left-radius: 12px;
            border-top-right-radius: 12px;
            border-bottom-left-radius: 0;
            border-bottom-right-radius: 0;
            position: relative;
            top: 0;
            left: 0;
            right: 0;
        }
        
        #recordDetailsModal .modal-content {
            box-sizing: border-box;
            width: 100%;
            margin: 0;
            padding: 24px 24px 48px 24px;
            margin-top: 0;
        }
        
        /* Fixed-size image containers with centered alignment */
        #recordDetailsModal .snapshot-item {
            display: flex;
            flex-direction: column;
            gap: 8px;
            min-height: 300px;
        }
        
        #recordDetailsModal .snapshot-image {
            width: 100%;
            max-width: 500px;
            height: 300px;
            min-height: 300px;
            object-fit: contain;
            object-position: center;
            border-radius: 8px;
            border: 2px solid #e9ecef;
            cursor: pointer;
            transition: transform 0.2s ease;
            margin: 0 auto;
            display: block;
            background-color: #f8f9fa;
        }
        
        #recordDetailsModal .attachment-image {
            width: 100%;
            max-width: 400px;
            height: 300px;
            min-height: 300px;
            object-fit: contain;
            object-position: center;
            border-radius: 8px;
            border: 2px solid #e9ecef;
            cursor: pointer;
            transition: transform 0.2s ease;
            margin: 0 auto;
            display: block;
            background-color: #f8f9fa;
        }
    }
    
    @media (max-width: 768px) {
        .modal-info-grid {
            grid-template-columns: 1fr;
        }
        
        .attachment-details {
            grid-template-columns: 1fr;
        }
    }
</style>
@endsection

@section('content')
<div class="faculty-header">
    <div class="faculty-title-group">
        <div class="faculty-title">Attendance Records</div>
        <div class="faculty-subtitle"></div>
    </div>
    <div class="faculty-actions-row">
        <!-- Search and Print moved to filter section -->
    </div>
</div>

<!-- Filter Section -->
<div class="filter-section">
    <div class="filter-grid">
        <div class="filter-group">
            <label class="filter-label">Start Date</label>
            <input type="date" class="filter-input" id="startDate">
        </div>
        <div class="filter-group">
            <label class="filter-label">End Date</label>
            <input type="date" class="filter-input" id="endDate">
        </div>
        <div class="filter-group">
            <label class="filter-label">Department</label>
            <select class="filter-select" id="departmentFilter">
                    <option value="">All Departments</option>
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
            <div class="filter-group">
                <label class="filter-label">Instructor</label>
                <select class="filter-select" id="instructorFilter">
                    <option value="">All Instructors</option>
                </select>
            </div>
        <div class="filter-group">
            <label class="filter-label">Course Code</label>
            <select class="filter-select" id="courseCodeFilter">
                <option value="">All Course Codes</option>
            </select>
        </div>
        <div class="filter-group">
            <label class="filter-label">Subject</label>
            <select class="filter-select" id="subjectFilter">
                <option value="">All Subjects</option>
            </select>
        </div>
        <div class="filter-group">
            <label class="filter-label">Day of Week</label>
            <select class="filter-select" id="dayFilter">
                <option value="">All Days</option>
                <option value="Monday">Monday</option>
                <option value="Tuesday">Tuesday</option>
                <option value="Wednesday">Wednesday</option>
                <option value="Thursday">Thursday</option>
                <option value="Friday">Friday</option>
                <option value="Saturday">Saturday</option>
                <option value="Sunday">Sunday</option>
            </select>
        </div>
        <div class="filter-group">
            <label class="filter-label">Room</label>
            <select class="filter-select" id="roomFilter">
                <option value="">All Rooms</option>
            </select>
        </div>
        <div class="filter-group">
            <label class="filter-label">Building</label>
            <select class="filter-select" id="buildingFilter">
                <option value="">All Buildings</option>
            </select>
        </div>
        <div class="filter-group">
            <label class="filter-label">Status</label>
            <select class="filter-select" id="statusFilter">
                <option value="">All Status</option>
                <option value="Present">Present</option>
                <option value="Absent">Absent</option>
                <option value="Late">Late</option>
            </select>
        </div>
            <div class="filter-group">
                <label class="filter-label">Remarks</label>
                <select class="filter-select" id="remarksFilter">
                    <option value="">All Remarks</option>
                    <option value="Present">Present</option>
                    <option value="Absent">Absent</option>
                    <option value="Late">Late</option>
                    <option value="Wrong room">Present (Wrong room)</option>
                    <option value="On Leave">On Leave</option>
                    <option value="With Pass Slip">With Pass Slip</option>
            </select>
        </div>
        <div class="filter-group">
            <button class="filter-btn" onclick="applyFilters()">Apply Filters</button>
        </div>
        <div class="filter-group">
            <button class="clear-btn" onclick="clearFilters()">Clear All</button>
        </div>
    </div>
    
    <div class="search-section">
        <div class="search-group">
            <input type="text" class="search-input" id="searchInput" placeholder="Search by faculty name, course code, subject, room, building...">
        </div>
        <div class="search-actions">
            <form id="printForm" method="GET" action="{{ route('admin.attendance.records.print') }}" target="_blank">
                <input type="hidden" name="startDate" id="printStartDate">
                <input type="hidden" name="endDate" id="printEndDate">
                <input type="hidden" name="department" id="printDepartment">
                    <input type="hidden" name="instructor" id="printInstructor">
                    <input type="hidden" name="courseCode" id="printCourseCode">
                    <input type="hidden" name="subject" id="printSubject">
                    <input type="hidden" name="day" id="printDay">
                    <input type="hidden" name="room" id="printRoom">
                    <input type="hidden" name="building" id="printBuilding">
                    <input type="hidden" name="status" id="printStatus">
                    <input type="hidden" name="remarks" id="printRemarks">
                <input type="hidden" name="search" id="printSearch">
                <button type="submit" class="print-btn">Print Report</button>
            </form>
            <form id="oldReportForm" method="GET" action="{{ route('admin.attendance.sheet.print') }}" target="_blank">
                <input type="hidden" name="startDate" id="sheetStartDate">
                <input type="hidden" name="endDate" id="sheetEndDate">
                <input type="hidden" name="department" id="sheetDepartment">
                <input type="hidden" name="instructor" id="sheetInstructor">
                <input type="hidden" name="course_code" id="sheetCourseCode">
                <input type="hidden" name="subject" id="sheetSubject">
                <input type="hidden" name="day" id="sheetDay">
                <input type="hidden" name="room" id="sheetRoom">
                <input type="hidden" name="building" id="sheetBuilding">
                <input type="hidden" name="status" id="sheetStatus">
                <input type="hidden" name="remarks" id="sheetRemarks">
                <input type="hidden" name="search" id="sheetSearch">
                <button type="submit" class="old-report-btn">OLD report Format</button>
            </form>
            
        </div>
    </div>
</div>

<div class="teaching-load-table-container">
    <table class="teaching-load-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Faculty Name</th>
                <th>Department</th>
                <th>Course code</th>
                <th>Subject</th>
                <th>Class Section</th>
                <th>Day</th>
                <th>Time Schedule</th>
                <th>Time in</th>
                <th>Time out</th>
                <th>Time duration</th>
                <th>Room name</th>
                <th>Building no.</th>
                <th>Status</th>
                <th>Remarks</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($records as $record)
                <tr onclick="viewRecordDetails({{ $record->record_id }})" class="record-row" data-record-id="{{ $record->record_id }}">
                    <td data-label="Date">{{ \Carbon\Carbon::parse($record->record_date)->format('F j, Y') }}</td>
                    <td data-label="Faculty Name">{{ $record->faculty->faculty_fname }} {{ $record->faculty->faculty_lname }}</td>
                    <td data-label="Department">{{ $record->faculty->faculty_department }}</td>
                    <td data-label="Course Code">{{ $record->teachingLoad->teaching_load_course_code }}</td>
                    <td data-label="Subject">{{ $record->teachingLoad->teaching_load_subject }}</td>
                    <td data-label="Class Section">{{ $record->teachingLoad->teaching_load_class_section }}</td>
                    <td data-label="Day">{{ $record->teachingLoad->teaching_load_day_of_week }}</td>
                    <td data-label="Time Schedule">{{ \Carbon\Carbon::parse($record->teachingLoad->teaching_load_time_in)->format('h:i A') }} to {{ \Carbon\Carbon::parse($record->teachingLoad->teaching_load_time_out)->format('h:i A') }}</td>
                    <td data-label="Time In">
                        @if(strtoupper(trim($record->record_remarks)) === 'ON LEAVE' || strtoupper(trim($record->record_remarks)) === 'WITH PASS SLIP' || !$record->record_time_in)
                            <span style="color: #999;">N/A</span>
                        @else
                            {{ \Carbon\Carbon::parse($record->record_time_in)->format('h:i A') }}
                        @endif
                    </td>
                    <td data-label="Time Out">
                        @if(strtoupper(trim($record->record_remarks)) === 'ON LEAVE' || strtoupper(trim($record->record_remarks)) === 'WITH PASS SLIP' || !$record->record_time_out)
                            <span style="color: #999;">N/A</span>
                        @else
                            {{ \Carbon\Carbon::parse($record->record_time_out)->format('h:i A') }}
                        @endif
                    </td>
                    <td data-label="Time Duration">
                        @if(strtoupper(trim($record->record_remarks)) === 'ON LEAVE' || strtoupper(trim($record->record_remarks)) === 'WITH PASS SLIP')
                            <span style="color: #999;">0</span>
                        @elseif($record->time_duration_seconds == 0)
                            <span style="color: #999;">0</span>
                        @else
                            {{ intval($record->time_duration_seconds / 60) }}m {{ $record->time_duration_seconds % 60 }}s
                        @endif
                    </td>
                    <td data-label="Room Name">{{ $record->camera->room->room_name }}</td>
                    <td data-label="Building No.">{{ $record->camera->room->room_building_no }}</td>
                    <td data-label="Status">{{ $record->record_status }}</td>
                    <td data-label="Remarks">
                        @if(strtoupper(trim($record->record_remarks)) === 'ON LEAVE')
                            <span class="remarks-on-leave">{{ $record->record_remarks }}</span>
                        @elseif(strtoupper(trim($record->record_remarks)) === 'WITH PASS SLIP')
                            <span class="remarks-on-pass-slip">{{ $record->record_remarks }}</span>
                        @else
                            {{ $record->record_remarks }}
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="15" style="text-align:center; padding:20px;">No attendance records found</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- Record Details Modal -->
<div id="recordDetailsModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header-custom">
            ATTENDANCE RECORD DETAILS
        </div>
        <div class="modal-content" id="recordDetailsContent">
            <div style="text-align: center; padding: 40px;">
                <div class="spinner-border" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
                <p style="margin-top: 16px; color: #666;">Loading record details...</p>
            </div>
        </div>
    </div>
</div>

<!-- Image Viewer Modal -->
<div id="imageViewerModal" class="image-viewer-modal">
    <div class="image-viewer-content">
        <button class="image-viewer-close" onclick="closeImageViewer()">&times;</button>
        <img id="viewerImage" src="" alt="Image Viewer">
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.29/jspdf.plugin.autotable.min.js"></script>
<script>
// Current filters object
let currentFilters = {};

// Load initial data
document.addEventListener('DOMContentLoaded', async function() {
    // Set current date as default filter
    setCurrentDateFilter();
    
    // Load filter options first
    await loadFilters();
    
    // Then populate filters from URL
    populateFiltersFromURL();
    
    // Suppress global loader for this page
    window.suppressLoader = true;
});

// Set current date as default filter (only if no URL parameters exist)
function setCurrentDateFilter() {
    const urlParams = new URLSearchParams(window.location.search);
    
    // Only set current date if no URL parameters exist (first load)
    if (urlParams.size === 0) {
        const today = new Date();
        const todayString = today.toISOString().split('T')[0]; // Format: YYYY-MM-DD
        
        // Set both start and end date to today
        document.getElementById('startDate').value = todayString;
        document.getElementById('endDate').value = todayString;
        
        // Update current filters with correct parameter names
        currentFilters.startDate = todayString;
        currentFilters.endDate = todayString;
    }
}

// Load filter options
async function loadFilters() {
    try {
        // Load instructors from faculty table
        const instructorsResponse = await fetch('/api/faculty');
        const instructors = await instructorsResponse.json();
        const instructorSelect = document.getElementById('instructorFilter');
        instructors.forEach(instructor => {
            const option = document.createElement('option');
            option.value = instructor.faculty_id;
            option.textContent = `${instructor.faculty_fname} ${instructor.faculty_lname}`;
            instructorSelect.appendChild(option);
        });
        // Keep Department list as predefined (no dynamic overwrite)

        // Load rooms from room table
        const roomsResponse = await fetch('/api/rooms');
        const rooms = await roomsResponse.json();
        const roomSelect = document.getElementById('roomFilter');
        rooms.forEach(room => {
            const option = document.createElement('option');
            option.value = room.room_name;
            option.textContent = room.room_name;
            roomSelect.appendChild(option);
        });

        // Load buildings from room table
        const buildings = [...new Set(rooms.map(room => room.room_building_no))];
        const buildingSelect = document.getElementById('buildingFilter');
        buildings.forEach(building => {
            const option = document.createElement('option');
            option.value = building;
            option.textContent = building;
            buildingSelect.appendChild(option);
        });

        // Load course codes and subjects from teaching loads
        const teachingLoadsResponse = await fetch('/api/teaching-loads');
        if (teachingLoadsResponse.ok) {
            const teachingLoads = await teachingLoadsResponse.json();
            
            // Course codes
            const courseCodes = [...new Set(teachingLoads.map(tl => tl.teaching_load_course_code))];
            const courseCodeSelect = document.getElementById('courseCodeFilter');
            courseCodes.forEach(code => {
                const option = document.createElement('option');
                option.value = code;
                option.textContent = code;
                courseCodeSelect.appendChild(option);
            });

            // Subjects
            const subjects = [...new Set(teachingLoads.map(tl => tl.teaching_load_subject))];
            const subjectSelect = document.getElementById('subjectFilter');
            subjects.forEach(subject => {
                const option = document.createElement('option');
                option.value = subject;
                option.textContent = subject;
                subjectSelect.appendChild(option);
            });
        }

        // Load statuses and remarks from attendance records (available values only)
        try {
            const attendanceResp = await fetch('/api/attendance-records?days=30');
            if (attendanceResp.ok) {
                const attendance = await attendanceResp.json();
                const statuses = [...new Set(attendance.map(r => r.record_status).filter(Boolean))].sort();
                const remarks = [...new Set(attendance.map(r => r.record_remarks).filter(Boolean))].sort();
                
                const statusSelect = document.getElementById('statusFilter');
                if (statusSelect) {
                    while (statusSelect.options.length > 1) statusSelect.remove(1);
                    statuses.forEach(s => {
                        const opt = document.createElement('option');
                        opt.value = s;
                        opt.textContent = s;
                        statusSelect.appendChild(opt);
                    });
                }
                
                const remarksSelect = document.getElementById('remarksFilter');
                if (remarksSelect) {
                    while (remarksSelect.options.length > 1) remarksSelect.remove(1);
                    remarks.forEach(r => {
                        const opt = document.createElement('option');
                        opt.value = r;
                        opt.textContent = r;
                        remarksSelect.appendChild(opt);
                    });
                    // Ensure "Wrong room" option exists (special LIKE filter)
                    if (![...remarksSelect.options].some(o => o.value === 'Wrong room')) {
                        const opt = document.createElement('option');
                        opt.value = 'Wrong room';
                        opt.textContent = 'Wrong room';
                        remarksSelect.appendChild(opt);
                    }
                }
            }
        } catch (e) {
            console.warn('Unable to load dynamic statuses/remarks:', e.message);
        }
        
        return true; // Indicate successful completion
    } catch (error) {
        console.error('Error loading filters:', error);
        return false;
    }
}



// Apply filters
function applyFilters() {
    // Get current filter values
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;
    const department = document.getElementById('departmentFilter').value;
    const instructor = document.getElementById('instructorFilter').value;
    const courseCode = document.getElementById('courseCodeFilter').value;
    const subject = document.getElementById('subjectFilter').value;
    const day = document.getElementById('dayFilter').value;
    const room = document.getElementById('roomFilter').value;
    const building = document.getElementById('buildingFilter').value;
    const status = document.getElementById('statusFilter').value;
    const remarks = document.getElementById('remarksFilter').value;
    const search = document.getElementById('searchInput').value;
    
    // Build URL with parameters
    const url = new URL(window.location);
    
    // Clear all existing parameters first
    url.search = '';
    
    // Add parameters only if they have values
    if (startDate) url.searchParams.set('startDate', startDate);
    if (endDate) url.searchParams.set('endDate', endDate);
    if (department) url.searchParams.set('department', department);
    if (instructor) url.searchParams.set('instructor', instructor);
    if (courseCode) url.searchParams.set('course_code', courseCode);
    if (subject) url.searchParams.set('subject', subject);
    if (day) url.searchParams.set('day', day);
    if (room) url.searchParams.set('room', room);
    if (building) url.searchParams.set('building', building);
    if (status) url.searchParams.set('status', status);
    if (remarks) url.searchParams.set('remarks', remarks);
    if (search) url.searchParams.set('search', search);
    
    // Reload page with new parameters
    window.location.href = url.toString();
}

// Clear all filters
function clearFilters() {
    // Reload page without any parameters
    window.location.href = window.location.pathname;
}

// Search logs
function searchLogs() {
    // Get search input value
    const searchValue = document.getElementById('searchInput').value;
    
    // Build URL with current parameters plus search
    const url = new URL(window.location);
    
    if (searchValue) {
        url.searchParams.set('search', searchValue);
    } else {
        url.searchParams.delete('search');
    }
    
    // Reload page with search parameter
    window.location.href = url.toString();
}

// Populate filter fields from URL parameters
function populateFiltersFromURL() {
    const urlParams = new URLSearchParams(window.location.search);
    
    document.getElementById('startDate').value = urlParams.get('startDate') || '';
    document.getElementById('endDate').value = urlParams.get('endDate') || '';
    document.getElementById('departmentFilter').value = urlParams.get('department') || '';
    document.getElementById('instructorFilter').value = urlParams.get('instructor') || '';
    document.getElementById('courseCodeFilter').value = urlParams.get('course_code') || '';
    document.getElementById('subjectFilter').value = urlParams.get('subject') || '';
    document.getElementById('dayFilter').value = urlParams.get('day') || '';
    document.getElementById('roomFilter').value = urlParams.get('room') || '';
    document.getElementById('buildingFilter').value = urlParams.get('building') || '';
    document.getElementById('statusFilter').value = urlParams.get('status') || '';
    document.getElementById('remarksFilter').value = urlParams.get('remarks') || '';
    document.getElementById('searchInput').value = urlParams.get('search') || '';
    
    // Also populate currentFilters from URL parameters
    currentFilters = {
        startDate: urlParams.get('startDate') || '',
        endDate: urlParams.get('endDate') || '',
        department: urlParams.get('department') || '',
        instructor: urlParams.get('instructor') || '',
        course_code: urlParams.get('course_code') || '',
        subject: urlParams.get('subject') || '',
        day: urlParams.get('day') || '',
        room: urlParams.get('room') || '',
        building: urlParams.get('building') || '',
        status: urlParams.get('status') || '',
        remarks: urlParams.get('remarks') || '',
        search: urlParams.get('search') || ''
    };
    
    // Remove empty filters
    Object.keys(currentFilters).forEach(key => {
        if (!currentFilters[key]) {
            delete currentFilters[key];
        }
    });
}

// Search on Enter key
document.querySelector('.search-input').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        searchLogs();
    }
});

    // Sync hidden print inputs with current URL parameters (what's actually displayed)
    function syncPrintInputs() {
        const urlParams = new URLSearchParams(window.location.search);
        
        document.getElementById('printStartDate').value = urlParams.get('startDate') || '';
        document.getElementById('printEndDate').value = urlParams.get('endDate') || '';
        document.getElementById('printDepartment').value = urlParams.get('department') || '';
        document.getElementById('printInstructor').value = urlParams.get('instructor') || '';
        document.getElementById('printCourseCode').value = urlParams.get('course_code') || '';
        document.getElementById('printSubject').value = urlParams.get('subject') || '';
        document.getElementById('printDay').value = urlParams.get('day') || '';
        document.getElementById('printRoom').value = urlParams.get('room') || '';
        document.getElementById('printBuilding').value = urlParams.get('building') || '';
        document.getElementById('printStatus').value = urlParams.get('status') || '';
        document.getElementById('printRemarks').value = urlParams.get('remarks') || '';
        document.getElementById('printSearch').value = urlParams.get('search') || '';
    }

    document.getElementById('printForm').addEventListener('submit', syncPrintInputs);

    function syncSheetInputs() {
        const urlParams = new URLSearchParams(window.location.search);
        document.getElementById('sheetStartDate').value = urlParams.get('startDate') || '';
        document.getElementById('sheetEndDate').value = urlParams.get('endDate') || '';
        document.getElementById('sheetDepartment').value = urlParams.get('department') || '';
        document.getElementById('sheetInstructor').value = urlParams.get('instructor') || '';
        document.getElementById('sheetCourseCode').value = urlParams.get('course_code') || '';
        document.getElementById('sheetSubject').value = urlParams.get('subject') || '';
        document.getElementById('sheetDay').value = urlParams.get('day') || '';
        document.getElementById('sheetRoom').value = urlParams.get('room') || '';
        document.getElementById('sheetBuilding').value = urlParams.get('building') || '';
        document.getElementById('sheetStatus').value = urlParams.get('status') || '';
        document.getElementById('sheetRemarks').value = urlParams.get('remarks') || '';
        document.getElementById('sheetSearch').value = urlParams.get('search') || '';
    }

    document.getElementById('oldReportForm').addEventListener('submit', syncSheetInputs);
    
    // Record Details Modal Functions
    function viewRecordDetails(recordId) {
        const modal = document.getElementById('recordDetailsModal');
        const content = document.getElementById('recordDetailsContent');
        
        // Show loading state
        content.innerHTML = `
            <div style="text-align: center; padding: 40px;">
                <div class="spinner-border" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
                <p style="margin-top: 16px; color: #666;">Loading record details...</p>
            </div>
        `;
        
        modal.classList.add('active');
        
        // Fetch record details
        fetch(`/api/attendance/${recordId}/details`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayRecordDetails(data.data);
                } else {
                    content.innerHTML = '<p style="color: #dc3545; text-align: center; padding: 40px;">Failed to load record details.</p>';
                }
            })
            .catch(error => {
                console.error('Error fetching record details:', error);
                content.innerHTML = '<p style="color: #dc3545; text-align: center; padding: 40px;">Error loading record details.</p>';
            });
    }
    
    function displayRecordDetails(data) {
        try {
            const record = data.record;
            const passSlip = data.pass_slip;
            const leaveSlip = data.leave_slip;
            const officialMatter = data.official_matter;
            
            const content = document.getElementById('recordDetailsContent');
            
            // Check if required data exists
            if (!record) {
                content.innerHTML = '<p style="color: #dc3545; text-align: center; padding: 40px;">Record data not found.</p>';
                return;
            }
            
            // Format date and times
            const recordDate = record.record_date ? 
                new Date(record.record_date).toLocaleDateString('en-US', { 
                    year: 'numeric', 
                    month: 'long', 
                    day: 'numeric' 
                }) : 'N/A';
            
            const timeIn = record.record_time_in ? 
                new Date(record.record_time_in).toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true }) : 
                'N/A';
            const timeOut = record.record_time_out ? 
                new Date(record.record_time_out).toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true }) : 
                'N/A';
            
            const duration = record.time_duration_seconds > 0 ? 
                `${Math.floor(record.time_duration_seconds / 60)}m ${record.time_duration_seconds % 60}s` : 
                '0';
            
            // Get remarks and determine which attachment to show
            const remarks = (record.record_remarks || '').toUpperCase().trim();
            const showPassSlip = remarks === 'WITH PASS SLIP' && passSlip;
            const showLeaveSlip = remarks === 'ON LEAVE' && leaveSlip;
            const showOfficialMatter = officialMatter && officialMatter.om_remarks && remarks === officialMatter.om_remarks.toUpperCase().trim();
            
            // Check if attendance came from recognition (has snapshots)
            const isFromRecognition = !!(record.time_in_snapshot || record.time_out_snapshot);
            
            // Determine what sections to show
            // Priority: If remarks exist, show attachments only. Otherwise, if from recognition, show recognition data.
            const showAttachmentsOnly = (remarks === 'ON LEAVE' || remarks === 'WITH PASS SLIP' || showOfficialMatter);
            const showRecognitionOnly = isFromRecognition && !showAttachmentsOnly;
            
            // Build HTML
            let html = '';
            
            // Only show Time Information and Snapshots if attendance came from recognition AND no attachments
            if (showRecognitionOnly) {
                html += `
                    <!-- Time Information -->
                    <div class="modal-section">
                        <div class="modal-section-title">Time Information</div>
                        <div class="modal-info-grid">
                            <div class="modal-info-item">
                                <div class="modal-info-label">Time In</div>
                                <div class="modal-info-value">${timeIn}</div>
                            </div>
                            <div class="modal-info-item">
                                <div class="modal-info-label">Time Out</div>
                                <div class="modal-info-value">${timeOut}</div>
                            </div>
                            <div class="modal-info-item">
                                <div class="modal-info-label">Duration</div>
                                <div class="modal-info-value">${duration}</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Snapshots -->
                    <div class="modal-section">
                        <div class="modal-section-title">Recognition Snapshots</div>
                        <div class="snapshot-container">
                            <div class="snapshot-item">
                                <div class="snapshot-label">Time In Snapshot</div>
                                ${record.time_in_snapshot ? 
                                    `<img src="/storage/${record.time_in_snapshot}" alt="Time In Snapshot" class="snapshot-image" onclick="viewImage('/storage/${record.time_in_snapshot}')">` : 
                                    '<p class="no-attachment">No snapshot available</p>'}
                            </div>
                            <div class="snapshot-item">
                                <div class="snapshot-label">Time Out Snapshot</div>
                                ${record.time_out_snapshot ? 
                                    `<img src="/storage/${record.time_out_snapshot}" alt="Time Out Snapshot" class="snapshot-image" onclick="viewImage('/storage/${record.time_out_snapshot}')">` : 
                                    '<p class="no-attachment">No snapshot available</p>'}
                            </div>
                        </div>
                    </div>
                `;
            }
            
            // Only show Attachments if remarks is "On Leave" or "With Pass Slip"
            if (showAttachmentsOnly) {
                html += `
                    <!-- Attachments -->
                    <div class="modal-section">
                        <div class="modal-section-title">Attachments</div>
                        <div class="attachment-container">
                            ${showPassSlip ? `
                                <div class="attachment-item">
                                    <div class="attachment-header">Pass Slip</div>
                                    <div class="attachment-details">
                                        <div class="modal-info-item">
                                            <div class="modal-info-label">Date</div>
                                            <div class="modal-info-value">${passSlip.pass_slip_date ? new Date(passSlip.pass_slip_date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) : 'N/A'}</div>
                                        </div>
                                        <div class="modal-info-item">
                                            <div class="modal-info-label">Departure Time</div>
                                            <div class="modal-info-value">${passSlip.pass_slip_departure_time || 'N/A'}</div>
                                        </div>
                                        <div class="modal-info-item">
                                            <div class="modal-info-label">Arrival Time</div>
                                            <div class="modal-info-value">${passSlip.pass_slip_arrival_time || 'N/A'}</div>
                                        </div>
                                        <div class="modal-info-item">
                                            <div class="modal-info-label">Itinerary</div>
                                            <div class="modal-info-value">${passSlip.pass_slip_itinerary || 'N/A'}</div>
                                        </div>
                                        <div class="modal-info-item">
                                            <div class="modal-info-label">Purpose</div>
                                            <div class="modal-info-value">${passSlip.lp_purpose || 'N/A'}</div>
                                        </div>
                                    </div>
                                    ${passSlip.lp_image ? `<img src="${passSlip.lp_image}" alt="Pass Slip" class="attachment-image" onclick="viewImage('${passSlip.lp_image}')">` : '<p class="no-attachment">No image available</p>'}
                                </div>
                            ` : ''}
                            
                            ${showLeaveSlip ? `
                                <div class="attachment-item">
                                    <div class="attachment-header">Leave Slip</div>
                                    <div class="attachment-details">
                                        <div class="modal-info-item">
                                            <div class="modal-info-label">Start Date</div>
                                            <div class="modal-info-value">${leaveSlip.leave_start_date ? new Date(leaveSlip.leave_start_date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) : 'N/A'}</div>
                                        </div>
                                        <div class="modal-info-item">
                                            <div class="modal-info-label">End Date</div>
                                            <div class="modal-info-value">${leaveSlip.leave_end_date ? new Date(leaveSlip.leave_end_date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) : 'N/A'}</div>
                                        </div>
                                        <div class="modal-info-item">
                                            <div class="modal-info-label">Purpose</div>
                                            <div class="modal-info-value">${leaveSlip.lp_purpose || 'N/A'}</div>
                                        </div>
                                    </div>
                                    ${leaveSlip.lp_image ? `<img src="${leaveSlip.lp_image}" alt="Leave Slip" class="attachment-image" onclick="viewImage('${leaveSlip.lp_image}')">` : '<p class="no-attachment">No image available</p>'}
                                </div>
                            ` : ''}
                            
                            ${showOfficialMatter ? `
                                <div class="attachment-item">
                                    <div class="attachment-header">Official Matter</div>
                                    <div class="attachment-details">
                                        <div class="modal-info-item">
                                            <div class="modal-info-label">Start Date</div>
                                            <div class="modal-info-value">${officialMatter.om_start_date ? new Date(officialMatter.om_start_date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) : 'N/A'}</div>
                                        </div>
                                        <div class="modal-info-item">
                                            <div class="modal-info-label">End Date</div>
                                            <div class="modal-info-value">${officialMatter.om_end_date ? new Date(officialMatter.om_end_date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) : 'N/A'}</div>
                                        </div>
                                        <div class="modal-info-item">
                                            <div class="modal-info-label">Purpose</div>
                                            <div class="modal-info-value">${officialMatter.om_purpose || 'N/A'}</div>
                                        </div>
                                        <div class="modal-info-item">
                                            <div class="modal-info-label">Remarks</div>
                                            <div class="modal-info-value">${officialMatter.om_remarks || 'N/A'}</div>
                                        </div>
                                    </div>
                                    ${officialMatter.om_attachment ? `<img src="${officialMatter.om_attachment}" alt="Official Matter" class="attachment-image" onclick="viewImage('${officialMatter.om_attachment}')">` : '<p class="no-attachment">No image available</p>'}
                                </div>
                            ` : ''}
                            
                            ${!showPassSlip && !showLeaveSlip && !showOfficialMatter ? '<p class="no-attachment">No attachments available</p>' : ''}
                        </div>
                    </div>
                    `;
            }
            
            // If no content was generated, show a message
            if (!html || html.trim() === '') {
                html = '<p style="color: #666; text-align: center; padding: 40px;">No details available for this record.</p>';
            }
            
            content.innerHTML = html;
        } catch (error) {
            console.error('Error displaying record details:', error);
            const content = document.getElementById('recordDetailsContent');
            content.innerHTML = '<p style="color: #dc3545; text-align: center; padding: 40px;">Error displaying record details. Please try again.</p>';
        }
    }
    
    function closeRecordModal() {
        const modal = document.getElementById('recordDetailsModal');
        if (modal) {
            modal.classList.remove('active');
        }
    }
    
    function viewImage(imageSrc) {
        const viewer = document.getElementById('imageViewerModal');
        const img = document.getElementById('viewerImage');
        if (viewer && img) {
            img.src = imageSrc;
            viewer.classList.add('active');
        }
    }
    
    function closeImageViewer() {
        const viewer = document.getElementById('imageViewerModal');
        if (viewer) {
            viewer.classList.remove('active');
        }
    }
    
    // Initialize modal event listeners once on page load
    document.addEventListener('DOMContentLoaded', function() {
        // Enable click-outside-to-close functionality
        const recordModal = document.getElementById('recordDetailsModal');
        if (recordModal) {
            recordModal.addEventListener('click', function(e) {
                // Close modal when clicking directly on the overlay background (not on modal-box)
                if (e.target === recordModal) {
                    closeRecordModal();
                }
            });
            
            // Prevent clicks inside modal-box from closing the modal
            const modalBox = recordModal.querySelector('.modal-box');
            if (modalBox) {
                modalBox.addEventListener('click', function(e) {
                    e.stopPropagation();
                });
            }
        }
        
        const imageViewer = document.getElementById('imageViewerModal');
        if (imageViewer) {
            imageViewer.addEventListener('click', function(e) {
                // Only close if clicking directly on the overlay, not on image-viewer-content or its children
                if (e.target === imageViewer) {
                    closeImageViewer();
                }
            });
        }
    });
</script>
@endsection
