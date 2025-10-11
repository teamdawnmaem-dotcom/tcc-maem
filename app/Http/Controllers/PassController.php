<?php

namespace App\Http\Controllers;

use App\Models\Pass;
use App\Models\Faculty;
use App\Models\ActivityLog;
use App\Services\AttendanceRemarksService;
use Illuminate\Http\Request;

class PassController extends Controller
{
    public function index()
    {
        $passes = Pass::with('faculty')->latest('pass_slip_date')->get();
        $faculties = Faculty::all();

        return view('checker.pass-management', compact('passes', 'faculties'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'faculty_id' => 'required|exists:tbl_faculty,faculty_id',
            'lp_purpose' => 'required|string|max:255',
            'pass_slip_itinerary' => 'required|string|max:255',
            'pass_slip_date' => 'required|date',
            'pass_slip_departure_time' => 'required',
            'pass_slip_arrival_time' => 'required',
            'lp_image' => 'required|image|max:2048',
        ]);

        $data = $request->all();
        $data['lp_type'] = 'Pass';

        // Prevent pass slip when faculty is on leave for that date
        $onLeave = \App\Models\Leave::where('faculty_id', $request->faculty_id)
            ->where('leave_start_date', '<=', $request->pass_slip_date)
            ->where('leave_end_date', '>=', $request->pass_slip_date)
            ->exists();
        if ($onLeave) {
            return redirect()->back()
                ->withErrors(['pass_slip_date' => 'Selected instructor is currently on leave for this date.'])
                ->with('open_modal', 'addModal')
                ->withInput();
        }

        if ($request->hasFile('lp_image')) {
            $data['lp_image'] = $request->file('lp_image')->store('passes', 'public');
        }

        $pass = Pass::create($data);

        // Update attendance remarks for the pass slip date (reconcile)
        $remarksService = new AttendanceRemarksService();
        $remarksService->reconcilePassChange($request->faculty_id, $request->pass_slip_date);
        
        // Also create absent records for scheduled classes on pass slip date
        $remarksService->updateAbsentFacultyRemarks($request->faculty_id, $request->pass_slip_date);

        $faculty = Faculty::find($pass->faculty_id);
        ActivityLog::create([
            'user_id' => auth()->id(),
            'logs_action' => 'CREATE',
            'logs_description' => 'Added a Pass slip for Faculty name: ' . $faculty->faculty_fname . ' ' . $faculty->faculty_lname,
            'logs_module' => 'Pass slip management',
        ]);
        return redirect()->route('checker.pass.management')->with('success', 'Pass created successfully!');
    }

    public function update(Request $request, $id)
    {
        $pass = Pass::findOrFail($id);

        $request->validate([
            'faculty_id' => 'required|exists:tbl_faculty,faculty_id',
            'lp_purpose' => 'required|string|max:255',
            'pass_slip_itinerary' => 'required|string|max:255',
            'pass_slip_date' => 'required|date',
            'pass_slip_departure_time' => 'required',
            'pass_slip_arrival_time' => 'required',
            'lp_image' => 'nullable|image|max:2048',
        ]);

        $data = $request->all();
        if ($request->hasFile('lp_image')) {
            // Delete old image if it exists
            if ($pass->lp_image) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($pass->lp_image);
            }
            $data['lp_image'] = $request->file('lp_image')->store('passes', 'public');
        } else {
            // Keep the old image if no new image is uploaded
            unset($data['lp_image']);
        }

        // Prevent pass slip when faculty is on leave for that date
        $onLeave = \App\Models\Leave::where('faculty_id', $request->faculty_id)
            ->where('leave_start_date', '<=', $request->pass_slip_date)
            ->where('leave_end_date', '>=', $request->pass_slip_date)
            ->exists();
        if ($onLeave) {
            return redirect()->back()
                ->withErrors(['pass_slip_date' => 'Selected instructor is currently on leave for this date.'])
                ->with('open_modal', 'editModal')
                ->withInput();
        }

        // Store old date for reconciliation
        $oldDate = $pass->pass_slip_date;
        
        $pass->update($data);

        // Update attendance remarks for the pass slip date (reconcile)
        $remarksService = new AttendanceRemarksService();
        
        // First, reconcile the old date to remove old pass slip records
        if ($oldDate !== $request->pass_slip_date) {
            $remarksService->reconcilePassChange($request->faculty_id, $oldDate);
        }
        
        // Then, reconcile the new date
        $remarksService->reconcilePassChange($request->faculty_id, $request->pass_slip_date);
        
        // Also create absent records for scheduled classes on pass slip date
        $remarksService->updateAbsentFacultyRemarks($request->faculty_id, $request->pass_slip_date);

