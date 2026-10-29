<?php

use App\Livewire\DashboardContent;
use App\Models\Attendance;
use App\Models\Student;
use App\Models\User;
use App\Services\AttendanceReportService;
use Livewire\Livewire;

test('dashboard renders deferred skeleton on initial request and hydrates content', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('data-flux-skeleton-group', false);

    Livewire::actingAs($admin)
        ->withoutLazyLoading()
        ->test(DashboardContent::class)
        ->assertSee('Total Peserta PKL')
        ->assertSee('Daftar Presensi Hari Ini');
});

test('reports filters attendance and excel export by school case-insensitively', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $userUntad1 = User::factory()->create(['name' => 'Budi Untad', 'role' => 'student']);
    $studentUntad1 = Student::create([
        'user_id' => $userUntad1->id,
        'student_code' => 'F55122001',
        'school' => 'Universitas Tadulako',
        'major' => 'Teknik Informatika',
        'start_date' => now()->subDays(5)->toDateString(),
        'end_date' => now()->addDays(30)->toDateString(),
        'status' => 'active',
    ]);

    $userUntad2 = User::factory()->create(['name' => 'Siti Untad', 'role' => 'student']);
    $studentUntad2 = Student::create([
        'user_id' => $userUntad2->id,
        'student_code' => 'F55122002',
        'school' => '  universitas tadulako ',
        'major' => 'Sistem Informasi',
        'start_date' => now()->subDays(5)->toDateString(),
        'end_date' => now()->addDays(30)->toDateString(),
        'status' => 'active',
    ]);

    $userSmk = User::factory()->create(['name' => 'Andi SMK', 'role' => 'student']);
    $studentSmk = Student::create([
        'user_id' => $userSmk->id,
        'student_code' => 'SMK001',
        'school' => 'SMKN 3 Palu',
        'major' => 'TKJ',
        'start_date' => now()->subDays(5)->toDateString(),
        'end_date' => now()->addDays(30)->toDateString(),
        'status' => 'active',
    ]);

    $today = now()->toDateString();

    Attendance::create([
        'student_id' => $studentUntad1->id,
        'attendance_date' => $today,
        'status' => 'present',
    ]);
    Attendance::create([
        'student_id' => $studentUntad2->id,
        'attendance_date' => $today,
        'status' => 'late',
    ]);
    Attendance::create([
        'student_id' => $studentSmk->id,
        'attendance_date' => $today,
        'status' => 'present',
    ]);

    $response = $this->actingAs($admin)->get(route('reports', [
        'start_date' => $today,
        'end_date' => $today,
        'school' => 'Universitas Tadulako',
    ]));

    $response->assertOk()
        ->assertSee('Budi Untad')
        ->assertSee('Siti Untad')
        ->assertDontSee('Andi SMK')
        ->assertViewHas('schools', fn ($schools) => $schools->count() === 2);

    $exportedRows = (new AttendanceReportService(
        startDate: $today,
        endDate: $today,
        school: 'Universitas Tadulako',
        status: 'present',
    ))->collection();

    expect($exportedRows)->toHaveCount(1)
        ->and($exportedRows->first()->student_id)->toBe($studentUntad1->id);
});
