<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\Leave;
use App\Models\Pass;
use App\Models\TeachingLoad;
use App\Models\Camera;
use Carbon\Carbon;

class AttendanceRemarksService
{
    /**
     * Update attendance remarks for a specific faculty and date range
     */
    public function updateAttendanceRemarksForFaculty($facultyId, $startDate = null, $endDate = null)
    {
        $startDate = $startDate ? Carbon::parse($startDate) : Carbon::now()->startOfDay();
        $endDate = $endDate ? Carbon::parse($endDate) : Carbon::now()->endOfDay();

        // Get all attendance records for the faculty in the date range
        $attendanceRecords = AttendanceRecord::where('faculty_id', $facultyId)
            ->whereBetween('record_time_in', [$startDate, $endDate])
            ->with(['teachingLoad'])
            ->get();

        foreach ($attendanceRecords as $record) {
            $this->updateSingleAttendanceRemarks($record);
        }
    }

    /**
     * Update attendance remarks for a specific attendance record
     */
    public function updateSingleAttendanceRemarks(AttendanceRecord $record)
    {
        $recordDate = Carbon::parse($record->record_time_in)->toDateString();
        $recordTime = Carbon::parse($record->record_time_in)->format('H:i:s');
        $dayOfWeek = Carbon::parse($record->record_time_in)->format('l');

        // Get the teaching load details
        $teachingLoad = $record->teachingLoad;
        if (!$teachingLoad) {
            return;
        }

        // Check if the teaching load day matches the record day
        if (strtolower($teachingLoad->teaching_load_day_of_week) !== strtolower($dayOfWeek)) {
            return;
        }

        // Check if the record time falls within the teaching load time
        $teachingStartTime = Carbon::parse($teachingLoad->teaching_load_time_in)->format('H:i:s');
        $teachingEndTime = Carbon::parse($teachingLoad->teaching_load_time_out)->format('H:i:s');

        if ($recordTime < $teachingStartTime || $recordTime > $teachingEndTime) {
            return;
        }

        // Check for leave records
        $leaveRemark = $this->checkLeaveOverlap($record->faculty_id, $recordDate, $teachingStartTime, $teachingEndTime);
        if ($leaveRemark) {
            $record->update([
                'record_status' => 'Absent',
                'record_remarks' => $leaveRemark
            ]);
            return;
        }

        // Check for pass slip records
        $passRemark = $this->checkPassSlipOverlap($record->faculty_id, $recordDate, $teachingStartTime, $teachingEndTime);
        if ($passRemark) {
            $record->update([
                'record_status' => 'Absent',
                'record_remarks' => $passRemark
            ]);
            return;
        }

        // If no leave or pass slip found, preserve the original remarks
        // Don't update remarks if they already contain attendance status (Present/Late/Absent)
        // Also preserve remarks that contain special information like "Wrong room"
        $currentRemarks = $record->record_remarks;
        
        // Check if remarks start with standard status or contain special information
        $shouldPreserve = false;
        if (!empty($currentRemarks)) {
            // Check if it's an exact match to standard status
            if (in_array($currentRemarks, ['Present', 'Late', 'Absent'])) {
                $shouldPreserve = true;
            }
            // Check if it starts with Present, Late, or Absent (handles "Present(Wrong room, ...)" etc.)
            elseif (preg_match('/^(Present|Late|Absent)/i', $currentRemarks)) {
                $shouldPreserve = true;
            }
            // Check if it contains special information like "Wrong room"
            elseif (stripos($currentRemarks, 'Wrong room') !== false || 
                    stripos($currentRemarks, 'On leave') !== false || 
                    stripos($currentRemarks, 'With pass slip') !== false) {
                $shouldPreserve = true;
            }
        }
        
        // Only clear remarks if they should not be preserved
        if (!$shouldPreserve) {
            $record->update(['record_remarks' => '']);
        }
    }

    /**
     * Check if there's a leave record that overlaps with the teaching schedule
     */
    private function checkLeaveOverlap($facultyId, $date, $teachingStartTime, $teachingEndTime)
    {
        $leaves = Leave::where('faculty_id', $facultyId)
            ->where('leave_start_date', '<=', $date)
            ->where('leave_end_date', '>=', $date)
            ->get();

        foreach ($leaves as $leave) {
            // For leave records, if the date falls within the leave period, mark as "On Leave"
            return 'On Leave';
        }

        return null;
    }

