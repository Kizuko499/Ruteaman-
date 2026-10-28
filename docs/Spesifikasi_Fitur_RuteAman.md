> **Pembaruan v4 — skrip demo terbaru:** implementasi yang berlaku dijelaskan dalam `DEMO_SETUP.md`. Dokumen ini tetap memuat rancangan fitur jangka panjang (misalnya integrasi BMKG otomatis), bukan klaim semua sudah tersedia. Dua role aktif: Siswa dan Admin Sekolah; notasi VER lama berarti kewenangan verifikasi milik Admin. Rute tersimpan, layer bersama, deteksi duplikat, dan feed GeoJSON adalah MUST demo. Tidak ada hasil audit/pilot yang dikarang.

# Spesifikasi Fitur RuteAman — Cara Kerja Backend dan Tata Letak

> Dokumen ini adalah **spesifikasi fitur lengkap**: untuk tiap fitur ada tujuan, peran, tata letak (wireframe), alur pengguna, cara kerja backend (endpoint, validasi, service, tabel, job), kasus tepi, dan kriteria penerimaan.
> Pakai bersama:
> - `RuteAman_Competition_Concept_v2.md` (sumber kebenaran produk)
> - `Prompt_Backend_RuteAman.md` (aturan implementasi backend)
> - `Prompt_UI_UX_RuteAman.md` (palet, komponen, aksesibilitas, slot gambar)
>
> Jika dokumen ini bertentangan dengan konsep produk, **konsep yang menang**, dan laporkan perbedaannya.

---

## 0. LEGENDA DAN ATURAN UMUM

**Prioritas:** `MUST` (wajib untuk demo), `SHOULD` (jika waktu cukup), `COULD` (setelah semua MUST selesai dan teruji).

**Peran:**

| Kode | Peran | Login? |
|---|---|---|
| `PUB` | Pembaca publik (siswa, orang tua, siapa pun) | Tidak |
| `STU` | Siswa pelapor (`student`) | Ya |
| `VER` | Verifikator (guru/staf, `verifier`) | Ya |
| `ADM` | Admin Sekolah (`school_admin`) | Ya |

**Aturan yang berlaku di semua fitur:**

1. Tidak ada kata "aman/safe" di UI, API, atau pesan. Gunakan "Beban hazard" dan "Belum ada laporan aktif".
2. Selalu tampilkan **umur data** (mis. "Dikonfirmasi 12 menit lalu").
3. Angka beban lebih tinggi = lebih berat. Tidak pernah dibalik.
4. Tanpa pelacakan lokasi pengguna. Lokasi hanya pada titik hazard. Titik asal rute tidak disimpan dan tidak di-log.
5. Tanpa data pelapor pada endpoint publik.
6. Nilai ambang, TTL, bobot, radius, dan jeda berasal dari `config/ruteaman.php` dan tabel `hazard_types`, bukan hardcode. Semua angka awal adalah **heuristik** yang dikalibrasi di pilot.
7. Semua perubahan status hazard, verifikasi, eskalasi, dan perubahan role menulis `audit_logs`.
8. Semua aksi tulis idempoten (aman ditekan dua kali).

**Notasi wireframe:** `[ ]` tombol, `( )` pilihan, `▼` dropdown/sheet, `●` penanda, `░` area peta/foto.

---

## 1. PETA FITUR DAN MATRIKS HAK AKSES

| ID | Fitur | Prioritas | PUB | STU | VER | ADM |
|---|---|---|---|---|---|---|
| F1 | Peta publik + hazard aktif | MUST | ✔ | ✔ | ✔ | ✔ |
| F2 | Perbandingan rute | MUST | ✔ | ✔ | ✔ | ✔ |
| F3 | Lapor hazard | MUST | — | ✔ | ✔ | ✔ |
| F4 | Konfirmasi "Masih ada / Sudah surut" | MUST | — | ✔ | ✔ | ✔ |
| F5 | Verifikasi (antrean verifikator) | MUST | — | — | ✔ | ✔* |
| F6 | Beban Hazard + rincian | MUST | ✔ | ✔ | ✔ | ✔ |
| F7 | Eskalasi terstruktur | MUST | — | — | lihat | ✔ |
| F8 | Dashboard sekolah | MUST | — | — | ✔ (baca) | ✔ |
| F9 | Autentikasi + 3 role + kelola pengguna | MUST | — | ✔ | ✔ | ✔ |
| F10 | Cuaca BMKG sebagai konteks | MUST | ✔ | ✔ | ✔ | ✔ |
| S1 | Layer hazard bersama antar sekolah | SHOULD | ✔ | ✔ | ✔ | ✔ |
| S2 | Feed GeoJSON terbuka | SHOULD | ✔ | — | — | — |
| S3 | Tampilan baca-saja untuk orang tua | SHOULD | ✔ | — | — | — |
| S4 | Notifikasi dalam aplikasi (status laporanmu) | SHOULD | — | ✔ | ✔ | ✔ |
| S5 | Grafik tren sederhana | SHOULD | — | — | ✔ | ✔ |
| C1 | Pra-isi kategori dari foto (AI) | COULD | — | ✔ | — | — |
| C2 | Web Push / PWA | COULD | ✔ | ✔ | ✔ | ✔ |
| C3 | Blur otomatis wajah/plat | COULD | — | — | — | — |

\* `ADM` boleh memverifikasi hanya jika diberi izin verifikator oleh sekolah (konfigurasi). Default: verifikasi dilakukan `VER`.

---

## 2. GAMBARAN ALUR DATA

```text
                         ┌──────────────┐
  STU: lapor / konfirmasi│   Laravel    │   VER: verifikasi
 ───────────────────────►│  (Service    │◄───────────────────────
                         │   layer)     │
  PUB: peta, rute        └──────┬───────┘   ADM: eskalasi, metrik
 ───────────────────────►       │
                    ┌───────────┼───────────────────────┐
                    ▼           ▼                       ▼
            PostgreSQL+PostGIS  Routing engine        BMKG
            (hazard, vote,      (OSRM / ORS /         (cuaca → cache
             verifikasi,         rute tersimpan)       weather_snapshots)
             eskalasi, audit)
                    │
        event: status/confidence berubah
                    ▼
        InvalidateRouteCacheJob (queue)
                    ▼
        Permintaan rute berikutnya: routing → overlay hazard
        (ST_DWithin 25 m) → HazardLoadService → Beban + rincian
```

Prinsip: **rute dihitung routing engine dan tidak disimpan**. Backend hanya menempelkan hazard ke geometri rute lalu menghitung beban.

---

## 3. FITUR MUST

### F1. Peta Publik + Hazard Aktif

**Tujuan:** siapa pun dapat melihat hazard aktif di area sekolah tanpa login, lengkap dengan status dan umur data.

**Tata letak, mobile (≤ 640 px):**

