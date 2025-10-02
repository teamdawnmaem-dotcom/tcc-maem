<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TeachingLoad;
use App\Models\Faculty;
use App\Models\Room;
use App\Models\Camera;
use App\Models\ActivityLog;
use Illuminate\Support\Carbon;

class TeachingLoadController extends Controller
{
    // Display all teaching loads
    public function index()
    {
        $teachingLoads = TeachingLoad::with(['faculty', 'room'])->get();
        $faculties = Faculty::all();
        $rooms = Room::all();

        return view('deptHead.teaching-load-management', compact('teachingLoads', 'faculties', 'rooms'));
    }

    // API endpoint for teaching loads list
    public function apiTeachingLoads()
    {
        $teachingLoads = TeachingLoad::select(
            'teaching_load_id',
            'teaching_load_course_code',
            'teaching_load_subject',
            'teaching_load_day_of_week',
            'teaching_load_time_in',
            'teaching_load_time_out',
            'faculty_id',
            'room_no'
        )->get();
        
        return response()->json($teachingLoads);
    }

public function apiTodaySchedule()
{
    // Set your local timezone, e.g., "Asia/Manila"
    $todayDate = Carbon::now('Asia/Manila');

    // Get full day name for today in your timezone
    $day = $todayDate->format('l'); // "Monday", "Tuesday", etc.

    // Fetch today's schedule using joins
    $today = TeachingLoad::join('tbl_faculty as f', 'tbl_teaching_load.faculty_id', '=', 'f.faculty_id')
        ->join('tbl_room as r', 'tbl_teaching_load.room_no', '=', 'r.room_no')
        ->select(
            'tbl_teaching_load.teaching_load_id',
            'tbl_teaching_load.teaching_load_course_code',
            'tbl_teaching_load.teaching_load_subject',
            'tbl_teaching_load.teaching_load_time_in',
            'tbl_teaching_load.teaching_load_time_out',
            'tbl_teaching_load.room_no',
            'f.faculty_id',
            'f.faculty_fname',
            'f.faculty_lname',
            'r.room_no',
            'r.room_name',
            'r.room_building_no'
        )
        ->where('tbl_teaching_load.teaching_load_day_of_week', $day)
        ->orderBy('tbl_teaching_load.teaching_load_time_in', 'asc')
        ->get();

    if ($today->isEmpty()) {
        return response()->json(['message' => 'No schedule for today'], 404);
    }

    return response()->json($today);
}

    /**
     * Get detailed information for recognition logging
     */
    public function apiTeachingLoadDetails(Request $request)
    {
        $request->validate([
            'teaching_load_id' => 'nullable|integer',
            'faculty_id' => 'nullable|integer',
            'camera_id' => 'nullable|integer',
        ]);

        $teaching_load_id = $request->input('teaching_load_id');
        $faculty_id = $request->input('faculty_id');
        $camera_id = $request->input('camera_id');

        $details = [];

        // Get teaching load details if teaching_load_id is provided
        if ($teaching_load_id) {
            $teachingLoad = TeachingLoad::with(['faculty', 'room'])->find($teaching_load_id);
            if ($teachingLoad) {
                $details['room_name'] = $teachingLoad->room->room_name ?? 'Unknown';
                $details['building_no'] = $teachingLoad->room->room_building_no ?? 'Unknown';
                $details['faculty_full_name'] = $teachingLoad->faculty->faculty_fname . ' ' . $teachingLoad->faculty->faculty_lname;
            }
        }

        // Get faculty details if faculty_id is provided
        if ($faculty_id && !isset($details['faculty_full_name'])) {
            $faculty = Faculty::find($faculty_id);
            if ($faculty) {
                $details['faculty_full_name'] = $faculty->faculty_fname . ' ' . $faculty->faculty_lname;
            }
        }

        // Get camera details if camera_id is provided
        if ($camera_id) {
            $camera = Camera::with('room')->find($camera_id);
            if ($camera) {
                $details['camera_name'] = $camera->camera_name ?? "Camera {$camera_id}";
                if (!isset($details['room_name'])) {
                    $details['room_name'] = $camera->room->room_name ?? 'Unknown';
                }
                if (!isset($details['building_no'])) {
                    $details['building_no'] = $camera->room->room_building_no ?? 'Unknown';
                }
            }
        }

        return response()->json($details);
    }

    // Store new teaching load
    public function store(Request $request)
    {
        $request->validate([
            'faculty_id' => 'required|exists:tbl_faculty,faculty_id',
            'teaching_load_course_code' => 'required|string|max:50',
            'teaching_load_subject' => 'required|string|max:50',
            'teaching_load_day_of_week' => 'required|string|max:50',
            'teaching_load_time_in' => 'required',
            'teaching_load_time_out' => 'required',
            'room_no' => 'required|exists:tbl_room,room_no',
        ]);

        TeachingLoad::create($request->all());

   $faculty = Faculty::find($request->faculty_id);
   // Log the action
        ActivityLog::create([
            'user_id' => auth()->id(),
            'logs_action' => 'CREATE',
            'logs_description' => 'Added a Teaching load for Faculty: ' . $faculty->faculty_fname . ' ' . $faculty->faculty_lname,
            'logs_module' => 'Faculty Information',
        ]);

        return redirect()->back()->with('success', 'Teaching load added successfully!');
    }

    // Update existing teaching load
    public function update(Request $request, $id)
    {
        $request->validate([
            'faculty_id' => 'required|exists:tbl_faculty,faculty_id',
            'teaching_load_course_code' => 'required|string|max:50',
            'teaching_load_subject' => 'required|string|max:50',
            'teaching_load_day_of_week' => 'required|string|max:50',
            'teaching_load_time_in' => 'required',
            'teaching_load_time_out' => 'required',
            'room_no' => 'required|exists:tbl_room,room_no',
        ]);

        $load = TeachingLoad::findOrFail($id);
        $load->update($request->all());

           $faculty = Faculty::find($request->faculty_id);
   // Log the action
        ActivityLog::create([
            'user_id' => auth()->id(),
            'logs_action' => 'UPDATE',
            'logs_description' => 'Updated a Teaching load of Faculty: ' . $faculty->faculty_fname . ' ' . $faculty->faculty_lname,
            'logs_module' => 'Faculty Information',
        ]);

        return redirect()->back()->with('success', 'Teaching load updated successfully!');
    }

    // Delete teaching load
    public function destroy($id)
    {
        $load = TeachingLoad::findOrFail($id);
        $load->delete();

    // Log the action
    $faculty = Faculty::find($load->faculty_id);
        ActivityLog::create([
            'user_id' => auth()->id(),
            'logs_action' => 'DELETE',
            'logs_description' => 'Deleted a Teaching load of Faculty: ' . $faculty->faculty_fname . ' ' . $faculty->faculty_lname,
            'logs_module' => 'Faculty Information',
        ]);
        return redirect()->back()->with('success', 'Teaching load deleted successfully!');
    }
}
