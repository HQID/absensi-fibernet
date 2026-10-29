<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\OfficeLocation;
use App\Models\Student;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use RuntimeException;

class AttendanceService
{
    protected GeofenceService $geofenceService;

    public function __construct(GeofenceService $geofenceService)
    {
        $this->geofenceService = $geofenceService;
    }

    /**
     * Handle student check-in
     */
    public function checkIn(Student $student, float $latitude, float $longitude, float $accuracy, UploadedFile $photo, string $locationType = 'inside', string $reason = ''): Attendance
    {
        $office = OfficeLocation::where('active', true)->first();
        if (! $office) {
            throw new \Exception('Lokasi kantor aktif tidak ditemukan.');
        }

        $distance = 0;

        // Validate Geofence only if location is inside
        if ($locationType === 'inside') {
            $validation = $this->geofenceService->validateLocation($latitude, $longitude, $accuracy, $office);
            if (! $validation['valid']) {
                throw new \Exception($validation['message']);
            }
            $distance = $validation['distance'];
        }

        // Block if after 17:00
        $closingTime = Carbon::today()->setHour(17)->setMinute(0)->setSecond(0);
        if (now()->greaterThan($closingTime)) {
            throw new \Exception('Batas waktu presensi hari ini telah berakhir (Pukul 17:00). Anda tercatat sebagai Alpa/Tidak Hadir.');
        }

        // Check if already checked in today
        $today = Carbon::today();
        $todayDate = $today->toDateString();
        $attendance = Attendance::where('student_id', $student->id)
            ->where('attendance_date', '>=', $todayDate)
            ->where('attendance_date', '<=', $todayDate.' 23:59:59')
            ->first();

        if ($attendance && $attendance->check_in_at) {
            throw new \Exception('Anda sudah melakukan check-in hari ini.');
        }

        // Determine status
        $status = $this->determineStatus(now());

        // Store photo
        $path = $this->storePhoto($student, $photo);

        // Create Attendance
        return Attendance::create([
            'student_id' => $student->id,
            'attendance_date' => $today->toDateString(),
            'check_in_at' => now(),
            'check_in_latitude' => $latitude,
            'check_in_longitude' => $longitude,
            'check_in_accuracy' => $accuracy,
            'check_in_distance' => $locationType === 'inside' ? $distance : null,
            'check_in_photo_path' => $path,
            'status' => $status,
            'location_type' => $locationType,
            'reason' => $reason ?: null,
            'is_approved' => ($locationType === 'outside' || $status === 'late') ? false : true,
        ]);
    }

    /**
     * Handle student check-out
     */
    public function checkOut(Student $student, float $latitude, float $longitude, float $accuracy): Attendance
    {
        $office = OfficeLocation::where('active', true)->first();
        if (! $office) {
            throw new \Exception('Lokasi kantor aktif tidak ditemukan.');
        }

        $today = Carbon::today();
        $todayDate = $today->toDateString();
        $attendance = Attendance::where('student_id', $student->id)
            ->where('attendance_date', '>=', $todayDate)
            ->where('attendance_date', '<=', $todayDate.' 23:59:59')
            ->first();

        if (! $attendance || ! $attendance->check_in_at) {
            throw new \Exception('Anda belum melakukan check-in hari ini.');
        }

        if ($attendance->check_out_at) {
            throw new \Exception('Anda sudah melakukan check-out hari ini.');
        }

        $distance = null;

        // Validate Geofence only if location type is inside
        if ($attendance->location_type === 'inside') {
            $validation = $this->geofenceService->validateLocation($latitude, $longitude, $accuracy, $office);
            if (! $validation['valid']) {
                throw new \Exception($validation['message']);
            }
            $distance = $validation['distance'];
        }

        $attendance->update([
            'check_out_at' => now(),
            'check_out_latitude' => $latitude,
            'check_out_longitude' => $longitude,
            'check_out_accuracy' => $accuracy,
            'check_out_distance' => $distance,
        ]);

        return $attendance;
    }

    /**
     * Determine attendance status based on time
     */
    protected function determineStatus(CarbonInterface $time): string
    {
        // 08:00 - 08:15 is Present, after 08:15 is Late
        $limit = Carbon::today()->setHour(8)->setMinute(15)->setSecond(0);

        return $time->lte($limit) ? 'present' : 'late';
    }

    /**
     * Store check-in photo locally
     */
    protected function storePhoto(Student $student, UploadedFile $photo): string
    {
        $year = now()->format('Y');
        $month = now()->format('m');
        $day = now()->format('d');

        $directory = "attendance/{$year}/{$month}/{$day}";
        $extension = $photo->getClientOriginalExtension() ?: 'jpg';
        $filename = "student_{$student->id}_checkin_".time().'.'.$extension;

        $disk = (string) config('filesystems.default', 'local');
        $storedPath = $photo->storeAs($directory, $filename, $disk);

        if (! is_string($storedPath) || $storedPath === '') {
            throw new RuntimeException('Gagal menyimpan foto presensi ke penyimpanan.');
        }

        if ($photo instanceof TemporaryUploadedFile) {
            rescue(static fn () => $photo->delete(), report: false);
        }

        Cache::forget(StorageUsageService::CACHE_KEY);

        return $storedPath;
    }
}
