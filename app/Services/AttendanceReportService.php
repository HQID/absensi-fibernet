<?php

namespace App\Services;

use App\Models\Attendance;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AttendanceReportService implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $startDate;
    protected $endDate;

    public function __construct($startDate = null, $endDate = null)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function collection(): \Illuminate\Support\Enumerable
    {
        $query = Attendance::with(['student.user']);

        if ($this->startDate) {
            $query->where('attendance_date', '>=', $this->startDate);
        }
        
        if ($this->endDate) {
            $query->where('attendance_date', '<=', $this->endDate);
        }

        return $query->orderBy('attendance_date', 'desc')->get();
    }

    public function headings(): array
    {
        return [
            'Student Code',
            'Student Name',
            'School/University',
            'Date',
            'Check In',
            'Check Out',
            'Status',
            'Distance Check-In (m)',
            'GPS Accuracy Check-In (m)'
        ];
    }

    public function map($attendance): array
    {
        return [
            $attendance->student->student_code,
            $attendance->student->user->name,
            $attendance->student->school,
            $attendance->attendance_date->format('Y-m-d'),
            $attendance->check_in_at ? $attendance->check_in_at->format('H:i:s') : '-',
            $attendance->check_out_at ? $attendance->check_out_at->format('H:i:s') : '-',
            strtoupper($attendance->status),
            $attendance->check_in_distance ?? '-',
            $attendance->check_in_accuracy ?? '-'
        ];
    }

    public function styles(Worksheet $sheet): ?array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}

