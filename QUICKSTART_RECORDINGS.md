# Quick Start - Stream Recordings

## ✅ Everything is Ready!

All setup is complete. Here's how to start using the stream recordings feature:

## Start the System (2 Steps)

### Step 1: Start Laravel
```bash
cd c:\Github\tcc-maem
php artisan serve
```

### Step 2: Start Python Service
```bash
cd c:\Github\tcc-maem\recognition
python service.py
```

**That's it!** Recording starts automatically.

---

## How to Know It's Working

You'll see in the Python console:
```
🎥 Starting recording for camera 1: camera_1_20251030_123456.mp4
✅ Recording completed for camera 1: 2700 frames, 15728640 bytes
✅ Recording saved to database: camera_1_20251030_123456.mp4
```

---

## Quick API Tests

### See all recordings:
```bash
curl http://127.0.0.1:8000/api/stream-recordings
```

### See recordings for camera 1:
```bash
curl http://127.0.0.1:8000/api/stream-recordings/camera/1
```

### Get statistics:
```bash
curl http://127.0.0.1:8000/api/stream-recordings/statistics
```

---

## Watch a Recording

After a few minutes, open in browser:
```
http://127.0.0.1:8000/storage/stream_recordings/camera_1_20251030_123456.mp4
```

(Replace with actual filename from API)

---

## What's Happening Automatically

- ✅ Every camera records continuously
- ✅ New 3-minute segment every 3 minutes
- ✅ Files saved to `storage/app/public/stream_recordings/`
- ✅ Metadata saved to database automatically
- ✅ Runs 24/7 in background

---

## Configuration

Default settings (in `recognition/service.py`):
- **Recording Duration**: 3 minutes per segment
- **Storage**: `storage/app/public/stream_recordings/`
- **Format**: MP4 (H.264)
- **Naming**: `camera_{id}_{timestamp}.mp4`

---

## Need More Info?

- 📚 **Full Setup Details**: See `STREAM_RECORDINGS_SETUP.md`
- 🧪 **Testing Guide**: See `TEST_STREAM_RECORDINGS.md`
- 📊 **Complete Summary**: See `SETUP_COMPLETE_SUMMARY.md`

---

## 🎉 Enjoy Your Automated Recording System!

