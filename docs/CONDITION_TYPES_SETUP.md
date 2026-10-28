# Ikon akun dan pengelolaan jenis kondisi

## Isi paket

- Tombol Akun di header desktop hanya ikon, tetap bernama Buka menu akun bagi pembaca layar. Tombol membuka menu yang sama; fungsi akun/profil dan label navigasi bawah HP tidak dihapus.
- Menu Administrator Utama > Jenis Kondisi untuk membuat kategori baru dan mengedit nama/logo kategori lama.
- Dua sumber logo: 12 ikon bawaan atau unggahan PNG/JPG/WebP sendiri.
- Kategori tersedia pada formulir laporan anggota/tamu, filter daftar kondisi, filter foto beranda, penanda/popup peta dan legenda. Logo tampil pada formulir, kartu/detail kondisi serta peta. Kondisi berstatus selesai tetap memakai simbol tertangani di peta.
- Paket juga mencakup patch Foto Beranda sebelumnya. Tidak perlu memasang ZIP Foto Beranda lebih dahulu. Prasyarat: RuteAman v7.3 + patch Panduan Publik terakhir, bukan instalasi Laravel kosong.

## Pasang

Cadangkan database dan seluruh file yang ditimpa. Jika memiliki kode tambahan pada route, layout, model atau aset, gabungkan perubahan terlebih dahulu; paket ini menimpa file bernama sama.

Upload ZIP ke /home/woixvwwz lalu Extract di folder tersebut. Gabungkan ruteaman dan public_html; jangan hapus folder lama atau membuat ruteaman/ruteaman.

Jalankan satu per satu, berhenti bila gagal:

```bash
cd ~/ruteaman
php artisan down
php scripts/check-condition-types.php
php scripts/check-home-gallery.php
php artisan optimize:clear
php artisan migrate --force
php artisan optimize
php artisan up
```

Migration galeri aman dilewati bila sudah dijalankan. Migration jenis kondisi menambah icon_key/icon_path ke hazard_types dan menetapkan ikon untuk kategori lama tanpa mengubah ID, label atau laporan. Tidak ada db:seed, key:generate, penghapusan database, Composer install, npm build atau cron baru. Aset build sudah disalin ke ruteaman/public/build dan public_html/build.

PHP GD dengan dukungan WebP diperlukan untuk unggahan (PHP terminal dan PHP situs). Pastikan storage serta bootstrap/cache dapat ditulis. .env, index.php, .htaccess, vendor, foto pengguna, fixture/demo pengujian dan kredensial tidak termasuk dalam ZIP.

## Buat kondisi

1. Login Administrator Utama, buka Konsol > Jenis Kondisi.
2. Isi nama, misalnya Longsor.
3. Untuk ikon bawaan, pilih sumber Ikon bawaan dan klik salah satu pilihan.
4. Untuk logo sendiri, pilih Unggah logo sendiri lalu pilih file. PNG transparan disarankan; maksimal 512 KB dan 1.048.576 piksel. Logo dipusatkan tanpa dipotong, diubah menjadi WebP 256x256 dan metadata dibuang. SVG/GIF/URL eksternal tidak diterima. Pastikan berhak menggunakan logo dan tidak memuat data pribadi.
5. Tekan Tambahkan kondisi; konfirmasikan kata sandi jika diminta.
6. Buka halaman laporan/peta untuk melihat kategori. Muat ulang halaman yang sudah terbuka untuk memuat daftar/legenda terbaru.

Kode dibuat otomatis dari nama. Bila kode sudah ada, ubah kode unik di Pengaturan lanjutan; huruf kecil, angka, garis bawah, dimulai huruf, maksimal 50 karakter. Kode tidak bisa diubah setelah disimpan agar laporan/integrasi tetap kompatibel.

Pengaturan lanjutan: bobot 1–5, masa aktif dasar 1–525600 menit, sensitivitas hujan dan khusus malam. Default bobot 2, masa aktif 360 menit, hujan/malam Tidak. Pengaturan ini memakai mekanisme penilaian/TTL yang sudah ada, bukan aturan keselamatan baru. Mengubahnya juga memengaruhi perhitungan laporan lama; lakukan dengan sengaja.

## Edit kondisi

Buka kategori di daftar, ubah nama/ikon, tekan Simpan perubahan. Mode Unggah dengan kolom file kosong mempertahankan logo yang ada. Unggah file baru untuk menggantinya; pilih Ikon bawaan untuk menghapus logo unggahan dan memakai ikon pilihan. Laporan dan ID jenis kondisi tetap disimpan. Tidak disediakan hapus kategori untuk menghindari merusak laporan yang merujuknya.

Hanya role owner (Administrator Utama), aktif dan terautentikasi, dapat membuat/mengedit jenis kondisi. Penulisan memakai reautentikasi admin, pembatasan permintaan dan audit. Admin sekolah tidak dapat mengubah kategori global. Logo unggahan disimpan di storage privat dan dilayani sebagai gambar publik melalui endpoint khusus; metadata/file path tidak dikirim ke feed publik.

## Pemeriksaan

- Header desktop: hanya ikon orang/avatar, menu akun tetap terbuka dan dapat ditutup.
- Owner melihat Jenis Kondisi; tamu/admin sekolah ditolak bila mencoba mengelolanya.
- Tambah kategori dengan ikon; pastikan muncul pada pilihan laporan serta filter.
- Unggah logo, lalu uji formulir laporan, kartu tanpa foto, detail, penanda/popup dan legenda peta.
- Ubah ikon kategori lama; ID/laporan tetap ada.
- Uji kategori di galeri Foto Beranda dan pergantian stay/bergulir tetap bekerja.

Tes lokal: 131 tes PHP berhasil (1203 assertions), 12 tes JavaScript berhasil. Tampilan dan alur admin diuji pada 390x844 dan 1440x900. Logo diuji dengan file simulasi; file/logo/laporan demo tersebut tidak ikut paket. Pratinjau detail/peta memakai transaksi baca-saja khusus untuk keterbatasan spatial MariaDB lokal; router itu tidak masuk kode produksi.
