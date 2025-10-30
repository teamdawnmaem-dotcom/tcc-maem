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

class CloudSyncService
{
    protected $cloudApiUrl;
    protected $cloudApiKey;
    
    public function __construct()
    {
        $this->cloudApiUrl = env('CLOUD_API_URL', 'https://your-cloud-server.com/api');
        $this->cloudApiKey = env('CLOUD_API_KEY', '');
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
            $results['synced']['rooms'] = $this->syncRooms();
            $results['synced']['cameras'] = $this->syncCameras();
            $results['synced']['faculties'] = $this->syncFaculties();
            $results['synced']['teaching_loads'] = $this->syncTeachingLoads();
            $results['synced']['attendance_records'] = $this->syncAttendanceRecords();
            $results['synced']['leaves'] = $this->syncLeaves();
            $results['synced']['passes'] = $this->syncPasses();
            $results['synced']['recognition_logs'] = $this->syncRecognitionLogs();
            $results['synced']['stream_recordings'] = $this->syncStreamRecordings();
            
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
            
            // Get cloud rooms to check what's already there
            $cloudRooms = $this->getCloudData('rooms');
            $cloudRoomIds = collect($cloudRooms)->pluck('room_id')->toArray();
            
            foreach ($localRooms as $room) {
                // Check if room exists in cloud
                if (!in_array($room->room_id, $cloudRoomIds)) {
                    // Push to cloud
                    $response = $this->pushToCloud('rooms', [
                        'room_id' => $room->room_id,
                        'room_no' => $room->room_no,
                        'room_name' => $room->room_name,
                        'room_building_no' => $room->room_building_no,
                        'room_floor_no' => $room->room_floor_no,
                    ]);
                    
                    if ($response['success']) {
                        $synced[] = $room->room_id;
                        Log::info("Synced room {$room->room_id} to cloud");
                    }
                }
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
            $cloudCameras = $this->getCloudData('cameras');
            $cloudCameraIds = collect($cloudCameras)->pluck('camera_id')->toArray();
            
            foreach ($localCameras as $camera) {
                if (!in_array($camera->camera_id, $cloudCameraIds)) {
                    $response = $this->pushToCloud('cameras', [
                        'camera_id' => $camera->camera_id,
                        'room_no' => $camera->room_no,
                        'room_name' => $camera->room_name,
                        'room_building_no' => $camera->room_building_no,
                        'camera_name' => $camera->camera_name,
                        'camera_live_feed' => $camera->camera_live_feed,
                    ]);
                    
                    if ($response['success']) {
                        $synced[] = $camera->camera_id;
                        Log::info("Synced camera {$camera->camera_id} to cloud");
                    }
                }
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
            $cloudFaculties = $this->getCloudData('faculties');
            $cloudFacultyIds = collect($cloudFaculties)->pluck('faculty_id')->toArray();
            
            foreach ($localFaculties as $faculty) {
                if (!in_array($faculty->faculty_id, $cloudFacultyIds)) {
                    $data = [
                        'faculty_id' => $faculty->faculty_id,
                        'faculty_fname' => $faculty->faculty_fname,
                        'faculty_lname' => $faculty->faculty_lname,
                        'faculty_mname' => $faculty->faculty_mname,
                        'faculty_department' => $faculty->faculty_department,
                        'faculty_face_embedding' => $faculty->faculty_face_embedding,
                        'faculty_images' => $faculty->faculty_images,
                    ];
                    
                    // Upload faculty images to cloud storage
                    if ($faculty->faculty_images) {
                        $imagesJson = (string) $faculty->faculty_images;
                        $images = json_decode($imagesJson, true);
                        
                        if (is_array($images)) {
                            $uploadedImages = [];
                            
                            foreach ($images as $imagePath) {
                                $fullPath = storage_path('app/public/' . $imagePath);
                                if (file_exists($fullPath)) {
                                    $cloudUrl = $this->uploadFileToCloud($fullPath, 'faculty_images');
                                    if ($cloudUrl) {
                                        $uploadedImages[] = $cloudUrl;
                                    }
                                }
                            }
                            
                            $data['cloud_image_urls'] = json_encode($uploadedImages);
                        }
                    }
                    
                    $response = $this->pushToCloud('faculties', $data);
                    
                    if ($response['success']) {
                        $synced[] = $faculty->faculty_id;
                        Log::info("Synced faculty {$faculty->faculty_id} to cloud");
                    }
                }
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
            $cloudLoads = $this->getCloudData('teaching-loads');
            $cloudLoadIds = collect($cloudLoads)->pluck('teaching_load_id')->toArray();
            
            foreach ($localLoads as $load) {
                if (!in_array($load->teaching_load_id, $cloudLoadIds)) {
                    $response = $this->pushToCloud('teaching-loads', [
                        'teaching_load_id' => $load->teaching_load_id,
                        'faculty_id' => $load->faculty_id,
                        'room_no' => $load->room_no,
                        'teaching_load_course_code' => $load->teaching_load_course_code,
                        'teaching_load_subject' => $load->teaching_load_subject,
                        'teaching_load_class_section' => $load->teaching_load_class_section,
                        'teaching_load_day_of_week' => $load->teaching_load_day_of_week,
                        'teaching_load_time_in' => $load->teaching_load_time_in,
                        'teaching_load_time_out' => $load->teaching_load_time_out,
                        'teaching_load_semester' => $load->teaching_load_semester,
                        'teaching_load_school_year' => $load->teaching_load_school_year,
                    ]);
                    
                    if ($response['success']) {
                        $synced[] = $load->teaching_load_id;
                        Log::info("Synced teaching load {$load->teaching_load_id} to cloud");
                    }
                }
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
            // Only sync recent records (last 30 days)
            $localRecords = AttendanceRecord::where('created_at', '>=', now()->subDays(30))->get();
            $cloudRecords = $this->getCloudData('attendance-records', ['days' => 30]);
            $cloudRecordIds = collect($cloudRecords)->pluck('record_id')->toArray();
            
            foreach ($localRecords as $record) {
                if (!in_array($record->record_id, $cloudRecordIds)) {
                    $response = $this->pushToCloud('attendance-records', [
                        'record_id' => $record->record_id,
                        'faculty_id' => $record->faculty_id,
                        'teaching_load_id' => $record->teaching_load_id,
                        'camera_id' => $record->camera_id,
                        'record_date' => $record->record_date,
                        'record_time_in' => $record->record_time_in,
                        'record_time_out' => $record->record_time_out,
                        'record_status' => $record->record_status,
                        'record_remarks' => $record->record_remarks,
                        'time_duration_seconds' => $record->time_duration_seconds,
                        'created_at' => $record->created_at,
                        'updated_at' => $record->updated_at,
                    ]);
                    
                    if ($response['success']) {
                        $synced[] = $record->record_id;
                    }
                }
            }
            
            Log::info("Synced " . count($synced) . " attendance records to cloud");
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
            $localLeaves = Leave::where('created_at', '>=', now()->subDays(90))->get();
            $cloudLeaves = $this->getCloudData('leaves', ['days' => 90]);
            $cloudLeaveIds = collect($cloudLeaves)->pluck('lp_id')->toArray();
            
            foreach ($localLeaves as $leave) {
                if (!in_array($leave->lp_id, $cloudLeaveIds)) {
                    $data = [
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
                    
                    // Upload leave slip if exists
                    if ($leave->lp_image) {
                        $fullPath = storage_path('app/public/' . $leave->lp_image);
                        if (file_exists($fullPath)) {
                            $cloudUrl = $this->uploadFileToCloud($fullPath, 'leave_slips');
                            $data['lp_image_cloud_url'] = $cloudUrl;
                        }
                    }
                    
                    $response = $this->pushToCloud('leaves', $data);
                    
                    if ($response['success']) {
                        $synced[] = $leave->lp_id;
                    }
                }
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
            $localPasses = Pass::where('created_at', '>=', now()->subDays(90))->get();
            $cloudPasses = $this->getCloudData('passes', ['days' => 90]);
            $cloudPassIds = collect($cloudPasses)->pluck('lp_id')->toArray();
            
            foreach ($localPasses as $pass) {
                if (!in_array($pass->lp_id, $cloudPassIds)) {
                    $data = [
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
                    
                    // Upload pass slip if exists
                    if ($pass->lp_image) {
                        $fullPath = storage_path('app/public/' . $pass->lp_image);
                        if (file_exists($fullPath)) {
                            $cloudUrl = $this->uploadFileToCloud($fullPath, 'passes');
                            $data['lp_image_cloud_url'] = $cloudUrl;
                        }
                    }
                    
                    $response = $this->pushToCloud('passes', $data);
                    
                    if ($response['success']) {
                        $synced[] = $pass->lp_id;
                    }
                }
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
            // Only sync recent logs (last 7 days)
            $localLogs = RecognitionLog::where('created_at', '>=', now()->subDays(7))->get();
            $cloudLogs = $this->getCloudData('recognition-logs', ['days' => 7]);
            $cloudLogIds = collect($cloudLogs)->pluck('log_id')->toArray();
            
            foreach ($localLogs as $log) {
                if (!in_array($log->log_id, $cloudLogIds)) {
                    $response = $this->pushToCloud('recognition-logs', [
                        'log_id' => $log->log_id,
                        'camera_id' => $log->camera_id,
                        'faculty_id' => $log->faculty_id,
                        'recognition_time' => $log->recognition_time,
                        'status' => $log->status,
                        'distance' => $log->distance,
                        'created_at' => $log->created_at,
                    ]);
                    
                    if ($response['success']) {
                        $synced[] = $log->log_id;
                    }
                }
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
            // Only sync recent recordings (last 7 days)
            $localRecordings = StreamRecording::where('created_at', '>=', now()->subDays(7))->get();
            $cloudRecordings = $this->getCloudData('stream-recordings', ['days' => 7]);
            $cloudRecordingIds = collect($cloudRecordings)->pluck('recording_id')->toArray();
            
            foreach ($localRecordings as $recording) {
                if (!in_array($recording->recording_id, $cloudRecordingIds)) {
                    $data = [
                        'recording_id' => $recording->recording_id,
                        'camera_id' => $recording->camera_id,
                        'filename' => $recording->filename,
                        'start_time' => $recording->start_time,
                        'duration' => $recording->duration,
                        'frames' => $recording->frames,
                        'file_size' => $recording->file_size,
                        'created_at' => $recording->created_at,
                    ];
                    
                    // Optionally upload video file to cloud storage
                    // This can be expensive, so you might want to do this selectively
                    $fullPath = storage_path('app/public/' . $recording->filepath);
                    if (file_exists($fullPath)) {
                        $cloudUrl = $this->uploadFileToCloud($fullPath, 'stream_recordings');
                        $data['video_cloud_url'] = $cloudUrl;
                    }
                    
                    $response = $this->pushToCloud('stream-recordings', $data);
                    
                    if ($response['success']) {
                        $synced[] = $recording->recording_id;
                    }
                }
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

