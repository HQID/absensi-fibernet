<?php

namespace App\Http\Controllers;

use Illuminate\View\View;
use App\Models\Student;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();
        
        if ($user->isStudent()) {
            return $this->studentDashboard($user);
        }
        
        return $this->adminDashboard();
    }
    
    protected function adminDashboard(): View
    {
        $today = Carbon::today()->toDateString();
        
        $totalStudents = Student::where('status', 'active')->count();
        
        $attendancesToday = Attendance::with(['student.user'])
            ->where('attendance_date', $today)
            ->get();
            
        $presentCount = $attendancesToday->where('status', 'present')->count();
        $lateCount = $attendancesToday->where('status', 'late')->count();
        $absentCount = $totalStudents - ($presentCount + $lateCount);
        
        return view('dashboard.admin', compact(
            'totalStudents', 'presentCount', 'lateCount', 'absentCount', 'attendancesToday'
        ));
    }
    
    protected function studentDashboard($user): View
    {
        $student = $user->student;
        $attendances = collect();
        if ($student) {
            $attendances = Attendance::where('student_id', $student->id)
                ->orderBy('attendance_date', 'desc')
                ->take(10)
                ->get();
        }
        
        return view('dashboard.student', compact('attendances', 'student'));
    }
}