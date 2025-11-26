<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Room;
use App\Models\TeachingLoad;
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

    // API endpoint to get schedules for a specific room
    public function apiRoomSchedules($roomNo)
    {
        $room = Room::findOrFail($roomNo);
        
        $schedules = TeachingLoad::with('faculty')
            ->where('room_no', $roomNo)
            ->get()
            ->map(function ($load) {
                return [
                    'teaching_load_id' => $load->teaching_load_id,
                    'teaching_load_course_code' => $load->teaching_load_course_code,
                    'teaching_load_subject' => $load->teaching_load_subject,
                    'teaching_load_class_section' => $load->teaching_load_class_section,
                    'teaching_load_day_of_week' => $load->teaching_load_day_of_week,
                    'teaching_load_time_in' => $load->teaching_load_time_in,
                    'teaching_load_time_out' => $load->teaching_load_time_out,
                    'faculty_id' => $load->faculty_id,
                    'faculty_name' => $load->faculty ? ($load->faculty->faculty_fname . ' ' . $load->faculty->faculty_lname) : 'N/A',
                ];
            })
            ->sortBy(function ($load) {
                // Define day order: Monday = 1, Tuesday = 2, ..., Sunday = 7
                $dayOrder = [
                    'Monday' => 1,
                    'Tuesday' => 2,
                    'Wednesday' => 3,
                    'Thursday' => 4,
                    'Friday' => 5,
                    'Saturday' => 6,
                    'Sunday' => 7
                ];
                
                $dayValue = $dayOrder[$load['teaching_load_day_of_week']] ?? 8; // Unknown days go last
                $timeValue = $load['teaching_load_time_in'];
                
                return [$dayValue, $timeValue];
            })
            ->values(); // Reset array keys

        return response()->json($schedules);
    }
}
