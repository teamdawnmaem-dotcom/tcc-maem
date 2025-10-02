<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Camera;
use App\Models\Room;
use App\Models\ActivityLog;

class CameraController extends Controller
{
    // Display all cameras
    public function index()
    {
        $cameras = Camera::with('room')->get();
        $rooms = Room::all();
        return view('deptHead.camera-management', compact('cameras', 'rooms'));
    }


public function apiIndex()
{
    $cameras = Camera::join('tbl_room as r', 'tbl_camera.room_no', '=', 'r.room_no')
        ->select(
            'tbl_camera.camera_id',
            'tbl_camera.camera_name',
            'tbl_camera.camera_live_feed',
            'r.room_no',
            'r.room_name',
            'r.room_building_no'
        )
        ->get();

    return response()->json($cameras, 200, [], JSON_UNESCAPED_SLASHES);
}

public function apiRooms()
{
    $rooms = Room::select('room_no', 'room_name', 'room_building_no')->get();
    return response()->json($rooms);
}

   // Store a new camera
public function store(Request $request)
{
    $request->validate([
        'camera_name' => 'required|string|max:255',
        'camera_ip_address' => 'required|string|max:50',
        'camera_username' => 'required|string|max:50',
        'camera_password' => 'required|string|max:50',
        'room_no' => 'required|exists:tbl_room,room_no',
    ]);

    // Generate RTSP URL
    $rtspUrl = 'rtsp://' . $request->camera_username . ':' . $request->camera_password . '@' . $request->camera_ip_address . ':554/stream1';

    Camera::create([
        'camera_name' => $request->camera_name,
        'camera_ip_address' => $request->camera_ip_address,
        'camera_username' => $request->camera_username,
        'camera_password' => $request->camera_password,
        'camera_live_feed' => $rtspUrl,
        'room_no' => $request->room_no,
    ]);

          ActivityLog::create([
            'user_id' => auth()->id(),
            'logs_action' => 'CREATE',
            'logs_description' => 'Added new Camera: ' . $request->camera_name,
            'logs_module' => 'Camera management',
        ]);

    return redirect()->route('deptHead.camera.management')->with('success', 'Camera added successfully!');
}

// Update a camera
public function update(Request $request, $id)
{
    $camera = Camera::findOrFail($id);

    $request->validate([
        'camera_name' => 'required|string|max:255',
        'camera_ip_address' => 'required|string|max:50',
        'camera_username' => 'required|string|max:50',
        'camera_password' => 'required|string|max:50',
        'room_no' => 'required|exists:tbl_room,room_no',
    ]);

    // Regenerate RTSP URL
    $rtspUrl = 'rtsp://' . $request->camera_username . ':' . $request->camera_password . '@' . $request->camera_ip_address . ':554/stream1';

    $camera->update([
        'camera_name' => $request->camera_name,
        'camera_ip_address' => $request->camera_ip_address,
        'camera_username' => $request->camera_username,
        'camera_password' => $request->camera_password,
        'camera_live_feed' => $rtspUrl,
        'room_no' => $request->room_no,
    ]);

        ActivityLog::create([
            'user_id' => auth()->id(),
            'logs_action' => 'UPDATE',
            'logs_description' => 'Updated a Camera: ' . $request->camera_name,
            'logs_module' => 'Camera management',
        ]);

    return redirect()->route('deptHead.camera.management')->with('success', 'Camera updated successfully!');
}

    // Delete a camera
    public function destroy($id)
    {
        $camera = Camera::findOrFail($id);
        $camera->delete();

        ActivityLog::create([
            'user_id' => auth()->id(),
            'logs_action' => 'DELETE',
            'logs_description' => 'Deleted a Camera: ' . $camera->camera_name,
            'logs_module' => 'Camera management',
        ]);

        return redirect()->route('deptHead.camera.management')->with('success', 'Camera deleted successfully!');
    }
}
