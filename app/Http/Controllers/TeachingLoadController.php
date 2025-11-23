<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TeachingLoad;
use App\Models\Faculty;
use App\Models\Room;
use App\Models\Camera;
use App\Models\ActivityLog;
use App\Services\CloudSyncService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Models\Subject;
use App\Models\TeachingLoadArchive;
use App\Models\AttendanceRecordArchive;
use App\Models\AttendanceRecord;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\Date;

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
        $loadId = $load->teaching_load_id;
        $load->delete();

        // Track deletion for sync
        $syncService = app(CloudSyncService::class);
        $syncService->trackDeletion('tbl_teaching_load', $loadId);
        
        // NEW APPROACH: Immediately trigger deletion on cloud
        try {
            $syncService->triggerDeleteOnCloudByTable('tbl_teaching_load', $loadId);
        } catch (\Exception $e) {
            \Log::error("Failed to trigger teaching load deletion on cloud: " . $e->getMessage());
        }

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

    // Excel/CSV Upload for teaching loads
    public function csvUpload(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt,xlsx,xls|max:10240'
        ]);

        try {
            $file = $request->file('csv_file');
            $extension = $file->getClientOriginalExtension();
            
            // Parse file based on extension
            if (in_array(strtolower($extension), ['xlsx', 'xls'])) {
                // Handle Excel file
                $spreadsheet = IOFactory::load($file->getPathname());
                $worksheet = $spreadsheet->getActiveSheet();
                $csvData = [];
                
                // Skip first 5 rows (institution header, title, empty row, column headers, example row)
                // Start from row 6 (actual data rows)
                $startRow = 6;
                $highestRow = $worksheet->getHighestRow();
                
                // Read header row (row 4) for validation
                $headerRow = [];
                $highestColumn = $worksheet->getHighestColumn();
                for ($col = 'A'; $col <= $highestColumn; $col++) {
                    $headerRow[] = $worksheet->getCell($col . '4')->getValue();
                }
                $header = $headerRow;
                
                // Read data rows starting from row 6
                for ($row = $startRow; $row <= $highestRow; $row++) {
                    $rowData = [];
                    $colIndex = 0;
                    for ($col = 'A'; $col <= $highestColumn; $col++) {
                        $cell = $worksheet->getCell($col . $row);
                        $value = $cell->getValue();
                        
                        // Handle Excel time/date formats (columns F and G are Time In and Time Out)
                        if ($colIndex == 5 || $colIndex == 6) { // Time In (F) and Time Out (G)
                            // First try to get the formatted value (what user sees in Excel)
                            $formattedValue = $cell->getFormattedValue();
                            
                            // Check if formatted value looks like a time (HH:MM or HH:MM:SS)
                            if (preg_match('/^\d{1,2}:\d{2}(:\d{2})?$/', trim($formattedValue))) {
                                $value = trim($formattedValue);
                                // Ensure it has seconds
                                if (substr_count($value, ':') == 1) {
                                    $value .= ':00';
                                }
                            } elseif ($value instanceof \DateTime) {
                                // If it's a DateTime object, format it as time
                                $value = $value->format('H:i:s');
                            } elseif (is_numeric($value)) {
                                // Check if it's an Excel time value (between 0 and 1)
                                if ($value >= 0 && $value < 1) {
                                    // Convert Excel time decimal to time string
                                    // Excel stores time as fraction of day (0.5 = 12:00:00)
                                    $totalSeconds = round($value * 86400); // 86400 seconds in a day
                                    $hours = floor($totalSeconds / 3600);
                                    $minutes = floor(($totalSeconds % 3600) / 60);
                                    $seconds = $totalSeconds % 60;
                                    $value = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
                                } else {
                                    // Try to convert using Date helper
                                    try {
                                        $formattedValue = Date::excelToDateTimeObject($value);
                                        $value = $formattedValue->format('H:i:s');
                                    } catch (\Exception $e) {
                                        // Fallback to string
                                        $value = trim((string)$formattedValue);
                                    }
                                }
                            } else {
                                // String, use as is but trim
                                $value = trim((string)$value);
                            }
                        } else {
                            // Non-time columns: convert to string
                            if ($value instanceof \DateTime) {
                                $value = $value->format('Y-m-d H:i:s');
                            } else {
                                $value = trim((string)$value);
                            }
                        }
                        
                        $rowData[] = $value;
                        $colIndex++;
                    }
                    if (!empty(array_filter($rowData))) { // Skip empty rows
                        $csvData[] = $rowData;
                    }
                }
            } else {
                // Handle CSV file
                $csvData = array_map('str_getcsv', file($file->getPathname()));
                
                // Remove header row
                $header = array_shift($csvData);
            }
            
            $successCount = 0;
            $errorCount = 0;
            $errors = [];
            $successDetails = []; // Track successful entries
            $processedRows = []; // Track processed rows to avoid duplicates within the same upload
            
            foreach ($csvData as $index => $row) {
                try {
                    // Calculate actual row number (Excel: row 6+ = index+6, CSV: row 2+ = index+2)
                    $actualRowNumber = (in_array(strtolower($extension), ['xlsx', 'xls'])) ? ($index + 6) : ($index + 2);
                    
                    // Validate row has required columns
                    if (count($row) < 8) {
                        $errors[] = "Row " . $actualRowNumber . ": Insufficient columns. Expected 8, got " . count($row);
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
                        $errors[] = "Row " . $actualRowNumber . ": All fields are required and cannot be empty";
                        $errorCount++;
                        continue;
                    }
                    
                    // Find faculty by name (case-insensitive)
                    $faculty = Faculty::whereRaw("LOWER(CONCAT(faculty_fname, ' ', faculty_lname)) = LOWER(?)", [$instructorName])->first();
                    if (!$faculty) {
                        $errors[] = "Row " . $actualRowNumber . ": Instructor '{$instructorName}' not found in the system";
                        $errorCount++;
                        continue;
                    }
                    
                    // Validate subject exists in subjects table
                    $subjectRecord = Subject::where('subject_code', $courseCode)
                        ->where('subject_description', $subject)
                        ->first();
                    if (!$subjectRecord) {
                        $errors[] = "Row " . $actualRowNumber . ": Subject '{$courseCode} - {$subject}' not found in the system";
                        $errorCount++;
                        continue;
                    }
                    
                    // Validate room exists by room_name
                    $room = Room::where('room_name', $roomNo)->first();
                    if (!$room) {
                        $errors[] = "Row " . $actualRowNumber . ": Room '{$roomNo}' not found in the system";
                        $errorCount++;
                        continue;
                    }
                    
                    // Parse class section to get department, year, section
                    $classSectionMatch = preg_match('/^([A-Z]+)\s+(\d+)([A-Z]+)$/', $classSection, $matches);
                    if (!$classSectionMatch) {
                        $errors[] = "Row " . $actualRowNumber . ": Invalid class section format '{$classSection}'. Expected format: 'BSIT 1A'";
                        $errorCount++;
                        continue;
                    }
                    
                    $department = $matches[1];
                    $year = $matches[2];
                    $section = $matches[3];
                    
                    // Validate day of week
                    $validDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                    if (!in_array($day, $validDays)) {
                        $errors[] = "Row " . $actualRowNumber . ": Invalid day '{$day}'. Must be one of: " . implode(', ', $validDays);
                        $errorCount++;
                        continue;
                    }
                    
                    // Validate and normalize time formats
                    try {
                        $normalizedTimeIn = $this->normalizeTimeFormat($timeIn);
                        $normalizedTimeOut = $this->normalizeTimeFormat($timeOut);
                    } catch (\Exception $e) {
                        $errors[] = "Row " . $actualRowNumber . ": Invalid time format. " . $e->getMessage();
                        $errorCount++;
                        continue;
                    }
                    
                    // Validate time logic: time in must be before time out
                    // Compare as time strings (H:i:s format)
                    $timeInSeconds = $this->timeToSeconds($normalizedTimeIn);
                    $timeOutSeconds = $this->timeToSeconds($normalizedTimeOut);
                    
                    if ($timeInSeconds >= $timeOutSeconds) {
                        $errors[] = "Row " . $actualRowNumber . ": Time out must be later than time in (Time In: {$normalizedTimeIn}, Time Out: {$normalizedTimeOut})";
                        $errorCount++;
                        continue;
                    }
                    
                    // Check for duplicates within the same CSV upload
                    $rowKey = $faculty->faculty_id . '|' . $day . '|' . $normalizedTimeIn . '|' . $normalizedTimeOut . '|' . $room->room_no;
                    if (in_array($rowKey, $processedRows)) {
                        $errors[] = "Row " . $actualRowNumber . ": Duplicate entry found within the same file";
                        $errorCount++;
                        continue;
                    }
                    $processedRows[] = $rowKey;
                    
                    // Check for time overlap with existing teaching loads
                    $overlapCheck = $this->hasTimeOverlap($day, $normalizedTimeIn, $normalizedTimeOut, $room->room_no);
                    if ($overlapCheck['has_overlap']) {
                        $errors[] = "Row " . $actualRowNumber . ": " . $overlapCheck['conflict_message'];
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
                    $successDetails[] = "Row " . $actualRowNumber . ": {$instructorName} - {$courseCode} ({$subject}) - {$classSection} - {$day} {$normalizedTimeIn}-{$normalizedTimeOut} - {$room->room_name}";
                    $successCount++;
                    
                } catch (\Exception $e) {
                    $errors[] = "Row " . $actualRowNumber . ": " . $e->getMessage();
                    $errorCount++;
                }
            }
            
            // Log the action
            ActivityLog::create([
                'user_id' => Auth::id(),
                'logs_action' => 'CREATE',
                'logs_description' => "Excel upload completed: {$successCount} teaching loads added, {$errorCount} errors",
                'logs_module' => 'Teaching Load Management',
            ]);
            
            $message = "Excel upload completed!\n";
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
            \Log::error("Excel upload error: " . $e->getMessage());
            return redirect()->back()->withErrors(['csv_file' => 'Error processing Excel file: ' . $e->getMessage()]);
        }
    }

    /**
     * Download Excel template for teaching loads with professional formatting
     */
    public function excelTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Set institution header
        $sheet->setCellValue('A1', 'TAGOLOAN COMMUNITY COLLEGE');
        $sheet->mergeCells('A1:H1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1')->getFill()->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF8B0000'); // Maroon background
        $sheet->getStyle('A1')->getFont()->getColor()->setARGB('FFFFFFFF'); // White text
        $sheet->getRowDimension('1')->setRowHeight(30);
        
        // Set template title
        $sheet->setCellValue('A2', 'TEACHING LOAD TEMPLATE');
        $sheet->mergeCells('A2:H2');
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getRowDimension('2')->setRowHeight(25);
        
        // Empty row
        $sheet->getRowDimension('3')->setRowHeight(10);
        
        // Set column headers
        $headers = ['Instructor', 'Course Code', 'Subject Description', 'Class Section', 'Day', 'Time In', 'Time Out', 'Room Name'];
        $column = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($column . '4', $header);
            $sheet->getStyle($column . '4')->getFont()->setBold(true)->setSize(11);
            $sheet->getStyle($column . '4')->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                ->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle($column . '4')->getFill()->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FF8B0000'); // Maroon background
            $sheet->getStyle($column . '4')->getFont()->getColor()->setARGB('FFFFFFFF'); // White text
            $sheet->getStyle($column . '4')->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN);
            $column++;
        }
        $sheet->getRowDimension('4')->setRowHeight(25);
        
        // Set sample data with examples in parentheses (italicized)
        // Example texts for each column
        $examples = [
            'Faculty Full Name',
            'IT 101',
            'Introduction to Computing',
            'BSIT 1A',
            'Monday',
            '08:00',
            '10:00',
            'ComLab 1'
        ];
        
        $row = 5;
        $column = 'A';
        
        // Create one example row with italicized examples
        foreach ($examples as $exampleText) {
            $richText = new RichText();
            $richText->createText('ex. (');
            $italicText = $richText->createTextRun($exampleText);
            $italicText->getFont()->setItalic(true);
            $richText->createText(')');
            
            $sheet->setCellValue($column . $row, $richText);
            $sheet->getStyle($column . $row)->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                ->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle($column . $row)->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN);
            $column++;
        }
        $sheet->getRowDimension($row)->setRowHeight(20);
        
        // Apply "Good" style (light green background) to row 5 (example row)
        $exampleRange = 'A5:H5';
        $sheet->getStyle($exampleRange)->getFill()->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFC6EFCE'); // Light green (Excel "Good" style color)
        
        // Apply borders and formatting to a large range (up to row 1000) for auto-formatting new data
        $lastRow = 1000;
        $dataRange = 'A5:H' . $lastRow;
        $sheet->getStyle($dataRange)->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle($dataRange)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);
        
        // Set default row height for data rows
        for ($i = 5; $i <= $lastRow; $i++) {
            $sheet->getRowDimension($i)->setRowHeight(20);
        }
        
        // Set column widths
        $sheet->getColumnDimension('A')->setWidth(20); // Instructor
        $sheet->getColumnDimension('B')->setWidth(15); // Course Code
        $sheet->getColumnDimension('C')->setWidth(35); // Subject Description
        $sheet->getColumnDimension('D')->setWidth(15); // Class Section
        $sheet->getColumnDimension('E')->setWidth(12); // Day
        $sheet->getColumnDimension('F')->setWidth(12); // Time In
        $sheet->getColumnDimension('G')->setWidth(12); // Time Out
        $sheet->getColumnDimension('H')->setWidth(15); // Room Name
        
        // Freeze rows 1-5 (institution header, title, empty row, column headers, example row)
        $sheet->freezePane('A6');
        
        // Generate file
        $filename = 'teaching_load_template_' . date('Y-m-d') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        
        $tempFile = tempnam(sys_get_temp_dir(), 'teaching_load_template');
        $writer->save($tempFile);
        
        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Normalize time format to ensure consistency
     */
    private function normalizeTimeFormat($timeString)
    {
        if (empty($timeString)) {
            throw new \InvalidArgumentException('Time string is empty');
        }

        $timeString = trim((string)$timeString);
        
        // Remove any extra whitespace
        $timeString = preg_replace('/\s+/', ' ', $timeString);
        
        // Try different time formats
        $formats = ['H:i:s', 'H:i', 'g:i A', 'g:i:s A', 'g:iA', 'g:i:sA'];
        
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
     * Convert time string (H:i:s format) to seconds for comparison
     */
    private function timeToSeconds($timeString)
    {
        $parts = explode(':', $timeString);
        $hours = (int)($parts[0] ?? 0);
        $minutes = (int)($parts[1] ?? 0);
        $seconds = (int)($parts[2] ?? 0);
        return ($hours * 3600) + ($minutes * 60) + $seconds;
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
                    
                    // Get attendance record IDs before deletion (for tracking)
                    $attendanceRecordIds = AttendanceRecord::where('teaching_load_id', $load->teaching_load_id)
                        ->pluck('record_id')
                        ->toArray();
                    
                    // Delete the original attendance records after archiving
                    AttendanceRecord::where('teaching_load_id', $load->teaching_load_id)->delete();
                    
                    // Track attendance record deletions for sync
                    if (!empty($attendanceRecordIds)) {
                        $syncService = app(CloudSyncService::class);
                        foreach ($attendanceRecordIds as $recordId) {
                            $syncService->trackDeletion('tbl_attendance_record', $recordId);
                        }
                    }

                    // Delete the original teaching load
                    $loadId = $load->teaching_load_id;
                    $load->delete();
                    
                    // Track deletion for sync
                    $syncService = app(CloudSyncService::class);
                    $syncService->trackDeletion('tbl_teaching_load', $loadId);
                    
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

            // Delete from archive (restore operation - don't track as deletion since it's being restored)
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
            
            // Get archived attendance record IDs before deletion (for tracking)
            $archivedAttendanceIds = AttendanceRecordArchive::where('teaching_load_id', $archivedLoad->archive_id)
                ->pluck('archive_id')
                ->toArray();
            
            // Delete corresponding archived attendance records
            AttendanceRecordArchive::where('teaching_load_id', $archivedLoad->archive_id)->delete();
            
            // Track archived attendance record deletions for sync
            if (!empty($archivedAttendanceIds)) {
                $syncService = app(CloudSyncService::class);
                foreach ($archivedAttendanceIds as $attendanceArchiveId) {
                    $syncService->trackDeletion('tbl_attendance_record_archive', $attendanceArchiveId);
                }
            }
            
            $teachingLoadArchiveId = $archivedLoad->archive_id;
            $archivedLoad->delete();

            // Track deletion for sync
            $syncService = app(CloudSyncService::class);
            $syncService->trackDeletion('tbl_teaching_load_archive', $teachingLoadArchiveId);

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