    /**
     * Check if there's a pass slip record that overlaps with the teaching schedule
     */
    private function checkPassSlipOverlap($facultyId, $date, $teachingStartTime, $teachingEndTime)
    {
        $passes = Pass::where('faculty_id', $facultyId)
            ->where('pass_slip_date', $date)
            ->get();

        foreach ($passes as $pass) {
            $passStartTime = Carbon::parse($pass->pass_slip_departure_time)->format('H:i:s');
            $passEndTime = Carbon::parse($pass->pass_slip_arrival_time)->format('H:i:s');

            // Check if the pass slip time overlaps with the teaching time
            if ($this->timeOverlaps($teachingStartTime, $teachingEndTime, $passStartTime, $passEndTime)) {
                return 'With Pass Slip';
            }
        }

        return null;
    }

    /**
     * Check if two time ranges overlap
     */
    private function timeOverlaps($start1, $end1, $start2, $end2)
    {
        $start1Time = Carbon::parse($start1);
        $end1Time = Carbon::parse($end1);
        $start2Time = Carbon::parse($start2);
        $end2Time = Carbon::parse($end2);

        // Check if the ranges overlap
        return $start1Time->lt($end2Time) && $start2Time->lt($end1Time);
    }

    /**
     * Update all attendance records for a specific date
     */
    public function updateAttendanceRemarksForDate($date)
    {
        $attendanceRecords = AttendanceRecord::whereDate('record_date', $date)
            ->with(['teachingLoad'])
            ->get();

        foreach ($attendanceRecords as $record) {
            $this->updateSingleAttendanceRemarks($record);
        }
    }

    /**
     * Update attendance remarks when a new leave or pass slip is created
     */
    public function updateAttendanceRemarksForNewRecord($facultyId, $type, $date)
    {
        if ($type === 'Leave') {
            $leave = Leave::where('faculty_id', $facultyId)
                ->where('leave_start_date', '<=', $date)
                ->where('leave_end_date', '>=', $date)
                ->first();

            if ($leave) {
                $this->updateAttendanceRemarksForFaculty($facultyId, $leave->leave_start_date, $leave->leave_end_date);
            }
        } elseif ($type === 'Pass') {
            $this->updateAttendanceRemarksForFaculty($facultyId, $date, $date);
        }
    }

    /**
     * Update attendance remarks for absent faculty who should have leave/pass slip remarks
     */
    public function updateAbsentFacultyRemarks($facultyId, $date)
    {
        // Get all teaching loads for this faculty on this day
        $dayOfWeek = Carbon::parse($date)->format('l');
        $teachingLoads = TeachingLoad::where('faculty_id', $facultyId)
            ->where('teaching_load_day_of_week', $dayOfWeek)
            ->get();

        foreach ($teachingLoads as $teachingLoad) {
            // Check if there's already an attendance record for this teaching load on this date
            $existingRecord = AttendanceRecord::where('faculty_id', $facultyId)
                ->where('teaching_load_id', $teachingLoad->teaching_load_id)
                ->whereDate('record_date', $date)
                ->first();

            if (!$existingRecord) {
                // Create an absent record with appropriate remarks
                $remarks = $this->checkLeaveOverlap($facultyId, $date, 
                    $teachingLoad->teaching_load_time_in, $teachingLoad->teaching_load_time_out);
                
                if (!$remarks) {
                    $remarks = $this->checkPassSlipOverlap($facultyId, $date, 
                        $teachingLoad->teaching_load_time_in, $teachingLoad->teaching_load_time_out);
                }

                if ($remarks) {
                    // Find a valid camera assigned to the teaching load's room
                    $cameraId = Camera::where('room_no', $teachingLoad->room_no)->value('camera_id');

                    // If no camera is mapped to the room, skip creation to avoid FK violation
                    if (!$cameraId) {
                        continue;
                    }

                    // Create absent record with remarks
                    AttendanceRecord::create([
                        'faculty_id' => $facultyId,
                        'teaching_load_id' => $teachingLoad->teaching_load_id,
                        'camera_id' => $cameraId,
                        'record_date' => Carbon::parse($date),
                        'record_time_in' => null,
                        'record_time_out' => null,
                        'time_duration_seconds' => 0,
                        'record_status' => 'Absent',
                        'record_remarks' => $remarks,
                    ]);
                }
            }
        }
    }

