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
    public $photo; // Can be base64 string from canvas or UploadedFile
    
    public $locationType = 'inside';
    public $reason = '';
    public $isLate = false;
    public $liveness_verified = false;
    public $liveness_method = 'BLINK_AND_TURN';

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
                    
                    <!-- Step 1: Acquire Location Button -->
                    <div x-show="!locationReady" class="mb-4">
                        <button type="button" @click="getLocation()" class="rounded-xl bg-cyan-600 hover:bg-cyan-700 text-white transition-colors px-6 py-3 text-sm font-semibold w-full sm:w-auto shadow-sm">
                            <span class="flex items-center justify-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                Dapatkan Lokasi Saya
                            </span>
                        </button>
                    </div>

                    <!-- Step 2: Location Acquired & AI Liveness Verification -->
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
                                <option value="inside">Di Dalam Kantor (Poligon Geofencing)</option>
                                <option value="outside">Di Luar Kantor (Kunjungan / Dinas)</option>
                            </select>
                            @error('locationType') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div x-show="$wire.locationType === 'outside' || $wire.isLate" class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Keterangan / Alasan <span class="text-red-500">*</span></label>
                            <p class="text-xs text-gray-500 mb-2">Wajib diisi karena Anda memilih Luar Kantor atau Anda datang terlambat.</p>
                            <textarea wire:model="reason" rows="2" class="mt-1 block w-full rounded-xl border-gray-200 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 sm:text-sm" placeholder="Masukkan alasan Anda..."></textarea>
                            @error('reason') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <!-- LIVENESS VERIFICATION SECTION -->
                        <div class="mb-6 rounded-2xl border border-gray-200 bg-slate-50/60 p-4">
                            <div class="flex items-center justify-between mb-3">
                                <div>
                                    <h3 class="text-sm font-semibold text-gray-900 flex items-center gap-1.5">
                                        <svg class="w-4 h-4 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                        Verifikasi Biometrik (AI Liveness Detection)
                                    </h3>
                                    <p class="text-xs text-gray-500 mt-0.5">Ikuti panduan di layar untuk membuktikan kehadiran fisik nyata.</p>
                                </div>

                                <template x-if="livenessDone">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                        Liveness Lolos
                                    </span>
                                </template>
                            </div>

                            <!-- Camera Error Alert -->
                            <div x-show="cameraError" x-cloak class="p-3 mb-3 bg-red-50 border border-red-200 rounded-xl text-xs text-red-700 flex items-center justify-between">
                                <span x-text="cameraError"></span>
                                <button type="button" @click="useManualUpload = true" class="underline font-semibold ml-2">Gunakan Upload Manual</button>
                            </div>

                            <!-- 1. Live Camera Mode -->
                            <div x-show="!livenessDone && !useManualUpload" class="space-y-4">
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

                                    <!-- Face Oval Guide Overlay -->
                                    <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                                        <div 
                                            class="w-[200px] h-[260px] rounded-[50%] border-2 transition-colors duration-300 flex items-center justify-center"
                                            :class="faceDetected ? (blinkPassed ? 'border-emerald-400 bg-emerald-500/10' : 'border-cyan-400 bg-cyan-500/10') : 'border-white/50 border-dashed'"
                                        >
                                            <span x-show="!faceDetected" class="text-[11px] text-white/80 bg-black/60 px-2 py-1 rounded-md">
                                                Arahkan Wajah Ke Sini
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Loading Spinner Overlay -->
                                    <div x-show="cameraLoading" class="absolute inset-0 bg-slate-900/80 flex flex-col items-center justify-center text-white gap-2">
                                        <svg class="animate-spin h-6 w-6 text-cyan-400" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                        <span class="text-xs">Memuat Model MediaPipe AI...</span>
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
                                                <span class="text-slate-400">Menunggu kedipan...</span>
                                            </template>
                                        </div>
                                    </div>

                                    <!-- Step 2: Head Turn -->
                                    <div class="flex items-center justify-between p-2 rounded-lg" :class="turnPassed ? 'bg-emerald-50 border border-emerald-100 text-emerald-800' : (blinkPassed ? 'bg-cyan-50 border border-cyan-100 text-cyan-800' : 'bg-slate-50 text-slate-400')">
                                        <div class="flex items-center gap-2">
                                            <span class="w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-bold" :class="turnPassed ? 'bg-emerald-600 text-white' : (blinkPassed ? 'bg-cyan-600 text-white' : 'bg-slate-300 text-slate-700')">2</span>
                                            <span class="font-medium">Tolehkan Kepala ke Samping</span>
                                        </div>
                                        <div>
                                            <template x-if="turnPassed">
                                                <span class="text-emerald-600 font-semibold flex items-center gap-1">✓ Lolos</span>
                                            </template>
                                            <template x-if="!turnPassed && blinkPassed">
                                                <span class="text-cyan-600 animate-pulse font-medium">Tolehkan kepala...</span>
                                            </template>
                                            <template x-if="!blinkPassed">
                                                <span class="text-slate-400">-</span>
                                            </template>
                                        </div>
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

                        <!-- Submit Button -->
                        <div x-show="$wire.photo || livenessDone">
                            <button type="button" wire:click="checkIn" wire:loading.attr="disabled" class="w-full rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white transition-colors px-5 py-3 text-sm font-semibold disabled:opacity-50 flex justify-center items-center gap-2 shadow-sm">
                                <span wire:loading.remove wire:target="checkIn">
                                    <svg class="w-5 h-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                    Submit Check In
                                </span>
                                <span wire:loading wire:target="checkIn" class="flex items-center gap-2">
                                    <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                    Memvalidasi Spasial & Menyimpan...
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
                        @if($attendanceToday->liveness_verified)
                        <div class="flex justify-between items-center pt-1 border-t border-gray-200/60">
                            <span class="text-gray-500">Verifikasi Liveness</span>
                            <span class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200">
                                ✓ {{ $attendanceToday->liveness_method ?? 'VERIFIED' }}
                            </span>
                        </div>
                        @endif
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
                    function attendanceHandler() {
                        return {
                            locationReady: false,
                            latitude: null,
                            longitude: null,
                            accuracy: null,

                            // Liveness state
                            cameraStarted: false,
                            cameraLoading: false,
                            cameraError: null,
                            faceDetected: false,
                            blinkPassed: false,
                            turnPassed: false,
                            livenessDone: false,
                            capturedPhotoUrl: null,
                            useManualUpload: false,

                            // Math parameters
                            eyeClosedCount: 0,
                            faceMesh: null,
                            videoStream: null,
                            animFrameId: null,

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

                                            // Start camera for liveness verification
                                            this.startCamera();
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
                            },

                            async startCamera() {
                                this.cameraLoading = true;
                                this.cameraError = null;
                                this.blinkPassed = false;
                                this.turnPassed = false;
                                this.livenessDone = false;
                                this.capturedPhotoUrl = null;

                                try {
                                    this.videoStream = await navigator.mediaDevices.getUserMedia({
                                        video: {
                                            facingMode: 'user',
                                            width: { ideal: 640 },
                                            height: { ideal: 480 }
                                        },
                                        audio: false
                                    });

                                    const video = this.$refs.videoElement;
                                    video.srcObject = this.videoStream;
                                    await video.play();

                                    this.cameraStarted = true;
                                    this.cameraLoading = false;

                                    this.initFaceMesh();
                                } catch (err) {
                                    console.error("Camera access error:", err);
                                    this.cameraLoading = false;
                                    this.cameraError = "Izin kamera ditolak atau tidak tersedia: " + (err.message || "");
                                }
                            },

                            initFaceMesh() {
                                if (typeof FaceMesh === 'undefined') {
                                    setTimeout(() => this.initFaceMesh(), 150);
                                    return;
                                }

                                if (!this.faceMesh) {
                                    this.faceMesh = new FaceMesh({
                                        locateFile: (file) => `https://cdn.jsdelivr.net/npm/@mediapipe/face_mesh/${file}`
                                    });

                                    this.faceMesh.setOptions({
                                        maxNumFaces: 1,
                                        refineLandmarks: true,
                                        minDetectionConfidence: 0.5,
                                        minTrackingConfidence: 0.5
                                    });

                                    this.faceMesh.onResults((results) => this.onFaceResults(results));
                                }

                                this.processFrames();
                            },

                            processFrames() {
                                if (!this.cameraStarted || this.livenessDone) return;

                                const video = this.$refs.videoElement;
                                if (video && video.readyState >= 2 && this.faceMesh) {
                                    this.faceMesh.send({ image: video })
                                        .catch(e => console.warn(e))
                                        .finally(() => {
                                            if (this.cameraStarted && !this.livenessDone) {
                                                this.animFrameId = requestAnimationFrame(() => this.processFrames());
                                            }
                                        });
                                } else {
                                    this.animFrameId = requestAnimationFrame(() => this.processFrames());
                                }
                            },

                            onFaceResults(results) {
                                if (!results.multiFaceLandmarks || results.multiFaceLandmarks.length === 0) {
                                    this.faceDetected = false;
                                    return;
                                }

                                this.faceDetected = true;
                                const landmarks = results.multiFaceLandmarks[0];

                                // 1. Calculate EAR for Blink Detection
                                const dist = (p1, p2) => Math.hypot(p1.x - p2.x, p1.y - p2.y);
                                const calcEAR = (p1, p2, p3, p4, p5, p6) => {
                                    const v1 = dist(landmarks[p2], landmarks[p6]);
                                    const v2 = dist(landmarks[p3], landmarks[p5]);
                                    const h = dist(landmarks[p1], landmarks[p4]);
                                    return h > 0 ? (v1 + v2) / (2.0 * h) : 0;
                                };

                                // Left & right eye landmarks
                                const leftEAR = calcEAR(33, 160, 158, 133, 153, 144);
                                const rightEAR = calcEAR(362, 385, 387, 263, 373, 380);
                                const avgEAR = (leftEAR + rightEAR) / 2.0;

                                // Challenge 1: Blink
                                if (!this.blinkPassed) {
                                    if (avgEAR < 0.19) {
                                        this.eyeClosedCount++;
                                    } else if (avgEAR >= 0.23 && this.eyeClosedCount >= 1) {
                                        this.blinkPassed = true;
                                    }
                                }

                                // Challenge 2: Head Turn (Active after blink passes)
                                if (this.blinkPassed && !this.turnPassed) {
                                    const nose = landmarks[1];
                                    const leftCheek = landmarks[234];
                                    const rightCheek = landmarks[454];

                                    const dLeft = Math.abs(nose.x - leftCheek.x);
                                    const dRight = Math.abs(rightCheek.x - nose.x);
                                    const turnRatio = dLeft / (dRight || 0.001);

                                    // Significant rotation towards left or right
                                    if (turnRatio > 1.80 || turnRatio < 0.55) {
                                        this.turnPassed = true;
                                    }
                                }

                                // Complete & Auto-Capture
                                if (this.blinkPassed && this.turnPassed && !this.livenessDone) {
                                    this.livenessDone = true;
                                    this.captureSnapshot();
                                }
                            },

                            captureSnapshot() {
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

                                // Update Livewire attributes
                                @this.set('photo', compressedBase64);
                                @this.set('liveness_verified', true);
                                @this.set('liveness_method', 'BLINK_AND_TURN');
                            },

                            stopCamera() {
                                if (this.animFrameId) {
                                    cancelAnimationFrame(this.animFrameId);
                                    this.animFrameId = null;
                                }
                                if (this.videoStream) {
                                    this.videoStream.getTracks().forEach(track => track.stop());
                                    this.videoStream = null;
                                }
                                this.cameraStarted = false;
                            },

                            retakeLiveness() {
                                this.stopCamera();
                                this.capturedPhotoUrl = null;
                                this.livenessDone = false;
                                this.blinkPassed = false;
                                this.turnPassed = false;
                                this.eyeClosedCount = 0;
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
