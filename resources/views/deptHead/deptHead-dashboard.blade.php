@extends('layouts.appdeptHead')

@section('title', 'Department Head Dashboard - Tagoloan Community College')

@section('dashboard-active', 'active')

@section('styles')
<style>
    .dashboard-title {
        font-size: 2.8rem;
        font-weight: bold;
        color: #6d0000;
        margin-bottom: 0.2em;
    }
    .dashboard-subtitle {
        color: #6d0000;
        font-size: 0.88rem;
        margin-bottom: 24px;
    }
    .user-greeting {
        background: linear-gradient(to right, #6d0000, #a00000);
        color: white;
        padding: 20px 28px;
        border-radius: 9.6px;
        margin-bottom: 20px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .greeting-icon {
        font-size: 1.2rem;
        opacity: 0.9;
    }
    .greeting-text {
        font-size: 0.96rem;
        font-weight: 500;
    }
    .greeting-date {
        text-align: right;
    }
    .date-text {
        font-size: 0.96rem;
        font-weight: bold;
    }
    .day-text {
        font-size: 0.72rem;
        opacity: 0.9;
    }
    .user-name {
        font-weight: bold;
        text-shadow: 0 1px 2px rgba(0,0,0,0.3);
    }

    /* === Cards Row === */
    .stats-row {
        display: flex;
        gap: 24px;
        margin-bottom: 32px;
    }
    .stat-card {
        flex: 1;
        background: #fff;
        border-radius: 9.6px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px 24px;
        position: relative;
        overflow: hidden;
    }
    .stat-card::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 9.6px;
        background: #8B0000;
        border-top-left-radius: 9.6px;
        border-bottom-left-radius: 9.6px;
    }
    .stat-content {
        display: flex;
        flex-direction: column;
        z-index: 1;
    }
    .stat-label-main {
        font-size: 0.88rem;
        font-weight: bold;
        color: #8B0000;
        margin-bottom: 2px;
    }
    .stat-label-sub {
        font-size: 0.72rem;
        color: #8B0000;
    }
    .stat-number {
        font-size: 2rem;
        font-weight: bold;
        color: #8B0000;
        z-index: 1;
    }

    /* Mobile Responsive Design - 430px */
    @media (max-width: 430px) {
        /* Optimized top spacing - enough to clear header but not excessive */
        .main-content {
            padding-top: 48px !important;
        }

        .dashboard-title {
            font-size: 1.8rem;
            margin-bottom: 0.2em;
        }
        
        .dashboard-subtitle {
            font-size: 0.8rem;
            margin-bottom: 12px;
        }

        .user-greeting {
            flex-direction: column;
            align-items: flex-start;
            gap: 10px;
            padding: 14px 18px;
            margin-bottom: 12px;
        }

        .greeting-text {
            font-size: 0.85rem;
            width: 100%;
        }

        .greeting-icon {
            font-size: 1rem;
        }

        .greeting-text small {
            font-size: 0.8rem !important;
            display: block;
            margin-top: 4px;
        }

        .greeting-date {
            text-align: left;
            width: 100%;
        }

        .date-text {
            font-size: 0.85rem;
        }

        .day-text {
            font-size: 0.7rem;
        }

        .stats-row {
            flex-direction: column;
            gap: 12px;
            margin-bottom: 16px;
        }

        .stat-card {
            padding: 16px 20px;
            width: 100%;
        }

        .stat-label-main {
            font-size: 0.8rem;
        }

        .stat-label-sub {
            font-size: 0.7rem;
        }

        .stat-number {
            font-size: 1.6rem;
        }
    }
</style>
@endsection

@section('content')

<!-- User Greeting -->
<div class="user-greeting">
    <div class="greeting-text">
        Welcome back, <span class="user-name">{{ Auth::user()->user_fname }} {{ Auth::user()->user_lname }}</span>!
        <br>
        <small style="opacity: 0.9; font-size: 0.9rem;">Ready to manage your department today?</small>
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