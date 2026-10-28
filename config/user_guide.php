<?php
// Screenshots use synthetic local demo accounts, never production data.
return json_decode(<<<'JSON'
{
  "version": "1.0",
  "sections": [
    {
      "id": "mulai",
      "title": "Mulai dari beranda",
      "image": "beranda.webp",
      "steps": [
        "Buka situs RuteAman menggunakan browser terbaru. Di HP, gunakan menu bawah: Dashboard, Peta, Lapor, Laporanku, dan Akun. Di komputer, gunakan navigasi halaman dan tombol Akun.",
        "Peta dan informasi kondisi bisa dibaca sebelum masuk. Akun diperlukan untuk riwayat laporan pribadi, konfirmasi kondisi, serta fitur pengelolaan sesuai izin.",
        "Berhenti di tempat yang sesuai sebelum memakai peta atau mengirim laporan. Jangan mengoperasikan web sambil berkendara."
      ],
      "note": "RuteAman menampilkan informasi komunitas dan umur data, bukan jaminan jalan aman atau rekomendasi rute paling aman.",
      "gate": null
    },
    {
      "id": "daftar",
      "title": "Buat akun dan pilih sekolah",
      "image": "daftar.webp",
      "steps": [
        "Buka Akun → Daftar. Isi nama panggilan, bukan nama lengkap siswa.",
        "Pilih provinsi, kabupaten/kota, dan kecamatan yang benar. Cari nama sekolah atau NPSN, lalu ketuk hasil pencariannya; mengetik NPSN saja belum berarti sekolah sudah dipilih.",
        "Periksa nama sekolah dan NPSN yang dikunci setelah dipilih. Gunakan Ganti bila salah memilih. Isi email dan kata sandi beserta konfirmasinya, lalu baca dan lengkapi persetujuan yang diminta.",
        "Kirim formulir dan ikuti pesan halaman. Jika sekolah tidak ditemukan, cek wilayah/NPSN dan hubungi pengelola; jangan mendaftar menggunakan sekolah lain."
      ],
      "note": "Akun admin dibuat atau diundang melalui pengelola, bukan dengan memilih peran admin pada pendaftaran siswa.",
      "gate": null
    },
    {
      "id": "masuk",
      "title": "Masuk ke akun",
      "image": "masuk.webp",
      "steps": [
        "Buka Akun → Masuk. Pengguna biasa mengisi email terdaftar. Admin menggunakan alias login yang diberikan saat akun dibuat.",
        "Isi kata sandi. Aktifkan Ingat saya hanya pada perangkat pribadi. Tekan Masuk dan tunggu sampai berpindah halaman.",
        "Jika lupa kata sandi, gunakan Lupa kata sandi? dan ikuti instruksi email jika layanan email sudah dikonfigurasi. Admin dapat menghubungi pengelola untuk tautan reset.",
        "Jika akun dinonaktifkan atau fitur sekolah dijeda, hubungi pengelola. Jangan mencoba membuat akun baru untuk melewati pembatasan."
      ],
      "note": "",
      "gate": null
    },
    {
      "id": "profil",
      "title": "Buka profil dan panduan ini",
      "image": "profil.webp",
      "steps": [
        "Setelah masuk, buka Akun → Profil Saya. Panduan Pengguna tersedia di menu Akun dan di kartu panduan pada Profil Saya.",
        "Ubah nama tampilan pada Nama panggilan lalu tekan Simpan nama. Pilih avatar bawaan atau Ganti foto bila unggahan diizinkan; avatar wajah tidak wajib.",
        "Foto profil menerima JPEG/PNG/WebP maksimal 2 MB dan minimal 128 × 128 piksel. Potong foto sebelum menyimpan. Hapus foto / reset mengembalikan avatar inisial.",
        "Periksa email dan status verifikasi. Untuk mengganti email, buka Ubah email, isi email baru dan kata sandi saat ini, lalu ikuti tautan verifikasi. Alias login admin tetap; email kontak admin dapat diperbarui."
      ],
      "note": "Avatar merupakan data akun privat. Unggah dapat dibatasi oleh fitur sekolah atau persetujuan orang tua.",
      "gate": null,
      "extra_images": [
        "akun.webp"
      ]
    },
    {
      "id": "peta",
      "title": "Pilih sekolah dan baca peta",
      "image": "peta.webp",
      "steps": [
        "Buka Peta. Pilih Sekolah / catchment dari daftar, atau gunakan Pilih sekolah lewat peta.",
        "Tekan Perbarui rute tersimpan. Periksa status pemuatan dan kartu rute. Rute tersimpan tidak membutuhkan GPS atau layanan analisis graph.",
        "Geser peta dan gunakan + / − untuk zoom. Ketuk marker kondisi untuk melihat ringkasannya; buka detail untuk menilai status dan kebaruan bukti.",
        "Buka Icon kondisi & riwayat untuk mengenali jenis marker. Marker lama tetap dapat terlihat; warna terang/redup menunjukkan kebaruan, bukan tingkat bahaya atau status penanganan."
      ],
      "note": "Tidak adanya garis rute dapat berarti sekolah belum memiliki rute tersimpan. Tidak adanya marker tidak berarti jalan bebas masalah.",
      "gate": null
    },
    {
      "id": "rute",
      "title": "Bandingkan rute dan analisis graph",
      "image": "graph.webp",
      "steps": [
        "Bandingkan kartu rute berdasarkan jarak, durasi jika tersedia, Beban Hazard, umur data, dan keterangan sumber. Jangan memilih hanya berdasarkan satu angka.",
        "Untuk analisis tambahan, buka Analisis graph jalan (opsional). Tentukan titik awal melalui Pilih di peta atau Gunakan lokasi sekali.",
        "Pilih moda perjalanan yang sesuai, lalu tekan Analisis alternatif rute. Tunggu hasil; lihat garis Rute A dan Alternatif, serta rincian tiap kartu.",
        "Bila analisis gagal, baca pesan status dan coba lagi seperlunya. Pengelola perlu memeriksa layanan routing, kuota, serta konfigurasi server. Mengaktifkan API tidak otomatis membuat rute tersimpan."
      ],
      "note": "Screenshot menunjukkan rute simulasi dan kontrol graph. Titik awal bukan riwayat perjalanan; pilihan rute bukan jaminan keselamatan.",
      "gate": null
    },
    {
      "id": "gps",
      "title": "Pilih titik dan izinkan GPS",
      "image": "lokasi.webp",
      "steps": [
        "Cara paling sederhana adalah mengetuk titik yang relevan di peta. Untuk melapor, pilih lokasi kondisi jalan, bukan alamat rumah atau posisi siswa.",
        "Jika memakai GPS, aktifkan Lokasi pada HP, izinkan browser menggunakan lokasi, lalu tekan Gunakan lokasi sekali dan setujui permintaan situs.",
        "GPS browser memerlukan HTTPS. Periksa izin lokasi situs pada browser jika tidak muncul atau ditolak. Tunggu posisi terbaca dan cek lagi ketepatan titiknya.",
        "Jika GPS tidak tersedia, tetap pilih titik di peta atau isi Latitude dan Longitude. Pada formulir laporan, titik harus berada dalam wilayah pemantauan sekolah."
      ],
      "note": "Jangan menyalin koordinat screenshot ini. Semua koordinat dan sekolah dalam screenshot adalah simulasi, bukan lokasi pengguna.",
      "gate": null
    },
    {
      "id": "lapor-akun",
      "title": "Kirim laporan menggunakan akun",
      "image": "lapor-akun.webp",
      "steps": [
        "Pastikan kamu sudah masuk dan terhubung ke sekolah yang benar. Buka Lapor atau tombol + Lapor di peta.",
        "Pada langkah 1, tandai titik kondisi di peta, gunakan lokasi sekali, atau masukkan koordinat. Pastikan titik sesuai wilayah sekolah.",
        "Pada langkah 2, pilih kategori kondisi yang benar. Pada langkah 3, tambahkan foto bila perlu; foto opsional, maksimal 5 MB. Hindari wajah, plat nomor, dan identitas pribadi.",
        "Pada langkah 4, isi label lokasi berupa jalan/patokan umum dan keterangan singkat bila perlu. Periksa kembali lokasi, kategori, dan bukti, lalu tekan Kirim laporan.",
        "Baca pesan hasil. Jika ada kondisi serupa, buka laporan yang sudah ada daripada mengirim berulang. Konfirmasi hanya dilakukan jika kamu memang memiliki pengamatan independen."
      ],
      "note": "Foto diproses dan metadata dibuang; foto ditolak bila sanitasi gagal. Jangan mengirim foto mentah yang memuat data pribadi dengan mengandalkan penghapusan metadata saja.",
      "gate": null,
      "extra_images": [
        "kirim-akun.webp"
      ]
    },
    {
      "id": "lapor-tamu",
      "title": "Kirim laporan tanpa akun",
      "image": "lapor-tamu.webp",
      "steps": [
        "Pada perangkat yang belum login, buka menu Lapor atau tombol + Lapor. Jika sudah login, tombol Lapor mengarah ke formulir akun.",
        "Pilih sekolah aktif yang pelaporannya tersedia. Nama / nama samaran boleh dikosongkan untuk Pelapor anonim. Pemilihan sekolah tidak membuktikan kamu anggota sekolah itu.",
        "Tandai lokasi dalam wilayah sekolah dan pilih kategori. Tambahkan foto opsional maksimal 5 MB, label lokasi umum, serta keterangan tanpa identitas pribadi.",
        "Baca pernyataan persetujuan dan centang jika sesuai. Tekan Kirim untuk diperiksa. Jangan menekan berulang; tunggu halaman tanda terima.",
        "Laporan tamu masuk antrean pengelola dan belum tampil publik sampai diverifikasi. Nama samaran hanya terlihat pengelola yang berwenang; tidak ada akun yang dibuat otomatis."
      ],
      "note": "Laporan tamu tidak masuk Laporanku pada akun dan tidak otomatis dihubungkan saat kamu login. Konfirmasi kondisi tetap membutuhkan akun.",
      "gate": null,
      "extra_images": [
        "kirim-tamu.webp"
      ]
    },
    {
      "id": "terkirim",
      "title": "Pahami hasil pengiriman",
      "image": "terkirim.webp",
      "steps": [
        "Jika muncul Laporan diterima, pengiriman berhasil dan laporan tamu menunggu pemeriksaan pengelola.",
        "Jika muncul Kondisi serupa sudah tercatat, laporan baru tidak dibuat. Pada alur tamu, nama dan foto pengiriman duplikat tidak disimpan dan tidak menjadi konfirmasi tambahan.",
        "Jika ada pesan validasi, perbaiki kolom yang disebutkan. Pastikan sekolah, lokasi, kategori, dan persetujuan sudah lengkap.",
        "Jika batas pengiriman tercapai, tunggu sebelum mencoba lagi. Jangan mengganti identitas atau memakai skrip untuk melewati batas."
      ],
      "note": "Tanda terima tamu bergantung pada sesi browser. Simpan catatan sendiri bila diperlukan; tidak ada janji pelacakan pribadi atau notifikasi tamu.",
      "gate": null
    },
    {
      "id": "riwayat",
      "title": "Pantau Laporanku",
      "image": "laporanku.webp",
      "steps": [
        "Setelah masuk, buka Laporanku pada menu bawah atau menu Akun.",
        "Daftar ini berisi laporan yang dikirim memakai akun tersebut. Ketuk laporan untuk melihat detail kondisi dan perkembangan statusnya.",
        "Status dapat berubah ketika ada konfirmasi, pemeriksaan, kedaluwarsa bukti, atau tindak lanjut. Jika laporan sudah tidak dapat dibuka, kondisi mungkin ditolak atau tidak lagi dapat diakses oleh akunmu.",
        "Gunakan Buat laporan hanya untuk pengamatan baru yang memang belum tercatat. Laporan tamu tidak ditambahkan ke daftar akun secara otomatis."
      ],
      "note": "",
      "gate": null
    },
    {
      "id": "kondisi",
      "title": "Baca bukti dan konfirmasi kondisi",
      "image": "kondisi.webp",
      "steps": [
        "Buka kondisi melalui marker peta atau daftar laporan. Baca kategori, label lokasi, status, umur bukti, dan riwayat yang tersedia.",
        "Jika kamu berada pada wilayah sekolah yang sesuai, login, dan fitur konfirmasi tersedia, gunakan pilihan konfirmasi sesuai pengamatanmu: kondisi masih ada atau sudah tidak ada.",
        "Jangan mengonfirmasi laporan sendiri atau memberi konfirmasi tanpa melihat kondisi. Satu pengguna tidak dihitung berkali-kali sebagai bukti independen.",
        "Bila tombol konfirmasi tidak tersedia, kondisi mungkin menunggu moderasi, tidak aktif, di luar cakupan sekolah, atau fitur akun/sekolah dibatasi. Hubungi pengelola jika perlu."
      ],
      "note": "Konfirmasi komunitas berbeda dari verifikasi pengelola. Umur data bukan bukti bahwa kondisi sudah ditangani; Beban Hazard bukan label aman.",
      "gate": null
    },
    {
      "id": "notifikasi",
      "title": "Periksa notifikasi dan pengaturan",
      "image": "notifikasi.webp",
      "steps": [
        "Buka Akun → Notifikasi untuk melihat pembaruan yang tersedia. Baca isinya dan buka tautan terkait bila ditampilkan; gunakan tandai sudah dibaca bila sudah diperiksa.",
        "Buka Akun → Pengaturan untuk menyesuaikan preferensi yang tersedia, lalu simpan perubahan.",
        "Jangan menganggap email atau notifikasi selalu terkirim. Ketersediaannya bergantung pada konfigurasi sistem dan peristiwa yang memang dicatat.",
        "Jika butuh bantuan, buka Bantuan dan Kontak. Gunakan kanal resmi sekolah jika kontak belum dikonfigurasi."
      ],
      "note": "",
      "gate": null,
      "extra_images": [
        "pengaturan.webp"
      ]
    },
    {
      "id": "keamanan",
      "title": "Amankan akun dan keluar",
      "image": "keamanan.webp",
      "steps": [
        "Buka Akun → Keamanan. Isi kata sandi saat ini, kata sandi baru, dan konfirmasi; kata sandi baru minimal 10 karakter dengan huruf besar/kecil, angka, dan simbol.",
        "Setelah kata sandi diubah, perangkat lain diminta masuk kembali. Jika tersedia, periksa daftar sesi dan gunakan Keluar dari semua perangkat lain.",
        "Buka Akun → Privasi dan Data untuk membaca pilihan ekspor data dan penghapusan akun. Unduh data ke perangkat pribadi; baca konsekuensi sebelum menghapus akun.",
        "Setelah selesai, buka Akun → Keluar, terutama pada komputer bersama. Jangan membagikan kata sandi atau membiarkan sesi aktif di perangkat orang lain."
      ],
      "note": "Menghapus akun tidak berarti seluruh catatan kondisi jalan dihapus; laporan dipertahankan tanpa identitas sesuai mekanisme aplikasi.",
      "gate": null,
      "extra_images": [
        "privasi.webp"
      ]
    },
    {
      "id": "verifikasi",
      "title": "Pengelola: periksa antrean laporan",
      "image": "verifikasi.webp",
      "steps": [
        "Buka Akun → Antrean Verifikasi. Menu ini hanya tersedia untuk akun dengan izin verification.decide dalam cakupan sekolah.",
        "Periksa kategori, titik, umur bukti, jumlah laporan, dan bukti privat yang boleh dilihat. Pada laporan tamu, nama dan pilihan sekolah bukan identitas terverifikasi.",
        "Pilih Verifikasi bila bukti memadai, Minta bukti jika belum cukup, atau Tolak bila tidak layak. Isi catatan yang relevan dan hindari data pribadi.",
        "Laporan tamu yang disetujui dapat masuk peta publik sebagai kondisi. Nama tamu dan keterangan privat tidak ikut dipublikasikan. Jangan memverifikasi laporanmu sendiri."
      ],
      "note": "Keputusan dicatat dalam audit log. Panduan bukan izin akses; tombol dan data tetap mengikuti hak akun yang sebenarnya.",
      "gate": "verification.decide"
    },
    {
      "id": "sekolah",
      "title": "Pengelola: tindak lanjut sekolah",
      "image": "sekolah.webp",
      "steps": [
        "Buka Dashboard Sekolah jika tersedia. Periksa ringkasan kondisi dan daftar yang termasuk wilayah sekolahmu.",
        "Jika memiliki izin pengelolaan eskalasi, pilih kondisi, lengkapi kanal/rujukan tindak lanjut, dan buat paket eskalasi sesuai kebutuhan.",
        "Perbarui status tanggapan dan catatan berdasarkan informasi yang benar. Paket eskalasi disiapkan tanpa identitas pelapor.",
        "Akses Manajemen Pengguna hanya jika tersedia. Kelola akun dalam cakupan sekolah dan jangan memberikan izin lebih luas dari kebutuhan."
      ],
      "note": "Akun hanya-baca tidak dapat mengubah keputusan atau tindak lanjut. Fitur dapat dibatasi oleh izin dan status sekolah.",
      "gate": "dashboard.view"
    },
    {
      "id": "admin",
      "title": "Administrator: kelola sistem",
      "image": "admin.webp",
      "steps": [
        "Buka Akun → Konsol Admin. Navigasi dan sekolah yang tersedia bergantung pada tingkat peran serta izin akun.",
        "Kelola sekolah dan fitur sekolah melalui menu yang tersedia. Sebelum menjeda atau mengarsipkan sekolah, pahami dampak terhadap pelaporan dan akses pengguna.",
        "Buat/undang admin sesuai cakupan yang dibolehkan. Berikan izin minimum yang diperlukan; tindakan sensitif dapat meminta autentikasi ulang.",
        "Periksa audit log, kondisi, ekspor, dan pengaturan feed jika memiliki izin. Jangan membagikan tautan undangan/reset, token feed, API key, atau hasil ekspor privat.",
        "Jika ada kegagalan server, catat waktu dan pesan kesalahan lalu hubungi pengelola hosting. Jangan menghapus database atau mengubah berkas konfigurasi hanya untuk mencoba-coba."
      ],
      "note": "Pilihan menu berbeda untuk Admin Utama, Admin Platform, Admin Sekolah, dan Staf Admin. Gunakan hanya tindakan yang diizinkan dalam cakupan akunmu.",
      "gate": "admin"
    },
    {
      "id": "kendala",
      "title": "Jika menemui kendala",
      "image": "peta.webp",
      "steps": [
        "Sekolah tidak ditemukan saat daftar: periksa kecamatan dan NPSN, pilih hasil pencarian yang benar, lalu hubungi pengelola jika layanan direktori bermasalah.",
        "Peta ada tetapi garis rute tidak tampil: cek sekolah, rute tersimpan, titik awal, moda, serta pesan analisis. Masalah layanan routing harus diperiksa pengelola.",
        "Foto ditolak: gunakan JPEG/PNG/WebP yang valid, ukuran sesuai batas, dan gambar yang tidak rusak. Jangan mengunggah data pribadi.",
        "Laporan gagal: baca validasi, pastikan titik dalam wilayah sekolah, lengkapi kategori/persetujuan, dan tunggu jika terkena batas pengiriman.",
        "404/500 atau sesi berakhir: login kembali bila perlu. Jika tetap gagal, kirim waktu, URL tanpa token, dan screenshot pesan kepada pengelola; jangan sertakan kata sandi, API key, atau isi .env."
      ],
      "note": "Sebelum berangkat, tetap gunakan penilaian kondisi nyata dan arahan pihak berwenang. Bila ada keadaan darurat, hubungi layanan darurat atau pihak setempat yang sesuai.",
      "gate": null
    }
  ]
}
JSON, true, 512, JSON_THROW_ON_ERROR);
