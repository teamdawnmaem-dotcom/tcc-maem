# Cloud Sync Setup Guide

## Overview

This system syncs your local database to a cloud server, checking first if data already exists in the cloud and only pushing what's missing.

## Features

✅ **Smart Sync** - Only syncs data not already in cloud  
✅ **Batch Processing** - Syncs all tables in proper order  
✅ **File Upload** - Syncs images, videos, and documents  
✅ **Scheduled Sync** - Automatic syncing at intervals  
✅ **Manual Trigger** - Sync on demand via API or command  
✅ **Error Handling** - Continues syncing even if some items fail  
✅ **Logging** - Detailed logs of all sync operations  

## Components Created

### 1. CloudSyncService
**Path:** `app/Services/CloudSyncService.php`

Main service that handles all sync logic:
- Checks what data exists in cloud
- Syncs only missing data
- Uploads files to cloud storage
- Handles all data types

### 2. CloudSyncController
**Path:** `app/Http/Controllers/CloudSyncController.php`

Controller with endpoints:
- `syncNow()` - Trigger manual sync
- `status()` - Get sync status
- `index()` - Show sync dashboard

### 3. SyncToCloud Command
**Path:** `app/Console/Commands/SyncToCloud.php`

Artisan command for CLI syncing:
```bash
php artisan sync:cloud
php artisan sync:cloud --force
```

## Configuration

### Step 1: Environment Variables

Add these to your `.env` file:

```env
# Cloud Server Settings
CLOUD_API_URL=https://your-cloud-server.com/api
CLOUD_API_KEY=your-secret-api-key-here

# Optional: Cloud Storage Settings (if using AWS S3, Google Cloud, etc.)
CLOUD_STORAGE_DRIVER=s3
CLOUD_STORAGE_BUCKET=your-bucket-name
```

### Step 2: Add Routes

Add to `routes/web.php`:

```php
use App\Http\Controllers\CloudSyncController;

// Cloud Sync Routes (Admin only)
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/cloud-sync', [CloudSyncController::class, 'index'])->name('cloud-sync');
    Route::post('/cloud-sync/sync-now', [CloudSyncController::class, 'syncNow'])->name('cloud-sync.sync-now');
    Route::get('/cloud-sync/status', [CloudSyncController::class, 'status'])->name('cloud-sync.status');
});
```

Or add to `routes/api.php` for API access:

```php
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/cloud-sync/sync-now', [CloudSyncController::class, 'syncNow']);
    Route::get('/cloud-sync/status', [CloudSyncController::class, 'status']);
});
```

### Step 3: Schedule Automatic Sync

Edit `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // Sync to cloud every hour
    $schedule->command('sync:cloud')
             ->hourly()
             ->withoutOverlapping()
             ->runInBackground();
    
    // Or sync daily at 2 AM
    // $schedule->command('sync:cloud')->dailyAt('02:00');
    
    // Or sync every 6 hours
    // $schedule->command('sync:cloud')->everySixHours();
}
```

## How It Works

### Sync Process Flow

```
1. Fetch Local Data
   ↓
2. Get Cloud Data (IDs only)
   ↓
3. Compare Local vs Cloud
   ↓
4. Identify Missing Data
   ↓
5. Push Missing Data to Cloud
   ↓
6. Upload Associated Files
   ↓
7. Return Summary
```

### Data Synced (in order)

1. **Rooms** - Base location data
2. **Cameras** - Camera configurations
3. **Faculties** - Faculty info + images + embeddings
4. **Teaching Loads** - Schedule data
5. **Attendance Records** - Last 30 days
6. **Leaves** - Last 90 days + slip images
7. **Passes** - Last 90 days + slip images
8. **Recognition Logs** - Last 7 days
9. **Stream Recordings** - Last 7 days + video files

### Smart Filtering

- **Recent Data Only**: Only syncs recent records to save bandwidth
- **Deduplication**: Checks cloud before pushing
- **File Upload**: Only uploads files not already in cloud
- **Error Recovery**: Continues even if some items fail

## Usage

### Method 1: Command Line

```bash
# Sync now
php artisan sync:cloud

# Force sync (ignore last sync time)
php artisan sync:cloud --force
```

### Method 2: API Call

```bash
# Trigger sync
curl -X POST http://127.0.0.1:8000/api/cloud-sync/sync-now \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json"

# Check status
curl http://127.0.0.1:8000/api/cloud-sync/status \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Method 3: Web Interface

1. Go to `/cloud-sync` in your admin panel
2. Click "Sync Now" button
3. View progress and results

### Method 4: Programmatically

```php
use App\Services\CloudSyncService;

$cloudSync = new CloudSyncService();
$results = $cloudSync->syncAllToCloud();

