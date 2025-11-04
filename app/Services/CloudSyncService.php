<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Faculty;
use App\Models\Room;
use App\Models\Camera;
use App\Models\TeachingLoad;
use App\Models\AttendanceRecord;
use App\Models\RecognitionLog;
use App\Models\StreamRecording;
use App\Models\Leave;
use App\Models\Pass;
use App\Models\Subject;
use App\Models\User;
use App\Models\ActivityLog;
use App\Models\TeachingLoadArchive;
use App\Models\AttendanceRecordArchive;

class CloudSyncService
{
    protected $cloudApiUrl;
    protected $cloudApiKey;
    
    public function __construct()
    {
        $this->cloudApiUrl = env('CLOUD_API_URL', 'https://tcc-maem.com/api');
        $this->cloudApiKey = env('CLOUD_API_KEY', 'e5a4466194f624d9e8611bd264a958e54473692ada6280840c118066f18e6815');
    }
    
    /**
     * Sync all data to cloud
     */
    public function syncAllToCloud()
    {
        $results = [
            'success' => true,
            'synced' => [],
            'errors' => [],
            'summary' => []
        ];
        
        try {
            Log::info('Starting cloud sync...');
            
            // Sync in order of dependencies - users first
            $results['synced']['users'] = $this->syncUsers();
            $results['synced']['subjects'] = $this->syncSubjects();
            $results['synced']['rooms'] = $this->syncRooms();
            $results['synced']['cameras'] = $this->syncCameras();
            $results['synced']['faculties'] = $this->syncFaculties();
            $results['synced']['teaching_loads'] = $this->syncTeachingLoads();
            $results['synced']['attendance_records'] = $this->syncAttendanceRecords();
            $results['synced']['leaves'] = $this->syncLeaves();
            $results['synced']['passes'] = $this->syncPasses();
            $results['synced']['recognition_logs'] = $this->syncRecognitionLogs();
            $results['synced']['stream_recordings'] = $this->syncStreamRecordings();
            $results['synced']['activity_logs'] = $this->syncActivityLogs();
            $results['synced']['teaching_load_archives'] = $this->syncTeachingLoadArchives();
            $results['synced']['attendance_record_archives'] = $this->syncAttendanceRecordArchives();
            
            // Calculate summary
            foreach ($results['synced'] as $key => $value) {
                $results['summary'][$key] = count($value);
            }
            
            Log::info('Cloud sync completed successfully', $results['summary']);
            
        } catch (\Exception $e) {
            Log::error('Cloud sync failed: ' . $e->getMessage());
            $results['success'] = false;
            $results['errors'][] = $e->getMessage();
        }
        
        return $results;
    }
    
    /**
     * Sync rooms
     */
    protected function syncRooms()
    {
        $synced = [];
        
        try {
            // Get all local rooms
            $localRooms = Room::all();
            
            // Bulk upsert all rooms
            $payload = $localRooms->map(function ($room) {
                return [
                    'room_no' => $room->room_no,
                    'room_name' => $room->room_name,
                    'room_building_no' => $room->room_building_no,
                ];
            })->values()->all();
            $resp = $this->pushBulkToCloud('rooms', $payload);
            if ($resp['success']) {
                $synced = $localRooms->pluck('room_no')->all();
            }
        } catch (\Exception $e) {
            Log::error("Error syncing rooms: " . $e->getMessage());
        }
        
        return $synced;
    }
    
    /**
     * Sync cameras
     */
    protected function syncCameras()
    {
        $synced = [];
        
        try {
            $localCameras = Camera::all();
            $payload = $localCameras->map(function ($camera) {
                return [
                    'camera_id' => $camera->camera_id,
                    'camera_name' => $camera->camera_name,
                    'camera_ip_address' => $camera->camera_ip_address,
                    'camera_username' => $camera->camera_username,
                    'camera_password' => $camera->camera_password,
                    'camera_live_feed' => $camera->camera_live_feed,
                    'room_no' => $camera->room_no,
                ];
            })->values()->all();
            $resp = $this->pushBulkToCloud('cameras', $payload);
            if ($resp['success']) {
                $synced = $localCameras->pluck('camera_id')->all();
            }
        } catch (\Exception $e) {
            Log::error("Error syncing cameras: " . $e->getMessage());
        }
        
        return $synced;
    }
    
    /**
     * Sync faculties (including images)
     */
    protected function syncFaculties()
    {
        $synced = [];
        
        try {
            $localFaculties = Faculty::all();
            $payload = $localFaculties->map(function ($faculty) {
                // Sync faculty images to cloud and update paths
                $cloudImages = $this->syncFacultyImages($faculty->faculty_images);
                
                return [
                    'faculty_id' => $faculty->faculty_id,
                    'faculty_fname' => $faculty->faculty_fname,
                    'faculty_lname' => $faculty->faculty_lname,
                    'faculty_department' => $faculty->faculty_department,
                    'faculty_images' => $cloudImages,
                    'faculty_face_embedding' => $faculty->faculty_face_embedding,
                    'created_at' => $this->formatDateTime($faculty->created_at),
                    'updated_at' => $this->formatDateTime($faculty->updated_at),
                ];
            })->values()->all();
            $resp = $this->pushBulkToCloud('faculties', $payload);
            if ($resp['success']) {
                $synced = $localFaculties->pluck('faculty_id')->all();
            }
        } catch (\Exception $e) {
            Log::error("Error syncing faculties: " . $e->getMessage());
        }
        
        return $synced;
    }
    
    /**
     * Sync teaching loads
     */
    protected function syncTeachingLoads()
    {
        $synced = [];
        
        try {
            $localLoads = TeachingLoad::all();
            $payload = $localLoads->map(function ($load) {
                return [
                    'teaching_load_id' => $load->teaching_load_id,
                    'faculty_id' => $load->faculty_id,
                    'teaching_load_course_code' => $load->teaching_load_course_code,
                    'teaching_load_subject' => $load->teaching_load_subject,
                    'teaching_load_day_of_week' => $load->teaching_load_day_of_week,
                    'teaching_load_class_section' => $load->teaching_load_class_section,
                    'teaching_load_time_in' => $load->teaching_load_time_in,
                    'teaching_load_time_out' => $load->teaching_load_time_out,
                    'room_no' => $load->room_no,
                    'created_at' => $this->formatDateTime($load->created_at),
                    'updated_at' => $this->formatDateTime($load->updated_at),
                ];
            })->values()->all();
            $resp = $this->pushBulkToCloud('teaching-loads', $payload);
            $upserted = $resp['data']['upserted'] ?? 0;
            Log::info('Bulk teaching-loads result', ['upserted' => $upserted, 'success' => $resp['success'] ?? null, 'local_count' => count($localLoads)]);
            
            if ($resp['success'] && $upserted > 0) {
                // Only return synced IDs if records were actually upserted
                $synced = $localLoads->pluck('teaching_load_id')->all();
            } elseif ($resp['success'] && $upserted == 0) {
                Log::warning("Teaching loads sync returned success but 0 records were upserted. Check cloud API logs for validation errors.");
            }
        } catch (\Exception $e) {
            Log::error("Error syncing teaching loads: " . $e->getMessage());
        }
        
        return $synced;
    }
    
    /**
     * Sync attendance records
     */
    protected function syncAttendanceRecords()
    {
        $synced = [];
        
        try {
            $localRecords = AttendanceRecord::all();
            $payload = $localRecords->map(function ($record) {
                return [
                    'record_id' => $record->record_id,
                    'record_date' => $this->formatDateTime($record->record_date),
                    'faculty_id' => $record->faculty_id,
                    'teaching_load_id' => $record->teaching_load_id,
                    'record_time_in' => $record->record_time_in,
                    'record_time_out' => $record->record_time_out,
                    'time_duration_seconds' => $record->time_duration_seconds,
                    'record_status' => $record->record_status,
                    'record_remarks' => $record->record_remarks,
                    'camera_id' => $record->camera_id,
                ];
            })->values()->all();
            $resp = $this->pushBulkToCloud('attendance-records', $payload);
            if ($resp['success']) {
                $synced = $localRecords->pluck('record_id')->all();
            }
        } catch (\Exception $e) {
            Log::error("Error syncing attendance records: " . $e->getMessage());
        }
        
        return $synced;
    }
    
