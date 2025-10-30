# 🔄 Automatic Cloud Sync - Every 10 Seconds

## Summary

The cloud sync system has been updated to:
1. ✅ **Sync ALL records** (no time limits)
2. ✅ **Auto-sync every 10 seconds** (continuous background sync)

---

## 📊 What Changed

### Before vs After

| Aspect | BEFORE | AFTER |
|--------|--------|-------|
| **Attendance Records** | Last 30 days | ✅ **ALL records** |
| **Leaves** | Last 90 days | ✅ **ALL records** |
| **Passes** | Last 90 days | ✅ **ALL records** |
| **Recognition Logs** | Last 7 days | ✅ **ALL records** |
| **Stream Recordings** | Last 7 days | ✅ **ALL records** |
| **Activity Logs** | Last 30 days | ✅ **ALL records** |
| **Sync Frequency** | Manual only | ✅ **Every 10 seconds (auto)** |

### All 14 Tables Now Sync Completely

✅ `tbl_subject` - ALL  
✅ `tbl_user` - ALL  
✅ `tbl_room` - ALL  
✅ `tbl_camera` - ALL  
✅ `tbl_faculty` - ALL  
✅ `tbl_teaching_load` - ALL  
✅ `tbl_attendance_record` - ALL *(was 30 days)*  
✅ `tbl_leave_pass` (Leaves) - ALL *(was 90 days)*  
✅ `tbl_leave_pass` (Passes) - ALL *(was 90 days)*  
✅ `tbl_recognition_logs` - ALL *(was 7 days)*  
✅ `tbl_stream_recordings` - ALL *(was 7 days)*  
✅ `tbl_activity_logs` - ALL *(was 30 days)*  
✅ `tbl_teaching_load_archive` - ALL  
✅ `tbl_attendance_record_archive` - ALL  

---

## 🚀 How to Run Auto-Sync

### Option 1: Using Batch File (Windows - Recommended)

**Double-click** `run-sync-scheduler.bat` or run from terminal:

```bash
run-sync-scheduler.bat
```

**Output:**
```
========================================
  Cloud Sync Scheduler - AUTO MODE
========================================

Started at: 10/30/2025 3:00:00 PM
Syncing every 10 seconds...
Press Ctrl+C to stop
========================================

Running scheduled command: Artisan::call("sync:cloud") ...
✅ Synced 6 faculties
...
(repeats every 10 seconds)
```

### Option 2: Using Shell Script (Linux/Mac)

Make it executable first:
```bash
chmod +x run-sync-scheduler.sh
./run-sync-scheduler.sh
```

### Option 3: Manual Scheduler (Background)

Run the Laravel scheduler in the background:

**Windows:**
```bash
start /B php artisan schedule:work
```

**Linux/Mac:**
```bash
php artisan schedule:work &
```

### Option 4: Manual One-Time Sync

If you just want to sync once (not continuously):
```bash
php artisan sync:cloud
```

---

## 🔧 Technical Details

### Files Modified

#### 1. `app/Services/CloudSyncService.php`
**Changes:**
- Removed all time filters (`where('date', '>=', ...)`)
- Changed to `Model::all()` for 6 tables:
  - `syncAttendanceRecords()`
  - `syncLeaves()`
  - `syncPasses()`
  - `syncRecognitionLogs()`
  - `syncStreamRecordings()`
  - `syncActivityLogs()`

**Example Before:**
```php
$localRecords = AttendanceRecord::where('record_date', '>=', now()->subDays(30))->get();
```

**Example After:**
```php
$localRecords = AttendanceRecord::all(); // ALL records
```

#### 2. `app/Http/Controllers/Api/SyncReceiverController.php`
**Changes:**
- Removed time filter parameters from 5 GET endpoints:
  - `getAttendanceRecords()`
  - `getLeaves()`
  - `getRecognitionLogs()`
  - `getStreamRecordings()`
  - `getActivityLogs()`

**Example Before:**
```php
$days = $request->get('days', 30);
$records = DB::table('tbl_attendance_record')
    ->where('created_at', '>=', now()->subDays($days))
    ->select('record_id')
    ->get();
```

**Example After:**
```php
$records = DB::table('tbl_attendance_record')
    ->select('record_id')
    ->get(); // ALL records
```

#### 3. `routes/console.php`
**Changes:**
- Added scheduler command that runs every 10 seconds:

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('sync:cloud')->everyTenSeconds();
```

#### 4. New Files Created

- **`run-sync-scheduler.bat`** - Windows auto-sync launcher
- **`run-sync-scheduler.sh`** - Linux/Mac auto-sync launcher
- **`AUTO_SYNC_SETUP.md`** - This documentation

---

## ⚙️ How the 10-Second Sync Works

### The Flow

```
1. Laravel Scheduler starts
   ↓
2. Every 10 seconds, executes: sync:cloud command
   ↓
3. CloudSyncService runs through all 14 tables
   ↓
4. For each table:
   - Fetches ALL local records
   - Checks which ones exist on cloud
   - Pushes only NEW records
   ↓
