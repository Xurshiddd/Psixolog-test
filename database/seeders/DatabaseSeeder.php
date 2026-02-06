<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    { 
        User::factory(100)->create();
        User::create([
            'name' => 'Admin',
            'email' => 'marifovxurshid8@gmail.com',
            'login' => 454546464,
            'password' => bcrypt('RootPassword'),
            'role' => 'admin',
        ]);
        User::create([
            'name' => 'Dildora Ikromovna',
            'email' => 'dildora@ttysi.com',
            'login' => 123456789,
            'password' => bcrypt('RootPassword'),
            'role' => 'admin',
        ]);
        User::create([
            'name' => 'Student',
            'email' => 'student@ttysi.com',
            'login' => 123456789,
            'password' => bcrypt('RootPassword'),
            'role' => 'student',
        ]);
    }
}
