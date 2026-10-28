# Foto Beranda — tiga koleksi independen

Prasyarat: RuteAman v7.3 yang sudah menerima patch Panduan Publik terakhir. Paket ini bukan aplikasi penuh. Jika ada kode kustom di file yang ditimpa (khususnya routes/web.php, routes/admin.php, layout admin dan app.js/app.css), gabungkan perubahan dahulu, jangan menimpa kustomisasi tanpa backup.

## Pasang di hosting

1. Cadangkan database dan file yang ditimpa.
2. Upload ZIP ke /home/woixvwwz, lalu Extract di folder itu. Gabungkan ruteaman dan public_html, jangan hapus folder lama atau mengekstrak di dalamnya.
3. Dari terminal:

```bash
cd ~/ruteaman
php artisan down
php scripts/check-home-gallery.php
php artisan optimize:clear
php artisan migrate --force
php artisan optimize
php artisan up
```

Berhenti jika ada perintah gagal. Pemeriksa file tidak mengakses database atau menampilkan .env. Migration menambah home_gallery_groups dan home_gallery_photos serta tiga pengaturan awal; tidak mengubah laporan lama. Tidak perlu db:seed, key:generate, Composer install, npm build atau cron baru. Aset sudah dibangun dan disalin ke kedua webroot. .env, index.php, .htaccess, vendor dan kredensial tidak termasuk.

PHP GD dengan WebP wajib tersedia pada PHP terminal dan PHP situs. Direktori storage dan bootstrap/cache harus dapat ditulis. Foto asli/hasil olahan disimpan privat, bukan pada public_html.

## Gunakan

Login sebagai Administrator Utama (role owner), buka Konsol > Foto Beranda. Admin sekolah tidak dapat mengubah galeri.

Tiga kelompok: Bandingkan alternatif rute, Lapor dalam empat langkah, Konfirmasi dan tindak lanjut. Unggahan setiap kelompok terpisah. Awalnya semua kelompok Stay dan memakai ilustrasi bawaan; foto laporan tidak diaktifkan tanpa pilihan owner.

Untuk setiap kelompok:
- Pilih orientasi landscape/portrait. Portrait hanya unggahan admin.
- Sumber landscape: Unggahan admin, Laporan publik terverifikasi, atau Gabungan.
- Pilih kategori kondisi (kosong berarti semua) dan opsional sekolah untuk foto laporan. Sekolah hanya membatasi laporan, bukan unggahan admin.
- Pilih Stay atau Bergulir otomatis. Interval berupa bilangan 1–1000 dengan satuan detik, menit, jam, hari, minggu, bulan.
- Bulan mengikuti kalender Asia/Jakarta, termasuk akhir bulan, bukan 30 hari tetap. Jadwal dimulai ulang saat disimpan.
- Untuk Stay pilih foto tengah; pilihan otomatis dikunci ke foto pertama saat menyimpan. Mengganti sumber/filter/orientasi mungkin memerlukan pilihan Otomatis dahulu.
- Unggah JPEG/PNG/WebP, maksimal 5 MB dan 12 juta piksel, dengan orientasi sesuai kelompok, keterangan publik, kategori, urutan, dan persetujuan publikasi. Batas 60 foto unggahan per kelompok. Simpan pengaturan orientasi sebelum mengunggah.
- Foto dapat diberi urutan, disembunyikan, atau dihapus. Foto tidak cocok dengan orientasi baru tetap tersimpan namun tidak dipakai.

Rotasi berjalan saat pengunjung membuka halaman, tanpa refresh manual dan tanpa cron. Jika halaman ditutup lalu dibuka, posisi dihitung dari jadwal server. Tombol Jeda/Lanjutkan hanya mengubah pengalaman pengunjung tersebut. Hover/fokus menunda pergantian agar foto tidak bergerak saat sedang dipilih; reduced-motion menjeda awal. Pengaturan admin diperiksa berkala, umumnya paling lambat 60 detik pada halaman terbuka. Interval 1 detik memakai lebih banyak data; 5–30 detik lebih nyaman.

## Foto laporan dan privasi

Hanya foto kondisi yang sudah publik, status verified, keputusan verifikasi terbaru verified, non-demo, tidak menunggu moderasi, masih aktif sesuai TTL, dan berorientasi landscape yang layak. Filter kondisi/sekolah diterapkan. Galeri tidak memuat nama pelapor atau keterangan privat. Foto yang kehilangan kelayakan tidak dilayani lagi pada permintaan berikutnya; foto yang sudah tampil akan diperbarui saat feed berikutnya. Foto yang sudah dilihat/diunduh pengunjung tidak dapat ditarik kembali.

Foto diproses ulang menjadi WebP dan metadata dibuang. Ini bukan blur otomatis wajah/plat nomor. Admin bertanggung jawab memeriksa hak publikasi dan data pribadi. Mengaktifkan sumber laporan berarti memperluas penayangan foto yang memang telah publik. Sumber admin dan ilustrasi bawaan ditandai ilustrasi, bukan bukti kondisi terkini. Ilustrasi mengisi slot bila koleksi belum cukup.

## Uji setelah pemasangan

- Beranda tanpa login menampilkan tiga kelompok dan panduan publik tetap berfungsi.
- Owner melihat Foto Beranda, admin sekolah/tamu tidak dapat membuka pengaturannya.
- Unggah landscape ke kelompok pertama dan portrait ke kelompok kedua; koleksinya tidak tercampur.
- Bergulir setiap 5 detik berpindah; kelompok Stay tetap. Uji Jeda dan Lanjutkan.
- Uji filter kategori/sumber; hanya laporan terverifikasi yang layak publik dapat masuk.
- Periksa HP dan komputer. Unggahan, pengaturan dan hapus menggunakan autentikasi ulang admin yang sudah ada.

QA lokal: 123 tes berhasil (1132 assertions), termasuk 11 tes galeri. Pemeriksaan visual mobile 390x844 dan desktop 1440x900: tanpa error JavaScript/overflow horizontal dan sembilan slot gambar termuat. Pengujian rotasi browser memakai feed simulasi untuk rotasi, Stay independen, Jeda dan Lanjutkan.
