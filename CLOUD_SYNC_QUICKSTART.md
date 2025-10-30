# Cloud Sync - Quick Start Guide

Get your local database syncing to the cloud in 5 minutes! ⚡

## What Does This Do?

✅ Automatically syncs your local data to a cloud server  
✅ Only pushes data that doesn't already exist in the cloud  
✅ Handles files (images, videos, documents)  
✅ Can run automatically on a schedule or manually  
✅ Works with any cloud provider (AWS, Google Cloud, DigitalOcean, etc.)  

## Step 1: Configure Environment (2 minutes)

Add these lines to your `.env` file:

```env
# Your cloud server API URL
CLOUD_API_URL=https://your-cloud-server.com/api

# Your API key for authentication
CLOUD_API_KEY=your-secret-api-key-here
```

**Where to get these:**
- `CLOUD_API_URL`: The URL of your cloud server's API
- `CLOUD_API_KEY`: Generate a secure random key: `openssl rand -hex 32`

## Step 2: Test the Connection (1 minute)

```bash
# Test if your cloud server is reachable
curl https://your-cloud-server.com/api/sync-status \
  -H "Authorization: Bearer your-secret-api-key-here"
```

**Expected response:**
```json
{
  "status": "ok",
  "message": "Cloud server is ready"
}
```

## Step 3: Run Your First Sync (1 minute)

### Option A: Command Line
```bash
php artisan sync:cloud
```

### Option B: API Call
```bash
curl -X POST http://127.0.0.1:8000/api/cloud-sync/sync-now \
  -H "Content-Type: application/json"
```

### Option C: Web Interface
1. Login as admin
2. Go to `/admin/cloud-sync`
3. Click "Sync Now" button

## Step 4: Enable Automatic Sync (1 minute)

Edit `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // Sync to cloud every hour
    $schedule->command('sync:cloud')
             ->hourly()
             ->withoutOverlapping();
}
```

**Start the scheduler:**
```bash
php artisan schedule:work
```

Or add to cron (Linux/Mac):
```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

## Done! 🎉

Your data is now syncing to the cloud!

## What Gets Synced?

| Data Type | Time Range | Includes |
|-----------|------------|----------|
| Rooms | All | Room info |
| Cameras | All | Camera configs |
| Faculties | All | Info + images + embeddings |
| Teaching Loads | All | Schedule data |
| Attendance | Last 30 days | Records + status |
| Leaves | Last 90 days | Info + slip images |
| Passes | Last 90 days | Info + slip images |
| Recognition Logs | Last 7 days | Detection logs |
| Stream Recordings | Last 7 days | Metadata + videos |

## Common Commands

```bash
# Sync now
php artisan sync:cloud

# Force sync (ignore last sync time)
php artisan sync:cloud --force

# Check cloud status
curl http://127.0.0.1:8000/api/cloud-sync/status

# View logs
tail -f storage/logs/laravel.log | grep "cloud sync"
```

## Expected Output

```
🚀 Starting cloud sync...

📊 Summary:
┌────────────────────┬────────────────┐
│ Data Type          │ Records Synced │
├────────────────────┼────────────────┤
│ Rooms              │ 5              │
│ Cameras            │ 3              │
│ Faculties          │ 25             │
│ Teaching Loads     │ 100            │
│ Attendance Records │ 450            │
│ Leaves             │ 10             │
│ Passes             │ 5              │
│ Recognition Logs   │ 1200           │
│ Stream Recordings  │ 50             │
└────────────────────┴────────────────┘

📈 Total records synced: 1848

✅ Cloud sync completed successfully!
```

## Troubleshooting

### "Cannot connect to cloud server"
- Check `CLOUD_API_URL` in `.env`
- Verify cloud server is running
- Test: `curl https://your-cloud-server.com/api/sync-status`

### "Authentication failed"
- Check `CLOUD_API_KEY` in `.env`
- Verify key matches on cloud server

### "No data synced"
- Data might already exist in cloud
- Check logs: `tail -f storage/logs/laravel.log`

## Need Help?

📖 **Full Documentation:** See `CLOUD_SYNC_SETUP.md`  
🔧 **Cloud Server Setup:** See `CLOUD_SERVER_EXAMPLE.md`  
💬 **Support:** Check the logs in `storage/logs/laravel.log`  

---

**That's it!** Your local database is now backed up to the cloud. 🚀

