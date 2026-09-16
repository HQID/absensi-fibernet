<?php

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Services\AttendanceService;
use App\Models\Attendance;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

new class extends Component
{
    use WithFileUploads;

    public $latitude;
    public $longitude;
    public $accuracy;
    public $photo;

    public $attendanceToday;
    public $errorMessage = '';
    public $successMessage = '';

    public function mount()
    {
        $this->loadAttendance();
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
            'photo' => 'required|image|max:5120', // 5MB max
        ]);

        $this->errorMessage = '';
        $this->successMessage = '';
        
        try {
            $student = Auth::user()->student;
            $attendanceService->checkIn($student, $this->latitude, $this->longitude, $this->accuracy, $this->photo);
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
    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        @if(Auth::user()->isStudent() && Auth::user()->student)
            
            @if($errorMessage)
                <div class="mb-4 rounded-md bg-red-50 p-4 border border-red-200">
                    <p class="text-sm text-red-700">{{ $errorMessage }}</p>
                </div>
            @endif

            @if($successMessage)
                <div class="mb-4 rounded-md bg-green-50 p-4 border border-green-200">
                    <p class="text-sm text-green-700">{{ $successMessage }}</p>
                </div>
            @endif

            <div class="text-center" x-data="attendanceHandler()">
                
                @if(!$attendanceToday || !$attendanceToday->check_in_at)
                    <!-- Form Check In -->
                    <h2 class="text-lg font-medium text-gray-900 mb-4">Check In Presensi</h2>
                    
                    <div x-show="!locationReady" class="mb-4">
                        <button type="button" @click="getLocation()" class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-blue-700 transition-colors">
                            1. Dapatkan Lokasi Saya
                        </button>
                    </div>

                    <div x-show="locationReady" style="display: none;" class="mb-4 text-left">
                        <div class="bg-blue-50 border border-blue-200 text-blue-800 text-sm p-3 rounded-md mb-4 flex items-center">
                            <svg class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            Lokasi terverifikasi (Akurasi: <span x-text="Math.round(accuracy)" class="mx-1 font-semibold"></span>m)
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">2. Foto Bukti Kehadiran</label>
                            <input type="file" wire:model="photo" accept="image/*" capture="user" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" />
                            @error('photo') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div wire:loading wire:target="photo" class="text-sm text-blue-600 mb-4">
                            Sedang memproses foto...
                        </div>

                        <div x-show="$wire.photo" style="display: none;">
                            <button type="button" wire:click="checkIn" wire:loading.attr="disabled" class="w-full rounded-lg bg-green-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-green-700 disabled:opacity-50 transition-colors flex justify-center">
                                <span wire:loading.remove wire:target="checkIn">3. Submit Check In</span>
                                <span wire:loading wire:target="checkIn">Menyimpan...</span>
                            </button>
                        </div>
                    </div>
                @elseif(!$attendanceToday->check_out_at)
                    <!-- Form Check Out -->
                    <h2 class="text-lg font-medium text-gray-900 mb-4">Check Out Presensi</h2>
                    
                    <div class="bg-gray-50 border border-gray-200 p-4 rounded-md mb-6 text-left text-sm text-gray-700 space-y-2">
                        <div class="flex justify-between">
                            <span class="text-gray-500">Waktu Check-In</span>
                            <span class="font-semibold">{{ $attendanceToday->check_in_at->format('H:i') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Status</span>
                            <span class="font-semibold uppercase {{ $attendanceToday->status === 'present' ? 'text-green-600' : 'text-orange-600' }}">{{ $attendanceToday->status }}</span>
                        </div>
                    </div>

                    <div x-show="!locationReady" class="mb-4">
                        <button type="button" @click="getLocation()" class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-blue-700 transition-colors">
                            1. Dapatkan Lokasi Saya
                        </button>
                    </div>

                    <div x-show="locationReady" style="display: none;" class="mb-4">
                        <div class="bg-blue-50 border border-blue-200 text-blue-800 text-sm p-3 rounded-md mb-4 text-left flex items-center">
                            <svg class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            Lokasi terverifikasi (Akurasi: <span x-text="Math.round(accuracy)" class="mx-1 font-semibold"></span>m)
                        </div>

                        <button type="button" wire:click="checkOut" wire:loading.attr="disabled" class="w-full rounded-lg bg-orange-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-orange-700 disabled:opacity-50 transition-colors flex justify-center">
                            <span wire:loading.remove wire:target="checkOut">2. Submit Check Out</span>
                            <span wire:loading wire:target="checkOut">Menyimpan...</span>
                        </button>
                    </div>
                @else
                    <!-- Selesai -->
                    <div class="py-8">
                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-green-100 mb-4">
                            <svg class="h-8 w-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                        </div>
                        <h2 class="text-xl font-medium text-gray-900">Presensi Selesai</h2>
                        <p class="text-sm text-gray-500 mt-2">Anda telah menyelesaikan presensi untuk hari ini.</p>
                        
                        <div class="mt-6 bg-gray-50 p-5 rounded-md text-left text-sm text-gray-700 border border-gray-200 inline-block w-full max-w-sm space-y-3">
                            <div class="flex justify-between items-center border-b border-gray-200 pb-2">
                                <span class="text-gray-500">Check In</span>
                                <span class="font-semibold">{{ $attendanceToday->check_in_at->format('H:i') }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-500">Check Out</span>
                                <span class="font-semibold">{{ $attendanceToday->check_out_at->format('H:i') }}</span>
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
            <div class="text-center py-6">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-blue-100 mb-4">
                    <svg class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900">Akses Terbatas</h3>
                <p class="text-sm text-gray-500 mt-1">Hanya peserta PKL yang dapat melakukan check-in dan check-out.</p>
            </div>
        @endif
    </div>
</div>

