
@extends('layouts.appChecker')

@section('title', 'Checker Dashboard - Tagoloan Community College')

@section('dashboard-active', 'active')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/checker/checker-dashboard.css') }}">
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
