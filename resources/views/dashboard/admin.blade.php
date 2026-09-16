<x-layouts::app :title="__('Admin Dashboard')">
    <div class="space-y-6">

        {{-- Page Header --}}
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">
                Attendance
            </h1>
            <p class="mt-1 text-sm text-gray-500">
                Dashboard monitoring presensi peserta PKL PT Fiber Networks Indonesia Branch Palu.
            </p>
        </div>

        {{-- Summary Cards --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 font-medium">Total Peserta PKL</p>
                    <p class="mt-2 text-3xl font-semibold text-gray-900">{{ $totalStudents }}</p>
                </div>
                <div class="h-12 w-12 rounded-full bg-blue-50 flex items-center justify-center text-blue-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 font-medium">Hadir Tepat Waktu</p>
                    <p class="mt-2 text-3xl font-semibold text-green-600">{{ $presentCount }}</p>
                </div>
                <div class="h-12 w-12 rounded-full bg-green-50 flex items-center justify-center text-green-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 font-medium">Terlambat</p>
                    <p class="mt-2 text-3xl font-semibold text-yellow-600">{{ $lateCount }}</p>
                </div>
                <div class="h-12 w-12 rounded-full bg-yellow-50 flex items-center justify-center text-yellow-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 font-medium">Tidak Hadir / Belum Presensi</p>
                    <p class="mt-2 text-3xl font-semibold text-red-600">{{ $absentCount }}</p>
                </div>
                <div class="h-12 w-12 rounded-full bg-red-50 flex items-center justify-center text-red-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
            </div>
        </div>

        {{-- Table Presensi Hari Ini --}}
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-200 px-6 py-4 flex justify-between items-center bg-white">
                <h2 class="font-semibold text-gray-900">
                    Daftar Presensi Hari Ini
                </h2>
                <a href="{{ route('reports') }}" class="text-sm font-medium text-blue-600 hover:text-blue-500">Lihat Semua Data &rarr;</a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-gray-500">
                    <thead class="bg-white text-xs uppercase text-gray-700 border-b border-gray-200">
                        <tr>
                            <th scope="col" class="px-6 py-3 font-medium">Siswa</th>
                            <th scope="col" class="px-6 py-3 font-medium">Sekolah</th>
                            <th scope="col" class="px-6 py-3 font-medium">Check In</th>
                            <th scope="col" class="px-6 py-3 font-medium">Check Out</th>
                            <th scope="col" class="px-6 py-3 font-medium">Status</th>
                            <th scope="col" class="px-6 py-3 font-medium">Jarak</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse($attendancesToday as $attendance)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 font-medium text-gray-900 flex items-center">
                                    <div class="h-8 w-8 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-xs font-bold mr-3">
                                        {{ substr($attendance->student->user->name, 0, 1) }}
                                    </div>
                                    <div>
                                        <div>{{ $attendance->student->user->name }}</div>
                                        <div class="text-xs text-gray-400">{{ $attendance->student->student_code }}</div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">{{ $attendance->student->school }}</td>
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
                                        <span class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">PRESENT</span>
                                    @elseif($attendance->status === 'late')
                                        <span class="inline-flex items-center rounded-md bg-yellow-50 px-2 py-1 text-xs font-medium text-yellow-800 ring-1 ring-inset ring-yellow-600/20">LATE</span>
                                    @else
                                        <span class="inline-flex items-center rounded-md bg-red-50 px-2 py-1 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/10">ABSENT</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-gray-500">
                                    {{ $attendance->check_in_distance ? $attendance->check_in_distance . ' m' : '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-sm text-gray-500">
                                    Belum ada yang melakukan presensi hari ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</x-layouts::app>