```text
┌────────────────────────────────┐
│ [≡] RuteAman     [Cuaca: Hujan]│  ← header teal; chip cuaca + jam update
│ ┌────────────────────────────┐ │
│ │ 🔍 Dari mana → ke sekolah  │ │  ← kotak pencarian rute (F2)
│ └────────────────────────────┘ │
│ (Semua)(Genangan)(Berlubang)…  │  ← chip filter kategori, scroll horizontal
│ ░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░ │
│ ░░░░░░░░░ ● ░░░░░░░░ ●●(3) ░░░ │  ← peta Leaflet, pin = ikon kategori
│ ░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░ │
│ ░░░░░░░░░░░░░░░░░░░░░ [ + ]░░░ │  ← FAB "Lapor" (oranye, 56 px)
│ ┌────────────────────────────┐ │
│ │ ▬▬ (handle bottom sheet)   │ │
│ │ 4 hazard aktif di area     │ │
│ │ ● Genangan, depan pasar    │ │
│ │   Terverifikasi · 4 mnt lalu│ │
│ │ ● Jalan berlubang, Jl. Mawar│ │
│ └────────────────────────────┘ │
│ [Peta] [Lapor] [Laporanku][Akun]│  ← bottom nav
└────────────────────────────────┘
```

**Tata letak, desktop (≥ 1024 px):** panel kiri 380 px (pencarian rute, filter, daftar hazard), peta di kanan penuh tinggi. Legenda dan atribusi OSM di pojok kanan bawah peta. Kartu hazard di panel kiri tersinkron dengan pin: klik kartu memusatkan peta, klik pin menyorot kartu.

**Bottom sheet** punya tiga posisi: tertutup (hanya handle), setengah (default), penuh (daftar). Daftar hazard punya **alternatif teks yang setara dengan peta** (aksesibilitas).

**Alur pengguna:** buka peta → lihat pin dan daftar → ketuk pin → kartu hazard (foto, jenis, status, umur, tombol konfirmasi jika login) → atau mulai cari rute (F2).

**Backend:**

| Hal | Rincian |
|---|---|
| Endpoint | `GET /api/hazards?bbox=w,s,e,n&category=&limit=` (publik, rate limit) |
| Query | Hazard dengan status `community_confirmed`, `pending_verification`, `verified`; `ST_Intersects(location, ST_MakeEnvelope(...))`; index GiST; `LIMIT` wajib (default 200) |
| Respons | `HazardPublicResource`: `public_slug`, `type`, `severity`, `status`, `confidence_label`, `lat`, `lng`, `last_confirmed_at`, `age_text`, `photo_thumb_url` (jika ada), `confirmations_count`. **Tanpa** `reporter_id`, nama, atau email |
| Cache | Respons di-cache 30 detik per bbox yang dibulatkan, dengan `ETag`. Invalidasi pada event perubahan status hazard |
| Hazard `unconfirmed` | Tidak muncul di peta publik umum. Muncul di daftar **Laporanku** milik pelapor dan di kartu konfirmasi untuk pengguna login di catchment yang sama (supaya bisa dikonfirmasi) |
| Hazard `expired`/`resolved` | Tidak muncul sebagai aktif. `resolved` terakhir ≤ 24 jam boleh muncul sebagai "Sudah surut" abu-abu (config) |
| Atribusi | Footer peta menampilkan atribusi OpenStreetMap |
| Data lama | Jika tidak ada aktivitas laporan di area selama N hari, header daftar menampilkan chip `stale_data` |

**State UI:** loading (skeleton kartu `--cream`), kosong ("Belum ada laporan aktif di area ini. Ini bukan jaminan kondisi aman."), error (pesan + "Coba lagi"), offline (tampilkan data terakhir tersimpan di browser dengan label waktu), gagal tile peta (peta abu + daftar teks tetap berfungsi).

**Kriteria penerimaan:**
- [ ] Terbuka tanpa login dan tanpa data pribadi di respons.
- [ ] Setiap hazard menampilkan jenis, status, dan umur data.
- [ ] Daftar teks setara dengan peta dan bisa dinavigasi keyboard.
- [ ] Peta tetap berguna saat BMKG atau routing gagal.

---

### F2. Perbandingan Rute

**Tujuan:** membandingkan rute ke sekolah berdasarkan **Beban Hazard**, tanpa menyatakan satu rute "aman".

**Tata letak, mobile:**

```text
┌────────────────────────────────┐
│ [←] Bandingkan rute            │
│ Dari : (● Lokasi saya sekali) ▼│  ← atau pilih titik di peta / cari tempat
│ Ke   : MAN Contoh (sekolah)    │
│ Moda : Jalan kaki              │  ← satu profil moda (hasil survei)
│ ░░░░░░░ peta: A solid, B putus ░│
│ ┌ geser → ───────────────────┐ │
│ │ RUTE A (solid, teal)       │ │
│ │ 1,8 km · [Beban 67 BERAT]  │ │
│ │ ⚠ Genangan, depan pasar    │ │
│ │   terverifikasi, 4 mnt lalu│ │
│ │ ⚠ Jalan berlubang, Jl.Mawar│ │
│ │   terverifikasi, 3 hr lalu │ │
│ │ [Kenapa angkanya segini? ▼]│ │
│ ├────────────────────────────┤ │
│ │ RUTE B (putus-putus, oranye)│ │
│ │ 2,1 km (+0,3 km)           │ │
│ │ [? Belum ada laporan aktif]│ │
│ │ Ini bukan jaminan kondisi  │ │
│ │ aman. Tetap perhatikan     │ │
│ │ kondisi di lapangan.       │ │
│ └────────────────────────────┘ │
│ Sumber rute: langsung · 18.40  │
└────────────────────────────────┘
```

Desktop: dua kartu berdampingan di panel kiri, peta di kanan, rincian dibuka di panel yang sama. Catatan layout: pada contoh angka di atas, kartu **tidak** diberi mahkota atau label "terbaik". Selisih jarak dan beban ditampilkan netral.

**Pemilihan titik:** tiga cara, tanpa menyimpan posisi:
1. "Gunakan lokasi saya": satu kali, hanya di perangkat untuk mengisi formulir. Tidak dikirim ke server sebagai pelacakan dan tidak disimpan.
2. Ketuk titik di peta.
3. Cari tempat lewat `GeocodingProviderInterface` (mis. Nominatim atau penyedia lain **[CEK ketentuan penggunaan dan batas laju penyedia yang dipilih]**). Tujuan default: lokasi sekolah.

**Backend: `POST /api/routes/compare`**

```json
{ "origin": {"lat": -6.2001, "lng": 106.8002},
  "destination": {"lat": -6.2100, "lng": 106.8100},
  "profile": "foot" }
```

Alur di `RouteComparisonService`:

1. Validasi koordinat dan pastikan keduanya dekat dengan area layanan (catchment diperluas buffer).
2. Cek cache (kunci: koordinat dibulatkan + profil, TTL 60 detik). Koordinat asal **tidak ditulis ke log atau DB**.
3. `RoutingProviderInterface::routes()` → 1 sampai 3 rute alternatif (geometri, jarak, durasi). Jika gagal: rute terakhir di cache, atau `StoredRouteProvider` (label "rute tersimpan").
4. Untuk tiap rute, ambil hazard aktif: `ST_DWithin(location::geography, route::geography, 25)` dengan `LIMIT`.
5. Ambil konteks cuaca dari `weather_snapshots` (F10) dan laporan genangan baru.
6. `HazardLoadService::compute(route, hazards, weather)` (F6) → angka, label, rincian.
7. Hitung selisih jarak terhadap rute terpendek, siapkan disclaimer, `source`, `fetched_at`.
8. Kembalikan JSON; **urutan rute mengikuti urutan routing engine**, bukan diurutkan oleh "keamanan".

