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
    protected $signature = 'sync:cloud {--force : Force sync even if recently synced} {--from-cloud : Sync from cloud to local instead of local to cloud}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync database between local and cloud server';

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
        $fromCloud = $this->option('from-cloud');
        
        if ($fromCloud) {
            $this->info('🚀 Starting cloud to local sync...');
            $this->newLine();
            
            try {
                $results = $this->cloudSyncService->syncAllFromCloud();
                
                if ($results['success']) {
                    $this->info('✅ Cloud to local sync completed successfully!');
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
                    $this->error('⚠️  Cloud to local sync completed with errors');
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
                $this->error('❌ Cloud to local sync failed!');
                $this->error("Error: {$e->getMessage()}");
                return Command::FAILURE;
            }
        } else {
            $this->info('🚀 Starting local to cloud sync...');
            $this->newLine();
            
            try {
                $results = $this->cloudSyncService->syncAllToCloud();
                
                if ($results['success']) {
                    $this->info('✅ Local to cloud sync completed successfully!');
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
                    $this->error('⚠️  Local to cloud sync completed with errors');
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
                $this->error('❌ Local to cloud sync failed!');
                $this->error("Error: {$e->getMessage()}");
                return Command::FAILURE;
            }
        }
    }
}
