# RuteAman v7.2 — MariaDB 10.11 / cPanel

Paket ini menyesuaikan source v7.1 untuk MariaDB 10.11 tanpa PostGIS. Target hosting yang diinformasikan: MariaDB **10.11.19**. Pengujian lokal: MariaDB **10.11.18**, PHP **8.4.24**, Laravel 13. Server hosting pengguna belum diuji langsung.

## 1. Persiapan

- Backup source, `.env`, database, dan seluruh unggahan sebelum mengganti source.
- PHP 8.3+ (8.4 direkomendasikan) dengan PDO MySQL, mbstring, XML/DOM, ctype, fileinfo, OpenSSL, tokenizer, dan GD dengan WebP. CLI PHP dan PHP domain harus sama-sama memenuhi persyaratan.
- MariaDB 10.11 dengan tabel InnoDB dan dukungan geometry/spatial bawaan. **Tidak perlu PostgreSQL/PostGIS atau akses root.**
- Akun database membutuhkan izin tabel, index, SELECT/INSERT/UPDATE/DELETE dan ALTER; tidak perlu CREATE EXTENSION.
- Buat database dan user melalui **cPanel → MySQL Databases / Database Wizard**, lalu hubungkan user ke database dengan ALL PRIVILEGES hanya untuk database aplikasi tersebut. Simpan nama lengkap termasuk prefix akun cPanel.
- Paket menyertakan `public/build` siap pakai; Node/npm tidak diperlukan di hosting bila asset tidak diubah. Composer tetap dibutuhkan untuk memasang vendor.

## 2. Unggah dengan aman

Ekstrak source ke folder aplikasi, misalnya `/home/akun/ruteaman`. Arahkan Document Root domain/subdomain ke `/home/akun/ruteaman/public`, bukan ke root proyek. Jangan menaruh `.env`, `vendor`, `storage` privat atau seluruh source di document root yang bisa diakses publik.

Jika hosting tidak memungkinkan penggantian Document Root, minta penyedia hosting mengarahkannya ke `public`; jangan membuka seluruh folder proyek sebagai jalan pintas. `storage` dan `bootstrap/cache` harus dapat ditulis akun PHP tanpa permission 777.

## 3. Konfigurasi `.env`

