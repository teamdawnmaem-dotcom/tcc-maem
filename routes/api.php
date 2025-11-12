<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CameraController;
use App\Http\Controllers\TeachingLoadController;
use App\Http\Controllers\FacultyController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\PassController;
use App\Http\Controllers\RecognitionLogController;
use App\Http\Controllers\StreamRecordingController;
use App\Http\Controllers\CloudSyncController;
use App\Http\Controllers\Api\SyncReceiverController;
use App\Http\Controllers\Api\LocalDeleteController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('/logs', [RecognitionLogController::class, 'push']);

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Public API routes (no authentication required)
Route::get('/cameras', [CameraController::class, 'apiIndex']);
Route::get('/rooms', [CameraController::class, 'apiRooms']);
Route::get('/faculty', [FacultyController::class, 'apiFaculty']);
Route::get('/faculty/{facultyId}', [FacultyController::class, 'apiFacultyById']);
Route::get('/faculty/{facultyId}/teaching-loads', [FacultyController::class, 'apiFacultyTeachingLoads']);
Route::get('/teaching-loads', [TeachingLoadController::class, 'apiTeachingLoads']);
Route::get('/schedule', [TeachingLoadController::class, 'apiTodaySchedule']);
Route::post('/teaching-load-details', [TeachingLoadController::class, 'apiTeachingLoadDetails']);
Route::get('/faculty-embeddings', [FacultyController::class, 'apiFacultyEmbeddings']);
Route::put('/faculty-embeddings', [FacultyController::class, 'apiUpdateFacultyEmbeddings']);
Route::post('/trigger-embedding-update', [FacultyController::class, 'apiTriggerEmbeddingUpdate']);
Route::post('/regenerate-all-embeddings', [FacultyController::class, 'apiRegenerateAllEmbeddings']);

Route::get('/attendance', [AttendanceController::class, 'index']);
Route::post('/attendance', [AttendanceController::class, 'store']);
Route::post('/attendance/check', [AttendanceController::class, 'check']);
Route::get('/attendance/{recordId}/details', [AttendanceController::class, 'getRecordDetails']);

// Leave and pass slip status checking for Python service
Route::post('/faculty-leave-status', [LeaveController::class, 'checkFacultyLeaveStatus']);
Route::post('/faculty-pass-status', [PassController::class, 'checkFacultyPassStatus']);

// Recognition logs API
Route::get('/recognition-logs', [RecognitionLogController::class, 'index']);
Route::post('/recognition-logs', [RecognitionLogController::class, 'store']);
Route::get('/recognition-logs/statistics', [RecognitionLogController::class, 'statistics']);

// Stream recordings API
Route::post('/stream-recordings', [StreamRecordingController::class, 'store']);
Route::get('/stream-recordings', [StreamRecordingController::class, 'index']);
Route::get('/stream-recordings/statistics', [StreamRecordingController::class, 'statistics']);
Route::get('/stream-recordings/camera/{camera_id}', [StreamRecordingController::class, 'getByCamera']);
Route::get('/stream-recordings/{id}/stream', [StreamRecordingController::class, 'stream']); // Video streaming endpoint
Route::get('/stream-recordings/{id}', [StreamRecordingController::class, 'show']);
Route::delete('/stream-recordings/{id}', [StreamRecordingController::class, 'destroy']);

// Cloud sync API (for local development server)
Route::post('/cloud-sync/sync-now', [CloudSyncController::class, 'syncNow']);
Route::get('/cloud-sync/status', [CloudSyncController::class, 'status']);

// ============================================================================
// SYNC RECEIVER ENDPOINTS (For Hostinger Cloud Server)
// ============================================================================
// These endpoints receive data FROM local development server TO cloud server
// Protected by API key authentication (see app/Http/Middleware/ApiKeyAuth.php)
// The API_KEY in .env must match the CLOUD_API_KEY from local server
// ============================================================================

// ============================================================================
// SYNC ROUTES - Separate routes with /sync/ prefix to avoid conflicts
// ============================================================================
// All sync routes use /api/sync/ prefix and require API key authentication
// These are for syncing data FROM local server TO cloud server
// ============================================================================

