<?php

namespace Database\Seeders;

use App\Models\Speciality;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Group;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    { 
        // for ($i = 1; $i <= 10; $i++) {
        //     Group::create([
        //         'name' => "Group $i",
        //         'code' => 1000 + $i,
        //         'education_lang' => 'Uzbek',
        //         'education_form' => 'Full-time',
        //         'education_type' => 'Bachelor',
        //     ]);
        //     Speciality::create([
        //         'name' => "Speciality $i",
        //         'code' => 2000 + $i,
        //     ]);
        // }
        // User::factory(1000)->create();
    }
}
