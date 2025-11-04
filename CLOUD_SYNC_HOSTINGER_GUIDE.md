 # Cloud Sync Setup for Hostinger

## Important Hostinger Considerations

Since you're using **Hostinger** as your cloud/live server, there are specific configurations and limitations to be aware of.

## Hostinger Environment

### Typical Hostinger Setup
- **Shared Hosting** - Resources shared with other users
- **cPanel Access** - File management via cPanel
- **MySQL Database** - Remote database access
- **PHP Version** - Usually 7.4 - 8.2
- **File Upload Limits** - Typically 128MB - 512MB
- **Execution Time Limits** - Usually 30-300 seconds
- **Storage Path** - `/home/u123456789/domains/yourdomain.com/public_html/`

## Setup Steps for Hostinger

### Step 1: Get Your Hostinger Details

Your `.env` should point to Hostinger:
 
```env
# Local .env Configuration

# Hostinger Cloud Server URL
CLOUD_API_URL=https://yourdomain.com/api
# or if in subdirectory:
# CLOUD_API_URL=https://yourdomain.com/tcc-maem/api

# Generate a secure API key
CLOUD_API_KEY=e5a4466194f624d9e8611bd264a958e54473692ada6280840c118066f18e6815

# Optional: If you want to test locally first
# CLOUD_API_URL=http://127.0.0.1:8000/api
```

**Generate API Key:**
```bash
# On your local machine
openssl rand -hex 32
# Output example: a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6
```

### Step 2: Hostinger Server Configuration

On your **Hostinger server**, add to `.env`:

```env
# Hostinger .env Configuration

# This API key must match your local CLOUD_API_KEY
API_KEY=a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6

# Database (Hostinger MySQL)
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=u123456789_tccmaem
DB_USERNAME=u123456789_tccuser
DB_PASSWORD=your-hostinger-db-password

# Increase limits for sync operations
MAX_EXECUTION_TIME=300
UPLOAD_MAX_FILESIZE=100M
POST_MAX_SIZE=100M
```

### Step 3: Adjust PHP Limits (Hostinger cPanel)

Hostinger shared hosting has strict limits. Create/edit `.user.ini` in your public directory:

```ini
; /public_html/.user.ini or /public/.user.ini

max_execution_time=300
max_input_time=300
memory_limit=256M
upload_max_filesize=100M
post_max_size=100M
```

**Note:** Changes take 5-10 minutes to apply on Hostinger.

### Step 4: Create API Routes on Hostinger

Your Hostinger server already has `routes/api.php`. The sync receiver endpoints are already set up! You just need to ensure authentication middleware is in place.

Create/update `app/Http/Middleware/ApiKeyAuth.php` on **Hostinger**:

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ApiKeyAuth
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $apiKey = $request->header('Authorization');
        $expectedKey = 'Bearer ' . env('API_KEY');
        
        if (!$apiKey || $apiKey !== $expectedKey) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Invalid or missing API key'
            ], 401);
        }
        
        return $next($request);
    }
}
```

Register middleware in `app/Http/Kernel.php` on **Hostinger**:

```php
protected $routeMiddleware = [
    // ... existing middleware
    'api.key' => \App\Http\Middleware\ApiKeyAuth::class,
];
```

### Step 5: Hostinger Storage Directories

Ensure storage directories exist on **Hostinger**:

```bash
# Via SSH (if you have SSH access)
cd /home/u123456789/domains/yourdomain.com/public_html
mkdir -p storage/app/public/sync/faculty_images
mkdir -p storage/app/public/sync/leave_slips
mkdir -p storage/app/public/sync/passes
mkdir -p storage/app/public/sync/stream_recordings
chmod -R 755 storage

# Create symbolic link
php artisan storage:link
```

Or via **cPanel File Manager**:
1. Navigate to `storage/app/public/`
2. Create folder `sync`
3. Inside `sync`, create: `faculty_images`, `leave_slips`, `passes`, `stream_recordings`

### Step 6: Test Hostinger Connection

From your **local machine**:

```bash
# Test if Hostinger server is reachable
curl https://yourdomain.com/api/sync-status \
  -H "Authorization: Bearer YOUR_API_KEY"
