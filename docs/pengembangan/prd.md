# Product Requirements Document (PRD) - PENGEMBANGAN SKRIPSI
## Rancang Bangun Sistem Presensi Peserta PKL dengan Polygon Geofencing dan Liveness Detection

**Judul Penelitian:** Penerapan Algoritma Ray Casting dan Liveness Detection pada Sistem Presensi PKL PT Fiber Networks Indonesia Branch Palu
**Studi Kasus:** PT Fiber Networks Indonesia Branch Palu
**Fokus Penelitian (Rekayasa Sistem Lanjut):** Verifikasi lokasi spasial berbasis *Polygon Geofencing (Ray Casting)* dan verifikasi kehadiran berbasis *Active Liveness Detection (EAR)*.
**Status:** Rancangan spesifikasi sistem untuk implementasi pengembangan skripsi.

> **Catatan Dokumen:** Dokumen ini merupakan turunan dan spesifikasi khusus untuk **fitur pengembangan Skripsi**. Spesifikasi dasar operasional sistem (Autentikasi, CRUD Siswa, Export Excel, dsb.) tetap merujuk pada PRD KP sebelumnya (`docs/PRD_Sistem_Presensi_PKL_Geofencing_PT_FNI_Branch_Palu.md`).

---

## 1. Problem Statement (Fokus Pengembangan)

### 1.1 Verifikasi Lokasi (Spasial)
Geofencing eksisting berbasis lingkaran (radius *Haversine*) tidak selalu mengikuti batas fisik area gedung yang sebenarnya (yang umumnya berbentuk poligon tak beraturan). Hal ini berisiko menimbulkan *false positive* (pengguna terabsen di jalan/luar gedung) serta kaku terhadap potensi relokasi batas kantor.

### 1.2 Bukti Presensi (Biometrik)
Foto presensi saat ini bersifat statis (file kamera mentah). Hal ini tidak menjamin bahwa proses pengambilan foto dilakukan oleh manusia hidup secara *real-time*, sehingga rentan terhadap *Spoofing Attack* (menggunakan foto cetak atau layar HP lain).

---

## 2. Research Focus & Scope (Ruang Lingkup Pengembangan)

Penelitian dibatasi pada dua mekanisme verifikasi cerdas:

1. **Polygon Geofencing (Algoritma Ray Casting):**
   * Menggantikan radius lingkaran kaku menjadi poligon dinamis.
   * Modul *Admin Interactive Map* (Leaflet.js) untuk menggambar batas area poligon baru jika ada pemindahan/renovasi batas kantor.
   * *Baseline* untuk komparasi: Circular Geofencing eksisting.

2. **Liveness Detection (Active - Client Side AI):**
   * Pemanfaatan *Pre-trained Edge AI* (MediaPipe Face Mesh / TensorFlow.js) pada sisi *client/browser*.
   * Menggunakan kombinasi multi-challenge: *Blink Challenge* (kalkulasi *Eye Aspect Ratio / EAR*) dan *Head-Turn Challenge* (estimasi orientasi wajah / Yaw-Pitch).
   * **Auto-Capture & Image Compression:** Mengotomatisasi pengambilan gambar (*snapshot*) hanya saat kondisi *liveness* terpenuhi dan mengompresi gambar via elemen HTML5 `<canvas>` (efisiensi storage server).

---

## 3. Verification Pipeline (Alur Verifikasi Baru)

```text
[Akses Halaman Presensi]
       ↓
(1) Acquire Location (GPS & Accuracy)
       ↓
(2) GPS Accuracy Validation (Bandingkan dengan Threshold)
       ↓
(3) Polygon Geofencing (Ray Casting Algorithm)
       ├── Di Luar Poligon → ❌ REJECT
       └── Di Dalam Poligon → Lanjut ke Kamera
              ↓
(4) Liveness Detection (Face Landmark: EAR & Head Pose)
       ├── Terindikasi Spoof/Statis → ❌ REJECT
       └── Kedipan & Menoleh Valid Terdeteksi → Lanjut Auto-Capture
              ↓
(5) Auto-Capture & Image Compression (Canvas ke JPEG ~150KB)
       ↓
(6) Kirim Payload ke Server (Koordinat + Foto)
       ↓
(7) Validasi Akhir Server Timestamp & Simpan Data Presensi
```

---

