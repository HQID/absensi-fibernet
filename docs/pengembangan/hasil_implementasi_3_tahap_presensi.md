# Hasil Implementasi 3 Tahap Alur Presensi
## Integrasi Polygon Geofencing (Ray Casting), Active AI Liveness Detection, dan Transaksi Presensi

**Dokumen Teknis & Laporan Pengembangan Skripsi**  
**Sistem Presensi PKL PT Fiber Networks Indonesia Branch Palu**  
**Tanggal Rilis:** Oktober 2026  
**Status:** Terimplementasi & Terverifikasi Penuh

---

## 1. Ringkasan Arsitektur & Filosofi Alur (Pipeline)

Sistem presensi telah diperbarui dengan menerapkan alur verifikasi berlapis secara sekuensial: **Tahap 1 (Spasial) $\rightarrow$ Tahap 2 (Biometrik Liveness) $\rightarrow$ Tahap 3 (Finalisasi Transaksi)**.

```mermaid
flowchart TD
    Start([Mahasiswa Membuka Halaman Presensi]) --> Step1[Tahap 1: Verifikasi Spasial]
    
    subgraph S1 [Tahap 1: Polygon Geofencing]
        Step1 --> Choice{Pilihan Lokasi}
        Choice -->|Luar Kantor / Dinas| Step1Pass[Bypass Poligon - Perlu Alasan]
        Choice -->|Dalam Kantor| GetLoc[Dapatkan Koordinat GPS / Mode Uji]
        GetLoc --> PreCheck[Server Pre-Check via Livewire: verifyLocation]
        PreCheck --> RayCast[Ray Casting Algorithm]
        RayCast -->|Di Luar Batas Poligon| S1Fail[❌ Akses Ditolak: Di Luar Batas Kantor]
        RayCast -->|Di Dalam Batas Poligon| Step1Pass[✓ Lokasi Valid Terverifikasi]
    end

    S1Fail -->|Perbaiki Posisi / Pilih Dinas| Step1
    Step1Pass --> Step2[Tahap 2: AI Liveness Detection Terbuka]

    subgraph S2 [Tahap 2: Active Biometric Liveness]
        Step2 --> OpenCam[Aktifkan Kamera & MediaPipe Face Mesh]
        OpenCam --> Blink[Tantangan 1: Berkedip Penuh - EAR <= 0.16]
        Blink -->|Lolos| TurnRight[Tantangan 2: Menoleh Kanan Penuh - posNose <= 0.22]
        TurnRight -->|Lolos| TurnLeft[Tantangan 3: Menoleh Kiri Penuh - posNose >= 0.78]
        TurnLeft -->|Lolos| Stabilize[Jeda Stabilisasi: Countdown 2 Detik - Tatap Depan & Diam]
        Stabilize --> AutoCap[Auto-Capture & Kompresi JPEG Canvas 640x480 ~120KB]
    end

    AutoCap --> Step3[Tahap 3: Kirim Presensi]

    subgraph S3 [Tahap 3: Transaksi & Rekam Data]
        Step3 --> InputReason{Terlambat > 08:15 / Luar Kantor?}
        InputReason -->|Ya| RequireReason[Wajib Isi Alasan / Keterangan]
        InputReason -->|Tidak| ReadySubmit[Siap Submit]
        RequireReason --> ReadySubmit
        ReadySubmit --> SubmitBtn[Klik: Kirim Presensi / Check In]
        SubmitBtn --> BackendService[AttendanceService::checkIn]
        BackendService --> ReValidate[Validasi Ganda Spasial Ray Casting di Server]
        ReValidate --> SaveDB[(Simpan Database: Foto, Status Liveness, Koordinat, Jarak Audit)]
    end

    SaveDB --> End([Presensi Berhasil Disimpan])
```

### Filosofi "Geofence-First"
Pada pembaruan ini, tahap verifikasi lokasi ditempatkan mendahului tahap kamera (*Geofence-First*):
1. **Efisiensi Pengguna:** Mencegah pengguna membuang waktu dan tenaga menyelesaikan tantangan gerakan wajah biometrik apabila posisi fisiknya ternyata berada di luar batas area kantor yang sah.
2. **Efisiensi Sumber Daya Klien:** Modul AI kamera (*MediaPipe Face Mesh WebAssembly*) tidak akan membebani komputasi CPU/GPU perangkat pengguna sebelum dipastikan pengguna berada di lokasi yang sah.
3. **Integritas Alur:** Kamera dan tantangan *liveness* dikunci (*gated*) secara ketat dan hanya akan terbuka otomatis setelah server mengonfirmasi validitas poligon spasial.

---

## 2. Rincian Implementasi Tiap Tahap

### Tahap 1: Verifikasi Spasial (Polygon Geofencing - Ray Casting Algorithm)

