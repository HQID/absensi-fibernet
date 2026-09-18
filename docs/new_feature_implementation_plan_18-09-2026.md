# Rancangan Penambahan 6 Fitur Presensi

Berikut adalah rancangan teknis (_Implementation Plan_) untuk mengadopsi 6 fitur baru yang Anda minta.

## Proposed Changes

---

### 1. Ubah Batas Waktu Terlambat (Point 5)

Waktu keterlambatan akan diubah menjadi **08:16**. Presensi pada 08:00 hingga 08:15 akan dihitung Hadir (Present), sedangkan 08:16 ke atas akan dihitung Terlambat (Late).

#### [MODIFY] `app/Services/AttendanceService.php`

- Mengubah fungsi `determineStatus()` dengan menetapkan batas `setHour(8)->setMinute(15)->setSecond(0)`.

---

### 2. Pilihan Lokasi Luar Kantor & Alasan Terlambat (Point 2)

Memungkinkan siswa memilih "Dalam Kantor" (Otomatis Geofence) atau "Luar Kantor" (Bebas lokasi, tapi butuh _Approval_ Admin & Keterangan). Selain itu, alasan wajib diisi jika terlambat.

#### [NEW] Database Migration

- Membuat file _migration_ baru untuk menambahkan 3 kolom pada tabel `attendances`:
    - `location_type` (enum/string: `inside`, `outside` - default: `inside`)
    - `reason` (text, nullable - untuk menampung alasan luar kantor atau alasan terlambat)
    - `is_approved` (boolean, nullable - status persetujuan admin jika luar kantor)

#### [MODIFY] `resources/views/livewire/student-attendance.blade.php`

- Menambahkan _Radio Button_ / _Dropdown_ pilihan lokasi (Dalam Kantor / Luar Kantor).
- Menambahkan _Textarea_ untuk pengisian "Alasan/Caption". _Textarea_ ini akan **wajib diisi (required)** jika:
    1. Siswa memilih "Luar Kantor".
    2. Waktu saat ini menunjukkan lewat dari jam 08:15 (Terlambat).

#### [MODIFY] `app/Services/AttendanceService.php`

- Memodifikasi validasi Geofence pada fungsi `checkIn()` dan `checkOut()`.
- Jika mode "Luar Kantor", validasi jarak geofence dilewati (_bypassed_), namun `is_approved` disetel ke `false` (Menunggu Persetujuan Admin).

#### [MODIFY] `resources/views/reports/show.blade.php` (Admin Detail)

- Menampilkan alasan/keterangan yang diisi siswa.
- Jika statusnya Menunggu Persetujuan (Luar Kantor), berikan tombol "Setujui" (_Approve_) atau "Tolak" (_Reject_) bagi Admin.

---

### 3. Tampilan Waktu & Status di Dashboard Siswa (Point 1)

Menambahkan kartu informasi utama di bagian atas Dashboard Siswa yang menampilkan waktu secara _real-time_ (jam digital) dan status presensi hari ini.

#### [MODIFY] `resources/views/dashboard/student.blade.php`

- Menambahkan kartu (_Card_) berukuran besar di bagian atas bertuliskan Jam saat ini.
- Menambahkan indikator status hari ini yang mencolok (misal: "Hari ini: HADIR / TERLAMBAT / ALPA").

---

### 4. Total Waktu Kerja & Koordinat Interaktif (Point 4 & 6)

Memperbaiki tampilan Detail Presensi di sisi Admin agar lebih informatif.

#### [MODIFY] `resources/views/reports/show.blade.php`

- **Koordinat Interaktif:** Mengubah teks Latitude/Longitude menjadi tautan (link) `<a href="https://maps.google.com/?q=lat,lng">` yang bisa diklik untuk membuka Google Maps.
- **Total Waktu Kerja:** Menambahkan baris informasi baru yang menghitung selisih waktu (`check_out_at` dikurangi `check_in_at`). Jika Check-Out belum dilakukan, akan tertulis "Belum Check-Out".

---

### 5. Penonaktifan Akun PKL Selesai & Perpanjangan Waktu (Point 3)

Akun siswa yang tanggal `end_date`-nya sudah lewat akan otomatis dinonaktifkan (tidak bisa login/absen). Admin bisa memperpanjang waktunya.

#### [MODIFY] `routes/web.php` & Controller/Volt Component

- Mengubah rute halaman `/students` yang saat ini hanya berupa tampilan Blade biasa menjadi komponen **Livewire (Volt)** agar memiliki fitur reaktif (tombol Edit).

#### [MODIFY] `resources/views/students/index.blade.php` -> Diubah ke Livewire/Volt

- Menambahkan form Modal/Dialog bagi Admin untuk mengedit tanggal `end_date` (Perpanjang PKL).
- Menambahkan tombol _Toggle_ Status (Aktif / Nonaktif).

#### [NEW] Middleware `CheckStudentActive`

- Membuat Middleware baru untuk mengecek: Jika `end_date` siswa sudah terlewati (kadaluwarsa) atau statusnya Nonaktif, paksa siswa _Logout_ atau beri halaman pesan "Masa PKL Anda Telah Berakhir".

---

## User Review Required

> **Alur Persetujuan Luar Kantor:** Jika siswa memilih Luar Kantor, absensinya akan tercatat namun **tidak akan dihitung sah** (Status: _Pending_) sebelum Admin menekan tombol _Approve_ di halaman detail presensi. Apakah alur ini sudah sesuai dengan ekspektasi Anda?

> **Penonaktifan Akun:** Saat masa PKL habis, siswa tidak akan bisa masuk (_login_) sama sekali ke dalam Dasbor. Mereka akan diarahkan kembali ke halaman Login dengan pesan error.
