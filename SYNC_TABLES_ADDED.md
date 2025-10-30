# Cloud Sync - 5 New Tables Added

## Summary

Added sync support for 5 additional database tables, bringing the total from **9 to 14 synchronized tables**.

---

## ✅ **New Tables Added to Sync**

### 1. **`tbl_subject`** - Subjects
- **Primary Key:** `subject_id`
- **Sync Strategy:** All records
- **Use Case:** Reference data for teaching loads
- **Columns Synced:**
  - `subject_id`, `subject_code`, `subject_description`, `department`
  - `created_at`, `updated_at`

### 2. **`tbl_user`** - Users/Admin Accounts  
- **Primary Key:** `user_id`
- **Sync Strategy:** All records
- **Use Case:** Admin/user accounts for system access
- **Security:** Passwords are already hashed
- **Columns Synced:**
  - `user_id`, `user_role`, `user_department`
  - `user_fname`, `user_lname`, `username`, `user_password`
  - `created_at`, `updated_at`

### 3. **`tbl_activity_logs`** - Activity/Audit Logs
- **Primary Key:** `logs_id`
- **Sync Strategy:** Last 30 days only
- **Use Case:** Audit trail of user actions
- **Columns Synced:**
  - `logs_id`, `user_id`, `logs_action`, `logs_description`
  - `logs_timestamp`, `logs_module`

### 4. **`tbl_teaching_load_archive`** - Teaching Load Archives
- **Primary Key:** `archive_id`
- **Sync Strategy:** All archived records
- **Use Case:** Historical teaching loads (by semester/year)
- **Columns Synced:**
  - `archive_id`, `original_teaching_load_id`, `faculty_id`
  - `teaching_load_course_code`, `teaching_load_subject`, `teaching_load_class_section`
  - `teaching_load_day_of_week`, `teaching_load_time_in`, `teaching_load_time_out`
  - `room_no`, `school_year`, `semester`
  - `archived_at`, `archived_by`, `archive_notes`

### 5. **`tbl_attendance_record_archive`** - Attendance Record Archives
- **Primary Key:** `archive_id`
- **Sync Strategy:** All archived records
- **Use Case:** Historical attendance records (by semester/year)
- **Columns Synced:**
  - `archive_id`, `original_record_id`, `faculty_id`, `teaching_load_id`, `camera_id`
  - `record_date`, `record_time_in`, `record_time_out`, `time_duration_seconds`
  - `record_status`, `record_remarks`
  - `school_year`, `semester`
  - `archived_at`, `archived_by`, `archive_notes`
  - `created_at`, `updated_at`

---

## 📋 **Complete List of Synced Tables (14 Total)**

| # | Table Name | Primary Key | Sync Strategy |
|---|------------|-------------|---------------|
| 1 | `tbl_subject` | `subject_id` | ✅ **NEW** - All records |
| 2 | `tbl_user` | `user_id` | ✅ **NEW** - All records |
| 3 | `tbl_room` | `room_id` | All records |
| 4 | `tbl_camera` | `camera_id` | All records |
| 5 | `tbl_faculty` | `faculty_id` | All records |
| 6 | `tbl_teaching_load` | `teaching_load_id` | All records |
| 7 | `tbl_attendance_record` | `record_id` | Last 30 days |
| 8 | `tbl_leave_pass` (Leaves) | `lp_id` | Last 90 days |
| 9 | `tbl_leave_pass` (Passes) | `lp_id` | Last 90 days |
| 10 | `tbl_recognition_logs` | `log_id` | Last 7 days |
| 11 | `tbl_stream_recordings` | `recording_id` | Last 7 days |
| 12 | `tbl_activity_logs` | `logs_id` | ✅ **NEW** - Last 30 days |
| 13 | `tbl_teaching_load_archive` | `archive_id` | ✅ **NEW** - All records |
| 14 | `tbl_attendance_record_archive` | `archive_id` | ✅ **NEW** - All records |

---

## 🔧 **Files Modified**

