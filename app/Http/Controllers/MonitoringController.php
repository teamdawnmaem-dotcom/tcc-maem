<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\RecognitionLog;

class MonitoringController extends Controller
{
    public function index()
    {
        return view('monitoring.index');
    }

    /**
     * POST /monitoring/poll-push?since_id=123
     * - Finds latest row by log_id
     * - If latest.log_id > since_id, pushes it to https://tcc-maem.com/api/logs
     * - Returns JSON with has_new, last_id, push status
     */
    public function pollAndPush(Request $request)
    {
        $sinceId = (int) $request->query('since_id', 0);

        $latest = RecognitionLog::orderByDesc('log_id')->first();
        if (!$latest) {
            return response()->json([
                'has_new' => false,
                'last_id' => $sinceId,
                'message' => 'No data yetyy.',
            ]);
        }

        if ($latest->log_id <= $sinceId) {
            return response()->json([
                'has_new' => false,
                'last_id' => $sinceId,
                'message' => 'No new data.',
            ]);
        }

        // Map to payload expected by public API
        $payload = [
            'recognition_time' => optional($latest->recognition_time)->toIso8601String(),
            'camera_name'      => $latest->camera_name,
            'room_name'        => $latest->room_name,
            'building_no'      => $latest->building_no,
            'faculty_name'     => $latest->faculty_name,
            'status'           => $latest->status,
            'distance'         => (string) $latest->distance, // preserve decimal(6)
            'faculty_id'       => $latest->faculty_id,
            'camera_id'        => $latest->camera_id,
            'teaching_load_id' => $latest->teaching_load_id,
        ];

        // Public API: simple JSON POST
        $resp = Http::timeout(10)
            ->retry(2, 250)
            ->acceptJson()
            ->asJson()
            ->post('https://tcc-maem.com/api/logs', $payload);

        return response()->json([
            'has_new'   => true,
            'last_id'   => $latest->log_id,
            'pushed_ok' => $resp->successful(),
            'status'    => $resp->status(),
            'payload'   => $payload,
        ], $resp->successful() ? 200 : 500);
    }
}
