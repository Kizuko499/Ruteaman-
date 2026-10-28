> Deployment v7.2: gunakan `MARIADB_CPANEL_SETUP.md` dan `MARIADB_QA.md` untuk MariaDB. Instruksi fitur akun lama tetap berlaku bila tidak bertentangan dengan konfigurasi admin v7.2.

# RuteAman v5.0 — Menu Akun dan Profil

## Memperbarui proyek lama
1. Cadangkan database dan folder `storage` terlebih dahulu.
2. Salin source terbaru. Pertahankan `.env`, database, foto laporan lama, dan `storage` milik instalasimu. Jangan memakai `migrate:fresh` untuk pembaruan.
3. Jalankan dari folder proyek:

```bash
composer install
npm install
npm run build
php artisan migrate
php artisan optimize:clear
php artisan serve --host=0.0.0.0 --port=8000
```

ZIP sudah menyertakan aset build. `vendor` dan `node_modules` tidak disertakan. Foto profil membutuhkan ekstensi PHP GD dengan dukungan WebP; bila tidak tersedia, unggah gagal dengan pesan ramah dan avatar bawaan tetap tersedia. Pastikan proses PHP bisa menulis `storage/app/private/avatars`. Jangan membuat symlink publik ke folder privat ini.

## Konfigurasi yang perlu ditinjau sekolah
- `AVATAR_UPLOAD_REQUIRES_CONSENT=true`: bawaan konservatif. Karena umur tidak dikumpulkan, syarat persetujuan berlaku untuk seluruh pengunggah, bukan mengasumsikan pengguna sudah dewasa. Avatar inisial/bawaan selalu tersedia.
- `ACCOUNT_BLOCKED_NAMES`: contoh blokir dasar (kata kasar dan nama yang bisa dipakai untuk penyamaran). Sekolah perlu meninjau daftar. Filter ini bukan moderasi lengkap.
- `SCHOOL_HELP_CONTACT`: kontak umum opsional, bukan kontak sekolah yang ditebak.
- `SCHOOL_HELP_CONTACTS='{"20363192":"https://kanal-resmi-sekolah.example"}'`: contoh format pemetaan NPSN atau ID sekolah ke kontak. Ganti alamat contoh dengan kanal resmi sekolah. HTTPS/mailto dirender sebagai tautan biasa; teks kontak lain sebagai teks.
- Atur mail sesuai penyedia SMTP dengan `MAIL_MAILER=smtp`, host, port, username, password, alamat pengirim, dan konfigurasi TLS yang sesuai. `MAIL_MAILER=log` hanya menulis email ke log; **tidak mengirim ke kotak masuk**. Jangan menaruh kredensial mail dalam source/ZIP.
- `APP_URL` harus sesuai alamat yang dipakai pengguna. Tautan verifikasi email bertanda tangan berlaku 60 menit dan hanya dapat digunakan oleh akun yang meminta. Gunakan HTTPS di produksi.
- Teks Kebijakan Privasi dan Syarat adalah draf teknis sesuai perilaku aplikasi. Sekolah harus meninjaunya sebelum penerapan produksi.

