# Patch pencarian sekolah pada formulir Semua Pengguna

Dropdown lama mengambil hanya tabel sekolah yang sudah terdaftar; tidak dibatasi satu sekolah. Patch ini menambah pencarian nama/NPSN terdaftar + direktori nasional pada pembuatan/edit profil/peran pengguna oleh Owner.

- Hasil terdaftar: Pilih sekolah, langsung mengisi school_id.
- Hasil direktori: Daftarkan & pilih; perlu konfirmasi eksplisit, reauth, CSRF dan izin Owner server. Pencarian GET tidak mengimpor sekolah.
- NPSN ditelusuri ulang server; tidak mempercayai nama/koordinat browser. Memakai importer direktori yang sudah ada, dengan lock transaksi yang sama. Existing NPSN tidak diimpor ulang; sekolah arsip tidak dipulihkan diam-diam.
- Sekolah baru tetap terdaftar walaupun pembuatan akun dibatalkan. Ini tindakan terpisah yang ditampilkan dalam dialog konfirmasi.
- Sekolah baru dijeda, pelaporan nonaktif, slug unik dan created_by Owner dicatat. Tidak membuat akun otomatis. Owner perlu meninjau pin/catchment sementara dari importer, BMKG, timezone serta fitur di menu Sekolah, mengaktifkan fitur pelaporan bila dikehendaki, kemudian memulihkan status. Tidak mengaktifkan pemantauan hanya karena dipilih.
- Lookup gagal tidak membuat pilihan palsu. Sekolah lokal masih tersedia bila layanan nasional gagal.

## Pemasangan

Prasyarat: patch konsol Owner/dua tingkat dan patch pencarian sekolah tamu nama/NPSN sudah terpasang (DapodikSchoolDirectory memiliki searchNationwide dan guestPreview). Tidak ada migrasi atau pembaruan Composer/Vite pada patch kecil ini.

Backup source/build/DB dahulu. Ekstrak ZIP di `/home/woixvwwz`, BUKAN di `/home/woixvwwz/ruteaman`, jika source dan public_html mengikuti struktur sebelumnya. Folder `ruteaman` menimpa file source yang sesuai; `public_html/js/admin-school-picker.js` merupakan file webroot publik. Jika webroot langsung ruteaman/public, salinan public_html tidak diperlukan. Jangan membuat ruteaman/ruteaman.

Source yang berubah: routes/admin.php, app/Http/Controllers/AdminSchoolPickerController.php, resources/views/admin/users/form.blade.php, resources/views/admin/school-picker.blade.php, public/js/admin-school-picker.js. Tidak ada .env/vendor/DB/akun uji operasional dalam ZIP. Hotfix aktivitas sebelumnya tidak ditimpa.

Dari source jalankan:

```bash
php artisan optimize:clear
php artisan route:list --path=admin/school-picker
php artisan view:cache
```

Harus ada GET admin/school-picker/search dan POST admin/school-picker/register. Refresh paksa browser, buka Semua Pengguna → Buat akun baru, ketik minimal3 karakter nama/NPSN, Cari sekolah, lalu Pilih atau Daftarkan & pilih. Pastikan JS webroot dapat diakses di /js/admin-school-picker.js.

## QA aktual

8 tes endpoint baru +6 tes komponen aktivitas sebelumnya:14 tes/54 assertions lulus di SQLite in-memory. Direktori/impor eksternal di-mock: gabungan hasil tanpa import saat GET, fallback outage, registrasi paused/reportingfalse/audit/no account, no duplicate existing, archived reject, school_admin403, reauth428, invalid input422. PHP/JS syntax, Pint3 file PHP dan Blade cache lulus.

Browser preview hanya komponen picker dengan respons API sintetis (bukan hosting). Hasilnya ada di QA/school-picker-browser.json. Suite penuh MariaDB/PostgreSQL dan API nasional live tidak diuji ulang untuk patch ini. Importer spatial yang dipakai ulang berasal dari patch sebelumnya; test endpoint baru tidak menguji ulang SQL geometri importer terhadap DB spatial asli. Hosting tidak disentuh.
