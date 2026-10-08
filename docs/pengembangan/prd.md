# Product Requirements Document (PRD)
## Pembaruan Sistem: Upgrade Menuju Skripsi (Rekayasa Sistem Lanjut)

* **Judul Penelitian:** Penerapan Algoritma Ray Casting dan Liveness Detection pada Sistem Presensi PKL PT Fiber Networks Indonesia Branch Palu
* **Studi Kasus:** PT Fiber Networks Indonesia Branch Palu
* **Fokus Peminatan:** Rekayasa Sistem Lanjut (RSL)
* **Status Dokumen:** Rencana Pengembangan & Spesifikasi Kebutuhan Sistem (PRD v2.0)
* **Tanggal Penyusunan:** Oktober 2026

---

## 1. Ringkasan Eksekutif & Latar Belakang Pembaruan

Proyek sistem presensi PKL eksisting berbasis Laravel 11, Livewire 3, dan PWA telah berhasil menyelesaikan masalah operasional dasar pada tahap Kerja Praktik (KP). Namun, untuk memenuhi standar kelayakan **Skripsi S1 Peminatan Rekayasa Sistem Lanjut**, sistem memerlukan peningkatan signifikan dari sekadar aplikasi *CRUD/Sistem Informasi* menjadi **Sistem Cerdas Terverifikasi Ganda (*Dual-Factor Anti-Fraud Verification System*)**.

### Dua Isu Kritis yang Diselesaikan:
1. **Kelemahan Geofencing Radius Lingkaran (Spasial):**  
   Formula *Haversine* dengan radius lingkaran kaku (100 m) berpotensi menimbulkan *false positive* (pengguna terabsen di luar batas fisik kantor/di jalan raya). Selain itu, sistem belum adaptif terhadap kemungkinan relokasi fisik gedung kantor PT FNI Branch Palu.
   * **Solusi:** Penerapan **Algoritma Ray Casting** (*Point-in-Polygon*) berbasis koordinat poligon tertutup yang dapat dikonfigurasi dinamis melalui peta interaktif.
2. **Celah Pemalsuan Foto Presensi (Biometrik):**  
   Pengunggahan foto *selfie* saat ini bersifat statis tanpa validasi kecerdasan visual, sehingga rentan manipulasi menggunakan foto cetak (*print attack*) atau layar smartphone lain (*screen replay attack*).
   * **Solusi:** Integrasi komponen **AI Liveness Detection** (*Computer Vision / Edge-AI*) menggunakan model *deep learning* ekstraksi *Face Landmark* dan perhitungan *Eye Aspect Ratio* (EAR) untuk memvalidasi subjek manusia hidup sebelum pengambilan foto.

---

## 2. Ruang Lingkup Pembaruan (Scope of Changes)

| Komponen | Kondisi Eksisting (Versi KP) | Target Pembaruan (Versi Skripsi) |
| :--- | :--- | :--- |
| **Geofencing** | Radius lingkaran ($100\text{ m}$) via Haversine Formula. | Poligon bebas (*arbitrary polygon*) via **Algoritma Ray Casting**. |
| **Konfigurasi Kantor** | Form input teks latitude & longitude tunggal. | Editor Peta Interaktif (**Leaflet.js**) untuk menggambar poligon batas kantor. |
| **Validasi Kamera** | Pengambilan/unggah foto selfie biasa (statis). | **AI Liveness Detection** real-time dengan verifikasi kedipan mata biologis (EAR). |
| **Snapshot Foto** | Tombol klik manual oleh pengguna. | **Auto-Capture Cerdas** (hanya terpicu setelah liveness tervalidasi). |
| **Arsitektur Pemrosesan AI** | Tidak ada modul AI. | **Client-Side Edge AI** (MediaPipe Face Mesh/TensorFlow.js) terintegrasi ke Livewire 3. |
| **Basis Data** | Kolom `latitude`, `longitude`, `radius_meters`. | Kolom `polygon_coordinates` (`JSON`/GeoJSON) pada tabel `office_locations`. |

---

## 3. Arsitektur & Alur Verifikasi Baru (Pipeline Verifikasi Ganda)

