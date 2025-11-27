<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Faculty;
use App\Models\TeachingLoad;
use App\Models\ActivityLog;
use App\Services\CloudSyncService;
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
    public function index1()
    {
        $faculties = Faculty::all();
        return view('checker.faculty-account-management', compact('faculties'));
    }

    // API endpoint for faculty list
    public function apiFaculty()
    {
        $faculties = Faculty::select('faculty_id', 'faculty_fname', 'faculty_lname')->get();
        return response()->json($faculties);
    }

    // API endpoint for individual faculty data
    public function apiFacultyById($facultyId)
    {
        $faculty = Faculty::select('faculty_id', 'faculty_fname', 'faculty_lname', 'faculty_department', 'faculty_images')
            ->findOrFail($facultyId);
        return response()->json($faculty);
    }

    // API endpoint to get teaching loads for a specific faculty
    public function apiFacultyTeachingLoads($facultyId)
    {
        $faculty = Faculty::findOrFail($facultyId);
        
        $teachingLoads = TeachingLoad::with('room')
            ->where('faculty_id', $facultyId)
            ->get()
            ->map(function ($load) {
                return [
                    'teaching_load_id' => $load->teaching_load_id,
                    'teaching_load_course_code' => $load->teaching_load_course_code,
                    'teaching_load_subject' => $load->teaching_load_subject,
                    'teaching_load_class_section' => $load->teaching_load_class_section,
                    'teaching_load_day_of_week' => $load->teaching_load_day_of_week,
                    'teaching_load_time_in' => $load->teaching_load_time_in,
                    'teaching_load_time_out' => $load->teaching_load_time_out,
                    'room_no' => $load->room_no,
                    'room_name' => $load->room->room_name ?? $load->room_no,
                ];
            })
            ->sortBy(function ($load) {
                // Define day order: Monday = 1, Tuesday = 2, ..., Sunday = 7
                $dayOrder = [
                    'Monday' => 1,
                    'Tuesday' => 2,
                    'Wednesday' => 3,
                    'Thursday' => 4,
                    'Friday' => 5,
                    'Saturday' => 6,
                    'Sunday' => 7
                ];
                
                $dayValue = $dayOrder[$load['teaching_load_day_of_week']] ?? 8; // Unknown days go last
                $timeValue = $load['teaching_load_time_in'];
                
                return [$dayValue, $timeValue];
            })
            ->values(); // Reset array keys

        return response()->json($teachingLoads);
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
                \Log::error("Invalid JSON format for faculty_face_embedding for faculty_id {$request->faculty_id}");
                return response()->json(['error' => 'Invalid JSON format for faculty_face_embedding'], 400);
            }

            // Validate that embedding is not empty
            if (empty($embedding)) {
                \Log::error("Empty embedding array for faculty_id {$request->faculty_id}");
                return response()->json(['error' => 'Embedding array cannot be empty'], 400);
            }

            \Log::info("Updating embeddings for faculty_id {$request->faculty_id} with " . count($embedding) . " embeddings");

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
            \Log::error("Stack trace: " . $e->getTraceAsString());
            return response()->json([
                'error' => 'Failed to update faculty embeddings',
                'details' => $e->getMessage(),
                'faculty_id' => $request->faculty_id
            ], 500);
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
            $faculty_images = $this->normalizeFacultyImages($faculty->faculty_images);
            
            if (empty($faculty_images)) {
                return response()->json([
                    'message' => 'No images found for faculty',
                    'faculty_id' => $faculty_id
                ], 200);
            }
            
            // Call the recognition service to update embeddings
            $response = Http::timeout(30)->post('http://127.0.0.1:5001/update-embeddings', [
                'faculty_id' => $faculty_id
            ]);
            
            if ($response->successful()) {
                $result = $response->json();
                \Log::info("Embedding update triggered via recognition service for faculty_id {$faculty_id}: " . $result['message']);
                
                return response()->json([
                    'message' => 'Embedding extraction triggered via recognition service',
                    'faculty_id' => $faculty_id,
                    'service_response' => $result
                ], 200);
            } else {
                \Log::error("Recognition service returned error for faculty_id {$faculty_id}: " . $response->body());
                return response()->json([
                    'error' => 'Recognition service error',
                    'faculty_id' => $faculty_id,
                    'service_error' => $response->body()
                ], 500);
            }

        } catch (\Exception $e) {
            \Log::error("Failed to trigger embedding extraction: " . $e->getMessage());
            return response()->json(['error' => 'Failed to trigger embedding extraction: ' . $e->getMessage()], 500);
        }
    }

    // API endpoint to regenerate all faculty embeddings
    public function apiRegenerateAllEmbeddings()
    {
        try {
            // Call the recognition service to regenerate all embeddings
            $response = Http::timeout(60)->post('http://127.0.0.1:5001/regenerate-all-embeddings');
            
            if ($response->successful()) {
                $result = $response->json();
                \Log::info("All embeddings regeneration triggered via recognition service: " . $result['message']);
                
                return response()->json([
                    'message' => 'All faculty embeddings regeneration triggered via recognition service',
                    'service_response' => $result
                ], 200);
            } else {
                \Log::error("Recognition service returned error for all embeddings regeneration: " . $response->body());
                return response()->json([
                    'error' => 'Recognition service error',
                    'service_error' => $response->body()
                ], 500);
            }

        } catch (\Exception $e) {
            \Log::error("Failed to trigger all embeddings regeneration: " . $e->getMessage());
            return response()->json(['error' => 'Failed to trigger all embeddings regeneration: ' . $e->getMessage()], 500);
        }
    }

    // Store new faculty
    public function store(Request $request)
    {
        $request->validate([
            'faculty_fname' => 'required|string|max:255',
            'faculty_lname' => 'required|string|max:255',
            'faculty_department' => 'required|string|max:255',
            'faculty_images.*' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
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
            'faculty_images' => $images
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
    public function store1(Request $request)
    {
        $request->validate([
            'faculty_fname' => 'required|string|max:255',
            'faculty_lname' => 'required|string|max:255',
            'faculty_department' => 'required|string|max:255',
            'faculty_images.*' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
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
            'faculty_images' => $images
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

        $images = $this->normalizeFacultyImages($faculty->faculty_images);

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
            'faculty_images' => $images
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
    public function update1(Request $request, $id)
    {
        $faculty = Faculty::findOrFail($id);

        $request->validate([
            'faculty_fname' => 'required|string|max:255',
            'faculty_lname' => 'required|string|max:255',
            'faculty_department' => 'required|string|max:255',
            'faculty_images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        $images = $this->normalizeFacultyImages($faculty->faculty_images);

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
            'faculty_images' => $images
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

        $images = $this->normalizeFacultyImages($faculty->faculty_images);
        foreach($images as $img){
            Storage::disk('public')->delete($img);
        }
        $facultyId = $faculty->faculty_id;
        $faculty->delete();

        // Track deletion for sync
        $syncService = app(CloudSyncService::class);
        $syncService->trackDeletion('tbl_faculty', $facultyId);
        
        // NEW APPROACH: Immediately trigger deletion on cloud
        try {
            $syncService->triggerDeleteOnCloudByTable('tbl_faculty', $facultyId);
        } catch (\Exception $e) {
            \Log::error("Failed to trigger faculty deletion on cloud: " . $e->getMessage());
        }

        // Log the action
        ActivityLog::create([
            'user_id' => auth()->id(),
            'logs_action' => 'DELETE',
            'logs_description' => 'Deleted Faculty Information: ' . $faculty->faculty_fname . ' ' . $faculty->faculty_lname,
            'logs_module' => 'Faculty Information',
        ]);

        return redirect()->back()->with('success', 'Faculty deleted successfully.');
    }
    public function destroy1($id)
    {
        $faculty = Faculty::findOrFail($id);

        $images = $this->normalizeFacultyImages($faculty->faculty_images);
        foreach($images as $img){
            Storage::disk('public')->delete($img);
        }
        $facultyId = $faculty->faculty_id;
        $faculty->delete();

        // Track deletion for sync
        $syncService = app(CloudSyncService::class);
        $syncService->trackDeletion('tbl_faculty', $facultyId);
        
        // NEW APPROACH: Immediately trigger deletion on cloud
        try {
            $syncService->triggerDeleteOnCloudByTable('tbl_faculty', $facultyId);
        } catch (\Exception $e) {
            \Log::error("Failed to trigger faculty deletion on cloud: " . $e->getMessage());
        }

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
    private function normalizeFacultyImages($value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

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