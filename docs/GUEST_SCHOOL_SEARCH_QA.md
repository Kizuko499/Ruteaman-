# QA pencarian sekolah laporan tamu

- PHP 8.4, Laravel 13, MariaDB 10.11; database uji terpisah dari data pengguna.
- 144 tes PHP lulus pada source final, termasuk 11 tes baru pencarian sekolah/tiket/registrasi sekolah saat laporan berhasil.
- 12 tes JavaScript marker lulus. Vite build berhasil. Enam file PHP yang berubah/ditambah lolos syntax check. Blade view cache berhasil.
- Browser Chromium: lebar 320, 390, 1440 px; tidak ada overflow horizontal atau error JavaScript yang terdeteksi.
- Browser: nama dan NPSN menghasilkan pilihan sekolah, memilih sekolah direktori memusatkan peta, mengganti pencarian menghapus tiket dan titik, submit sukses menuju receipt, pilihan lokal tetap bekerja, kegagalan API memberi pesan, respons pencarian lama tidak mengganti hasil terbaru, submit tanpa pilihan dibatalkan tanpa mengunci tombol.
- JavaScript mati: dropdown sekolah terdaftar tetap tersedia. Pencarian nasional membutuhkan JavaScript.
- Backend: nama bebas tidak mengganti pilihan terverifikasi; ID sekolah dan tiket yang bertentangan ditolak; tiket diubah/kedaluwarsa/dari sesi lain ditolak; sekolah dijeda/fitur pelaporan mati tidak diaktifkan ulang; lokasi salah tidak mencatat sekolah baru; pencarian/preview tidak membuat sekolah atau akun; laporan sekolah baru tidak membuat akun dan tetap privat/pending.
- Smoke check layanan nyata berhasil: MAN 2 REMBANG / 20363192 dengan lokasi Lasem; MAN 1 REMBANG / 20363196; pencarian SMA NEGERI 1 REMBANG mengembalikan dua NPSN berbeda. Keberhasilan ini tidak menjamin ketersediaan upstream di kemudian hari.
- Browser memakai cache fixture direktori yang deterministik agar tes gangguan, pergantian pilihan, dan pengiriman bisa diulang. Smoke check upstream dilakukan terpisah tanpa fixture.
- Salinan source lokal kehilangan UserGuideController dari patch Panduan Publik. Placeholder lokal dipakai hanya untuk boot route saat tes; placeholder TIDAK dimasukkan ke ZIP, routes hosting TIDAK diganti, dan Panduan Publik bukan cakupan QA patch ini.
- Belum dipasang di hosting pengguna. API wilayah/SSL/firewall/cache browser hosting perlu diperiksa setelah pemasangan.
