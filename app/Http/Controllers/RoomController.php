<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Room;
use App\Models\ActivityLog;
use App\Services\CloudSyncService;

class RoomController extends Controller
{
    // Display all rooms
    public function index()
    {
        $rooms = Room::all();
        return view('deptHead.room-management', compact('rooms')); // Blade you mentioned
    }

    // Store a new room
    public function store(Request $request)
    {
        $request->validate([
            'room_name' => 'required|string|max:255',
            'room_building_no' => 'required|string|max:50',
        ]);

        Room::create([
            'room_name' => $request->room_name,
            'room_building_no' => $request->room_building_no,
        ]);

 
   // Log the action
        ActivityLog::create([
            'user_id' => auth()->id(),
            'logs_action' => 'CREATE',
            'logs_description' => 'Added new room: ' . $request->room_name,
            'logs_module' => 'Room management',
        ]);

        return redirect()->route('deptHead.room.management')->with('success', 'Room added successfully!');
    }

    // Update an existing room
    public function update(Request $request, $id)
    {
        $room = Room::findOrFail($id);

        $request->validate([
            'room_name' => 'required|string|max:255',
            'room_building_no' => 'required|string|max:50',
        ]);

        $room->update([
            'room_name' => $request->room_name,
            'room_building_no' => $request->room_building_no,
        ]);

       
   // Log the action
        ActivityLog::create([
            'user_id' => auth()->id(),
            'logs_action' => 'UPDATE',
            'logs_description' => 'Updated room name: ' . $room->room_name,
            'logs_module' => 'Room management',
        ]);
        return redirect()->route('deptHead.room.management')->with('success', 'Room updated successfully!');
    }

    // Delete a room
    public function destroy($id)
    {
        $room = Room::findOrFail($id);
        $roomNo = $room->room_no;
        $room->delete();

        // Track deletion for sync
        $syncService = app(CloudSyncService::class);
        $syncService->trackDeletion('tbl_room', $roomNo);
        
        // NEW APPROACH: Immediately trigger deletion on cloud
        try {
            $syncService->triggerDeleteOnCloudByTable('tbl_room', $roomNo);
        } catch (\Exception $e) {
            \Log::error("Failed to trigger room deletion on cloud: " . $e->getMessage());
        }

        ActivityLog::create([
            'user_id' => auth()->id(),
            'logs_action' => 'DELETE',
            'logs_description' => 'Deleted room name: ' . $room->room_name,
            'logs_module' => 'Room management',
        ]);
        return redirect()->route('deptHead.room.management')->with('success', 'Room deleted successfully!');
    }
}
