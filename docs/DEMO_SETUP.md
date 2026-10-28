# Demo RuteAman v4

Versi ini mengikuti `RuteAman_Skrip_Demo_Asli.md`. Gunakan `RuteAman_Skrip_Demo.md` untuk presentasi: narasi sudah dikoreksi agar sesuai implementasi. Jangan menganggap simulasi sebagai hasil audit/pilot.

## 1. Persiapan Laragon

- PHP 8.3+ (8.4 direkomendasikan); ekstensi GD dengan WebP, mbstring, XML, PDO MySQL.
- Versi v7.2: MariaDB 10.11 tanpa PostGIS (`DB_CONNECTION=mariadb`), lihat `MARIADB_CPANEL_SETUP.md`. Source MySQL 8 / PostgreSQL+PostGIS tetap dipertahankan sebagai alternatif baseline; tidak diuji ulang pada patch v7.2.
- Pakai database demo **terpisah**. Konfigurasi `.env` sesuai database; APP_ENV=local.
- Migrasi ini menambahkan `saved_routes`, penanda `is_demo`, serta mengubah role `verifier` menjadi `school_admin`. Migrasi biasa tidak menghapus database.

Dari root proyek, setelah dependensi dan `.env` tersedia:

```bash
composer install
php scripts/ensure-storage.php
php artisan migrate
php artisan optimize:clear
php artisan storage:link
php artisan ruteaman:demo-reset --yes
npm install
npm run build
php artisan serve
```

Jika pemasangan baru, buat APP_KEY memakai `php artisan key:generate` terlebih dahulu. Jangan mengganti APP_KEY pada instalasi lama yang masih digunakan.

`ruteaman:demo-reset` hanya boleh local/testing. Menghapus hazard milik akun demo khusus yang tidak memiliki bukti pengguna non-demo, lalu membuat ulang fixture lubang dan rute A/B. DemoSeeder sendiri idempoten untuk fixture-nya, tetapi tidak menghapus genangan latihan sebelumnya; gunakan perintah reset. Tidak diperlukan `migrate:fresh`.

## 2. Akun dan tab

Password semua akun demo: **RuteAman123!**

| Tab / konteks | Akun | Sekolah | Role |
|---|---|---|---|
| Publik / incognito | tanpa login | pilih Sekolah A — Demo Simulasi | publik |
| Profil browser 1 | siswa_1@ruteaman.test | A | Siswa |
| Profil browser 2 | siswa_2@ruteaman.test | A | Siswa |
| Profil browser 3 | siswa_3@ruteaman.test | B | Siswa |
| Profil browser 4 | admin_a@ruteaman.test | A | Admin Sekolah |

**Tab biasa di profil yang sama berbagi sesi login.** Gunakan profil browser/container berbeda, atau login/logout bergantian. Role switcher belum disediakan dan tidak boleh membuka akses role hanya dari frontend. Akun audit tambahan tidak aktif dan tidak bisa login. Akun `siswa@`, `admin@`, dan `verifikator@` dari seeder lama tetap kompatibel; verifikator lama sekarang role Admin.

## 3. Data skenario

- Sekolah A/B adalah fiktif dan memiliki catchment tumpang tindih.
- Rute A 1.800 m; Rute B 2.100 m. Jarak adalah angka skenario, belum pengukuran audit lapangan. Durasi tidak dikarang: UI menampilkan belum diaudit.
- Lubang Jl. Mawar: -6.1780, 106.8250. Dua laporan historis independen, dua konfirmasi, foto ilustrasi, verifikasi, dikonfirmasi 3 hari lalu. Laporan historis kedua adalah fixture sebelum deteksi duplikat, bukan aksi yang dibuat oleh alur laporan baru.
- Genangan depan pasar: **latitude -6.1768, longitude 106.8250**. Awalnya belum ada; laporkan live memakai siswa_1 dan unggah foto.
- Foto wajib untuk memperoleh skor demo yang sama. Foto opsional dalam produk; tanpa foto skornya memang berbeda. Jangan unggah wajah/plat nomor.
- Cache hujan manual/demo berlaku **8 jam** sejak reset, bukan data BMKG. Hujan menambah w=1.5 untuk genangan, bukan lubang. Reset tepat sebelum demo.

## 4. Urutan dan hasil yang harus terlihat

