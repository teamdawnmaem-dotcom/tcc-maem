# Testing Stream Recordings System

## Prerequisites
✅ Database table created: `tbl_stream_recordings`
✅ Model created: `StreamRecording.php`
✅ Controller created: `StreamRecordingController.php`
✅ Routes registered in `routes/api.php`
✅ Storage directory created: `storage/app/public/stream_recordings`
✅ Python service configured to save recordings

## Registered API Routes

```
POST       api/stream-recordings ...................... Store new recording
GET        api/stream-recordings ...................... List all recordings
GET        api/stream-recordings/camera/{camera_id} .. Get recordings by camera
GET        api/stream-recordings/statistics ........... Get statistics
GET        api/stream-recordings/{id} ................. Get specific recording
DELETE     api/stream-recordings/{id} ................. Delete recording
```

## Testing Steps

### 1. Start the Laravel Application
```bash
cd c:\Github\tcc-maem
php artisan serve
```

### 2. Start the Python Recognition Service
```bash
cd c:\Github\tcc-maem\recognition
python service.py
```

When the service starts, it will:
- Fetch all cameras from the database
- Start recording threads for each camera
- Begin recording 3-minute segments continuously

### 3. Test API Endpoints

#### Get All Recordings
```bash
curl http://127.0.0.1:8000/api/stream-recordings
```

#### Get Recordings for Camera 1
```bash
curl http://127.0.0.1:8000/api/stream-recordings/camera/1
```

#### Get Recording Statistics
```bash
curl http://127.0.0.1:8000/api/stream-recordings/statistics
```

Expected response:
```json
{
    "total_recordings": 5,
    "total_size": 157286400,
    "total_duration": 900,
    "recordings_today": 5,
    "by_camera": [
        {
            "camera_id": 1,
            "count": 3,
            "total_size": 94371840,
            "camera": {
                "camera_id": 1,
                "room_no": "101",
                "room_name": "Conference Room"
            }
        }
    ]
}
```

#### Get Specific Recording
```bash
curl http://127.0.0.1:8000/api/stream-recordings/1
```

#### Delete a Recording
```bash
curl -X DELETE http://127.0.0.1:8000/api/stream-recordings/1
```

### 4. Manually Test Recording Save

If you want to manually test saving a recording (simulating what Python service does):

```bash
curl -X POST http://127.0.0.1:8000/api/stream-recordings \
  -H "Content-Type: application/json" \
  -d '{
    "camera_id": 1,
    "filename": "camera_1_20251030_123456.mp4",
    "filepath": "stream_recordings/camera_1_20251030_123456.mp4",
    "start_time": "2025-10-30 12:34:56",
    "duration": 180,
    "frames": 2700,
    "file_size": 15728640
  }'
```

Expected response:
```json
{
    "message": "Recording saved successfully",
    "recording_id": 1
}
```

## Verify Database

Check the database to see saved recordings:

```sql
SELECT * FROM tbl_stream_recordings ORDER BY start_time DESC;
```

## Check Storage Directory

List recorded files:
```bash
dir storage\app\public\stream_recordings
```

## Access Video Files

Once recordings are saved, access them via:
```
http://127.0.0.1:8000/storage/stream_recordings/camera_1_20251030_123456.mp4
```

## Monitoring

### Check Python Service Logs
When the Python service is running, you'll see logs like:
```
🎥 Starting recording for camera 1: camera_1_20251030_123456.mp4
✅ Recording completed for camera 1: 2700 frames, 15728640 bytes
✅ Recording saved to database: camera_1_20251030_123456.mp4
```

### Check Laravel Logs
```bash
tail -f storage/logs/laravel.log
```

Look for:
```
[timestamp] local.INFO: Stream recording saved: camera_1_20251030_123456.mp4 for camera 1
```

## Troubleshooting

### Recordings Not Being Saved?

1. Check if Python service is running
2. Check if cameras are in the database
3. Check Python service console for errors
4. Verify `STREAM_RECORDING_ENDPOINT` is correct in Python service
5. Check Laravel logs for API errors

### Can't Access Video Files?

1. Verify storage link exists: `public/storage` → `storage/app/public`
2. Check file permissions on `storage/app/public/stream_recordings`
3. Verify files are being saved to the correct directory

### Database Errors?

1. Verify migration was run: `php artisan migrate:status`
2. Check camera_id exists in `tbl_camera` table
3. Verify table structure matches the model

## Expected Behavior

✅ **Every 3 minutes**: Python service records a new segment for each camera
✅ **Automatically**: Metadata is sent to Laravel and saved in database
✅ **Continuously**: Recording runs 24/7 in background threads
✅ **Per Camera**: Each camera has its own recording thread
✅ **File Management**: Files are saved with timestamps for easy identification

## Performance Notes

- Each 3-minute recording at 15 FPS ≈ 2700 frames
- File size depends on resolution and codec
- Database stores metadata only (not video data)
- Actual video files are stored in filesystem
- Consider implementing cleanup for old recordings to manage disk space

## Next Steps (Optional)

### Implement Automatic Cleanup
Create a scheduled task to delete recordings older than X days:

```php
// In app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    $schedule->call(function () {
        $cutoffDate = now()->subDays(7); // Keep 7 days
        $oldRecordings = StreamRecording::where('created_at', '<', $cutoffDate)->get();
        
        foreach ($oldRecordings as $recording) {
            $fullPath = storage_path('app/public/' . $recording->filepath);
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
            $recording->delete();
        }
    })->daily();
}
```

### Create Admin Dashboard
Build a UI to:
- View all recordings
- Filter by camera and date
- Play videos directly in browser
- Download recordings
- Delete old recordings
- View storage statistics

