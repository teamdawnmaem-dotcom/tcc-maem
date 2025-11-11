# Deletion Tracking Guide

## Overview

The sync system now tracks deletions to prevent deleted records from being restored during bidirectional sync.

## How It Works

1. **When a record is deleted locally:**
   - The deletion is tracked in cache (expires after 90 days)
   - During sync, cloud records matching deleted IDs are skipped

2. **When a record is deleted in cloud:**
   - The deletion is synced to local
   - The record is deleted locally and tracked

## Integration in Controllers

### Example: Tracking Deletions

When deleting a record in a controller, call `trackDeletion()`:

```php
use App\Services\CloudSyncService;

public function destroy($id)
{
    $record = YourModel::findOrFail($id);
    $recordId = $record->your_id_field;
    
    // Delete the record
    $record->delete();
    
    // Track the deletion for sync
    $syncService = app(CloudSyncService::class);
    $syncService->trackDeletion('tbl_your_table', $recordId);
    
    // ... rest of your code
}
```

### Table Name Mapping

Use the correct table name when tracking deletions:

| Table Name | Example ID Field |
|------------|------------------|
| `tbl_user` | `user_id` |
| `tbl_subject` | `subject_id` |
| `tbl_room` | `room_no` |
| `tbl_camera` | `camera_id` |
| `tbl_faculty` | `faculty_id` |
| `tbl_teaching_load` | `teaching_load_id` |
| `tbl_attendance_record` | `record_id` |
| `tbl_leave_pass` | `lp_id` |
| `tbl_official_matters` | `om_id` |
| `tbl_recognition_logs` | `log_id` |
| `tbl_stream_recordings` | `recording_id` |
| `tbl_activity_logs` | `logs_id` |
| `tbl_teaching_load_archive` | `archive_id` |
| `tbl_attendance_record_archive` | `archive_id` |

## Complete Example

### OfficialMatterController

```php
use App\Services\CloudSyncService;

public function destroy($id)
{
    $officialMatter = OfficialMatter::findOrFail($id);
    $omId = $officialMatter->om_id;
    
    // ... existing deletion logic ...
    
    $officialMatter->delete();
    
    // Track deletion for sync
    $syncService = app(CloudSyncService::class);
    $syncService->trackDeletion('tbl_official_matters', $omId);
    
    // ... rest of your code ...
}
```

### FacultyController

```php
use App\Services\CloudSyncService;

public function destroy($id)
{
    $faculty = Faculty::findOrFail($id);
    $facultyId = $faculty->faculty_id;
    
    // ... existing deletion logic ...
    
    $faculty->delete();
    
    // Track deletion for sync
    $syncService = app(CloudSyncService::class);
    $syncService->trackDeletion('tbl_faculty', $facultyId);
    
    // ... rest of your code ...
}
```

## Automatic Deletion Processing

The sync system automatically:
- ✅ Checks for deletions from cloud and deletes them locally
- ✅ Skips syncing records that were deleted locally
- ✅ Tracks deletions for 90 days (configurable)

## Notes

- Deletions are tracked in cache (Laravel Cache)
- Default TTL is 90 days (can be changed per deletion)
- Deletions are automatically processed during bidirectional sync
- No manual intervention needed once integrated in controllers

