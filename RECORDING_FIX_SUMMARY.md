# Stream Recording Fix Summary

## Problem Identified

The live camera feed was showing black screens because:

1. **Multiple RTSP connections conflict**: 
   - Recording thread was creating its own `cv2.VideoCapture` connection
   - Background recognition thread had its own connection
   - WebRTC track had its own connection
   - Most IP cameras only support 1-2 simultaneous RTSP connections

2. **Missing initialization**: 
   - `RTSPVideoTrack._cap` was not initialized in `__init__`

## Solution Implemented

### Fix 1: Initialize `_cap` attribute
**File**: `recognition/service.py` line 1662

Added `self._cap = None` to properly initialize the video capture attribute.

### Fix 2: Use shared frame buffer for recording
**File**: `recognition/service.py` lines 1962-2022

Changed the recording function to:
- Read from **shared frame buffer** instead of creating a new RTSP connection
- Use `get_shared_frame(camera_id)` to get frames
- Write frames at 15 FPS to MP4 file
- No longer opens or releases its own capture

## How It Works Now

```
RTSP Camera Stream
       ↓
   (single connection)
       ↓
Background Recognition Thread
  - Opens ONE shared capture
  - Reads frames continuously
  - Updates shared frame buffer
  - Performs face recognition
       ↓
   Shared Frame Buffer
       ↓
    ┌────────┴─────────┐
    ↓                  ↓
WebRTC Track      Recording Thread
(for browser)     (saves to MP4)
```

### Benefits:
✅ **One RTSP connection** - No conflicts
✅ **Better performance** - Less CPU/network usage
✅ **Reliable streaming** - WebRTC always has frames
✅ **Successful recording** - Saves frames at consistent FPS

## Files Modified

1. `recognition/service.py`:
   - Line 1662: Added `self._cap = None` initialization
   - Lines 1962-2022: Rewrote `record_stream_segment()` to use shared frames

## How to Restart

```bash
# Stop the current Python service (Ctrl+C)

# Start it again
cd C:\Github\tcc-maem\recognition
python service.py
```

Then refresh your browser on the live camera feed page.

## Expected Results

✅ Live camera feed displays video (not black)
✅ Face recognition bounding boxes appear
✅ Recordings continue saving every 3 minutes
✅ No "recognition service not running" errors
✅ Smooth video playback without stuttering

## Technical Details

### Recording FPS
- Fixed at 15 FPS for consistent file sizes
- Uses `time.sleep(1.0 / fps)` to control recording rate
- Prevents writing duplicate frames too fast

### Frame Fallback
- If shared frame is temporarily `None`, uses last valid frame
- Ensures recording never fails due to temporary buffer issues

### Thread Safety
- Shared frame buffer uses `_frame_lock` for thread safety
- All threads can safely read from shared frames
- Only background thread writes to the buffer

