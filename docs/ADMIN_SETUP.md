> **Catatan versi baru:** dokumen ini menyimpan konteks historis. Patch konsol dua tingkat menggantikan pilihan empat tingkat/preset lama. Ikuti `ADMIN_CONSOLE_REPAIR_SETUP.md` dan `ADMIN_CONSOLE_REPAIR_REPORT.md`; akun tingkat lama tidak diubah otomatis.

# Admin berjenjang — RuteAman v7.2

## Persiapan deployment

Versi v7.2 mendukung MariaDB 10.11 tanpa PostGIS; lihat `MARIADB_CPANEL_SETUP.md` dan `MARIADB_QA.md` untuk deployment dan pengujian versi ini. PostgreSQL 16/PostGIS adalah baseline teruji versi v7.0, bukan pengujian ulang versi ini.

```bash
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
# Isi koneksi MariaDB (atau PostgreSQL baseline), APP_URL HTTPS, SMTP dan filesystem sebelum lanjut.
php artisan migrate --force
npm ci && npm run build
php artisan optimize
php artisan ruteaman:owner:create
```

Upgrade instalasi lama: backup DB/file privat, pasang source baru, jangan menimpa `.env`, jalankan migration; **jangan migrate:fresh dan jangan seed data demo ke produksi**. Migration admin v7.0 menambah kolom, backfill status dari `active`, dan memberikan izin baseline kepada admin sekolah/verifikator lama. Versi v7.2 juga menambahkan tabel infrastruktur `admin_transaction_locks` untuk serialisasi transaksi Owner. Unggah avatar/tren/feed nonaktif sesuai default baru; fitur tampilan orang tua sudah dihapus. `settings` dan slug sebelumnya tetap dipertahankan.

