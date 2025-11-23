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
    protected $description = 'Sync data bidirectionally (deletions first, then parallel data sync for speed)';

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
        
        // Hybrid approach: Process deletions first, then run data syncs in parallel
        // This gives us both data consistency (deletions first) and speed (parallel data sync)
        return $this->runHybridSync($startTime);
    }
    
    /**
     * Run hybrid sync: Process deletions first, then run data syncs in parallel
     * This gives us both data consistency (deletions first) and speed (parallel data sync)
     */
    private function runHybridSync($startTime = null)
    {
        if ($startTime === null) {
            $startTime = microtime(true);
        }
        
        $this->info('🔄 Running hybrid sync (deletions first, then parallel data sync)...');
        $this->newLine();
        
        // STEP 1: Process deletions from cloud FIRST (quick operation)
        // This ensures deletions are processed before any data sync happens
        $this->info('🗑️  Step 1: Processing deletions from cloud (this happens FIRST)...');
        try {
            $deletionResults = $this->cloudSyncService->processAllDeletionsFromCloud();
            $totalDeleted = $deletionResults['total_deleted'] ?? 0;
            
            if ($totalDeleted > 0) {
                $this->info("✅ Deletions processed: {$totalDeleted} records deleted locally");
            } else {
                $this->info("✅ No deletions to process");
            }
        } catch (\Exception $e) {
            $this->error("❌ Deletion processing failed: {$e->getMessage()}");
            // Continue with sync even if deletion processing fails
        }
        
        $this->newLine();
        
        // STEP 2: Run data syncs in parallel (faster than sequential)
        $this->info('📤📥 Step 2: Running data syncs in parallel (cloud to local + local to cloud)...');
        
        // Use proc_open to run both syncs in parallel
        $descriptorspec = [
            0 => ['pipe', 'r'],  // stdin
            1 => ['pipe', 'w'],  // stdout
            2 => ['pipe', 'w']   // stderr
        ];
        
        // Start cloud-to-local sync process (data only, deletions already processed)
        $processFromCloud = proc_open(
            PHP_BINARY . ' ' . base_path('artisan') . ' sync:cloud --from-cloud 2>&1',
            $descriptorspec,
            $pipesFromCloud
        );
        
        // Start local-to-cloud sync process
        $processToCloud = proc_open(
            PHP_BINARY . ' ' . base_path('artisan') . ' sync:cloud 2>&1',
            $descriptorspec,
            $pipesToCloud
        );
        
        // Close stdin pipes (we don't need to write to them)
        if (isset($pipesFromCloud[0])) fclose($pipesFromCloud[0]);
        if (isset($pipesToCloud[0])) fclose($pipesToCloud[0]);
        
        // Read output from both processes
        $outputFromCloud = '';
        $errorFromCloud = '';
        $outputToCloud = '';
        $errorToCloud = '';
        
        // Set pipes to non-blocking mode
        if (isset($pipesFromCloud[1])) stream_set_blocking($pipesFromCloud[1], false);
        if (isset($pipesFromCloud[2])) stream_set_blocking($pipesFromCloud[2], false);
        if (isset($pipesToCloud[1])) stream_set_blocking($pipesToCloud[1], false);
        if (isset($pipesToCloud[2])) stream_set_blocking($pipesToCloud[2], false);
        
        // Wait for both processes to complete
        while (true) {
            // Check if processes are still running
            $statusFromCloud = proc_get_status($processFromCloud);
            $statusToCloud = proc_get_status($processToCloud);
            
            // Read available output from stdout
            if (isset($pipesFromCloud[1]) && !feof($pipesFromCloud[1])) {
                $chunk = fread($pipesFromCloud[1], 8192);
                if ($chunk !== false && $chunk !== '') {
                    $outputFromCloud .= $chunk;
                }
            }
            
            if (isset($pipesToCloud[1]) && !feof($pipesToCloud[1])) {
                $chunk = fread($pipesToCloud[1], 8192);
                if ($chunk !== false && $chunk !== '') {
                    $outputToCloud .= $chunk;
                }
            }
            
            // Read available output from stderr
            if (isset($pipesFromCloud[2]) && !feof($pipesFromCloud[2])) {
                $chunk = fread($pipesFromCloud[2], 8192);
                if ($chunk !== false && $chunk !== '') {
                    $errorFromCloud .= $chunk;
                }
            }
            
            if (isset($pipesToCloud[2]) && !feof($pipesToCloud[2])) {
                $chunk = fread($pipesToCloud[2], 8192);
                if ($chunk !== false && $chunk !== '') {
                    $errorToCloud .= $chunk;
                }
            }
            
            // If both processes have finished, break
            if ((!$statusFromCloud || !$statusFromCloud['running']) && 
                (!$statusToCloud || !$statusToCloud['running'])) {
                break;
            }
            
            // Small delay to prevent CPU spinning
            usleep(100000); // 0.1 second
        }
        
        // Read any remaining output
        if (isset($pipesFromCloud[1])) {
            $remaining = stream_get_contents($pipesFromCloud[1]);
            if ($remaining !== false) $outputFromCloud .= $remaining;
        }
        if (isset($pipesToCloud[1])) {
            $remaining = stream_get_contents($pipesToCloud[1]);
            if ($remaining !== false) $outputToCloud .= $remaining;
        }
        if (isset($pipesFromCloud[2])) {
            $remaining = stream_get_contents($pipesFromCloud[2]);
            if ($remaining !== false) $errorFromCloud .= $remaining;
        }
        if (isset($pipesToCloud[2])) {
            $remaining = stream_get_contents($pipesToCloud[2]);
            if ($remaining !== false) $errorToCloud .= $remaining;
        }
        
        // Close pipes
        if (isset($pipesFromCloud[1])) fclose($pipesFromCloud[1]);
        if (isset($pipesFromCloud[2])) fclose($pipesFromCloud[2]);
        if (isset($pipesToCloud[1])) fclose($pipesToCloud[1]);
        if (isset($pipesToCloud[2])) fclose($pipesToCloud[2]);
        
        // Get exit codes
        $exitCodeFromCloud = proc_close($processFromCloud);
        $exitCodeToCloud = proc_close($processToCloud);
        
        $endTime = microtime(true);
        $duration = round($endTime - $startTime, 2);
        
        // Display results
        $this->newLine();
        $this->info('📥 Cloud to Local Results:');
        if ($exitCodeFromCloud === 0) {
            $this->info('✅ Cloud to local completed successfully');
            // Try to extract summary from output
            if (preg_match('/Total records synced: (\d+)/', $outputFromCloud, $matches)) {
                $this->line("   Records synced: {$matches[1]}");
            }
            
            // Extract and display successfully synced tables
            $this->extractAndDisplaySyncedTables($outputFromCloud, 'Cloud to Local');
        } else {
            $this->error('❌ Cloud to local failed (exit code: ' . $exitCodeFromCloud . ')');
            if (!empty($errorFromCloud)) {
                $this->line('Errors:');
                $this->line($errorFromCloud);
            }
        }
        
        $this->newLine();
        $this->info('📤 Local to Cloud Results:');
        if ($exitCodeToCloud === 0) {
            $this->info('✅ Local to cloud completed successfully');
            // Try to extract summary from output
            if (preg_match('/Total records synced: (\d+)/', $outputToCloud, $matches)) {
                $this->line("   Records synced: {$matches[1]}");
            }
            
            // Extract and display successfully synced tables
            $this->extractAndDisplaySyncedTables($outputToCloud, 'Local to Cloud');
        } else {
            $this->error('❌ Local to cloud failed (exit code: ' . $exitCodeToCloud . ')');
            if (!empty($errorToCloud)) {
                $this->line('Errors:');
                $this->line($errorToCloud);
            }
        }
        
        $this->newLine();
        $this->info("⏱️  Total sync duration: {$duration} seconds");
        $this->info('✨ Bidirectional sync completed!');
        
        return Command::SUCCESS;
    }
    
    /**
     * Extract and display successfully synced tables from command output
     */
    private function extractAndDisplaySyncedTables($output, $direction)
    {
        // Look for the summary table in the output
        // Laravel's table() method outputs tables with pipes (|) separating columns
        // Format: "Data Type          | Records Synced"
        //         "Users              | 5"
        //         "Subjects           | 10"
        
        $syncedTables = [];
        $lines = explode("\n", $output);
        $inTableSection = false;
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Check if we're entering the summary section
            if (stripos($line, 'Summary:') !== false || stripos($line, '📊 Summary:') !== false) {
                $inTableSection = true;
                continue;
            }
            
            // If we're in the table section, look for table rows
            if ($inTableSection && strpos($line, '|') !== false) {
                // Split by pipe and clean up
                $parts = array_map('trim', explode('|', $line));
                
                if (count($parts) >= 2) {
                    $tableName = $parts[0];
                    $recordCount = $parts[1];
                    
                    // Skip header row and separator lines
                    if (strtolower($tableName) !== 'data type' && 
                        !empty($tableName) && 
                        !preg_match('/^[-+]+$/', $tableName)) {
                        
                        // Add table if it has a valid record count (including 0, as it still means sync was successful)
                        if (is_numeric($recordCount)) {
                            $syncedTables[] = $tableName;
                        }
                    }
                }
            }
            
            // Stop if we hit the "Total records synced" line or empty line after table
            if (stripos($line, 'Total records synced:') !== false || 
                (empty($line) && $inTableSection && !empty($syncedTables))) {
                break;
            }
        }
        
        // Alternative parsing: Use regex to find table rows if the above didn't work
        if (empty($syncedTables)) {
            // Look for patterns like: "Users | 5" or "Users              | 5"
            // This regex matches: word(s) followed by spaces and pipe, then spaces and number
            if (preg_match_all('/^\s*([A-Za-z][A-Za-z\s]+?)\s+\|\s+(\d+)\s*$/m', $output, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $tableName = trim($match[1]);
                    // Skip header
                    if (strtolower($tableName) !== 'data type' && !empty($tableName)) {
                        $syncedTables[] = $tableName;
                    }
                }
            }
        }
        
        // Display the successfully synced tables
        if (!empty($syncedTables)) {
            $this->line("   ✅ Successfully synced tables:");
            foreach ($syncedTables as $table) {
                $this->line("      • {$table}");
            }
        }
    }
    
    /**
     * Run sequential sync: Cloud to Local FIRST, then Local to Cloud
     * This ensures deletions from cloud are processed before syncing local data
     * (Fallback method - kept for reference)
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

