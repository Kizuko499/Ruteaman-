> **Catatan versi baru:** dokumen ini menyimpan konteks historis. Patch konsol dua tingkat menggantikan pilihan empat tingkat/preset lama. Ikuti `ADMIN_CONSOLE_REPAIR_SETUP.md` dan `ADMIN_CONSOLE_REPAIR_REPORT.md`; akun tingkat lama tidak diubah otomatis.

# QA — Admin Berjenjang RuteAman v7.0

## Lingkungan dan hasil

Pengujian dijalankan pada source paket akhir dengan PHP 8.4, PostgreSQL 16/PostGIS, Vite 8 dan Chromium headless lokal. Database QA terpisah; bukan produksi.

| Pemeriksaan | Hasil | Cakupan |
| --- | --- | --- |
| Suite PHPUnit lengkap dengan spasial aktif | 78 tes lulus, 718 assertion | Suite lama + 22 tes admin baru |
| Owner cap konkurensi | Lulus | Dua proses PHP; tepat satu penciptaan diterima pada slot terakhir |
| Last Owner konkurensi | Lulus | Dua upaya suspend; keduanya ditolak |
| Browser desktop/mobile | 35 pemeriksaan, 0 pageerror | 15 route × 1440/390 px, lalu lima alur interaktif |
| axe WCAG 2 A/AA + 2.1 AA | 16 scan, 0 violation terdeteksi | Delapan halaman konsol × dua ukuran |
| Lighthouse accessibility | 100/100 | Halaman `/admin` terautentikasi saja |
| Build frontend produksi | Lulus | Vite build, asset disertakan |
| Compile Blade dan lint PHP | Lulus | Semua Blade; app/config/routes/database/tests |
| Perbaikan breadcrumb akhir | Lulus | Undangan mobile memiliki gap 24 px dan judul membungkus |

Angka Lighthouse/axe bukan sertifikasi WCAG dan tidak mencakup semua halaman aplikasi.

## Pengujian backend

`tests/Feature/TieredAdminTest.php` mencakup:

- Hierarki tingkat lebih rendah, penolakan peer/self-edit, last Owner, cap termasuk pending Owner.
- Grant ceiling, dependency child/parent, akses lintas sekolah/IDOR dan mass assignment profil.
- Re-auth, idle expiry, revoke sessions melalui session_version, status pending/suspended.
- Alias cadangan/domain/unik, email kontak, expiry undangan, token sekali pakai, tautan baru membatalkan token lama, tidak ada token dalam audit.
- Wizard sekolah/account dalam transaksi; pin harus di dalam catchment, self-intersection dan koordinat tidak lengkap ditolak, tidak menambah tabel.
- School pause/archive, fitur dimatikan langsung, core public map tidak rusak, penerapan massal sesuai cakupan.
- Sekolah arsip tidak muncul pada pencarian direktori nasional dan request pendaftaran langsung juga ditolak.
- Audit baca-saja, scope dan izin ekspor; CSV, feed opsional, parent read-only, metrics nyata.
- Buka kembali hazard resolved/expired menambahkan keputusan needs_evidence yang menggantikan rejection lama; Platform dapat mengelola pengguna di sekolah dalam cakupan.
- Editor izin mempertahankan key permission dan placeholder alias Blade benar.

`tests/Concurrency/admin-owner-race.php` menjalankan dua proses independen. Script membutuhkan DB khusus kosong dengan nama berakhiran `_tests`; bukan untuk produksi. Gunakan database baru untuk pengulangan.

## Browser, keyboard, dan visual

Route yang dirender dan diperiksa overflow horizontal: ringkasan, daftar/tambah/edit admin, daftar/tambah/edit sekolah, fitur sekolah, referensi izin, audit, hazard, metrics, feed-settings, dashboard sekolah, antrean verifikasi. Lebar desktop 1440 px dan mobile 390 px.

Alur interaktif nyata: Menu mobile/Escape; wizard sekolah → tinjau → re-auth dengan fokus tidak keluar dari dialog → pending account; penerima menyetel password dan replay ditolak; menyimpan perubahan izin; halaman orang tua lama mengembalikan 404 setelah fitur dihapus.

Inspeksi visual keadaan utama termasuk form panjang, list/card, peta catchment, review, fitur, audit, antrean kosong/berisi, undangan sekali tampil, set password, link tidak berlaku dan dialog re-auth. Perbaikan dari inspeksi: footer tidak menutupi preset, dialog terpusat, key grup izin tidak berubah menjadi angka, placeholder bukan literal Blade, SVG tier icon, fokus/region tabel, teks minimum 14 px, jarak tombol dan breadcrumb mobile. Tile peta dirender ulang setelah pemuatan untuk menghindari screenshot parsial. Token disembunyikan pada screenshot QA.

## Reproduksi

Ikuti `ADMIN_SETUP.md`. Aktifkan `RUTEAMAN_SPATIAL_TESTS=1` pada database PostgreSQL/PostGIS terpisah untuk suite penuh. Suite SQLite default sengaja melewati tes spasial dan tidak membuktikan invarian admin.

ZIP menyertakan tes otomatis PHP, bukan browser fixture berisi akun/password uji, cookies, token, screenshot atau database lokal. Data demo dapat dibuat hanya pada lingkungan pengembangan melalui seeder proyek yang sudah ada.

## Belum terverifikasi / batas paket

- SMTP sungguhan/deliverability, reverse proxy/APM redaction, Android fisik, dan deployment pengguna belum diuji.
- 2FA/TOTP belum tersedia. Tidak ada impersonasi atau akses kata sandi pengguna.
- Tidak ada persisted queue untuk undangan; SMTP sinkron setelah commit agar token tidak masuk jobs/failed_jobs.
- Baseline 11 tabel domain tetap dipertahankan; tidak memenuhi batas total ≤10 tabel.
- Metrik tren berbentuk tabel 7 hari, belum grafik interaktif; fitur per sekolah berupa panel responsif, bukan matriks lebar.
- Pengaman immutable audit berada pada aplikasi/model, bukan pertahanan terhadap operator SQL berprivilege.
- Kompatibilitas MySQL/MariaDB tidak dijamin; wizard dan lock memakai PostgreSQL/PostGIS.
- Baseline tidak memiliki composer.lock; versi dependensi Composer perlu dikunci oleh deployment. npm memakai package-lock.json.
