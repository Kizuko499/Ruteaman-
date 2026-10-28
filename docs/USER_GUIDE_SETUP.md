# Panduan Pengguna — patch 1.1

Panduan umum berada di `/panduan` dan dapat dibaca tanpa login. Tautannya berada pada bagian Panduan Pengguna di bawah FAQ, tepat sebelum footer beranda. Panduan akun tetap berada di `/account/guide` setelah login, dengan menu setelah Profil Saya dan kartu pembuka pada Profil Saya.

## Cakupan

18 bab: beranda, pendaftaran/sekolah, login, profil, peta, rute/graph, GPS, laporan akun, laporan tamu, tanda terima, Laporanku, detail/konfirmasi, notifikasi/pengaturan, keamanan/privasi/keluar, verifikasi, dashboard sekolah, konsol administrator, dan kendala umum. Pengguna biasa membaca 15 bab; tiga bab pengelola mengikuti izin akun. Halaman publik selalu memuat 15 bab umum, termasuk jika dibuka oleh admin. Panduan akun memuat bab pengelola sesuai izin. Panduan bukan pemberian akses baru.

22 screenshot WebP diambil dari aplikasi lokal dengan data simulasi, bukan dari hosting produksi. Gambar beresolusi 780 × 1688 dan dapat dibuka untuk diperbesar. Sekolah/rute contoh adalah fixture simulasi, bukan audit lapangan. Screenshot graph menunjukkan kontrol dan rute tersimpan simulasi, bukan hasil panggilan routing produksi. Tidak ada kata sandi, API key, file .env, token, atau foto pengguna asli dalam paket.

## Pemasangan cPanel

Prasyarat: proyek RuteAman v7.3 dengan laporan tamu dan menu akun. Patch mengikuti struktur hosting yang telah dikonfirmasi: proyek privat `/home/woixvwwz/ruteaman`, webroot `/home/woixvwwz/public_html`.

1. Backup file yang akan ditimpa, terutama `routes/web.php`, profil, dan menu akun. Jika memiliki route tambahan di luar versi ini, gabungkan baris route panduan ke grup akun yang sama, jangan menghilangkan route kustom.
2. Opsional sebelum upload: `cd ~/ruteaman && php artisan down`.
3. Upload ZIP ke `/home/woixvwwz`, lalu Extract di folder itu. Gabungkan folder dan timpa file yang sama; jangan hapus folder aplikasi. Hindari nested `ruteaman/ruteaman`.
4. Pastikan folder publik `public_html/css` dan `public_html/images/user-guide` ikut terisi. Jangan hanya menyalin folder sumber.
5. Dari Terminal:

```bash
cd ~/ruteaman
php scripts/check-user-guide.php
php artisan optimize:clear
php artisan optimize
php artisan up
```

Jika pemeriksaan atau optimasi gagal, berhenti, catat error, dan perbaiki sebelum menyatakan deployment selesai. Tidak perlu migration, seed, key:generate, Composer install, npm build, atau mengganti konfigurasi database. Jangan menghapus data lama. Tidak ada perubahan public index.php, .htaccess, vendor, konfigurasi routing provider, atau .env.

## Verifikasi setelah pasang

- Logout: beranda bagian bawah menampilkan tombol panduan; `/panduan` terbuka tanpa login. `/account/guide` tetap meminta login. Tidak ada menu panduan pada panel akun tamu.
- Login pengguna biasa: Akun > Panduan Pengguna; halaman menampilkan daftar isi, langkah bernomor, dan screenshot.
- Buka Profil Saya: tombol Buka panduan pengguna tersedia.
- Ketuk daftar isi, scroll, perbesar gambar, dan kembali ke daftar isi.
- Periksa HP dan komputer; gambar tidak rusak dan teks tidak meluber.
- Akun yang berwenang melihat bagian verifikasi/dashboard/konsol sesuai izin. Pengguna biasa tidak melihat tiga bab itu.

## Memperbarui isi

Teks berada di `config/user_guide.php`. Gambar berada di `public/images/user-guide`, dengan salinan webroot `public_html/images/user-guide`. Setelah perubahan teks, bersihkan config/view cache melalui `php artisan optimize:clear`, lalu optimasi kembali.

## Pengujian

7 tes khusus panduan mencakup autentikasi, akses peran, akun nonaktif, menu/profil, cache privat, kelengkapan gambar, akses publik, serta penyaringan bab admin di halaman publik. QA browser pada 390 × 844 dan 1440 × 900 memeriksa seluruh bab yang dapat diakses, seluruh gambar, daftar isi, dan redirect tamu; tidak ada page error atau overflow horizontal.

Pembuatan screenshot menggunakan database lokal terpisah. Workaround transaksi untuk pembacaan spasial pada lingkungan demo sudah dihapus dan tidak ikut patch; tidak ada perubahan bootstrap atau koneksi database produksi.
