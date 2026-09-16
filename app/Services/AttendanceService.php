<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\OfficeLocation;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

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
    public function checkIn(Student $student, float $latitude, float $longitude, float $accuracy, UploadedFile $photo)
    {
        $office = OfficeLocation::where('active', true)->first();
        if (!$office) {
            throw new \Exception('Lokasi kantor aktif tidak ditemukan.');
        }

        // Validate Geofence
        $validation = $this->geofenceService->validateLocation($latitude, $longitude, $accuracy, $office);
        if (!$validation['valid']) {
            throw new \Exception($validation['message']);
        }

        // Check if already checked in today
        $today = Carbon::today();
        $attendance = Attendance::where('student_id', $student->id)
            ->where('attendance_date', $today->toDateString())
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
            'check_in_distance' => $validation['distance'],
            'check_in_photo_path' => $path,
            'status' => $status,
        ]);
    }

    /**
     * Handle student check-out
     */
    public function checkOut(Student $student, float $latitude, float $longitude, float $accuracy)
    {
        $office = OfficeLocation::where('active', true)->first();
        if (!$office) {
            throw new \Exception('Lokasi kantor aktif tidak ditemukan.');
        }

        // Validate Geofence
        $validation = $this->geofenceService->validateLocation($latitude, $longitude, $accuracy, $office);
        if (!$validation['valid']) {
            throw new \Exception($validation['message']);
        }

        $today = Carbon::today();
        $attendance = Attendance::where('student_id', $student->id)
            ->where('attendance_date', $today->toDateString())
            ->first();

        if (!$attendance || !$attendance->check_in_at) {
            throw new \Exception('Anda belum melakukan check-in hari ini.');
        }

        if ($attendance->check_out_at) {
            throw new \Exception('Anda sudah melakukan check-out hari ini.');
        }

        $attendance->update([
            'check_out_at' => now(),
            'check_out_latitude' => $latitude,
            'check_out_longitude' => $longitude,
            'check_out_accuracy' => $accuracy,
            'check_out_distance' => $validation['distance'],
        ]);

        return $attendance;
    }

    /**
     * Determine attendance status based on time
     */
    protected function determineStatus(\Carbon\CarbonInterface $time): string
    {
        // Example configuration: <= 08:00 Present, > 08:00 Late
        $limit = Carbon::today()->setHour(8)->setMinute(0)->setSecond(0);
        
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
        $filename = "student_{$student->id}_checkin_" . time() . '.' . $photo->getClientOriginalExtension();
        
        // Use Laravel Storage, typically 'local' or 'public'
        // According to PRD, shouldn't be publicly accessible directly, so store in local/private storage
        return $photo->storeAs($directory, $filename, 'local');
    }
}

