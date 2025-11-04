import os
import json
import threading
import cv2
import numpy as np
import requests
from insightface.app import FaceAnalysis
from dotenv import load_dotenv

def _load_envs():
    env_path = os.getenv("ENV_PATH", "../.env")
    if os.path.exists(env_path):
        load_dotenv(env_path)
_load_envs()

# Get config from environment
API_BASE = os.getenv("API_BASE", "http://127.0.0.1:8000/api")
FACULTY_EMBEDDINGS_ENDPOINT = f"{API_BASE}/faculty-embeddings"
STORAGE_PATH = os.getenv("LARAVEL_STORAGE_PATH", "../storage/app/public")

# Initialize InsightFace model
print("Initializing InsightFace for embedding extraction...")
try:
    face_app = FaceAnalysis(
        name='buffalo_l',
        providers=['CPUExecutionProvider'],
        allowed_modules=['detection', 'recognition']
    )
    face_app.prepare(ctx_id=0, det_size=(640, 640))
    print("✅ InsightFace ready for embeddings extraction")
except Exception as e:
    print(f"❌ Cannot initialize InsightFace: {e}")
    face_app = None

def update_faculty_embeddings_from_images(faculty_id=None):
    """
    Compute and update embeddings from saved images for all faculty or for single faculty if faculty_id is given.
    """
    try:
        if face_app is None:
            print("InsightFace model not initialized.")
            return

        r = requests.get(FACULTY_EMBEDDINGS_ENDPOINT, timeout=15)
        r.raise_for_status()
        faculty_data = r.json()

        for f in faculty_data:
            fid = f.get("faculty_id")
            if faculty_id and fid != faculty_id:
                continue
            faculty_images = f.get("faculty_images", "[]")
            if isinstance(faculty_images, str):
                try:
                    image_paths = json.loads(faculty_images)
                except json.JSONDecodeError:
                    print(f"Invalid faculty_images JSON for faculty_id {fid}: {faculty_images}")
                    continue
            else:
                image_paths = faculty_images

            if not image_paths or not isinstance(image_paths, list):
                print(f"No valid image paths for faculty_id {fid}")
                continue
            embeddings_list = []
            print(f"Processing {len(image_paths)} images for faculty_id {fid}")
            for img_path in image_paths:
                try:
                    if os.path.isabs(img_path):
                        full_path = img_path
                    else:
                        full_path = os.path.join(STORAGE_PATH, img_path)
                    print(f"Processing image: {full_path}")
                    if not os.path.exists(full_path):
                        print(f"File not found: {full_path}")
                        continue
                    img = cv2.imread(full_path)
                    if img is None:
                        print(f"Could not load image: {full_path}")
                        continue
                    rgb_img = cv2.cvtColor(img, cv2.COLOR_BGR2RGB)
                    faces = face_app.get(rgb_img)
                    if faces:
                        print(f"Found {len(faces)} face(s) in {img_path}")
                        for face in faces:
                            embedding = face.embedding
                            embeddings_list.append(embedding)
                            print(f"Extracted embedding from face in {img_path}")
                    else:
                        print(f"No faces detected in {img_path}")
                except Exception as e:
                    print(f"Error processing image {img_path}: {e}")

            if embeddings_list:
                emb_list_json = [emb.tolist() for emb in embeddings_list]
                payload = {"faculty_id": fid, "faculty_face_embedding": json.dumps(emb_list_json)}
                try:
                    r_post = requests.put(FACULTY_EMBEDDINGS_ENDPOINT, json=payload, timeout=30)
                    print(f"Embedding update response [{fid}]: {r_post.status_code}, {r_post.text}")
                    if r_post.status_code in (200, 201):
                        print(f"✅ Updated faculty_face_embedding for faculty_id {fid}")
                    else:
                        print(f"❌ Failed to update embeddings for faculty_id {fid}")
                except Exception as e:
                    print(f"Error posting embeddings for faculty_id {fid}: {e}")
            else:
                print(f"No valid faces found for faculty_id {fid}")
                # Optionally, clear previous embeddings:
                try:
                    payload = {"faculty_id": fid, "faculty_face_embedding": json.dumps([])}
                    r_post = requests.put(FACULTY_EMBEDDINGS_ENDPOINT, json=payload, timeout=30)
                    if r_post.status_code in (200, 201):
                        print(f"Cleared embeddings for faculty_id {fid} (no faces detected)")
                    else:
                        print(f"Failed to clear embeddings for faculty_id {fid}: {r_post.status_code} - {r_post.text}")
                except Exception as e:
                    print(f"Error clearing embeddings for faculty_id {fid}: {e}")
    except Exception as e:
        print(f"update_faculty_embeddings_from_images error: {e}")


def regenerate_all_faculty_embeddings():
    """Entry point to regenerate embeddings for ALL faculty."""
    update_faculty_embeddings_from_images(faculty_id=None)

def regenerate_faculty_embedding(faculty_id):
    """Entry point to regenerate embeddings for a single faculty."""
    update_faculty_embeddings_from_images(faculty_id=faculty_id)

# -------------------
# aiohttp server for embedding extraction
# -------------------
from aiohttp import web

async def handle_update_embeddings(request):
    data = await request.json()
    faculty_id = data.get("faculty_id")
    t = threading.Thread(target=update_faculty_embeddings_from_images, args=(faculty_id,), daemon=True)
    t.start()
    return web.json_response({"status": "ok", "message": f"Embedding update triggered for faculty_id {faculty_id}"})

async def handle_regenerate_all(request):
    t = threading.Thread(target=update_faculty_embeddings_from_images, args=(None,), daemon=True)
    t.start()
    return web.json_response({"status": "ok", "message": "Regeneration triggered for all faculty"})

def main_server():
    app = web.Application()
    app.router.add_post("/update-embeddings", handle_update_embeddings)
    app.router.add_post("/regenerate-all-embeddings", handle_regenerate_all)
    port = int(os.getenv("EMBEDDINGS_PORT", "5001"))
    print(f"Starting Embeddings Extraction Server on port {port} ...")
    web.run_app(app, host="0.0.0.0", port=port)

# Main entrypoint
if __name__ == "__main__":
    import sys
    import argparse
    # If there are no arguments or only --server: start server mode
    if len(sys.argv) == 1 or (len(sys.argv) == 2 and sys.argv[1] == "--server"):
        main_server()
    else:
        # Run CLI batch mode ONLY if other CLI arguments are provided
        parser = argparse.ArgumentParser(description="Regenerate faculty image embeddings using InsightFace.")
        parser.add_argument("--faculty_id", type=int, help="Regenerate embedding for only this faculty ID", default=None)
        args = parser.parse_args()
        if args.faculty_id is not None:
            regenerate_faculty_embedding(args.faculty_id)
        else:
            regenerate_all_faculty_embeddings()