1. Peta publik → pilih Sekolah A → otomatis Rute A/B tersimpan (tanpa API routing, tanpa GPS). A: 22 Ringan; B: no_report + peringatan bukan jaminan.
2. Buka detail lubang → dua laporan, dua konfirmasi; penjelasan c=1, f=1, w=1, h=2.
3. siswa_1 → Lapor → ketuk peta / isi titik pasar → Genangan → foto → Kirim. Empat **tahap**, bukan janji empat sentuhan termasuk keyboard/pemilih foto. Menunggu konfirmasi; belum dihitung ke Beban rute.
4. siswa_2 → Lapor pada jenis/titik sama → dialog duplikat → Masih ada. Laporan tetap 1, konfirmasi 1. Status masih Menunggu konfirmasi. Backend juga mencegah duplikat jika request melewati dialog.
5. siswa_3 dari B → peta B menampilkan hazard sama → Masih ada. Laporan 1 + konfirmasi 2 = tiga pengguna independen; status Dikonfirmasi Komunitas. Peta A: 47 Sedang.
6. admin_A → Dashboard → Antrean verifikasi → catatan singkat tanpa data siswa → Verifikasi. Peta A: 67 Berat; audit log bertambah.
7. Dashboard / detail paket → Buka halaman publik → Salin pesan WhatsApp → kirim **manual** ke kanal resmi yang benar → tandai Diteruskan. Waktu penerusan otomatis disimpan. Status juga tersedia Ditanggapi/Tidak ada respons. Tidak ada pengiriman otomatis atau integrasi dua arah pemerintah.
8. Feed `/api/feed/hazards.geojson` → tidak memuat reporter_id, user_id, nama, email atau metadata GPS foto. `?school=<id>` untuk catchment tertentu.

Pelapor tidak dapat mengonfirmasi laporannya sendiri. Retry pengguna yang sama tidak menambah konfirmasi atau mengubah umur data. Admin tidak dapat memverifikasi laporan sendiri dan tidak dapat memperbarui eskalasi di luar catchment. Hazard wilayah bukan duplikat per sekolah.

## 5. Rumus 22 → 47 → 67 (Bagian 8.6)

```text
c = min(1, 0.10*min(laporan,3) + 0.10*min(konfirmasi,3) + 0.20*foto + 0.40*verifikasi)
h = severity * c * freshness * weather_multiplier
Beban = round(min(100, 100*sum(h)/9))
Lubang: c=1; h=2; L=22
Genangan komunitas: c=.10+.20+.20=.50; h=3*.5*1*1.5=2.25
Gabungan: sum=4.25; L=47
Genangan terverifikasi: c=.90; h=4.05
Gabungan: sum=6.05; L=67
```

Angka adalah heuristik, bukan akurasi prediktif. Detail titik menampilkan kontribusi titik; detail rute menjumlah seluruh hazard. TTL kedaluwarsa langsung difilter saat baca; penanda expired juga bisa disimpan dengan `php artisan ruteaman:expire` / scheduler.

## 6. Batas implementasi dan klaim yang harus dihindari

- Tidak ada klaim rute aman/teraman; tidak ada laporan bukan jaminan.
- Peta publik tidak perlu akun dan aplikasi tidak melacak perjalanan, tetapi server tetap memiliki sesi/log operasional biasa. Jangan menyatakan “tidak ada data pribadi apa pun dikumpulkan”.
- Checkbox pendaftaran adalah deklarasi persetujuan orang tua, **bukan verifikasi identitas wali**. Perlu prosedur sekolah untuk pilot nyata.
- Foto di-re-encode, metadata GPS dibuang; tidak otomatis menyamarkan wajah/plat. Jika GD/WebP gagal, upload ditolak.
- Cache tile offline/prefetch dan video cadangan bukan fitur source. Tile OpenStreetMap tetap memerlukan internet; hindari bulk caching yang melanggar kebijakan provider. Siapkan rekaman cadangan sendiri.
- BMKG otomatis belum terhubung; cache/manual tersedia. Tidak ada hasil survei atau pilot dalam seed.
- Graph routing opsional memerlukan API key/koneksi; rute tersimpan tidak. Tidak merekam riwayat titik asal.

## 7. Pengujian

Unit test tanpa database:

```bash
php vendor/bin/phpunit --testsuite Unit
```

Integration test memerlukan database spatial khusus yang sudah dimigrasi. **Jangan gunakan database produksi.** Set DB_CONNECTION/DB_HOST/DB_PORT/DB_DATABASE/DB_USERNAME/DB_PASSWORD dan RUTEAMAN_SPATIAL_TESTS=1 lewat environment terminal.

```bash
php artisan migrate --env=testing
# Setelah environment DB khusus terset:
php vendor/bin/phpunit --testsuite Feature
```

Contoh PowerShell sebelum pengujian:

```powershell
$env:APP_ENV="testing"
$env:DB_CONNECTION="mysql"
$env:DB_DATABASE="ruteaman_test"
$env:RUTEAMAN_SPATIAL_TESTS="1"
php artisan migrate
php vendor/bin/phpunit --testsuite Feature
```

Test menggunakan transaksi dan fixture demo. Default tanpa RUTEAMAN_SPATIAL_TESTS akan melewati integration test, bukan mencoba SQLite yang tidak memiliki Spatial. Konfigurasi host/kredensial dapat diambil dari `.env` hanya bila benar-benar menunjuk DB uji.
