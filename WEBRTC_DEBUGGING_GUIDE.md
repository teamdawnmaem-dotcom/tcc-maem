# WebRTC Debugging Guide

## Changes Made to Blade Files

Updated all three live camera feed files:
- `resources/views/admin/live-camera-feed.blade.php`
- `resources/views/checker/live-camera-feed.blade.php`
- `resources/views/deptHead/live-camera-feed.blade.php`

### What Was Added:

1. **ICE Server Configuration**
   ```javascript
   const pc = new RTCPeerConnection({
       iceServers: [{ urls: 'stun:stun.l.google.com:19302' }]
   });
   ```

2. **Detailed Event Listeners**
   - `onicecandidate` - Shows ICE candidates being gathered
   - `onicecandidateerror` - Shows ICE errors
   - `oniceconnectionstatechange` - Shows ICE connection state changes

## How to Test

### Step 1: Restart Everything

**Terminal 1 - Laravel:**
```bash
cd C:\Github\tcc-maem
php artisan serve
```

**Terminal 2 - Python Service:**
```bash
cd C:\Github\tcc-maem\recognition
python service.py
```

Watch for these messages in Python service:
```
🚀 Starting TCC-MAEM Recognition Service...
✅ Loaded X cameras
🎬 Starting background recognition threads...
✅ Started X background threads
✅ Background processing started for camera 1
📹 Camera 1: 100 frames captured
🌐 Starting web server on port 5000...
```

### Step 2: Open Browser Console

1. Open your browser
2. Go to the live camera feed page
3. Press F12 to open Developer Tools
4. Go to the **Console** tab

### Step 3: Watch Console Logs

You should see messages like this:

**Good Signs (Everything Working):**
```
ICE candidate: candidate:...
ICE gathering completed
ICE connection state: checking
ICE connection state: connected
WebRTC connection state: connected for camera: Camera 1
WebRTC track received for camera: Camera 1
Video metadata loaded for camera: Camera 1
Video can play for camera: Camera 1
WebRTC connection established for camera: Camera 1
```

**Bad Signs (Problems):**
```
ICE candidate error: ...
ICE connection state: failed
WebRTC connection state: failed
WebRTC error: ...
Failed to fetch
```

### Step 4: Check Network Tab

In browser DevTools:
1. Go to **Network** tab
2. Filter by **XHR** or **Fetch**
3. Look for requests to `http://127.0.0.1:5000/offer/1`

**Good Response:**
- Status: 200
- Response contains: `{"sdp": "...", "type": "answer"}`

**Bad Response:**
- Status: 500 (server error)
- Status: 404 (service not running)
- Failed (connection refused)

## Common Issues and What Console Shows

### Issue 1: Python Service Not Running

**Console Shows:**
```
Failed to fetch
net::ERR_CONNECTION_REFUSED
```

**Solution:**
Start the Python service: `python recognition/service.py`

### Issue 2: Port 5000 Blocked

**Console Shows:**
```
Failed to fetch
WebRTC error: TypeError: Failed to fetch
```

**Solution:**
- Check Windows Firewall
- Verify port 5000 is allowed
- Try: `curl http://127.0.0.1:5000/health`

### Issue 3: No Shared Frames

**Console Shows:**
```
WebRTC connection state: connected
(but black screen)
```

**Python Console Should Show:**
```
✅ Background processing started for camera 1
📹 Camera 1: 100 frames captured
📹 Camera 1: 200 frames captured
```

**If NOT showing these messages:**
- Background thread didn't start
- RTSP connection failed
- Run test: `python recognition/test_connection.py`

### Issue 4: ICE Connection Failed

**Console Shows:**
```
ICE connection state: failed
ICE candidate error: ...
```

**Solution:**
- ICE servers issue
- Network/firewall blocking WebRTC
- Try different STUN server in blade file

### Issue 5: Track Received But No Video

**Console Shows:**
```
WebRTC track received for camera: Camera 1
(but still black screen)
```

**Solution:**
- Video element issue
- Check if `video.srcObject` is set
- Check video element styles
- Hard refresh browser (Ctrl+F5)

## Debugging Commands

### Test Recognition Service
```bash
# Check if service is running
curl http://127.0.0.1:5000/health

# Should return: {"status":"ok"}
```

### Test Offer Endpoint
```bash
# Send a test offer (won't work but shows if endpoint exists)
curl -X POST http://127.0.0.1:5000/offer/1 \
  -H "Content-Type: application/json" \
  -d '{"sdp":"test","type":"offer"}'
```

### Check Status
```bash
curl http://127.0.0.1:5000/status
```