## Perilaku yang diimplementasikan
- Mobile ≤640 px (v5.1): dock mengambang 64 px, jarak sisi/bawah 12 px + safe area, sudut 22 px, bayangan halus, ikon SVG konsisten, serta item aktif berbentuk pil teal penuh. Area konten menyisakan 88 px + safe area. Halaman non-beranda memakai area gulir yang berakhir di atas nav; beranda memiliki ruang bawah dan ukuran hero yang disesuaikan. Nav disembunyikan saat VisualViewport menunjukkan keyboard pada input.
- Akun membuka dialog bottom sheet (<1024 px) atau popover 360 px (≥1024 px). Tutup melalui tombol, Escape, latar, atau geser area pegangan. Fokus dikembalikan; dialog native + trap fokus mencegah akses ke peta di belakang.
- Tamu melihat Masuk/Daftar. Menu petugas tidak dirender untuk siswa. Verifikator kembali tersedia untuk penugasan baru; admin lama tidak otomatis diturunkan.
- Nama panggilan 2–30 karakter, sanitasi tag/control character, unik per sekolah tanpa membedakan kapital. Pemeriksaan duplikat diulang dalam transaksi dengan kunci sekolah.
- Avatar inisial memakai warna palet deterministik; 8 avatar SVG tersedia. Cropper mendukung drag/pointer, zoom, dan panah keyboard; output unggah maksimal 1024 px. Input/output dibatasi 2 MB.
- Server memvalidasi konten/MIME/dimensi, mendecode ulang tanpa metadata ke WebP 512 px serta thumbnail 96/256 px, memakai ULID acak dan disk privat. Pemilik serta verifikator/admin aktif di sekolah sama dapat membaca lewat route terotorisasi. Tidak ada foto/nama pengguna lain pada hazard, GeoJSON, atau paket eskalasi.
- Nama dan avatar tersinkron ke header/nav setelah simpan tanpa reload. Error di bawah field, status pending, toast, dan tombol retry/reset tersedia.
- Email lama tetap digunakan hingga email baru diverifikasi. Password wajib untuk perubahan email. Token hanya disimpan sebagai hash; tautan bertanda tangan, sekali pakai, dengan expiry dan throttle. Pengiriman gagal tidak mengganti email aktif.
- Pengaturan menyediakan Bahasa Indonesia dan tema Terang/Ikuti perangkat. **Tema Gelap dan toggle gelap sengaja tidak ditampilkan**: layar lama belum memiliki seluruh token gelap yang teruji. Preferensi `system` saat ini menghasilkan tema terang, dengan penjelasan di UI. Tidak ada pilihan bahasa/tema palsu.
- Notifikasi berasal dari audit hazard pada laporan milik sendiri, hanya kejadian setelah laporan dibuat. Ada pagination, alasan penolakan, badge, empty state, dan tandai semua dibaca. Tidak ada push atau tabel notifikasi baru.
- Password baru minimal 10 karakter, campuran huruf, angka, simbol; perangkat lain diminta masuk kembali melalui autentikasi sesi. Daftar sesi dan tombol keluar sesi lain hanya ditampilkan jika driver database dan tabel sesi sudah ada. Driver bawaan proyek adalah file.
- Ekspor JSON hanya profil, laporan, dan konfirmasi sendiri; tanpa password/token/path foto privat atau data pengguna lain.
- Hapus akun perlu password + HAPUS + konfirmasi UI. Dalam transaksi, profil menjadi tombstone anonim yang tidak aktif: nama “Pengguna dihapus”, email acak `@deleted.invalid`, password acak ter-hash, tanpa sekolah/persetujuan/avatar/token lama. Foto privat dihapus. ID anonim tetap unik sehingga FK dan bukti konfirmasi independen tidak berubah. Laporan/konfirmasi tetap tersedia tanpa identitas akun.
- Manajemen pengguna hanya admin sekolah dan hanya sekolah sendiri. Peran siswa/verifikator dan status dapat diubah dengan konfirmasi. Admin lain dan akun sendiri tidak dapat diubah dari menu ini. Tindakan email/password/peran/status/hapus/ekspor memiliki audit tanpa nilai sensitif.

## Keputusan kompatibilitas
- Tidak ada tabel baru. Proyek sebelumnya sudah memiliki **11 tabel domain** termasuk `saved_routes` (di luar tabel infrastruktur Laravel). Target maksimal 10 tabel bukan kondisi baseline yang terpenuhi; tabel lama tidak dihapus untuk memaksakannya.
- Kolom email lama NOT NULL, sehingga tombstone menggunakan email acak non-deliverable alih-alih NULL. Tidak ada akses login kembali.
- Tambahan kolom pending email/token hash/expiry ada pada users; tidak perlu tabel baru untuk verifikasi email.
- Route gambar terotorisasi digunakan, bukan signed URL gambar, sesuai pilihan dalam prompt. Cache foto `private, no-store`.
- Screenshot referensi tidak disertakan dalam lampiran. UI mengikuti wireframe/pola tertulis dan palet RuteAman yang sudah ada.

## Pengujian
Lihat `ACCOUNT_QA.md`. Untuk suite spasial, gunakan database PostGIS **khusus uji** yang sudah dimigrasikan, bukan database produksi:

```bash
RUTEAMAN_SPATIAL_TESTS=1 DB_CONNECTION=pgsql DB_DATABASE=nama_db_uji DB_HOST=127.0.0.1 DB_PORT=5432 DB_USERNAME=user_uji DB_PASSWORD=password_uji php artisan test
```

Di Windows, set environment variables dengan sintaks PowerShell/CMD yang sesuai sebelum `php artisan test`. Pengujian fitur spasial sengaja dilewati pada mode SQLite karena schema geometri tidak kompatibel.

## v5.2 — Tombol Dashboard
Bar mengambang kini berisi Dashboard · Peta · Lapor · Laporanku · Akun. Dashboard menggunakan route home (halaman utama `/`) dan bisa diakses tanpa login. Tautan yang sama juga tersedia pada menu Akun pengguna maupun tamu. Dashboard Sekolah tetap terpisah dan hanya tersedia sesuai izin petugas. Bagian ini berasal dari versi akun v5.x; paket v7.2 kini menyertakan patch MariaDB hosting, lihat `MARIADB_CPANEL_SETUP.md`.
