> **Patch konsol admin terbaru:** dua tingkat aktif, kendali Owner semua pengguna, sidebar ikon, audit rinci dan sandi awal. Ikuti `docs/ADMIN_CONSOLE_REPAIR_SETUP.md` dan `docs/ADMIN_CONSOLE_REPAIR_REPORT.md`. Pengujian patch ini meliputi MariaDB dan PostgreSQL/PostGIS; catatan versi lama di bawah bersifat historis.

# RuteAman v7.3 — Laporan Tanpa Login

Paket penuh v7.1 dengan kompatibilitas MariaDB 10.11 tanpa PostGIS. Target hosting pengguna: MariaDB 10.11.19; pengujian lokal dilakukan pada MariaDB 10.11.18 + PHP 8.4.24. Marker umur/popup/legend tetap dipertahankan. Versi v7.3 menambahkan formulir tamu, gate moderasi, nama opsional dan asset Vite baru.

Versi v7.3: mulai dari `docs/GUEST_REPORTS_SETUP.md` untuk upgrade hosting dan `docs/GUEST_REPORTS_QA.md` untuk hasil pengujian. Nama tamu privat, sekolah wajib, laporan menunggu verifikasi; tidak ada akun anonim palsu.

## Mulai dari sini

- `docs/MARIADB_CPANEL_SETUP.md` — instalasi dan upgrade MariaDB/cPanel, konfigurasi `.env`, Owner, keamanan, dan batas teknis.
- `docs/MARIADB_QA.md` — bukti pengujian versi ini.
- `docs/ADMIN_SETUP.md` — peran admin, izin, undangan, SMTP dan pemulihan Owner.
- `docs/MAP_MARKERS.md` — arsitektur marker persisten v7.1.

**Upgrade:** backup dahulu, pertahankan `.env` dan APP_KEY, jalankan `php artisan optimize:clear` lalu `php artisan migrate --force`. Jangan `migrate:fresh` atau seed demo ke produksi. Satu tabel infrastruktur pengunci transaksi admin ditambahkan; tidak ada tabel domain baru. Memilih MariaDB tidak otomatis memindahkan data dari PostgreSQL.

ZIP tidak berisi `vendor`, `node_modules`, `.env`, database, sesi, atau unggahan QA. `public/build` produksi baru disertakan; Composer diperlukan untuk memasang dependensi. Composer lock belum tersedia pada baseline, sehingga Composer melakukan resolusi versi; simpan lock deployment Anda.

Panduan MariaDB di atas menggantikan instruksi database yang bertentangan pada dokumen versi lama. PostgreSQL/PostGIS tetap ada pada source, tetapi tidak diuji ulang pada patch ini; MySQL 8 asli juga tidak diuji ulang. 2FA belum tersedia.

## Riwayat basis v4.5 — Laravel 13 + MySQL Spatial / PostGIS

Source ini merupakan perubahan penuh dari basis `MIE-Auth-Fixed` menjadi **RuteAman**, papan kondisi rute sekolah berbasis laporan komunitas.

## Perubahan utama

- UI landing mengikuti pola referensi: navbar putih membulat di atas hero, hero foto besar, panel pencarian mengambang, bagian dua kolom, dan kartu visual.
- Palet RuteAman: teal, mint, cream, dan orange.
- Modul lama MIE (AI tutor, gamification, membership, voice tutor, pomodoro, dan 3D lab) sudah dihapus.
- Dua peran: `student` dan `school_admin` (verifikasi menjadi kewenangan Admin Sekolah).
- Laporan, konfirmasi, verifikasi, eskalasi, cuaca, dan audit disimpan melalui Eloquent/PostgreSQL.
- Geometri sekolah dan hazard menggunakan PostGIS SRID 4326.
- Foto laporan disimpan pada disk `public` dan wajib di-re-encode ke WebP agar metadata EXIF terhapus. Jika GD/WebP tidak tersedia atau pemrosesan gagal, foto ditolak.
- Formula Beban Hazard berada di `app/Services/HazardLoadService.php`.
- Form pendaftaran memakai direktori nasional: provinsi → kabupaten/kota → kecamatan → pencarian nama sekolah/NPSN. Sekolah yang dipilih diverifikasi server-side lalu disimpan ke tabel `schools` bersama wilayah dan koordinatnya.

