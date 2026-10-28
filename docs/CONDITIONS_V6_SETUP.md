> Catatan v7.2: dokumen ini adalah riwayat publikasi kondisi v6.0. Untuk hosting MariaDB/cPanel gunakan `MARIADB_CPANEL_SETUP.md`. Patch MariaDB sudah termasuk; PHP tetap wajib >=8.3 (8.4 direkomendasikan).

# RuteAman v6.0 — publikasi kondisi

## Yang berubah
- Beranda: satu kartu SSR terbaru, rotasi stateless 120 detik, Jeda/Putar, Berikutnya, posisi, ringkasan jumlah/kategori per sekolah dan tautan `/kondisi`.
- Daftar: 3/2/1 kolom pada desktop/tablet/mobile, filter GET yang bisa dibagikan, keyset cursor terenkripsi, tombol Muat lebih banyak dan pemulihan gulir/jumlah kartu.
- Detail: judul/meta/status, konfirmasi melalui alur yang sudah ada, bagikan/salin, foto dan native modal lightbox, ringkasan faktual, rincian, bukti, linimasa publik, kondisi terdekat <=1 km, peta Leaflet tanpa meminta GPS, sidebar dan bar ketika judul melewati viewport.
- Navigasi lima menu mengambang dipertahankan. Beranda mobile sekarang juga memakai area gulir di atas zona dock, agar teks tidak tertutup.
- Metadata OG adalah gambar teks kategori/status/umur, bukan foto manusia. Default `noindex,nofollow`.

## Penyesuaian terhadap prompt
1. Enum asli dipertahankan: `new` = unconfirmed; `confirmed` = community_confirmed; `disputed` = perlu ditinjau/pending verification; `verified` = terverifikasi; `resolved` = surut; `expired` = kedaluwarsa. `rejected` di skema lama dicatat sebagai keputusan verifikasi terbaru dan status resolved; keputusan rejected diperiksa sebelum visibilitas. Tidak membuat enum/tabel baru.
2. Kondisi new tidak tampil di beranda, daftar, related atau umpan tamu. Detail new hanya untuk akun aktif dalam catchment yang sama. Peta untuk anggota aktif masih mendukung laporan new di area sendiri agar konfirmasi awal dapat berlangsung. Feed tamu dan analisis rute mengecualikannya.
3. Expired yang belum pernah memiliki bukti publik tetap privat bagi tamu. Rekonfirmasi still_there oleh anggota independen mengaktifkan ulang sebagai disputed, tidak otomatis mengembalikan verifikasi lama. Resolved/rejected tidak dapat diaktifkan ulang lewat tombol ini.
4. Catchment memakai layer geografis bersama S1 yang sudah ada. Hazard pada wilayah beririsan dapat terlihat pada kedua sekolah. Related dibatasi catchment dan jarak metrik <=1000 m; pemutus seri activity_at/id.
5. `schools.public_slug` ditambah tanpa tabel baru; sekolah lama mendapat `sekolah-{id}`, sekolah baru ULID publik. Endpoint juga menerima NPSN atau ID numerik untuk kompatibilitas. Tanpa DEFAULT_SCHOOL_SLUG, fallback sekolah dengan ID paling awal. Tetapkan default secara eksplisit untuk produksi; tidak menciptakan sekolah/demo sebagai fallback.
6. Struktur beranda lama (hero, pencarian, informasi, fitur, FAQ) dipertahankan. Landing tetap di controller PublicMapController; komponen kondisi dipisah, CSS digabung dalam conditions.css, bukan delapan file kecil. JS fitur dipecah dan dimuat per halaman.
7. Offline berarti DOM halaman yang sedang terbuka tetap bisa dibaca, dengan banner dan waktu data terakhir. Tidak ada Service Worker/offline navigation cache; halaman yang belum terbuka tidak dijanjikan tersedia offline. Tersimpan di sessionStorage hanya posisi gulir dan jumlah kartu daftar. Preferensi tampilan akun lama bukan data lokasi.
8. Pagination tahan sisipan hazard baru di atas cursor; jika activity_at sebuah kondisi lama berubah, posisinya memang dapat berubah. UI menolak duplikat URL saat append. Tidak mengklaim snapshot historis tanpa tabel/snapshot.
9. Data model publik hanya berisi bidang yang diizinkan. Account menu untuk pengguna masuk tetap memuat identitas akun sendiri, bukan identitas pelapor kondisi. Notes, descriptions, actor IDs, reviewer names tidak digunakan sebagai konten artikel/timeline.
10. Tidak ada tabel baru. Baseline proyek sebelumnya sudah 11 tabel domain (termasuk saved_routes), bukan <=10. Migrasi ini hanya kolom/index.

