<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserAccountController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\DeptHeadController;
use App\Http\Controllers\CheckerController;
use App\Http\Controllers\FacultyController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\CameraController;
use App\Http\Controllers\TeachingLoadController;
use App\Http\Controllers\LiveCameraController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\PassController;

// --------------------
// Login routes
// --------------------
Route::get('/', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// --------------------
// Admin routes
// --------------------
Route::prefix('admin')->middleware('role:admin')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'admin'])->name('admin.dashboard');
    
    // Account Setting
    Route::put('/account/update', [AdminController::class, 'updateAccount'])
    ->name('admin.account.update');
      
    // Users
    Route::get('/users', [UserAccountController::class, 'index'])->name('admin.user.account.management');
    Route::post('/users', [UserAccountController::class, 'store'])->name( 'admin.users.store');
    Route::put('/users/{id}', [UserAccountController::class, 'update'])->name( 'admin.users.update');
    Route::delete('/users/{id}', [UserAccountController::class, 'destroy'])->name('admin.users.destroy');

    // Live Feed
    Route::get('/live-camera-feed', [LiveCameraController::class, 'index1'])->name('admin.live.camera.feed');

    // Reports
    Route::get('/attendance-records', [AdminController::class, 'attendanceRecords'])->name('admin.attendance.records');
    Route::get('/attendance-records/print', [AdminController::class, 'attendanceRecordsPrint'])->name('admin.attendance.records.print');
    Route::get('/recognition-logs', [AdminController::class, 'recognitionLogs'])->name('admin.recognition.logs');
});


// --------------------
// Department Head routes
// --------------------
Route::prefix('deptHead')->middleware('role:department head')->group(function () {
  Route::get('/dashboard', [DashboardController::class, 'deptHead'])->name('deptHead.dashboard');
  
  // Account Setting
    Route::put('/account/update', [DeptHeadController::class, 'updateAccount'])
    ->name('deptHead.account.update');
    
    // Faculty
    Route::get('/faculty', [FacultyController::class, 'index'])->name('deptHead.faculty.account.management');
    Route::post('/faculty', [FacultyController::class, 'store'])->name('deptHead.faculty.store');
    Route::put('/faculty/{id}', [FacultyController::class, 'update'])->name('deptHead.faculty.update');
    Route::delete('/faculty/{id}', [FacultyController::class, 'destroy'])->name('deptHead.faculty.destroy');
    
    // Rooms
    Route::get('/rooms', [RoomController::class, 'index'])->name('deptHead.room.management');
    Route::post('/rooms', [RoomController::class, 'store'])->name('deptHead.room.store');
    Route::put('/rooms/{id}', [RoomController::class, 'update'])->name('deptHead.room.update');
    Route::delete('/rooms/{id}', [RoomController::class, 'destroy'])->name('deptHead.room.destroy');
    
    // Cameras
    Route::get('/cameras', [CameraController::class, 'index'])->name('deptHead.camera.management');
    Route::post('/cameras', [CameraController::class, 'store'])->name('deptHead.camera.store');
    Route::put('/cameras/{id}', [CameraController::class, 'update'])->name('deptHead.camera.update');
    Route::delete('/cameras/{id}', [CameraController::class, 'destroy'])->name('deptHead.camera.destroy');
    
    // Teaching Load
    Route::get('/teaching-load', [TeachingLoadController::class, 'index'])->name('deptHead.teaching.load.management');
    Route::post('/teaching-load', [TeachingLoadController::class, 'store'])->name('deptHead.teaching-load.store');
    Route::put('/teaching-load/{id}', [TeachingLoadController::class, 'update'])->name('deptHead.teaching-load.update');
    Route::delete('/teaching-load/{id}', [TeachingLoadController::class, 'destroy'])->name('deptHead.teaching-load.destroy');
    
    // Live Feed
    Route::get('/live-camera-feed', [LiveCameraController::class, 'index'])->name('deptHead.live.camera.feed');
    
    // Reports
    Route::get('/attendance-records', [DeptHeadController::class, 'attendanceRecords'])->name('deptHead.attendance.records');
    Route::get('/attendance-records/print', [DeptHeadController::class, 'attendanceRecordsPrint'])->name('deptHead.attendance.records.print');
    Route::get('/recognition-logs', [DeptHeadController::class, 'recognitionLogs'])->name('deptHead.recognition.logs');
    
    
  });
  
  
  // --------------------
  // Checker routes
  // --------------------
  Route::prefix('checker')->middleware('role:checker')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'checker'])->name('checker.dashboard');

    // Account Setting
    Route::put('/account/update', [CheckerController::class, 'updateAccount'])
    ->name('checker.account.update');
    
    // Leave Routes
    Route::get('/leaves', [LeaveController::class, 'index'])->name('checker.leave.management');
    Route::post('/leaves', [LeaveController::class, 'store'])->name('checker.leaves.store');
    Route::put('/leaves/{id}', [LeaveController::class, 'update'])->name('checker.leaves.update');
    Route::delete('/leaves/{id}', [LeaveController::class, 'destroy'])->name('checker.leaves.destroy');

    // Pass Routes
    Route::get('/passes', [PassController::class, 'index'])->name('checker.pass.management');
    Route::post('/passes', [PassController::class, 'store'])->name('checker.passes.store');
    Route::put('/passes/{id}', [PassController::class, 'update'])->name('checker.passes.update');
    Route::delete('/passes/{id}', [PassController::class, 'destroy'])->name('checker.passes.destroy');
      
    // Live Feed
    Route::get('/live-camera-feed', [LiveCameraController::class, 'index2'])->name('checker.live.camera.feed');

    // Reports
    Route::get('/attendance-records', [CheckerController::class, 'attendanceRecords'])->name('checker.attendance.records');
    Route::get('/attendance-records/print', [CheckerController::class, 'attendanceRecordsPrint'])->name('checker.attendance.records.print');
    Route::get('/recognition-logs', [CheckerController::class, 'recognitionLogs'])->name('checker.recognition.logs');
    
    

  });
  