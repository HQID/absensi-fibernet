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
                            {{ $attendance->is_approved === false ? 'text-yellow-600' : ($attendance->status === 'present' ? 'text-green-600' : ($attendance->status === 'late' ? 'text-orange-600' : 'text-red-600')) }}">
                            {{ $attendance->is_approved === false ? 'PENDING' : $attendance->status }}
                        </dd>
                    </div>
                    <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Kategori Persetujuan</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0 font-medium">
                            @if($attendance->location_type === 'outside')
                                <span class="text-blue-600">Di Luar Kantor (Kunjungan/Dinas)</span>
                            @else
                                <span>Di Dalam Kantor</span>
                            @endif

                            @if($attendance->is_approved === false)
                                <span class="ml-2 inline-flex items-center rounded-md bg-yellow-50 px-2 py-1 text-xs font-medium text-yellow-800 ring-1 ring-inset ring-yellow-600/20">Pending Approval</span>
                            @elseif($attendance->is_approved === true)
                                <span class="ml-2 inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">Approved</span>
                            @endif
                        </dd>
                    </div>

                    @if($attendance->is_approved === false)
                    <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 bg-yellow-50/50">
                        <dt class="text-sm font-medium text-gray-500 flex items-center">Aksi Persetujuan</dt>
                        <dd class="mt-1 sm:col-span-2 sm:mt-0 flex flex-wrap gap-2">
                            <form method="POST" action="{{ route('reports.approve', $attendance->id) }}" class="inline-block">
                                @csrf
                                <input type="hidden" name="status" value="present">
                                <flux:button type="submit" variant="primary" size="sm">
                                    Terima (Hadir)
                                </flux:button>
                            </form>
                            <form method="POST" action="{{ route('reports.approve', $attendance->id) }}" class="inline-block">
                                @csrf
                                <input type="hidden" name="status" value="late">
                                <flux:button type="submit" size="sm">
                                    Terima (Terlambat)
                                </flux:button>
                            </form>
                            <form method="POST" action="{{ route('reports.approve', $attendance->id) }}" class="inline-block">
                                @csrf
                                <input type="hidden" name="status" value="absent">
                                <flux:button type="submit" variant="danger" size="sm">
                                    Tolak (Alpa)
                                </flux:button>
                            </form>
                        </dd>
                    </div>
                    @endif

                    @if($attendance->reason)
                    <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Keterangan / Alasan</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0 italic">"{{ $attendance->reason }}"</dd>
                    </div>
                    @endif
                    
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
                            <div>
                                Koordinat: <a href="https://maps.google.com/?q={{ $attendance->check_in_latitude }},{{ $attendance->check_in_longitude }}" target="_blank" class="text-blue-600 hover:underline">
                                    {{ $attendance->check_in_latitude }}, {{ $attendance->check_in_longitude }}
                                </a>
                            </div>
                            <div>Akurasi GPS: {{ $attendance->check_in_accuracy }} m</div>
                            <div>Jarak ke Kantor: {{ $attendance->check_in_distance ?? '-' }} m</div>
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
                            <div>
                                Koordinat: <a href="https://maps.google.com/?q={{ $attendance->check_out_latitude }},{{ $attendance->check_out_longitude }}" target="_blank" class="text-blue-600 hover:underline">
                                    {{ $attendance->check_out_latitude }}, {{ $attendance->check_out_longitude }}
                                </a>
                            </div>
                            <div>Akurasi GPS: {{ $attendance->check_out_accuracy }} m</div>
                            <div>Jarak ke Kantor: {{ $attendance->check_out_distance ?? '-' }} m</div>
                        </dd>
                    </div>
                    @endif

                    <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 bg-gray-50">
                        <dt class="text-sm font-medium text-gray-900">Total Waktu Kerja</dt>
                        <dd class="mt-1 text-sm font-semibold text-gray-900 sm:col-span-2 sm:mt-0">
                            @if($attendance->check_in_at && $attendance->check_out_at)
                                {{ $attendance->check_in_at->diff($attendance->check_out_at)->format('%h Jam %i Menit') }}
                            @else
                                <span class="text-gray-400">Belum Check-Out</span>
                            @endif
                        </dd>
                    </div>
                </dl>
            </div>
        </div>

    </div>
</x-layouts::app>