```text
[Siswa Membuka Halaman Presensi (PWA/Browser)]
                       │
                       ▼
┌────────────────────────────────────────────────────────┐
│ TAHAP 1: VERIFIKASI SPASIAL (RAY CASTING)               │
├────────────────────────────────────────────────────────┤
│ 1. Browser meminta izin dan mengambil koordinat GPS.   │
│ 2. Backend mengambil data poligon kantor aktif (JSON). │
│ 3. Ray Casting Engine mengeksekusi Point-in-Polygon:   │
│    - Jika di LUAR poligon  ──> ❌ TOLAK PRESENSI        │
│    - Jika di DALAM poligon ──>  LANJUT KE TAHAP 2     │
└────────────────────────────────────────────────────────┘
                       │
                       ▼
┌────────────────────────────────────────────────────────┐
│ TAHAP 2: VERIFIKASI AI BIOMETRIK (LIVENESS DETECTION)  │
├────────────────────────────────────────────────────────┤
│ 1. Kamera aktif, memuat model Face Landmark (CNN).    │
│ 2. Sistem memantau stream video real-time (468 titik). │
│ 3. Kalkulasi matematis Eye Aspect Ratio (EAR):         │
│    - Wajah diam / layar / foto cetak ──> Status: Idle  │
│    - Kedipan mata valid terdeteksi   ──> Status: Live  │
│ 4. Auto-Capture frame kamera saat status = Live.       │
└────────────────────────────────────────────────────────┘
                       │
                       ▼
┌────────────────────────────────────────────────────────┐
│ TAHAP 3: FINALISASI & PENYIMPANAN DATA                 │
├────────────────────────────────────────────────────────┤
│ 1. Foto snapshot & koordinat dikirim ke Laravel.       │
│ 2. AttendanceService memvalidasi jam server (cutoff).  │
│ 3. Simpan record presensi ke tabel attendances.        │
│ 4. Tampilkan feedback sukses ke siswa.                 │
└────────────────────────────────────────────────────────┘
```

---

## 4. Spesifikasi Kebutuhan Fungsional & Teknis

### 4.1 Modul Geofencing Poligon Dinamis (Algoritma Ray Casting)

* **Lokasi Implementasi:** `app/Services/GeofenceService.php`
* **Logika Matematis:**
  Menarik sinar horizontal dari titik uji $(x_0, y_0)$ ke arah timur ($\infty$). Untuk setiap sisi poligon yang dibentuk oleh simpul $(x_i, y_i)$ dan $(x_j, y_j)$:
  $$\text{Intersect} = ((y_i > y_0) \neq (y_j > y_0)) \land \left(x_0 < \frac{(x_j - x_i)(y_0 - y_i)}{y_j - y_i} + x_i\right)$$
  Jika total perpotongan ganjil, titik berada di dalam poligon.
* **Penanganan Relokasi Kantor:**
  Jika kantor berpindah, administrator tidak perlu mengubah kode program. Admin cukup mengakses menu pengaturan dan menggambar poligon baru di atas peta.

### 4.2 Modul Editor Peta Interaktif Admin

* **Lokasi Implementasi:** `resources/views/pages/settings/geofence.blade.php`
* **Teknologi:** **Leaflet.js** + **Leaflet-Geoman** (atau Leaflet Draw) berbasis OpenStreetMap tiles (bebas biaya API).
* **Fitur:**
  * Menampilkan lokasi kantor saat ini dengan layer poligon berwarna semi-transparan.
  * Fitur *Draw Polygon*: Menggambar titik-titik batas properti gedung baru.
  * Fitur *Edit/Delete Polygon*: Menyesuaikan titik sudut poligon jika terjadi renovasi/perubahan batas area.
  * Tombol *Save Geofence*: Mengonversi bentuk poligon ke format JSON array `[{lat: ..., lng: ...}, ...]` dan menyimpannya melalui Livewire.

### 4.3 Modul AI Liveness Detection (Face Anti-Spoofing)

* **Lokasi Implementasi:** `resources/views/livewire/student-attendance.blade.php` (Integrasi Alpine.js & JavaScript Module)
* **Teknologi AI:** **MediaPipe Face Mesh** / **TensorFlow.js** (Client-side Edge AI).
* **Mekanisme Active Liveness:**
  * Memanfaatkan koordinat 6 titik penting pada mata kiri dan mata kanan dari 468 titik *facial landmarks*.
  * Menghitung nilai **Eye Aspect Ratio (EAR)**:
    $$EAR = \frac{||p_2 - p_6|| + ||p_3 - p_5||}{2 ||p_1 - p_4||}$$
  * Ambang Batas (*Threshold Calibration*):
    * Kondisi mata terbuka: $EAR \ge 0.25$
    * Kondisi mata tertutup/berkedip: $EAR < 0.20$
    * Durasi kedipan: Valid jika tertutup selama 2–3 frame berurutan lalu kembali terbuka.
* **Fitur Anti-Spoofing & Auto-Capture:**
  * Menolak foto statis (tidak ada variasi EAR).
  * Menolak foto cetak di depan kamera.
  * Memicu *auto-capture* kamera secara otomatis hanya ketika kedipan valid terdeteksi, mencegah penekanan tombol manual yang curang.
* **Kompresi & Optimasi Penyimpanan Otomatis (Client-Side Compression):**
  * Frame video dari elemen `<video>` di-*render* ke elemen `<canvas>` dengan resolusi optimal ($640 \times 480$ atau $720 \times 720$ piksel) alih-alih resolusi asli kamera ponsel ($4000 \times 3000$ piksel).
  * Konversi ke format JPEG/WebP dengan *quality factor* 0.70 ($70\%$) menggunakan `canvas.toBlob('image/jpeg', 0.7)`.
  * **Efisiensi Ukuran File:** Mengurangi ukuran foto mentah dari **$\sim 4 - 8\text{ MB}$** menjadi hanya **$\sim 100 - 250\text{ KB}$** (penghematan kapasitas penyimpanan hingga **$96\%$**) tanpa menurunkan visibilitas wajah untuk verifikasi admin.

