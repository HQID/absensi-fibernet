# Panduan Penggunaan Sistem Absensi PKL Fibernet

Dokumen ini berisi panduan langkah demi langkah tentang cara menggunakan aplikasi Absensi PKL Fibernet. Panduan dibagi menjadi dua bagian: **Bagi Administrator** dan **Bagi Peserta PKL (Siswa)**.

---

## 👨‍💻 BAGIAN 1: PANDUAN UNTUK PESERTA PKL

### 1. Registrasi Akun
1. Buka tautan website absensi.
2. Klik tombol **Daftar / Register**.
3. Isi data diri dengan lengkap:
   - **Nama Lengkap**
   - **Email** & **Password** (Gunakan email yang aktif untuk keperluan login).
   - **NIM / NISN**
   - **Asal Sekolah / Kampus** dan **Jurusan**
   - **Tanggal Mulai** & **Tanggal Selesai PKL**
4. Setelah mendaftar, Anda akan otomatis masuk ke **Dashboard Siswa**.

### 2. Cara Melakukan Presensi (Absen)
Siswa diwajibkan melakukan absen 2 kali sehari: **Check In** (Saat tiba di kantor) dan **Check Out** (Saat pulang).

1. Pastikan Anda berada **di kawasan kantor** (dalam radius yang telah ditentukan).
2. Login ke dalam aplikasi, lalu pada *sidebar* sebelah kiri, klik menu **Attendance**.
3. Sistem akan meminta izin untuk mengakses **Lokasi (GPS)** dan **Kamera**. Klik **Izinkan / Allow** pada *browser* Anda.
4. Tunggu hingga sistem mendeteksi lokasi dan akurasi GPS Anda.
5. Posisikan wajah Anda pada kamera untuk mengambil foto *selfie*.
6. Klik tombol **Ambil Foto & Check In** (atau *Check Out* jika Anda pulang).
7. Jika jarak Anda terlalu jauh dari kantor, akan muncul peringatan error. Anda harus mendekat ke kantor untuk bisa absen.

### 3. Instalasi Aplikasi di HP (PWA)
Anda dapat menginstal website ini ke HP Anda agar lebih mudah diakses layaknya aplikasi biasa.
1. Buka website absensi menggunakan *browser* **Google Chrome** (Android) atau **Safari** (iOS).
2. Akan muncul *popup* atau *banner* di bawah layar bertuliskan **"Add to Home Screen"** atau **"Install App"**. 
3. Klik tombol tersebut dan tunggu proses instalasi selesai.
4. Aplikasi Absensi PKL Fibernet kini sudah muncul di menu/layar utama HP Anda.

---

## 👑 BAGIAN 2: PANDUAN UNTUK ADMINISTRATOR

### 1. Login sebagai Admin
Gunakan *email* dan *password* khusus administrator yang telah dibuat (atau menggunakan akun admin bawaan yang di-*seed* di database) untuk masuk ke sistem. Anda akan diarahkan ke **Dashboard Admin**.

### 2. Menu Dashboard
- Di halaman ini, Anda dapat melihat **Timeline** atau aktivitas absen terbaru pada hari ini.
- Anda dapat melihat siapa saja yang baru melakukan *Check In* atau *Check Out* beserta statusnya (Hadir/Terlambat).

### 3. Mengelola Peserta PKL
Jika ada peserta PKL yang masa magangnya sudah selesai atau ada kesalahan input masa magang, Admin bisa mengubahnya.
1. Klik menu **Peserta PKL** di *sidebar* kiri.
2. Cari nama peserta pada tabel.
3. Klik tombol **Edit** berwarna biru di sebelah kanan nama siswa.
4. Pada jendela *modal* yang muncul, Anda bisa mengubah:
   - **Tanggal Berakhir PKL**
   - **Status Akun** (Ubah menjadi *Nonaktif* jika siswa sudah lulus magang).
5. Klik **Simpan**. Siswa yang nonaktif tidak akan dimasukkan ke dalam daftar laporan absen harian lagi.

### 4. Melihat Laporan & Validasi Presensi (Reports)
1. Klik menu **Reports** di *sidebar* kiri.
2. **Filter Data:** Anda dapat mencari data absen menggunakan filter di bagian atas tabel:
   - Tanggal Mulai & Tanggal Selesai
   - Berdasarkan Nama Siswa
   - Berdasarkan Status (Hadir, Terlambat, Absen)
3. **Melihat Foto Selfie:** Klik tombol **"Lihat Foto"** (ikon gambar) pada kolom *Aksi* untuk memastikan keabsahan kehadiran siswa.
4. **Ubah Status / Validasi:** Jika sistem mencatat siswa "Absen" namun ternyata ada kesalahan teknis, atau Anda ingin mengubah statusnya:
   - Klik tombol **Setujui / Ubah Status** (ikon centang hijau) pada kolom *Aksi*.
   - Pilih status baru (Hadir / Terlambat / Absen).
   - Klik **Simpan Status**.

### 5. Mengunduh Rekap Absensi (Export ke Excel)
Untuk keperluan laporan sekolah/kampus, Admin dapat mengunduh seluruh data dalam format Excel.
1. Masuk ke menu **Reports**.
2. *(Opsional)* Tentukan **Filter** tanggal jika Anda hanya ingin mengunduh laporan bulan tertentu. Jika tidak difilter, semua data akan terunduh.
3. Klik tombol **Export XLSX** di sebelah kanan atas halaman laporan.
4. File `.xlsx` akan terunduh secara otomatis. File ini sudah memiliki judul kolom berwarna *cyan* dan rapi untuk langsung dicetak.

---

## ❓ Kendala Umum
1. **"Gagal mendeteksi lokasi":** Pastikan fitur GPS (Location) di HP siswa menyala dan *browser* telah diberikan izin (*permission*) untuk mengakses lokasi.
2. **"Jarak terlalu jauh":** Siswa berada di luar batas radius absen yang ditentukan (misalnya > 100 meter dari kantor).
3. **Kamera tidak muncul:** Pastikan *browser* sudah diberi izin untuk mengakses kamera, dan tidak ada aplikasi lain yang sedang menggunakan kamera di HP siswa.
