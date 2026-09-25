<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Student;
use App\Models\OfficeLocation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Office Location
        OfficeLocation::firstOrCreate(
            ['name' => 'PT Fiber Networks Indonesia Branch Palu'],
            [
                'latitude' => -0.874971, 
                'longitude' => 119.886639, 
                'radius_meters' => 100,
                'max_accuracy_meters' => 50,
                'active' => true,
            ]
        );

        // Admin
        User::firstOrCreate(
            ['email' => 'adminabsensi@fibernet.id'],
            [
                'name' => 'Administrator',
                'password' => Hash::make('fibernetpalupcs2026'),
                'role' => 'admin',
            ]
        );

    }
}