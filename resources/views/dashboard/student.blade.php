<x-layouts::app :title="__('Student Dashboard')">
    <div class="space-y-6">

        {{-- Page Header --}}
        <div class="bg-white rounded-xl border border-gray-100 p-6 shadow-sm">
            <h1 class="text-2xl font-bold text-gray-900">
                Halo, {{ Auth::user()->name }} 👋
            </h1>
            <p class="mt-1 text-sm text-gray-500">
                Berikut adalah riwayat presensi Anda.
            </p>
        </div>

        <div class="grid grid-cols-2 gap-4 mb-6">
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 text-center flex flex-col justify-center items-center">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Waktu Saat Ini</p>
                <div x-data="{ time: new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' }) }" 
                     x-init="setInterval(() => time = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' }), 1000)" 
                     class="text-2xl sm:text-4xl font-bold text-gray-900" x-text="time">
                </div>
                <p class="text-xs sm:text-sm text-gray-500 mt-1">{{ \Carbon\Carbon::now()->translatedFormat('l, d F Y') }}</p>
            </div>
            
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 text-center flex flex-col justify-center items-center">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Status Hari Ini</p>
                @if($todayAttendance)
                    @if($todayAttendance->status === 'present')
                        <span class="inline-flex items-center rounded-md bg-emerald-50 px-3 py-1.5 text-xs sm:text-sm font-semibold text-emerald-600">HADIR</span>
                    @elseif($todayAttendance->status === 'late')
                        <span class="inline-flex items-center rounded-md bg-rose-50 px-3 py-1.5 text-xs sm:text-sm font-semibold text-rose-600">TERLAMBAT</span>
                    @else
                        <span class="inline-flex items-center rounded-md bg-gray-100 px-3 py-1.5 text-xs sm:text-sm font-semibold text-gray-600">ABSEN</span>
                    @endif
                @else
                    <span class="inline-flex items-center rounded-md bg-gray-100 px-3 py-1.5 text-xs sm:text-sm font-semibold text-gray-600">BELUM PRESENSI</span>
                @endif
            </div>
        </div>

        <div class="grid gap-6 md:grid-cols-3">
            <div class="md:col-span-1 space-y-6">
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                    <div class="flex items-center gap-2 mb-4">
                        <div class="h-8 w-8 rounded-lg bg-cyan-50 flex items-center justify-center">
                            <svg class="w-4 h-4 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900">Profil PKL</h3>
                    </div>
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

                <div class="relative overflow-hidden rounded-2xl bg-cyan-50 border border-cyan-100 p-6 shadow-sm">
                    <div class="relative">
                        <h3 class="text-lg font-semibold text-cyan-900 mb-2">Presensi Hari Ini</h3>
                        <p class="text-sm text-cyan-700 mb-4">Lakukan presensi kehadiran Anda menggunakan fitur geofencing.</p>
                        <a href="{{ route('attendance') }}" class="inline-flex w-full justify-center items-center rounded-lg bg-white border border-cyan-200 px-4 py-2.5 text-sm font-semibold text-cyan-700 hover:bg-gray-50 transition-colors shadow-sm">
                            Buka Halaman Presensi
                        </a>
                    </div>
                </div>
            </div>

            <div class="md:col-span-2">
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                    <div class="border-b border-gray-100 px-6 py-4 flex items-center gap-3">
                        <div class="h-8 w-8 rounded-lg bg-blue-50 flex items-center justify-center">
                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <h2 class="font-semibold text-gray-900">
                            10 Riwayat Presensi Terakhir
                        </h2>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="fibernet-table-header">
                                <tr>
                                    <th scope="col" class="px-6 py-4 font-semibold text-gray-900">Tanggal</th>
                                    <th scope="col" class="px-6 py-4 font-semibold text-gray-900">Check In</th>
                                    <th scope="col" class="px-6 py-4 font-semibold text-gray-900">Check Out</th>
                                    <th scope="col" class="px-6 py-4 font-semibold text-gray-900">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @forelse($attendances as $attendance)
                                    <tr class="hover:bg-gray-50/50 transition-colors">
                                        <td class="px-6 py-4 font-medium text-gray-900">
                                            {{ $attendance->attendance_date->format('d M Y') }}
                                        </td>
                                        <td class="px-6 py-4">
                                            @if($attendance->check_in_at)
                                                <span class="text-gray-900 font-medium">{{ $attendance->check_in_at->format('H:i') }}</span>
                                            @else
                                                <span class="text-gray-400">-</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4">
                                            @if($attendance->check_out_at)
                                                <span class="text-gray-900 font-medium">{{ $attendance->check_out_at->format('H:i') }}</span>
                                            @else
                                                <span class="text-gray-400">-</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4">
                                            @if($attendance->status === 'present')
                                                <span class="inline-flex items-center rounded-md bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-600">Hadir</span>
                                            @elseif($attendance->status === 'late')
                                                <span class="inline-flex items-center rounded-md bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-600">Terlambat</span>
                                            @else
                                                <span class="inline-flex items-center rounded-md bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600">Absen</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-6 py-16 text-center">
                                            <div class="flex flex-col items-center">
                                                <div class="h-12 w-12 rounded-full bg-gray-50 flex items-center justify-center mb-3">
                                                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                </div>
                                                <p class="text-sm text-gray-500">Belum ada riwayat presensi.</p>
                                            </div>
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
