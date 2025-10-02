<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Camera extends Model
{
    use HasFactory;

    protected $table = 'tbl_camera';
    protected $primaryKey = 'camera_id';
    public $timestamps = false;

    protected $fillable = [
        'camera_name',
        'camera_ip_address',
        'camera_username',
        'camera_password',
        'camera_live_feed',
        'room_no',
    ];

    public function room()
    {
        return $this->belongsTo(Room::class, 'room_no');
    }
}
