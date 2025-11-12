<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CloudSyncService;
use Illuminate\Support\Facades\Log;

class SyncBidirectional extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:bidirectional';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync data bidirectionally (cloud to local first, then local to cloud)';

    protected $cloudSyncService;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(CloudSyncService $cloudSyncService)
    {
        parent::__construct();
        $this->cloudSyncService = $cloudSyncService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('🔄 Starting bidirectional sync...');
        $this->newLine();
        
        $startTime = microtime(true);
        
        // Run sequential sync: Cloud to Local FIRST, then Local to Cloud
        // This ensures deletions from cloud are processed before syncing local data
        return $this->runSequentialSync($startTime);
    }
    
    /**
     * Run sequential sync: Cloud to Local FIRST, then Local to Cloud
     * This ensures deletions from cloud are processed before syncing local data
     */
    private function runSequentialSync($startTime = null)
    {
        if ($startTime === null) {
            $startTime = microtime(true);
        }
        
        $this->info('🔄 Running sequential sync (Cloud to Local first, then Local to Cloud)...');
        $this->newLine();
        
        // Step 1: Cloud to Local FIRST
        // This processes deletions from cloud before syncing any local data
        $this->info('📥 Step 1: Syncing cloud to local (processes deletions first)...');
        try {
            $resultsFromCloud = $this->cloudSyncService->syncAllFromCloud();
            
            if ($resultsFromCloud['success']) {
                $totalSynced = array_sum($resultsFromCloud['summary']);
                
                // Show deletion summary if available
                if (isset($resultsFromCloud['deletion_summary']) && $resultsFromCloud['deletion_summary']['total_deleted'] > 0) {
                    $this->info("🗑️  Deletions processed: {$resultsFromCloud['deletion_summary']['total_deleted']} records deleted");
                }
                
                $this->info("✅ Cloud to local completed: {$totalSynced} records synced");
                
                // Show summary table
                $this->table(
                    ['Data Type', 'Records Synced'],
                    collect($resultsFromCloud['summary'])->map(function ($count, $key) {
                        return [ucwords(str_replace('_', ' ', $key)), $count];
                    })->toArray()
                );
            } else {
                $this->warn('⚠️  Cloud to local completed with errors');
                if (!empty($resultsFromCloud['errors'])) {
                    foreach ($resultsFromCloud['errors'] as $error) {
                        $this->line("  - {$error}");
                    }
                }
            }
        } catch (\Exception $e) {
            $this->error("❌ Cloud to local failed: {$e->getMessage()}");
        }
        
        $this->newLine();
        
        // Step 2: Local to Cloud
        // This syncs any new/updated local data to cloud
        $this->info('📤 Step 2: Syncing local to cloud...');
        try {
            $resultsToCloud = $this->cloudSyncService->syncAllToCloud();
            
            if ($resultsToCloud['success']) {
                $totalSynced = array_sum($resultsToCloud['summary']);
                $this->info("✅ Local to cloud completed: {$totalSynced} records synced");
                
                // Show summary table
                $this->table(
                    ['Data Type', 'Records Synced'],
                    collect($resultsToCloud['summary'])->map(function ($count, $key) {
                        return [ucwords(str_replace('_', ' ', $key)), $count];
                    })->toArray()
                );
            } else {
                $this->warn('⚠️  Local to cloud completed with errors');
                if (!empty($resultsToCloud['errors'])) {
                    foreach ($resultsToCloud['errors'] as $error) {
                        $this->line("  - {$error}");
                    }
                }
            }
        } catch (\Exception $e) {
            $this->error("❌ Local to cloud failed: {$e->getMessage()}");
        }
        
        $endTime = microtime(true);
        $duration = round($endTime - $startTime, 2);
        
        $this->newLine();
        $this->info("⏱️  Total sync duration: {$duration} seconds");
        $this->info('✨ Bidirectional sync completed!');
        
        return Command::SUCCESS;
    }
}

