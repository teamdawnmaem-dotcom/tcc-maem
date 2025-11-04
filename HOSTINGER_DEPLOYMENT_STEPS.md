# Hostinger Deployment - Step by Step

## Overview

This guide walks you through deploying the TCC-MAEM system with cloud sync to Hostinger via GitHub.

## What's Already Done ✅

All the necessary files are already in this repository:

- ✅ `app/Http/Controllers/Api/SyncReceiverController.php` - Receives sync data on Hostinger
- ✅ `app/Http/Middleware/ApiKeyAuth.php` - API authentication
- ✅ `routes/api.php` - All sync endpoints configured
- ✅ `bootstrap/app.php` - Middleware registered
- ✅ `app/Services/CloudSyncService.php` - Sync logic for local
- ✅ `app/Http/Controllers/CloudSyncController.php` - Manual sync
- ✅ `app/Console/Commands/SyncToCloud.php` - Artisan command

## Deployment Steps

### Part 1: Push to GitHub

**On your local machine:**

```bash
cd C:\Github\tcc-maem

# Add all files
git add .

# Commit changes
git commit -m "Add cloud sync system for Hostinger"

# Push to GitHub
git push origin main
```

### Part 2: Deploy to Hostinger

**Option A: Using Hostinger Git Deployment (Recommended)**

1. **Login to Hostinger cPanel**
2. **Go to "Git™ Version Control"**
3. **Create/Update Deployment:**
   - Repository URL: `https://github.com/yourusername/tcc-maem.git`
   - Branch: `main`
   - Repository Path: Select your public_html folder
4. **Click "Pull" or "Deploy"**

**Option B: Manual via FTP**

1. Download repository as ZIP from GitHub
2. Upload via Hostinger File Manager
3. Extract in public_html directory

### Part 3: Configure Hostinger Environment

**Step 1: Update `.env` file on Hostinger**

Via Hostinger cPanel → File Manager → Edit `.env`:

```env
# Add these lines to your Hostinger .env

# API Key for sync authentication (MUST match local CLOUD_API_KEY)
API_KEY=e5a4466194f624d9e8611bd264a958e54473692ada6280840c118066f18e6815

# Database (your existing Hostinger MySQL settings)
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=u123456789_tccmaem
DB_USERNAME=u123456789_user
DB_PASSWORD=your-hostinger-db-password

# App settings
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com
```

**Step 2: Create `.user.ini` file**

In your public directory, create/edit `.user.ini`:

```ini
max_execution_time=300
max_input_time=300
memory_limit=256M
upload_max_filesize=100M
post_max_size=100M
```

Wait 5-10 minutes for changes to take effect.

**Step 3: Create Storage Directories**

Via Hostinger File Manager:

1. Navigate to `storage/app/public/`
2. Create new folder: `sync`
3. Inside `sync`, create folders:
   - `faculty_images`
   - `leave_slips`
   - `passes`
   - `stream_recordings`

**Step 4: Run Setup Commands**

Via Hostinger Terminal (if available) or SSH:

```bash
# Navigate to your project
cd ~/public_html

# Set permissions
chmod -R 755 storage
chmod -R 755 bootstrap/cache

# Create storage link
php artisan storage:link

# Run migrations (if needed)
php artisan migrate

# Clear caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Part 4: Test Hostinger Setup

**From your local machine**, test the connection:

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
    "teaching_loads": 0,
    "attendance_records": 0,
    "leaves": 0,
    "passes": 0,
    "recognition_logs": 0,
    "stream_recordings": 0
  },
  "timestamp": "2025-10-30 18:00:00"
}
```

✅ **If you see this response, Hostinger is ready to receive data!**

### Part 5: Configure Local Development Server

**On your local machine**, update `.env`:

```env
# Cloud Sync Configuration
CLOUD_API_URL=https://yourdomain.com/api
CLOUD_API_KEY=e5a4466194f624d9e8611bd264a958e54473692ada6280840c118066f18e6815
```

### Part 6: Run First Sync

**From your local machine:**

```bash
cd C:\Github\tcc-maem

# Run manual sync
php artisan sync:cloud
```

**Expected Output:**
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
│ Attendance Records │ 150            │
│ Leaves             │ 10             │
│ Passes             │ 5              │
│ Recognition Logs   │ 500            │
│ Stream Recordings  │ 20             │
└────────────────────┴────────────────┘