## Menjalankan

```bash
cp .env.example .env
composer install
php scripts/ensure-storage.php
php artisan key:generate
npm install
npm run build
php artisan storage:link
php artisan migrate --seed
php artisan serve
```

Frontend memakai `@ybouane/liquidglass` untuk navbar, pencarian mengambang, panel rute, status, legenda peta, dan tombol aksi. Konfigurasi setiap elemen berada di `resources/js/app.js` pada objek `glassConfigs`. Jika WebGL tidak tersedia, CSS otomatis memakai fallback `backdrop-filter`.

### Direktori sekolah Indonesia

RuteAman mem-proxy endpoint publik portal Dapodik melalui server Laravel sehingga token publik tidak pernah dikirim ke browser. Daftar wilayah diambil online dari `wilayah.id` (38 provinsi, 514 kabupaten/kota, dan 7.285 kecamatan). Salinan lengkapnya juga disimpan di `database/data/indonesia-school-regions.json` sebagai fallback jika API wilayah sedang tidak tersedia. Pencarian sekolah memakai Dapodik sebagai sumber utama dan `sekolah.devapi.id` sebagai fallback. Hasil wilayah, pencarian, dan detail sekolah di-cache untuk mengurangi permintaan.

Konfigurasi opsional di `.env`:

```env
DAPODIK_BASE_URL=https://dapo.kemendikdasmen.go.id
DAPODIK_API_TOKEN=
DAPODIK_TIMEOUT=12
DAPODIK_VERIFY_SSL=false
SCHOOL_DIRECTORY_FALLBACK_URL=https://sekolah.devapi.id
INDONESIA_REGION_API_URL=https://wilayah.id/api
```

Jika `DAPODIK_API_TOKEN` kosong, aplikasi membaca token publik dari `https://dapo.kemendikdasmen.go.id/env.js` dan menyimpannya sementara di cache server. `DAPODIK_VERIFY_SSL=false` ditujukan untuk Laragon lokal yang belum memiliki CA bundle; gunakan `true` pada server produksi. Pencarian sekolah tetap membutuhkan koneksi internet. Jalankan migrasi terbaru setelah memperbarui source:

```bash
php artisan migrate
php artisan optimize:clear
```

### Jika muncul “Please provide a valid cache path”

Jalankan dari root proyek:

```bash
php scripts/ensure-storage.php
php artisan optimize:clear
```

Untuk Windows tanpa menjalankan script, pastikan folder berikut tersedia dan dapat ditulis:

```text
storage/framework/views
storage/framework/cache/data
storage/framework/sessions
bootstrap/cache
```

Buka `http://127.0.0.1:8000`.

## Akun demo

Semua akun menggunakan kata sandi `RuteAman123!`.

- Siswa: `siswa@ruteaman.test`
- Admin kompatibilitas akun lama: `verifikator@ruteaman.test`
- Admin sekolah: `admin@ruteaman.test`

## Pengujian

```bash
php artisan test
```

Unit test memeriksa contoh Beban Hazard 22, 47, 67, `no_report`, dan batas freshness.

## Catatan routing

`POST /api/routes/compare` memakai OpenRouteService untuk mencari 1–3 alternatif pada graph jalan. Untuk setiap geometri rute, PostGIS mengambil hazard aktif dalam radius 25 meter dan `HazardLoadService` menghitung Beban Hazard.

Isi `ROUTING_API_KEY` pada `.env`. Titik asal hanya dikirim untuk satu permintaan routing, tidak disimpan ke database, audit log, atau riwayat perjalanan.

UI menampilkan dua dimensi secara netral:

- waktu dan jarak dari routing engine;
- Beban Hazard dari laporan komunitas.

Sistem tidak menamai alternatif sebagai “rute paling aman” dan tidak mengubah urutan provider berdasarkan klaim keamanan.

## Tabel

Tabel produk (termasuk `saved_routes`): `users`, `schools`, `hazard_types`, `hazards`, `hazard_reports`, `hazard_confirmations`, `verifications`, `escalations`, `weather_snapshots`, dan `audit_logs`.

