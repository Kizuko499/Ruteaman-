# Laporan perbaikan konsol admin RuteAman

## Ruang lingkup dan temuan awal

Perbaikan lokal pada branch `perbaikan/admin-konsol`, berbasis konsol terbaru dan patch pencarian sekolah tamu v7.3. Tidak mengubah hosting, tidak membuat impersonasi, tidak menambah isi butir 7 (kosong).

Skema **MariaDB lokal nyata** sebelum perubahan sudah memiliki `audit_logs.id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY`; indeks penelusuran masih terbatas. Screenshot PK yang dirujuk prompt tidak terlampir. Skema hosting tidak dapat diverifikasi tanpa akses; laporan ini bukan bukti bahwa PK hosting sudah benar.

Konsol lama belum memberi Owner kendali semua peran/Owner lain; masih menawarkan empat tingkat, audit sederhana, sidebar penuh, pembuatan admin berfokus undangan, serta akun wajib ganti sandi ditolak pada login. Temuan dan keputusan kompatibilitas disampaikan sebelum penerapan.

## Hasil per butir

| Butir | Implementasi | Verifikasi |
| --- | --- | --- |
| 5 PK | Migrasi aditif memverifikasi PK id, menambah PK bila tidak ada ID, mengadopsi ID valid tanpa PK, menghentikan ID NULL/duplikat atau PK lain tanpa ID; indeks created_at, actor/action/school + created_at, occurred_at + id | PK/indeks, duplicate ID, trigger diuji pada MariaDB dan PostgreSQL; metadata skema lokal disertakan |
| 6 Dua tingkat | Konfigurasi, formulir, preset, referensi dan filter hanya owner/school_admin; school_admin tidak dapat membuat admin; enum dan data lama dipertahankan; CLI laporan legacy hanya baca | Validasi tingkat nonaktif, scope, legacy tidak berubah, proteksi Owner diuji |
| 3 Audit | Logger terpusat, snapshot pelaku/target, UTC, sebelum/sesudah, hasil/severity, IP/UA/request ID/route template, filter, detail, cursor keyset stabil, CSV terbatas dengan mitigasi formula | Redaksi rahasia/lokasi, failed/denied response, legacy tanpa snapshot, pagination, ekspor/scope, penolakan UPDATE/DELETE model dan DB diuji |
| 1 Akun | Owner mengelola semua peran/sekolah dan Owner lain; edit profil, izin, peran, sandi, revoke sesi, status, anonimisasi; delete permanen terbatas akun pending kosong | Reauth, konfirmasi ketik nama/sandi, self/last Owner, rollback transaksi dan race last Owner, histori laporan/audit dipertahankan |
| 4 Sandi | Sandi awal atau undangan, hash, kebijakan min12 mixed case/angka/simbol, generator CSPRNG20, show/copy, wajib ganti pertama, pemberitahuan tanpa sandi | Hash, validasi, secret nonflash/redaction, login/ganti/sesi lanjut, undangan nonadmin dan generator browser diuji |
| 2 Sidebar | 260/72px, SVG ikon+teks, tooltip hover/fokus, localStorage non-sensitif, aria, drawer mobile dengan inert/fokus/Esc/backdrop/swipe, reduced motion | Browser360/768/1280 tanpa overflow, persist, tooltip fokus, fokus kembali, axe lima halaman tanpa temuan |

## Keputusan dan batasan yang disengaja

- Agar tidak mencabut akses diam-diam, akun **legacy** platform_admin/school_staff mempertahankan ceiling efektif lama. Aturan platform khusus Owner berlaku untuk dua tingkat aktif baru; legacy merupakan pengecualian transisi yang diketahui. Owner harus meninjau laporan dan memilih migrasi ke Admin Sekolah dengan sekolah yang tepat atau suspend. Tidak mengubah enum ataupun akun otomatis.
- Default maksimum Owner tetap 3 sesuai konfigurasi lama (aktif + pending); Owner terakhir dan perubahan hak/status diri sendiri terlindung. Owner tetap memiliki izin implisit penuh, bukan checkbox yang menurunkan haknya.
- Hapus normal berupa anonimisasi; ID/histori laporan dan audit bertahan. Hapus permanen hanya akun pending yang tidak punya aktivitas/referensi yang diperiksa. Tidak menyediakan penghapusan massal histori.
- PK invalid dihentikan, bukan dinomori ulang diam-diam, untuk melindungi referensi historis. PK terverifikasi sengaja tetap ada saat down; kolom detail/trigger memiliki down. Ini penyimpangan terkontrol dari rollback PK destruktif.
- Entri lama tidak diberi snapshot rekaan; tampilan memakai “—”. Snapshot audit privat yang sudah ada tidak ditulis ulang saat akun dianonimkan.
- Retensi IP/UA **belum diputuskan**. `AUDIT_RETENTION_DAYS` belum memiliki nilai; tidak ada purge/job otomatis. Trigger immutable dan retensi harus dirancang bersama setelah kebijakan disetujui.
- Breach-check eksternal opsional, bawaan false agar tidak bergantung jaringan saat membuat akun. Sandi tidak dikirim lewat email. Undangan/reset satu kali tetap tersedia.
- Trigger melindungi UPDATE/DELETE biasa, bukan tindakan DBA berprivilege DDL/TRUNCATE. Runtime harus least privilege; tidak ada fitur aplikasi untuk edit/hapus/truncate audit.
- Butir 7 kosong. Tidak dibuat fitur tambahan untuk butir itu.

