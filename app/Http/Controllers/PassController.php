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
            'lp_image' => 'nullable|image|max:2048',
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
            $data['lp_image'] = $request->file('lp_image')->store('passes', 'public');
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

        $pass->update($data);

        // Update attendance remarks for the pass slip date (reconcile)
        $remarksService = new AttendanceRemarksService();
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
            'time_out' => 'required|string'
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
}
