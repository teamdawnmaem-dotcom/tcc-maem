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
}