Untuk instalasi baru, salin `.env.example` menjadi `.env`. Untuk upgrade, **pertahankan `.env` dan APP_KEY lama**. Isi nama database dan kredensial yang sebenarnya secara lokal:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://domain-anda.example
DB_CONNECTION=mariadb
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=prefix_nama_database
DB_USERNAME=prefix_nama_user
DB_PASSWORD="ISI_PASSWORD_DATABASE_ANDA"
SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=database
SESSION_SECURE_COOKIE=true
DAPODIK_VERIFY_SSL=true
```

`DB_CONNECTION=mysql` juga didukung saat server sebenarnya MariaDB, tetapi `mariadb` direkomendasikan. Kedua driver menggunakan ekstensi PDO MySQL. Jangan memakai `pgsql` untuk server MariaDB. Jika `.env` lama berisi `DB_URL`/`DATABASE_URL` yang mengarah ke server lama, hapus/nonaktifkan sesuai konfigurasi Laravel; `DB_URL` dapat mengalahkan DB_HOST dan kredensial terpisah.

Isi SMTP di `.env` bila ingin mengirim undangan admin. `MAIL_MAILER=log`/`array` tidak mengirim email; panel menyediakan link undangan satu kali yang harus ditangani privat. Jangan mengunggah `.env` atau password ke chat.

## 4. Jalankan dari Terminal cPanel

Masuk ke **root proyek**, bukan folder `public`. Pastikan `php -v` menggunakan PHP yang kompatibel; bila belum, gunakan binary PHP 8.3/8.4 yang disediakan hosting untuk setiap perintah di bawah.

```bash
composer install --no-dev --optimize-autoloader
php scripts/ensure-storage.php
php artisan optimize:clear
# Instalasi BARU saja; jangan mengganti APP_KEY aplikasi yang sudah digunakan:
php artisan key:generate
php artisan migrate --force
php artisan storage:link
php artisan optimize
```

Saat upgrade, lewati `key:generate`. Migration wajib dijalankan **sebelum** menggunakan pengelolaan admin atau Owner, karena versi ini menambahkan tabel infrastruktur `admin_transaction_locks` dengan satu baris pengunci. Tidak ada tabel domain baru.

Buat Owner pertama hanya bila belum ada:

```bash
php artisan ruteaman:owner:create
```

Jika terminal hosting tidak mendukung input password tersembunyi, opsi eksplisit berikut tetap tersedia dari patch sebelumnya:

```bash
php artisan ruteaman:owner:create --show-password
# Pemulihan Owner yang sudah ada:
php artisan ruteaman:owner:reset --show-password
```

Mode tersebut menampilkan password saat diketik: gunakan terminal privat, jangan merekam atau membagikan layarnya. Password disimpan sebagai hash. Akun admin baru menggunakan alias login domain konfigurasi dan email kontak asli; gunakan misalnya `sekolah.pengelola@ruteaman.my.id`, bukan kata alias yang dicadangkan seperti `admin`.

Jika percobaan migration lama gagal dan meninggalkan tabel parsial, jangan langsung menghapus tabel: backup dahulu, lalu gunakan database baru kosong untuk instalasi baru atau periksa status migration dan skema bila ada data penting.

Jangan jalankan `migrate:fresh`, `--seed`, atau DemoSeeder pada produksi. Memilih MariaDB tidak otomatis menyalin data PostgreSQL lama: jika database lama berisi data, ekspor dan konversi data/geometry perlu rencana migrasi terpisah. Jangan menghapus database lama sebelum data baru diverifikasi.

## 5. Scheduler dan queue

Cron cPanel untuk scheduler, dengan path dan binary PHP sesuai hosting:

```cron
* * * * * cd /home/akun/ruteaman && php artisan schedule:run >> /dev/null 2>&1
```

Jika queue database dipakai tanpa worker permanen, ikuti fasilitas hosting untuk menjalankan `php artisan queue:work --stop-when-empty`; hindari worker bertumpuk. Pengiriman undangan admin memakai jalur langsung, bukan menyimpan link mentah ke job queue.

## 6. Verifikasi setelah memasang

1. Buka landing dan `/map`; lihat daftar sekolah dan marker. Peta kosong pada instalasi baru tanpa data sekolah/laporan bukan error database.
2. Login Owner, buat sekolah lewat wizard dengan polygon tertutup dan pin di dalam wilayah. Akun pengelola sekolah berstatus pending sampai menerima undangan.
3. Periksa detail sekolah, koordinat pin, catchment dan pengeditannya.
4. Uji laporan, deteksi duplikat, verifikasi, daftar kondisi, marker persisten dan rute.
5. Pastikan data privat/ditolak tidak muncul pada endpoint publik.
6. Periksa log `storage/logs/laravel.log` secara privat jika ada 500; jangan mempublikasikan log penuh atau APP_KEY.

## 7. Batas teknis

- MariaDB menggunakan POINT/POLYGON dengan SRID 4326 tersimpan pada geometry, tanpa sintaks kolom `SRID 4326` milik MySQL 8. Urutan koordinat adalah longitude lalu latitude.
- Validasi catchment dilakukan di PHP: Polygon satu ring, 4–500 titik termasuk penutup, tidak self-intersection/edge overlap, pin tercakup termasuk batas, luas maksimal 25 km². Luas dihitung pada bola WGS84 pendekatan (bukan luas ellipsoid PostGIS yang identik); polygon melintasi antimeridian tidak didukung untuk catchment sekolah.
- Jarak titik ke segmen rute dihitung pada bola dalam **meter** untuk MariaDB/mysql; PostgreSQL mempertahankan ST_DWithin geography. Nilai dapat sedikit berbeda dari ellipsoid PostGIS.
- Hazard rute dibaca dalam batch 500 dan difilter sebelum batas hasil publik diterapkan. Ini menjaga ketepatan meter dan memori, tetapi dataset nasional yang sangat besar belum dibenchmark; tidak mengklaim performa setara GiST/PostGIS.
- Pada MariaDB, feed memakai COALESCE waktu aktivitas langsung, sehingga tidak bergantung pada kolom generated khusus baseline MySQL. Indeks spatial lokasi dan catchment tetap tersedia.
- Source PostgreSQL/PostGIS dipertahankan, tetapi PostgreSQL dan MySQL 8 asli tidak diuji ulang pada pengerjaan patch ini.
- Tampilan, CSS/JS marker dan asset build tidak diubah. Ini bukan pemasangan langsung ke hosting pengguna.

Lihat `MARIADB_QA.md` untuk hasil pengujian.
