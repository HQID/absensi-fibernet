<?php

use App\Livewire\StudentsIndex;
use App\Models\Attendance;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('two-step student deletion blocks wrong confirmation and deletes student data plus supabase s3 photos when valid', function () {
    Storage::fake('s3');
    config(['filesystems.default' => 's3']);

    $admin = User::factory()->create(['role' => 'admin']);

    $targetUser = User::factory()->create(['name' => 'Rizky PKL', 'role' => 'student']);
    $targetStudent = Student::create([
        'user_id' => $targetUser->id,
        'student_code' => 'F55122099',
        'school' => 'Universitas Tadulako',
        'major' => 'Teknik Informatika',
        'start_date' => now()->subMonths(2)->toDateString(),
        'end_date' => now()->subDays(5)->toDateString(),
        'status' => 'inactive',
    ]);

    $otherUser = User::factory()->create(['name' => 'Dewi PKL', 'role' => 'student']);
    $otherStudent = Student::create([
        'user_id' => $otherUser->id,
        'student_code' => 'F55122100',
        'school' => 'SMKN 3 Palu',
        'major' => 'TKJ',
        'start_date' => now()->subDays(10)->toDateString(),
        'end_date' => now()->addDays(20)->toDateString(),
        'status' => 'active',
    ]);

    $targetPhoto1 = "attendance/2026/10/01/student_{$targetStudent->id}_checkin_1.jpg";
    $targetPhoto2 = "attendance/2026/10/02/student_{$targetStudent->id}_checkin_2.jpg";
    $otherPhoto = "attendance/2026/10/01/student_{$otherStudent->id}_checkin_1.jpg";

    Storage::disk('s3')->put($targetPhoto1, 'fake-image-1');
    Storage::disk('s3')->put($targetPhoto2, 'fake-image-2');
    Storage::disk('s3')->put($otherPhoto, 'fake-image-other');

    Attendance::create([
        'student_id' => $targetStudent->id,
        'attendance_date' => '2026-10-01',
        'status' => 'present',
        'check_in_photo_path' => $targetPhoto1,
    ]);
    Attendance::create([
        'student_id' => $targetStudent->id,
        'attendance_date' => '2026-10-02',
        'status' => 'late',
        'check_in_photo_path' => $targetPhoto2,
    ]);
    Attendance::create([
        'student_id' => $targetStudent->id,
        'attendance_date' => '2026-10-03',
        'status' => 'absent',
    ]);
    Attendance::create([
        'student_id' => $otherStudent->id,
        'attendance_date' => '2026-10-01',
        'status' => 'present',
        'check_in_photo_path' => $otherPhoto,
    ]);

    $component = Livewire::actingAs($admin)
        ->test(StudentsIndex::class)
        ->call('confirmDeleteStudent', $targetStudent->id)
        ->assertSet('deleteStep', 1)
        ->assertSet('deleteAttendanceCount', 3)
        ->assertSet('deletePhotoCount', 2);

    // Calling deleteStudent on step 1 must do nothing
    $component->call('deleteStudent');
    expect($targetStudent->fresh())->not->toBeNull();

    // Proceed to step 2 and submit wrong code -> rejected
    $component->call('proceedToFinalDeleteStep')
        ->assertSet('deleteStep', 2)
        ->set('deleteConfirmationInput', 'SALAH-NIM')
        ->call('deleteStudent')
        ->assertHasErrors(['deleteConfirmationInput']);

    expect($targetStudent->fresh())->not->toBeNull();
    Storage::disk('s3')->assertExists($targetPhoto1);

    // Submit exact student_code -> deletes target user, student, attendances, and ONLY target's S3 photos
    $component->set('deleteConfirmationInput', 'F55122099')
        ->call('deleteStudent')
        ->assertHasNoErrors();

    expect($targetUser->fresh())->toBeNull()
        ->and($targetStudent->fresh())->toBeNull()
        ->and(Attendance::where('student_id', $targetStudent->id)->count())->toBe(0);

    Storage::disk('s3')->assertMissing($targetPhoto1);
    Storage::disk('s3')->assertMissing($targetPhoto2);

    // Other student's data & photo in the same date folder remain untouched
    expect($otherStudent->fresh())->not->toBeNull();
    Storage::disk('s3')->assertExists($otherPhoto);
});
