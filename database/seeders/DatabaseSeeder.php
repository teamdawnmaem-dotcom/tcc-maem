<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        User::firstOrCreate(
            ['user_id' => '2025001','user_role' => 'Admin','user_department' => 'VPAA', 'user_fname' => 'Kharen Jane', 'user_lname' => 'Ungab', 'username' => 'ADMIN2025',],
            [
                'user_password' => Hash::make('admin123') // change to your secure password
            ]
        );
    }
}
