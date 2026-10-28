# RuteAman — project memory

Dokumen ini adalah konteks tetap untuk pengembangan berikutnya.

## Status terbaru — v7.1 Map Markers

- Leaflet map lama diintegrasikan, bukan rewrite. Catalog dan manager di resources/js/features/map; panduan aktif docs/MAP_MARKERS.md.
- Umur visual selalu first_reported_at; satu tick, polling berpaginasi khusus /api/map/conditions. Jangan menggunakan active-only feed untuk histori marker.
- published_at menyimpan bukti pernah publik; old/resolved/expired sah tetap terlihat, rejected/private tetap tertutup. Umur tidak menghapus marker. Route scoring tetap active-only.
- CSS frost fallback pada live map menggantikan snapshot WebGL mahal tanpa mengubah layout. Jangan capture ribuan marker ke renderer glass setiap perubahan.
- QA versi ini: 82 tes PHP/772 assertion, 9 tes Node, browser desktop/mobile dan stress 1.000 marker. Tidak ada dummy marker dalam source produksi.

## Basis v7.0 Admin Berjenjang

- Melanjutkan v6.0, bukan rollback kontrak publikasi kondisi. Panduan aktif: `docs/ADMIN_SETUP.md`, `docs/ADMIN_CHANGES.md`, `docs/ADMIN_QA.md`.
- Role admin: owner (1), platform_admin (2), school_admin (3), school_staff (4). Siswa/verifikator bukan tingkat admin. PermissionService adalah sumber keputusan izin; UI bukan pengaman otorisasi.
- Pengaman: grant ceiling, dependency, fresh scope/feature, re-auth, status, session_version/idle, cap dan last Owner melalui advisory transaction/row locks PostgreSQL.
- Alias login terpisah dari email kontak. Undangan memakai password broker/token table yang sudah ada; SMTP setelah commit sinkron dan tidak menggunakan persisted queue token.
- Tidak menambah tabel. Baseline 11 tabel domain; batas total ≤10 tidak dipenuhi. 2FA/TOTP belum tersedia. PostgreSQL 16/PostGIS + PHP 8.4 adalah target yang diuji, bukan jaminan Laragon/MySQL/MariaDB.
- School archive menyembunyikan pilihan sekolah lokal/nasional dan menolak pendaftaran baru; pause mempertahankan core map baca-saja. Jangan menghapus data lama hanya karena fitur nonaktif.
- QA final: 78 tes/718 assertion, dua skenario balapan Owner, 35 pemeriksaan browser, 16 scan axe, Lighthouse accessibility 100 untuk ringkasan admin. Tidak mengklaim semua halaman WCAG sempurna, SMTP nyata atau Android fisik teruji.
- Catatan source-of-truth/konvensi di bawah merupakan riwayat; gunakan amendment v7 jika bertentangan.

## Source of truth

- Basis Laravel: `MIE-Auth-Fixed.zip`, dibersihkan dan dialihkan sepenuhnya menjadi RuteAman.
- Konsep: `RuteAman_Competition_Concept_v2.md`.
- Backend: Laravel 13, Eloquent, database queue. Target lokal pengguna adalah Laragon MySQL Spatial; PostgreSQL/PostGIS tetap didukung.
- Frontend: Blade, Tailwind CSS/Vite, Alpine.js.
- Referensi visual utama: layout properti dengan navbar membulat di atas hero, pencarian mengambang, section dua kolom, dan grid kartu.

## Aturan yang tidak boleh dilanggar