```json
{
  "routes": [
    { "id": "A", "distance_m": 1800, "duration_s": null, "extra_distance_m": 0,
      "load": {"value": 67, "label_code": "heavy", "label": "Berat"},
      "hazards": [ {"public_slug": "…", "type": "flood", "status": "verified", "age_text": "4 menit lalu"} ],
      "geometry": {"type": "LineString", "coordinates": []} },
    { "id": "B", "distance_m": 2100, "extra_distance_m": 300,
      "load": {"value": null, "label_code": "no_report",
               "label": "Belum ada laporan aktif. Ini bukan jaminan kondisi aman."},
      "hazards": [] }
  ],
  "source": "live", "fetched_at": "2026-10-06T18:40:00+07:00",
  "disclaimer": "Informasi berasal dari laporan komunitas. Tetap perhatikan kondisi di lapangan."
}
```

`duration_s` diisi dari routing engine; contoh di atas `null` karena belum ada hasil ukur. **Jangan mengarang durasi.**

**Kasus tepi:**
- Hanya satu rute ditemukan: tampilkan satu kartu tanpa memaksa perbandingan.
- Dua rute beban sama atau selisih kecil: tampilkan keduanya apa adanya, tanpa rekomendasi.
- Asal di luar area layanan: pesan ramah "Titik awal di luar area yang dipantau sekolah", rute tetap dihitung dengan catatan.
- Routing gagal total dan tidak ada rute tersimpan: banner "Perbandingan rute sedang tidak tersedia", peta dan daftar hazard tetap jalan.
- Server demo publik OSRM: hanya untuk pengembangan.

**Kriteria penerimaan:**
- [ ] Rute berasal dari routing engine nyata dengan alternatif.
- [ ] Hazard yang tampil di tiap rute berasal dari overlay PostGIS 25 m.
- [ ] `no_report` tidak pernah tampil sebagai "aman".
- [ ] Koordinat asal tidak ditemukan di DB maupun log setelah permintaan.

---

### F3. Lapor Hazard (≤ 4 langkah)

**Tujuan:** melaporkan hazard dengan sesedikit mungkin ketukan.

**Tata letak (mobile, satu layar per langkah di dalam bottom sheet penuh):**

```text
LANGKAH 1/4  Lokasi                LANGKAH 2/4  Jenis
┌──────────────────────────┐       ┌──────────────────────────┐
│ [×]                1/4   │       │ [←]                2/4   │
│ ░░░░░░░░ peta ░░░░░░░░░░ │       │ Apa yang kamu temukan?   │
│ ░░░░░░░░░ 📍 ░░░░░░░░░░░ │       │ ┌────┐ ┌────┐ ┌────┐     │
│ ░░ geser pin ke lokasi ░ │       │ │ 🌊 │ │ 🕳 │ │ 🌑 │     │
│ [Gunakan lokasi saya]    │       │ │Gena│ │Lubg│ │Gelp│     │
│ Di luar area sekolah? →  │       │ └────┘ └────┘ └────┘     │
│ pesan ramah di sini      │       │ ┌────┐ ┌────┐ ┌────┐     │
│ [ Lanjut ]               │       │ │Pohn│ │Hamb│ │Trot│ ... │
└──────────────────────────┘       └──────────────────────────┘

LANGKAH 3/4  Foto (opsional)       LANGKAH 4/4  Kirim
┌──────────────────────────┐       ┌──────────────────────────┐
│ [←]                3/4   │       │ [←]                4/4   │
│ ┌──────────────────────┐ │       │ Ringkasan:               │
│ │  [ Ambil foto ]      │ │       │ Genangan · depan pasar   │
│ │  [ Pilih dari galeri]│ │       │ ░ foto kecil ░           │
│ └──────────────────────┘ │       │ Catatan (opsional) ____  │
│ Hindari memotret wajah   │       │ Waktu: [sekarang ▼]      │
│ dan plat nomor.          │       │ [ Kirim laporan ]        │
│ [ Lewati ]    [ Lanjut ] │       │ (oranye, teks gelap)     │
└──────────────────────────┘       └──────────────────────────┘
```

Setelah kirim: layar hasil berupa **kartu hazard** dengan status "Menunggu konfirmasi", teks apa yang terjadi selanjutnya, dan tombol "Lihat di peta" serta "Laporan lain". Bukan halaman JSON.

**Backend: `POST /hazards`** (`StoreHazardReportRequest`, header `Idempotency-Key: <uuid>` dari klien)

| Tahap | Aturan |
|---|---|
| Validasi | `lat/lng` valid; `type_code` ada di `hazard_types`; `description` ≤ 280 karakter, disanitasi; `observed_at` tidak di masa depan dan tidak lebih tua dari batas (config); foto: MIME `image/jpeg|png|webp`, ukuran maks (config) |
| Persetujuan | Siswa < 18 tahun wajib punya `parental_consent_at` |
| Geofence | `ST_Covers(schools.catchment, point)`; di luar → kode `OUTSIDE_CATCHMENT` dengan pesan ramah |
| Akurasi GPS | Jika akurasi GPS dari klien > 50 m, UI meminta pengguna menggeser pin (validasi di klien, bukan di server) |
| Rate limit | Per pengguna per jam (config) → `RATE_LIMITED` dengan waktu tunggu |
| Idempotensi | `Idempotency-Key` disimpan sementara (cache). Kunci yang sama mengembalikan hasil yang sama, tidak membuat laporan baru |
| Duplikat | Cari hazard aktif sejenis dalam radius (default 30 m) dan TTL. Ada → tambahkan `hazard_report` ke hazard itu. Tidak ada → buat `hazards` baru status `unconfirmed` |
| Foto | Simpan di disk privat. Baca timestamp EXIF untuk validasi, lalu **buang GPS dan metadata** dari salinan tersimpan. Buat thumbnail. Pemrosesan berat boleh lewat queue; kartu menampilkan "Foto sedang diproses" sampai selesai |
| Transaksi | Dalam satu transaksi: buat report, gabung ke hazard, hitung ulang confidence, tulis `audit_logs`; setelah commit, jadwalkan `InvalidateRouteCacheJob` |
| Respons | `201` dengan kartu hazard; bila digabung ke yang sudah ada: kode `MERGED_INTO_EXISTING` dan teks "Laporan serupa sudah ada. Laporanmu ditambahkan sebagai bukti." |

**Reputasi ringan:** laporan dari pengguna dengan rasio penolakan tinggi membuat confidence naik lebih lambat (pengali `< 1` pada kontribusi laporan). Dihitung dari `verifications` dan `hazard_reports`, tanpa tabel baru, dan **tidak ditampilkan** ke pengguna.

**Kriteria penerimaan:**
- [ ] ≤ 4 langkah; deskripsi dan waktu opsional.
- [ ] Tekan "Kirim" dua kali tidak membuat laporan ganda.
- [ ] Titik di luar catchment ditolak dengan pesan ramah.
- [ ] EXIF GPS tidak ada pada foto yang tersimpan.
- [ ] Status "Menunggu konfirmasi" terlihat langsung setelah kirim.

---

### F4. Konfirmasi "Masih ada / Sudah surut"

**Tujuan:** komunitas menguatkan atau menutup hazard dengan satu ketukan, tanpa menunggu guru.

**Tata letak (kartu hazard):**

