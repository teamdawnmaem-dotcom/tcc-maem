<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Subject;
use App\Models\ActivityLog;
use App\Services\CloudSyncService;
use Illuminate\Support\Facades\Auth;

class SubjectController extends Controller
{
    public function index()
    {
        $subjects = Subject::orderBy('subject_code')->get();
        return view('deptHead.subject-management', compact('subjects'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'subject_code' => 'required|string|max:100',
            'subject_description' => 'required|string|max:255',
            'department' => 'required|string|max:255',
        ]);

        $subject = Subject::create($request->only(['subject_code', 'subject_description', 'department']));

        ActivityLog::create([
            'user_id' => Auth::id(),
            'logs_action' => 'CREATE',
            'logs_description' => 'Added subject: ' . $subject->subject_code,
            'logs_module' => 'Subject management',
        ]);

        return redirect()->route('deptHead.subject.management')->with('success', 'Subject added successfully!');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'subject_code' => 'required|string|max:100',
            'subject_description' => 'required|string|max:255',
            'department' => 'required|string|max:255',
        ]);

        $subject = Subject::findOrFail($id);
        $subject->update($request->only(['subject_code', 'subject_description', 'department']));

        ActivityLog::create([
            'user_id' => Auth::id(),
            'logs_action' => 'UPDATE',
            'logs_description' => 'Updated subject: ' . $subject->subject_code,
            'logs_module' => 'Subject management',
        ]);

        return redirect()->route('deptHead.subject.management')->with('update', 'Subject updated successfully!');
    }

    public function destroy($id)
    {
        $subject = Subject::findOrFail($id);
        $code = $subject->subject_code;
        $subjectId = $subject->subject_id;
        $subject->delete();

        // Track deletion for sync
        $syncService = app(CloudSyncService::class);
        $syncService->trackDeletion('tbl_subject', $subjectId);
        
        // NEW APPROACH: Immediately trigger deletion on cloud
        try {
            $syncService->triggerDeleteOnCloudByTable('tbl_subject', $subjectId);
        } catch (\Exception $e) {
            \Log::error("Failed to trigger subject deletion on cloud: " . $e->getMessage());
        }

        ActivityLog::create([
            'user_id' => Auth::id(),
            'logs_action' => 'DELETE',
            'logs_description' => 'Deleted subject: ' . $code,
            'logs_module' => 'Subject management',
        ]);

        return redirect()->route('deptHead.subject.management')->with('delete', 'Subject deleted successfully!');
    }

    /**
     * Check for duplicate subjects
     */
    public function checkDuplicate(Request $request)
    {
        $request->validate([
            'subject_code' => 'required|string|max:100',
            'subject_description' => 'required|string|max:255',
            'department' => 'required|string|max:255',
            'exclude_id' => 'nullable|integer',
        ]);

        $subjectCode = $request->subject_code;
        $subjectDescription = $request->subject_description;
        $department = $request->department;
        $excludeId = $request->exclude_id;

        $query = Subject::where('subject_code', $subjectCode)
            ->where('subject_description', $subjectDescription)
            ->where('department', $department);
        
        if ($excludeId) {
            $query->where('subject_id', '!=', $excludeId);
        }
        
        $existingSubject = $query->first();

        if ($existingSubject) {
            return response()->json([
                'is_duplicate' => true,
                'message' => "A subject with the same code ({$subjectCode}), description ({$subjectDescription}), and department ({$department}) already exists."
            ]);
        }

        return response()->json([
            'is_duplicate' => false,
            'message' => null
        ]);
    }

    /**
     * Handle CSV upload for batch subject creation
     */
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
            $successDetails = [];
            $processedRows = []; // Track processed rows to avoid duplicates within the same upload
            
            // Valid departments list
            $validDepartments = [
                'Department of Admin',
                'College of Information Technology',
                'College of Library and Information Science',
                'College of Criminology',
                'College of Arts and Sciences',
                'College of Hospitality Management',
                'College of Sociology',
                'College of Engineering',
                'College of Education',
                'College of Business Administration'
            ];
            
            foreach ($csvData as $index => $row) {
                try {
                    // Validate row has required columns
                    if (count($row) < 3) {
                        $errors[] = "Row " . ($index + 2) . ": Insufficient columns. Expected 3, got " . count($row);
                        $errorCount++;
                        continue;
                    }
                    
                    // Extract data from CSV row
                    $subjectCode = trim($row[0]);
                    $subjectDescription = trim($row[1]);
                    $department = trim($row[2]);
                    
                    // Validate required fields are not empty
                    if (empty($subjectCode) || empty($subjectDescription) || empty($department)) {
                        $errors[] = "Row " . ($index + 2) . ": All fields are required and cannot be empty";
                        $errorCount++;
                        continue;
                    }
                    
                    // Validate subject code length
                    if (strlen($subjectCode) > 100) {
                        $errors[] = "Row " . ($index + 2) . ": Subject code exceeds maximum length of 100 characters";
                        $errorCount++;
                        continue;
                    }
                    
                    // Validate subject description length
                    if (strlen($subjectDescription) > 255) {
                        $errors[] = "Row " . ($index + 2) . ": Subject description exceeds maximum length of 255 characters";
                        $errorCount++;
                        continue;
                    }
                    
                    // Validate department
                    if (!in_array($department, $validDepartments)) {
                        $errors[] = "Row " . ($index + 2) . ": Invalid department '{$department}'. Must be one of: " . implode(', ', $validDepartments);
                        $errorCount++;
                        continue;
                    }
                    
                    // Check for duplicates within the same CSV upload
                    $rowKey = strtolower($subjectCode) . '|' . strtolower($subjectDescription) . '|' . strtolower($department);
                    if (in_array($rowKey, $processedRows)) {
                        $errors[] = "Row " . ($index + 2) . ": Duplicate entry found within the same CSV file";
                        $errorCount++;
                        continue;
                    }
                    $processedRows[] = $rowKey;
                    
                    // Check for duplicates in database
                    $existingSubject = Subject::where('subject_code', $subjectCode)
                        ->where('subject_description', $subjectDescription)
                        ->where('department', $department)
                        ->first();
                    
                    if ($existingSubject) {
                        $errors[] = "Row " . ($index + 2) . ": Subject with code '{$subjectCode}', description '{$subjectDescription}', and department '{$department}' already exists";
                        $errorCount++;
                        continue;
                    }
                    
                    // Create subject
                    Subject::create([
                        'subject_code' => $subjectCode,
                        'subject_description' => $subjectDescription,
                        'department' => $department,
                    ]);
                    
                    // Add success details
                    $successDetails[] = "Row " . ($index + 2) . ": {$subjectCode} - {$subjectDescription} ({$department})";
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
                'logs_description' => "CSV upload completed: {$successCount} subjects added, {$errorCount} errors",
                'logs_module' => 'Subject management',
            ]);
            
            $message = "CSV upload completed!\n";
            $message .= "✅ Successfully added: {$successCount} subjects\n";
            
            // Add success details
            if ($successCount > 0) {
                $message .= "\nSuccess Details:\n";
                $message .= implode("\n", $successDetails);
            }
            
            if ($errorCount > 0) {
                $message .= "\n❌ Errors: {$errorCount} rows\n";
                $message .= "\nError Details:\n";
                $message .= implode("\n", $errors);
            }
            
            return redirect()->back()->with('success', $message);
            
        } catch (\Exception $e) {
            \Log::error("CSV upload error: " . $e->getMessage());
            return redirect()->back()->withErrors(['csv_file' => 'Error processing CSV file: ' . $e->getMessage()]);
        }
    }

    /**
     * Download CSV template for subjects
     */
    public function csvTemplate()
    {
        $csvContent = "Subject Code,Subject Description,Department\n";
        $csvContent .= "IT 101,Introduction to Computing,College of Information Technology\n";
        $csvContent .= "IT 102,Computer Programming 1,College of Information Technology\n";
        $csvContent .= "IT 103,Data Structures and Algorithms,College of Information Technology\n";
        $csvContent .= "ED 101,Principles of Education,College of Education\n";
        $csvContent .= "BA 101,Introduction to Business,College of Business Administration\n";
        
        $filename = 'subject_template_' . date('Y-m-d') . '.csv';
        
        return response($csvContent)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }
}


