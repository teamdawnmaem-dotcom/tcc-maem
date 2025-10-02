<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceRecord extends Model
{
    use HasFactory;

    protected $table = 'tbl_attendance_record';
    protected $primaryKey = 'record_id';
    public $timestamps = false;

    protected $fillable = [
        'faculty_id',
        'teaching_load_id',
        'camera_id',
        'record_time_in',
        'record_time_out',
        'time_duration_seconds',
        'record_status',
        'record_remarks',
    ];

        public function faculty()
        {
            return $this->belongsTo(Faculty::class, 'faculty_id', 'faculty_id');
        }

        public function teachingLoad()
        {
            return $this->belongsTo(TeachingLoad::class, 'teaching_load_id', 'teaching_load_id');
        }

        public function camera()
        {
            return $this->belongsTo(Camera::class, 'camera_id', 'camera_id');
        }

}
