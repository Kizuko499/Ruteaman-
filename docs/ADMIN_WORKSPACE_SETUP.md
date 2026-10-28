> **Catatan versi baru:** dokumen ini menyimpan konteks historis. Patch konsol dua tingkat menggantikan pilihan empat tingkat/preset lama. Ikuti `ADMIN_CONSOLE_REPAIR_SETUP.md` dan `ADMIN_CONSOLE_REPAIR_REPORT.md`; akun tingkat lama tidak diubah otomatis.

# Konsol Admin — Foto Masuk, Kontak, Panduan, dan Profil

## Isi paket

1. Administrator Utama dapat mengganti/reset foto kiri halaman masuk pada menu Tampilan & Kontak. Penggantian manual, tidak berputar otomatis. Foto hanya terlihat pada desktop, sesuai tata letak login sebelumnya; di HP foto disembunyikan.
2. Kontak bantuan platform dan per sekolah dapat diisi dari konsol. Tidak perlu mengubah .env untuk menggunakan form baru.
3. Panduan Administrator berada dalam sidebar: sepuluh bab untuk Owner (bab sesuai izin untuk admin lain), langkah pengisian, catatan privasi, dan tujuh screenshot demo.
4. Akun saya, Profil, Pengaturan, Notifikasi, Keamanan, dan Privasi admin memakai sidebar konsol. Pengguna biasa tetap memakai halaman akun lama. Alias login, izin, cakupan, dan aturan unggah avatar tidak diubah.
5. Paket juga menyertakan patch Jenis Kondisi/Logo dan Foto Beranda sebelumnya. Tidak perlu memasang kedua ZIP itu secara terpisah. Prasyarat: RuteAman v7.3 + patch Panduan Publik terakhir. Ini patch kumulatif, bukan Laravel baru.

## Pemasangan hosting

Cadangkan database dan file yang akan ditimpa. Jika mengubah route/controller/model/Blade/aset sendiri setelah patch sebelumnya, gabungkan kustomisasi terlebih dahulu; file bernama sama akan ditimpa.

Upload ZIP ke /home/woixvwwz, lalu Extract di folder tersebut. Gabungkan ruteaman dan public_html; jangan hapus folder lama atau mengekstrak di dalam keduanya sehingga membuat folder bertingkat.

Jalankan satu per satu, berhenti bila ada error:

```bash
cd ~/ruteaman
php artisan down
php scripts/check-admin-workspace.php
php scripts/check-condition-types.php
php scripts/check-home-gallery.php
php artisan optimize:clear
php artisan migrate --force
php artisan optimize
php artisan up
```

Migration terbaru menambah tabel site_presentations dengan satu pengaturan awal kosong. Tidak mengubah akun/kata sandi/kontak yang sudah ada. Migration galeri dan ikon dilewati otomatis bila sudah diterapkan. Screenshot dan aset tersedia di ruteaman/public dan public_html. Tidak perlu npm build, Composer install, db:seed, key:generate, atau cron baru.

PHP GD/WebP wajib tersedia pada PHP situs dan terminal. storage serta bootstrap/cache perlu bisa ditulis. .env, index.php, .htaccess, vendor, runtime storage, foto pengguna, database/demo, dan kredensial tidak termasuk ZIP. Foto login demo pada pengujian bukan nilai awal pengaturan produksi.

## Mengganti foto masuk

Login Administrator Utama → Tampilan & Kontak → Foto halaman masuk. Pilih JPEG/PNG/WebP maksimal 5 MB dan 12 juta piksel. Isi deskripsi, centang persetujuan publikasi, klik Ganti foto halaman masuk. Bila diminta, konfirmasikan kata sandi.

Foto diproses ulang menjadi WebP tanpa metadata; tidak ada deteksi/blur wajah otomatis. Periksa hak penggunaan dan data pribadi sebelum unggah. Tampilan memakai crop, jadi letakkan subjek penting di tengah. Kembali ke foto bawaan menghapus unggahan saat ini dan menggunakan ilustrasi awal.

Foto login dan Foto Beranda terpisah. Mengganti foto login tidak mengubah galeri atau akses masuk. Pesan email/kata sandi tidak sesuai pada screenshot pengguna tetap masalah identitas/kata sandi, bukan foto; gunakan alias dan kata sandi yang benar atau prosedur reset resmi.

