<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Services\AttendanceReportService;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->endOfMonth()->toDateString());

        $this->syncAbsentees($startDate, $endDate);

        $query = Attendance::with(['student.user'])
            ->where('attendance_date', '>=', $startDate)
            ->where('attendance_date', '<=', $endDate)
            ->orderBy('attendance_date', 'desc');

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->input('student_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $attendances = $query->paginate(15)->withQueryString();

        return view('reports.index', compact('attendances', 'startDate', 'endDate'));
    }

    public function export(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        
        if ($startDate && $endDate) {
            $this->syncAbsentees($startDate, $endDate);
        }
        
        $filename = 'rekap-presensi-' . ($startDate ?: 'all') . '-to-' . ($endDate ?: 'all') . '.xlsx';
        
        return Excel::download(new AttendanceReportService($startDate, $endDate), $filename);
    }

    public function show(Attendance $attendance)
    {
        $attendance->load('student.user');
        return view('reports.show', compact('attendance'));
    }

    public function photo(Attendance $attendance)
    {
        if (!$attendance->check_in_photo_path || !\Illuminate\Support\Facades\Storage::disk('local')->exists($attendance->check_in_photo_path)) {
            abort(404);
        }

        return response()->file(\Illuminate\Support\Facades\Storage::disk('local')->path($attendance->check_in_photo_path));
    }

    public function approve(Request $request, Attendance $attendance)
    {
        $request->validate([
            'status' => 'required|in:present,late,absent'
        ]);

        $attendance->update([
            'status' => $request->status,
            'is_approved' => true
        ]);

        return back()->with('success', 'Status presensi berhasil diubah dan disetujui.');
    }

    protected function syncAbsentees($startDate, $endDate)
    {
        $students = \App\Models\Student::where('status', 'active')->get();
        if ($students->isEmpty()) return;

        $start = \Carbon\Carbon::parse($startDate);
        $end = \Carbon\Carbon::parse($endDate);
        
        // Don't backfill for future dates, cap at today
        if ($end->isFuture()) {
            $end = \Carbon\Carbon::today();
        }

        // Generate array of dates
        $dates = [];
        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            // Optional: Skip weekends if company doesn't work on weekends
            if (!$date->isWeekend()) {
                $dates[] = $date->toDateString();
            }
        }

        if (empty($dates)) return;

        foreach ($dates as $dateStr) {
            // For today, only mark absent if it's past 17:00
            if ($dateStr === \Carbon\Carbon::today()->toDateString()) {
                if (now()->format('H:i') < '17:00') {
                    continue; // Skip marking absent for today if it's not yet 17:00
                }
            }

            foreach ($students as $student) {
                // Skip if date is outside student's PKL period
                $studentStart = \Carbon\Carbon::parse($student->start_date)->startOfDay();
                $studentEnd = \Carbon\Carbon::parse($student->end_date)->endOfDay();
                $currentDate = \Carbon\Carbon::parse($dateStr);
                
                if ($currentDate->lt($studentStart) || $currentDate->gt($studentEnd)) {
                    continue;
                }

                // We use firstOrCreate so it doesn't duplicate
                Attendance::firstOrCreate([
                    'student_id' => $student->id,
                    'attendance_date' => $dateStr,
                ], [
                    'status' => 'absent',
                ]);
            }
        }
    }
}