        $faculty = Faculty::find($pass->faculty_id);
        ActivityLog::create([
            'user_id' => auth()->id(),
            'logs_action' => 'UPDATE',
            'logs_description' => 'Updated a Pass slip for Faculty name: ' . $faculty->faculty_fname . ' ' . $faculty->faculty_lname,
            'logs_module' => 'Pass slip management',
        ]);

        return redirect()->route('checker.pass.management')->with('success', 'Pass updated successfully!');
    }

    public function destroy($id)
    {
        $pass = Pass::findOrFail($id);
        $facultyId = $pass->faculty_id;
        $date = $pass->pass_slip_date;
        $pass->delete();

        // Reconcile attendance for the date after deletion
        $remarksService = new AttendanceRemarksService();
        $remarksService->reconcilePassChange($facultyId, $date);

         $faculty = Faculty::find($pass->faculty_id);
        ActivityLog::create([
            'user_id' => auth()->id(),
            'logs_action' => 'DELETE',
            'logs_description' => 'Deleted a Pass slip for Faculty name: ' . $faculty->faculty_fname . ' ' . $faculty->faculty_lname,
            'logs_module' => 'Pass slip management',
        ]);

        return redirect()->route('checker.pass.management')->with('success', 'Pass deleted successfully!');
    }

    /**
     * Check if faculty has pass slip for a specific date and time range (for Python service)
     */
    public function checkFacultyPassStatus(Request $request)
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

        // Check if faculty has an active pass slip for this date
        $pass = Pass::where('faculty_id', $facultyId)
            ->where('pass_slip_date', $date)
            ->first();

        if ($pass) {
            // Check if the time range overlaps with teaching schedule
            $timeOverlaps = $this->timeOverlaps($timeIn, $timeOut, $pass->pass_slip_time_in, $pass->pass_slip_time_out);
            return response()->json(['has_pass' => $timeOverlaps]);
        }

        return response()->json(['has_pass' => false]);
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
     * Check if faculty is on leave for a specific date (for real-time validation)
     */
    public function checkLeaveConflict(Request $request)
    {
        $request->validate([
            'faculty_id' => 'required|integer',
            'date' => 'required|date',
        ]);

        $facultyId = $request->faculty_id;
        $date = $request->date;

        // Check if faculty has an active leave for this date
        $onLeave = \App\Models\Leave::where('faculty_id', $facultyId)
            ->where('leave_start_date', '<=', $date)
            ->where('leave_end_date', '>=', $date)
            ->exists();

        return response()->json([
            'on_leave' => $onLeave,
            'message' => $onLeave ? 'Selected instructor is currently on leave for this date.' : null
        ]);
    }

    /**
     * Check if faculty has overlapping pass slip for a specific date and time (for real-time validation)
     */
    public function checkPassOverlap(Request $request)
    {
        $request->validate([
            'faculty_id' => 'required|integer',
            'date' => 'required|date',
            'departure_time' => 'required|string',
            'arrival_time' => 'required|string',
            'exclude_id' => 'nullable|integer',
            'current_departure' => 'nullable|string',
            'current_arrival' => 'nullable|string',
        ]);

        $facultyId = $request->faculty_id;
        $date = $request->date;
        $departureTime = $request->departure_time;
        $arrivalTime = $request->arrival_time;
        $excludeId = $request->exclude_id;
        $currentDeparture = $request->current_departure;
        $currentArrival = $request->current_arrival;

        // Check if faculty has any pass slips on the same date
        $query = Pass::where('faculty_id', $facultyId)
            ->where('pass_slip_date', $date);
        
        if ($excludeId) {
            $query->where('lp_id', '!=', $excludeId);
        }
        
        $existingPasses = $query->get();

        foreach ($existingPasses as $pass) {
            // Skip if this is the current record being edited and times are the same
            if ($excludeId && $currentDeparture && $currentArrival && 
                $pass->pass_slip_departure_time === $currentDeparture && 
                $pass->pass_slip_arrival_time === $currentArrival) {
                continue;
            }
            
            // Check if times overlap
            if ($this->timeOverlaps($departureTime, $arrivalTime, $pass->pass_slip_departure_time, $pass->pass_slip_arrival_time)) {
                // Format times to 12-hour format
                $formattedDeparture = \Carbon\Carbon::createFromFormat('H:i:s', $pass->pass_slip_departure_time)->format('g:i a');
                $formattedArrival = \Carbon\Carbon::createFromFormat('H:i:s', $pass->pass_slip_arrival_time)->format('g:i a');
                
                return response()->json([
                    'has_overlap' => true,
                    'message' => "This instructor already has a pass slip from {$formattedDeparture} to {$formattedArrival} on this date."
                ]);
            }
        }

        return response()->json([
            'has_overlap' => false,
            'message' => null
        ]);
    }
}
