<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AttendanceRecordArchive;
use App\Models\AttendanceRecord;
use App\Models\TeachingLoadArchive;
use App\Models\ActivityLog;
use App\Services\CloudSyncService;
use Illuminate\Support\Facades\Auth;

class AttendanceRecordArchiveController extends Controller
{
    /**
     * View archived attendance records for DeptHead
     */
    public function index()
    {
        try {
            $archivedRecords = AttendanceRecordArchive::with(['faculty', 'teachingLoadArchive', 'camera.room', 'archivedBy'])
                ->orderBy('school_year', 'desc')
                ->orderBy('semester', 'desc')
                ->orderBy('archived_at', 'desc')
                ->get();

            \Log::info("Retrieved " . $archivedRecords->count() . " archived attendance records");

            return view('deptHead.archived-attendance-records', compact('archivedRecords'));
        } catch (\Exception $e) {
            \Log::error("Error retrieving archived attendance records: " . $e->getMessage());
            return redirect()->back()->withErrors(['error' => 'Error loading archived attendance records: ' . $e->getMessage()]);
        }
    }

    /**
     * View archived attendance records for Admin
     */
    public function index1()
    {
        try {
            $archivedRecords = AttendanceRecordArchive::with(['faculty', 'teachingLoadArchive', 'camera.room', 'archivedBy'])
                ->orderBy('school_year', 'desc')
                ->orderBy('semester', 'desc')
                ->orderBy('archived_at', 'desc')
                ->get();

            \Log::info("Retrieved " . $archivedRecords->count() . " archived attendance records");

            return view('admin.archived-attendance-records', compact('archivedRecords'));
        } catch (\Exception $e) {
            \Log::error("Error retrieving archived attendance records: " . $e->getMessage());
            return redirect()->back()->withErrors(['error' => 'Error loading archived attendance records: ' . $e->getMessage()]);
        }
    }

    /**
     * View archived attendance records for Checker
     */
    public function index2()
    {
        try {
            $archivedRecords = AttendanceRecordArchive::with(['faculty', 'teachingLoadArchive', 'camera.room', 'archivedBy'])
                ->orderBy('school_year', 'desc')
                ->orderBy('semester', 'desc')
                ->orderBy('archived_at', 'desc')
                ->get();

            \Log::info("Retrieved " . $archivedRecords->count() . " archived attendance records");

            return view('checker.archived-attendance-records', compact('archivedRecords'));
        } catch (\Exception $e) {
            \Log::error("Error retrieving archived attendance records: " . $e->getMessage());
            return redirect()->back()->withErrors(['error' => 'Error loading archived attendance records: ' . $e->getMessage()]);
        }
    }