    /**
     * Reconcile attendance records after a Leave date range change.
     * Intelligently updates records to preserve IDs when dates overlap.
     * 
     * @param int $facultyId Faculty ID
     * @param string $newStartDate New start date
     * @param string $newEndDate New end date
     * @param string|null $oldStartDate Old start date (null if new record)
     * @param string|null $oldEndDate Old end date (null if new record)
     */
    public function reconcileLeaveChange($facultyId, $newStartDate, $newEndDate, $oldStartDate = null, $oldEndDate = null)
    {
        $newStart = Carbon::parse($newStartDate)->startOfDay();
        $newEnd = Carbon::parse($newEndDate)->endOfDay();
        
        // Calculate date ranges
        $newDates = [];
        $cursor = $newStart->copy();
        while ($cursor->lte($newEnd)) {
            $newDates[] = $cursor->toDateString();
            $cursor->addDay();
        }
        
        $oldDates = [];
        if ($oldStartDate && $oldEndDate) {
            $oldStart = Carbon::parse($oldStartDate)->startOfDay();
            $oldEnd = Carbon::parse($oldEndDate)->endOfDay();
            $cursor = $oldStart->copy();
            while ($cursor->lte($oldEnd)) {
                $oldDates[] = $cursor->toDateString();
                $cursor->addDay();
            }
        }
        
        // Determine which dates to keep, update, and delete
        $datesToKeep = array_intersect($oldDates, $newDates); // Dates in both ranges - UPDATE
        $datesToDelete = array_diff($oldDates, $newDates); // Dates only in old range - DELETE
        $datesToCreate = array_diff($newDates, $oldDates); // Dates only in new range - CREATE
        
        // Step 1: Delete records for dates that are no longer in the range
        if (!empty($datesToDelete)) {
            $recordIds = AttendanceRecord::where('faculty_id', $facultyId)
                ->where('record_status', 'Absent')
                ->where('record_remarks', 'On Leave')
                ->whereIn('record_date', $datesToDelete)
                ->pluck('record_id')
                ->toArray();
            
            if (!empty($recordIds)) {
                AttendanceRecord::whereIn('record_id', $recordIds)->delete();
            }
        }
        
        // Step 2: Update records for dates that are in both ranges (preserve IDs)
        foreach ($datesToKeep as $date) {
            $dayOfWeek = Carbon::parse($date)->format('l');
            $teachingLoads = TeachingLoad::where('faculty_id', $facultyId)
                ->where('teaching_load_day_of_week', $dayOfWeek)
                ->get();
            
            foreach ($teachingLoads as $teachingLoad) {
                $existingRecord = AttendanceRecord::where('faculty_id', $facultyId)
                    ->where('teaching_load_id', $teachingLoad->teaching_load_id)
                    ->whereDate('record_date', $date)
                    ->first();
                
                if ($existingRecord) {
                    // Update existing record - preserve ID
                    $existingRecord->update([
                        'record_status' => 'Absent',
                        'record_remarks' => 'On Leave',
                    ]);
                }
            }
        }
        
        // Step 3: Create/update records for dates that are only in new range
        foreach ($datesToCreate as $date) {
            $this->updateAbsentFacultyRemarks($facultyId, $date);
        }
    }

    /**
     * Remove 'on leave' absent records within a specific window (used when deleting a leave).
     */
    public function removeLeaveAbsencesInWindow($facultyId, $startDate, $endDate)
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        AttendanceRecord::where('faculty_id', $facultyId)
            ->where('record_status', 'Absent')
            ->where('record_remarks', 'On Leave')
            ->whereBetween('record_date', [$start, $end])
            ->delete();
    }

    /**
     * Reconcile attendance records for Pass slip changes on a specific date.
     * - Remove 'absent' + 'with pass slip' records that no longer overlap any pass
     * - Ensure records exist for overlaps (idempotent creation)
     */
    public function reconcilePassChange($facultyId, $date)
    {
        $dayOfWeek = Carbon::parse($date)->format('l');
        $teachingLoads = TeachingLoad::where('faculty_id', $facultyId)
            ->where('teaching_load_day_of_week', $dayOfWeek)
            ->get();

        // Fetch all passes on this date
        $passes = Pass::where('faculty_id', $facultyId)
            ->where('pass_slip_date', $date)
            ->get();

        foreach ($teachingLoads as $load) {
            $hasOverlap = false;
            foreach ($passes as $pass) {
                $ps = Carbon::parse($pass->pass_slip_departure_time)->format('H:i:s');
                $pe = Carbon::parse($pass->pass_slip_arrival_time)->format('H:i:s');
                if ($this->timeOverlaps(
                    Carbon::parse($load->teaching_load_time_in)->format('H:i:s'),
                    Carbon::parse($load->teaching_load_time_out)->format('H:i:s'),
                    $ps,
                    $pe
                )) {
                    $hasOverlap = true;
                    break;
                }
            }

            if (!$hasOverlap) {
                // Remove no-longer-valid pass-slip absent records for this date/load
                AttendanceRecord::where('faculty_id', $facultyId)
                    ->where('teaching_load_id', $load->teaching_load_id)
                    ->whereDate('record_date', $date)
                    ->where('record_status', 'Absent')
                    ->where('record_remarks', 'With Pass Slip')
                    ->delete();
            }
        }

        // Ensure records are created for any valid overlaps
        $this->updateAbsentFacultyRemarks($facultyId, $date);
    }
}
