# Sistem Notifikasi Email RuteAman — Penerima Mandiri & Ringkasan Berkala

Dokumen ini melengkapi `DOKUMENTASI_EMAIL.md` (fondasi antrean, log, dan email
peristiwa untuk akun internal yang tidak berubah). Fitur pada dokumen ini adalah
lapisan tambahan: **penerima email mandiri** di luar akun pengguna.

## Konsep

Ada dua jalur penerima yang hidup berdampingan:

1. **Akun internal** — pengguna RuteAman menerima email peristiwa sesuai peran,
   cakupan sekolah, dan preferensi akunnya (`users.notification_preferences`).
2. **Penerima mandiri** — alamat email apa pun (mis. kepala sekolah yang tidak
   punya akun) yang didaftarkan administrator di tab **Penerima Notifikasi**,
   lengkap dengan langganan kategori, mode pengiriman, jadwal, dan zona waktu.

## Penerima mandiri

- Dikelola dari Admin → Email & Notifikasi → tab **Penerima Notifikasi**
  (permission `email.recipients`). Owner/Admin Platform mengelola semua
  penerima; Admin Sekolah hanya penerima milik sekolahnya.
- Setiap alamat wajib **verifikasi**: sistem mengirim tautan bertanda tangan
  (berlaku 72 jam) + token acak yang hash-nya tersimpan. Alamat belum
  terverifikasi tidak menerima apa pun selain email verifikasi dan email uji
  yang sengaja dikirim administrator.
- Mengubah alamat email membatalkan verifikasi lama dan mengirim verifikasi baru.
- Menonaktifkan/menghapus penerima menghentikan pengiriman berikutnya; email
  yang sudah mengantre untuk penerima itu **dibatalkan otomatis** oleh job
  pengiriman (status log menjadi `cancelled`).
- Cakupan `platform_only` (aktivitas akun & kesehatan sistem email) tidak pernah
  dikirim ke penerima bercakupan sekolah.

## Mode & jadwal

- **Langsung**: email peristiwa (laporan baru, perubahan status, komentar)
  dikirim seketika untuk kategori yang dilanggan.
- **Ringkasan berkala**: interval N hari (dihitung dari jadwal terakhir),
  mingguan (hari + jam), bulanan (tanggal tertentu — dijepit ke hari terakhir
  bulan pendek — / hari pertama / hari terakhir). Semua di zona waktu penerima.
- **Kombinasi**: keduanya.
- Command scheduler `notifications:dispatch-digests` berjalan tiap 15 menit
  (scheduler yang sama dengan worker antrean). Periode ringkasan diturunkan
  dari jadwal tersimpan, dan pasangan (penerima, periode) unik di database —
  scheduler yang berjalan berulang atau terlambat tidak menggandakan kiriman.
- **Jam tenang** opsional per penerima: ringkasan dan notifikasi nonkritis
  (komentar) digeser keluar jam tenang; laporan baru dan perubahan status
  tetap dikirim langsung.
- Ringkasan kosong dilewati secara default; per penerima dapat diatur untuk
  tetap mengirim pemberitahuan "tidak ada aktivitas".

## Tab dashboard

- **Ringkasan**: statistik antrean aktual, penerima aktif, jadwal aktif,
  pengiriman berikutnya.
- **Riwayat**: filter status/kategori/tanggal + pencarian penerima/subjek,
  halaman detail, **Batalkan** untuk yang masih antre, kirim ulang untuk yang
  gagal, ekspor CSV.
- **Konfigurasi**: panel SMTP aman (tanpa kata sandi) + **Uji Koneksi SMTP**
  yang melakukan jabat tangan nyata dan memeriksa sertifikat dengan verifikasi
  TLS aktif. Hasil uji terakhir tersimpan dan ditampilkan.

## Izin (permission)

| Izin | Hak |
| --- | --- |
| `email.monitor` | Melihat tab Ringkasan, Riwayat (termasuk Detail), Konfigurasi; mengirim email uji; mengirim ulang email gagal. |
| `email.recipients` | Mengelola penerima notifikasi (tambah, ubah, hapus, aktif/nonaktif, kirim ulang verifikasi, email uji per penerima) dalam cakupan akun. |
| `email.manage` | Membatalkan pengiriman yang mengantre, mengekspor riwayat CSV, dan menjalankan Uji Koneksi SMTP. Hanya tingkat platform (Owner/Admin Platform). |

Owner melewati semua izin. Langit-langit peran dan preset diperbarui pada
`config/ruteaman_roles.php`; akun lama perlu izinnya disimpan ulang oleh Owner
agar izin baru yang masuk langit-langitnya ikut aktif.

## Panduan di dalam aplikasi

Panduan Administrator (menu Panduan) memiliki dua bab baru yang tampil mengikuti
izin akun: **Email & Notifikasi** (gate `email.monitor`) dan **Penerima
Notifikasi** (gate `email.recipients`), terdaftar di `config/admin_guide.php`.

## Keamanan

- Kredensial SMTP tetap di environment server; tidak pernah tampil di UI/log.
- Alamat penerima di log selalu tersamar; hash SHA-256 dipakai untuk dedupe.
- Isi email hanya memuat data sesuai cakupan penerima; tidak ada kata sandi,
  token, atau kredensial di dalam email maupun payload log.

## Tab Kirim Informasi (admin.broadcast)

Tab **Kirim Informasi** (izin `email.manage`) mengirim email informasi bebas
tanpa mendaftarkan penerima terlebih dahulu. Target: alamat manual,
semua penerima notifikasi terverifikasi, semua admin & staf aktif, atau semua
pengguna terdaftar aktif. Pengiriman LANGSUNG (sinkron, tanpa antrean) dengan
batas total 200 alamat sekali kirim agar waktu proses wajar; alamat tidak
valid membatalkan pengiriman sebelum apa pun terkirim. Setiap alamat menerima
email sendiri-sendiri dan hasilnya langsung tercatat final di Riwayat
Pengiriman sebagai kategori **Informasi admin**; yang gagal dapat dikirim
ulang dari Riwayat. Izin `email.manage` kini juga dapat diberikan ke akun
Admin Sekolah per akun (ceiling ditambah; preset tidak berubah).