- RuteAman bukan aplikasi navigasi dan tidak menjamin kondisi jalan.
- Jangan memakai “Aman” atau “Safe” sebagai label status.
- Tidak ada laporan berarti `no_report`, bukan kondisi terjamin.
- Tidak menyimpan riwayat titik asal atau melacak perjalanan pengguna.
- Lokasi perangkat hanya diminta sekali untuk mengisi formulir rute. Routing engine menggunakan graph jalan untuk menghasilkan alternatif, tetapi hasil tidak boleh diberi label “paling aman”.
- Bandingkan alternatif dengan dua dimensi netral: durasi/jarak dan Beban Hazard. Rute tidak diurutkan ulang berdasarkan klaim keamanan.
- Titik awal dapat berasal dari GPS sekali pakai atau klik peta. Tujuan dipilih dari sekolah di database atau langsung melalui penanda sekolah. Moda tersedia: jalan kaki, sepeda, motor (mengikuti profil jalur kendaraan), dan mobil.
- Overlay utama menggunakan `@ybouane/liquidglass`. Setiap elemen `.liquid-glass` didaftarkan ke root `[data-liquid-root]` terdekat agar kartu bertingkat tidak terdaftar dua kali. Varian konfigurasi: nav, search, panel, status, notice, legend, action, card, media, content, control, dan row. CSS `backdrop-filter` dengan highlight tepi, inset light, serta bayangan lembut menjadi fallback ketika WebGL gagal atau reduced-motion aktif.
- Bahasa visual Liquid Glass diterapkan konsisten pada navbar, pencarian hero, CTA, welcome panel, kartu hazard, showcase fitur, langkah cara kerja, FAQ, kontrol formulir, kartu rute, status, legenda peta, FAB, autentikasi, daftar, dan dashboard. Pertahankan kontras teks dan jangan membuat semua permukaan sama kuat; gunakan glass paling transparan untuk panel besar dan glass lebih pekat untuk tombol/aksi.
- Pola yang diadaptasi dari FreeFrontend: bottom tab mobile dengan safe-area, skeleton route compositor-friendly, card lift untuk hover/focus, dan FAQ native `<details>`. Sumber serta keputusan implementasi dicatat di `docs/FRONTEND_REFERENCES.md`.
- Landing hero menggunakan ukuran penuh viewport (`100svh`, lebar penuh), tanpa gutter hijau, tanpa batas `max-width`, dan tanpa radius pada tepi layar. Navbar serta pencarian tetap mengambang di dalam hero.
- Layout landing yang sudah dirapikan memakai navbar grid tiga kolom agar tombol kanan tidak terpotong, hero copy dengan lebar/ukuran seimbang, panel pencarian desktop yang overlap 55px ke section berikutnya, serta panel pencarian mobile satu kolom dengan tinggi otomatis. Jangan memberi `position: relative` generik pada `.liquid-glass` karena akan merusak elemen glass yang memang `absolute`/`fixed` seperti navbar, search, legenda, dan FAB.
- CTA utama landing memakai komponen Blade reusable `resources/views/components/liquid-cta.blade.php`, dengan efek liquid berlapis, SVG panah, fokus keyboard, ukuran berbasis `clamp()`, dan fallback reduced-motion.
- Landing memiliki feature showcase stacked-card di `resources/views/partials/feature-showcase.blade.php`: satu kartu lebar dan dua kartu responsif, gambar audit/demo nyata, tag fitur, kontrol tema Sistem/Terang/Gelap, efek Holo opsional, status tombol `aria-pressed`, serta preferensi tersimpan di localStorage.
- Pendaftaran sekolah memakai direktori nasional dengan alur provinsi → kabupaten/kota → kecamatan → pencarian nama/NPSN. Wilayah diambil online dari `wilayah.id` memakai kode BPS (38 provinsi, 514 kabupaten/kota, 7.285 kecamatan), dengan snapshot lengkap `database/data/indonesia-school-regions.json` sebagai fallback offline. Pencarian sekolah memakai Dapodik sebagai sumber utama dan `sekolah.devapi.id` sebagai fallback. Request diproxy oleh `DapodikSchoolDirectory`, token publik Dapodik ditemukan server-side dari `env.js` bila tidak dikonfigurasi, hasil di-cache, dan NPSN diverifikasi ulang saat submit. Sekolah terpilih diimpor ke tabel lokal bersama wilayah dan koordinat agar dapat dipakai pada peta. Laragon lokal memakai default SSL verify nonaktif melalui konfigurasi, tetapi produksi wajib mengaktifkannya.
- Umur data wajib terlihat.
- Laporan maksimal empat langkah; konfirmasi satu ketuk.
- Jangan menambah gamification, membership, AI tutor, voice tutor, 3D lab, atau pomodoro.
- Data pelapor tidak boleh muncul di feed publik atau paket eskalasi.

## Palet

- Mint `#8AD6D1`
- Teal `#359FA0`
- Cream `#FFF0C5`
- Orange `#FF8C52`
- Ink `#12383A`
- Teal dark `#1F6E70`
- Orange dark `#B8480F`

## Akun demo

- `siswa@ruteaman.test`
- `verifikator@ruteaman.test`
- `admin@ruteaman.test`
- Kata sandi: `RuteAman123!`

