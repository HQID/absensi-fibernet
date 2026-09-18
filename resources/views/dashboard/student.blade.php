<x-layouts::app :title="__('Student Dashboard')">
    <div class="space-y-6">

        {{-- Page Header --}}
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">
                Halo, {{ Auth::user()->name }}
            </h1>
            <p class="mt-1 text-sm text-gray-500">
                Berikut adalah riwayat presensi Anda.
            </p>
        </div>

        <div class="grid grid-cols-1 gap-6 mb-6">
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm flex flex-col sm:flex-row items-center justify-between">
                <div class="text-center sm:text-left mb-4 sm:mb-0">
                    <p class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-1">Waktu Saat Ini</p>
                    <div x-data="{ time: new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' }) }" 
                         x-init="setInterval(() => time = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' }), 1000)" 
                         class="text-4xl font-bold text-gray-900" x-text="time">
                    </div>
                    <p class="text-sm text-gray-500 mt-1">{{ \Carbon\Carbon::now()->translatedFormat('l, d F Y') }}</p>
                </div>
                
                <div class="text-center sm:text-right">
                    <p class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-2">Status Hari Ini</p>
                    @if($todayAttendance)
                        @if($todayAttendance->status === 'present')
                            <span class="inline-flex items-center rounded-md bg-green-100 px-3 py-1.5 text-sm font-semibold text-green-800 ring-1 ring-inset ring-green-600/20">HADIR</span>
                        @elseif($todayAttendance->status === 'late')
                            <span class="inline-flex items-center rounded-md bg-yellow-100 px-3 py-1.5 text-sm font-semibold text-yellow-800 ring-1 ring-inset ring-yellow-600/20">TERLAMBAT</span>
                        @else
                            <span class="inline-flex items-center rounded-md bg-red-100 px-3 py-1.5 text-sm font-semibold text-red-800 ring-1 ring-inset ring-red-600/20">ALPA / TIDAK HADIR</span>
                        @endif
                    @else
                        <span class="inline-flex items-center rounded-md bg-gray-100 px-3 py-1.5 text-sm font-semibold text-gray-800 ring-1 ring-inset ring-gray-600/20">BELUM PRESENSI</span>
                    @endif
                </div>
            </div>
        </div>

        <div class="grid gap-6 md:grid-cols-3">
            <div class="md:col-span-1">
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm mb-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Profil PKL</h3>
                    @if($student)
                        <dl class="space-y-3 text-sm">
                            <div>
                                <dt class="text-gray-500">NIM/NIS</dt>
                                <dd class="font-medium text-gray-900">{{ $student->student_code }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500">Asal Sekolah/Kampus</dt>
                                <dd class="font-medium text-gray-900">{{ $student->school }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500">Jurusan</dt>
                                <dd class="font-medium text-gray-900">{{ $student->major }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500">Periode</dt>
                                <dd class="font-medium text-gray-900">{{ $student->start_date->format('d M Y') }} - {{ $student->end_date->format('d M Y') }}</dd>
                            </div>
                        </dl>
                    @else
                        <p class="text-gray-500">Data siswa tidak ditemukan.</p>
                    @endif
                </div>

                <div class="rounded-xl border border-blue-200 bg-blue-50 p-6 shadow-sm">
                    <h3 class="text-lg font-medium text-blue-900 mb-2">Presensi Hari Ini</h3>
                    <p class="text-sm text-blue-700 mb-4">Lakukan presensi kehadiran Anda menggunakan fitur geofencing.</p>
                    <a href="{{ route('attendance') }}" class="inline-flex w-full justify-center items-center rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-blue-700">
                        Buka Halaman Presensi
                    </a>
                </div>
            </div>

            <div class="md:col-span-2">
                <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                    <div class="border-b border-gray-200 px-6 py-4 bg-gray-50">
                        <h2 class="font-semibold text-gray-900">
                            10 Riwayat Presensi Terakhir
                        </h2>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-gray-500">
                            <thead class="bg-white text-xs uppercase text-gray-700 border-b border-gray-200">
                                <tr>
                                    <th scope="col" class="px-6 py-3">Tanggal</th>
                                    <th scope="col" class="px-6 py-3">Check In</th>
                                    <th scope="col" class="px-6 py-3">Check Out</th>
                                    <th scope="col" class="px-6 py-3">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                @forelse($attendances as $attendance)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 font-medium text-gray-900">
                                            {{ $attendance->attendance_date->format('d M Y') }}
                                        </td>
                                        <td class="px-6 py-4">
                                            @if($attendance->check_in_at)
                                                <span class="text-gray-900">{{ $attendance->check_in_at->format('H:i') }}</span>
                                            @else
                                                <span class="text-gray-400">-</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4">
                                            @if($attendance->check_out_at)
                                                <span class="text-gray-900">{{ $attendance->check_out_at->format('H:i') }}</span>
                                            @else
                                                <span class="text-gray-400">-</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4">
                                            @if($attendance->status === 'present')
                                                <span class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">Hadir</span>
                                            @elseif($attendance->status === 'late')
                                                <span class="inline-flex items-center rounded-md bg-yellow-50 px-2 py-1 text-xs font-medium text-yellow-800 ring-1 ring-inset ring-yellow-600/20">Terlambat</span>
                                            @else
                                                <span class="inline-flex items-center rounded-md bg-red-50 px-2 py-1 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/10">Absen</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-6 py-12 text-center text-sm text-gray-500">
                                            Belum ada riwayat presensi.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>
</x-layouts::app>

