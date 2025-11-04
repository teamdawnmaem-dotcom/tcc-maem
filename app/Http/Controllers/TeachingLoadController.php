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
use App\Models\TeachingLoadArchive;
use App\Models\AttendanceRecordArchive;
use App\Models\AttendanceRecord;

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
                    $roomName = $load->room->room_name ?? $load->room_no;
                    return [
                        'has_overlap' => true,
                        'conflicting_load' => $load,
                        'conflict_message' => "Time conflict with existing teaching load: {$load->teaching_load_course_code} ({$load->teaching_load_time_in} - {$load->teaching_load_time_out}) in {$roomName}"
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

    // Admin view for teaching loads
    public function indexAdmin()
    {
        $teachingLoads = TeachingLoad::with(['faculty', 'room'])->get();
        $faculties = Faculty::all();
        $rooms = Room::all();

        $subjectsOptions = Subject::select('subject_code as code', 'subject_description as name', 'department')
            ->orderBy('subject_code')
            ->orderBy('subject_description')
            ->get();

        return view('admin.teaching-load-management', compact('teachingLoads', 'faculties', 'rooms', 'subjectsOptions'));
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

    // CSV Upload for teaching loads
    public function csvUpload(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:2048'
        ]);

        try {
            $file = $request->file('csv_file');
            $csvData = array_map('str_getcsv', file($file->getPathname()));
            
            // Remove header row
            $header = array_shift($csvData);
            
            $successCount = 0;
            $errorCount = 0;
            $errors = [];
            $successDetails = []; // Track successful entries
            $processedRows = []; // Track processed rows to avoid duplicates within the same upload
            
            foreach ($csvData as $index => $row) {
                try {
                    // Validate row has required columns
                    if (count($row) < 8) {
                        $errors[] = "Row " . ($index + 2) . ": Insufficient columns. Expected 8, got " . count($row);
                        $errorCount++;
                        continue;
                    }
                    
                    // Extract data from CSV row
                    $instructorName = trim($row[0]);
                    $courseCode = trim($row[1]);
                    $subject = trim($row[2]);
                    $classSection = trim($row[3]);
                    $day = trim($row[4]);
                    $timeIn = trim($row[5]);
                    $timeOut = trim($row[6]);
                    $roomNo = trim($row[7]);
                    
                    // Validate required fields are not empty
                    if (empty($instructorName) || empty($courseCode) || empty($subject) || 
                        empty($classSection) || empty($day) || empty($timeIn) || 
                        empty($timeOut) || empty($roomNo)) {
                        $errors[] = "Row " . ($index + 2) . ": All fields are required and cannot be empty";
                        $errorCount++;
                        continue;
                    }
                    
                    // Find faculty by name (case-insensitive)
                    $faculty = Faculty::whereRaw("LOWER(CONCAT(faculty_fname, ' ', faculty_lname)) = LOWER(?)", [$instructorName])->first();
                    if (!$faculty) {
                        $errors[] = "Row " . ($index + 2) . ": Instructor '{$instructorName}' not found in the system";
                        $errorCount++;
                        continue;
                    }
                    
                    // Validate subject exists in subjects table
                    $subjectRecord = Subject::where('subject_code', $courseCode)
                        ->where('subject_description', $subject)
                        ->first();
                    if (!$subjectRecord) {
                        $errors[] = "Row " . ($index + 2) . ": Subject '{$courseCode} - {$subject}' not found in the system";
                        $errorCount++;
                        continue;
                    }
                    
                    // Validate room exists by room_name
                    $room = Room::where('room_name', $roomNo)->first();
                    if (!$room) {
                        $errors[] = "Row " . ($index + 2) . ": Room '{$roomNo}' not found in the system";
                        $errorCount++;
                        continue;
                    }
                    
                    // Parse class section to get department, year, section
                    $classSectionMatch = preg_match('/^([A-Z]+)\s+(\d+)([A-Z]+)$/', $classSection, $matches);
                    if (!$classSectionMatch) {
                        $errors[] = "Row " . ($index + 2) . ": Invalid class section format '{$classSection}'. Expected format: 'BSIT 1A'";
                        $errorCount++;
                        continue;
                    }
                    
                    $department = $matches[1];
                    $year = $matches[2];
                    $section = $matches[3];
                    
                    // Validate day of week
                    $validDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                    if (!in_array($day, $validDays)) {
                        $errors[] = "Row " . ($index + 2) . ": Invalid day '{$day}'. Must be one of: " . implode(', ', $validDays);
                        $errorCount++;
                        continue;
                    }
                    
                    // Validate and normalize time formats
                    try {
                        $normalizedTimeIn = $this->normalizeTimeFormat($timeIn);
                        $normalizedTimeOut = $this->normalizeTimeFormat($timeOut);
                    } catch (\Exception $e) {
                        $errors[] = "Row " . ($index + 2) . ": Invalid time format. " . $e->getMessage();
                        $errorCount++;
                        continue;
                    }
                    
                    // Validate time logic: time in must be before time out
                    if ($normalizedTimeIn >= $normalizedTimeOut) {
                        $errors[] = "Row " . ($index + 2) . ": Time out must be later than time in";
                        $errorCount++;
                        continue;
                    }
                    
                    // Check for duplicates within the same CSV upload
                    $rowKey = $faculty->faculty_id . '|' . $day . '|' . $normalizedTimeIn . '|' . $normalizedTimeOut . '|' . $room->room_no;
                    if (in_array($rowKey, $processedRows)) {
                        $errors[] = "Row " . ($index + 2) . ": Duplicate entry found within the same CSV file";
                        $errorCount++;
                        continue;
                    }
                    $processedRows[] = $rowKey;
                    
                    // Check for time overlap with existing teaching loads
                    $overlapCheck = $this->hasTimeOverlap($day, $normalizedTimeIn, $normalizedTimeOut, $room->room_no);
                    if ($overlapCheck['has_overlap']) {
                        $errors[] = "Row " . ($index + 2) . ": " . $overlapCheck['conflict_message'];
                        $errorCount++;
                        continue;
                    }
                    
                    // Create teaching load
                    TeachingLoad::create([
                        'faculty_id' => $faculty->faculty_id,
                        'teaching_load_course_code' => $courseCode,
                        'teaching_load_subject' => $subject,
                        'teaching_load_class_section' => $classSection,
                        'teaching_load_day_of_week' => $day,
                        'teaching_load_time_in' => $normalizedTimeIn,
                        'teaching_load_time_out' => $normalizedTimeOut,
                        'room_no' => $room->room_no,
                    ]);
                    
                    // Add success details
                    $successDetails[] = "Row " . ($index + 2) . ": {$instructorName} - {$courseCode} ({$subject}) - {$classSection} - {$day} {$normalizedTimeIn}-{$normalizedTimeOut} - {$room->room_name}";
                    $successCount++;
                    
                } catch (\Exception $e) {
                    $errors[] = "Row " . ($index + 2) . ": " . $e->getMessage();
                    $errorCount++;
                }
            }
            
            // Log the action
            ActivityLog::create([
                'user_id' => Auth::id(),
                'logs_action' => 'CREATE',
                'logs_description' => "CSV upload completed: {$successCount} teaching loads added, {$errorCount} errors",
                'logs_module' => 'Teaching Load Management',
            ]);
            
            $message = "CSV upload completed!\n";
            $message .= "✅ Successfully added: {$successCount} teaching loads\n";
            
            // Add success details
            if ($successCount > 0) {
                $message .= "\nSuccess Details:\n";
                $message .= implode("\n", $successDetails); // Show ALL successes
            }
            
            if ($errorCount > 0) {
                $message .= "\n❌ Errors: {$errorCount} rows\n";
                $message .= "\nError Details:\n";
                $message .= implode("\n", $errors); // Show ALL errors
            }
            
            return redirect()->back()->with('success', $message);
            
        } catch (\Exception $e) {
            \Log::error("CSV upload error: " . $e->getMessage());
            return redirect()->back()->withErrors(['csv_file' => 'Error processing CSV file: ' . $e->getMessage()]);
        }
    }

    /**
     * Download CSV template for teaching loads
     */
    public function csvTemplate()
    {
        $csvContent = "Instructor,Course Code,Subject Description,Class Section,Day,Time In,Time Out,Room Name\n";
        $csvContent .= "John Doe,IT 101,Introduction to Computing,BSIT 1A,Monday,08:00,10:00,ComLab 1\n";
        $csvContent .= "Jane Smith,IT 102,Computer Programming 1,BSIT 2B,Tuesday,10:30,12:30,ComLab 1\n";
        $csvContent .= "Mike Johnson,IT 103,Integrated Application Software,BSIT 3A,Wednesday,14:00,16:00,ComLab 1\n";
        
        $filename = 'teaching_load_template_' . date('Y-m-d') . '.csv';
        
        return response($csvContent)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    /**
     * Normalize time format to ensure consistency
     */
    private function normalizeTimeFormat($timeString)
    {
        if (empty($timeString)) {
            throw new \InvalidArgumentException('Time string is empty');
        }

        $timeString = trim($timeString);
        
        // Try different time formats
        $formats = ['H:i:s', 'H:i', 'g:i A', 'g:i:s A'];
        
        foreach ($formats as $format) {
            try {
                $carbon = Carbon::createFromFormat($format, $timeString);
                return $carbon->format('H:i:s');
            } catch (\Exception $e) {
                continue;
            }
        }
        
        // If all formats fail, try Carbon's flexible parsing
        try {
            $carbon = Carbon::parse($timeString);
            return $carbon->format('H:i:s');
        } catch (\Exception $e) {
            throw new \InvalidArgumentException("Unable to parse time: {$timeString}. Expected formats: HH:MM, HH:MM:SS, or H:MM AM/PM");
        }
    }

    /**
     * Archive all teaching loads for the current school year
     */
    public function archiveAllTeachingLoads(Request $request)
    {
        $request->validate([
            'school_year' => 'required|string|max:20',
            'semester' => 'required|string|max:20',
            'archive_notes' => 'nullable|string|max:500'
        ]);

        try {
            // Get all current teaching loads
            $teachingLoads = TeachingLoad::with(['faculty', 'room'])->get();
            
            if ($teachingLoads->isEmpty()) {
                return redirect()->back()->with('error', 'No teaching loads found to archive.');
            }

            $archivedCount = 0;
            $errors = [];

            foreach ($teachingLoads as $load) {
                try {
                    // Create archive record
                    $archivedLoad = TeachingLoadArchive::create([
                        'original_teaching_load_id' => $load->teaching_load_id,
                        'faculty_id' => $load->faculty_id,
                        'teaching_load_course_code' => $load->teaching_load_course_code,
                        'teaching_load_subject' => $load->teaching_load_subject,
                        'teaching_load_class_section' => $load->teaching_load_class_section,
                        'teaching_load_day_of_week' => $load->teaching_load_day_of_week,
                        'teaching_load_time_in' => $load->teaching_load_time_in,
                        'teaching_load_time_out' => $load->teaching_load_time_out,
                        'room_no' => $load->room_no,
                        'school_year' => $request->school_year,
                        'semester' => $request->semester,
                        'archived_at' => now(),
                        'archived_by' => Auth::id(),
                        'archive_notes' => $request->archive_notes,
                    ]);

                    // Archive corresponding attendance records
                    $attendanceRecords = AttendanceRecord::where('teaching_load_id', $load->teaching_load_id)->get();
                    foreach ($attendanceRecords as $record) {
                        AttendanceRecordArchive::create([
                            'original_record_id' => $record->record_id,
                            'faculty_id' => $record->faculty_id,
                            'teaching_load_id' => $archivedLoad->archive_id, // Reference to archived teaching load
                            'camera_id' => $record->camera_id,
                            'record_date' => $record->record_date,
                            'record_time_in' => $record->record_time_in,
                            'record_time_out' => $record->record_time_out,
                            'time_duration_seconds' => $record->time_duration_seconds,
                            'record_status' => $record->record_status,
                            'record_remarks' => $record->record_remarks,
                            'school_year' => $request->school_year,
                            'semester' => $request->semester,
                            'archived_at' => now(),
                            'archived_by' => Auth::id(),
                            'archive_notes' => $request->archive_notes,
                        ]);
                    }
                    
                    // Delete the original attendance records after archiving
                    AttendanceRecord::where('teaching_load_id', $load->teaching_load_id)->delete();

                    // Delete the original teaching load
                    $load->delete();
                    $archivedCount++;

                } catch (\Exception $e) {
                    $errors[] = "Failed to archive teaching load ID {$load->teaching_load_id}: " . $e->getMessage();
                }
            }

            // Log the action
            ActivityLog::create([
                'user_id' => Auth::id(),
                'logs_action' => 'ARCHIVE',
                'logs_description' => "Archived {$archivedCount} teaching loads for {$request->school_year} - {$request->semester}",
                'logs_module' => 'Teaching Load Management',
            ]);

            $message = "Successfully archived {$archivedCount} teaching loads for {$request->school_year} - {$request->semester}";
            
            if (!empty($errors)) {
                $message .= "\n\nErrors encountered:\n" . implode("\n", $errors);
            }

            return redirect()->back()->with('success', $message);

        } catch (\Exception $e) {
            \Log::error("Archive teaching loads error: " . $e->getMessage());
            return redirect()->back()->withErrors(['archive' => 'Error archiving teaching loads: ' . $e->getMessage()]);
        }
    }

    /**
     * View archived teaching loads
     */
    public function viewArchivedTeachingLoads()
    {
        try {
            $archivedLoads = TeachingLoadArchive::with(['faculty', 'room', 'archivedBy'])
                ->orderBy('school_year', 'desc')
                ->orderBy('semester', 'desc')
                ->orderBy('archived_at', 'desc')
                ->get()
                ->map(function ($load) {
                    // Get attendance records count for this archived teaching load
                    $attendanceCount = AttendanceRecordArchive::where('teaching_load_id', $load->archive_id)->count();
                    $load->attendance_records_count = $attendanceCount;
                    return $load;
                });

            \Log::info("Retrieved " . $archivedLoads->count() . " archived teaching loads");

            return view('deptHead.archived-teaching-loads', compact('archivedLoads'));
        } catch (\Exception $e) {
            \Log::error("Error retrieving archived teaching loads: " . $e->getMessage());
            return redirect()->back()->withErrors(['error' => 'Error loading archived teaching loads: ' . $e->getMessage()]);
        }
    }

    // Admin archived teaching loads view
    public function viewArchivedTeachingLoadsAdmin()
    {
        try {
            $archivedLoads = TeachingLoadArchive::with(['faculty', 'room', 'archivedBy'])
                ->orderBy('school_year', 'desc')
                ->orderBy('semester', 'desc')
                ->orderBy('archived_at', 'desc')
                ->get()
                ->map(function ($load) {
                    $attendanceCount = AttendanceRecordArchive::where('teaching_load_id', $load->archive_id)->count();
                    $load->attendance_records_count = $attendanceCount;
                    return $load;
                });

            return view('admin.archived-teaching-loads', compact('archivedLoads'));
        } catch (\Exception $e) {
            \Log::error("Error retrieving archived teaching loads (admin): " . $e->getMessage());
            return redirect()->back()->withErrors(['error' => 'Error loading archived teaching loads: ' . $e->getMessage()]);
        }
    }

    /**
     * Restore a teaching load from archive
     */
    public function restoreTeachingLoad($archiveId)
    {
        try {
            $archivedLoad = TeachingLoadArchive::findOrFail($archiveId);

            // Check for time overlap before restoring
            $overlapCheck = $this->hasTimeOverlap(
                $archivedLoad->teaching_load_day_of_week,
                $archivedLoad->teaching_load_time_in,
                $archivedLoad->teaching_load_time_out,
                $archivedLoad->room_no
            );

            if ($overlapCheck['has_overlap']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot restore: ' . $overlapCheck['conflict_message']
                ], 400);
            }

            // Create new teaching load
            $restoredLoad = TeachingLoad::create([
                'faculty_id' => $archivedLoad->faculty_id,
                'teaching_load_course_code' => $archivedLoad->teaching_load_course_code,
                'teaching_load_subject' => $archivedLoad->teaching_load_subject,
                'teaching_load_class_section' => $archivedLoad->teaching_load_class_section,
                'teaching_load_day_of_week' => $archivedLoad->teaching_load_day_of_week,
                'teaching_load_time_in' => $archivedLoad->teaching_load_time_in,
                'teaching_load_time_out' => $archivedLoad->teaching_load_time_out,
                'room_no' => $archivedLoad->room_no,
            ]);

            // Restore corresponding attendance records
            $archivedAttendanceRecords = AttendanceRecordArchive::where('teaching_load_id', $archivedLoad->archive_id)->get();
            foreach ($archivedAttendanceRecords as $archivedRecord) {
                AttendanceRecord::create([
                    'faculty_id' => $archivedRecord->faculty_id,
                    'teaching_load_id' => $restoredLoad->teaching_load_id, // Reference to restored teaching load
                    'camera_id' => $archivedRecord->camera_id,
                    'record_date' => $archivedRecord->record_date,
                    'record_time_in' => $archivedRecord->record_time_in,
                    'record_time_out' => $archivedRecord->record_time_out,
                    'time_duration_seconds' => $archivedRecord->time_duration_seconds,
                    'record_status' => $archivedRecord->record_status,
                    'record_remarks' => $archivedRecord->record_remarks,
                ]);
                
                // Delete the archived attendance record
                $archivedRecord->delete();
            }

            // Log the action
            ActivityLog::create([
                'user_id' => Auth::id(),
                'logs_action' => 'RESTORE',
                'logs_description' => "Restored teaching load from archive: {$archivedLoad->teaching_load_course_code} - {$archivedLoad->teaching_load_subject}",
                'logs_module' => 'Teaching Load Management',
            ]);

            // Delete from archive
            $archivedLoad->delete();

            return response()->json([
                'success' => true,
                'message' => 'Teaching load restored successfully!'
            ]);

        } catch (\Exception $e) {
            \Log::error("Restore teaching load error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error restoring teaching load: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Permanently delete archived teaching load
     */
    public function permanentlyDeleteArchived($archiveId)
    {
        try {
            $archivedLoad = TeachingLoadArchive::findOrFail($archiveId);
            $courseCode = $archivedLoad->teaching_load_course_code;
            $subject = $archivedLoad->teaching_load_subject;
            
            // Delete corresponding archived attendance records
            AttendanceRecordArchive::where('teaching_load_id', $archivedLoad->archive_id)->delete();
            
            $archivedLoad->delete();

            // Log the action
            ActivityLog::create([
                'user_id' => Auth::id(),
                'logs_action' => 'DELETE',
                'logs_description' => "Permanently deleted archived teaching load: {$courseCode} - {$subject}",
                'logs_module' => 'Teaching Load Management',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Archived teaching load permanently deleted!'
            ]);

        } catch (\Exception $e) {
            \Log::error("Permanently delete archived teaching load error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error deleting archived teaching load: ' . $e->getMessage()
            ], 500);
        }
    }
}