Tabel `password_reset_tokens`, `jobs`, `job_batches`, dan `failed_jobs` merupakan infrastruktur Laravel untuk reset kata sandi dan queue.


## Demo terbaru sesuai skrip

Baca **docs/DEMO_SETUP.md** sebelum demo. Source memuat skrip asli dan versi terkoreksi agar tidak menjanjikan hasil audit, privasi, atau fitur yang belum tersedia.

Untuk database yang sudah ada (jangan gunakan migrate:fresh):

```bash
php artisan migrate
php artisan optimize:clear
php artisan storage:link
php artisan ruteaman:demo-reset --yes
npm run build
php artisan serve
```

Perintah reset hanya untuk APP_ENV=local/testing, menargetkan data akun demo khusus; gunakan database demo terpisah. Tidak mereset database penuh. Akun demo: `siswa_1@ruteaman.test`, `siswa_2@ruteaman.test`, `siswa_3@ruteaman.test`, `admin_a@ruteaman.test`; password `RuteAman123!`.

Rute tersimpan tersedia di `/api/routes/saved?school=<id>` dan otomatis dimuat setelah memilih sekolah pada peta. Tidak memerlukan ROUTING_API_KEY. Feed publik: `/api/feed/hazards.geojson` (opsional `?school=<id>`).

Produksi: aktifkan GD/WebP, gunakan HTTPS dan SSL verify, jalankan `php artisan schedule:run` tiap menit. Data cuaca demo manual berlaku 8 jam; reset sebelum demo. Integrasi BMKG otomatis, cache tile offline, pengiriman WhatsApp otomatis, dan integrasi pemerintah dua arah belum tersedia.


## Perbaikan direktori v4.1

Sekolah/madrasah tidak selalu muncul pada API Dapodik. Jika Dapodik mengembalikan daftar/detail kosong, server sekarang mencoba direktori cadangan, baik untuk pencarian maupun saat submit pendaftaran. Kasus MAN 2 REMBANG (20363192), Lasem, Kabupaten Rembang, Jawa Tengah sudah diuji dengan respons API nyata.

Setelah mengganti source, pertahankan `.env` dan database; jalankan:

```bash
php artisan optimize:clear
php artisan cache:clear
npm run build
```

Tidak ada migrasi baru khusus patch v4.1. Jika datang dari versi sebelum v4.0, tetap jalankan migrasi v4 terlebih dahulu. Hasil pencarian lama yang kosong tidak lagi dipakai; NPSN dan wilayah tetap diperiksa ulang sebelum sekolah diimpor.


## Perbaikan register 429 v4.2

Pencarian sekolah, login, daftar, lupa password, routing, dan laporan kini memakai counter throttle terpisah. Angka limit tetap sama; proteksi spam tidak dimatikan. Jika daftar benar-benar melewati 6 POST per menit, form menampilkan pesan waktu tunggu, bukan halaman putih 429. Input non-password dipertahankan; password harus diisi ulang. JSON tetap 429 dengan Retry-After.

Setelah mengganti source, jangan mengganti `.env` atau mereset database:

```bash
php artisan optimize:clear
php artisan cache:clear
npm run build
php artisan serve
```

Tidak ada migrasi baru khusus v4.2. Tunggu sesuai pesan bila mengirim formulir terlalu sering. Jangan menghapus middleware throttle sebagai solusi.


## Perbaikan panel landing v4.3

Panel pencarian pada batas hero dan section berikutnya tidak lagi tertutup. Urutan stacking context section diperbaiki; teks serta tombol form berada di atas canvas Liquid Glass. Tampilan mobile dan fallback tetap dipertahankan.

Sesudah mengganti source (pertahankan `.env`/database):

```bash
php artisan optimize:clear
npm run build
```

Refresh browser dengan Ctrl+F5. Tidak diperlukan migrasi atau reset database.


## Input sekolah landing v4.4

Kolom “Ke sekolah” kini kosong dan dapat diketik; nama demo tidak lagi dipaksakan. Daftar saran memuat sekolah di database lokal. Ketik/pilih nama lengkap atau NPSN; sekolah itu dipakai sebagai tujuan peta. Nama yang belum tersedia atau ambigu menampilkan pesan, bukan memilih sekolah demo secara diam-diam. Sekolah hasil pendaftaran tersedia setelah impor berhasil.

