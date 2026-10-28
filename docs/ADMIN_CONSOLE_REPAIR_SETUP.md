# Pemasangan patch konsol admin RuteAman

Patch inkremental untuk instalasi RuteAman v7.3 dengan perbaikan konsol terbaru dan pencarian sekolah tamu berdasarkan nama/NPSN. Bukan paket instalasi baru. Hosting belum disentuh oleh pembuat patch.

## Sebelum memasang

1. Backup database lengkap serta source, `.env`, APP_KEY, `storage`, unggahan privat, dan build aktif. Simpan backup di luar webroot.
2. Cocokkan versi source. Jika hosting bukan basis v7.3/konsol terbaru, bandingkan `PATCH.diff` terlebih dahulu; jangan menimpa perubahan lokal tanpa review.
3. PHP 8.4+, dependensi Laravel yang sudah terpasang, MariaDB 10.11 atau PostgreSQL 16/PostGIS. Driver MySQL diuji terhadap MariaDB, **bukan server MySQL 8 asli**.
4. Akun migrasi perlu hak DDL dan pembuatan trigger. Guard audit bawaan aktif. Jika hosting menolak hak TRIGGER, minta administrator hosting memasang trigger. Jangan menganggap audit terlindung pada level DB bila trigger gagal.
5. Pastikan ada Owner aktif dan Anda mengetahui kata sandinya sebelum maintenance. Jangan menjalankan seeder demo atau `migrate:fresh`.

## Isi ZIP / cPanel

- `ruteaman/`: source yang berubah, tes, dokumentasi, dan build lengkap saat ini.
- `public_html/build/`: salinan build yang sama untuk webroot cPanel terpisah.
- `QA/`: bukti pengujian, skema lokal, dan ringkasan commit; tanpa database/akun/kata sandi fixture.
- `PATCH.diff`: perubahan source terhadap baseline lokal (tidak berisi source panduan publik pengganti).
- `SHA256SUMS.txt`: checksum isi paket.

Untuk struktur hosting yang sebelumnya dipakai, ekstrak di `/home/woixvwwz` sehingga source masuk ke `/home/woixvwwz/ruteaman` dan build publik masuk ke `/home/woixvwwz/public_html/build`. Jika direktori berbeda, sesuaikan. Bila document root langsung menunjuk `ruteaman/public`, salinan `public_html` tidak diperlukan. Pastikan `public/build` dan webroot build memakai manifest/asset dari **build yang sama**. Asset lama boleh dibersihkan setelah deploy berhasil, bukan sebelum backup.

Tidak ada `.env`, `vendor`, `node_modules`, DB, sesi, fixture, unggahan pribadi atau penggantian `UserGuideController.php` dalam ZIP. `routes/web.php` tidak diubah. Panduan publik asli hosting harus dipertahankan. Tidak perlu menjalankan npm untuk patch ini; asset produksi sudah dibangun. Dependensi tidak diubah, sehingga tidak perlu memperbarui Composer hanya untuk patch ini.

## Langkah terminal

Masuk maintenance **sebelum** menimpa source:

```bash
cd /home/woixvwwz/ruteaman
php artisan down
```

Unggah dan ekstrak source/build sesuai struktur di atas, lalu:

```bash
php artisan optimize:clear
php artisan migrate --force
php artisan ruteaman:audit:inspect
php artisan ruteaman:admin:report-legacy-tiers
php artisan optimize
php artisan up
```

Jika migrasi gagal, jangan lanjutkan `up` seolah berhasil. Periksa pesan dan skema karena DDL MariaDB tidak selalu transaksional. PK dengan ID NULL/duplikat dihentikan tanpa renumber histori; perlu rekonsiliasi salinan DB dan referensi terlebih dahulu. Jangan menghapus histori agar migrasi lolos.

## Pengaturan penting

```dotenv
# Bawaan: konteks IP/UA dicatat di audit privat.
AUDIT_CAPTURE_CONTEXT=true
# Bawaan: trigger menolak UPDATE/DELETE pada audit.
AUDIT_DB_GUARDS=true
# Bawaan: maksimum Owner aktif + undangan pending.
OWNER_MAX_COUNT=3
# Pemeriksaan sandi terhadap layanan breach eksternal opsional, bawaan false.
ADMIN_PASSWORD_UNCOMPROMISED=false
```

`AUDIT_RETENTION_DAYS` belum diputuskan dan tidak perlu diisi sekarang. Tidak ada job penghapusan otomatis. **Mengisi variabel itu saja tidak melakukan purge**. Retensi/penghapusan konteks perlu keputusan tersendiri dan prosedur DB dengan hak terbatas; histori audit tetap append-only.

Jika hak trigger benar-benar tidak tersedia, `AUDIT_DB_GUARDS=false` merupakan kompromi eksplisit sebelum migrasi: aplikasi tetap menolak edit/hapus audit, tetapi query SQL langsung tidak dilindungi trigger. Catat dan terima risiko dahulu; rekomendasi tetap menggunakan trigger. Mengubah env menjadi true setelah migrasi yang dilewati tidak otomatis membuat trigger—perlu pemasangan terkontrol oleh pengelola DB.

Trigger tidak mencegah administrator DB berprivilege tinggi melakukan DDL/TRUNCATE/drop trigger. Gunakan kredensial migrasi terpisah dari runtime dan least privilege. Tidak ada route aplikasi untuk truncate/edit/delete audit.

## Pemeriksaan setelah deploy

- Owner: menu Semua Pengguna; cari lintas sekolah, edit profil, ubah izin/tingkat, reset sandi, revoke sesi, suspend/aktifkan. Jangan memakai akun produksi untuk uji penghapusan.
- Admin Sekolah: hanya sekolahnya; tidak dapat membuat admin atau mengelola platform. Dua tingkat aktif yang ditawarkan: Admin Utama dan Admin Sekolah.
- Akun lama platform_admin/school_staff tetap bertahan dengan akses lama sampai Owner mengubah tingkat atau menangguhkan secara sadar. Baca hasil laporan CLI; tidak ada konversi otomatis.
- Buat akun uji melalui sandi awal; login mengarahkan ke ganti sandi dan sesi tetap berfungsi setelah perubahan. Email pemberitahuan tidak berisi sandi. Uji SMTP nyata di hosting secara terpisah.
- Sidebar: ciut/buka, tooltip fokus keyboard, reload mempertahankan pilihan; mobile bisa ditutup dengan Esc dan fokus kembali.
- Audit: cek satu perubahan izin uji, snapshot pelaku, sebelum/sesudah, request ID; detail dapat di-scroll di ponsel. Ekspor maksimum 31 hari/10.000 baris mengikuti izin dan cakupan.
- Form `/lapor`: pencarian sekolah nama/NPSN tetap tersedia tanpa login; alur laporan tamu/moderasi tetap dipertahankan.
- Hard refresh browser setelah deploy agar asset lama tidak tertahan cache.

## Pemulihan

Utamakan restore backup source/build yang cocok. Untuk rollback tiga migrasi audit **saja**, verifikasi `migrate:status` dan batch terlebih dahulu; jangan menebak bahwa tiga migrasi terakhir pasti patch ini. Rollback menghapus kolom detail baru dan trigger, sehingga detail audit baru dapat hilang—backup DB sesudah deploy sebelum rollback. Migrasi PK sengaja tidak mencabut ID/PK terverifikasi saat `down()` agar referensi histori tidak rusak. Upgrade/rollback/remigrate diuji pada salinan DB lokal, bukan produksi. Setelah pemulihan, clear cache, gunakan build yang cocok, dan verifikasi Owner serta skema kembali.
