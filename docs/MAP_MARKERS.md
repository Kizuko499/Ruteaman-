# RuteAman v7.1 — Condition / Incident Map Markers

## Integrasi, bukan rewrite

Map tetap Leaflet 1.9 dengan basemap OpenStreetMap, posisi sidebar/nav, rute, sekolah, tombol analisis dan pelaporan yang ada. Marker lingkaran hazard generik diganti icon SVG khusus. Legenda rute/sekolah tetap ada; daftar icon kondisi ditambahkan dalam disclosure yang tertutup secara default.

Perubahan UI terbatas yang diperlukan: zoom control desktop berada di bawah nav fixed agar dapat diklik; daftar legend mobile dapat digeser; legend sementara tidak tampil ketika popup terbuka agar tidak menutupi popup, lalu kembali saat ditutup. Tidak mengubah posisi geografis marker atau melakukan jitter/spiderfy. Popup menyediakan pemilihan kondisi berdekatan dengan pagination 20 item untuk lokasi bertumpuk.

Efek glass pada halaman map memakai fallback CSS frost/backdrop yang sudah tersedia, bukan WebGL snapshot seluruh subtree Leaflet. Uji 1.000 marker menemukan snapshot lama memblokir event loop; fallback menghilangkan hambatan itu sambil mempertahankan struktur dan palet UI. Halaman lain tidak diubah.

## Modul frontend

`resources/js/features/map/`:

- `condition-types.js`: catalog icon/warna/label dan alias kode Laravel lama. Setiap jenis visual berbeda; status resolved menggunakan icon check “Tertangani”. Ini bukan jaminan kondisi jalan.
- `marker-state.js`: fungsi murni `parseReportTime`, `calculateMarkerAge`, `calculateMarkerIntensity`, adapter Laravel/GeoJSON, escaping dan validasi URL.
- `condition-marker.js`: satu instance Leaflet yang stabil per ID, popup, tooltip, dan `updateMarkerVisual`. Timer tidak dibuat di sini.
- `condition-marker-manager.js`: sync ID-based, snapshot API berpaginasi, satu centralized tick, refresh, scope sekolah, zoom, overlap, offline/visibility/lifecycle cleanup.
- `condition-legend.js`: legenda dari catalog yang sama, tidak menyalin konfigurasi per marker.
- `resources/css/condition-markers.css`: gaya additive, responsif dan reduced motion.

`app.js` hanya menghubungkan manager ke map yang telah ada. Rute tersimpan/analisis graph dan layer sekolah/catchment tetap memakai kode lama.

## Umur dan visual

Umur dihitung dari `first_reported_at` yang dipetakan menjadi `created_at`, **bukan waktu halaman dibuka** dan bukan last_confirmed_at. Konfirmasi baru tidak membuat laporan lama terlihat baru. ISO timestamp wajib mempunyai zona waktu (`Z` atau offset). Timestamp null/invalid/tanpa zona dianggap tidak diketahui, tampil subtle tanpa pulse; timestamp masa depan di-clamp ke umur nol. Refresh tidak mereset umur.

| Umur (menit) | Intensitas warna | Brightness | Halo | Pulse |
| --- | --- | --- | --- | --- |
| 0 | 1.00 | 1.00 | 1.00 | Halus |
| 5 | 0.94 | 0.98 | 0.75 | Berkurang |
| 15 | 0.86 | 0.96 | 0.45 | Mati |
| 30 | 0.76 | 0.94 | 0.25 | Mati |
| 60 | 0.65 | 0.92 | 0.08 | Mati |
| 180 dan seterusnya | 0.48 | 0.88 | 0.00 | Mati |

Antar anchor memakai smoothstep; CSS transisi 1.2 detik. Pulse halo 2.2 detik ease-in-out, tidak menggeser/memperbesar posisi icon. Brightness sengaja tidak turun ke 60% agar icon tetap terbaca. Intensitas diterapkan pada permukaan warna dan halo; glyph beralih secara smooth dari putih di permukaan solid ke warna kategori pada backing putih yang makin subtle. Jadi **glyph tidak ikut dibuat hampir transparan**. Identitas warna tidak berganti karena umur. Reduced-motion menonaktifkan pulse.

Satu timer 1 detik memperbarui marker yang berada di viewport/open popup; off-screen marker tetap ada dan diperbarui saat map bergerak. DOM style hanya ditulis jika nilai terkuantisasi berubah. Maksimal 40 pulse; pulse/halo ekstra dimatikan pada viewport padat >250 marker. Icon 24/30/34 px sesuai zoom, hit target 44 px. Marker lama statis, tidak berkedip.

## Backend dan histori permanen

Endpoint khusus `GET /api/map/conditions?school=ID&limit=500&cursor=...` disediakan oleh `MapConditionController` / `MapConditionService`. Keyset cursor terenkripsi, terikat sekolah dan ceiling ID snapshot; tidak ada cutoff umur. Refresh menggabungkan **seluruh halaman** sebelum menerapkan snapshot. Default polling 30 detik; interval bertambah untuk snapshot sangat besar. API gagal atau pagination terputus mempertahankan snapshot sebelumnya; bukan mengosongkan map.

