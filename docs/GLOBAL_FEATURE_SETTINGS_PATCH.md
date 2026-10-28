# Pengaturan fitur umum dan khusus sekolah

## Cara menggunakan
Masuk sebagai Admin Utama → **Fitur Umum & Sekolah**. Di bagian paling atas ada **Pengaturan umum · seluruh sekolah**.

1. Ubah saklar yang diinginkan, termasuk dua saklar terpisah verifikasi tamu/login.
2. Klik **Simpan & terapkan secara umum**, baca peringatan, lalu konfirmasi keamanan jika diminta.
3. Semua saklar pada panel umum diterapkan ke seluruh sekolah yang sudah terdaftar, termasuk baris sekolah dijeda/diarsipkan. Status sekolah TIDAK diaktifkan atau diubah.
4. Profil umum disimpan di database dan digunakan sebagai default sekolah yang baru terdaftar/diimpor, bukan hanya menerapkan sekali ke MAN 2 REMBANG.
5. Jika perlu pengecualian, buka **Pengaturan khusus per sekolah**, ubah sekolah itu, lalu klik **Simpan khusus sekolah ini**. Default umum tidak berubah. Menyimpan panel umum lagi mengganti pengecualian tersebut.

Panel umum menampilkan profil/default umum, bukan indikator bahwa semua pengecualian sekolah selalu sama. Setelah instalasi, default awal tidak otomatis mengganti pengaturan sekolah lama: perubahan seluruh sekolah hanya dilakukan setelah Admin Utama menyimpan panel umum.

Menu lama **Terapkan satu fitur ke sekolah yang sudah terdaftar** tetap ada untuk penerapan satu saklar saja; menu itu tidak mengubah default sekolah baru. Gunakan panel umum untuk konfigurasi umum lengkap.

## Batasan yang dipertahankan
- Membaca peta bukan fitur yang bisa dimatikan.
- Saklar Wajib verifikasi tamu dan pengguna login tetap independen. Hidup = laporan baru menunggu pengelola; Mati = langsung tampil, belum terverifikasi. Antrean lama tidak otomatis diterbitkan, dan laporan yang sudah dipublikasikan tidak otomatis ditarik.
- Verifikasi harus aktif jika salah satu kelompok laporan diwajibkan pemeriksaan. Profil tidak valid ditolak secara atomik tanpa mengubah sekolah atau default umum.
- Sekolah yang dijeda/diarsipkan tidak diaktifkan oleh pengaturan umum. Sekolah direktori yang baru ditambahkan Admin Utama tetap dijeda dengan pelaporan nonaktif sampai pin/wilayah/fitur ditinjau, sambil mengikuti default umum lainnya.
- Pengaturan umum hanya dapat disimpan Admin Utama yang aktif, dengan CSRF, konfirmasi keamanan, throttling dan audit. Tidak memberikan hak global pada admin sekolah/siswa atau admin platform lama.
- Persetujuan privasi siswa dipertahankan. Fitur Tampilan Orang Tua tetap dihapus; feed GeoJSON adalah fitur terpisah.
- Pencarian sekolah nasional, tombol Kembali, moderasi dan hapus akun cepat dari patch sebelumnya dipertahankan.

## Pemasangan cPanel
Paket kumulatif untuk aplikasi yang sudah memakai patch administrator/publikasi sebelumnya: memuat pemilih sekolah nasional dan penghapusan Tampilan Orang Tua juga, agar file lama tidak mengembalikan fitur yang sudah dihapus. Tidak memuat .env, vendor, node_modules, database pribadi, atau pengganti UserGuideController.

1. Backup kode, direktori public_html terkait, dan database.
2. Jalankan sebelum menimpa file:

```bash
cd /home/woixvwwz/ruteaman
php artisan down
```

3. Ekstrak ZIP di **/home/woixvwwz**, izinkan overwrite. Jangan di folder ruteaman supaya tidak menjadi direktori rangkap. Struktur paket ruteaman/ dan public_html/.
4. Jalankan:

```bash
cd /home/woixvwwz/ruteaman
rm -f resources/views/admin/parent-board.blade.php
php artisan optimize:clear
php artisan migrate --force
php artisan optimize
php artisan up
```

`rm` hanya membuang template orang tua yang sudah dihentikan. Ekstraksi ZIP tidak dapat menghapus file lama secara otomatis.

Migrasi baru **2026_10_19_000000_create_platform_feature_settings** membuat satu baris profil umum di tabel platform_feature_settings. Migrasi penghapusan flag orang tua 2026_10_18 juga disertakan bagi instalasi yang belum menjalankannya. Tidak otomatis menerapkan default ke semua sekolah lama pada saat migrasi.

Aset Vite yang kompatibel sudah disertakan dalam kedua direktori build; tidak perlu npm build di hosting. Jangan hanya menyalin manifest atau menimpa .env. Tidak perlu menjalankan demo-reset atau migrate:fresh.

Jika migrate/optimize gagal, jangan jalankan up: simpan error untuk diperiksa. Rollback tabel profil tidak mengembalikan konfigurasi khusus sekolah yang telah diganti oleh tombol umum. Pulihkan pasangan backup kode/database yang konsisten bila membutuhkan rollback penuh; jangan menulis ulang log audit.

## Cek setelah pemasangan
- Panel Pengaturan umum tampil paling atas, tanpa judul MAN 2 REMBANG.
- Ubah satu saklar di panel umum lalu simpan. Periksa dua sekolah terdaftar jika tersedia; nilai keduanya mengikuti profil umum.
- Sekolah baru memakai default umum, dengan pengecualian keamanan untuk sekolah direktori baru yang dijeda/nonaktif pelaporannya.
- Buka pengecualian satu sekolah dan simpan: default umum tetap sama; sekolah lainnya tidak berubah.
- Tampilan orang tua tidak muncul dan /parent/SLUG mengembalikan 404.
- Refresh keras bila browser masih memakai halaman lama.

## Pengujian lokal
- **248 tes PHPUnit / 2034 assertion lulus**, termasuk 11 skenario baru pengaturan umum.
- **27 tes JavaScript lulus**, tidak ada perubahan sumber JavaScript dalam fitur umum ini.
- PHP 8.4.24, MariaDB 10.11.18 dan SQLite terisolasi; PostgreSQL tidak diuji ulang.
- Mencakup penyimpanan database, semua sekolah/future defaults, pengecualian, status sekolah tetap, impor direktori tetap dijeda, mode verifikasi baru, penolakan profil tidak valid/akses tak berizin, antrean lama tetap pending, serta flag layer bersama pada peta sekolah baru.
- Browser lokal 360/768/1280 px: panel umum terlihat, tidak ada overflow/error JS/temuan axe pada halaman fitur. Simpan umum benar-benar memperbarui sekolah simulasi; simpan pengecualian tidak mengubah profil umum.
- Blade cache dan pemeriksaan gaya PHP terkait dilakukan. Hosting produksi belum diakses atau diubah oleh agen.
