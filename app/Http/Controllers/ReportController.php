<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Student;
use App\Services\AttendanceReportService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $startDate = (string) $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = (string) $request->input('end_date', now()->endOfMonth()->toDateString());

        $this->syncAbsentees($startDate, $endDate);

        $schools = Student::query()
            ->whereNotNull('school')
            ->where('school', '!=', '')
            ->orderBy('school')
            ->pluck('school')
            ->map(fn (string $school): string => trim($school))
            ->filter()
            ->unique(fn (string $school): string => mb_strtolower($school))
            ->values();

        $query = Attendance::with(['student.user'])
            ->where('attendance_date', '>=', $startDate)
            ->where('attendance_date', '<=', $endDate.' 23:59:59')
            ->orderBy('attendance_date', 'desc');

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->input('student_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('school')) {
            $normalizedSchool = mb_strtolower(trim((string) $request->input('school')));
            $query->whereHas('student', function ($q) use ($normalizedSchool) {
                $q->whereRaw('LOWER(TRIM(school)) = ?', [$normalizedSchool]);
            });
        }

        $attendances = $query->paginate(15)->withQueryString();

        return view('reports.index', compact('attendances', 'startDate', 'endDate', 'schools'));
    }

    public function export(Request $request): BinaryFileResponse
    {
        $startDate = $request->filled('start_date') ? (string) $request->input('start_date') : null;
        $endDate = $request->filled('end_date') ? (string) $request->input('end_date') : null;
        $school = $request->filled('school') ? (string) $request->input('school') : null;
        $status = $request->filled('status') ? (string) $request->input('status') : null;
        $studentId = $request->filled('student_id') ? (string) $request->input('student_id') : null;

        if ($startDate && $endDate) {
            $this->syncAbsentees($startDate, $endDate);
        }

        $schoolSlug = $school ? '-'.Str::slug($school) : '';
        $filename = 'rekap-presensi'.$schoolSlug.'-'.($startDate ?: 'all').'-to-'.($endDate ?: 'all').'.xlsx';

        return Excel::download(
            new AttendanceReportService($startDate, $endDate, $school, $status, $studentId),
            $filename
        );
    }

    public function show(Attendance $attendance): View
    {
        $attendance->load('student.user');

        return view('reports.show', compact('attendance'));
    }

    public function photo(Attendance $attendance): StreamedResponse
    {
        if (! $attendance->check_in_photo_path) {
            abort(404);
        }

        $disk = Storage::disk(config('filesystems.default'));

        // Cek disk utama lebih dulu; jika tidak ada, fallback ke disk local
        if (! $disk->exists($attendance->check_in_photo_path)) {
            if (Storage::disk('local')->exists($attendance->check_in_photo_path)) {
                $disk = Storage::disk('local');
            } else {
                abort(404);
            }
        }

        return $disk->response($attendance->check_in_photo_path);
    }

    public function approve(Request $request, Attendance $attendance): RedirectResponse
    {
        $request->validate([
            'status' => 'required|in:present,late,absent',
        ]);

        $attendance->update([
            'status' => $request->status,
            'is_approved' => true,
        ]);

        return back()->with('success', 'Status presensi berhasil diubah dan disetujui.');
    }

    protected function syncAbsentees(string $startDate, string $endDate): void
    {
        $students = Student::where('status', 'active')->get();
        if ($students->isEmpty()) {
            return;
        }

        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        // Don't backfill for future dates, cap at today
        if ($end->isFuture()) {
            $end = Carbon::today();
        }

        // Generate array of dates
        $dates = [];
        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            // Optional: Skip weekends if company doesn't work on weekends
            if (! $date->isWeekend()) {
                $dates[] = $date->toDateString();
            }
        }

        if (empty($dates)) {
            return;
        }

        $existing = Attendance::query()
            ->where('attendance_date', '>=', $start->toDateString())
            ->where('attendance_date', '<=', $end->toDateString().' 23:59:59')
            ->get(['student_id', 'attendance_date'])
            ->mapWithKeys(fn (Attendance $item): array => [
                $item->student_id.'|'.$item->attendance_date->toDateString() => true,
            ]);

        $todayStr = Carbon::today()->toDateString();
        $isBeforeCutoff = now()->format('H:i') < '17:00';
        $now = now();
        $rows = [];

        foreach ($dates as $dateStr) {
            // For today, only mark absent if it's past 17:00
            if ($dateStr === $todayStr && $isBeforeCutoff) {
                continue;
            }

            $currentDate = Carbon::parse($dateStr);

            foreach ($students as $student) {
                if ($existing->has($student->id.'|'.$dateStr)) {
                    continue;
                }

                // Skip if date is outside student's PKL period
                $studentStart = Carbon::parse($student->start_date)->startOfDay();
                $studentEnd = Carbon::parse($student->end_date)->endOfDay();

                if ($currentDate->lt($studentStart) || $currentDate->gt($studentEnd)) {
                    continue;
                }

                $rows[] = [
                    'student_id' => $student->id,
                    'attendance_date' => $dateStr,
                    'status' => 'absent',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if (! empty($rows)) {
            foreach (array_chunk($rows, 500) as $chunk) {
                Attendance::insertOrIgnore($chunk);
            }
        }
    }
}
