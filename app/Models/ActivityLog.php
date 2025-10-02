<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use HasFactory;

    protected $table = 'tbl_activity_logs';
    protected $primaryKey = 'logs_id';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'logs_action',
        'logs_description',
        'logs_timestamp',
        'logs_module',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
