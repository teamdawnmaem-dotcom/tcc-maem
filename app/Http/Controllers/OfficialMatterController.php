<?php

namespace App\Http\Controllers;

use App\Models\OfficialMatter;
use App\Models\Faculty;
use App\Models\ActivityLog;
use App\Models\AttendanceRecord;
use App\Models\Camera;
use App\Models\TeachingLoad;
use App\Services\AttendanceRemarksService;
use App\Services\CloudSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class OfficialMatterController extends Controller
{
    /**
     * Display all official matters.
     */
    public function index()
    {
        $officialMatters = OfficialMatter::with('faculty')
            ->latest('om_start_date')
            ->get();
        $faculties = Faculty::all();

        return view('checker.official-matters-management', compact('officialMatters', 'faculties'));
    }

    /**
     * Store a new official matter.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'is_department_mode' => 'nullable|string',
            'faculty_id' => 'nullable|exists:tbl_faculty,faculty_id',
            'om_department' => 'nullable|string|max:255',
            'om_purpose' => 'required|string|max:255',
            'om_remarks' => 'required|string|max:255',
            'om_start_date' => 'required|date',
            'om_end_date' => 'required|date|after_or_equal:om_start_date',
            'om_attachment' => 'required|image|max:2048',
        ]);

        // Check if department mode is enabled
        $isDepartmentMode = isset($request->is_department_mode) && 
                           ($request->is_department_mode === true || $request->is_department_mode === '1' || $request->is_department_mode === 1);
        $validated['is_department_mode'] = $isDepartmentMode;

        // Ensure either faculty_id or om_department is provided
        if (!$isDepartmentMode && !isset($validated['faculty_id'])) {
            return redirect()->back()
                ->withErrors(['faculty_id' => 'Please select a faculty member.'])
                ->withInput();
        }

        if ($isDepartmentMode && !isset($validated['om_department'])) {
            return redirect()->back()
                ->withErrors(['om_department' => 'Please select a department.'])
                ->withInput();
        }

        // Handle file upload
        if ($request->hasFile('om_attachment')) {
            $validated['om_attachment'] = $request->file('om_attachment')->store('official_matters', 'public');
        }

        // Get affected faculty IDs (is_department_mode is already set in validated)
        $isDepartmentMode = $validated['is_department_mode'];
        $affectedFacultyIds = $this->getAffectedFacultyIds($validated);

        // Create official matter record(s)
        if ($isDepartmentMode) {
            // Create one record for department mode
            $officialMatter = OfficialMatter::create([
                'faculty_id' => null,
                'om_department' => $validated['om_department'],
                'om_purpose' => $validated['om_purpose'],
                'om_remarks' => $validated['om_remarks'],
                'om_start_date' => $validated['om_start_date'],
                'om_end_date' => $validated['om_end_date'],
                'om_attachment' => $validated['om_attachment'],
            ]);
        } else {
            // Create record for individual faculty
            $officialMatter = OfficialMatter::create([
                'faculty_id' => $validated['faculty_id'],
                'om_department' => null,
                'om_purpose' => $validated['om_purpose'],
                'om_remarks' => $validated['om_remarks'],
                'om_start_date' => $validated['om_start_date'],
                'om_end_date' => $validated['om_end_date'],
                'om_attachment' => $validated['om_attachment'],
            ]);
        }

        // Update attendance records for all affected faculty
        $this->updateAttendanceRecordsForOfficialMatter(
            $affectedFacultyIds,
            $validated['om_start_date'],
            $validated['om_end_date'],
            $validated['om_remarks']
        );

        // Log activity
        $description = $isDepartmentMode 
            ? "Added Official Matter for Department: {$validated['om_department']}"
            : "Added Official Matter for Faculty: " . Faculty::find($validated['faculty_id'])->faculty_fname . " " . Faculty::find($validated['faculty_id'])->faculty_lname;
        
        ActivityLog::create([
            'user_id' => auth()->id(),
            'logs_action' => 'CREATE',
            'logs_description' => $description,
            'logs_module' => 'Official Matters management',
        ]);

        return redirect()->route('checker.official.matters.management')
            ->with('success', 'Official matter record added successfully.');
    }

    /**
     * Update an existing official matter.
     */
    public function update(Request $request, $id)
    {
        $officialMatter = OfficialMatter::findOrFail($id);

        $validated = $request->validate([
            'is_department_mode' => 'nullable|string',
            'faculty_id' => 'nullable|exists:tbl_faculty,faculty_id',
            'om_department' => 'nullable|string|max:255',
            'om_purpose' => 'required|string|max:255',
            'om_remarks' => 'required|string|max:255',
            'om_start_date' => 'required|date',
            'om_end_date' => 'required|date|after_or_equal:om_start_date',
            'om_attachment' => 'nullable|image|max:2048',
        ]);

        // Check if department mode is enabled
        $isDepartmentMode = isset($request->is_department_mode) && 
                           ($request->is_department_mode === true || $request->is_department_mode === '1' || $request->is_department_mode === 1);
        $validated['is_department_mode'] = $isDepartmentMode;

        // Ensure either faculty_id or om_department is provided
        if (!$isDepartmentMode && !isset($validated['faculty_id'])) {
            return redirect()->back()
                ->withErrors(['faculty_id' => 'Please select a faculty member.'])
                ->withInput();
        }

        if ($isDepartmentMode && !isset($validated['om_department'])) {
            return redirect()->back()
                ->withErrors(['om_department' => 'Please select a department.'])
                ->withInput();
        }

        // Store old values for reconciliation
        $oldIsDepartmentMode = $officialMatter->om_department ? true : false;
        $oldFacultyIds = $this->getAffectedFacultyIds([
            'is_department_mode' => $oldIsDepartmentMode,
            'faculty_id' => $officialMatter->faculty_id,
            'om_department' => $officialMatter->om_department,
        ]);
        $oldStartDate = $officialMatter->om_start_date;
        $oldEndDate = $officialMatter->om_end_date;
        $oldRemarks = $officialMatter->om_remarks;

        // Get new affected faculty IDs (is_department_mode is already set in validated)
        $newIsDepartmentMode = $validated['is_department_mode'];
        $newFacultyIds = $this->getAffectedFacultyIds($validated);

        // Handle new image upload
        if ($request->hasFile('om_attachment')) {
            // Delete old image if it exists
            if ($officialMatter->om_attachment) {
                Storage::disk('public')->delete($officialMatter->om_attachment);
            }
            $validated['om_attachment'] = $request->file('om_attachment')->store('official_matters', 'public');
        } else {
            // Keep the old image if no new image is uploaded
            unset($validated['om_attachment']);
        }

        // Update the official matter
        $officialMatter->update([
            'faculty_id' => $newIsDepartmentMode ? null : $validated['faculty_id'],
            'om_department' => $newIsDepartmentMode ? $validated['om_department'] : null,
            'om_purpose' => $validated['om_purpose'],
            'om_remarks' => $validated['om_remarks'],
            'om_start_date' => $validated['om_start_date'],
            'om_end_date' => $validated['om_end_date'],
        ]);

        // Remove old attendance records
        $this->removeAttendanceRecordsForOfficialMatter(
            $oldFacultyIds,
            $oldStartDate,
            $oldEndDate,
            $oldRemarks
        );

        // Update attendance records for all affected faculty
        $this->updateAttendanceRecordsForOfficialMatter(
            $newFacultyIds,
            $validated['om_start_date'],
            $validated['om_end_date'],
            $validated['om_remarks']
        );

        // Log activity
        $description = $newIsDepartmentMode 
            ? "Updated Official Matter for Department: {$validated['om_department']}"
            : "Updated Official Matter for Faculty: " . Faculty::find($validated['faculty_id'])->faculty_fname . " " . Faculty::find($validated['faculty_id'])->faculty_lname;
        
        ActivityLog::create([
            'user_id' => auth()->id(),
            'logs_action' => 'UPDATE',
            'logs_description' => $description,
            'logs_module' => 'Official Matters management',
        ]);

        return redirect()->route('checker.official.matters.management')
            ->with('success', 'Official matter record updated successfully.');
    }

    /**
     * Delete an official matter.
     */
    public function destroy($id)
    {
        $officialMatter = OfficialMatter::findOrFail($id);
        
        $facultyIds = $this->getAffectedFacultyIds([
            'is_department_mode' => $officialMatter->om_department ? true : false,
            'faculty_id' => $officialMatter->faculty_id,
            'om_department' => $officialMatter->om_department,
        ]);
        $startDate = $officialMatter->om_start_date;
        $endDate = $officialMatter->om_end_date;
        $remarks = $officialMatter->om_remarks;

        if ($officialMatter->om_attachment) {
            Storage::disk('public')->delete($officialMatter->om_attachment);
        }

        $omId = $officialMatter->om_id;
        $officialMatter->delete();

        // Track deletion for sync
        $syncService = app(CloudSyncService::class);
        $syncService->trackDeletion('tbl_official_matters', $omId);

        // Remove attendance records
        $this->removeAttendanceRecordsForOfficialMatter($facultyIds, $startDate, $endDate, $remarks);

        // Log activity
        $description = $officialMatter->om_department 
            ? "Deleted Official Matter for Department: {$officialMatter->om_department}"
            : "Deleted Official Matter for Faculty: " . ($officialMatter->faculty ? $officialMatter->faculty->faculty_fname . " " . $officialMatter->faculty->faculty_lname : 'N/A');
        
        ActivityLog::create([
            'user_id' => auth()->id(),
            'logs_action' => 'DELETE',
            'logs_description' => $description,
            'logs_module' => 'Official Matters management',
        ]);

        return redirect()->route('checker.official.matters.management')
            ->with('success', 'Official matter record deleted successfully.');
    }

    /**
     * Get affected faculty IDs based on mode and selection.
     */
    private function getAffectedFacultyIds($data)
    {
        // Check if department mode is enabled (either boolean true or string '1')
        $isDepartmentMode = isset($data['is_department_mode']) && 
                           ($data['is_department_mode'] === true || $data['is_department_mode'] === '1' || $data['is_department_mode'] === 1);
        
        if ($isDepartmentMode && isset($data['om_department']) && $data['om_department']) {
            if ($data['om_department'] === 'All Instructor') {
                return Faculty::pluck('faculty_id')->toArray();
            } else {
                return Faculty::where('faculty_department', $data['om_department'])
                    ->pluck('faculty_id')
                    ->toArray();
            }
        } else {
            // Individual faculty mode
            if (isset($data['faculty_id']) && $data['faculty_id']) {
                return [$data['faculty_id']];
            }
            return [];
        }
    }

    /**
     * Update attendance records for official matter.
     */
    private function updateAttendanceRecordsForOfficialMatter($facultyIds, $startDate, $endDate, $remarks)
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        $cursor = $start->copy();

        while ($cursor->lte($end)) {
            $date = $cursor->toDateString();
            $dayOfWeek = $cursor->format('l');

            foreach ($facultyIds as $facultyId) {
                // Get all teaching loads for this faculty on this day
                $teachingLoads = TeachingLoad::where('faculty_id', $facultyId)
                    ->where('teaching_load_day_of_week', $dayOfWeek)
                    ->get();

                foreach ($teachingLoads as $teachingLoad) {
                    // Check if attendance record already exists
                    $existingRecord = AttendanceRecord::where('faculty_id', $facultyId)
                        ->where('teaching_load_id', $teachingLoad->teaching_load_id)
                        ->whereDate('record_date', $date)
                        ->first();

                    // Find a valid camera assigned to the teaching load's room
                    $cameraId = Camera::where('room_no', $teachingLoad->room_no)->value('camera_id');

                    // If no camera is mapped to the room, skip creation to avoid FK violation
                    if (!$cameraId) {
                        continue;
                    }

                    if ($existingRecord) {
                        // Update existing record - append remarks
                        $existingRecord->update([
                            'record_remarks' => $remarks,
                            'record_status' => 'Absent', // Mark as absent for official matters
                        ]);
                    } else {
                        // Create new record
                        AttendanceRecord::create([
                            'faculty_id' => $facultyId,
                            'teaching_load_id' => $teachingLoad->teaching_load_id,
                            'camera_id' => $cameraId,
                            'record_date' => $date,
                            'record_time_in' => null,
                            'record_time_out' => null,
                            'time_duration_seconds' => 0,
                            'record_status' => 'Absent',
                            'record_remarks' => $remarks,
                        ]);
                    }
                }
            }

            $cursor->addDay();
        }
    }

    /**
     * Remove attendance records for official matter.
     */
    private function removeAttendanceRecordsForOfficialMatter($facultyIds, $startDate, $endDate, $remarks)
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        // Get record IDs before deletion (for tracking)
        $recordIds = AttendanceRecord::whereIn('faculty_id', $facultyIds)
            ->whereBetween('record_date', [$start, $end])
            ->where('record_remarks', $remarks)
            ->where('record_status', 'Absent')
            ->pluck('record_id')
            ->toArray();

        // Delete the records
        AttendanceRecord::whereIn('faculty_id', $facultyIds)
            ->whereBetween('record_date', [$start, $end])
            ->where('record_remarks', $remarks)
            ->where('record_status', 'Absent')
            ->delete();
        
        // Track all deletions for sync
        if (!empty($recordIds)) {
            $syncService = app(CloudSyncService::class);
            foreach ($recordIds as $recordId) {
                $syncService->trackDeletion('tbl_attendance_record', $recordId);
            }
        }
    }
}