    /**
     * Restore an attendance record from archive
     */
    public function restore($archiveId)
    {
        try {
            $archivedRecord = AttendanceRecordArchive::findOrFail($archiveId);

            // Create new attendance record
            AttendanceRecord::create([
                'faculty_id' => $archivedRecord->faculty_id,
                'teaching_load_id' => $archivedRecord->teaching_load_id,
                'camera_id' => $archivedRecord->camera_id,
                'record_date' => $archivedRecord->record_date,
                'record_time_in' => $archivedRecord->record_time_in,
                'record_time_out' => $archivedRecord->record_time_out,
                'time_duration_seconds' => $archivedRecord->time_duration_seconds,
                'record_status' => $archivedRecord->record_status,
                'record_remarks' => $archivedRecord->record_remarks,
            ]);

            // Log the action
            ActivityLog::create([
                'user_id' => Auth::id(),
                'logs_action' => 'RESTORE',
                'logs_description' => "Restored attendance record from archive: {$archivedRecord->record_date}",
                'logs_module' => 'Attendance Records Management',
            ]);

            // Delete from archive
            $archivedRecord->delete();

            return response()->json([
                'success' => true,
                'message' => 'Attendance record restored successfully!'
            ]);

        } catch (\Exception $e) {
            \Log::error("Restore attendance record error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error restoring attendance record: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Permanently delete archived attendance record
     */
    public function permanentlyDelete($archiveId)
    {
        try {
            $archivedRecord = AttendanceRecordArchive::findOrFail($archiveId);
            $recordDate = $archivedRecord->record_date;
            $archiveIdValue = $archivedRecord->archive_id;
            
            $archivedRecord->delete();

            // Track deletion for sync
            $syncService = app(CloudSyncService::class);
            $syncService->trackDeletion('tbl_attendance_record_archive', $archiveIdValue);

            // Log the action
            ActivityLog::create([
                'user_id' => Auth::id(),
                'logs_action' => 'DELETE',
                'logs_description' => "Permanently deleted archived attendance record: {$recordDate}",
                'logs_module' => 'Attendance Records Management',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Archived attendance record permanently deleted!'
            ]);

        } catch (\Exception $e) {
            \Log::error("Permanently delete archived attendance record error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error deleting archived attendance record: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Archive all attendance records (Admin)
     */
    public function archiveAll(Request $request)
    {
        try {
            $schoolYear = $request->input('school_year');
            $semester = $request->input('semester');
            $archiveNotes = $request->input('archive_notes');

            $records = AttendanceRecord::with(['faculty', 'teachingLoad', 'camera'])
                ->get();

            if ($records->isEmpty()) {
                return redirect()->back()->withErrors(['error' => 'No attendance records to archive.']);
            }

            foreach ($records as $record) {
                AttendanceRecordArchive::create([
                    'faculty_id' => $record->faculty_id,
                    'teaching_load_id' => $record->teaching_load_id,
                    'camera_id' => $record->camera_id,
                    'record_date' => $record->record_date,
                    'record_time_in' => $record->record_time_in,
                    'record_time_out' => $record->record_time_out,
                    'time_duration_seconds' => $record->time_duration_seconds,
                    'record_status' => $record->record_status,
                    'record_remarks' => $record->record_remarks,
                    'school_year' => $schoolYear ?: ($record->school_year ?? null),
                    'semester' => $semester ?: ($record->semester ?? null),
                    'archived_at' => now(),
                    'archived_by' => Auth::id(),
                ]);
            }

            // Log action
            ActivityLog::create([
                'user_id' => Auth::id(),
                'logs_action' => 'ARCHIVE',
                'logs_description' => 'Archived all attendance records' . ($schoolYear || $semester ? (" for $schoolYear $semester") : ''),
                'logs_module' => 'Attendance Records Management',
            ]);

            // Get all record IDs before deletion (for tracking)
            $recordIds = AttendanceRecord::pluck('record_id')->toArray();
            
            // Delete original records after archiving
            AttendanceRecord::query()->delete();
            
            // Track all deletions for sync
            if (!empty($recordIds)) {
                $syncService = app(CloudSyncService::class);
                foreach ($recordIds as $recordId) {
                    $syncService->trackDeletion('tbl_attendance_record', $recordId);
                }
            }

            return redirect()->route('admin.attendance.records.archived')
                ->with('success', 'All attendance records archived successfully.');
        } catch (\Exception $e) {
            \Log::error('Archive all attendance records error: ' . $e->getMessage());
            return redirect()->back()->withErrors(['error' => 'Error archiving attendance records: ' . $e->getMessage()]);
        }
    }

    /**
     * Print archived attendance records as PDF (Admin)
     */
    public function print(Request $request)
    {
        $query = AttendanceRecordArchive::with(['faculty', 'teachingLoadArchive', 'camera.room']);

        // School Year filter
        if ($request->schoolYear) {
            $query->where('school_year', $request->schoolYear);
        }

        // Semester filter
        if ($request->semester) {
            $query->where('semester', $request->semester);
        }

        // Date filters
        if ($request->startDate) {
            $query->whereDate('record_date', '>=', $request->startDate);
        }
        if ($request->endDate) {
            $query->whereDate('record_date', '<=', $request->endDate);
        }
        
        // Department filter
        if ($request->department) {
            $query->whereHas('faculty', function($q) use ($request) {
                $q->where('faculty_department', $request->department);
            });
        }
        
        // Instructor filter
        if ($request->instructor || $request->faculty) {
            $facultyId = $request->instructor ?? $request->faculty;
            $query->where('faculty_id', $facultyId);
        }
        
        // Course code filter
        $courseCodeParam = $request->courseCode ?? $request->course_code;
        if ($courseCodeParam) {
            $query->whereHas('teachingLoadArchive', function($q) use ($courseCodeParam) {
                $q->where('teaching_load_course_code', $courseCodeParam);
            });
        }
        
        // Subject filter
        if ($request->subject) {
            $query->whereHas('teachingLoadArchive', function($q) use ($request) {
                $q->where('teaching_load_subject', $request->subject);
            });
        }
        
        // Day of week filter
        if ($request->day) {
            $query->whereHas('teachingLoadArchive', function($q) use ($request) {
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
                // Search in teaching load archive (course code and subject)
                ->orWhereHas('teachingLoadArchive', function($teachingQuery) use ($searchTerm) {
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

        $records = $query->orderBy('record_date', 'asc')->orderBy('record_time_in', 'asc')->get();

        $pdf = \PDF::loadView('admin.archived-attendance-records-pdf', [
            'records' => $records,
            'generatedAt' => now('Asia/Manila'),
            'generatedBy' => auth()->user()->name ?? 'Administrator',
            'schoolYear' => $request->schoolYear ?? null,
            'semester' => $request->semester ?? null,
            'dateFrom' => $request->startDate ?? null,
            'dateTo' => $request->endDate ?? null,
            'department' => $request->department ?? null,
            'subject' => $request->subject ?? null,
            'faculty' => ($request->instructor || $request->faculty) ? \App\Models\Faculty::find($request->instructor ?? $request->faculty)?->faculty_fname . ' ' . \App\Models\Faculty::find($request->instructor ?? $request->faculty)?->faculty_lname : null,
            'status' => $request->status ?? null,
            'room' => $request->room ?? null,
            'courseCode' => $courseCodeParam ?? null,
            'day' => $request->day ?? null,
            'building' => $request->building ?? null,
            'remarks' => $request->remarks ?? null,
            'search' => $request->search ?? null,
        ])->setPaper('a4', 'landscape')
          ->setOptions([
              'isHtml5ParserEnabled' => true,
              'isRemoteEnabled' => true,
              'defaultFont' => 'DejaVu Sans'
          ]);

        return $pdf->download('archived-attendance-records-report-' . now()->format('Y-m-d') . '.pdf');
    }
}