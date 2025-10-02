<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory;

    protected $table = 'tbl_room';
    protected $primaryKey = 'room_no';
    public $timestamps = false;

    protected $fillable = [
        'room_name',
        'room_building_no',
    ];

    public function teachingLoads()
    {
        return $this->hasMany(TeachingLoad::class, 'room_no');
    }

    public function cameras()
    {
        return $this->hasMany(Camera::class, 'room_no');
    }
}
