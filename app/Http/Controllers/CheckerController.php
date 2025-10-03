<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AttendanceRecord;
use App\Models\RecognitionLog;
class CheckerController extends Controller
{
  
    
    public function updateAccount(Request $request)
    {
    $user = auth()->user();

    $request->validate([
        'user_fname'               => 'required|string|max:255',
        'user_lname'               => 'required|string|max:255',
        'username'                 => 'required|string|max:255|unique:tbl_user,username,' . $user->user_id . ',user_id',
        'current_password'         => 'required|string',
        'new_password'             => 'nullable|string|min:8|confirmed',
    ]);

    if (!\Hash::check($request->current_password, $user->user_password)) {
        return response()->json(['errors' => ['current_password' => ['Old password is incorrect']]], 422);
    }

    $user->update([
        'user_fname'    => $request->user_fname,
        'user_lname'    => $request->user_lname,
        'username'      => $request->username,
        'user_password' => $request->filled('new_password')
                            ? \Hash::make($request->new_password)
                            : $user->user_password,
    ]);

    return response()->json(['success' => 'Account updated successfully!']);
    }

    
public function attendanceRecords(Request $request)
{
    $query = AttendanceRecord::with(['faculty', 'teachingLoad', 'camera']);

    // Date filters
    if ($request->startDate) {
        $query->whereDate('record_date', '>=', $request->startDate);
    }
    if ($request->endDate) {
        $query->whereDate('record_date', '<=', $request->endDate);
    }
    
    // If no date filters are provided and no other filters, default to current date (real-time attendance)
    if (!$request->startDate && !$request->endDate && !$request->department && !$request->instructor && 
        !$request->course_code && !$request->subject && !$request->day && !$request->room && 
        !$request->building && !$request->status && !$request->remarks && !$request->search) {
        $query->whereDate('record_date', now()->toDateString());
    }
    
    // Department filter
    if ($request->department) {
        $query->whereHas('faculty', function($q) use ($request) {
            $q->where('faculty_department', $request->department);
        });
    }
    
    // Instructor filter
    if ($request->instructor) {
        $query->where('faculty_id', $request->instructor);
    }
    
    // Course code filter
    if ($request->course_code) {
        $query->whereHas('teachingLoad', function($q) use ($request) {
            $q->where('teaching_load_course_code', $request->course_code);
        });
    }
    
    // Subject filter
    if ($request->subject) {
        $query->whereHas('teachingLoad', function($q) use ($request) {
            $q->where('teaching_load_subject', $request->subject);
        });
    }
    
    // Day of week filter
    if ($request->day) {
        $query->whereHas('teachingLoad', function($q) use ($request) {
            $q->where('teaching_load_day_of_week', $request->day);
        });
    }
    
    // Room filter
    if ($request->room) {
        $query->whereHas('camera.room', function($q) use ($request) {
            $q->where('room_name', $request->room);
        });
    }
    
    // Building filter
    if ($request->building) {
        $query->whereHas('camera.room', function($q) use ($request) {
            $q->where('room_building_no', $request->building);
        });
    }
    
    // Status filter
    if ($request->status) {
        $query->where('record_status', $request->status);
    }
    
    // Remarks filter
    if ($request->remarks) {
        $query->where('record_remarks', $request->remarks);
    }
    
    // Search filter
    if ($request->search) {
        $searchTerm = $request->search;
        $query->where(function($q) use ($searchTerm) {
            // Search in faculty name and department
            $q->whereHas('faculty', function($facultyQuery) use ($searchTerm) {
                $facultyQuery->where('faculty_fname', 'like', "%{$searchTerm}%")
                           ->orWhere('faculty_lname', 'like', "%{$searchTerm}%")
                           ->orWhere('faculty_department', 'like', "%{$searchTerm}%");
            })
            // Search in teaching load (course code and subject)
            ->orWhereHas('teachingLoad', function($teachingQuery) use ($searchTerm) {
                $teachingQuery->where('teaching_load_course_code', 'like', "%{$searchTerm}%")
                             ->orWhere('teaching_load_subject', 'like', "%{$searchTerm}%");
            })
            // Search in camera/room information
            ->orWhereHas('camera.room', function($roomQuery) use ($searchTerm) {
                $roomQuery->where('room_name', 'like', "%{$searchTerm}%")
                         ->orWhere('room_building_no', 'like', "%{$searchTerm}%");
            })
            // Search in attendance record fields
            ->orWhere('record_status', 'like', "%{$searchTerm}%")
            ->orWhere('record_remarks', 'like', "%{$searchTerm}%")
            ->orWhere('record_date', 'like', "%{$searchTerm}%")
            ->orWhere('record_id', 'like', "%{$searchTerm}%");
        });
    }

    $records = $query->orderBy('record_date', 'desc')->get();

    // Return JSON for AJAX requests
    if ($request->ajax() || $request->wantsJson()) {
        return response()->json([
            'success' => true,
            'data' => $records
        ]);
    }

    // Return view for regular requests
    return view('checker.attendance-records', compact('records'));
}

public function attendanceRecordsPrint(Request $request)
{
    $query = AttendanceRecord::with(['faculty', 'teachingLoad', 'camera']);

    // Date filters
    if ($request->startDate) {
        $query->whereDate('record_date', '>=', $request->startDate);
    }
    if ($request->endDate) {
        $query->whereDate('record_date', '<=', $request->endDate);
    }
    
    // If no date filters are provided and no other filters, default to current date (real-time attendance)
    if (!$request->startDate && !$request->endDate && !$request->department && !$request->instructor && 
        !$request->courseCode && !$request->subject && !$request->day && !$request->room && 
        !$request->building && !$request->status && !$request->remarks && !$request->search) {
        $query->whereDate('record_date', now()->toDateString());
    }
    
    // Department filter
    if ($request->department) {
        $query->whereHas('faculty', function($q) use ($request) {
            $q->where('faculty_department', $request->department);
        });
    }
    
    // Instructor filter
    if ($request->instructor) {
        $query->where('faculty_id', $request->instructor);
    }
    
    // Course code filter
    if ($request->courseCode) {
        $query->whereHas('teachingLoad', function($q) use ($request) {
            $q->where('teaching_load_course_code', $request->courseCode);
        });
    }
    
    // Subject filter
    if ($request->subject) {
        $query->whereHas('teachingLoad', function($q) use ($request) {
            $q->where('teaching_load_subject', $request->subject);
        });
    }
    
    // Day of week filter
    if ($request->day) {
        $query->whereHas('teachingLoad', function($q) use ($request) {
            $q->where('teaching_load_day_of_week', $request->day);
        });
    }
    
    // Room filter
    if ($request->room) {
        $query->whereHas('camera.room', function($q) use ($request) {
            $q->where('room_name', $request->room);
        });
    }
    
    // Building filter
    if ($request->building) {
        $query->whereHas('camera.room', function($q) use ($request) {
            $q->where('room_building_no', $request->building);
        });
    }
    
    // Status filter
    if ($request->status) {
        $query->where('record_status', $request->status);
    }
    
    // Remarks filter
    if ($request->remarks) {
        $query->where('record_remarks', $request->remarks);
    }
    
    // Search filter
    if ($request->search) {
        $searchTerm = $request->search;
        $query->where(function($q) use ($searchTerm) {
            // Search in faculty name and department
            $q->whereHas('faculty', function($facultyQuery) use ($searchTerm) {
                $facultyQuery->where('faculty_fname', 'like', "%{$searchTerm}%")
                           ->orWhere('faculty_lname', 'like', "%{$searchTerm}%")
                           ->orWhere('faculty_department', 'like', "%{$searchTerm}%");
            })
            // Search in teaching load (course code and subject)
            ->orWhereHas('teachingLoad', function($teachingQuery) use ($searchTerm) {
                $teachingQuery->where('teaching_load_course_code', 'like', "%{$searchTerm}%")
                             ->orWhere('teaching_load_subject', 'like', "%{$searchTerm}%");
            })
            // Search in camera/room information
            ->orWhereHas('camera.room', function($roomQuery) use ($searchTerm) {
                $roomQuery->where('room_name', 'like', "%{$searchTerm}%")
                         ->orWhere('room_building_no', 'like', "%{$searchTerm}%");
            })
            // Search in attendance record fields
            ->orWhere('record_status', 'like', "%{$searchTerm}%")
            ->orWhere('record_remarks', 'like', "%{$searchTerm}%")
            ->orWhere('record_date', 'like', "%{$searchTerm}%")
            ->orWhere('record_id', 'like', "%{$searchTerm}%");
        });
    }

    $records = $query->orderBy('record_date', 'desc')->get();

    $pdf = \PDF::loadView('checker.attendance-records-pdf', [
        'records' => $records,
        'generatedAt' => now('Asia/Manila')
    ])->setPaper('a4', 'portrait');

    return $pdf->download('attendance-records.pdf');
}

public function recognitionLogs(Request $request)
{
    $query = RecognitionLog::query();

    // Filters
    if ($request->start_date) {
        $query->whereDate('recognition_time', '>=', $request->start_date);
    }
    if ($request->end_date) {
        $query->whereDate('recognition_time', '<=', $request->end_date);
    }
    if ($request->status) {
        $query->where('status', $request->status);
    }
    if ($request->faculty_id) {
        $query->where('faculty_id', $request->faculty_id);
    }
    if ($request->room_name) {
        $query->where('room_name', $request->room_name);
    }
    if ($request->building_no) {
        $query->where('building_no', $request->building_no);
    }
    if ($request->camera_id) {
        $query->where('camera_id', $request->camera_id);
    }
    if ($request->distance_range) {
        $range = explode('-', $request->distance_range);
        if (count($range) === 2) {
            $query->whereBetween('distance', [$range[0], $range[1]]);
        }
    }
    if ($request->search) {
        $searchTerm = $request->search;
        $query->where(function($q) use ($searchTerm) {
            $q->where('faculty_name', 'like', "%{$searchTerm}%")
              ->orWhere('room_name', 'like', "%{$searchTerm}%")
              ->orWhere('camera_name', 'like', "%{$searchTerm}%")
              ->orWhere('building_no', 'like', "%{$searchTerm}%")
              ->orWhere('status', 'like', "%{$searchTerm}%")
              ->orWhere('recognition_time', 'like', "%{$searchTerm}%")
              // Search in readable date format
              ->orWhereRaw("DATE_FORMAT(recognition_time, '%M %d, %Y - %h:%i:%s%p') LIKE ?", ["%{$searchTerm}%"])
              ->orWhereRaw("DATE_FORMAT(recognition_time, '%M %d, %Y') LIKE ?", ["%{$searchTerm}%"])
              ->orWhereRaw("DATE_FORMAT(recognition_time, '%h:%i:%s%p') LIKE ?", ["%{$searchTerm}%"])
              ->orWhereRaw("DATE_FORMAT(recognition_time, '%M') LIKE ?", ["%{$searchTerm}%"])
              ->orWhereRaw("DATE_FORMAT(recognition_time, '%Y') LIKE ?", ["%{$searchTerm}%"]);
        });
    }

    $logs = $query->orderBy('recognition_time', 'desc')->get();

    // Return JSON for AJAX requests
    if ($request->ajax() || $request->wantsJson()) {
        return response()->json([
            'success' => true,
            'data' => $logs
        ]);
    }

    // Return view for regular requests
    return view('checker.recognition-logs', compact('logs'));
}

}