## Pengujian yang benar-benar dijalankan

Seluruh tes PHP dijalankan dengan spatial tests aktif, **tanpa migrate:fresh**. DB pengujian disposable, bukan hosting.

| Gerbang | Hasil akhir |
| --- | --- |
| MariaDB 10.11, driver mariadb | 167 tes lulus, 1.436 assertions |
| PostgreSQL16/PostGIS, driver pgsql | 167 tes lulus, 1.436 assertions |
| Driver mysql terhadap MariaDB10.11 | 167 tes lulus, 1.436 assertions; bukan MySQL8 asli |
| Tes JS marker | 12 lulus, 0 gagal, 0 dilewati |
| Build Vite produksi | Lulus; manifest dan asset lengkap di ZIP |
| Blade cache dan route:list middleware | Lulus; hasil mentah disertakan |
| Pint file PHP berubah | Lulus; hasil mentah mencantumkan jumlah file |
| Pint seluruh repo | **Gagal: 170 file, 122 style issues** pada source lama yang tidak seluruhnya direformat; tidak mengklaim seluruh repo bersih |
| Composer audit / npm audit --omit=dev | 0 vulnerability advisories ditemukan saat pengujian |
| Browser Chromium + axe | 5 halaman/ukuran tanpa pelanggaran axe; tanpa JS error; 360/768/1280 tanpa overflow body |
| Panel audit mobile | bounding box top0, lebar360, tinggi800; fokus/scroll detail berfungsi |
| Upgrade salinan DB berisi data | migrate → rollback3 → remigrate lulus; digest ID/konten audit lama, pengguna/peran/izin/hash/status, laporan tidak berubah |
| Race Owner MariaDB | Dua mutasi bersamaan: satu berhasil, satu ditolak LAST_OWNER; tetap 1 Owner aktif |

Kegagalan antara yang diperbaiki: eksekusi Pint awal kehilangan izin executable; PostgreSQL sempat connection refused setelah restart lingkungan (ownership data dir), kemudian dipulihkan dan suite penuh dijalankan ulang; tooltip fokus awal tidak muncul lalu diperbaiki dan diuji ulang. Satu-satunya gerbang akhir yang masih gagal adalah Pint seluruh repo seperti di atas.

## Belum diverifikasi / tidak termasuk

- Instalasi, skema, trigger privileges, SMTP delivery nyata, cache/CDN dan perilaku di hosting produksi.
- Server MySQL8 asli; driver mysql memakai MariaDB. Race concurrency terpisah hanya di MariaDB, bukan PostgreSQL.
- Semua kombinasi browser/device dan audit accessibility manual menyeluruh; axe bukan jaminan aksesibilitas sempurna.
- Source asli controller panduan publik tidak tersedia pada baseline lokal. Stub QA digunakan hanya untuk boot lokal dan **tidak dimasukkan paket/diff**. Halaman panduan publik asli hosting tidak diganti; perilakunya di luar QA patch ini.
- Tidak melakukan penghapusan audit lama atau menetapkan retensi sepihak.

## Bukti dan perubahan dokumen

Folder `QA` dalam paket memuat output akhir PHP/JS/build/Blade/routes/Pint/audit dependency, schema inspection lokal, ringkasan browser, dan hasil upgrade/race tanpa dump DB atau rahasia fixture. Tes regresi baru: `tests/Feature/AdminRepairTest.php` (23 kasus); tes lama TieredAdmin diperbarui sesuai dua tingkat dan reauth. Screenshot generator yang berisi sandi sintetis tidak dikirim.

Dokumen admin lama diberi peringatan historis: empat tingkat dan preset lama bukan daftar aktif baru. Ini menggantikan bagian terkait pada Prompt_Admin_Berjenjang_RuteAman tanpa menghapus nilai enum/histori. README dan panduan dalam konsol diperbarui. Lihat `ADMIN_CONSOLE_REPAIR_SETUP.md` untuk pemasangan dan konsekuensi rollback.
