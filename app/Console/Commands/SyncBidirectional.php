<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CloudSyncService;

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
    protected $description = 'Sync data bidirectionally (local to cloud, then cloud to local)';

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
        
        // Step 1: Local to Cloud
        $this->info('📤 Step 1: Syncing local to cloud...');
        try {
            $results = $this->cloudSyncService->syncAllToCloud();
            
            if ($results['success']) {
                $totalSynced = array_sum($results['summary']);
                $this->info("✅ Local to cloud completed: {$totalSynced} records synced");
                
                // Show summary table
                $this->table(
                    ['Data Type', 'Records Synced'],
                    collect($results['summary'])->map(function ($count, $key) {
                        return [ucwords(str_replace('_', ' ', $key)), $count];
                    })->toArray()
                );
            } else {
                $this->warn('⚠️  Local to cloud completed with errors');
                if (!empty($results['errors'])) {
                    foreach ($results['errors'] as $error) {
                        $this->line("  - {$error}");
                    }
                }
            }
        } catch (\Exception $e) {
            $this->error("❌ Local to cloud failed: {$e->getMessage()}");
        }
        
        $this->newLine();
        
        // Step 2: Cloud to Local
        $this->info('📥 Step 2: Syncing cloud to local...');
        try {
            $results = $this->cloudSyncService->syncAllFromCloud();
            
            if ($results['success']) {
                $totalSynced = array_sum($results['summary']);
                $this->info("✅ Cloud to local completed: {$totalSynced} records synced");
                
                // Show summary table
                $this->table(
                    ['Data Type', 'Records Synced'],
                    collect($results['summary'])->map(function ($count, $key) {
                        return [ucwords(str_replace('_', ' ', $key)), $count];
                    })->toArray()
                );
            } else {
                $this->warn('⚠️  Cloud to local completed with errors');
                if (!empty($results['errors'])) {
                    foreach ($results['errors'] as $error) {
                        $this->line("  - {$error}");
                    }
                }
            }
        } catch (\Exception $e) {
            $this->error("❌ Cloud to local failed: {$e->getMessage()}");
        }
        
        $this->newLine();
        $this->info('✨ Bidirectional sync completed!');
        
        return Command::SUCCESS;
    }
}

