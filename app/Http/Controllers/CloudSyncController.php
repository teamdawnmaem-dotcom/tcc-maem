<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\CloudSyncService;
use Illuminate\Support\Facades\Log;

class CloudSyncController extends Controller
{
    protected $cloudSyncService;
    
    public function __construct(CloudSyncService $cloudSyncService)
    {
        $this->cloudSyncService = $cloudSyncService;
    }
    
    /**
     * Manual sync trigger
     */
    public function syncNow(Request $request)
    {
        try {
            Log::info('Manual cloud sync triggered by user');
            
            $results = $this->cloudSyncService->syncAllToCloud();
            
            if ($results['success']) {
                return response()->json([
                    'message' => 'Cloud sync completed successfully',
                    'summary' => $results['summary'],
                    'details' => $results['synced']
                ], 200);
            } else {
                return response()->json([
                    'message' => 'Cloud sync completed with errors',
                    'summary' => $results['summary'],
                    'errors' => $results['errors']
                ], 207); // 207 Multi-Status
            }
            
        } catch (\Exception $e) {
            Log::error('Cloud sync error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Cloud sync failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get sync status
     */
    public function status(Request $request)
    {
        try {
            $status = $this->cloudSyncService->getSyncStatus();
            return response()->json($status);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Show sync dashboard
     */
    public function index()
    {
        try {
            $status = $this->cloudSyncService->getSyncStatus();
            return view('admin.cloud-sync', compact('status'));
        } catch (\Exception $e) {
            return view('admin.cloud-sync', [
                'status' => ['status' => 'error', 'message' => $e->getMessage()]
            ]);
        }
    }
}
