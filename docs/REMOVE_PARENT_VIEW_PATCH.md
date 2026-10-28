# Menghapus fitur Tampilan Orang Tua

## Yang dihapus
- Saklar `parent_view` / Tampilan orang tua dari Fitur Sekolah, formulir sekolah, dan pilihan penerapan massal.
- Label dan syarat orang tua pada izin `feed.manage`; izin ini menjadi **Atur feed GeoJSON**, hanya bergantung pada fitur GeoJSON dan izin sekolah terkait.
- Tautan Feed dan Orang Tua, link Buka tampilan baca-saja, controller action parent, dan route lama `/parent/{slug}`. Tautan langsung mengembalikan 404, termasuk jika flag lama sempat hidup.
- Template halaman orang tua yang lama. Panduan mencantumkan satu file yang harus dihapus dari hosting karena ekstraksi ZIP tidak menghapus file lama secara otomatis.
- Flag `parent_view` di JSON `schools.features` melalui migrasi; saklar yang sudah dihapus ditolak oleh validasi jika dikirim lewat form lama/API.

## Yang dipertahankan
Feed GeoJSON, ekspor sekolah, metrik, status penerusan publik, sekolah online, publikasi laporan, moderasi, dan pengelolaan akun tetap bekerja sesuai izin/cakupan. Status penerusan publik masih digunakan pada detail kondisi, bukan hanya halaman orang tua.

Persetujuan privasi pendaftaran siswa dan `users.parental_consent_at` TIDAK dihapus atau dinonaktifkan. Itu adalah catatan persetujuan dan aturan unggah/lapor yang berbeda dari fitur Tampilan Orang Tua pada administrator. Data siswa, laporan, foto, dan log audit lama tidak dihapus/ditulis ulang oleh migrasi ini.

## Pasang pada cPanel
Untuk aplikasi yang telah memakai patch publikasi/moderasi dan pemilih sekolah nasional sebelumnya. Tidak menyertakan .env, vendor, dump data atau pengganti UserGuideController. Tidak ada perubahan JavaScript/Vite dan tidak perlu npm build.

1. Backup source dan database.
2. Sebelum menimpa file:

```bash
cd /home/woixvwwz/ruteaman
php artisan down
```

3. Ekstrak ZIP di **/home/woixvwwz**, izinkan overwrite. Jangan di dalam folder ruteaman agar tidak menjadi direktori rangkap.
4. Jalankan:

```bash
cd /home/woixvwwz/ruteaman
rm -f resources/views/admin/parent-board.blade.php
php artisan optimize:clear
php artisan migrate --force
php artisan optimize
php artisan up
```

`rm` hanya menghapus satu template fitur yang dihentikan, bukan data/database. Walaupun belum dihapus, template lama tidak mempunyai route/controller yang bisa membukanya setelah patch dan cache route diperbarui.

Jika migrate/optimize gagal, jangan jalankan up dan jangan mencoba migrate:fresh atau menghapus tabel. Simpan error untuk diperiksa. Migrasi baru `2026_10_18_000000_retire_parent_view_feature` membersihkan hanya kunci parent_view pada JSON fitur sekolah dengan lock transaksi, mempertahankan nilai fitur lain. Migrasi idempotent; JSON yang tidak valid tidak ditulis ulang secara lossy.

Rollback otomatis tidak merekonstruksi nilai flag fitur orang tua sebelumnya. Untuk rollback lengkap, pulihkan pasangan backup kode/database yang konsisten; jangan mengedit atau menghapus audit yang sudah tersimpan.

## Cek sesudah pasang
- Fitur Sekolah tidak memiliki Tampilan orang tua.
- Pada izin akun, lihat Atur feed GeoJSON, tanpa peringatan Fitur Tampilan orang tua nonaktif.
- Pada data sekolah dan pengaturan feed tidak ada tautan orang tua/baca-saja.
- `/parent/SLUG` mengembalikan 404, sedangkan feed GeoJSON tetap dapat dibuka jika fitur dan izin terkait aktif.
- Dua saklar verifikasi laporan dan pencarian sekolah online tetap tersedia.
- Refresh keras bila halaman lama masih muncul dari cache browser.

## Pengujian lokal
- PHPUnit: **237 tes / 1978 assertion lulus** pada PHP 8.4.24/MariaDB 10.11.18 dan test SQLite terisolasi.
- JavaScript: **27 tes lulus**; sumber JavaScript tidak berubah dalam patch ini.
- Tes baru: katalog/rute/action orang tua tidak ada, pembersihan flag mempertahankan fitur lain/settings/persetujuan pengguna, migrasi idempotent, form lama ditolak, UI tidak menyebut orang tua, feed/ekspor/metrik tetap bekerja.
- Browser lokal 360/1280 px: halaman fitur, izin akun dan data sekolah tidak memiliki saklar/tautan/teks tampilan orang tua; rute lama 404. Tidak ada error JS/overflow. Axe pada halaman fitur dan izin akun tidak menemukan pelanggaran; halaman formulir sekolah masih memiliki satu temuan gaya tautan (`link-in-text-block`) yang tidak terkait penghapusan ini dan tidak diubah.
- Blade cache berhasil. Halaman/rute produksi belum diakses atau dipasangi patch oleh agen. PostgreSQL tidak diuji ulang.
