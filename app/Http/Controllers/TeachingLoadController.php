<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TeachingLoad;
use App\Models\Faculty;
use App\Models\Room;
use App\Models\Camera;
use App\Models\ActivityLog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Models\Subject;

class TeachingLoadController extends Controller
{
    /**
     * Check if a time range overlaps with existing teaching loads
     */
    private function hasTimeOverlap($dayOfWeek, $timeIn, $timeOut, $roomNo, $excludeId = null)
    {
        \Log::info("Checking time overlap for: Day={$dayOfWeek}, TimeIn={$timeIn}, TimeOut={$timeOut}, Room={$roomNo}, ExcludeId={$excludeId}");
        
        $query = TeachingLoad::where('teaching_load_day_of_week', $dayOfWeek)
            ->where('room_no', $roomNo);
        
        if ($excludeId) {
            $query->where('teaching_load_id', '!=', $excludeId);
        }
        
        $existingLoads = $query->get();
        \Log::info("Found {$existingLoads->count()} existing teaching loads to check against");
        
        foreach ($existingLoads as $load) {
            try {
                \Log::info("Comparing with existing load: {$load->teaching_load_course_code} ({$load->teaching_load_time_in} - {$load->teaching_load_time_out})");
                
                // Convert times to Carbon instances for comparison
                // Handle both H:i and H:i:s formats
                $newStart = $this->parseTime($timeIn);
                $newEnd = $this->parseTime($timeOut);
                $existingStart = $this->parseTime($load->teaching_load_time_in);
                $existingEnd = $this->parseTime($load->teaching_load_time_out);
                
                \Log::info("Parsed times - New: {$newStart->format('H:i:s')} to {$newEnd->format('H:i:s')}, Existing: {$existingStart->format('H:i:s')} to {$existingEnd->format('H:i:s')}");
                
                // Check if the new time range overlaps with existing time range
                // Two time ranges overlap if: newStart < existingEnd AND existingStart < newEnd
                if ($newStart->lt($existingEnd) && $existingStart->lt($newEnd)) {
                    \Log::info("Time overlap detected!");
                    return [
                        'has_overlap' => true,
                        'conflicting_load' => $load,
                        'conflict_message' => "Time conflict with existing teaching load: {$load->teaching_load_course_code} ({$load->teaching_load_time_in} - {$load->teaching_load_time_out})"
                    ];
                }
            } catch (\Exception $e) {
                \Log::error("Error parsing time in overlap check: " . $e->getMessage());
                // If there's an error parsing time, skip this comparison
                continue;
            }
        }
        
        return ['has_overlap' => false];
    }

    /**
     * Parse time string to Carbon instance, handling multiple formats
     */
    private function parseTime($timeString)
    {
        if (empty($timeString)) {
            throw new \InvalidArgumentException('Time string is empty');
        }

        // Try different time formats
        $formats = ['H:i:s', 'H:i', 'g:i A', 'g:i:s A'];
        
        foreach ($formats as $format) {
            try {
                return Carbon::createFromFormat($format, $timeString);
            } catch (\Exception $e) {
                continue;
            }
        }
        
        // If all formats fail, try Carbon's flexible parsing
        try {
            return Carbon::parse($timeString);
        } catch (\Exception $e) {
            throw new \InvalidArgumentException("Unable to parse time: {$timeString}");
        }
    }

    // Display all teaching loads
    public function index()
    {
        $teachingLoads = TeachingLoad::with(['faculty', 'room'])->get();
        $faculties = Faculty::all();
        $rooms = Room::all();

        // Build subject options from subjects table
        $subjectsOptions = Subject::select('subject_code as code', 'subject_description as name', 'department')
            ->orderBy('subject_code')
            ->orderBy('subject_description')
            ->get();

        return view('deptHead.teaching-load-management', compact('teachingLoads', 'faculties', 'rooms', 'subjectsOptions'));
    }

    // API endpoint for teaching loads list
    public function apiTeachingLoads()
    {
        $teachingLoads = TeachingLoad::select(
            'teaching_load_id',
            'teaching_load_course_code',
            'teaching_load_subject',
            'teaching_load_day_of_week',
            'teaching_load_class_section',
            'teaching_load_time_in',
            'teaching_load_time_out',
            'faculty_id',
            'room_no'
        )->get();
        
        return response()->json($teachingLoads);
    }

public function apiTodaySchedule()
{
    // local timezone "Asia/Manila"
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
            'tbl_teaching_load.teaching_load_class_section',
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
            'tl_department_short' => 'required|string',
            'tl_year_level' => 'required|string',
            'tl_section' => 'required|string',
        ]);

        // Check for time overlap
        try {
            $overlapCheck = $this->hasTimeOverlap(
                $request->teaching_load_day_of_week,
                $request->teaching_load_time_in,
                $request->teaching_load_time_out,
                $request->room_no
            );

            if ($overlapCheck['has_overlap']) {
                return redirect()->back()
                    ->withErrors(['teaching_load_time_in' => $overlapCheck['conflict_message']])
                    ->with('open_modal', 'addTeachingLoadModal')
                    ->withInput();
            }
        } catch (\Exception $e) {
            \Log::error("Error in time overlap check during store: " . $e->getMessage());
            return redirect()->back()
                ->withErrors(['teaching_load_time_in' => 'Error validating time schedule. Please check your time format.'])
                ->with('open_modal', 'addTeachingLoadModal')
                ->withInput();
        }

        // Combine department, year, and section into class_section
        $classSection = $request->tl_department_short . ' ' . $request->tl_year_level . $request->tl_section;
        
        $data = $request->all();
        $data['teaching_load_class_section'] = $classSection;
        
        TeachingLoad::create($data);

   $faculty = Faculty::find($request->faculty_id);
   // Log the action
        ActivityLog::create([
            'user_id' => Auth::id(),
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
            'tl_department_short' => 'required|string',
            'tl_year_level' => 'required|string',
            'tl_section' => 'required|string',
        ]);

        // Check for time overlap (excluding the current teaching load being updated)
        try {
            $overlapCheck = $this->hasTimeOverlap(
                $request->teaching_load_day_of_week,
                $request->teaching_load_time_in,
                $request->teaching_load_time_out,
                $request->room_no,
                $id // Exclude current teaching load from overlap check
            );

            if ($overlapCheck['has_overlap']) {
                return redirect()->back()
                    ->withErrors(['teaching_load_time_in' => $overlapCheck['conflict_message']])
                    ->with('open_modal', 'updateTeachingLoadModal')
                    ->withInput();
            }
        } catch (\Exception $e) {
            \Log::error("Error in time overlap check during update: " . $e->getMessage());
            return redirect()->back()
                ->withErrors(['teaching_load_time_in' => 'Error validating time schedule. Please check your time format.'])
                ->with('open_modal', 'updateTeachingLoadModal')
                ->withInput();
        }

        // Combine department, year, and section into class_section
        $classSection = $request->tl_department_short . ' ' . $request->tl_year_level . $request->tl_section;
        
        $data = $request->all();
        $data['teaching_load_class_section'] = $classSection;

        $load = TeachingLoad::findOrFail($id);
        $load->update($data);

           $faculty = Faculty::find($request->faculty_id);
   // Log the action
        ActivityLog::create([
            'user_id' => Auth::id(),
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
            'user_id' => Auth::id(),
            'logs_action' => 'DELETE',
            'logs_description' => 'Deleted a Teaching load of Faculty: ' . $faculty->faculty_fname . ' ' . $faculty->faculty_lname,
            'logs_module' => 'Faculty Information',
        ]);
        return redirect()->back()->with('success', 'Teaching load deleted successfully!');
    }
}
