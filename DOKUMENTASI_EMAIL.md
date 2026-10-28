# Dokumentasi Sistem Email & Notifikasi — RuteAman Sekolah

## Gambaran

Dua jalur pengiriman, sesuai sifat isinya:

1. **Email keamanan akun** (verifikasi email, undangan admin, pemberitahuan akun,
   pemberitahuan owner) — dikirim **sinkron** seperti sebelumnya, karena isinya
   tautan sekali pakai yang tidak boleh tersimpan di payload antrean. Setiap
   pengiriman kini **tercatat** di `email_delivery_logs`.
2. **Email peristiwa laporan** (laporan baru, perubahan status, komentar baru) —
   dikirim lewat **antrean** (`DeliverEventMail`, maks 3 percobaan, jeda 60/300/900
   detik), tercatat, dan dapat dikirim ulang dari dashboard admin.

Semua email tercatat di tabel `email_delivery_logs`: kategori, subjek, status
(queued/sent/failed), jumlah percobaan, waktu, dan pesan kesalahan yang **sudah
dibersihkan** dari tautan, token, dan alamat email. Isi email dan kredensial
tidak pernah disimpan.

## Aturan penerima (least privilege)

Email peristiwa hanya dikirim bila penerima: akun aktif, email terverifikasi,
memiliki cakupan/permission atas laporan terkait, dan preferensi kategorinya
menyala. Kunci dedupe per peristiwa mencegah email ganda. Email keamanan akun
tidak dapat dimatikan dari preferensi.

- Laporan baru → verifikator/admin sekolah dari sekolah laporan itu saja
  (permission `verification.decide`), bukan semua admin.
- Perubahan status → pelapor (mengikuti jejak audit yang sama dengan notifikasi
  dalam aplikasi).
- Komentar baru → pelapor + peserta komentar sebelumnya, kecuali penulisnya.
- Kegagalan pengiriman → terlihat di dashboard admin (permission `email.monitor`:
  Owner dan Admin Platform), bukan email berantai.

## Preferensi pengguna

Pengaturan akun → bagian "Notifikasi email": perubahan status laporan, komentar,
laporan baru di sekolahku, aktivitas administratif. Tersimpan di
`users.notification_preferences` (JSON).

## Konfigurasi SMTP (production)

Di `.env` server (jangan pernah commit / kirim lewat chat):

```
MAIL_MAILER=smtp
MAIL_HOST=ruteaman.my.id
MAIL_PORT=465
MAIL_ENCRYPTION=ssl
MAIL_USERNAME=service@ruteaman.my.id
MAIL_PASSWORD=(password mailbox cPanel)
MAIL_FROM_ADDRESS=service@ruteaman.my.id
MAIL_FROM_NAME="RuteAman"
```

Prasyarat domain:

1. Buat mailbox `service@ruteaman.my.id` di cPanel → Email Accounts.
2. cPanel → Email Deliverability: salin nilai SPF & DKIM.
3. Tambahkan sebagai TXT record di Cloudflare (DNS pengelola domain), mode
   DNS-only (awan abu-abu). Tambahkan DMARC dasar: `_dmarc` TXT
   `v=DMARC1; p=none;`.
4. Tunggu propagasi, lalu uji dari Konsol Admin → Email & Notifikasi →
   "Kirim email uji" ke Gmail; pastikan masuk inbox (bukan spam) dan log
   berstatus `sent`.

Mode lokal/pengujian: `MAIL_MAILER=log` atau `array`. Selama mailer masih
log/array, email verifikasi admin ditolak oleh sistem (sesuai perilaku lama)
dan dashboard menampilkan peringatan konfigurasi.

## Antrean

`QUEUE_CONNECTION=database` (tabel `jobs` sudah ada di skema dasar). Scheduler
menjalankan `queue:work --stop-when-empty` tiap menit (terdaftar di
`routes/console.php`) — pastikan cron `schedule:run` server aktif tiap menit.
Di cPanel tanpa daemon, pola inilah yang menguras antrean.

## Menangani email gagal

1. Buka Konsol Admin → Email & Notifikasi, saring status **Gagal**.
2. Baca kolom error (sudah disanitasi): koneksi ditolak = SMTP/host salah;
   autentikasi gagal = username/password mailbox; ditolak penerima = periksa
   SPF/DKIM.
3. Untuk email peristiwa: perbaiki penyebabnya lalu klik **Kirim ulang**.
   Untuk email keamanan (verifikasi/undangan): gunakan fitur kirim ulang di
   halaman akun/pengguna — tautan sekali pakai selalu dibuat baru, tidak
   pernah dipakai ulang dari log.

Status `sent` berarti mailer berhasil menyerahkan pesan ke server tujuan,
bukan jaminan pesan dibaca penerima.

## Batasan Gmail API

Sistem ini **tidak** mengakses inbox Gmail pengguna dan tidak membuat akun
Gmail. Email dikirim sebagai notifikasi biasa melalui SMTP dan dapat dibaca di
klien email apa pun (Gmail, Outlook, Apple Mail, Thunderbird). Akses inbox
memerlukan integrasi Gmail API + OAuth 2.0 terpisah bila suatu saat dibutuhkan.

## Pengujian

`tests/Feature/EmailNotificationTest.php` (mengikuti pola suite yang ada,
memerlukan env uji spatial `RUTEAMAN_SPATIAL_TESTS=1`) mencakup: pencatatan
log verifikasi, penerima laporan baru yang berhak + dedupe, email status dari
jejak audit, email komentar + preferensi, endpoint preferensi, dashboard +
permission + email uji, kirim ulang email gagal, dan sanitasi pesan error.
Seluruh pengujian memakai mail/queue fake — tidak ada email sungguhan terkirim.
