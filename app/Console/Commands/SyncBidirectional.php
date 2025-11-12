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
    protected $description = 'Sync data bidirectionally (local to cloud and cloud to local in parallel)';

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
        
        try {
            // NEW APPROACH: Deletions are now processed per table before each table's data sync
            // This ensures deletions are synced right before the data sync for each table
            // This prevents race conditions and ensures deletions are processed in the correct order
            $this->info('📤📥 Running data syncs in parallel (with per-table deletion processing)...');
            
            // Use proc_open to run both syncs in parallel
            $descriptorspec = [
                0 => ['pipe', 'r'],  // stdin
                1 => ['pipe', 'w'],  // stdout
                2 => ['pipe', 'w']   // stderr
            ];
            
            // Start local-to-cloud sync process
            $processToCloud = proc_open(
                PHP_BINARY . ' ' . base_path('artisan') . ' sync:cloud 2>&1',
                $descriptorspec,
                $pipesToCloud
            );
            
            // Start cloud-to-local sync process
            $processFromCloud = proc_open(
                PHP_BINARY . ' ' . base_path('artisan') . ' sync:cloud --from-cloud 2>&1',
                $descriptorspec,
                $pipesFromCloud
            );
            
            // Close stdin pipes (we don't need to write to them)
            if (isset($pipesToCloud[0])) fclose($pipesToCloud[0]);
            if (isset($pipesFromCloud[0])) fclose($pipesFromCloud[0]);
            
            // Read output from both processes
            $outputToCloud = '';
            $errorToCloud = '';
            $outputFromCloud = '';
            $errorFromCloud = '';
            
            // Set pipes to non-blocking mode
            if (isset($pipesToCloud[1])) stream_set_blocking($pipesToCloud[1], false);
            if (isset($pipesToCloud[2])) stream_set_blocking($pipesToCloud[2], false);
            if (isset($pipesFromCloud[1])) stream_set_blocking($pipesFromCloud[1], false);
            if (isset($pipesFromCloud[2])) stream_set_blocking($pipesFromCloud[2], false);
            
            // Wait for both processes to complete
            while (true) {
                // Check if processes are still running
                $statusToCloud = proc_get_status($processToCloud);
                $statusFromCloud = proc_get_status($processFromCloud);
                
                // Read available output from stdout
                if (isset($pipesToCloud[1]) && !feof($pipesToCloud[1])) {
                    $chunk = fread($pipesToCloud[1], 8192);
                    if ($chunk !== false && $chunk !== '') {
                        $outputToCloud .= $chunk;
                    }
                }
                
                if (isset($pipesFromCloud[1]) && !feof($pipesFromCloud[1])) {
                    $chunk = fread($pipesFromCloud[1], 8192);
                    if ($chunk !== false && $chunk !== '') {
                        $outputFromCloud .= $chunk;
                    }
                }
                
                // Read available output from stderr
                if (isset($pipesToCloud[2]) && !feof($pipesToCloud[2])) {
                    $chunk = fread($pipesToCloud[2], 8192);
                    if ($chunk !== false && $chunk !== '') {
                        $errorToCloud .= $chunk;
                    }
                }
                
                if (isset($pipesFromCloud[2]) && !feof($pipesFromCloud[2])) {
                    $chunk = fread($pipesFromCloud[2], 8192);
                    if ($chunk !== false && $chunk !== '') {
                        $errorFromCloud .= $chunk;
                    }
                }
                
                // If both processes have finished, break
                if ((!$statusToCloud || !$statusToCloud['running']) && 
                    (!$statusFromCloud || !$statusFromCloud['running'])) {
                    break;
                }
                
                // Small delay to prevent CPU spinning
                usleep(100000); // 0.1 second
            }
            
            // Read any remaining output
            if (isset($pipesToCloud[1])) {
                $remaining = stream_get_contents($pipesToCloud[1]);
                if ($remaining !== false) $outputToCloud .= $remaining;
            }
            if (isset($pipesFromCloud[1])) {
                $remaining = stream_get_contents($pipesFromCloud[1]);
                if ($remaining !== false) $outputFromCloud .= $remaining;
            }
            if (isset($pipesToCloud[2])) {
                $remaining = stream_get_contents($pipesToCloud[2]);
                if ($remaining !== false) $errorToCloud .= $remaining;
            }
            if (isset($pipesFromCloud[2])) {
                $remaining = stream_get_contents($pipesFromCloud[2]);
                if ($remaining !== false) $errorFromCloud .= $remaining;
            }
            
            // Close pipes
            if (isset($pipesToCloud[1])) fclose($pipesToCloud[1]);
            if (isset($pipesToCloud[2])) fclose($pipesToCloud[2]);
            if (isset($pipesFromCloud[1])) fclose($pipesFromCloud[1]);
            if (isset($pipesFromCloud[2])) fclose($pipesFromCloud[2]);
            
            // Get exit codes
            $exitCodeToCloud = proc_close($processToCloud);
            $exitCodeFromCloud = proc_close($processFromCloud);
            
            $endTime = microtime(true);
            $duration = round($endTime - $startTime, 2);
            
            // Display results
            $this->newLine();
            $this->info('📤 Local to Cloud Results:');
            if ($exitCodeToCloud === 0) {
                $this->info('✅ Local to cloud completed successfully');
            } else {
                $this->error('❌ Local to cloud failed (exit code: ' . $exitCodeToCloud . ')');
                if (!empty($outputToCloud)) {
                    $this->line('Output:');
                    $this->line($outputToCloud);
                }
                if (!empty($errorToCloud)) {
                    $this->line('Errors:');
                    $this->line($errorToCloud);
                }
            }
            
            $this->newLine();
            $this->info('📥 Cloud to Local Results:');
            if ($exitCodeFromCloud === 0) {
                $this->info('✅ Cloud to local completed successfully');
            } else {
                $this->error('❌ Cloud to local failed (exit code: ' . $exitCodeFromCloud . ')');
                if (!empty($outputFromCloud)) {
                    $this->line('Output:');
                    $this->line($outputFromCloud);
                }
                if (!empty($errorFromCloud)) {
                    $this->line('Errors:');
                    $this->line($errorFromCloud);
                }
            }
            
            $this->newLine();
            $this->info("⏱️  Total sync duration: {$duration} seconds");
            $this->info('✨ Bidirectional sync completed!');
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            // Fallback to sequential sync if parallel execution fails
            $this->warn('⚠️  Parallel execution failed, falling back to sequential sync...');
            $this->warn("Error: {$e->getMessage()}");
            $this->newLine();
            
            return $this->runSequentialSync();
        }
    }
    
    /**
     * Fallback to sequential sync if parallel execution fails
     */
    private function runSequentialSync()
    {
        $this->info('🔄 Running sequential sync (fallback mode)...');
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

