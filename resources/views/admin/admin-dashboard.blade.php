@extends('layouts.appAdmin')

@section('title', 'Admin Dashboard - Tagoloan Community College')

@section('dashboard-active', 'active')

@section('styles')
<style>
    body {
        background: #f8f8f8;
    }

    /* === User Greeting === */
    .user-greeting {
        background: linear-gradient(to right, #6d0000, #a00000);
        color: #fff;
        padding: 25px 35px;
        border-radius: 12px;
        margin-bottom: 25px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .greeting-text {
        font-size: 1.2rem;
        font-weight: 500;
    }
    .user-name {
        font-weight: bold;
        text-shadow: 0 1px 2px rgba(0,0,0,0.3);
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

    /* === Stats Cards Row === */
    .stats-row {
        display: flex;
        gap: 25px;
        margin-bottom: 30px;
    }
    .stat-card {
        flex: 1;
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.08);
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px 25px;
        position: relative;
        overflow: hidden;
    }
    .stat-card::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 10px;
        background: linear-gradient(to bottom, #6d0000, #a00000);
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
        color: #6d0000;
        margin-bottom: 3px;
    }
    .stat-label-sub {
        font-size: 0.9rem;
        color: #6d0000;
        opacity: 0.9;
    }
    .stat-number {
        font-size: 2.3rem;
        font-weight: bold;
        color: #6d0000;
        z-index: 1;
    }

    /* === Activity Logs === */
    .logs-card {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
        overflow: hidden;
    }
    .logs-card-header {
        background: transparent;
        padding: 16px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .logs-card-header h5 {
        margin: 0;
        font-size: 1.3rem;
        font-weight: bold;
        color: inherit;
    }
    .user-table {
        width: 100%;
        border-collapse: collapse;
    }
    .user-table th {
        background: #8B0000;
        color: #fff;
        padding: 16px 7px;
        font-size: 1.1rem;
        font-weight: bold;
        border: none;
    }
    /* Keep table header visible while scrolling */
    .user-table thead th {
        position: sticky;
        top: 0;
        z-index: 2;
    }
    .user-table td {
        padding: 12px 40px;
        text-align: left;
        font-size: 1rem;
        border: none;
    }
    .user-table tr:nth-child(even) {
        background: #fff;
    }
    .user-table tr:nth-child(odd) {
        background: #fbeeee;
    }
    .user-table tr:hover {
        background: #fff2e6;
    }
      .search-input {
            padding: 8px;
            font-size: 14px;
            border: 1px solid #ccc;
            border-radius: 4px;
            width: 500px;
        }

    /* Make only the table area scroll vertically */
    .user-table-scroll {
        max-height: 47vh;
        overflow-y: auto;
        width: 100%;
    }
    @media (max-width: 992px) {
        .user-table-scroll {
            max-height: 50vh;
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
    <div class="stat-card">
        <div class="stat-content">
            <div class="stat-label-main">Registered</div>
            <div class="stat-label-sub">Users</div>
        </div>
        <div class="stat-number">{{ $registeredUser }}</div>
    </div>

    <div class="stat-card">
        <div class="stat-content">
            <div class="stat-label-main">Registered</div>
            <div class="stat-label-sub">Faculty</div>
        </div>
        <div class="stat-number">{{ $registeredFaculty }}</div>
    </div>

    <div class="stat-card">
        <div class="stat-content">
            <div class="stat-label-main">Registered</div>
            <div class="stat-label-sub">Camera</div>
        </div>
        <div class="stat-number">{{ $totalCameras }}</div>
    </div>
</div>    
  
  

<!-- Activity Logs -->
<div class="logs-card mt-4">
    <div class="logs-card-header">
        <h5>Activity Logs</h5>
        <input type="text" class="search-input" placeholder="Search...">
    </div>
    <div class="user-table-scroll">
    <table class="user-table">
        <thead>
            <tr>
                <th>Activity Log ID</th>
                <th>User</th>
                <th>Action</th>
                <th>Description</th>
                <th>Module</th>
                <th>Timestamp</th>
            </tr>
        </thead>
        <tbody>
            @forelse($logs as $log)
                <tr>
                    <td>{{ $log->logs_id }}</td>
                    <td>{{ $log->user->user_fname }} {{ $log->user->user_lname }}</td>
                    <td><span class="badge bg-info">{{ $log->logs_action }}</span></td>
                    <td>{{ $log->logs_description }}</td>
                    <td>{{ $log->logs_module }}</td>
                    <td>{{ \Carbon\Carbon::parse($log->logs_timestamp)->format('F j, Y - g:i:sa') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align:center; padding:12px; color:#999; font-style:italic;">No activity logs found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    </div>
</div>
@endsection

@section('scripts')
    <script>

        // =========================
        // Responsive Table Search with "No results found"
        // =========================
        document.querySelector('.search-input').addEventListener('input', function() {
            let searchTerm = this.value.toLowerCase();
            let rows = document.querySelectorAll('.user-table tbody tr');
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
            let tbody = document.querySelector('.user-table tbody');
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
            </script>
@endsection
