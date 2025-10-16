<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <style>
        @page {
            size: A4 landscape;
            margin: 0.5in;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            margin: 0;
            padding: 5px;
            counter-reset: page;
            /* Reserve space for fixed header */
            padding-top: 70px;
        }

        .header {
            text-align: center;
            margin-bottom: 8px;
            border-bottom: 1px solid #8B0000;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: #ffffff;
            z-index: 1000;
        }

        .school-name {
            font-size: 16px;
            font-weight: bold;
            color: #8B0000;
            margin-bottom: 2px;
        }

        .report-title {
            font-size: 12px;
            font-weight: bold;
            color: #333;
            margin-bottom: 3px;
        }

        .report-info {
            font-size: 8px;
            color: #555;
            margin-bottom: 1px;
        }

        .header-right {
            position: absolute;
            top: 0;
            right: 0;
            text-align: right;
            font-size: 8px;
            color: #666;
        }

        .curriculum-year {
            font-weight: bold;
            margin-bottom: 2px;
        }

        .page-number {
            font-weight: bold;
        }

        /* Page numbering for header */
        .page-counter::before {
            content: counter(page);
        }


        .filters-section {
            margin-bottom: 8px;
            padding: 4px 6px;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 2px;
        }

        .filters-title {
            font-size: 9px;
            font-weight: bold;
            color: #495057;
            margin-bottom: 2px;
        }

        .filter-item {
            font-size: 7px;
            color: #6c757d;
            margin-bottom: 1px;
            display: inline-block;
            margin-right: 15px;
        }

        .summary-section {
            margin-bottom: 8px;
            padding: 4px 6px;
            background-color: #e8f4fd;
            border: 1px solid #b8daff;
            border-radius: 2px;
        }

        .summary-title {
            font-size: 9px;
            font-weight: bold;
            color: #004085;
            margin-bottom: 3px;
            text-align: center;
        }

        .summary-grid {
            display: table;
            width: 100%;
            margin-bottom: 2px;
        }

        .summary-row {
            display: table-row;
        }

        .summary-cell {
            display: table-cell;
            font-size: 7px;
            color: #495057;
            padding: 1px 4px;
            border-right: 1px solid #b8daff;
            width: 25%;
        }

        .summary-cell:last-child {
            border-right: none;
        }

        .summary-label {
            font-weight: bold;
            color: #004085;
        }

        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 30px;
            background-color: #f8f9fa;
            border-top: 1px solid #dee2e6;
            padding: 5px 10px;
            font-size: 8px;
            color: #6c757d;
        }

        .footer-left {
            float: left;
        }

        .footer-right {
            float: right;
        }


        .confidentiality-notice {
            font-size: 8px;
            color: #dc3545;
            text-align: center;
            margin-top: 10px;
            font-style: italic;
        }

        table {
            width: 100%;
            max-width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin-top: 10px;
            border-spacing: 0;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 4px 3px;
            text-align: center;
            vertical-align: top;
            word-wrap: break-word;
        }

        th {
            background: #f5f5f5;
            font-weight: bold;
            font-size: 8px;
            white-space: nowrap;
            text-align: center;
        }

        td {
            font-size: 7px;
            line-height: 1.2;
            text-align: center;
        }

        /* Prevent row stretching on the last page */
        tbody tr,
        tbody td {
            height: auto;
        }

        /* Optimized column widths for better alignment and spacing */
        .date-col {
            width: 7%;
            text-align: center;
        }

        .name-col {
            width: 13%;
            text-align: left;
            padding-left: 6px;
        }

        .dept-col {
            width: 10%;
            text-align: left;
            padding-left: 4px;
        }

        .course-col {
            width: 8%;
            text-align: center;
        }

        .subject-col {
            width: 12%;
            text-align: left;
            padding-left: 4px;
        }

        .class-section-col {
            width: 8%;
            text-align: center;
        }

        .day-col {
            width: 5%;
            text-align: center;
        }

        .time-schedule {
            width: 12%;
            text-align: center;
        }

        .time-in-col {
            width: 6%;
            text-align: center;
        }

        .time-out-col {
            width: 6%;
            text-align: center;
        }

        .duration-col {
            width: 9%;
            text-align: center;
        }

        .room-col {
            width: 8%;
            text-align: center;
        }

        .building-col {
            width: 7%;
            text-align: center;
        }

        .status-col {
            width: 7%;
            text-align: center;
        }

        .remarks-col {
            width: 8%;
            text-align: left;
            padding-left: 4px;
        }

        .remarks-on-leave {
            color: #dc3545;
            font-weight: bold;
        }

        .remarks-on-pass-slip {
            color: #ff8c00;
            font-weight: bold;
        }

        /* Additional alignment improvements */
        tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        tbody tr:hover {
            background-color: #f0f8ff;
        }

        /* Ensure consistent spacing for specific data types */
        .time-data {
            font-family: 'Courier New', monospace;
            font-size: 6px;
        }

        .status-present {
            color: #28a745;
            font-weight: bold;
        }

        .status-absent {
            color: #dc3545;
            font-weight: bold;
        }

        .status-late {
            color: #ffc107;
            font-weight: bold;
        }

        /* Page counter styles */
        .page-counter::before {
            content: counter(page);
        }

        /* Multi-page layout improvements */
        thead {
            display: table-header-group;
        }

        tfoot {
            display: table-footer-group;
        }

        tbody {
            display: table-row-group;
        }

        tr {
            page-break-inside: avoid;
        }

        /* Page break improvements for DomPDF */
        .summary-section {
            page-break-inside: avoid;
            page-break-after: avoid;
            margin-bottom: 8px;
            padding: 4px 6px;
            background-color: #e8f4fd;
            border: 1px solid #b8daff;
            border-radius: 2px;
            text-align: center;
        }

        .confidentiality-notice {
            page-break-inside: avoid;
            page-break-after: avoid;
            font-size: 8px;
            color: #dc3545;
            text-align: center;
            margin-top: 10px;
            font-style: italic;
        }

        /* Prevent orphaned summary sections */
        .summary-section + .confidentiality-notice {
            page-break-before: avoid;
        }

        /* Better table handling for single records */
        tbody tr {
            page-break-inside: avoid;
        }

        /* Ensure table doesn't break unnecessarily */
        table {
            page-break-inside: auto;
        }

        /* Simple footer that won't interfere with design */
        .simple-footer {
            margin-top: 20px;
            padding: 5px 0;
            font-size: 8px;
            color: #6c757d;
            border-top: 1px solid #dee2e6;
        }

        .footer-left {
            float: left;
        }
    </style>
    <title>Attendance Records</title>
