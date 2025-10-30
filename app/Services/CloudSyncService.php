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
            
            // Sync in order of dependencies
            $results['synced']['subjects'] = $this->syncSubjects();
            $results['synced']['users'] = $this->syncUsers();
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
                return [
                    'faculty_id' => $faculty->faculty_id,
                    'faculty_fname' => $faculty->faculty_fname,
                    'faculty_lname' => $faculty->faculty_lname,
                    'faculty_department' => $faculty->faculty_department,
                    'faculty_images' => $faculty->faculty_images,
                    'faculty_face_embedding' => $faculty->faculty_face_embedding,
                    'created_at' => $faculty->created_at,
                    'updated_at' => $faculty->updated_at,
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
                    'created_at' => $load->created_at,
                    'updated_at' => $load->updated_at,
                ];
            })->values()->all();
            $resp = $this->pushBulkToCloud('teaching-loads', $payload);
            if ($resp['success']) {
                $synced = $localLoads->pluck('teaching_load_id')->all();
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
                    'record_date' => $record->record_date,
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
                return [
                    'lp_id' => $leave->lp_id,
                    'faculty_id' => $leave->faculty_id,
                    'lp_type' => $leave->lp_type,
                    'lp_purpose' => $leave->lp_purpose,
                    'leave_start_date' => $leave->leave_start_date,
                    'leave_end_date' => $leave->leave_end_date,
                    'lp_image' => $leave->lp_image,
                    'created_at' => $leave->created_at,
                    'updated_at' => $leave->updated_at,
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
                return [
                    'lp_id' => $pass->lp_id,
                    'faculty_id' => $pass->faculty_id,
                    'lp_type' => $pass->lp_type,
                    'lp_purpose' => $pass->lp_purpose,
                    'pass_slip_itinerary' => $pass->pass_slip_itinerary,
                    'pass_slip_date' => $pass->pass_slip_date,
                    'pass_slip_departure_time' => $pass->pass_slip_departure_time,
                    'pass_slip_arrival_time' => $pass->pass_slip_arrival_time,
                    'lp_image' => $pass->lp_image,
                    'created_at' => $pass->created_at,
                    'updated_at' => $pass->updated_at,
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
                    'recognition_time' => $log->recognition_time,
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
            if ($resp['success']) {
                $synced = $localLogs->pluck('log_id')->all();
            }
        } catch (\Exception $e) {
            Log::error("Error syncing recognition logs: " . $e->getMessage());
        }
        
        return $synced;
    }
    
    /**
     * Sync stream recordings (metadata only, videos handled separately)
     */
    protected function syncStreamRecordings()
    {
        $synced = [];
        
        try {
            // Sync ALL stream recordings
            $localRecordings = StreamRecording::all();
            $payload = $localRecordings->map(function ($recording) {
                return [
                    'recording_id' => $recording->recording_id,
                    'camera_id' => $recording->camera_id,
                    'filename' => $recording->filename,
                    'filepath' => $recording->filepath,
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
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->cloudApiKey,
                    'Accept' => 'application/json',
                ])
                ->get("{$this->cloudApiUrl}/{$endpoint}", $params);
            
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
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->cloudApiKey,
                    'Accept' => 'application/json',
                ])
                ->post("{$this->cloudApiUrl}/{$endpoint}", $data);
            
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
            foreach (array_chunk($records, 500) as $chunk) {
                $response = Http::timeout(60)
                    ->withHeaders([
                        'Authorization' => 'Bearer ' . $this->cloudApiKey,
                        'Accept' => 'application/json',
                    ])
                    ->post("{$this->cloudApiUrl}/bulk/{$endpoint}", ['records' => $chunk]);
                if (!$response->successful()) {
                    Log::error("Bulk push failed for {$endpoint}: " . $response->body());
                    return ['success' => false, 'error' => $response->body()];
                }
                $json = $response->json();
                $total += (int)($json['upserted'] ?? count($chunk));
            }
            return ['success' => true, 'data' => ['upserted' => $total]];
        } catch (\Exception $e) {
            Log::error("Error bulk pushing to cloud {$endpoint}: " . $e->getMessage());
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
            $cloudSubjects = $this->getCloudData('subjects');
            $cloudSubjectIds = collect($cloudSubjects)->pluck('subject_id')->toArray();
            
            foreach ($localSubjects as $subject) {
                if (!in_array($subject->subject_id, $cloudSubjectIds)) {
                    $response = $this->pushToCloud('subjects', [
                        'subject_id' => $subject->subject_id,
                        'subject_code' => $subject->subject_code,
                        'subject_description' => $subject->subject_description,
                        'department' => $subject->department,
                        'created_at' => $subject->created_at,
                        'updated_at' => $subject->updated_at,
                    ]);
                    
                    if ($response['success']) {
                        $synced[] = $subject->subject_id;
                        Log::info("Synced subject {$subject->subject_id} to cloud");
                    }
                }
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
            $cloudUsers = $this->getCloudData('users');
            $cloudUserIds = collect($cloudUsers)->pluck('user_id')->toArray();
            
            foreach ($localUsers as $user) {
                if (!in_array($user->user_id, $cloudUserIds)) {
                    $response = $this->pushToCloud('users', [
                        'user_id' => $user->user_id,
                        'user_role' => $user->user_role,
                        'user_department' => $user->user_department,
                        'user_fname' => $user->user_fname,
                        'user_lname' => $user->user_lname,
                        'username' => $user->username,
                        'user_password' => $user->user_password, // Already hashed
                        'created_at' => $user->created_at,
                        'updated_at' => $user->updated_at,
                    ]);
                    
                    if ($response['success']) {
                        $synced[] = $user->user_id;
                        Log::info("Synced user {$user->user_id} to cloud");
                    }
                }
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
            // Sync ALL activity logs
            $localLogs = ActivityLog::all();
            $cloudLogs = $this->getCloudData('activity-logs');
            $cloudLogIds = collect($cloudLogs)->pluck('logs_id')->toArray();
            
            foreach ($localLogs as $log) {
                if (!in_array($log->logs_id, $cloudLogIds)) {
                    $response = $this->pushToCloud('activity-logs', [
                        'logs_id' => $log->logs_id,
                        'user_id' => $log->user_id,
                        'logs_action' => $log->logs_action,
                        'logs_description' => $log->logs_description,
                        'logs_timestamp' => $log->logs_timestamp,
                        'logs_module' => $log->logs_module,
                    ]);
                    
                    if ($response['success']) {
                        $synced[] = $log->logs_id;
                        Log::info("Synced activity log {$log->logs_id} to cloud");
                    }
                }
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
            // Sync all archived records (they're historical data)
            $localArchives = TeachingLoadArchive::all();
            $cloudArchives = $this->getCloudData('teaching-load-archives');
            $cloudArchiveIds = collect($cloudArchives)->pluck('archive_id')->toArray();
            
            foreach ($localArchives as $archive) {
                if (!in_array($archive->archive_id, $cloudArchiveIds)) {
                    $response = $this->pushToCloud('teaching-load-archives', [
                        'archive_id' => $archive->archive_id,
                        'original_teaching_load_id' => $archive->original_teaching_load_id,
                        'faculty_id' => $archive->faculty_id,
                        'teaching_load_course_code' => $archive->teaching_load_course_code,
                        'teaching_load_subject' => $archive->teaching_load_subject,
                        'teaching_load_class_section' => $archive->teaching_load_class_section,
                        'teaching_load_day_of_week' => $archive->teaching_load_day_of_week,
                        'teaching_load_time_in' => $archive->teaching_load_time_in,
                        'teaching_load_time_out' => $archive->teaching_load_time_out,
                        'room_no' => $archive->room_no,
                        'school_year' => $archive->school_year,
                        'semester' => $archive->semester,
                        'archived_at' => $archive->archived_at,
                        'archived_by' => $archive->archived_by,
                        'archive_notes' => $archive->archive_notes,
                    ]);
                    
                    if ($response['success']) {
                        $synced[] = $archive->archive_id;
                        Log::info("Synced teaching load archive {$archive->archive_id} to cloud");
                    }
                }
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
            // Sync all archived records (they're historical data)
            $localArchives = AttendanceRecordArchive::all();
            $cloudArchives = $this->getCloudData('attendance-record-archives');
            $cloudArchiveIds = collect($cloudArchives)->pluck('archive_id')->toArray();
            
            foreach ($localArchives as $archive) {
                if (!in_array($archive->archive_id, $cloudArchiveIds)) {
                    $response = $this->pushToCloud('attendance-record-archives', [
                        'archive_id' => $archive->archive_id,
                        'original_record_id' => $archive->original_record_id,
                        'faculty_id' => $archive->faculty_id,
                        'teaching_load_id' => $archive->teaching_load_id,
                        'camera_id' => $archive->camera_id,
                        'record_date' => $archive->record_date,
                        'record_time_in' => $archive->record_time_in,
                        'record_time_out' => $archive->record_time_out,
                        'time_duration_seconds' => $archive->time_duration_seconds,
                        'record_status' => $archive->record_status,
                        'record_remarks' => $archive->record_remarks,
                        'school_year' => $archive->school_year,
                        'semester' => $archive->semester,
                        'archived_at' => $archive->archived_at,
                        'archived_by' => $archive->archived_by,
                        'archive_notes' => $archive->archive_notes,
                        'created_at' => $archive->created_at,
                        'updated_at' => $archive->updated_at,
                    ]);
                    
                    if ($response['success']) {
                        $synced[] = $archive->archive_id;
                        Log::info("Synced attendance record archive {$archive->archive_id} to cloud");
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error("Error syncing attendance record archives: " . $e->getMessage());
        }
        
        return $synced;
    }
    
    /**
     * Upload file to cloud storage (AWS S3, Google Cloud Storage, etc.)
     */
    protected function uploadFileToCloud($filePath, $directory)
    {
        try {
            $filename = basename($filePath);
            
            // Using multipart file upload
            $response = Http::timeout(120)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->cloudApiKey,
                ])
                ->attach('file', file_get_contents($filePath), $filename)
                ->post("{$this->cloudApiUrl}/upload/{$directory}");
            
            if ($response->successful()) {
                $result = $response->json();
                return $result['url'] ?? null;
            }
            
            return null;
            
        } catch (\Exception $e) {
            Log::error("Error uploading file to cloud: " . $e->getMessage());
            return null;
        }
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
                ->get("{$this->cloudApiUrl}/sync-status");
            
            if ($response->successful()) {
                return $response->json();
            }
            
            return ['status' => 'error', 'message' => 'Cannot connect to cloud'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
}

