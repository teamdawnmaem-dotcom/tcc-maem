#!/usr/bin/env python3
"""
Test script to verify face embedding extraction is working properly.
Run this script to test the embedding extraction without the full service.
"""

import os
import json
import requests
import face_recognition
import numpy as np
import sys
import argparse
from dotenv import load_dotenv

# Load environment variables
env_path = os.getenv("ENV_PATH", "../.env")
if os.path.exists(env_path):
    load_dotenv(env_path)

API_BASE = os.getenv("API_BASE", "http://127.0.0.1:8000/api")
FACULTY_EMBEDDINGS_ENDPOINT = f"{API_BASE}/faculty-embeddings"
STORAGE_PATH = os.getenv("LARAVEL_STORAGE_PATH", "../storage/app/public")

def test_embedding_extraction(faculty_id=None):
    """Test the embedding extraction process"""
    print("=== Testing Face Embedding Extraction ===")
    
    try:
        # Fetch faculty data
        print("1. Fetching faculty data from Laravel API...")
        r = requests.get(FACULTY_EMBEDDINGS_ENDPOINT, timeout=10)
        r.raise_for_status()
        faculty_data = r.json()
        print(f"   Found {len(faculty_data)} faculty records")
        
        if not faculty_data:
            print("   No faculty data found. Please add some faculty with images first.")
            return
        
        # Filter by faculty_id if provided
        if faculty_id:
            faculty_data = [f for f in faculty_data if f.get("faculty_id") == faculty_id]
            if not faculty_data:
                print(f"   Faculty with ID {faculty_id} not found.")
                return
        
        # Test with faculty that have images
        for faculty in faculty_data:
            fid = faculty.get("faculty_id")
            faculty_images = faculty.get("faculty_images", "[]")
            
            # Parse images
            if isinstance(faculty_images, str):
                try:
                    image_paths = json.loads(faculty_images)
                except json.JSONDecodeError:
                    print(f"   Invalid JSON for faculty_images for faculty_id {fid}")
                    continue
            else:
                image_paths = faculty_images
            
            if not image_paths or not isinstance(image_paths, list):
                print(f"   Faculty {fid} has no images, skipping...")
                continue
            
            print(f"\n2. Testing faculty_id {fid} with {len(image_paths)} images:")
            
            embeddings_list = []
            
            for i, img_path in enumerate(image_paths):
                print(f"   Processing image {i+1}: {img_path}")
                
                # Handle both relative and absolute paths
                if os.path.isabs(img_path):
                    full_path = img_path
                else:
                    full_path = os.path.join(STORAGE_PATH, img_path)
                
                print(f"   Full path: {full_path}")
                
                if not os.path.exists(full_path):
                    print(f"   ❌ File not found: {full_path}")
                    continue
                
                try:
                    # Load and process image
                    img = face_recognition.load_image_file(full_path)
                    print(f"   ✅ Image loaded successfully: {img.shape}")
                    
                    # Try different face detection models
                    encodings = face_recognition.face_encodings(img, model="cnn")
                    if not encodings:
                        # Fallback to HOG model if CNN fails
                        encodings = face_recognition.face_encodings(img, model="hog")
                    
                    if encodings:
                        embeddings_list.extend(encodings)
                        print(f"   ✅ Found {len(encodings)} face(s) in {img_path}")
                    else:
                        print(f"   ⚠️  No faces detected in {img_path}")
                        
                except Exception as e:
                    print(f"   ❌ Error processing image {img_path}: {e}")
            
            if embeddings_list:
                print(f"\n3. Testing embedding update for faculty_id {fid}:")
                emb_list_json = [emb.tolist() for emb in embeddings_list]
                payload = {"faculty_id": fid, "faculty_face_embedding": json.dumps(emb_list_json)}
                
                try:
                    # Test the update endpoint
                    r_post = requests.put(FACULTY_EMBEDDINGS_ENDPOINT, json=payload, timeout=10)
                    if r_post.status_code in (200, 201):
                        print(f"   ✅ Successfully updated embeddings for faculty_id {fid} with {len(embeddings_list)} face(s)")
                        print(f"   Response: {r_post.json()}")
                    else:
                        print(f"   ❌ Failed to update embeddings: {r_post.status_code} - {r_post.text}")
                except Exception as e:
                    print(f"   ❌ Error posting embeddings: {e}")
            else:
                print(f"   ⚠️  No valid faces found for faculty_id {fid}")
            
            # If specific faculty_id was requested, only process that one
            if faculty_id:
                break
        
        print("\n=== Test Complete ===")
        
    except Exception as e:
        print(f"❌ Test failed: {e}")

if __name__ == "__main__":
    parser = argparse.ArgumentParser(description='Test face embedding extraction')
    parser.add_argument('--faculty-id', type=int, help='Specific faculty ID to process')
    args = parser.parse_args()
    
    test_embedding_extraction(args.faculty_id)
