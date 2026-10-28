# Tombol Kembali pada konsol administrator

Tombol Kembali ditambahkan pada header bersama semua halaman dengan layout admin untuk pengguna berperan administrator. Tidak perlu memilih menu sidebar lagi untuk kembali.

- Jika datang dari halaman /admin di origin yang sama dan tersedia history: browser history.back, mempertahankan URL filter/halaman sebelumnya.
- Jika dibuka langsung, tab baru tanpa history, referrer kosong/eksternal/login/publik, atau referrer halaman sama: fallback ke Ringkasan /admin.
- Referrer tidak disimpan ke localStorage/sessionStorage/DB/log. Tidak mengubah izin atau route. Klik Ctrl/Cmd/Shift/middle tetap perilaku link native.
- Link menuju Ringkasan tetap berfungsi tanpa JavaScript. Tombol ikon+teks, target44px, header dapat wrap pada ponsel. Peringatan form belum disimpan dari aplikasi tetap berlaku saat meninggalkan halaman.

## Pasang

Backup file layout yang ada. Patch ini untuk konsol terbaru yang sudah memakai sidebar ikon/Owner pengguna. ZIP memuat ruteaman/resources/views/admin/layout.blade.php, back-button.blade.php, dan public/js/admin-back.js. Webroot cPanel mendapat public_html/js/admin-back.js yang sama. Tidak menimpa picker sekolah, hotfix aktivitas, .env, database atau build Vite.

Ekstrak ZIP ke /home/woixvwwz (bukan ke folder ruteaman) jika struktur source=/home/woixvwwz/ruteaman dan webroot=/home/woixvwwz/public_html. Jika webroot ruteaman/public, salinan public_html tidak diperlukan.

```bash
cd /home/woixvwwz/ruteaman
php artisan view:clear
php artisan view:cache
```

Tidak perlu migrate, npm build atau Composer update. Refresh paksa browser. Buka Semua Pengguna → Buat akun baru → Kembali. Buka tautan halaman admin langsung di tab baru → Kembali menuju Ringkasan.

## QA aktual

7 tes JS baru lulus; seluruh suite JS19 tes lulus (12 marker lama +7 tombol kembali). JS syntax dan Blade cache lulus. Browser localhost dengan template tombol asli dan halaman sintetis menguji history serta filter, direct fallback, ukuran360/768/1280 dan axe; keluaran ada di QA. Tidak menguji login/backend seluruh aplikasi atau suite DB pada patch template/JS ini. Hosting tidak diubah.
