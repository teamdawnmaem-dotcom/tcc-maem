<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceRecordArchive extends Model
{
    use HasFactory;

    protected $table = 'tbl_attendance_record_archive';
    protected $primaryKey = 'archive_id';
    public $timestamps = true;

    protected $fillable = [
        'original_record_id',
        'faculty_id',
        'teaching_load_id',
        'camera_id',
        'record_date',
        'record_time_in',
        'record_time_out',
        'time_duration_seconds',
        'record_status',
        'record_remarks',
        'school_year',
        'semester',
        'archived_at',
        'archived_by',
        'archive_notes',
    ];

    protected $casts = [
        'archived_at' => 'datetime',
        'record_date' => 'date',
        'record_time_in' => 'datetime',
        'record_time_out' => 'datetime',
    ];

    public function faculty()
    {
        return $this->belongsTo(Faculty::class, 'faculty_id', 'faculty_id');
    }

    public function teachingLoadArchive()
    {
        return $this->belongsTo(TeachingLoadArchive::class, 'teaching_load_id', 'archive_id');
    }

    public function camera()
    {
        return $this->belongsTo(Camera::class, 'camera_id', 'camera_id');
    }

    public function archivedBy()
    {
        return $this->belongsTo(User::class, 'archived_by', 'user_id');
    }
}