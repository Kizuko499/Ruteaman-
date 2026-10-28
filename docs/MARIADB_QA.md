# RuteAman v7.2 — MariaDB QA

## Lingkungan dan cakupan

- Runtime lokal: PHP 8.4.24, MariaDB 10.11.18, Laravel 13.
- Target hosting pengguna yang diinformasikan: MariaDB 10.11.19. Patch release hosting tersebut belum diuji langsung; bukan klaim pengujian pada server pengguna.
- Database uji terpisah untuk suite fitur, race, mutex kosong, upgrade data lama dan instalasi melalui alias mysql. Tidak ada koneksi ke database hosting pengguna.
- UI/asset tidak dirancang ulang atau dibangun ulang. Sebanyak **107 file** `resources/` dan `public/build/` dibandingkan SHA-256 dengan ZIP v7.1, semuanya identik. Tidak dilakukan sesi QA browser visual baru.

## Hasil

| Pengujian | Hasil |
| --- | --- |
| Migration instalasi baru, driver mariadb | Semua 10 migration berhasil |
| Migration instalasi baru, driver mysql pada server MariaDB | Semua 10 migration berhasil |
| Seluruh unit/feature suite, driver mariadb | 91 passed, 822 assertions, 0 skipped |
| Seluruh unit/feature suite, driver mysql pada MariaDB | 91 passed, 822 assertions, 0 skipped |
| Suite dasar tanpa spatial | 20 passed, 71 skipped sesuai flag, 169 assertions |
| JavaScript marker | 9 passed, 0 failed |
| Owner cap race lintas proses | PASS: hanya satu undangan tercipta pada sisa satu slot |
| Perlindungan Owner aktif terakhir | PASS |
| Mutex pada kondisi belum ada Owner, snapshot dibaca sebelum lock | PASS: satu created, satu blocked |
| Upgrade dengan data lama | PASS: backfill publikasi, 3 actor unik, overlap tidak dihitung ganda, penolakan tetap privat, tanggal historis tetap |
| Compile seluruh Blade view | PASS |
| Sintaks PHP | Seluruh file app/database/config/tests/routes lulus |

Suite mencakup provisioning/edit polygon sekolah, longitude/latitude/SRID round-trip, containment, distance buffer route 50 meter (dekat masuk, jauh keluar), filter sebelum limit, direktori sekolah via fixture HTTP, duplikasi laporan, verifikasi, foto sanitization, feed/pagination, marker historis/privasi, izin/cakupan/hierarki admin, undangan dan CLI Owner dengan input password terlihat/tersembunyi. Geometri unit mencakup ring terbalik, pin di tepi, bowtie, titik duplikat, pin di luar, luas berlebih, JSON invalid dan jarak segmen/endpoint.

## Perubahan inti

1. `SpatialSql`: WKT dengan dua argumen untuk MariaDB/PostGIS, axis-order hanya untuk MySQL asli; deteksi MariaDB juga saat diakses lewat driver mysql; ekstraksi X/Y yang benar.
2. Schema MariaDB memakai POINT/POLYGON tanpa sintaks batas SRID kolom MySQL 8; setiap insert aplikasi menggunakan SRID 4326, spatial index tetap ada.
3. `GeoGeometry`: validasi polygon dan area pendekatan bola dalam meter persegi, serta jarak titik-ke-segmen rute dalam meter; bukan ST_Distance Cartesian derajat.
4. Sekolah wizard/provisioning, impor direktori, laporan, seed/fixture, map dan detail kondisi memakai query yang portabel.
5. Riwayat publikasi menghitung union actor unik lewat correlated subqueries yang didukung MariaDB, bukan correlated derived-table UNION lama.
6. Transaksi Owner diserialisasi melalui satu baris `admin_transaction_locks` pada MariaDB/mysql; PostgreSQL mempertahankan advisory transaction lock. Penghitungan Owner untuk batas/last-owner memakai locking read untuk menghindari snapshot lama pada repeatable-read.
7. Pada server MariaDB, kedua driver memakai COALESCE activity; tidak mengasumsikan generated column yang mungkin hanya dibuat baseline mysql.
8. Default `.env.example` menjadi mariadb dengan password database kosong yang harus diisi saat setup; engine mysql/mariadb InnoDB eksplisit.
9. Source password-visible patch tetap disertakan; frontend/marker tidak berubah.

## Reproduksi lokal

Pasang dependensi dev. Buat database **kosong, terpisah, disposable** berakhiran `_tests`, set koneksi dan password melalui environment privat; jangan gunakan database produksi.

```bash
# DB_HOST/DB_USERNAME/DB_PASSWORD/DB_DATABASE harus diisi untuk database uji:
DB_CONNECTION=mariadb php artisan migrate --force
RUTEAMAN_SPATIAL_TESTS=1 DB_CONNECTION=mariadb php artisan test
# Driver alternatif di server MariaDB yang sama:
RUTEAMAN_SPATIAL_TESTS=1 DB_CONNECTION=mysql php artisan test
npm run test:markers
```

Race test menggunakan database lain yang telah dimigrasi dan **belum berisi Owner**. Jalankan masing-masing di database baru terpisah, karena script mempertahankan fixture setelah selesai:

```bash
DB_CONNECTION=mariadb DB_DATABASE=ruteaman_race_tests php tests/Concurrency/admin-owner-race.php
DB_CONNECTION=mariadb DB_DATABASE=ruteaman_mutex_tests php tests/Concurrency/admin-mutex-race.php
```

## Batas verifikasi

- Bukan deploy atau smoke test pada cPanel/domain pengguna; PHP CLI/domain, izin filesystem, Composer/SMTP, cron dan API routing harus diverifikasi di hosting.
- PostgreSQL/PostGIS dan MySQL 8 asli belum diuji ulang pada patch ini. Perbedaan sintaksnya dipertahankan, bukan bukti kompatibilitas runtime ulang.
- Luas/jarak pendekatan bola tidak identik dengan ellipsoid PostGIS; catchment antimeridian/holes tidak didukung.
- Batch route scan dan query COALESCE feed belum dibenchmark pada dataset nasional besar; tidak menyatakan performa setara indeks geography/GiST.
- Routing/direktori/BMKG eksternal yang memakai fixture/mock dalam tes bukan bukti API layanan langsung. Key dan jaringan tetap diperlukan sesuai provider aplikasi.
- ZIP source baseline belum memiliki composer.lock; Composer akan melakukan resolusi dependency. Simpan lock dan vendor hasil pemasangan deployment Anda agar repeatable.