```text
┌──────────────────────────────────┐
│ ░░░░░░░░ foto 4:3 ░░░░░░░░░░░░░░ │
│ 🌊 Genangan · depan pasar        │
│ [Dikonfirmasi komunitas]         │
│ Terakhir dikonfirmasi 12 mnt lalu│
│ 3 laporan · 2 konfirmasi         │
│ ┌──────────────┐ ┌─────────────┐ │
│ │  Masih ada   │ │ Sudah surut │ │  ← dua tombol berdampingan, ≥ 48 px
│ └──────────────┘ └─────────────┘ │
│ [Kenapa statusnya begini? ▼]     │
└──────────────────────────────────┘
```

Setelah memilih: tombol yang dipilih menyala (ikon + teks, bukan warna saja), ada teks "Suaramu tercatat" dan tombol "Ubah suara". Pengguna belum login: tombol memicu ajakan masuk, kartu tetap terbaca.

**Backend: `POST /hazards/{hazard}/confirmations`** body `{ "vote": "still_there" | "gone" }`

1. Otorisasi: pengguna login, akun aktif, termasuk catchment tempat hazard berada (`HazardPolicy@vote`).
2. Rate limit per pengguna per jam; batas jumlah konfirmasi per hari.
3. Transaksi dengan `SELECT … FOR UPDATE` pada baris hazard (hindari balapan dua konfirmasi bersamaan).
4. **Upsert** `hazard_confirmations` (unique `hazard_id + user_id`). Mengubah suara memperbarui baris yang sama.
5. Hitung **konfirmasi independen**: pengguna berbeda dari pelapor awal, dan jeda waktu antar-suara ≥ `independence_min_gap_minutes`.
6. `HazardStateService::evaluate()` memutuskan transisi:
   - `unconfirmed` → `community_confirmed` bila independen `still_there` ≥ N (awal 3) **dan** titik valid di catchment.
   - `community_confirmed` → `pending_verification` bila severity tinggi (≥ 3) atau terjadi **sengketa** (ada suara `still_there` dan `gone` independen, tidak ada yang mencapai N).
   - Hazard aktif → `resolved` bila independen `gone` ≥ N.
   - `last_confirmed_at` diperbarui pada suara `still_there`.
7. Tulis `audit_logs`, jadwalkan `InvalidateRouteCacheJob` bila status atau confidence berubah.
8. Respons: status baru, jumlah suara, dan teks umpan balik.

**Aturan keadilan:** pelapor awal tidak dihitung sebagai konfirmasi independen atas hazard-nya sendiri. Satu orang tidak dapat menaikkan confidence sendirian.

**Hazard `resolved` dapat dibuka kembali:** laporan baru di radius yang sama dalam jendela waktu (config, mis. 7 hari) membuka hazard dengan status `unconfirmed` dan catatan "Dibuka kembali". Verifikator juga dapat membuka kembali.

**Kriteria penerimaan:**
- [ ] Satu pengguna satu suara per hazard; dapat diubah.
- [ ] Konfirmasi ganda cepat dari satu pengguna tidak menaikkan hitungan independen.
- [ ] Transisi status hanya lewat `HazardStateService` dan tercatat di audit.
- [ ] `gone` mencapai N → hazard `resolved` tanpa campur tangan admin.

---

### F5. Verifikasi (Antrean Verifikator)

**Tujuan:** verifikator sekolah memeriksa hanya hazard berat atau yang disengketakan, dengan prioritas jelas.

**Tata letak desktop:**

```text
┌ Sidebar ──┬──────────────────────────────────────────────────────┐
│ Antrean ● │ Antrean verifikasi (4)        Filter: [Semua jenis ▼]│
│ Peta      │ ┌─────────┬──────────────┬──────┬───────┬──────────┐ │
│ Riwayat   │ │Prioritas│ Hazard       │Lapor │ Umur  │ Alasan   │ │
│           │ ├─────────┼──────────────┼──────┼───────┼──────────┤ │
│ Logout    │ │ Tinggi  │Genangan pasar│  3   │ 4 mnt │Sev.tinggi│ │
│           │ │ Sedang  │Pohon tumbang │  2   │ 40 mnt│Sengketa  │ │
│           │ └─────────┴──────────────┴──────┴───────┴──────────┘ │
│           │ ┌ Detail ───────────────────────────────────────────┐│
│           │ │ ░ foto ░ ░ peta mini ░                            ││
│           │ │ Laporan (3) · Konfirmasi (2 ada, 0 surut)         ││
│           │ │ Severity: (1)(2)(●3)  Catatan: __________         ││
│           │ │ [Verifikasi] [Tolak] [Minta bukti tambahan]       ││
│           │ └───────────────────────────────────────────────────┘│
└───────────┴──────────────────────────────────────────────────────┘
```

Mobile: daftar kartu, ketuk membuka detail layar penuh dengan tiga tombol aksi di bagian bawah (tetap terlihat).

**Backend:**

| Hal | Rincian |
|---|---|
| Daftar | `GET /verifier/queue`: hazard `pending_verification` di catchment verifikator |
| Prioritas | Skor heuristik (config): `severity×10 + min(laporan independen,5)×4 + min(umur_jam,48)×0.5 + (sengketa ? 15 : 0)`. Skor ditampilkan sebagai label Tinggi/Sedang/Rendah, bukan angka |
| Aksi | `POST /hazards/{hazard}/verifications` dengan `decision`: `verify`, `reject`, `request_evidence`, plus `note` dan `severity` (opsional) |
| `verify` | Status → `verified`, komponen verifikasi pada confidence = 0.40; severity boleh disesuaikan (1–3) dengan catatan |
| `reject` | Status → `rejected` (tidak dihitung pada rute), wajib `note`; dicatat untuk reputasi pelapor |
| `request_evidence` | Hazard tetap `pending_verification` dengan penanda `evidence_requested`; pelapor mendapat pesan dalam aplikasi (S4) |
| Transaksi | Tulis `verifications` + `audit_logs` + perubahan status dalam satu transaksi; lalu `InvalidateRouteCacheJob` |
| Otorisasi | `VerificationPolicy`: role `verifier` dan catchment sama; tidak bisa memverifikasi laporan yang ia buat sendiri |
| Label publik | Hazard terverifikasi tampil "Terverifikasi oleh verifikator sekolah" (bukan "Verified by AI") |

**Catatan:** status `rejected` adalah tambahan di luar siklus status di `Prompt_Backend_RuteAman.md`; tambahkan ke enum `HazardStatus` bila belum ada.

**Kriteria penerimaan:**
- [ ] Hanya hazard berat/sengketa masuk antrean (hazard sementara tidak menunggu guru).
- [ ] Setiap keputusan tercatat dengan verifikator, waktu, dan catatan.
- [ ] Beban rute berubah setelah verifikasi (22 → 47 → 67 pada data demo).
- [ ] Verifikator sekolah lain tidak dapat melihat atau memutuskan antrean ini.

---

### F6. Beban Hazard + Rincian ("Kenapa angkanya segini?")

**Tujuan:** angka yang **transparan dan dapat dibantah**, bukan kotak hitam.

**Tata letak komponen:**

