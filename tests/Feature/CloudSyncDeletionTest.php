<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\CloudSyncService;
use App\Models\OfficialMatter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CloudSyncDeletionTest extends TestCase
{
    use RefreshDatabase;

    protected $cloudApiUrl;
    protected $cloudApiKey;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Set test environment variables
        $this->cloudApiUrl = 'https://test-cloud-api.com/api';
        $this->cloudApiKey = 'test-api-key';
        
        // Set environment variables for the service using config
        config(['services.cloud_api_url' => $this->cloudApiUrl]);
        config(['services.cloud_api_key' => $this->cloudApiKey]);
        
        // Also set in environment for env() calls
        $_ENV['CLOUD_API_URL'] = $this->cloudApiUrl;
        $_ENV['CLOUD_API_KEY'] = $this->cloudApiKey;
        
        // Clear cache before each test
        Cache::flush();
    }

    /**
     * Test that deletion tracking stores the deletion in cache
     */
    public function test_deletion_tracking_stores_in_cache(): void
    {
        $service = new CloudSyncService();
        $tableName = 'tbl_official_matters';
        $recordId = 123;

        // Track a deletion
        $service->trackDeletion($tableName, $recordId);

        // Verify deletion is stored in cache
        $cacheKey = "sync_deletion:{$tableName}:{$recordId}";
        $this->assertTrue(Cache::has($cacheKey), 'Deletion should be stored in cache');

        // Verify deletion data
        $deletionData = Cache::get($cacheKey);
        $this->assertEquals($tableName, $deletionData['table']);
        $this->assertEquals($recordId, $deletionData['id']);
        $this->assertArrayHasKey('deleted_at', $deletionData);
        $this->assertArrayHasKey('expires_at', $deletionData);

        // Verify deletion is in the list
        $listKey = "sync_deletion_list:{$tableName}";
        $deletedIds = Cache::get($listKey, []);
        $this->assertContains($recordId, $deletedIds, 'Deletion ID should be in the list');
    }

    /**
     * Test that getDeletedIds returns tracked deletions
     */
    public function test_get_deleted_ids_returns_tracked_deletions(): void
    {
        $service = new CloudSyncService();
        $tableName = 'tbl_official_matters';
        
        // Track multiple deletions
        $service->trackDeletion($tableName, 1);
        $service->trackDeletion($tableName, 2);
        $service->trackDeletion($tableName, 3);

        // Use reflection to access protected method
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('getDeletedIds');
        $method->setAccessible(true);
        
        $deletedIds = $method->invoke($service, $tableName);

        $this->assertCount(3, $deletedIds);
        $this->assertContains(1, $deletedIds);
        $this->assertContains(2, $deletedIds);
        $this->assertContains(3, $deletedIds);
    }

    /**
     * Test that syncDeletionsToCloud sends deletions to cloud API
     */
    public function test_sync_deletions_to_cloud_sends_to_api(): void
    {
        // Use wildcard pattern to match any URL
        Http::fake([
            '*/sync/official-matters/deletions' => Http::response([
                'success' => true,
                'message' => 'Deletions processed'
            ], 200)
        ]);

        $service = new CloudSyncService();
        
        // Use reflection to access protected method
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('syncDeletionsToCloud');
        $method->setAccessible(true);
        
        $deletedIds = [1, 2, 3];
        $method->invoke($service, 'official-matters', $deletedIds);

        // Verify HTTP request was made - check for POST to deletions endpoint
        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/sync/official-matters/deletions') &&
                   $request->method() === 'POST' &&
                   isset($request->data()['deleted_ids']) &&
                   count($request->data()['deleted_ids']) === 3;
        });
    }

    /**
     * Test that getDeletedIdsFromCloud retrieves deletions from cloud
     */
    public function test_get_deleted_ids_from_cloud_retrieves_from_api(): void
    {
        $cloudDeletedIds = [10, 20, 30];
        
        Http::fake([
            '*/sync/official-matters/deletions' => Http::response([
                'deleted_ids' => $cloudDeletedIds
            ], 200)
        ]);

        $service = new CloudSyncService();
        
        // Use reflection to access protected method
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('getDeletedIdsFromCloud');
        $method->setAccessible(true);
        
        $deletedIds = $method->invoke($service, 'official-matters');

        $this->assertEquals($cloudDeletedIds, $deletedIds);
        
        // Verify HTTP request was made
        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/sync/official-matters/deletions') &&
                   $request->method() === 'GET';
        });
    }

    /**
     * Test that processDeletionsFromCloud deletes local records
     */
    public function test_process_deletions_from_cloud_deletes_local_records(): void
    {
        // Create test records in database
        DB::table('tbl_official_matters')->insert([
            'om_id' => 100,
            'om_purpose' => 'Test Purpose',
            'om_remarks' => 'Test Remarks',
            'om_start_date' => now(),
            'om_end_date' => now()->addDay(),
            'om_attachment' => 'test/attachment.jpg',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('tbl_official_matters')->insert([
            'om_id' => 200,
            'om_purpose' => 'Test Purpose 2',
            'om_remarks' => 'Test Remarks 2',
            'om_start_date' => now(),
            'om_end_date' => now()->addDay(),
            'om_attachment' => 'test/attachment2.jpg',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Mock cloud API to return deleted IDs
        Http::fake([
            '*/sync/official-matters/deletions' => Http::response([
                'deleted_ids' => [100, 300] // 100 exists, 300 doesn't
            ], 200)
        ]);

        $service = new CloudSyncService();
        
        // Use reflection to access protected method
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('processDeletionsFromCloud');
        $method->setAccessible(true);
        
        $method->invoke($service, 'official-matters', 'tbl_official_matters', 'om_id');

        // Verify record 100 was deleted
        $this->assertFalse(
            DB::table('tbl_official_matters')->where('om_id', 100)->exists(),
            'Record 100 should be deleted'
        );

        // Verify record 200 still exists (wasn't in deletion list)
        $this->assertTrue(
            DB::table('tbl_official_matters')->where('om_id', 200)->exists(),
            'Record 200 should still exist'
        );

        // Verify deletion was tracked locally
        $this->assertTrue(
            Cache::has('sync_deletion:tbl_official_matters:100'),
            'Deletion should be tracked locally'
        );
    }

    /**
     * Test that syncAllDeletionsToCloud syncs all tracked deletions
     */
    public function test_sync_all_deletions_to_cloud_syncs_all_tables(): void
    {
        $service = new CloudSyncService();
        
        // Track deletions for multiple tables
        $service->trackDeletion('tbl_official_matters', 1);
        $service->trackDeletion('tbl_user', 10);
        $service->trackDeletion('tbl_room', 'ROOM001');

        Http::fake([
            '*/sync/official-matters/deletions' => Http::response(['success' => true], 200),
            '*/sync/users/deletions' => Http::response(['success' => true], 200),
            '*/sync/rooms/deletions' => Http::response(['success' => true], 200),
        ]);

        // Use reflection to access protected method
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('syncAllDeletionsToCloud');
        $method->setAccessible(true);
        
        $method->invoke($service);

        // Verify all deletions were synced
        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/sync/official-matters/deletions');
        });
        
        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/sync/users/deletions');
        });
        
        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/sync/rooms/deletions');
        });
    }

    /**
     * Test that processAllDeletionsFromCloud processes deletions from all tables
     */
    public function test_process_all_deletions_from_cloud_processes_all_tables(): void
    {
        // Create test records
        DB::table('tbl_official_matters')->insert([
            'om_id' => 500,
            'om_purpose' => 'Test',
            'om_remarks' => 'Test',
            'om_start_date' => now(),
            'om_end_date' => now()->addDay(),
            'om_attachment' => 'test/attachment.jpg',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('tbl_user')->insert([
            'user_id' => 100,
            'user_role' => 'admin',
            'user_department' => 'IT',
            'user_fname' => 'Test',
            'user_lname' => 'User',
            'username' => 'testuser',
            'user_password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Http::fake([
            '*/sync/official-matters/deletions' => Http::response(['deleted_ids' => [500]], 200),
            '*/sync/users/deletions' => Http::response(['deleted_ids' => [100]], 200),
        ]);

        $service = new CloudSyncService();
        
        // Use reflection to access protected method
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('processAllDeletionsFromCloud');
        $method->setAccessible(true);
        
        $method->invoke($service);

        // Verify records were deleted
        $this->assertFalse(
            DB::table('tbl_official_matters')->where('om_id', 500)->exists(),
            'Official matter should be deleted'
        );

        $this->assertFalse(
            DB::table('tbl_user')->where('user_id', 100)->exists(),
            'User should be deleted'
        );
    }

    /**
     * Test that deletions tracked during sync are caught in final pass
     */
    public function test_deletions_during_sync_are_caught_in_final_pass(): void
    {
        $service = new CloudSyncService();
        
        // Track a deletion before sync
        $service->trackDeletion('tbl_official_matters', 1);
        
        // Mock that initial sync doesn't see this deletion (simulating race condition)
        Http::fake([
            '*/sync/official-matters/deletions' => Http::response(['success' => true], 200),
        ]);

        // Simulate sync process - track another deletion "during" sync
        $service->trackDeletion('tbl_official_matters', 2);
        $service->trackDeletion('tbl_official_matters', 3);

        // Call final deletion sync
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('syncAllDeletionsToCloud');
        $method->setAccessible(true);
        $method->invoke($service);

        // Verify all deletions (including those tracked during sync) were sent
        Http::assertSent(function ($request) use ($service) {
            if (str_contains($request->url(), '/sync/official-matters/deletions') && 
                $request->method() === 'POST') {
                $deletedIds = $request->data()['deleted_ids'] ?? [];
                // Should include all three deletions
                return count($deletedIds) >= 3 && 
                       in_array(1, $deletedIds) && 
                       in_array(2, $deletedIds) && 
                       in_array(3, $deletedIds);
            }
            return false;
        });
    }

    /**
     * Test that isDeletedLocally correctly identifies deleted records
     */
    public function test_is_deleted_locally_identifies_deleted_records(): void
    {
        $service = new CloudSyncService();
        $tableName = 'tbl_official_matters';
        
        // Track a deletion
        $service->trackDeletion($tableName, 999);

        // Use reflection to access protected method
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('isDeletedLocally');
        $method->setAccessible(true);
        
        // Should return true for deleted record
        $this->assertTrue(
            $method->invoke($service, $tableName, 999),
            'Should identify deleted record'
        );

        // Should return false for non-deleted record
        $this->assertFalse(
            $method->invoke($service, $tableName, 888),
            'Should not identify non-deleted record'
        );
    }

    /**
     * Test that leaves and passes are handled separately in deletion sync
     */
    public function test_leaves_and_passes_handled_separately(): void
    {
        $service = new CloudSyncService();
        
        // Track deletions with metadata
        $service->trackDeletion('tbl_leave_pass', 1, 90, ['lp_type' => 'Leave']);
        $service->trackDeletion('tbl_leave_pass', 2, 90, ['lp_type' => 'Pass']);
        $service->trackDeletion('tbl_leave_pass', 3, 90, ['lp_type' => 'Leave']);

        Http::fake([
            '*/sync/leaves/deletions' => Http::response(['success' => true], 200),
            '*/sync/passes/deletions' => Http::response(['success' => true], 200),
        ]);

        // Use reflection to access protected method
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('syncAllDeletionsToCloud');
        $method->setAccessible(true);
        $method->invoke($service);

        // Verify leaves deletion was sent with correct IDs
        Http::assertSent(function ($request) {
            if (str_contains($request->url(), '/sync/leaves/deletions') && 
                $request->method() === 'POST') {
                $deletedIds = $request->data()['deleted_ids'] ?? [];
                return in_array(1, $deletedIds) && 
                       in_array(3, $deletedIds) && 
                       !in_array(2, $deletedIds);
            }
            return false;
        });

        // Verify passes deletion was sent with correct IDs
        Http::assertSent(function ($request) {
            if (str_contains($request->url(), '/sync/passes/deletions') && 
                $request->method() === 'POST') {
                $deletedIds = $request->data()['deleted_ids'] ?? [];
                return in_array(2, $deletedIds) && 
                       !in_array(1, $deletedIds) && 
                       !in_array(3, $deletedIds);
            }
            return false;
        });
    }
}

