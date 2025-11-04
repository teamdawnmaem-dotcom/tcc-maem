<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeachingLoadArchive extends Model
{
    use HasFactory;

    protected $table = 'tbl_teaching_load_archive';
    protected $primaryKey = 'archive_id';
    public $timestamps = false;

    protected $fillable = [
        'original_teaching_load_id',
        'faculty_id',
        'teaching_load_course_code',
        'teaching_load_subject',
        'teaching_load_class_section',
        'teaching_load_day_of_week',
        'teaching_load_time_in',
        'teaching_load_time_out',
        'room_no',
        'school_year',
        'semester',
        'archived_at',
        'archived_by',
        'archive_notes',
    ];

    protected $casts = [
        'archived_at' => 'datetime',
    ];

    // Relationship to Faculty
    public function faculty()
    {
        return $this->belongsTo(Faculty::class, 'faculty_id');
    }

    // Relationship to Room
    public function room()
    {
        return $this->belongsTo(Room::class, 'room_no', 'room_no');
    }

    // Relationship to User who archived
    public function archivedBy()
    {
        return $this->belongsTo(User::class, 'archived_by');
    }
}