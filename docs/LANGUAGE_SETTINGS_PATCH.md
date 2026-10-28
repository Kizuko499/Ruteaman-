# Bahasa Indonesia dan English

Patch menambahkan pilihan bahasa untuk tamu dan pengguna login. Berkas ini adalah patch source, bukan bukti bahwa hosting sudah diperbarui.

## Cara menggunakan

- Tanpa login: buka panel Akun, lalu pilih Bahasa Indonesia atau English tepat di bawah tombol Dashboard dan tekan Simpan bahasa / Save language. Pilihan bahasa tamu tidak lagi berada di atas halaman. Pilihan diingat oleh cookie browser selama maksimal satu tahun; menghapus cookie mengembalikan pilihan awal Indonesia.
- Dengan login: panel Bahasa di atas halaman tetap menyimpan pilihan pada akun. Bisa juga melalui Akun saya → Pengaturan → Bahasa. Pilihan akun mengalahkan cookie browser, sehingga berlaku lagi setelah masuk di perangkat lain.
- Perubahan melalui formulir Bahasa pada Pengaturan memuat ulang halaman setelah berhasil. Mengubah tampilan tidak mereset bahasa.
- Akun baru mewarisi bahasa yang dipilih tamu saat mendaftar. Akun lama tetap menggunakan nilai preferensi yang sudah tersimpan, umumnya Indonesia.

## Cakupan dan batas

Menu, tombol, formulir, label bawaan kondisi/izin, pesan tetap dan tanggal antarmuka mendukung kedua bahasa. Nama sekolah, NPSN, wilayah resmi, nama akun, alias/email, label lokasi dan isi laporan tidak diterjemahkan. Kode audit, permission, feature, status internal dan zona waktu tetap sama. Teks khusus yang ditambahkan pengelola tetap dalam bahasa aslinya bila tidak mempunyai entri kamus.

Kamus English disimpan di lang/en.json. Terjemahan dibuat offline dari teks antarmuka statis dan ditinjau pada alur utama; aplikasi tidak mengirim isi akun/laporan ke layanan penerjemahan saat digunakan. Kualitas redaksi dapat diperbaiki dengan mengedit kamus ini, tanpa mengubah data atau aturan aplikasi.

Kedua bahasa tidak mengubah izin, status verifikasi, publikasi, moderasi atau pengaturan fitur. Pilihan bahasa bukan pendaftaran akun. Semua pilihan tetap memerlukan POST melalui middleware web; return_to dibatasi ke path situs sendiri. JSON hanya menerjemahkan bidang pesan sistem yang dikenal. Cache feed kondisi dipisahkan per bahasa. Formulir tetap dapat digunakan tanpa JavaScript.

## Pemasangan cPanel

1. Backup database, source, public_html/build dan public_html/js. Jangan mengganti .env, APP_KEY, vendor atau storage.
2. Terminal:

```bash
cd /home/woixvwwz/ruteaman
php artisan down
```

3. Ekstrak ZIP di **/home/woixvwwz**, bukan /home/woixvwwz/ruteaman. Folder ruteaman/ menimpa source dan public_html/ menimpa aset publik pada lokasi itu. Bila lokasi hosting berbeda, sesuaikan kedua tujuan. public_html/build dan ruteaman/public/build harus berasal dari paket yang sama.
4. Hapus hanya view fitur orang tua yang sudah ditarik dari patch sebelumnya; catatan persetujuan akun tidak dihapus:

```bash
cd /home/woixvwwz/ruteaman
rm -f resources/views/admin/parent-board.blade.php
php artisan optimize:clear
php artisan migrate --force
php artisan route:list --path=language
php artisan optimize
```

5. Hanya jika semua perintah berhasil:

```bash
php artisan up
```

Jika migrate atau optimize gagal, jangan lanjutkan up; simpan error untuk diperiksa. Jangan migrate:fresh, seed demo atau demo-reset di hosting. Guard log audit dari patch sebelumnya membutuhkan izin database yang sesuai; histori audit tidak perlu dikosongkan.

Paket bersifat kumulatif untuk source/aset patch sebelumnya yang tersedia pada proyek ini. routes/web.php dan UserGuideController.php tidak ditimpa agar panduan publik hosting tetap utuh. Diperlukan aplikasi RuteAman yang sudah memiliki rute tamu/akun dan memuat routes/admin.php, seperti versi pada screenshot pengguna. Tidak perlu npm build atau update Composer pada hosting; build siap pakai sudah disertakan. Preferensi users.locale sudah ada; tidak ada migrasi bahasa baru. Migrasi kumulatif sebelumnya dijalankan sekali bila masih pending.

## Pemeriksaan setelah pasang

- Buka /login tanpa login, pilih English, simpan, refresh: Sign in, Password dan Remember me harus berbahasa Inggris; merek RuteAman tetap sama.
- Buka /lapor dan /map: bahasa yang sama bertahan, daftar sekolah/NPSN tidak berubah.
- Masuk, pilih English, buka Akun saya → Pengaturan; pilihan tersimpan di akun. Mengubah tampilan tidak mengganti bahasa.
- Pilih Indonesia kembali: menu dan tombol kembali Indonesia.
- Administrator: label General & School Features dan pilihan tinjauan tamu/login tetap terpisah dan aturan publikasi tidak berubah.
- Bila tampilan lama masih muncul, refresh tanpa cache setelah optimize:clear dan pastikan kedua folder build dan public_html/js telah diganti.

## QA lokal

- PHPUnit: 260 tes, 2119 assertions, seluruhnya lulus pada MariaDB lokal; bukan pengujian langsung pada hosting.
- JavaScript: 27 tes lulus.
- Vite build dan cache Blade berhasil. Pint 18 berkas PHP berubah lulus.
- Browser: 6 halaman tamu dan 4 halaman administrator pada 390/768/1280 px; tidak ditemukan overflow, error JavaScript atau temuan axe WCAG A/AA pada halaman yang diperiksa.
- Penyimpanan cookie tamu, preferensi akun, penggantian bahasa lewat AJAX, pendaftaran mewarisi bahasa, identitas tetap asli, cache feed per bahasa, input locale tidak sah dan pengalihan keluar situs diuji.
- Permintaan lintas situs tanpa token ditolak 419. Laravel 13 juga menerima permintaan same-origin berdasarkan mekanisme bawaan origin verification; perlindungan ini tidak dimatikan oleh patch.
