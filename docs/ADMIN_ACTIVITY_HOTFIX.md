# Hotfix Aktivitas terbaru

Komponen bersama `resources/views/admin/log-rows.blade.php` pada Ringkasan admin dan dashboard sekolah sebelumnya masih menampilkan Aktor #ID / user #ID. Diperbaiki untuk memakai snapshot nama pelaku/target, label tindakan dari konfigurasi, dan waktu WIB.

Entri lama tanpa snapshot dapat menampilkan nama akun saat ini dengan keterangan eksplisit. Lookup dibatasi hanya ID yang disebut dalam daftar serta school scope penonton; tidak ada N+1. Akun hilang/di luar scope tetap fallback ID, tidak mengarang nama. Histori audit tidak diubah. Semua nama di-escape Blade.

Pasang hanya file log-rows.blade.php ke folder resources/views/admin instalasi yang sudah memakai patch konsol. Backup file lama, lalu php artisan view:clear. Tidak membutuhkan migrasi atau build aset.

QA hotfix: 6 tes komponen, 20 assertions lulus di SQLite in-memory; snapshot prioritas, batch legacy, scope sekolah, pelaku null, HTML escaping, empty state. Blade view:cache lulus. Suite penuh MariaDB/PostgreSQL tidak dijalankan ulang untuk hotfix satu template ini; hasil 167 tes sebelumnya berlaku untuk patch dasar.
