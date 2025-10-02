<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeachingLoad extends Model
{
    use HasFactory;

    protected $table = 'tbl_teaching_load';
    protected $primaryKey = 'teaching_load_id';
    public $timestamps = true; // Migration uses timestamps

    protected $fillable = [
        'faculty_id',
        'teaching_load_course_code', // corrected
        'teaching_load_subject',
        'teaching_load_day_of_week',
        'teaching_load_time_in',
        'teaching_load_time_out',
        'room_no',
    ];

    // Relationship to Faculty
    public function faculty()
    {
        return $this->belongsTo(Faculty::class, 'faculty_id', 'faculty_id');
    }

    // Relationship to Room
    public function room()
    {
        return $this->belongsTo(Room::class, 'room_no', 'room_no');
    }
}
