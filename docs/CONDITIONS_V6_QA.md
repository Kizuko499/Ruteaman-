# Hasil QA — RuteAman v6.0

## Lingkungan
PHP 8.4.24, PostgreSQL 16 + PostGIS, Node 24, Chromium lokal headless. Database untuk suite backend berbeda dari database fixture visual/browser. Tidak menguji/mengubah hosting pengguna. Snapshot berasal dari Blade dan CSS build asli; data pratinjau bersifat simulasi dan diberi label.

## Hasil terukur
- PHPUnit: **56 tes lolos, 576 assertions**, termasuk regresi akun/direktori/peta/alur demo dan publikasi baru.
- Node fake timers: **8 tes lolos** (120 detik, jeda hover/fokus/hidden/manual, sisa waktu, satu timer, retry sekali, single hazard, error, kembali/fallback).
- Browser nyata: **28 pemeriksaan lolos**, meliputi 21 kombinasi halaman/ukuran (360, 768, 1280 px), append/fokus, kembali/restoration, lightbox trap/Esc/fokus kembali, sticky pada scroller mobile, offline DOM, Next/Jeda, dan deep-link peta.
- Real-mounted rotator dengan jam virtual Chromium: **5 pemeriksaan lolos** (120 detik, jeda 240 detik, lanjut, gagal mempertahankan kartu, backoff 240 detik).
- Axe: **0 pelanggaran** pada audit WCAG2 A/AA dan WCAG2.1 AA yang dijalankan untuk 21 kombinasi tadi. Tidak mengklaim sertifikasi seluruh WCAG atau menggantikan audit manusia.
- Lighthouse Accessibility lokal: **Beranda 100, Daftar 100, Detail 100**. Kategori performance tidak diukur, sehingga tidak ada klaim skor performance/CLS/LCP produksi.
- Media: tes batas 640/1280, **<=120/200 KiB**, format WebP + fallback JPEG, tanpa string EXIF uji, tidak tersimpan di disk public, dan endpoint private tidak membuka new/rejected. Rute OG tidak memakai foto asli.
- Cache: ETag/304, invalidasi perubahan status, throttle 429, filter/keyset, wrap/single/empty/missing-after, catchment dan related <=1km diuji.
- Privasi: new yang kemudian expired masih privat; `/h/` lama ditutup untuk kondisi yang tak terlihat; foto lama dipindahkan dan di-encode ulang; actor/notes/meta audit tidak dibocorkan; eskalasi opt-in.

## Pemeriksaan visual manusia
Beranda/kartu, grid tiga/dua/satu kolom, artikel, rincian, peta nyata, modal foto, navigasi mengambang, keadaan kosong/surut/kedaluwarsa/offline/404 diperiksa. Temuan diperbaiki: glyph yang tampil kotak diganti SVG, kontrol select diperjelas, hirarki h2/h3 dirapikan, teks beranda tidak berada di bawah dock, dan tombol Bagikan bar artikel tidak ditutupi tombol Akun desktop. Zoom mini-map berlabel Indonesia dan target 44px; lokasi juga tersedia sebagai teks.

## Menjalankan ulang
Di root proyek, pada database PostGIS **khusus tes**, jalankan migrasi dahulu lalu:

```bash
RUTEAMAN_SPATIAL_TESTS=1 DB_CONNECTION=pgsql DB_DATABASE=nama_database_tes \
DB_HOST=127.0.0.1 DB_PORT=5432 DB_USERNAME=user_tes DB_PASSWORD= \
php artisan test
node --test tests/Frontend/conditions.test.cjs
```

Gunakan cara privat untuk mengatur DB_PASSWORD di lingkungan sendiri, bukan menuliskan kata sandi dalam chat atau history shell. Tanpa RUTEAMAN_SPATIAL_TESTS=1, tes spatial sengaja skipped; itu bukan bukti backend publikasi lolos.

Harness browser disertakan di tests/Browser. Untuk menjalankannya, pakai laptop/CI (bukan shared hosting), Playwright + Chromium dan axe-core yang terpasang sebagai alat QA. Pakai database local/testing yang bersih, bukan produksi:

```bash
php tests/Browser/seed-publication.php
php artisan serve --host=127.0.0.1 --port=8010
# terminal lain, dari root proyek:
node tests/Browser/conditions.browser.cjs
node tests/Browser/rotation.browser.cjs
```

PUBLICATION_QA_URL, PUBLICATION_QA_ARTIFACTS, CHROME_PATH bisa diatur. DEFAULT_SCHOOL_SLUG pada lingkungan QA harus kosong atau menunjuk sekolah demo A agar fixture tes konsisten. Hasil browser disimpan di .qa-artifacts, tidak disertakan sebagai data aplikasi. Seeder browser hanya local/testing, mengubah fixture qa-kondisi-demo-* dan akun demo, tidak boleh dijalankan pada database pengguna sebenarnya.

## Tidak diklaim
Kompatibilitas MySQL/MariaDB, performa hosting, pengujian pada perangkat Android fisik, penghapusan salinan CDN/backup eksternal, offline navigasi ke halaman yang belum dibuka, validitas faktual foto demo, serta kesiapan production tanpa review sekolah.