    /**
     * Sync leaves
     * Note: Leaves and passes share the same table (tbl_leave_pass)
     */
    protected function syncLeaves()
    {
        $synced = [];
        
        try {
            // Sync ALL leaves
            $localLeaves = Leave::all();
            $payload = $localLeaves->map(function ($leave) {
                // Sync leave image to cloud and update path
                $cloudImagePath = $this->syncLeaveImage($leave->lp_image);
                
                return [
                    'lp_id' => $leave->lp_id,
                    'faculty_id' => $leave->faculty_id,
                    'lp_type' => $leave->lp_type,
                    'lp_purpose' => $leave->lp_purpose,
                    'leave_start_date' => $leave->leave_start_date,
                    'leave_end_date' => $leave->leave_end_date,
                    'lp_image' => $cloudImagePath,
                    'created_at' => $this->formatDateTime($leave->created_at),
                    'updated_at' => $this->formatDateTime($leave->updated_at),
                ];
            })->values()->all();
            $resp = $this->pushBulkToCloud('leaves', $payload);
            if ($resp['success']) {
                $synced = $localLeaves->pluck('lp_id')->all();
            }
        } catch (\Exception $e) {
            Log::error("Error syncing leaves: " . $e->getMessage());
        }
        
        return $synced;
    }
    
    /**
     * Sync passes
     * Note: Leaves and passes share the same table (tbl_leave_pass)
     */
    protected function syncPasses()
    {
        $synced = [];
        
        try {
            // Sync ALL passes
            $localPasses = Pass::all();
            $payload = $localPasses->map(function ($pass) {
                // Sync pass image to cloud and update path
                $cloudImagePath = $this->syncPassImage($pass->lp_image);
                
                return [
                    'lp_id' => $pass->lp_id,
                    'faculty_id' => $pass->faculty_id,
                    'lp_type' => $pass->lp_type,
                    'lp_purpose' => $pass->lp_purpose,
                    'pass_slip_itinerary' => $pass->pass_slip_itinerary,
                    'pass_slip_date' => $pass->pass_slip_date,
                    'pass_slip_departure_time' => $pass->pass_slip_departure_time,
                    'pass_slip_arrival_time' => $pass->pass_slip_arrival_time,
                    'lp_image' => $cloudImagePath,
                    'created_at' => $this->formatDateTime($pass->created_at),
                    'updated_at' => $this->formatDateTime($pass->updated_at),
                ];
            })->values()->all();
            $resp = $this->pushBulkToCloud('passes', $payload);
            if ($resp['success']) {
                $synced = $localPasses->pluck('lp_id')->all();
            }
        } catch (\Exception $e) {
            Log::error("Error syncing passes: " . $e->getMessage());
        }
        
        return $synced;
    }
    
    /**
     * Sync recognition logs
     */
    protected function syncRecognitionLogs()
    {
        $synced = [];
        
        try {
            // Sync ALL recognition logs
            $localLogs = RecognitionLog::all();
            $payload = $localLogs->map(function ($log) {
                return [
                    'log_id' => $log->log_id,
                    'recognition_time' => $this->formatDateTime($log->recognition_time),
                    'camera_name' => $log->camera_name,
                    'room_name' => $log->room_name,
                    'building_no' => $log->building_no,
                    'faculty_name' => $log->faculty_name,
                    'status' => $log->status,
                    'distance' => $log->distance,
                    'faculty_id' => $log->faculty_id,
                    'camera_id' => $log->camera_id,
                    'teaching_load_id' => $log->teaching_load_id,
                ];
            })->values()->all();
            $resp = $this->pushBulkToCloud('recognition-logs', $payload);
            $upserted = $resp['data']['upserted'] ?? 0;
            Log::info('Bulk recognition-logs result', ['upserted' => $upserted, 'success' => $resp['success'] ?? null, 'local_count' => count($localLogs)]);
            
            if ($resp['success'] && $upserted > 0) {
                // Only return synced IDs if records were actually upserted
                $synced = $localLogs->pluck('log_id')->all();
            } elseif ($resp['success'] && $upserted == 0) {
                Log::warning("Recognition logs sync returned success but 0 records were upserted. Check cloud API logs for validation errors.");
            }
        } catch (\Exception $e) {
            Log::error("Error syncing recognition logs: " . $e->getMessage());
        }
        
        return $synced;
    }
    
    /**
     * Sync stream recordings (including video files)
     */
    protected function syncStreamRecordings()
    {
        $synced = [];
        
        try {
            // Sync ALL stream recordings
            $localRecordings = StreamRecording::all();
            $payload = $localRecordings->map(function ($recording) {
                // Sync video file to cloud and update path
                $cloudVideoPath = $this->syncStreamRecordingVideo($recording->filepath);
                
                return [
                    'recording_id' => $recording->recording_id,
                    'camera_id' => $recording->camera_id,
                    'filename' => $recording->filename,
                    'filepath' => $cloudVideoPath,
                    'start_time' => date('Y-m-d H:i:s', strtotime($recording->start_time)),
                    'duration' => $recording->duration,
                    'frames' => $recording->frames,
                    'file_size' => $recording->file_size,
                    'created_at' => $recording->created_at ? date('Y-m-d H:i:s', strtotime($recording->created_at)) : null,
                    'updated_at' => $recording->updated_at ? date('Y-m-d H:i:s', strtotime($recording->updated_at)) : null,
                ];
            })->values()->all();
            $resp = $this->pushBulkToCloud('stream-recordings', $payload);
            if ($resp['success']) {
                $synced = $localRecordings->pluck('recording_id')->all();
            }
        } catch (\Exception $e) {
            Log::error("Error syncing stream recordings: " . $e->getMessage());
        }
        
        return $synced;
    }
    
    /**
     * Get data from cloud to check what exists
     */
    protected function getCloudData($endpoint, $params = [])
    {
        try {
            // Use sync routes with /sync/ prefix
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->cloudApiKey,
                    'Accept' => 'application/json',
                ])
                ->get("{$this->cloudApiUrl}/sync/{$endpoint}", $params);
            
            if ($response->successful()) {
                return $response->json();
            }
            