```

**Expected response:**
```json
{
  "status": "ok",
  "message": "Cloud server is ready"
}
```

If you get a 404, check:
- Is your Laravel app in a subdirectory? (e.g., `/tcc-maem`)
- Is `.htaccess` configured correctly?
- Is mod_rewrite enabled?

## Hostinger-Specific Challenges & Solutions

### Challenge 1: Execution Time Limits

**Problem:** Sync might timeout on large datasets

**Solution 1 - Batch Processing:**
```php
// On LOCAL machine: app/Services/CloudSyncService.php

protected function syncAttendanceRecords()
{
    $synced = [];
    
    // Reduce to last 7 days instead of 30 for Hostinger
    $localRecords = AttendanceRecord::where('created_at', '>=', now()->subDays(7))
                                   ->limit(100) // Add limit for batching
                                   ->get();
    
    // ... rest of the code
}
```

**Solution 2 - Use Queue (if available):**
```bash
# Check if Hostinger supports queues
php artisan queue:work --once
```

### Challenge 2: File Upload Size Limits

**Problem:** Large video files fail to upload

**Solution:** Skip video uploads, sync metadata only:

```php
// On LOCAL machine: app/Services/CloudSyncService.php

protected function syncStreamRecordings()
{
    // ... existing code ...
    
    // SKIP video upload for Hostinger
    // Comment out or remove:
    // $cloudUrl = $this->uploadFileToCloud($fullPath, 'stream_recordings');
    // $data['video_cloud_url'] = $cloudUrl;
    
    // Only sync metadata
    $data['video_cloud_url'] = null; // Metadata only
    
    $response = $this->pushToCloud('stream-recordings', $data);
}
```

### Challenge 3: Shared Hosting Resource Limits

**Problem:** Sync uses too much memory/CPU

**Solutions:**
1. **Reduce sync frequency** - Daily instead of hourly
2. **Sync during low-traffic hours** - 2-4 AM
3. **Use smaller batches** - Limit records per sync
4. **Skip large files** - Images only, no videos

```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    // Daily at 3 AM instead of hourly
    $schedule->command('sync:cloud')
             ->dailyAt('03:00')
             ->withoutOverlapping();
}
```

### Challenge 4: MySQL Remote Connection

**Problem:** Cannot connect to Hostinger MySQL remotely

**Solution:** Use Hostinger's API endpoints instead:

```php
// Your local sync connects via HTTP API (already implemented)
// NOT direct database connection
// This is why we built the API endpoints!
```

### Challenge 5: cPanel File Management

**Problem:** Cannot use SSH to manage files

**Solution:** Use Hostinger's cPanel:
1. **File Manager** - Upload/manage files
2. **phpMyAdmin** - Database management
3. **Terminal** (if available) - Run artisan commands
4. **Cron Jobs** - Schedule tasks

## Hostinger Cron Job Setup

### Enable Scheduled Sync on Hostinger

1. **Login to Hostinger cPanel**
2. **Find "Cron Jobs"** in Advanced section
3. **Add new cron job:**

```bash
# Run every hour
0 * * * * cd /home/u123456789/domains/yourdomain.com/public_html && php artisan schedule:run >> /dev/null 2>&1

# Or daily at 3 AM
0 3 * * * cd /home/u123456789/domains/yourdomain.com/public_html && php artisan schedule:run >> /dev/null 2>&1
```

**Important:** Replace `/home/u123456789/domains/yourdomain.com/public_html` with your actual path.

To find your path, create a PHP file `path.php` in public folder:
```php
<?php
echo __DIR__;
```
Visit: `https://yourdomain.com/path.php`

## Hostinger-Optimized Sync Configuration

Here's a recommended configuration for Hostinger's limitations:

