# RuteAman v7.3 — Laporan tanpa login

Patch ini ditujukan untuk source RuteAman v7.2 MariaDB beserta perbaikan routing GeoJSON yang sudah dipasang. Menambahkan form `/lapor` tanpa mengubah APP_KEY, `.env`, kredensial database, akun Owner, atau aturan hosting `.htaccess`/`public_html/index.php`.

## Perilaku

- Tamu memilih sekolah **yang sudah terdaftar, aktif, dan fitur pelaporannya aktif**. Jika sekolah belum ada, pengelola harus menambahkannya melalui wizard; tamu tidak membuat sekolah/akun otomatis.
- Nama/nama samaran opsional; kosong berarti **Pelapor anonim**. Nama dan sekolah yang diisi bukan identitas atau keanggotaan terverifikasi.
- Lokasi wajib berada dalam catchment sekolah. Jenis kondisi, keterangan, foto opsional dan label lokasi tetap tersedia. Foto harus lolos sanitasi GD/WebP.
- Laporan tamu berstatus menunggu verifikasi pengelola dan tidak tampil pada map/feed/detail/media publik. Konfirmasi anggota tidak dapat melewati gate moderasi ini.
- Pengelola yang mempunyai izin verifikasi pada sekolah pilihan dapat melihat nama/keterangan privat, lokasi dan foto, lalu memverifikasi, meminta bukti, atau menolak.
- Setelah keputusan **verified**, marker kondisi dapat tampil publik sesuai filter aktif/riwayat. Nama tamu dan keterangan privat tetap tidak ditampilkan di detail atau payload publik.
- Laporan tamu tidak dihitung sebagai pelapor independen dan tidak membuat User palsu. Konfirmasi tetap membutuhkan login. Pengguna yang sudah login tetap memakai alur laporan akun; `/lapor` mengarahkannya ke form akun.
- Laporan tamu tidak muncul di Laporanku akun. Tanda terima bersifat sesi, bukan URL publik berisi ID/slug laporan. Duplikat tidak menambah laporan, menyimpan nama/foto tambahan atau menambah konfirmasi.
- Jika kondisi tamu kedaluwarsa sebelum ditinjau, tetap ada di antrean moderasi; verifikasi memperbarui waktu kondisi agar data yang baru diperiksa aktif.

## Pemasangan pada struktur hosting pengguna

Struktur yang sudah digunakan:

```
/home/woixvwwz/ruteaman       # source dan .env privat
/home/woixvwwz/public_html    # file publik dan asset
```

ZIP berisi dua folder: **ruteaman/** (file source berubah, migration, docs, tes, build) dan **public_html/build/** (salinan build publik yang sama). Tidak berisi `.env`, vendor/node_modules, database, unggahan, `public_html/index.php`, `.htaccess`, atau API key.

1. Backup database, folder aplikasi, `.env`, dan `public_html` dahulu. Jika ada perubahan source khusus selain setup hosting/routing, bandingkan file sebelum overwrite; patch adalah file lengkap berdasarkan baseline v7.2, bukan merge otomatis.
2. Aktifkan maintenance dari Terminal:

```bash
cd /home/woixvwwz/ruteaman
php artisan down
```

3. Unggah ZIP lalu ekstrak ke **`/home/woixvwwz`**, bukan ke `public_html` dan bukan ke `ruteaman`. Struktur arsip akan menempatkan file di `ruteaman/...` dan `public_html/build/...`. Izinkan overwrite file yang memang ada di patch. Bila File Manager tidak menggabungkan folder, copy isinya secara manual sesuai path tersebut.
4. Jalankan:

```bash
cd /home/woixvwwz/ruteaman
php artisan migrate --force
php artisan optimize:clear
php artisan optimize
php artisan up
```

Jika migration gagal, berhenti dan periksa error; jangan menjalankan up sampai kode/skema cocok. Migration menambah kolom dan index, tidak menghapus data lama. **Jangan migrate:fresh, jangan seed demo, dan jangan key:generate di produksi.**

5. Muat ulang browser. Karena asset di hosting dilayani dari `public_html/build`, kedua salinan build dalam ZIP harus dipasang. Tidak perlu npm/Composer untuk patch ini.
6. Tes sebagai tamu: logout atau gunakan jendela samaran, buka `https://ruteaman.my.id/lapor` atau tombol Lapor. Pilih sekolah, lokasi dan jenis, biarkan nama kosong, setujui ketentuan, lalu kirim.
7. Login admin berizin verifikasi pada sekolah itu. Buka antrean `/verifier/queue` atau menu Antrean Verifikasi, periksa data, lalu verifikasi. Owner/Admin Platform memilih sekolah terkait lewat navigasi admin jika memiliki banyak sekolah. Pada antrean yang memakai parameter sekolah, gunakan `/verifier/queue?school=ID_SEKOLAH` yang berasal dari daftar sekolah, bukan menebak ID.
8. Cek marker sesudah verified; nama tamu tetap tidak publik. Uji juga laporan pengguna yang login.

Perbaikan header `Accept: application/geo+json` tetap disertakan pada provider routing. Tidak mengganti API key.

## Keamanan dan batas

- CSRF tetap aktif, honeypot kosong, token form sesi satu kali, waktu form minimal 2 detik, batas 3 pengiriman/menit dan 30/hari per alamat jaringan. HMAC IP sementara disimpan lewat cache untuk pembatasan; bukan identitas independen. Pakai cache persisten (default file), bukan array pada produksi.
- IP bersama dapat terkena batas yang sama. Jika memakai reverse proxy/Cloudflare, konfigurasi trusted proxy harus sesuai hosting agar alamat klien yang dipakai pembatasan benar; jangan mempercayai header dari semua sumber secara sembarangan.
- Honeypot/throttle bukan CAPTCHA dan tidak menjamin kebal bot. CAPTCHA dapat ditambahkan kemudian jika spam nyata terjadi, dengan konfigurasi layanan yang terpisah.
- Jangan meminta nama lengkap siswa atau alamat rumah. Tanpa akun tidak berarti tidak ada cookie/log server. Pengelola harus menentukan retensi data tamu dan prosedur koreksi/penghapusan; patch tidak menambahkan pembersihan data otomatis.
- Identitas tamu tidak dapat dibuktikan; larangan self-verification berdasarkan akun tidak membuktikan bahwa pengelola bukan orang yang sama dengan pelapor anonim.
- Jangan rollback migration sendirian setelah ada laporan tamu: itu menghapus metadata dan gate moderasi. Untuk pemulihan gunakan backup source+database yang konsisten; jangan menimpa APP_KEY.
- Uji lokal dilakukan pada PHP 8.4.24 dan MariaDB 10.11.18. Hosting pengguna PHP 8.5/MariaDB 10.11.19 belum diuji langsung untuk patch tamu; lakukan smoke test sesudah memasang.