### 1. `app/Services/CloudSyncService.php`
**Changes:**
- Added imports for 5 new models
- Added 5 new sync methods:
  - `syncSubjects()`
  - `syncUsers()`
  - `syncActivityLogs()`
  - `syncTeachingLoadArchives()`
  - `syncAttendanceRecordArchives()`
- Updated `syncAllToCloud()` to include all 14 sync calls

### 2. `app/Http/Controllers/Api/SyncReceiverController.php`
**Changes:**
- Added 10 new receiver endpoints (GET + POST for each table):
  - `getSubjects()` / `receiveSubject()`
  - `getUsers()` / `receiveUser()`
  - `getActivityLogs()` / `receiveActivityLog()`
  - `getTeachingLoadArchives()` / `receiveTeachingLoadArchive()`
  - `getAttendanceRecordArchives()` / `receiveAttendanceRecordArchive()`
- Updated `getSyncStatus()` to include counts for all 14 tables

### 3. `routes/api.php`
**Changes:**
- Added 10 new API routes for sync receiver endpoints
- Routes are protected by `api.key` middleware

---

## 🚀 **Deployment Steps for Hostinger**

### Step 1: Push to GitHub
```bash
git add .
git commit -m "Add sync support for 5 additional tables (subjects, users, activity logs, archives)"
git push origin staging
```

### Step 2: Pull on Hostinger
Via cPanel Terminal or SSH:
```bash
cd ~/public_html
git pull origin staging
```

### Step 3: Clear Cache (Important!)
```bash
php artisan config:clear
php artisan route:clear
php artisan cache:clear
```

### Step 4: Test Connection from Local
```bash
php artisan sync:cloud
```

Expected output - all 14 data types should now show in the summary table.

### Step 5: Verify on Hostinger
```bash
curl https://tcc-maem.com/api/sync-status \
  -H "Authorization: Bearer e5a4466194f624d9e8611bd264a958e54473692ada6280840c118066f18e6815"
```

Expected response should include all 14 counts:
```json
{
  "status": "ok",
  "counts": {
    "subjects": 0,
    "users": 0,
    "rooms": 0,
    "cameras": 0,
    "faculties": 6,
    "teaching_loads": 0,
    "attendance_records": 0,
    "leaves": 0,
    "passes": 0,
    "leave_pass_total": 0,
    "recognition_logs": 0,
    "stream_recordings": 0,
    "activity_logs": 0,
    "teaching_load_archives": 0,
    "attendance_record_archives": 0
  }
}
```

---

## ⚠️ **Tables Intentionally Excluded**

These Laravel system tables are **correctly excluded** from sync:

| Table | Reason |
|-------|--------|
| `users` | Laravel's default auth table (not used) |
| `password_reset_tokens` | Temporary tokens |
| `sessions` | Session data |
| `cache` | Temporary cache |
| `cache_locks` | Cache locks |
| `jobs` | Queue jobs |
| `job_batches` | Queue batches |
| `failed_jobs` | Failed queue jobs |

---

## 📊 **Testing the Sync**

### Check Local Data
```bash
# Check if you have data to sync
php artisan tinker
>>> App\Models\Subject::count();
>>> App\Models\User::count();
>>> App\Models\ActivityLog::count();
>>> App\Models\TeachingLoadArchive::count();
>>> App\Models\AttendanceRecordArchive::count();
```

### Run Full Sync
```bash
php artisan sync:cloud
```

### Check Sync Logs
```bash
tail -f storage/logs/laravel.log | grep -i sync
```

---

## ✅ **Complete!**

All 14 application tables are now synchronized between your local development server and Hostinger cloud server!

**Total Coverage:**
- ✅ **Reference Data:** Subjects, Rooms, Cameras
- ✅ **User Data:** Users (admins), Faculties
- ✅ **Operational Data:** Teaching Loads, Attendance Records, Leaves, Passes
- ✅ **Logs:** Recognition Logs, Stream Recordings, Activity Logs
- ✅ **Archives:** Teaching Load Archives, Attendance Record Archives

**Excluded (Correct):**
- ❌ Laravel system tables (cache, jobs, sessions, etc.)

