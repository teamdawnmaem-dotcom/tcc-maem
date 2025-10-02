<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RecognitionLog;
use Carbon\Carbon;

class RecognitionLogController extends Controller
{
    /**
     * Get recognition logs with optional filtering
     */
    public function index(Request $request)
    {
        $query = RecognitionLog::query();

        // Filter by date range
        if ($request->has('start_date') && $request->start_date) {
            $query->whereDate('recognition_time', '>=', $request->start_date);
        }
        if ($request->has('end_date') && $request->end_date) {
            $query->whereDate('recognition_time', '<=', $request->end_date);
        }

        // Filter by camera
        if ($request->has('camera_id') && $request->camera_id) {
            $query->where('camera_id', $request->camera_id);
        }

        // Filter by faculty
        if ($request->has('faculty_id') && $request->faculty_id) {
            $query->where('faculty_id', $request->faculty_id);
        }

        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Search by faculty name
        if ($request->has('search') && $request->search) {
            $query->where('faculty_name', 'like', '%' . $request->search . '%');
        }

        $logs = $query->orderBy('recognition_time', 'desc')->paginate(50);

        return response()->json([
            'success' => true,
            'data' => $logs
        ]);
    }

    /**
     * Store a new recognition log
     */
    public function store(Request $request)
    {
        $request->validate([
            'camera_name' => 'required|string|max:100',
            'room_name' => 'required|string|max:100',
            'building_no' => 'required|string|max:50',
            'faculty_name' => 'required|string|max:200',
            'status' => 'required|string|max:50',
            'distance' => 'nullable|numeric',
            'faculty_id' => 'nullable|integer',
            'camera_id' => 'nullable|integer',
            'teaching_load_id' => 'nullable|integer',
        ]);

        $log = RecognitionLog::create([
            'recognition_time' => $request->input('recognition_time', now()),
            'camera_name' => $request->camera_name,
            'room_name' => $request->room_name,
            'building_no' => $request->building_no,
            'faculty_name' => $request->faculty_name,
            'status' => $request->status,
            'distance' => $request->distance,
            'faculty_id' => $request->faculty_id,
            'camera_id' => $request->camera_id,
            'teaching_load_id' => $request->teaching_load_id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Recognition log created successfully',
            'data' => $log
        ], 201);
    }

    /**
     * Get recognition statistics
     */
    public function statistics(Request $request)
    {
        $query = RecognitionLog::query();

        // Filter by date range
        if ($request->has('start_date') && $request->start_date) {
            $query->whereDate('recognition_time', '>=', $request->start_date);
        }
        if ($request->has('end_date') && $request->end_date) {
            $query->whereDate('recognition_time', '<=', $request->end_date);
        }

        $stats = [
            'total_recognitions' => $query->count(),
            'successful_recognitions' => $query->where('status', 'recognized')->count(),
            'unknown_faces' => $query->where('status', 'unknown_face')->count(),
            'unique_faculty' => $query->whereNotNull('faculty_id')->distinct('faculty_id')->count(),
            'unique_cameras' => $query->whereNotNull('camera_id')->distinct('camera_id')->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}