## Prasyarat dan peringatan hosting
Laravel 13/PHP >=8.3, GD/WebP, database spatial. Pengujian versi ini memakai PHP 8.4 dan PostgreSQL 16 + PostGIS.
**Catatan historis v6.0: hosting saat itu menggunakan PHP CLI/domain 8.2 dan MariaDB 10.11. Versi v7.2 sudah mencakup patch MariaDB, tetapi PHP 8.2 tetap tidak memenuhi Laravel 13. Pilih PHP >=8.3 untuk CLI dan domain sebelum memasang paket v7.2.**
MySQL 8 memiliki jalur SQL di kode, tetapi v6.0 belum dites terhadap MySQL maupun MariaDB. Hasil PostGIS tidak dianggap bukti kompatibilitas hosting.

## Konfigurasi
Tambahkan/ubah di .env server, di luar document root:

```dotenv
FEATURED_ROTATE_SECONDS=120
DEFAULT_SCHOOL_SLUG=sekolah-1
CONDITION_RESOLVED_HOURS=24
PUBLIC_HAZARD_INDEXABLE=false
```

`sekolah-1` hanya contoh: pakai slug sekolah yang benar. Lihat ID/NPSN/slug di database sendiri, jangan menebak sekolah produksi. `schools.settings` JSON opsional:

```json
{"featured_rotate_seconds":120,"public_escalation_status":false}
```

Gunakan APP_ENV=production, APP_DEBUG=false, APP_URL=https://domain-kamu. SSL harus dipercaya browser (bukan self-signed), juga diperlukan GPS dan Clipboard/Web Share. Konfigurasi SMTP sungguhan tetap diperlukan untuk email akun; log mailer tidak mengirim email.

## Upgrade pada server yang sudah kompatibel
Backup database dan file terlebih dahulu; uji staging. Jangan timpa .env, upload, vendor atau konfigurasi server dengan contoh.

```bash
php artisan down
composer install --no-dev --prefer-dist --optimize-autoloader
php artisan migrate --force
php artisan ruteaman:privatize-hazard-photos
php artisan optimize:clear
php artisan config:cache
php artisan view:cache
php artisan up
```

Aset `public/build` sudah ada dalam paket. Jika membangun ulang: `npm ci && npm run build`. Pastikan hanya direktori `public` menjadi webroot; source/.env/storage privat harus di luar public_html. Zip tidak berisi vendor/node_modules/secret/data pengguna.

**Foto lama:** command menyalin, membuang metadata, memverifikasi hasil tulis, lalu menghapus file dari disk public. Endpoint baru tidak fallback ke public disk. Jika command gagal, jangan membuka situs; selesaikan migrasi foto dahulu. Periksa dan hapus salinan lama di public_html/storage/hazards, CDN, mirror publik dan cache browser/proxy. Mengganti kode saja tidak menarik kembali foto yang telah diunduh. Backup file privat harus di lokasi yang tidak dapat diakses web.

Foto baru tersimpan pada disk `hazard_photos` (`storage/app/private/hazard-photos`, serve=false). `/media/h/{slug}/{index}?w=640|1280` memeriksa visibilitas setiap permintaan, membuat WebP dan fallback JPEG lewat picture (format=jpeg), tanpa metadata, maksimal 120/200 KiB, dan mengirim private,no-store. Derivatif bisa menurun ukuran/quality untuk memenuhi batas, tidak selalu tepat 640/1280 px. Resolusi input dibatasi 12 megapiksel sebelum decode. OG memakai font Liberation Sans dengan lisensi yang disertakan.

Jalankan cron scheduler Laravel seperti sebelumnya untuk expire job. Layanan aktif juga memeriksa TTL tanpa menunggu cron. Sesudah import/perubahan raw SQL, jalankan `php artisan cache:clear`; perubahan model Eloquent sudah menginvalidasi cache feed.

Jangan menjalankan DemoSeeder di produksi. Data/foto demo hanya ilustrasi, bukan audit lapangan.

## API
- GET /api/hazards/featured?school={slug}&after={hazard_slug}
- GET /api/conditions?school={slug}&kategori={type_code}&tampil=aktif|surut&limit=12&cursor={token}
- GET /api/conditions/{hazard_slug}
- GET /api/conditions/{hazard_slug}/related
- GET /media/h/{hazard_slug}/{0..5}?w=640|1280&format=webp|jpeg
- GET /media/og/{hazard_slug}

Feed/featured cache server 30 detik, invalidasi versi pada perubahan model, ETag/304, throttle publik per IP. HTML berakun/detail tidak dicache bersama. Unknown school/slug menghasilkan 404; cursor/filter/limit salah 422 pada API. Limit maksimum 24.

## Pemeriksaan produksi sebelum publikasi
- Sertifikat valid, source/env/backup/foto asli tidak di webroot; semua salinan foto lama sudah privat.
- Default sekolah benar; kategori/TTL/catchment bukan data demo.
- Tamu tidak dapat membuka new/rejected, termasuk melalui rute lama `/h/{slug}` dan media.
- Data label lokasi hanya jalan/patokan umum, bukan alamat rumah/identitas siswa.
- Lapor, konfirmasi, verifikasi, password dan email diuji memakai akun staging; noindex sesuai kebijakan sekolah.
- Ukuran foto/network/performa diuji lagi pada hosting dan koneksi pengguna. Skor lokal bukan SLA produksi.
