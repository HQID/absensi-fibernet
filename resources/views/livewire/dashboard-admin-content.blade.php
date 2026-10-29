<div class="space-y-6">
    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        {{-- Total Peoples --}}
        <div class="card-soft-cyan">
            <div class="flex items-start justify-between">
                <div class="flex items-center gap-2 text-gray-700 font-medium">
                    <svg class="w-5 h-5 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    <span>Total Peserta PKL</span>
                </div>
            </div>
            <div class="mt-4">
                <p class="text-3xl font-bold text-gray-900">{{ $totalStudents }}</p>
            </div>
        </div>

        {{-- On Time --}}
        <div class="card-soft-white">
            <div class="flex items-start justify-between">
                <div class="flex items-center gap-2 text-gray-700 font-medium">
                    <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span>Hadir Tepat Waktu</span>
                </div>
            </div>
            <div class="mt-4">
                <p class="text-3xl font-bold text-gray-900">{{ $presentCount }}</p>
            </div>
        </div>

        {{-- Late --}}
        <div class="card-soft-white">
            <div class="flex items-start justify-between">
                <div class="flex items-center gap-2 text-gray-700 font-medium">
                    <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span>Terlambat</span>
                </div>
            </div>
            <div class="mt-4">
                <p class="text-3xl font-bold text-gray-900">{{ $lateCount }}</p>
            </div>
        </div>

        {{-- Absent --}}
        <div class="card-soft-rose">
            <div class="flex items-start justify-between">
                <div class="flex items-center gap-2 text-gray-700 font-medium">
                    <svg class="w-5 h-5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span>Tidak Hadir / Belum</span>
                </div>
            </div>
            <div class="mt-4">
                <p class="text-3xl font-bold text-gray-900">{{ $absentCount }}</p>
            </div>
        </div>
    </div>

    {{-- Table Presensi Hari Ini --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="border-b border-gray-100 px-6 py-4 flex justify-between items-center">
            <div class="flex items-center gap-3">
                <div class="h-8 w-8 rounded-lg bg-cyan-50 flex items-center justify-center">
                    <svg class="w-4 h-4 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                </div>
                <h2 class="font-semibold text-gray-900">
                    Daftar Presensi Hari Ini
                </h2>
            </div>
            <a href="{{ route('reports') }}" wire:navigate class="inline-flex items-center gap-1 rounded-lg bg-cyan-50 px-3 py-1.5 text-sm font-medium text-cyan-700 hover:bg-cyan-100 transition-colors">
                Lihat Semua
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="fibernet-table-header">
                    <tr>
                        <th scope="col" class="px-6 py-4 font-semibold text-gray-900">Nama Peserta</th>
                        <th scope="col" class="px-6 py-4 font-semibold text-gray-900 w-1/3">Check-In & Out</th>
                        <th scope="col" class="px-6 py-4 font-semibold text-gray-900">Sekolah/Kampus</th>
                        <th scope="col" class="px-6 py-4 font-semibold text-gray-900">Jenis</th>
                        <th scope="col" class="px-6 py-4 font-semibold text-gray-900">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($attendancesToday as $attendance)
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="font-semibold text-gray-900">{{ $attendance->student->user->name }}</div>
                                <div class="text-xs text-gray-500">{{ $attendance->student->student_code }}</div>
                            </td>

                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    {{-- Check In Time --}}
                                    @if($attendance->check_in_at)
                                        <span class="text-gray-900 font-medium whitespace-nowrap {{ $attendance->status === 'late' ? 'text-rose-600' : '' }}">{{ $attendance->check_in_at->format('h:i A') }}</span>
                                    @else
                                        <span class="text-gray-400 font-medium whitespace-nowrap">--:--</span>
                                    @endif

                                    {{-- Timeline Connector --}}
                                    <div class="flex-1 flex items-center min-w-[80px]">
                                        <div class="w-2 h-2 rounded-full {{ $attendance->check_in_at ? 'bg-gray-400' : 'bg-gray-200' }}"></div>
                                        <div class="h-px {{ $attendance->check_in_at ? 'bg-gray-400' : 'bg-gray-200' }} flex-1 relative">
                                            @if($attendance->check_in_at && $attendance->check_out_at)
                                                @php
                                                    $duration = $attendance->check_in_at->diff($attendance->check_out_at);
                                                    $durationStr = $duration->h . 'h ' . $duration->i . 'm';
                                                @endphp
                                                <span class="absolute top-1/2 left-1/2 -translate-y-1/2 -translate-x-1/2 bg-white px-2 text-xs text-gray-400 whitespace-nowrap">
                                                    {{ $durationStr }}
                                                </span>
                                            @endif
                                        </div>
                                        <div class="w-2 h-2 rounded-full border-2 {{ $attendance->check_out_at ? 'border-gray-400 bg-white' : 'border-gray-200 bg-white' }}"></div>
                                    </div>

                                    {{-- Check Out Time --}}
                                    @if($attendance->check_out_at)
                                        <span class="text-gray-400 font-medium whitespace-nowrap">{{ $attendance->check_out_at->format('h:i A') }}</span>
                                    @else
                                        <span class="text-gray-400 font-medium whitespace-nowrap">--:--</span>
                                    @endif
                                </div>
                            </td>

                            <td class="px-6 py-4 text-gray-500 font-medium text-sm">
                                {{ $attendance->student->school }}
                            </td>

                            <td class="px-6 py-4">
                                @if($attendance->location_type === 'outside')
                                    <span class="text-orange-600 font-medium text-sm">Luar Kantor</span>
                                @else
                                    <span class="text-gray-700 font-medium text-sm">Dalam Kantor</span>
                                @endif
                            </td>

                            <td class="px-6 py-4">
                                @if($attendance->status === 'present')
                                    <span class="inline-flex items-center rounded-md bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-600 uppercase">HADIR</span>
                                @elseif($attendance->status === 'late')
                                    <span class="inline-flex items-center rounded-md bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-600 uppercase">TERLAMBAT</span>
                                @else
                                    <span class="inline-flex items-center rounded-md bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600 uppercase">ABSEN</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="h-12 w-12 rounded-full bg-gray-50 flex items-center justify-center mb-3">
                                        <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    </div>
                                    <p class="text-sm text-gray-500">Belum ada yang melakukan presensi hari ini.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
