# Sistem Presensi PKL Geofencing — PT Fiber Networks Indonesia Branch Palu

Aplikasi web presensi Peserta PKL berbasis **Next.js 16 (App Router)**, **React 19**, **Tailwind CSS v4**, **Supabase (PostgreSQL + Auth + Private Storage)**, dan **Vercel Serverless** dengan dukungan penuh **Geofencing Haversine**, kompresi foto di sisi browser (`<canvas>`), dan **PWA**.

## Fitur Utama

- **Peserta PKL (Siswa):**
  - Presensi **Check-In** & **Check-Out** dengan validasi radius koordinat kantor & batas akurasi GPS.
  - Mode **Dalam Kantor** & **Luar Kantor** (*Pending Approval* Admin) serta deteksi keterlambatan otomatis (`> 08:15` WITA) dan penutupan presensi (`17:00` WITA).
  - Kompresi foto selfie langsung di RAM browser (`max 1280px`, JPEG `0.72`) tanpa file sementara.
  - Dashboard real-time dengan jam digital WITA & instalasi PWA (*Add to Home Screen* + halaman `/offline`).
- **Administrator:**
  - Dashboard aktivitas harian & metrik kehadiran real-time.
  - Laporan presensi (`/reports`) dengan sinkronisasi Alpa hari kerja otomatis, filter rentang tanggal/sekolah/siswa/status, persetujuan presensi 3-pilihan, serta **Export Excel (`.xlsx`)**.
  - Manajemen Peserta PKL (`/students`): ubah periode/status aktif & **Hapus Permanen 2 Tahap** beserta foto di bucket privat.
  - Pengaturan Geofence (`/settings/geofence`) & pemantauan kuota + pembersihan sampah **Supabase Storage** (`/settings/storage`).

## Menjalankan Secara Lokal

1. Salin variabel environment:
   ```bash
   cp .env.example .env.local
   ```
2. Isi kredensial proyek Supabase pada `.env.local`.
3. Instal dependensi & jalankan server pengembangan:
   ```bash
   npm install
   npm run dev
   ```

## Deploy ke Vercel

1. Hubungkan repositori GitHub ke **Vercel** (**Root Directory:** `/` default).
2. Tambahkan **Environment Variables** pada *Vercel Project Settings*:
   - `NEXT_PUBLIC_APP_NAME` = `Presensi PKL Fibernet`
   - `NEXT_PUBLIC_APP_URL` = `https://<domain-vercel-anda>.vercel.app`
   - `APP_TIMEZONE` = `Asia/Makassar`
   - `NEXT_PUBLIC_SUPABASE_URL` = `https://<project-ref>.supabase.co`
   - `NEXT_PUBLIC_SUPABASE_PUBLISHABLE_KEY` = `sb_publishable_...`
   - `SUPABASE_SECRET_KEY` = `sb_secret_...`
   - `SUPABASE_STORAGE_BUCKET` = `photos`
   - `STORAGE_QUOTA_MB` = `1024`
3. Pada **Supabase Dashboard → Authentication → URL Configuration**:
   - **Site URL:** `https://<domain-vercel-anda>.vercel.app`
   - **Redirect URLs:** `https://<domain-vercel-anda>.vercel.app/api/auth/callback**`
