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
            ['email' => 'admin@pkl.test'],
            [
                'name' => 'Administrator',
                'password' => Hash::make('password123'),
                'role' => 'admin',
            ]
        );

        // Dummy Students
        $students = [
            [
                'email' => 'ahmad@pkl.test',
                'name' => 'Ahmad',
                'code' => 'PKL-001',
                'school' => 'Universitas A',
                'major' => 'Informatika'
            ],
            [
                'email' => 'budi@pkl.test',
                'name' => 'Budi',
                'code' => 'PKL-002',
                'school' => 'Universitas B',
                'major' => 'Informatika'
            ],
            [
                'email' => 'citra@pkl.test',
                'name' => 'Citra',
                'code' => 'PKL-003',
                'school' => 'SMK A',
                'major' => 'TKJ'
            ]
        ];

        foreach ($students as $studentData) {
            $user = User::firstOrCreate(
                ['email' => $studentData['email']],
                [
                    'name' => $studentData['name'],
                    'password' => Hash::make('password123'),
                    'role' => 'student',
                ]
            );

            Student::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'student_code' => $studentData['code'],
                    'school' => $studentData['school'],
                    'major' => $studentData['major'],
                    'start_date' => now()->startOfMonth(),
                    'end_date' => now()->addMonths(3)->endOfMonth(),
                    'status' => 'active',
                ]
            );
        }
    }
}