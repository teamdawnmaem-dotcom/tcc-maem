<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OfficialMatter extends Model
{
    use HasFactory;

    protected $table = 'tbl_official_matters';
    protected $primaryKey = 'om_id';
    public $timestamps = true;

    protected $fillable = [
        'faculty_id',
        'om_department',
        'om_purpose',
        'om_remarks',
        'om_start_date',
        'om_end_date',
        'om_attachment',
    ];

    public function faculty()
    {
        return $this->belongsTo(Faculty::class, 'faculty_id', 'faculty_id');
    }
}
