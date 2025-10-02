<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Leave extends Model
{
    use HasFactory;

    protected $table = 'tbl_leave_pass';
    protected $primaryKey = 'lp_id';
    public $timestamps = false;

    protected $fillable = [
        'lp_type',
        'lp_purpose',
        'faculty_id',
        'leave_start_date',
        'leave_end_date',
        'lp_image',
    ];

    // Always filter by lp_type = 'Leave'
    protected static function booted()
    {
        static::addGlobalScope('leave', function (Builder $builder) {
            $builder->where('lp_type', 'Leave');
        });
    }

    public function faculty()
    {
        return $this->belongsTo(Faculty::class, 'faculty_id');
    }
}