Endpoint lama `/api/feed/hazards.geojson` tetap aktif-only untuk kompatibilitas dan tidak dipakai sebagai sumber marker histori. Perhitungan Beban Hazard/routing tetap memakai kondisi aktif, tidak memasukkan catatan 30 hari sebagai hazard aktif.

Migration `2026_10_10_000000_add_map_publication_history.php`:

- Menambah `hazards.published_at`; tidak menambah tabel.
- Mencatat status yang sudah publik dan backfill expired yang punya bukti verifikasi/independensi yang dapat dibuktikan. Riwayat expired tanpa bukti publikasi tidak ditebak sebagai publik.
- Menambah kategori accident, road_closed, traffic, street_light dan warning jika belum ada; tidak menimpa konfigurasi kategori lama.
- Hazard saving hook mencatat publikasi pertama. `published_at` tidak bisa ditulis melalui mass-assignment request.

Kondisi sah yang telah dipublikasikan tetap ada setelah TTL, expiry job, dan setelah resolved window 24 jam. Penolakan terbaru tetap menutup publikasi meskipun published_at pernah terisi. Pending/new privat dan expired yang tidak pernah terbukti publik tidak diekspos. Scope/catchment/shared_layer/sekolah arsip tetap berlaku.

**Tidak ada penghapusan akibat umur.** `dispose()` hanya untuk snapshot otoritatif yang mencabut/menghapus data, perpindahan cakupan sekolah, atau teardown halaman. Bukan dari calculateMarkerAge/intensity. Marker di luar viewport tidak berarti dihapus; koordinatnya tetap sama.

Payload memuat ID publik, kategori, koordinat, timestamp pertama, status, lokasi publik, jumlah laporan/konfirmasi, bobot, flag historis/demo dan detail URL. Tidak memuat identitas pelapor, password, note reviewer, photo path atau description mentah yang bisa memuat identitas siswa. Description map adalah ringkasan status publik yang aman. Adapter siap menerima public description yang telah disetujui backend; jangan meneruskan raw laporan pengguna tanpa pemeriksaan privasi.

Migration pendamping `2026_10_10_010000_add_map_lookup_indexes.php` menambah indeks hazard/ID untuk latest verification dan indeks hazard untuk report counts; indeks GIST lokasi dan unique hazard/user confirmation sudah ada. Tidak ada tabel tambahan.

## Upgrade

Backup DB/files. Pertahankan `.env`; target teruji PHP 8.4 + PostgreSQL 16/PostGIS. Jangan migrate:fresh/seed ke produksi.

```bash
composer install --no-dev --optimize-autoloader
php artisan optimize:clear
php artisan migrate --force
npm ci
npm run build
php artisan optimize
```

ZIP menyertakan asset Vite build. Jika memakai asset itu, build ulang tidak wajib; deploy semua file manifest bersama. Browser melakukan API polling, bukan WebSocket. Riwayat belum dimuat sebelum koneksi pertama tidak dapat muncul saat offline; marker yang telah dimuat tetap ada saat gangguan sementara. Basemap bergantung OpenStreetMap/network dan tidak termasuk tile caching/offline map.

Cara menambah jenis: buat row hazard_types dengan code baru, tambahkan satu entri pada conditionTypes (icon SVG + warna unik + label) dan alias bila dibutuhkan, lalu build. Jangan menambahkan marker satu per satu atau timer baru.

## QA paket

- 82 tes PHP lulus, 772 assertion: regresi v7 + 4 tes map baru.
- 9 tes Node lulus: timezone, timestamp rusak, decay monotonic/continuous, 30 hari, catalog unik, adapter, URL aman, satu timer/cleanup, duplicate, error dan pagination.
- Fresh migration pada database PostgreSQL/PostGIS kosong lulus; Blade compile, lint PHP dan Vite build lulus.
- Chromium 1440×1000 dan 390×900: marker dari DB, legend 12 jenis, fresh/history visual, popup mouse/keyboard, realtime centralized tick dengan jam uji maju 4 jam tanpa menghapus marker, reduced-motion dan offline polling. Tidak ada pageerror.
- Stress browser 1.000 marker, 5 halaman fixture: render sekitar 1.2 detik pada mesin QA; wait 1.1 detik selesai sekitar 1.1 detik, tanpa freeze panjang dan tanpa pageerror. Zoom tidak mengurangi jumlah marker. Angka ini bukan jaminan kecepatan Android fisik atau semua volume data.
- Empat scan axe scoped pada legend/popup desktop/mobile tidak menemukan violation A/AA yang terdeteksi; popup berada di area map, close target 44×44, tidak ada overflow horizontal. Ini bukan sertifikasi WCAG seluruh website.
- Visual diperiksa pada map, legend tertutup/terbuka dan popup desktop/mobile. Perbaikan: legend lebih opaque, popup tidak tertutup legend/nav fixed, close target 44px, lebar popup mobile, zoom tidak tertutup navbar, dan fallback frost untuk kinerja.

Tes browser memakai fixture lokal, bukan data dummy di implementasi produksi. ZIP tidak menyertakan DB QA, akun/cookie/token, screenshot, atau script fixture lokal.

```bash
npm run test:markers
# Ikuti ADMIN_SETUP.md untuk DB PostGIS khusus pengujian:
RUTEAMAN_SPATIAL_TESTS=1 DB_CONNECTION=pgsql DB_DATABASE=ruteaman_admin_tests php artisan test
```
