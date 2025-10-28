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
