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

Route::middleware('api.key')->group(function () {
    // Sync status endpoint (public - for testing connection)
    Route::get('/sync-status', [SyncReceiverController::class, 'getSyncStatus']);
    
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
    
    // File Uploads
    Route::post('/upload/{directory}', [SyncReceiverController::class, 'receiveFileUpload']);
});