Sesudah mengganti source: `php artisan optimize:clear`, `npm run build`, kemudian Ctrl+F5. Pertahankan `.env` dan database; tidak ada migrasi baru.


## Pilihan sekolah register v4.5

NPSN pilihan tidak lagi bergantung hanya pada hidden field: pilihan dikonfirmasi disimpan dalam state, dipulihkan saat submit, dan dipertahankan setelah error validasi. Setelah memilih sekolah, gunakan **Ganti** untuk mencari ulang; mengubah wilayah memang membatalkan pilihan agar sekolah tidak masuk ke wilayah yang salah.

Backend dapat memulihkan NPSN yang kosong dari field cadangan / query NPSN lengkap, tetapi selalu memverifikasi sekolah dan wilayah melalui direktori. Pilihan belum siap diblokir sebelum POST; tombol tetap bisa dipakai sesudah dilengkapi.

Setelah mengganti source, pertahankan `.env` dan database:

```bash
php artisan optimize:clear
php artisan cache:clear
npm run build
```

Restart server, kemudian Ctrl+F5. Tidak ada migrasi baru atau reset database.

## v4.6 — Panduan GPS Android / Chrome
Halaman peta dan laporan menampilkan panduan Lokasi/GPS. Permintaan lokasi tetap satu kali, hanya setelah pengguna menekan tombol. Notifikasi membedakan izin ditolak, lokasi tidak tersedia, timeout, dan akurasi rendah. Browser tidak dapat menyalakan atau mendeteksi sakelar GPS Android secara langsung.

Akses `http://IP-LAPTOP:8000` dari HP dapat dipakai untuk melihat peta, tetapi geolocation memerlukan secure context (umumnya HTTPS dengan sertifikat yang dipercaya). Mengaktifkan GPS saja tidak mengatasi pembatasan HTTP. Gunakan pilihan titik manual di peta untuk pengujian LAN; jangan menonaktifkan keamanan Chrome. Setelah mengganti source, jalankan `npm run build` dan `php artisan view:clear`, lalu muat ulang halaman.

## v5.0 — Menu Akun dan Profil
Bottom nav opak dan menu Akun kini membuka sheet/popover. Profil, avatar privat dengan cropper, pengaturan, notifikasi laporan, keamanan, ekspor/hapus akun, bantuan, dan manajemen pengguna sekolah telah ditambahkan. Lihat `docs/ACCOUNT_SETUP.md` untuk upgrade **tanpa menghapus database lama**, konfigurasi mail/GD/consent, dan batas tema gelap. Hasil pengujian tercatat di `docs/ACCOUNT_QA.md`.

## v5.1 — Floating bottom navigation
User requested the floating rounded dock from their first reference, not the full-width edge-attached bar. Restored 12px side/bottom gaps, 22px rounded dock, subtle shadow, and a full teal active-item pill with white icon/label. Kept an opaque surface and 88px + safe-area reserved clearance so the dock does not overlap forms. Account sheet/popover, role handling, and backend behavior unchanged. This is a visual update, not the pending MariaDB hosting compatibility patch.

## v5.2 — Dashboard / beranda
Added a public Dashboard shortcut to route home as the first floating-nav item: Dashboard · Peta · Lapor · Laporanku · Akun. Also added Dashboard to signed-in account menus and the guest sheet. Dashboard Sekolah remains separately named and protected for staff. Updated five-column dock spacing/compact labels while retaining floating shape, active teal pill, touch targets, and content clearance. Hosting MariaDB compatibility remains pending; no backend/database changes in this visual update.

## Perbaikan konsol admin: dua tingkat, audit rinci, dan sidebar ikon

Lihat [panduan patch](docs/ADMIN_CONSOLE_REPAIR_SETUP.md) dan [laporan QA/keputusan](docs/ADMIN_CONSOLE_REPAIR_REPORT.md). Tingkat baru hanya Admin Utama dan Admin Sekolah; enum dan hak efektif tingkat lama dipertahankan selama transisi. Owner mengelola seluruh peran di `/admin/users`.
