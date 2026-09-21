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
            'NIM/NISN',
            'Nama Peserta',
            'Sekolah/Kampus',
            'Tanggal',
            'Check In',
            'Check Out',
            'Jenis Presensi',
            'Status',
            'Jarak (m)',
            'Akurasi GPS (m)'
        ];
    }

    public function map($attendance): array
    {
        $jenis = '-';
        if ($attendance->location_type === 'inside') {
            $jenis = 'Dalam Kantor';
        } elseif ($attendance->location_type === 'outside') {
            $jenis = 'Luar Kantor';
        }

        $status = '-';
        if ($attendance->status === 'present') {
            $status = 'HADIR';
        } elseif ($attendance->status === 'late') {
            $status = 'TERLAMBAT';
        } elseif ($attendance->status === 'absent') {
            $status = 'ABSEN';
        }

        return [
            $attendance->student->student_code,
            $attendance->student->user->name,
            $attendance->student->school,
            $attendance->attendance_date->format('Y-m-d'),
            $attendance->check_in_at ? $attendance->check_in_at->format('H:i') : '-',
            $attendance->check_out_at ? $attendance->check_out_at->format('H:i') : '-',
            $jenis,
            $status,
            $attendance->check_in_distance ?? '-',
            $attendance->check_in_accuracy ?? '-'
        ];
    }

    public function styles(Worksheet $sheet): ?array
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['argb' => 'FFFFFFFF'],
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => [
                        'argb' => 'FF0891B2', // Cyan 600
                    ],
                ],
            ],
        ];
    }
}

