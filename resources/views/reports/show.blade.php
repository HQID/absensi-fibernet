<x-layouts::app :title="__('Detail Presensi')">
    <div class="space-y-6">

        {{-- Page Header --}}
        <div class="flex items-center gap-4">
            <a href="{{ route('reports') }}" class="h-10 w-10 rounded-xl bg-gray-100 hover:bg-gray-200 flex items-center justify-center transition-colors" wire:navigate>
                <svg class="h-5 w-5 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">
                    Detail Presensi
                </h1>
                <p class="text-sm text-gray-500">
                    Informasi detail terkait presensi siswa.
                </p>
            </div>
        </div>

        <div class="fibernet-card-blue">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="text-base font-semibold text-gray-900 flex items-center gap-2">
                    <div class="h-7 w-7 rounded-lg bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center">
                        <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    </div>
                    Informasi Siswa
                </h3>
            </div>
            <div class="px-4 py-5 sm:p-0">
                <dl class="sm:divide-y sm:divide-gray-100">
                    <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Nama Lengkap</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0 font-medium">{{ $attendance->student->user->name }}</dd>
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

        <div class="fibernet-card-purple">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="text-base font-semibold text-gray-900 flex items-center gap-2">
                    <div class="h-7 w-7 rounded-lg bg-gradient-to-br from-violet-500 to-purple-600 flex items-center justify-center">
                        <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                    </div>
                    Data Presensi
                </h3>
            </div>
            <div class="px-4 py-5 sm:p-0">
                <dl class="sm:divide-y sm:divide-gray-100">
                    <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Tanggal</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0 font-medium">{{ $attendance->attendance_date->format('d F Y') }}</dd>
                    </div>
                    <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Status</dt>
                        <dd class="mt-1 text-sm sm:col-span-2 sm:mt-0 uppercase font-bold
                            {{ $attendance->is_approved === false ? 'text-amber-600' : ($attendance->status === 'present' ? 'text-emerald-600' : ($attendance->status === 'late' ? 'text-orange-600' : 'text-red-600')) }}">
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
                                <span class="ml-2 inline-flex items-center rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700 ring-1 ring-inset ring-amber-600/20">Pending Approval</span>
                            @elseif($attendance->is_approved === true)
                                <span class="ml-2 inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-600/20">Approved</span>
                            @endif
                        </dd>
                    </div>

                    @if($attendance->is_approved === false)
                    <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 bg-amber-50/30">
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
                                Koordinat: <a href="https://maps.google.com/?q={{ $attendance->check_in_latitude }},{{ $attendance->check_in_longitude }}" target="_blank" class="inline-flex items-center gap-1 text-blue-600 hover:underline font-medium">
                                    {{ $attendance->check_in_latitude }}, {{ $attendance->check_in_longitude }}
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
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
                                <img src="{{ route('reports.photo', $attendance->id) }}" alt="Foto Check-In" class="mt-2 h-48 w-auto rounded-xl border border-gray-100 object-cover shadow-md hover:shadow-lg transition-shadow">
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
                                Koordinat: <a href="https://maps.google.com/?q={{ $attendance->check_out_latitude }},{{ $attendance->check_out_longitude }}" target="_blank" class="inline-flex items-center gap-1 text-blue-600 hover:underline font-medium">
                                    {{ $attendance->check_out_latitude }}, {{ $attendance->check_out_longitude }}
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                                </a>
                            </div>
                            <div>Akurasi GPS: {{ $attendance->check_out_accuracy }} m</div>
                            <div>Jarak ke Kantor: {{ $attendance->check_out_distance ?? '-' }} m</div>
                        </dd>
                    </div>
                    @endif

                    <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 bg-gradient-to-r from-blue-50/50 to-cyan-50/50">
                        <dt class="text-sm font-semibold text-gray-900">Total Waktu Kerja</dt>
                        <dd class="mt-1 text-sm font-bold text-gray-900 sm:col-span-2 sm:mt-0">
                            @if($attendance->check_in_at && $attendance->check_out_at)
                                {{ $attendance->check_in_at->diff($attendance->check_out_at)->format('%h Jam %i Menit') }}
                            @else
                                <span class="text-gray-400 font-normal">Belum Check-Out</span>
                            @endif
                        </dd>
                    </div>
                </dl>
            </div>
        </div>

    </div>
</x-layouts::app>
