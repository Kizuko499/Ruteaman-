# Perbaikan pencarian sekolah online nasional

Patch pengganti picker akun sebelumnya. Dropdown awal hanya menunjukkan sekolah lokal; field pencarian sekarang selalu mencari ke Dapodik dan sumber sekolah nasional cadangan, lalu menggabungkan/deduplikasi NPSN. Tidak mengunduh seluruh database nasional atau menampilkan ratusan ribu option sekaligus. Maksimum30 hasil per pencarian; gunakan nama resmi/NPSN lebih spesifik. Cakupan/keterkinian mengikuti dua penyedia, bukan jaminan100% semua sekolah selalu tersedia.

## Penyebab yang diverifikasi

Bug lama menganggap semua teks8 karakter sebagai NPSN; Sukamaju terbukti menghasilkan0 walaupun API memiliki sekolah terkait. Fallback juga hanya dipakai bila respons Dapodik mentah kosong, bukan setelah hasil disaring. Kedua hal diperbaiki. Singkatan SDN/SMPN/SMAN/SMKN/MIN/MTSN/MAN dicoba sebagai variasi nama negeri saat pencarian awal kosong. Konfigurasi sumber yang hilang kini memakai default host dikenal. HTTP User-Agent eksplisit ditambahkan; dalam probe lokal beberapa permintaan tanpa header mendapat403, dengan header berhasil. Ini bukan bukti bahwa penyebab hosting persis sama.

Tidak menyembunyikan kegagalan kedua sumber sebagai hasil kosong. UI menampilkan jumlah hasil online dan pesan terpisah bila layanan tidak terhubung. Hasil positif disimpan15 menit, hasil kosong tidak disimpan; cache nasional versi4 menghindari hasil lama. Host endpoint tetap allowlist, TLS tidak dimatikan, log kegagalan pencarian tidak berisi token/HTTP body/headers.

## Sumber online

- Portal Dapodik: https://dapo.kemendikdasmen.go.id/
- API cadangan: https://sekolah.devapi.id/sekolah; parameter nama/NPSN sesuai dokumentasi penyedia. [^https://github.com/ikhsan-rfl/api-sekolah]
- API wilayah untuk validasi lokasi: https://wilayah.id/api

Pencarian tidak menciptakan sekolah/akun. Daftarkan & pilih tetap Owner-only, reauth, CSRF, transaksi/audit. Sekolah baru dijeda/reportingfalse sampai Owner meninjau pin/catchment/BMKG/timezone serta fitur. Sekolah arsip tidak dipulihkan diam-diam.

## Pemasangan

Prasyarat: konsol Owner terbaru dan fondasi direktori sekolah/patch guest-search sudah ada. ZIP menyertakan picker UI/backend sebelumnya dan service direktori yang diperbaiki, jadi tidak perlu memasang ulang ZIP picker lama setelah ZIP ini.

Backup source dan DB. Ekstrak di /home/woixvwwz, BUKAN di /home/woixvwwz/ruteaman. Source harus ke /home/woixvwwz/ruteaman, public JS ke /home/woixvwwz/public_html/js. Jika document root ruteaman/public, gunakan JS pada ruteaman/public/js. Tidak ada .env/vendor/DB/dump/kredensial pengguna atau migrasi baru; tidak perlu build Vite/Composer update.

```bash
cd /home/woixvwwz/ruteaman
php artisan optimize:clear
php artisan route:list --path=admin/school-picker
php artisan view:cache
```

Refresh paksa. Cari MAN 1 REMBANG, SMP NEGERI 1 SURABAYA, atau NPSN20363196. Bila hasil tetap kosong, lihat pesan di bawah pencarian. Pemeriksaan live dari hosting (hanya baca, tidak mendaftarkan sekolah):

```bash
php artisan tinker --execute="dump(app(\App\Services\DapodikSchoolDirectory::class)->searchNationwide('MAN 1 REMBANG'));"
```

Jika ada kegagalan, kirim screenshot pesan yang tidak memuat rahasia. Hosting membutuhkan DNS/HTTPS keluar ke ketiga host; jangan mematikan TLS untuk mengatasi masalah sertifikat. Konfigurasi sumber override .env yang menunjuk host lain tetap ditolak, bukan diikuti sembarang. Rate limit penyedia berlaku; jangan scraping seluruh sekolah berulang-ulang.

## QA yang benar-benar dijalankan

Service PHP aktual diuji ke API live dengan TLS verifytrue, bukan hanya respons tiruan:

- MAN 1 REMBANG:1 hasil, NPSN20363196.
- SMP NEGERI 1 SURABAYA:1 hasil, NPSN20532613.
- NPSN20363192:MAN 2 REMBANG.
- Sukamaju:sebelumnya0, sesudah patch30 hasil (batas pencarian).

Output normalized nama/NPSN/wilayah dari service ada dalam QA/live-before-directory.json dan live-after-directory.json; bukan dump data mentah/kontak/token. Preview detail sekolah live dicatat terpisah dalam QA/live-online-preview.json.

9 tes regresi service online +8 tes endpoint picker +6 tes aktivitas:23 tes/73 assertions lulus di test terisolasi; API dalam tes regresi di-mock. Browser komponen lokal memakai respons API sintetis, diuji360/768/1280 tanpa overflow/axe violation/JS error. PHP3 file Pint, JS syntax, Blade cache dan daftar route lulus. Suite penuh MariaDB/PostgreSQL, import geometri ke DB spatial aktual dan pemasangan hosting tidak diuji ulang untuk patch ini. Tidak mengklaim server hosting sudah diperbaiki hanya berdasarkan tes lokal.
