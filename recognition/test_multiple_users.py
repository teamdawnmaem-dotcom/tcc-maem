#!/usr/bin/env python3
"""
Test script to verify multiple users can access the same camera feed.
This simulates multiple browser connections to the same camera.
"""

import asyncio
import aiohttp
import json
import time

async def test_multiple_connections():
    """Test multiple connections to the same camera."""
    
    base_url = "http://localhost:5000"
    camera_id = 1  # Test with camera ID 1
    
    print("🧪 Testing multiple user connections to camera feed...")
    
    # Test 1: Check if recognition service is healthy
    print("\n1. Checking recognition service health...")
    try:
        async with aiohttp.ClientSession() as session:
            async with session.get(f"{base_url}/health") as response:
                if response.status == 200:
                    print("✅ Recognition service is healthy")
                else:
                    print(f"❌ Recognition service health check failed: {response.status}")
                    return
    except Exception as e:
        print(f"❌ Cannot connect to recognition service: {e}")
        return
    
    # Test 2: Check connection stats
    print("\n2. Checking initial connection stats...")
    try:
        async with aiohttp.ClientSession() as session:
            async with session.get(f"{base_url}/connection-stats") as response:
                if response.status == 200:
                    stats = await response.json()
                    print(f"📊 Current connections: {stats}")
                else:
                    print(f"❌ Failed to get connection stats: {response.status}")
    except Exception as e:
        print(f"❌ Error getting connection stats: {e}")
    
    # Test 3: Simulate multiple WebRTC connections
    print(f"\n3. Simulating multiple connections to camera {camera_id}...")
    
    connections = []
    num_connections = 3  # Simulate 3 users
    
    try:
        async with aiohttp.ClientSession() as session:
            # Create multiple connections
            for i in range(num_connections):
                print(f"   Creating connection {i+1}/{num_connections}...")
                
                # Simulate WebRTC offer
                offer_data = {
                    "sdp": f"test-sdp-{i}",
                    "type": "offer"
                }
                
                try:
                    async with session.post(f"{base_url}/offer/{camera_id}", json=offer_data) as response:
                        if response.status == 200:
                            answer = await response.json()
                            print(f"   ✅ Connection {i+1} established")
                            connections.append(i)
                        else:
                            print(f"   ❌ Connection {i+1} failed: {response.status}")
                except Exception as e:
                    print(f"   ❌ Connection {i+1} error: {e}")
                
                # Small delay between connections
                await asyncio.sleep(0.5)
            
            # Test 4: Check connection stats after multiple connections
            print(f"\n4. Checking connection stats after {num_connections} connections...")
            async with session.get(f"{base_url}/connection-stats") as response:
                if response.status == 200:
                    stats = await response.json()
                    print(f"📊 Connections after test: {stats}")
                    
                    # Check if camera has multiple connections
                    if str(camera_id) in stats.get("cameras", {}):
                        camera_stats = stats["cameras"][str(camera_id)]
                        active_connections = camera_stats.get("active_connections", 0)
                        print(f"🎥 Camera {camera_id} has {active_connections} active connections")
                        
                        if active_connections > 0:
                            print("✅ Multiple connections supported!")
                        else:
                            print("❌ No active connections found")
                    else:
                        print(f"❌ Camera {camera_id} not found in stats")
                else:
                    print(f"❌ Failed to get connection stats: {response.status}")
            
            # Test 5: Cleanup connections
            print(f"\n5. Cleaning up connections...")
            for i in connections:
                cleanup_data = {
                    "camera_id": camera_id,
                    "connection_id": f"{camera_id}_{int(time.time() * 1000)}_{i}"
                }
                
                try:
                    async with session.post(f"{base_url}/cleanup-connection", json=cleanup_data) as response:
                        if response.status == 200:
                            print(f"   ✅ Connection {i+1} cleaned up")
                        else:
                            print(f"   ❌ Cleanup {i+1} failed: {response.status}")
                except Exception as e:
                    print(f"   ❌ Cleanup {i+1} error: {e}")
            
            # Final stats check
            print(f"\n6. Final connection stats...")
            async with session.get(f"{base_url}/connection-stats") as response:
                if response.status == 200:
                    stats = await response.json()
                    print(f"📊 Final connections: {stats}")
                else:
                    print(f"❌ Failed to get final stats: {response.status}")
    
    except Exception as e:
        print(f"❌ Test failed with error: {e}")
    
    print("\n🏁 Multiple user connection test completed!")

if __name__ == "__main__":
    asyncio.run(test_multiple_connections())
