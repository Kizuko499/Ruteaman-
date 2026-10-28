# Pilihan bahasa tamu di bawah Dashboard

Pembaruan ini memindahkan pilihan Bahasa Indonesia / English untuk pengguna tanpa login ke panel Akun, tepat di bawah tombol Dashboard. Bilah bahasa tamu di atas halaman dihapus. Pilihan pengguna login di atas halaman dan pada Akun → Pengaturan tetap seperti sebelumnya.

Pilihan masih diingat oleh cookie, dan Simpan bahasa memuat ulang halaman yang sedang dibuka. Tidak mengubah akun, nama sekolah, laporan, izin atau status publikasi. Jika JavaScript dinonaktifkan, tersedia formulir cadangan di bagian bawah halaman agar pilihan bahasa tetap dapat digunakan.

## Persyaratan

Paket ini adalah pembaruan tampilan, bukan instalasi penuh. Patch Bahasa Indonesia-English sebelumnya harus sudah terpasang, seperti pada screenshot pengguna. Tidak menambah rute, backend atau kolom database; tidak perlu migrasi, Composer update ataupun npm build di hosting. Aset build terbaru sudah disertakan.

## Pemasangan

Backup source dan kedua folder build, kemudian:

```bash
cd /home/woixvwwz/ruteaman
php artisan down
```

Ekstrak ZIP di **/home/woixvwwz**, bukan di folder ruteaman. Timpa berkas yang sama. ruteaman/public/build dan public_html/build harus sama-sama diperbarui. Bila lokasi source/webroot berbeda, sesuaikan tujuan.

```bash
cd /home/woixvwwz/ruteaman
php artisan optimize:clear
php artisan optimize
```

Setelah kedua perintah berhasil:

```bash
php artisan up
```

Jika gagal, jangan lanjutkan up. Jangan menghapus .env, APP_KEY, storage atau database. Jangan migrate:fresh maupun reset demo.

## Pemeriksaan

- Tanpa login, buka Dashboard lalu tombol Akun. Setelah Masuk, Daftar dan Dashboard, muncul pilihan Bahasa serta tombol Simpan bahasa.
- Pilih English dan simpan. Buka Akun kembali; pilihan English tetap dipilih. Bisa kembali ke Bahasa Indonesia.
- Tidak ada bilah bahasa tamu di atas halaman.
- Setelah login, pengaturan bahasa akun tidak dipindahkan atau dihapus.
- Jika tampilan lama masih muncul, refresh tanpa cache dan pastikan public_html/build sudah ditimpa.

## QA lokal

260 tes PHPUnit (2119 assertions) dan 27 tes JavaScript lulus. Posisi formulir dan tidak adanya bilah tamu di atas halaman diuji; toolbar pengguna login tetap tersedia. Build Vite dan cache Blade berhasil. Panel tamu Indonesia/English diperiksa pada 390/768/1280 px, termasuk persistensi pilihan dan cadangan tanpa JavaScript. Hasil browser aktual disertakan dalam folder QA. Pengujian ini bukan deployment ke hosting.
