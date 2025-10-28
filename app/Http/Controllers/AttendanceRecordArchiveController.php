<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AttendanceRecordArchive;
use App\Models\AttendanceRecord;
use App\Models\TeachingLoadArchive;
use App\Models\ActivityLog;
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
            
            $archivedRecord->delete();

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
}