---

## 5. Rencana Perubahan Skema Basis Data (Database Schema)

### 5.1 Tabel `office_locations`
Membuat migrasi untuk menambahkan kolom poligon dinamis:
```php
Schema::table('office_locations', function (Blueprint $table) {
    // Menyimpan koordinat poligon dalam format JSON
    $table->json('polygon_coordinates')->nullable()->after('name');
    
    // Titik pusat poligon untuk inisialisasi pusat peta
    $table->decimal('center_latitude', 10, 8)->nullable()->after('polygon_coordinates');
    $table->decimal('center_longitude', 11, 8)->nullable()->after('center_latitude');
    
    // Status versi/aktif
    $table->boolean('active')->default(true)->change();
});
```

Contoh payload `polygon_coordinates`:
```json
[
  {"lat": -0.900812, "lng": 119.877710},
  {"lat": -0.900815, "lng": 119.878120},
  {"lat": -0.901140, "lng": 119.878115},
  {"lat": -0.901135, "lng": 119.877705}
]
```

### 5.2 Tabel `attendances`
Menambahkan metadata hasil verifikasi cerdas:
```php
Schema::table('attendances', function (Blueprint $table) {
    $table->boolean('is_inside_polygon')->default(false)->after('check_in_distance');
    $table->boolean('is_liveness_verified')->default(false)->after('is_inside_polygon');
    $table->decimal('liveness_score', 5, 2)->nullable()->after('is_liveness_verified');
});
```

---

## 6. Rencana Pengujian Ilmiah & Metrik Evaluasi (Untuk Bab IV Skripsi)

Untuk memenuhi bobot skripsi akademik, pengujian tidak hanya sekadar fungsional *black-box*, melainkan mencakup dua eksperimen ilmiah:

### A. Pengujian Algoritma Ray Casting
1. **Pengujian Posisi Titik:**
   * Titik jelas di dalam area kantor (10 sampel pengujian).
   * Titik jelas di luar area kantor (10 sampel pengujian).
   * Titik tepat pada garis batas/simpul poligon (*boundary edge case* - 5 sampel pengujian).
2. **Pengujian Performa Komputasi:**
   * Mengukur *execution time* (latensi dalam satuan milidetik / ms) untuk variasi jumlah titik sudut poligon ($N = 4, 8, 16$).

### B. Pengujian Model AI Liveness Detection
1. **Skenario Uji Serangan Manipulasi:**
   * **Wajah Asli (Normal):** Pengujian terhadap 20–30 sesi presensi wajah manusia hidup.
   * **Print Attack:** 20 kali percobaan menggunakan foto wajah cetak berkualitas tinggi pada kertas HVS dan kertas foto.
   * **Screen Replay Attack:** 20 kali percobaan menampilkan foto/video wajah dari layar smartphone lain di depan kamera.
2. **Metrik Akademis yang Dihitung:**
   * **Akurasi Deteksi (%):** Rasio klasifikasi benar terhadap total pengujian.
   * **False Acceptance Rate (FAR):** Rasio serangan palsu (foto/layar) yang tidak sengaja diloloskan sistem:
     $$FAR = \frac{\text{Jumlah Serangan Palsu Diterima}}{\text{Total Percobaan Serangan}} \times 100\%$$
   * **False Rejection Rate (FRR):** Rasio wajah asli yang tidak sengaja ditolak sistem:
     $$FRR = \frac{\text{Jumlah Wajah Asli Ditolak}}{\text{Total Percobaan Wajah Asli}} \times 100\%$$
3. **Eksperimen Kalibrasi Threshold:**
   * Mencari nilai ambang batas $EAR$ optimal dengan menguji variasi nilai: $0.18$, $0.20$, dan $0.22$.

---

## 7. Tahapan & Rencana Pengerjaan (Action Plan)

1. **Sprint 1: Modul Geofencing Dinamis & Ray Casting**
   * Pembuatan migrasi penambahan kolom `polygon_coordinates`.
   * Implementasi method `isInsidePolygon()` pada `GeofenceService.php`.
   * Integrasi Leaflet.js pada halaman pengaturan `geofence.blade.php`.
2. **Sprint 2: Modul AI Liveness Detection & Auto-Capture**
   * Pemasangan library MediaPipe Face Mesh di frontend.
   * Pembuatan pipeline deteksi kedipan (kalkulasi EAR) di Alpine.js.
   * Integrasi auto-capture dengan Livewire upload pada `student-attendance.blade.php`.
3. **Sprint 3: Pengujian Eksperimental & Pengumpulan Data Skripsi**
   * Pelaksanaan uji coba skenario Ray Casting & Latensi.
   * Pelaksanaan uji coba serangan *Print Attack* dan *Replay Attack* untuk menghitung nilai FAR & FRR.
   * Dokumentasi hasil pengujian ke dalam draf Bab IV Skripsi.
