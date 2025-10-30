<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * SyncReceiverController
 * 
 * This controller receives data from local development server
 * and stores it in the Hostinger cloud database.
 * 
 * Used when: Local server syncs to Hostinger production
 */
class SyncReceiverController extends Controller
{
    /**
     * Get all existing room IDs
     */
    public function getRooms()
    {
        try {
            $rooms = DB::table('tbl_room')->select('room_no')->get();
            return response()->json($rooms);
        } catch (\Exception $e) {
            Log::error('Error getting rooms: ' . $e->getMessage());
            return response()->json([], 500);
        }
    }
    
    /**
     * Receive and store room data
     */
    public function receiveRoom(Request $request)
    {
        try {
            $validated = $request->validate([
                'room_no' => 'required|integer',
                'room_name' => 'required|string|max:50',
                'room_building_no' => 'required|string|max:50',
            ]);
            
            DB::table('tbl_room')->updateOrInsert(
                ['room_no' => $validated['room_no']],
                $validated
            );
            
            Log::info("Synced room {$validated['room_no']} from local server");
            
            return response()->json([
                'success' => true,
                'message' => 'Room synced successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error receiving room: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get all existing camera IDs
     */
    public function getCameras()
    {
        try {
            $cameras = DB::table('tbl_camera')->select('camera_id')->get();
            return response()->json($cameras);
        } catch (\Exception $e) {
            Log::error('Error getting cameras: ' . $e->getMessage());
            return response()->json([], 500);
        }
    }
    
    /**
     * Receive and store camera data
     */
    public function receiveCamera(Request $request)
    {
        try {
            $validated = $request->validate([
                'camera_id' => 'required|integer',
                'camera_name' => 'required|string|max:50',
                'camera_ip_address' => 'required|string|max:50',
                'camera_username' => 'required|string|max:50',
                'camera_password' => 'required|string|max:50',
                'camera_live_feed' => 'required|string|max:255',
                'room_no' => 'required|integer',
            ]);
            
            DB::table('tbl_camera')->updateOrInsert(
                ['camera_id' => $validated['camera_id']],
                $validated
            );
            
            Log::info("Synced camera {$validated['camera_id']} from local server");
            
            return response()->json([
                'success' => true,
                'message' => 'Camera synced successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error receiving camera: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get all existing faculty IDs
     */
    public function getFaculties()
    {
        try {
            $faculties = DB::table('tbl_faculty')->select('faculty_id')->get();
            return response()->json($faculties);
        } catch (\Exception $e) {
            Log::error('Error getting faculties: ' . $e->getMessage());
            return response()->json([], 500);
        }
    }
    
    /**
     * Receive and store faculty data
     */
    public function receiveFaculty(Request $request)
    {
        try {
            $validated = $request->validate([
                'faculty_id' => 'required|integer',
                'faculty_fname' => 'required|string|max:50',
                'faculty_lname' => 'required|string|max:50',
                'faculty_department' => 'required|string|max:50',
                'faculty_face_embedding' => 'nullable|string',
                'faculty_images' => 'required|string',
                'created_at' => 'nullable|date',
                'updated_at' => 'nullable|date',
            ]);
            
            DB::table('tbl_faculty')->updateOrInsert(
                ['faculty_id' => $validated['faculty_id']],
                $validated
            );
            
            Log::info("Synced faculty {$validated['faculty_id']} from local server");
            
            return response()->json([
                'success' => true,
                'message' => 'Faculty synced successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error receiving faculty: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get all existing teaching load IDs
     */
    public function getTeachingLoads()
    {
        try {
            $loads = DB::table('tbl_teaching_load')->select('teaching_load_id')->get();
            return response()->json($loads);
        } catch (\Exception $e) {
            Log::error('Error getting teaching loads: ' . $e->getMessage());
            return response()->json([], 500);
        }
    }
    
    /**
     * Receive and store teaching load data
     */
    public function receiveTeachingLoad(Request $request)
    {
        try {
            $validated = $request->validate([
                'teaching_load_id' => 'required|integer',
                'faculty_id' => 'required|integer',
                'teaching_load_course_code' => 'required|string|max:50',
                'teaching_load_subject' => 'required|string|max:50',
                'teaching_load_day_of_week' => 'required|string|max:50',
                'teaching_load_class_section' => 'required|string|max:50',
                'teaching_load_time_in' => 'required',
                'teaching_load_time_out' => 'required',
                'room_no' => 'required|integer',
                'created_at' => 'nullable|date',
                'updated_at' => 'nullable|date',
            ]);
            
            DB::table('tbl_teaching_load')->updateOrInsert(
                ['teaching_load_id' => $validated['teaching_load_id']],
                $validated
            );
            
            Log::info("Synced teaching load {$validated['teaching_load_id']} from local server");
            
            return response()->json([
                'success' => true,
                'message' => 'Teaching load synced successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error receiving teaching load: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get existing attendance record IDs (filtered by days)
     */
    public function getAttendanceRecords(Request $request)
    {
        try {
            // Return ALL attendance record IDs
            $records = DB::table('tbl_attendance_record')
                ->select('record_id')
                ->get();
            
            return response()->json($records);
        } catch (\Exception $e) {
            Log::error('Error getting attendance records: ' . $e->getMessage());
            return response()->json([], 500);
        }
    }
    
    /**
     * Receive and store attendance record data
     */
    public function receiveAttendanceRecord(Request $request)
    {
        try {
            $validated = $request->validate([
                'record_id' => 'required|integer',
                'faculty_id' => 'required|integer',
                'teaching_load_id' => 'required|integer',
                'camera_id' => 'required|integer',
                'record_date' => 'required',
                'record_time_in' => 'nullable',
                'record_time_out' => 'nullable',
                'record_status' => 'required|string|max:50',
                'record_remarks' => 'required|string|max:50',
                'time_duration_seconds' => 'required|integer',
            ]);
            
            DB::table('tbl_attendance_record')->updateOrInsert(
                ['record_id' => $validated['record_id']],
                $validated
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Attendance record synced successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error receiving attendance record: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get existing leave/pass IDs (filtered by days)
     * Note: Leaves and passes are in the same table (tbl_leave_pass)
     */
    public function getLeaves(Request $request)
    {
        try {
            // Return ALL leave IDs
            $leaves = DB::table('tbl_leave_pass')
                ->select('lp_id')
                ->get();
            
            return response()->json($leaves);
        } catch (\Exception $e) {
            Log::error('Error getting leaves: ' . $e->getMessage());
            return response()->json([], 500);
        }
    }
    
    /**
     * Receive and store leave/pass data
     * Note: Both leaves and passes use the same table (tbl_leave_pass)
     */
    public function receiveLeave(Request $request)
    {
        try {
            $validated = $request->validate([
                'lp_id' => 'required|integer',
                'faculty_id' => 'required|integer',
                'lp_type' => 'required|string|max:50',
                'lp_purpose' => 'required|string|max:255',
                'pass_slip_itinerary' => 'nullable|string|max:50',
                'pass_slip_date' => 'nullable|date',
                'pass_slip_departure_time' => 'nullable',
                'pass_slip_arrival_time' => 'nullable',
                'leave_start_date' => 'nullable|date',
                'leave_end_date' => 'nullable|date',
                'lp_image' => 'required|string|max:255',
                'created_at' => 'nullable|date',
                'updated_at' => 'nullable|date',
            ]);
            
            DB::table('tbl_leave_pass')->updateOrInsert(
                ['lp_id' => $validated['lp_id']],
                $validated
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Leave/Pass synced successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error receiving leave/pass: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get existing pass IDs (same as getLeaves since they share table)
     */
    public function getPasses(Request $request)
    {
        // Passes and leaves are in the same table
        return $this->getLeaves($request);
    }
    
    /**
     * Receive and store pass data (same as receiveLeave since they share table)
     */
    public function receivePass(Request $request)
    {
        // Passes and leaves are in the same table
        return $this->receiveLeave($request);
    }
    
    /**
     * Get existing recognition log IDs (filtered by days)
     */
    public function getRecognitionLogs(Request $request)
    {
        try {
            // Return ALL recognition log IDs
            $logs = DB::table('tbl_recognition_logs')
                ->select('log_id')
                ->get();
            
            return response()->json($logs);
        } catch (\Exception $e) {
            Log::error('Error getting recognition logs: ' . $e->getMessage());
            return response()->json([], 500);
        }
    }
    
    /**
     * Receive and store recognition log data
     */
    public function receiveRecognitionLog(Request $request)
    {
        try {
            $validated = $request->validate([
                'log_id' => 'required|integer',
                'recognition_time' => 'required',
                'camera_name' => 'required|string|max:100',
                'room_name' => 'required|string|max:100',
                'building_no' => 'required|string|max:50',
                'faculty_name' => 'required|string|max:200',
                'status' => 'required|string|max:50',
                'distance' => 'nullable|numeric',
                'faculty_id' => 'nullable|integer',
                'camera_id' => 'nullable|integer',
                'teaching_load_id' => 'nullable|integer',
            ]);
            
            DB::table('tbl_recognition_logs')->updateOrInsert(
                ['log_id' => $validated['log_id']],
                $validated
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Recognition log synced successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error receiving recognition log: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get existing stream recording IDs (filtered by days)
     */
    public function getStreamRecordings(Request $request)
    {
        try {
            // Return ALL stream recording IDs
            $recordings = DB::table('tbl_stream_recordings')
                ->select('recording_id')
                ->get();
            
            return response()->json($recordings);
        } catch (\Exception $e) {
            Log::error('Error getting stream recordings: ' . $e->getMessage());
            return response()->json([], 500);
        }
    }
    
    /**
     * Receive and store stream recording data
     */
    public function receiveStreamRecording(Request $request)
    {
        try {
            $validated = $request->validate([
                'recording_id' => 'required|integer',
                'camera_id' => 'required|integer',
                'filename' => 'required|string|max:255',
                'filepath' => 'required|string|max:500',
                'start_time' => 'required',
                'duration' => 'required|integer',
                'frames' => 'required|integer',
                'file_size' => 'required|integer',
                'created_at' => 'nullable|date',
                'updated_at' => 'nullable|date',
            ]);
            
            DB::table('tbl_stream_recordings')->updateOrInsert(
                ['recording_id' => $validated['recording_id']],
                $validated
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Stream recording synced successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error receiving stream recording: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Receive file upload
     */
    public function receiveFileUpload(Request $request, $directory)
    {
        try {
            $request->validate([
                'file' => 'required|file|max:102400', // 100MB max
            ]);
            
            $file = $request->file('file');
            $filename = time() . '_' . $file->getClientOriginalName();
            
            // Store in public/sync/{directory}
            $path = $file->storeAs("sync/{$directory}", $filename, 'public');
            
            // Generate URL
            $url = asset("storage/{$path}");
            
            Log::info("Uploaded file to {$path}");
            
            return response()->json([
                'success' => true,
                'url' => $url,
                'path' => $path,
                'filename' => $filename
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error uploading file: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    // ============================================================================
    // SUBJECTS ENDPOINTS
    // ============================================================================
    
    /**
     * Get existing subject IDs
     */
    public function getSubjects(Request $request)
    {
        try {
            $subjects = DB::table('tbl_subject')->select('subject_id')->get();
            return response()->json($subjects);
        } catch (\Exception $e) {
            Log::error('Error getting subjects: ' . $e->getMessage());
            return response()->json([], 500);
        }
    }
    
    /**
     * Receive and store subject data
     */
    public function receiveSubject(Request $request)
    {
        try {
            $validated = $request->validate([
                'subject_id' => 'required|integer',
                'subject_code' => 'required|string|max:100',
                'subject_description' => 'required|string|max:255',
                'department' => 'required|string|max:255',
                'created_at' => 'nullable|date',
                'updated_at' => 'nullable|date',
            ]);
            
            DB::table('tbl_subject')->updateOrInsert(
                ['subject_id' => $validated['subject_id']],
                $validated
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Subject synced successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error receiving subject: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    // ============================================================================
    // USERS ENDPOINTS
    // ============================================================================
    
    /**
     * Get existing user IDs
     */
    public function getUsers(Request $request)
    {
        try {
            $users = DB::table('tbl_user')->select('user_id')->get();
            return response()->json($users);
        } catch (\Exception $e) {
            Log::error('Error getting users: ' . $e->getMessage());
            return response()->json([], 500);
        }
    }
    
    /**
     * Receive and store user data
     */
    public function receiveUser(Request $request)
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|integer',
                'user_role' => 'required|string|max:50',
                'user_department' => 'required|string|max:50',
                'user_fname' => 'required|string|max:50',
                'user_lname' => 'required|string|max:50',
                'username' => 'required|string|max:50',
                'user_password' => 'required|string|max:255',
                'created_at' => 'nullable|date',
                'updated_at' => 'nullable|date',
            ]);
            
            DB::table('tbl_user')->updateOrInsert(
                ['user_id' => $validated['user_id']],
                $validated
            );
            
            return response()->json([
                'success' => true,
                'message' => 'User synced successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error receiving user: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    // ============================================================================
    // ACTIVITY LOGS ENDPOINTS
    // ============================================================================
    
    /**
     * Get existing activity log IDs (filtered by days)
     */
    public function getActivityLogs(Request $request)
    {
        try {
            // Return ALL activity log IDs
            $logs = DB::table('tbl_activity_logs')
                ->select('logs_id')
                ->get();
            
            return response()->json($logs);
        } catch (\Exception $e) {
            Log::error('Error getting activity logs: ' . $e->getMessage());
            return response()->json([], 500);
        }
    }
    
    /**
     * Receive and store activity log data
     */
    public function receiveActivityLog(Request $request)
    {
        try {
            $validated = $request->validate([
                'logs_id' => 'required|integer',
                'user_id' => 'required|integer',
                'logs_action' => 'required|string|max:50',
                'logs_description' => 'required|string',
                'logs_timestamp' => 'required',
                'logs_module' => 'required|string',
            ]);
            
            DB::table('tbl_activity_logs')->updateOrInsert(
                ['logs_id' => $validated['logs_id']],
                $validated
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Activity log synced successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error receiving activity log: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    // ============================================================================
    // TEACHING LOAD ARCHIVES ENDPOINTS
    // ============================================================================
    
    /**
     * Get existing teaching load archive IDs
     */
    public function getTeachingLoadArchives(Request $request)
    {
        try {
            $archives = DB::table('tbl_teaching_load_archive')->select('archive_id')->get();
            return response()->json($archives);
        } catch (\Exception $e) {
            Log::error('Error getting teaching load archives: ' . $e->getMessage());
            return response()->json([], 500);
        }
    }
    
    /**
     * Receive and store teaching load archive data
     */
    public function receiveTeachingLoadArchive(Request $request)
    {
        try {
            $validated = $request->validate([
                'archive_id' => 'required|integer',
                'original_teaching_load_id' => 'required|integer',
                'faculty_id' => 'required|integer',
                'teaching_load_course_code' => 'required|string|max:50',
                'teaching_load_subject' => 'required|string|max:50',
                'teaching_load_class_section' => 'required|string|max:50',
                'teaching_load_day_of_week' => 'required|string|max:50',
                'teaching_load_time_in' => 'required',
                'teaching_load_time_out' => 'required',
                'room_no' => 'required|string|max:50',
                'school_year' => 'required|string|max:20',
                'semester' => 'required|string|max:20',
                'archived_at' => 'required',
                'archived_by' => 'required|integer',
                'archive_notes' => 'nullable|string',
            ]);
            
            DB::table('tbl_teaching_load_archive')->updateOrInsert(
                ['archive_id' => $validated['archive_id']],
                $validated
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Teaching load archive synced successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error receiving teaching load archive: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    // ============================================================================
    // ATTENDANCE RECORD ARCHIVES ENDPOINTS
    // ============================================================================
    
    /**
     * Get existing attendance record archive IDs
     */
    public function getAttendanceRecordArchives(Request $request)
    {
        try {
            $archives = DB::table('tbl_attendance_record_archive')->select('archive_id')->get();
            return response()->json($archives);
        } catch (\Exception $e) {
            Log::error('Error getting attendance record archives: ' . $e->getMessage());
            return response()->json([], 500);
        }
    }
    
    /**
     * Receive and store attendance record archive data
     */
    public function receiveAttendanceRecordArchive(Request $request)
    {
        try {
            $validated = $request->validate([
                'archive_id' => 'required|integer',
                'original_record_id' => 'nullable|integer',
                'faculty_id' => 'required|integer',
                'teaching_load_id' => 'required|integer',
                'camera_id' => 'required|integer',
                'record_date' => 'required|date',
                'record_time_in' => 'nullable',
                'record_time_out' => 'nullable',
                'time_duration_seconds' => 'required|integer',
                'record_status' => 'required|string|max:50',
                'record_remarks' => 'nullable|string',
                'school_year' => 'required|string|max:20',
                'semester' => 'required|string|max:20',
                'archived_at' => 'required',
                'archived_by' => 'nullable|integer',
                'archive_notes' => 'nullable|string',
                'created_at' => 'nullable|date',
                'updated_at' => 'nullable|date',
            ]);
            
            DB::table('tbl_attendance_record_archive')->updateOrInsert(
                ['archive_id' => $validated['archive_id']],
                $validated
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Attendance record archive synced successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error receiving attendance record archive: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Sync status endpoint
     */
    public function getSyncStatus()
    {
        try {
            // Note: leaves and passes share tbl_leave_pass table
            $leavePassCount = DB::table('tbl_leave_pass')->count();
            $leavesCount = DB::table('tbl_leave_pass')->where('lp_type', 'Leave')->count();
            $passesCount = DB::table('tbl_leave_pass')->where('lp_type', 'Pass')->count();
            
            $counts = [
                'subjects' => DB::table('tbl_subject')->count(),
                'users' => DB::table('tbl_user')->count(),
                'rooms' => DB::table('tbl_room')->count(),
                'cameras' => DB::table('tbl_camera')->count(),
                'faculties' => DB::table('tbl_faculty')->count(),
                'teaching_loads' => DB::table('tbl_teaching_load')->count(),
                'attendance_records' => DB::table('tbl_attendance_record')->count(),
                'leaves' => $leavesCount,
                'passes' => $passesCount,
                'leave_pass_total' => $leavePassCount,
                'recognition_logs' => DB::table('tbl_recognition_logs')->count(),
                'stream_recordings' => DB::table('tbl_stream_recordings')->count(),
                'activity_logs' => DB::table('tbl_activity_logs')->count(),
                'teaching_load_archives' => DB::table('tbl_teaching_load_archive')->count(),
                'attendance_record_archives' => DB::table('tbl_attendance_record_archive')->count(),
            ];
            
            return response()->json([
                'status' => 'ok',
                'message' => 'Cloud server is ready',
                'server' => 'Hostinger',
                'counts' => $counts,
                'timestamp' => now()->toDateTimeString()
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting sync status: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