5. Logs results to storage/logs/laravel.log
   ↓
6. Waits 10 seconds
   ↓
7. Repeats from step 2
```

### Performance Considerations

**First Sync:**
- ⏱️ May take **several minutes** to sync all historical data
- 📊 Depends on database size

**Subsequent Syncs (every 10 seconds):**
- ⚡ Very fast (usually < 1 second)
- ✅ Only syncs NEW records created since last sync
- 🎯 Most cycles will sync 0 records (nothing new)

---

## 📋 Deployment Steps

### Step 1: Commit and Push to GitHub

```bash
git add .
git commit -m "Update cloud sync: Remove time limits + Add 10-second auto-sync"
git push origin staging
```

### Step 2: Deploy to Hostinger

**Pull the latest code:**
```bash
cd ~/public_html
git pull origin staging
```

**Clear cache:**
```bash
php artisan config:clear
php artisan route:clear
php artisan cache:clear
```

### Step 3: Test Manual Sync First

```bash
php artisan sync:cloud
```

Expected: All records should sync (not just recent ones).

### Step 4: Start Auto-Sync (Local Only)

**On your LOCAL development machine:**
```bash
# Windows
run-sync-scheduler.bat

# Linux/Mac
./run-sync-scheduler.sh
```

⚠️ **Important:** Run the scheduler on your LOCAL machine, NOT on Hostinger.

---

## ⚠️ Important Notes

### Why Run Scheduler Locally (Not on Hostinger)?

1. **Hostinger is the RECEIVER** (cloud storage)
2. **Your local machine is the SENDER** (source of truth)
3. The scheduler needs to run where the data is created

### Hostinger Limitations

- Shared hosting cron jobs typically have **1-minute minimum** interval
- Cannot run tasks every 10 seconds on Hostinger
- Hostinger is passive (receives data), not active (sends data)

### When to Use Each Method

| Scenario | Method |
|----------|--------|
| Development/Testing | Manual: `php artisan sync:cloud` |
| Continuous Background Sync | Auto: `run-sync-scheduler.bat` |
| Production Server (VPS/Dedicated) | Scheduler: `php artisan schedule:work` |
| Hostinger Deployment | N/A - Hostinger only receives |

---

## 🔍 Monitoring the Sync

### Watch Sync Activity in Real-Time

**Terminal 1 - Run Scheduler:**
```bash
run-sync-scheduler.bat
```

**Terminal 2 - Watch Logs:**
```bash
# Windows PowerShell
Get-Content storage/logs/laravel.log -Wait -Tail 50

# Linux/Mac
tail -f storage/logs/laravel.log
```

### Check Sync Status on Cloud

```bash
curl https://tcc-maem.com/api/sync-status \
  -H "Authorization: Bearer e5a4466194f624d9e8611bd264a958e54473692ada6280840c118066f18e6815"
```

---

## 🛑 How to Stop Auto-Sync

1. Press `Ctrl+C` in the terminal running the scheduler
2. Or close the command prompt window

---

## 📊 Expected Behavior

### First Run (Initial Sync)
```
🚀 Starting cloud sync...
✅ Synced 150 subjects
✅ Synced 5 users
✅ Synced 20 rooms
✅ Synced 10 cameras
✅ Synced 200 faculties
✅ Synced 1,500 teaching loads
✅ Synced 50,000 attendance records  ← Takes time
✅ Synced 500 leaves
✅ Synced 300 passes
✅ Synced 100,000 recognition logs   ← Takes time
✅ Synced 1,000 stream recordings
✅ Synced 10,000 activity logs
✅ Synced 500 teaching load archives
✅ Synced 5,000 attendance archives

📈 Total: 169,180 records synced
⏱️ Time: ~5-10 minutes
```

### Subsequent Runs (Every 10 Seconds)
```
🚀 Starting cloud sync...
✅ Synced 0 subjects
✅ Synced 0 users
✅ Synced 0 rooms
✅ Synced 0 cameras
✅ Synced 0 faculties
✅ Synced 0 teaching loads
✅ Synced 2 attendance records      ← New records only
✅ Synced 0 leaves
✅ Synced 0 passes
✅ Synced 15 recognition logs       ← New logs only
✅ Synced 0 stream recordings
✅ Synced 1 activity log
✅ Synced 0 teaching load archives
✅ Synced 0 attendance archives

📈 Total: 18 records synced
⏱️ Time: < 1 second
```

---

## ✅ Verification Checklist

After setup, verify:

- [ ] Manual sync works: `php artisan sync:cloud`
- [ ] All records sync (no time limits)
- [ ] Scheduler starts without errors
- [ ] Sync runs every 10 seconds
- [ ] Logs show sync activity
- [ ] Cloud server receives data
- [ ] New records auto-sync within 10 seconds

---

## 🎉 You're All Set!

Your cloud sync system now:
- ✅ Syncs ALL records (complete historical backup)
- ✅ Runs automatically every 10 seconds
- ✅ Keeps cloud always up-to-date
- ✅ Requires no manual intervention

**Just run `run-sync-scheduler.bat` and let it work in the background!**

