<?php

namespace App\Http\Controllers;
use App\Models\ActivityLog;
use App\Models\Camera;
use App\Models\User;
use App\Models\Faculty;

use Illuminate\Http\Request;

class DashboardController extends Controller
{

  public function dashboard()
{
    $user = auth()->user();
    return view('layouts.appAdmin', compact('user'));
}

    public function admin()
    {
         
      // Count of users with role 'User'
  $registeredUser = User::whereIn('user_role', ['Checker', 'Department Head'])->count();

    // Total number of registered cameras
    $totalCameras = Camera::count();

    $registeredFaculty = Faculty::count();

     $logs = ActivityLog::with('user')
        ->orderBy('logs_timestamp', direction: 'desc')
        ->get();

    return view('admin.admin-dashboard', compact('registeredUser','registeredFaculty', 'totalCameras', 'logs'));
    }

    public function checker()
    {
   // Count of users with role 'User'
  $registeredUser = User::whereIn('user_role', ['Checker', 'Department Head'])->count();

    // Total number of registered cameras
    $totalCameras = Camera::count();

    $registeredFaculty = Faculty::count();
        return view('checker.checker-dashboard', compact('registeredUser', 'registeredFaculty', 'totalCameras'));
    }


    public function deptHead()
    {
        // Count of users with role 'User'
  $registeredUser = User::whereIn('user_role', ['Checker', 'Department Head'])->count();

    // Total number of registered cameras
    $totalCameras = Camera::count();

    $registeredFaculty = Faculty::count();
        return view('deptHead.deptHead-dashboard', compact('registeredUser', 'registeredFaculty', 'totalCameras'));
    }
}
