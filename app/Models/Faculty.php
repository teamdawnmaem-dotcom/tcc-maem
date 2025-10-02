<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Faculty extends Model
{
    use HasFactory;

    protected $table = 'tbl_faculty';
    protected $primaryKey = 'faculty_id';
    public $timestamps = false;

    protected $fillable = [
        'faculty_fname',
        'faculty_lname',
        'faculty_department',
        'faculty_images',
        'faculty_face_embedding',
    ];
    
    protected $casts = [
        'faculty_images' => 'array', // This will automatically handle JSON <-> array conversion
    ];
    
    // One Faculty has many teaching loads
    public function teachingLoads()
    {
        return $this->hasMany(TeachingLoad::class, 'faculty_id');
    }

    // One Faculty has many leave/pass slips
    public function leavePasses()
    {
        return $this->hasMany(LeavePass::class, 'faculty_id');
    }

    // One Faculty has many attendance records
    public function attendanceRecords()
    {
        return $this->hasMany(AttendanceRecord::class, 'faculty_id');
    }
}
