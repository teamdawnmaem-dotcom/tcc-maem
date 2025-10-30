# Stream Recordings Database Setup

## Overview
This document describes the database setup for storing stream recording metadata in the TCC-MAEM system.

## Database Table: `tbl_stream_recordings`

### Schema
- **recording_id** (Primary Key) - Auto-incrementing ID
- **camera_id** (Foreign Key) - References `tbl_camera.camera_id`
- **filename** - Name of the recording file
- **filepath** - Relative path to the recording file
- **start_time** - When the recording started (datetime)
- **duration** - Length of recording in seconds (integer)
- **frames** - Total number of frames captured (integer)
- **file_size** - Size of the recording file in bytes (bigint)
- **created_at** - Record creation timestamp
- **updated_at** - Record last update timestamp

### Indexes
- `camera_id` - For faster queries by camera
- `start_time` - For faster queries by time range

### Foreign Keys
- `camera_id` references `tbl_camera.camera_id` with CASCADE on delete

## Files Created

### 1. Migration File
**Path:** `database/migrations/2025_10_30_093456_create_tbl_stream_recordings_table.php`
- Creates the `tbl_stream_recordings` table
- Sets up foreign key constraints and indexes

### 2. Model
**Path:** `app/Models/StreamRecording.php`
- Eloquent model for stream recordings
- Includes relationship to Camera model
- Helper methods:
  - `getFormattedFileSizeAttribute()` - Returns human-readable file size (B, KB, MB, GB)
  - `getFormattedDurationAttribute()` - Returns formatted duration (MM:SS)

### 3. Controller
**Path:** `app/Http/Controllers/StreamRecordingController.php`
- Handles all stream recording operations

#### API Endpoints

##### Store Recording (POST)
```php
POST /api/stream-recordings
Body: {
    "camera_id": 1,
    "filename": "recording_20251030_123456.mp4",
    "filepath": "stream_recordings/recording_20251030_123456.mp4",
    "start_time": "2025-10-30 12:34:56",
    "duration": 120,
    "frames": 3600,
    "file_size": 15728640
}
```

##### Get All Recordings (GET)
```php
GET /api/stream-recordings?per_page=50&camera_id=1
```

##### Get Recording by ID (GET)
```php
GET /api/stream-recordings/{id}
```

##### Get Recordings by Camera (GET)
```php
GET /api/stream-recordings/camera/{camera_id}
```

##### Delete Recording (DELETE)
```php
DELETE /api/stream-recordings/{id}
```

##### Get Statistics (GET)
```php
GET /api/stream-recordings/statistics
```

## Usage from Python Service

### Example: Save Recording Metadata

```python
import requests
from datetime import datetime

# After saving a recording file, send metadata to Laravel
recording_data = {
    "camera_id": camera_id,
    "filename": filename,
    "filepath": f"stream_recordings/{filename}",
    "start_time": start_time.strftime("%Y-%m-%d %H:%M:%S"),
    "duration": duration_seconds,
    "frames": total_frames,
    "file_size": os.path.getsize(filepath)
}

response = requests.post(
    f"{API_BASE}/api/stream-recordings",
    json=recording_data
)

if response.status_code == 201:
    print(f"Recording metadata saved: {response.json()}")
else:
    print(f"Error saving recording: {response.text}")
```

## ✅ Setup Complete!

All next steps have been completed:

### 1. ✅ Routes Added (in `routes/api.php`)
The following routes have been added and are now active:
```php
use App\Http\Controllers\StreamRecordingController;

Route::post('/stream-recordings', [StreamRecordingController::class, 'store']);
Route::get('/stream-recordings', [StreamRecordingController::class, 'index']);
Route::get('/stream-recordings/statistics', [StreamRecordingController::class, 'statistics']);
Route::get('/stream-recordings/camera/{camera_id}', [StreamRecordingController::class, 'getByCamera']);
Route::get('/stream-recordings/{id}', [StreamRecordingController::class, 'show']);
Route::delete('/stream-recordings/{id}', [StreamRecordingController::class, 'destroy']);
```

### 2. ✅ Storage Directory Created
The storage directory has been created at:
- `storage/app/public/stream_recordings`
- Symbolic link already exists: `public/storage` → `storage/app/public`

### 3. ✅ Python Service Already Configured
The Python service (`recognition/service.py`) already includes:
- ✅ Stream recording functionality
- ✅ Automatic 3-minute segment recording for all cameras
- ✅ Metadata sent to Laravel API after each recording
- ✅ Continuous recording in background threads
- ✅ Recording starts automatically when service starts
- ✅ Updated to use `stream_recordings` directory

## How It Works

The stream recording system operates automatically:

1. **Service Startup**: When `recognition/service.py` starts, it automatically begins recording all cameras
2. **Continuous Recording**: Each camera records in 3-minute segments continuously
3. **Background Threads**: Each camera has its own dedicated recording thread
4. **Automatic Saving**: After each segment is recorded:
   - Video file is saved to `storage/app/public/stream_recordings/`
   - Metadata is automatically sent to Laravel API
   - Database record is created with all recording details
5. **File Naming**: Files are named as `camera_{camera_id}_{timestamp}.mp4`

## Recording Settings (in service.py)

```python
RECORDING_INTERVAL = 180  # 3 minutes in seconds
RECORDING_STORAGE_PATH = "../storage/app/public/stream_recordings"
STREAM_RECORDING_ENDPOINT = "http://127.0.0.1:8000/api/stream-recordings"
```

## Viewing Recordings

### Get All Recordings
```bash
curl http://127.0.0.1:8000/api/stream-recordings
```

### Get Recordings for Specific Camera
```bash
curl http://127.0.0.1:8000/api/stream-recordings/camera/1
```

### Get Recording Statistics
```bash
curl http://127.0.0.1:8000/api/stream-recordings/statistics
```

### Access Video File
Recordings are accessible via:
```
http://127.0.0.1:8000/storage/stream_recordings/camera_1_20251030_123456.mp4
```

## Notes
- ✅ The migration has been successfully run and the table is created
- ✅ Foreign key ensures recordings are deleted when a camera is deleted
- ✅ File paths are stored relative to `storage/app/public/`
- ✅ Actual video files are stored in `storage/app/public/stream_recordings/`
- ✅ Recording starts automatically when the Python service starts
- ✅ Each camera records continuously in 3-minute segments
- ✅ All metadata is automatically saved to the database

