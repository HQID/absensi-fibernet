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
        
        $filename = 'rekap-presensi-' . ($startDate ?: 'all') . '-to-' . ($endDate ?: 'all') . '.xlsx';
        
        return Excel::download(new AttendanceReportService($startDate, $endDate), $filename);
    }
}
