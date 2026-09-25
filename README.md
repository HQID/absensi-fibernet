# Absensi PKL Fibernet

Aplikasi Absensi PKL (Praktik Kerja Lapangan) berbasis *web* yang dikembangkan menggunakan **Laravel 11**, **Livewire 3**, dan **Tailwind CSS 4 (Flux UI)**. Aplikasi ini dirancang khusus untuk memonitor kehadiran siswa/mahasiswa PKL di PT Fiber Networks Indonesia Branch Palu dengan dukungan fitur **Geofencing** (Pembatasan Lokasi) dan PWA (Progressive Web App).

## ✨ Fitur Utama

### 👑 Untuk Administrator
* **Dashboard Terpusat:** Melihat *timeline* presensi siswa secara *real-time* dan status kehadiran hari ini.
* **Manajemen Peserta PKL:** Mengelola data siswa PKL (mengubah masa periode aktif PKL dan menonaktifkan siswa yang sudah selesai).
* **Laporan Presensi (Reports):**
  * Menyaring ( *filter*) riwayat absensi berdasarkan rentang tanggal, nama siswa, dan status.
  * Melihat bukti foto *check-in* siswa.
  * Memvalidasi atau mengubah status kehadiran (Hadir/Terlambat/Absen).
  * **Export Excel:** Mengunduh rekapitulasi data presensi ke dalam format `.xlsx`.

### 👨‍🎓 Untuk Peserta PKL (Siswa)
* **Presensi Geofencing:** Melakukan *Check In* dan *Check Out* dengan validasi radius jarak (meter) dari titik koordinat kantor. 
* **Bukti Foto (Selfie):** Mewajibkan pengambilan foto *selfie* secara *real-time* saat melakukan absen.
* **Dashboard Siswa:** Melihat profil PKL, sisa masa periode, dan riwayat presensi mandiri.
* **Deteksi Lokasi (Dalam/Luar Kantor):** Sistem otomatis mendeteksi apakah siswa absen dari dalam batas area kantor atau berada di luar area kantor.

### 📱 Progressive Web App (PWA)
* Dapat diunduh/di- *install* langsung ke layar utama *smartphone* tanpa melalui Play Store/App Store.
* Memiliki halaman *Offline Fallback* jika koneksi internet terputus.

## 🚀 Teknologi yang Digunakan
* **Backend:** Laravel 11.x, PHP 8.2+
* **Frontend:** Livewire 3, Tailwind CSS v4, Flux UI, Alpine.js
* **Database:** MySQL
* **Export:** Laravel Excel (Maatwebsite)
* **PWA:** Vanilla Service Worker & Web Manifest

## 🛠️ Instalasi & Persiapan

1. **Kloning Repositori**
   ```bash
   git clone <repo-url> absensi-fibernet
   cd absensi-fibernet
   ```

2. **Install Dependensi PHP & Node.js**
   ```bash
   composer install
   npm install
   ```

3. **Konfigurasi Lingkungan (.env)**
   Salin *file* konfigurasi dan sesuaikan koneksi *database* Anda.
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   *Atur koneksi DB di dalam file `.env`.*

4. **Migrasi Database & Seeding**
   ```bash
   php artisan migrate:fresh --seed
   ```
   *(Catatan: Pastikan Anda telah membuat seeder untuk akun Admin default jika ada).*

5. **Symlink Storage (Untuk Foto Presensi)**
   ```bash
   php artisan storage:link
   ```

6. **Kompilasi Aset Frontend (Vite)**
   ```bash
   npm run build
   ```

7. **Jalankan Server Lokal**
   ```bash
   php artisan serve
   ```
   Akses aplikasi di `http://localhost:8000`.

## 📍 Pengaturan Geofencing (Koordinat Kantor)
Untuk mengubah titik kordinat kantor dan batas radius maksimal absen, buka *file* `app/Services/AttendanceService.php` dan sesuaikan variabel berikut:
```php
private $officeLat = -0.9008985; // Latitude Kantor
private $officeLng = 119.8778007; // Longitude Kantor
private $maxRadius = 100; // Radius toleransi dalam meter
```

## 📝 Lisensi
Dibuat untuk keperluan internal **PT Fiber Networks Indonesia Branch Palu**.
