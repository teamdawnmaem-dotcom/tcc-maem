<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use GuzzleHttp\Client;
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
use App\Models\OfficialMatter;
use App\Services\AttendanceRemarksService;

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
     * NEW APPROACH: Process deletions per table before syncing each table's data
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
            Log::info('Starting cloud sync (with per-table deletion processing)...');
            
            // Sync data in order of dependencies - process deletions before each table
            // Each sync method now handles its own deletions before syncing data
            $results['synced']['users'] = $this->syncUsers();
            $results['synced']['subjects'] = $this->syncSubjects();
            $results['synced']['rooms'] = $this->syncRooms();
            $results['synced']['cameras'] = $this->syncCameras();
            $results['synced']['faculties'] = $this->syncFaculties();
            $results['synced']['teaching_loads'] = $this->syncTeachingLoads();
            $results['synced']['leaves'] = $this->syncLeaves();
            $results['synced']['passes'] = $this->syncPasses();
            $results['synced']['official_matters'] = $this->syncOfficialMatters();
            $results['synced']['attendance_records'] = $this->syncAttendanceRecords();
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
     * Sync deletions for a specific table to cloud
     * @param string $tableName Table name (e.g., 'tbl_user')
     * @param string $endpoint API endpoint (e.g., 'users')
     */
    protected function syncTableDeletionsToCloud(string $tableName, string $endpoint)
    {
        try {
            $deletedIds = $this->getDeletedIds($tableName);
            if (!empty($deletedIds)) {
                Log::info("Processing " . count($deletedIds) . " deletions for {$tableName} before syncing to cloud endpoint {$endpoint}");
                $this->syncDeletionsToCloud($endpoint, $deletedIds);
            }
        } catch (\Exception $e) {
            Log::error("Error syncing deletions for {$tableName} to cloud: " . $e->getMessage());
        }
    }
    
    /**
     * Process deletions from cloud for a specific table
     * @param string $endpoint API endpoint (e.g., 'users')
     * @param string $tableName Table name (e.g., 'tbl_user')
     * @param string $idKey Primary key field name (e.g., 'user_id')
     */
    protected function processTableDeletionsFromCloud(string $endpoint, string $tableName, string $idKey)
    {
        try {
            Log::info("Processing deletions from cloud for {$tableName} before syncing data");
            $this->processDeletionsFromCloud($endpoint, $tableName, $idKey);
        } catch (\Exception $e) {
            Log::error("Error processing deletions from cloud for {$tableName}: " . $e->getMessage());
        }
    }
    
    /**
     * Sync rooms
     */
    protected function syncRooms()
    {
        $synced = [];
        
        try {
            // STEP 1: Process deletions for this table before syncing data
            $this->syncTableDeletionsToCloud('tbl_room', 'rooms');
            
            // Get deleted IDs from cloud (to prevent syncing records that were deleted in cloud)
            $cloudDeletedIds = $this->getDeletedIdsFromCloud('rooms');
            
            // Get existing cloud records with their data for comparison
            $existingCloudRecords = $this->getExistingCloudRecords('rooms', 'room_no');
            
            // Get all local rooms
            $localRooms = Room::all();
            
            if ($localRooms->isEmpty()) {
                Log::info('No rooms to sync to cloud');
                return $synced;
            }
            
            // Filter: only sync new records or records where local has newer/latest updated_at
            $roomsToSync = $localRooms->filter(function ($room) use ($existingCloudRecords, $cloudDeletedIds) {
                $roomNo = $room->room_no;
                
                // Skip if this record was deleted in cloud (prevent restoring deleted records)
                if (in_array($roomNo, $cloudDeletedIds)) {
                    Log::debug("Skipping room {$roomNo} - was deleted in cloud");
                    return false;
                }
                
                // If not in cloud, needs to be synced (new record)
                if (!isset($existingCloudRecords[$roomNo])) {
                    return true;
                }
                
                // STRICT RULE: Compare updated_at timestamps - only sync if local is newer or equal
                $localUpdatedAt = $room->updated_at ? $this->formatDateTime($room->updated_at) : null;
                $cloudUpdatedAt = $existingCloudRecords[$roomNo]['updated_at'] ?? null;
                
                $comparison = $this->compareRecordTimestamps($localUpdatedAt, $cloudUpdatedAt);
                
                if ($comparison === 1) {
                    // Cloud is newer, skip syncing local to cloud
                    Log::debug("Skipping room {$roomNo} - cloud has newer updated_at (cloud: {$cloudUpdatedAt}, local: {$localUpdatedAt})");
                    return false;
                }
                
                // Local is newer or equal - sync it
                if ($comparison === -1) {
                    Log::debug("Syncing room {$roomNo} - local has newer updated_at (local: {$localUpdatedAt}, cloud: {$cloudUpdatedAt})");
                    return true;
                }
                
                // Timestamps are equal - check if data is different
                if ($comparison === 0) {
                    $localData = [
                        'room_no' => $room->room_no,
                        'room_name' => $room->room_name,
                        'room_building_no' => $room->room_building_no,
                    ];
                    return $this->recordsAreDifferent($localData, $existingCloudRecords[$roomNo]);
                }
                
                return false;
            });
            
            if ($roomsToSync->isEmpty()) {
                Log::info('No new or changed rooms to sync to cloud');
                return $synced;
            }
            
            // Bulk upsert only changed/new rooms
            $payload = $roomsToSync->map(function ($room) {
                return [
                    'room_no' => $room->room_no,
                    'room_name' => $room->room_name,
                    'room_building_no' => $room->room_building_no,
                    'created_at' => $this->formatDateTime($room->created_at),
                    'updated_at' => $this->formatDateTime($room->updated_at),
                ];
            })->values()->all();
            $resp = $this->pushBulkToCloud('rooms', $payload);
            if ($resp['success']) {
                $synced = $roomsToSync->pluck('room_no')->all();
                Log::info('Synced ' . count($synced) . ' rooms to cloud (' . count($roomsToSync->filter(function($r) use ($existingCloudRecords) { return !isset($existingCloudRecords[$r->room_no]); })) . ' new, ' . count($roomsToSync->filter(function($r) use ($existingCloudRecords) { return isset($existingCloudRecords[$r->room_no]); })) . ' updated)');
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
            // STEP 1: Process deletions for this table before syncing data
            $this->syncTableDeletionsToCloud('tbl_camera', 'cameras');
            
            // Get deleted IDs from cloud (to prevent syncing records that were deleted in cloud)
            $cloudDeletedIds = $this->getDeletedIdsFromCloud('cameras');
            
            // Get existing cloud records with their data for comparison
            $existingCloudRecords = $this->getExistingCloudRecords('cameras', 'camera_id');
            
            // Get all local cameras
            $localCameras = Camera::all();
            
            if ($localCameras->isEmpty()) {
                Log::info('No cameras to sync to cloud');
                return $synced;
            }
            
            // Filter: only sync new records or records where local has newer/latest updated_at
            $camerasToSync = $localCameras->filter(function ($camera) use ($existingCloudRecords, $cloudDeletedIds) {
                $cameraId = $camera->camera_id;
                
                // Skip if this record was deleted in cloud (prevent restoring deleted records)
                if (in_array($cameraId, $cloudDeletedIds)) {
                    Log::debug("Skipping camera {$cameraId} - was deleted in cloud");
                    return false;
                }
                
                // If not in cloud, needs to be synced (new record)
                if (!isset($existingCloudRecords[$cameraId])) {
                    return true;
                }
                
                // STRICT RULE: Compare updated_at timestamps - only sync if local is newer or equal
                $localUpdatedAt = $camera->updated_at ? $this->formatDateTime($camera->updated_at) : null;
                $cloudUpdatedAt = $existingCloudRecords[$cameraId]['updated_at'] ?? null;
                
                $comparison = $this->compareRecordTimestamps($localUpdatedAt, $cloudUpdatedAt);
                
                if ($comparison === 1) {
                    // Cloud is newer, skip syncing local to cloud
                    Log::debug("Skipping camera {$cameraId} - cloud has newer updated_at (cloud: {$cloudUpdatedAt}, local: {$localUpdatedAt})");
                    return false;
                }
                
                // Local is newer or equal - sync it
                if ($comparison === -1) {
                    Log::debug("Syncing camera {$cameraId} - local has newer updated_at (local: {$localUpdatedAt}, cloud: {$cloudUpdatedAt})");
                    return true;
                }
                
                // Timestamps are equal - check if data is different
                if ($comparison === 0) {
                    $localData = [
                        'camera_id' => $camera->camera_id,
                        'camera_name' => $camera->camera_name,
                        'camera_ip_address' => $camera->camera_ip_address,
                        'camera_username' => $camera->camera_username,
                        'camera_password' => $camera->camera_password,
                        'camera_live_feed' => $camera->camera_live_feed,
                        'room_no' => $camera->room_no,
                    ];
                    return $this->recordsAreDifferent($localData, $existingCloudRecords[$cameraId]);
                }
                
                return false;
            });
            
            if ($camerasToSync->isEmpty()) {
                Log::info('No new or changed cameras to sync to cloud');
                return $synced;
            }
            
            $payload = $camerasToSync->map(function ($camera) {
                return [
                    'camera_id' => $camera->camera_id,
                    'camera_name' => $camera->camera_name,
                    'camera_ip_address' => $camera->camera_ip_address,
                    'camera_username' => $camera->camera_username,
                    'camera_password' => $camera->camera_password,
                    'camera_live_feed' => $camera->camera_live_feed,
                    'room_no' => $camera->room_no,
                    'created_at' => $this->formatDateTime($camera->created_at),
                    'updated_at' => $this->formatDateTime($camera->updated_at),
                ];
            })->values()->all();
            $resp = $this->pushBulkToCloud('cameras', $payload);
            if ($resp['success']) {
                $synced = $camerasToSync->pluck('camera_id')->all();
                $newCount = count($camerasToSync->filter(function($c) use ($existingCloudRecords) { return !isset($existingCloudRecords[$c->camera_id]); }));
                $updatedCount = count($camerasToSync) - $newCount;
                Log::info('Synced ' . count($synced) . ' cameras to cloud (' . $newCount . ' new, ' . $updatedCount . ' updated)');
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
            // STEP 1: Process deletions for this table before syncing data
            $this->syncTableDeletionsToCloud('tbl_faculty', 'faculties');
            
            // Get deleted IDs from cloud (to prevent syncing records that were deleted in cloud)
            $cloudDeletedIds = $this->getDeletedIdsFromCloud('faculties');
            
            // Get existing cloud records with their data for comparison
            $existingCloudRecords = $this->getExistingCloudRecords('faculties', 'faculty_id');
            
            // Get all local faculties
            $localFaculties = Faculty::all();
            
            if ($localFaculties->isEmpty()) {
                Log::info('No faculties to sync to cloud');
                return $synced;
            }
            
            // Filter: only sync new records or records where local has newer/latest updated_at
            $facultiesToSync = $localFaculties->filter(function ($faculty) use ($existingCloudRecords, $cloudDeletedIds) {
                $facultyId = $faculty->faculty_id;
                
                // Skip if this record was deleted in cloud (prevent restoring deleted records)
                if (in_array($facultyId, $cloudDeletedIds)) {
                    Log::debug("Skipping faculty {$facultyId} - was deleted in cloud");
                    return false;
                }
                
                // If not in cloud, needs to be synced (new record)
                if (!isset($existingCloudRecords[$facultyId])) {
                    return true;
                }
                
                // STRICT RULE: Compare updated_at timestamps - only sync if local is newer or equal
                $localUpdatedAt = $faculty->updated_at ? $this->formatDateTime($faculty->updated_at) : null;
                $cloudUpdatedAt = $existingCloudRecords[$facultyId]['updated_at'] ?? null;
                
                $comparison = $this->compareRecordTimestamps($localUpdatedAt, $cloudUpdatedAt);
                
                if ($comparison === 1) {
                    // Cloud is newer, skip syncing local to cloud
                    Log::debug("Skipping faculty {$facultyId} - cloud has newer updated_at (cloud: {$cloudUpdatedAt}, local: {$localUpdatedAt})");
                    return false;
                }
                
                // Local is newer or equal - sync it
                if ($comparison === -1) {
                    Log::debug("Syncing faculty {$facultyId} - local has newer updated_at (local: {$localUpdatedAt}, cloud: {$cloudUpdatedAt})");
                    return true;
                }
                
                // Timestamps are equal - check if data is different
                if ($comparison === 0) {
                    $localData = [
                        'faculty_id' => $faculty->faculty_id,
                        'faculty_fname' => $faculty->faculty_fname,
                        'faculty_lname' => $faculty->faculty_lname,
                        'faculty_department' => $faculty->faculty_department,
                        'faculty_face_embedding' => $faculty->faculty_face_embedding,
                    ];
                    return $this->recordsAreDifferent($localData, $existingCloudRecords[$facultyId]);
                }
                
                return false;
            });
            
            if ($facultiesToSync->isEmpty()) {
                Log::info('No new or changed faculties to sync to cloud');
                return $synced;
            }
            
            $payload = $facultiesToSync->map(function ($faculty) {
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
                $synced = $facultiesToSync->pluck('faculty_id')->all();
                $newCount = count($facultiesToSync->filter(function($f) use ($existingCloudRecords) { return !isset($existingCloudRecords[$f->faculty_id]); }));
                $updatedCount = count($facultiesToSync) - $newCount;
                Log::info('Synced ' . count($synced) . ' faculties to cloud (' . $newCount . ' new, ' . $updatedCount . ' updated)');
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
            // STEP 1: Process deletions for this table before syncing data
            $this->syncTableDeletionsToCloud('tbl_teaching_load', 'teaching-loads');
            
            // Get deleted IDs from cloud (to prevent syncing records that were deleted in cloud)
            $cloudDeletedIds = $this->getDeletedIdsFromCloud('teaching-loads');
            
            // Get existing cloud records with their data for comparison
            $existingCloudRecords = $this->getExistingCloudRecords('teaching-loads', 'teaching_load_id');
            
            // Get all local teaching loads
            $localLoads = TeachingLoad::all();
            
            if ($localLoads->isEmpty()) {
                Log::info('No teaching loads to sync to cloud');
                return $synced;
            }
            
            // Filter: STRICT RULE - only sync if local has newer/latest updated_at
            $loadsToSync = $localLoads->filter(function ($load) use ($existingCloudRecords, $cloudDeletedIds) {
                $loadId = $load->teaching_load_id;
                
                // Skip if this record was deleted in cloud (prevent restoring deleted records)
                if (in_array($loadId, $cloudDeletedIds)) {
                    Log::debug("Skipping teaching load {$loadId} - was deleted in cloud");
                    return false;
                }
                
                // If not in cloud, needs to be synced (new record)
                if (!isset($existingCloudRecords[$loadId])) {
                    return true;
                }
                
                // STRICT RULE: Compare updated_at timestamps - only sync if local is newer or equal
                if (!$this->shouldSyncLocalToCloud($load, $existingCloudRecords[$loadId], 'teaching_load', $loadId)) {
                    return false;
                }
                
                // If timestamps are equal, check if data is different
                $localData = [
                    'teaching_load_id' => $load->teaching_load_id,
                    'faculty_id' => $load->faculty_id,
                    'teaching_load_course_code' => $load->teaching_load_course_code,
                    'teaching_load_subject' => $load->teaching_load_subject,
                    'teaching_load_day_of_week' => $load->teaching_load_day_of_week,
                    'teaching_load_class_section' => $load->teaching_load_class_section,
                    'teaching_load_time_in' => $load->teaching_load_time_in,
                    'teaching_load_time_out' => $load->teaching_load_time_out,
                    'room_no' => $load->room_no,
                ];
                
                return $this->recordsAreDifferent($localData, $existingCloudRecords[$loadId]);
            });
            
            if ($loadsToSync->isEmpty()) {
                Log::info('No new or changed teaching loads to sync to cloud');
                return $synced;
            }
            
            $payload = $loadsToSync->map(function ($load) {
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
            Log::info('Bulk teaching-loads result', ['upserted' => $upserted, 'success' => $resp['success'] ?? null, 'local_count' => count($loadsToSync)]);
            
            if ($resp['success'] && $upserted > 0) {
                $synced = $loadsToSync->pluck('teaching_load_id')->all();
                $newCount = count($loadsToSync->filter(function($l) use ($existingCloudRecords) { return !isset($existingCloudRecords[$l->teaching_load_id]); }));
                $updatedCount = count($loadsToSync) - $newCount;
                Log::info('Synced ' . count($synced) . ' teaching loads to cloud (' . $newCount . ' new, ' . $updatedCount . ' updated)');
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
            // STEP 1: Process deletions for this table before syncing data
            $this->syncTableDeletionsToCloud('tbl_attendance_record', 'attendance-records');
            
            // Get deleted IDs from cloud (to prevent syncing records that were deleted in cloud)
            $cloudDeletedIds = $this->getDeletedIdsFromCloud('attendance-records');
            
            // Get existing cloud records with their data for comparison (last 30 days for performance)
            $existingCloudRecords = $this->getExistingCloudRecords('attendance-records', 'record_id', ['days' => 30]);
            
            // Get all local attendance records
            $localRecords = AttendanceRecord::all();
            
            if ($localRecords->isEmpty()) {
                Log::info('No attendance records to sync to cloud');
                return $synced;
            }
            
            // Filter: STRICT RULE - only sync if local has newer/latest updated_at
            $recordsToSync = $localRecords->filter(function ($record) use ($existingCloudRecords, $cloudDeletedIds) {
                $recordId = $record->record_id;
                
                // Skip if this record was deleted in cloud (prevent restoring deleted records)
                if (in_array($recordId, $cloudDeletedIds)) {
                    Log::debug("Skipping attendance record {$recordId} - was deleted in cloud");
                    return false;
                }
                
                // If not in cloud, needs to be synced (new record)
                if (!isset($existingCloudRecords[$recordId])) {
                    return true;
                }
                
                // STRICT RULE: Compare updated_at timestamps - only sync if local is newer or equal
                if (!$this->shouldSyncLocalToCloud($record, $existingCloudRecords[$recordId], 'attendance_record', $recordId)) {
                    return false;
                }
                
                // If timestamps are equal, check if data is different
                $localData = [
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
                
                return $this->recordsAreDifferent($localData, $existingCloudRecords[$recordId]);
            });
            
            if ($recordsToSync->isEmpty()) {
                Log::info('No new or changed attendance records to sync to cloud');
                return $synced;
            }
            
            $payload = $recordsToSync->map(function ($record) {
                // Sync snapshot images to cloud and update paths
                $cloudTimeInSnapshot = $this->syncAttendanceSnapshot($record->time_in_snapshot);
                $cloudTimeOutSnapshot = $this->syncAttendanceSnapshot($record->time_out_snapshot);
                
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
                    'time_in_snapshot' => $cloudTimeInSnapshot,
                    'time_out_snapshot' => $cloudTimeOutSnapshot,
                    'created_at' => $this->formatDateTime($record->created_at),
                    'updated_at' => $this->formatDateTime($record->updated_at),
                ];
            })->values()->all();
            $resp = $this->pushBulkToCloud('attendance-records', $payload);
            if ($resp['success']) {
                $synced = $recordsToSync->pluck('record_id')->all();
                $newCount = count($recordsToSync->filter(function($r) use ($existingCloudRecords) { return !isset($existingCloudRecords[$r->record_id]); }));
                $updatedCount = count($recordsToSync) - $newCount;
                Log::info('Synced ' . count($synced) . ' attendance records to cloud (' . $newCount . ' new, ' . $updatedCount . ' updated)');
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
            // STEP 1: Process deletions for leaves before syncing data
            $deletedIds = $this->getDeletedIds('tbl_leave_pass');
            // Filter for leaves only (lp_type = 'Leave') using metadata stored in cache
            $leaveDeletedIds = [];
            foreach ($deletedIds as $id) {
                $cacheKey = "sync_deletion:tbl_leave_pass:{$id}";
                $deletionData = Cache::get($cacheKey);
                $lpType = $deletionData['metadata']['lp_type'] ?? null;
                if ($lpType === 'Leave') {
                    $leaveDeletedIds[] = $id;
                }
            }
            if (!empty($leaveDeletedIds)) {
                Log::info("Processing " . count($leaveDeletedIds) . " leave deletions before syncing to cloud");
                $this->syncDeletionsToCloud('leaves', $leaveDeletedIds);
            }
            
            // Get deleted IDs from cloud (to prevent syncing records that were deleted in cloud)
            $cloudDeletedIds = $this->getDeletedIdsFromCloud('leaves');
            
            // Get existing cloud records with their data for comparison (last 90 days for performance)
            $existingCloudRecords = $this->getExistingCloudRecords('leaves', 'lp_id', ['days' => 90]);
            
            // Get all local leaves
            $localLeaves = Leave::all();
            
            if ($localLeaves->isEmpty()) {
                Log::info('No leaves to sync to cloud');
                return $synced;
            }
            
            // Filter: only sync new records or records that have changed, and skip records deleted in cloud
            $leavesToSync = $localLeaves->filter(function ($leave) use ($existingCloudRecords, $cloudDeletedIds) {
                $lpId = $leave->lp_id;
                
                // Skip if this record was deleted in cloud (prevent restoring deleted records)
                if (in_array($lpId, $cloudDeletedIds)) {
                    Log::debug("Skipping leave {$lpId} - was deleted in cloud");
                    return false;
                }
                
                // If not in cloud, needs to be synced (new record)
                if (!isset($existingCloudRecords[$lpId])) {
                    return true;
                }
                
                // STRICT RULE: Compare updated_at timestamps - only sync if local is newer or equal
                if (!$this->shouldSyncLocalToCloud($leave, $existingCloudRecords[$lpId], 'leave', $lpId)) {
                    return false;
                }
                
                // If in cloud, compare data to see if it changed (ignore image path differences)
                $localData = [
                    'lp_id' => $leave->lp_id,
                    'faculty_id' => $leave->faculty_id,
                    'lp_type' => $leave->lp_type,
                    'lp_purpose' => $leave->lp_purpose,
                    'leave_start_date' => $leave->leave_start_date,
                    'leave_end_date' => $leave->leave_end_date,
                ];
                
                return $this->recordsAreDifferent($localData, $existingCloudRecords[$lpId]);
            });
            
            if ($leavesToSync->isEmpty()) {
                Log::info('No new or changed leaves to sync to cloud');
                return $synced;
            }
            
            // Store old dates from cloud BEFORE syncing (for attendance reconciliation)
            $oldDatesMap = [];
            foreach ($leavesToSync as $leave) {
                $lpId = $leave->lp_id;
                if (isset($existingCloudRecords[$lpId])) {
                    // This is an update - store old dates from cloud
                    $oldDatesMap[$lpId] = [
                        'old_start_date' => $existingCloudRecords[$lpId]['leave_start_date'] ?? null,
                        'old_end_date' => $existingCloudRecords[$lpId]['leave_end_date'] ?? null,
                    ];
                }
            }
            
            $payload = $leavesToSync->map(function ($leave) {
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
                $synced = $leavesToSync->pluck('lp_id')->all();
                $newCount = count($leavesToSync->filter(function($l) use ($existingCloudRecords) { return !isset($existingCloudRecords[$l->lp_id]); }));
                $updatedCount = count($leavesToSync) - $newCount;
                Log::info('Synced ' . count($synced) . ' leaves to cloud (' . $newCount . ' new, ' . $updatedCount . ' updated)');
                
                // Trigger attendance record updates on cloud for synced leaves
                // Pass old dates for updates so cloud can properly delete attendance records
                if (!empty($synced)) {
                    $this->triggerCloudAttendanceUpdateForLeaves($synced, $oldDatesMap);
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
            // STEP 1: Process deletions for passes before syncing data
            $deletedIds = $this->getDeletedIds('tbl_leave_pass');
            // Filter for passes only (lp_type = 'Pass') using metadata stored in cache
            $passDeletedIds = [];
            foreach ($deletedIds as $id) {
                $cacheKey = "sync_deletion:tbl_leave_pass:{$id}";
                $deletionData = Cache::get($cacheKey);
                $lpType = $deletionData['metadata']['lp_type'] ?? null;
                if ($lpType === 'Pass') {
                    $passDeletedIds[] = $id;
                }
            }
            if (!empty($passDeletedIds)) {
                Log::info("Processing " . count($passDeletedIds) . " pass deletions before syncing to cloud");
                $this->syncDeletionsToCloud('passes', $passDeletedIds);
            }
            
            // Get deleted IDs from cloud (to prevent syncing records that were deleted in cloud)
            $cloudDeletedIds = $this->getDeletedIdsFromCloud('passes');
            
            // Get existing cloud records with their data for comparison (last 90 days for performance)
            $existingCloudRecords = $this->getExistingCloudRecords('passes', 'lp_id', ['days' => 90]);
            
            // Get all local passes
            $localPasses = Pass::all();
            
            if ($localPasses->isEmpty()) {
                Log::info('No passes to sync to cloud');
                return $synced;
            }
            
            // Filter: only sync new records or records that have changed, and skip records deleted in cloud
            $passesToSync = $localPasses->filter(function ($pass) use ($existingCloudRecords, $cloudDeletedIds) {
                $lpId = $pass->lp_id;
                
                // Skip if this record was deleted in cloud (prevent restoring deleted records)
                if (in_array($lpId, $cloudDeletedIds)) {
                    Log::debug("Skipping pass {$lpId} - was deleted in cloud");
                    return false;
                }
                
                // If not in cloud, needs to be synced (new record)
                if (!isset($existingCloudRecords[$lpId])) {
                    return true;
                }
                
                // STRICT RULE: Compare updated_at timestamps - only sync if local is newer or equal
                if (!$this->shouldSyncLocalToCloud($pass, $existingCloudRecords[$lpId], 'pass', $lpId)) {
                    return false;
                }
                
                // If in cloud, compare data to see if it changed (ignore image path differences)
                $localData = [
                    'lp_id' => $pass->lp_id,
                    'faculty_id' => $pass->faculty_id,
                    'lp_type' => $pass->lp_type,
                    'lp_purpose' => $pass->lp_purpose,
                    'pass_slip_itinerary' => $pass->pass_slip_itinerary,
                    'pass_slip_date' => $pass->pass_slip_date,
                    'pass_slip_departure_time' => $pass->pass_slip_departure_time,
                    'pass_slip_arrival_time' => $pass->pass_slip_arrival_time,
                ];
                
                return $this->recordsAreDifferent($localData, $existingCloudRecords[$lpId]);
            });
            
            if ($passesToSync->isEmpty()) {
                Log::info('No new or changed passes to sync to cloud');
                return $synced;
            }
            
            // Store old dates from cloud BEFORE syncing (for attendance reconciliation)
            $oldDatesMap = [];
            foreach ($passesToSync as $pass) {
                $lpId = $pass->lp_id;
                if (isset($existingCloudRecords[$lpId])) {
                    // This is an update - store old date from cloud
                    $oldDatesMap[$lpId] = [
                        'old_date' => $existingCloudRecords[$lpId]['pass_slip_date'] ?? null,
                    ];
                }
            }
            
            $payload = $passesToSync->map(function ($pass) {
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
                $synced = $passesToSync->pluck('lp_id')->all();
                $newCount = count($passesToSync->filter(function($p) use ($existingCloudRecords) { return !isset($existingCloudRecords[$p->lp_id]); }));
                $updatedCount = count($passesToSync) - $newCount;
                Log::info('Synced ' . count($synced) . ' passes to cloud (' . $newCount . ' new, ' . $updatedCount . ' updated)');
                
                // Trigger attendance record updates on cloud for synced passes
                // Pass old dates for updates so cloud can properly delete attendance records
                if (!empty($synced)) {
                    $this->triggerCloudAttendanceUpdateForPasses($synced, $oldDatesMap);
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
            // STEP 1: Process deletions for this table before syncing data
            $this->syncTableDeletionsToCloud('tbl_recognition_logs', 'recognition-logs');
            
            // Get deleted IDs from cloud (to prevent syncing records that were deleted in cloud)
            $cloudDeletedIds = $this->getDeletedIdsFromCloud('recognition-logs');
            
            // Get existing recognition log IDs from cloud (limit by recent days to reduce payload)
            $existingCloudIds = $this->getExistingCloudIds('recognition-logs', 'log_id', ['days' => 7]);
            
            // Get local logs and only include those NOT present on cloud (append-only, no updates needed)
            // Also skip records that were deleted in cloud
            $localLogs = RecognitionLog::all()->filter(function ($log) use ($existingCloudIds, $cloudDeletedIds) {
                $logId = $log->log_id;
                
                // Skip if this record was deleted in cloud (prevent restoring deleted records)
                if (in_array($logId, $cloudDeletedIds)) {
                    Log::debug("Skipping recognition log {$logId} - was deleted in cloud");
                    return false;
                }
                
                return !in_array($logId, $existingCloudIds);
            });
            
            if ($localLogs->isEmpty()) {
                Log::info('No new recognition logs to sync to cloud');
                return $synced;
            }
            
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
                    'created_at' => $this->formatDateTime($log->created_at),
                    'updated_at' => $this->formatDateTime($log->updated_at),
                ];
            })->values()->all();
            
            $resp = $this->pushBulkToCloud('recognition-logs', $payload);
            $upserted = $resp['data']['upserted'] ?? 0;
            Log::info('Selective recognition-logs sync result', ['upserted' => $upserted, 'to_send' => count($payload)]);
            
            if ($resp['success'] && $upserted > 0) {
                $synced = $localLogs->pluck('log_id')->all();
                Log::info('Synced ' . count($synced) . ' new recognition logs to cloud');
            } elseif ($resp['success'] && $upserted == 0) {
                Log::warning("Recognition logs sync returned success but 0 records were upserted (nothing new).");
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
            // STEP 1: Process deletions for this table before syncing data
            $this->syncTableDeletionsToCloud('tbl_stream_recordings', 'stream-recordings');
            
            // Get deleted IDs from cloud (to prevent syncing records that were deleted in cloud)
            $cloudDeletedIds = $this->getDeletedIdsFromCloud('stream-recordings');
            
            // Get existing stream recording IDs from cloud (last 7 days)
            $existingCloudIds = $this->getExistingCloudIds('stream-recordings', 'recording_id', ['days' => 7]);
            
            // Get all local stream recordings and filter out existing ones (append-only, no updates needed)
            // Also skip records that were deleted in cloud
            $localRecordings = StreamRecording::all()->filter(function ($recording) use ($existingCloudIds, $cloudDeletedIds) {
                $recordingId = $recording->recording_id;
                
                // Skip if this record was deleted in cloud (prevent restoring deleted records)
                if (in_array($recordingId, $cloudDeletedIds)) {
                    Log::debug("Skipping stream recording {$recordingId} - was deleted in cloud");
                    return false;
                }
                
                return !in_array($recordingId, $existingCloudIds);
            });
            
            if ($localRecordings->isEmpty()) {
                Log::info('No new stream recordings to sync to cloud');
                return $synced;
            }
            
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
                Log::info('Synced ' . count($synced) . ' new stream recordings to cloud');
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
     * Convert cloud timestamp from UTC to Asia/Manila timezone
     * Cloud database stores timestamps in UTC, but we need to compare with local (Asia/Manila)
     * @param string|null $cloudTimestamp Timestamp from cloud (assumed to be in UTC)
     * @return string|null Timestamp in Asia/Manila timezone (Y-m-d H:i:s format)
     */
    protected function convertCloudTimestampToLocalTimezone($cloudTimestamp)
    {
        if (empty($cloudTimestamp)) {
            return null;
        }
        
        try {
            // Parse the cloud timestamp as UTC
            $utcTime = \Carbon\Carbon::parse($cloudTimestamp)->setTimezone('UTC');
            // Convert to Asia/Manila timezone
            $localTime = $utcTime->setTimezone('Asia/Manila');
            return $localTime->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            Log::warning("Failed to convert cloud timestamp '{$cloudTimestamp}' to local timezone: " . $e->getMessage());
            // If conversion fails, return as-is (might already be in correct timezone)
            return $cloudTimestamp;
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
            // STEP 1: Process deletions for this table before syncing data
            $this->syncTableDeletionsToCloud('tbl_subject', 'subjects');
            
            // Get deleted IDs from cloud (to prevent syncing records that were deleted in cloud)
            $cloudDeletedIds = $this->getDeletedIdsFromCloud('subjects');
            
            // Get existing cloud records with their data for comparison
            $existingCloudRecords = $this->getExistingCloudRecords('subjects', 'subject_id');
            
            // Get all local subjects
            $localSubjects = Subject::all();
            
            if ($localSubjects->isEmpty()) {
                Log::info('No subjects to sync to cloud');
                return $synced;
            }
            
            // Filter: STRICT RULE - only sync if local has newer/latest updated_at
            $subjectsToSync = $localSubjects->filter(function ($subject) use ($existingCloudRecords, $cloudDeletedIds) {
                $subjectId = $subject->subject_id;
                
                // Skip if this record was deleted in cloud (prevent restoring deleted records)
                if (in_array($subjectId, $cloudDeletedIds)) {
                    Log::debug("Skipping subject {$subjectId} - was deleted in cloud");
                    return false;
                }
                
                // If not in cloud, needs to be synced (new record)
                if (!isset($existingCloudRecords[$subjectId])) {
                    return true;
                }
                
                // STRICT RULE: Compare updated_at timestamps - only sync if local is newer or equal
                if (!$this->shouldSyncLocalToCloud($subject, $existingCloudRecords[$subjectId], 'subject', $subjectId)) {
                    return false;
                }
                
                // If timestamps are equal, check if data is different
                $localData = [
                    'subject_id' => $subject->subject_id,
                    'subject_code' => $subject->subject_code,
                    'subject_description' => $subject->subject_description,
                    'department' => $subject->department,
                ];
                
                return $this->recordsAreDifferent($localData, $existingCloudRecords[$subjectId]);
            });
            
            if ($subjectsToSync->isEmpty()) {
                Log::info('No new or changed subjects to sync to cloud');
                return $synced;
            }
            
            $payload = $subjectsToSync->map(function ($s) {
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
            Log::info('Bulk subjects result', ['upserted' => $upserted, 'success' => $resp['success'] ?? null, 'local_count' => count($subjectsToSync)]);
            
            if ($resp['success'] && $upserted > 0) {
                $synced = $subjectsToSync->pluck('subject_id')->all();
                $newCount = count($subjectsToSync->filter(function($s) use ($existingCloudRecords) { return !isset($existingCloudRecords[$s->subject_id]); }));
                $updatedCount = count($subjectsToSync) - $newCount;
                Log::info('Synced ' . count($synced) . ' subjects to cloud (' . $newCount . ' new, ' . $updatedCount . ' updated)');
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
            // STEP 1: Process deletions for this table before syncing data
            $this->syncTableDeletionsToCloud('tbl_user', 'users');
            
            // Get deleted IDs from cloud (to prevent syncing records that were deleted in cloud)
            $cloudDeletedIds = $this->getDeletedIdsFromCloud('users');
            
            // Get existing cloud records with their data for comparison
            $existingCloudRecords = $this->getExistingCloudRecords('users', 'user_id');
            
            // Get all local users
            $localUsers = User::all();
            
            if ($localUsers->isEmpty()) {
                Log::info('No users to sync to cloud');
                return $synced;
            }
            
            // Filter: STRICT RULE - only sync if local has newer/latest updated_at
            $usersToSync = $localUsers->filter(function ($user) use ($existingCloudRecords, $cloudDeletedIds) {
                $userId = $user->user_id;
                
                // Skip if this record was deleted in cloud (prevent restoring deleted records)
                if (in_array($userId, $cloudDeletedIds)) {
                    Log::debug("Skipping user {$userId} - was deleted in cloud");
                    return false;
                }
                
                // If not in cloud, needs to be synced (new record)
                if (!isset($existingCloudRecords[$userId])) {
                    return true;
                }
                
                // STRICT RULE: Compare updated_at timestamps - only sync if local is newer or equal
                if (!$this->shouldSyncLocalToCloud($user, $existingCloudRecords[$userId], 'user', $userId)) {
                    return false;
                }
                
                // If timestamps are equal, check if data is different
                $localData = [
                    'user_id' => $user->user_id,
                    'user_role' => $user->user_role,
                    'user_department' => $user->user_department,
                    'user_fname' => $user->user_fname,
                    'user_lname' => $user->user_lname,
                    'username' => $user->username,
                    'user_password' => $user->user_password,
                ];
                
                return $this->recordsAreDifferent($localData, $existingCloudRecords[$userId]);
            });
            
            if ($usersToSync->isEmpty()) {
                Log::info('No new or changed users to sync to cloud');
                return $synced;
            }
            
            $payload = $usersToSync->map(function ($u) {
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
            Log::info('Bulk users result', ['upserted' => $upserted, 'success' => $resp['success'] ?? null, 'local_count' => count($usersToSync)]);
            
            if ($resp['success'] && $upserted > 0) {
                $synced = $usersToSync->pluck('user_id')->all();
                $newCount = count($usersToSync->filter(function($u) use ($existingCloudRecords) { return !isset($existingCloudRecords[$u->user_id]); }));
                $updatedCount = count($usersToSync) - $newCount;
                Log::info('Synced ' . count($synced) . ' users to cloud (' . $newCount . ' new, ' . $updatedCount . ' updated)');
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
            // STEP 1: Process deletions for this table before syncing data
            $this->syncTableDeletionsToCloud('tbl_activity_logs', 'activity-logs');
            
            // Get deleted IDs from cloud (to prevent syncing records that were deleted in cloud)
            $cloudDeletedIds = $this->getDeletedIdsFromCloud('activity-logs');
            
            // Get existing activity log IDs from cloud (recent window)
            $existingCloudIds = $this->getExistingCloudIds('activity-logs', 'logs_id', ['days' => 30]);
            
            // Only send logs that don't exist on cloud (append-only, no updates needed)
            // Also skip records that were deleted in cloud
            $localLogs = ActivityLog::all()->filter(function ($log) use ($existingCloudIds, $cloudDeletedIds) {
                $logId = $log->logs_id;
                
                // Skip if this record was deleted in cloud (prevent restoring deleted records)
                if (in_array($logId, $cloudDeletedIds)) {
                    Log::debug("Skipping activity log {$logId} - was deleted in cloud");
                    return false;
                }
                
                return !in_array($logId, $existingCloudIds);
            });
            
            if ($localLogs->isEmpty()) {
                Log::info('No new activity logs to sync to cloud');
                return $synced;
            }
            
            $payload = $localLogs->map(function ($log) {
                return [
                    'logs_id' => $log->logs_id,
                    'user_id' => $log->user_id,
                    'logs_action' => $log->logs_action,
                    'logs_description' => $log->logs_description,
                    'logs_timestamp' => $this->formatDateTime($log->logs_timestamp),
                    'logs_module' => $log->logs_module,
                    'created_at' => $this->formatDateTime($log->created_at),
                    'updated_at' => $this->formatDateTime($log->updated_at),
                ];
            })->values()->all();
            
            $resp = $this->pushBulkToCloud('activity-logs', $payload);
            $upserted = $resp['data']['upserted'] ?? 0;
            Log::info('Selective activity-logs sync result', ['upserted' => $upserted, 'to_send' => count($payload)]);
            
            if ($resp['success'] && $upserted > 0) {
                $synced = $localLogs->pluck('logs_id')->all();
                Log::info('Synced ' . count($synced) . ' new activity logs to cloud');
            } elseif ($resp['success'] && $upserted == 0) {
                Log::warning("Activity logs sync returned success but 0 records were upserted (nothing new).");
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
            // STEP 1: Process deletions for this table before syncing data
            $this->syncTableDeletionsToCloud('tbl_teaching_load_archive', 'teaching-load-archives');
            
            // Get deleted IDs from cloud (to prevent syncing records that were deleted in cloud)
            $cloudDeletedIds = $this->getDeletedIdsFromCloud('teaching-load-archives');
            
            // Get existing teaching load archive IDs from cloud
            $existingCloudIds = $this->getExistingCloudIds('teaching-load-archives', 'archive_id');
            
            // Get all local teaching load archives and filter out existing ones (append-only, no updates needed)
            // Also skip records that were deleted in cloud
            $localArchives = TeachingLoadArchive::all()->filter(function ($archive) use ($existingCloudIds, $cloudDeletedIds) {
                $archiveId = $archive->archive_id;
                
                // Skip if this record was deleted in cloud (prevent restoring deleted records)
                if (in_array($archiveId, $cloudDeletedIds)) {
                    Log::debug("Skipping teaching load archive {$archiveId} - was deleted in cloud");
                    return false;
                }
                
                return !in_array($archiveId, $existingCloudIds);
            });
            
            if ($localArchives->isEmpty()) {
                Log::info('No new teaching load archives to sync to cloud');
                return $synced;
            }
            
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
                    'created_at' => $this->formatDateTime($a->created_at),
                    'updated_at' => $this->formatDateTime($a->updated_at),
                ];
            })->values()->all();
            $resp = $this->pushBulkToCloud('teaching-load-archives', $payload);
            $upserted = $resp['data']['upserted'] ?? 0;
            Log::info('Bulk teaching-load-archives result', ['upserted' => $upserted, 'success' => $resp['success'] ?? null, 'local_count' => count($localArchives)]);
            
            if ($resp['success'] && $upserted > 0) {
                $synced = $localArchives->pluck('archive_id')->all();
                Log::info('Synced ' . count($synced) . ' new teaching load archives to cloud');
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
            // STEP 1: Process deletions for this table before syncing data
            $this->syncTableDeletionsToCloud('tbl_attendance_record_archive', 'attendance-record-archives');
            
            // Get deleted IDs from cloud (to prevent syncing records that were deleted in cloud)
            $cloudDeletedIds = $this->getDeletedIdsFromCloud('attendance-record-archives');
            
            // Get existing attendance record archive IDs from cloud
            $existingCloudIds = $this->getExistingCloudIds('attendance-record-archives', 'archive_id');
            
            // Get all local attendance record archives and filter out existing ones (append-only, no updates needed)
            // Also skip records that were deleted in cloud
            $localArchives = AttendanceRecordArchive::all()->filter(function ($archive) use ($existingCloudIds, $cloudDeletedIds) {
                $archiveId = $archive->archive_id;
                
                // Skip if this record was deleted in cloud (prevent restoring deleted records)
                if (in_array($archiveId, $cloudDeletedIds)) {
                    Log::debug("Skipping attendance record archive {$archiveId} - was deleted in cloud");
                    return false;
                }
                
                return !in_array($archiveId, $existingCloudIds);
            });
            
            if ($localArchives->isEmpty()) {
                Log::info('No new attendance record archives to sync to cloud');
                return $synced;
            }
            
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
                $synced = $localArchives->pluck('archive_id')->all();
                Log::info('Synced ' . count($synced) . ' new attendance record archives to cloud');
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
            
            // Using Guzzle directly with file stream to avoid memory exhaustion for large files
            // Directory structure: storage/app/public/{directory}/
            // Use file resource/stream instead of file_get_contents to handle large files efficiently
            $client = new Client([
                'timeout' => 300, // 5 minutes for large video files
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->cloudApiKey,
                ],
            ]);
            
            // Open file as stream to avoid loading entire file into memory
            $fileHandle = fopen($fullPath, 'rb');
            if (!$fileHandle) {
                Log::error("Failed to open file for upload: {$fullPath}");
                return null;
            }
            
            try {
                $response = $client->post("{$this->cloudApiUrl}/sync/upload/{$directory}", [
                    'multipart' => [
                        [
                            'name' => 'file',
                            'contents' => $fileHandle, // Guzzle supports file resources for streaming
                            'filename' => $filename,
                        ],
                    ],
                ]);
                
                // Convert Guzzle response to Laravel response format
                $statusCode = $response->getStatusCode();
                $body = $response->getBody()->getContents();
                
                if ($statusCode >= 200 && $statusCode < 300) {
                    $result = json_decode($body, true);
                    return [
                        'success' => true,
                        'url' => $result['url'] ?? null,
                        'path' => $result['path'] ?? null
                    ];
                }
                
                Log::error("Failed to upload file to cloud: " . $body);
                return null;
            } catch (\GuzzleHttp\Exception\RequestException $e) {
                Log::error("Error uploading file to cloud ({$localPath}): " . $e->getMessage());
                return null;
            } finally {
                // Ensure file handle is closed even if request fails
                if (is_resource($fileHandle)) {
                    fclose($fileHandle);
                }
            }
            
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
     * Sync attendance snapshot image to cloud
     * @param string $snapshotPath Local snapshot path
     * @return string Cloud snapshot path/URL
     */
    protected function syncAttendanceSnapshot($snapshotPath)
    {
        if (empty($snapshotPath)) {
            return $snapshotPath;
        }
        
        // Directory: storage/app/public/attendance_snapshots/
        $uploadResult = $this->uploadFileToCloud($snapshotPath, 'attendance_snapshots');
        
        if ($uploadResult && $uploadResult['success']) {
            Log::info("Synced attendance snapshot to cloud: {$snapshotPath} -> " . ($uploadResult['path'] ?? $uploadResult['url']));
            return $uploadResult['path'] ?? $uploadResult['url'] ?? $snapshotPath;
        }
        
        Log::warning("Failed to sync attendance snapshot: {$snapshotPath}");
        return $snapshotPath;
    }
    
    /**
     * Download attendance snapshot image from cloud
     * @param string $cloudSnapshotPath Cloud snapshot path/URL
     * @return string Local snapshot path
     */
    protected function downloadAttendanceSnapshot($cloudSnapshotPath)
    {
        if (empty($cloudSnapshotPath)) {
            return $cloudSnapshotPath;
        }
        
        $localPath = $this->downloadFileFromCloud($cloudSnapshotPath, 'attendance_snapshots');
        
        if ($localPath) {
            Log::info("Downloaded attendance snapshot from cloud: {$cloudSnapshotPath} -> {$localPath}");
            return $localPath;
        }
        
        Log::warning("Failed to download attendance snapshot: {$cloudSnapshotPath}");
        return $cloudSnapshotPath;
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
            
            // STEP 1: Process deletions FIRST - delete records locally that were deleted in cloud
            // This ensures deletions are processed before any data sync happens
            Log::info('STEP 1: Processing all deletions from cloud...');
            $this->processAllDeletionsFromCloud();
            
            // STEP 2: Sync data in order of dependencies - users first
            Log::info('STEP 2: Syncing data from cloud...');
            $results['synced']['users'] = $this->syncUsersFromCloud();
            $results['synced']['subjects'] = $this->syncSubjectsFromCloud();
            $results['synced']['rooms'] = $this->syncRoomsFromCloud();
            $results['synced']['cameras'] = $this->syncCamerasFromCloud();
            $results['synced']['faculties'] = $this->syncFacultiesFromCloud();
            $results['synced']['teaching_loads'] = $this->syncTeachingLoadsFromCloud();
            $results['synced']['leaves'] = $this->syncLeavesFromCloud();
            $results['synced']['passes'] = $this->syncPassesFromCloud();
            $results['synced']['official_matters'] = $this->syncOfficialMattersFromCloud();
            $results['synced']['attendance_records'] = $this->syncAttendanceRecordsFromCloud();
            $results['synced']['recognition_logs'] = $this->syncRecognitionLogsFromCloud();
            $results['synced']['stream_recordings'] = $this->syncStreamRecordingsFromCloud();
            $results['synced']['activity_logs'] = $this->syncActivityLogsFromCloud();
            $results['synced']['teaching_load_archives'] = $this->syncTeachingLoadArchivesFromCloud();
            $results['synced']['attendance_record_archives'] = $this->syncAttendanceRecordArchivesFromCloud();
            
            // Final pass: Process any deletions from cloud that happened during the sync process
            // This ensures deletions are processed even if they occur while sync is running
            Log::info('STEP 3: Performing final deletion processing from cloud to catch deletions that occurred during sync...');
            $this->processAllDeletionsFromCloud();
            
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
     * Get existing IDs from cloud (for filtering local-to-cloud sync)
     * @param string $endpoint API endpoint
     * @param string $idKey The key name for the ID field (e.g., 'user_id', 'room_no')
     * @param array $params Additional query parameters
     * @return array Array of existing IDs
     */
    protected function getExistingCloudIds(string $endpoint, string $idKey, array $params = [])
    {
        try {
            $cloudData = $this->fetchBulkFromCloud($endpoint, $params);
            $existingIds = [];
            
            foreach ($cloudData as $record) {
                if (isset($record[$idKey])) {
                    $existingIds[] = $record[$idKey];
                }
            }
            
            return $existingIds;
        } catch (\Exception $e) {
            Log::error("Error getting existing cloud IDs for {$endpoint}: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get existing records from cloud with their data (for compare-and-update)
     * @param string $endpoint API endpoint
     * @param string $idKey The key name for the ID field (e.g., 'user_id', 'room_no')
     * @param array $params Additional query parameters
     * @return array Array keyed by ID with record data
     */
    protected function getExistingCloudRecords(string $endpoint, string $idKey, array $params = [])
    {
        try {
            $cloudData = $this->fetchBulkFromCloud($endpoint, $params);
            $existingRecords = [];
            
            foreach ($cloudData as $record) {
                if (isset($record[$idKey])) {
                    $existingRecords[$record[$idKey]] = $record;
                }
            }
            
            return $existingRecords;
        } catch (\Exception $e) {
            Log::error("Error getting existing cloud records for {$endpoint}: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Compare two records to determine if they're different
     * @param array $localRecord Local record data
     * @param array $cloudRecord Cloud record data
     * @param array $fieldsToCompare Fields to compare (if empty, compares all fields except timestamps)
     * @return bool True if records are different, false if same
     */
    protected function recordsAreDifferent(array $localRecord, array $cloudRecord, array $fieldsToCompare = [])
    {
        // Fields to ignore in comparison (timestamps, file paths that might differ)
        $ignoreFields = ['created_at', 'updated_at', 'time_in_snapshot', 'time_out_snapshot', 'lp_image', 'om_attachment', 'faculty_images', 'filepath'];
        
        // If no specific fields provided, compare all except ignored ones
        if (empty($fieldsToCompare)) {
            $allFields = array_unique(array_merge(array_keys($localRecord), array_keys($cloudRecord)));
            $fieldsToCompare = array_diff($allFields, $ignoreFields);
        }
        
        foreach ($fieldsToCompare as $field) {
            $localValue = $localRecord[$field] ?? null;
            $cloudValue = $cloudRecord[$field] ?? null;
            
            // Normalize values for comparison
            $localValue = $this->normalizeValueForComparison($localValue);
            $cloudValue = $this->normalizeValueForComparison($cloudValue);
            
            if ($localValue !== $cloudValue) {
                return true; // Records are different
            }
        }
        
        return false; // Records are the same
    }
    
    /**
     * Normalize value for comparison (handles dates, nulls, etc.)
     */
    protected function normalizeValueForComparison($value)
    {
        if ($value === null || $value === '') {
            return null;
        }
        
        // Normalize dates
        if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}/', $value)) {
            try {
                return \Carbon\Carbon::parse($value)->format('Y-m-d');
            } catch (\Exception $e) {
                return $value;
            }
        }
        
        return $value;
    }
    
    /**
     * Check if cloud record is newer than local record based on updated_at timestamp
     * Returns: 1 if cloud is newer, -1 if local is newer, 0 if same or cannot determine
     * STRICT: If one timestamp is missing, treat the one with timestamp as newer
     */
    protected function compareRecordTimestamps($localUpdatedAt, $cloudUpdatedAt)
    {
        // STRICT RULE: If one is missing, the one with timestamp is considered newer
        if (empty($localUpdatedAt) && !empty($cloudUpdatedAt)) {
            return 1; // Cloud has timestamp, local doesn't - cloud is newer
        }
        if (!empty($localUpdatedAt) && empty($cloudUpdatedAt)) {
            return -1; // Local has timestamp, cloud doesn't - local is newer
        }
        if (empty($localUpdatedAt) && empty($cloudUpdatedAt)) {
            return 0; // Both missing - cannot determine
        }
        
        try {
            // CRITICAL: Convert cloud timestamp from UTC to Asia/Manila before comparison
            // Cloud database stores timestamps in UTC, but local is in Asia/Manila
            $cloudUpdatedAtConverted = $this->convertCloudTimestampToLocalTimezone($cloudUpdatedAt);
            
            // Parse both timestamps (local is already in Asia/Manila, cloud is now converted)
            $localTime = \Carbon\Carbon::parse($localUpdatedAt)->setTimezone('Asia/Manila');
            $cloudTime = \Carbon\Carbon::parse($cloudUpdatedAtConverted)->setTimezone('Asia/Manila');
            
            if ($cloudTime->gt($localTime)) {
                return 1; // Cloud is newer
            } elseif ($localTime->gt($cloudTime)) {
                return -1; // Local is newer
            } else {
                return 0; // Same timestamp
            }
        } catch (\Exception $e) {
            Log::debug("Could not compare timestamps: " . $e->getMessage());
            return 0; // Cannot determine on error
        }
    }
    
    /**
     * STRICT TIMESTAMP RULE: Check if local record should be synced to cloud
     * Returns true if local should be synced (local is newer or equal), false otherwise
     */
    protected function shouldSyncLocalToCloud($localRecord, $cloudRecord, $recordType, $recordId)
    {
        $localUpdatedAt = is_object($localRecord) 
            ? ($localRecord->updated_at ? $this->formatDateTime($localRecord->updated_at) : null)
            : ($localRecord['updated_at'] ?? null);
        
        $cloudUpdatedAt = is_array($cloudRecord) 
            ? ($cloudRecord['updated_at'] ?? null)
            : ($cloudRecord->updated_at ?? null);
        
        $comparison = $this->compareRecordTimestamps($localUpdatedAt, $cloudUpdatedAt);
        
        if ($comparison === 1) {
            // Cloud is newer, skip syncing local to cloud
            Log::debug("STRICT: Skipping {$recordType} {$recordId} - cloud has newer updated_at (cloud: {$cloudUpdatedAt}, local: {$localUpdatedAt})");
            return false;
        }
        
        if ($comparison === -1) {
            // Local is newer, sync it
            Log::debug("STRICT: Syncing {$recordType} {$recordId} - local has newer updated_at (local: {$localUpdatedAt}, cloud: {$cloudUpdatedAt})");
            return true;
        }
        
        // Timestamps are equal or cannot determine - check if data is different
        return true; // Default to syncing if timestamps are equal (will be filtered by recordsAreDifferent if needed)
    }
    
    /**
     * STRICT TIMESTAMP RULE: Check if cloud record should be synced to local
     * Returns true if cloud should be synced (cloud is newer or equal), false otherwise
     */
    protected function shouldSyncCloudToLocal($localRecord, $cloudRecord, $recordType, $recordId)
    {
        $localUpdatedAt = is_array($localRecord) 
            ? ($localRecord['updated_at'] ?? null)
            : ($localRecord->updated_at ?? null);
        
        $cloudUpdatedAt = is_array($cloudRecord) 
            ? ($cloudRecord['updated_at'] ?? null)
            : ($cloudRecord->updated_at ?? null);
        
        $comparison = $this->compareRecordTimestamps($localUpdatedAt, $cloudUpdatedAt);
        
        if ($comparison === -1) {
            // Local is newer, skip syncing cloud to local
            Log::debug("STRICT: Skipping {$recordType} {$recordId} from cloud - local has newer updated_at (local: {$localUpdatedAt}, cloud: {$cloudUpdatedAt})");
            return false;
        }
        
        if ($comparison === 1) {
            // Cloud is newer, sync it
            Log::debug("STRICT: Syncing {$recordType} {$recordId} from cloud - cloud has newer updated_at (cloud: {$cloudUpdatedAt}, local: {$localUpdatedAt})");
            return true;
        }
        
        // Timestamps are equal or cannot determine - check if data is different
        return true; // Default to syncing if timestamps are equal (will be filtered by recordsAreDifferent if needed)
    }
    
    /**
     * Check if we should skip syncing a record because the other side has a newer version
     * Used during local-to-cloud sync to prevent overwriting cloud updates
     */
    protected function shouldSkipDueToNewerVersion($localRecord, $cloudRecord, $recordType, $recordId)
    {
        $localUpdatedAt = is_object($localRecord) 
            ? ($localRecord->updated_at ? $this->formatDateTime($localRecord->updated_at) : null)
            : ($localRecord['updated_at'] ?? null);
        
        $cloudUpdatedAt = $cloudRecord['updated_at'] ?? null;
        
        $comparison = $this->compareRecordTimestamps($localUpdatedAt, $cloudUpdatedAt);
        
        if ($comparison === 1) {
            // Cloud is newer, skip syncing to avoid overwriting cloud's updates
            Log::debug("Skipping {$recordType} {$recordId} - cloud has newer version (cloud: {$cloudUpdatedAt}, local: {$localUpdatedAt})");
            return true;
        }
        
        return false;
    }
    
    /**
     * Check if we should skip syncing a record from cloud because local has a newer version
     * Used during cloud-to-local sync to prevent overwriting local updates
     */
    protected function shouldSkipCloudRecordDueToNewerLocal($localRecord, $cloudRecord, $recordType, $recordId)
    {
        $localUpdatedAt = $localRecord['updated_at'] ?? null;
        $cloudUpdatedAt = $cloudRecord['updated_at'] ?? null;
        
        $comparison = $this->compareRecordTimestamps($localUpdatedAt, $cloudUpdatedAt);
        
        if ($comparison === -1) {
            // Local is newer, skip syncing from cloud to avoid overwriting local updates
            Log::debug("Skipping {$recordType} {$recordId} from cloud - local has newer version (local: {$localUpdatedAt}, cloud: {$cloudUpdatedAt})");
            return true;
        }
        
        return false;
    }
    
    /**
     * Track a deleted record ID (to prevent it from being restored during sync)
     * This should be called when a record is deleted locally
     * @param string $tableName Table name (e.g., 'tbl_user', 'tbl_room')
     * @param mixed $recordId The ID of the deleted record
     * @param int $ttlDays How many days to remember this deletion (default: 90 days)
     */
    public function trackDeletion(string $tableName, $recordId, int $ttlDays = 90, array $metadata = [])
    {
        try {
            $cacheKey = "sync_deletion:{$tableName}:{$recordId}";
            $listKey = "sync_deletion_list:{$tableName}";
            $ttl = now()->addDays($ttlDays);
            
            // Store deletion with expiration and metadata
            Cache::put($cacheKey, [
                'table' => $tableName,
                'id' => $recordId,
                'deleted_at' => now()->toDateTimeString(),
                'expires_at' => $ttl->toDateTimeString(),
                'metadata' => $metadata, // Store additional data (e.g., lp_type for leaves/passes)
            ], $ttl);
            
            // Also add to list of deleted IDs for this table (for syncing to cloud)
            $deletedIds = Cache::get($listKey, []);
            if (!in_array($recordId, $deletedIds)) {
                $deletedIds[] = $recordId;
                Cache::put($listKey, $deletedIds, $ttl);
            }
            
            Log::info("Tracked deletion: {$tableName} #{$recordId} (expires in {$ttlDays} days)");
        } catch (\Exception $e) {
            Log::error("Error tracking deletion for {$tableName} #{$recordId}: " . $e->getMessage());
        }
    }
    
    /**
     * Check if a record was deleted locally (to prevent restoring from cloud)
     * @param string $tableName Table name (e.g., 'users', 'rooms')
     * @param mixed $recordId The ID to check
     * @return bool True if the record was deleted locally
     */
    protected function isDeletedLocally(string $tableName, $recordId): bool
    {
        try {
            $cacheKey = "sync_deletion:{$tableName}:{$recordId}";
            return Cache::has($cacheKey);
        } catch (\Exception $e) {
            Log::error("Error checking deletion status for {$tableName} #{$recordId}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get all deleted IDs for a table (for syncing deletions to cloud)
     * @param string $tableName Table name
     * @return array Array of deleted IDs
     */
    protected function getDeletedIds(string $tableName): array
    {
        try {
            $listKey = "sync_deletion_list:{$tableName}";
            $deletedIds = Cache::get($listKey, []);
            
            // Filter out IDs that are no longer in cache (expired)
            $validDeletedIds = [];
            foreach ($deletedIds as $id) {
                $cacheKey = "sync_deletion:{$tableName}:{$id}";
                if (Cache::has($cacheKey)) {
                    $validDeletedIds[] = $id;
                }
            }
            
            // Update the list if some IDs expired
            if (count($validDeletedIds) !== count($deletedIds)) {
                Cache::put($listKey, $validDeletedIds, now()->addDays(90));
            }
            
            return $validDeletedIds;
        } catch (\Exception $e) {
            Log::error("Error getting deleted IDs for {$tableName}: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Sync deletions to cloud (notify cloud about locally deleted records)
     * @param string $endpoint API endpoint (e.g., 'users', 'rooms')
     * @param array $deletedIds Array of deleted record IDs
     */
    protected function syncDeletionsToCloud(string $endpoint, array $deletedIds)
    {
        if (empty($deletedIds)) {
            return;
        }
        
        try {
            $url = "{$this->cloudApiUrl}/sync/{$endpoint}/deletions";
            
            $response = Http::withHeaders([
                'X-API-Key' => $this->cloudApiKey,
                'Content-Type' => 'application/json',
            ])->post($url, [
                'deleted_ids' => $deletedIds,
                'deleted_at' => now()->toDateTimeString(),
            ]);
            
            if ($response->successful()) {
                Log::info("Synced " . count($deletedIds) . " deletions to cloud for {$endpoint}");
            } else {
                Log::warning("Failed to sync deletions to cloud for {$endpoint}: " . $response->body());
            }
        } catch (\Exception $e) {
            Log::error("Error syncing deletions to cloud for {$endpoint}: " . $e->getMessage());
        }
    }
    
    /**
     * Get deleted IDs from cloud (to delete locally)
     * @param string $endpoint API endpoint
     * @return array Array of deleted record IDs
     */
    protected function getDeletedIdsFromCloud(string $endpoint): array
    {
        try {
            $url = "{$this->cloudApiUrl}/sync/{$endpoint}/deletions";
            
            $response = Http::withHeaders([
                'X-API-Key' => $this->cloudApiKey,
            ])->get($url);
            
            if ($response->successful()) {
                $data = $response->json();
                return $data['deleted_ids'] ?? [];
            }
            
            return [];
        } catch (\Exception $e) {
            Log::error("Error getting deleted IDs from cloud for {$endpoint}: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Map table name to endpoint name for deletion tracking
     * @param string $tableName Database table name
     * @return string|null Endpoint name or null if not mappable
     */
    protected function getTableEndpoint(string $tableName): ?string
    {
        $mapping = [
            'tbl_user' => 'users',
            'tbl_subject' => 'subjects',
            'tbl_room' => 'rooms',
            'tbl_camera' => 'cameras',
            'tbl_faculty' => 'faculties',
            'tbl_teaching_load' => 'teaching-loads',
            'tbl_attendance_record' => 'attendance-records',
            'tbl_leave_pass' => 'leaves', // or 'passes' depending on lp_type
            'tbl_official_matters' => 'official-matters',
            'tbl_recognition_logs' => 'recognition-logs',
            'tbl_stream_recordings' => 'stream-recordings',
            'tbl_activity_logs' => 'activity-logs',
            'tbl_teaching_load_archive' => 'teaching-load-archives',
            'tbl_attendance_record_archive' => 'attendance-record-archives',
        ];
        
        return $mapping[$tableName] ?? null;
    }
    
    /**
     * Sync all deletions to cloud (final pass to catch deletions that occurred during sync)
     * This ensures deletions are synced even if they happen while sync is running
     */
    public function syncAllDeletionsToCloud()
    {
        try {
            // Define all tables and their endpoints
            $tables = [
                'tbl_user' => 'users',
                'tbl_subject' => 'subjects',
                'tbl_room' => 'rooms',
                'tbl_camera' => 'cameras',
                'tbl_faculty' => 'faculties',
                'tbl_teaching_load' => 'teaching-loads',
                'tbl_attendance_record' => 'attendance-records',
                'tbl_official_matters' => 'official-matters',
                'tbl_recognition_logs' => 'recognition-logs',
                'tbl_stream_recordings' => 'stream-recordings',
                'tbl_activity_logs' => 'activity-logs',
                'tbl_teaching_load_archive' => 'teaching-load-archives',
                'tbl_attendance_record_archive' => 'attendance-record-archives',
            ];
            
            // Sync deletions for each table
            foreach ($tables as $tableName => $endpoint) {
                $deletedIds = $this->getDeletedIds($tableName);
                if (!empty($deletedIds)) {
                    Log::info("Syncing " . count($deletedIds) . " deletions for {$tableName} to cloud endpoint {$endpoint}");
                    $this->syncDeletionsToCloud($endpoint, $deletedIds);
                }
            }
            
            // Special handling for leaves and passes (they share the same table)
            $deletedIds = $this->getDeletedIds('tbl_leave_pass');
            if (!empty($deletedIds)) {
                $leaveDeletedIds = [];
                $passDeletedIds = [];
                
                foreach ($deletedIds as $id) {
                    $cacheKey = "sync_deletion:tbl_leave_pass:{$id}";
                    $deletionData = Cache::get($cacheKey);
                    $lpType = $deletionData['metadata']['lp_type'] ?? null;
                    
                    if ($lpType === 'Leave') {
                        $leaveDeletedIds[] = $id;
                    } elseif ($lpType === 'Pass') {
                        $passDeletedIds[] = $id;
                    }
                }
                
                if (!empty($leaveDeletedIds)) {
                    Log::info("Syncing " . count($leaveDeletedIds) . " leave deletions to cloud");
                    $this->syncDeletionsToCloud('leaves', $leaveDeletedIds);
                }
                
            if (!empty($passDeletedIds)) {
                Log::info("Processing " . count($passDeletedIds) . " pass deletions before syncing to cloud");
                $this->syncDeletionsToCloud('passes', $passDeletedIds);
            }
            }
            
            Log::info('Final deletion sync completed');
        } catch (\Exception $e) {
            Log::error("Error in final deletion sync: " . $e->getMessage());
        }
    }
    
    /**
     * Process all deletions from cloud (final pass to catch deletions that occurred during sync)
     * This ensures deletions are processed even if they happen while sync is running
     */
    public function processAllDeletionsFromCloud()
    {
        try {
            // Define all tables, their endpoints, and primary keys
            $tables = [
                ['endpoint' => 'users', 'table' => 'tbl_user', 'idKey' => 'user_id'],
                ['endpoint' => 'subjects', 'table' => 'tbl_subject', 'idKey' => 'subject_id'],
                ['endpoint' => 'rooms', 'table' => 'tbl_room', 'idKey' => 'room_no'],
                ['endpoint' => 'cameras', 'table' => 'tbl_camera', 'idKey' => 'camera_id'],
                ['endpoint' => 'faculties', 'table' => 'tbl_faculty', 'idKey' => 'faculty_id'],
                ['endpoint' => 'teaching-loads', 'table' => 'tbl_teaching_load', 'idKey' => 'teaching_load_id'],
                ['endpoint' => 'attendance-records', 'table' => 'tbl_attendance_record', 'idKey' => 'record_id'],
                ['endpoint' => 'leaves', 'table' => 'tbl_leave_pass', 'idKey' => 'lp_id'],
                ['endpoint' => 'passes', 'table' => 'tbl_leave_pass', 'idKey' => 'lp_id'],
                ['endpoint' => 'official-matters', 'table' => 'tbl_official_matters', 'idKey' => 'om_id'],
                ['endpoint' => 'recognition-logs', 'table' => 'tbl_recognition_logs', 'idKey' => 'log_id'],
                ['endpoint' => 'stream-recordings', 'table' => 'tbl_stream_recordings', 'idKey' => 'recording_id'],
                ['endpoint' => 'activity-logs', 'table' => 'tbl_activity_logs', 'idKey' => 'logs_id'],
                ['endpoint' => 'teaching-load-archives', 'table' => 'tbl_teaching_load_archive', 'idKey' => 'archive_id'],
                ['endpoint' => 'attendance-record-archives', 'table' => 'tbl_attendance_record_archive', 'idKey' => 'archive_id'],
            ];
            
            // Process deletions for each table
            foreach ($tables as $config) {
                $this->processDeletionsFromCloud($config['endpoint'], $config['table'], $config['idKey']);
            }
            
            Log::info('Final deletion processing from cloud completed');
        } catch (\Exception $e) {
            Log::error("Error in final deletion processing from cloud: " . $e->getMessage());
        }
    }
    
    /**
     * Process deletions from cloud (delete records locally that were deleted in cloud)
     * @param string $endpoint API endpoint
     * @param string $tableName Local table name
     * @param string $idKey Primary key field name
     */
    protected function processDeletionsFromCloud(string $endpoint, string $tableName, string $idKey)
    {
        try {
            $deletedIds = $this->getDeletedIdsFromCloud($endpoint);
            
            if (empty($deletedIds)) {
                return;
            }
            
            $deletedCount = 0;
            foreach ($deletedIds as $deletedId) {
                try {
                    // Special handling for leaves and passes (they share the same table)
                    if ($tableName === 'tbl_leave_pass') {
                        // First, get the record to check its type (before deletion)
                        $record = DB::table($tableName)->where($idKey, $deletedId)->first();
                        
                        if (!$record) {
                            continue;
                        }
                        
                        $lpType = $record->lp_type ?? null;
                        
                        // Filter by lp_type based on endpoint
                        if ($endpoint === 'leaves' && $lpType !== 'Leave') {
                            // This is not a leave, skip it
                            continue;
                        } elseif ($endpoint === 'passes' && $lpType !== 'Pass') {
                            // This is not a pass, skip it
                            continue;
                        }
                        
                        // Delete the record locally (with type filter to ensure we delete the right one)
                        DB::table($tableName)
                            ->where($idKey, $deletedId)
                            ->where('lp_type', $lpType)
                            ->delete();
                        
                        // Track the deletion locally (so it won't be restored)
                        // Include lp_type metadata for proper filtering
                        $this->trackDeletion($tableName, $deletedId, 90, ['lp_type' => $lpType]);
                        
                        $deletedCount++;
                        Log::info("Deleted {$tableName} #{$deletedId} (type: {$lpType}) (synced from cloud deletion)");
                    } else {
                        // For other tables, standard deletion
                        $exists = DB::table($tableName)->where($idKey, $deletedId)->exists();
                        
                        if ($exists) {
                            // Delete the record locally
                            DB::table($tableName)->where($idKey, $deletedId)->delete();
                            
                            // Track the deletion locally (so it won't be restored)
                            $this->trackDeletion($tableName, $deletedId);
                            
                            $deletedCount++;
                            Log::info("Deleted {$tableName} #{$deletedId} (synced from cloud deletion)");
                        }
                    }
                } catch (\Exception $e) {
                    Log::error("Error deleting {$tableName} #{$deletedId} from cloud deletion: " . $e->getMessage());
                }
            }
            
            if ($deletedCount > 0) {
                Log::info("Processed {$deletedCount} deletions from cloud for {$endpoint}");
            }
        } catch (\Exception $e) {
            Log::error("Error processing deletions from cloud for {$endpoint}: " . $e->getMessage());
        }
    }
    
    /**
     * Get existing IDs from local database (for filtering cloud-to-local sync)
     * @param string $table Table name
     * @param string $idKey The key name for the ID field (e.g., 'user_id', 'room_no')
     * @return array Array of existing IDs
     */
    protected function getExistingLocalIds(string $table, string $idKey)
    {
        try {
            $existingIds = DB::table($table)->pluck($idKey)->toArray();
            return $existingIds;
        } catch (\Exception $e) {
            Log::error("Error getting existing local IDs for {$table}: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get existing records from local database with their data (for compare-and-update)
     * @param string $table Table name
     * @param string $idKey The key name for the ID field (e.g., 'user_id', 'room_no')
     * @return array Array keyed by ID with record data
     */
    protected function getExistingLocalRecords(string $table, string $idKey)
    {
        try {
            $records = DB::table($table)->get();
            $existingRecords = [];
            
            foreach ($records as $record) {
                $id = $record->$idKey ?? null;
                if ($id !== null) {
                    $existingRecords[$id] = (array)$record;
                }
            }
            
            return $existingRecords;
        } catch (\Exception $e) {
            Log::error("Error getting existing local records for {$table}: " . $e->getMessage());
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
            // STEP 1: Process deletions for this table before syncing data
            $this->processTableDeletionsFromCloud('users', 'tbl_user', 'user_id');
            
            // Get existing local records with their data for comparison
            $existingLocalRecords = $this->getExistingLocalRecords('tbl_user', 'user_id');
            
            // Get all users from cloud
            $cloudUsers = $this->fetchBulkFromCloud('users');
            
            if (empty($cloudUsers)) {
                Log::info('No users found in cloud');
                return $synced;
            }
            
            // Filter: only sync new records or records that have changed
            $usersToSync = array_filter($cloudUsers, function ($cloudUser) use ($existingLocalRecords) {
                $userId = $cloudUser['user_id'] ?? null;
                if (!$userId) return false;
                
                // Skip if this record was deleted locally (prevent restoring deleted records)
                if ($this->isDeletedLocally('tbl_user', $userId)) {
                    Log::debug("Skipping user {$userId} - was deleted locally");
                    return false;
                }
                
                // If not in local, needs to be synced (new record)
                if (!isset($existingLocalRecords[$userId])) {
                    return true;
                }
                
                // STRICT RULE: Compare updated_at timestamps - only sync if cloud is newer or equal
                if (!$this->shouldSyncCloudToLocal($existingLocalRecords[$userId], $cloudUser, 'user', $userId)) {
                    return false;
                }
                
                // If in local, compare data to see if it changed
                $localData = [
                    'user_id' => $existingLocalRecords[$userId]['user_id'],
                    'user_role' => $existingLocalRecords[$userId]['user_role'] ?? null,
                    'user_department' => $existingLocalRecords[$userId]['user_department'] ?? null,
                    'user_fname' => $existingLocalRecords[$userId]['user_fname'] ?? null,
                    'user_lname' => $existingLocalRecords[$userId]['user_lname'] ?? null,
                    'username' => $existingLocalRecords[$userId]['username'] ?? null,
                    'user_password' => $existingLocalRecords[$userId]['user_password'] ?? null,
                ];
                
                return $this->recordsAreDifferent($localData, $cloudUser);
            });
            
            if (empty($usersToSync)) {
                Log::info('No new or changed users to sync from cloud to local');
                return $synced;
            }
            
            // Upsert only changed/new users
            foreach ($usersToSync as $cloudUser) {
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
                            'created_at' => $this->convertCloudTimestampToLocalTimezone($cloudUser['created_at'] ?? null),
                            'updated_at' => $this->convertCloudTimestampToLocalTimezone($cloudUser['updated_at'] ?? null),
                        ]
                    ], ['user_id'], ['user_role', 'user_department', 'user_fname', 'user_lname', 'username', 'user_password', 'created_at', 'updated_at']);
                    
                    $synced[] = $cloudUser['user_id'];
                } catch (\Exception $e) {
                    Log::error("Error syncing user {$cloudUser['user_id']} from cloud: " . $e->getMessage());
                }
            }
            
            $newCount = count(array_filter($usersToSync, function($u) use ($existingLocalRecords) { return !isset($existingLocalRecords[$u['user_id']]); }));
            $updatedCount = count($usersToSync) - $newCount;
            Log::info("Synced " . count($synced) . " users from cloud to local (" . $newCount . " new, " . $updatedCount . " updated)");
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
            // STEP 1: Process deletions for this table before syncing data
            $this->processTableDeletionsFromCloud('subjects', 'tbl_subject', 'subject_id');
            
            // Get existing local records with their data for comparison
            $existingLocalRecords = $this->getExistingLocalRecords('tbl_subject', 'subject_id');
            
            // Get all subjects from cloud
            $cloudSubjects = $this->fetchBulkFromCloud('subjects');
            
            if (empty($cloudSubjects)) {
                return $synced;
            }
            
            // Filter: only sync new records or records that have changed
            $subjectsToSync = array_filter($cloudSubjects, function ($cloudSubject) use ($existingLocalRecords) {
                $subjectId = $cloudSubject['subject_id'] ?? null;
                if (!$subjectId) return false;
                
                // Skip if this record was deleted locally (prevent restoring deleted records)
                if ($this->isDeletedLocally('tbl_subject', $subjectId)) {
                    Log::debug("Skipping subject {$subjectId} - was deleted locally");
                    return false;
                }
                
                // If not in local, needs to be synced (new record)
                if (!isset($existingLocalRecords[$subjectId])) {
                    return true;
                }
                
                // STRICT RULE: Compare updated_at timestamps - only sync if cloud is newer or equal
                if (!$this->shouldSyncCloudToLocal($existingLocalRecords[$subjectId], $cloudSubject, 'subject', $subjectId)) {
                    return false;
                }
                
                // If in local, compare data to see if it changed
                $localData = [
                    'subject_id' => $existingLocalRecords[$subjectId]['subject_id'],
                    'subject_code' => $existingLocalRecords[$subjectId]['subject_code'] ?? null,
                    'subject_description' => $existingLocalRecords[$subjectId]['subject_description'] ?? null,
                    'department' => $existingLocalRecords[$subjectId]['department'] ?? null,
                ];
                
                return $this->recordsAreDifferent($localData, $cloudSubject);
            });
            
            if (empty($subjectsToSync)) {
                Log::info('No new or changed subjects to sync from cloud to local');
                return $synced;
            }
            
            // Upsert only changed/new subjects
            foreach ($subjectsToSync as $cloudSubject) {
                try {
                    DB::table('tbl_subject')->upsert([
                        [
                            'subject_id' => $cloudSubject['subject_id'],
                            'subject_code' => $cloudSubject['subject_code'] ?? null,
                            'subject_description' => $cloudSubject['subject_description'] ?? null,
                            'department' => $cloudSubject['department'] ?? null,
                            'created_at' => $this->convertCloudTimestampToLocalTimezone($cloudSubject['created_at'] ?? null),
                            'updated_at' => $this->convertCloudTimestampToLocalTimezone($cloudSubject['updated_at'] ?? null),
                        ]
                    ], ['subject_id'], ['subject_code', 'subject_description', 'department', 'created_at', 'updated_at']);
                    
                    $synced[] = $cloudSubject['subject_id'];
                } catch (\Exception $e) {
                    Log::error("Error syncing subject {$cloudSubject['subject_id']} from cloud: " . $e->getMessage());
                }
            }
            
            $newCount = count(array_filter($subjectsToSync, function($s) use ($existingLocalRecords) { return !isset($existingLocalRecords[$s['subject_id']]); }));
            $updatedCount = count($subjectsToSync) - $newCount;
            Log::info("Synced " . count($synced) . " subjects from cloud to local (" . $newCount . " new, " . $updatedCount . " updated)");
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
            // STEP 1: Process deletions for this table before syncing data
            $this->processTableDeletionsFromCloud('rooms', 'tbl_room', 'room_no');
            
            // Get existing local records with their data for comparison
            $existingLocalRecords = $this->getExistingLocalRecords('tbl_room', 'room_no');
            
            // Get all rooms from cloud
            $cloudRooms = $this->fetchBulkFromCloud('rooms');
            
            if (empty($cloudRooms)) {
                return $synced;
            }
            
            // Filter: STRICT RULE - only sync if cloud has newer/latest updated_at
            $roomsToSync = array_filter($cloudRooms, function ($cloudRoom) use ($existingLocalRecords) {
                $roomNo = $cloudRoom['room_no'] ?? null;
                if (!$roomNo) return false;
                
                // Skip if this record was deleted locally (prevent restoring deleted records)
                if ($this->isDeletedLocally('tbl_room', $roomNo)) {
                    Log::debug("Skipping room {$roomNo} - was deleted locally");
                    return false;
                }
                
                // If not in local, needs to be synced (new record)
                if (!isset($existingLocalRecords[$roomNo])) {
                    return true;
                }
                
                // STRICT RULE: Compare updated_at timestamps - only sync if cloud is newer or equal
                if (!$this->shouldSyncCloudToLocal($existingLocalRecords[$roomNo], $cloudRoom, 'room', $roomNo)) {
                    return false;
                }
                
                // If timestamps are equal, check if data is different
                $localData = [
                    'room_no' => $existingLocalRecords[$roomNo]['room_no'],
                    'room_name' => $existingLocalRecords[$roomNo]['room_name'] ?? null,
                    'room_building_no' => $existingLocalRecords[$roomNo]['room_building_no'] ?? null,
                ];
                
                return $this->recordsAreDifferent($localData, $cloudRoom);
            });
            
            if (empty($roomsToSync)) {
                Log::info('No new or changed rooms to sync from cloud to local');
                return $synced;
            }
            
            // Upsert only changed/new rooms
            foreach ($roomsToSync as $cloudRoom) {
                try {
                    DB::table('tbl_room')->upsert([
                        [
                            'room_no' => $cloudRoom['room_no'],
                            'room_name' => $cloudRoom['room_name'] ?? null,
                            'room_building_no' => $cloudRoom['room_building_no'] ?? null,
                            'created_at' => $this->convertCloudTimestampToLocalTimezone($cloudRoom['created_at'] ?? null),
                            'updated_at' => $this->convertCloudTimestampToLocalTimezone($cloudRoom['updated_at'] ?? null),
                        ]
                    ], ['room_no'], ['room_name', 'room_building_no', 'created_at', 'updated_at']);
                    
                    $synced[] = $cloudRoom['room_no'];
                } catch (\Exception $e) {
                    Log::error("Error syncing room {$cloudRoom['room_no']} from cloud: " . $e->getMessage());
                }
            }
            
            $newCount = count(array_filter($roomsToSync, function($r) use ($existingLocalRecords) { return !isset($existingLocalRecords[$r['room_no']]); }));
            $updatedCount = count($roomsToSync) - $newCount;
            Log::info("Synced " . count($synced) . " rooms from cloud to local (" . $newCount . " new, " . $updatedCount . " updated)");
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
            // STEP 1: Process deletions for this table before syncing data
            $this->processTableDeletionsFromCloud('cameras', 'tbl_camera', 'camera_id');
            
            // Get existing local records with their data for comparison
            $existingLocalRecords = $this->getExistingLocalRecords('tbl_camera', 'camera_id');
            
            // Get all cameras from cloud
            $cloudCameras = $this->fetchBulkFromCloud('cameras');
            
            if (empty($cloudCameras)) {
                return $synced;
            }
            
            // Filter: STRICT RULE - only sync if cloud has newer/latest updated_at
            $camerasToSync = array_filter($cloudCameras, function ($cloudCamera) use ($existingLocalRecords) {
                $cameraId = $cloudCamera['camera_id'] ?? null;
                if (!$cameraId) return false;
                
                // Skip if this record was deleted locally (prevent restoring deleted records)
                if ($this->isDeletedLocally('tbl_camera', $cameraId)) {
                    Log::debug("Skipping camera {$cameraId} - was deleted locally");
                    return false;
                }
                
                // If not in local, needs to be synced (new record)
                if (!isset($existingLocalRecords[$cameraId])) {
                    return true;
                }
                
                // STRICT RULE: Compare updated_at timestamps - only sync if cloud is newer or equal
                if (!$this->shouldSyncCloudToLocal($existingLocalRecords[$cameraId], $cloudCamera, 'camera', $cameraId)) {
                    return false;
                }
                
                // If timestamps are equal, check if data is different
                $localData = [
                    'camera_id' => $existingLocalRecords[$cameraId]['camera_id'],
                    'camera_name' => $existingLocalRecords[$cameraId]['camera_name'] ?? null,
                    'camera_ip_address' => $existingLocalRecords[$cameraId]['camera_ip_address'] ?? null,
                    'camera_username' => $existingLocalRecords[$cameraId]['camera_username'] ?? null,
                    'camera_password' => $existingLocalRecords[$cameraId]['camera_password'] ?? null,
                    'camera_live_feed' => $existingLocalRecords[$cameraId]['camera_live_feed'] ?? null,
                    'room_no' => $existingLocalRecords[$cameraId]['room_no'] ?? null,
                ];
                
                return $this->recordsAreDifferent($localData, $cloudCamera);
            });
            
            if (empty($camerasToSync)) {
                Log::info('No new or changed cameras to sync from cloud to local');
                return $synced;
            }
            
            // Upsert only changed/new cameras
            foreach ($camerasToSync as $cloudCamera) {
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
                            'created_at' => $this->convertCloudTimestampToLocalTimezone($cloudCamera['created_at'] ?? null),
                            'updated_at' => $this->convertCloudTimestampToLocalTimezone($cloudCamera['updated_at'] ?? null),
                        ]
                    ], ['camera_id'], ['camera_name', 'camera_ip_address', 'camera_username', 'camera_password', 'camera_live_feed', 'room_no', 'created_at', 'updated_at']);
                    
                    $synced[] = $cloudCamera['camera_id'];
                } catch (\Exception $e) {
                    Log::error("Error syncing camera {$cloudCamera['camera_id']} from cloud: " . $e->getMessage());
                }
            }
            
            $newCount = count(array_filter($camerasToSync, function($c) use ($existingLocalRecords) { return !isset($existingLocalRecords[$c['camera_id']]); }));
            $updatedCount = count($camerasToSync) - $newCount;
            Log::info("Synced " . count($synced) . " cameras from cloud to local (" . $newCount . " new, " . $updatedCount . " updated)");
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
            // STEP 1: Process deletions for this table before syncing data
            $this->processTableDeletionsFromCloud('faculties', 'tbl_faculty', 'faculty_id');
            
            // Get existing local records with their data for comparison
            $existingLocalRecords = $this->getExistingLocalRecords('tbl_faculty', 'faculty_id');
            
            // Get all faculties from cloud
            $cloudFaculties = $this->fetchBulkFromCloud('faculties');
            
            if (empty($cloudFaculties)) {
                return $synced;
            }
            
            // Filter: only sync new records or records that have changed
            $facultiesToSync = array_filter($cloudFaculties, function ($cloudFaculty) use ($existingLocalRecords) {
                $facultyId = $cloudFaculty['faculty_id'] ?? null;
                if (!$facultyId) return false;
                
                // Skip if this record was deleted locally (prevent restoring deleted records)
                if ($this->isDeletedLocally('tbl_faculty', $facultyId)) {
                    Log::debug("Skipping faculty {$facultyId} - was deleted locally");
                    return false;
                }
                
                // If not in local, needs to be synced (new record)
                if (!isset($existingLocalRecords[$facultyId])) {
                    return true;
                }
                
                // STRICT RULE: Compare updated_at timestamps - only sync if cloud is newer or equal
                if (!$this->shouldSyncCloudToLocal($existingLocalRecords[$facultyId], $cloudFaculty, 'faculty', $facultyId)) {
                    return false;
                }
                
                // If in local, compare data to see if it changed (ignore images path differences)
                $localData = [
                    'faculty_id' => $existingLocalRecords[$facultyId]['faculty_id'],
                    'faculty_fname' => $existingLocalRecords[$facultyId]['faculty_fname'] ?? null,
                    'faculty_lname' => $existingLocalRecords[$facultyId]['faculty_lname'] ?? null,
                    'faculty_department' => $existingLocalRecords[$facultyId]['faculty_department'] ?? null,
                    'faculty_face_embedding' => $existingLocalRecords[$facultyId]['faculty_face_embedding'] ?? null,
                ];
                
                return $this->recordsAreDifferent($localData, $cloudFaculty);
            });
            
            if (empty($facultiesToSync)) {
                Log::info('No new or changed faculties to sync from cloud to local');
                return $synced;
            }
            
            // Upsert only changed/new faculties
            foreach ($facultiesToSync as $cloudFaculty) {
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
                            'created_at' => $this->convertCloudTimestampToLocalTimezone($cloudFaculty['created_at'] ?? null),
                            'updated_at' => $this->convertCloudTimestampToLocalTimezone($cloudFaculty['updated_at'] ?? null),
                        ]
                    ], ['faculty_id'], ['faculty_fname', 'faculty_lname', 'faculty_department', 'faculty_images', 'faculty_face_embedding', 'created_at', 'updated_at']);
                    
                    $synced[] = $cloudFaculty['faculty_id'];
                } catch (\Exception $e) {
                    Log::error("Error syncing faculty {$cloudFaculty['faculty_id']} from cloud: " . $e->getMessage());
                }
            }
            
            $newCount = count(array_filter($facultiesToSync, function($f) use ($existingLocalRecords) { return !isset($existingLocalRecords[$f['faculty_id']]); }));
            $updatedCount = count($facultiesToSync) - $newCount;
            Log::info("Synced " . count($synced) . " faculties from cloud to local (" . $newCount . " new, " . $updatedCount . " updated)");
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
            // STEP 1: Process deletions for this table before syncing data
            $this->processTableDeletionsFromCloud('teaching-loads', 'tbl_teaching_load', 'teaching_load_id');
            
            // Get existing local records with their data for comparison
            $existingLocalRecords = $this->getExistingLocalRecords('tbl_teaching_load', 'teaching_load_id');
            
            // Get all teaching loads from cloud
            $cloudLoads = $this->fetchBulkFromCloud('teaching-loads');
            
            if (empty($cloudLoads)) {
                return $synced;
            }
            
            // Filter: only sync new records or records that have changed
            $loadsToSync = array_filter($cloudLoads, function ($cloudLoad) use ($existingLocalRecords) {
                $loadId = $cloudLoad['teaching_load_id'] ?? null;
                if (!$loadId) return false;
                
                // Skip if this record was deleted locally (prevent restoring deleted records)
                if ($this->isDeletedLocally('tbl_teaching_load', $loadId)) {
                    Log::debug("Skipping teaching load {$loadId} - was deleted locally");
                    return false;
                }
                
                // If not in local, needs to be synced (new record)
                if (!isset($existingLocalRecords[$loadId])) {
                    return true;
                }
                
                // STRICT RULE: Compare updated_at timestamps - only sync if cloud is newer or equal
                if (!$this->shouldSyncCloudToLocal($existingLocalRecords[$loadId], $cloudLoad, 'teaching_load', $loadId)) {
                    return false;
                }
                
                // If in local, compare data to see if it changed
                $localData = [
                    'teaching_load_id' => $existingLocalRecords[$loadId]['teaching_load_id'],
                    'faculty_id' => $existingLocalRecords[$loadId]['faculty_id'] ?? null,
                    'teaching_load_course_code' => $existingLocalRecords[$loadId]['teaching_load_course_code'] ?? null,
                    'teaching_load_subject' => $existingLocalRecords[$loadId]['teaching_load_subject'] ?? null,
                    'teaching_load_day_of_week' => $existingLocalRecords[$loadId]['teaching_load_day_of_week'] ?? null,
                    'teaching_load_class_section' => $existingLocalRecords[$loadId]['teaching_load_class_section'] ?? null,
                    'teaching_load_time_in' => $existingLocalRecords[$loadId]['teaching_load_time_in'] ?? null,
                    'teaching_load_time_out' => $existingLocalRecords[$loadId]['teaching_load_time_out'] ?? null,
                    'room_no' => $existingLocalRecords[$loadId]['room_no'] ?? null,
                ];
                
                return $this->recordsAreDifferent($localData, $cloudLoad);
            });
            
            if (empty($loadsToSync)) {
                Log::info('No new or changed teaching loads to sync from cloud to local');
                return $synced;
            }
            
            // Upsert only changed/new teaching loads
            foreach ($loadsToSync as $cloudLoad) {
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
                            'created_at' => $this->convertCloudTimestampToLocalTimezone($cloudLoad['created_at'] ?? null),
                            'updated_at' => $this->convertCloudTimestampToLocalTimezone($cloudLoad['updated_at'] ?? null),
                        ]
                    ], ['teaching_load_id'], ['faculty_id', 'teaching_load_course_code', 'teaching_load_subject', 'teaching_load_day_of_week', 'teaching_load_class_section', 'teaching_load_time_in', 'teaching_load_time_out', 'room_no', 'created_at', 'updated_at']);
                    
                    $synced[] = $cloudLoad['teaching_load_id'];
                } catch (\Exception $e) {
                    Log::error("Error syncing teaching load {$cloudLoad['teaching_load_id']} from cloud: " . $e->getMessage());
                }
            }
            
            $newCount = count(array_filter($loadsToSync, function($l) use ($existingLocalRecords) { return !isset($existingLocalRecords[$l['teaching_load_id']]); }));
            $updatedCount = count($loadsToSync) - $newCount;
            Log::info("Synced " . count($synced) . " teaching loads from cloud to local (" . $newCount . " new, " . $updatedCount . " updated)");
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
            // STEP 1: Process deletions for this table before syncing data
            $this->processTableDeletionsFromCloud('attendance-records', 'tbl_attendance_record', 'record_id');
            
            // Get existing local records with their data for comparison (last 30 days for performance)
            $existingLocalRecords = $this->getExistingLocalRecords('tbl_attendance_record', 'record_id');
            // Filter to only last 30 days for comparison
            $thirtyDaysAgo = now()->subDays(30);
            $existingLocalRecords = array_filter($existingLocalRecords, function($record) use ($thirtyDaysAgo) {
                $recordDate = $record['record_date'] ?? null;
                if (!$recordDate) return false;
                try {
                    return \Carbon\Carbon::parse($recordDate)->gte($thirtyDaysAgo);
                } catch (\Exception $e) {
                    return false;
                }
            });
            
            // Get all attendance records from cloud
            $cloudRecords = $this->fetchBulkFromCloud('attendance-records', ['days' => 30]);
            
            if (empty($cloudRecords)) {
                return $synced;
            }
            
            // Filter: only sync new records or records that have changed
            $recordsToSync = array_filter($cloudRecords, function ($cloudRecord) use ($existingLocalRecords) {
                $recordId = $cloudRecord['record_id'] ?? null;
                if (!$recordId) return false;
                
                // Skip if this record was deleted locally (prevent restoring deleted records)
                if ($this->isDeletedLocally('tbl_attendance_record', $recordId)) {
                    Log::debug("Skipping attendance record {$recordId} - was deleted locally");
                    return false;
                }
                
                // If not in local, needs to be synced (new record) - but first check if it's orphaned
                if (!isset($existingLocalRecords[$recordId])) {
                    // CRITICAL: Skip attendance records if they are related to deleted leaves/passes/official matters
                    // This prevents restoring attendance records when their parent entity was deleted locally
                    $remarks = $cloudRecord['record_remarks'] ?? null;
                    $recordDate = $cloudRecord['record_date'] ?? null;
                    $facultyId = $cloudRecord['faculty_id'] ?? null;
                    
                    if ($remarks && $recordDate && $facultyId) {
                        // Check for leave-related records
                        if ($remarks === 'On Leave' || stripos($remarks, 'leave') !== false) {
                            // Check if any leave exists locally for this faculty and date range
                            $hasLocalLeave = DB::table('tbl_leave_pass')
                            ->where('faculty_id', $facultyId)
                            ->where('lp_type', 'Leave')
                                ->where('leave_start_date', '<=', $recordDate)
                                ->where('leave_end_date', '>=', $recordDate)
                                ->exists();
                            
                            if (!$hasLocalLeave) {
                                // No leave exists locally - this attendance record is orphaned
                                // It's likely related to a deleted leave, so skip it
                                Log::info("Skipping attendance record {$recordId} - related leave does not exist locally (likely deleted)");
                                return false;
                            }
                        }
                        
                        // Check for pass-related records
                        if ($remarks === 'With Pass Slip' || stripos($remarks, 'pass') !== false) {
                            // Check if any pass exists locally for this faculty and date
                            $hasLocalPass = DB::table('tbl_leave_pass')
                                ->where('faculty_id', $facultyId)
                                ->where('lp_type', 'Pass')
                                ->where('pass_slip_date', $recordDate)
                                ->exists();
                            
                            if (!$hasLocalPass) {
                                // No pass exists locally - this attendance record is orphaned
                                // It's likely related to a deleted pass, so skip it
                                Log::info("Skipping attendance record {$recordId} - related pass does not exist locally (likely deleted)");
                                return false;
                            }
                        }
                        
                        // Check for official matter-related records
                        // Official matter remarks are stored in record_remarks, so we match by exact remarks
                        if (stripos($remarks, 'official') !== false || 
                            (stripos($remarks, 'om') !== false && strlen($remarks) > 10)) {
                            // Check if any official matter exists locally for this faculty/date with matching remarks
                            $hasLocalOM = DB::table('tbl_official_matters')
                                ->where(function($q) use ($facultyId) {
                                    $q->where('faculty_id', $facultyId)
                                      ->orWhere('om_department', 'All Instructor');
                                })
                                ->where('om_start_date', '<=', $recordDate)
                                ->where('om_end_date', '>=', $recordDate)
                                ->where('om_remarks', $remarks)
                                ->exists();
                            
                            if (!$hasLocalOM) {
                                // No official matter exists locally - this attendance record is orphaned
                                // It's likely related to a deleted official matter, so skip it
                                Log::info("Skipping attendance record {$recordId} - related official matter does not exist locally (likely deleted)");
                                return false;
                            }
                        }
                    }
                    
                    // If we get here, it's a new record and not orphaned, so sync it
                    return true;
                }
                
                // STRICT RULE: Compare updated_at timestamps - only sync if cloud is newer or equal
                if (!$this->shouldSyncCloudToLocal($existingLocalRecords[$recordId], $cloudRecord, 'attendance_record', $recordId)) {
                    return false;
                }
                
                // If in local, compare data to see if it changed (ignore snapshot paths)
                $localData = [
                    'record_id' => $existingLocalRecords[$recordId]['record_id'],
                    'record_date' => $this->formatDateTime($existingLocalRecords[$recordId]['record_date'] ?? null),
                    'faculty_id' => $existingLocalRecords[$recordId]['faculty_id'] ?? null,
                    'teaching_load_id' => $existingLocalRecords[$recordId]['teaching_load_id'] ?? null,
                    'record_time_in' => $existingLocalRecords[$recordId]['record_time_in'] ?? null,
                    'record_time_out' => $existingLocalRecords[$recordId]['record_time_out'] ?? null,
                    'time_duration_seconds' => $existingLocalRecords[$recordId]['time_duration_seconds'] ?? null,
                    'record_status' => $existingLocalRecords[$recordId]['record_status'] ?? null,
                    'record_remarks' => $existingLocalRecords[$recordId]['record_remarks'] ?? null,
                    'camera_id' => $existingLocalRecords[$recordId]['camera_id'] ?? null,
                ];
                
                return $this->recordsAreDifferent($localData, $cloudRecord);
            });
            
            if (empty($recordsToSync)) {
                Log::info('No new or changed attendance records to sync from cloud to local');
                return $synced;
            }
            
            // Upsert only changed/new attendance records
            foreach ($recordsToSync as $cloudRecord) {
                try {
                    // Download snapshot images from cloud
                    $localTimeInSnapshot = $this->downloadAttendanceSnapshot($cloudRecord['time_in_snapshot'] ?? null);
                    $localTimeOutSnapshot = $this->downloadAttendanceSnapshot($cloudRecord['time_out_snapshot'] ?? null);
                    
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
                            'time_in_snapshot' => $localTimeInSnapshot,
                            'time_out_snapshot' => $localTimeOutSnapshot,
                            'created_at' => $this->convertCloudTimestampToLocalTimezone($cloudRecord['created_at'] ?? null),
                            'updated_at' => $this->convertCloudTimestampToLocalTimezone($cloudRecord['updated_at'] ?? null),
                        ]
                    ], ['record_id'], ['record_date', 'faculty_id', 'teaching_load_id', 'record_time_in', 'record_time_out', 'time_duration_seconds', 'record_status', 'record_remarks', 'camera_id', 'time_in_snapshot', 'time_out_snapshot', 'created_at', 'updated_at']);
                    
                    $synced[] = $cloudRecord['record_id'];
                } catch (\Exception $e) {
                    Log::error("Error syncing attendance record {$cloudRecord['record_id']} from cloud: " . $e->getMessage());
                }
            }
            
            $newCount = count(array_filter($recordsToSync, function($r) use ($existingLocalRecords) { return !isset($existingLocalRecords[$r['record_id']]); }));
            $updatedCount = count($recordsToSync) - $newCount;
            Log::info("Synced " . count($synced) . " attendance records from cloud to local (" . $newCount . " new, " . $updatedCount . " updated)");
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
            // STEP 1: Process deletions for this table before syncing data
            $this->processTableDeletionsFromCloud('leaves', 'tbl_leave_pass', 'lp_id');
            
            // Get existing local records with their data for comparison (last 90 days for performance)
            $existingLocalRecords = $this->getExistingLocalRecords('tbl_leave_pass', 'lp_id');
            // Filter to only leaves and last 90 days
            $ninetyDaysAgo = now()->subDays(90);
            $existingLocalRecords = array_filter($existingLocalRecords, function($record) use ($ninetyDaysAgo) {
                if (($record['lp_type'] ?? null) !== 'Leave') return false;
                $createdAt = $record['created_at'] ?? null;
                if (!$createdAt) return false;
                try {
                    return \Carbon\Carbon::parse($createdAt)->gte($ninetyDaysAgo);
                } catch (\Exception $e) {
                    return false;
                }
            });
            
            // Get all leaves from cloud
            $cloudLeaves = $this->fetchBulkFromCloud('leaves', ['days' => 90]);
            
            Log::info("Fetched " . count($cloudLeaves) . " leaves from cloud");
            
            if (empty($cloudLeaves)) {
                Log::info("No leaves found in cloud");
                return $synced;
            }
            
            // Filter: only sync new records or records that have changed
            $leavesToSync = array_filter($cloudLeaves, function ($cloudLeave) use ($existingLocalRecords) {
                $lpId = $cloudLeave['lp_id'] ?? null;
                if (!$lpId) return false;
                
                // Skip if this record was deleted locally (prevent restoring deleted records)
                if ($this->isDeletedLocally('tbl_leave_pass', $lpId)) {
                    Log::debug("Skipping leave {$lpId} - was deleted locally");
                    return false;
                }
                
                // If not in local, needs to be synced (new record)
                if (!isset($existingLocalRecords[$lpId])) {
                    return true;
                }
                
                // STRICT RULE: Compare updated_at timestamps - only sync if cloud is newer or equal
                if (!$this->shouldSyncCloudToLocal($existingLocalRecords[$lpId], $cloudLeave, 'leave', $lpId)) {
                    return false;
                }
                
                // If in local, compare data to see if it changed (ignore image path differences)
                $localData = [
                    'lp_id' => $existingLocalRecords[$lpId]['lp_id'],
                    'faculty_id' => $existingLocalRecords[$lpId]['faculty_id'] ?? null,
                    'lp_type' => $existingLocalRecords[$lpId]['lp_type'] ?? null,
                    'lp_purpose' => $existingLocalRecords[$lpId]['lp_purpose'] ?? null,
                    'leave_start_date' => $existingLocalRecords[$lpId]['leave_start_date'] ?? null,
                    'leave_end_date' => $existingLocalRecords[$lpId]['leave_end_date'] ?? null,
                ];
                
                return $this->recordsAreDifferent($localData, $cloudLeave);
            });
            
            // Store old records for attendance reconciliation
            $oldRecordsMap = [];
            foreach ($leavesToSync as $cloudLeave) {
                $lpId = $cloudLeave['lp_id'] ?? null;
                if ($lpId && isset($existingLocalRecords[$lpId])) {
                    $oldRecordsMap[$lpId] = $existingLocalRecords[$lpId];
                }
            }
            
            if (empty($leavesToSync)) {
                Log::info('No new or changed leaves to sync from cloud to local');
                return $synced;
            }
            
            // Upsert only changed/new leaves
            foreach ($leavesToSync as $cloudLeave) {
                try {
                    // Download leave slip image from cloud
                    $localImagePath = $this->downloadLeaveImage($cloudLeave['lp_image'] ?? null);
                    
                    // Ensure lp_image is not null (database requires it)
                    if (empty($localImagePath)) {
                        $localImagePath = $cloudLeave['lp_image'] ?? '';
                    }
                    if (empty($localImagePath)) {
                        $localImagePath = 'placeholder.jpg'; // Ensure we have a non-empty string
                    }
                    
                    // Validate required fields
                    if (empty($cloudLeave['lp_id'])) {
                        Log::warning("Skipping leave record without lp_id");
                        continue;
                    }
                    
                    if (empty($cloudLeave['lp_purpose'])) {
                        Log::warning("Leave {$cloudLeave['lp_id']} has empty lp_purpose, using default");
                        $cloudLeave['lp_purpose'] = 'N/A';
                    }
                    
                    // Format dates properly (date fields, not datetime) - matching local-to-cloud sync
                    $leaveStartDate = null;
                    if (!empty($cloudLeave['leave_start_date'])) {
                        try {
                            $leaveStartDate = \Carbon\Carbon::parse($cloudLeave['leave_start_date'])->format('Y-m-d');
                        } catch (\Exception $e) {
                            Log::warning("Invalid leave_start_date for leave {$cloudLeave['lp_id']}: {$cloudLeave['leave_start_date']}");
                        }
                    }
                    
                    $leaveEndDate = null;
                    if (!empty($cloudLeave['leave_end_date'])) {
                        try {
                            $leaveEndDate = \Carbon\Carbon::parse($cloudLeave['leave_end_date'])->format('Y-m-d');
                        } catch (\Exception $e) {
                            Log::warning("Invalid leave_end_date for leave {$cloudLeave['lp_id']}: {$cloudLeave['leave_end_date']}");
                        }
                    }
                    
                    // Match local-to-cloud sync structure - only update leave-specific fields
                    DB::table('tbl_leave_pass')->upsert([
                        [
                            'lp_id' => $cloudLeave['lp_id'],
                            'faculty_id' => $cloudLeave['faculty_id'] ?? null,
                            'lp_type' => 'Leave', // Force Leave type
                            'lp_purpose' => $cloudLeave['lp_purpose'] ?? 'N/A',
                            'pass_slip_itinerary' => null, // Leaves don't use this
                            'pass_slip_date' => null, // Leaves don't use this
                            'pass_slip_departure_time' => null, // Leaves don't use this
                            'pass_slip_arrival_time' => null, // Leaves don't use this
                            'leave_start_date' => $leaveStartDate,
                            'leave_end_date' => $leaveEndDate,
                            'lp_image' => $localImagePath,
                            'created_at' => $this->convertCloudTimestampToLocalTimezone($cloudLeave['created_at'] ?? null),
                            'updated_at' => $this->convertCloudTimestampToLocalTimezone($cloudLeave['updated_at'] ?? null),
                        ]
                    ], ['lp_id'], ['faculty_id', 'lp_type', 'lp_purpose', 'pass_slip_itinerary', 'pass_slip_date', 'pass_slip_departure_time', 'pass_slip_arrival_time', 'leave_start_date', 'leave_end_date', 'lp_image', 'created_at', 'updated_at']);
                    
                    // Check if date range changed and update attendance records if needed
                    $isUpdate = isset($oldRecordsMap[$cloudLeave['lp_id']]);
                    $oldRecord = $isUpdate ? $oldRecordsMap[$cloudLeave['lp_id']] : null;
                    
                    if ($isUpdate && $oldRecord && !empty($cloudLeave['faculty_id'])) {
                        $oldStartDate = $oldRecord['leave_start_date'] ?? null;
                        $oldEndDate = $oldRecord['leave_end_date'] ?? null;
                        $newStartDate = $leaveStartDate;
                        $newEndDate = $leaveEndDate;
                        
                        $dateRangeChanged = ($oldStartDate !== $newStartDate) || ($oldEndDate !== $newEndDate);
                        
                        if ($dateRangeChanged) {
                            try {
                                $remarksService = new AttendanceRemarksService();
                                
                                // Intelligently reconcile leave change - preserve existing IDs
                                if ($newStartDate && $newEndDate) {
                                    $remarksService->reconcileLeaveChange(
                                        $cloudLeave['faculty_id'], 
                                        $newStartDate, 
                                        $newEndDate,
                                        $oldStartDate,
                                        $oldEndDate
                                    );
                                }
                                
                                Log::info("Updated attendance records for leave {$cloudLeave['lp_id']} due to date range change");
                            } catch (\Exception $e) {
                                Log::error("Error updating attendance records for leave {$cloudLeave['lp_id']}: " . $e->getMessage());
                            }
                        }
                    } elseif (!$isUpdate && !empty($cloudLeave['faculty_id']) && $leaveStartDate && $leaveEndDate) {
                        // New leave record - create attendance records
                        try {
                            $remarksService = new AttendanceRemarksService();
                            $remarksService->reconcileLeaveChange($cloudLeave['faculty_id'], $leaveStartDate, $leaveEndDate);
                            Log::info("Created attendance records for new leave {$cloudLeave['lp_id']}");
                        } catch (\Exception $e) {
                            Log::error("Error creating attendance records for new leave {$cloudLeave['lp_id']}: " . $e->getMessage());
                        }
                    }
                    
                    $synced[] = $cloudLeave['lp_id'];
                    Log::info("Successfully synced leave {$cloudLeave['lp_id']} from cloud");
                } catch (\Exception $e) {
                    Log::error("Error syncing leave {$cloudLeave['lp_id']} from cloud: " . $e->getMessage());
                    Log::error("Leave data: " . json_encode($cloudLeave));
                }
            }
            
            $newCount = count(array_filter($leavesToSync, function($l) use ($existingLocalRecords) { return !isset($existingLocalRecords[$l['lp_id']]); }));
            $updatedCount = count($leavesToSync) - $newCount;
            Log::info("Synced " . count($synced) . " leaves from cloud to local (" . $newCount . " new, " . $updatedCount . " updated)");
        } catch (\Exception $e) {
            Log::error("Error syncing leaves from cloud: " . $e->getMessage());
            Log::error("Stack trace: " . $e->getTraceAsString());
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
            // STEP 1: Process deletions for this table before syncing data
            $this->processTableDeletionsFromCloud('passes', 'tbl_leave_pass', 'lp_id');
            
            // Get existing local records with their data for comparison (last 90 days for performance)
            $existingLocalRecords = $this->getExistingLocalRecords('tbl_leave_pass', 'lp_id');
            // Filter to only passes and last 90 days
            $ninetyDaysAgo = now()->subDays(90);
            $existingLocalRecords = array_filter($existingLocalRecords, function($record) use ($ninetyDaysAgo) {
                if (($record['lp_type'] ?? null) !== 'Pass') return false;
                $createdAt = $record['created_at'] ?? null;
                if (!$createdAt) return false;
                try {
                    return \Carbon\Carbon::parse($createdAt)->gte($ninetyDaysAgo);
                } catch (\Exception $e) {
                    return false;
                }
            });
            
            // Get all passes from cloud
            $cloudPasses = $this->fetchBulkFromCloud('passes', ['days' => 90]);
            
            Log::info("Fetched " . count($cloudPasses) . " passes from cloud");
            
            if (empty($cloudPasses)) {
                Log::info("No passes found in cloud");
                return $synced;
            }
            
            // Filter: only sync new records or records that have changed
            $passesToSync = array_filter($cloudPasses, function ($cloudPass) use ($existingLocalRecords) {
                $lpId = $cloudPass['lp_id'] ?? null;
                if (!$lpId) return false;
                
                // Skip if this record was deleted locally (prevent restoring deleted records)
                if ($this->isDeletedLocally('tbl_leave_pass', $lpId)) {
                    Log::debug("Skipping pass {$lpId} - was deleted locally");
                    return false;
                }
                
                // If not in local, needs to be synced (new record)
                if (!isset($existingLocalRecords[$lpId])) {
                    return true;
                }
                
                // STRICT RULE: Compare updated_at timestamps - only sync if cloud is newer or equal
                if (!$this->shouldSyncCloudToLocal($existingLocalRecords[$lpId], $cloudPass, 'pass', $lpId)) {
                    return false;
                }
                
                // If in local, compare data to see if it changed (ignore image path differences)
                $localData = [
                    'lp_id' => $existingLocalRecords[$lpId]['lp_id'],
                    'faculty_id' => $existingLocalRecords[$lpId]['faculty_id'] ?? null,
                    'lp_type' => $existingLocalRecords[$lpId]['lp_type'] ?? null,
                    'lp_purpose' => $existingLocalRecords[$lpId]['lp_purpose'] ?? null,
                    'pass_slip_itinerary' => $existingLocalRecords[$lpId]['pass_slip_itinerary'] ?? null,
                    'pass_slip_date' => $existingLocalRecords[$lpId]['pass_slip_date'] ?? null,
                    'pass_slip_departure_time' => $existingLocalRecords[$lpId]['pass_slip_departure_time'] ?? null,
                    'pass_slip_arrival_time' => $existingLocalRecords[$lpId]['pass_slip_arrival_time'] ?? null,
                ];
                
                return $this->recordsAreDifferent($localData, $cloudPass);
            });
            
            // Store old records for attendance reconciliation
            $oldRecordsMap = [];
            foreach ($passesToSync as $cloudPass) {
                $lpId = $cloudPass['lp_id'] ?? null;
                if ($lpId && isset($existingLocalRecords[$lpId])) {
                    $oldRecordsMap[$lpId] = $existingLocalRecords[$lpId];
                }
            }
            
            if (empty($passesToSync)) {
                Log::info('No new or changed passes to sync from cloud to local');
                return $synced;
            }
            
            // Upsert only changed/new passes
            foreach ($passesToSync as $cloudPass) {
                try {
                    // Download pass slip image from cloud
                    $localImagePath = $this->downloadPassImage($cloudPass['lp_image'] ?? null);
                    
                    // Ensure lp_image is not null (database requires it)
                    if (empty($localImagePath)) {
                        $localImagePath = $cloudPass['lp_image'] ?? '';
                    }
                    if (empty($localImagePath)) {
                        $localImagePath = 'placeholder.jpg'; // Ensure we have a non-empty string
                    }
                    
                    // Validate required fields
                    if (empty($cloudPass['lp_id'])) {
                        Log::warning("Skipping pass record without lp_id");
                        continue;
                    }
                    
                    if (empty($cloudPass['lp_purpose'])) {
                        Log::warning("Pass {$cloudPass['lp_id']} has empty lp_purpose, using default");
                        $cloudPass['lp_purpose'] = 'N/A';
                    }
                    
                    // Format dates properly (date fields, not datetime) - matching local-to-cloud sync
                    $passSlipDate = null;
                    if (!empty($cloudPass['pass_slip_date'])) {
                        try {
                            $passSlipDate = \Carbon\Carbon::parse($cloudPass['pass_slip_date'])->format('Y-m-d');
                        } catch (\Exception $e) {
                            Log::warning("Invalid pass_slip_date for pass {$cloudPass['lp_id']}: {$cloudPass['pass_slip_date']}");
                        }
                    }
                    
                    // Match local-to-cloud sync structure - only update pass-specific fields
                    // Passes don't use leave_start_date or leave_end_date, so don't update them
                    DB::table('tbl_leave_pass')->upsert([
                        [
                            'lp_id' => $cloudPass['lp_id'],
                            'faculty_id' => $cloudPass['faculty_id'] ?? null,
                            'lp_type' => 'Pass', // Force Pass type
                            'lp_purpose' => $cloudPass['lp_purpose'] ?? 'N/A',
                            'pass_slip_itinerary' => $cloudPass['pass_slip_itinerary'] ?? null,
                            'pass_slip_date' => $passSlipDate,
                            'pass_slip_departure_time' => $cloudPass['pass_slip_departure_time'] ?? null,
                            'pass_slip_arrival_time' => $cloudPass['pass_slip_arrival_time'] ?? null,
                            'lp_image' => $localImagePath,
                            'created_at' => $this->convertCloudTimestampToLocalTimezone($cloudPass['created_at'] ?? null),
                            'updated_at' => $this->convertCloudTimestampToLocalTimezone($cloudPass['updated_at'] ?? null),
                        ]
                    ], ['lp_id'], ['faculty_id', 'lp_type', 'lp_purpose', 'pass_slip_itinerary', 'pass_slip_date', 'pass_slip_departure_time', 'pass_slip_arrival_time', 'lp_image', 'created_at', 'updated_at']);
                    
                    // Check if date changed and update attendance records if needed
                    $isUpdate = isset($oldRecordsMap[$cloudPass['lp_id']]);
                    $oldRecord = $isUpdate ? $oldRecordsMap[$cloudPass['lp_id']] : null;
                    
                    if ($isUpdate && $oldRecord && !empty($cloudPass['faculty_id'])) {
                        $oldDate = $oldRecord['pass_slip_date'] ?? null;
                        $newDate = $passSlipDate;
                        
                        $dateChanged = ($oldDate !== $newDate);
                        
                        if ($dateChanged || $newDate) {
                            try {
                                $remarksService = new AttendanceRemarksService();
                                
                                // Intelligently reconcile pass change - preserve existing IDs
                                // Pass old date only if date changed, otherwise null to preserve IDs
                                $remarksService->reconcilePassChange(
                                    $cloudPass['faculty_id'], 
                                    $newDate,
                                    $dateChanged ? $oldDate : null
                                );
                                
                                if ($dateChanged) {
                                    Log::info("Updated attendance records for pass {$cloudPass['lp_id']} due to date change");
                                } else {
                                    Log::debug("Reconciled attendance records for pass {$cloudPass['lp_id']} (time or other field may have changed)");
                                }
                            } catch (\Exception $e) {
                                Log::error("Error updating attendance records for pass {$cloudPass['lp_id']}: " . $e->getMessage());
                            }
                        }
                    } elseif (!$isUpdate && !empty($cloudPass['faculty_id']) && $passSlipDate) {
                        // New pass record - create attendance records
                        try {
                            $remarksService = new AttendanceRemarksService();
                            $remarksService->reconcilePassChange($cloudPass['faculty_id'], $passSlipDate);
                            Log::info("Created attendance records for new pass {$cloudPass['lp_id']}");
                        } catch (\Exception $e) {
                            Log::error("Error creating attendance records for new pass {$cloudPass['lp_id']}: " . $e->getMessage());
                        }
                    }
                    
                    $synced[] = $cloudPass['lp_id'];
                    Log::info("Successfully synced pass {$cloudPass['lp_id']} from cloud");
                } catch (\Exception $e) {
                    Log::error("Error syncing pass {$cloudPass['lp_id']} from cloud: " . $e->getMessage());
                    Log::error("Pass data: " . json_encode($cloudPass));
                }
            }
            
            Log::info("Synced " . count($synced) . " passes from cloud to local (upserted)");
        } catch (\Exception $e) {
            Log::error("Error syncing passes from cloud: " . $e->getMessage());
            Log::error("Stack trace: " . $e->getTraceAsString());
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
            // STEP 1: Process deletions for this table before syncing data
            $this->processTableDeletionsFromCloud('recognition-logs', 'tbl_recognition_logs', 'log_id');
            
            // Get existing recognition log IDs from local database
            $existingLocalIds = $this->getExistingLocalIds('tbl_recognition_logs', 'log_id');
            
            $cloudLogs = $this->fetchBulkFromCloud('recognition-logs', ['days' => 7]);
            
            if (empty($cloudLogs)) {
                return $synced;
            }
            
            // Filter out logs that already exist locally or were deleted locally (append-only, no updates needed)
            $cloudLogs = array_values(array_filter($cloudLogs, function ($log) use ($existingLocalIds) {
                $logId = $log['log_id'] ?? null;
                if (!$logId) return false;
                
                // Skip if this record was deleted locally (prevent restoring deleted records)
                if ($this->isDeletedLocally('tbl_recognition_logs', $logId)) {
                    Log::debug("Skipping recognition log {$logId} - was deleted locally");
                    return false;
                }
                
                return !in_array($logId, $existingLocalIds);
            }));
            
            if (empty($cloudLogs)) {
                Log::info('No new recognition logs to sync from cloud to local');
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
                            'created_at' => $this->convertCloudTimestampToLocalTimezone($cloudLog['created_at'] ?? null),
                            'updated_at' => $this->convertCloudTimestampToLocalTimezone($cloudLog['updated_at'] ?? null),
                        ]
                    ], ['log_id'], ['faculty_id', 'camera_id', 'teaching_load_id', 'recognition_time', 'camera_name', 'room_name', 'building_no', 'faculty_name', 'status', 'distance', 'created_at', 'updated_at']);
                    
                    $synced[] = $cloudLog['log_id'];
                } catch (\Exception $e) {
                    Log::error("Error syncing recognition log {$cloudLog['log_id']} from cloud: " . $e->getMessage());
                }
            }
            
            Log::info("Synced " . count($synced) . " new recognition logs from cloud to local");
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
            // STEP 1: Process deletions for this table before syncing data
            $this->processTableDeletionsFromCloud('stream-recordings', 'tbl_stream_recordings', 'recording_id');
            
            // Get existing stream recording IDs from local database
            $existingLocalIds = $this->getExistingLocalIds('tbl_stream_recordings', 'recording_id');
            
            $cloudRecordings = $this->fetchBulkFromCloud('stream-recordings', ['days' => 7]);
            
            if (empty($cloudRecordings)) {
                return $synced;
            }
            
            // Filter out stream recordings that already exist locally or were deleted locally (append-only, no updates needed)
            $newRecordings = array_filter($cloudRecordings, function ($cloudRecording) use ($existingLocalIds) {
                $recordingId = $cloudRecording['recording_id'] ?? null;
                if (!$recordingId) return false;
                
                // Skip if this record was deleted locally (prevent restoring deleted records)
                if ($this->isDeletedLocally('tbl_stream_recordings', $recordingId)) {
                    Log::debug("Skipping stream recording {$recordingId} - was deleted locally");
                    return false;
                }
                
                return !in_array($recordingId, $existingLocalIds);
            });
            
            if (empty($newRecordings)) {
                Log::info('No new stream recordings to sync from cloud to local');
                return $synced;
            }
            
            foreach ($newRecordings as $cloudRecording) {
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
                            'created_at' => $this->convertCloudTimestampToLocalTimezone($cloudRecording['created_at'] ?? null),
                            'updated_at' => $this->convertCloudTimestampToLocalTimezone($cloudRecording['updated_at'] ?? null),
                        ]
                    ], ['recording_id'], ['camera_id', 'start_time', 'duration', 'frames', 'filepath', 'filename', 'file_size', 'created_at', 'updated_at']);
                    
                    $synced[] = $cloudRecording['recording_id'];
                } catch (\Exception $e) {
                    Log::error("Error syncing stream recording {$cloudRecording['recording_id']} from cloud: " . $e->getMessage());
                }
            }
            
            Log::info("Synced " . count($synced) . " new stream recordings from cloud to local");
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
            // STEP 1: Process deletions for this table before syncing data
            $this->processTableDeletionsFromCloud('activity-logs', 'tbl_activity_logs', 'logs_id');
            
            // Get existing activity log IDs from local database
            $existingLocalIds = $this->getExistingLocalIds('tbl_activity_logs', 'logs_id');
            
            $cloudLogs = $this->fetchBulkFromCloud('activity-logs');
            
            if (empty($cloudLogs)) {
                return $synced;
            }
            
            // Filter out logs that already exist locally or were deleted locally (append-only, no updates needed)
            $cloudLogs = array_values(array_filter($cloudLogs, function ($log) use ($existingLocalIds) {
                $logId = $log['logs_id'] ?? null;
                if (!$logId) return false;
                
                // Skip if this record was deleted locally (prevent restoring deleted records)
                if ($this->isDeletedLocally('tbl_activity_logs', $logId)) {
                    Log::debug("Skipping activity log {$logId} - was deleted locally");
                    return false;
                }
                
                return !in_array($logId, $existingLocalIds);
            }));
            
            if (empty($cloudLogs)) {
                Log::info('No new activity logs to sync from cloud to local');
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
                            'created_at' => $this->convertCloudTimestampToLocalTimezone($cloudLog['created_at'] ?? null),
                            'updated_at' => $this->convertCloudTimestampToLocalTimezone($cloudLog['updated_at'] ?? null),
                        ]
                    ], ['logs_id'], ['user_id', 'logs_action', 'logs_description', 'logs_timestamp', 'logs_module', 'created_at', 'updated_at']);
                    
                    $synced[] = $cloudLog['logs_id'];
                } catch (\Exception $e) {
                    Log::error("Error syncing activity log {$cloudLog['logs_id']} from cloud: " . $e->getMessage());
                }
            }
            
            Log::info("Synced " . count($synced) . " new activity logs from cloud to local");
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
            // STEP 1: Process deletions for this table before syncing data
            $this->processTableDeletionsFromCloud('teaching-load-archives', 'tbl_teaching_load_archive', 'archive_id');
            
            // Get existing teaching load archive IDs from local database
            $existingLocalIds = $this->getExistingLocalIds('tbl_teaching_load_archive', 'archive_id');
            
            $cloudArchives = $this->fetchBulkFromCloud('teaching-load-archives');
            
            if (empty($cloudArchives)) {
                return $synced;
            }
            
            // Filter out teaching load archives that already exist locally or were deleted locally (append-only, no updates needed)
            $newArchives = array_filter($cloudArchives, function ($cloudArchive) use ($existingLocalIds) {
                $archiveId = $cloudArchive['archive_id'] ?? null;
                if (!$archiveId) return false;
                
                // Skip if this record was deleted locally (prevent restoring deleted records)
                if ($this->isDeletedLocally('tbl_teaching_load_archive', $archiveId)) {
                    Log::debug("Skipping teaching load archive {$archiveId} - was deleted locally");
                    return false;
                }
                
                return !in_array($archiveId, $existingLocalIds);
            });
            
            if (empty($newArchives)) {
                Log::info('No new teaching load archives to sync from cloud to local');
                return $synced;
            }
            
            foreach ($newArchives as $cloudArchive) {
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
                            'created_at' => $this->convertCloudTimestampToLocalTimezone($cloudArchive['created_at'] ?? null),
                            'updated_at' => $this->convertCloudTimestampToLocalTimezone($cloudArchive['updated_at'] ?? null),
                        ]
                    ], ['archive_id'], ['original_teaching_load_id', 'faculty_id', 'teaching_load_course_code', 'teaching_load_subject', 'teaching_load_class_section', 'teaching_load_day_of_week', 'teaching_load_time_in', 'teaching_load_time_out', 'room_no', 'school_year', 'semester', 'archived_at', 'archived_by', 'archive_notes', 'created_at', 'updated_at']);
                    
                    $synced[] = $cloudArchive['archive_id'];
                } catch (\Exception $e) {
                    Log::error("Error syncing teaching load archive {$cloudArchive['archive_id']} from cloud: " . $e->getMessage());
                }
            }
            
            Log::info("Synced " . count($synced) . " new teaching load archives from cloud to local");
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
            // STEP 1: Process deletions for this table before syncing data
            $this->processTableDeletionsFromCloud('attendance-record-archives', 'tbl_attendance_record_archive', 'archive_id');
            
            // Get existing attendance record archive IDs from local database
            $existingLocalIds = $this->getExistingLocalIds('tbl_attendance_record_archive', 'archive_id');
            
            $cloudArchives = $this->fetchBulkFromCloud('attendance-record-archives');
            
            if (empty($cloudArchives)) {
                return $synced;
            }
            
            // Filter out attendance record archives that already exist locally or were deleted locally (append-only, no updates needed)
            $newArchives = array_filter($cloudArchives, function ($cloudArchive) use ($existingLocalIds) {
                $archiveId = $cloudArchive['archive_id'] ?? null;
                if (!$archiveId) return false;
                
                // Skip if this record was deleted locally (prevent restoring deleted records)
                if ($this->isDeletedLocally('tbl_attendance_record_archive', $archiveId)) {
                    Log::debug("Skipping attendance record archive {$archiveId} - was deleted locally");
                    return false;
                }
                
                return !in_array($archiveId, $existingLocalIds);
            });
            
            if (empty($newArchives)) {
                Log::info('No new attendance record archives to sync from cloud to local');
                return $synced;
            }
            
            foreach ($newArchives as $cloudArchive) {
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
                            'created_at' => $this->convertCloudTimestampToLocalTimezone($cloudArchive['created_at'] ?? null),
                            'updated_at' => $this->convertCloudTimestampToLocalTimezone($cloudArchive['updated_at'] ?? null),
                        ]
                    ], ['archive_id'], ['original_record_id', 'faculty_id', 'teaching_load_id', 'camera_id', 'record_date', 'record_time_in', 'record_time_out', 'time_duration_seconds', 'record_status', 'record_remarks', 'school_year', 'semester', 'archived_at', 'archived_by', 'archive_notes', 'created_at', 'updated_at']);
                    
                    $synced[] = $cloudArchive['archive_id'];
                } catch (\Exception $e) {
                    Log::error("Error syncing attendance record archive {$cloudArchive['archive_id']} from cloud: " . $e->getMessage());
                }
            }
            
            Log::info("Synced " . count($synced) . " new attendance record archives from cloud to local");
        } catch (\Exception $e) {
            Log::error("Error syncing attendance record archives from cloud: " . $e->getMessage());
        }
        
        return $synced;
    }
    
    /**
     * Sync official matters
     */
    protected function syncOfficialMatters()
    {
        $synced = [];
        
        try {
            // First, sync deletions to cloud (notify cloud about locally deleted records)
            $deletedIds = $this->getDeletedIds('tbl_official_matters');
            $this->syncDeletionsToCloud('official-matters', $deletedIds);
            
            // Get deleted IDs from cloud (to prevent syncing records that were deleted in cloud)
            $cloudDeletedIds = $this->getDeletedIdsFromCloud('official-matters');
            
            // Get existing cloud records with their data for comparison
            $existingCloudRecords = $this->getExistingCloudRecords('official-matters', 'om_id', ['days' => 90]);
            
            // Get all local official matters
            $localOfficialMatters = OfficialMatter::all();
            
            if ($localOfficialMatters->isEmpty()) {
                Log::info('No official matters to sync to cloud');
                return $synced;
            }
            
            // Filter: only sync new records or records that have changed, and skip records deleted in cloud
            $mattersToSync = $localOfficialMatters->filter(function ($om) use ($existingCloudRecords, $cloudDeletedIds) {
                $omId = $om->om_id;
                
                // Skip if this record was deleted in cloud (prevent restoring deleted records)
                if (in_array($omId, $cloudDeletedIds)) {
                    Log::debug("Skipping official matter {$omId} - was deleted in cloud");
                    return false;
                }
                
                // If not in cloud, needs to be synced (new record)
                if (!isset($existingCloudRecords[$omId])) {
                    return true;
                }
                
                // STRICT RULE: Compare updated_at timestamps - only sync if local is newer or equal
                if (!$this->shouldSyncLocalToCloud($om, $existingCloudRecords[$omId], 'official_matter', $omId)) {
                    return false;
                }
                
                // If timestamps are equal, check if data is different (ignore attachment path differences)
                $localData = [
                    'om_id' => $om->om_id,
                    'faculty_id' => $om->faculty_id,
                    'om_department' => $om->om_department,
                    'om_purpose' => $om->om_purpose,
                    'om_remarks' => $om->om_remarks,
                    'om_start_date' => $om->om_start_date,
                    'om_end_date' => $om->om_end_date,
                ];
                
                return $this->recordsAreDifferent($localData, $existingCloudRecords[$omId]);
            });
            
            if ($mattersToSync->isEmpty()) {
                Log::info('No new or changed official matters to sync to cloud');
                return $synced;
            }
            
            // Store old data from cloud BEFORE syncing (for attendance reconciliation)
            $oldDataMap = [];
            foreach ($mattersToSync as $om) {
                $omId = $om->om_id;
                if (isset($existingCloudRecords[$omId])) {
                    // This is an update - store old data from cloud
                    $oldDataMap[$omId] = [
                        'old_start_date' => $existingCloudRecords[$omId]['om_start_date'] ?? null,
                        'old_end_date' => $existingCloudRecords[$omId]['om_end_date'] ?? null,
                        'old_department' => $existingCloudRecords[$omId]['om_department'] ?? null,
                        'old_remarks' => $existingCloudRecords[$omId]['om_remarks'] ?? null,
                    ];
                }
            }
            
            $payload = $mattersToSync->map(function ($om) {
                // Sync attachment to cloud and update path
                $cloudAttachmentPath = $this->syncOfficialMatterAttachment($om->om_attachment);
                
                return [
                    'om_id' => $om->om_id,
                    'faculty_id' => $om->faculty_id,
                    'om_department' => $om->om_department,
                    'om_purpose' => $om->om_purpose,
                    'om_remarks' => $om->om_remarks,
                    'om_start_date' => $om->om_start_date,
                    'om_end_date' => $om->om_end_date,
                    'om_attachment' => $cloudAttachmentPath,
                    'created_at' => $this->formatDateTime($om->created_at),
                    'updated_at' => $this->formatDateTime($om->updated_at),
                ];
            })->values()->all();
            $resp = $this->pushBulkToCloud('official-matters', $payload);
            $upserted = $resp['data']['upserted'] ?? 0;
            Log::info('Bulk official-matters result', ['upserted' => $upserted, 'success' => $resp['success'] ?? null, 'local_count' => count($mattersToSync)]);
            
            if ($resp['success'] && $upserted > 0) {
                $synced = $mattersToSync->pluck('om_id')->all();
                $newCount = count($mattersToSync->filter(function($om) use ($existingCloudRecords) { return !isset($existingCloudRecords[$om->om_id]); }));
                $updatedCount = count($mattersToSync) - $newCount;
                Log::info('Synced ' . count($synced) . ' official matters to cloud (' . $newCount . ' new, ' . $updatedCount . ' updated)');
                
                // Trigger attendance record updates on cloud for synced official matters
                // Pass old data for updates so cloud can properly delete attendance records
                if (!empty($synced)) {
                    $this->triggerCloudAttendanceUpdateForOfficialMatters($synced, $oldDataMap);
                }
            } elseif ($resp['success'] && $upserted == 0) {
                Log::warning("Official matters sync returned success but 0 records were upserted. Check cloud API logs for validation errors.");
            }
        } catch (\Exception $e) {
            Log::error("Error syncing official matters: " . $e->getMessage());
        }
        
        return $synced;
    }
    
    /**
     * Sync official matter attachment
     * @param string $attachmentPath Local attachment path
     * @return string Cloud attachment path/URL
     */
    protected function syncOfficialMatterAttachment($attachmentPath)
    {
        if (empty($attachmentPath)) {
            return $attachmentPath;
        }
        
        // Directory: storage/app/public/official_matters/
        $uploadResult = $this->uploadFileToCloud($attachmentPath, 'official_matters');
        
        if ($uploadResult && $uploadResult['success']) {
            Log::info("Synced official matter attachment to cloud: {$attachmentPath} -> " . ($uploadResult['path'] ?? $uploadResult['url']));
            return $uploadResult['path'] ?? $uploadResult['url'] ?? $attachmentPath;
        }
        
        Log::warning("Failed to sync official matter attachment: {$attachmentPath}");
        return $attachmentPath;
    }
    
    /**
     * Download official matter attachment from cloud
     * @param string $cloudAttachmentPath Cloud attachment path/URL
     * @return string Local attachment path
     */
    protected function downloadOfficialMatterAttachment($cloudAttachmentPath)
    {
        if (empty($cloudAttachmentPath)) {
            return $cloudAttachmentPath;
        }
        
        $localPath = $this->downloadFileFromCloud($cloudAttachmentPath, 'official_matters');
        
        if ($localPath) {
            Log::info("Downloaded official matter attachment from cloud: {$cloudAttachmentPath} -> {$localPath}");
            return $localPath;
        }
        
        Log::warning("Failed to download official matter attachment: {$cloudAttachmentPath}");
        return $cloudAttachmentPath;
    }
    
    /**
     * Sync official matters from cloud to local
     */
    protected function syncOfficialMattersFromCloud()
    {
        $synced = [];
        
        try {
            // STEP 1: Process deletions for this table before syncing data
            $this->processTableDeletionsFromCloud('official-matters', 'tbl_official_matters', 'om_id');
            
            // Get existing local records with their data for comparison (last 90 days for performance)
            $existingLocalRecords = $this->getExistingLocalRecords('tbl_official_matters', 'om_id');
            // Filter to only last 90 days
            $ninetyDaysAgo = now()->subDays(90);
            $existingLocalRecords = array_filter($existingLocalRecords, function($record) use ($ninetyDaysAgo) {
                $createdAt = $record['created_at'] ?? null;
                if (!$createdAt) return false;
                try {
                    return \Carbon\Carbon::parse($createdAt)->gte($ninetyDaysAgo);
                } catch (\Exception $e) {
                    return false;
                }
            });
            
            // Get all official matters from cloud
            $cloudOfficialMatters = $this->fetchBulkFromCloud('official-matters', ['days' => 90]);
            
            if (empty($cloudOfficialMatters)) {
                return $synced;
            }
            
            // Filter: only sync new records or records that have changed
            $mattersToSync = array_filter($cloudOfficialMatters, function ($cloudOM) use ($existingLocalRecords) {
                $omId = $cloudOM['om_id'] ?? null;
                if (!$omId) return false;
                
                // Skip if this record was deleted locally (prevent restoring deleted records)
                if ($this->isDeletedLocally('tbl_official_matters', $omId)) {
                    Log::debug("Skipping official matter {$omId} - was deleted locally");
                    return false;
                }
                
                // If not in local, needs to be synced (new record)
                if (!isset($existingLocalRecords[$omId])) {
                    return true;
                }
                
                // STRICT RULE: Compare updated_at timestamps - only sync if cloud is newer or equal
                if (!$this->shouldSyncCloudToLocal($existingLocalRecords[$omId], $cloudOM, 'official_matter', $omId)) {
                    return false;
                }
                
                // If in local, compare data to see if it changed (ignore attachment path differences)
                $localData = [
                    'om_id' => $existingLocalRecords[$omId]['om_id'],
                    'faculty_id' => $existingLocalRecords[$omId]['faculty_id'] ?? null,
                    'om_department' => $existingLocalRecords[$omId]['om_department'] ?? null,
                    'om_purpose' => $existingLocalRecords[$omId]['om_purpose'] ?? null,
                    'om_remarks' => $existingLocalRecords[$omId]['om_remarks'] ?? null,
                    'om_start_date' => $existingLocalRecords[$omId]['om_start_date'] ?? null,
                    'om_end_date' => $existingLocalRecords[$omId]['om_end_date'] ?? null,
                ];
                
                return $this->recordsAreDifferent($localData, $cloudOM);
            });
            
            if (empty($mattersToSync)) {
                Log::info('No new or changed official matters to sync from cloud to local');
                return $synced;
            }
            
            // Upsert only changed/new official matters
            foreach ($mattersToSync as $cloudOM) {
                try {
                    // Check if this is an update (record exists locally)
                    $isUpdate = isset($existingLocalRecords[$cloudOM['om_id']]);
                    $oldRecord = $isUpdate ? $existingLocalRecords[$cloudOM['om_id']] : null;
                    
                    // Check if date range, department, faculty, or remarks changed (affects attendance records)
                    $dateRangeChanged = false;
                    $facultyChanged = false;
                    $departmentChanged = false;
                    $remarksChanged = false;
                    $needsAttendanceUpdate = false;
                    
                    if ($isUpdate && $oldRecord) {
                        $oldStartDate = $oldRecord['om_start_date'] ?? null;
                        $oldEndDate = $oldRecord['om_end_date'] ?? null;
                        $oldFacultyId = $oldRecord['faculty_id'] ?? null;
                        $oldDepartment = $oldRecord['om_department'] ?? null;
                        $oldRemarks = $oldRecord['om_remarks'] ?? null;
                        
                        $newStartDate = $cloudOM['om_start_date'] ?? null;
                        $newEndDate = $cloudOM['om_end_date'] ?? null;
                        $newFacultyId = $cloudOM['faculty_id'] ?? null;
                        $newDepartment = $cloudOM['om_department'] ?? null;
                        $newRemarks = $cloudOM['om_remarks'] ?? null;
                        
                        $dateRangeChanged = ($oldStartDate !== $newStartDate) || ($oldEndDate !== $newEndDate);
                        $facultyChanged = ($oldFacultyId != $newFacultyId);
                        $departmentChanged = ($oldDepartment !== $newDepartment);
                        $remarksChanged = ($oldRemarks !== $newRemarks);
                        
                        $needsAttendanceUpdate = $dateRangeChanged || $facultyChanged || $departmentChanged || $remarksChanged;
                    }
                    
                    // Download attachment from cloud
                    $localAttachmentPath = $this->downloadOfficialMatterAttachment($cloudOM['om_attachment'] ?? null);
                    
                    DB::table('tbl_official_matters')->upsert([
                        [
                            'om_id' => $cloudOM['om_id'],
                            'faculty_id' => $cloudOM['faculty_id'] ?? null,
                            'om_department' => $cloudOM['om_department'] ?? null,
                            'om_purpose' => $cloudOM['om_purpose'] ?? null,
                            'om_remarks' => $cloudOM['om_remarks'] ?? null,
                            'om_start_date' => $cloudOM['om_start_date'] ?? null,
                            'om_end_date' => $cloudOM['om_end_date'] ?? null,
                            'om_attachment' => $localAttachmentPath,
                            'created_at' => $this->convertCloudTimestampToLocalTimezone($cloudOM['created_at'] ?? null),
                            'updated_at' => $this->convertCloudTimestampToLocalTimezone($cloudOM['updated_at'] ?? null),
                        ]
                    ], ['om_id'], ['faculty_id', 'om_department', 'om_purpose', 'om_remarks', 'om_start_date', 'om_end_date', 'om_attachment', 'created_at', 'updated_at']);
                    
                    // Intelligently update attendance records - preserve existing IDs where possible
                    if ($needsAttendanceUpdate && $isUpdate && $oldRecord) {
                        $this->updateAttendanceRecordsForOfficialMatterSync($cloudOM, $oldRecord);
                    } elseif (!$isUpdate) {
                        // If this is a new record, create attendance records
                        $this->createAttendanceRecordsForOfficialMatterSync($cloudOM);
                    }
                    
                    $synced[] = $cloudOM['om_id'];
                } catch (\Exception $e) {
                    Log::error("Error syncing official matter {$cloudOM['om_id']} from cloud: " . $e->getMessage());
                }
            }
            
            $newCount = count(array_filter($mattersToSync, function($om) use ($existingLocalRecords) { return !isset($existingLocalRecords[$om['om_id']]); }));
            $updatedCount = count($mattersToSync) - $newCount;
            Log::info("Synced " . count($synced) . " official matters from cloud to local (" . $newCount . " new, " . $updatedCount . " updated)");
        } catch (\Exception $e) {
            Log::error("Error syncing official matters from cloud: " . $e->getMessage());
        }
        
        return $synced;
    }
    
    /**
     * Remove attendance records for official matter during sync (before updating)
     * This removes old attendance records based on old official matter data
     */
    /**
     * Intelligently update attendance records for official matter during sync.
     * Preserves existing record IDs when dates overlap between old and new ranges.
     */
    protected function updateAttendanceRecordsForOfficialMatterSync(array $newOfficialMatter, array $oldOfficialMatter)
    {
        try {
            $omId = $newOfficialMatter['om_id'] ?? null;
            if (!$omId) {
                return;
            }
            
            // Get new affected faculty IDs
            $newIsDepartmentMode = !empty($newOfficialMatter['om_department']);
            $newFacultyIds = [];
            
            if ($newIsDepartmentMode) {
                if (($newOfficialMatter['om_department'] ?? null) === 'All Instructor') {
                    $newFacultyIds = Faculty::pluck('faculty_id')->toArray();
                } else {
                    $newFacultyIds = Faculty::where('faculty_department', $newOfficialMatter['om_department'] ?? null)
                        ->pluck('faculty_id')
                        ->toArray();
                }
            } else {
                if ($newOfficialMatter['faculty_id'] ?? null) {
                    $newFacultyIds = [$newOfficialMatter['faculty_id']];
                }
            }
            
            // Get old affected faculty IDs
            $oldIsDepartmentMode = !empty($oldOfficialMatter['om_department']);
            $oldFacultyIds = [];
            
            if ($oldIsDepartmentMode) {
                if (($oldOfficialMatter['om_department'] ?? null) === 'All Instructor') {
                    $oldFacultyIds = Faculty::pluck('faculty_id')->toArray();
                } else {
                    $oldFacultyIds = Faculty::where('faculty_department', $oldOfficialMatter['om_department'] ?? null)
                        ->pluck('faculty_id')
                        ->toArray();
                }
            } else {
                if ($oldOfficialMatter['faculty_id'] ?? null) {
                    $oldFacultyIds = [$oldOfficialMatter['faculty_id']];
                }
            }
            
            $newStartDate = $newOfficialMatter['om_start_date'] ?? null;
            $newEndDate = $newOfficialMatter['om_end_date'] ?? null;
            $newRemarks = $newOfficialMatter['om_remarks'] ?? null;
            
            $oldStartDate = $oldOfficialMatter['om_start_date'] ?? null;
            $oldEndDate = $oldOfficialMatter['om_end_date'] ?? null;
            $oldRemarks = $oldOfficialMatter['om_remarks'] ?? null;
            
            if (!$newStartDate || !$newEndDate || !$newRemarks) {
                return;
            }
            
            // Calculate date ranges
            $newStart = \Carbon\Carbon::parse($newStartDate);
            $newEnd = \Carbon\Carbon::parse($newEndDate);
            $newDates = [];
            $cursor = $newStart->copy();
            while ($cursor->lte($newEnd)) {
                $newDates[] = $cursor->toDateString();
                $cursor->addDay();
            }
            
            $oldDates = [];
            if ($oldStartDate && $oldEndDate) {
                $oldStart = \Carbon\Carbon::parse($oldStartDate);
                $oldEnd = \Carbon\Carbon::parse($oldEndDate);
                $cursor = $oldStart->copy();
                while ($cursor->lte($oldEnd)) {
                    $oldDates[] = $cursor->toDateString();
                    $cursor->addDay();
                }
            }
            
            // Determine which dates to keep, update, and delete
            $datesToKeep = array_intersect($oldDates, $newDates); // Dates in both ranges - UPDATE
            $datesToDelete = array_diff($oldDates, $newDates); // Dates only in old range - DELETE
            $datesToCreate = array_diff($newDates, $oldDates); // Dates only in new range - CREATE
            
            // Step 1: Delete records for dates that are no longer in the range
            if (!empty($datesToDelete) && !empty($oldFacultyIds)) {
                $recordIds = AttendanceRecord::whereIn('faculty_id', $oldFacultyIds)
                    ->whereIn('record_date', $datesToDelete)
                    ->where('record_remarks', $oldRemarks)
                    ->where('record_status', 'Absent')
                    ->pluck('record_id')
                    ->toArray();
                
                if (!empty($recordIds)) {
                    AttendanceRecord::whereIn('record_id', $recordIds)->delete();
                    
                    // Track deletions for sync
                    foreach ($recordIds as $recordId) {
                        $this->trackDeletion('tbl_attendance_record', $recordId);
                    }
                    
                    Log::info("Deleted " . count($recordIds) . " attendance records for dates no longer in range for official matter {$omId}");
                }
            }
            
            // Step 2: Update records for dates that are in both ranges
            foreach ($datesToKeep as $date) {
                $dayOfWeek = \Carbon\Carbon::parse($date)->format('l');
                
                foreach ($newFacultyIds as $facultyId) {
                    $teachingLoads = TeachingLoad::where('faculty_id', $facultyId)
                        ->where('teaching_load_day_of_week', $dayOfWeek)
                        ->get();
                    
                    foreach ($teachingLoads as $teachingLoad) {
                        $existingRecord = AttendanceRecord::where('faculty_id', $facultyId)
                            ->where('teaching_load_id', $teachingLoad->teaching_load_id)
                            ->whereDate('record_date', $date)
                            ->first();
                        
                        if ($existingRecord) {
                            // Update existing record - preserve ID
                            $existingRecord->update([
                                'record_remarks' => $newRemarks,
                                'record_status' => 'Absent',
                            ]);
                        }
                    }
                }
            }
            
            // Step 3: Create records for dates that are only in new range
            foreach ($datesToCreate as $date) {
                $dayOfWeek = \Carbon\Carbon::parse($date)->format('l');
                
                foreach ($newFacultyIds as $facultyId) {
                    $teachingLoads = TeachingLoad::where('faculty_id', $facultyId)
                        ->where('teaching_load_day_of_week', $dayOfWeek)
                        ->get();
                    
                    foreach ($teachingLoads as $teachingLoad) {
                        $existingRecord = AttendanceRecord::where('faculty_id', $facultyId)
                            ->where('teaching_load_id', $teachingLoad->teaching_load_id)
                            ->whereDate('record_date', $date)
                            ->first();
                        
                        if ($existingRecord) {
                            // Update if exists
                            $existingRecord->update([
                                'record_remarks' => $newRemarks,
                                'record_status' => 'Absent',
                            ]);
                        } else {
                            // Create new record only if doesn't exist
                            $cameraId = Camera::where('room_no', $teachingLoad->room_no)->value('camera_id');
                            
                            if (!$cameraId) {
                                continue;
                            }
                            
                            AttendanceRecord::create([
                                'faculty_id' => $facultyId,
                                'teaching_load_id' => $teachingLoad->teaching_load_id,
                                'camera_id' => $cameraId,
                                'record_date' => $date,
                                'record_time_in' => null,
                                'record_time_out' => null,
                                'time_duration_seconds' => 0,
                                'record_status' => 'Absent',
                                'record_remarks' => $newRemarks,
                            ]);
                        }
                    }
                }
            }
            
            Log::info("Updated attendance records for official matter {$omId} (kept: " . count($datesToKeep) . ", deleted: " . count($datesToDelete) . ", created: " . count($datesToCreate) . ")");
        } catch (\Exception $e) {
            Log::error("Error updating attendance records for official matter sync: " . $e->getMessage());
        }
    }
    
    /**
     * Create attendance records for official matter during sync
     * This creates new attendance records based on official matter data
     */
    protected function createAttendanceRecordsForOfficialMatterSync(array $officialMatter)
    {
        try {
            $omId = $officialMatter['om_id'] ?? null;
            if (!$omId) {
                return;
            }
            
            // Get the official matter record (should exist after upsert)
            $localOM = DB::table('tbl_official_matters')->where('om_id', $omId)->first();
            if (!$localOM) {
                return;
            }
            
            // Get affected faculty IDs
            $isDepartmentMode = !empty($localOM->om_department);
            $facultyIds = [];
            
            if ($isDepartmentMode) {
                if ($localOM->om_department === 'All Instructor') {
                    $facultyIds = Faculty::pluck('faculty_id')->toArray();
                } else {
                    $facultyIds = Faculty::where('faculty_department', $localOM->om_department)
                        ->pluck('faculty_id')
                        ->toArray();
                }
            } else {
                if ($localOM->faculty_id) {
                    $facultyIds = [$localOM->faculty_id];
                }
            }
            
            if (empty($facultyIds)) {
                return;
            }
            
            $startDate = $localOM->om_start_date;
            $endDate = $localOM->om_end_date;
            $remarks = $localOM->om_remarks;
            
            // Create new attendance records
            $start = \Carbon\Carbon::parse($startDate);
            $end = \Carbon\Carbon::parse($endDate);
            $cursor = $start->copy();
            $createdCount = 0;
            $updatedCount = 0;
            
            while ($cursor->lte($end)) {
                $date = $cursor->toDateString();
                $dayOfWeek = $cursor->format('l');
                
                foreach ($facultyIds as $facultyId) {
                    // Get all teaching loads for this faculty on this day
                    $teachingLoads = TeachingLoad::where('faculty_id', $facultyId)
                        ->where('teaching_load_day_of_week', $dayOfWeek)
                        ->get();
                    
                    foreach ($teachingLoads as $teachingLoad) {
                        // Check if attendance record already exists (might have been created by other means)
                        $existingRecord = AttendanceRecord::where('faculty_id', $facultyId)
                            ->where('teaching_load_id', $teachingLoad->teaching_load_id)
                            ->whereDate('record_date', $date)
                            ->first();
                        
                        // Find a valid camera assigned to the teaching load's room
                        $cameraId = Camera::where('room_no', $teachingLoad->room_no)->value('camera_id');
                        
                        // If no camera is mapped to the room, skip creation to avoid FK violation
                        if (!$cameraId) {
                            continue;
                        }
                        
                        if ($existingRecord) {
                            // Update existing record - set remarks and status
                            $existingRecord->update([
                                'record_remarks' => $remarks,
                                'record_status' => 'Absent',
                            ]);
                            $updatedCount++;
                        } else {
                            // Create new record
                            AttendanceRecord::create([
                                'faculty_id' => $facultyId,
                                'teaching_load_id' => $teachingLoad->teaching_load_id,
                                'camera_id' => $cameraId,
                                'record_date' => $date,
                                'record_time_in' => null,
                                'record_time_out' => null,
                                'time_duration_seconds' => 0,
                                'record_status' => 'Absent',
                                'record_remarks' => $remarks,
                            ]);
                            $createdCount++;
                        }
                    }
                }
                
                $cursor->addDay();
            }
            
            Log::info("Created {$createdCount} new and updated {$updatedCount} attendance records for official matter {$omId} during sync");
        } catch (\Exception $e) {
            Log::error("Error creating attendance records for official matter sync: " . $e->getMessage());
        }
    }
    
    /**
     * Trigger attendance record updates on cloud for synced leaves
     * @param array $leaveIds Array of leave IDs that were synced
     */
    protected function triggerCloudAttendanceUpdateForLeaves(array $leaveIds, array $oldDatesMap = [])
    {
        try {
            if (empty($leaveIds)) {
                return;
            }
            
            // Get leave details to determine which dates need attendance updates
            $leaves = Leave::whereIn('lp_id', $leaveIds)->get();
            
            foreach ($leaves as $leave) {
                if (!$leave->faculty_id || !$leave->leave_start_date || !$leave->leave_end_date) {
                    continue;
                }
                
                // Get old dates if this is an update
                $oldStartDate = $oldDatesMap[$leave->lp_id]['old_start_date'] ?? null;
                $oldEndDate = $oldDatesMap[$leave->lp_id]['old_end_date'] ?? null;
                
                // Call cloud API to trigger attendance update for this leave
                // Include old dates so cloud can properly delete attendance records for dates no longer in range
                $this->callCloudAttendanceUpdateTrigger('leave', [
                    'lp_id' => $leave->lp_id,
                    'faculty_id' => $leave->faculty_id,
                    'start_date' => $leave->leave_start_date,
                    'end_date' => $leave->leave_end_date,
                    'old_start_date' => $oldStartDate,
                    'old_end_date' => $oldEndDate,
                ]);
            }
            
            Log::info("Triggered attendance updates on cloud for " . count($leaveIds) . " leaves");
        } catch (\Exception $e) {
            Log::error("Error triggering cloud attendance update for leaves: " . $e->getMessage());
        }
    }
    
    /**
     * Trigger attendance record updates on cloud for synced passes
     * @param array $passIds Array of pass IDs that were synced
     */
    protected function triggerCloudAttendanceUpdateForPasses(array $passIds, array $oldDatesMap = [])
    {
        try {
            if (empty($passIds)) {
                return;
            }
            
            // Get pass details to determine which dates need attendance updates
            $passes = Pass::whereIn('lp_id', $passIds)->get();
            
            foreach ($passes as $pass) {
                if (!$pass->faculty_id || !$pass->pass_slip_date) {
                    continue;
                }
                
                // Get old date if this is an update
                $oldDate = $oldDatesMap[$pass->lp_id]['old_date'] ?? null;
                
                // Call cloud API to trigger attendance update for this pass
                // Include old date so cloud can properly delete attendance records for old date
                $this->callCloudAttendanceUpdateTrigger('pass', [
                    'lp_id' => $pass->lp_id,
                    'faculty_id' => $pass->faculty_id,
                    'date' => $pass->pass_slip_date,
                    'old_date' => $oldDate,
                ]);
            }
            
            Log::info("Triggered attendance updates on cloud for " . count($passIds) . " passes");
        } catch (\Exception $e) {
            Log::error("Error triggering cloud attendance update for passes: " . $e->getMessage());
        }
    }
    
    /**
     * Trigger attendance record updates on cloud for synced official matters
     * @param array $omIds Array of official matter IDs that were synced
     */
    protected function triggerCloudAttendanceUpdateForOfficialMatters(array $omIds, array $oldDataMap = [])
    {
        try {
            if (empty($omIds)) {
                return;
            }
            
            // Get official matter details to determine which dates need attendance updates
            $officialMatters = OfficialMatter::whereIn('om_id', $omIds)->get();
            
            foreach ($officialMatters as $om) {
                if (!$om->om_start_date || !$om->om_end_date) {
                    continue;
                }
                
                // Get old data if this is an update
                $oldData = $oldDataMap[$om->om_id] ?? [];
                
                // Call cloud API to trigger attendance update for this official matter
                // Include old data so cloud can properly delete attendance records for dates/departments no longer in range
                $this->callCloudAttendanceUpdateTrigger('official_matter', [
                    'om_id' => $om->om_id,
                    'faculty_id' => $om->faculty_id,
                    'department' => $om->om_department,
                    'start_date' => $om->om_start_date,
                    'end_date' => $om->om_end_date,
                    'remarks' => $om->om_remarks,
                    'old_start_date' => $oldData['old_start_date'] ?? null,
                    'old_end_date' => $oldData['old_end_date'] ?? null,
                    'old_department' => $oldData['old_department'] ?? null,
                    'old_remarks' => $oldData['old_remarks'] ?? null,
                ]);
            }
            
            Log::info("Triggered attendance updates on cloud for " . count($omIds) . " official matters");
        } catch (\Exception $e) {
            Log::error("Error triggering cloud attendance update for official matters: " . $e->getMessage());
        }
    }
    
    /**
     * Call cloud API to trigger attendance record updates
     * @param string $type Type of record: 'leave', 'pass', or 'official_matter'
     * @param array $data Data needed for attendance update
     */
    protected function callCloudAttendanceUpdateTrigger(string $type, array $data)
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->cloudApiKey,
                    'Accept' => 'application/json',
                ])
                ->post("{$this->cloudApiUrl}/sync/trigger-attendance-update", [
                    'type' => $type,
                    'data' => $data,
                ]);
            
            if ($response->successful()) {
                Log::debug("Successfully triggered attendance update on cloud for {$type} " . ($data['lp_id'] ?? $data['om_id'] ?? 'unknown'));
            } else {
                Log::warning("Failed to trigger attendance update on cloud for {$type}: " . $response->body());
            }
        } catch (\Exception $e) {
            Log::error("Error calling cloud attendance update trigger for {$type}: " . $e->getMessage());
        }
    }
}