Perbarui dokumen ini ketika keputusan arsitektur atau aturan produk berubah.

## v4 — skrip demo terbaru

- Permintaan pengguna terbaru menggantikan larangan lama rute tersimpan: tabel `saved_routes` sekarang dipakai untuk papan kondisi rute audit sekolah. Graph routing tetap opsional; rute tersimpan bisa dibaca tanpa GPS/API routing.
- Dua role aktif: `student`, `school_admin`. Migrasi mengubah role `verifier` lama menjadi Admin Sekolah. Admin mengelola antrean, verifikasi, dan eskalasi pada catchment-nya.
- `HazardLookupService`: duplikat jenis yang sama, radius konfigurasi, hazard aktif; lintas sekolah. UI menawarkan konfirmasi, backend tidak menambah laporan pada duplikat.
- `HazardEvidenceService`: reporter berbeda dari confirmer; 3 pengguna independen (1 pelapor + 2 konfirmasi) -> Dikonfirmasi Komunitas. Retry konfirmasi tidak memperbarui umur data.
- Skor aktual setelah tindakan: c(1,2,foto,tanpa verifikasi)=0.50; c setelah verifikasi=0.90. Demo lubang + genangan: 22 → 47 → 67. Foto diperlukan untuk angka skenario ini.
- Freshness/TTL difilter saat baca sekalipun scheduler belum berjalan. Cron menjalankan `ruteaman:expire`. Hujan berasal dari cache/manual; belum ada BMKG otomatis.
- PhotoSanitizer gagal tertutup: foto ditolak jika GD/WebP gagal, bukan disimpan mentah dengan GPS.
- MySQL WKT menggunakan `axis-order=long-lat`, ekstraksi memakai ST_Longitude/ST_Latitude. Relasi polygon-point memakai ST_Intersects; ST_Distance untuk point-line, bukan ST_Distance_Sphere yang tidak mendukung line. Target MySQL 8.0.24+ / 8.4; PostgreSQL PostGIS tetap didukung.
- DemoSeeder dan `ruteaman:demo-reset` hanya local/testing; data simulasi diberi label, bukan hasil audit/pilot.
- Akun khusus: siswa_1@ruteaman.test, siswa_2@ruteaman.test, siswa_3@ruteaman.test, admin_a@ruteaman.test. Password tetap RuteAman123!.
- Koordinat genangan demo: -6.1768, 106.8250; sekolah A/B catchment tumpang tindih. Rute A/B jarak skenario 1800/2100 m; bukan pengukuran audit nyata.

- QA v4: PHP 8.4 dipasang untuk pengujian; migrasi dan seed sukses pada PostgreSQL 16/PostGIS. Unit + integration alur 22→47→67, scope catchment, privasi, dan sanitizer lulus. UI desktop/mobile dicek dengan Playwright; tidak ada error JS atau horizontal overflow halaman/form. MySQL Spatial belum diuji langsung di sandbox.
- Bug Leaflet lama diperbaiki: selector `.map-art svg` menjadi `.map-art > svg` supaya SVG overlay Leaflet tidak dipaksa berdimensi parent nol. Marker laporan memakai asset Vite eksplisit.


## v4.1 — perbaikan direktori madrasah / NPSN

- Kasus nyata: NPSN 20363192 (MAN 2 REMBANG, Lasem, Kab. Rembang, Jawa Tengah). API Dapodik search/detail mengembalikan data=[] dengan HTTP 200; sekolah.devapi.id punya data lengkap.
- Pencarian sekarang memakai fallback ketika Dapodik kosong ATAU seluruh hasil tersaring oleh wilayah, bukan hanya ketika request error. Impor/pendaftaran juga memakai fallback ketika detail Dapodik kosong.
- NPSN dan kecocokan wilayah tetap diverifikasi server-side; tidak bypass wilayah atau menambah sekolah fiktif manual untuk memperbaiki masalah.
- Cache search/detail memakai namespace v2 untuk menghindari hasil kosong cache lama; cache search mencakup kecamatan dan tidak menyimpan hasil kosong.
- UI menghapus NPSN tersembunyi saat query berubah dan mengabaikan respons pencarian yang sudah kedaluwarsa.
- Verifikasi live lewat service: pencarian 20363192 dan MAN 2 REMBANG keduanya mengembalikan sekolah yang benar. Full suite v4.1: 14 test / 96 assertion lulus di PostGIS; Vite build dan syntax check lulus.


