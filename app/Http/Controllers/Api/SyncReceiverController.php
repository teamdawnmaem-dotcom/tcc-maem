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
     * Get all existing rooms (full data)
     */
    public function getRooms()
    {
        try {
            $rooms = DB::table('tbl_room')->get();
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
            // Primary key is required for sync
            if (!$request->has('room_no') || !$request->input('room_no')) {
                return response()->json([
                    'success' => false,
                    'error' => 'room_no (primary key) is required for sync operations'
                ], 400);
            }
            
            $validated = $request->validate([
                'room_no' => 'required|integer|min:1', // Primary key required (BIGINT)
                'room_name' => 'required|string|max:50',
                'room_building_no' => 'required|string|max:50',
                'created_at' => 'nullable|date',
                'updated_at' => 'nullable|date',
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
     * Get all existing cameras (full data)
     */
    public function getCameras()
    {
        try {
            $cameras = DB::table('tbl_camera')->get();
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
            // Primary key is required for sync
            if (!$request->has('camera_id') || !$request->input('camera_id')) {
                return response()->json([
                    'success' => false,
                    'error' => 'camera_id (primary key) is required for sync operations'
                ], 400);
            }
            
            $validated = $request->validate([
                'camera_id' => 'required|integer|min:1', // Primary key required (BIGINT)
                'camera_name' => 'required|string|max:50',
                'camera_ip_address' => 'required|string|max:50',
                'camera_username' => 'required|string|max:50',
                'camera_password' => 'required|string|max:50',
                'camera_live_feed' => 'required|string|max:255',
                'room_no' => 'required|integer',
                'created_at' => 'nullable|date',
                'updated_at' => 'nullable|date',
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
     * Get all existing faculties (full data)
     */
    public function getFaculties()
    {
        try {
            $faculties = DB::table('tbl_faculty')->get();
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
            // Primary key is required for sync
            if (!$request->has('faculty_id') || !$request->input('faculty_id')) {
                return response()->json([
                    'success' => false,
                    'error' => 'faculty_id (primary key) is required for sync operations'
                ], 400);
            }
            
            $validated = $request->validate([
                'faculty_id' => 'required|integer|min:1', // Primary key required (BIGINT)
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
     * Get all existing teaching loads (full data)
     */
    public function getTeachingLoads()
    {
        try {
            $loads = DB::table('tbl_teaching_load')->get();
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
            // Primary key is required for sync
            if (!$request->has('teaching_load_id') || !$request->input('teaching_load_id')) {
                return response()->json([
                    'success' => false,
                    'error' => 'teaching_load_id (primary key) is required for sync operations'
                ], 400);
            }
            
            $validated = $request->validate([
                'teaching_load_id' => 'required|integer|min:1', // Primary key required (BIGINT)
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
     * Get existing attendance records (full data, filtered by days if provided)
     */
    public function getAttendanceRecords(Request $request)
    {
        try {
            $query = DB::table('tbl_attendance_record');
            
            // Filter by days if provided
            $days = $request->input('days');
            if ($days && is_numeric($days)) {
                $query->where('record_date', '>=', now()->subDays($days)->toDateString());
            }
            
            $records = $query->get();
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
            // Primary key is required for sync
            if (!$request->has('record_id') || !$request->input('record_id')) {
                return response()->json([
                    'success' => false,
                    'error' => 'record_id (primary key) is required for sync operations'
                ], 400);
            }
            
            $validated = $request->validate([
                'record_id' => 'required|integer|min:1', // Primary key required (BIGINT)
                'faculty_id' => 'required|integer',
                'teaching_load_id' => 'required|integer',
                'camera_id' => 'required|integer',
                'record_date' => 'required',
                'record_time_in' => 'nullable',
                'record_time_out' => 'nullable',
                'record_status' => 'required|string|max:50',
                'record_remarks' => 'required|string|max:50',
                'time_duration_seconds' => 'required|integer',
                'time_in_snapshot' => 'nullable|string|max:500',
                'time_out_snapshot' => 'nullable|string|max:500',
                'created_at' => 'nullable|date',
                'updated_at' => 'nullable|date',
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
     * Get existing leaves (full data, filtered by days if provided)
     * Note: Leaves and passes are in the same table (tbl_leave_pass)
     */
    public function getLeaves(Request $request)
    {
        try {
            $query = DB::table('tbl_leave_pass')
                ->where('lp_type', 'Leave');
            
            // Filter by days if provided
            $days = $request->input('days');
            if ($days && is_numeric($days)) {
                $query->where(function($q) use ($days) {
                    $q->where('leave_start_date', '>=', now()->subDays($days)->toDateString())
                      ->orWhere('leave_end_date', '>=', now()->subDays($days)->toDateString());
                });
            }
            
            $leaves = $query->get();
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
            // Primary key is required for sync
            if (!$request->has('lp_id') || !$request->input('lp_id')) {
                return response()->json([
                    'success' => false,
                    'error' => 'lp_id (primary key) is required for sync operations'
                ], 400);
            }
            
            $validated = $request->validate([
                'lp_id' => 'required|integer|min:1', // Primary key required (BIGINT)
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
            
            // Explicitly set timestamps if missing to ensure correct timezone
            // Use now() which respects APP_TIMEZONE, not MySQL's CURRENT_TIMESTAMP
            $now = now()->format('Y-m-d H:i:s');
            if (empty($validated['created_at'])) {
                $validated['created_at'] = $now;
            }
            if (empty($validated['updated_at'])) {
                $validated['updated_at'] = $now;
            }
            
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
     * Get existing passes (full data, filtered by days if provided)
     */
    public function getPasses(Request $request)
    {
        try {
            $query = DB::table('tbl_leave_pass')
                ->where('lp_type', 'Pass');
            
            // Filter by days if provided
            $days = $request->input('days');
            if ($days && is_numeric($days)) {
                $query->where(function($q) use ($days) {
                    $q->where('pass_slip_date', '>=', now()->subDays($days)->toDateString())
                      ->orWhere('created_at', '>=', now()->subDays($days)->toDateTimeString());
                });
            }
            
            $passes = $query->get();
            return response()->json($passes);
        } catch (\Exception $e) {
            Log::error('Error getting passes: ' . $e->getMessage());
            return response()->json([], 500);
        }
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
     * Get existing recognition logs (full data, filtered by days if provided)
     */
    public function getRecognitionLogs(Request $request)
    {
        try {
            $query = DB::table('tbl_recognition_logs');
            
            // Filter by days if provided
            $days = $request->input('days');
            if ($days && is_numeric($days)) {
                $query->where('recognition_time', '>=', now()->subDays($days)->toDateTimeString());
            }
            
            $logs = $query->get();
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
            // Primary key is required for sync
            if (!$request->has('log_id') || !$request->input('log_id')) {
                return response()->json([
                    'success' => false,
                    'error' => 'log_id (primary key) is required for sync operations. For creating new recognition logs, use the public endpoint (POST /api/recognition-logs without API key).'
                ], 400);
            }
            
            $validated = $request->validate([
                'log_id' => 'required|integer|min:1', // Primary key required (BIGINT)
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
                'created_at' => 'nullable|date',
                'updated_at' => 'nullable|date',
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
     * Get existing stream recordings (full data, filtered by days if provided)
     */
    public function getStreamRecordings(Request $request)
    {
        try {
            $query = DB::table('tbl_stream_recordings');
            
            // Filter by days if provided
            $days = $request->input('days');
            if ($days && is_numeric($days)) {
                $query->where('start_time', '>=', now()->subDays($days)->toDateTimeString());
            }
            
            $recordings = $query->get();
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
            // Primary key is required for sync
            if (!$request->has('recording_id') || !$request->input('recording_id')) {
                return response()->json([
                    'success' => false,
                    'error' => 'recording_id (primary key) is required for sync operations. For creating new recordings, use the public endpoint (POST /api/stream-recordings without API key).'
                ], 400);
            }
            
            $validated = $request->validate([
                'recording_id' => 'required|integer|min:1', // Primary key required (BIGINT(20))
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
                'file' => 'required|file', // No size limit - removed to allow large files
            ]);
            
            $file = $request->file('file');
            $filename = time() . '_' . $file->getClientOriginalName();
            
            // Store directly in storage/app/public/{directory} (no sync folder)
            $path = $file->storeAs($directory, $filename, 'public');
            
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

    /**
     * Bulk receive/upsert for any supported resource.
     * Path param {resource} must be one of the supported keys below.
     * Body: { records: [ { ...row }, ... ] }
     */
    public function receiveBulk(Request $request, string $resource)
    {
        $map = [
            'subjects' => [
                'table' => 'tbl_subject',
                'unique' => ['subject_id'],
                'columns' => ['subject_id','subject_code','subject_description','department','created_at','updated_at'],
            ],
            'users' => [
                'table' => 'tbl_user',
                'unique' => ['user_id'],
                'columns' => ['user_id','user_role','user_department','user_fname','user_lname','username','user_password','created_at','updated_at'],
            ],
            'rooms' => [
                'table' => 'tbl_room',
                'unique' => ['room_no'],
                'columns' => ['room_no','room_name','room_building_no','created_at','updated_at'],
            ],
            'cameras' => [
                'table' => 'tbl_camera',
                'unique' => ['camera_id'],
                'columns' => ['camera_id','camera_name','camera_ip_address','camera_username','camera_password','camera_live_feed','room_no','created_at','updated_at'],
            ],
            'faculties' => [
                'table' => 'tbl_faculty',
                'unique' => ['faculty_id'],
                'columns' => ['faculty_id','faculty_fname','faculty_lname','faculty_department','faculty_images','faculty_face_embedding','created_at','updated_at'],
            ],
            'teaching-loads' => [
                'table' => 'tbl_teaching_load',
                'unique' => ['teaching_load_id'],
                'columns' => ['teaching_load_id','faculty_id','teaching_load_course_code','teaching_load_subject','teaching_load_day_of_week','teaching_load_class_section','teaching_load_time_in','teaching_load_time_out','room_no','created_at','updated_at'],
            ],
            'attendance-records' => [
                'table' => 'tbl_attendance_record',
                'unique' => ['record_id'],
                'columns' => ['record_id','faculty_id','teaching_load_id','camera_id','record_date','record_time_in','record_time_out','record_status','record_remarks','time_duration_seconds','time_in_snapshot','time_out_snapshot','created_at','updated_at'],
            ],
            'leaves' => [
                'table' => 'tbl_leave_pass',
                'unique' => ['lp_id'],
                'columns' => ['lp_id','faculty_id','lp_type','lp_purpose','pass_slip_itinerary','pass_slip_date','pass_slip_departure_time','pass_slip_arrival_time','leave_start_date','leave_end_date','lp_image','created_at','updated_at'],
            ],
            'passes' => [
                'table' => 'tbl_leave_pass',
                'unique' => ['lp_id'],
                'columns' => ['lp_id','faculty_id','lp_type','lp_purpose','pass_slip_itinerary','pass_slip_date','pass_slip_departure_time','pass_slip_arrival_time','leave_start_date','leave_end_date','lp_image','created_at','updated_at'],
            ],
            'recognition-logs' => [
                'table' => 'tbl_recognition_logs',
                'unique' => ['log_id'],
                'columns' => ['log_id','recognition_time','camera_name','room_name','building_no','faculty_name','status','distance','faculty_id','camera_id','teaching_load_id','created_at','updated_at'],
            ],
            'stream-recordings' => [
                'table' => 'tbl_stream_recordings',
                'unique' => ['recording_id'],
                'columns' => ['recording_id','camera_id','filename','filepath','start_time','duration','frames','file_size','created_at','updated_at'],
            ],
            'activity-logs' => [
                'table' => 'tbl_activity_logs',
                'unique' => ['logs_id'],
                'columns' => ['logs_id','user_id','logs_action','logs_description','logs_timestamp','logs_module','created_at','updated_at'],
            ],
            'teaching-load-archives' => [
                'table' => 'tbl_teaching_load_archive',
                'unique' => ['archive_id'],
                'columns' => ['archive_id','original_teaching_load_id','faculty_id','teaching_load_course_code','teaching_load_subject','teaching_load_class_section','teaching_load_day_of_week','teaching_load_time_in','teaching_load_time_out','room_no','school_year','semester','archived_at','archived_by','archive_notes','created_at','updated_at'],
            ],
            'attendance-record-archives' => [
                'table' => 'tbl_attendance_record_archive',
                'unique' => ['archive_id'],
                'columns' => ['archive_id','original_record_id','faculty_id','teaching_load_id','camera_id','record_date','record_time_in','record_time_out','time_duration_seconds','record_status','record_remarks','school_year','semester','archived_at','archived_by','archive_notes','created_at','updated_at'],
            ],
            'official-matters' => [
                'table' => 'tbl_official_matters',
                'unique' => ['om_id'],
                'columns' => ['om_id','faculty_id','om_department','om_purpose','om_remarks','om_start_date','om_end_date','om_attachment','created_at','updated_at'],
            ],
        ];

        if (!isset($map[$resource])) {
            return response()->json(['success' => false, 'error' => 'Unknown resource'], 400);
        }

        try {
            $payload = $request->input('records', []);
            if (!is_array($payload)) {
                return response()->json(['success' => false, 'error' => 'Invalid payload'], 422);
            }

            $cfg = $map[$resource];
            // Filter columns to allowed list to prevent mass assignment of unknown fields
            // Also ensure primary key is present for each record
            $rows = [];
            foreach ($payload as $row) {
                if (!is_array($row)) { continue; }
                
                // Check if primary key(s) are present
                $missingPrimaryKey = false;
                foreach ($cfg['unique'] as $pk) {
                    if (!isset($row[$pk]) || empty($row[$pk])) {
                        $missingPrimaryKey = true;
                        break;
                    }
                }
                
                if ($missingPrimaryKey) {
                    // Skip records without primary keys
                    Log::warning("Bulk sync: Skipping record without primary key for resource {$resource}");
                    continue;
                }
                
                $filtered = array_intersect_key($row, array_flip($cfg['columns']));
                if (!empty($filtered)) { $rows[] = $filtered; }
            }

            if (empty($rows)) {
                return response()->json([
                    'success' => true, 
                    'message' => 'No records to upsert (all records must have primary keys)', 
                    'upserted' => 0
                ]);
            }

            // Use DB::table()->upsert for bulk idempotent insert/update
            // IMPORTANT: We explicitly set created_at and updated_at to preserve exact timestamps
            // When explicitly set in UPDATE, MySQL will use our values instead of ON UPDATE CURRENT_TIMESTAMP
            $updateCols = array_values(array_diff($cfg['columns'], $cfg['unique']));
            $total = 0;
            $errors = [];
            DB::beginTransaction();
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            try {
                foreach (array_chunk($rows, 500) as $chunk) {
                    // Ensure all records have created_at and updated_at if the table supports them
                    foreach ($chunk as &$row) {
                        // If created_at/updated_at are in allowed columns but missing, set to null (will use DB default)
                        // But we prefer to use the values from sync to preserve exact timestamps
                        if (in_array('created_at', $cfg['columns']) && !isset($row['created_at'])) {
                            $row['created_at'] = now()->format('Y-m-d H:i:s');
                        }
                        if (in_array('updated_at', $cfg['columns']) && !isset($row['updated_at'])) {
                            $row['updated_at'] = now()->format('Y-m-d H:i:s');
                        }
                    }
                    unset($row); // Break reference
                    
                    DB::table($cfg['table'])->upsert($chunk, $cfg['unique'], $updateCols);
                    $total += count($chunk);
                }
                DB::commit();
            } catch (\Throwable $e) {
                // Fallback to per-row upsert to skip bad rows and continue
                DB::rollBack();
                DB::beginTransaction();
                foreach ($rows as $row) {
                    try {
                        // Ensure timestamps are set
                        if (in_array('created_at', $cfg['columns']) && !isset($row['created_at'])) {
                            $row['created_at'] = now()->format('Y-m-d H:i:s');
                        }
                        if (in_array('updated_at', $cfg['columns']) && !isset($row['updated_at'])) {
                            $row['updated_at'] = now()->format('Y-m-d H:i:s');
                        }
                        
                        DB::table($cfg['table'])->upsert([$row], $cfg['unique'], $updateCols);
                        $total += 1;
                    } catch (\Throwable $ie) {
                        // collect minimal error info
                        if (count($errors) < 20) {
                            $errors[] = $ie->getMessage();
                        }
                    }
                }
                DB::commit();
            } finally {
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            }
            
            return response()->json(['success' => true, 'upserted' => $total, 'skipped' => max(0, count($rows) - $total), 'errors' => $errors]);
        } catch (\Exception $e) {
            Log::error('Bulk upsert error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
    
    // ============================================================================
    // SUBJECTS ENDPOINTS
    // ============================================================================
    
    /**
     * Get existing subjects (full data)
     */
    public function getSubjects(Request $request)
    {
        try {
            $subjects = DB::table('tbl_subject')->get();
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
            // Primary key is required for sync
            if (!$request->has('subject_id') || !$request->input('subject_id')) {
                return response()->json([
                    'success' => false,
                    'error' => 'subject_id (primary key) is required for sync operations'
                ], 400);
            }
            
            $validated = $request->validate([
                'subject_id' => 'required|integer|min:1', // Primary key required (BIGINT)
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
     * Get existing users (full data)
     */
    public function getUsers(Request $request)
    {
        try {
            $users = DB::table('tbl_user')->get();
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
            // Primary key is required for sync
            if (!$request->has('user_id') || !$request->input('user_id')) {
                return response()->json([
                    'success' => false,
                    'error' => 'user_id (primary key) is required for sync operations'
                ], 400);
            }
            
            $validated = $request->validate([
                'user_id' => 'required|integer|min:1', // Primary key required (BIGINT)
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
     * Get existing activity logs (full data, filtered by days if provided)
     */
    public function getActivityLogs(Request $request)
    {
        try {
            $query = DB::table('tbl_activity_logs');
            
            // Filter by days if provided
            $days = $request->input('days');
            if ($days && is_numeric($days)) {
                $query->where('logs_timestamp', '>=', now()->subDays($days)->toDateTimeString());
            }
            
            $logs = $query->get();
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
            // Primary key is required for sync
            if (!$request->has('logs_id') || !$request->input('logs_id')) {
                return response()->json([
                    'success' => false,
                    'error' => 'logs_id (primary key) is required for sync operations'
                ], 400);
            }
            
            $validated = $request->validate([
                'logs_id' => 'required|integer|min:1', // Primary key required (BIGINT)
                'user_id' => 'required|integer',
                'logs_action' => 'required|string|max:50',
                'logs_description' => 'required|string',
                'logs_timestamp' => 'required',
                'logs_module' => 'required|string',
                'created_at' => 'nullable|date',
                'updated_at' => 'nullable|date',
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
     * Get existing teaching load archives (full data)
     */
    public function getTeachingLoadArchives(Request $request)
    {
        try {
            $archives = DB::table('tbl_teaching_load_archive')->get();
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
            // Primary key is required for sync
            if (!$request->has('archive_id') || !$request->input('archive_id')) {
                return response()->json([
                    'success' => false,
                    'error' => 'archive_id (primary key) is required for sync operations'
                ], 400);
            }
            
            $validated = $request->validate([
                'archive_id' => 'required|integer|min:1', // Primary key required (BIGINT)
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
                'created_at' => 'nullable|date',
                'updated_at' => 'nullable|date',
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
     * Get existing attendance record archives (full data)
     */
    public function getAttendanceRecordArchives(Request $request)
    {
        try {
            $archives = DB::table('tbl_attendance_record_archive')->get();
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
            // Primary key is required for sync
            if (!$request->has('archive_id') || !$request->input('archive_id')) {
                return response()->json([
                    'success' => false,
                    'error' => 'archive_id (primary key) is required for sync operations'
                ], 400);
            }
            
            $validated = $request->validate([
                'archive_id' => 'required|integer|min:1', // Primary key required (BIGINT)
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
    
    // ============================================================================
    // OFFICIAL MATTERS ENDPOINTS
    // ============================================================================
    
    /**
     * Get existing official matters (full data, filtered by days if provided)
     */
    public function getOfficialMatters(Request $request)
    {
        try {
            $query = DB::table('tbl_official_matters');
            
            // Filter by days if provided
            $days = $request->input('days');
            if ($days && is_numeric($days)) {
                $query->where(function($q) use ($days) {
                    $q->where('om_start_date', '>=', now()->subDays($days)->toDateString())
                      ->orWhere('om_end_date', '>=', now()->subDays($days)->toDateString());
                });
            }
            
            $officialMatters = $query->get();
            return response()->json($officialMatters);
        } catch (\Exception $e) {
            Log::error('Error getting official matters: ' . $e->getMessage());
            return response()->json([], 500);
        }
    }
    
    /**
     * Receive and store official matter data
     */
    public function receiveOfficialMatter(Request $request)
    {
        try {
            // Primary key is required for sync
            if (!$request->has('om_id') || !$request->input('om_id')) {
                return response()->json([
                    'success' => false,
                    'error' => 'om_id (primary key) is required for sync operations'
                ], 400);
            }
            
            $validated = $request->validate([
                'om_id' => 'required|integer|min:1', // Primary key required (BIGINT)
                'faculty_id' => 'nullable|integer',
                'om_department' => 'nullable|string|max:255',
                'om_purpose' => 'required|string|max:255',
                'om_remarks' => 'required|string|max:255',
                'om_start_date' => 'required|date',
                'om_end_date' => 'required|date',
                'om_attachment' => 'required|string|max:255',
                'created_at' => 'nullable|date',
                'updated_at' => 'nullable|date',
            ]);
            
            // Explicitly set timestamps if missing to ensure correct timezone
            // Use now() which respects APP_TIMEZONE, not MySQL's CURRENT_TIMESTAMP
            $now = now()->format('Y-m-d H:i:s');
            if (empty($validated['created_at'])) {
                $validated['created_at'] = $now;
            }
            if (empty($validated['updated_at'])) {
                $validated['updated_at'] = $now;
            }
            
            DB::table('tbl_official_matters')->updateOrInsert(
                ['om_id' => $validated['om_id']],
                $validated
            );
            
            Log::info("Synced official matter {$validated['om_id']} from local server");
            
            return response()->json([
                'success' => true,
                'message' => 'Official matter synced successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error receiving official matter: ' . $e->getMessage());
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
                'official_matters' => DB::table('tbl_official_matters')->count(),
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
    
    /**
     * Trigger attendance record updates when leaves, passes, or official matters are synced
     * This is called by the local server after syncing leaves/passes/official matters to cloud
     */
    public function triggerAttendanceUpdate(Request $request)
    {
        try {
            $validated = $request->validate([
                'type' => 'required|string|in:leave,pass,official_matter',
                'data' => 'required|array',
            ]);
            
            $type = $validated['type'];
            $data = $validated['data'];
            
            // Use AttendanceRemarksService to update attendance records
            $remarksService = app(\App\Services\AttendanceRemarksService::class);
            
            if ($type === 'leave') {
                $lpId = $data['lp_id'] ?? null;
                $facultyId = $data['faculty_id'] ?? null;
                $startDate = $data['start_date'] ?? null;
                $endDate = $data['end_date'] ?? null;
                
                // CRITICAL: Use old dates from the request if provided (before leave was updated)
                // If not provided, try to get from database (for backward compatibility)
                $oldStartDate = $data['old_start_date'] ?? null;
                $oldEndDate = $data['old_end_date'] ?? null;
                
                if (!$oldStartDate || !$oldEndDate) {
                    // Fallback: Get old leave data from database (may not work if already updated)
                    $oldLeave = null;
                    if ($lpId) {
                        $oldLeave = DB::table('tbl_leave_pass')->where('lp_id', $lpId)->first();
                    }
                    $oldStartDate = $oldStartDate ?? $oldLeave->leave_start_date ?? null;
                    $oldEndDate = $oldEndDate ?? $oldLeave->leave_end_date ?? null;
                }
                
                if ($facultyId && $startDate && $endDate) {
                    // Reconcile leave change to update attendance records - preserve existing IDs
                    // This will delete attendance records for dates no longer in range
                    $remarksService->reconcileLeaveChange($facultyId, $startDate, $endDate, $oldStartDate, $oldEndDate);
                    Log::info("Triggered attendance update for leave {$lpId} (faculty: {$facultyId}, dates: {$startDate} to {$endDate}, old dates: {$oldStartDate} to {$oldEndDate})");
                }
                
            } elseif ($type === 'pass') {
                $lpId = $data['lp_id'] ?? null;
                $facultyId = $data['faculty_id'] ?? null;
                $date = $data['date'] ?? null;
                
                // CRITICAL: Use old date from the request if provided (before pass was updated)
                // If not provided, try to get from database (for backward compatibility)
                $oldDate = $data['old_date'] ?? null;
                
                if (!$oldDate) {
                    // Fallback: Get old pass data from database (may not work if already updated)
                    $oldPass = null;
                    if ($lpId) {
                        $oldPass = DB::table('tbl_leave_pass')->where('lp_id', $lpId)->first();
                    }
                    $oldDate = $oldPass && $oldPass->pass_slip_date !== $date ? $oldPass->pass_slip_date : null;
                }
                
                if ($facultyId && $date) {
                    // Reconcile pass change to update attendance records - preserve existing IDs
                    // This will delete attendance records for the old date if date changed
                    $remarksService->reconcilePassChange($facultyId, $date, $oldDate);
                    Log::info("Triggered attendance update for pass {$lpId} (faculty: {$facultyId}, date: {$date}, old date: {$oldDate})");
                }
                
            } elseif ($type === 'official_matter') {
                $omId = $data['om_id'] ?? null;
                $facultyId = $data['faculty_id'] ?? null;
                $department = $data['department'] ?? null;
                $startDate = $data['start_date'] ?? null;
                $endDate = $data['end_date'] ?? null;
                $remarks = $data['remarks'] ?? null;
                
                // CRITICAL: Get old data from request if provided (before official matter was updated)
                $oldStartDate = $data['old_start_date'] ?? null;
                $oldEndDate = $data['old_end_date'] ?? null;
                $oldDepartment = $data['old_department'] ?? null;
                $oldRemarks = $data['old_remarks'] ?? null;
                
                if ($startDate && $endDate && $remarks) {
                    // Step 1: Delete attendance records for old dates/departments if this is an update
                    if ($oldStartDate && $oldEndDate && $oldRemarks) {
                        // Get old affected faculty IDs
                        $oldFacultyIds = [];
                        if (!empty($oldDepartment)) {
                            if ($oldDepartment === 'All Instructor') {
                                $oldFacultyIds = DB::table('tbl_faculty')->pluck('faculty_id')->toArray();
                            } else {
                                $oldFacultyIds = DB::table('tbl_faculty')
                                    ->where('faculty_department', $oldDepartment)
                                    ->pluck('faculty_id')
                                    ->toArray();
                            }
                        } elseif ($facultyId) {
                            $oldFacultyIds = [$facultyId];
                        }
                        
                        // Calculate old date range
                        $oldStart = \Carbon\Carbon::parse($oldStartDate);
                        $oldEnd = \Carbon\Carbon::parse($oldEndDate);
                        $oldDates = [];
                        $cursor = $oldStart->copy();
                        while ($cursor->lte($oldEnd)) {
                            $oldDates[] = $cursor->toDateString();
                            $cursor->addDay();
                        }
                        
                        // Calculate new date range
                        $newStart = \Carbon\Carbon::parse($startDate);
                        $newEnd = \Carbon\Carbon::parse($endDate);
                        $newDates = [];
                        $cursor = $newStart->copy();
                        while ($cursor->lte($newEnd)) {
                            $newDates[] = $cursor->toDateString();
                            $cursor->addDay();
                        }
                        
                        // Find dates that are in old range but not in new range (or department changed)
                        $datesToDelete = array_diff($oldDates, $newDates);
                        $departmentChanged = ($oldDepartment !== $department);
                        
                        if (!empty($datesToDelete) || $departmentChanged) {
                            // Delete attendance records for dates no longer in range or if department changed
                            $deleteQuery = DB::table('tbl_attendance_record')
                                ->where('record_status', 'Absent')
                                ->where('record_remarks', $oldRemarks);
                            
                            if (!empty($datesToDelete)) {
                                $deleteQuery->whereIn('record_date', $datesToDelete);
                            }
                            
                            if (!empty($oldFacultyIds)) {
                                $deleteQuery->whereIn('faculty_id', $oldFacultyIds);
                            }
                            
                            $deletedRecordIds = $deleteQuery->pluck('record_id')->toArray();
                            
                            if (!empty($deletedRecordIds)) {
                                DB::table('tbl_attendance_record')->whereIn('record_id', $deletedRecordIds)->delete();
                                
                                // Track deletions for sync
                                $syncService = app(\App\Services\CloudSyncService::class);
                                foreach ($deletedRecordIds as $recordId) {
                                    $syncService->trackDeletion('tbl_attendance_record', $recordId);
                                }
                                
                                Log::info("Deleted " . count($deletedRecordIds) . " attendance records for official matter {$omId} (old dates/department)");
                            }
                        }
                    }
                    
                    // Step 2: Create/update attendance records for new dates
                    // Get affected faculty IDs
                    $facultyIds = [];
                    
                    if (!empty($department)) {
                        if ($department === 'All Instructor') {
                            $facultyIds = DB::table('tbl_faculty')->pluck('faculty_id')->toArray();
                        } else {
                            $facultyIds = DB::table('tbl_faculty')
                                ->where('faculty_department', $department)
                                ->pluck('faculty_id')
                                ->toArray();
                        }
                    } elseif ($facultyId) {
                        $facultyIds = [$facultyId];
                    }
                    
                    if (!empty($facultyIds)) {
                        // Update attendance records for official matter
                        // This mimics the logic in OfficialMatterController
                        $start = \Carbon\Carbon::parse($startDate);
                        $end = \Carbon\Carbon::parse($endDate);
                        $cursor = $start->copy();
                        
                        while ($cursor->lte($end)) {
                            $date = $cursor->toDateString();
                            $dayOfWeek = $cursor->format('l');
                            
                            foreach ($facultyIds as $fId) {
                                // Get teaching loads for this faculty on this day
                                $teachingLoads = DB::table('tbl_teaching_load')
                                    ->where('faculty_id', $fId)
                                    ->where('teaching_load_day_of_week', $dayOfWeek)
                                    ->get();
                                
                                foreach ($teachingLoads as $load) {
                                    // Find camera for the room
                                    $cameraId = DB::table('tbl_camera')
                                        ->where('room_no', $load->room_no)
                                        ->value('camera_id');
                                    
                                    if (!$cameraId) {
                                        continue;
                                    }
                                    
                                    // Check if attendance record exists
                                    $existingRecord = DB::table('tbl_attendance_record')
                                        ->where('faculty_id', $fId)
                                        ->where('teaching_load_id', $load->teaching_load_id)
                                        ->whereDate('record_date', $date)
                                        ->first();
                                    
                                    if ($existingRecord) {
                                        // Update existing record
                                        DB::table('tbl_attendance_record')
                                            ->where('record_id', $existingRecord->record_id)
                                            ->update([
                                                'record_remarks' => $remarks,
                                                'record_status' => 'Absent',
                                                'updated_at' => now(),
                                            ]);
                                    } else {
                                        // Create new record
                                        DB::table('tbl_attendance_record')->insert([
                                            'faculty_id' => $fId,
                                            'teaching_load_id' => $load->teaching_load_id,
                                            'camera_id' => $cameraId,
                                            'record_date' => $date,
                                            'record_time_in' => null,
                                            'record_time_out' => null,
                                            'time_duration_seconds' => 0,
                                            'record_status' => 'Absent',
                                            'record_remarks' => $remarks,
                                            'created_at' => now(),
                                            'updated_at' => now(),
                                        ]);
                                    }
                                }
                            }
                            
                            $cursor->addDay();
                        }
                        
                        Log::info("Triggered attendance update for official matter {$omId} (faculties: " . count($facultyIds) . ", dates: {$startDate} to {$endDate})");
                    }
                }
            }
            
            return response()->json([
                'success' => true,
                'message' => "Attendance records updated for {$type}",
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error triggering attendance update: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Delete a user (called from local server)
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
            $syncService = app(\App\Services\CloudSyncService::class);
            $syncService->trackDeletion('tbl_user', $id);
            
            // NEW APPROACH: Immediately trigger deletion on local server
            try {
                $syncService->triggerDeleteOnLocal('users', $id);
            } catch (\Exception $e) {
                Log::error("Failed to trigger user deletion on local: " . $e->getMessage());
            }
            
            Log::info("Deleted user {$id} from cloud via API");
            return response()->json(['success' => true, 'message' => 'User deleted successfully']);
        } catch (\Exception $e) {
            Log::error("Error deleting user {$id}: " . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Delete a subject (called from local server)
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
            $syncService = app(\App\Services\CloudSyncService::class);
            $syncService->trackDeletion('tbl_subject', $id);
            
            // NEW APPROACH: Immediately trigger deletion on local server
            try {
                $syncService->triggerDeleteOnLocal('subjects', $id);
            } catch (\Exception $e) {
                Log::error("Failed to trigger subject deletion on local: " . $e->getMessage());
            }
            
            Log::info("Deleted subject {$id} from cloud via API");
            return response()->json(['success' => true, 'message' => 'Subject deleted successfully']);
        } catch (\Exception $e) {
            Log::error("Error deleting subject {$id}: " . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Delete a room (called from local server)
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
            $syncService = app(\App\Services\CloudSyncService::class);
            $syncService->trackDeletion('tbl_room', $id);
            
            // NEW APPROACH: Immediately trigger deletion on local server
            try {
                $syncService->triggerDeleteOnLocal('rooms', $id);
            } catch (\Exception $e) {
                Log::error("Failed to trigger room deletion on local: " . $e->getMessage());
            }
            
            Log::info("Deleted room {$id} from cloud via API");
            return response()->json(['success' => true, 'message' => 'Room deleted successfully']);
        } catch (\Exception $e) {
            Log::error("Error deleting room {$id}: " . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Delete a camera (called from local server)
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
            $syncService = app(\App\Services\CloudSyncService::class);
            $syncService->trackDeletion('tbl_camera', $id);
            
            // NEW APPROACH: Immediately trigger deletion on local server
            try {
                $syncService->triggerDeleteOnLocal('cameras', $id);
            } catch (\Exception $e) {
                Log::error("Failed to trigger camera deletion on local: " . $e->getMessage());
            }
            
            Log::info("Deleted camera {$id} from cloud via API");
            return response()->json(['success' => true, 'message' => 'Camera deleted successfully']);
        } catch (\Exception $e) {
            Log::error("Error deleting camera {$id}: " . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Delete a faculty (called from local server)
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
            $syncService = app(\App\Services\CloudSyncService::class);
            $syncService->trackDeletion('tbl_faculty', $id);
            
            // NEW APPROACH: Immediately trigger deletion on local server
            try {
                $syncService->triggerDeleteOnLocal('faculties', $id);
            } catch (\Exception $e) {
                Log::error("Failed to trigger faculty deletion on local: " . $e->getMessage());
            }
            
            Log::info("Deleted faculty {$id} from cloud via API");
            return response()->json(['success' => true, 'message' => 'Faculty deleted successfully']);
        } catch (\Exception $e) {
            Log::error("Error deleting faculty {$id}: " . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Delete a teaching load (called from local server)
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
            $syncService = app(\App\Services\CloudSyncService::class);
            $syncService->trackDeletion('tbl_teaching_load', $id);
            
            // NEW APPROACH: Immediately trigger deletion on local server
            try {
                $syncService->triggerDeleteOnLocal('teaching-loads', $id);
            } catch (\Exception $e) {
                Log::error("Failed to trigger teaching load deletion on local: " . $e->getMessage());
            }
            
            Log::info("Deleted teaching load {$id} from cloud via API");
            return response()->json(['success' => true, 'message' => 'Teaching load deleted successfully']);
        } catch (\Exception $e) {
            Log::error("Error deleting teaching load {$id}: " . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Delete a leave (called from local server)
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
            $syncService = app(\App\Services\CloudSyncService::class);
            $syncService->trackDeletion('tbl_leave_pass', $id, 90, ['lp_type' => 'Leave']);
            
            // Remove attendance records
            $remarksService = app(\App\Services\AttendanceRemarksService::class);
            $remarksService->removeLeaveAbsencesInWindow($facultyId, $start, $end);
            
            // NEW APPROACH: Immediately trigger deletion on local server
            try {
                $syncService->triggerDeleteOnLocal('leaves', $id);
            } catch (\Exception $e) {
                Log::error("Failed to trigger leave deletion on local: " . $e->getMessage());
            }
            
            Log::info("Deleted leave {$id} from cloud via API");
            return response()->json(['success' => true, 'message' => 'Leave deleted successfully']);
        } catch (\Exception $e) {
            Log::error("Error deleting leave {$id}: " . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Delete a pass (called from local server)
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
            $syncService = app(\App\Services\CloudSyncService::class);
            $syncService->trackDeletion('tbl_leave_pass', $id, 90, ['lp_type' => 'Pass']);
            
            // Reconcile attendance
            $remarksService = app(\App\Services\AttendanceRemarksService::class);
            $remarksService->reconcilePassChange($facultyId, $date);
            
            // NEW APPROACH: Immediately trigger deletion on local server
            try {
                $syncService->triggerDeleteOnLocal('passes', $id);
            } catch (\Exception $e) {
                Log::error("Failed to trigger pass deletion on local: " . $e->getMessage());
            }
            
            Log::info("Deleted pass {$id} from cloud via API");
            return response()->json(['success' => true, 'message' => 'Pass deleted successfully']);
        } catch (\Exception $e) {
            Log::error("Error deleting pass {$id}: " . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Delete an official matter (called from local server)
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
            $syncService = app(\App\Services\CloudSyncService::class);
            $syncService->trackDeletion('tbl_official_matters', $id);
            
            // NEW APPROACH: Immediately trigger deletion on local server
            try {
                $syncService->triggerDeleteOnLocal('official-matters', $id);
            } catch (\Exception $e) {
                Log::error("Failed to trigger official matter deletion on local: " . $e->getMessage());
            }
            
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
            
            Log::info("Deleted official matter {$id} from cloud via API");
            return response()->json(['success' => true, 'message' => 'Official matter deleted successfully']);
        } catch (\Exception $e) {
            Log::error("Error deleting official matter {$id}: " . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Delete an attendance record (called from local server)
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
            $syncService = app(\App\Services\CloudSyncService::class);
            $syncService->trackDeletion('tbl_attendance_record', $id);
            
            // NEW APPROACH: Immediately trigger deletion on local server
            try {
                $syncService->triggerDeleteOnLocal('attendance-records', $id);
            } catch (\Exception $e) {
                Log::error("Failed to trigger attendance record deletion on local: " . $e->getMessage());
            }
            
            Log::info("Deleted attendance record {$id} from cloud via API");
            return response()->json(['success' => true, 'message' => 'Attendance record deleted successfully']);
        } catch (\Exception $e) {
            Log::error("Error deleting attendance record {$id}: " . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Delete a recognition log (called from local server)
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
            $syncService = app(\App\Services\CloudSyncService::class);
            $syncService->trackDeletion('tbl_recognition_logs', $id);
            
            // NEW APPROACH: Immediately trigger deletion on local server
            try {
                $syncService->triggerDeleteOnLocal('recognition-logs', $id);
            } catch (\Exception $e) {
                Log::error("Failed to trigger recognition log deletion on local: " . $e->getMessage());
            }
            
            Log::info("Deleted recognition log {$id} from cloud via API");
            return response()->json(['success' => true, 'message' => 'Recognition log deleted successfully']);
        } catch (\Exception $e) {
            Log::error("Error deleting recognition log {$id}: " . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Delete a stream recording (called from local server)
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
            $syncService = app(\App\Services\CloudSyncService::class);
            $syncService->trackDeletion('tbl_stream_recordings', $id);
            
            // NEW APPROACH: Immediately trigger deletion on local server
            try {
                $syncService->triggerDeleteOnLocal('stream-recordings', $id);
            } catch (\Exception $e) {
                Log::error("Failed to trigger stream recording deletion on local: " . $e->getMessage());
            }
            
            Log::info("Deleted stream recording {$id} from cloud via API");
            return response()->json(['success' => true, 'message' => 'Stream recording deleted successfully']);
        } catch (\Exception $e) {
            Log::error("Error deleting stream recording {$id}: " . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Delete an activity log (called from local server)
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
            $syncService = app(\App\Services\CloudSyncService::class);
            $syncService->trackDeletion('tbl_activity_logs', $id);
            
            // NEW APPROACH: Immediately trigger deletion on local server
            try {
                $syncService->triggerDeleteOnLocal('activity-logs', $id);
            } catch (\Exception $e) {
                Log::error("Failed to trigger activity log deletion on local: " . $e->getMessage());
            }
            
            Log::info("Deleted activity log {$id} from cloud via API");
            return response()->json(['success' => true, 'message' => 'Activity log deleted successfully']);
        } catch (\Exception $e) {
            Log::error("Error deleting activity log {$id}: " . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Delete a teaching load archive (called from local server)
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
            $syncService = app(\App\Services\CloudSyncService::class);
            $syncService->trackDeletion('tbl_teaching_load_archive', $id);
            
            // NEW APPROACH: Immediately trigger deletion on local server
            try {
                $syncService->triggerDeleteOnLocal('teaching-load-archives', $id);
            } catch (\Exception $e) {
                Log::error("Failed to trigger teaching load archive deletion on local: " . $e->getMessage());
            }
            
            Log::info("Deleted teaching load archive {$id} from cloud via API");
            return response()->json(['success' => true, 'message' => 'Teaching load archive deleted successfully']);
        } catch (\Exception $e) {
            Log::error("Error deleting teaching load archive {$id}: " . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Delete an attendance record archive (called from local server)
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
            $syncService = app(\App\Services\CloudSyncService::class);
            $syncService->trackDeletion('tbl_attendance_record_archive', $id);
            
            // NEW APPROACH: Immediately trigger deletion on local server
            try {
                $syncService->triggerDeleteOnLocal('attendance-record-archives', $id);
            } catch (\Exception $e) {
                Log::error("Failed to trigger attendance record archive deletion on local: " . $e->getMessage());
            }
            
            Log::info("Deleted attendance record archive {$id} from cloud via API");
            return response()->json(['success' => true, 'message' => 'Attendance record archive deleted successfully']);
        } catch (\Exception $e) {
            Log::error("Error deleting attendance record archive {$id}: " . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Get deleted IDs for a specific endpoint (called by local server during sync)
     * @param string $endpoint API endpoint (e.g., 'users', 'leaves')
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDeletedIds($endpoint)
    {
        try {
            $syncService = app(\App\Services\CloudSyncService::class);
            
            // Map endpoint to table name
            $tableMapping = [
                'users' => 'tbl_user',
                'subjects' => 'tbl_subject',
                'rooms' => 'tbl_room',
                'cameras' => 'tbl_camera',
                'faculties' => 'tbl_faculty',
                'teaching-loads' => 'tbl_teaching_load',
                'attendance-records' => 'tbl_attendance_record',
                'leaves' => 'tbl_leave_pass',
                'passes' => 'tbl_leave_pass',
                'official-matters' => 'tbl_official_matters',
                'recognition-logs' => 'tbl_recognition_logs',
                'stream-recordings' => 'tbl_stream_recordings',
                'activity-logs' => 'tbl_activity_logs',
                'teaching-load-archives' => 'tbl_teaching_load_archive',
                'attendance-record-archives' => 'tbl_attendance_record_archive',
            ];
            
            $tableName = $tableMapping[$endpoint] ?? null;
            if (!$tableName) {
                return response()->json(['success' => false, 'error' => 'Invalid endpoint'], 400);
            }
            
            // Get deleted IDs from cache
            $deletedIds = $syncService->getDeletedIds($tableName);
            
            // For leaves and passes, filter by lp_type
            if ($tableName === 'tbl_leave_pass') {
                $filteredIds = [];
                foreach ($deletedIds as $id) {
                    $cacheKey = "sync_deletion:tbl_leave_pass:{$id}";
                    $deletionData = \Illuminate\Support\Facades\Cache::get($cacheKey);
                    $lpType = $deletionData['metadata']['lp_type'] ?? null;
                    
                    if (($endpoint === 'leaves' && $lpType === 'Leave') || 
                        ($endpoint === 'passes' && $lpType === 'Pass')) {
                        $filteredIds[] = $id;
                    }
                }
                $deletedIds = $filteredIds;
            }
            
            Log::info("Returning " . count($deletedIds) . " deleted IDs for {$endpoint}");
            return response()->json([
                'success' => true,
                'deleted_ids' => $deletedIds
            ]);
        } catch (\Exception $e) {
            Log::error("Error getting deleted IDs for {$endpoint}: " . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
