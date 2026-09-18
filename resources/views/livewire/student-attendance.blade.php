<?php

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Services\AttendanceService;
use App\Models\Attendance;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

new class extends Component
{
    use WithFileUploads, \Livewire\WithPagination;

    public $latitude;
    public $longitude;
    public $accuracy;
    public $photo;
    
    public $locationType = 'inside';
    public $reason = '';
    public $isLate = false;

    public $attendanceToday;
    public $errorMessage = '';
    public $successMessage = '';

    public function mount()
    {
        $this->loadAttendance();
        $this->isLate = now()->format('H:i') > '08:15';
    }

    public function with(): array
    {
        $history = [];
        if (Auth::user()->isStudent() && Auth::user()->student) {
            $history = Attendance::where('student_id', Auth::user()->student->id)
                ->orderBy('attendance_date', 'desc')
                ->paginate(5);
        }

        return [
            'history' => $history,
        ];
    }

    public function loadAttendance()
    {
        if (Auth::user()->isStudent()) {
            $student = Auth::user()->student;
            if ($student) {
                $this->attendanceToday = Attendance::where('student_id', $student->id)
                    ->where('attendance_date', Carbon::today()->toDateString())
                    ->first();
            }
        }
    }

    public function checkIn(AttendanceService $attendanceService)
    {
        $this->validate([
            'latitude' => 'required',
            'longitude' => 'required',
            'accuracy' => 'required',
            'photo' => 'required|image|max:20480', // 20MB max
            'locationType' => 'required|in:inside,outside',
        ]);

        $isLate = now()->format('H:i') > '08:15';
        if ($this->locationType === 'outside' || $isLate) {
            $this->validate(['reason' => 'required|string|min:3'], [
                'reason.required' => 'Keterangan/Alasan wajib diisi (karena Anda memilih Luar Kantor atau terlambat).'
            ]);
        }

        $this->errorMessage = '';
        $this->successMessage = '';
        
        try {
            $student = Auth::user()->student;
            $attendanceService->checkIn($student, $this->latitude, $this->longitude, $this->accuracy, $this->photo, $this->locationType, $this->reason);
            $this->successMessage = 'Check-in berhasil!';
            $this->photo = null;
            $this->loadAttendance();
        } catch (\Exception $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function checkOut(AttendanceService $attendanceService)
    {
        $this->validate([
            'latitude' => 'required',
            'longitude' => 'required',
            'accuracy' => 'required',
        ]);

        $this->errorMessage = '';
        $this->successMessage = '';

        try {
            $student = Auth::user()->student;
            $attendanceService->checkOut($student, $this->latitude, $this->longitude, $this->accuracy);
            $this->successMessage = 'Check-out berhasil!';
            $this->loadAttendance();
        } catch (\Exception $e) {
            $this->errorMessage = $e->getMessage();
        }
    }
};
?>

<div>
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 sm:p-6">
        @if(Auth::user()->isStudent() && Auth::user()->student)
            
            @if($errorMessage)
                <div class="mb-4 rounded-xl bg-red-50 p-4 border border-red-100 flex items-start gap-3">
                    <div class="h-5 w-5 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                        <svg class="h-3 w-3 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </div>
                    <p class="text-sm text-red-700">{{ $errorMessage }}</p>
                </div>
            @endif

            @if($successMessage)
                <div class="mb-4 rounded-xl bg-emerald-50 p-4 border border-emerald-100 flex items-start gap-3">
                    <div class="h-5 w-5 rounded-full bg-emerald-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                        <svg class="h-3 w-3 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                    </div>
                    <p class="text-sm text-emerald-700">{{ $successMessage }}</p>
                </div>
            @endif

            <div class="text-center" x-data="attendanceHandler()">
                
                @if(!$attendanceToday || !$attendanceToday->check_in_at)
                    <!-- Form Check In -->
                    <div class="flex items-center justify-center gap-2 mb-6">
                        <div class="h-8 w-8 rounded-lg bg-cyan-50 flex items-center justify-center">
                            <svg class="w-4 h-4 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" /></svg>
                        </div>
                        <h2 class="text-lg font-semibold text-gray-900">Check In Presensi</h2>
                    </div>
                    
                    <div x-show="!locationReady" class="mb-4">
                        <button type="button" @click="getLocation()" class="rounded-xl bg-cyan-600 hover:bg-cyan-700 text-white transition-colors px-6 py-3 text-sm font-semibold w-full sm:w-auto">
                            <span class="flex items-center justify-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                Dapatkan Lokasi Saya
                            </span>
                        </button>
                    </div>

                    <div x-show="locationReady" style="display: none;" class="mb-4 text-left">
                        <div class="bg-cyan-50 border border-cyan-100 text-cyan-800 text-sm p-3 rounded-xl mb-4 flex items-center">
                            <div class="h-6 w-6 rounded-full bg-cyan-100 flex items-center justify-center mr-2 flex-shrink-0">
                                <svg class="h-3.5 w-3.5 text-cyan-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                            Lokasi terverifikasi (Akurasi: <span x-text="Math.round(accuracy)" class="mx-1 font-semibold"></span>m)
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Jenis Lokasi Presensi</label>
                            <select wire:model.live="locationType" class="mt-1 block w-full rounded-xl border-gray-200 py-2 pl-3 pr-10 text-base focus:border-cyan-500 focus:outline-none focus:ring-cyan-500 sm:text-sm">
                                <option value="inside">Di Dalam Kantor</option>
                                <option value="outside">Di Luar Kantor (Kunjungan/Dinas)</option>
                            </select>
                            @error('locationType') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div x-show="$wire.locationType === 'outside' || $wire.isLate" class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Keterangan / Alasan <span class="text-red-500">*</span></label>
                            <p class="text-xs text-gray-500 mb-2">Wajib diisi karena Anda memilih Luar Kantor atau Anda datang terlambat.</p>
                            <textarea wire:model="reason" rows="2" class="mt-1 block w-full rounded-xl border-gray-200 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 sm:text-sm" placeholder="Masukkan alasan Anda..."></textarea>
                            @error('reason') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Foto Bukti Kehadiran</label>
                            <input type="file" wire:model="photo" accept="image/*" capture="user" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-cyan-50 file:text-cyan-700 hover:file:bg-cyan-100 transition-colors" />
                            @error('photo') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div wire:loading wire:target="photo" class="text-sm text-cyan-600 mb-4 flex items-center justify-center gap-2">
                            <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                            Sedang memproses foto...
                        </div>

                        <div x-show="$wire.photo" style="display: none;">
                            <button type="button" wire:click="checkIn" wire:loading.attr="disabled" class="w-full rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white transition-colors px-5 py-3 text-sm font-semibold disabled:opacity-50 flex justify-center items-center gap-2">
                                <span wire:loading.remove wire:target="checkIn">
                                    <svg class="w-5 h-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                    Submit Check In
                                </span>
                                <span wire:loading wire:target="checkIn" class="flex items-center gap-2">
                                    <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                    Menyimpan...
                                </span>
                            </button>
                        </div>
                    </div>
                @elseif(!$attendanceToday->check_out_at)
                    <!-- Form Check Out -->
                    <div class="flex items-center justify-center gap-2 mb-6">
                        <div class="h-8 w-8 rounded-lg bg-orange-50 flex items-center justify-center">
                            <svg class="w-4 h-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                        </div>
                        <h2 class="text-lg font-semibold text-gray-900">Check Out Presensi</h2>
                    </div>
                    
                    <div class="bg-gray-50 border border-gray-100 p-4 rounded-xl mb-6 text-left text-sm text-gray-700 space-y-2">
                        <div class="flex justify-between">
                            <span class="text-gray-500">Waktu Check-In</span>
                            <span class="font-semibold">{{ $attendanceToday->check_in_at->format('H:i') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Status</span>
                            <span class="font-semibold uppercase {{ $attendanceToday->status === 'present' ? 'text-emerald-600' : 'text-orange-600' }}">{{ $attendanceToday->status }}</span>
                        </div>
                    </div>

                    <div x-show="!locationReady" class="mb-4">
                        <button type="button" @click="getLocation()" class="rounded-xl bg-cyan-600 hover:bg-cyan-700 text-white transition-colors px-6 py-3 text-sm font-semibold w-full sm:w-auto">
                            <span class="flex items-center justify-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                Dapatkan Lokasi Saya
                            </span>
                        </button>
                    </div>

                    <div x-show="locationReady" style="display: none;" class="mb-4">
                        <div class="bg-cyan-50 border border-cyan-100 text-cyan-800 text-sm p-3 rounded-xl mb-4 text-left flex items-center">
                            <div class="h-6 w-6 rounded-full bg-cyan-100 flex items-center justify-center mr-2 flex-shrink-0">
                                <svg class="h-3.5 w-3.5 text-cyan-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                            Lokasi terverifikasi (Akurasi: <span x-text="Math.round(accuracy)" class="mx-1 font-semibold"></span>m)
                        </div>

                        <button type="button" wire:click="checkOut" wire:loading.attr="disabled" class="w-full rounded-xl bg-orange-500 hover:bg-orange-600 px-5 py-3 text-sm font-semibold text-white disabled:opacity-50 transition-colors flex justify-center items-center gap-2 shadow-sm">
                            <span wire:loading.remove wire:target="checkOut">
                                <svg class="w-5 h-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                                Submit Check Out
                            </span>
                            <span wire:loading wire:target="checkOut" class="flex items-center gap-2">
                                <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                Menyimpan...
                            </span>
                        </button>
                    </div>
                @else
                    <!-- Selesai -->
                    <div class="py-8">
                        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-emerald-50 mb-4 shadow-sm border border-emerald-100">
                            <svg class="h-8 w-8 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                        </div>
                        <h2 class="text-xl font-bold text-gray-900">Presensi Selesai</h2>
                        <p class="text-sm text-gray-500 mt-2">Anda telah menyelesaikan presensi untuk hari ini.</p>
                        
                        <div class="mt-6 bg-gray-50 p-5 rounded-xl text-left text-sm text-gray-700 border border-gray-100 inline-block w-full max-w-sm space-y-3">
                            <div class="flex justify-between items-center border-b border-gray-100 pb-2">
                                <span class="text-gray-500">Check In</span>
                                <span class="font-semibold text-emerald-600">{{ $attendanceToday->check_in_at->format('H:i') }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-500">Check Out</span>
                                <span class="font-semibold text-orange-600">{{ $attendanceToday->check_out_at->format('H:i') }}</span>
                            </div>
                        </div>
                    </div>
                @endif
                
                <script>
                    function attendanceHandler() {
                        return {
                            locationReady: false,
                            latitude: null,
                            longitude: null,
                            accuracy: null,
                            
                            getLocation() {
                                if (navigator.geolocation) {
                                    navigator.geolocation.getCurrentPosition(
                                        (position) => {
                                            this.latitude = position.coords.latitude;
                                            this.longitude = position.coords.longitude;
                                            this.accuracy = position.coords.accuracy;
                                            this.locationReady = true;
                                            
                                            @this.set('latitude', this.latitude);
                                            @this.set('longitude', this.longitude);
                                            @this.set('accuracy', this.accuracy);
                                        },
                                        (error) => {
                                            let msg = 'Lokasi gagal diperoleh.';
                                            if (error.code === 1) msg = 'Akses lokasi ditolak. Harap izinkan akses lokasi di browser Anda.';
                                            if (error.code === 2) msg = 'Lokasi tidak tersedia (GPS tidak aktif).';
                                            if (error.code === 3) msg = 'Waktu permintaan lokasi habis.';
                                            alert(msg);
                                        },
                                        { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
                                    );
                                } else {
                                    alert('Browser Anda tidak mendukung Geolocation.');
                                }
                            }
                        }
                    }
                </script>
            </div>
        @else
            <div class="text-center py-8">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-cyan-50 mb-4 shadow-sm">
                    <svg class="h-7 w-7 text-cyan-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900">Akses Terbatas</h3>
                <p class="text-sm text-gray-500 mt-1">Hanya peserta PKL yang dapat melakukan check-in dan check-out.</p>
            </div>
        @endif
    </div>

    @if(Auth::user()->isStudent() && Auth::user()->student)
    <div class="mt-8 bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-4 py-4 sm:px-6 sm:py-5 border-b border-gray-100 flex items-center gap-3">
            <div class="h-8 w-8 rounded-lg bg-blue-50 flex items-center justify-center">
                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900">Riwayat Presensi Saya</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="fibernet-table-header">
                    <tr>
                        <th scope="col" class="px-4 py-3 sm:px-6 font-semibold text-gray-900">Tanggal</th>
                        <th scope="col" class="px-4 py-3 sm:px-6 font-semibold text-gray-900">Check In</th>
                        <th scope="col" class="px-4 py-3 sm:px-6 font-semibold text-gray-900">Check Out</th>
                        <th scope="col" class="px-4 py-3 sm:px-6 font-semibold text-gray-900">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($history as $record)
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-4 py-3 sm:px-6 sm:py-4 whitespace-nowrap">
                                <div class="font-medium text-gray-900">{{ $record->attendance_date->format('d M Y') }}</div>
                                <div class="text-xs text-gray-400">{{ $record->attendance_date->translatedFormat('l') }}</div>
                            </td>
                            <td class="px-4 py-3 sm:px-6 sm:py-4 whitespace-nowrap">
                                @if($record->check_in_at)
                                    <div class="text-gray-900 font-medium">{{ $record->check_in_at->format('H:i') }}</div>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 sm:px-6 sm:py-4 whitespace-nowrap">
                                @if($record->check_out_at)
                                    <div class="text-gray-900 font-medium">{{ $record->check_out_at->format('H:i') }}</div>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 sm:px-6 sm:py-4 whitespace-nowrap">
                                @if($record->is_approved === false)
                                    <span class="inline-flex items-center rounded-md bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-600 uppercase">PENDING</span>
                                @elseif($record->status === 'present')
                                    <span class="inline-flex items-center rounded-md bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-600 uppercase">HADIR</span>
                                @elseif($record->status === 'late')
                                    <span class="inline-flex items-center rounded-md bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-600 uppercase">TERLAMBAT</span>
                                @else
                                    <span class="inline-flex items-center rounded-md bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600 uppercase">ABSEN</span>
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
                                    <p class="text-sm text-gray-500">Anda belum memiliki riwayat presensi.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($history && method_exists($history, 'hasPages') && $history->hasPages())
        <div class="border-t border-gray-100 px-6 py-4">
            {{ $history->links() }}
        </div>
        @endif
    </div>
    @endif
</div>
