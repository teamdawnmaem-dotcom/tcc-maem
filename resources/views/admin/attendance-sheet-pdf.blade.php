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

        .date-info {
            margin-bottom: 15px;
            text-align: left;
            font-size: 10px;
            color: #333;
        }

        .date-info span {
            margin: 0 20px;
            font-weight: bold;
        }

        /* Filters section (match attendance-records-pdf) */
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
            vertical-align: middle;
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
            font-size: 10px;
            line-height: 1.3;
            text-align: center;
        }

        /* Main column widths */
        .time-col {
            width: 30%;
            text-align: left;
        }

        .remarks-col {
            width: 70%;
            text-align: center;
        }

        /* Sub-column widths - 7 columns total */
        .room-col {
            width: 15%;
            text-align: center;
        }

        .name-col {
            width: 15%;
            text-align: center;
        }

        .subject-col {
            width: 20%;
            text-align: center;
        }

        .time-in-col {
            width: 15%;
            text-align: center;
        }

        .late-col {
            width: 10%;
            text-align: center;
        }

        .absent-col {
            width: 10%;
            text-align: center;
        }

        .signature-col {
            width: 15%;
            text-align: center;
        }

        /* Additional styling */
        tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        tbody tr:hover {
            background-color: #f0f8ff;
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

        /* Simple footer */
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

        .footer-right {
            float: right;
        }

        /* Empty rows for manual filling */
        .empty-row {
            height: 30px;
        }

        .empty-row td {
            vertical-align: top;
            padding-top: 8px;
        }
    </style>
    <title>Attendance Sheet</title>
</head>

<body data-generated="{{ now('Asia/Manila')->format('Y-m-d H:i:s') }}">
    <div class="header">
        <div class="header-right">
            <div class="curriculum-year">SY {{ now()->format('Y') }}-{{ (now()->format('Y') + 1) }}</div>
            <div class="page-number">Page <span class="page-counter"></span></div>
        </div>
        <div class="school-name">TAGOLOAN COMMUNITY COLLEGE</div>
        <div class="report-title">ATTENDANCE SHEET</div>
    </div>

    @php
        // Normalize filter inputs and resolve instructor name once for reuse
        $dateFrom = $dateFrom ?? request('startDate');
        $dateTo = $dateTo ?? request('endDate');
        $department = $department ?? request('department');
        $instructor = $instructor ?? request('instructor');
        $courseCode = $courseCode ?? request('course_code');
        $subject = $subject ?? request('subject');
        $day = $day ?? request('day');
        $room = $room ?? request('room');
        $building = $building ?? request('building');
        $status = $status ?? request('status');
        $remarks = $remarks ?? request('remarks');
        $search = $search ?? request('search');
        if (!empty($instructor) && empty($instructorName ?? null) && is_numeric($instructor)) {
            $fac = \App\Models\Faculty::find($instructor);
            if ($fac) {
                $instructorName = $fac->faculty_fname.' '.$fac->faculty_lname;
            }
        }

        // Compute display Date and Day for header using Asia/Manila
        $manilaNow = \Carbon\Carbon::now('Asia/Manila');
        // Date text mirrors filter style when filters exist; otherwise today's date
        if (!empty($dateFrom) && !empty($dateTo)) {
            $displayDateText = \Carbon\Carbon::parse($dateFrom)->format('M j').' - '.\Carbon\Carbon::parse($dateTo)->format('M j, Y');
        } elseif (!empty($dateFrom)) {
            $displayDateText = 'From '.\Carbon\Carbon::parse($dateFrom)->format('M j, Y');
        } elseif (!empty($dateTo)) {
            $displayDateText = 'Until '.\Carbon\Carbon::parse($dateTo)->format('M j, Y');
        } else {
            $displayDateText = $manilaNow->format('F j, Y');
        }

        // Day text: use explicit day filter if set; else derive from single date; else today's day
        if (!empty($day)) {
            $displayDayText = $day;
        } elseif (!empty($dateFrom) && !empty($dateTo) && \Carbon\Carbon::parse($dateFrom)->isSameDay(\Carbon\Carbon::parse($dateTo))) {
            $displayDayText = \Carbon\Carbon::parse($dateFrom)->format('l');
        } elseif (!empty($dateFrom) && empty($dateTo)) {
            $displayDayText = \Carbon\Carbon::parse($dateFrom)->format('l');
        } elseif (empty($dateFrom) && !empty($dateTo)) {
            $displayDayText = \Carbon\Carbon::parse($dateTo)->format('l');
        } else {
            $displayDayText = $manilaNow->format('l');
        }
    @endphp

    

    @php
        $collection = $records instanceof \Illuminate\Support\Collection ? $records : collect($records ?? []);
        // First group by record_date (per-day pages), then within each day by time span
        $byDate = $collection->groupBy(function($rec){
            try {
                return \Carbon\Carbon::parse($rec->record_date)->toDateString();
            } catch (\Exception $e) {
                return optional($rec->record_date) ?: 'unknown-date';
            }
        });
        $iPage = 0;
    @endphp

    @forelse($byDate->sortKeys() as $dateStr => $recordsOnDate)
        @php
            $dateObj = \Carbon\Carbon::parse($dateStr)->timezone('Asia/Manila');
            $pageDateText = $dateObj->format('F j, Y');
            $pageDayText = $dateObj->format('l');
            $bySpan = $recordsOnDate->sortBy(function($rec){
                return optional($rec->teachingLoad)->teaching_load_time_in ?? '';
            })->groupBy(function($rec){
                $in = optional($rec->teachingLoad)->teaching_load_time_in;
                $out = optional($rec->teachingLoad)->teaching_load_time_out;
                try {
                    $spanIn = $in ? \Carbon\Carbon::parse($in)->format('g:i A') : 'N/A';
                    $spanOut = $out ? \Carbon\Carbon::parse($out)->format('g:i A') : 'N/A';
                } catch (\Exception $e) {
                    $spanIn = $in ?: 'N/A';
                    $spanOut = $out ?: 'N/A';
                }
                return $spanIn.' - '.$spanOut;
            });
        @endphp

        @foreach($bySpan as $span => $groupRecords)
        <div style="{{ $iPage > 0 ? 'page-break-before: always;' : '' }}">
            <div class="date-info">
                <span>Date: {{ $pageDateText }}</span>
                <span>Day: {{ $pageDayText }}</span>
            </div>
            <div class="filters-section">
                <div class="filters-title">Filters Applied:</div>
                <div class="filter-item">
                    <strong>Date:</strong>
                    @if(isset($dateFrom) && isset($dateTo) && $dateFrom && $dateTo)
                        {{ \Carbon\Carbon::parse($dateFrom)->format('M j') }} - {{ \Carbon\Carbon::parse($dateTo)->format('M j, Y') }}
                    @elseif(isset($dateFrom) && $dateFrom)
                        From {{ \Carbon\Carbon::parse($dateFrom)->format('M j, Y') }}
                    @elseif(isset($dateTo) && $dateTo)
                        Until {{ \Carbon\Carbon::parse($dateTo)->format('M j, Y') }}
                    @else
                        All dates
                    @endif
                </div>
                @if(isset($department) && $department)
                    <div class="filter-item"><strong>Department:</strong> {{ $department }}</div>
                @endif
                @if(isset($day) && $day)
                    <div class="filter-item"><strong>Day:</strong> {{ $day }}</div>
                @endif
                @if(isset($instructor) && $instructor)
                    <div class="filter-item"><strong>Instructor:</strong> {{ $instructorName ?? $instructor }}</div>
                @endif
                @if(isset($courseCode) && $courseCode)
                    <div class="filter-item"><strong>Course Code:</strong> {{ $courseCode }}</div>
                @endif
                @if(isset($subject) && $subject)
                    <div class="filter-item"><strong>Subject:</strong> {{ $subject }}</div>
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
                        <th class="time-col" colspan="2">TIME SPAN: {{ $span }}</th>
                        <th class="remarks-col" colspan="7">REMARKS</th>
                    </tr>
                    <tr>
                        <th class="room-col">ROOM</th>
                        <th class="name-col">NAME</th>
                        <th class="subject-col">SUBJECT</th>
                        <th class="time-in-col">TIME IN</th>
                        <th class="late-col">LATE</th>
                        <th class="absent-col">ABSENT</th>
                        <th class="late-col">PASS SLIP</th>
                        <th class="late-col">LEAVE</th>
                        <th class="signature-col">SIGNATURE</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($groupRecords as $record)
                        <tr>
                            <td class="room-col">{{ optional(optional($record->camera)->room)->room_name }}</td>
                            <td class="name-col">{{ optional($record->faculty)->faculty_fname }} {{ optional($record->faculty)->faculty_lname }}</td>
                            <td class="subject-col">{{ optional($record->teachingLoad)->teaching_load_subject }}</td>
                            @php
                                $remarksUpper = strtoupper(trim($record->record_remarks ?? ''));
                                $isPassSlip = str_contains($remarksUpper, 'PASS SLIP');
                                $isOnLeave = str_contains($remarksUpper, 'LEAVE');
                            @endphp
                            <td class="time-in-col">{{ $record->record_time_in ? \Carbon\Carbon::parse($record->record_time_in)->format('h:i A') : '' }}</td>
                            <td class="late-col">{{ $remarksUpper === 'LATE' ? '✓' : '' }}</td>
                            <td class="absent-col">{{ (!$isOnLeave && !$isPassSlip) && ($remarksUpper === 'ABSENT') ? '✓' : '' }}</td>
                            <td class="late-col">{{ $isPassSlip ? '✓' : '' }}</td>
                            <td class="late-col">{{ $isOnLeave ? '✓' : '' }}</td>
                            <td class="signature-col"></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="simple-footer">
                <div class="footer-left">Modern Attendance Enhance Monitoring - Attendance Sheet | Generated: {{ now('Asia/Manila')->format('Y-m-d H:i:s') }}</div>
            </div>
        </div>
        @php $iPage++; @endphp
        @endforeach
    @empty
        <div class="date-info">
            <span>Date: {{ $displayDateText }}</span>
            <span>Day: {{ $displayDayText }}</span>
        </div>
        <div class="filters-section">
            <div class="filters-title">Filters Applied:</div>
            <div class="filter-item">
                <strong>Date:</strong>
                @if(isset($dateFrom) && isset($dateTo) && $dateFrom && $dateTo)
                    {{ \Carbon\Carbon::parse($dateFrom)->format('M j') }} - {{ \Carbon\Carbon::parse($dateTo)->format('M j, Y') }}
                @elseif(isset($dateFrom) && $dateFrom)
                    From {{ \Carbon\Carbon::parse($dateFrom)->format('M j, Y') }}
                @elseif(isset($dateTo) && $dateTo)
                    Until {{ \Carbon\Carbon::parse($dateTo)->format('M j, Y') }}
                @else
                    All dates
                @endif
            </div>
            @if(isset($department) && $department)
                <div class="filter-item"><strong>Department:</strong> {{ $department }}</div>
            @endif
            @if(isset($day) && $day)
                <div class="filter-item"><strong>Day:</strong> {{ $day }}</div>
            @endif
            @if(isset($instructor) && $instructor)
                <div class="filter-item"><strong>Instructor:</strong> {{ $instructorName ?? $instructor }}</div>
            @endif
            @if(isset($courseCode) && $courseCode)
                <div class="filter-item"><strong>Course Code:</strong> {{ $courseCode }}</div>
            @endif
            @if(isset($subject) && $subject)
                <div class="filter-item"><strong>Subject:</strong> {{ $subject }}</div>
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
                    <th class="time-col" colspan="2">TIME SPAN</th>
                    <th class="remarks-col" colspan="7">REMARKS</th>
                </tr>
                <tr>
                    <th class="room-col">ROOM</th>
                    <th class="name-col">NAME</th>
                    <th class="subject-col">SUBJECT</th>
                    <th class="time-in-col">TIME IN</th>
                    <th class="late-col">LATE</th>
                    <th class="absent-col">ABSENT</th>
                    <th class="late-col">PASS SLIP</th>
                    <th class="late-col">LEAVE</th>
                    <th class="signature-col">SIGNATURE</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="room-col" colspan="9" style="text-align:center; padding: 10px;">No records found</td>
                </tr>
            </tbody>
        </table>
        <div class="simple-footer">
            <div class="footer-left">Modern Attendance Enhance Monitoring - Attendance Sheet | Generated: {{ now('Asia/Manila')->format('Y-m-d H:i:s') }}</div>
        </div>
    @endforelse

</body>

</html>
