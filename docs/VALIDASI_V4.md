# Validasi v4

## Sudah dijalankan di sandbox

- PHP 8.4.24 / Laravel 13.33.0.
- PostgreSQL 16 + PostGIS: empat migrasi dan DemoSeeder berhasil.
- PHPUnit: 7 test, 80 assertion lulus (unit + integrasi spatial).
- Alur integrasi: 22 → laporan genangan baru (tetap 22) → duplikat tanpa laporan tambahan → 1 konfirmasi (tetap new) → konfirmasi sekolah B → 47 → verifikasi Admin → 67.
- Retry konfirmasi tidak memperbarui umur data; retry verifikasi yang sama tidak menambah catatan.
- Scope catchment diuji untuk verifikasi, paket, dan update eskalasi.
- Feed tidak mengandung identitas pelapor; hazard TTL kedaluwarsa langsung dihilangkan saat baca.
- Foto hasil sanitizer WebP, tanpa EXIF pada fixture uji.
- Semua file PHP source lolos syntax lint; Blade lolos kompilasi; JS lolos node --check; Vite build berhasil.
- Playwright lokal: peta desktop/mobile, login, form, dan dialog duplikat; tidak ada error JS; tidak ada horizontal overflow halaman/form.

## Belum diuji / tidak boleh diklaim

- MySQL Spatial/Laragon belum diuji langsung. Query longitude-first dan polygon-point diperbaiki untuk MySQL 8.0.24+; tetap jalankan pengujian pada mesin Laragon.
- Tidak ada hasil audit lapangan, survei, pilot, metrik dampak, atau klaim akurasi prediktif.
- Tidak ada integrasi BMKG otomatis, pemerintahan dua arah, pengiriman WhatsApp otomatis, maupun cache tile offline.
- Foto demo adalah ilustrasi, bukan bukti bahwa hazard nyata berada pada koordinat simulasi.


## Patch v4.1 — direktori sekolah

- 14 test / 96 assertion lulus, termasuk fallback Dapodik kosong, pencarian nama/NPSN, filter wilayah, error upstream 503, dan impor geometri sekolah pada PostGIS.
- Live API lewat service Laravel: 20363192 serta MAN 2 REMBANG keduanya ditemukan.
- API Dapodik mengembalikan data=[]; direktori cadangan mengembalikan MAN 2 REMBANG, Lasem, Kab. Rembang, Jawa Tengah.
- Referensi resmi: https://referensi.data.kemendikdasmen.go.id/pendidikan/npsn/20363192
- Namespace cache diperbarui; hasil kosong tidak disimpan. Build frontend dan lint PHP/JS berhasil.


## Patch v4.2 — register 429

- 20 test / 238 assertion lulus.
- Sukses mendaftar setelah >6 request direktori sekolah; login dan daftar tidak berbagi counter.
- Direktorinya tetap dibatasi 60/menit; daftar tetap 6 POST/menit. Pembatas tidak dimatikan.
- JSON throttled tetap 429 + Retry-After; HTML auth kembali ke form dengan pesan dan input non-password.
- HTTP lokal dengan cookie jar memverifikasi pesan pembatas muncul di form pada POST daftar ke-7.
- Pendaftaran akun (import sekolah, create user, login, redirect) diuji di PostGIS dengan fixture upstream; tanpa membuat akun ke layanan eksternal.
- Build dan lint lulus. Tidak ada migrasi tambahan untuk patch ini.


## Patch v4.3 — panel pencarian landing

- Reproduksi sebelum fix menunjukkan bagian bawah form tertutup welcome section.
- Visual QA setelah fix: desktop 1702×900, mobile 390×844, serta reduced-motion; form tampil utuh dan kontrol terbaca.
- 16/16 kombinasi breakpoint dan reduced-motion lolos hit-testing: input/tombol tidak tertutup section/canvas dan tidak ada horizontal page overflow.
- Build Vite serta JS syntax check berhasil. PHPUnit non-spatial dijalankan; test spatial memang dilewati pada run tanpa flag/database spatial. Full suite PostGIS terakhir tetap tercatat pada v4.2 (20 test/238 assertion); kode backend tidak berubah pada v4.3.


## Patch v4.4 — input tujuan sekolah

- 25 test / 256 assertion lulus pada PostGIS.
- Input kosong, editable, tanpa readonly/disabled; pilihan nama dan NPSN menentukan sekolah tujuan.
- Nama tidak ditemukan/ambigu menghasilkan pesan, bukan fallback diam-diam.
- Pengetikan browser desktop/mobile berhasil; screenshot dua ukuran sudah diperiksa. Build dan kompilasi Blade berhasil.


## Patch v4.5 — pilihan sekolah tidak hilang pada submit

- 31 test / 276 assertion lulus pada PostGIS.
- Pemulihan primary hidden NPSN yang kosong, typed NPSN tepat 8 karakter, preservasi old input setelah password validation error, penolakan wilayah salah, dan prioritas NPSN primer diuji.
- Lima browser case desktop/mobile dengan fixture respons API: pilih → event input terlambat → hidden fields kosong → submit, serta restore form dan missing-selection guard. POST masih membawa school_npsn=20363192, selected_school_npsn=20363192, district_code=33.17.14.
- Browser test memakai fixture lokal dan intercept POST; tidak membuat akun pengguna nyata di layanan eksternal. Backend account creation/import diuji pada DB spatial sementara.
- Pilihan tersimpan dan NPSN terlihat jelas pada visual QA desktop/mobile; tidak ada horizontal page overflow atau error JS.
- Build Vite, node syntax check, dan kompilasi Blade berhasil.