```php
// app/Services/CloudSyncService.php

// Reduce time ranges for Hostinger
protected function syncAttendanceRecords()
{
    // 7 days instead of 30
    $localRecords = AttendanceRecord::where('created_at', '>=', now()->subDays(7))->get();
}

protected function syncLeaves()
{
    // 30 days instead of 90
    $localLeaves = Leave::where('created_at', '>=', now()->subDays(30))->get();
}

protected function syncPasses()
{
    // 30 days instead of 90
    $localPasses = Pass::where('created_at', '>=', now()->subDays(30))->get();
}

protected function syncRecognitionLogs()
{
    // 3 days instead of 7
    $localLogs = RecognitionLog::where('created_at', '>=', now()->subDays(3))->get();
}

protected function syncStreamRecordings()
{
    // Skip entirely or 1 day only
    // $localRecordings = StreamRecording::where('created_at', '>=', now()->subDays(1))->get();
    return []; // Skip for now
}
```

## Testing on Hostinger

### Test Receiving Data

```bash
# From your local machine
curl -X POST https://yourdomain.com/api/rooms \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "room_id": 999,
    "room_no": "TEST",
    "room_name": "Test Room",
    "room_building_no": "1",
    "room_floor_no": "1"
  }'
```

### Test File Upload

```bash
curl -X POST https://yourdomain.com/api/upload/faculty_images \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -F "file=@test-image.jpg"
```

### Test Full Sync

```bash
# From local machine
php artisan sync:cloud
```

## Hostinger Deployment Checklist

- [ ] Upload Laravel files to Hostinger via FTP/Git
- [ ] Configure `.env` with database credentials
- [ ] Add `API_KEY` to Hostinger `.env`
- [ ] Create `.user.ini` with increased limits
- [ ] Create storage directories via cPanel
- [ ] Run `php artisan storage:link` via Terminal
- [ ] Run `php artisan migrate` via Terminal
- [ ] Test API endpoint: `curl yourdomain.com/api/sync-status`
- [ ] Add cron job for scheduled sync
- [ ] Test sync from local to Hostinger

## Recommended Workflow

### Development → Hostinger Sync

```
┌─────────────────────────────────────────────────┐
│  Local Development Server                       │
│  - Develop features                             │
│  - Test locally                                 │
│  - Collect attendance data                      │
└──────────────────┬──────────────────────────────┘
                   │
                   │ Sync via API
                   ▼
┌─────────────────────────────────────────────────┐
│  Hostinger Production Server                    │
│  - Receive synced data                          │
│  - Store in MySQL database                      │
│  - Serve to web users                           │
│  - Generate reports                             │
└─────────────────────────────────────────────────┘
```

### Two-Way Sync (Optional)

If you want data to flow both ways:

1. **Local → Hostinger** - Attendance data, recognition logs
2. **Hostinger → Local** - Updated faculty info, teaching loads

This requires additional logic to prevent conflicts.

## Hostinger Limitations Summary

| Limitation | Typical Value | Workaround |
|------------|---------------|------------|
| Execution Time | 30-300s | Reduce batch sizes |
| Memory | 128-512MB | Process in chunks |
| Upload Size | 128-512MB | Skip large videos |
| Storage Space | 50-100GB | Regular cleanup |
| Database Connections | 10-100 | Use connection pooling |
| Cron Jobs | Limited | One master cron job |

## Alternative: Use Hostinger MySQL Directly

If Hostinger allows remote MySQL connections:

```env
# On LOCAL .env
CLOUD_DB_CONNECTION=mysql
CLOUD_DB_HOST=mysql.hostinger.com
CLOUD_DB_PORT=3306
CLOUD_DB_DATABASE=u123456789_tccmaem
CLOUD_DB_USERNAME=u123456789_user
CLOUD_DB_PASSWORD=password
```

But **API approach is recommended** for:
- Better security (API key vs database credentials)
- Easier to manage
- Works even if remote MySQL is disabled
- Can add validation/business logic

## Monitoring on Hostinger

### Check Logs via cPanel

1. **Error Logs** - cPanel → Metrics → Errors
2. **Laravel Logs** - Download `storage/logs/laravel.log` via FTP
3. **Database** - phpMyAdmin to check synced records

### Create Health Check

Create `public/health.php` on Hostinger:

