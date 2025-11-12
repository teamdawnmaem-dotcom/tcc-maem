<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Camera;
use App\Models\TeachingLoad;
use App\Models\Faculty;
use App\Models\StreamRecording;
use Illuminate\Support\Carbon;

class LiveCameraController extends Controller
{
    /**
     * Display the live camera feed management page.
     */
    public function index()
    {
 // Get all cameras with room info
    $cameras = Camera::join('tbl_room as r', 'tbl_camera.room_no', '=', 'r.room_no')
        ->select(
            'tbl_camera.camera_id',
            'tbl_camera.camera_name',
            'tbl_camera.camera_ip_address',
            'tbl_camera.camera_live_feed',
            'tbl_camera.room_no',
            'r.room_name',
            'r.room_building_no'
        )
        ->get();

    // Get today's teaching loads with faculty
    $day = Carbon::now('Asia/Manila')->format('l');
    $teachingLoads = TeachingLoad::join('tbl_faculty as f', 'tbl_teaching_load.faculty_id', '=', 'f.faculty_id')
        ->select(
            'tbl_teaching_load.teaching_load_id',
            'tbl_teaching_load.teaching_load_course_code',
            'tbl_teaching_load.teaching_load_subject',
            'tbl_teaching_load.teaching_load_day_of_week',
            'tbl_teaching_load.teaching_load_class_section',
            'tbl_teaching_load.teaching_load_time_in',
            'tbl_teaching_load.teaching_load_time_out',
            'tbl_teaching_load.room_no',
            'f.faculty_id',
            'f.faculty_fname',
            'f.faculty_lname'
        )
        ->where('tbl_teaching_load.teaching_load_day_of_week', $day)
        ->get();
       
    $faculties = Faculty::all();

    // Get all stream recordings grouped by camera_id
    // Format start_time as MySQL datetime string to avoid timezone conversion issues
    $recordings = StreamRecording::orderBy('start_time', 'desc')->get()->map(function ($recording) {
        $recording->start_time_formatted = $recording->start_time ? $recording->start_time->format('Y-m-d H:i:s') : null;
        return $recording;
    });

        // Pass data to the Blade view
        return view('deptHead.live-camera-feed', compact('cameras', 'teachingLoads', 'faculties', 'recordings'));
    }

     /**
     * Display the live camera feed management page.
     */
    public function index1()
    {
 // Get all cameras with room info
    $cameras = Camera::join('tbl_room as r', 'tbl_camera.room_no', '=', 'r.room_no')
        ->select(
            'tbl_camera.camera_id',
            'tbl_camera.camera_name',
            'tbl_camera.camera_ip_address',
            'tbl_camera.camera_live_feed',
            'tbl_camera.room_no',
            'r.room_name',
            'r.room_building_no'
        )
        ->get();

    // Get today's teaching loads with faculty
    $day = Carbon::now('Asia/Manila')->format('l');
    $teachingLoads = TeachingLoad::join('tbl_faculty as f', 'tbl_teaching_load.faculty_id', '=', 'f.faculty_id')
        ->select(
            'tbl_teaching_load.teaching_load_id',
            'tbl_teaching_load.teaching_load_course_code',
            'tbl_teaching_load.teaching_load_subject',
            'tbl_teaching_load.teaching_load_day_of_week',
            'tbl_teaching_load.teaching_load_class_section',
            'tbl_teaching_load.teaching_load_time_in',
            'tbl_teaching_load.teaching_load_time_out',
            'tbl_teaching_load.room_no',
            'f.faculty_id',
            'f.faculty_fname',
            'f.faculty_lname'
        )
        ->where('tbl_teaching_load.teaching_load_day_of_week', $day)
        ->get();
       
    $faculties = Faculty::all();

    // Get all stream recordings grouped by camera_id
    // Format start_time as MySQL datetime string to avoid timezone conversion issues
    $recordings = StreamRecording::orderBy('start_time', 'desc')->get()->map(function ($recording) {
        $recording->start_time_formatted = $recording->start_time ? $recording->start_time->format('Y-m-d H:i:s') : null;
        return $recording;
    });

        // Pass data to the Blade view
        return view('admin.live-camera-feed', compact('cameras', 'teachingLoads', 'faculties', 'recordings'));
    }

     /**
     * Display the live camera feed management page.
     */
    public function index2()
    {
 // Get all cameras with room info
    $cameras = Camera::join('tbl_room as r', 'tbl_camera.room_no', '=', 'r.room_no')
        ->select(
            'tbl_camera.camera_id',
            'tbl_camera.camera_name',
            'tbl_camera.camera_ip_address',
            'tbl_camera.camera_live_feed',
            'tbl_camera.room_no',
            'r.room_name',
            'r.room_building_no'
        )
        ->get();

    // Get today's teaching loads with faculty
    $day = Carbon::now('Asia/Manila')->format('l');
    $teachingLoads = TeachingLoad::join('tbl_faculty as f', 'tbl_teaching_load.faculty_id', '=', 'f.faculty_id')
        ->select(
            'tbl_teaching_load.teaching_load_id',
            'tbl_teaching_load.teaching_load_course_code',
            'tbl_teaching_load.teaching_load_subject',
            'tbl_teaching_load.teaching_load_day_of_week',
            'tbl_teaching_load.teaching_load_class_section',
            'tbl_teaching_load.teaching_load_time_in',
            'tbl_teaching_load.teaching_load_time_out',
            'tbl_teaching_load.room_no',
            'f.faculty_id',
            'f.faculty_fname',
            'f.faculty_lname'
        )
        ->where('tbl_teaching_load.teaching_load_day_of_week', $day)
        ->get();
       
    $faculties = Faculty::all();

    // Get all stream recordings grouped by camera_id
    // Format start_time as MySQL datetime string to avoid timezone conversion issues
    $recordings = StreamRecording::orderBy('start_time', 'desc')->get()->map(function ($recording) {
        $recording->start_time_formatted = $recording->start_time ? $recording->start_time->format('Y-m-d H:i:s') : null;
        return $recording;
    });

        // Pass data to the Blade view
        return view('checker.live-camera-feed', compact('cameras', 'teachingLoads', 'faculties', 'recordings'));
    }
}