</head>

<body data-generated="{{ $generatedAt->format('Y-m-d H:i:s') }}">
    <div class="header">
        <div class="header-right">
            <div class="curriculum-year">SY {{ $curriculumYear ?? now()->format('Y') }}-{{ (now()->format('Y') + 1) }}</div>
            <div class="page-number">page <span class="page-counter"></span></div>
        </div>
        <div class="school-name">Tagoloan Community College</div>
        <div class="report-title">Attendance Records Report</div>
        <div class="report-info">Generated on: {{ $generatedAt->format('F j, Y \a\t g:i A') }}</div>
        <div class="report-info">Generated by: {{ $generatedBy ?? 'System Administrator' }}</div>
    </div>

    <div class="filters-section">
        <div class="filters-title">Filters Applied:</div>
        <div class="filter-item">
            <strong>Date:</strong> 
            @if(isset($dateFrom) && isset($dateTo))
                {{ \Carbon\Carbon::parse($dateFrom)->format('M j') }} - {{ \Carbon\Carbon::parse($dateTo)->format('M j, Y') }}
            @elseif(isset($dateFrom))
                From {{ \Carbon\Carbon::parse($dateFrom)->format('M j, Y') }}
            @elseif(isset($dateTo))
                Until {{ \Carbon\Carbon::parse($dateTo)->format('M j, Y') }}
            @else
                All dates
            @endif
        </div>
        @if(isset($department) && $department)
            <div class="filter-item"><strong>Department:</strong> {{ $department }}</div>
        @endif
        @if(isset($faculty) && $faculty)
            <div class="filter-item"><strong>Faculty:</strong> {{ $faculty }}</div>
        @endif
        @if(isset($courseCode) && $courseCode)
            <div class="filter-item"><strong>Course Code:</strong> {{ $courseCode }}</div>
        @endif
        @if(isset($subject) && $subject)
            <div class="filter-item"><strong>Subject:</strong> {{ $subject }}</div>
        @endif
        @if(isset($day) && $day)
            <div class="filter-item"><strong>Day:</strong> {{ $day }}</div>
        @endif
        @if(isset($room) && $room)
            <div class="filter-item"><strong>Room:</strong> {{ $room }}</div>
        @endif
        @if(isset($building) && $building)
            <div class="filter-item"><strong>Building:</strong> {{ $building }}</div>
        @endif
        @if(isset($status) && $status)
            <div class="filter-item"><strong>Status:</strong> {{ $status }}</div>
        @endif
        @if(isset($remarks) && $remarks)
            <div class="filter-item"><strong>Remarks:</strong> {{ $remarks }}</div>
        @endif
        @if(isset($search) && $search)
            <div class="filter-item"><strong>Search:</strong> {{ $search }}</div>
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th class="date-col">Date</th>
                <th class="name-col">Faculty Name</th>
                <th class="dept-col">Department</th>
                <th class="course-col">Course code</th>
                <th class="subject-col">Subject</th>
                <th class="class-section-col">Class Section</th>
                <th class="day-col">Day</th>
                <th class="time-schedule">Time Schedule</th>
                <th class="time-in-col">Time in</th>
                <th class="time-out-col">Time out</th>
                <th class="duration-col">Time duration</th>
                <th class="room-col">Room name</th>
                <th class="building-col">Building no.</th>
                <th class="status-col">Status</th>
                <th class="remarks-col">Remarks</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($records as $record)
                <tr>
                    <td class="date-col">{{ \Carbon\Carbon::parse($record->record_date)->format('F j, Y') }}</td>
                    <td class="name-col">{{ $record->faculty->faculty_fname }} {{ $record->faculty->faculty_lname }}
                    </td>
                    <td class="dept-col">{{ $record->faculty->faculty_department }}</td>
                    <td class="course-col">{{ $record->teachingLoad->teaching_load_course_code }}</td>
                    <td class="subject-col">{{ $record->teachingLoad->teaching_load_subject }}</td>
                    <td class="class-section-col">{{ $record->teachingLoad->teaching_load_class_section }}</td>
                    <td class="day-col">{{ $record->teachingLoad->teaching_load_day_of_week }}</td>
                    <td class="time-schedule time-data">
                        {{ \Carbon\Carbon::parse($record->teachingLoad->teaching_load_time_in)->format('h:i A') }} -<br>
                        {{ \Carbon\Carbon::parse($record->teachingLoad->teaching_load_time_out)->format('h:i A') }}
                    </td>
                    <td class="time-in-col time-data">
                        @if (strtoupper(trim($record->record_remarks)) === 'ON LEAVE' ||
                                strtoupper(trim($record->record_remarks)) === 'WITH PASS SLIP' ||
                                !$record->record_time_in)
                            <span style="color: #999;">N/A</span>
                        @else
                            {{ \Carbon\Carbon::parse($record->record_time_in)->format('h:i A') }}
                        @endif
                    </td>
                    <td class="time-out-col time-data">
                        @if (strtoupper(trim($record->record_remarks)) === 'ON LEAVE' ||
                                strtoupper(trim($record->record_remarks)) === 'WITH PASS SLIP')
                            <span style="color: #999;">N/A</span>
                        @elseif($record->record_time_out)
                            {{ \Carbon\Carbon::parse($record->record_time_out)->format('h:i A') }}
                        @else
                            <span style="color: #999;">N/A</span>
                        @endif
                    </td>
                    <td class="duration-col time-data">
                        @if (strtoupper(trim($record->record_remarks)) === 'ON LEAVE' ||
                                strtoupper(trim($record->record_remarks)) === 'WITH PASS SLIP')
                            <span style="color: #999;">0</span>
                        @elseif($record->time_duration_seconds > 0)
                            @php
                                $hours = intval($record->time_duration_seconds / 3600);
                                $minutes = intval(($record->time_duration_seconds % 3600) / 60);
                                $seconds = $record->time_duration_seconds % 60;
                            @endphp
                            @if($hours > 0)
                                {{ $hours }}h {{ $minutes }}m
                            @else
                                {{ $minutes }}m {{ $seconds }}s
                            @endif
                        @else
                            <span style="color: #999;">0</span>
                        @endif
                    </td>
                    <td class="room-col">{{ $record->camera->room->room_name }}</td>
                    <td class="building-col">{{ $record->camera->room->room_building_no }}</td>
                    <td class="status-col">
                        @if(strtoupper($record->record_status) === 'PRESENT')
                            <span class="status-present">{{ $record->record_status }}</span>
                        @elseif(strtoupper($record->record_status) === 'ABSENT')
                            <span class="status-absent">{{ $record->record_status }}</span>
                        @elseif(strtoupper($record->record_status) === 'LATE')
                            <span class="status-late">{{ $record->record_status }}</span>
                        @else
                            {{ $record->record_status }}
                        @endif
                    </td>
                    <td class="remarks-col">
                        @if (strtoupper(trim($record->record_remarks)) === 'ON LEAVE')
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
                    <td colspan="15" style="text-align:center; padding:12px;">No records found</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="summary-section">
        <div class="summary-title">Summary: {{ $records->count() }} records | 
            Present: {{ $records->where('record_remarks', 'Present')->count() }} | 
            Absent: {{ $records->where('record_remarks', 'Absent')->count() }} | 
            Late: {{ $records->where('record_remarks', 'Late')->count() }} | 
            Leave: {{ $records->where('record_remarks', 'On Leave')->count() }} | 
            Pass: {{ $records->where('record_remarks', 'With Pass Slip')->count() }}
        </div>
    </div>

    <div class="confidentiality-notice">
        This report contains confidential attendance information and is intended for authorized personnel only.
        Distribution is restricted to Department Heads, Administrators, and authorized staff members.
    </div>

    <div class="simple-footer">
        <div class="footer-left">Modern Attendance Enhance Monitoring - Attendance Records Report | Generated: {{ $generatedAt->format('Y-m-d H:i:s') }}</div>
    </div>
</body>

</html>
