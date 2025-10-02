<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Faculty;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;

class FacultyController extends Controller
{
    // List all faculties
    public function index()
    {
        $faculties = Faculty::all();
        return view('deptHead.faculty-account-management', compact('faculties'));
    }

    // API endpoint for faculty list
    public function apiFaculty()
    {
        $faculties = Faculty::select('faculty_id', 'faculty_fname', 'faculty_lname')->get();
        return response()->json($faculties);
    }

    // API endpoint for faculty embeddings
    public function apiFacultyEmbeddings()
    {
        $faculties = Faculty::select('faculty_id', 'faculty_fname', 'faculty_lname', 'faculty_images', 'faculty_face_embedding')->get();

        $data = $faculties->map(function ($f) {
            return [
                'faculty_id'   => $f->faculty_id,
                'faculty_name' => $f->faculty_fname . ' ' . $f->faculty_lname,
                'faculty_images'=> $f->faculty_images,
                'faculty_face_embedding' => json_decode($f->faculty_face_embedding, true) ?? []
            ];
        });

        return response()->json($data);
    }

    // API endpoint to update faculty embeddings
    public function apiUpdateFacultyEmbeddings(Request $request)
    {
        $request->validate([
            'faculty_id' => 'required|integer|exists:tbl_faculty,faculty_id',
            'faculty_face_embedding' => 'required|string'
        ]);

        try {
            $faculty = Faculty::findOrFail($request->faculty_id);
            
            // Validate that the embedding is valid JSON
            $embedding = json_decode($request->faculty_face_embedding, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json(['error' => 'Invalid JSON format for faculty_face_embedding'], 400);
            }

            $faculty->update([
                'faculty_face_embedding' => $request->faculty_face_embedding
            ]);

            return response()->json([
                'message' => 'Faculty embeddings updated successfully',
                'faculty_id' => $faculty->faculty_id,
                'embedding_count' => count($embedding)
            ], 200);

        } catch (\Exception $e) {
            \Log::error("Failed to update faculty embeddings: " . $e->getMessage());
            return response()->json(['error' => 'Failed to update faculty embeddings'], 500);
        }
    }

    // API endpoint to trigger embedding extraction
    public function apiTriggerEmbeddingUpdate(Request $request)
    {
        $request->validate([
            'faculty_id' => 'required|integer|exists:tbl_faculty,faculty_id'
        ]);

        try {
            $faculty_id = $request->faculty_id;
            $faculty = Faculty::findOrFail($faculty_id);
            
            // Get faculty images
            $faculty_images = json_decode($faculty->faculty_images, true) ?? [];
            
            if (empty($faculty_images)) {
                return response()->json([
                    'message' => 'No images found for faculty',
                    'faculty_id' => $faculty_id
                ], 200);
            }
            
            // Create a Python script to extract embeddings
            $python_script = $this->createEmbeddingExtractionScript($faculty_id, $faculty_images);
            $script_path = storage_path('app/temp_embedding_extraction.py');
            file_put_contents($script_path, $python_script);
            
            // Run the Python script
            $command = "python \"{$script_path}\" 2>&1";
            $output = shell_exec($command);
            
            // Clean up the temporary script
            unlink($script_path);
            
            \Log::info("Embedding extraction output for faculty_id {$faculty_id}: " . $output);
            
            return response()->json([
                'message' => 'Embedding extraction completed',
                'faculty_id' => $faculty_id,
                'output' => $output
            ], 200);

        } catch (\Exception $e) {
            \Log::error("Failed to trigger embedding extraction: " . $e->getMessage());
            return response()->json(['error' => 'Failed to trigger embedding extraction: ' . $e->getMessage()], 500);
        }
    }
    
    // Create a Python script for embedding extraction
    private function createEmbeddingExtractionScript($faculty_id, $image_paths)
    {
        $storage_path = storage_path('app/public');
        $api_url = url('/api/faculty-embeddings');
        
        $script = "#!/usr/bin/env python3
import os
import json
import requests
import face_recognition
import numpy as np

# Configuration
FACULTY_ID = {$faculty_id}
STORAGE_PATH = r'{$storage_path}'
API_URL = '{$api_url}'
IMAGE_PATHS = " . json_encode($image_paths) . "

def extract_embeddings():
    print(f'Processing faculty_id {FACULTY_ID} with {len(IMAGE_PATHS)} images')
    
    embeddings_list = []
    
    for i, img_path in enumerate(IMAGE_PATHS):
        print(f'Processing image {i+1}: {img_path}')
        
        # Handle both relative and absolute paths
        if os.path.isabs(img_path):
            full_path = img_path
        else:
            full_path = os.path.join(STORAGE_PATH, img_path)
        
        print(f'Full path: {full_path}')
        
        if not os.path.exists(full_path):
            print(f'File not found: {full_path}')
            continue
        
        try:
            # Load and process image
            img = face_recognition.load_image_file(full_path)
            print(f'Image loaded successfully: {img.shape}')
            
            # Try different face detection models
            encodings = face_recognition.face_encodings(img, model='cnn')
            if not encodings:
                # Fallback to HOG model if CNN fails
                encodings = face_recognition.face_encodings(img, model='hog')
            
            if encodings:
                embeddings_list.extend(encodings)
                print(f'Found {len(encodings)} face(s) in {img_path}')
            else:
                print(f'No faces detected in {img_path}')
                
        except Exception as e:
            print(f'Error processing image {img_path}: {e}')
    
    if embeddings_list:
        print(f'Extracting embeddings for faculty_id {FACULTY_ID}')
        emb_list_json = [emb.tolist() for emb in embeddings_list]
        payload = {'faculty_id': FACULTY_ID, 'faculty_face_embedding': json.dumps(emb_list_json)}
        
        try:
            # Update embeddings via API
            r = requests.put(API_URL, json=payload, timeout=30)
            if r.status_code in (200, 201):
                print(f'Successfully updated embeddings for faculty_id {FACULTY_ID} with {len(embeddings_list)} face(s)')
                print(f'Response: {r.json()}')
            else:
                print(f'Failed to update embeddings: {r.status_code} - {r.text}')
        except Exception as e:
            print(f'Error posting embeddings: {e}')
    else:
        print(f'No valid faces found for faculty_id {FACULTY_ID}')

if __name__ == '__main__':
    extract_embeddings()
";
        
        return $script;
    }