            return [];
        } catch (\Exception $e) {
            Log::error("Error getting cloud data for {$endpoint}: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Push data to cloud
     */
    protected function pushToCloud($endpoint, $data)
    {
        try {
            // Use sync routes with /sync/ prefix
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->cloudApiKey,
                    'Accept' => 'application/json',
                ])
                ->post("{$this->cloudApiUrl}/sync/{$endpoint}", $data);
            
            if ($response->successful()) {
                return ['success' => true, 'data' => $response->json()];
            }
            
            Log::error("Failed to push to cloud {$endpoint}: " . $response->body());
            return ['success' => false, 'error' => $response->body()];
            
        } catch (\Exception $e) {
            Log::error("Error pushing to cloud {$endpoint}: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Format datetime for MySQL compatibility
     * Converts Carbon/DateTime objects to MySQL datetime format (Y-m-d H:i:s)
     */
    protected function formatDateTime($value)
    {
        if (empty($value)) {
            return null;
        }
        if ($value instanceof \Carbon\Carbon || $value instanceof \DateTime) {
            return $value->format('Y-m-d H:i:s');
        }
        if (is_string($value)) {
            // Try to parse and reformat if it's an ISO 8601 string
            try {
                return \Carbon\Carbon::parse($value)->format('Y-m-d H:i:s');
            } catch (\Exception $e) {
                return $value;
            }
        }
        return $value;
    }

    /**
     * Bulk push helper: POST records array to /bulk/{endpoint}
     */
    protected function pushBulkToCloud(string $endpoint, array $records)
    {
        if (empty($records)) {
            return ['success' => true, 'data' => ['upserted' => 0]];
        }
        try {
            // Chunk large payloads
            $total = 0;
            foreach (array_chunk($records, 100) as $chunkIndex => $chunk) {
                $response = Http::timeout(60)
                    ->withHeaders([
                        'Authorization' => 'Bearer ' . $this->cloudApiKey,
                        'Accept' => 'application/json',
                    ])
                    ->post("{$this->cloudApiUrl}/sync/bulk/{$endpoint}", ['records' => $chunk]);
                
                if (!$response->successful()) {
                    $errorBody = $response->body();
                    Log::error("Bulk push failed for {$endpoint} (chunk {$chunkIndex}): " . $errorBody);
                    Log::error("Response status: " . $response->status());
                    return ['success' => false, 'error' => $errorBody];
                }
                
                $json = $response->json();
                $chunkUpserted = (int)($json['upserted'] ?? 0);
                $total += $chunkUpserted;
                
                // Log if chunk had 0 upserted but request was successful
                if ($chunkUpserted == 0 && count($chunk) > 0) {
                    Log::warning("Bulk push for {$endpoint} (chunk {$chunkIndex}): Request successful but 0 records upserted. Response: " . json_encode($json));
                }
            }
            
            Log::info("Bulk push completed for {$endpoint}", ['total_upserted' => $total, 'total_sent' => count($records)]);
            return ['success' => true, 'data' => ['upserted' => $total]];
        } catch (\Exception $e) {
            Log::error("Error bulk pushing to cloud {$endpoint}: " . $e->getMessage());
            Log::error("Stack trace: " . $e->getTraceAsString());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Sync subjects
     */
    protected function syncSubjects()
    {
        $synced = [];
        
        try {
            $localSubjects = Subject::all();
            $payload = $localSubjects->map(function ($s) {
                return [
                    'subject_id' => $s->subject_id,
                    'subject_code' => $s->subject_code,
                    'subject_description' => $s->subject_description,
                    'department' => $s->department,
                    'created_at' => $this->formatDateTime($s->created_at),
                    'updated_at' => $this->formatDateTime($s->updated_at),
                ];
            })->values()->all();
            $resp = $this->pushBulkToCloud('subjects', $payload);
            $upserted = $resp['data']['upserted'] ?? 0;
            Log::info('Bulk subjects result', ['upserted' => $upserted, 'success' => $resp['success'] ?? null, 'local_count' => count($localSubjects)]);
            
            if ($resp['success'] && $upserted > 0) {
                // Only return synced IDs if records were actually upserted
                $synced = $localSubjects->pluck('subject_id')->all();
            } elseif ($resp['success'] && $upserted == 0) {
                Log::warning("Subjects sync returned success but 0 records were upserted. Check cloud API logs for validation errors.");
            }
        } catch (\Exception $e) {
            Log::error("Error syncing subjects: " . $e->getMessage());
        }
        
        return $synced;
    }
    
    /**
     * Sync users (admin accounts)
     */
    protected function syncUsers()
    {
        $synced = [];
        
        try {
            $localUsers = User::all();
            $payload = $localUsers->map(function ($u) {
                return [
                    'user_id' => $u->user_id,
                    'user_role' => $u->user_role,
                    'user_department' => $u->user_department,
                    'user_fname' => $u->user_fname,
                    'user_lname' => $u->user_lname,
                    'username' => $u->username,
                    'user_password' => $u->user_password,
                    'created_at' => $this->formatDateTime($u->created_at),
                    'updated_at' => $this->formatDateTime($u->updated_at),
                ];
            })->values()->all();
            $resp = $this->pushBulkToCloud('users', $payload);
            $upserted = $resp['data']['upserted'] ?? 0;
            Log::info('Bulk users result', ['upserted' => $upserted, 'success' => $resp['success'] ?? null, 'local_count' => count($localUsers)]);
            
            if ($resp['success'] && $upserted > 0) {
                // Only return synced IDs if records were actually upserted
                $synced = $localUsers->pluck('user_id')->all();
            } elseif ($resp['success'] && $upserted == 0) {
                Log::warning("Users sync returned success but 0 records were upserted. Check cloud API logs for validation errors.");
            }
        } catch (\Exception $e) {
            Log::error("Error syncing users: " . $e->getMessage());
        }
        
        return $synced;
    }
    
    /**
     * Sync activity logs
     */
    protected function syncActivityLogs()
    {
        $synced = [];
        
        try {
            $localLogs = ActivityLog::all();
            $payload = $localLogs->map(function ($log) {
                return [
                    'logs_id' => $log->logs_id,
                    'user_id' => $log->user_id,
                    'logs_action' => $log->logs_action,
                    'logs_description' => $log->logs_description,
                    'logs_timestamp' => $this->formatDateTime($log->logs_timestamp),
                    'logs_module' => $log->logs_module,
                ];
            })->values()->all();
            $resp = $this->pushBulkToCloud('activity-logs', $payload);
            if ($resp['success']) {
                $synced = $localLogs->pluck('logs_id')->all();
            }
        } catch (\Exception $e) {
            Log::error("Error syncing activity logs: " . $e->getMessage());
        }
        
        return $synced;
    }
    
    /**
     * Sync teaching load archives
     */
    protected function syncTeachingLoadArchives()
    {
        $synced = [];
        
        try {
            $localArchives = TeachingLoadArchive::all();
            $payload = $localArchives->map(function ($a) {
                return [
                    'archive_id' => $a->archive_id,
                    'original_teaching_load_id' => $a->original_teaching_load_id,
                    'faculty_id' => $a->faculty_id,
                    'teaching_load_course_code' => $a->teaching_load_course_code,
                    'teaching_load_subject' => $a->teaching_load_subject,
                    'teaching_load_class_section' => $a->teaching_load_class_section,
                    'teaching_load_day_of_week' => $a->teaching_load_day_of_week,
                    'teaching_load_time_in' => $a->teaching_load_time_in,
                    'teaching_load_time_out' => $a->teaching_load_time_out,
                    'room_no' => $a->room_no,
                    'school_year' => $a->school_year,
                    'semester' => $a->semester,
                    'archived_at' => $this->formatDateTime($a->archived_at),
                    'archived_by' => $a->archived_by,
                    'archive_notes' => $a->archive_notes,
                ];
            })->values()->all();
            $resp = $this->pushBulkToCloud('teaching-load-archives', $payload);
            $upserted = $resp['data']['upserted'] ?? 0;
            Log::info('Bulk teaching-load-archives result', ['upserted' => $upserted, 'success' => $resp['success'] ?? null, 'local_count' => count($localArchives)]);
            
            if ($resp['success'] && $upserted > 0) {
                // Only return synced IDs if records were actually upserted
                $synced = $localArchives->pluck('archive_id')->all();
            } elseif ($resp['success'] && $upserted == 0) {
                Log::warning("Teaching load archives sync returned success but 0 records were upserted. Check cloud API logs for validation errors.");
            }
        } catch (\Exception $e) {
            Log::error("Error syncing teaching load archives: " . $e->getMessage());
        }
        
        return $synced;
    }
    
    /**
     * Sync attendance record archives
     */
    protected function syncAttendanceRecordArchives()
    {
        $synced = [];
        
        try {
            $localArchives = AttendanceRecordArchive::all();
            $payload = $localArchives->map(function ($a) {
                return [
                    'archive_id' => $a->archive_id,
                    'original_record_id' => $a->original_record_id,
                    'faculty_id' => $a->faculty_id,
                    'teaching_load_id' => $a->teaching_load_id,
                    'camera_id' => $a->camera_id,
                    'record_date' => $this->formatDateTime($a->record_date),
                    'record_time_in' => $a->record_time_in,
                    'record_time_out' => $a->record_time_out,
                    'time_duration_seconds' => $a->time_duration_seconds,
                    'record_status' => $a->record_status,
                    'record_remarks' => $a->record_remarks,
                    'school_year' => $a->school_year,
                    'semester' => $a->semester,
                    'archived_at' => $this->formatDateTime($a->archived_at),
                    'archived_by' => $a->archived_by,
                    'archive_notes' => $a->archive_notes,
                    'created_at' => $this->formatDateTime($a->created_at),
                    'updated_at' => $this->formatDateTime($a->updated_at),
                ];
            })->values()->all();
            $resp = $this->pushBulkToCloud('attendance-record-archives', $payload);
            $upserted = $resp['data']['upserted'] ?? 0;
            Log::info('Bulk attendance-record-archives result', ['upserted' => $upserted, 'success' => $resp['success'] ?? null, 'local_count' => count($localArchives)]);
            
            if ($resp['success'] && $upserted > 0) {
                // Only return synced IDs if records were actually upserted
                $synced = $localArchives->pluck('archive_id')->all();
            } elseif ($resp['success'] && $upserted == 0) {
                Log::warning("Attendance record archives sync returned success but 0 records were upserted. Check cloud API logs for validation errors.");
            }
        } catch (\Exception $e) {
            Log::error("Error syncing attendance record archives: " . $e->getMessage());
        }
        
        return $synced;
    }
    
    /**
     * Download file from cloud storage
     * @param string $cloudUrl Cloud URL or path to the file
     * @param string $directory Directory name on local server (e.g., 'faculty_images')
     * @return string|null Returns local relative path or null on failure
     */
    protected function downloadFileFromCloud($cloudUrl, $directory)
    {
        try {
            if (empty($cloudUrl)) {
                return null;
            }
            
            // Build full cloud URL if it's a relative path
            $fullUrl = $cloudUrl;
            if (!filter_var($cloudUrl, FILTER_VALIDATE_URL)) {
                // It's a relative path, prepend cloud domain
                $cloudDomain = rtrim($this->cloudApiUrl, '/api');
                if (strpos($cloudUrl, '/storage/') === 0) {
                    $fullUrl = $cloudDomain . $cloudUrl;
                } elseif (strpos($cloudUrl, 'storage/') === 0) {
                    $fullUrl = $cloudDomain . '/' . $cloudUrl;
                } else {
                    $fullUrl = $cloudDomain . '/storage/' . $directory . '/' . basename($cloudUrl);
                }
            }
            
            // Extract filename from URL
            $filename = basename(parse_url($fullUrl, PHP_URL_PATH));
            if (empty($filename)) {
                $filename = basename($cloudUrl);
            }
            
            // Ensure directory exists
            $localDir = storage_path('app/public/' . $directory);
            if (!is_dir($localDir)) {
                mkdir($localDir, 0755, true);
            }
            
            // Download file
            $response = Http::timeout(300) // 5 minutes for large files
                ->get($fullUrl);
            
            if ($response->successful()) {
                $localPath = $directory . '/' . $filename;
                $fullLocalPath = storage_path('app/public/' . $localPath);
                
                // Save file
                file_put_contents($fullLocalPath, $response->body());
                
                Log::info("Downloaded file from cloud: {$fullUrl} -> {$localPath}");
                return $localPath;
            }
            
            Log::warning("Failed to download file from cloud: {$fullUrl}");
            return null;
            
        } catch (\Exception $e) {
            Log::error("Error downloading file from cloud ({$cloudUrl}): " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Upload file to cloud storage
     * @param string $localPath Relative path from storage/app/public (e.g., 'faculty_images/file.jpg')
     * @param string $directory Directory name on cloud server (e.g., 'faculty_images')
     * @return array|null Returns ['success' => true, 'url' => cloud_url, 'path' => cloud_path] or null on failure
     */
    protected function uploadFileToCloud($localPath, $directory)
    {
        try {
            // Build full local file path
            $fullPath = storage_path('app/public/' . $localPath);
            
            // Check if file exists
            if (!file_exists($fullPath)) {
                Log::warning("File not found for upload: {$fullPath}");
                return null;
            }
            
            $filename = basename($localPath);
            
            // Using multipart file upload with increased timeout for large files
            // Directory structure: storage/app/public/{directory}/
            $response = Http::timeout(300) // 5 minutes for large video files
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->cloudApiKey,
                ])
                ->attach('file', file_get_contents($fullPath), $filename)
                ->post("{$this->cloudApiUrl}/sync/upload/{$directory}");
            
            if ($response->successful()) {
                $result = $response->json();
                return [
                    'success' => true,
                    'url' => $result['url'] ?? null,
                    'path' => $result['path'] ?? null
                ];
            }
            
            Log::error("Failed to upload file to cloud: " . $response->body());
            return null;
            
        } catch (\Exception $e) {
            Log::error("Error uploading file to cloud ({$localPath}): " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Sync faculty images (handles JSON array of image paths)
     * @param string|array $facultyImages JSON string or array of image paths
     * @return string|array Updated image paths with cloud URLs
     */
    protected function syncFacultyImages($facultyImages)
    {
        if (empty($facultyImages)) {
            return $facultyImages;
        }
        
        // Parse JSON if it's a string
        if (is_string($facultyImages)) {
            $images = json_decode($facultyImages, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                // Not valid JSON, return as is
                return $facultyImages;
            }
        } else {
            $images = $facultyImages;
        }
        
        if (!is_array($images)) {
            return $facultyImages;
        }
        
        $cloudImages = [];
        foreach ($images as $imagePath) {
            if (empty($imagePath)) {
                continue;
            }
            
            // Upload image to cloud - directory: storage/app/public/faculty_images/
            $uploadResult = $this->uploadFileToCloud($imagePath, 'faculty_images');
            
            if ($uploadResult && $uploadResult['success']) {
                // Use cloud path/URL instead of local path
                $cloudImages[] = $uploadResult['path'] ?? $uploadResult['url'] ?? $imagePath;
                Log::info("Synced faculty image to cloud: {$imagePath} -> " . ($uploadResult['path'] ?? $uploadResult['url']));
            } else {
                // Keep original path if upload failed
                $cloudImages[] = $imagePath;
                Log::warning("Failed to sync faculty image: {$imagePath}");
            }
        }
        
        // Return as JSON string (same format as database)
        return json_encode($cloudImages);
    }
    
    /**
     * Sync leave slip image
     * @param string $imagePath Local image path
     * @return string Cloud image path/URL
     */
    protected function syncLeaveImage($imagePath)
    {
        if (empty($imagePath)) {
            return $imagePath;
        }
        
        // Directory: storage/app/public/leave_slips/
        $uploadResult = $this->uploadFileToCloud($imagePath, 'leave_slips');
        
        if ($uploadResult && $uploadResult['success']) {
            Log::info("Synced leave slip image to cloud: {$imagePath} -> " . ($uploadResult['path'] ?? $uploadResult['url']));
            return $uploadResult['path'] ?? $uploadResult['url'] ?? $imagePath;
        }
        
        Log::warning("Failed to sync leave slip image: {$imagePath}");
        return $imagePath;
    }
    
    /**
     * Sync pass slip image
     * @param string $imagePath Local image path
     * @return string Cloud image path/URL
     */
    protected function syncPassImage($imagePath)
    {
        if (empty($imagePath)) {
            return $imagePath;
        }
        
        // Directory: storage/app/public/passes/
        $uploadResult = $this->uploadFileToCloud($imagePath, 'passes');
        
        if ($uploadResult && $uploadResult['success']) {
            Log::info("Synced pass slip image to cloud: {$imagePath} -> " . ($uploadResult['path'] ?? $uploadResult['url']));
            return $uploadResult['path'] ?? $uploadResult['url'] ?? $imagePath;
        }
        
        Log::warning("Failed to sync pass slip image: {$imagePath}");
        return $imagePath;
    }
    
    /**
     * Sync stream recording video file
     * @param string $filepath Local video file path
     * @return string Cloud video path/URL
     */
    protected function syncStreamRecordingVideo($filepath)
    {
        if (empty($filepath)) {
            return $filepath;
        }
        
        // Directory: storage/app/public/stream_recordings/
        $uploadResult = $this->uploadFileToCloud($filepath, 'stream_recordings');
        
        if ($uploadResult && $uploadResult['success']) {
            Log::info("Synced stream recording video to cloud: {$filepath} -> " . ($uploadResult['path'] ?? $uploadResult['url']));
            return $uploadResult['path'] ?? $uploadResult['url'] ?? $filepath;
        }
        
        Log::warning("Failed to sync stream recording video: {$filepath}");
        return $filepath;
    }
    
    /**
     * Download faculty images from cloud (handles JSON array of image paths)
     * @param string|array $cloudImages JSON string or array of cloud image paths/URLs
     * @return string JSON string of local image paths
     */
    protected function downloadFacultyImages($cloudImages)
    {
        if (empty($cloudImages)) {
            return $cloudImages;
        }
        
        // Parse JSON if it's a string
        if (is_string($cloudImages)) {
            $images = json_decode($cloudImages, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                // Not valid JSON, try to download as single image
                $localPath = $this->downloadFileFromCloud($cloudImages, 'faculty_images');
                return $localPath ? json_encode([$localPath]) : $cloudImages;
            }
        } else {
            $images = $cloudImages;
        }
        
        if (!is_array($images)) {
            return $cloudImages;
        }
        
        $localImages = [];
        foreach ($images as $imagePath) {
            if (empty($imagePath)) {
                continue;
            }
            
            // Download image from cloud
            $localPath = $this->downloadFileFromCloud($imagePath, 'faculty_images');
            
            if ($localPath) {
                $localImages[] = $localPath;
                Log::info("Downloaded faculty image from cloud: {$imagePath} -> {$localPath}");
            } else {
                // Keep original path if download failed
                $localImages[] = $imagePath;
                Log::warning("Failed to download faculty image: {$imagePath}");
            }
        }
        
        // Return as JSON string (same format as database)
        return json_encode($localImages);
    }
    
    /**
     * Download leave slip image from cloud
     * @param string $cloudImagePath Cloud image path/URL
     * @return string Local image path
     */
    protected function downloadLeaveImage($cloudImagePath)
    {
        if (empty($cloudImagePath)) {
            return $cloudImagePath;
        }
        
        $localPath = $this->downloadFileFromCloud($cloudImagePath, 'leave_slips');
        
        if ($localPath) {
            Log::info("Downloaded leave slip image from cloud: {$cloudImagePath} -> {$localPath}");
            return $localPath;
        }
        
        Log::warning("Failed to download leave slip image: {$cloudImagePath}");
        return $cloudImagePath;
    }
    
    /**
     * Download pass slip image from cloud
     * @param string $cloudImagePath Cloud image path/URL
     * @return string Local image path
     */
    protected function downloadPassImage($cloudImagePath)
    {
        if (empty($cloudImagePath)) {
            return $cloudImagePath;
        }
        
        $localPath = $this->downloadFileFromCloud($cloudImagePath, 'passes');
        
        if ($localPath) {
            Log::info("Downloaded pass slip image from cloud: {$cloudImagePath} -> {$localPath}");
            return $localPath;
        }
        
        Log::warning("Failed to download pass slip image: {$cloudImagePath}");
        return $cloudImagePath;
    }
    
    /**
     * Download stream recording video from cloud
     * @param string $cloudVideoPath Cloud video path/URL
     * @return string Local video path
     */
    protected function downloadStreamRecordingVideo($cloudVideoPath)
    {
        if (empty($cloudVideoPath)) {
            return $cloudVideoPath;
        }
        
        $localPath = $this->downloadFileFromCloud($cloudVideoPath, 'stream_recordings');
        
        if ($localPath) {
            Log::info("Downloaded stream recording video from cloud: {$cloudVideoPath} -> {$localPath}");
            return $localPath;
        }
        
        Log::warning("Failed to download stream recording video: {$cloudVideoPath}");
        return $cloudVideoPath;
    }
    
    /**
     * Check sync status
     */
    public function getSyncStatus()
    {
        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->cloudApiKey,
                    'Accept' => 'application/json',
                ])
                ->get("{$this->cloudApiUrl}/sync/status");
            
            if ($response->successful()) {
                return $response->json();
            }
            
            return ['status' => 'error', 'message' => 'Cannot connect to cloud'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Sync all data FROM cloud TO local
     */
    public function syncAllFromCloud()
    {
        $results = [
            'success' => true,
            'synced' => [],
            'errors' => [],
            'summary' => []
        ];
        
        try {
            Log::info('Starting cloud to local sync...');
            
            // Sync in order of dependencies - users first
            $results['synced']['users'] = $this->syncUsersFromCloud();
            $results['synced']['subjects'] = $this->syncSubjectsFromCloud();
            $results['synced']['rooms'] = $this->syncRoomsFromCloud();
            $results['synced']['cameras'] = $this->syncCamerasFromCloud();
            $results['synced']['faculties'] = $this->syncFacultiesFromCloud();
            $results['synced']['teaching_loads'] = $this->syncTeachingLoadsFromCloud();
            $results['synced']['attendance_records'] = $this->syncAttendanceRecordsFromCloud();
            $results['synced']['leaves'] = $this->syncLeavesFromCloud();
            $results['synced']['passes'] = $this->syncPassesFromCloud();
            $results['synced']['recognition_logs'] = $this->syncRecognitionLogsFromCloud();
            $results['synced']['stream_recordings'] = $this->syncStreamRecordingsFromCloud();
            $results['synced']['activity_logs'] = $this->syncActivityLogsFromCloud();
            $results['synced']['teaching_load_archives'] = $this->syncTeachingLoadArchivesFromCloud();
            $results['synced']['attendance_record_archives'] = $this->syncAttendanceRecordArchivesFromCloud();
            
            // Calculate summary
            foreach ($results['synced'] as $key => $value) {
                $results['summary'][$key] = count($value);
            }
            
            Log::info('Cloud to local sync completed successfully', $results['summary']);
            
        } catch (\Exception $e) {
            Log::error('Cloud to local sync failed: ' . $e->getMessage());
            $results['success'] = false;
            $results['errors'][] = $e->getMessage();
        }
        
        return $results;
    }
    
    /**
     * Fetch bulk data from cloud
     */
    protected function fetchBulkFromCloud(string $endpoint, array $params = [])
    {
        try {
            $response = Http::timeout(120)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->cloudApiKey,
                    'Accept' => 'application/json',
                ])
                ->get("{$this->cloudApiUrl}/sync/{$endpoint}", $params);
            
            if ($response->successful()) {
                $data = $response->json();
                // Handle both array and object responses
                if (isset($data['data']) && is_array($data['data'])) {
                    return $data['data'];
                } elseif (is_array($data)) {
                    return $data;
                }
                return [];
            }
            
            Log::error("Failed to fetch from cloud {$endpoint}: " . $response->body());
            return [];
        } catch (\Exception $e) {
            Log::error("Error fetching from cloud {$endpoint}: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Sync users from cloud to local
     */
    protected function syncUsersFromCloud()
    {
        $synced = [];
        
        try {
            $cloudUsers = $this->fetchBulkFromCloud('users');
            
            if (empty($cloudUsers)) {
                Log::info('No users found in cloud');
                return $synced;
            }
            
            foreach ($cloudUsers as $cloudUser) {
                try {
                    DB::table('tbl_user')->upsert([
                        [
                            'user_id' => $cloudUser['user_id'],
                            'user_role' => $cloudUser['user_role'] ?? null,
                            'user_department' => $cloudUser['user_department'] ?? null,
                            'user_fname' => $cloudUser['user_fname'] ?? null,
                            'user_lname' => $cloudUser['user_lname'] ?? null,
                            'username' => $cloudUser['username'] ?? null,
                            'user_password' => $cloudUser['user_password'] ?? null,
                            'created_at' => $this->formatDateTime($cloudUser['created_at'] ?? null),
                            'updated_at' => $this->formatDateTime($cloudUser['updated_at'] ?? null),
                        ]
                    ], ['user_id'], ['user_role', 'user_department', 'user_fname', 'user_lname', 'username', 'user_password', 'updated_at']);
                    
                    $synced[] = $cloudUser['user_id'];
                } catch (\Exception $e) {
                    Log::error("Error syncing user {$cloudUser['user_id']} from cloud: " . $e->getMessage());
                }
            }
            
            Log::info("Synced " . count($synced) . " users from cloud to local");
        } catch (\Exception $e) {
            Log::error("Error syncing users from cloud: " . $e->getMessage());
        }
        
        return $synced;
    }
    
    /**
     * Sync subjects from cloud to local
     */
    protected function syncSubjectsFromCloud()
    {
        $synced = [];
        
        try {
            $cloudSubjects = $this->fetchBulkFromCloud('subjects');
            
            if (empty($cloudSubjects)) {
                return $synced;
            }
            
            foreach ($cloudSubjects as $cloudSubject) {
                try {
                    DB::table('tbl_subject')->upsert([
                        [
                            'subject_id' => $cloudSubject['subject_id'],
                            'subject_code' => $cloudSubject['subject_code'] ?? null,
                            'subject_description' => $cloudSubject['subject_description'] ?? null,
                            'department' => $cloudSubject['department'] ?? null,
                            'created_at' => $this->formatDateTime($cloudSubject['created_at'] ?? null),
                            'updated_at' => $this->formatDateTime($cloudSubject['updated_at'] ?? null),
                        ]
                    ], ['subject_id'], ['subject_code', 'subject_description', 'department', 'updated_at']);
                    
                    $synced[] = $cloudSubject['subject_id'];
                } catch (\Exception $e) {
                    Log::error("Error syncing subject {$cloudSubject['subject_id']} from cloud: " . $e->getMessage());
                }
            }
            
            Log::info("Synced " . count($synced) . " subjects from cloud to local");
        } catch (\Exception $e) {
            Log::error("Error syncing subjects from cloud: " . $e->getMessage());
        }
        
        return $synced;
    }
    
    /**
     * Sync rooms from cloud to local
     */
    protected function syncRoomsFromCloud()
    {
        $synced = [];
        
        try {
            $cloudRooms = $this->fetchBulkFromCloud('rooms');
            
            if (empty($cloudRooms)) {
                return $synced;
            }
            
            foreach ($cloudRooms as $cloudRoom) {
                try {
                    DB::table('tbl_room')->upsert([
                        [
                            'room_no' => $cloudRoom['room_no'],
                            'room_name' => $cloudRoom['room_name'] ?? null,
                            'room_building_no' => $cloudRoom['room_building_no'] ?? null,
                        ]
                    ], ['room_no'], ['room_name', 'room_building_no']);
                    
                    $synced[] = $cloudRoom['room_no'];
                } catch (\Exception $e) {
                    Log::error("Error syncing room {$cloudRoom['room_no']} from cloud: " . $e->getMessage());
                }
            }
            
            Log::info("Synced " . count($synced) . " rooms from cloud to local");
        } catch (\Exception $e) {
            Log::error("Error syncing rooms from cloud: " . $e->getMessage());
        }
        
        return $synced;
    }
    
    /**
     * Sync cameras from cloud to local
     */
    protected function syncCamerasFromCloud()
    {
        $synced = [];
        
        try {
            $cloudCameras = $this->fetchBulkFromCloud('cameras');
            
            if (empty($cloudCameras)) {
                return $synced;
            }
            
            foreach ($cloudCameras as $cloudCamera) {
                try {
                    DB::table('tbl_camera')->upsert([
                        [
                            'camera_id' => $cloudCamera['camera_id'],
                            'camera_name' => $cloudCamera['camera_name'] ?? null,
                            'camera_ip_address' => $cloudCamera['camera_ip_address'] ?? null,
                            'camera_username' => $cloudCamera['camera_username'] ?? null,
                            'camera_password' => $cloudCamera['camera_password'] ?? null,
                            'camera_live_feed' => $cloudCamera['camera_live_feed'] ?? null,
                            'room_no' => $cloudCamera['room_no'] ?? null,
                        ]
                    ], ['camera_id'], ['camera_name', 'camera_ip_address', 'camera_username', 'camera_password', 'camera_live_feed', 'room_no']);
                    
                    $synced[] = $cloudCamera['camera_id'];
                } catch (\Exception $e) {
                    Log::error("Error syncing camera {$cloudCamera['camera_id']} from cloud: " . $e->getMessage());
                }
            }
            
            Log::info("Synced " . count($synced) . " cameras from cloud to local");
        } catch (\Exception $e) {
            Log::error("Error syncing cameras from cloud: " . $e->getMessage());
        }
        
        return $synced;
    }
    
    /**
     * Sync faculties from cloud to local
     */
    protected function syncFacultiesFromCloud()
    {
        $synced = [];
        
        try {
            $cloudFaculties = $this->fetchBulkFromCloud('faculties');
            
            if (empty($cloudFaculties)) {
                return $synced;
            }
            
            foreach ($cloudFaculties as $cloudFaculty) {
                try {
                    // Download faculty images from cloud
                    $localImages = $this->downloadFacultyImages($cloudFaculty['faculty_images'] ?? null);
                    
                    DB::table('tbl_faculty')->upsert([
                        [
                            'faculty_id' => $cloudFaculty['faculty_id'],
                            'faculty_fname' => $cloudFaculty['faculty_fname'] ?? null,
                            'faculty_lname' => $cloudFaculty['faculty_lname'] ?? null,
                            'faculty_department' => $cloudFaculty['faculty_department'] ?? null,
                            'faculty_images' => $localImages,
                            'faculty_face_embedding' => $cloudFaculty['faculty_face_embedding'] ?? null,
                            'created_at' => $this->formatDateTime($cloudFaculty['created_at'] ?? null),
                            'updated_at' => $this->formatDateTime($cloudFaculty['updated_at'] ?? null),
                        ]
                    ], ['faculty_id'], ['faculty_fname', 'faculty_lname', 'faculty_department', 'faculty_images', 'faculty_face_embedding', 'updated_at']);
                    
                    $synced[] = $cloudFaculty['faculty_id'];
                } catch (\Exception $e) {
                    Log::error("Error syncing faculty {$cloudFaculty['faculty_id']} from cloud: " . $e->getMessage());
                }
            }
            
            Log::info("Synced " . count($synced) . " faculties from cloud to local");
        } catch (\Exception $e) {
            Log::error("Error syncing faculties from cloud: " . $e->getMessage());
        }
        
        return $synced;
    }
    
    /**
     * Sync teaching loads from cloud to local
     */
    protected function syncTeachingLoadsFromCloud()
    {
        $synced = [];
        
        try {
            $cloudLoads = $this->fetchBulkFromCloud('teaching-loads');
            
            if (empty($cloudLoads)) {
                return $synced;
            }
            
            foreach ($cloudLoads as $cloudLoad) {
                try {
                    DB::table('tbl_teaching_load')->upsert([
                        [
                            'teaching_load_id' => $cloudLoad['teaching_load_id'],
                            'faculty_id' => $cloudLoad['faculty_id'] ?? null,
                            'teaching_load_course_code' => $cloudLoad['teaching_load_course_code'] ?? null,
                            'teaching_load_subject' => $cloudLoad['teaching_load_subject'] ?? null,
                            'teaching_load_day_of_week' => $cloudLoad['teaching_load_day_of_week'] ?? null,
                            'teaching_load_class_section' => $cloudLoad['teaching_load_class_section'] ?? null,
                            'teaching_load_time_in' => $cloudLoad['teaching_load_time_in'] ?? null,
                            'teaching_load_time_out' => $cloudLoad['teaching_load_time_out'] ?? null,
                            'room_no' => $cloudLoad['room_no'] ?? null,
                            'created_at' => $this->formatDateTime($cloudLoad['created_at'] ?? null),
                            'updated_at' => $this->formatDateTime($cloudLoad['updated_at'] ?? null),
                        ]
                    ], ['teaching_load_id'], ['faculty_id', 'teaching_load_course_code', 'teaching_load_subject', 'teaching_load_day_of_week', 'teaching_load_class_section', 'teaching_load_time_in', 'teaching_load_time_out', 'room_no', 'updated_at']);
                    
                    $synced[] = $cloudLoad['teaching_load_id'];
                } catch (\Exception $e) {
                    Log::error("Error syncing teaching load {$cloudLoad['teaching_load_id']} from cloud: " . $e->getMessage());
                }
            }
            
            Log::info("Synced " . count($synced) . " teaching loads from cloud to local");
        } catch (\Exception $e) {
            Log::error("Error syncing teaching loads from cloud: " . $e->getMessage());
        }
        
        return $synced;
    }
    
    /**
     * Sync attendance records from cloud to local
     */
    protected function syncAttendanceRecordsFromCloud()
    {
        $synced = [];
        
        try {
            $cloudRecords = $this->fetchBulkFromCloud('attendance-records', ['days' => 30]);
            
            if (empty($cloudRecords)) {
                return $synced;
            }
            
            foreach ($cloudRecords as $cloudRecord) {
                try {
                    DB::table('tbl_attendance_record')->upsert([
                        [
                            'record_id' => $cloudRecord['record_id'],
                            'record_date' => $this->formatDateTime($cloudRecord['record_date'] ?? null),
                            'faculty_id' => $cloudRecord['faculty_id'] ?? null,
                            'teaching_load_id' => $cloudRecord['teaching_load_id'] ?? null,
                            'record_time_in' => $cloudRecord['record_time_in'] ?? null,
                            'record_time_out' => $cloudRecord['record_time_out'] ?? null,
                            'time_duration_seconds' => $cloudRecord['time_duration_seconds'] ?? null,
                            'record_status' => $cloudRecord['record_status'] ?? null,
                            'record_remarks' => $cloudRecord['record_remarks'] ?? null,
                            'camera_id' => $cloudRecord['camera_id'] ?? null,
                        ]
                    ], ['record_id'], ['record_date', 'faculty_id', 'teaching_load_id', 'record_time_in', 'record_time_out', 'time_duration_seconds', 'record_status', 'record_remarks', 'camera_id']);
                    
                    $synced[] = $cloudRecord['record_id'];
                } catch (\Exception $e) {
                    Log::error("Error syncing attendance record {$cloudRecord['record_id']} from cloud: " . $e->getMessage());
                }
            }
            
            Log::info("Synced " . count($synced) . " attendance records from cloud to local");
        } catch (\Exception $e) {
            Log::error("Error syncing attendance records from cloud: " . $e->getMessage());
        }
        
        return $synced;
    }
    
    /**
     * Sync leaves from cloud to local
     */
    protected function syncLeavesFromCloud()
    {
        $synced = [];
        
        try {
            $cloudLeaves = $this->fetchBulkFromCloud('leaves', ['days' => 90]);
            
            if (empty($cloudLeaves)) {
                return $synced;
            }
            
            foreach ($cloudLeaves as $cloudLeave) {
                try {
                    // Download leave slip image from cloud
                    $localImagePath = $this->downloadLeaveImage($cloudLeave['lp_image'] ?? null);
                    
                    DB::table('tbl_leave_pass')->upsert([
                        [
                            'lp_id' => $cloudLeave['lp_id'],
                            'faculty_id' => $cloudLeave['faculty_id'] ?? null,
                            'lp_type' => $cloudLeave['lp_type'] ?? 'Leave',
                            'lp_purpose' => $cloudLeave['lp_purpose'] ?? null,
                            'pass_slip_itinerary' => $cloudLeave['pass_slip_itinerary'] ?? null,
                            'pass_slip_date' => $this->formatDateTime($cloudLeave['pass_slip_date'] ?? null),
                            'pass_slip_departure_time' => $cloudLeave['pass_slip_departure_time'] ?? null,
                            'pass_slip_arrival_time' => $cloudLeave['pass_slip_arrival_time'] ?? null,
                            'lp_start_date' => $this->formatDateTime($cloudLeave['lp_start_date'] ?? null),
                            'lp_end_date' => $this->formatDateTime($cloudLeave['lp_end_date'] ?? null),
                            'lp_image' => $localImagePath,
                            'created_at' => $this->formatDateTime($cloudLeave['created_at'] ?? null),
                            'updated_at' => $this->formatDateTime($cloudLeave['updated_at'] ?? null),
                        ]
                    ], ['lp_id'], ['faculty_id', 'lp_type', 'lp_purpose', 'pass_slip_itinerary', 'pass_slip_date', 'pass_slip_departure_time', 'pass_slip_arrival_time', 'lp_start_date', 'lp_end_date', 'lp_image', 'updated_at']);
                    
                    $synced[] = $cloudLeave['lp_id'];
                } catch (\Exception $e) {
                    Log::error("Error syncing leave {$cloudLeave['lp_id']} from cloud: " . $e->getMessage());
                }
            }
            
            Log::info("Synced " . count($synced) . " leaves from cloud to local");
        } catch (\Exception $e) {
            Log::error("Error syncing leaves from cloud: " . $e->getMessage());
        }
        
        return $synced;
    }
    
    /**
     * Sync passes from cloud to local
     */
    protected function syncPassesFromCloud()
    {
        $synced = [];
        
        try {
            $cloudPasses = $this->fetchBulkFromCloud('passes', ['days' => 90]);
            
            if (empty($cloudPasses)) {
                return $synced;
            }
            
            foreach ($cloudPasses as $cloudPass) {
                try {
                    // Download pass slip image from cloud
                    $localImagePath = $this->downloadPassImage($cloudPass['lp_image'] ?? null);
                    
                    DB::table('tbl_leave_pass')->upsert([
                        [
                            'lp_id' => $cloudPass['lp_id'],
                            'faculty_id' => $cloudPass['faculty_id'] ?? null,
                            'lp_type' => $cloudPass['lp_type'] ?? 'Pass',
                            'lp_purpose' => $cloudPass['lp_purpose'] ?? null,
                            'pass_slip_itinerary' => $cloudPass['pass_slip_itinerary'] ?? null,
                            'pass_slip_date' => $this->formatDateTime($cloudPass['pass_slip_date'] ?? null),
                            'pass_slip_departure_time' => $cloudPass['pass_slip_departure_time'] ?? null,
                            'pass_slip_arrival_time' => $cloudPass['pass_slip_arrival_time'] ?? null,
                            'lp_start_date' => $this->formatDateTime($cloudPass['lp_start_date'] ?? null),
                            'lp_end_date' => $this->formatDateTime($cloudPass['lp_end_date'] ?? null),
                            'lp_image' => $localImagePath,
                            'created_at' => $this->formatDateTime($cloudPass['created_at'] ?? null),
                            'updated_at' => $this->formatDateTime($cloudPass['updated_at'] ?? null),
                        ]
                    ], ['lp_id'], ['faculty_id', 'lp_type', 'lp_purpose', 'pass_slip_itinerary', 'pass_slip_date', 'pass_slip_departure_time', 'pass_slip_arrival_time', 'lp_start_date', 'lp_end_date', 'lp_image', 'updated_at']);
                    
                    $synced[] = $cloudPass['lp_id'];
                } catch (\Exception $e) {
                    Log::error("Error syncing pass {$cloudPass['lp_id']} from cloud: " . $e->getMessage());
                }
            }
            
            Log::info("Synced " . count($synced) . " passes from cloud to local");
        } catch (\Exception $e) {
            Log::error("Error syncing passes from cloud: " . $e->getMessage());
        }
        
        return $synced;
    }
    
    /**
     * Sync recognition logs from cloud to local
     */
    protected function syncRecognitionLogsFromCloud()
    {
        $synced = [];
        
        try {
            $cloudLogs = $this->fetchBulkFromCloud('recognition-logs', ['days' => 7]);
            
            if (empty($cloudLogs)) {
                return $synced;
            }
            
            foreach ($cloudLogs as $cloudLog) {
                try {
                    DB::table('tbl_recognition_logs')->upsert([
                        [
                            'log_id' => $cloudLog['log_id'],
                            'faculty_id' => $cloudLog['faculty_id'] ?? null,
                            'camera_id' => $cloudLog['camera_id'] ?? null,
                            'teaching_load_id' => $cloudLog['teaching_load_id'] ?? null,
                            'recognition_time' => $this->formatDateTime($cloudLog['recognition_time'] ?? null),
                            'camera_name' => $cloudLog['camera_name'] ?? null,
                            'room_name' => $cloudLog['room_name'] ?? null,
                            'building_no' => $cloudLog['building_no'] ?? null,
                            'faculty_name' => $cloudLog['faculty_name'] ?? null,
                            'status' => $cloudLog['status'] ?? null,
                            'distance' => $cloudLog['distance'] ?? null,
                        ]
                    ], ['log_id'], ['faculty_id', 'camera_id', 'teaching_load_id', 'recognition_time', 'camera_name', 'room_name', 'building_no', 'faculty_name', 'status', 'distance']);
                    
                    $synced[] = $cloudLog['log_id'];
                } catch (\Exception $e) {
                    Log::error("Error syncing recognition log {$cloudLog['log_id']} from cloud: " . $e->getMessage());
                }
            }
            
            Log::info("Synced " . count($synced) . " recognition logs from cloud to local");
        } catch (\Exception $e) {
            Log::error("Error syncing recognition logs from cloud: " . $e->getMessage());
        }
        
        return $synced;
    }
    
    /**
     * Sync stream recordings from cloud to local
     */
    protected function syncStreamRecordingsFromCloud()
    {
        $synced = [];
        
        try {
            $cloudRecordings = $this->fetchBulkFromCloud('stream-recordings', ['days' => 7]);
            
            if (empty($cloudRecordings)) {
                return $synced;
            }
            
            foreach ($cloudRecordings as $cloudRecording) {
                try {
                    // Download video file from cloud (use filepath or filename)
                    $cloudVideoPath = $cloudRecording['filepath'] ?? $cloudRecording['filename'] ?? null;
                    $localVideoPath = $this->downloadStreamRecordingVideo($cloudVideoPath);
                    
                    // Extract filename if we have filepath
                    $localFilename = $cloudRecording['filename'] ?? null;
                    if ($localVideoPath && !$localFilename) {
                        $localFilename = basename($localVideoPath);
                    }
                    
                    DB::table('tbl_stream_recordings')->upsert([
                        [
                            'recording_id' => $cloudRecording['recording_id'],
                            'camera_id' => $cloudRecording['camera_id'] ?? null,
                            'start_time' => $this->formatDateTime($cloudRecording['start_time'] ?? null),
                            'duration' => $cloudRecording['duration'] ?? null,
                            'frames' => $cloudRecording['frames'] ?? null,
                            'filepath' => $localVideoPath,
                            'filename' => $localFilename,
                            'file_size' => $cloudRecording['file_size'] ?? null,
                            'created_at' => $this->formatDateTime($cloudRecording['created_at'] ?? null),
                            'updated_at' => $this->formatDateTime($cloudRecording['updated_at'] ?? null),
                        ]
                    ], ['recording_id'], ['camera_id', 'start_time', 'duration', 'frames', 'filepath', 'filename', 'file_size', 'updated_at']);
                    
                    $synced[] = $cloudRecording['recording_id'];
                } catch (\Exception $e) {
                    Log::error("Error syncing stream recording {$cloudRecording['recording_id']} from cloud: " . $e->getMessage());
                }
            }
            
            Log::info("Synced " . count($synced) . " stream recordings from cloud to local");
        } catch (\Exception $e) {
            Log::error("Error syncing stream recordings from cloud: " . $e->getMessage());
        }
        
        return $synced;
    }
    
    /**
     * Sync activity logs from cloud to local
     */
    protected function syncActivityLogsFromCloud()
    {
        $synced = [];
        
        try {
            $cloudLogs = $this->fetchBulkFromCloud('activity-logs');
            
            if (empty($cloudLogs)) {
                return $synced;
            }
            
            foreach ($cloudLogs as $cloudLog) {
                try {
                    DB::table('tbl_activity_logs')->upsert([
                        [
                            'logs_id' => $cloudLog['logs_id'],
                            'user_id' => $cloudLog['user_id'] ?? null,
                            'logs_action' => $cloudLog['logs_action'] ?? null,
                            'logs_description' => $cloudLog['logs_description'] ?? null,
                            'logs_timestamp' => $this->formatDateTime($cloudLog['logs_timestamp'] ?? null),
                            'logs_module' => $cloudLog['logs_module'] ?? null,
                        ]
                    ], ['logs_id'], ['user_id', 'logs_action', 'logs_description', 'logs_timestamp', 'logs_module']);
                    
                    $synced[] = $cloudLog['logs_id'];
                } catch (\Exception $e) {
                    Log::error("Error syncing activity log {$cloudLog['logs_id']} from cloud: " . $e->getMessage());
                }
            }
            
            Log::info("Synced " . count($synced) . " activity logs from cloud to local");
        } catch (\Exception $e) {
            Log::error("Error syncing activity logs from cloud: " . $e->getMessage());
        }
        
        return $synced;
    }
    
    /**
     * Sync teaching load archives from cloud to local
     */
    protected function syncTeachingLoadArchivesFromCloud()
    {
        $synced = [];
        
        try {
            $cloudArchives = $this->fetchBulkFromCloud('teaching-load-archives');
            
            if (empty($cloudArchives)) {
                return $synced;
            }
            
            foreach ($cloudArchives as $cloudArchive) {
                try {
                    DB::table('tbl_teaching_load_archive')->upsert([
                        [
                            'archive_id' => $cloudArchive['archive_id'],
                            'original_teaching_load_id' => $cloudArchive['original_teaching_load_id'] ?? null,
                            'faculty_id' => $cloudArchive['faculty_id'] ?? null,
                            'teaching_load_course_code' => $cloudArchive['teaching_load_course_code'] ?? null,
                            'teaching_load_subject' => $cloudArchive['teaching_load_subject'] ?? null,
                            'teaching_load_class_section' => $cloudArchive['teaching_load_class_section'] ?? null,
                            'teaching_load_day_of_week' => $cloudArchive['teaching_load_day_of_week'] ?? null,
                            'teaching_load_time_in' => $cloudArchive['teaching_load_time_in'] ?? null,
                            'teaching_load_time_out' => $cloudArchive['teaching_load_time_out'] ?? null,
                            'room_no' => $cloudArchive['room_no'] ?? null,
                            'school_year' => $cloudArchive['school_year'] ?? null,
                            'semester' => $cloudArchive['semester'] ?? null,
                            'archived_at' => $this->formatDateTime($cloudArchive['archived_at'] ?? null),
                            'archived_by' => $cloudArchive['archived_by'] ?? null,
                            'archive_notes' => $cloudArchive['archive_notes'] ?? null,
                        ]
                    ], ['archive_id'], ['original_teaching_load_id', 'faculty_id', 'teaching_load_course_code', 'teaching_load_subject', 'teaching_load_class_section', 'teaching_load_day_of_week', 'teaching_load_time_in', 'teaching_load_time_out', 'room_no', 'school_year', 'semester', 'archived_at', 'archived_by', 'archive_notes']);
                    
                    $synced[] = $cloudArchive['archive_id'];
                } catch (\Exception $e) {
                    Log::error("Error syncing teaching load archive {$cloudArchive['archive_id']} from cloud: " . $e->getMessage());
                }
            }
            
            Log::info("Synced " . count($synced) . " teaching load archives from cloud to local");
        } catch (\Exception $e) {
            Log::error("Error syncing teaching load archives from cloud: " . $e->getMessage());
        }
        
        return $synced;
    }
    
    /**
     * Sync attendance record archives from cloud to local
     */
    protected function syncAttendanceRecordArchivesFromCloud()
    {
        $synced = [];
        
        try {
            $cloudArchives = $this->fetchBulkFromCloud('attendance-record-archives');
            
            if (empty($cloudArchives)) {
                return $synced;
            }
            
            foreach ($cloudArchives as $cloudArchive) {
                try {
                    DB::table('tbl_attendance_record_archive')->upsert([
                        [
                            'archive_id' => $cloudArchive['archive_id'],
                            'original_record_id' => $cloudArchive['original_record_id'] ?? null,
                            'faculty_id' => $cloudArchive['faculty_id'] ?? null,
                            'teaching_load_id' => $cloudArchive['teaching_load_id'] ?? null,
                            'camera_id' => $cloudArchive['camera_id'] ?? null,
                            'record_date' => $this->formatDateTime($cloudArchive['record_date'] ?? null),
                            'record_time_in' => $cloudArchive['record_time_in'] ?? null,
                            'record_time_out' => $cloudArchive['record_time_out'] ?? null,
                            'time_duration_seconds' => $cloudArchive['time_duration_seconds'] ?? null,
                            'record_status' => $cloudArchive['record_status'] ?? null,
                            'record_remarks' => $cloudArchive['record_remarks'] ?? null,
                            'school_year' => $cloudArchive['school_year'] ?? null,
                            'semester' => $cloudArchive['semester'] ?? null,
                            'archived_at' => $this->formatDateTime($cloudArchive['archived_at'] ?? null),
                            'archived_by' => $cloudArchive['archived_by'] ?? null,
                            'archive_notes' => $cloudArchive['archive_notes'] ?? null,
                            'created_at' => $this->formatDateTime($cloudArchive['created_at'] ?? null),
                            'updated_at' => $this->formatDateTime($cloudArchive['updated_at'] ?? null),
                        ]
                    ], ['archive_id'], ['original_record_id', 'faculty_id', 'teaching_load_id', 'camera_id', 'record_date', 'record_time_in', 'record_time_out', 'time_duration_seconds', 'record_status', 'record_remarks', 'school_year', 'semester', 'archived_at', 'archived_by', 'archive_notes', 'updated_at']);
                    
                    $synced[] = $cloudArchive['archive_id'];
                } catch (\Exception $e) {
                    Log::error("Error syncing attendance record archive {$cloudArchive['archive_id']} from cloud: " . $e->getMessage());
                }
            }
            
            Log::info("Synced " . count($synced) . " attendance record archives from cloud to local");
        } catch (\Exception $e) {
            Log::error("Error syncing attendance record archives from cloud: " . $e->getMessage());
        }
        
        return $synced;
    }
}