```php
<?php
// Simple health check
header('Content-Type: application/json');

try {
    // Check database
    $pdo = new PDO(
        'mysql:host=localhost;dbname=u123456789_tccmaem',
        'u123456789_user',
        'password'
    );
    
    $stmt = $pdo->query('SELECT COUNT(*) FROM tbl_room');
    $count = $stmt->fetchColumn();
    
    echo json_encode([
        'status' => 'ok',
        'database' => 'connected',
        'rooms_count' => $count,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
```

Visit: `https://yourdomain.com/health.php`

## Files Included for Hostinger (Push via GitHub)

When you push this codebase to Hostinger via GitHub, these files are automatically included:

### ✅ Receiver Endpoints (Already Created)

1. **`app/Http/Controllers/Api/SyncReceiverController.php`**
   - Receives data from local server
   - Stores in Hostinger database
   - Returns existing IDs for comparison
   - All 9 data types supported

2. **`app/Http/Middleware/ApiKeyAuth.php`**
   - Authenticates incoming sync requests
   - Checks Authorization header
   - Validates API_KEY from .env

3. **`routes/api.php`** (Updated)
   - Sync receiver endpoints registered
   - Protected by `api.key` middleware
   - GET endpoints: Check existing data
   - POST endpoints: Receive new data

4. **`bootstrap/app.php`** (Updated)
   - Middleware alias registered: `'api.key' => ApiKeyAuth::class`

### 📋 Hostinger Setup After GitHub Push

After pushing to Hostinger via GitHub:

**Step 1: Update `.env` on Hostinger**
```env
# Add this to your Hostinger .env file
API_KEY=e5a4466194f624d9e8611bd264a958e54473692ada6280840c118066f18e6815
```

**Step 2: Create Storage Directories** (via cPanel File Manager)
```
storage/app/public/sync/
├── faculty_images/
├── leave_slips/
├── passes/
└── stream_recordings/
```

**Step 3: Run Migrations** (if needed - via Hostinger Terminal)
```bash
php artisan migrate
php artisan storage:link
```

**Step 4: Test the Connection** (from local machine)
```bash
curl https://yourdomain.com/api/sync-status \
  -H "Authorization: Bearer e5a4466194f624d9e8611bd264a958e54473692ada6280840c118066f18e6815"
```

**Expected Response:**
```json
{
  "status": "ok",
  "message": "Cloud server is ready",
  "server": "Hostinger",
  "counts": {
    "rooms": 0,
    "cameras": 0,
    "faculties": 0,
    ...
  },
  "timestamp": "2025-10-30 18:00:00"
}
```

**Step 5: Update Local `.env`** (on development machine)
```env
CLOUD_API_URL=https://yourdomain.com/api
CLOUD_API_KEY=e5a4466194f624d9e8611bd264a958e54473692ada6280840c118066f18e6815
```

**Step 6: Run First Sync** (from local machine)
```bash
php artisan sync:cloud
```

## Summary

For Hostinger specifically:

✅ **Use API-based sync** (not direct database connection)  
✅ **Reduce time ranges** (7 days instead of 30)  
✅ **Skip large files** (videos)  
✅ **Sync daily at 3 AM** (not hourly)  
✅ **Use `.user.ini`** to increase limits  
✅ **Set up cron job** via cPanel  
✅ **Monitor via logs** (download via FTP)  
✅ **Test with small batches first**  

The system I built is **perfect for Hostinger** because:
- Uses HTTP API (not direct DB)
- Checks for existing data first
- Handles timeouts gracefully
- Can be configured for small batches
- Logs all operations

### 🎉 All Files Included in GitHub Push

When you push to Hostinger, you get:
- ✅ `SyncReceiverController.php` - Receives sync data
- ✅ `ApiKeyAuth.php` - API authentication
- ✅ `routes/api.php` - All endpoints configured
- ✅ `bootstrap/app.php` - Middleware registered
- ✅ `CloudSyncService.php` - Sync logic (for local)
- ✅ `CloudSyncController.php` - Manual sync trigger
- ✅ `SyncToCloud.php` - Artisan command

**Just push to GitHub, pull on Hostinger, update `.env`, and sync!** 🚀

