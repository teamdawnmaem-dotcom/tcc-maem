<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Subject;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class SubjectController extends Controller
{
    public function index()
    {
        $subjects = Subject::orderBy('subject_code')->get();
        return view('deptHead.subject-management', compact('subjects'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'subject_code' => 'required|string|max:100',
            'subject_description' => 'required|string|max:255',
            'department' => 'required|string|max:255',
        ]);

        $subject = Subject::create($request->only(['subject_code', 'subject_description', 'department']));

        ActivityLog::create([
            'user_id' => Auth::id(),
            'logs_action' => 'CREATE',
            'logs_description' => 'Added subject: ' . $subject->subject_code,
            'logs_module' => 'Subject management',
        ]);

        return redirect()->route('deptHead.subject.management')->with('success', 'Subject added successfully!');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'subject_code' => 'required|string|max:100',
            'subject_description' => 'required|string|max:255',
            'department' => 'required|string|max:255',
        ]);

        $subject = Subject::findOrFail($id);
        $subject->update($request->only(['subject_code', 'subject_description', 'department']));

        ActivityLog::create([
            'user_id' => Auth::id(),
            'logs_action' => 'UPDATE',
            'logs_description' => 'Updated subject: ' . $subject->subject_code,
            'logs_module' => 'Subject management',
        ]);

        return redirect()->route('deptHead.subject.management')->with('update', 'Subject updated successfully!');
    }

    public function destroy($id)
    {
        $subject = Subject::findOrFail($id);
        $code = $subject->subject_code;
        $subject->delete();

        ActivityLog::create([
            'user_id' => Auth::id(),
            'logs_action' => 'DELETE',
            'logs_description' => 'Deleted subject: ' . $code,
            'logs_module' => 'Subject management',
        ]);

        return redirect()->route('deptHead.subject.management')->with('delete', 'Subject deleted successfully!');
    }

    /**
     * Check for duplicate subjects
     */
    public function checkDuplicate(Request $request)
    {
        $request->validate([
            'subject_code' => 'required|string|max:100',
            'subject_description' => 'required|string|max:255',
            'department' => 'required|string|max:255',
            'exclude_id' => 'nullable|integer',
        ]);

        $subjectCode = $request->subject_code;
        $subjectDescription = $request->subject_description;
        $department = $request->department;
        $excludeId = $request->exclude_id;

        $query = Subject::where('subject_code', $subjectCode)
            ->where('subject_description', $subjectDescription)
            ->where('department', $department);
        
        if ($excludeId) {
            $query->where('subject_id', '!=', $excludeId);
        }
        
        $existingSubject = $query->first();

        if ($existingSubject) {
            return response()->json([
                'is_duplicate' => true,
                'message' => "A subject with the same code ({$subjectCode}), description ({$subjectDescription}), and department ({$department}) already exists."
            ]);
        }

        return response()->json([
            'is_duplicate' => false,
            'message' => null
        ]);
    }
}