```text
[ Beban hazard 67 · BERAT ]   ← chip: ikon ⚠ + angka + teks (bukan warna saja)
Kenapa angkanya segini? ▼
┌──────────────────────────────────────────────────────────┐
│ Jenis        Bobot Keyakinan Kesegaran Cuaca  Kontribusi │
│ Genangan       3     0.9       1.0     ×1.5     4.05      │
│ J. berlubang   2     1.0       1.0      1.0     2.0      │
│ Total 6.05 dari titik jenuh 9 → 100 × 6.05 / 9 ≈ 67      │
│ ───────────────────────────────────────────────────────  │
│ Keyakinan naik karena ada foto, konfirmasi komunitas, dan │
│ verifikasi sekolah. Cuaca hujan menambah bobot genangan.  │
│ Angka ini ringkasan laporan komunitas, bukan penilaian    │
│ keamanan.                                                 │
└──────────────────────────────────────────────────────────┘
```

Label ditampilkan bersama angka: Ringan (1–29), Sedang (30–59), Berat (60–100), `no_report`, `stale_data`. Setiap pasangan warna memakai token kontras dari `Prompt_UI_UX_RuteAman.md`.

**Backend: `HazardLoadService`** (service murni, tanpa HTTP):

```text
c = min(1, 0.30×min(laporan_independen,3)/3 + 0.30×min(konfirmasi,3)/3
         + 0.20×(ada_foto) + 0.40×(terverifikasi))      # reputasi pelapor dapat mengecilkan kontribusi laporan
f = 1 jika umur ≤ TTL; turun linear ke 0.3 pada 2×TTL; kedaluwarsa setelah 3×TTL
w = 1.5 untuk hazard rain_sensitive jika hujan pada rentang waktu perjalanan
    ATAU ada laporan genangan baru; selain itu 1.0
h = s × c × f × w            (s = hazards.severity, awalnya dari bobot jenis)
L = min(100, 100 × Σh / 9)   (9 = titik jenuh, config)
```

- Hazard `night_only` dihitung hanya pada jam malam menurut zona waktu sekolah.
- Hanya status `community_confirmed`, `pending_verification`, `verified` yang dihitung.
- Output selalu berisi **rincian** per hazard (jenis, s, c, f, w, h, status, umur konfirmasi terakhir). Frontend memakainya untuk tabel di atas.
- Nilai disimpan penuh, dibulatkan hanya untuk tampilan.

**Tabel uji wajib** (sesuai contoh hitung konsep):

| Skenario | Σh | L | Label |
|---|---|---|---|
| Jalan berlubang (s=2, c=1.0, f=1, w=1) | 2.0 | 22 | Ringan |
| + genangan, 1 laporan, 2 konfirmasi, foto, belum diverifikasi (c=0.50, w=1.5, h=2.25) | 4.25 | 47 | Sedang |
| Genangan diverifikasi (c=0.90, h=4.05) | 6.05 | 67 | Berat |
| Tanpa hazard aktif | 0 | — | Belum ada laporan aktif |

**Kriteria penerimaan:** unit test tabel di atas lulus; rincian selalu tersedia; tidak ada tempat yang membalik arah angka.

---

### F7. Eskalasi Terstruktur

**Tujuan:** menutup loop dengan jujur. Sekolah **tidak mengklaim memperbaiki jalan**; sekolah mengumpulkan bukti dan meneruskannya, lalu mencatat respons.

**Tata letak (detail hazard untuk Admin Sekolah, desktop):**

```text
┌ Genangan · depan pasar ────────────────── [Terverifikasi] ───┐
│ ░ foto ░  ░ peta mini ░    3 laporan · 2 konfirmasi          │
│                                                              │
│ Tindak lanjut                                                │
│ ( ) Internal: pengumuman / jadwal piket / imbauan jam pulang │
│ ( ) Eksternal: teruskan ke pihak berwenang                   │
│                                                              │
│ Status eskalasi:                                             │
│ ●─────────○─────────○──────────○                             │
│ Belum      Diteruskan Ditanggapi Selesai                     │
│ diteruskan (tgl/kanal) (catatan)  (konfirmasi komunitas)     │
│                                                              │
│ [Buat paket eskalasi]  [Salin pesan WA] [Salin pesan email]  │
│ [Unduh PDF]            [Catat penerusan] [Catat respons]     │
└──────────────────────────────────────────────────────────────┘
```

Modal "Buat paket eskalasi": pratinjau isi paket, pilih kanal (WhatsApp, email, laporan resmi, internal), centang apa yang disertakan (foto, koordinat), lalu "Buat". Pratinjau **tidak** pernah memuat data pelapor.

**Backend:**

| Hal | Rincian |
|---|---|
| Buat paket | `POST /hazards/{hazard}/escalations` → `EscalationPackageService` merakit: ID, jenis, koordinat + tautan peta, foto, jumlah laporan dan konfirmasi, waktu pertama dilaporkan dan terakhir dikonfirmasi, riwayat verifikasi, **tautan publik baca-saja** (`/h/{public_slug}`). Tanpa data pelapor |
| Keluaran | PDF dan teks siap kirim (salin WA/email) |
| Unduh | `GET /hazards/{hazard}/escalation-package` (route model binding, `authorize('escalate', $hazard)`) |
| Catat penerusan | `PATCH /escalations/{escalation}` → `forwarded` dengan `channel`, `forwarded_at`, `reference_id` (nomor tiket jika ada) |
| Catat respons | `PATCH /escalations/{escalation}` → `responded` dengan `response_note`, `response_at` |
| Selesai | **Hanya** lewat konfirmasi komunitas `gone` ≥ N (F4). Admin tidak dapat menandai "selesai" sendiri |
| Tanpa respons | `EscalationReminderJob` menandai `no_response` setelah N hari (config) dan membuat pengingat dalam aplikasi untuk Admin |
| Kanal otomatis | Hanya bila ada API resmi dan izin. Jika tidak, manual dan didokumentasikan. **[CEK kanal resmi yang berlaku di wilayah Anda]** |
| Audit | Setiap pembuatan paket, penerusan, dan respons masuk `audit_logs` |

**Contoh teks siap salin:**

```text
Laporan kondisi jalan dari komunitas MAN Contoh

Jenis     : Genangan
Lokasi    : -6.2xxxx, 106.8xxxx  (peta: <tautan>)
Status    : Terverifikasi oleh verifikator sekolah
Dasar     : 1 laporan, 2 konfirmasi komunitas, foto terlampir
Dilaporkan: 06 Okt 2026, 18.36 · Terakhir dikonfirmasi 18.40
Tautan    : https://…/h/<public_slug>

Mohon tindak lanjut. Kami akan memperbarui status berdasarkan konfirmasi di lapangan.
```

**Dua jenis tindak lanjut** dicatat terpisah di `escalations.channel`: **internal** (pengumuman, piket, imbauan) dan **eksternal** (lubang, drainase, lampu jalan).

**Kriteria penerimaan:**
- [ ] Paket dibuat otomatis dan tidak memuat data pelapor.
- [ ] Siklus `not_forwarded → forwarded → responded → confirmed_resolved` tercatat dan tervalidasi urutannya.
- [ ] `no_response` memunculkan pengingat.
- [ ] Admin tidak dapat menutup hazard tanpa konfirmasi komunitas.

---

### F8. Dashboard Sekolah

**Tujuan:** satu tampilan untuk memantau hazard aktif, antrean, dan status eskalasi, dengan metrik yang jujur.

**Tata letak (desktop):**