### Test Camera Connection
```bash
cd C:\Github\tcc-maem\recognition
python test_connection.py
```

## What Each Log Means

### Python Service Logs

| Log Message | Meaning |
|------------|---------|
| `✅ Loaded X cameras` | Successfully fetched cameras from Laravel |
| `✅ Started X background threads` | Background recognition threads created |
| `🎬 Starting background thread for camera X` | Thread is starting |
| `Creating shared capture for camera X` | Opening RTSP connection |
| `✅ Shared capture created for camera X` | RTSP connection successful |
| `✅ Background processing started for camera X` | Thread is running |
| `📹 Camera X: 100 frames captured` | Frames are being read successfully |
| `❌ Failed to open shared capture` | RTSP connection failed |
| `⚠️ Camera X read failed` | Can't read frames from RTSP |

### Browser Console Logs

| Log Message | Meaning |
|------------|---------|
| `ICE candidate: ...` | WebRTC gathering connection info |
| `ICE gathering completed` | Ready to connect |
| `ICE connection state: connected` | WebRTC connection established |
| `WebRTC connection state: connected` | Peer connection established |
| `WebRTC track received` | Video track received from server |
| `Video metadata loaded` | Video stream is valid |
| `Video can play` | Video is ready to play |
| `Failed to fetch` | Can't connect to Python service |
| `ICE connection state: failed` | WebRTC connection failed |

## Expected Timeline

When everything works:

1. **0s - Service Start**
   ```
   🚀 Starting TCC-MAEM Recognition Service...
   ✅ Loaded 1 cameras
   ```

2. **1s - Threads Start**
   ```
   🎬 Starting background recognition threads...
   ✅ Started 1 background threads
   ```

3. **2s - RTSP Connection**
   ```
   🎬 Starting background thread for camera 1
   Creating shared capture for camera 1
   ✅ Shared capture created for camera 1
   ✅ Background processing started for camera 1
   ```

4. **3s - Frames Start**
   ```
   📹 Camera 1: 100 frames captured
   ```

5. **5s - Browser Opens**
   ```javascript
   // In browser console:
   ICE candidate: ...
   ICE gathering completed
   ICE connection state: connected
   WebRTC track received for camera: Camera 1
   Video can play for camera: Camera 1
   ```

6. **6s - Video Plays**
   ✅ Live video visible in browser

## Still Showing Black Screen?

If after all this you still see black screen:

1. **Check Python console** - Are frames being captured?
   - Look for: `📹 Camera 1: 100 frames captured`
   - If NO → RTSP problem, run `python test_connection.py`
   - If YES → Continue to next step

2. **Check browser console** - Is WebRTC connecting?
   - Look for: `WebRTC connection state: connected`
   - If NO → Network/firewall issue
   - If YES → Continue to next step

3. **Check browser console** - Is track received?
   - Look for: `WebRTC track received`
   - If NO → Server not sending video
   - If YES → Video element issue

4. **Try these:**
   - Hard refresh: Ctrl+F5
   - Clear cache and reload
   - Try different browser
   - Check browser console for errors
   - Inspect video element in DevTools

## Advanced Debugging

### Enable Verbose Logging in Python

Add this to the start of `service.py` main():
```python
import logging
logging.basicConfig(level=logging.DEBUG)
```

### Check Video Element in Browser

In browser console:
```javascript
// Get the video element
const video = document.getElementById('webrtc-player-1');

// Check srcObject
console.log('srcObject:', video.srcObject);

// Check if tracks exist
if (video.srcObject) {
    console.log('Tracks:', video.srcObject.getTracks());
    console.log('Active tracks:', video.srcObject.getTracks().filter(t => t.enabled && t.readyState === 'live'));
}

// Check video properties
console.log('Video ready state:', video.readyState);
console.log('Video paused:', video.paused);
console.log('Video current time:', video.currentTime);
```

### Monitor Shared Frames

Add this to Python service (temporary debug):
```python
# In update_shared_frame function
def update_shared_frame(camera_id, frame):
    try:
        with _frame_lock:
            if camera_id in _shared_frames and frame is not None:
                _shared_frames[camera_id] = frame.copy()
                print(f"DEBUG: Updated shared frame for camera {camera_id}, shape: {frame.shape}")
    except Exception as e:
        print(f"Error updating shared frame for camera {camera_id}: {e}")
```

## Summary

The blade files now have:
- ✅ Proper ICE server configuration
- ✅ Detailed event logging
- ✅ Connection state monitoring
- ✅ Error tracking

This will help identify exactly where the issue is:
- RTSP connection problem
- Python service not running
- WebRTC connection failure
- Network/firewall blocking
- Video element issue

