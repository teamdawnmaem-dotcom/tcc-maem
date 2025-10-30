<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CloudSyncService;

class SyncToCloud extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:cloud {--force : Force sync even if recently synced}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync local database to cloud server';

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
        $this->info('🚀 Starting cloud sync...');
        $this->newLine();
        
        try {
            $results = $this->cloudSyncService->syncAllToCloud();
            
            if ($results['success']) {
                $this->info('✅ Cloud sync completed successfully!');
                $this->newLine();
                
                $this->info('📊 Summary:');
                $this->table(
                    ['Data Type', 'Records Synced'],
                    collect($results['summary'])->map(function ($count, $key) {
                        return [ucwords(str_replace('_', ' ', $key)), $count];
                    })->toArray()
                );
                
                $totalSynced = array_sum($results['summary']);
                $this->newLine();
                $this->info("📈 Total records synced: {$totalSynced}");
                
                return Command::SUCCESS;
            } else {
                $this->error('⚠️  Cloud sync completed with errors');
                $this->newLine();
                
                if (!empty($results['errors'])) {
                    $this->error('Errors:');
                    foreach ($results['errors'] as $error) {
                        $this->line("  - {$error}");
                    }
                }
                
                return Command::FAILURE;
            }
            
        } catch (\Exception $e) {
            $this->error('❌ Cloud sync failed!');
            $this->error("Error: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }
}
