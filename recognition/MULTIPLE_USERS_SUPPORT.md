# Multiple Users Support for Live Camera Feed

## Problem
Previously, only the first user to connect to a camera feed would see the live video. Other users would see a black screen because each camera had only one `cv2.VideoCapture` instance, and only one connection could read from it at a time.

## Solution
Implemented a **shared video capture system** that allows multiple users to access the same camera feed simultaneously.

## Key Changes

### 1. **Shared Capture System**
- **`_shared_captures`**: Stores one `cv2.VideoCapture` instance per camera
- **`_shared_frames`**: Stores the latest processed frame for each camera
- **`_frame_lock`**: Thread-safe access to shared resources

### 2. **New Functions**
```python
def get_or_create_shared_capture(camera_id, camera_feed):
    """Get or create a shared video capture for a camera."""

def update_shared_frame(camera_id, frame):
    """Update the shared frame for a camera."""

def get_shared_frame(camera_id):
    """Get the latest shared frame for a camera."""
```

### 3. **Updated RTSPVideoTrack**
- **Shared Frame Priority**: First tries to get shared frame, falls back to direct capture
- **Frame Sharing**: Updates shared frame when processing recognition
- **Connection Tracking**: Each connection gets a unique ID

### 4. **Background Processing**
- **Shared Updates**: Background processing updates shared frames for all connections
- **Efficient Processing**: Only one instance processes recognition, shares results

### 5. **Connection Management**
- **`_active_connections`**: Tracks multiple connections per camera
- **Cleanup Endpoint**: `/cleanup-connection` for proper disconnection
- **Stats Endpoint**: `/connection-stats` for monitoring

## How It Works

### Multiple Users Flow:
1. **First User Connects**:
   - Creates shared capture for camera
   - Starts background processing
   - Updates shared frames

2. **Additional Users Connect**:
   - Reuses existing shared capture
   - Gets latest shared frame
   - No additional RTSP connections needed

3. **All Users See Same Video**:
   - Shared frame is updated by background processing
   - All connections get the same processed frame
   - Recognition results are shared

## Benefits

### ✅ **Multiple Users Supported**
- Unlimited users can view the same camera
- No black screens for additional users
- Efficient resource usage

### ✅ **Performance Optimized**
- Only one RTSP connection per camera
- Shared processing reduces CPU usage
- Background processing handles recognition

### ✅ **Scalable Architecture**
- Connection tracking per camera
- Cleanup when users disconnect
- Monitoring and statistics

## Testing

### Manual Testing:
1. Open multiple browser tabs/windows
2. Login with different accounts
3. Navigate to live camera feed
4. All users should see the same video

### Automated Testing:
```bash
cd recognition
python test_multiple_users.py
```

## Configuration

### Environment Variables:
- `RECOGNITION_PORT`: Port for recognition service (default: 5000)
- `API_BASE`: Laravel API base URL
- `MATCH_THRESHOLD`: Face recognition threshold

### Server Requirements:
- **CPU**: Multi-core recommended for multiple users
- **RAM**: 2GB+ for shared frame storage
- **Network**: Stable RTSP connection to cameras

## Monitoring

### Connection Stats:
```bash
curl http://localhost:5000/connection-stats
```

### Health Check:
```bash
curl http://localhost:5000/health
```

## Troubleshooting

### Common Issues:

1. **Black Screen for Additional Users**:
   - Check if shared capture is created
   - Verify background processing is running
   - Check connection stats

2. **High CPU Usage**:
   - Reduce `RECOGNITION_INTERVAL`
   - Lower `FRAME_SCALE_FACTOR`
   - Check number of active connections

3. **Memory Issues**:
   - Monitor `_shared_frames` size
   - Check for memory leaks in connections
   - Restart service if needed

## Future Improvements

### Potential Enhancements:
1. **Load Balancing**: Distribute users across multiple recognition services
2. **Caching**: Implement frame caching for better performance
3. **Compression**: Add video compression for bandwidth optimization
4. **Quality Scaling**: Adjust quality based on connection count

## Migration Notes

### For Live Server Deployment:
1. **Update Recognition Service**: Deploy new `service.py`
2. **Test Multiple Connections**: Verify with multiple browsers
3. **Monitor Performance**: Check CPU and memory usage
4. **Configure Load Balancing**: If using multiple servers

### Backward Compatibility:
- Existing single-user connections still work
- No changes needed to frontend code
- Gradual rollout possible

## Conclusion

The shared capture system successfully enables multiple users to view the same camera feed simultaneously, solving the black screen issue while maintaining performance and scalability.
