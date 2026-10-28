# QA menu akun v5.0

## Hasil otomatis
- PHP 8.4, PostGIS 16, Laravel 13: **46 tests / 410 assertions**, seluruh suite termasuk regresi direktori sekolah, registrasi, rate limit, hazard, dan rute tersimpan.
- Sintaks PHP, kompilasi Blade, route akun, serta Vite production build berhasil.
- Browser Chromium lokal: lima halaman akun diuji pada **360/768/1280 px**, tanpa overflow horizontal atau pageerror pada suite akun.
- Interaksi nyata dengan server: buka/tutup menu, Escape/focus return/trap, geser pegangan/latar, nama via AJAX, preset avatar tersinkron, validasi field, crop + zoom/panah, unggah privat/hapus foto, preferensi, lihat/sembunyikan password, consent gate, feed notifikasi berisi data, badge/tandai dibaca, dan daftar pengguna admin.
- Area main mobile berakhir di atas nav, termasuk saat menggulir ke akhir formulir. Dialog crop mobile/desktop dan menu ditinjau secara visual.
- Dua browser dengan session driver file: perubahan password mengeluarkan sesi lama; sesi saat ini tetap aktif.
- axe WCAG 2 A/AA + 2.1 AA: tidak ada pelanggaran pada profil, pengaturan, notifikasi, keamanan, privasi, menu akun, menu dengan badge, dan manajemen pengguna admin dalam pengujian yang dicatat.
- Lighthouse Accessibility profil login: **100** (bukan skor seluruh aplikasi atau seluruh kategori Lighthouse).
- Backend memverifikasi re-encoding WebP, tiga dimensi output, EXIF/GPS fixture hilang, cleanup foto lama/thumbnails, izin pemilik/petugas/sekolah lain/tamu, consent, tipe/dimensi, nama duplikat, password/email, signature/token expiry/replay/throttle, ekspor sendiri, tombstone anonim, audit, dan mode baca verifikator.

## Checklist per langkah
| Langkah | Kontras/fokus/state | Privasi/otorisasi | Hasil |
|---|---|---|---|
| 1 Analisis | Wireframe tertulis; screenshot referensi tidak terlampir | Tidak mengimpor elemen billing/WhatsApp | Selesai dengan keterbatasan referensi |
| 2 Bottom nav | Opak, SVG konsisten, pil+underline, safe-area, area main terpisah | Tidak memuat profil pengguna lain | Selesai; keyboard fisik belum diuji |
| 3 Menu akun | Dialog, trap, Escape, focus return, backdrop, swipe | Tamu/siswa/verifikator/admin; backend policy | Selesai; toggle gelap ditunda sesuai syarat |
| 4 Profil | Kartu, field errors, toast/pending, AJAX sync | Nama samaran; email verifikasi | Selesai |
| 5 Foto | Cropper pointer/keyboard, zoom, cancel/error | Privat, consent, re-encode/metadata, cleanup | Selesai |
| 6 Pengaturan/notifikasi | Tab keyboard, empty/populated/seen/badge | Hanya event setelah laporan sendiri; tanpa push | Selesai; tema gelap tidak tersedia |
| 7 Keamanan/privasi/bantuan | Password toggle, export/download, HAPUS confirmation, FAQ | Audit tanpa nilai sensitif; anonimisasi | Selesai; daftar sesi bersyarat |
| 8 Manajemen pengguna | Pencarian, peran/status, konfirmasi/update AJAX | Hanya admin/sekolah sendiri; tidak bisa mengubah admin lain/diri sendiri | Selesai |
| 9 QA | Suite backend/browser, axe, Lighthouse, visual | Foto tak ditambahkan ke public hazard/feed/eskalasi | Selesai dalam lingkungan lokal |

Tidak ada label status “aman/safe” atau ikon centang hijau sebagai jaminan kondisi jalan. Kata tersebut hanya muncul dalam nama aplikasi/FAQ/disclaimer yang menjelaskan keterbatasan.

## Batas pengujian
- Bukan pengujian langsung pada HP Android/iOS, keyboard native, pembaca layar fisik, atau jaringan/deployment pengguna. Safe area menggunakan CSS; keyboard menggunakan VisualViewport, bukan klaim uji hardware.
- Email diuji dengan mail fake dan validasi signed-link. SMTP/kotak masuk eksternal belum terhubung; MAIL_MAILER=log tidak mengirim email.
- Daftar sesi database bersyarat diimplementasikan; uji lintas browser nyata memakai driver file bawaan, bukan tabel sesi baru.
- Mode gelap tidak dibuat/disertakan; UI terang yang diperiksa. Kebijakan privasi/syarat/kontak/daftar kata terlarang membutuhkan tinjauan sekolah.
- Database QA, akun fixture, cookie browser, email/token/log, vendor, dan node_modules **tidak dibundel** dalam ZIP source.

## Pembaruan visual v5.1 — sesuai foto pertama pengguna
Dock mengambang menggantikan bar yang menempel ke tepi bawah. Diuji dengan fixture komponen memakai CSS production terbaru dan kode menu Akun yang sama: lebar 360/390/640 px, gap minimum12px, area main berakhir12px di atas dock, item aktif teal dengan teks putih, target sentuh>=44px, menu buka/Escape/focus return, axe nav0pelanggaran. Pada1280px bottom nav tetap tersembunyi. Render HP/desktop diperiksa secara visual. Build Vite berhasil. Backend tidak diubah/diuji ulang pada pembaruan visual ini. Ini **bukan patch kompatibilitas MariaDB10.11 untuk hosting**; migrasi deployment masih perlu penyesuaian terpisah.

## QA navigasi v5.2
Build Vite berhasil. Lima tombol diuji pada fixture komponen dengan CSS production di320/360/390/640px: label tetap di dalam tombol, target sentuh>=44px, gap dock12px, ruang konten12px di atas dock, dan axe nav0pelanggaran. Pada1280px navmobile tetap tersembunyi. Tautan Dashboard diuji melalui navigasi fixture dan route home/tautan guest/login diverifikasi di source. Menu Akun/Escape/focus return tetap berfungsi. Render390px dan320px ditinjau visual. Backend tidak diubah dan suite backend tidak dijalankan ulang pada update ini.
