<?php

use App\Models\Attendance;
use App\Models\OfficeLocation;
use App\Models\Student;
use App\Models\User;
use App\Services\AttendanceService;
use App\Services\StorageUsageService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Livewire;

test('livewire temp upload defaults to local disk and check-in immediately deletes temporary photo', function () {
    Storage::fake('local');
    Storage::fake('s3');
    config(['filesystems.default' => 's3']);

    $this->travelTo(now()->setTime(8, 5, 0));

    expect(config('livewire.temporary_file_upload.disk'))->toBe('local');

    OfficeLocation::create([
        'name' => 'Kantor Fibernet Palu',
        'latitude' => -0.897587,
        'longitude' => 119.873215,
        'radius_meters' => 100,
        'max_accuracy_meters' => 50,
        'active' => true,
    ]);

    $user = User::factory()->create(['role' => 'student']);
    $student = Student::create([
        'user_id' => $user->id,
        'student_code' => 'F55122001',
        'school' => 'Universitas Tadulako',
        'major' => 'Teknik Informatika',
        'start_date' => now()->subDays(5)->toDateString(),
        'end_date' => now()->addDays(30)->toDateString(),
        'status' => 'active',
    ]);

    Storage::disk('local')->put('livewire-tmp/selfie-temp.jpg', str_repeat('a', 2048));
    $tempPhoto = new TemporaryUploadedFile('selfie-temp.jpg', 'local');

    $attendance = app(AttendanceService::class)->checkIn(
        student: $student,
        latitude: -0.897587,
        longitude: 119.873215,
        accuracy: 10.0,
        photo: $tempPhoto,
        locationType: 'inside',
    );

    // Final photo exists on s3, while temporary file is gone from both local and s3
    Storage::disk('s3')->assertExists($attendance->check_in_photo_path);
    expect(Storage::disk('s3')->allFiles('livewire-tmp'))->toBeEmpty();
    Storage::disk('local')->assertMissing('livewire-tmp/selfie-temp.jpg');
});

test('admin storage settings calculates active, temp, and orphan usage and cleans only old junk files', function () {
    Storage::fake('s3');
    Cache::forget(StorageUsageService::CACHE_KEY);
    config([
        'filesystems.default' => 's3',
        'filesystems.disks.s3.bucket' => 'photos',
        'filesystems.storage_quota_mb' => 1024,
    ]);

    $admin = User::factory()->create(['role' => 'admin']);
    $studentUser = User::factory()->create(['role' => 'student']);
    $student = Student::create([
        'user_id' => $studentUser->id,
        'student_code' => 'F55122077',
        'school' => 'SMKN 3 Palu',
        'major' => 'TKJ',
        'start_date' => now()->subDays(10)->toDateString(),
        'end_date' => now()->addDays(20)->toDateString(),
        'status' => 'active',
    ]);

    // Student cannot access /settings/storage
    $this->actingAs($studentUser)
        ->get(route('storage.edit'))
        ->assertForbidden();

    $activePath = "attendance/2026/10/28/student_{$student->id}_checkin_active.jpg";
    $orphanOldPath = 'attendance/2026/10/20/orphan_deleted_row.jpg';
    $tempOldPath = 'livewire-tmp/5PjbuGhLvpYtd0K8pHDTg4-meta.jpg';
    $tempRecentPath = 'livewire-tmp/in-flight-recent.jpg';

    Storage::disk('s3')->put($activePath, str_repeat('x', 4000));
    Storage::disk('s3')->put($orphanOldPath, str_repeat('y', 1500));
    Storage::disk('s3')->put($tempOldPath, str_repeat('z', 2500));

    Attendance::create([
        'student_id' => $student->id,
        'attendance_date' => '2026-10-28',
        'status' => 'present',
        'check_in_photo_path' => $activePath,
    ]);

    // Advance clock 20 minutes so the 3 files above are > 15 minutes old, then create 1 recent file (< 15m)
    $this->travel(20)->minutes();
    Storage::disk('s3')->put($tempRecentPath, str_repeat('r', 1000));
    @touch(Storage::disk('s3')->path($tempRecentPath), now()->getTimestamp());

    $this->actingAs($admin)
        ->get(route('storage.edit'))
        ->assertOk()
        ->assertSee('Informasi Penyimpanan');

    $component = Livewire::actingAs($admin)
        ->test('pages::settings.storage')
        ->assertSet('stats.active_count', 1)
        ->assertSet('stats.active_bytes', 4000)
        ->assertSet('stats.temp_count', 2)
        ->assertSet('stats.temp_bytes', 3500)
        ->assertSet('stats.orphan_count', 1)
        ->assertSet('stats.orphan_bytes', 1500)
        ->assertSet('stats.cleanable_count', 2)
        ->assertSet('stats.cleanable_bytes', 4000);

    $component->call('cleanupStorage')
        ->assertSet('stats.active_count', 1)
        ->assertSet('stats.temp_count', 1)
        ->assertSet('stats.orphan_count', 0)
        ->assertSet('stats.cleanable_count', 0);

    // Old temp & orphan deleted; active photo & recent (<15m) temp preserved
    Storage::disk('s3')->assertMissing($tempOldPath);
    Storage::disk('s3')->assertMissing($orphanOldPath);
    Storage::disk('s3')->assertExists($activePath);
    Storage::disk('s3')->assertExists($tempRecentPath);
});
