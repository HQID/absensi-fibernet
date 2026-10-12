<?php

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Services\AttendanceService;
use App\Services\GeofenceService;
use App\Models\Attendance;
use App\Models\OfficeLocation;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

new class extends Component
{
    use WithFileUploads, \Livewire\WithPagination;

    public $latitude;
    public $longitude;
    public $accuracy;
    public $photo; // Can be base64 string from canvas or UploadedFile
    
    public $locationType = 'inside';
    public $reason = '';
    public $isLate = false;
    public $liveness_verified = false;
    public $liveness_method = 'BLINK_AND_TURN';

    public $attendanceToday;
    public $errorMessage = '';
    public $successMessage = '';

    // Hasil pra-validasi geofence (Tahap 1) sebelum liveness dibuka
    public $geofenceChecked = false;
    public $geofenceValid = false;
    public $geofenceMessage = '';
    public $geofenceMethod = '';
    public $geofenceDistance = null;

    public function mount()
    {
        $this->loadAttendance();
        $this->isLate = now()->format('H:i') > '08:15';
    }

    public function verifyLocation($latitude, $longitude, $accuracy): void
    {
        $this->latitude = $latitude;
        $this->longitude = $longitude;
        $this->accuracy = $accuracy;
        $this->runGeofenceCheck();
    }

    public function updatedLocationType(): void
    {
        if ($this->latitude !== null) {
            $this->runGeofenceCheck();
        }
    }

    protected function runGeofenceCheck(): void
    {
        $this->geofenceChecked = true;
        $this->geofenceDistance = null;

        // Check-out mengikuti jenis lokasi saat check-in
        $type = $this->attendanceToday?->check_in_at ? $this->attendanceToday->location_type : $this->locationType;

        if ($type === 'outside') {
            $this->geofenceValid = true;
            $this->geofenceMethod = 'outside_duty';
            $this->geofenceMessage = 'Mode Luar Kantor (Dinas): validasi poligon dilewati, presensi memerlukan persetujuan admin.';
            return;
        }

        $office = OfficeLocation::where('active', true)->first();
        if (!$office) {
            $this->geofenceValid = false;
            $this->geofenceMethod = '';
            $this->geofenceMessage = 'Lokasi kantor aktif tidak ditemukan.';
            return;
        }

        $result = app(GeofenceService::class)->validateLocation(
            (float) $this->latitude,
            (float) $this->longitude,
            (float) $this->accuracy,
            $office
        );

        $this->geofenceValid = $result['valid'];
        $this->geofenceMethod = $result['method'];
        $this->geofenceMessage = $result['message'];
        $this->geofenceDistance = $result['distance'];
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
        // Convert base64 dataUrl from canvas to UploadedFile if necessary
        if (is_string($this->photo) && str_starts_with($this->photo, 'data:image')) {
            $dataParts = explode(',', $this->photo);
            if (isset($dataParts[1])) {
                $decoded = base64_decode($dataParts[1]);
                $tempFilePath = tempnam(sys_get_temp_dir(), 'attendance_');
                file_put_contents($tempFilePath, $decoded);
                
                $this->photo = new \Illuminate\Http\UploadedFile(
                    $tempFilePath,
                    'liveness_capture_' . time() . '.jpg',
                    'image/jpeg',
                    null,
                    true
                );
            }
        }

        $this->validate([
            'latitude' => 'required',
            'longitude' => 'required',
            'accuracy' => 'required',
            'photo' => 'required',
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
            $attendanceService->checkIn(
                $student,
                (float) $this->latitude,
                (float) $this->longitude,
                (float) $this->accuracy,
                $this->photo,
                $this->locationType,
                $this->reason,
                (bool) $this->liveness_verified,
                $this->liveness_method
            );
            $this->successMessage = 'Check-in berhasil! Verifikasi liveness telah tersimpan.';
            $this->photo = null;
            $this->liveness_verified = false;
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
            $attendanceService->checkOut($student, (float) $this->latitude, (float) $this->longitude, (float) $this->accuracy);
            $this->successMessage = 'Check-out berhasil!';
            $this->loadAttendance();
        } catch (\Exception $e) {
            $this->errorMessage = $e->getMessage();
        }
    }
};
?>

<div>
    <!-- MediaPipe Face Mesh & Camera Utils Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/@mediapipe/camera_utils/camera_utils.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/@mediapipe/face_mesh/face_mesh.js" crossorigin="anonymous"></script>

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 sm:p-6">
        @if(Auth::user()->isStudent() && Auth::user()->student)
            
            @if($errorMessage)
                <div class="mb-4 rounded-xl bg-red-50 p-4 border border-red-100 flex items-start gap-3 text-left">
                    <div class="h-5 w-5 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                        <svg class="h-3 w-3 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </div>
                    <p class="text-sm text-red-700">{{ $errorMessage }}</p>
                </div>
            @endif

            @if($successMessage)
                <div class="mb-4 rounded-xl bg-emerald-50 p-4 border border-emerald-100 flex items-start gap-3 text-left">
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
                    
                    <!-- Step Progress Indicator -->
                    <div class="flex items-center justify-center gap-2 sm:gap-3 mb-6 text-xs font-semibold">
                        <div class="flex items-center gap-1.5" :class="$wire.geofenceValid ? 'text-emerald-600' : 'text-cyan-600'">
                            <span class="w-6 h-6 rounded-full flex items-center justify-center text-xs text-white" :class="$wire.geofenceValid ? 'bg-emerald-600' : 'bg-cyan-600'">
                                <span x-show="$wire.geofenceValid">✓</span>
                                <span x-show="!$wire.geofenceValid">1</span>
                            </span>
                            <span>Tahap 1: Lokasi & Geofence</span>
                        </div>
                        <div class="w-6 sm:w-10 h-0.5" :class="$wire.geofenceValid ? 'bg-emerald-400' : 'bg-gray-200'"></div>
                        <div class="flex items-center gap-1.5" :class="$wire.geofenceValid ? ((livenessDone || $wire.photo) ? 'text-emerald-600' : 'text-cyan-600') : 'text-gray-400'">
                            <span class="w-6 h-6 rounded-full flex items-center justify-center text-xs" :class="$wire.geofenceValid ? ((livenessDone || $wire.photo) ? 'bg-emerald-600 text-white' : 'bg-cyan-600 text-white') : 'bg-gray-200 text-gray-500'">
                                <span x-show="livenessDone || $wire.photo">✓</span>
                                <span x-show="!livenessDone && !$wire.photo">2</span>
                            </span>
                            <span>Tahap 2: AI Liveness</span>
                        </div>
                    </div>

                    <!-- TAHAP 1: VERIFIKASI SPASIAL (POLIGON GEOFENCE - RAY CASTING) -->
                    <div class="mb-6 rounded-2xl border border-gray-200 bg-white p-4 text-left shadow-sm space-y-4">
                        <div class="flex items-center justify-between pb-2 border-b border-gray-100">
                            <div>
                                <h3 class="text-sm font-semibold text-gray-900 flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                    Tahap 1: Verifikasi Spasial (Poligon Geofencing)
                                </h3>
                                <p class="text-xs text-gray-500 mt-0.5">Koordinat Anda divalidasi di server dengan algoritma Ray Casting terhadap poligon kantor.</p>
                            </div>
                            <template x-if="$wire.geofenceValid">
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                    Lokasi Valid
                                </span>
                            </template>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Jenis Lokasi Presensi</label>
                            <select wire:model.live="locationType" class="mt-1 block w-full rounded-xl border-gray-200 py-2 pl-3 pr-10 text-sm focus:border-cyan-500 focus:outline-none focus:ring-cyan-500">
                                <option value="inside">Di Dalam Kantor (Poligon Geofencing)</option>
                                <option value="outside">Di Luar Kantor (Kunjungan / Dinas)</option>
                            </select>
                            @error('locationType') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <!-- 1A: Ambil lokasi -->
                        <div x-show="!locationReady" class="space-y-3">
                            <div class="flex flex-col sm:flex-row gap-2">
                                <button type="button" @click="getLocation()" :disabled="locating" class="flex-1 rounded-xl bg-cyan-600 hover:bg-cyan-700 disabled:opacity-60 text-white transition-colors px-4 py-2.5 text-sm font-semibold shadow-sm flex items-center justify-center gap-2">
                                    <svg class="w-4 h-4" :class="locating ? 'animate-pulse' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                    <span x-text="locating ? 'Mencari sinyal GPS...' : 'Dapatkan Lokasi Saya (GPS)'"></span>
                                </button>
                                <button type="button" @click="setTestLocation()" class="rounded-xl bg-amber-50 hover:bg-amber-100 border border-amber-200 text-amber-800 transition-colors px-4 py-2.5 text-xs font-semibold flex items-center justify-center gap-1.5 shadow-sm">
                                    <span>💻 Mode Uji Laptop (Kantor)</span>
                                </button>
                            </div>
                            <p class="text-[11px] text-amber-700/90 italic">
                                * Tombol "Mode Uji Laptop" otomatis menyetel titik ke area poligon kantor PT Fiber Networks untuk mempermudah pengujian di laptop.
                            </p>
                        </div>

                        <!-- 1B: Koordinat & hasil validasi -->
                        <div x-show="locationReady" x-cloak class="space-y-3">
                            <div class="bg-slate-50 border border-slate-200 text-slate-700 text-xs p-3 rounded-xl flex items-center justify-between">
                                <span>Koordinat: <span class="font-mono font-semibold" x-text="latitude ? Number(latitude).toFixed(6) : ''"></span>, <span class="font-mono font-semibold" x-text="longitude ? Number(longitude).toFixed(6) : ''"></span> (Akurasi: <span x-text="Math.round(accuracy)" class="font-semibold"></span>m)</span>
                                <button type="button" @click="getLocation()" class="text-xs text-cyan-600 hover:underline shrink-0 ml-2">Perbarui</button>
                            </div>

                            <div wire:loading.flex wire:target="verifyLocation,locationType" class="items-center gap-2 text-xs text-cyan-700 bg-cyan-50 border border-cyan-100 p-3 rounded-xl">
                                <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                Memvalidasi titik terhadap poligon kantor (Ray Casting)...
                            </div>

                            @if($geofenceChecked)
                                <div wire:loading.remove wire:target="verifyLocation,locationType" class="text-xs p-3 rounded-xl border {{ $geofenceValid ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-rose-50 border-rose-200 text-rose-800' }}">
                                    <p class="font-semibold">{{ $geofenceValid ? '✓ ' : '✗ ' }}{{ $geofenceMessage }}</p>
                                    <div class="mt-1.5 flex flex-wrap gap-x-4 gap-y-1 text-[11px] opacity-80">
                                        @if($geofenceMethod === 'polygon')
                                            <span>Metode: Ray Casting (Poligon)</span>
                                        @elseif($geofenceMethod === 'circular')
                                            <span>Metode: Radius Haversine (fallback &mdash; poligon belum diatur admin)</span>
                                        @endif
                                        @if($geofenceDistance !== null)
                                            <span>Jarak ke titik pusat kantor: {{ $geofenceDistance }} m (informasi audit)</span>
                                        @endif
                                    </div>
                                    @unless($geofenceValid)
                                        <p class="mt-1.5 text-[11px]">Pindah ke dalam area kantor lalu klik <b>Perbarui</b>, atau pilih "Di Luar Kantor" jika sedang dinas.</p>
                                    @endunless
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- TAHAP 2: VERIFIKASI BIOMETRIK (AI LIVENESS DETECTION) - terbuka setelah geofence valid -->
                    <div x-show="$wire.geofenceValid" x-cloak class="mb-6 rounded-2xl border border-gray-200 bg-slate-50/60 p-4 text-left">
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <h3 class="text-sm font-semibold text-gray-900 flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                    Tahap 2: Verifikasi Biometrik (AI Liveness)
                                </h3>
                                <p class="text-xs text-gray-500 mt-0.5">Uji tantangan berkedip dan menoleh untuk membuktikan kehadiran fisik langsung.</p>
                            </div>

                            <template x-if="livenessDone || $wire.photo">
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                    Liveness Lolos
                                </span>
                            </template>
                        </div>

                        <!-- Camera Error Alert -->
                        <div x-show="cameraError" x-cloak class="p-3.5 mb-4 bg-rose-50 border border-rose-200 rounded-xl text-xs text-rose-800 space-y-2.5">
                            <div class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-rose-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                <div>
                                    <p class="font-semibold" x-text="cameraError"></p>
                                    <p class="text-[11px] text-rose-600 mt-0.5">
                                        Peramban memerlukan izin akses kamera. Pastikan izin kamera aktif pada peramban Anda.
                                    </p>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-2 pt-1">
                                <button type="button" @click="startCamera()" class="px-3 py-1.5 rounded-lg bg-rose-600 hover:bg-rose-700 text-white font-semibold transition-colors flex items-center gap-1 shadow-sm">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                    Coba Buka Kamera Lagi
                                </button>
                                <button type="button" @click="useManualUpload = true" class="px-3 py-1.5 rounded-lg bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 font-medium transition-colors">
                                    Gunakan Upload Manual
                                </button>
                            </div>
                        </div>

                        <!-- Start Camera Button (If camera not started yet) -->
                        <div x-show="!cameraStarted && !livenessDone && !useManualUpload && !cameraError" class="text-center py-4">
                            <button type="button" @click="startCamera()" class="px-6 py-3 rounded-xl bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-sm transition-colors shadow-sm inline-flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                Buka Kamera & Mulai Verifikasi Wajah
                            </button>
                            <p class="text-[11px] text-gray-500 mt-2">Klik tombol di atas untuk membuka webcam laptop atau kamera HP Anda.</p>
                        </div>

                        <!-- 1. Live Camera Mode -->
                        <div x-show="cameraStarted && !livenessDone && !useManualUpload" class="space-y-4">
                            <!-- Video Frame Container -->
                            <div class="relative w-full max-w-[340px] mx-auto aspect-square rounded-2xl overflow-hidden bg-slate-900 shadow-md border-2 border-cyan-500/40">
                                <!-- Video Element -->
                                <video 
                                    x-ref="videoElement" 
                                    autoplay 
                                    playsinline 
                                    muted 
                                    class="w-full h-full object-cover transform -scale-x-100"
                                ></video>

                                <!-- Realtime AI Status Chips Header -->
                                <div class="absolute top-3 left-3 right-3 flex items-center justify-between text-[11px] pointer-events-none z-10">
                                    <span 
                                        class="px-2.5 py-1 rounded-full font-semibold backdrop-blur shadow-sm transition-all flex items-center gap-1.5"
                                        :class="faceDetected ? 'bg-emerald-500/90 text-white' : 'bg-black/60 text-white/80'"
                                    >
                                        <span class="w-2 h-2 rounded-full" :class="faceDetected ? 'bg-white animate-pulse' : 'bg-amber-400'"></span>
                                        <span x-text="faceDetected ? 'Wajah Terdeteksi' : 'Mencari Wajah...'"></span>
                                    </span>

                                    <template x-if="faceDetected">
                                        <span class="px-2.5 py-1 rounded-full font-mono font-medium bg-black/60 text-white backdrop-blur shadow-sm">
                                            EAR: <span x-text="currentEAR.toFixed(2)"></span> <span class="text-[9px] text-gray-300">(Tutup &le; 0.16)</span>
                                        </span>
                                    </template>
                                </div>

                                <!-- Face Oval Guide Overlay -->
                                <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                                    <div 
                                        class="w-[200px] h-[260px] rounded-[50%] border-2 transition-colors duration-300 flex flex-col items-center justify-center text-center p-2"
                                        :class="isCountingDown ? 'border-emerald-400 bg-emerald-500/20 ring-4 ring-emerald-400/40 animate-pulse' : (faceDetected ? (blinkPassed ? 'border-emerald-400 bg-emerald-500/10' : 'border-cyan-400 bg-cyan-500/10') : 'border-white/50 border-dashed')"
                                    >
                                        <template x-if="!faceDetected && !isCountingDown">
                                            <span class="text-[11px] text-white/90 bg-black/70 px-2.5 py-1 rounded-md">
                                                Posisikan Wajah di Sini
                                            </span>
                                        </template>
                                        <template x-if="faceDetected && !blinkPassed && !isCountingDown">
                                            <span class="text-[11px] text-cyan-200 bg-black/70 px-2.5 py-1 rounded-md" x-text="eyeState"></span>
                                        </template>
                                        <template x-if="faceDetected && blinkPassed && !isCountingDown && !livenessDone">
                                            <span class="text-[11px] text-amber-200 bg-black/70 px-2.5 py-1 rounded-md" x-text="headState"></span>
                                        </template>
                                        <template x-if="isCountingDown && !livenessDone">
                                            <div class="flex flex-col items-center justify-center gap-1 bg-black/80 text-white px-3 py-2 rounded-xl backdrop-blur shadow-lg">
                                                <span class="text-xs font-semibold text-emerald-400 flex items-center gap-1">
                                                    <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                                    Tatap Depan & Diam
                                                </span>
                                                <span class="text-3xl font-black font-mono text-white animate-bounce" x-text="countdownSeconds"></span>
                                                <span class="text-[10px] text-gray-300">Mengambil foto...</span>
                                            </div>
                                        </template>
                                    </div>
                                </div>

                                <!-- Loading Spinner Overlay -->
                                <div x-show="cameraLoading" class="absolute inset-0 bg-slate-900/80 flex flex-col items-center justify-center text-white gap-2">
                                    <svg class="animate-spin h-6 w-6 text-cyan-400" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                    <span class="text-xs" x-text="aiLoadingText"></span>
                                </div>
                            </div>

                            <!-- Challenge Status Card -->
                            <div class="bg-white p-3 rounded-xl border border-gray-200 space-y-2 max-w-[340px] mx-auto text-xs">
                                <!-- Step 1: Blink -->
                                <div class="flex items-center justify-between p-2 rounded-lg" :class="blinkPassed ? 'bg-emerald-50 border border-emerald-100 text-emerald-800' : 'bg-slate-50 text-slate-700'">
                                    <div class="flex items-center gap-2">
                                        <span class="w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-bold" :class="blinkPassed ? 'bg-emerald-600 text-white' : 'bg-slate-300 text-slate-700'">1</span>
                                        <span class="font-medium">Berkediplah (Blink Challenge)</span>
                                    </div>
                                    <div>
                                        <template x-if="blinkPassed">
                                            <span class="text-emerald-600 font-semibold flex items-center gap-1">✓ Lolos</span>
                                        </template>
                                        <template x-if="!blinkPassed">
                                            <span class="text-slate-500 font-medium" x-text="eyeState"></span>
                                        </template>
                                    </div>
                                </div>

                                <!-- Step 2: Head Turn Right -->
                                <div class="flex items-center justify-between p-2 rounded-lg" :class="rightTurnPassed ? 'bg-emerald-50 border border-emerald-100 text-emerald-800' : (blinkPassed ? 'bg-cyan-50 border border-cyan-100 text-cyan-800' : 'bg-slate-50 text-slate-400')">
                                    <div class="flex items-center gap-2">
                                        <span class="w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-bold" :class="rightTurnPassed ? 'bg-emerald-600 text-white' : (blinkPassed ? 'bg-cyan-600 text-white' : 'bg-slate-300 text-slate-700')">2</span>
                                        <span class="font-medium">Menoleh ke Kanan</span>
                                    </div>
                                    <div>
                                        <template x-if="rightTurnPassed">
                                            <span class="text-emerald-600 font-semibold flex items-center gap-1">✓ Lolos</span>
                                        </template>
                                        <template x-if="!rightTurnPassed && blinkPassed">
                                            <span class="text-cyan-600 font-medium" :class="rightTurnFrames > 0 ? 'animate-pulse font-bold text-amber-600' : ''" x-text="rightTurnFrames > 0 ? 'Tahan Posisi...' : 'Menoleh ke Kanan'"></span>
                                        </template>
                                        <template x-if="!blinkPassed">
                                            <span class="text-slate-400">-</span>
                                        </template>
                                    </div>
                                </div>

                                <!-- Step 3: Head Turn Left -->
                                <div class="flex items-center justify-between p-2 rounded-lg" :class="leftTurnPassed ? 'bg-emerald-50 border border-emerald-100 text-emerald-800' : (blinkPassed ? 'bg-cyan-50 border border-cyan-100 text-cyan-800' : 'bg-slate-50 text-slate-400')">
                                    <div class="flex items-center gap-2">
                                        <span class="w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-bold" :class="leftTurnPassed ? 'bg-emerald-600 text-white' : (blinkPassed ? 'bg-cyan-600 text-white' : 'bg-slate-300 text-slate-700')">3</span>
                                        <span class="font-medium">Menoleh ke Kiri</span>
                                    </div>
                                    <div>
                                        <template x-if="leftTurnPassed">
                                            <span class="text-emerald-600 font-semibold flex items-center gap-1">✓ Lolos</span>
                                        </template>
                                        <template x-if="!leftTurnPassed && blinkPassed">
                                            <span class="text-cyan-600 font-medium" :class="leftTurnFrames > 0 ? 'animate-pulse font-bold text-amber-600' : ''" x-text="leftTurnFrames > 0 ? 'Tahan Posisi...' : 'Menoleh ke Kiri'"></span>
                                        </template>
                                        <template x-if="!blinkPassed">
                                            <span class="text-slate-400">-</span>
                                        </template>
                                    </div>
                                </div>

                                <!-- Bypass button for testing -->
                                <div class="pt-1 text-center border-t border-gray-100">
                                    <button type="button" @click="captureSnapshot()" class="text-[11px] text-cyan-600 hover:text-cyan-800 hover:underline inline-flex items-center gap-1">
                                        <span>📸 Ambil Foto Sekarang (Lewati AI untuk Pengujian)</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- 2. Snapshot Captured Preview Mode -->
                        <div x-show="livenessDone" x-cloak class="space-y-3 text-center">
                            <div class="relative w-full max-w-[280px] mx-auto aspect-square rounded-2xl overflow-hidden bg-slate-900 border-2 border-emerald-500 shadow-md">
                                <template x-if="capturedPhotoUrl">
                                    <img :src="capturedPhotoUrl" alt="Snapshot Liveness" class="w-full h-full object-cover">
                                </template>
                                <div class="absolute bottom-2 left-2 right-2 bg-emerald-950/80 backdrop-blur text-emerald-200 text-[11px] py-1 px-2 rounded-lg flex items-center justify-center gap-1.5 font-medium">
                                    <svg class="w-3.5 h-3.5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    Auto-Captured & Terkompresi (~120KB)
                                </div>
                            </div>

                            <button 
                                type="button" 
                                @click="retakeLiveness()" 
                                class="inline-flex items-center gap-1.5 text-xs text-slate-600 hover:text-cyan-600 font-medium py-1 px-3 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 transition-colors shadow-sm"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                                Ulangi Verifikasi Kamera
                            </button>
                        </div>

                        <!-- 3. Fallback Manual Upload (Jika Kamera Bermasalah) -->
                        <div x-show="useManualUpload" x-cloak class="mt-3">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Unggah Foto Manual (Fallback)</label>
                            <input type="file" wire:model="photo" accept="image/*" capture="user" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-cyan-50 file:text-cyan-700 hover:file:bg-cyan-100 transition-colors" />
                            <button type="button" @click="useManualUpload = false; startCamera()" class="mt-2 text-xs text-cyan-600 hover:underline">← Kembali ke Kamera Liveness</button>
                        </div>

                        <div x-show="!useManualUpload && !livenessDone" class="text-center mt-3">
                            <button type="button" @click="useManualUpload = true" class="text-[11px] text-slate-400 hover:text-slate-600 underline">
                                Kamera bermasalah? Klik di sini untuk unggah foto manual
                            </button>
                        </div>
                    </div>

                    <!-- TAHAP AKHIR: KIRIM PRESENSI (terbuka setelah geofence & liveness lolos) -->
                    <div x-show="$wire.geofenceValid && (livenessDone || $wire.photo)" x-cloak class="rounded-2xl border border-gray-200 bg-white p-4 text-left shadow-sm mb-6">
                        <div class="space-y-4">
                            <div x-show="$wire.locationType === 'outside' || $wire.isLate">
                                <label class="block text-xs font-medium text-gray-700 mb-1">Keterangan / Alasan <span class="text-red-500">*</span></label>
                                <p class="text-[11px] text-gray-500 mb-1.5">Wajib diisi karena Anda memilih Luar Kantor atau Anda datang terlambat.</p>
                                <textarea wire:model="reason" rows="2" class="mt-1 block w-full rounded-xl border-gray-200 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 text-sm" placeholder="Masukkan alasan Anda..."></textarea>
                                @error('reason') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <!-- Submit Button -->
                            <div>
                                <button type="button" wire:click="checkIn" wire:loading.attr="disabled" class="w-full rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white transition-colors px-5 py-3 text-sm font-semibold disabled:opacity-50 flex justify-center items-center gap-2 shadow-sm">
                                    <span wire:loading.remove wire:target="checkIn" class="flex items-center gap-2">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                        Kirim Presensi (Submit Check In)
                                    </span>
                                    <span wire:loading wire:target="checkIn" class="flex items-center gap-2">
                                        <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                        Memvalidasi Spasial Ray-Casting & Menyimpan...
                                    </span>
                                </button>
                            </div>
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
                        @if($attendanceToday->liveness_verified)
                        <div class="flex justify-between items-center pt-1 border-t border-gray-200/60">
                            <span class="text-gray-500">Verifikasi Liveness</span>
                            <span class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200">
                                ✓ {{ $attendanceToday->liveness_method ?? 'VERIFIED' }}
                            </span>
                        </div>
                        @endif
                    </div>

                    <div x-show="!locationReady" class="mb-4 space-y-2">
                        <button type="button" @click="getLocation()" class="rounded-xl bg-cyan-600 hover:bg-cyan-700 text-white transition-colors px-6 py-3 text-sm font-semibold w-full sm:w-auto shadow-sm">
                            <span class="flex items-center justify-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                Dapatkan Lokasi Saya (GPS)
                            </span>
                        </button>
                        <div class="pt-1">
                            <button type="button" @click="setTestLocation()" class="text-xs text-amber-700 hover:underline inline-flex items-center gap-1 font-medium">
                                <span>💻 Mode Uji Laptop (Gunakan Lokasi Kantor)</span>
                            </button>
                        </div>
                    </div>

                    <div x-show="locationReady" style="display: none;" class="mb-4">
                        @if($geofenceChecked)
                            <div class="text-sm p-3 rounded-xl mb-4 text-left border {{ $geofenceValid ? 'bg-emerald-50 border-emerald-100 text-emerald-800' : 'bg-rose-50 border-rose-200 text-rose-800' }}">
                                {{ $geofenceValid ? '✓ ' : '✗ ' }}{{ $geofenceMessage }}
                                <span class="block text-[11px] opacity-80 mt-0.5">Akurasi GPS: <span x-text="Math.round(accuracy)"></span>m</span>
                            </div>
                        @endif

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
                            @if($attendanceToday->liveness_verified)
                            <div class="flex justify-between items-center pt-2 border-t border-gray-100 text-xs">
                                <span class="text-gray-500">AI Liveness</span>
                                <span class="text-emerald-700 font-semibold bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                                    ✓ Lolos Verifikasi
                                </span>
                            </div>
                            @endif
                        </div>
                    </div>
                @endif
                
                <!-- Alpine.js Liveness Controller -->
                <script>
                    // Plain non-reactive references for MediaPipe and Camera WASM
                    // (Kept outside Alpine's x-data to avoid Proxy conflicts with WebAssembly property '$$')
                    let _faceMeshInstance = null;
                    let _videoStreamInstance = null;
                    let _animFrameId = null;
                    let _procCanvas = null;
                    let _procCtx = null;
                    let _isProcessingFrame = false;

                    function attendanceHandler() {
                        return {
                            locationReady: false,
                            locating: false,
                            latitude: null,
                            longitude: null,
                            accuracy: null,

                            // Liveness state
                            cameraStarted: false,
                            cameraLoading: false,
                            cameraError: null,
                            aiLoadingText: 'Memuat Engine AI...',
                            faceDetected: false,
                            blinkPassed: false,
                            turnPassed: false,
                            livenessDone: false,
                            capturedPhotoUrl: null,
                            useManualUpload: false,

                            // Realtime metrics
                            currentEAR: 0,
                            baselineEAR: 0,
                            eyeState: 'Mencari Wajah...',
                            headState: 'Posisi Normal',
                            eyeClosedCount: 0,
                            rightTurnPassed: false,
                            leftTurnPassed: false,
                            rightTurnFrames: 0,
                            leftTurnFrames: 0,
                            isCountingDown: false,
                            countdownSeconds: 2,
                            countdownTimer: null,

                            setTestLocation() {
                                this.applyLocation(-0.87495286, 119.88654882, 10);
                            },

                            // Kirim koordinat ke server -> validasi Ray Casting (Tahap 1)
                            applyLocation(lat, lng, acc) {
                                // Lokasi berubah: hentikan kamera agar liveness diulang setelah geofence valid lagi
                                if (this.cameraStarted) this.stopCamera();
                                this.latitude = lat;
                                this.longitude = lng;
                                this.accuracy = acc;
                                this.locationReady = true;
                                this.$wire.verifyLocation(lat, lng, acc);
                            },

                            getLocation() {
                                if (!navigator.geolocation) {
                                    alert('Browser Anda tidak mendukung Geolocation.');
                                    return;
                                }
                                this.locating = true;
                                navigator.geolocation.getCurrentPosition(
                                    (position) => {
                                        this.locating = false;
                                        this.applyLocation(position.coords.latitude, position.coords.longitude, position.coords.accuracy);
                                    },
                                    (error) => {
                                        this.locating = false;
                                        let msg = 'Lokasi gagal diperoleh.';
                                        if (error.code === 1) msg = 'Akses lokasi ditolak. Harap izinkan akses lokasi di browser Anda.';
                                        if (error.code === 2) msg = 'Lokasi tidak tersedia (GPS tidak aktif).';
                                        if (error.code === 3) msg = 'Waktu permintaan lokasi habis.';
                                        alert(msg);
                                    },
                                    { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
                                );
                            },

                            async startCamera() {
                                this.cameraLoading = true;
                                this.aiLoadingText = 'Menghubungkan ke kamera...';
                                this.cameraError = null;
                                this.blinkPassed = false;
                                this.turnPassed = false;
                                this.livenessDone = false;
                                this.capturedPhotoUrl = null;
                                this.currentEAR = 0;
                                this.baselineEAR = 0;
                                this.eyeClosedCount = 0;
                                this.rightTurnPassed = false;
                                this.leftTurnPassed = false;
                                this.rightTurnFrames = 0;
                                this.leftTurnFrames = 0;
                                if (this.countdownTimer) clearInterval(this.countdownTimer);
                                this.countdownTimer = null;
                                this.isCountingDown = false;
                                this.countdownSeconds = 2;

                                if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                                    this.cameraLoading = false;
                                    this.cameraError = "Peramban ini tidak mendukung akses kamera langsung (WebRTC).";
                                    return;
                                }

                                try {
                                    let stream = null;
                                    try {
                                        stream = await navigator.mediaDevices.getUserMedia({
                                            video: {
                                                facingMode: 'user',
                                                width: { ideal: 640 },
                                                height: { ideal: 480 }
                                            },
                                            audio: false
                                        });
                                    } catch (e1) {
                                        console.warn("Attempt 1 front camera failed, trying fallback:", e1);
                                        stream = await navigator.mediaDevices.getUserMedia({
                                            video: true,
                                            audio: false
                                        });
                                    }

                                    _videoStreamInstance = stream;
                                    const video = this.$refs.videoElement;
                                    if (video) {
                                        video.srcObject = _videoStreamInstance;
                                        await video.play();
                                    }

                                    this.cameraStarted = true;
                                    this.initFaceMesh();
                                } catch (err) {
                                    console.error("Camera access error:", err);
                                    this.cameraLoading = false;
                                    this.cameraStarted = false;
                                    this.cameraError = "Izin kamera ditolak atau tidak tersedia (" + (err.message || "Permission denied") + ")";
                                }
                            },

                            initFaceMesh() {
                                this.cameraLoading = true;
                                this.aiLoadingText = 'Memuat Engine AI FaceMesh...';

                                if (typeof FaceMesh === 'undefined') {
                                    setTimeout(() => this.initFaceMesh(), 150);
                                    return;
                                }

                                try {
                                    if (!_faceMeshInstance) {
                                        _faceMeshInstance = new FaceMesh({
                                            locateFile: (file) => `https://cdn.jsdelivr.net/npm/@mediapipe/face_mesh@0.4.1633559619/${file}`
                                        });

                                        _faceMeshInstance.setOptions({
                                            maxNumFaces: 1,
                                            refineLandmarks: false,
                                            minDetectionConfidence: 0.35,
                                            minTrackingConfidence: 0.35
                                        });

                                        _faceMeshInstance.onResults((results) => this.onFaceResults(results));
                                    }

                                    this.cameraLoading = false;
                                    this.processFrames();
                                } catch (err) {
                                    console.error("Gagal inisialisasi FaceMesh:", err);
                                    this.cameraLoading = false;
                                    this.cameraError = "Gagal memuat model AI: " + err.message;
                                }
                            },

                            async processFrames() {
                                if (!this.cameraStarted || this.livenessDone) return;
                                if (_isProcessingFrame) {
                                    _animFrameId = requestAnimationFrame(() => this.processFrames());
                                    return;
                                }

                                const video = this.$refs.videoElement;
                                if (video && video.readyState >= 2 && video.videoWidth > 0 && _faceMeshInstance) {
                                    _isProcessingFrame = true;
                                    try {
                                        if (!_procCanvas) {
                                            _procCanvas = document.createElement('canvas');
                                            _procCanvas.width = 320;
                                            _procCanvas.height = 240;
                                            _procCtx = _procCanvas.getContext('2d', { willReadFrequently: true });
                                        }
                                        _procCtx.drawImage(video, 0, 0, 320, 240);
                                        await _faceMeshInstance.send({ image: _procCanvas });
                                    } catch (e) {
                                        console.warn("FaceMesh send error:", e);
                                    } finally {
                                        _isProcessingFrame = false;
                                    }
                                }

                                if (this.cameraStarted && !this.livenessDone) {
                                    _animFrameId = requestAnimationFrame(() => this.processFrames());
                                }
                            },

                            onFaceResults(results) {
                                if (this.livenessDone || this.isCountingDown) return;

                                if (!results || !results.multiFaceLandmarks || results.multiFaceLandmarks.length === 0) {
                                    this.faceDetected = false;
                                    this.eyeState = 'Mencari Wajah...';
                                    return;
                                }

                                this.faceDetected = true;
                                const landmarks = results.multiFaceLandmarks[0];

                                // True isotropic Euclidean distance with canvas pixel dimensions (320x240)
                                const W = 320;
                                const H = 240;
                                const pxDist = (i1, i2) => {
                                    const p1 = landmarks[i1];
                                    const p2 = landmarks[i2];
                                    const dx = (p1.x - p2.x) * W;
                                    const dy = (p1.y - p2.y) * H;
                                    return Math.hypot(dx, dy);
                                };

                                const calcEAR = (p1, p2, p3, p4, p5, p6) => {
                                    const v1 = pxDist(p2, p6);
                                    const v2 = pxDist(p3, p5);
                                    const h = pxDist(p1, p4);
                                    return h > 0 ? (v1 + v2) / (2.0 * h) : 0;
                                };

                                // Left & right eye landmarks (standard 6-point EAR)
                                const leftEAR = calcEAR(33, 160, 158, 133, 153, 144);
                                const rightEAR = calcEAR(362, 385, 387, 263, 373, 380);
                                const avgEAR = (leftEAR + rightEAR) / 2.0;
                                this.currentEAR = avgEAR;

                                // Track rolling baseline open EAR
                                if (avgEAR > this.baselineEAR && avgEAR < 0.45) {
                                    this.baselineEAR = avgEAR;
                                }

                                // Challenge 1: Blink Detection
                                // Eye must close completely: EAR <= 0.16 (or drop > 45% from open baseline).
                                // Squinting (mengerutkan mata) stays around 0.19 - 0.23 and will NOT trigger isClosed.
                                if (!this.blinkPassed) {
                                    const isClosed = avgEAR <= 0.16 || (this.baselineEAR >= 0.24 && avgEAR <= this.baselineEAR * 0.52);
                                    const isOpen = avgEAR >= 0.22 || (this.baselineEAR >= 0.24 && avgEAR >= this.baselineEAR * 0.78);

                                    if (isClosed) {
                                        this.eyeClosedCount++;
                                        this.eyeState = `Mata Tertutup (${this.eyeClosedCount >= 2 ? 'Bagus! Buka mata sekarang' : 'Tahan...'})`;
                                    } else if (isOpen) {
                                        if (this.eyeClosedCount >= 2) {
                                            this.blinkPassed = true;
                                            this.eyeState = 'Kedipan Lolos!';
                                        } else {
                                            // Reset if opened before reaching 2 closed frames (e.g. quick squint or noise)
                                            this.eyeClosedCount = 0;
                                            this.eyeState = 'Silakan Berkedip Penuh (Tutup lalu Buka)';
                                        }
                                    }
                                }

                                // Challenge 2 & 3: Full Head Turn (Wajib Menoleh ke Kanan Penuh DAN Menoleh ke Kiri Penuh)
                                if (this.blinkPassed && (!this.rightTurnPassed || !this.leftTurnPassed)) {
                                    const nose = landmarks[1];
                                    const leftCheek = landmarks[234];
                                    const rightCheek = landmarks[454];

                                    const xMin = Math.min(leftCheek.x, rightCheek.x);
                                    const xMax = Math.max(leftCheek.x, rightCheek.x);
                                    const faceW = xMax - xMin;

                                    // Posisi horizontal hidung terhadap lebar rentang wajah (tengah = ~0.50)
                                    // Goyang sedikit hanya berada di rentang 0.38 - 0.62
                                    const posNose = faceW > 0 ? (nose.x - xMin) / faceW : 0.50;

                                    // Menoleh ke Kanan Penuh (posNose <= 0.22): Hidung bergeser jauh ke tepi kanan
                                    // Menoleh ke Kiri Penuh (posNose >= 0.78): Hidung bergeser jauh ke tepi kiri
                                    const isFullRight = posNose <= 0.22;
                                    const isFullLeft = posNose >= 0.78;

                                    // 1. Verifikasi Menoleh ke Kanan Penuh
                                    if (!this.rightTurnPassed) {
                                        if (isFullRight) {
                                            this.rightTurnFrames++;
                                            if (this.rightTurnFrames >= 4) {
                                                this.rightTurnPassed = true;
                                                this.headState = this.leftTurnPassed ? 'Kanan & Kiri Lolos!' : 'Kanan Lolos! Sekarang Menoleh ke Kiri Penuh';
                                            } else {
                                                this.headState = 'Menoleh ke Kanan Penuh (Tahan sebentar...)';
                                            }
                                        } else {
                                            this.rightTurnFrames = 0;
                                        }
                                    }

                                    // 2. Verifikasi Menoleh ke Kiri Penuh
                                    if (!this.leftTurnPassed) {
                                        if (isFullLeft) {
                                            this.leftTurnFrames++;
                                            if (this.leftTurnFrames >= 4) {
                                                this.leftTurnPassed = true;
                                                this.headState = this.rightTurnPassed ? 'Kanan & Kiri Lolos!' : 'Kiri Lolos! Sekarang Menoleh ke Kanan Penuh';
                                            } else {
                                                this.headState = 'Menoleh ke Kiri Penuh (Tahan sebentar...)';
                                            }
                                        } else {
                                            this.leftTurnFrames = 0;
                                        }
                                    }

                                    // Teks instruksi dinamis
                                    if (!isFullRight && !isFullLeft) {
                                        if (!this.rightTurnPassed && !this.leftTurnPassed) {
                                            this.headState = 'Menoleh ke Kanan Penuh';
                                        } else if (this.rightTurnPassed && !this.leftTurnPassed) {
                                            this.headState = 'Sekarang Menoleh ke Kiri Penuh';
                                        } else if (!this.rightTurnPassed && this.leftTurnPassed) {
                                            this.headState = 'Sekarang Menoleh ke Kanan Penuh';
                                        }
                                    }

                                    if (this.rightTurnPassed && this.leftTurnPassed) {
                                        this.turnPassed = true;
                                    }
                                }

                                // Complete & Auto-Capture when all challenges pass (dengan jeda countdown stabilisasi)
                                if (this.blinkPassed && this.rightTurnPassed && this.leftTurnPassed && !this.livenessDone) {
                                    if (!this.isCountingDown) {
                                        this.startCaptureCountdown();
                                    }
                                }
                            },

                            startCaptureCountdown() {
                                if (this.isCountingDown || this.livenessDone) return;
                                this.isCountingDown = true;
                                this.countdownSeconds = 2;
                                this.headState = 'Tatap Depan & Diam';

                                if (this.countdownTimer) clearInterval(this.countdownTimer);
                                this.countdownTimer = setInterval(() => {
                                    this.countdownSeconds--;
                                    if (this.countdownSeconds <= 0) {
                                        clearInterval(this.countdownTimer);
                                        this.countdownTimer = null;
                                        this.isCountingDown = false;
                                        this.captureSnapshot();
                                    }
                                }, 1000);
                            },

                            captureSnapshot() {
                                if (this.countdownTimer) {
                                    clearInterval(this.countdownTimer);
                                    this.countdownTimer = null;
                                }
                                this.isCountingDown = false;

                                const video = this.$refs.videoElement;
                                if (!video) return;

                                const canvas = document.createElement('canvas');
                                canvas.width = 640;
                                canvas.height = 480;
                                const ctx = canvas.getContext('2d');

                                // Mirror horizontally to match preview
                                ctx.translate(640, 0);
                                ctx.scale(-1, 1);
                                ctx.drawImage(video, 0, 0, 640, 480);

                                // Client-side JPEG Compression with Quality 0.70 (~120KB)
                                const compressedBase64 = canvas.toDataURL('image/jpeg', 0.70);
                                this.capturedPhotoUrl = compressedBase64;

                                // Stop video stream
                                this.stopCamera();

                                this.livenessDone = true;

                                // Update Livewire attributes
                                @this.set('photo', compressedBase64);
                                @this.set('liveness_verified', true);
                                @this.set('liveness_method', 'BLINK_AND_TURN');
                            },

                            stopCamera() {
                                if (this.countdownTimer) {
                                    clearInterval(this.countdownTimer);
                                    this.countdownTimer = null;
                                }
                                this.isCountingDown = false;
                                if (_animFrameId) {
                                    cancelAnimationFrame(_animFrameId);
                                    _animFrameId = null;
                                }
                                if (_videoStreamInstance) {
                                    _videoStreamInstance.getTracks().forEach(track => track.stop());
                                    _videoStreamInstance = null;
                                }
                                _isProcessingFrame = false;
                                this.cameraStarted = false;
                            },

                            retakeLiveness() {
                                if (this.countdownTimer) {
                                    clearInterval(this.countdownTimer);
                                    this.countdownTimer = null;
                                }
                                this.isCountingDown = false;
                                this.countdownSeconds = 2;
                                this.stopCamera();
                                this.capturedPhotoUrl = null;
                                this.livenessDone = false;
                                this.blinkPassed = false;
                                this.turnPassed = false;
                                this.eyeClosedCount = 0;
                                this.rightTurnPassed = false;
                                this.leftTurnPassed = false;
                                this.rightTurnFrames = 0;
                                this.leftTurnFrames = 0;
                                this.baselineEAR = 0;
                                @this.set('photo', null);
                                @this.set('liveness_verified', false);
                                this.startCamera();
                            }
                        };
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
                        <th scope="col" class="px-4 py-3 sm:px-6 font-semibold text-gray-900">Liveness</th>
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
                            <td class="px-4 py-3 sm:px-6 sm:py-4 whitespace-nowrap">
                                @if($record->liveness_verified)
                                    <span class="inline-flex items-center gap-1 text-xs font-medium text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">
                                        <svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                        {{ $record->liveness_method ?? 'VERIFIED' }}
                                    </span>
                                @else
                                    <span class="text-xs text-gray-400">Manual / N/A</span>
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