Konfigurasi:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://domain-anda.example
ALIAS_EMAIL_DOMAIN=ruteaman.my.id
OWNER_MAX_COUNT=3
INVITE_EXPIRES_HOURS=48
ADMIN_IDLE_MINUTES=20
SESSION_SECURE_COOKIE=true
MAIL_MAILER=smtp
DAPODIK_VERIFY_SSL=true
```

**Alias bukan kotak surat.** Gunakan `sekolah.pengelola@ruteaman.my.id`, bukan `sekolah.admin@...` karena admin termasuk kata cadangan. Semua akun admin baru wajib mempunyai email kontak asli. Alamat alias hanya digunakan untuk login dan kunci password broker. Domain dapat diganti melalui konfigurasi; domain aplikasi dan penyedia email harus Anda siapkan sendiri.

Owner awal dan pemulihan darurat menggunakan CLI interaktif:

```bash
php artisan ruteaman:owner:create
php artisan ruteaman:owner:reset
```

Password ditanya lewat input rahasia, bukan argumen command. Jika ingin teks terlihat di terminal lokal, gunakan `php artisan ruteaman:owner:create --show-password` atau `php artisan ruteaman:owner:reset --show-password`. Mode ini sengaja menampilkan ketikan; jangan merekam terminal, membagikan layar, atau mengirim screenshot password. Tanpa flag, input tetap tersembunyi. Jangan menaruh Owner/password produksi di seeder. Command pemulihan membatalkan sesi dan token lama. Batasi SSH/console hanya kepada operator terpercaya.

## Penggunaan

1. Masuk sebagai Owner → `/admin` → Sekolah dan akun.
2. Wizard: data/pin/GeoJSON → identitas/email kontak → fitur dan izin → tinjau/re-auth.
3. Catchment satu ring Polygon, maksimal 500 koordinat/25 km², mencakup pin; diperiksa `ST_IsValid` dan `ST_Covers`. Upload dapat menggantikan menggambar peta. Bentuk bertumpuk/self-intersection ditolak.
4. Salin undangan dari respons sekali tampil, kirim secara privat. Password minimal 12 karakter, berlaku 48 jam; tautan baru membatalkan yang lama. Alias login tidak berubah saat email kontak diverifikasi lewat halaman akun.
5. Menu Akun → Konsol Admin. Owner/Platform/Sekolah/Staf mendapatkan menu sesuai izin. Verifikator bukan admin dan tetap memakai antrean/dashboard baca-saja.
6. Editor izin: preset, switch, dependency otomatis, ringkasan perubahan, pratinjau menu tanpa impersonasi, Simpan. Server menegakkan grant ceiling dan dependency meskipun JS dimanipulasi.
7. Fitur Sekolah: simpan per sekolah, atau terapkan satu fitur ke seluruh sekolah (hanya cakupan semua). Fitur core membaca peta tidak dimatikan.
8. Jeda sekolah menghentikan pelaporan/konfirmasi serta mutasi akun sekolah. Arsip menghilangkan sekolah dari pilihan publik, mempertahankan data.
9. Undangan/reset, suspend/activate, cabut sesi, hapus anonim, tier dan cakupan tersedia pada akun yang tingkatnya lebih rendah. Tidak dapat mengubah hak/status sendiri. Owner dapat **membuat** Owner lain tetapi tidak dapat mengubah Owner sejajar lewat UI; gunakan CLI untuk pemulihan.
10. Ekspor CSV sekolah/audit tidak memuat identitas pelapor. Feed integrasi opsional `/api/integrations/hazards.geojson?school=ID`; feed internal peta tetap hidup. Halaman lama `/parent/SLUG` sudah tidak tersedia. Metrik 7 hari berupa tabel jumlah kondisi pertama dilaporkan, bukan prediksi.

## Email, sesi, dan log

Undangan dikirim **setelah commit, sinkron**, bukan persisted queue: job database/log dapat menyimpan token mentah. Mailer `log`/`array` sengaja tidak mengirim/menulis undangan. Status UI menyatakan apakah SMTP menerima pengiriman; bukan jaminan email sampai. SMTP asli dan retry operasional harus dikonfigurasi; terbitkan tautan baru untuk mengulang. Notifikasi Owner tidak menyertakan token.

`session_version` membatalkan sesi file/Redis/database pada permintaan berikutnya; sesi database juga dihapus bila tabel tersedia. Idle admin 20 menit, re-auth 10 menit, login 6/menit, re-auth 5/menit, link 5/menit, pembuatan 10/menit. Pengaman Owner menggunakan advisory transaction lock serta row locks; cap menghitung undangan Owner pending juga.

**2FA/TOTP belum tersedia pada proyek ini.** Tidak diklaim aktif atau teruji. Prompt mensyaratkannya bila fasilitas 2FA tersedia; sebelum pemakaian produksi sensitif, tambahkan integrasi TOTP yang diaudit dan uji enrolment/recovery. Jangan menganggap password/re-auth pengganti MFA.

Audit app hanya baca dan event model menolak update/delete. Token/password/URL tidak ditulis ke audit. Batasi hak DB audit melalui role DB saat deployment; aplikasi tidak melindungi dari operator SQL berprivilege. Redaksi/nonaktifkan request/access-log untuk `/invite/*` dan respons link di reverse proxy, APM, tracing, debug toolbar, dan penyedia email; URL path mengandung token. Jangan menggunakan mailer log untuk email admin lainnya yang berisi tautan. HTTPS, cookie secure, SSL verification, dan `APP_DEBUG=false` wajib.

## Pengujian

Gunakan DB khusus `_tests`, jangan database produksi:

```bash
DB_DATABASE=ruteaman_admin_tests php artisan migrate --force
RUTEAMAN_SPATIAL_TESTS=1 DB_CONNECTION=pgsql DB_DATABASE=ruteaman_admin_tests php artisan test
# database kosong berbeda untuk tes proses paralel:
DB_DATABASE=ruteaman_admin_race_tests php artisan migrate --force
DB_DATABASE=ruteaman_admin_race_tests php tests/Concurrency/admin-owner-race.php
```

Suite default memakai SQLite dan melewati tes spasial; hasil default saja bukan bukti pengaman admin. Lihat `ADMIN_QA.md` untuk hasil paket akhir. Lighthouse/axe otomatis tidak menggantikan pemeriksaan keyboard/visual atau pengujian perangkat Android/SMTP nyata.