## Cara mengisi kontak pada halaman Bantuan

- Untuk bantuan platform/akun tanpa sekolah: Administrator Utama → Tampilan & Kontak → Kontak bantuan platform.
- Untuk sekolah tertentu: Kontak Sekolah → sekolah yang benar → Kontak bantuan sekolah. Mengubah memerlukan izin schools.manage dan cakupan/status yang sesuai.
- Isi satu kanal resmi: URL HTTPS, mailto:alamat-email-resmi, atau nomor telepon sebagai teks. Untuk WhatsApp, gunakan https://wa.me/ diikuti nomor resmi dengan kode negara tanpa tanda +; jangan gunakan nomor contoh sebagai nomor sungguhan.
- Centang persetujuan bahwa kanal tersebut resmi dan boleh ditampilkan publik, lalu simpan.

Urutan pembacaan: pengaturan sekolah baru → konfigurasi kontak sekolah lama (NPSN/ID) → pengaturan platform baru → konfigurasi kontak umum lama. Kosongkan kolom untuk kembali ke fallback lama, bukan menghapus .env.

Pengguna melihat kontak berdasarkan sekolah akun, bukan sekolah terakhir di peta. Owner tanpa sekolah menggunakan kontak platform. Kontak Bantuan berbeda dari email kontak pribadi admin, alias login, dan kontak undangan. Kolom pengisian baru belum otomatis memiliki kontak nyata; operator perlu mengisi kanal resmi miliknya.

## Profil admin

Klik Akun saya pada sidebar. URL khusus: /admin/account/profile, lalu tab Pengaturan, Notifikasi, Keamanan, Privasi. URL /account/... lama tetap bekerja dan juga merender sidebar untuk admin, agar tautan lama tidak rusak.

Form menyimpan melalui layanan akun yang sama. Penyimpanan nama, avatar bawaan, notifikasi, dan preferensi tidak menghilangkan sidebar. Verifikasi email kembali ke profil konsol bagi admin. Peran ditampilkan sesuai tingkat admin, bukan Siswa. Profil tidak dapat menaikkan peran/izin atau menghapus admin sendiri.

Kebijakan unggah avatar tetap membutuhkan keterhubungan sekolah, fitur avatar, serta persetujuan yang berlaku. Jika Owner tanpa sekolah belum bisa mengunggah avatar, gunakan avatar bawaan; pembaruan tata letak tidak melewati aturan tersebut.

## Panduan

Buka Panduan Administrator di sidebar atau Panduan admin di bagian atas konsol. Daftar isi menaut ke petunjuk: ringkasan, data sekolah/BMKG/catchment, kontak, akun/undangan/izin, verifikasi, foto masuk, galeri, jenis kondisi/logo, profil admin, dan kendala/audit. Bab fitur Owner tidak muncul untuk admin sekolah.

Screenshot menunjukkan data simulasi dan tidak untuk disalin sebagai kontak/kode BMKG nyata. Panduan memakai sidebar yang sama; membuka versi gambar besar dilakukan pada tab baru.

## Uji setelah pemasangan

- Owner melihat Tampilan & Kontak; admin sekolah/tamu tidak bisa mengubah pengaturan platform.
- Unggah foto lalu buka login pada tab baru, pastikan foto berubah; reset kembali ke bawaan.
- Isi kontak platform/sekolah dan buka Bantuan dengan akun yang sesuai; pastikan urutan pemilihan benar.
- Buka Akun saya, pindah kelima tab, simpan nama/fitur yang diperbolehkan; sidebar tetap ada.
- Pengguna biasa tidak mendapatkan sidebar admin atau akses konsol.
- Panduan, screenshot, jenis kondisi/logo, galeri, dan panduan publik tetap tersedia.

Pengujian lokal: 140 tes PHP berhasil (1296 assertions) dan 12 tes JavaScript berhasil; Blade berhasil dikompilasi. Alur ganti foto, kontak, simpan nama dan semua tab akun admin diperiksa pada 390x844 dan 1440x900 dengan akun demo. Data demo serta router pratinjau lokal tidak masuk paket.