Route::middleware('api.key')->prefix('sync')->group(function () {
    // Sync status endpoint
    Route::get('/status', [SyncReceiverController::class, 'getSyncStatus']);
    
    // Subjects
    Route::get('/subjects', [SyncReceiverController::class, 'getSubjects']);
    Route::post('/subjects', [SyncReceiverController::class, 'receiveSubject']);
    
    // Users
    Route::get('/users', [SyncReceiverController::class, 'getUsers']);
    Route::post('/users', [SyncReceiverController::class, 'receiveUser']);
    
    // Rooms
    Route::get('/rooms', [SyncReceiverController::class, 'getRooms']);
    Route::post('/rooms', [SyncReceiverController::class, 'receiveRoom']);
    
    // Cameras
    Route::get('/cameras', [SyncReceiverController::class, 'getCameras']);
    Route::post('/cameras', [SyncReceiverController::class, 'receiveCamera']);
    
    // Faculties
    Route::get('/faculties', [SyncReceiverController::class, 'getFaculties']);
    Route::post('/faculties', [SyncReceiverController::class, 'receiveFaculty']);
    
    // Teaching Loads
    Route::get('/teaching-loads', [SyncReceiverController::class, 'getTeachingLoads']);
    Route::post('/teaching-loads', [SyncReceiverController::class, 'receiveTeachingLoad']);
    
    // Attendance Records
    Route::get('/attendance-records', [SyncReceiverController::class, 'getAttendanceRecords']);
    Route::post('/attendance-records', [SyncReceiverController::class, 'receiveAttendanceRecord']);
    
    // Leaves
    Route::get('/leaves', [SyncReceiverController::class, 'getLeaves']);
    Route::post('/leaves', [SyncReceiverController::class, 'receiveLeave']);
    
    // Passes
    Route::get('/passes', [SyncReceiverController::class, 'getPasses']);
    Route::post('/passes', [SyncReceiverController::class, 'receivePass']);
    
    // Recognition Logs
    Route::get('/recognition-logs', [SyncReceiverController::class, 'getRecognitionLogs']);
    Route::post('/recognition-logs', [SyncReceiverController::class, 'receiveRecognitionLog']);
    
    // Stream Recordings
    Route::get('/stream-recordings', [SyncReceiverController::class, 'getStreamRecordings']);
    Route::post('/stream-recordings', [SyncReceiverController::class, 'receiveStreamRecording']);
    
    // Activity Logs
    Route::get('/activity-logs', [SyncReceiverController::class, 'getActivityLogs']);
    Route::post('/activity-logs', [SyncReceiverController::class, 'receiveActivityLog']);
    
    // Teaching Load Archives
    Route::get('/teaching-load-archives', [SyncReceiverController::class, 'getTeachingLoadArchives']);
    Route::post('/teaching-load-archives', [SyncReceiverController::class, 'receiveTeachingLoadArchive']);
    
    // Attendance Record Archives
    Route::get('/attendance-record-archives', [SyncReceiverController::class, 'getAttendanceRecordArchives']);
    Route::post('/attendance-record-archives', [SyncReceiverController::class, 'receiveAttendanceRecordArchive']);
    
    // Official Matters
    Route::get('/official-matters', [SyncReceiverController::class, 'getOfficialMatters']);
    Route::post('/official-matters', [SyncReceiverController::class, 'receiveOfficialMatter']);
    
    // File Uploads
    Route::post('/upload/{directory}', [SyncReceiverController::class, 'receiveFileUpload']);

    // Bulk upsert endpoint (generic)
    Route::post('/bulk/{resource}', [SyncReceiverController::class, 'receiveBulk']);
    
    // Trigger attendance record updates
    Route::post('/trigger-attendance-update', [SyncReceiverController::class, 'triggerAttendanceUpdate']);
    
    // Delete endpoints (called from local server to delete records on cloud)
    Route::delete('/users/{id}', [SyncReceiverController::class, 'deleteUser']);
    Route::delete('/subjects/{id}', [SyncReceiverController::class, 'deleteSubject']);
    Route::delete('/rooms/{id}', [SyncReceiverController::class, 'deleteRoom']);
    Route::delete('/cameras/{id}', [SyncReceiverController::class, 'deleteCamera']);
    Route::delete('/faculties/{id}', [SyncReceiverController::class, 'deleteFaculty']);
    Route::delete('/teaching-loads/{id}', [SyncReceiverController::class, 'deleteTeachingLoad']);
    Route::delete('/leaves/{id}', [SyncReceiverController::class, 'deleteLeave']);
    Route::delete('/passes/{id}', [SyncReceiverController::class, 'deletePass']);
    Route::delete('/official-matters/{id}', [SyncReceiverController::class, 'deleteOfficialMatter']);
    Route::delete('/attendance-records/{id}', [SyncReceiverController::class, 'deleteAttendanceRecord']);
    Route::delete('/recognition-logs/{id}', [SyncReceiverController::class, 'deleteRecognitionLog']);
    Route::delete('/stream-recordings/{id}', [SyncReceiverController::class, 'deleteStreamRecording']);
    Route::delete('/activity-logs/{id}', [SyncReceiverController::class, 'deleteActivityLog']);
    Route::delete('/teaching-load-archives/{id}', [SyncReceiverController::class, 'deleteTeachingLoadArchive']);
    Route::delete('/attendance-record-archives/{id}', [SyncReceiverController::class, 'deleteAttendanceRecordArchive']);
    
    // Delete endpoints (called from cloud server to delete records on local)
    Route::delete('/local/users/{id}', [LocalDeleteController::class, 'deleteUser']);
    Route::delete('/local/subjects/{id}', [LocalDeleteController::class, 'deleteSubject']);
    Route::delete('/local/rooms/{id}', [LocalDeleteController::class, 'deleteRoom']);
    Route::delete('/local/cameras/{id}', [LocalDeleteController::class, 'deleteCamera']);
    Route::delete('/local/faculties/{id}', [LocalDeleteController::class, 'deleteFaculty']);
    Route::delete('/local/teaching-loads/{id}', [LocalDeleteController::class, 'deleteTeachingLoad']);
    Route::delete('/local/leaves/{id}', [LocalDeleteController::class, 'deleteLeave']);
    Route::delete('/local/passes/{id}', [LocalDeleteController::class, 'deletePass']);
    Route::delete('/local/official-matters/{id}', [LocalDeleteController::class, 'deleteOfficialMatter']);
    Route::delete('/local/attendance-records/{id}', [LocalDeleteController::class, 'deleteAttendanceRecord']);
    Route::delete('/local/recognition-logs/{id}', [LocalDeleteController::class, 'deleteRecognitionLog']);
    Route::delete('/local/stream-recordings/{id}', [LocalDeleteController::class, 'deleteStreamRecording']);
    Route::delete('/local/activity-logs/{id}', [LocalDeleteController::class, 'deleteActivityLog']);
    Route::delete('/local/teaching-load-archives/{id}', [LocalDeleteController::class, 'deleteTeachingLoadArchive']);
    Route::delete('/local/attendance-record-archives/{id}', [LocalDeleteController::class, 'deleteAttendanceRecordArchive']);
});
