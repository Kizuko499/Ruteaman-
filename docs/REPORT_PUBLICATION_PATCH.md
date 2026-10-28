# Publikasi laporan langsung dan moderasi

## Perilaku
- Default untuk laporan BARU: tamu dan pengguna login langsung tampil publik sebagai **Belum terverifikasi**. Status `new`, confidence dan perhitungan bukti tidak dipalsukan menjadi terverifikasi.
- Tamu tidak dibuatkan akun dan tidak mempunyai Laporanku pribadi. Bukti penerimaan anonim tetap tersedia; nama tamu/deskripsi privat tidak dikirim ke API publik.
- Admin Utama → Fitur Sekolah: dua saklar independen, **Wajib verifikasi laporan tamu** dan **Wajib verifikasi laporan pengguna login**. Mati = langsung tampil; Hidup = antrean sampai pengelola menyetujui.
- Saklar disimpan per sekolah. Menu penerapan massal dapat mengubah satu saklar ke semua sekolah. Fitur Verifikasi harus aktif jika salah satu jenis laporan diwajibkan pemeriksaan.
- Perubahan saklar hanya memengaruhi laporan berikutnya: tidak memublikasikan antrean lama dan tidak menarik laporan yang sudah tampil. Antrean lama tetap perlu diputuskan pengelola.
- Pengguna login dapat melacak laporan sendiri yang menunggu pemeriksaan di Laporanku; konfirmasi publik tidak dapat meloloskan pemeriksaan wajib.
- Admin → Laporan / Kondisi: pilih sekolah dalam cakupan, lalu **Tangguhkan**, **Pulihkan publikasi**, atau **Hapus dari publik**. Alasan diperlukan dan dicatat dalam audit; konfirmasi keamanan admin tetap berlaku.
- Tangguhkan dapat dipulihkan. Hapus dari publik adalah penarikan permanen lewat antarmuka ini, bukan DELETE fisik. Laporan, konfirmasi, dan audit dipertahankan. Tindakan berlaku pada satu titik kondisi agregat beserta laporan terkait.
- Tindakan moderasi menggunakan izin `hazards.reopen` yang sudah ada, tanpa memberikan izin baru otomatis. Admin di luar cakupan dan pengguna biasa ditolak.
- Kondisi tersembunyi dikecualikan dari peta, feed publik, detail anonim, foto/OG, riwayat kedaluwarsa, dan perhitungan rute. Admin yang berwenang tetap dapat membuka detail privat. Cache feed diinvalidasi setelah perubahan.
- Penangguhan bukan perubahan keputusan verifikasi. Titik tidak dapat dipublikasikan ulang melalui verifikasi, konfirmasi, atau Buka kembali ketika disembunyikan.
- Pelaporan aktif, batas spam, deduplikasi, wilayah sekolah, persetujuan, dan sanitasi foto tetap berlaku. Saklar ini tidak mengaktifkan sekolah yang sedang dijeda atau pelaporannya nonaktif.

## Pemasangan cPanel
Patch untuk aplikasi Laravel RuteAman yang telah memakai patch administrator sebelumnya, bukan aplikasi baru. Termasuk kompatibilitas hapus akun cepat dan tombol Kembali. Tidak menyertakan `.env`, vendor, dump database, akun nyata, maupun pengganti UserGuideController.

1. Backup kode aplikasi, `public_html/build`, dan database. Catat pengaturan sekolah yang berlaku.
2. Di Terminal, jalankan `cd /home/woixvwwz/ruteaman` lalu `php artisan down` SEBELUM menimpa file.
3. Upload ZIP ke `/home/woixvwwz`, ekstrak DI SANA, izinkan overwrite. ZIP mempunyai direktori `ruteaman/` dan `public_html/`. Jangan ekstrak di `/home/woixvwwz/ruteaman` supaya tidak menjadi direktori rangkap.
4. Jalankan:

```bash
cd /home/woixvwwz/ruteaman
php artisan optimize:clear
php artisan migrate --force
php artisan optimize
php artisan up
```

Migrasi baru: `2026_10_17_000000_add_report_publication_controls`, menambah `hazards.publication_control` dengan default `normal` dan index. Tidak menghapus atau otomatis meloloskan data antrean lama. Jangan menjalankan migrate:fresh atau demo-reset pada produksi.

Jika migrate atau optimize gagal: jangan jalankan up, simpan keluaran error, periksa backup. Jangan mencoba menghapus tabel atau menjalankan rollback secara sembarang. Menghapus kolom moderation dapat mengungkap kembali laporan tersembunyi bila dipasangkan dengan kode lama. Pemulihan harus memakai pasangan backup kode/database yang konsisten.

Aset Vite sudah dibangun. Upload kedua direktori build yang disediakan; tidak perlu npm build di hosting. Manifest dan semua chunk harus ikut. File build lama boleh tetap ada sementara agar halaman lama/rollback tidak kehilangan asset; jangan hanya menyalin manifest.

## Cek setelah pemasangan
- Admin Utama → Fitur Sekolah: dua saklar review default Mati jika belum pernah disimpan.
- Uji laporan tamu pada titik baru di sekolah pelaporan aktif: muncul Belum terverifikasi tanpa akun.
- Nyalakan hanya review tamu: laporan tamu berikutnya menunggu; laporan login tetap langsung tampil. Balik untuk menguji review login.
- Laporan pending lama tidak ikut terbit ketika saklar dimatikan. Periksa melalui antrean.
- Uji Tangguhkan → hilang untuk browser tanpa login; Pulihkan → kembali jika status memang publik; Hapus dari publik → hilang dan tetap ada audit. Hindari menguji dengan laporan nyata tanpa alasan moderasi yang benar.
- Reload keras jika antarmuka masih memuat asset lama.

## Pengujian lokal
- PHP 8.4.24, Laravel 13, MariaDB 10.11.18 pada database pengujian terpisah.
- PHPUnit: 228 tes / 1909 assertion lulus, termasuk mode lama wajib review dan mode default baru.
- JavaScript: 19 tes lulus; Vite production build berhasil.
- Browser Chromium lokal: 360, 768, 1280 px, halaman Fitur Sekolah dan Laporan / Kondisi; tidak ada overflow horizontal, error JS, atau temuan axe pada halaman yang diperiksa. Saklar terpisah tersimpan, suspend menghasilkan 404 anonim, restore berfungsi.
- PostgreSQL tidak diuji ulang pada patch ini. Hosting produksi belum diakses/dipasangi patch oleh agen.
