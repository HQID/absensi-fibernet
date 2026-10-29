<x-layouts::app :title="__('Laporan Presensi')">
    <div class="space-y-6">

        {{-- Page Header --}}
        <div class="bg-white rounded-xl border border-gray-100 p-6 shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="h-10 w-10 rounded-xl bg-cyan-50 flex items-center justify-center">
                        <svg class="w-5 h-5 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">
                            Laporan Presensi
                        </h1>
                        <p class="text-sm text-gray-500">
                            Riwayat presensi peserta PKL dan fitur export.
                        </p>
                    </div>
                </div>
                <a href="{{ route('reports.export', ['start_date' => $startDate, 'end_date' => $endDate, 'school' => request('school'), 'student_id' => request('student_id'), 'status' => request('status')]) }}" data-download class="inline-flex items-center rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white transition-colors px-4 py-2.5 text-sm font-semibold">
                    <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Export XLSX
                </a>
            </div>
        </div>

        {{-- Filters --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
            <form action="{{ route('reports') }}" method="GET" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5 items-end">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Mulai</label>
                    <input type="date" name="start_date" value="{{ $startDate }}" class="block w-full rounded-lg border-0 py-2.5 px-3 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-200 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-cyan-500 sm:text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Selesai</label>
                    <input type="date" name="end_date" value="{{ $endDate }}" class="block w-full rounded-lg border-0 py-2.5 px-3 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-200 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-cyan-500 sm:text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Sekolah / Kampus</label>
                    <select name="school" class="block w-full rounded-lg border-0 py-2.5 px-3 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-inset focus:ring-cyan-500 sm:text-sm">
                        <option value="">Semua Sekolah / Kampus</option>
                        @foreach($schools as $schoolOption)
                            <option value="{{ $schoolOption }}" {{ mb_strtolower(trim((string) request('school'))) === mb_strtolower($schoolOption) ? 'selected' : '' }}>
                                {{ $schoolOption }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" class="block w-full rounded-lg border-0 py-2.5 px-3 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-inset focus:ring-cyan-500 sm:text-sm">
                        <option value="">Semua Status</option>
                        <option value="present" {{ request('status') === 'present' ? 'selected' : '' }}>Hadir</option>
                        <option value="late" {{ request('status') === 'late' ? 'selected' : '' }}>Terlambat</option>
                        <option value="absent" {{ request('status') === 'absent' ? 'selected' : '' }}>Absen</option>
                    </select>
                </div>
                <div class="flex items-center gap-2">
                    <flux:button type="submit" variant="primary" class="flex-1 h-[42px]!">
                        Filter
                    </flux:button>
                    @if(request()->filled('school') || request()->filled('status') || request()->filled('student_id'))
                        <a href="{{ route('reports', ['start_date' => $startDate, 'end_date' => $endDate]) }}" wire:navigate title="Reset Filter" class="inline-flex h-[42px] items-center justify-center rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-600 hover:bg-gray-50 transition-colors">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        {{-- Table --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="fibernet-table-header">
                        <tr>
                            <th scope="col" class="px-6 py-4 font-semibold text-gray-900">Tanggal</th>
                            <th scope="col" class="px-6 py-4 font-semibold text-gray-900">Nama Peserta</th>
                            <th scope="col" class="px-6 py-4 font-semibold text-gray-900 w-1/3">Check-In & Out</th>
                            <th scope="col" class="px-6 py-4 font-semibold text-gray-900">Sekolah/Kampus</th>
                            <th scope="col" class="px-6 py-4 font-semibold text-gray-900">Jenis</th>
                            <th scope="col" class="px-6 py-4 font-semibold text-gray-900">Status</th>
                            <th scope="col" class="px-6 py-4 font-semibold text-gray-900 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($attendances as $attendance)
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-gray-700">
                                    {{ $attendance->attendance_date->format('d/m/Y') }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-medium text-gray-900">{{ $attendance->student->user->name }}</div>
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
                                <td class="px-6 py-4 text-right">
                                    <a href="{{ route('reports.show', $attendance->id) }}" class="inline-flex items-center gap-1 text-sm font-medium text-cyan-600 hover:text-cyan-800 transition-colors" wire:navigate>
                                        Detail
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-16 text-center">
                                    <div class="flex flex-col items-center">
                                        <div class="h-12 w-12 rounded-full bg-gray-50 flex items-center justify-center mb-3">
                                            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                        </div>
                                        <p class="text-sm text-gray-500">Tidak ada data presensi yang ditemukan.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-gray-100 px-6 py-4">
                {{ $attendances->links() }}
            </div>
        </div>

    </div>
</x-layouts::app>
