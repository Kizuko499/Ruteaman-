# Hapus akun dari bagian atas halaman pengguna

Tombol Hapus akun kini di bawah identitas akun dan sebelum formulir profil, sehingga tidak perlu scroll ke panel hapus di bawah. Klik membuka dialog native dengan nama/alias target, kata sandi Admin Utama, alasan dan opsi permanent jika backend menyatakan eligible. Panel bawah dihapus; endpoint/form DELETE dan validasi server tetap sama.

Tidak menghapus akun hanya karena popup dibuka. Tidak menghapus syarat password/typed confirmation, CSRF, reauth, Owner-only, self/last Owner, eligibilitas permanent atau audit. Default tetap anonimisasi, mencabut sesi dan mempertahankan laporan/histori. Tidak ada impersonasi.

Tombol tidak muncul pada akun sendiri/nonOwner. Dialog mendukung Batal/X/Esc/backdrop, fokus awal dan kembali ke tombol, serta mengosongkan password ketika ditutup/pagehide. Password tidak diprefill dari old input. Error field membuka dialog kembali. Tidak ada window.confirm tambahan karena dialog sudah mengkonfirmasi; global reauth aplikasi tetap berlaku dan dapat meminta konfirmasi ulang bila sesi konfirmasi sudah kedaluwarsa.

## Pemasangan

Backup source. Prasyarat: konsol Owner terbaru beserta picker sekolah yang sudah terpasang. Ekstrak ZIP ke /home/woixvwwz, bukan /home/woixvwwz/ruteaman. Source ke folder ruteaman yang benar; public_html/js/admin-user-delete.js ke webroot publik. Jika webroot langsung ruteaman/public, salinan public_html tidak perlu.

File produksi berubah: resources/views/admin/users/form.blade.php, resources/views/admin/users/delete-dialog.blade.php, public/js/admin-user-delete.js. Tidak mengubah controller, service, request, middleware, route, database, .env, picker online, tombol Kembali atau aset build.

```bash
cd /home/woixvwwz/ruteaman
php artisan view:clear
php artisan view:cache
```

Tidak perlu migrate, npm build atau Composer update. Hard refresh browser. Semua Pengguna → buka akun lain → Hapus akun di atas → konfirmasi. Jangan memakai akun nyata untuk percobaan penghapusan; gunakan akun uji.

## QA aktual

7 tes template/struktur baru:19 assertions lulus. Regresi terisolasi bersama tes online/picker/aktivitas:30 tes92 assertions lulus. JS syntax, Pint file tes, Blade cache lulus. Browser localhost dengan dialog Blade asli dan halaman sintetis: popup terbuka tanpa scroll, fokus awal/restore, password cleared Esc, validasi kosong, Batal tidak POST. Lebar360/768/1280 tanpa overflow/axe violation/JS error. Browser tidak menghapus akun apa pun. Tidak menguji ulang suite DB spatial, penghapusan backend penuh atau hosting pada patch ini; pengaman backend tidak diubah.
