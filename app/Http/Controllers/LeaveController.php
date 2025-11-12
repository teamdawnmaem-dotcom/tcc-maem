<?php

namespace App\Http\Controllers;

use App\Models\Leave;
use App\Models\Faculty;
use App\Models\ActivityLog;
use App\Services\AttendanceRemarksService;
use App\Services\CloudSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LeaveController extends Controller
{
    /**
     * Display all leave slips.
     */
    public function index()
    {
        $leaves = Leave::with('faculty')->latest('leave_start_date')->get();
        $faculties = Faculty::all();

        return view('checker.leave-management', compact('leaves', 'faculties'));
    }

    /**
     * Store a new leave slip.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'faculty_id' => 'required|exists:tbl_faculty,faculty_id',
            'lp_purpose' => 'required|string|max:255',
            'leave_start_date' => 'required|date',
            'leave_end_date' => 'required|date|after_or_equal:leave_start_date',
            'lp_image' => 'required|image|max:2048',
        ]);

        // Check if start date is in the past and if purpose allows it
        $purpose = strtolower(trim($validated['lp_purpose']));
        $allowsPastDate = $purpose === 'emergency' || $purpose === 'sick leave';
        $startDate = \Carbon\Carbon::parse($validated['leave_start_date']);
        $today = \Carbon\Carbon::today();
        
        if ($startDate->lt($today) && !$allowsPastDate) {
            return redirect()->back()
                ->withErrors(['leave_start_date' => 'Start date cannot be in the past.'])
                ->withInput();
        }

        // Force type to Leave
        $validated['lp_type'] = 'Leave';

        // Handle file upload
        if ($request->hasFile('lp_image')) {
            $validated['lp_image'] = $request->file('lp_image')->store('leave_slips', 'public');
        }

        $leave = Leave::create($validated);

        // Update attendance remarks for the leave period
        $remarksService = new AttendanceRemarksService();
        $remarksService->updateAttendanceRemarksForNewRecord($request->faculty_id, 'Leave', $validated['leave_start_date']);
        
        // Also create absent records for scheduled classes during leave period
        $startDate = \Carbon\Carbon::parse($validated['leave_start_date']);
        $endDate = \Carbon\Carbon::parse($validated['leave_end_date']);
        
        while ($startDate->lte($endDate)) {
            $remarksService->updateAbsentFacultyRemarks($request->faculty_id, $startDate->toDateString());
            $startDate->addDay();
        }

        $faculty = Faculty::find($request->faculty_id);
        ActivityLog::create([
            'user_id' => auth()->id(),
            'logs_action' => 'CREATE',
            'logs_description' => 'Added a Leave for Faculty name: ' . $faculty->faculty_fname . ' ' . $faculty->faculty_lname,
            'logs_module' => 'Leave management',
        ]);

        return redirect()->route('checker.leave.management')->with('success', 'Leave record added successfully.');
    }

    /**
     * Update an existing leave slip.
     */
    public function update(Request $request, $id)
    {
        $leave = Leave::findOrFail($id);

        $validated = $request->validate([
            'faculty_id' => 'required|exists:tbl_faculty,faculty_id',
            'lp_purpose' => 'required|string|max:255',
            'leave_start_date' => 'required|date',
            'leave_end_date' => 'required|date|after_or_equal:leave_start_date',
            'lp_image' => 'nullable|image|max:2048',
        ]);

        // Check if start date is in the past and if purpose allows it
        $purpose = strtolower(trim($validated['lp_purpose']));
        $allowsPastDate = $purpose === 'emergency' || $purpose === 'sick leave';
        $startDate = \Carbon\Carbon::parse($validated['leave_start_date']);
        $today = \Carbon\Carbon::today();
        
        if ($startDate->lt($today) && !$allowsPastDate) {
            return redirect()->back()
                ->withErrors(['leave_start_date' => 'Start date cannot be in the past.'])
                ->withInput();
        }

        // Ensure type stays Leave
        $validated['lp_type'] = 'Leave';

        // Handle new image upload
        if ($request->hasFile('lp_image')) {
            // Delete old image if it exists
            if ($leave->lp_image) {
                Storage::disk('public')->delete($leave->lp_image);
            }
            $validated['lp_image'] = $request->file('lp_image')->store('leave_slips', 'public');
        } else {
            // Keep the old image if no new image is uploaded
            unset($validated['lp_image']);
        }

        // Store old dates for reconciliation
        $oldStartDate = $leave->leave_start_date;
        $oldEndDate = $leave->leave_end_date;
        
        $leave->update($validated);

        // Intelligently update attendance remarks for the leave period - preserve existing IDs
        $remarksService = new AttendanceRemarksService();
        $remarksService->reconcileLeaveChange(
            $request->faculty_id, 
            $validated['leave_start_date'], 
            $validated['leave_end_date'],
            $oldStartDate,
            $oldEndDate
        );

         $faculty = Faculty::find($request->faculty_id);
        ActivityLog::create([
            'user_id' => auth()->id(),
            'logs_action' => 'UPDATE',
            'logs_description' => 'Updated a Leave for Faculty name: ' . $faculty->faculty_fname . ' ' . $faculty->faculty_lname,
            'logs_module' => 'Leave management',
        ]);

        return redirect()->route('checker.leave.management')->with('success', 'Leave record updated successfully.');
    }

    /**
     * Delete a leave slip.
     */
    public function destroy($id)
    {
        $leave = Leave::findOrFail($id);
        $facultyId = $leave->faculty_id;
        $start = $leave->leave_start_date;
        $end = $leave->leave_end_date;
        if ($leave->lp_image) {
            Storage::disk('public')->delete($leave->lp_image);
        }

        $lpId = $leave->lp_id;
        $lpType = $leave->lp_type ?? 'Leave';
        $leave->delete();

        // Track deletion for sync (include lp_type metadata for filtering)
        $syncService = app(CloudSyncService::class);
        $syncService->trackDeletion('tbl_leave_pass', $lpId, 90, ['lp_type' => $lpType]);
        
        // NEW APPROACH: Immediately trigger deletion on cloud
        try {
            $syncService->triggerDeleteOnCloud('leaves', $lpId);
        } catch (\Exception $e) {
            \Log::error("Failed to trigger leave deletion on cloud: " . $e->getMessage());
        }

        // Reconcile attendance records after deletion: remove 'on leave' absences in the former window only
        $remarksService = new AttendanceRemarksService();
        $remarksService->removeLeaveAbsencesInWindow($facultyId, $start, $end);

         $faculty = Faculty::find($leave->faculty_id);
        ActivityLog::create([
            'user_id' => auth()->id(),
            'logs_action' => 'DELETE',
            'logs_description' => 'Deleted a Leave for Faculty name: ' . $faculty->faculty_fname . ' ' . $faculty->faculty_lname,
            'logs_module' => 'Leave management',
        ]);
        return redirect()->route('checker.leave.management')->with('success', 'Leave slip deleted successfully.');
    }

    /**
     * Check if faculty is on leave for a specific date and time range (for Python service)
     */
    public function checkFacultyLeaveStatus(Request $request)
    {
        $request->validate([
            'faculty_id' => 'required|integer',
            'date' => 'required|date',
            'time_in' => 'required|string',
            'time_out' => 'required|string',
            'teaching_load_class_section' => 'required|string',
        ]);

        $facultyId = $request->faculty_id;
        $date = $request->date;
        $timeIn = $request->time_in;
        $timeOut = $request->time_out;

        // Check if faculty has an active leave for this date
        $leave = Leave::where('faculty_id', $facultyId)
            ->where('leave_start_date', '<=', $date)
            ->where('leave_end_date', '>=', $date)
            ->first();

        if ($leave) {
            // Check if the time range overlaps with teaching schedule
            $timeOverlaps = $this->timeOverlaps($timeIn, $timeOut, $leave->leave_start_time, $leave->leave_end_time);
            return response()->json(['on_leave' => $timeOverlaps]);
        }

        return response()->json(['on_leave' => false]);
    }

    /**
     * Check if two time ranges overlap
     */
    private function timeOverlaps($start1, $end1, $start2, $end2)
    {
        $start1 = \Carbon\Carbon::parse($start1);
        $end1 = \Carbon\Carbon::parse($end1);
        $start2 = \Carbon\Carbon::parse($start2);
        $end2 = \Carbon\Carbon::parse($end2);

        return $start1->lt($end2) && $start2->lt($end1);
    }

    /**
     * Check for overlapping leave requests
     */
    public function checkLeaveOverlap(Request $request)
    {
        $request->validate([
            'faculty_id' => 'required|integer',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'exclude_id' => 'nullable|integer',
        ]);

        $facultyId = $request->faculty_id;
        $startDate = $request->start_date;
        $endDate = $request->end_date;
        $excludeId = $request->exclude_id;

        $query = Leave::where('faculty_id', $facultyId);
        
        if ($excludeId) {
            $query->where('lp_id', '!=', $excludeId);
        }
        
        $existingLeaves = $query->get();

        foreach ($existingLeaves as $leave) {
            // Check for exact duplicate (same start and end dates)
            if ($startDate === $leave->leave_start_date && $endDate === $leave->leave_end_date) {
                $formattedStart = \Carbon\Carbon::parse($leave->leave_start_date)->format('F j, Y');
                $formattedEnd = \Carbon\Carbon::parse($leave->leave_end_date)->format('F j, Y');
                return response()->json([
                    'has_overlap' => true,
                    'message' => "This instructor already has a leave request with the exact same dates ({$formattedStart} to {$formattedEnd})."
                ]);
            }
            
            // Check for date overlap
            if ($this->datesOverlap($startDate, $endDate, $leave->leave_start_date, $leave->leave_end_date)) {
                $formattedStart = \Carbon\Carbon::parse($leave->leave_start_date)->format('F j, Y');
                $formattedEnd = \Carbon\Carbon::parse($leave->leave_end_date)->format('F j, Y');
                return response()->json([
                    'has_overlap' => true,
                    'message' => "This instructor already has a leave request from {$formattedStart} to {$formattedEnd}."
                ]);
            }
        }

        return response()->json([
            'has_overlap' => false,
            'message' => null
        ]);
    }

    /**
     * Check if two date ranges overlap
     */
    private function datesOverlap($start1, $end1, $start2, $end2)
    {
        $start1Date = \Carbon\Carbon::parse($start1);
        $end1Date = \Carbon\Carbon::parse($end1);
        $start2Date = \Carbon\Carbon::parse($start2);
        $end2Date = \Carbon\Carbon::parse($end2);
        
        // Two date ranges overlap if one starts before the other ends
        return $start1Date->lte($end2Date) && $start2Date->lte($end1Date);
    }
}