📈 Total records synced: 818
✅ Cloud sync completed successfully!
```

### Part 7: Verify Data on Hostinger

**Login to Hostinger cPanel → phpMyAdmin**

Check tables:
- `tbl_room` - Should have synced rooms
- `tbl_camera` - Should have synced cameras
- `tbl_faculty` - Should have synced faculties
- `tbl_attendance_record` - Should have synced attendance

✅ **Data is now in your Hostinger cloud database!**

### Part 8: Enable Automatic Sync (Optional)

**On Local Machine** - Edit `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // Sync to Hostinger daily at 3 AM
    $schedule->command('sync:cloud')
             ->dailyAt('03:00')
             ->withoutOverlapping();
}
```

**Start Scheduler:**
```bash
# Keep this running (or add to Windows Task Scheduler)
php artisan schedule:work
```

Or **add to Windows Task Scheduler**:
- Program: `C:\php\php.exe`
- Arguments: `C:\Github\tcc-maem\artisan schedule:run`
- Trigger: Every hour

## Troubleshooting

### Issue: "404 Not Found" when testing /api/sync-status

**Solutions:**
1. Check if `.htaccess` exists in public folder
2. Verify mod_rewrite is enabled on Hostinger
3. Check if app is in subdirectory (use `/subdirectory/api/sync-status`)
4. Clear route cache: `php artisan route:clear`

### Issue: "Unauthorized" response

**Solutions:**
1. Check `API_KEY` in Hostinger `.env` matches `CLOUD_API_KEY` in local `.env`
2. Ensure both use the same key: `e5a4466194f624d9e8611bd264a958e54473692ada6280840c118066f18e6815`
3. Clear config cache on Hostinger: `php artisan config:clear`

### Issue: "Connection timeout"

**Solutions:**
1. Check Hostinger URL is correct
2. Verify SSL certificate is valid (use `https://`)
3. Check Hostinger firewall settings
4. Increase timeout in `app/Services/CloudSyncService.php`:
   ```php
   $response = Http::timeout(60) // Increase from 30 to 60
   ```

### Issue: "File upload failed"

**Solutions:**
1. Check storage directories exist and have correct permissions (755)
2. Verify `.user.ini` settings are applied (wait 5-10 minutes)
3. Check Hostinger storage quota
4. Skip large files (videos) - see configuration in guide

### Issue: "Database error" on Hostinger

**Solutions:**
1. Check database credentials in Hostinger `.env`
2. Run migrations: `php artisan migrate`
3. Check database exists in Hostinger cPanel → MySQL Databases
4. Verify user has permissions

## Testing Checklist

- [ ] GitHub repository pushed
- [ ] Code deployed to Hostinger
- [ ] `.env` updated on Hostinger with `API_KEY`
- [ ] `.user.ini` created with increased limits
- [ ] Storage directories created
- [ ] `php artisan storage:link` executed
- [ ] Migrations run (if needed)
- [ ] `/api/sync-status` returns 200 OK
- [ ] Local `.env` updated with `CLOUD_API_URL` and `CLOUD_API_KEY`
- [ ] First sync completed successfully
- [ ] Data verified in Hostinger phpMyAdmin
- [ ] Automatic sync scheduled (optional)

## Quick Reference

### Local .env
```env
CLOUD_API_URL=https://yourdomain.com/api
CLOUD_API_KEY=e5a4466194f624d9e8611bd264a958e54473692ada6280840c118066f18e6815
```

### Hostinger .env
```env
API_KEY=e5a4466194f624d9e8611bd264a958e54473692ada6280840c118066f18e6815
```

### Test Connection
```bash
curl https://yourdomain.com/api/sync-status \
  -H "Authorization: Bearer e5a4466194f624d9e8611bd264a958e54473692ada6280840c118066f18e6815"
```

### Run Sync
```bash
php artisan sync:cloud
```

### Check Logs
```bash
# Local
tail -f storage/logs/laravel.log | grep "cloud sync"

# Hostinger (download via FTP)
# storage/logs/laravel.log
```

## Architecture Diagram

```
┌──────────────────────────────────────┐
│   Local Development Server           │
│   - Cameras connected                │
│   - Face recognition running         │
│   - Attendance data collected        │
│   - php artisan sync:cloud           │
└──────────────┬───────────────────────┘
               │
               │ HTTPS POST
               │ Authorization: Bearer API_KEY
               ▼
┌──────────────────────────────────────┐
│   Hostinger Cloud Server             │
│   - SyncReceiverController           │
│   - Validates API Key                │
│   - Stores in MySQL database         │
│   - Returns existing IDs             │
│   - Serves web interface             │
└──────────────────────────────────────┘
```

## Summary

1. ✅ Push code to GitHub
2. ✅ Deploy to Hostinger (Git/FTP)
3. ✅ Update Hostinger `.env` with `API_KEY`
4. ✅ Create storage directories
5. ✅ Run setup commands
6. ✅ Test connection with curl
7. ✅ Update local `.env` with Hostinger URL and key
8. ✅ Run `php artisan sync:cloud`
9. ✅ Verify data in Hostinger database
10. ✅ Schedule automatic sync

**All files are already in the repository - just push, deploy, configure, and sync!** 🚀

