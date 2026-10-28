# Hapus akun: peringatan Batalkan / Lanjutkan

Sesuai permintaan terbaru, Owner tidak perlu mengetik nama maupun kata sandi ketika menghapus akun yang sedang dibuka. Identitas target ditampilkan dari server; bukan password yang diisi otomatis. Klik Hapus akun membuka peringatan. Batalkan/X/Esc tidak mengirim hapus. Lanjutkan mengirim konfirmasi eksplisit dengan CSRF dan proof halaman sekali pakai.

## Perubahan otorisasi yang disengaja

Alur cepat baru memakai sesi Owner aktif, bukan password/reauth10 menit. Ini menggantikan persyaratan ketik nama/password pada UI sebelumnya, sesuai instruksi pengguna terbaru. Siapa pun yang menguasai sesi Owner aktif dapat menjalankan aksi ini; gunakan perangkat terpercaya dan logout setelah selesai. Tidak menyimpan, memprefill, atau mengirim password untuk alur cepat.

Server tetap memastikan role Owner aktual/aktif, session version/idle/auth hash, izin, target tepat, self/last Owner, transaksi dan audit. Proof halaman random64 karakter terikat sesi, actor/version, target identity fingerprint; hanya hash tersimpan, TTL10 menit, batas10 target terbaru. Cache atomic add mencegah replay proof. Perubahan identitas/peran/session target, expiry atau session berbeda menolak konfirmasi. Proof tidak diflash/log audit; tidak diletakkan dalam URL.

Alur cepat selalu anonimisasi (cabut sesi, tidak bisa login, histori laporan/audit tetap ada); tidak mengizinkan permanent melalui payload tambahan. Endpoint lama DELETE /admin/users/{user} tetap punya reauth/password/typed confirmation untuk kompatibilitas. Controller/Request/Service lama tidak diberi flag bypass yang bisa disuplai client. Core delete memakai metode private dengan flag hanya ditentukan server setelah proof diverifikasi. Cleanup avatar dilakukan sesudah transaksi terluar commit.

## Pasang

Patch ini menggantikan popup hapus yang sebelumnya meminta nama/password. Prasyarat: konsol Owner terbaru, popup atas, picker sekolah dan patch navigasi sebelumnya sudah ada.

Backup source/DB. Ekstrak ZIP di /home/woixvwwz, bukan folder ruteaman. Source harus ke /home/woixvwwz/ruteaman; public_html/js/admin-user-delete.js ke /home/woixvwwz/public_html/js. Jika document root ruteaman/public, JS public/js cukup. Pastikan JS versi2 dimuat, jangan timpa file lagi dengan patch popup lama.

File produksi: QuickUserDeletion service baru, OwnerUserService, OwnerUserController, routes/admin.php, bootstrap/app.php (dontFlash proof), users/form.blade.php (tombol atas), users/delete-dialog.blade.php, public/js/admin-user-delete.js. Tidak menimpa .env/database/build Vite/picker online/layout tombol kembali.

```bash
cd /home/woixvwwz/ruteaman
php artisan optimize:clear
php artisan route:list --path=confirm-delete
php artisan view:cache
```

Harus ada DELETE admin/users/{user}/confirm-delete. Tidak ada migrate, Composer update atau npm build. Hard refresh. Buka akun lain, Hapus akun, baca nama/email pada peringatan, Batalkan atau Lanjutkan. Bila halaman kedaluwarsa, refresh lalu coba lagi. Bila sesi Owner berakhir, harus login kembali (bukan otomatis mengabaikan auth).

## QA aktual dan batasan

14 tes backend baru +7 tes popup:21 tes54 assertions lulus. Backend nyata dijalankan pada SQLite in-memory dengan akun sintetis: sukses tanpa nama/password/fresh reauth, anonimisasi/revoke/histori/audit, payload permanent diabaikan, missing consent/proof, wrong target/session, replay, target berubah, nonOwner, self/last Owner, proof expired, session revoked/logged out, dontFlash proof. Legacy endpoint tetap428 tanpa reauth.

Regresi terisolasi termasuk online/picker/aktivitas dicatat di QA/quick-delete-regression.log. Browser localhost memakai popup Blade asli/endpoint penerima tiruan: tanpa input nama/sandi, fokus Batalkan, buka/batal tanpa POST, POST hanya Lanjutkan, viewport360/768/1280 tanpa overflow/axe/JS error. Tidak menghapus akun nyata di browser. Resolusi middleware HTTP diverifikasi: Session, PreventRequestForgery, Auth, AuthenticateSession, AdminSession, ActiveAccount, RoleMin dan throttle; bukan hanya output route-list console.

Fixture tes awal gagal karena kolom updated_at tidak dibuat; diperbaiki, lalu seluruh tes diulang dan lulus. Suite penuh MariaDB/PostgreSQL, race lintas proses dan pemasangan hosting tidak diuji ulang untuk perubahan ini. Gunakan staging dan akun uji saat mencoba perilaku delete. Cache session/atomic add hosting harus berfungsi; tidak ada migrasi baru.
