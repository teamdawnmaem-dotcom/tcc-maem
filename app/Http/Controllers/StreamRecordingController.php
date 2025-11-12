<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\StreamRecording;
use App\Models\Camera;
use App\Services\CloudSyncService;

class StreamRecordingController extends Controller
{
    /**
     * Store a new stream recording from Python service
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'camera_id' => 'required|integer|exists:tbl_camera,camera_id',
                'filename' => 'required|string',
                'filepath' => 'required|string',
                'start_time' => 'required|date',
                'duration' => 'required|integer',
                'frames' => 'required|integer',
                'file_size' => 'required|integer',
            ]);
            
            $recording = StreamRecording::create($validated);
            
            \Log::info("Stream recording saved: {$validated['filename']} for camera {$validated['camera_id']}");
            
            return response()->json([
                'message' => 'Recording saved successfully',
                'recording_id' => $recording->recording_id
            ], 201);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error("Validation error saving recording: " . json_encode($e->errors()));
            return response()->json([
                'error' => 'Validation failed',
                'details' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error("Error saving stream recording: " . $e->getMessage());
            return response()->json([
                'error' => 'Failed to save recording',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get all recordings for a specific camera
     */
    public function getByCamera($camera_id)
    {
        try {
            $recordings = StreamRecording::where('camera_id', $camera_id)
                ->orderBy('start_time', 'desc')
                ->get();
            
            return response()->json($recordings);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch recordings',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get all recordings with pagination
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 50);
            $camera_id = $request->input('camera_id');
            
            $query = StreamRecording::with('camera')
                ->orderBy('start_time', 'desc');
            
            if ($camera_id) {
                $query->where('camera_id', $camera_id);
            }
            
            $recordings = $query->paginate($perPage);
            
            return response()->json($recordings);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch recordings',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get a specific recording
     */
    public function show($id)
    {
        try {
            $recording = StreamRecording::with('camera')->findOrFail($id);
            return response()->json($recording);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Recording not found',
                'message' => $e->getMessage()
            ], 404);
        }
    }
    
    /**
     * Stream/download a video file
     */
    public function stream($id)
    {
        try {
            $recording = StreamRecording::findOrFail($id);
            
            // Build full path to video file
            $fullPath = storage_path('app/public/' . $recording->filepath);
            
            // Alternative: try direct filename path
            if (!file_exists($fullPath)) {
                $fullPath = storage_path('app/public/stream_recordings/' . $recording->filename);
            }
            
            if (!file_exists($fullPath)) {
                return response()->json([
                    'error' => 'Video file not found',
                    'filepath' => $recording->filepath,
                    'filename' => $recording->filename
                ], 404);
            }
            
            // Get file size
            $fileSize = filesize($fullPath);
            
            // Determine MIME type
            $mimeType = 'video/mp4';
            
            // Set headers for video streaming
            $headers = [
                'Content-Type' => $mimeType,
                'Content-Length' => $fileSize,
                'Accept-Ranges' => 'bytes',
            ];
            
            // Handle range requests for video seeking
            if (request()->hasHeader('Range')) {
                $range = request()->header('Range');
                $range = str_replace('bytes=', '', $range);
                [$start, $end] = explode('-', $range);
                $start = intval($start);
                $end = $end ? intval($end) : $fileSize - 1;
                $length = $end - $start + 1;
                
                $headers['Content-Range'] = sprintf('bytes %d-%d/%d', $start, $end, $fileSize);
                $headers['Content-Length'] = $length;
                
                return response()->stream(function() use ($fullPath, $start, $length) {
                    $fp = fopen($fullPath, 'rb');
                    fseek($fp, $start);
                    echo fread($fp, $length);
                    fclose($fp);
                }, 206, $headers);
            }
            
            // Stream the entire file
            return response()->stream(function() use ($fullPath) {
                $fp = fopen($fullPath, 'rb');
                while (!feof($fp)) {
                    echo fread($fp, 8192); // 8KB chunks
                    flush();
                }
                fclose($fp);
            }, 200, $headers);
            
        } catch (\Exception $e) {
            \Log::error("Error streaming video: " . $e->getMessage());
            return response()->json([
                'error' => 'Failed to stream video',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Delete a recording
     */
    public function destroy($id)
    {
        try {
            $recording = StreamRecording::findOrFail($id);
            
            // Delete the physical file
            $fullPath = storage_path('app/public/' . $recording->filepath);
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
            
            // Delete the database record
            $recordingId = $recording->recording_id;
            $recording->delete();
            
            // Track deletion for sync
            $syncService = app(CloudSyncService::class);
            $syncService->trackDeletion('tbl_stream_recordings', $recordingId);
            
            // NEW APPROACH: Immediately trigger deletion on cloud
            try {
                $syncService->triggerDeleteOnCloudByTable('tbl_stream_recordings', $recordingId);
            } catch (\Exception $e) {
                \Log::error("Failed to trigger stream recording deletion on cloud: " . $e->getMessage());
            }
            
            return response()->json([
                'message' => 'Recording deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to delete recording',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get recording statistics
     */
    public function statistics()
    {
        try {
            $stats = [
                'total_recordings' => StreamRecording::count(),
                'total_size' => StreamRecording::sum('file_size'),
                'total_duration' => StreamRecording::sum('duration'),
                'recordings_today' => StreamRecording::whereDate('start_time', today())->count(),
                'by_camera' => StreamRecording::selectRaw('camera_id, COUNT(*) as count, SUM(file_size) as total_size')
                    ->groupBy('camera_id')
                    ->with('camera:camera_id,room_no,room_name')
                    ->get()
            ];
            
            return response()->json($stats);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch statistics',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
