<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; margin: 0; padding: 10px; }
        .header { text-align:center; margin-bottom: 12px; }
        .title { font-size: 18px; font-weight: bold; color: #8B0000; }
        .subtitle { font-size: 12px; color: #555; margin-top: 4px; }
        table { width: 100%; max-width: 100%; border-collapse: collapse; table-layout: fixed; }
        th, td { border: 1px solid #ddd; padding: 4px 2px; text-align: left; vertical-align: top; word-wrap: break-word; }
        th { background: #f5f5f5; font-weight: bold; font-size: 8px; white-space: nowrap; }
        td { font-size: 7px; line-height: 1.0; }
        .time-schedule { width: 11%; }
        .date-col { width: 6%; }
        .name-col { width: 10%; }
        .dept-col { width: 9%; }
        .course-col { width: 7%; }
        .subject-col { width: 9%; }
        .class-section-col { width: 7%; }
        .day-col { width: 4%; }
        .time-in-col { width: 5%; }
        .time-out-col { width: 5%; }
        .duration-col { width: 7%; }
        .room-col { width: 5%; }
        .building-col { width: 4%; }
        .status-col { width: 4%; }
        .remarks-col { width: 6%; }
        .remarks-on-leave { color: #dc3545; font-weight: bold; }
        .remarks-on-pass-slip { color: #ff8c00; font-weight: bold; }
    </style>
    <title>Attendance Records</title>
    </head>
<body>
    <div class="header">
        <div class="title">Tagoloan Community College</div>
        <div class="subtitle">Attendance Records Report</div>
        <div class="subtitle">Generated: {{ $generatedAt->format('Y-m-d H:i') }}</div>
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
                    <td class="name-col">{{ $record->faculty->faculty_fname }} {{ $record->faculty->faculty_lname }}</td>
                    <td class="dept-col">{{ $record->faculty->faculty_department }}</td>
                    <td class="course-col">{{ $record->teachingLoad->teaching_load_course_code }}</td>
                    <td class="subject-col">{{ $record->teachingLoad->teaching_load_subject }}</td>
                    <td class="class-section-col">{{ $record->teachingLoad->teaching_load_class_section }}</td>
                    <td class="day-col">{{ $record->teachingLoad->teaching_load_day_of_week }}</td>
                    <td class="time-schedule">{{ \Carbon\Carbon::parse($record->teachingLoad->teaching_load_time_in)->format('h:i A') }} to {{ \Carbon\Carbon::parse($record->teachingLoad->teaching_load_time_out)->format('h:i A') }}</td>
                    <td class="time-in-col">
                        @if(strtoupper(trim($record->record_remarks)) === 'ON LEAVE' || strtoupper(trim($record->record_remarks)) === 'WITH PASS SLIP' || !$record->record_time_in)
                            N/A
                        @else
                            {{ \Carbon\Carbon::parse($record->record_time_in)->format('h:i A') }}
                        @endif
                    </td>
                    <td class="time-out-col">
                        @if(strtoupper(trim($record->record_remarks)) === 'ON LEAVE' || strtoupper(trim($record->record_remarks)) === 'WITH PASS SLIP')
                            N/A
                        @elseif($record->record_time_out)
                            {{ \Carbon\Carbon::parse($record->record_time_out)->format('h:i A') }}
                        @else
                            N/A
                        @endif
                    </td>
                    <td class="duration-col">
                        @if(strtoupper(trim($record->record_remarks)) === 'ON LEAVE' || strtoupper(trim($record->record_remarks)) === 'WITH PASS SLIP')
                            0
                        @elseif($record->time_duration_seconds > 0)
                            {{ intval($record->time_duration_seconds / 60) }}m {{ $record->time_duration_seconds % 60 }}s
                        @else
                            0
                        @endif
                    </td>
                    <td class="room-col">{{ $record->camera->room->room_name }}</td>
                    <td class="building-col">{{ $record->camera->room->room_building_no }}</td>
                    <td class="status-col">{{ $record->record_status }}</td>
                    <td class="remarks-col">
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
                <tr><td colspan="15" style="text-align:center; padding:12px;">No records found</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>