    // Store new faculty
    public function store(Request $request)
    {
        $request->validate([
            'faculty_fname' => 'required|string|max:255',
            'faculty_lname' => 'required|string|max:255',
            'faculty_department' => 'required|string|max:255',
            'faculty_images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        $images = [];
        if($request->hasFile('faculty_images')){
            foreach($request->file('faculty_images') as $image){
                $path = $image->store('faculty_images', 'public');
                $images[] = $path;
            }
        }

        $faculty = Faculty::create([
            'faculty_fname' => $request->faculty_fname,
            'faculty_lname' => $request->faculty_lname,
            'faculty_department' => $request->faculty_department,
            'faculty_images' => json_encode($images)
        ]);

        // Log the action
        ActivityLog::create([
            'user_id' => auth()->id(),
            'logs_action' => 'CREATE',
            'logs_description' => 'Added new Faculty: ' . $request->faculty_fname . ' ' . $request->faculty_lname,
            'logs_module' => 'Faculty Information',
        ]);

        // Trigger Python embedding generation
        $this->triggerEmbeddingUpdate($faculty->faculty_id);

        return redirect()->back()->with('success', 'Faculty added successfully.');
    }

    // Update existing faculty
    public function update(Request $request, $id)
    {
        $faculty = Faculty::findOrFail($id);

        $request->validate([
            'faculty_fname' => 'required|string|max:255',
            'faculty_lname' => 'required|string|max:255',
            'faculty_department' => 'required|string|max:255',
            'faculty_images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        $images = json_decode($faculty->faculty_images, true) ?? [];

        if($request->hasFile('faculty_images')){
            foreach($request->file('faculty_images') as $image){
                $path = $image->store('faculty_images', 'public');
                $images[] = $path;
            }
        }

        $faculty->update([
            'faculty_fname' => $request->faculty_fname,
            'faculty_lname' => $request->faculty_lname,
            'faculty_department' => $request->faculty_department,
            'faculty_images' => json_encode($images)
        ]);

        // Log the action
        ActivityLog::create([
            'user_id' => auth()->id(),
            'logs_action' => 'UPDATE',
            'logs_description' => 'Updated Faculty Information: ' . $request->faculty_fname . ' ' . $request->faculty_lname,
            'logs_module' => 'Faculty Information',
        ]);

        // Trigger Python embedding generation
        $this->triggerEmbeddingUpdate($faculty->faculty_id);

        return redirect()->back()->with('success', 'Faculty updated successfully.');
    }

    // Delete faculty
    public function destroy($id)
    {
        $faculty = Faculty::findOrFail($id);

        if($faculty->faculty_images){
            $images = json_decode($faculty->faculty_images, true);
            foreach($images as $img){
                Storage::disk('public')->delete($img);
            }
        }
        $faculty->delete();

        // Log the action
        ActivityLog::create([
            'user_id' => auth()->id(),
            'logs_action' => 'DELETE',
            'logs_description' => 'Deleted Faculty Information: ' . $faculty->faculty_fname . ' ' . $faculty->faculty_lname,
            'logs_module' => 'Faculty Information',
        ]);

        return redirect()->back()->with('success', 'Faculty deleted successfully.');
    }

    // -----------------------------
    // Trigger embedding update
    // -----------------------------
    private function triggerEmbeddingUpdate($faculty_id)
    {
        try {
            // Call our own API endpoint to trigger embedding extraction
            $response = Http::post(url('/api/trigger-embedding-update'), [
                'faculty_id' => $faculty_id
            ]);
            
            if ($response->successful()) {
                \Log::info("Embedding update triggered successfully for faculty_id {$faculty_id}");
            } else {
                \Log::error("Failed to trigger embedding update for faculty_id {$faculty_id}: " . $response->body());
            }
        } catch (\Exception $e) {
            \Log::error("Failed to trigger embedding update for faculty_id {$faculty_id}: " . $e->getMessage());
        }
    }
}
