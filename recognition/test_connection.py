#!/usr/bin/env python3
"""
Test script to check if RTSP camera connection is working
"""
import cv2
import sys
import requests

def test_laravel_connection():
    """Test connection to Laravel API"""
    print("=" * 60)
    print("Testing Laravel API Connection...")
    print("=" * 60)
    
    try:
        response = requests.get("http://127.0.0.1:8000/api/cameras", timeout=5)
        if response.status_code == 200:
            cameras = response.json()
            print(f"✅ Successfully connected to Laravel API")
            print(f"✅ Found {len(cameras)} cameras")
            return cameras
        else:
            print(f"❌ Laravel API returned status {response.status_code}")
            return []
    except Exception as e:
        print(f"❌ Failed to connect to Laravel API: {e}")
        return []

def test_rtsp_connection(camera):
    """Test RTSP connection for a camera"""
    print("\n" + "=" * 60)
    print(f"Testing Camera: {camera.get('camera_name', 'Unknown')}")
    print(f"Feed URL: {camera.get('camera_live_feed')}")
    print("=" * 60)
    
    feed_url = camera.get('camera_live_feed')
    if not feed_url:
        print("❌ No feed URL configured")
        return False
    
    try:
        print("Attempting to open RTSP stream...")
        cap = cv2.VideoCapture(feed_url, cv2.CAP_FFMPEG)
        
        if not cap.isOpened():
            print("❌ Failed to open RTSP stream")
            return False
        
        print("✅ RTSP stream opened successfully")
        
        # Try to read a few frames
        print("Reading test frames...")
        for i in range(5):
            ret, frame = cap.read()
            if not ret:
                print(f"❌ Failed to read frame {i+1}")
                cap.release()
                return False
            
            print(f"✅ Frame {i+1}: {frame.shape} - Success")
        
        cap.release()
        print("✅ Camera test PASSED")
        return True
        
    except Exception as e:
        print(f"❌ Error testing camera: {e}")
        return False

def main():
    print("\n")
    print("╔" + "=" * 58 + "╗")
    print("║" + " " * 10 + "TCC-MAEM RTSP Connection Test" + " " * 18 + "║")
    print("╚" + "=" * 58 + "╝")
    print("\n")
    
    # Test Laravel connection
    cameras = test_laravel_connection()
    
    if not cameras:
        print("\n❌ No cameras found. Please add cameras in Laravel first.")
        sys.exit(1)
    
    # Test each camera
    print(f"\n{'=' * 60}")
    print(f"Testing {len(cameras)} camera(s)...")
    print(f"{'=' * 60}\n")
    
    results = {}
    for camera in cameras:
        camera_id = camera.get('camera_id')
        success = test_rtsp_connection(camera)
        results[camera_id] = success
    
    # Summary
    print("\n" + "=" * 60)
    print("TEST SUMMARY")
    print("=" * 60)
    
    for camera_id, success in results.items():
        camera = next(c for c in cameras if c.get('camera_id') == camera_id)
        status = "✅ PASS" if success else "❌ FAIL"
        print(f"Camera {camera_id} ({camera.get('camera_name')}): {status}")
    
    passed = sum(1 for s in results.values() if s)
    total = len(results)
    
    print(f"\n{passed}/{total} cameras working")
    
    if passed == total:
        print("\n🎉 All cameras working! You can start the service now.")
        sys.exit(0)
    else:
        print("\n⚠️  Some cameras failed. Check the RTSP URLs and network connection.")
        sys.exit(1)

if __name__ == "__main__":
    main()