```text
┌ Sidebar ──┬───────────────────────────────────────────────────────┐
│ Ringkasan●│ [Hazard aktif 4] [Menunggu verifikasi 2]              │
│ Hazard    │ [Belum diteruskan 1] [Menunggu respons 1]             │
│ Eskalasi  │ ┌ Tabel hazard ───────────────┐ ┌ Peta mini ────────┐ │
│ Pengguna  │ │Jenis  Status  Umur  Eskalasi│ │ ░░░░░░ ● ●  ░░░░░ │ │
│ Metrik    │ │…                            │ └───────────────────┘ │
│ Logout    │ └─────────────────────────────┘                       │
│           │ ┌ Metrik (apa adanya) ─────────────────────────────┐  │
│           │ │ Median lapor→konfirmasi: [ISI HASIL]             │  │
│           │ │ Konfirmasi→diteruskan : [ISI HASIL]              │  │
│           │ │ % diteruskan / % ditanggapi : [ISI HASIL]        │  │
│           │ └──────────────────────────────────────────────────┘  │
│           │ ┌ Tren (S5) ───────────────────────────────────────┐  │
└───────────┴───────────────────────────────────────────────────────┘
```

Mobile: kartu KPI bertumpuk, tabel menjadi daftar kartu, menu lewat bottom nav. Layout mengikuti pola **F-shape** (KPI di atas, tabel di tengah, metrik di bawah).

**Backend:**

| Hal | Rincian |
|---|---|
| Endpoint | `GET /school/dashboard`, `GET /school/metrics` (catchment sekolah pengguna) |
| KPI | Hitung hazard aktif, menunggu verifikasi, belum diteruskan, menunggu respons (query teragregasi, cache 60 detik) |
| Metrik | Median waktu lapor → konfirmasi; waktu konfirmasi → diteruskan; persen hazard diteruskan; persen ditanggapi; waktu sampai diperbaiki. Nilai `null` bila data belum cukup, UI menampilkan "Belum ada data" |
| Klaim | Hanya angka yang benar-benar terukur. Tidak ada angka dampak karangan |
| Otorisasi | `ADM` penuh; `VER` hanya baca |

**Kriteria penerimaan:** angka KPI cocok dengan data tabel; metrik tanpa data tampil "Belum ada data" (bukan 0 palsu).

---

### F9. Autentikasi, Tiga Role, dan Kelola Pengguna

**Tujuan:** akses sederhana dan ramah anak, dengan privasi sebagai bawaan.

**Tata letak Login/Daftar** (mengacu referensi login mobile dan portal kampus): satu kolom, label jelas, tombol penuh lebar.

```text
Masuk                             Daftar
┌──────────────────────────┐      ┌──────────────────────────┐
│ Selamat datang kembali   │      │ Buat akun                │
│ Nama pengguna / email    │      │ Kode sekolah   ________  │
│ ________________________ │      │ Nama panggilan ________  │
│ Kata sandi        [👁]   │      │ Email          ________  │
│ ________________________ │      │ Kata sandi     ________  │
│ [x] Ingat saya           │      │ [ ] Saya di bawah 18 tahun│
│ [ Masuk ]                │      │     → persetujuan orang   │
│ Belum punya akun? Daftar │      │       tua diperlukan      │
│ Lupa kata sandi?         │      │ [x] Saya menyetujui       │
│ Syarat & Kebijakan Privasi│     │     Kebijakan Privasi     │
└──────────────────────────┘      │ [ Daftar ]               │
                                  └──────────────────────────┘
```

**Backend:**

| Hal | Rincian |
|---|---|
| Daftar siswa | `POST /register` dengan kode sekolah → `school_id`. Data minimal: nama panggilan (tampil publik sebagai nama samaran), email, kata sandi. Tanpa alamat rumah dan telepon |
| Persetujuan orang tua | Untuk < 18 tahun, `parental_consent_at` dicatat setelah alur persetujuan sekolah. Sebelum itu akun hanya dapat membaca dan mengonfirmasi tidak dapat melapor (kebijakan sekolah) |
| Login | `POST /login`, remember me, throttle per IP + akun; pesan error tidak membocorkan keberadaan akun |
| Reset sandi | Lupa/reset kata sandi dengan token bertenggat waktu dan throttle |
| Role | `student` (default), `verifier`, `school_admin` |
| Kelola pengguna (ADM) | `GET /school/users`, `PATCH /school/users/{user}` untuk promosi/turun peran verifikator, suspend/aktifkan. Admin hanya mengelola pengguna sekolahnya |
| Pembuatan Admin Sekolah | Lewat seeder/console (System Admin bukan role produk) |
| Akun | Hapus akun dan ekspor data milik sendiri. Hapus akun menganonimkan laporan, bukan menghapus riwayat hazard |
| Audit | Perubahan role dan status akun masuk `audit_logs` |

**Kriteria penerimaan:** pengguna publik tidak butuh login untuk membaca; siswa < 18 tanpa consent tidak bisa melapor; verifikator sekolah A tidak punya akses ke sekolah B.

---

### F10. Cuaca BMKG sebagai Konteks

**Tujuan:** cuaca hanya sebagai **pengali konteks** (w), bukan pembuat hazard.

**Tata letak:** chip kecil di header peta dan di kartu rute: "Hujan · diperbarui 18.40". Saat data basi: "Data cuaca terakhir 18.10 (mungkin sudah berubah)" dengan ikon jam. Saat tidak ada data: chip tidak ditampilkan dan rincian beban menyebut "Konteks cuaca tidak tersedia, bobot cuaca 1.0".

**Backend:**

| Hal | Rincian |
|---|---|
| Provider | `WeatherProviderInterface` → `BmkgWeatherProvider`, `MockWeatherProvider` |
| Sumber | Per `schools.bmkg_area_code`. **[CEK dokumentasi BMKG: endpoint, granularitas wilayah dan waktu, atribusi]** |
| Penyegaran | `RefreshWeatherSnapshotsJob` berkala sesuai batas penggunaan; hasil disimpan di `weather_snapshots` (`fetched_at`, `valid_until`, `rain_flag`) |
| Penggunaan | `w = 1.5` bila periode prakiraan yang bertampalan dengan rentang `sekarang → sekarang + durasi rute` menunjukkan hujan, **atau** ada laporan genangan baru di catchment dalam jendela waktu (config), meski BMKG tidak menunjukkan hujan |
| Gagal | Pakai snapshot terakhir (ditandai basi); tanpa data: `w = 1.0`. Fitur inti tetap berjalan |
| Status | `GET /api/status` → `{routing, weather}` dengan `ok` dan `last_success_at` untuk banner status |
| Batas | Prakiraan BMKG kasar. Jangan pernah menyimpulkan "jalan ini banjir" dari cuaca |

**Kriteria penerimaan:** BMKG mati → rute tetap terhitung dengan `w=1.0` dan banner basi; atribusi tampil sesuai ketentuan.

---

## 4. FITUR SHOULD

### S1. Layer Hazard Bersama Antar Sekolah

- **Cara kerja:** hazard milik **wilayah** (geometri), bukan milik sekolah. Hazard terlihat oleh sekolah mana pun yang `catchment`-nya berpotongan dengan titik hazard (`ST_Intersects`). Tanpa tabel tambahan.
- **Tata letak:** kartu hazard dari sekolah lain diberi lencana "Dilaporkan dari area sekolah lain", warna netral, dan tetap dapat dikonfirmasi oleh siapa pun yang berada di catchment tersebut.
- **Aturan:** verifikasi tetap oleh verifikator dari catchment yang berisi titik hazard. Jika dua catchment berpotongan, verifikator mana pun dari keduanya boleh memutuskan; keputusan pertama yang tercatat berlaku, yang kedua dapat meninjau.

