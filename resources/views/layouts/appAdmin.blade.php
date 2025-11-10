<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Tagoloan Community College')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Onest:wght@100..900&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        *,
        *::before,
        *::after {
            font-family: 'Onest', system-ui, -apple-system, 'Segoe UI', 'Arial', sans-serif;
        }

        body {
            font-family: 'Onest', system-ui, -apple-system, 'Segoe UI', 'Arial', sans-serif;
            background: #fff;
            min-height: 100vh;
        }

        .sidebar {
            width: 224px;
            background: linear-gradient(180deg, #8B0000 0%, #6d0000 100%);
            color: #fff;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            overflow-y: auto;
            overflow-x: hidden;
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.15);
            transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            z-index: 1000;
        }

        .logo-container {
            width: 80%;
            height: 18.4vh;
            background: linear-gradient(180deg, #6d0000 0%, #4a0000 100%);
            border-radius: 0 0 100px 100px;
            margin: 0 auto 20px auto;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.25);
            transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            position: sticky;
            top: 0;
            z-index: 1;
            overflow: hidden;
        }

        .logo-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transition: left 0.6s ease;
        }

        .logo-container:hover::before {
            left: 100%;
        }

        .sidebar-logo {
            width: 128px;
            display: block;
            transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            filter: drop-shadow(0 2px 8px rgba(0, 0, 0, 0.3));
        }

        .logo-container:hover .sidebar-logo {
            transform: scale(1.05);
        }

        .logo-container:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.35);
        }

        .nav-menu {
            width: 100%;
            margin-top: 10px;
            flex: 1;
            padding: 0 12px 10px 12px;
        }

        .nav-item,
        .sub-nav-item {
            display: flex;
            align-items: center;
            gap: 12.8px;
            padding: 11px 16px;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            border: none;
            background: none;
            color: inherit;
            width: 95%;
            text-align: left;
            border-radius: 9.6px;
            margin-bottom: 5px;
            position: relative;
            overflow: hidden;
        }

        /* Ensure icon color follows label via currentColor */
        .nav-item svg,
        .sub-nav-item svg {
            display: inline-block;
        }

        .nav-item svg *,
        .sub-nav-item svg * {
            stroke: currentColor !important;
            fill: none;
        }

        .nav-item::before,
        .sub-nav-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transition: left 0.5s ease;
        }

        .nav-item:hover::before,
        .sub-nav-item:hover::before {
            left: 100%;
        }

        .nav-item.active,
        .sub-nav-item.active {
            background: linear-gradient(135deg, #fff2e6 0%, #ffe6d9 100%);
            color: #8B0000;
            box-shadow: 0 4px 15px rgba(255, 242, 230, 0.3);
            transform: translateX(4px);
        }

        .nav-item:hover,
        .sub-nav-item:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            transform: translateX(6px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .nav-item.active:hover,
        .sub-nav-item.active:hover {
            background: linear-gradient(135deg, #fff2e6 0%, #ffe6d9 100%);
            color: #8B0000;
            transform: translateX(4px);
        }

        .nav-icon {
            font-size: 0.96rem;
            width: 19px;
            text-align: center;
            transition: transform 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .nav-item:hover .nav-icon,
        .sub-nav-item:hover .nav-icon {
            transform: scale(1.1);
        }

        .nav-item.has-dropdown {
            justify-content: space-between;
            position: relative;
        }

        .nav-item.has-dropdown::after {
            content: '▼';
            font-size: 0.56rem;
            transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            opacity: 0.8;
        }

        .nav-item.has-dropdown.open::after {
            transform: rotate(180deg);
            opacity: 1;
        }

        .nav-item.has-dropdown:hover::after {
            transform: scale(1.2);
        }

        .nav-item.has-dropdown.open:hover::after {
            transform: rotate(180deg) scale(1.2);
        }

        .sub-nav {
            background: rgba(0, 0, 0, 0.10);
            border-radius: 9.6px;
            margin: 6px 6px;
            padding: 5px 6px;
            display: none;
            flex-direction: column;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            max-height: 0;
            opacity: 0;
            transform: translateY(-10px);
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.06);
        }

        .nav-item.open+.sub-nav {
            display: flex;
            max-height: 240px;
            opacity: 1;
            transform: translateY(0);
        }

        .sub-nav-item {
            font-size: 0.76rem;
            font-weight: 500;
            padding: 8px 13px 8px 29px;
            margin: 3px 0;
            border-radius: 8px;
            position: relative;
        }

        .sub-nav-item::after {
            content: '';
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            width: 3px;
            height: 3px;
            background: currentColor;
            border-radius: 50%;
            opacity: 0.6;
            transition: all 0.3s ease;
        }

        .sub-nav-item:hover::after {
            transform: translateY(-50%) scale(1.5);
            opacity: 1;
        }

        .sidebar .nav-menu {
            flex: 1;
        }

        .sidebar .nav-item:last-child {
            margin-top: auto;
        }

        /* Header */
        .header {
            margin-left: 224px;
            background: #fdf8ee;
            padding: 0 32px;
            height: 72px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1.5px solid #ddd;
        }

        .header-logo-img {
            width: 56px;
            height: 56px;
            margin-right: 14px;
        }

        .header-title-block {
            display: flex;
            flex-direction: column;
        }

        .header-title {
            color: #8B0000;
            font-size: 1.76rem;
            font-weight: bold;
            letter-spacing: 0.8px;
        }

        .header-address {
            color: #a77b5a;
            font-size: 0.8rem;
            margin-top: 2px;
        }

        .header-profile {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .profile-btn {
            background: #8B0000;
            color: #fff;
            border: none;
            border-radius: 20px;
            padding: 10px 16px;
            font-weight: 600;
            font-size: 0.76rem;
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(139, 0, 0, 0.2);
        }

        .profile-btn:hover {
            background: #6d0000;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(139, 0, 0, 0.3);
        }

        .profile-btn .profile-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 29px;
            height: 29px;
        }

        .profile-btn .profile-icon svg {
            width: 100%;
            height: 100%;
        }

        .profile-text {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            line-height: 1.2;
        }

        .profile-title {
            font-size: 0.72rem;
            font-weight: 600;
        }

        .profile-chevron {
            font-size: 0.64rem;
            margin-left: 3px;
            transition: transform 0.3s ease;
        }

        .profile-btn:hover .profile-chevron {
            transform: translateY(1px);
        }

        .dropdown-arrow {
            font-size: 0.9rem;
        }

        .profile-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 6px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            min-width: 144px;
            z-index: 1000;
            display: none;
            margin-top: 4px;
        }

        .profile-dropdown.show {
            display: block;
        }

        .profile-dropdown-item {
            padding: 10px 13px;
            cursor: pointer;
            transition: background-color 0.2s;
            border-bottom: 1px solid #f0f0f0;
            text-decoration: none;
            color: #333;
            display: block;
        }

        .profile-dropdown-item:last-child {
            border-bottom: none;
        }

        .profile-dropdown-item:hover {
            background-color: #f8f9fa;
        }

        .profile-dropdown-item.logout {
            color: #dc3545;
        }

        .profile-btn-container {
            position: relative;
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
            border-radius: 8px;
            width: 400px;
            max-width: 98vw;
            padding: 32px 32px 24px 32px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.22), 0 1.5px 8px rgba(0, 0, 0, 0.12);
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .modal-header {
            font-size: 1.6rem;
            font-weight: bold;
            color: #8B0000;
            text-align: center;
            margin-bottom: 22px;
        }

        .modal-form {
            width: 100%;
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .modal-form-group {
            display: flex;
            flex-direction: column;
            flex: 1;
        }

        .modal-form-group label {
            margin-bottom: 5px;
            font-size: 0.8rem;
            font-weight: bold;
            color: #333;
        }

        .modal-form-group input,
        .modal-form-group select {
            width: 100%;
            padding: 8px 10px;
            font-size: 0.8rem;
            border: 1px solid #bbb;
            border-radius: 4px;
        }

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

        .modal-btn.update {
            background: #7cc6fa;
            color: #fff;
        }

        .modal-btn.update:hover {
            background: #5bb3f5;
            color: #fff;
        }

        .modal-btn.cancel {
            background: #ff3636;
            color: #fff;
        }

        .modal-row {
            display: flex;
            gap: 14px;
            width: 100%;
        }

        .modal-form-group.half {
            flex: 1;
        }

        .modal-img-box:hover {
            border-color: #8B0000;
            background: #fff5f5;
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
            font-size: 0.72rem;
        }

        /* Profile Update Modal Styles */
        .profile-modal .modal-box {
            width: 416px;
            max-width: 95vw;
            padding: 28px 32px 24px 32px;
            border-radius: 12.8px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.25), 0 8px 25px rgba(0, 0, 0, 0.15);
            background: linear-gradient(145deg, #ffffff 0%, #f8f9fa 100%);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .profile-modal .modal-header {
            color: #8B0000;
            margin-bottom: 24px;
            font-size: 1.44rem;
            font-weight: 700;
            letter-spacing: 0.4px;
            text-align: center;
            text-shadow: 0 2px 4px rgba(139, 0, 0, 0.1);
            position: relative;
        }

        .profile-modal .modal-header::after {
            content: '';
            position: absolute;
            bottom: -6px;
            left: 50%;
            transform: translateX(-50%);
            width: 48px;
            height: 2.4px;
            background: linear-gradient(90deg, #8B0000, #6d0000);
            border-radius: 1.6px;
        }

        .profile-modal .modal-form {
            width: 100%;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .profile-modal .form-section {
            background: rgba(139, 0, 0, 0.02);
            border: 1px solid rgba(139, 0, 0, 0.08);
            border-radius: 9.6px;
            padding: 16px;
            margin-bottom: 4px;
        }

        .profile-modal .form-section-title {
            color: #8B0000;
            font-size: 0.88rem;
            font-weight: 600;
            margin-bottom: 12px;
            padding-bottom: 6px;
            border-bottom: 1.6px solid rgba(139, 0, 0, 0.1);
            display: flex;
            align-items: center;
            gap: 6.4px;
        }

        .profile-modal .form-section-title::before {
            content: '';
            width: 3.2px;
            height: 16px;
            background: linear-gradient(180deg, #8B0000, #6d0000);
            border-radius: 1.6px;
        }

        .profile-modal .modal-form-group {
            display: flex;
            flex-direction: column;
            gap: 6.4px;
            margin-bottom: 12px;
        }

        .profile-modal .modal-form-group:last-child {
            margin-bottom: 0;
        }

        .profile-modal .modal-form-group label {
            font-size: 0.76rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 0;
            display: flex;
            align-items: center;
            gap: 4.8px;
        }

        .profile-modal .modal-form-group label::after {
            content: ':';
            color: #8B0000;
            font-weight: bold;
        }

        .profile-modal .modal-form-group input {
            width: 100%;
            padding: 9.6px 12.8px;
            font-size: 0.8rem;
            border: 1.6px solid #e1e5e9;
            border-radius: 6.4px;
            background: #fff;
            transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
        }

        .profile-modal .modal-form-group input:focus {
            outline: none;
            border-color: #8B0000;
            box-shadow: 0 0 0 3px rgba(139, 0, 0, 0.1);
            transform: translateY(-1px);
        }

        .profile-modal .modal-form-group input[readonly] {
            background: #f8f9fa;
            color: #6c757d;
            border-color: #dee2e6;
            cursor: not-allowed;
        }

        .profile-modal .modal-form-group input[readonly]:focus {
            transform: none;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
        }

        .profile-modal .modal-form-group input.error {
            border-color: #dc3545;
            box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.1);
        }

        .profile-modal .modal-form-group input.success {
            border-color: #28a745;
            box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.1);
        }

        .profile-modal .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .profile-modal .form-row .modal-form-group {
            margin-bottom: 0;
        }

        .profile-modal .modal-buttons {
            display: flex;
            gap: 12px;
            width: 100%;
            margin-top: 20px;
            justify-content: center;
        }

        .profile-modal .modal-btn {
            flex: 1;
            padding: 11px 19px;
            font-size: 0.8rem;
            font-weight: 600;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            transition: background-color 0.2s ease;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .profile-modal .modal-btn.update {
            background: #7cc6fa !important;
            color: #fff !important;
            border: none !important;
        }

        .profile-modal .modal-btn.update:hover {
            background: #7cc6fa !important;
            color: #fff !important;
        }

        .profile-modal .modal-btn.cancel {
            background: #6c757d;
            color: #fff;
            border: none;
        }

        .profile-modal .modal-btn.cancel:hover {
            background: #5a6268;
            border: none;
        }

        .profile-modal .feedback-message {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 15px;
            font-size: 0.95rem;
            font-weight: 500;
            text-align: center;
            display: none;
        }

        .profile-modal .feedback-message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .profile-modal .feedback-message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .profile-modal .field-error {
            color: #dc3545;
            font-size: 0.85rem;
            margin-top: 4px;
            display: none;
        }

        .profile-modal .modal-form-group.has-error .field-error {
            display: block;
        }

        .profile-modal .modal-form-group.has-error input {
            border-color: #dc3545;
            box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.1);
        }

        @media (max-width: 600px) {
            .profile-modal .form-row {
                grid-template-columns: 1fr;
            }

            .profile-modal .modal-buttons {
                flex-direction: column;
            }

            .profile-modal .modal-btn {
                max-width: none;
            }
        }

        /* Logout Modal */
        .logout-modal .modal-box {
            width: 336px;
            height: auto !important;
            max-height: none !important;
            min-height: auto !important;
            text-align: center;
            padding: 32px 28px 28px 28px;
            border-radius: 12.8px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3), 0 8px 25px rgba(0, 0, 0, 0.15);
            background: linear-gradient(145deg, #ffffff 0%, #f8f9fa 100%);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .logout-modal .modal-header {
            color: #dc3545;
            margin-bottom: 20px;
            font-size: 1.44rem;
            font-weight: 700;
            letter-spacing: 0.4px;
            text-shadow: 0 2px 4px rgba(220, 53, 69, 0.1);
        }

        .logout-modal .modal-content {
            margin-bottom: 28px;
            font-size: 0.88rem;
            color: #495057;
            line-height: 1.6;
            font-weight: 500;
            padding: 0 8px;
        }

        .logout-modal .modal-buttons {
            display: flex;
            gap: 16px;
            justify-content: center;
            margin-top: 8px;
        }

        .logout-modal .modal-buttons .modal-btn {
            flex: 0 0 auto;
            margin-top: 0;
            padding: 11px 19px;
            font-size: 0.8rem;
            font-weight: 700;
            border-radius: 9.6px;
            transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            border: none;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            min-width: 80px;
            max-width: 112px;
        }

        .logout-modal .modal-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.6s ease;
        }

        .logout-modal .modal-btn:hover::before {
            left: 100%;
        }

        .logout-modal .modal-btn.logout {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: #fff;
            box-shadow: 0 6px 20px rgba(220, 53, 69, 0.4);
            border: 2px solid transparent;
        }

        .logout-modal .modal-btn.logout:hover {
            background: linear-gradient(135deg, #c82333 0%, #bd2130 100%);
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(220, 53, 69, 0.5);
            border: 2px solid rgba(255, 255, 255, 0.2);
        }

        .logout-modal .modal-btn.logout:active {
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.4);
        }

        .logout-modal .modal-btn.cancel {
            background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
            color: #fff;
            box-shadow: 0 6px 20px rgba(108, 117, 125, 0.4);
            border: 2px solid transparent;
        }

        .logout-modal .modal-btn.cancel:hover {
            background: linear-gradient(135deg, #5a6268 0%, #495057 100%);
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(108, 117, 125, 0.5);
            border: 2px solid rgba(255, 255, 255, 0.2);
        }

        .logout-modal .modal-btn.cancel:active {
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(108, 117, 125, 0.4);
        }

        /* Logout Modal Icon */
        .logout-modal .modal-icon {
            width: 51.2px;
            height: 51.2px;
            margin: 0 auto 16px auto;
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 25px rgba(220, 53, 69, 0.3);
        }

        .logout-modal .modal-icon svg {
            width: 25.6px;
            height: 25.6px;
            color: white;
        }

        .main-content {
            margin-left: 224px;
            padding: 32px 32px 0 32px;
        }

        @media (max-width: 900px) {

            .main-content,
            .header {
                padding: 20px 10px 0 10px;
            }
        }

        @media (max-width: 700px) {
            .sidebar {
                width: 48px;
            }

            .main-content,
            .header {
                margin-left: 48px;
            }

            .header-logo-img {
                display: none;
            }

            .header-title {
                font-size: 0.88rem;
            }
        }

        /* Mobile Responsive Design for phones (max-width: 430px) */
        @media (max-width: 430px) {
            /* Sidebar - Hide by default, show on toggle */
            .sidebar {
                width: 224px;
                transform: translateX(-100%);
                transition: transform 0.3s ease;
                z-index: 2000;
            }

            .sidebar.mobile-open {
                transform: translateX(0);
            }

            /* Mobile menu toggle button */
            .mobile-menu-toggle {
                position: relative;
                top: 0;
                left: 0;
                z-index: 100;
                background: #8B0000;
                color: white;
                border: none;
                border-radius: 6px;
                padding: 8px 10px;
                cursor: pointer;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
                display: flex !important;
                align-items: center;
                justify-content: center;
                transition: background 0.3s ease;
                flex-shrink: 0;
                min-width: 36px;
                height: 36px;
            }

            .mobile-menu-toggle:hover {
                background: #6d0000;
            }

            .mobile-menu-toggle:active {
                transform: scale(0.95);
            }

            .mobile-menu-toggle svg {
                width: 20px;
                height: 20px;
            }

            /* Overlay when sidebar is open */
            .sidebar-overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.5);
                z-index: 1999;
            }

            .sidebar-overlay.active {
                display: block;
            }

            /* Main content and header adjustments */
            .main-content,
            .header {
                margin-left: 0;
                padding: 16px 12px;
            }

            /* Header layout for mobile: Menu toggle, Title, Profile button in one row */
            .header {
                height: auto;
                min-height: auto;
                padding: 10px 12px;
                display: flex;
                flex-direction: row;
                align-items: center;
                justify-content: space-between;
                gap: 8px;
            }

            /* Hide title/address on very small screens, show only college name */
            .header > div:nth-child(2) {
                flex: 1;
                min-width: 0;
                overflow: hidden;
            }

            .header-title-block {
                flex: 1;
                min-width: 0;
            }

            /* Hide address on mobile to save space */
            .header-address {
                display: none;
            }

            .header-title {
                font-size: 0.75rem;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }


            .header-profile {
                width: auto;
                flex-shrink: 0;
            }

            .profile-btn {
                padding: 6px 10px;
                font-size: 0.65rem;
                border-radius: 14px;
                white-space: nowrap;
            }

            .profile-btn .profile-icon {
                width: 20px;
                height: 20px;
            }

            .profile-btn .profile-icon svg {
                width: 18px;
                height: 18px;
            }

            .profile-title {
                font-size: 0.6rem;
            }

            .profile-chevron {
                font-size: 0.55rem;
            }

            .logo-container {
                height: 14vh;
                margin-bottom: 16px;
            }

            .sidebar-logo {
                width: 96px;
            }

            .nav-item,
            .sub-nav-item {
                font-size: 0.75rem;
                padding: 10px 14px;
            }

            .nav-icon {
                width: 18px;
            }

            .nav-item svg,
            .sub-nav-item svg {
                width: 18px;
                height: 18px;
            }

            .main-content {
                padding: 16px 12px;
            }

            /* Profile dropdown adjustments */
            .profile-dropdown {
                right: 0;
                left: auto;
                min-width: 160px;
                font-size: 0.75rem;
            }

            /* Modal adjustments for mobile */
            .modal-box {
                width: 95vw;
                max-width: 95vw;
                padding: 24px 20px;
                margin: 20px 10px;
            }

            .profile-modal .modal-box {
                width: 95vw;
                max-width: 95vw;
                padding: 20px 16px;
            }

            .logout-modal .modal-box {
                width: 90vw;
                height: auto !important;
                max-height: none !important;
                min-height: auto !important;
                padding: 24px 20px;
            }

            .modal-header {
                font-size: 1.2rem;
            }

            .profile-modal .modal-header {
                font-size: 1.2rem;
            }

            .logout-modal .modal-header {
                font-size: 1.2rem;
            }
        }

        /* Global loader */
        .loader-overlay {
            position: fixed;
            inset: 0;
            background: rgba(255, 255, 255, 0.8);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 2000;
            flex-direction: column;
            gap: 9.6px;
        }

        .loader-spinner {
            width: 35.2px;
            height: 35.2px;
            border: 3.2px solid #eee;
            border-top-color: #8B0000;
            border-radius: 50%;
            animation: spin 0.9s linear infinite;
        }

        .loader-text {
            color: #8B0000;
            font-weight: bold;
            font-size: 0.76rem;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        @yield('styles')
    </style>
</head>

<body>
    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <div class="sidebar">
        <div class="logo-container">
            <img src="{{ asset('images/tcc-logo.png') }}" alt="TCC Logo" class="sidebar-logo">
        </div>
        <div class="nav-menu">
            <div class="nav-item @yield('dashboard-active')" onclick="window.location.href='{{ route('admin.dashboard') }}'">
                <span class="nav-icon" style="display:inline-flex;align-items:center;justify-content:center;">
                    <svg width="22" height="22" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M3 10.5L11 4l8 6.5V18a1 1 0 0 1-1 1h-4v-4H8v4H4a1 1 0 0 1-1-1V10.5Z" stroke="#fff"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </span> Dashboard
            </div>

            <div class="nav-item @yield('checker-account-active')"
                onclick="window.location.href='{{ route('admin.user.account.management') }}'">
                <span class="nav-icon" style="display:inline-flex;align-items:center;justify-content:center;">
                    <svg width="22" height="22" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M7 7a3 3 0 1 1 6 0 3 3 0 0 1-6 0Zm8 10v-1a4 4 0 0 0-8 0v1" stroke="#fff"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </span> User Accounts
            </div>

            <div class="nav-item has-dropdown @yield('monitoring-active') @if (trim($__env->yieldContent('monitoring-active')) == 'active' ||
                    trim($__env->yieldContent('live-camera-active')) == 'active' ||
                    trim($__env->yieldContent('recognition-logs-active')) == 'active') open @endif"
                onclick="toggleDropdown(this, 'monitoring')">
                <span class="nav-icon" style="display:inline-flex;align-items:center;justify-content:center;">
                    <svg width="22" height="22" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect x="3" y="7" width="16" height="10" rx="2" stroke="#fff"
                            stroke-width="2" />
                        <circle cx="11" cy="12" r="3" stroke="#fff" stroke-width="2" />
                    </svg>
                </span> Monitoring
            </div>
            <div class="sub-nav" id="monitoring-subnav" @if (trim($__env->yieldContent('monitoring-active')) == 'active' ||
                    trim($__env->yieldContent('live-camera-active')) == 'active' ||
                    trim($__env->yieldContent('recognition-logs-active')) == 'active') style="display:flex;" @endif>
                <!-- Cameras - Removed due to missing admin blade file -->
                <!-- Rooms - Removed due to missing admin blade file -->
                <div class="sub-nav-item @yield('live-camera-active')"
                    onclick="window.location.href='{{ route('admin.live.camera.feed') }}'">Camera Feed</div>
                <div class="sub-nav-item @yield('recognition-logs-active')"
                    onclick="window.location.href='{{ route('admin.recognition.logs') }}'">Recognition Logs</div>
            </div>

            <div class="nav-item @yield('reports-active')"
                onclick="window.location.href='{{ route('admin.attendance.records') }}'"> <span class="nav-icon"
                    style="display:inline-flex;align-items:center;justify-content:center;"> <svg width="22"
                        height="22" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect x="3" y="5" width="16" height="14" rx="2" stroke="#fff"
                            stroke-width="2" />
                        <path d="M7 9h8M7 13h8M7 17h8" stroke="#fff" stroke-width="2" />
                    </svg> </span> Reports </div>

            <div class="nav-item @yield('teaching-load-active')"
                onclick="window.location.href='{{ url('/admin/teaching-load-management') }}'">
                <span class="nav-icon" style="display:inline-flex;align-items:center;justify-content:center;">
                    <svg width="22" height="22" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect x="3" y="4" width="16" height="14" rx="2" stroke="#fff" stroke-width="2" />
                        <path d="M7 8h10M7 12h10M7 16h6" stroke="#fff" stroke-width="2" />
                    </svg>
                </span>
                Semester Management
            </div>

            <div class="nav-item @yield('archived-attendance-active')"
                onclick="window.location.href='{{ route('admin.attendance.records.archived') }}'">
                <span class="nav-icon" style="display:inline-flex;align-items:center;justify-content:center;">
                    <svg width="22" height="22" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect x="3" y="5" width="16" height="14" rx="2" stroke="#fff" stroke-width="2" />
                        <path d="M7 9h8M7 13h8M7 17h8" stroke="#fff" stroke-width="2" />
                    </svg>
                </span>
                Archived Records
            </div>

        </div>
    </div>
    <div class="header">
        <!-- Mobile Menu Toggle Button (only visible on mobile) -->
        <button class="mobile-menu-toggle" id="mobileMenuToggle" style="display: none;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M3 12h18M3 6h18M3 18h18" stroke="white" stroke-width="2" stroke-linecap="round"/>
            </svg>
        </button>
        
        <div style="display: flex; align-items: center; flex: 1;">
            <div class="header-title-block">
                <span class="header-title">TAGOLOAN COMMUNITY COLLEGE</span>
                <span class="header-address">M.H del Pilar St. Baluarte, Tagoloan, Misamis Oriental</span>
            </div>
        </div>
        <div class="header-profile">
            <div class="profile-btn-container">
                <button class="profile-btn">
                    <span class="profile-icon">
                        <svg width="36" height="36" viewBox="0 0 24 24" fill="none"
                            xmlns="http://www.w3.org/2000/svg">
                            <circle cx="12" cy="12" r="11" stroke="white" stroke-width="2"
                                fill="none" />
                            <circle cx="12" cy="8" r="3" stroke="white" stroke-width="2"
                                fill="none" />
                            <path d="M5 19c0-3.866 3.134-7 7-7s7 3.134 7 7" stroke="white" stroke-width="2"
                                fill="none" />
                        </svg>
                    </span>
                    <span class="profile-text">
                        <span class="profile-title">{{ auth()->user()->user_fname }}
                            {{ auth()->user()->user_lname }}</span>
                    </span>
                    <span class="profile-chevron">&#9662;</span>
                </button>
                <div class="profile-dropdown" id="profileDropdown">
                    <div class="profile-dropdown-item" onclick="openModal('accountSettingsModal')">Account Settings
                    </div>
                    <a href="#" class="profile-dropdown-item logout"
                        onclick="openModal('logoutModal')">Logout</a>
                </div>
            </div>
        </div>
    </div>
    <div class="main-content">
        @yield('content')
    </div>

    <!-- Global Loader -->
    <div id="globalLoader" class="loader-overlay" aria-hidden="true">
        <div class="loader-spinner"></div>
        <div class="loader-text">Loading...</div>
    </div>

    <!-- Account Settings Modal -->
    <div id="accountSettingsModal" class="modal-overlay profile-modal" style="display:none;">
        <div class="modal-box">
            <div class="modal-header">Update Profile</div>

            <form id="accountSettingsForm" class="modal-form" method="POST"
                action="{{ route('admin.account.update') }}">
                @csrf
                @method('PUT')

                <!-- Feedback area -->
                <div id="accountFeedback" class="feedback-message"></div>

                <!-- Personal Information Section -->
                <div class="form-section">
                    <div class="form-section-title">Personal Information</div>

                    <!-- Account Role -->
                    <div class="modal-form-group">
                        <label for="accountRole">Account Role</label>
                        <input name="user_role" type="text" id="accountRole"
                            value="{{ auth()->user()->user_role }}" readonly>
                    </div>

                    <!-- Name Fields -->
                    <div class="form-row">
                        <div class="modal-form-group">
                            <label for="fname">First Name</label>
                            <input name="user_fname" type="text" id="fname"
                                value="{{ auth()->user()->user_fname }}" placeholder="Enter first name" required>
                            <div class="field-error" id="fname-error"></div>
                        </div>

                        <div class="modal-form-group">
                            <label for="lname">Last Name</label>
                            <input name="user_lname" type="text" id="lname"
                                value="{{ auth()->user()->user_lname }}" placeholder="Enter last name" required>
                            <div class="field-error" id="lname-error"></div>
                        </div>
                    </div>

                    <!-- Username -->
                    <div class="modal-form-group">
                        <label for="username">Username</label>
                        <input name="username" type="text" id="username" value="{{ auth()->user()->username }}"
                            placeholder="Enter username" required>
                        <div class="field-error" id="username-error"></div>
                    </div>
                </div>

                <!-- Security Section -->
                <div class="form-section">
                    <div class="form-section-title">Security Settings</div>

                    <!-- Old Password -->
                    <div class="modal-form-group">
                        <label for="oldPassword">Current Password</label>
                        <input name="current_password" type="password" id="oldPassword"
                            placeholder="Enter current password">
                        <div class="field-error" id="current_password-error"></div>
                    </div>

                    <!-- Password Fields -->
                    <div class="form-row">
                        <div class="modal-form-group">
                            <label for="newPassword">New Password</label>
                            <input name="new_password" type="password" id="newPassword"
                                placeholder="Enter new password">
                            <div class="field-error" id="new_password-error"></div>
                        </div>

                        <div class="modal-form-group">
                            <label for="confirmPassword">Confirm Password</label>
                            <input name="new_password_confirmation" type="password" id="confirmPassword"
                                placeholder="Confirm new password">
                            <div class="field-error" id="new_password_confirmation-error"></div>
                        </div>
                    </div>
                </div>

                <!-- Buttons -->
                <div class="modal-buttons">
                    <button type="submit" class="modal-btn update">Update Profile</button>
                    <button type="button" class="modal-btn cancel"
                        onclick="closeModal('accountSettingsModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>



    <!-- Logout Modal -->
    <div id="logoutModal" class="modal-overlay logout-modal" style="display:none;">
        <div class="modal-box">
            <div class="modal-icon">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                    <polyline points="16,17 21,12 16,7" />
                    <line x1="21" y1="12" x2="9" y2="12" />
                </svg>
            </div>
            <div class="modal-header">LOGOUT</div>
            <div class="modal-content">
                Are you sure you want to logout from your account?<br>

            </div>
            <div class="modal-buttons">
                <button class="modal-btn logout" onclick="logout()">Logout</button>
                <button class="modal-btn cancel" onclick="closeModal('logoutModal')">Cancel</button>
            </div>
        </div>
    </div>

    <script>
        function toggleDropdown(element, subnavId) {
            // Close all other dropdowns
            const allDropdowns = document.querySelectorAll('.nav-item.has-dropdown');
            allDropdowns.forEach(dropdown => {
                if (dropdown !== element) {
                    dropdown.classList.remove('open');
                    const subnav = dropdown.nextElementSibling;
                    if (subnav && subnav.classList.contains('sub-nav')) {
                        subnav.style.display = 'none';
                    }
                }
            });

            // Toggle current dropdown
            element.classList.toggle('open');
            const subnav = document.getElementById(subnavId + '-subnav');
            if (subnav) {
                subnav.style.display = subnav.style.display === 'flex' ? 'none' : 'flex';
            }
        }

        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'flex';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        function logout() {
            // Create and submit logout form
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route('logout') }}';

            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = '{{ csrf_token() }}';

            form.appendChild(csrfToken);
            document.body.appendChild(form);
            form.submit();
        }

        // Mobile menu toggle functionality
        document.addEventListener('DOMContentLoaded', function() {
            const mobileMenuToggle = document.getElementById('mobileMenuToggle');
            const sidebar = document.querySelector('.sidebar');
            const sidebarOverlay = document.getElementById('sidebarOverlay');
            
            // Show/hide mobile menu button based on screen size
            function checkMobileMenu() {
                if (window.innerWidth <= 430) {
                    mobileMenuToggle.style.display = 'flex';
                } else {
                    mobileMenuToggle.style.display = 'none';
                    sidebar.classList.remove('mobile-open');
                    sidebarOverlay.classList.remove('active');
                }
            }
            
            // Toggle sidebar
            if (mobileMenuToggle) {
                mobileMenuToggle.addEventListener('click', function(e) {
                    e.stopPropagation();
                    sidebar.classList.toggle('mobile-open');
                    sidebarOverlay.classList.toggle('active');
                });
            }
            
            // Close sidebar when overlay is clicked
            if (sidebarOverlay) {
                sidebarOverlay.addEventListener('click', function() {
                    sidebar.classList.remove('mobile-open');
                    sidebarOverlay.classList.remove('active');
                });
            }
            
            // Close sidebar when clicking outside on mobile
            document.addEventListener('click', function(e) {
                if (window.innerWidth <= 430 && sidebar.classList.contains('mobile-open')) {
                    if (!sidebar.contains(e.target) && !mobileMenuToggle.contains(e.target)) {
                        sidebar.classList.remove('mobile-open');
                        sidebarOverlay.classList.remove('active');
                    }
                }
            });
            
            // Check on load and resize
            checkMobileMenu();
            window.addEventListener('resize', checkMobileMenu);
            
            // Close sidebar when nav item is clicked on mobile (but not dropdown toggles)
            if (window.innerWidth <= 430) {
                const navItems = document.querySelectorAll('.nav-item, .sub-nav-item');
                navItems.forEach(item => {
                    item.addEventListener('click', function(e) {
                        // Don't close sidebar if clicking a dropdown toggle
                        if (item.classList.contains('has-dropdown')) {
                            return;
                        }
                        
                        // Only close sidebar when clicking actual navigation links
                        if (window.innerWidth <= 430) {
                            setTimeout(() => {
                                sidebar.classList.remove('mobile-open');
                                sidebarOverlay.classList.remove('active');
                            }, 100);
                        }
                    });
                });
            }
        });
        
        // Profile dropdown functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Hook global loader to form submits and navigation
            const loader = document.getElementById('globalLoader');
            let suppressLoader = false;

            function showLoader() {
                if (loader && !suppressLoader && !window.suppressLoader) loader.style.display = 'flex';
            }

            function hideLoader() {
                if (loader) loader.style.display = 'none';
            }
            // Show only when form is valid and not prevented
            document.addEventListener('submit', function(e) {
                const form = e.target;
                const isValid = !form || typeof form.checkValidity !== 'function' ? true : form
                    .checkValidity();
                // Defer to allow preventDefault in handlers
                setTimeout(function() {
                    if (isValid && !e.defaultPrevented) showLoader();
                }, 0);
            }, true);
            // Do not show loader for general navigations; keep only for form submits
            window.addEventListener('beforeunload', function() {});

            const profileBtn = document.querySelector('.profile-btn');
            const profileDropdown = document.getElementById('profileDropdown');

            profileBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                profileDropdown.classList.toggle('show');
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if (!profileBtn.contains(e.target)) {
                    profileDropdown.classList.remove('show');
                }
            });
        });



        // Close modals when clicking outside
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal-overlay')) {
                e.target.style.display = 'none';
            }
        });








        // Enhanced form validation and submission
        document.getElementById('accountSettingsForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const form = e.target;
            const formData = new FormData(form);
            const feedback = document.getElementById('accountFeedback');

            // Clear previous errors and feedback
            clearAllErrors();
            hideFeedback();

            // Show loading state
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            submitBtn.textContent = 'Updating...';
            submitBtn.disabled = true;

            fetch(form.action, {
                    method: "POST",
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(async res => {
                    const data = await res.json();

                    if (!res.ok) {
                        if (data.errors) {
                            displayFieldErrors(data.errors);
                            showFeedback('Please correct the errors below.', 'error');
                        } else {
                            showFeedback(data.message || 'An error occurred. Please try again.', 'error');
                        }
                        throw new Error('Validation failed');
                    }
                    return data;
                })
                .then(data => {
                    showFeedback(data.success || 'Profile updated successfully!', 'success');

                    // Auto-close modal after 2 seconds
                    setTimeout(() => {
                        closeModal('accountSettingsModal');
                        location.reload();
                    }, 2000);
                })
                .catch(err => {
                    console.error('Error:', err);
                    if (!feedback.classList.contains('error')) {
                        showFeedback('An unexpected error occurred. Please try again.', 'error');
                    }
                })
                .finally(() => {
                    // Reset button state
                    submitBtn.textContent = originalText;
                    submitBtn.disabled = false;
                });
        });

        // Helper functions for form validation
        function clearAllErrors() {
            // Clear field errors
            document.querySelectorAll('.field-error').forEach(error => {
                error.textContent = '';
                error.style.display = 'none';
            });

            // Remove error classes
            document.querySelectorAll('.modal-form-group').forEach(group => {
                group.classList.remove('has-error');
            });
        }

        function displayFieldErrors(errors) {
            Object.keys(errors).forEach(fieldName => {
                const field = document.querySelector(`[name="${fieldName}"]`);
                const errorElement = document.getElementById(`${fieldName}-error`);
                const formGroup = field?.closest('.modal-form-group');

                if (field && errorElement && formGroup) {
                    errorElement.textContent = errors[fieldName][0];
                    errorElement.style.display = 'block';
                    formGroup.classList.add('has-error');
                }
            });
        }

        function showFeedback(message, type) {
            const feedback = document.getElementById('accountFeedback');
            feedback.textContent = message;
            feedback.className = `feedback-message ${type}`;
            feedback.style.display = 'block';
        }

        function hideFeedback() {
            const feedback = document.getElementById('accountFeedback');
            feedback.style.display = 'none';
            feedback.className = 'feedback-message';
        }

        // Real-time validation
        document.querySelectorAll('#accountSettingsForm input').forEach(input => {
            input.addEventListener('blur', function() {
                validateField(this);
            });

            input.addEventListener('input', function() {
                if (this.classList.contains('error')) {
                    validateField(this);
                }
            });
        });

        function validateField(field) {
            const formGroup = field.closest('.modal-form-group');
            const errorElement = formGroup.querySelector('.field-error');

            // Clear previous error state
            formGroup.classList.remove('has-error');
            if (errorElement) {
                errorElement.style.display = 'none';
                errorElement.textContent = '';
            }

            // Basic validation
            if (field.hasAttribute('required') && !field.value.trim()) {
                showFieldError(field, 'This field is required.');
                return false;
            }

            // Email validation for username
            if (field.name === 'username' && field.value) {
                const usernameRegex = /^[a-zA-Z0-9_]{3,}$/;
                if (!usernameRegex.test(field.value)) {
                    showFieldError(field,
                        'Username must be at least 3 characters and contain only letters, numbers, and underscores.');
                    return false;
                }
            }

            // Password confirmation
            if (field.name === 'new_password_confirmation') {
                const newPassword = document.getElementById('newPassword');
                if (newPassword.value && field.value !== newPassword.value) {
                    showFieldError(field, 'Passwords do not match.');
                    return false;
                }
            }

            return true;
        }

        function showFieldError(field, message) {
            const formGroup = field.closest('.modal-form-group');
            const errorElement = formGroup.querySelector('.field-error');

            formGroup.classList.add('has-error');
            if (errorElement) {
                errorElement.textContent = message;
                errorElement.style.display = 'block';
            }
        }
    </script>

    <!-- SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- SweetAlert2 Custom Styles -->
    <style>
        .swal2-popup-custom {
            border-radius: 12px !important;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2) !important;
        }

        .swal2-title-custom {
            color: #8B0000 !important;
            font-weight: bold !important;
        }

        .swal2-content-custom {
            color: #333 !important;
        }
    </style>
    <!-- Global SweetAlert2 helpers and confirm handlers -->
    <script>
        (function() {
            window.SwalUtils = {
                error: function(title, text) {
                    if (window.Swal) {
                        Swal.fire({
                            icon: 'error',
                            title: title || 'Error',
                            text: text || '',
                            confirmButtonColor: '#8B0000'
                        });
                    }
                },
                info: function(title, text) {
                    if (window.Swal) {
                        Swal.fire({
                            icon: 'info',
                            title: title || 'Info',
                            text: text || '',
                            confirmButtonColor: '#8B0000'
                        });
                    }
                },
                success: function(title, text) {
                    if (window.Swal) {
                        Swal.fire({
                            icon: 'success',
                            title: title || 'Success',
                            text: text || '',
                            confirmButtonColor: '#8B0000'
                        });
                    }
                },
                confirmDelete: async function(opts) {
                    if (!window.Swal) return {
                        isConfirmed: true
                    };
                    return await Swal.fire({
                        icon: 'warning',
                        title: (opts && opts.title) || 'Are you sure?',
                        text: (opts && opts.text) || 'This action cannot be undone.',
                        showCancelButton: true,
                        confirmButtonText: (opts && opts.confirmText) || 'Delete',
                        cancelButtonText: (opts && opts.cancelText) || 'Cancel',
                        confirmButtonColor: '#ff3636',
                        cancelButtonColor: '#800000'
                    });
                },
                incompleteFields: function() {
                    if (window.Swal) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Incomplete fields',
                            text: 'Please fill out Subject Code, Description, and Department.',
                            confirmButtonColor: '#8B0000'
                        });
                    }
                }
            };
            document.addEventListener('submit', async function(e) {
                const form = e.target;
                if (form && form.dataset && form.dataset.swalConfirm === 'delete') {
                    e.preventDefault();
                    const res = await window.SwalUtils.confirmDelete({});
                    if (res && res.isConfirmed) {
                        form.submit();
                    }
                }
            }, true);
        })();
    </script>

    @yield('scripts')
</body>

</html>
