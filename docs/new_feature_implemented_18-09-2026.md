# Changelog & Implementasi Fitur Baru (18-09-2026)

Dokumen ini mencatat 6 penambahan fitur baru pada sistem Presensi Geofencing PKL PT Fiber Networks Indonesia Branch Palu, yang diimplementasikan pada 18 September 2026.

## 1. Perubahan Jam Terlambat (Late Threshold)

Batas waktu terlambat diubah dari pukul `09:00` menjadi `08:15`.

- **Status:** Diimplementasikan
- **File Terdampak:** `app/Services/AttendanceService.php`

## 2. Pilihan Lokasi Luar Kantor & Alasan Terlambat

Siswa sekarang dapat memilih opsi "Dalam Kantor" atau "Luar Kantor (Dinas)". Jika memilih luar kantor, sistem akan mem-_bypass_ batasan Geofence, namun mewajibkan pengisian "Alasan/Keterangan". Status presensi akan masuk dalam kategori _Pending Approval_ (Menunggu Persetujuan Admin). Alasan juga wajib diisi jika siswa melakukan presensi (baik dalam maupun luar kantor) setelah pukul 08:15.

- **Status:** Diimplementasikan
- **File Terdampak:**
    - `database/migrations/*_add_location_type_to_attendances_table.php`
    - `app/Services/AttendanceService.php`
    - `resources/views/livewire/student-attendance.blade.php`
    - `resources/views/reports/show.blade.php`

## 3. Tampilan Waktu & Status Real-time (Dashboard Siswa)

Dashboard siswa kini menampilkan Jam Digital secara _real-time_ dan Status Presensi Hari Ini (HADIR / TERLAMBAT / ALPA / BELUM PRESENSI) dalam kotak visual berukuran besar.

- **Status:** Diimplementasikan
- **File Terdampak:**
    - `app/Http/Controllers/DashboardController.php`
    - `resources/views/dashboard/student.blade.php`

## 4. Total Waktu Kerja (Admin Report)

Pada halaman Detail Presensi Admin, kini terdapat informasi "Total Waktu Kerja" yang menghitung selisih jam dan menit antara waktu _Check-In_ dan _Check-Out_.

- **Status:** Diimplementasikan
- **File Terdampak:** `resources/views/reports/show.blade.php`

## 5. Koordinat Peta Interaktif (Admin Report)

Latitude dan Longitude pada halaman detail presensi Admin kini ditampilkan sebagai tautan biru (_hyperlink_). Jika diklik, tautan tersebut akan otomatis membuka _Google Maps_ di jendela baru dengan pin penanda di koordinat tersebut.

- **Status:** Diimplementasikan
- **File Terdampak:** `resources/views/reports/show.blade.php`

## 6. Penonaktifan Akun & Perpanjangan Masa PKL

Jika `end_date` masa PKL siswa telah lewat dari tanggal hari ini, atau Admin mengubah status akun menjadi "Nonaktif", siswa akan otomatis ter-logout paksa (ditolak oleh sistem) dengan pesan _error_. Admin dapat memperpanjang masa PKL (`end_date`) atau mengubah status aktif/nonaktif akun dari menu "Peserta PKL".

- **Status:** Diimplementasikan
- **File Terdampak:**
    - `app/Http/Middleware/CheckStudentActive.php`
    - `bootstrap/app.php`
    - `routes/web.php`
    - `resources/views/pages/students/index.blade.php`
