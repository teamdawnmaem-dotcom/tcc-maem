@extends('layouts.appAdmin')

@section('title', 'Admin Dashboard - Tagoloan Community College')

@section('dashboard-active', 'active')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/admin-dashboard.css') }}">
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
