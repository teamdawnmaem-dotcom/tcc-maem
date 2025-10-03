<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AttendanceRecord;
use App\Services\AttendanceRemarksService;

class AttendanceController extends Controller
{
	/**
	 * ✅ Return all attendance records for frontend (Blade fetchAttendance)
	 */
	public function index()
	{
		$records = AttendanceRecord::with(['faculty', 'teachingLoad.room', 'camera'])
			->orderBy('record_date', 'desc')
			->get()
			->map(function ($record) {
				return [
					'id'             => $record->record_id,
					'faculty_id'     => $record->faculty_id,
					'faculty_name'   => $record->faculty->faculty_name ?? null,
					'teaching_load'  => $record->teachingLoad->subject_name ?? null,
					'room_name'      => $record->teachingLoad->room->room_name ?? null,
					'room_building'  => $record->teachingLoad->room->room_building_no ?? null,
					'camera_id'      => $record->camera_id,
					'status'         => $record->record_status,
					'remarks'        => $record->record_remarks,
					'time_in'        => $record->record_time_in->format('Y-m-d H:i:s'),
				];
			});

		return response()->json([
			'success' => true,
			'data'    => $records
		]);
	}

	public function store(Request $request)
	{
		$request->validate([
			'faculty_id'       => 'required|exists:tbl_faculty,faculty_id',
			'teaching_load_id' => 'required|exists:tbl_teaching_load,teaching_load_id',
			'camera_id'        => 'required|exists:tbl_camera,camera_id',
			'record_status'    => 'required|string|in:Present,Late,Absent,present,late,absent',
			'record_time_in'   => 'nullable|string',
			'record_time_out'  => 'nullable|string',
			'time_duration_seconds' => 'nullable|integer|min:0',
			'record_remarks'   => 'nullable|string',
		]);

		// Handle N/A values for time fields
		$timeIn = $request->input('record_time_in');
		$timeOut = $request->input('record_time_out');
		$timeDuration = $request->input('time_duration_seconds', 0);
		
		// Convert N/A to null for database storage
		$timeIn = ($timeIn === 'N/A') ? null : $timeIn;
		$timeOut = ($timeOut === 'N/A') ? null : $timeOut;
		
		// Only use current time as fallback if time_in is not provided at all (not N/A)
		// If the original input was N/A, keep it as null (don't use fallback)
		if (!$timeIn && $request->input('record_time_in') !== 'N/A') {
			$timeIn = now();
		}

		$exists = AttendanceRecord::where('faculty_id', $request->faculty_id)
			->where('teaching_load_id', $request->teaching_load_id)
			->whereDate('record_date', now()->toDateString())
			->exists();

		if ($exists) {
			return response()->json([
				'success' => false,
				'message' => 'Attendance already recorded.'
			], 409);
		}

		$record = AttendanceRecord::create([
			'faculty_id'       => $request->faculty_id,
			'teaching_load_id' => $request->teaching_load_id,
			'camera_id'        => $request->camera_id,
			'record_date'      => now(),
			'record_time_in'   => $timeIn,
			'record_time_out'  => $timeOut,
			'time_duration_seconds' => $timeDuration,
			'record_status'    => $request->record_status,
			'record_remarks'   => $request->input('record_remarks', ''),
		]);

		// Update remarks based on leave/pass slip records
		$remarksService = new AttendanceRemarksService();
		$remarksService->updateSingleAttendanceRemarks($record);

		return response()->json([
			'success' => true,
			'message' => 'Attendance recorded successfully',
			'data'    => $record
		], 201);
	}

	public function check(Request $request)
	{
		$request->validate([
			'faculty_id'       => 'required|exists:tbl_faculty,faculty_id',
			'teaching_load_id' => 'required|exists:tbl_teaching_load,teaching_load_id',
		]);

		$exists = AttendanceRecord::where('faculty_id', $request->faculty_id)
			->where('teaching_load_id', $request->teaching_load_id)
			->whereDate('record_date', now()->toDateString())
			->exists();

		return response()->json([
			'success' => true,
			'data'    => ['exists' => $exists]
		]);
	}
}