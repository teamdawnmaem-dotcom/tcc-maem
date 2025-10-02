<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Pass extends Model
{
    use HasFactory;

    protected $table = 'tbl_leave_pass';
    protected $primaryKey = 'lp_id';
    public $timestamps = false;

    protected $fillable = [
        'lp_type',
        'pass_slip_itinerary',
        'lp_purpose',
        'pass_slip_date',
        'pass_slip_departure_time',
        'pass_slip_arrival_time',
        'faculty_id',
        'lp_image',
    ];

    // Always filter by lp_type = 'Pass'
    protected static function booted()
    {
        static::addGlobalScope('pass', function (Builder $builder) {
            $builder->where('lp_type', 'Pass');
        });
    }

    public function faculty()
    {
        return $this->belongsTo(Faculty::class, 'faculty_id');
    }
}
