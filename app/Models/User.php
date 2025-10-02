<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $table = 'tbl_user';
    protected $primaryKey = 'user_id';
    public $timestamps = true;

    protected $fillable = [
        'user_id',
        'user_role',
        'user_department',
        'user_fname',
        'user_lname',
        'username',
        'user_password',
    ];

    protected $hidden = [
        'user_password',
    ];

     public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class, 'user_id');
    }

    // Tell Laravel what the password column is
    public function getAuthPassword()
    {
        return $this->user_password;
    }
}
