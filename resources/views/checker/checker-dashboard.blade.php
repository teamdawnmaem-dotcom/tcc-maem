@extends('layouts.appChecker')

@section('title', 'Checker Dashboard - Tagoloan Community College')

@section('dashboard-active', 'active')

@section('styles')
    <style>
        .dashboard-title {
            font-size: 3.5rem;
            font-weight: bold;
            color: #6d0000;
            margin-bottom: 0.2em;
        }
        .dashboard-subtitle {
            color: #6d0000;
            font-size: 1.1rem;
            margin-bottom: 30px;
        }
        .user-greeting {
            background: linear-gradient(to right, #6d0000, #a00000);
            color: white;
            padding: 25px 35px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .greeting-icon {
            font-size: 1.5rem;
            opacity: 0.9;
        }
        .greeting-text {
            font-size: 1.2rem;
            font-weight: 500;
        }
        .greeting-date {
            text-align: right;
        }
        .date-text {
            font-size: 1.2rem;
            font-weight: bold;
        }
        .day-text {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        .user-name {
            font-weight: bold;
            text-shadow: 0 1px 2px rgba(0,0,0,0.3);
        }

        /* === Cards Row === */
        .stats-row {
            display: flex;
            gap: 30px;
            margin-bottom: 40px;
        }
        .stat-card {
            flex: 1;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 25px 30px;
            position: relative;
            overflow: hidden;
        }
        .stat-card::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 12px;
            background: #8B0000;
            border-top-left-radius: 12px;
            border-bottom-left-radius: 12px;
        }
        .stat-content {
            display: flex;
            flex-direction: column;
            z-index: 1;
        }
        .stat-label-main {
            font-size: 1.1rem;
            font-weight: bold;
            color: #8B0000;
            margin-bottom: 2px;
        }
        .stat-label-sub {
            font-size: 0.9rem;
            color: #8B0000;
        }
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #8B0000;
            z-index: 1;
        }
    </style>
@endsection

@section('content')

    <!-- User Greeting -->
    <div class="user-greeting">
        <div class="greeting-text">
            Welcome back, <span class="user-name">{{ Auth::user()->user_fname }} {{ Auth::user()->user_lname }}</span>!
            <br>
            <small style="opacity: 0.9; font-size: 0.9rem;">Ready to review attendance and cameras today?</small>
        </div>
        <div class="greeting-date">
            <div class="date-text">{{ date('F j Y') }}</div>
            <div class="day-text">{{ date('l') }}</div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="stats-row">
        <div class="stat-card registered">
            <div class="stat-content">
                <div class="stat-label-main">Registered</div>
                <div class="stat-label-sub">Users</div>
            </div>
            <div class="stat-number">{{ $registeredUser }}</div>
        </div>

        <div class="stat-card faculty">
            <div class="stat-content">
                <div class="stat-label-main">Registered</div>
                <div class="stat-label-sub">Faculty</div>
            </div>
            <div class="stat-number">{{ $registeredFaculty }}</div>
        </div>

        <div class="stat-card totalcamera">
            <div class="stat-content">
                <div class="stat-label-main">Registered</div>
                <div class="stat-label-sub">Camera</div>
            </div>
            <div class="stat-number">{{ $totalCameras }}</div>
        </div>
    </div>
@endsection
