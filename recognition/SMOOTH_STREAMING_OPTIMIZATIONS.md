# Smooth Video Streaming Optimizations

## Overview
This document outlines the optimizations implemented to make the live video feed smoother and more responsive.

## Key Optimizations Implemented

### 1. **Video Streaming Settings**
```python
# Video streaming optimization settings
VIDEO_FPS = 15  # Target FPS for video streaming
FRAME_BUFFER_SIZE = 2  # Slightly larger buffer for smoother video
DRAIN_FRAMES_LIMIT = 3  # Limit frame draining for smoother video
SKIP_FRAME_THRESHOLD = 0.02  # Minimal frame skipping for better responsiveness
ENABLE_SMOOTH_STREAMING = True  # Enable smooth streaming optimizations
```

### 2. **Optimized Frame Processing**
- **Smart Frame Draining**: Limited to 3 frames to prevent lag while maintaining smoothness
- **Minimal Frame Skipping**: Only skip frames when absolutely necessary (0.02s threshold)
- **Cached Overlay Drawing**: Always draw cached overlays for smooth video playback

### 3. **Adaptive Frame Scaling**
```python
def get_optimal_frame_scale(frame_shape):
    """Get optimal scaling factor based on frame size for smooth streaming."""
    h, w = frame_shape[:2]
    max_dim = max(h, w)
    
    if max_dim > 1080:
        return 0.4  # Very aggressive scaling for 4K+
    elif max_dim > 720:
        return 0.5  # Moderate scaling for HD
    else:
        return 0.6  # Light scaling for smaller frames
```

### 4. **Smooth Streaming Function**
```python
def optimize_for_smooth_streaming():
    """Apply optimizations for smooth video streaming."""
    global RECOGNITION_INTERVAL, FRAME_SCALE_FACTOR, DRAIN_FRAMES_LIMIT
    
    if ENABLE_SMOOTH_STREAMING:
        # Reduce recognition interval for smoother processing
        RECOGNITION_INTERVAL = 0.3  # Faster recognition for smoother video
        
        # Optimize frame scaling for better performance
        FRAME_SCALE_FACTOR = 0.5  # More aggressive scaling for smoother video
        
        # Limit frame draining to prevent lag
        DRAIN_FRAMES_LIMIT = 2  # Reduced draining for smoother video
```

## Performance Improvements

### 1. **Reduced Latency**
- **Faster Recognition**: Reduced interval from 0.5s to 0.3s
- **Optimized Draining**: Limited frame draining to prevent lag
- **Smart Processing**: Only process when necessary

### 2. **Better Frame Handling**
- **Consistent Overlays**: Always draw cached overlays for smooth video
- **Minimal Skipping**: Reduced frame skipping for better responsiveness
- **Adaptive Scaling**: Dynamic scaling based on frame size

### 3. **Improved WebRTC Performance**
- **Thread Pool**: Better performance with thread pool execution
- **Optimized Buffering**: Balanced buffer size for smooth streaming
- **Reduced CPU Usage**: More efficient processing

## Key Features

### 1. **Cached Overlay System**
```python
def _draw_cached_overlays(self, frame, current_time):
    """Draw cached overlays for smooth video playback."""
    # Always draw cached overlays to maintain smooth video
    # Prevents flickering and maintains visual consistency
```

### 2. **Smart Frame Processing**
- **Conditional Processing**: Only process when necessary
- **Recent Recognition Skip**: Skip processing if recently recognized
- **Active Schedule Check**: Only process when there's an active schedule

### 3. **Optimized Video Capture**
- **Better Buffer Management**: Balanced buffer size for smooth streaming
- **FPS Control**: Target 15 FPS for consistent video flow
- **Error Handling**: Better error handling for camera issues

## Benefits

### 1. **Smoother Video Playback**
- Reduced frame dropping
- Consistent frame rate
- Better visual quality

### 2. **Reduced Latency**
- Faster processing
- Better responsiveness
- Improved real-time performance

### 3. **Better Resource Management**
- Optimized CPU usage
- Efficient memory management
- Reduced device overload

## Configuration

The system automatically applies smooth streaming optimizations when `ENABLE_SMOOTH_STREAMING = True`. You can adjust these settings:

```python
# Adjust these values for your specific needs
VIDEO_FPS = 15  # Increase for higher quality (more CPU usage)
FRAME_BUFFER_SIZE = 2  # Increase for smoother video (more memory)
DRAIN_FRAMES_LIMIT = 3  # Decrease for lower latency
SKIP_FRAME_THRESHOLD = 0.02  # Decrease for more responsive video
```

## Usage

The optimizations are automatically applied when the service starts. No additional configuration is required. The system will:

1. **Apply smooth streaming optimizations** on startup
2. **Use adaptive scaling** based on frame size
3. **Maintain smooth video playback** with cached overlays
4. **Optimize processing** for better performance

## Monitoring

You can monitor the smooth streaming performance through the status endpoint:
- Check frame processing rates
- Monitor recognition intervals
- Track overlay caching effectiveness

The system provides detailed logging for troubleshooting any streaming issues.
