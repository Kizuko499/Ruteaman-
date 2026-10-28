<p align="center">
  <img src="./public/favicon.svg" width="96" alt="Logo RuteAman">
</p>

<h1 align="center">RuteAman</h1>

<p align="center">
  <strong>Cek kondisi jalan sebelum berangkat.</strong><br>
  Platform peta kondisi jalan menuju sekolah dan kampus — dilaporkan warga, diverifikasi petugas, dan tampil langsung di peta.
</p>

<p align="center">
  <a href="https://ruteaman.my.id">Website Live</a> ·
  Karya untuk <strong>Web Development Competition UINIC 8.0</strong> — UIN Sunan Kalijaga, 2026 · Subtema: Pendidikan
</p>

---

## Tentang RuteAman

RuteAman membantu siswa, mahasiswa, guru, dan orang tua mengetahui kondisi rute menuju sekolah atau kampus **sebelum berangkat**. Warga melaporkan kondisi jalan (jalan rusak, banjir, jalan ditutup, kecelakaan, dan lainnya), petugas institusi memverifikasinya, dan hasilnya tampil di peta interaktif lengkap dengan perbandingan rute berdasarkan beban kondisi di sepanjang jalurnya.

Satu platform melayani banyak institusi sekaligus: setiap sekolah/kampus memiliki wilayah pantau, petugas, dan antrean verifikasinya sendiri, sementara data kondisi yang sudah terverifikasi saling melengkapi di peta publik.

## Fitur Utama

- **Peta interaktif kondisi jalan** — Semua laporan publik tampil sebagai penanda berkode warna per jenis kondisi, dengan legenda, saringan jenis kondisi, dan panel rute yang bisa disembunyikan.
- **Lapor kondisi tanpa ribet** — Warga bisa melapor sebagai tamu atau dengan akun, lengkap dengan foto, titik lokasi di peta, dan pilihan institusi tujuan. Foto diproses ulang (diubah ke WebP, metadata/EXIF dibuang) demi privasi.
- **Perbandingan rute aman** — Bandingkan rute dasar dan rute alternatif dari OpenRouteService, dinilai dari jarak, durasi, dan beban kondisi aktif di sepanjang jalur.
- **Verifikasi berjenjang** — Laporan masuk ke antrean verifikasi petugas institusi: bisa diverifikasi, dimintai bukti tambahan, atau ditolak. Petugas tidak dapat memverifikasi laporannya sendiri.
- **Komentar per kondisi** — Setiap kondisi dapat didiskusikan; komentar dapat dihapus moderator melalui izin khusus.
- **Pencarian institusi nasional** — Direktori sekolah dari data Dapodik dan katalog kampus lokal berisi 33.742 entri (PDDikti 2025 + ROR), sehingga institusi di seluruh Indonesia dapat ditemukan saat mendaftar dan melapor.
- **Usulan institusi** — Institusi yang belum terdaftar dapat diusulkan publik dan ditinjau admin, dengan kode tiket untuk melacak statusnya.
- **Panel admin lengkap** — Ringkasan, pengelolaan institusi, tingkatan & izin per akun (setiap fitur punya saklar izin), pengelolaan fitur per institusi, jenis kondisi & ikon, foto beranda, impor/ekspor CSV, log audit, dan panduan admin bawaan.
- **Email & notifikasi** — Notifikasi email untuk laporan baru, perubahan status verifikasi, dan akun, memakai antrean pengiriman dengan riwayat dan status terkirim/gagal.
- **Statistik & ringkasan wilayah** — Dashboard institusi menampilkan aktivitas wilayah, jumlah laporan, dan kondisi yang perlu perhatian.

## Teknologi

| Lapisan | Teknologi |
|---|---|
| Backend | Laravel 13 (PHP 8.4) |
| Database | MariaDB 10.11 (kolom spasial POINT/POLYGON untuk titik & wilayah pantau) |
| Frontend | Blade + Tailwind CSS 4 + Vite |
| Peta | Leaflet + OpenStreetMap |
| Routing | OpenRouteService API |
| Data pendidikan | Dapodik (Kemendikdasmen), PDDikti, ROR |
| Geocoding | Nominatim (dengan pembatasan laju yang sopan) |
| Email | SMTP + antrean (queue) Laravel |

## Keamanan & Privasi

- 6 tingkat peran (Admin Utama sampai Siswa) dengan sistem izin granular — akses tiap akun dapat diatur per fitur.
- Foto laporan tersimpan di folder privat dan hanya ditayangkan setelah layak publik.
- Validasi sisi server di semua formulir, proteksi CSRF, dan pembatasan laju (rate limiting) pada endpoint publik & API.
- Kredensial sensitif (kunci API, kata sandi SMTP) hanya disimpan di `.env` server dan **tidak** disertakan di repository ini.

## Instalasi

### Kebutuhan

- PHP 8.4+ dengan ekstensi: pdo_mysql, mbstring, gd, fileinfo
- MariaDB 10.11+ (atau MySQL 8 dengan dukungan spasial)
- Composer 2 dan Node.js 18+

### Langkah

```bash
# 1. Ambil kode & pasang dependensi
git clone https://github.com/Kizuko499/Ruteaman-.git
cd ruteaman
composer install
npm install && npm run build

# 2. Konfigurasi lingkungan
cp .env.example .env
php artisan key:generate
# lalu isi DB_* dan ORS_API_KEY di .env

# 3. Siapkan database
php artisan migrate --force

# 4. Jalankan
php artisan serve
```

### Variabel environment penting

```env
APP_URL=https://domain-anda.com
DB_CONNECTION=mysql
DB_DATABASE=ruteaman
ORS_API_KEY=isi-dengan-kunci-anda
MAIL_MAILER=smtp
```

## Struktur Direktori

```
app/
├── Http/Controllers/     # Controller publik, admin, dan API
├── Models/               # Model Eloquent (Hazard, School, User, ...)
├── Services/             # Logika bisnis (izin, verifikasi, direktori institusi, email)
resources/views/
├── landing.blade.php     # Beranda
├── map.blade.php         # Peta interaktif
├── admin/                # Panel admin
├── verifier/             # Antrean verifikasi
database/migrations/      # Migrasi database
routes/
├── web.php               # Route publik
└── admin.php             # Route admin
```

## Demo

- **Website:** https://ruteaman.my.id

## Tim

- **Institusi:** MAN 2 Rembang
- **Nama tim:** Tim RuteAman
- **Anggota:** Tim siswa MAN 2 Rembang

---

*Dikembangkan untuk UINIC 8.0 — Web Development Competition, UIN Sunan Kalijaga Yogyakarta.*

© 2026 Tim RuteAman — MAN 2 Rembang