if ($results['success']) {
    echo "Synced: " . json_encode($results['summary']);
} else {
    echo "Errors: " . json_encode($results['errors']);
}
```

## Response Format

### Success Response

```json
{
  "success": true,
  "synced": {
    "rooms": [1, 2, 3],
    "cameras": [1],
    "faculties": [1, 2, 3, 4, 5],
    "teaching_loads": [10, 11, 12],
    "attendance_records": [50, 51, 52, 53],
    "leaves": [],
    "passes": [1],
    "recognition_logs": [100, 101, 102],
    "stream_recordings": []
  },
  "summary": {
    "rooms": 3,
    "cameras": 1,
    "faculties": 5,
    "teaching_loads": 3,
    "attendance_records": 4,
    "leaves": 0,
    "passes": 1,
    "recognition_logs": 3,
    "stream_recordings": 0
  },
  "errors": []
}
```

### Error Response

```json
{
  "success": false,
  "synced": {
    "rooms": [1, 2],
    "cameras": []
  },
  "summary": {
    "rooms": 2,
    "cameras": 0
  },
  "errors": [
    "Failed to sync cameras: Connection timeout",
    "Failed to upload file: Storage quota exceeded"
  ]
}
```

## Cloud Server Requirements

Your cloud server must provide these API endpoints:

### GET Endpoints (Check existing data)

```
GET /api/rooms
GET /api/cameras
GET /api/faculties
GET /api/teaching-loads
GET /api/attendance-records?days=30
GET /api/leaves?days=90
GET /api/passes?days=90
GET /api/recognition-logs?days=7
GET /api/stream-recordings?days=7
GET /api/sync-status
```

**Response format:**
```json
[
  {"room_id": 1, ...},
  {"room_id": 2, ...},
  ...
]
```

### POST Endpoints (Receive new data)

```
POST /api/rooms
POST /api/cameras
POST /api/faculties
POST /api/teaching-loads
POST /api/attendance-records
POST /api/leaves
POST /api/passes
POST /api/recognition-logs
POST /api/stream-recordings
POST /api/upload/faculty_images
POST /api/upload/leave_slips
POST /api/upload/passes
POST /api/upload/stream_recordings
```

**Request format:**
```json
{
  "room_id": 1,
  "room_no": "101",
  "room_name": "Computer Lab 1",
  ...
}
```

### Authentication

All requests include header:
```
Authorization: Bearer YOUR_API_KEY
```

## Logging

Sync operations are logged to `storage/logs/laravel.log`:

```
[2025-10-30 18:00:00] local.INFO: Starting cloud sync...
[2025-10-30 18:00:05] local.INFO: Synced room 1 to cloud
[2025-10-30 18:00:05] local.INFO: Synced room 2 to cloud
[2025-10-30 18:00:10] local.INFO: Synced faculty 1 to cloud
[2025-10-30 18:00:15] local.INFO: Synced 20 attendance records to cloud
[2025-10-30 18:00:20] local.INFO: Cloud sync completed successfully
```

## Testing

### Test Cloud Connection

```bash
curl http://your-cloud-server.com/api/sync-status \
  -H "Authorization: Bearer YOUR_API_KEY"
```

Should return:
```json
{
  "status": "ok",
  "message": "Cloud server is ready",
  "last_sync": "2025-10-30 18:00:00"
}
```

### Test Sync Locally

```php
// In tinker (php artisan tinker)
$cloudSync = new App\Services\CloudSyncService();
$results = $cloudSync->syncAllToCloud();
dd($results);
```

## Troubleshooting

### Issue: "Cannot connect to cloud server"

**Solutions:**
- Check `CLOUD_API_URL` in `.env`
- Verify cloud server is online
- Check firewall rules
- Test with: `curl YOUR_CLOUD_URL/api/sync-status`

### Issue: "Authentication failed"

**Solutions:**
- Check `CLOUD_API_KEY` in `.env`
- Verify API key is correct
- Check cloud server auth configuration

### Issue: "File upload failed"

**Solutions:**
- Check cloud storage permissions
- Verify storage quota
- Check file size limits
- Ensure `multipart/form-data` is supported

### Issue: "Sync very slow"

**Solutions:**
- Reduce sync frequency
- Limit days for historical data
- Skip video file uploads (metadata only)
- Use background jobs (queues)

## Advanced: Using Queues

For large datasets, use queues for better performance:

```php
use Illuminate\Support\Facades\Queue;

// Dispatch sync job
dispatch(function () {
    $cloudSync = new CloudSyncService();
    $cloudSync->syncAllToCloud();
})->onQueue('cloud-sync');
```

## Security Considerations

1. **API Key Security**
   - Store API key in `.env`, never commit to git
   - Use long, random keys
   - Rotate keys periodically

2. **HTTPS Required**
   - Always use HTTPS for cloud API
   - Never send data over HTTP

3. **Rate Limiting**
   - Implement rate limiting on cloud server
   - Add delays between large uploads

4. **Data Privacy**
   - Encrypt sensitive data before upload
   - Ensure cloud server is compliant (GDPR, etc.)

## Cost Optimization

### Reduce Data Transfer

```php
// In CloudSyncService.php

// Skip video uploads (save bandwidth)
protected function syncStreamRecordings()
{
    // Comment out video upload section
    // $cloudUrl = $this->uploadFileToCloud($fullPath, 'stream_recordings');
    
    // Only sync metadata
    $data['video_cloud_url'] = null;
}
```

### Sync Only Essential Data

```php
// Only sync attendance records from last 7 days instead of 30
$localRecords = AttendanceRecord::where('created_at', '>=', now()->subDays(7))->get();
```

## Monitoring

### Check Last Sync

```sql
SELECT MAX(created_at) as last_local_record 
FROM tbl_attendance_record;
```

### View Sync Logs

```bash
tail -f storage/logs/laravel.log | grep "cloud sync"
```

### Create Monitoring Dashboard

Add to your admin panel:
- Last sync time
- Records synced count
- Sync status (success/failed)
- Next scheduled sync

## Summary

✅ **Service Created**: `CloudSyncService.php`  
✅ **Controller Created**: `CloudSyncController.php`  
✅ **Command Created**: `SyncToCloud.php`  
✅ **Supports**: 9 data types + files  
✅ **Smart**: Only syncs missing data  
✅ **Scheduled**: Can run automatically  
✅ **Flexible**: CLI, API, or web interface  

Configure your `.env`, set up routes, schedule the command, and you're ready to sync to the cloud! 🚀