## v4.2 — limiter registrasi terisolasi

- Root cause 429: middleware numeric throttle Laravel memakai signature domain+IP/user tanpa nama route; semua limiter tanpa prefix berbagi counter. Request direktori sekolah dapat menghabiskan budget POST /register (6/menit).
- Semua limiter sekarang memakai prefix v2 yang berbeda per fungsi. Direktori tetap berbagi satu budget sendiri (60/menit), bukan budget login/daftar. Batas spam tidak dimatikan dan tidak dinaikkan.
- Auth POST HTML yang terbatasi diarahkan kembali ke form dengan pesan waktu tunggu dan input non-password; kata sandi tidak disimpan di flash session. JSON tetap HTTP 429 + Retry-After. GET form tidak ditrottle.
- Form login/register mencegah submit ganda selama request berlangsung; pageshow mengaktifkan tombol kembali saat kembali dari browser history.

- QA v4.2: 20 test / 238 assertion lulus pada PostGIS, termasuk pembuatan akun MAN 2 Rembang setelah >6 request direktori. Budget login/daftar/direktori terisolasi, HTML redirect menyimpan input non-password, JSON tetap 429, proteksi daftar 6 POST/menit tetap aktif.
- HTTP lokal dengan cookie jar nyata: POST ke-1..6 menampilkan validasi, POST ke-7 mengembalikan form dengan pesan “Batas percobaan daftar tercapai”; bukan halaman putih.
- Vite build, PHP/JS lint, dan kompilasi Blade lulus.


## v4.3 — panel landing tertutup

- Root cause: `.reference-hero` memakai `isolation:isolate` dengan z-index auto; `.welcome-block` positioned sibling yang dirender setelahnya dapat menutup bagian form yang menonjol 55px di luar hero. Menaikkan z-index form saja tidak menembus stacking context parent.
- Perbaikan scoped: `.landing-wrap > .reference-hero { z-index:2 }`, welcome sibling `z-index:1`. Hero tetap fullscreen dan desktop search tetap overlap; mobile tetap satu kolom di dalam hero.
- Canvas glass form diberi z-index 0/opacity .32; label/input/tombol langsung di form diberi position relative/z-index 1 supaya tidak ditimpa canvas. Tint form diperjelas untuk keterbacaan. Jangan memakai posisi relatif generik pada floating glass.
- QA: kondisi sebelum perbaikan berhasil direproduksi. Screenshot sesudah fix desktop 1702px, mobile 390px, dan reduced-motion sudah diperiksa. Playwright 16/16 ukuran/mode (1702,1366,1024,901,900,768,390,320; normal/reduced) lolos: kontrol terlihat, bisa diklik, dan tidak ada horizontal page overflow.
- Vite build dan JS syntax check berhasil. Patch ini hanya CSS/asset frontend; tidak ada migrasi atau perubahan logika registrasi/direktori/rute.


## v4.4 — input tujuan sekolah landing

- Nama bawaan SMAN 8 Nusantara dan readonly dihapus dari input `to`. Kolom awal kosong, bisa diketik, dan memiliki datalist nama/NPSN sekolah yang sudah tersedia di database lokal (termasuk hasil impor pendaftaran).
- Map membaca query `to`, mencocokkan nama (case-insensitive/whitespace-normalized) atau NPSN, dan memakai sekolah tersebut sebagai tujuan. Nama tidak ditemukan / ambigu tidak diam-diam dialihkan ke sekolah demo; tampilkan pesan pada landing dan minta pilih nama/NPSN.
- Dataset nasional tidak diunduh ke landing: sekolah yang belum ada di DB harus diimpor melalui pendaftaran yang sudah ada.
- QA: 25 test / 256 assertion lulus pada PostGIS; nama/NPSN menuju sekolah benar, input kosong/editable, unknown/ambiguity ditolak. Browser desktop/mobile berhasil mengetik MAN 2 REMBANG ke kolom tersebut. Screenshot desktop/mobile sudah diperiksa; build, syntax check, dan Blade compilation lulus.


## v4.5 — pilihan NPSN hilang saat submit register

