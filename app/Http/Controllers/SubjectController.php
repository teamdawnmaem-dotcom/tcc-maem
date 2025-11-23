<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Subject;
use App\Models\ActivityLog;
use App\Services\CloudSyncService;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\RichText\RichText;

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
                    for ($col = 'A'; $col <= $highestColumn; $col++) {
                        $rowData[] = $worksheet->getCell($col . $row)->getValue();
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
                    // Calculate actual row number (Excel: row 6+ = index+6, CSV: row 2+ = index+2)
                    $actualRowNumber = (in_array(strtolower($extension), ['xlsx', 'xls'])) ? ($index + 6) : ($index + 2);
                    
                    // Validate row has required columns
                    if (count($row) < 3) {
                        $errors[] = "Row " . $actualRowNumber . ": Insufficient columns. Expected 3, got " . count($row);
                        $errorCount++;
                        continue;
                    }
                    
                    // Extract data from CSV row
                    $subjectCode = trim($row[0]);
                    $subjectDescription = trim($row[1]);
                    $department = trim($row[2]);
                    
                    // Validate required fields are not empty
                    if (empty($subjectCode) || empty($subjectDescription) || empty($department)) {
                        $errors[] = "Row " . $actualRowNumber . ": All fields are required and cannot be empty";
                        $errorCount++;
                        continue;
                    }
                    
                    // Validate subject code length
                    if (strlen($subjectCode) > 100) {
                        $errors[] = "Row " . $actualRowNumber . ": Subject code exceeds maximum length of 100 characters";
                        $errorCount++;
                        continue;
                    }
                    
                    // Validate subject description length
                    if (strlen($subjectDescription) > 255) {
                        $errors[] = "Row " . $actualRowNumber . ": Subject description exceeds maximum length of 255 characters";
                        $errorCount++;
                        continue;
                    }
                    
                    // Validate department
                    if (!in_array($department, $validDepartments)) {
                        $errors[] = "Row " . $actualRowNumber . ": Invalid department '{$department}'. Must be one of: " . implode(', ', $validDepartments);
                        $errorCount++;
                        continue;
                    }
                    
                    // Check for duplicates within the same CSV upload
                    $rowKey = strtolower($subjectCode) . '|' . strtolower($subjectDescription) . '|' . strtolower($department);
                    if (in_array($rowKey, $processedRows)) {
                        $errors[] = "Row " . $actualRowNumber . ": Duplicate entry found within the same file";
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
                        $errors[] = "Row " . $actualRowNumber . ": Subject with code '{$subjectCode}', description '{$subjectDescription}', and department '{$department}' already exists";
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
                    $successDetails[] = "Row " . $actualRowNumber . ": {$subjectCode} - {$subjectDescription} ({$department})";
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
                'logs_description' => "Excel upload completed: {$successCount} subjects added, {$errorCount} errors",
                'logs_module' => 'Subject management',
            ]);
            
            $message = "Excel upload completed!\n";
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
            \Log::error("Excel upload error: " . $e->getMessage());
            return redirect()->back()->withErrors(['csv_file' => 'Error processing Excel file: ' . $e->getMessage()]);
        }
    }

    /**
     * Download Excel template for subjects with professional formatting
     */
    public function excelTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Set institution header
        $sheet->setCellValue('A1', 'TAGOLOAN COMMUNITY COLLEGE');
        $sheet->mergeCells('A1:C1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1')->getFill()->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF8B0000'); // Maroon background
        $sheet->getStyle('A1')->getFont()->getColor()->setARGB('FFFFFFFF'); // White text
        $sheet->getRowDimension('1')->setRowHeight(30);
        
        // Set template title
        $sheet->setCellValue('A2', 'SUBJECT TEMPLATE');
        $sheet->mergeCells('A2:C2');
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getRowDimension('2')->setRowHeight(25);
        
        // Empty row
        $sheet->getRowDimension('3')->setRowHeight(10);
        
        // Set column headers
        $headers = ['Subject Code', 'Subject Description', 'Department'];
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
            'IT 101',
            'Introduction to Computing',
            'College of Information Technology'
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
        $exampleRange = 'A5:C5';
        $sheet->getStyle($exampleRange)->getFill()->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFC6EFCE'); // Light green (Excel "Good" style color)
        
        // Apply borders and formatting to a large range (up to row 1000) for auto-formatting new data
        $lastRow = 1000;
        $dataRange = 'A5:C' . $lastRow;
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
        $sheet->getColumnDimension('A')->setWidth(18); // Subject Code
        $sheet->getColumnDimension('B')->setWidth(40); // Subject Description
        $sheet->getColumnDimension('C')->setWidth(45); // Department
        
        // Freeze rows 1-5 (institution header, title, empty row, column headers, example row)
        $sheet->freezePane('A6');
        
        // Generate file
        $filename = 'subject_template_' . date('Y-m-d') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        
        $tempFile = tempnam(sys_get_temp_dir(), 'subject_template');
        $writer->save($tempFile);
        
        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}