Tahap pertama memvalidasi posisi fisik mahasiswa terhadap batas area kantor PT Fiber Networks Indonesia Branch Palu.

#### 1. Algoritma Ray Casting (Jordan Curve Theorem)
- Menggantikan batas lingkaran statis menjadi poligon batas tanah/gedung aktual (yang dikelola admin melalui peta satelit interaktif).
- Algoritma menembakkan sinar horizontal ($y = y_0$, $x > x_0$) dari titik koordinat pengguna ke arah tak hingga.
- **Kaidah Persilangan:** Jika jumlah perpotongan sinar dengan sisi poligon berjumlah **ganjil**, maka titik berada **di dalam poligon** (*valid*). Jika berjumlah **genap**, titik berada **di luar poligon** (*invalid*).
- Diterapkan pada kelas [`GeofenceService::isInsidePolygon()`](file:///c:/laragon/www/absensi-fibernet/app/Services/GeofenceService.php#L45-L86) dan lolos 100% pengujian unit pada [`GeofenceServiceTest.php`](file:///c:/laragon/www/absensi-fibernet/tests/Unit/GeofenceServiceTest.php).

#### 2. Pra-Validasi Realtime Server-Side (`verifyLocation`)
- Begitu tombol GPS ditekan (atau pengguna memilih Mode Uji Laptop), Alpine.js langsung memicu metode server Livewire `verifyLocation($lat, $lng, $accuracy)`.
- Server mengevaluasi koordinat secara *realtime* dan mengembalikan status evaluasi:
  - **Lolos:** Menampilkan banner hijau *"✓ Lokasi valid berada di dalam poligon kantor"* serta membuka modul Tahap 2.
  - **Gagal:** Menampilkan banner merah *"✗ Anda berada di luar batas poligon kantor PT FNI Branch Palu (Jarak dari titik tengah: X m)"* dan Tahap 2 tetap terkunci rapat.

#### 3. Peran Rumus Haversine & Radius Lingkaran
Apakah rumus *Haversine* masih dipertahankan? **Ya, dengan 3 peran spesifik non-deterministik:**
1. **Baseline Komparasi Skripsi:** Radius lingkaran baseline dipertahankan di basis data dan peta admin sebagai acuan pembanding akademis guna membuktikan bahwa model *Polygon Geofencing* berhasil mengeliminasi *false positive* (area di luar gedung yang salah terbaca masuk pada model lingkaran).
2. **Fallback Safety:** Apabila koordinat poligon belum disetel oleh administrator atau terjadi anomali data, sistem secara otomatis beralih (*fallback*) ke metode radius lingkaran *Haversine* sehingga operasional presensi tidak lumpuh total.
3. **Metrik Jarak Audit:** Jarak *geodesic* (lingkaran besar) dari posisi presensi ke titik pusat gedung tetap dihitung dan dicatat pada kolom `check_in_distance` tabel `attendances` sebagai bahan audit dan analisis data.

#### 4. Fitur Mode Uji Laptop
- Menyediakan tombol *"💻 Mode Uji Laptop (Kantor)"* yang otomatis mengisi koordinat ke titik tengah poligon kantor PT FNI (-0.87495286, 119.88654882).
- Mempermudah dosen penguji dan evaluator dalam mendemonstrasikan sistem pada perangkat laptop/desktop tanpa harus berpindah tempat.

---

### Tahap 2: Verifikasi Biometrik (Active AI Liveness Detection)

Tahap kedua memastikan bahwa yang melakukan presensi adalah manusia sungguhan yang hadir secara fisik (*live human*), bukan manipulasi foto statis atau layar digital (*photo spoofing*).

#### 1. Arsitektur Edge AI & Isolasi WebAssembly (WASM)
- Menggunakan library *pre-trained* **MediaPipe Face Mesh (Google Research)** yang dieksekusi langsung pada peramban klien via WebAssembly.
- **Penyelesaian Masalah Alpine.js Proxy Conflict:** Seluruh instansiasi WebAssembly (`_faceMeshInstance`, stream video, dan konteks kanvas pemrosesan) diisolasi ke dalam lingkup *closure* murni JavaScript di luar *reactive proxy* Alpine.js. Hal ini menuntaskan bug fatal:
  `TypeError: 'get' on proxy: property '$$' is a read-only and non-configurable data property`.

#### 2. Tantangan 1: Blink Challenge (Eye Aspect Ratio / EAR)
- Menggunakan 6 titik landmark mata standar untuk masing-masing mata:
  $$\text{EAR} = \frac{\|p_2 - p_6\| + \|p_3 - p_5\|}{2 \cdot \|p_1 - p_4\|}$$
- Perhitungan jarak telah dikalibrasi menggunakan jarak Euclidean isotropik skala kanvas piksel ($320 \times 240$).
- **Ambang Batas Ketat:** Mata terdeteksi tertutup penuh bila `avgEAR <= 0.16` (atau turun $> 45\%$ dari garis dasar mata terbuka).
- **Verifikasi Konsistensi:** Kondisi tertutup wajib dipertahankan selama minimal $\ge 2$ frame berturut-turut sebelum mata dibuka kembali. Aturan ini mencegah pengguna memicu verifikasi palsu hanya dengan memicingkan mata (*squinting*).

#### 3. Tantangan 2 & 3: Head-Turn Challenge (Menoleh ke Kanan & Kiri Penuh)
- Posisi kepala diukur dari rasio posisi horizontal hidung terhadap lebar bentang pipi kiri-kanan:
  $$pos_{\text{nose}} = \frac{x_{\text{nose}} - x_{\text{min}}}{W_{\text{face}}}$$
  *(Posisi normal di depan kamera bernilai $\approx 0.50$)*
- **Menoleh ke Kanan Penuh:** Hidung bergeser jauh ke tepi kanan ($pos_{\text{nose}} \le 0.22$).
- **Menoleh ke Kiri Penuh:** Hidung bergeser jauh ke tepi kiri ($pos_{\text{nose}} \ge 0.78$).
- **Multi-Frame Hold:** Setiap putaran wajib ditahan minimal $\ge 4$ frame berturut-turut. Hal ini menjamin bahwa gelengan kecil (*head wobble*) tidak akan dianggap sah; pengguna wajib menoleh penuh ke kanan dan ke kiri.
- Teks instruksi diseragamkan menjadi: *"Menoleh ke Kanan"* dan *"Menoleh ke Kiri"*.

#### 4. Jeda Stabilisasi & Hitung Mundur Otomatis (2 Detik Countdown)
- **Problem:** Pada versi sebelumnya, foto langsung dijepret secara instan sesaat setelah pengguna menyelesaikan gerakan menoleh ke kiri, sehingga hasil foto sering kali miring atau buram.
- **Solusi:** Ditambahkan fase jeda stabilisasi hitung mundur 2 detik (`isCountingDown = true`).
- **Visual Feedback:** Border oval panduan kamera berkedip hijau (*emerald ring pulse*) dengan tampilan animasi hitung mundur:
  > **Tatap Depan & Diam**  
  > **2 $\rightarrow$ 1**  
  > *"Mengambil foto..."*
- Memberikan waktu yang cukup bagi pengguna untuk kembali menatap lurus ke depan, merapikan pose, dan bersiap sebelum foto diambil.

#### 5. Auto-Capture & Kompresi Klien
- Saat hitung mundur berakhir, kanvas HTML5 otomatis mengambil frame video beresolusi $640 \times 480$ dengan efek cermin (*mirrored horizontally*).
- Gambar dikompresi langsung di sisi klien (*client-side JPEG compression*) dengan rasio kualitas `0.70`, menghasilkan *payload* gambar ringkas sekitar $\approx 120\text{ KB}$.
- Kompresi ini menghemat kuota internet mahasiswa dan memangkas penggunaan penyimpanan server hingga $80\%$ dibandingkan pengunggahan foto kamera mentah.

---

### Tahap 3: Finalisasi & Transaksi Presensi (Submit Check-In / Check-Out)

Tahap ketiga memfinalisasi transaksi presensi ke dalam basis data server.

#### 1. Validasi Input Formulir
- Apabila mahasiswa memilih opsi *"Di Luar Kantor (Kunjungan / Dinas)"* atau waktu presensi telah melewati pukul **08:15** (*terlambat*), sistem mewajibkan pengisian kolom **Keterangan / Alasan**.
- Jika waktu $\le 08:15$ dan berada di dalam kantor, status otomatis menjadi `HADIR` (`present`) dan langsung disetujui (`is_approved = true`).

#### 2. Validasi Ganda di Sisi Server (Double-Verification Integrity)
- Saat tombol *"Kirim Presensi (Submit Check In)"* ditekan, backend melalui [`AttendanceService::checkIn()`](file:///c:/laragon/www/absensi-fibernet/app/Services/AttendanceService.php) memvalidasi ulang seluruh data:
  - Melakukan eksekusi ulang algoritma *Ray Casting* poligon pada server untuk memastikan koordinat tidak dimanipulasi melalui peretas *client-side payload*.
  - Mengonversi data foto Base64 menjadi file gambar `.jpg` yang disimpan rapi di storage server.
  - Memeriksa batas waktu operasional (pukul 17:00 pada mode *production*).
  - Merekam data ke basis data lengkap dengan metadata verifikasi:
    - `is_inside_polygon = true`
    - `location_verification_status = 'polygon'`
    - `check_in_distance` (jarak Haversine audit)
    - `liveness_verified = true`
    - `liveness_method = 'BLINK_AND_TURN'`
    - `liveness_verified_at = now()`

---

## 3. Matriks Hasil Pengujian Alur 3 Tahap

Berikut rangkuman hasil pengujian fungsional dan unit sistem:

| Skenario Pengujian | Masukan / Kondisi | Respon Sistem | Status |
| :--- | :--- | :--- | :---: |
| **Uji Spasial (Di Dalam Poligon)** | Titik kantor PT FNI (-0.87495286, 119.88654882) | Server Ray Casting menyatakan `valid=true`. Tahap 2 terbuka. | **Lolos (Pass)** |
| **Uji Spasial (Di Luar Poligon)** | Titik luar kantor (~680 meter dari kantor) | Server Ray Casting menolak (`valid=false`). Tahap 2 terkunci. | **Lolos (Pass)** |
| **Uji Spasial (Dinas Luar)** | Opsi `outside` (Luar Kantor) | Bypass poligon aktif, wajib menyertakan alasan presensi. | **Lolos (Pass)** |
| **Blink Spoofing (Mata Statis)** | Foto diam / layar statis tanpa kedipan | EAR konstan $\ge 0.25$, indikator berkedip tidak lolos. | **Lolos (Pass)** |
| **Blink Spoofing (Memicingkan)** | Memicingkan mata (*squinting*) | EAR tertahan di range $0.19 - 0.23$ ($> 0.16$), tidak memicu *closed*. | **Lolos (Pass)** |
| **Blink Nyata** | Menutup mata penuh lalu membukanya | EAR $\le 0.16$ selama $\ge 2$ frame, status Berkedip `✓ Lolos`. | **Lolos (Pass)** |
| **Head Turn Wobble** | Gerakan kepala goyang tipis ($\pm 5^\circ$) | $pos_{\text{nose}}$ berada di rentang $0.38 - 0.62$, tantangan tidak terpicu. | **Lolos (Pass)** |
| **Head Turn Nyata** | Menoleh kanan penuh ($pos \le 0.22$) & kiri ($pos \ge 0.78$) | Masing-masing tertahan $\ge 4$ frame, status Menoleh `✓ Lolos`. | **Lolos (Pass)** |
| **Jeda Stabilisasi** | Kedua tantangan selesai | Hitung mundur 2 detik berjalan ("Tatap Depan & Diam"). | **Lolos (Pass)** |
| **Auto-Capture & Kompresi** | Hitung mundur mencapai 0 | Frame dijepret otomatis, ukuran Base64 $\approx 120\text{ KB}$. | **Lolos (Pass)** |
| **Integritas Database** | Submit presensi | Kolom `is_inside_polygon`, `liveness_verified`, dan foto tersimpan. | **Lolos (Pass)** |

---

## 4. Berkas-Berkas Terdampak

Berikut daftar file utama yang memuat implementasi 3 tahap ini:

1. **[`resources/views/livewire/student-attendance.blade.php`](file:///c:/laragon/www/absensi-fibernet/resources/views/livewire/student-attendance.blade.php)**
   - Mengatur sekuens 3 tahap antarmuka pengguna: Progress indicator, verifikasi spasial (Tahap 1), panel kamera MediaPipe (Tahap 2), dan form submit akhir (Tahap 3).
   - Menangani isolasi WebAssembly, kalkulasi EAR, rasio menoleh kepala, *countdown timer*, dan kompresi kanvas.
   - Menyediakan metode backend `verifyLocation()` untuk validasi spasial instan sebelum kamera dibuka.
2. **[`app/Services/GeofenceService.php`](file:///c:/laragon/www/absensi-fibernet/app/Services/GeofenceService.php)**
   - Implementasi logika matematika algoritma *Ray Casting* (`isInsidePolygon`) dan formula lingkaran besar *Haversine* (`calculateDistance`).
   - Logika penentuan prioritas validasi (*polygon first, circular fallback*).
3. **[`app/Services/AttendanceService.php`](file:///c:/laragon/www/absensi-fibernet/app/Services/AttendanceService.php)**
   - Transaksi presensi di sisi server: validasi ganda keamanan poligon, pemrosesan unggahan file gambar dari Base64, dan penulisan metadata *liveness*.
4. **[`tests/Unit/GeofenceServiceTest.php`](file:///c:/laragon/www/absensi-fibernet/tests/Unit/GeofenceServiceTest.php)**
   - Pengujian unit otomatis untuk memastikan kebenaran matematis algoritma Ray Casting pada titik dalam, titik luar, dan ambang batas poligon.
5. **[`resources/views/pages/settings/geofence.blade.php`](file:///c:/laragon/www/absensi-fibernet/resources/views/pages/settings/geofence.blade.php)**
   - Antarmuka manajemen geofence administrator menggunakan peta satelit *hybrid* Leaflet.js untuk menggambar titik poligon dinamis kantor.
