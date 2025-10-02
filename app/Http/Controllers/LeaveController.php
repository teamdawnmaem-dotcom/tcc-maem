<?php

namespace App\Http\Controllers;

use App\Models\Leave;
use App\Models\Faculty;
use App\Models\ActivityLog;
use App\Services\AttendanceRemarksService;
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
            'lp_image' => 'nullable|image|max:2048',
        ]);

        // Force type to Leave
        $validated['lp_type'] = 'Leave';

        // Validation: prevent creating leave if a pass-slip already exists for this faculty and date with time overlap
        // (Optional strict rule, can be relaxed if business allows both)
        // Currently we only prevent pass creation during leave; leave creation is allowed.

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

        // Ensure type stays Leave
        $validated['lp_type'] = 'Leave';

        // Handle new image upload
        if ($request->hasFile('lp_image')) {
            if ($leave->lp_image) {
                Storage::disk('public')->delete($leave->lp_image);
            }
            $validated['lp_image'] = $request->file('lp_image')->store('leave_slips', 'public');
        }

        $leave->update($validated);

        // Update attendance remarks for the leave period
        $remarksService = new AttendanceRemarksService();
        $remarksService->reconcileLeaveChange($request->faculty_id, $validated['leave_start_date'], $validated['leave_end_date']);

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

        $leave->delete();

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
            'time_out' => 'required|string'
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
}