### S2. Feed GeoJSON Terbuka

- **Endpoint:** `GET /api/feed/hazards.geojson?school=&bbox=` (publik, rate limit, cache singkat).
- **Properti fitur:** `public_slug`, `type`, `severity`, `status`, `last_confirmed_at`, `confirmations_count`. **Tanpa** data pelapor, foto privat, atau kolom internal.
- **Metadata:** `attribution`, `generated_at`, dan catatan lisensi data **[TENTUKAN lisensi, mis. CC BY]**.
- **Tata letak:** halaman "Data terbuka" sederhana berisi penjelasan, contoh `curl`, dan tautan unduh.
- **Batas:** ini feed baca, bukan integrasi dua arah dengan sistem pemerintah. Jangan menamainya "ekosistem".

### S3. Tampilan Baca-Saja untuk Orang Tua

- **Cara kerja:** halaman publik `/s/{school_slug}` (tambah kolom `slug` pada `schools`) menampilkan peta dan daftar hazard aktif di area sekolah tanpa login dan tanpa tombol lapor.
- **Tata letak:** peta penuh, ringkasan "4 hazard aktif di sekitar sekolah", daftar kartu, penjelasan singkat cara membaca "Beban hazard" dan disclaimer. Tidak ada rute pribadi dan tidak ada data anak.
- **Aturan:** tidak ada pelacakan, tidak ada akun. Teks bahasa awam, bukan istilah teknis.

### S4. Notifikasi Dalam Aplikasi (Status Laporanmu)

- **Cara kerja (tanpa modul notifikasi mandiri, sesuai konsep):** halaman **Laporanku** menampilkan linimasa status tiap laporan, diturunkan dari `hazards.updated_at` dan `audit_logs`. Penanda "baru" dihitung dengan membandingkan perubahan terakhir dengan `users.notif_seen_at` (satu kolom, bukan tabel baru). Banner dalam aplikasi muncul ketika ada perubahan penting.
- **Peristiwa yang ditampilkan:** "Laporanmu dikonfirmasi komunitas", "Diverifikasi oleh verifikator sekolah", "Verifikator meminta bukti tambahan", "Laporanmu ditolak (alasan: …)", "Hazard sudah surut".
- **Tata letak:** daftar laporan dengan lencana status (Baru, Dikonfirmasi, Terverifikasi, Selesai, Kedaluwarsa), titik "baru" di item Laporanku pada bottom nav, tanpa pop-up mengganggu.

### S5. Grafik Tren Sederhana (Chart.js)

- **Endpoint:** `GET /school/metrics/trend?range=30d` mengembalikan hitungan hazard per hari/pekan (dilaporkan, dikonfirmasi, selesai).
- **Tata letak:** satu grafik garis atau batang di dashboard dengan judul jelas, label sumbu, dan **tabel data alternatif** (aksesibilitas). Warna dari palet; jangan bergantung warna saja (pola garis berbeda).
- **Aturan:** tampilkan "Belum cukup data" bila kurang dari ambang minimum. Jangan menarik kesimpulan "menurun/meningkat" tanpa data cukup.

---

## 5. FITUR COULD (HANYA SETELAH SEMUA MUST SELESAI DAN TERUJI)

### C1. Pra-isi Kategori dari Foto (AI)

- **Cara kerja:** setelah foto dipilih di langkah 3 form lapor, `POST /api/hazards/photo-suggest` mengirim foto ke `AIProviderInterface` (kunci hanya di backend) dan mengembalikan **saran** jenis dan severity. Pengguna selalu dapat mengubahnya.
- **Tata letak:** chip "Saran: Jalan berlubang" di atas pilihan kategori dengan tombol "Pakai saran / Pilih sendiri". Bukan keputusan otomatis.
- **Aturan:** feature flag `AI_PHOTO_SUGGEST=false` secara default. Hasil AI **tidak** mengubah confidence atau status. **Protokol evaluasi wajib** (≥ 100 foto, akurasi per kategori) sebelum diklaim; jika kurang, fitur dibuang. Tanpa AI, produk tetap utuh.

### C2. Web Push / PWA

- **Cara kerja:** manifest + service worker untuk instal dan cache aset serta data terakhir. Web Push hanya untuk status laporan milik pengguna, dengan persetujuan eksplisit.
- **Tata letak:** ajakan pasang aplikasi muncul setelah penggunaan berulang, bukan di kunjungan pertama; pengaturan notifikasi di Akun.
- **Aturan:** tidak ada push berisi lokasi atau data sensitif. Offline tetap menampilkan data terakhir dengan label waktu.

### C3. Blur Otomatis Wajah dan Plat Nomor

- **Cara kerja:** job antrean memproses foto yang sudah diunggah, mendeteksi wajah/plat, mengaburkannya pada salinan yang ditampilkan, dan menyimpan versi asli hanya selama retensi.
- **Tata letak:** lencana "Wajah/plat dikaburkan otomatis" pada kartu; pelapor dapat meminta penghapusan foto.
- **Aturan:** perlu evaluasi akurasi sebelum diklaim. Tetap tampilkan peringatan "hindari memotret wajah dan plat" di form.

---

## 6. KOMPONEN LINTAS FITUR

### 6.1 Format error standar (semua endpoint JSON)

```json
{ "error": { "code": "OUTSIDE_CATCHMENT",
             "message": "Titik ini di luar area yang dipantau sekolahmu.",
             "field_errors": { "lat": ["…"] },
             "retry_after": null } }
```

| Kode | Arti | Pesan UI (contoh) |
|---|---|---|
| `OUTSIDE_CATCHMENT` | Titik di luar area | "Titik ini di luar area yang dipantau sekolahmu." |
| `RATE_LIMITED` | Terlalu sering | "Kamu sudah melapor beberapa kali. Coba lagi dalam beberapa menit." |
| `CONSENT_REQUIRED` | Belum ada persetujuan orang tua | "Akunmu belum bisa melapor. Minta sekolah memproses persetujuan orang tua." |
| `MERGED_INTO_EXISTING` | Laporan digabung (bukan error) | "Laporan serupa sudah ada. Laporanmu ditambahkan sebagai bukti." |
| `ROUTING_UNAVAILABLE` | Routing gagal | "Perbandingan rute sedang tidak tersedia." |
| `FORBIDDEN_SCOPE` | Bukan wewenang/catchment | "Kamu tidak punya akses ke data sekolah ini." |
| `VALIDATION_FAILED` | Input tidak valid | Pesan per field yang ramah |

Pesan teknis penuh hanya di log, tidak pernah ke pengguna.

### 6.2 Banner status data (tampil di atas peta)

| Kondisi | Banner | Fitur lain |
|---|---|---|
| Routing gagal | "Perbandingan rute terbatas. Menampilkan rute tersimpan." | Peta, hazard, lapor tetap jalan |
| BMKG gagal | "Data cuaca terakhir 18.10. Bobot cuaca mungkin tidak akurat." | Rute tetap dihitung |
| Offline | "Kamu offline. Menampilkan data terakhir (18.35)." | Lapor ditunda ke antrean klien (C2) atau diminta coba lagi |
| Data area lama | "Belum ada aktivitas di area ini selama N hari." | — |