## 4. Perubahan Skema Basis Data (Database Schema)

Fokus hanya pada struktur yang berubah/ditambahkan untuk mendukung skripsi:

### 4.1 Tabel `office_locations`
Ditambahkan kolom untuk poligon dan titik pusat peta:
```text
- polygon_coordinates (JSON) -> Menyimpan array koordinat batas gedung
- center_latitude (Decimal)
- center_longitude (Decimal)
```

### 4.2 Tabel `attendances`
Ditambahkan metadata audit verifikasi sistem cerdas:
```text
- is_inside_polygon (Boolean)
- location_verification_status (String)
- liveness_verified (Boolean)
- liveness_method (String) -> misal: "BLINK_AND_TURN"
- liveness_verified_at (Timestamp)
```

---

## 5. Kebutuhan Fungsional Pengembangan (Skripsi)

| ID | Modul | Deskripsi Pembaruan |
|---|---|---|
| FR-DEV-01 | Polygon Config | Admin dapat menggambar dan menyimpan batas polygon kantor melalui UI Peta Interaktif (Leaflet.js). |
| FR-DEV-02 | Ray Casting | Sistem memvalidasi letak presensi menggunakan algoritma Point-in-Polygon (Ray Casting) di Backend (PHP). |
| FR-DEV-03 | Liveness UI | Sistem memuat model *Face Landmark* di browser peserta dan memberikan instruksi interaktif ganda (misal: "Berkediplah", dilanjutkan "Tolehkan kepala ke Kanan/Kiri"). |
| FR-DEV-04 | Auto-Capture | Sistem secara otomatis mengambil snapshot gambar HANYA ketika rangkaian *challenge* (kedipan dan menoleh) berhasil dipenuhi. |
| FR-DEV-05 | Compression | Sistem mengonversi frame video menjadi format `.jpeg` resolusi web terkompresi sebelum diunggah. |

---

## 6. Testing Strategy & Evaluasi Akademis

### 6.1 Experimental Testing A: Polygon Geofencing
Membandingkan *Baseline* (Circular/Haversine) dengan *Proposed* (Polygon/Ray Casting).
* **Skenario:**
  1. Titik jelas di dalam area.
  2. Titik jelas di luar area.
  3. Titik tepat pada garis batas (boundary).
  4. Titik di luar bentuk fisik bangunan tetapi masih terjangkau oleh radius lingkaran lama (False Positive pada baseline).
* **Metrik Akademis:** Akurasi klasifikasi lokasi, *False Acceptance Rate* (luar dianggap dalam), *Processing Time/Latency* (milidetik).

### 6.2 Experimental Testing B: Liveness Detection
* **Skenario (Presentation Attacks):**
  1. *Live Human* (Manusia asli, kondisi pencahayaan terang dan redup).
  2. *Print Attack* (Foto wajah yang dicetak di kertas/kertas foto).
  3. *Replay Attack* (Foto/Video wajah yang ditampilkan di layar perangkat lain).
* **Metrik Akademis:**
  * **Akurasi (%)**
  * **FAR (False Acceptance Rate):** Tingkat kelolosan serangan foto/layar.
  * **FRR (False Rejection Rate):** Tingkat penolakan keliru terhadap manusia asli.
  * **Response Time:** Waktu rata-rata inferensi hingga kedipan terdeteksi.

---

## 7. Research Variables

* **Independent Variables:**
  * Metode Geofencing (Circular vs Polygon).
  * Jenis Input Liveness (Live human vs Print attack vs Replay attack).
* **Dependent Variables:**
  * Hasil Validasi, Akurasi, FAR, FRR, Waktu Komputasi.
* **Controlled Variables:**
  * Area pengujian, konfigurasi batas akurasi GPS, parameter jarak kamera ke wajah, *threshold EAR*.

---

## 8. Research Threat Model (Batasan Celah)
Penelitian difokuskan pada penanganan dua celah:
1. **Spatial Boundary Mismatch:** Pengguna memanipulasi presensi dari area yang tidak valid namun masih tercakup radius lingkaran palsu.
2. **Presentation Attack (Spoofing):** Pengguna memakai media statis (kertas/layar HP).

*Catatan: Sistem tidak mencakup perlindungan dari GPS Spoofing tingkat kernel (seperti modul Fake GPS berbasis root) maupun serangan Deepfake video tingkat lanjut.*
