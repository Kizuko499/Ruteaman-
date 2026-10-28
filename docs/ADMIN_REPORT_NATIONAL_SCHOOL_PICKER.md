# Pemilih sekolah nasional pada Laporan / Kondisi

## Penyebab dan perbaikan
Dropdown sebelumnya hanya memuat tabel `schools` lokal dalam cakupan akun. Bila baru MAN 2 REMBANG yang terdaftar, hanya sekolah itu yang muncul. Sekolah lainnya bukan sengaja dihapus, tetapi belum tersedia dalam tabel aplikasi.

Admin Utama sekarang mendapatkan pencarian direktori nasional di halaman **Administrator → Laporan / Kondisi**:
1. Ketik nama sekolah atau NPSN, minimal 3 karakter, klik **Cari sekolah online**. Tidak dibatasi sekolah yang sudah terdaftar.
2. Sekolah terdaftar: klik **Tampilkan laporan**, langsung berpindah ke sekolah yang dipilih.
3. Sekolah dari direktori yang belum terdaftar: klik **Daftarkan & tampilkan**, setujui peringatan dan konfirmasi keamanan bila diminta. Data sekolah/NPSN diverifikasi ulang di server, bukan mempercayai nama atau koordinat kiriman browser.
4. Sekolah baru ditambahkan sekali, berstatus dijeda dan pelaporan nonaktif. Tinjau pin/catchment pada menu **Sekolah** serta fitur pada **Fitur Sekolah** sebelum mengaktifkan pemantauan dan pelaporan.

Tidak membuat akun atau laporan otomatis. Direktori nasional berisi identitas sekolah, bukan laporan kondisi jalan. Halaman dapat kosong bila belum ada kondisi RuteAman di wilayah sekolah yang dipilih. Wilayah hasil impor perlu ditinjau; bukan batas sekolah resmi yang dijamin akurat.

Dropdown cepat tetap menampilkan sekolah yang sudah terdaftar, bukan ratusan ribu sekolah sekaligus. Pencarian online menampilkan sampai 30 hasil relevan. Gunakan nama lebih spesifik atau NPSN. Hasil bergantung pada cakupan dan ketersediaan sumber online. Bila sumber tidak dapat dihubungi, pilihan lokal tetap tersedia dan tampil pesan untuk mencoba kembali.

Akun Admin Sekolah tetap dibatasi sekolah dalam cakupan izin; pencarian/impor nasional tidak diberikan kepada akun sekolah atau siswa. Perilaku pemilih di halaman akun tetap memilih sekolah tanpa otomatis menyimpan perubahan akun.

Publikasi langsung, dua saklar verifikasi, moderasi, tombol Kembali dan konfirmasi hapus akun dari patch sebelumnya tidak diubah.

## Pemasangan
Untuk aplikasi yang sudah memakai patch publikasi/moderasi terakhir. Tidak ada migrasi baru dan tidak perlu rebuild Vite.

1. Backup file aplikasi dan `public_html/js/admin-school-picker.js`.
2. Jalankan dari terminal:

```bash
cd /home/woixvwwz/ruteaman
php artisan down
```

3. Ekstrak ZIP di **/home/woixvwwz**, izinkan overwrite. Jangan di folder `/home/woixvwwz/ruteaman`, supaya tidak menjadi folder rangkap. ZIP memuat `ruteaman/` dan `public_html/`.
4. Jalankan:

```bash
cd /home/woixvwwz/ruteaman
php artisan optimize:clear
php artisan optimize
php artisan up
```

Jika optimize gagal, tetap biarkan maintenance dan kirim error. Jangan menjalankan migrate:fresh atau demo-reset. Refresh halaman keras sesudah pemasangan. Script laporan memakai penanda versi untuk menghindari cache lama.

## Uji setelah pasang
- Masuk sebagai Admin Utama, buka Laporan / Kondisi; pencarian nama/NPSN terlihat di bawah dropdown.
- Cari MAN 1 REMBANG / 20363196 atau SMPN 1 Surabaya / 20532613; pastikan nama, NPSN, wilayah, dan asal data ditampilkan.
- Pilih sekolah terdaftar → halaman berpindah; pilih hasil direktori → konfirmasi pendaftaran sebelum data berubah.
- Membatalkan peringatan tidak mendaftarkan sekolah.
- Sekolah baru tidak otomatis aktif atau mempunyai laporan. Aktifkan hanya sesudah pin/catchment dan fitur ditinjau.
- Jika koneksi online gagal, periksa akses HTTPS keluar hosting; jangan menonaktifkan verifikasi TLS sebagai solusi.

## QA lokal
- PHPUnit seluruh suite: **232 tes / 1938 assertion lulus** (PHP 8.4.24, MariaDB 10.11.18; tes backend ringan memakai SQLite terisolasi).
- JavaScript: **27 tes lulus**, termasuk pilihan sekolah laporan, tidak otomatis menyimpan akun, konfirmasi/cancel pendaftaran, minimum query, kegagalan sumber, reauth, dan teks aman.
- Blade cache dan pemeriksaan gaya test PHP baru berhasil.
- Browser Chromium lokal 360/768/1280 px: tidak ada overflow horizontal, error JS, atau temuan axe pada halaman yang diperiksa. Navigasi ke sekolah lain dan cancel tanpa POST pendaftaran berhasil. Respons pencarian browser memakai fixture, bukan data produksi.
- Pengujian jaringan terpisah dengan verifikasi TLS aktif: MAN 1 REMBANG (20363196), SMP NEGERI 1 SURABAYA (20532613) berhasil ditemukan dari sumber online. Tidak ada sekolah nyata didaftarkan pada hosting.
- PostgreSQL tidak diuji ulang; hosting produksi belum diakses atau diubah oleh agen.
