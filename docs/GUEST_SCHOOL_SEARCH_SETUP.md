# Patch pencarian sekolah pada Lapor tanpa login

Patch ini memperbaiki `/lapor`, bukan installer mandiri. Diperlukan RuteAman v7.3 dengan fitur Lapor tanpa login; build frontend disiapkan dari salinan source yang sudah memuat patch Konsol Admin Lengkap terbaru. Jika source hosting pernah dimodifikasi sendiri, gabungkan perubahan sebelum menimpa file.

## Perubahan

- Pilihan sekolah menjadi pencarian nama atau NPSN, minimum 3 karakter.
- Hasil menampilkan nama, NPSN, kecamatan, kabupaten/kota, dan provinsi agar sekolah bernama sama tidak tertukar.
- Mencari sekolah aktif terdaftar serta sekolah dari direktori nasional, termasuk sekolah lain yang belum tercatat di RuteAman.
- Pilih tombol hasil pencarian; mengetik teks saja tidak cukup untuk mengirim laporan.
- Peta mengikuti sekolah pilihan. Mengubah pencarian membatalkan pilihan lama dan menghapus koordinat lama.
- Laporan tetap tanpa akun dan menunggu pemeriksaan; nama tamu tetap privat.
- Sekolah baru dicatat dari data direktori saat laporan valid berhasil dikirim, bukan ketika dicari/diklik. Tidak membuat akun siswa, admin sekolah, atau keanggotaan otomatis.
- Sekolah yang dijeda/diarsipkan atau pelaporannya dimatikan tidak diaktifkan kembali lewat pencarian nasional.
- Pilihan direktori memakai tiket terenkripsi, terikat sesi tamu, berlaku dua jam. Pilih ulang jika kedaluwarsa.

## Pemasangan di cPanel

1. Backup source dan database. Jangan mengubah `.env` atau `APP_KEY`.
2. Di Terminal:

```bash
cd /home/woixvwwz/ruteaman
php artisan down
```

3. Upload dan ekstrak ZIP ke `/home/woixvwwz`. Isi ZIP sudah memiliki `ruteaman/...` dan `public_html/build/...`. Timpa hanya file yang disertakan; jangan menghapus folder lain dan jangan membuat folder `ruteaman/ruteaman` tambahan.
4. Jalankan:

```bash
cd /home/woixvwwz/ruteaman
php artisan optimize:clear
php artisan optimize
php artisan up
```

5. Tidak ada migrasi baru. Tidak perlu npm atau Composer; build CSS/JS siap pakai disertakan pada kedua lokasi build. Jangan menjalankan `migrate:fresh`, `db:seed`, atau `key:generate`.
6. Buka jendela samaran dan akses `https://ruteaman.my.id/lapor`. Jika tampilan lama masih muncul, refresh/hapus cache browser.

Tidak disertakan/diganti: `.env`, `vendor`, `node_modules`, database, unggahan, runtime storage, `public_html/index.php`, `.htaccess`, routes utama, atau kode Panduan Publik. Panduan Publik dan konfigurasi konsol admin hosting tetap menggunakan file yang sudah ada.

## Pemeriksaan setelah pemasangan

1. Cari `MAN 2 REMBANG` atau `20363192`, lalu pilih hasil yang cocok.
2. Cari sekolah lain, misalnya `MAN 1 REMBANG` / `20363196`. Nama sama di wilayah berbeda ditampilkan terpisah; pilih NPSN dan wilayah yang tepat.
3. Pilih titik di wilayah sekolah, jenis kondisi, setujui ketentuan, lalu kirim. Nama boleh kosong.
4. Pastikan muncul halaman Laporan diterima. Kondisi belum ditampilkan publik sebelum diverifikasi.
5. Pengelola platform/Owner dengan izin verifikasi dapat meninjau laporan sekolah baru. Admin sekolah lama tidak otomatis mendapat akses sekolah lain; cakupan izin tetap berlaku. Atur pengelola sekolah yang baru jika diperlukan.
6. Coba mengganti pencarian setelah memilih sekolah. Pilihan dan titik lama harus terhapus.

## Batasan data dan wilayah

Direktori nasional bukan jaminan semua sekolah tersedia. Data/NPSN, wilayah, dan koordinat harus ditemukan serta dapat diverifikasi oleh server. Tidak menerima nama/NPSN bebas yang belum dicocokkan dengan direktori. Data koordinat tidak tersedia atau layanan gagal: tampilkan pesan dan pilih ulang; jangan membuat sekolah palsu.

Sekolah yang sudah terdaftar memakai wilayah pemantauan yang sudah dikonfigurasi. Sekolah baru mengikuti aturan importer yang sudah ada: kotak awal +/-0,025 derajat dari titik direktori. Ini wilayah awal aplikasi, bukan batas sekolah atau zona resmi. Pengelola perlu meninjau dan memperbaikinya sesuai kebutuhan.

Pencarian nasional bergantung pada layanan direktori yang sudah digunakan proyek (Dapodik dan cadangan sekolah.devapi.id, pencocokan wilayah melalui wilayah.id dengan fallback lokal). Ketika layanan nasional bermasalah, sekolah terdaftar tetap dapat dipilih dari hasil lokal. Batas pencarian: 30 permintaan per menit/IP; pengiriman laporan memakai batas anti-spam yang sudah ada.

## Pemulihan

Jika pemasangan gagal, pulihkan file source dan kedua folder build dari backup, jalankan `php artisan optimize:clear`, lalu `php artisan up`. Tidak ada migrasi skema pada patch ini. Jika sudah ada laporan sekolah baru, jangan menghapus record sekolah yang dirujuk laporan.

Patch disiapkan dan diuji lokal. Tidak memasang atau mengubah hosting secara otomatis.