- Laporan pengguna: sudah memilih sekolah, tetapi POST register tetap mengatakan school_npsn required. Titik rawan di kode lama: reset input pencarian dan loader wilayah dapat mengosongkan hidden field; pilihan hanya dipegang di field tersebut. Tidak menganggap pengguna belum memilih.
- Frontend sekarang punya state `chosen` berisi NPSN/nama dan kode wilayah, membatalkan pencarian/debounce saat pilihan dikonfirmasi, serta menolak respons async wilayah/pencarian yang kedaluwarsa.
- Query dipasang readonly hanya setelah memilih sekolah; gunakan Ganti untuk mencari ulang. Event input terlambat/autofill tidak menghapus pilihan yang dikonfirmasi. Landing input tujuan tetap editable (v4.4 tidak dibatalkan).
- Submit capture merehidrasi hidden NPSN dan backup `selected_school_npsn` di luar fieldset, memblokir submit ketika pilihan belum siap, dan memastikan generic auth button-lock mengabaikan event yang dicegah.
- Backend memulihkan NPSN kosong dari backup atau query NPSN tepat 8 karakter, tetapi import() tetap memverifikasi NPSN dan wilayah pada direktori. NPSN primer nonkosong tidak ditimpa backup berbeda. Pesan required sekolah sekarang Bahasa Indonesia.
- Restore setelah error validasi tidak lagi menghapus pilihan saat loader wilayah berjalan.
- QA v4.5: 31 test / 276 assertion lulus pada PostGIS. Lima browser case lulus (desktop/mobile pilihan baru/restore, event input terlambat, hidden fields dibersihkan sebelum submit lalu pulih, missing-selection guard tidak mengunci tombol). Payload POST diperiksa membawa NPSN dan kecamatan yang benar. Screenshot pilihan tersimpan desktop/mobile diperiksa; build/lint/Blade compilation berhasil.

## v4.6 — GPS Android notice
- Added shared location-help partial to map and hazard report, with Android/Chrome instructions and HTTP LAN/HTTPS warning.
- Both location buttons use resources/js/location.js: one-shot request after click, retry reset, distinct denied/unavailable/timeout messages and low-accuracy feedback. No permission request on load, no watchPosition.
- Validation: Vite production build passed; 32 browser fixture cases passed (2 scopes × 2 widths × 8 states). Mobile and desktop guide/error screenshots inspected. These are component fixtures, not live Laravel routes; current computer has no PHP binary, so Laravel integration tests were not rerun. Actual Android device/GPS not tested.

## v5.0 — Account menu/profile
Implemented request Prompt_Menu_Akun_Profil_RuteAman.md on v4.6 base. Added user columns only (no new tables), Account/SchoolUser controllers, mutation Form Requests, AccountPolicy, Account/Avatar/Notification services, resource and verification mail. Private avatars on dedicated serve=false disk; WebP 512/96/256 with metadata discarded. Pending email changes require password, signed one-time expiry verification. Name/profile/avatar AJAX sync; anonymous deletion preserves unique contributor IDs and evidence. Restored verifier for new assignments; existing school_admin roles unchanged. Mobile opaque nav with reserved main scroll area; native modal sheet/popover, focus trap/return, swipe, guest/per-role menus. Light-only theme intentionally; no false dark switch. Consent applies to all uploads because age is not stored. Optional session listing only when database table exists; file-session cross-browser revocation tested. Baseline has 11 domain tables, not claimed <=10. Added docs ACCOUNT_SETUP/QA. QA: PostGIS suite46tests410assertions, browser360/768/1280, axe account pages/menu no violations, Lighthouse profile Accessibility100. Real Android hardware/SMTP not tested. Never bundle .env/storage/session files, QA DB/cookies/logs, vendor/node_modules.

## v5.1 — Floating bottom navigation
User requested the floating rounded dock from their first reference, not the full-width edge-attached bar. Restored 12px side/bottom gaps, 22px rounded dock, subtle shadow, and a full teal active-item pill with white icon/label. Kept an opaque surface and 88px + safe-area reserved clearance so the dock does not overlap forms. Account sheet/popover, role handling, and backend behavior unchanged. This is a visual update, not the pending MariaDB hosting compatibility patch.

## v5.2 — Dashboard / beranda
Added a public Dashboard shortcut to route home as the first floating-nav item: Dashboard · Peta · Lapor · Laporanku · Akun. Also added Dashboard to signed-in account menus and the guest sheet. Dashboard Sekolah remains separately named and protected for staff. Updated five-column dock spacing/compact labels while retaining floating shape, active teal pill, touch targets, and content clearance. Hosting MariaDB compatibility remains pending; no backend/database changes in this visual update.
