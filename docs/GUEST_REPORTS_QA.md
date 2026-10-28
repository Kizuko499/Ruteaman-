# QA laporan tamu v7.3

## Lingkungan

PHP 8.4.24, Laravel 13, MariaDB 10.11.18; database lokal disposable yang terpisah untuk unit/feature, browser dan upgrade data lama. Tidak mengubah hosting pengguna.

## Pengujian

- Semua unit/feature: **105 passed, 966 assertions** pada driver mariadb dan mysql yang mengakses MariaDB.
- Termasuk **14 tes khusus guest report**, dengan 144 assertion: nama kosong/nama privat, tidak membuat akun, sekolah wajib/aktif/feature, area geometri, duplikat, sanitasi foto, shared-layer mati, pengelola sekolah lain ditolak meski catchment overlap, bukti independen, larangan konfirmasi sebelum verifikasi, needs_evidence/rejected/reopen, guest kedaluwarsa tetap antrean, nonce/waktu/consent/honeypot/throttle, CSRF produksi, serta regresi laporan akun.
- Marker JS: **9 passed**. Asset produksi dibangun ulang dengan Vite.
- Semua Blade berhasil dikompilasi dan sintaks PHP lulus.
- Upgrade dengan data v7.2 terisi: **PASS**, digest akun/password, report, status/slug/published_at hazard dan saved_routes tetap sama. Kolom guest/moderasi default false/null untuk data lama.
- Browser Chromium lokal, viewport **390×844** dan **1440×900**: submit dengan nama samaran → tanda terima → login pengelola → antrean privat → verifikasi → marker publik. Nama tidak muncul di tanda terima/payload publik. Marker sebelum/sesudah verifikasi: 1→2 (satu marker demo sudah ada). Tidak ada error JavaScript atau horizontal overflow.

## Batas

Pengujian bukan bukti pemasangan di server pengguna. Routing publik API tidak dipanggil ulang untuk fitur tamu; fix header yang sudah berhasil di hosting dipertahankan. PostgreSQL/MySQL asli, CAPTCHA, retensi otomatis, dan beban spam skala besar belum diuji/ditambahkan. Kebijakan privasi frontend ditambahkan untuk laporan tamu dan penggunaan sesi/cache.
