<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecognitionLog extends Model
{
    protected $table = 'tbl_recognition_logs';
    protected $primaryKey = 'log_id';
    public $timestamps = false;

    protected $fillable = [
        'recognition_time',
        'camera_name',
        'room_name',
        'building_no',
        'faculty_name',
        'status',
        'distance',
        'faculty_id',
        'camera_id',
        'teaching_load_id',
    ];

    protected $casts = [
        'recognition_time' => 'datetime',
        'distance' => 'decimal:6',
    ];
}
