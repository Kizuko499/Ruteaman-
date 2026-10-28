> **Catatan versi baru:** dokumen ini menyimpan konteks historis. Patch konsol dua tingkat menggantikan pilihan empat tingkat/preset lama. Ikuti `ADMIN_CONSOLE_REPAIR_SETUP.md` dan `ADMIN_CONSOLE_REPAIR_REPORT.md`; akun tingkat lama tidak diubah otomatis.

# Perubahan konsep dan spesifikasi F9 — v7.0

## Keputusan yang menggantikan konsep lama

System Admin sebelumnya bukan role produk. Kini operator multi-sekolah mempunyai Owner (Admin Utama) dan Admin Platform; Staf Admin Sekolah juga role produk. Alasan: provisioning banyak sekolah dan delegasi operasional tanpa memberi hak penuh kepada setiap sekolah. Role lama siswa, verifikator, admin sekolah tetap ada. Tingkatan admin 1–4; verifikator/siswa bukan tingkat admin.

F9 kini terdiri dari permission service tunggal, grant ceiling, school scope, dependency, fitur sekolah, re-auth, status pending/active/suspended, undangan, session revocation, alias/kontak, audit dan konsol `/admin`. Fitur membaca peta inti tetap publik. Tidak ada impersonasi, pelacakan lokasi, akses password pengguna, maupun data pelapor dalam ekspor/feed/paket publik.

## Penyimpangan yang diumumkan

- Baseline v6.0 sudah **11 tabel domain**, termasuk saved_routes. Patch ini tidak menambah tabel; klaim total ≤10 tidak dipenuhi. Tidak menghapus tabel lama demi angka batas.
- Alias default `ruteaman.my.id`; bukan mail provisioning atau DNS forwarding.
- Undangan SMTP sinkron setelah commit, bukan persisted queue; dipilih agar token tidak terserialisasi ke jobs/failed_jobs/log. SMTP perlu deployment.
- Staf tingkat 4 boleh memakai konsol terbatas, mengikuti tujuan seluruh tingkat walaupun tabel endpoint prompt hanya menyebut 1–3.
- Provisioning sekolah baru memerlukan cakupan semua sekolah. ID sekolah baru belum ada di cakupan daftar tertentu.
- Catchment maksimum 25 km², satu ring tanpa lubang. Pengaman PostGIS menolak area terlalu besar/tidak valid/pin di luar.
- Batas Owner 3 secara default menghitung pending juga. Owner dapat membuat Owner sejajar, tetapi tidak mengubah Owner sejajar melalui UI sesuai invarian tingkat lebih rendah. Pemulihan lewat CLI.
- Metrik 7 hari disajikan sebagai tabel data nyata; belum ada grafik tren interaktif.
- Saklar sekolah disajikan dalam panel responsif per sekolah, bukan tabel matriks lebar; kontrol per fitur dan penerapan massal tetap ada. Tautan sekali tampil berupa halaman respons, bukan modal tambahan.
- Frontend meneruskan Blade/Vite/Tailwind/Alpine yang ada dengan modul JavaScript terpisah; tidak mengganti stack lama.
- 2FA belum tersedia, sehingga tidak diklaim diterapkan. Harus ditambahkan/diuji sebelum operasional berisiko tinggi.

Checklist implementasi: hierarki di server; grant ceiling/dependency; scope IDOR; re-auth; audit; fitur seketika; public map tetap terbaca; no new tables. Checklist QA dan batas terukur dicatat di ADMIN_QA.
