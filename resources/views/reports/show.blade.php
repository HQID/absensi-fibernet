<x-layouts::app :title="__('Detail Presensi')">
    <div class="space-y-6">

        {{-- Page Header --}}
        <div class="sm:flex sm:items-center sm:justify-between">
            <div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('reports') }}" class="text-gray-500 hover:text-gray-700" wire:navigate>
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </a>
                    <h1 class="text-2xl font-semibold text-gray-900">
                        Detail Presensi
                    </h1>
                </div>
                <p class="mt-1 text-sm text-gray-500 ml-7">
                    Menampilkan informasi detail terkait presensi siswa.
                </p>
            </div>
        </div>

        <div class="overflow-hidden bg-white shadow-sm sm:rounded-xl border border-gray-200">
            <div class="px-4 py-5 sm:px-6">
                <h3 class="text-lg font-medium leading-6 text-gray-900">Informasi Siswa</h3>
            </div>
            <div class="border-t border-gray-200 px-4 py-5 sm:p-0">
                <dl class="sm:divide-y sm:divide-gray-200">
                    <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Nama Lengkap</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $attendance->student->user->name }}</dd>
                    </div>
                    <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">NIM / NISN</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $attendance->student->student_code }}</dd>
                    </div>
                    <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Asal Sekolah</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $attendance->student->school }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        <div class="overflow-hidden bg-white shadow-sm sm:rounded-xl border border-gray-200">
            <div class="px-4 py-5 sm:px-6">
                <h3 class="text-lg font-medium leading-6 text-gray-900">Data Presensi</h3>
            </div>
            <div class="border-t border-gray-200 px-4 py-5 sm:p-0">
                <dl class="sm:divide-y sm:divide-gray-200">
                    <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Tanggal</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $attendance->attendance_date->format('d F Y') }}</dd>
                    </div>
                    <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Status</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0 uppercase font-semibold
                            {{ $attendance->status === 'present' ? 'text-green-600' : ($attendance->status === 'late' ? 'text-yellow-600' : 'text-red-600') }}">
                            {{ $attendance->status }}
                        </dd>
                    </div>
                    
                    <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Waktu Check-In</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                            {{ $attendance->check_in_at ? $attendance->check_in_at->format('H:i:s') : '-' }}
                        </dd>
                    </div>

                    @if($attendance->check_in_at)
                    <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Lokasi Check-In</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0 space-y-1">
                            <div>Latitude: {{ $attendance->check_in_latitude }}</div>
                            <div>Longitude: {{ $attendance->check_in_longitude }}</div>
                            <div>Akurasi GPS: {{ $attendance->check_in_accuracy }} m</div>
                            <div>Jarak ke Kantor: {{ $attendance->check_in_distance }} m</div>
                        </dd>
                    </div>
                    @endif

                    @if($attendance->check_in_photo_path)
                    <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Foto Check-In</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                            <a href="{{ route('reports.photo', $attendance->id) }}" target="_blank">
                                <img src="{{ route('reports.photo', $attendance->id) }}" alt="Foto Check-In" class="mt-2 h-48 w-auto rounded-lg border border-gray-200 object-cover shadow-sm">
                            </a>
                        </dd>
                    </div>
                    @endif

                    <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Waktu Check-Out</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                            {{ $attendance->check_out_at ? $attendance->check_out_at->format('H:i:s') : '-' }}
                        </dd>
                    </div>

                    @if($attendance->check_out_at)
                    <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Lokasi Check-Out</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0 space-y-1">
                            <div>Latitude: {{ $attendance->check_out_latitude }}</div>
                            <div>Longitude: {{ $attendance->check_out_longitude }}</div>
                            <div>Akurasi GPS: {{ $attendance->check_out_accuracy }} m</div>
                            <div>Jarak ke Kantor: {{ $attendance->check_out_distance }} m</div>
                        </dd>
                    </div>
                    @endif
                </dl>
            </div>
        </div>

    </div>
</x-layouts::app>

