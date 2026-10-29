<?php

namespace App\Livewire;

use App\Models\Attendance;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Defer;
use Livewire\Component;

#[Defer]
class DashboardContent extends Component
{
    public function placeholder(): View
    {
        return view('livewire.placeholders.dashboard-skeleton');
    }

    public function render(): View
    {
        $user = Auth::user();

        if ($user && $user->isStudent()) {
            $student = $user->student()->first();
            $attendances = collect();
            $todayAttendance = null;

            if ($student) {
                $attendances = Attendance::where('student_id', $student->id)
                    ->orderBy('attendance_date', 'desc')
                    ->take(10)
                    ->get();

                $todayStr = Carbon::today()->toDateString();
                $todayAttendance = Attendance::where('student_id', $student->id)
                    ->where('attendance_date', '>=', $todayStr)
                    ->where('attendance_date', '<=', $todayStr.' 23:59:59')
                    ->first();
            }

            return view('livewire.dashboard-student-content', compact(
                'attendances',
                'student',
                'todayAttendance',
            ));
        }

        $today = Carbon::today()->toDateString();
        $totalStudents = Student::where('status', 'active')->count();

        $attendancesToday = Attendance::with(['student.user'])
            ->where('attendance_date', '>=', $today)
            ->where('attendance_date', '<=', $today.' 23:59:59')
            ->get();

        $presentCount = $attendancesToday->where('status', 'present')->count();
        $lateCount = $attendancesToday->where('status', 'late')->count();
        $absentCount = max(0, $totalStudents - ($presentCount + $lateCount));

        return view('livewire.dashboard-admin-content', compact(
            'totalStudents',
            'presentCount',
            'lateCount',
            'absentCount',
            'attendancesToday',
        ));
    }
}
