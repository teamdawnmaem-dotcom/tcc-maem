<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Services\CloudSyncService;
use App\Services\AttendanceRemarksService;

/**
 * LocalDeleteController
 * 
 * This controller handles deletions from cloud server
 * Called when cloud server deletes a record and needs to delete it on local
 */
class LocalDeleteController extends Controller
{
    /**
     * Delete a user (called from cloud server)
     */
    public function deleteUser($id)
    {
        try {
            $user = DB::table('tbl_user')->where('user_id', $id)->first();
            if (!$user) {
                return response()->json(['success' => false, 'error' => 'User not found'], 404);
            }
            
            DB::table('tbl_user')->where('user_id', $id)->delete();
            
            // Track deletion locally
            $syncService = app(CloudSyncService::class);
            $syncService->trackDeletion('tbl_user', $id);
            
            Log::info("Deleted user {$id} from local via API (called from cloud)");
            return response()->json(['success' => true, 'message' => 'User deleted successfully']);
        } catch (\Exception $e) {
            Log::error("Error deleting user {$id}: " . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Delete a subject (called from cloud server)
     */
    public function deleteSubject($id)
    {
        try {
            $subject = DB::table('tbl_subject')->where('subject_id', $id)->first();
            if (!$subject) {
                return response()->json(['success' => false, 'error' => 'Subject not found'], 404);
            }
            
            DB::table('tbl_subject')->where('subject_id', $id)->delete();
            
            // Track deletion locally
            $syncService = app(CloudSyncService::class);
            $syncService->trackDeletion('tbl_subject', $id);
            
            Log::info("Deleted subject {$id} from local via API (called from cloud)");
            return response()->json(['success' => true, 'message' => 'Subject deleted successfully']);
        } catch (\Exception $e) {
            Log::error("Error deleting subject {$id}: " . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Delete a room (called from cloud server)
     */
    public function deleteRoom($id)
    {
        try {
            $room = DB::table('tbl_room')->where('room_no', $id)->first();
            if (!$room) {
                return response()->json(['success' => false, 'error' => 'Room not found'], 404);
            }
            
            DB::table('tbl_room')->where('room_no', $id)->delete();
            
            // Track deletion locally
            $syncService = app(CloudSyncService::class);
            $syncService->trackDeletion('tbl_room', $id);
            
            Log::info("Deleted room {$id} from local via API (called from cloud)");
            return response()->json(['success' => true, 'message' => 'Room deleted successfully']);
        } catch (\Exception $e) {
            Log::error("Error deleting room {$id}: " . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Delete a camera (called from cloud server)
     */
    public function deleteCamera($id)
    {
        try {
            $camera = DB::table('tbl_camera')->where('camera_id', $id)->first();
            if (!$camera) {
                return response()->json(['success' => false, 'error' => 'Camera not found'], 404);
            }
            
            DB::table('tbl_camera')->where('camera_id', $id)->delete();
            
            // Track deletion locally
            $syncService = app(CloudSyncService::class);
            $syncService->trackDeletion('tbl_camera', $id);
            
            Log::info("Deleted camera {$id} from local via API (called from cloud)");
            return response()->json(['success' => true, 'message' => 'Camera deleted successfully']);
        } catch (\Exception $e) {
            Log::error("Error deleting camera {$id}: " . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Delete a faculty (called from cloud server)
     */
    public function deleteFaculty($id)
    {
        try {
            $faculty = DB::table('tbl_faculty')->where('faculty_id', $id)->first();
            if (!$faculty) {
                return response()->json(['success' => false, 'error' => 'Faculty not found'], 404);
            }
            
            // Delete faculty images if they exist
            if ($faculty->faculty_images) {
                $images = json_decode($faculty->faculty_images, true);
                if (is_array($images)) {
                    foreach ($images as $image) {
                        if ($image && Storage::disk('public')->exists($image)) {
                            Storage::disk('public')->delete($image);
                        }
                    }
                }
            }
            
            DB::table('tbl_faculty')->where('faculty_id', $id)->delete();
            
            // Track deletion locally
            $syncService = app(CloudSyncService::class);
            $syncService->trackDeletion('tbl_faculty', $id);
            
            Log::info("Deleted faculty {$id} from local via API (called from cloud)");
            return response()->json(['success' => true, 'message' => 'Faculty deleted successfully']);
        } catch (\Exception $e) {
            Log::error("Error deleting faculty {$id}: " . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Delete a teaching load (called from cloud server)
     */
    public function deleteTeachingLoad($id)
    {
        try {
            $load = DB::table('tbl_teaching_load')->where('teaching_load_id', $id)->first();
            if (!$load) {
                return response()->json(['success' => false, 'error' => 'Teaching load not found'], 404);
            }
            
            DB::table('tbl_teaching_load')->where('teaching_load_id', $id)->delete();
            
            // Track deletion locally
            $syncService = app(CloudSyncService::class);
            $syncService->trackDeletion('tbl_teaching_load', $id);
            
            Log::info("Deleted teaching load {$id} from local via API (called from cloud)");
            return response()->json(['success' => true, 'message' => 'Teaching load deleted successfully']);
        } catch (\Exception $e) {
            Log::error("Error deleting teaching load {$id}: " . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Delete a leave (called from cloud server)
     */
    public function deleteLeave($id)
    {
        try {
            $leave = DB::table('tbl_leave_pass')->where('lp_id', $id)->where('lp_type', 'Leave')->first();
            if (!$leave) {
                return response()->json(['success' => false, 'error' => 'Leave not found'], 404);
            }
            
            $facultyId = $leave->faculty_id;
            $start = $leave->leave_start_date;
            $end = $leave->leave_end_date;
            
            // Delete image if exists
            if ($leave->lp_image && Storage::disk('public')->exists($leave->lp_image)) {
                Storage::disk('public')->delete($leave->lp_image);
            }
            
            DB::table('tbl_leave_pass')->where('lp_id', $id)->where('lp_type', 'Leave')->delete();
            
            // Track deletion locally
            $syncService = app(CloudSyncService::class);
            $syncService->trackDeletion('tbl_leave_pass', $id, 90, ['lp_type' => 'Leave']);
            
            // Remove attendance records
            $remarksService = app(AttendanceRemarksService::class);
            $remarksService->removeLeaveAbsencesInWindow($facultyId, $start, $end);
            
            Log::info("Deleted leave {$id} from local via API (called from cloud)");
            return response()->json(['success' => true, 'message' => 'Leave deleted successfully']);
        } catch (\Exception $e) {
            Log::error("Error deleting leave {$id}: " . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Delete a pass (called from cloud server)
     */
    public function deletePass($id)
    {
        try {
            $pass = DB::table('tbl_leave_pass')->where('lp_id', $id)->where('lp_type', 'Pass')->first();
            if (!$pass) {
                return response()->json(['success' => false, 'error' => 'Pass not found'], 404);
            }
            
            $facultyId = $pass->faculty_id;
            $date = $pass->pass_slip_date;
            
            // Delete image if exists
            if ($pass->lp_image && Storage::disk('public')->exists($pass->lp_image)) {
                Storage::disk('public')->delete($pass->lp_image);
            }
            
            DB::table('tbl_leave_pass')->where('lp_id', $id)->where('lp_type', 'Pass')->delete();
            
            // Track deletion locally
            $syncService = app(CloudSyncService::class);
            $syncService->trackDeletion('tbl_leave_pass', $id, 90, ['lp_type' => 'Pass']);
            
            // Reconcile attendance
            $remarksService = app(AttendanceRemarksService::class);
            $remarksService->reconcilePassChange($facultyId, $date);
            
            Log::info("Deleted pass {$id} from local via API (called from cloud)");
            return response()->json(['success' => true, 'message' => 'Pass deleted successfully']);
        } catch (\Exception $e) {
            Log::error("Error deleting pass {$id}: " . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Delete an official matter (called from cloud server)
     */
    public function deleteOfficialMatter($id)
    {
        try {
            $om = DB::table('tbl_official_matters')->where('om_id', $id)->first();
            if (!$om) {
                return response()->json(['success' => false, 'error' => 'Official matter not found'], 404);
            }
            
            $facultyIds = [];
            if ($om->om_department) {
                if ($om->om_department === 'All Instructor') {
                    $facultyIds = DB::table('tbl_faculty')->pluck('faculty_id')->toArray();
                } else {
                    $facultyIds = DB::table('tbl_faculty')
                        ->where('faculty_department', $om->om_department)
                        ->pluck('faculty_id')
                        ->toArray();
                }
            } elseif ($om->faculty_id) {
                $facultyIds = [$om->faculty_id];
            }
            
            $startDate = $om->om_start_date;
            $endDate = $om->om_end_date;
            $remarks = $om->om_remarks;
            
            // Delete attachment if exists
            if ($om->om_attachment && Storage::disk('public')->exists($om->om_attachment)) {
                Storage::disk('public')->delete($om->om_attachment);
            }
            
            DB::table('tbl_official_matters')->where('om_id', $id)->delete();
            
            // Track deletion locally
            $syncService = app(CloudSyncService::class);
            $syncService->trackDeletion('tbl_official_matters', $id);
            
            // Remove attendance records
            if (!empty($facultyIds)) {
                $start = \Carbon\Carbon::parse($startDate);
                $end = \Carbon\Carbon::parse($endDate);
                $cursor = $start->copy();
                
                while ($cursor->lte($end)) {
                    $date = $cursor->toDateString();
                    $dayOfWeek = $cursor->format('l');
                    
                    foreach ($facultyIds as $fId) {
                        $teachingLoads = DB::table('tbl_teaching_load')
                            ->where('faculty_id', $fId)
                            ->where('teaching_load_day_of_week', $dayOfWeek)
                            ->get();
                        
                        foreach ($teachingLoads as $load) {
                            DB::table('tbl_attendance_record')
                                ->where('faculty_id', $fId)
                                ->where('teaching_load_id', $load->teaching_load_id)
                                ->whereDate('record_date', $date)
                                ->where('record_status', 'Absent')
                                ->where('record_remarks', $remarks)
                                ->delete();
                        }
                    }
                    $cursor->addDay();
                }
            }
            
            Log::info("Deleted official matter {$id} from local via API (called from cloud)");
            return response()->json(['success' => true, 'message' => 'Official matter deleted successfully']);
        } catch (\Exception $e) {
            Log::error("Error deleting official matter {$id}: " . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Delete an attendance record (called from cloud server)
     */
    public function deleteAttendanceRecord($id)
    {
        try {
            $record = DB::table('tbl_attendance_record')->where('record_id', $id)->first();
            if (!$record) {
                return response()->json(['success' => false, 'error' => 'Attendance record not found'], 404);
            }
            
            // Delete snapshots if they exist
            if ($record->time_in_snapshot && Storage::disk('public')->exists($record->time_in_snapshot)) {
                Storage::disk('public')->delete($record->time_in_snapshot);
            }
            if ($record->time_out_snapshot && Storage::disk('public')->exists($record->time_out_snapshot)) {
                Storage::disk('public')->delete($record->time_out_snapshot);
            }
            
            DB::table('tbl_attendance_record')->where('record_id', $id)->delete();
            
            // Track deletion locally
            $syncService = app(CloudSyncService::class);
            $syncService->trackDeletion('tbl_attendance_record', $id);
            
            Log::info("Deleted attendance record {$id} from local via API (called from cloud)");
            return response()->json(['success' => true, 'message' => 'Attendance record deleted successfully']);
        } catch (\Exception $e) {
            Log::error("Error deleting attendance record {$id}: " . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Delete a recognition log (called from cloud server)
     */
    public function deleteRecognitionLog($id)
    {
        try {
            $log = DB::table('tbl_recognition_logs')->where('log_id', $id)->first();
            if (!$log) {
                return response()->json(['success' => false, 'error' => 'Recognition log not found'], 404);
            }
            
            DB::table('tbl_recognition_logs')->where('log_id', $id)->delete();
            
            // Track deletion locally
            $syncService = app(CloudSyncService::class);
            $syncService->trackDeletion('tbl_recognition_logs', $id);
            
            Log::info("Deleted recognition log {$id} from local via API (called from cloud)");
            return response()->json(['success' => true, 'message' => 'Recognition log deleted successfully']);
        } catch (\Exception $e) {
            Log::error("Error deleting recognition log {$id}: " . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Delete a stream recording (called from cloud server)
     */
    public function deleteStreamRecording($id)
    {
        try {
            $recording = DB::table('tbl_stream_recordings')->where('recording_id', $id)->first();
            if (!$recording) {
                return response()->json(['success' => false, 'error' => 'Stream recording not found'], 404);
            }
            
            // Delete the physical file
            if ($recording->filepath && Storage::disk('public')->exists($recording->filepath)) {
                Storage::disk('public')->delete($recording->filepath);
            }
            
            DB::table('tbl_stream_recordings')->where('recording_id', $id)->delete();
            
            // Track deletion locally
            $syncService = app(CloudSyncService::class);
            $syncService->trackDeletion('tbl_stream_recordings', $id);
            
            Log::info("Deleted stream recording {$id} from local via API (called from cloud)");
            return response()->json(['success' => true, 'message' => 'Stream recording deleted successfully']);
        } catch (\Exception $e) {
            Log::error("Error deleting stream recording {$id}: " . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Delete an activity log (called from cloud server)
     */
    public function deleteActivityLog($id)
    {
        try {
            $log = DB::table('tbl_activity_logs')->where('logs_id', $id)->first();
            if (!$log) {
                return response()->json(['success' => false, 'error' => 'Activity log not found'], 404);
            }
            
            DB::table('tbl_activity_logs')->where('logs_id', $id)->delete();
            
            // Track deletion locally
            $syncService = app(CloudSyncService::class);
            $syncService->trackDeletion('tbl_activity_logs', $id);
            
            Log::info("Deleted activity log {$id} from local via API (called from cloud)");
            return response()->json(['success' => true, 'message' => 'Activity log deleted successfully']);
        } catch (\Exception $e) {
            Log::error("Error deleting activity log {$id}: " . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Delete a teaching load archive (called from cloud server)
     */
    public function deleteTeachingLoadArchive($id)
    {
        try {
            $archive = DB::table('tbl_teaching_load_archive')->where('archive_id', $id)->first();
            if (!$archive) {
                return response()->json(['success' => false, 'error' => 'Teaching load archive not found'], 404);
            }
            
            DB::table('tbl_teaching_load_archive')->where('archive_id', $id)->delete();
            
            // Track deletion locally
            $syncService = app(CloudSyncService::class);
            $syncService->trackDeletion('tbl_teaching_load_archive', $id);
            
            Log::info("Deleted teaching load archive {$id} from local via API (called from cloud)");
            return response()->json(['success' => true, 'message' => 'Teaching load archive deleted successfully']);
        } catch (\Exception $e) {
            Log::error("Error deleting teaching load archive {$id}: " . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Delete an attendance record archive (called from cloud server)
     */
    public function deleteAttendanceRecordArchive($id)
    {
        try {
            $archive = DB::table('tbl_attendance_record_archive')->where('archive_id', $id)->first();
            if (!$archive) {
                return response()->json(['success' => false, 'error' => 'Attendance record archive not found'], 404);
            }
            
            DB::table('tbl_attendance_record_archive')->where('archive_id', $id)->delete();
            
            // Track deletion locally
            $syncService = app(CloudSyncService::class);
            $syncService->trackDeletion('tbl_attendance_record_archive', $id);
            
            Log::info("Deleted attendance record archive {$id} from local via API (called from cloud)");
            return response()->json(['success' => true, 'message' => 'Attendance record archive deleted successfully']);
        } catch (\Exception $e) {
            Log::error("Error deleting attendance record archive {$id}: " . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}

