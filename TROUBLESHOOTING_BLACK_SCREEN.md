# Troubleshooting Black Screen Issue

## Quick Diagnosis Steps

### Step 1: Test RTSP Connection
Run the test script to check if your camera connection is working:

```bash
cd C:\Github\tcc-maem\recognition
python test_connection.py
```

This will tell you:
- ✅ If Laravel API is accessible
- ✅ If cameras are configured
- ✅ If RTSP streams are accessible
- ✅ If frames can be read from the camera

### Step 2: Start Service with Logs
Start the service and watch for the startup messages:

```bash
cd C:\Github\tcc-maem\recognition
python service.py
```

**Look for these messages:**
```
🚀 Starting TCC-MAEM Recognition Service...
📡 Fetching cameras...
✅ Loaded X cameras
📅 Fetching today's schedule...
✅ Loaded schedules for X rooms
👤 Fetching faculty embeddings...
✅ Loaded embeddings for X faculty members
🎬 Starting background recognition threads...
✅ Started X background threads
🌐 Starting web server on port 5000...
```

**Then watch for these messages:**
```
🎬 Starting background thread for camera 1 - Feed: rtsp://...
Creating shared capture for camera 1
✅ Shared capture created for camera 1
✅ Background processing started for camera 1 (Room XXX)
📹 Camera 1: 100 frames captured
📹 Camera 1: 200 frames captured
```

### Step 3: Check Browser Console
Open your browser's Developer Tools (F12) and check for errors:

**Good signs:**
```
WebRTC connection established for camera: XXX
Video can play for camera: XXX
```

**Bad signs:**
```
Failed to fetch
Cannot connect to recognition service
WebRTC connection failed
```

## Common Issues and Solutions

### Issue 1: "No cameras found"
**Symptoms:** Service says "Loaded 0 cameras"

**Solution:**
1. Make sure Laravel is running: `php artisan serve`
2. Add cameras in the Laravel admin panel
3. Verify API endpoint: `http://127.0.0.1:8000/api/cameras`

### Issue 2: "Failed to open shared capture"
**Symptoms:** Service shows `❌ Failed to create shared capture for camera X`

**Causes:**
- RTSP URL is wrong
- Camera is offline
- Network connectivity issue
- Camera doesn't support the codec

**Solution:**
1. Test with VLC: Open VLC → Media → Open Network Stream → Paste RTSP URL
2. Check camera settings for RTSP configuration
3. Verify network connectivity to camera
4. Try different RTSP transport (TCP vs UDP)

### Issue 3: "Camera read failed"
**Symptoms:** Service shows `⚠️ Camera X read failed, retrying in 10s`

**Causes:**
- Unstable network connection
- Camera stream interrupted
- Camera restarted

**Solution:**
- Check network stability
- Restart the camera
- Check camera logs for errors

### Issue 4: "WebRTC connection failed"
**Symptoms:** Browser console shows connection failures

**Causes:**
- Recognition service not running
- Port 5000 is blocked
- CORS issues

**Solution:**
1. Verify service is running: `http://127.0.0.1:5000/health`
2. Check Windows Firewall allows port 5000
3. Check browser console for CORS errors

### Issue 5: Black screen but recording works
**Symptoms:** 
- Recordings save successfully to `storage/app/public/stream_recordings/`
- Live feed shows black screen
- No "recognition service not running" error

**This means:**
- ✅ RTSP connection works
- ✅ Recording works
- ❌ Shared frame buffer not working
- ❌ Background thread not starting

**Solution:**
This is what we're fixing! The changes made should resolve this:

1. **Restart the Python service** (important!)
   ```bash
   # Stop current service (Ctrl+C)
   cd C:\Github\tcc-maem\recognition
   python service.py
   ```

2. **Watch the startup logs** - You should see:
   ```
   ✅ Started X background threads
   🎬 Starting background thread for camera 1
   ✅ Background processing started for camera 1
   📹 Camera 1: 100 frames captured
   ```

3. **Refresh browser** - Hard refresh (Ctrl+F5)

## Debugging Commands

### Check if service is running
```bash
curl http://127.0.0.1:5000/health
```

Should return: `{"status":"ok"}`

### Check recognition status
```bash
curl http://127.0.0.1:5000/status
```

Should return JSON with recognition logs

### Check Laravel API
```bash
curl http://127.0.0.1:8000/api/cameras
```

Should return array of cameras

### List recordings
```bash
dir C:\Github\tcc-maem\storage\app\public\stream_recordings
```

Should show `.mp4` files if recording works

### Play a recording
Open in VLC or browser:
```
http://127.0.0.1:8000/storage/stream_recordings/camera_1_YYYYMMDD_HHMMSS.mp4
```

## What's Been Fixed

### Fix 1: Added `_cap` initialization
**Problem:** RTSPVideoTrack didn't initialize `self._cap`
**Solution:** Added `self._cap = None` in `__init__`

### Fix 2: Recording uses shared frames
**Problem:** Recording opened separate RTSP connection, blocking WebRTC
**Solution:** Recording now reads from shared frame buffer

### Fix 3: Background threads start immediately
**Problem:** Background threads might start late
**Solution:** `_start_background_recognition()` called before web server starts

### Fix 4: Added comprehensive logging
**Problem:** Hard to diagnose what's failing
**Solution:** Added detailed logging at every step

## Expected Behavior

When everything works correctly:

1. **Service starts:**
   ```
   🚀 Starting TCC-MAEM Recognition Service...
   ✅ Loaded 1 cameras
   ✅ Started 1 background threads
   🌐 Starting web server on port 5000...
   ```

2. **Background threads run:**
   ```
   ✅ Background processing started for camera 1
   📹 Camera 1: 100 frames captured
   📹 Camera 1: 200 frames captured
   ```

3. **Browser shows video:**
   - Video plays smoothly
   - Bounding boxes appear around faces
   - Recognition logs update every 2 seconds

4. **Recordings save:**
   - New .mp4 file every 3 minutes
   - Files are playable
   - Database records created

## Still Not Working?

If you've tried everything and it's still showing black:

1. **Check your RTSP URL format:**
   ```
   rtsp://username:password@ip:port/stream
   rtsp://192.168.1.100:554/stream1
   ```

2. **Test with test script:**
   ```bash
   python recognition/test_connection.py
   ```

3. **Check Python packages:**
   ```bash
   pip install opencv-python-headless aiortc aiohttp requests python-dotenv insightface
   ```

4. **Provide error logs:**
   - Copy all terminal output from service.py
   - Copy browser console errors (F12)
   - Share for further debugging

## Next Steps

After fixing:
1. ✅ Restart Python service
2. ✅ Watch for background thread logs
3. ✅ Refresh browser (Ctrl+F5)
4. ✅ Check if video appears
5. ✅ Verify recordings still save