### 6.3 Anatomi kartu hazard (satu komponen, dipakai di semua halaman)

Foto 4:3 (atau ikon kategori bila tanpa foto) → jenis + lokasi → lencana status (ikon + teks) → umur konfirmasi terakhir → jumlah laporan/konfirmasi → tombol konfirmasi (jika berhak) → "Kenapa statusnya begini?". Satu sumber kebenaran: `HazardPublicResource` (publik) dan `HazardOwnerResource` (milik pelapor, memuat status internal).

### 6.4 Audit log (minimal)

`hazard.created`, `hazard.merged`, `hazard.status_changed`, `vote.cast`, `verification.decided`, `escalation.created`, `escalation.forwarded`, `escalation.responded`, `user.role_changed`, `user.suspended`, `data.exported`, `data.deleted`. Setiap entri memuat aktor, entitas, waktu, dan meta ringkas **tanpa koordinat asal rute**.

---

## 7. SKENARIO UJI END-TO-END (DATA DEMO KONSISTEN)

Konteks: hujan sedang berlangsung; sekolah fiktif "MAN Contoh".

| Langkah | Aktor | Aksi | Hasil di sistem |
|---|---|---|---|
| 1 | PUB | Buka peta, bandingkan rute | Rute A 1,8 km: beban **22 (Ringan)**, 1 hazard (jalan berlubang, terverifikasi, 3 hari lalu). Rute B 2,1 km (+0,3 km): **"Belum ada laporan aktif"**, bukan "aman" |
| 2 | STU | Lapor genangan di depan pasar (Rute A) dengan foto | Hazard baru `unconfirmed` · "Menunggu konfirmasi" |
| 3 | STU lain | Laporan serupa dari pengguna berbeda | Digabung (`MERGED_INTO_EXISTING`), 3 laporan |
| 4 | STU lain ×2 | "Masih ada" | 2 konfirmasi independen (ambang N diatur ke 2 untuk demo bila perlu, lihat catatan) |
| 5 | Sistem | Evaluasi | Status **Dikonfirmasi Komunitas**, `c = 0.70`, `h = 3.15`, Σh = 5.15 → Rute A **57 (Sedang)** |
| 6 | VER | Buka antrean, verifikasi genangan | `c = 1.0`, `h = 4.5`, Σh = 6.5 → Rute A **72 (Berat)** |
| 7 | PUB | Muat ulang perbandingan | Rute A **72 (Berat)** dengan 2 hazard; Rute B tetap `no_report` |
| 8 | ADM | Buat paket eskalasi, catat penerusan | `forwarded` dengan kanal dan tanggal; teks siap salin |
| 9 | Komunitas | N suara "Sudah surut" | Hazard `resolved`; eskalasi `confirmed_resolved` |

**Catatan demo:** contoh hitung konsep memakai 3 laporan + 2 konfirmasi untuk status Dikonfirmasi Komunitas. Jika N konfirmasi independen di config = 3, atur N = 2 pada lingkungan demo agar skenario sesuai (parameter, bukan perubahan kode).

---

## 8. DAFTAR PERIKSA PENERIMAAN GLOBAL

- [ ] Semua 10 fitur MUST berjalan dari layar hingga database, dengan test otomatis.
- [ ] Tidak ada kata "aman/safe" di UI, API, atau pesan.
- [ ] Peta, rute, dan feed publik tidak memuat data pelapor.
- [ ] Titik asal rute dan lokasi pengguna tidak tersimpan dan tidak di-log.
- [ ] Beban Hazard sesuai tabel uji (22 → 47 → 67, `no_report`) dan selalu disertai rincian.
- [ ] Hazard sementara tidak menunggu verifikator; hanya severity tinggi/sengketa yang antre.
- [ ] Eskalasi menutup dengan konfirmasi komunitas, bukan klaim admin.
- [ ] Semua state (kosong, memuat, error, offline, data lama, gagal routing/BMKG) punya desain dan pesan.
- [ ] Setiap status memakai ikon + teks + angka, bukan warna saja; kontras sesuai token di prompt UI.
- [ ] Parameter domain di config, bukan hardcode.
- [ ] Setiap angka dampak yang belum diukur ditulis `[ISI HASIL]`.

---

## CATATAN UNTUK ANDA (bukan bagian spesifikasi)

- **Hal yang saya tambahkan di luar konsep karena diperlukan agar fitur bisa berjalan:** status `rejected` (F5), kolom `schools.slug` (S3), kolom `users.notif_seen_at` (S4), geocoding untuk pencarian tempat (F2), dan manajemen pengguna oleh Admin Sekolah (F9). Semuanya tidak menambah tabel baru, jadi batas ≤ 10 tabel tetap terjaga. Jika tidak mau, bagian itu mudah dicoret.
- **Nilai yang masih heuristik:** skor prioritas antrean (F5), jendela "laporan genangan baru" (F10), jendela buka-kembali hazard (F4), dan semua ambang config. Kalibrasi lewat pilot.
- **Perlu Anda putuskan/cek sendiri:** ketentuan penyedia geocoding dan BMKG, kanal resmi eskalasi di wilayah Anda, lisensi feed GeoJSON, dan kebijakan persetujuan orang tua sekolah.
- Saya juga merapikan satu ketidakcocokan di `Prompt_UI_UX_RuteAman.md`: contoh data di Bagian 9 sebelumnya tidak sesuai konsep, sekarang disamakan dengan contoh hitung konsep.


## 8.6. Contoh hitung demo terbaru (menggantikan contoh lama)

- Lubang: 2 laporan independen, 2 konfirmasi, foto, verifikasi. c = min(1, 0.2 + 0.2 + 0.2 + 0.4) = 1.0. h = 2.0; Beban 22.
- Genangan: 1 laporan, 2 konfirmasi independen, foto. c = 0.1 + 0.2 + 0.2 = 0.5. w = 1.5 dari cache hujan manual; f = 1. h = 2.25. Dengan lubang Σh = 4.25; Beban 47.
- Setelah verifikasi Admin: c = 0.9, h = 4.05; Σh = 6.05; Beban 67.
- Duplikat tidak membuat laporan kedua: UI menawarkan “Masih ada”; pengguna harus memilih konfirmasi. Pelapor tidak dapat mengonfirmasi laporannya sendiri.
- Belum ada konfirmasi komunitas: hazard tampil di layer publik, tetapi tidak dihitung ke Beban rute.
- Dua konfirmasi + satu pelapor (3 pengguna independen) menaikkan status tanpa menunggu Admin.

## Amendemen v7.0 — F9 Admin Berjenjang

Amendemen ini menggantikan keputusan bahwa System Admin hanya operasi console: Owner dan Admin Platform sekarang role produk, ditambah Staf Admin Sekolah. Hierarki dan F9 mengikuti `ADMIN_CHANGES.md` dan `ADMIN_SETUP.md`: izin akun ∩ langit-langit tingkat ∩ saklar sekolah ∩ cakupan, tanpa self-escalation dan tanpa impersonasi. Owner awal/pemulihan tetap CLI, sementara provisioning/delegasi dilakukan lewat konsol. Tidak ada tabel baru, tetapi baseline total 11 domain tables tetap menjadi pengecualian terbuka. 2FA belum diintegrasikan; ini bukan klaim seluruh kriteria prompt terpenuhi.
