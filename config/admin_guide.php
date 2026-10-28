<?php

// Chapters are filtered by AdminGuideController using the existing permission gates.
return array (
  'sections' => 
  array (
    0 => 
    array (
      'id' => 'mulai',
      'title' => 'Mulai dari konsol',
      'gate' => NULL,
      'image' => 'overview.webp',
      'steps' => 
      array (
        0 => 'Masuk dengan alias login dan kata sandi admin yang diberikan kepadamu. Alias login tidak selalu berupa alamat email. Bila muncul pesan email/kata sandi tidak sesuai, berarti alias atau kata sandi belum cocok. Mengganti foto profil tidak mengubah akses masuk.',
        1 => 'Buka Ringkasan. Angka institusi/admin dihitung dari database sesuai cakupan akun; institusi yang belum terhubung pada profil Admin Utama bukan berarti tidak ada institusi di platform.',
        2 => 'Di desktop/tablet gunakan tombol «/» untuk mode ikon atau terbuka; pilihan diingat. Label mode ikon muncul saat kursor diarahkan atau saat difokuskan dengan keyboard. Di HP tekan Menu, lalu tutup dengan ×, Esc, ketuk latar, atau geser ke kiri.',
        3 => 'Menu yang tersedia mengikuti tingkat, izin tindakan, fitur institusi, dan cakupan. Menu yang tidak ada bukan alasan mencoba mengubah URL untuk melewati izin.',
      ),
      'note' => 'Panduan ini tidak memberi izin baru. Simpan perubahan akan meminta konfirmasi kata sandi bila sesi konfirmasi sudah kedaluwarsa.',
    ),
    1 => 
    array (
      'id' => 'buat-institusi',
      'title' => 'Membuat institusi baru beserta akun pengelola',
      'gate' => 'schools.provision',
      'image' => NULL,
      'steps' => 
      array (
        0 => 'Gunakan Buat sekolah / kampus dan akun. Pembuatan memerlukan izin membuat institusi (schools.provision) dan cakupan semua institusi; Admin Sekolah tidak dapat membuat institusi baru hanya dengan mengubah alamat halaman.',
        1 => 'Langkah 1 — Data: pilih Sekolah atau Kampus; isi nama resmi, kode negara, kode kampus bila relevan, slug unik, zona waktu, dan kode BMKG bila tersedia. Tentukan pin serta Polygon wilayah pantauan. Data dari katalog dapat terkunci agar identitas resmi tidak berubah.',
        2 => 'Pada peta pilih Letakkan pin untuk titik institusi, lalu Gambar catchment untuk batas wilayah pantauan. Setelah minimal tiga titik ring ditutup otomatis. Urungkan titik memperbaiki titik terakhir; Bersihkan mengulang gambar. Alternatifnya isi koordinat pin serta tempel/unggah Polygon GeoJSON.',
        3 => 'Tekan Lanjut setelah seluruh kolom Data valid. Jika diminta mengisi catchment (wilayah pantauan), pin saja belum cukup: gambar atau unggah Polygon, bukan Point. Pemeriksaan bentuk, luas, dan kecocokan pin juga dilakukan server saat penyimpanan.',
        4 => 'Langkah 2 — Akun: isi nama tampilan pengelola, alias login unik, dan email kontak asli. Peran yang dibuat lewat formulir ini adalah Admin Sekolah; bukan Admin Utama. Jangan isi email kontak dengan alias yang tidak menerima pesan.',
        5 => 'Langkah 3 — Fitur: pilih kemampuan institusi, izin tindakan pengelola, dan cakupan yang diperbolehkan. Fitur yang aktif tidak otomatis memberi semua akun hak melakukan tindakan.',
        6 => 'Langkah 4 — Tinjau: periksa kembali institusi, pin/wilayah pantauan dan identitas pengelola sebelum menyimpan. Konfirmasikan kata sandi admin jika diminta. Institusi baru dari wizard dibuat aktif; akun pengelola menunggu undangan hingga penerima menyelesaikan aktivasi.',
        7 => 'Salin tautan undangan saat ditampilkan dan kirim secara privat kepada pengelola. Pastikan penerima membuka tautan, menetapkan kata sandi minimal 12 karakter sesuai kebijakan, lalu berhasil login. SMTP harus benar agar pengiriman email dapat berjalan.',
      ),
      'note' => 'Pembuatan institusi baru berbeda dari impor institusi nasional pada pemilih akun. Jangan membuat duplikat jika institusi sudah terdaftar; gunakan akun baru atau hubungan katalog pada institusi yang benar.',
    ),
    2 => 
    array (
      'id' => 'sekolah',
      'title' => 'Data institusi dan wilayah pantauan',
      'gate' => 'schools.view',
      'image' => 'school.webp',
      'steps' => 
      array (
        0 => 'Buka Institusi dan pilih institusi dalam cakupan akun. Perubahan memerlukan izin Kelola institusi; tanpa izin tersebut data hanya dapat dibaca.',
        1 => 'Gunakan nama resmi. Jenis institusi Sekolah atau Kampus tidak dapat diganti setelah dibuat. Kode negara memakai dua huruf, misalnya ID; kode kampus bukan NPSN sekolah.',
        2 => 'Slug adalah identitas URL unik dengan huruf kecil dan tanda hubung. Jangan mengganti slug institusi lama sembarangan; pada akses Admin Sekolah kolom ini baca-saja.',
        3 => 'Latitude adalah lintang (-90 sampai 90), longitude adalah bujur (-180 sampai 180). Pin harus menunjukkan lokasi institusi yang tepat, bukan rumah pengguna atau perkiraan pusat kota.',
        4 => 'Catchment adalah Polygon GeoJSON wilayah pantauan yang mencakup pin. Baca bagian Pin lokasi dan wilayah pantauan untuk langkah menggambar, mengunggah, dan memperbaiki kesalahan.',
        5 => 'Zona waktu menggunakan nama IANA: Asia/Jakarta (WIB), Asia/Makassar (WITA), Asia/Jayapura (WIT), atau zona setempat untuk kampus luar negeri. Kode BMKG bersifat opsional; gunakan kode resmi provider jika tersedia, jangan menyalin kode demo.',
      ),
      'note' => 'Kontak bantuan publik berbeda dari email kontak privat pengelola. Mengganti data institusi tidak membuat akun baru dan tidak otomatis memindahkan wilayah pantauan.',
    ),
    3 => 
    array (
      'id' => 'geojson',
      'title' => 'Pin lokasi dan wilayah pantauan (Catchment GeoJSON)',
      'gate' => 'schools.view',
      'image' => NULL,
      'steps' => 
      array (
        0 => 'Isi latitude/longitude pin lebih dulu, atau letakkan pin di peta. Pin dan wilayah pantauan adalah dua data berbeda dan keduanya diperlukan untuk institusi.',
        1 => 'Gambar sedikitnya tiga titik berbeda yang tidak segaris mengelilingi pin. Hindari garis yang saling silang, titik berulang, atau batas yang tidak mencakup pin.',
        2 => 'Untuk unggahan pilih file .geojson atau .json maksimal 50 KB. Untuk tempel, gunakan kolom Catchment GeoJSON (Polygon). Gunakan objek Polygon langsung; Feature, FeatureCollection, Point, LineString, dan MultiPolygon bukan format kolom ini. Jika hasil ekspor berupa Feature, ambil objek geometry yang bertipe Polygon.',
        3 => 'Polygon hanya boleh memiliki satu ring tanpa lubang. Ring berisi 4–500 pasangan koordinat termasuk penutup; pasangan pertama harus diulang sebagai pasangan terakhir. GeoJSON memakai [longitude, latitude], bukan urutan kolom formulir pin.',
        4 => 'Luas maksimal 25 km². Jika validasi gagal, cek JSON, penutupan ring, urutan koordinat, pin di dalam wilayah, garis bersilang, dan luasnya. Perkecil batas sesuai wilayah yang benar; jangan memindahkan pin ke lokasi palsu untuk lolos validasi.',
        5 => 'Gunakan keyboard untuk mengisi koordinat dan menempel GeoJSON jika tidak dapat menggambar dengan pointer. Sesudah pratinjau tampil, server tetap memeriksa geometri ketika disimpan.',
      ),
      'note' => 'Wilayah pantauan (catchment) menetapkan area pantauan institusi, bukan rute perjalanan dan bukan bukti seluruh wilayah aman.',
      'examples' => 
      array (
        0 => 
        array (
          'title' => 'Contoh Polygon untuk latihan, bukan wilayah institusi nyata',
          'caption' => 'Contoh ini mencakup pin latitude -7.000 dan longitude 110.001. Ganti seluruh koordinat dengan batas resmi institusi sebelum digunakan. Urutan setiap pasangan GeoJSON adalah [longitude, latitude].',
          'code' => '{
  "type": "Polygon",
  "coordinates": [
    [
      [
        110.0,
        -7.001
      ],
      [
        110.002,
        -7.001
      ],
      [
        110.002,
        -6.999
      ],
      [
        110.0,
        -6.999
      ],
      [
        110.0,
        -7.001
      ]
    ]
  ]
}',
        ),
      ),
    ),
    4 => 
    array (
      'id' => 'katalog-kampus',
      'title' => 'Katalog kampus: cari, aktifkan, atau hubungkan',
      'gate' => 'schools.provision',
      'image' => NULL,
      'steps' => 
      array (
        0 => 'Gunakan Cari daftar kampus Indonesia / dunia pada halaman publik. Cari nama/kode dan pilih negara yang tepat. Direktori bukan daftar lengkap seluruh kampus di dunia; data ROR dapat menunjukkan pusat kota, bukan pin gedung kampus.',
        1 => 'Jika kampus belum terdaftar di RuteAman, lanjutkan aktivasi melalui formulir Buat sekolah / kampus dan akun. Identitas katalog dipertahankan; tetap tentukan pin yang benar, wilayah pantauan, dan akun pengelola.',
        2 => 'Jika kampus sudah terdaftar, gunakan Hubungkan entri kampus. Pilih institusi kampus yang aktif, cocokkan negara serta identitas/cabang. Jika kedua sumber memiliki kode resmi, kode harus sesuai.',
        3 => 'Isi alasan hubungan (8–500 karakter) dan centang konfirmasi kecocokan identitas sebelum menyimpan. Tinjau ulang jika ada beberapa cabang dengan nama mirip; jangan memilih hanya berdasarkan kemiripan nama.',
        4 => 'Hubungan katalog tidak membuat akun, memindahkan pin/wilayah pantauan, atau menyalakan fitur. Entri yang sudah terhubung tidak dapat ditimpa untuk mengarah ke institusi lain dari alur ini.',
      ),
      'note' => 'Aktivasi dan hubungan katalog membutuhkan izin pembuatan institusi serta cakupan yang sesuai. Jangan menganggap entri direktori otomatis berarti kampus sudah aktif di RuteAman.',
    ),
    5 => 
    array (
      'id' => 'status-institusi',
      'title' => 'Jeda, pulihkan, dan arsipkan institusi',
      'gate' => 'schools.view',
      'image' => NULL,
      'steps' => 
      array (
        0 => 'Buka rincian institusi yang benar. Tindakan perubahan status membutuhkan izin Kelola institusi dan konfirmasi kata sandi bila diminta.',
        1 => 'Jeda dipakai untuk menghentikan kegiatan pemantauan untuk sementara. Pelaporan berhenti dan akses mengikuti pembatasan baca-saja yang berlaku; data tidak dihapus.',
        2 => 'Pulihkan mengembalikan status aktif. Periksa kembali pin/wilayah pantauan dan saklar fitur, karena status aktif tidak otomatis menyalakan semua kemampuan.',
        3 => 'Arsipkan menyembunyikan institusi dari pilihan publik. Ini bukan penghapusan database dan bukan penghapusan laporan atau audit.',
      ),
      'note' => 'Perubahan status institusi berbeda dari menangguhkan akun, menyembunyikan laporan, atau mematikan sebuah fitur.',
    ),
    6 => 
    array (
      'id' => 'kontak',
      'title' => 'Mengisi kontak pada halaman Bantuan',
      'gate' => 'schools.view',
      'image' => 'contacts.webp',
      'steps' => 
      array (
        0 => 'Untuk kontak per institusi: buka Kontak Institusi, cari institusi yang benar, isi Kontak bantuan institusi.',
        1 => 'Gunakan kanal layanan resmi: tautan HTTPS (termasuk WhatsApp resmi), mailto: diikuti email resmi, atau nomor telepon sebagai teks. WhatsApp memakai kode negara tanpa tanda +; misalnya nomor lokal berawalan 0 diganti kode negara yang benar. Jangan memakai nomor contoh sebagai kontak sungguhan.',
        2 => 'Centang persetujuan publikasi, lalu tekan Simpan kontak institusi dan konfirmasikan kata sandi jika diminta. Pengaturan institusi lainnya tidak ditimpa.',
        3 => 'Untuk bantuan umum/platform, Administrator Utama membuka Tampilan & Kontak → Kontak bantuan platform. Dipakai untuk pengunjung tanpa institusi atau sebagai cadangan bila kontak institusi belum diisi.',
        4 => 'Halaman Bantuan pengguna memilih kontak institusi berdasarkan institusi akun, bukan institusi terakhir yang dipilih di peta. Admin Utama tanpa institusi menggunakan kontak platform.',
        5 => 'Kosongkan kolom untuk menghapus pengaturan baru dan kembali ke kontak bawaan sebelumnya/platform bila tersedia.',
      ),
      'note' => 'Yang disimpan di sini terlihat pada halaman Bantuan. Jangan mengisi email privat, nomor personal tanpa izin, kata sandi, atau alias login yang tidak menerima pesan.',
    ),
    7 => 
    array (
      'id' => 'akun-admin',
      'title' => 'Membuat akun pada institusi yang sudah ada',
      'gate' => 'users.view',
      'image' => NULL,
      'steps' => 
      array (
        0 => 'Buka Semua Pengguna → Buat akun baru. Pilih peran yang tersedia menurut izin akun dan institusi tujuan. Admin Utama mengelola seluruh peran; Admin Sekolah hanya mengelola pelajar/mahasiswa dan verifikator dalam cakupannya, bukan admin lain.',
        1 => 'Isi nama tampilan, alias/email login dan email kontak asli. Alias login adalah identitas masuk, tidak otomatis menjadi kotak surat. Email kontak asli digunakan untuk undangan dan pemberitahuan.',
        2 => 'Pilih institusi terdaftar. Pencarian sekolah nasional memakai nama/NPSN minimal tiga karakter. Jika Admin Utama mendaftarkan institusi baru dari pencarian ini, institusi dimulai dalam status dijeda dan pelaporan belum aktif: periksa pin, wilayah pantauan, dan fitur terlebih dahulu. Langkah ini tidak otomatis membuat akun pengelola.',
        3 => 'Pada metode kata sandi awal, pilih Tetapkan kata sandi sekarang atau Kirim tautan undangan. Pilihan ini ada pada formulir akun terpisah; formulir Buat sekolah / kampus dan akun menggunakan alur undangan.',
        4 => 'Kata sandi awal minimal 12 karakter, memadukan huruf besar/kecil, angka, dan simbol; jangan memasukkan nama atau alias login. Ulangi sandi dengan benar. Admin Utama dapat memakai Buat otomatis dan Salin; simpan secara privat, bukan di laporan atau kontak bantuan.',
        5 => 'Biarkan kewajiban mengganti kata sandi saat login pertama aktif. Jika dimatikan, pembuat akun masih mengetahui sandi awal. Untuk undangan, tautan hanya tampil sekali dan penerima menentukan sandinya sendiri; masa berlaku mengikuti konfigurasi hosting.',
        6 => 'Jika email belum terkirim, periksa email kontak dan SMTP. Bagikan tautan hanya kepada penerima melalui kanal privat. Setelah kedaluwarsa gunakan penerbitan undangan/reset baru sesuai izin, bukan membagikan tautan lama.',
        7 => 'Pada Tingkatan dan Izin, bedakan peran, izin tindakan, cakupan institusi, dan fitur. Izin anak dapat memerlukan izin induk. Dua tingkat admin aktif adalah Admin Utama (Owner) dan Admin Sekolah; tingkat lama berlabel nonaktif perlu ditinjau, bukan dimigrasikan otomatis.',
      ),
      'note' => 'Jangan menonaktifkan Admin Utama terakhir atau membagikan kredensial. Menu dan aksi mengikuti batas maksimal tingkat akun.',
    ),
    8 => 
    array (
      'id' => 'hapus-akun',
      'title' => 'Menonaktifkan akun, mencabut sesi, dan menghapus akun',
      'gate' => 'owner',
      'image' => NULL,
      'steps' => 
      array (
        0 => 'Di Semua Pengguna, buka akun target lalu cocokkan nama, alias, peran, dan institusinya. Jangan memilih akun hanya dari nama panggilan yang mirip.',
        1 => 'Gunakan tangguhkan/nonaktifkan untuk menghentikan akses sementara, aktifkan untuk memulihkannya, atau cabut sesi untuk mengeluarkan perangkat. Tindakan ini berbeda dari menghapus akun.',
        2 => 'Untuk Hapus akun, baca identitas pada dialog. Pilih Batalkan bila ragu; Lanjutkan hanya bila akun target sudah benar dan penghapusan memang diperlukan.',
        3 => 'Penghapusan menganonimkan identitas dan mencabut sesi. Laporan serta jejak audit tetap dipertahankan; bukan menghapus semua kontribusi pengguna.',
        4 => 'Akun sendiri tidak dapat dihapus melalui tindakan pengelolaan ini. Perlindungan Admin Utama terakhir dan pembatasan akses tetap berlaku.',
      ),
      'note' => 'Hapus akun bukan cara menghapus laporan dari peta. Gunakan moderasi publikasi untuk mengatur keterlihatan laporan.',
    ),
    9 => 
    array (
      'id' => 'fitur-institusi',
      'title' => 'Fitur umum dan pengecualian per institusi',
      'gate' => 'schools.features',
      'image' => NULL,
      'steps' => 
      array (
        0 => 'Buka Fitur Umum & Sekolah. Bedakan Pelaporan, Konfirmasi, Verifikasi, Eskalasi, Feed GeoJSON, laporan publik lintas institusi, layer bersama, Tren, dan Unggah avatar. Membaca peta tidak dapat dimatikan dari saklar fitur.',
        1 => 'Wajib verifikasi laporan tamu dan Wajib verifikasi laporan pengguna login adalah dua pilihan terpisah. Perubahannya berlaku bagi laporan baru; antrean lama tidak otomatis dipublikasikan.',
        2 => 'Admin Utama menggunakan Simpan & terapkan secara umum untuk menerapkan seluruh pilihan ke institusi terdaftar sekaligus menjadi default institusi baru. Perubahan ini menimpa pengecualian per institusi; tinjau dampaknya sebelum menyimpan. Pilihan awal di layar belum diterapkan sampai disimpan.',
        3 => 'Tindakan penerapan satu fitur ke semua institusi terdaftar, termasuk arsip, tidak mengubah default institusi baru. Penerapan fitur juga tidak mengubah status aktif/dijeda/arsip.',
        4 => 'Pengaturan khusus sebuah institusi tidak mengubah default umum. Pengecualian ini dapat tertimpa ketika pengaturan umum diterapkan lagi.',
        5 => 'Tinjau juga izin tindakan dan cakupan akun. Menyalakan fitur Verifikasi tidak otomatis memberi setiap pengguna izin memverifikasi; fitur lintas institusi hanya menampilkan data publik sesuai kebijakan.',
      ),
      'note' => 'Penerapan massal dapat memengaruhi banyak institusi. Periksa target dan simpan hanya setelah dampaknya dipahami.',
    ),
    10 => 
    array (
      'id' => 'verifikasi',
      'title' => 'Verifikasi dan tindak lanjut laporan',
      'gate' => 'verification.decide',
      'image' => NULL,
      'steps' => 
      array (
        0 => 'Buka Antrean Verifikasi untuk sekolah dalam cakupan. Baca lokasi, umur data, bukti foto, dan riwayat konfirmasi.',
        1 => 'Pilih keputusan sesuai bukti: verifikasi, minta bukti tambahan, atau tolak sesuai opsi yang tersedia. Tulis alasan yang dapat dipahami tanpa data pribadi.',
        2 => 'Jangan memperlakukan foto ilustrasi atau laporan demo sebagai bukti lokasi nyata. Konfirmasi independen berbeda dari konfirmasi pelapor sendiri.',
        3 => 'Jika berwenang mengelola kondisi, pembukaan kembali kondisi selesai/kedaluwarsa membuatnya membutuhkan bukti baru; bukan langsung terverifikasi.',
      ),
      'note' => 'Tidak ada laporan bukan jaminan kondisi aman. Kondisi lama perlu dilihat bersama umur data dan konfirmasi terbaru.',
    ),
    11 => 
    array (
      'id' => 'publikasi',
      'title' => 'Moderasi publikasi: tangguhkan, pulihkan, hapus dari publik',
      'gate' => 'hazards.view',
      'image' => NULL,
      'steps' => 
      array (
        0 => 'Buka daftar kondisi dan pilih institusi dalam cakupan. Baca bukti, umur data, status verifikasi, serta status publikasinya. Kemunculan di peta tidak berarti sudah terverifikasi.',
        1 => 'Tangguhkan menyembunyikan kondisi dari publik untuk sementara. Pulihkan publikasi mengembalikan keterlihatan jika diperbolehkan, bukan memberikan status terverifikasi.',
        2 => 'Hapus dari publik adalah penghentian publikasi yang tidak dapat dipulihkan lewat menu ini. Riwayat laporan dan audit tetap disimpan; tindakan ini bukan menghapus baris database.',
        3 => 'Isi alasan tindakan tanpa data pribadi. Perubahan publikasi berdampak pada kondisi dan laporan yang terhubung; tindakan hanya tersedia dengan izin serta cakupan yang sesuai.',
        4 => 'Jika membuka kembali kondisi selesai/kedaluwarsa, diperlukan bukti baru. Jangan memakai pembukaan kembali atau pemulihan publikasi untuk melewati proses verifikasi.',
      ),
      'note' => 'Status publikasi dan status verifikasi adalah dua hal berbeda. Laporan yang langsung publik dapat tetap belum terverifikasi.',
    ),
    12 => 
    array (
      'id' => 'peta-ringkasan',
      'title' => 'Peta otomatis, ringkasan area, dan perbandingan rute',
      'gate' => NULL,
      'image' => NULL,
      'steps' => 
      array (
        0 => 'Di beranda buka Ringkasan area sekolah / kampus, pilih institusi lalu Tampilkan area. Panel dapat dilipat melalui judulnya. Angka dan label kategori merangkum kondisi aktif dalam wilayah institusi; bukan seluruh riwayat yang ada di peta.',
        1 => 'Kondisi publik dan riwayat dimuat otomatis sesuai pengaturan lapisan dan cakupan, tanpa harus menekan Tampilkan kondisi. Daftar tidak hilang hanya karena penanda berada di luar layar setelah zoom atau geser.',
        2 => 'Peta membingkai lokasi kondisi saat pertama dibuka atau saat institusi diganti. Setelah Anda menggeser/zoom, pembaruan data tidak menarik posisi peta kembali. Gunakan Tampilkan kondisi atau tombol bidik untuk melihat lokasi kembali jika diperlukan.',
        3 => 'Pin tetap pada koordinat laporan aslinya. Laporan publik lintas institusi muncul hanya jika fitur yang relevan aktif; bukan berarti semua laporan privat ikut dibuka.',
        4 => 'Untuk membandingkan rute, pilih sekolah/kampus dengan rute tersimpan. Analisis rute lanjutan adalah pilihan tambahan bila penyedia layanan tersedia. Lokasi awal dapat dipilih di peta; GPS tidak harus digunakan untuk membaca rute tersimpan.',
        5 => 'Beban kondisi hanya menghitung kondisi aktif dalam koridor rute, bukan semua penanda atau riwayat. Perhatikan jarak, umur data, dan status; sistem tidak memberi rekomendasi rute paling aman.',
      ),
      'note' => 'Angka 0 berarti tidak ada kondisi aktif yang tercatat pada cakupan itu, bukan jaminan aman. Waktu pembaruan data bukan jaminan kondisi lapangan saat ini.',
    ),
    13 => 
    array (
      'id' => 'feed-metrik',
      'title' => 'Feed GeoJSON untuk aplikasi lain',
      'gate' => 'feed.manage',
      'image' => NULL,
      'steps' => 
      array (
        0 => 'Buka rincian institusi → Feed GeoJSON. Fitur geojson_feed dan izin feed.manage harus aktif; pengaturan mengikuti institusi serta cakupan akun.',
        1 => 'Feed berisi kondisi publik, bukan identitas pelapor. Gunakan alamat feed yang diberikan aplikasi dan pilih institusi yang benar pada integrasi.',
        2 => 'Jika mengaktifkan status eskalasi publik, feed hanya menyertakan status/waktunya, bukan catatan tindak lanjut privat.',
        3 => 'Bedakan feed kondisi ini dari Polygon wilayah pantauan pada formulir institusi dan LineString rute. Jangan menempel seluruh feed ke kolom catchment.',
      ),
      'note' => 'Periksa kebijakan publikasi sebelum membagikan feed. Menyalakan integrasi tidak membuka laporan privat atau menggantikan hak akses admin.',
    ),
    14 => 
    array (
      'id' => 'metrik',
      'title' => 'Statistik 7 hari terakhir dan ekspor institusi',
      'gate' => 'metrics.view',
      'image' => NULL,
      'steps' => 
      array (
        0 => 'Buka rincian institusi → Metrik 7 Hari. Membaca tren memerlukan fitur Tren dan izin metrics.view.',
        1 => 'Angka harian menghitung kondisi yang pertama dilaporkan pada tanggal tersebut menurut zona waktu institusi. Ini bukan jumlah semua kejadian jalan sebenarnya, bukan seluruh konfirmasi, dan bukan skor keamanan.',
        2 => 'Jika memiliki exports.run, gunakan ekspor CSV pada institusi yang benar. Ekspor audit berbeda: pilih filter yang sesuai, rentang maksimal 31 hari dan batas 10.000 entri.',
        3 => 'Simpan hasil ekspor hanya pada tempat yang diizinkan. Jangan membagikan data admin atau audit sebagai feed publik.',
      ),
      'note' => 'Izin membaca metrik tidak otomatis memberi izin ekspor. Periksa cakupan serta zona waktu saat membandingkan angka.',
    ),
    15 => 
    array (
      'id' => 'foto-masuk',
      'title' => 'Mengganti foto halaman masuk',
      'gate' => 'owner',
      'image' => 'presentation.webp',
      'steps' => 
      array (
        0 => 'Buka Tampilan & Kontak → Foto halaman masuk. Pratinjau menunjukkan foto yang sedang dipakai.',
        1 => 'Pilih foto baru JPEG/PNG/WebP maksimal 5 MB dan 12 juta piksel. Letakkan objek penting di tengah karena area tampilan memakai crop; pastikan berhak menggunakan dan menampilkan foto tersebut.',
        2 => 'Isi Deskripsi gambar, misalnya ilustrasi perjalanan ke sekolah. Deskripsi untuk aksesibilitas, bukan nama file atau informasi privat.',
        3 => 'Centang persetujuan publikasi, tekan Ganti foto halaman masuk, lalu konfirmasikan kata sandi jika diminta.',
        4 => 'Buka halaman masuk pada tab baru untuk memeriksa hasil. Foto berada di sisi kiri pada desktop; di HP disembunyikan.',
        5 => 'Untuk kembali ke awal, tekan Kembali ke foto bawaan. Unggahan sebelumnya dihapus setelah berhasil diganti/reset.',
      ),
      'note' => 'Penggantian ini manual dan hanya untuk halaman masuk. Tidak mengubah foto beranda, akun, atau kata sandi.',
    ),
    16 => 
    array (
      'id' => 'galeri',
      'title' => 'Mengisi tiga koleksi Foto Beranda',
      'gate' => 'owner',
      'image' => 'gallery.webp',
      'steps' => 
      array (
        0 => 'Buka Foto Beranda. Pilih kelompok: Bandingkan alternatif rute, Lapor dalam empat langkah, atau Konfirmasi dan tindak lanjut. Koleksi unggahan setiap kelompok terpisah.',
        1 => 'Pilih orientasi; landscape dapat memakai unggahan admin/laporan publik terverifikasi/gabungan. Portrait hanya unggahan admin.',
        2 => 'Kategori kondisi kosong berarti semua kategori. Sekolah hanya memfilter foto laporan, bukan koleksi unggahan admin.',
        3 => 'Pilih Stay untuk foto tetap atau Bergulir otomatis. Isi bilangan interval 1–1000 dan satuan detik, menit, jam, hari, minggu, atau bulan. Bulan mengikuti kalender Asia/Jakarta.',
        4 => 'Simpan pengaturan orientasi sebelum mengunggah. Isi foto, keterangan publik, kategori, urutan, dan persetujuan publikasi. Foto maksimal 5 MB/12 juta piksel; batas 60 unggahan per kelompok.',
        5 => 'Untuk Stay pilih foto tengah. Jika sumber/filter/orientasi berubah, pilih Otomatis terlebih dahulu. Foto dapat diberi urutan, disembunyikan, atau dihapus.',
      ),
      'note' => 'Foto laporan harus publik, terverifikasi, non-demo, masih aktif dan tidak menunggu moderasi. Ilustrasi mengisi kekurangan slot; bukan bukti kondisi terkini.',
    ),
    17 => 
    array (
      'id' => 'jenis-kondisi',
      'title' => 'Menambah kondisi dan logo',
      'gate' => 'owner',
      'image' => 'types.webp',
      'steps' => 
      array (
        0 => 'Buka Jenis Kondisi. Isi nama, misalnya jenis hambatan yang memang akan dipantau.',
        1 => 'Pilih Ikon bawaan lalu klik ikon yang cocok, atau pilih Unggah logo sendiri dan unggah PNG/JPG/WebP maksimal 512 KB dan 1.048.576 piksel. PNG transparan disarankan. SVG/GIF tidak diterima.',
        2 => 'Kode dibuat dari nama; jika sudah dipakai, isi kode unik pada Pengaturan lanjutan. Kode tetap setelah dibuat agar laporan tidak rusak.',
        3 => 'Bobot 1–5 dan masa aktif dasar memengaruhi penilaian dan masa aktif laporan. Ubah sensitivitas hujan/khusus malam hanya bila sesuai kategori. Perubahan juga memengaruhi laporan lama.',
        4 => 'Tekan Tambahkan kondisi. Kategori tersedia di formulir laporan, filter dan peta. Muat ulang halaman yang sudah terbuka untuk memuat daftar/legenda terbaru.',
        5 => 'Untuk edit, buka kategori yang ada. Unggahan kosong mempertahankan logo; pilih Ikon bawaan untuk kembali ke ikon. Laporan lama tidak dihapus.',
      ),
      'note' => 'Logo sederhana lebih terbaca pada penanda kecil. Jangan memakai data pribadi atau logo tanpa hak publikasi.',
    ),
    18 => 
    array (
      'id' => 'profil',
      'title' => 'Mengatur profil admin',
      'gate' => NULL,
      'image' => 'profile.webp',
      'steps' => 
      array (
        0 => 'Klik Akun saya pada menu samping. Profil tetap berada di konsol, berbeda dari tata letak akun pengguna biasa.',
        1 => 'Pindah melalui tab Profil, Pengaturan, Notifikasi, Keamanan, dan Privasi; menu samping tetap tersedia.',
        2 => 'Antarmuka situs hanya menggunakan Bahasa Indonesia. Pilihan bahasa telah dihapus dari menu dan Pengaturan. Nama institusi dan isi laporan tetap sesuai data aslinya.',
        3 => 'Gunakan avatar bawaan atau unggah jika fitur institusi serta kebijakan persetujuan mengizinkan. Admin Utama tanpa institusi mungkin belum dapat mengunggah; itu pembatasan yang berlaku, bukan error tampilan.',
        4 => 'Nama tampilan berbeda dari alias login. Ubah nama panggilan bila diperlukan; label peran menampilkan tingkat admin yang sebenarnya.',
        5 => 'Email kontak akun bersifat privat dan tidak sama dengan Kontak bantuan platform/institusi. Perubahan email kontak memakai verifikasi dan kata sandi saat ini.',
        6 => 'Gunakan Keamanan untuk mengganti kata sandi atau mengeluarkan perangkat lain. Peran/izin/cakupan tidak dapat dinaikkan melalui profil sendiri.',
      ),
      'note' => 'Menyimpan perubahan memakai layanan akun yang sama; menu samping hanya mengubah cara navigasi, bukan memberi akses tambahan.',
    ),
    19 => 
    array (
      'id' => 'audit',
      'title' => 'Memeriksa log audit dan menangani kendala',
      'gate' => NULL,
      'image' => NULL,
      'steps' => 
      array (
        0 => 'Jika memiliki audit.view, filter waktu/pelaku/aksi/hasil/institusi, lalu klik rincian untuk melihat rincian pelaku, target, perubahan, alamat IP/perangkat dan ID permintaan. Entri lama tidak diisi dengan tebakan. Ekspor perlu exports.run, maksimal 31 hari dan 10.000 entri. Audit hanya baca; retensi harus ditetapkan pengelola.',
        1 => 'Jika formulir ditolak, baca pesan validasi; pastikan izin, cakupan sekolah, status aktif, dan fitur yang diperlukan.',
        2 => 'Jika unggahan gagal, periksa tipe/ukuran/dimensi serta dukungan PHP GD/WebP dan izin tulis storage pada hosting. Jangan mengunggah file SVG/script sebagai gambar.',
        3 => 'Jika email undangan/verifikasi belum masuk, periksa email kontak yang benar dan SMTP. Jangan menganggap alias login adalah alamat penerima email.',
        4 => 'Jika perubahan belum terlihat, muat ulang halaman. Setelah pemasangan patch, operator hosting mengikuti petunjuk paket untuk membersihkan cache. Migration hanya dijalankan bila paket memang menyertakan perubahan database; pembaruan panduan ini tidak memerlukan migration.',
      ),
      'note' => 'Jangan menjalankan db:seed, migrate:fresh, atau mengganti APP_KEY pada situs yang berisi data pengguna hanya untuk memperbaiki tampilan.',
    ),
    array(
      'id' => 'email-notifikasi',
      'title' => 'Email & Notifikasi: memantau pengiriman dan konfigurasi SMTP',
      'gate' => 'email.monitor',
      'image' => NULL,
      'steps' => array(
        'Buka menu Email & Notifikasi. Halaman terbagi lima tab: Ringkasan (statistik antrean, penerima aktif, jadwal aktif, pengiriman berikutnya), Penerima Notifikasi, Riwayat Pengiriman, Kirim Informasi, dan Konfigurasi Email. Tab yang tampil mengikuti izin akun.',
        'Semua angka berasal dari catatan pengiriman aktual. Alamat penerima selalu tersamar; isi email, kata sandi, dan tautan bertanda tangan tidak pernah tersimpan di catatan.',
        'Di tab Riwayat, saring berdasarkan status, kategori, tanggal, atau kata pada penerima/subjek. Klik Detail untuk melihat waktu antre, terkirim, dan gagal, jumlah percobaan, pesan kesalahan yang sudah dibersihkan, serta detail teknis pengiriman.',
        'Email peristiwa yang gagal dapat dikirim ulang dari tab Riwayat. Pengiriman yang masih Dalam antrean dapat dibatalkan bila akun memiliki izin Kelola pengiriman email (email.manage); pembatalan tetap berlaku walau worker baru saja mengambilnya.',
        'Ekspor CSV riwayat mengikuti saringan yang sedang aktif, maksimal 5.000 baris, dan memerlukan izin email.manage.',
        'Di tab Konfigurasi, periksa mailer, host, port, enkripsi, dan pengirim yang terbaca dari pengaturan server. Kata sandi SMTP tidak ditampilkan dan tidak dapat diubah dari halaman ini.',
        'Gunakan tombol Uji Koneksi SMTP (izin email.manage) untuk memeriksa koneksi server dan kecocokan sertifikat tanpa mengirim email. Hasil uji terakhir tersimpan dan tampil di tab yang sama.',
        'Jika riwayat menunjukkan error "Peer certificate subjectAltName did not match", MAIL_HOST tidak cocok dengan nama pada sertifikat server email. Gunakan hostname server email yang benar, umumnya hostname hosting, bukan domain yang melewati proxy. Jangan pernah mematikan verifikasi sertifikat.',
      ),
      'note' => 'Email keamanan akun (verifikasi dan undangan) sengaja dikirim langsung, bukan lewat antrean, karena tautan sekali pakainya tidak boleh tersimpan di data antrean. Email peristiwa laporan dan ringkasan berjalan lewat antrean dengan percobaan ulang otomatis sampai 3 kali.',
    ),
    array(
      'id' => 'penerima-notifikasi',
      'title' => 'Penerima Notifikasi: alamat di luar akun, verifikasi, dan jadwal ringkasan',
      'gate' => 'email.recipients',
      'image' => NULL,
      'steps' => array(
        'Penerima notifikasi adalah alamat email di luar akun pengguna, misalnya kepala sekolah, yang dikelola dari tab Penerima Notifikasi. Penerima tidak dapat masuk ke konsol; ia hanya menerima email sesuai langganannya.',
        'Klik Tambah penerima notifikasi, lalu isi nama, alamat email, dan keterangan atau jabatan. Satu alamat hanya boleh terdaftar satu kali.',
        'Pilih cakupan penerima. Cakupan Semua institusi (platform) hanya untuk Admin Utama dan Admin Platform. Admin Sekolah hanya membuat penerima untuk institusinya; isi NPSN atau nama institusi persis seperti di data institusi.',
        'Setelah disimpan, sistem mengirim email verifikasi ke alamat tersebut. Penerima wajib membuka tautannya, berlaku 72 jam. Selama belum terverifikasi, alamat tidak menerima notifikasi maupun ringkasan apa pun selain email verifikasi dan email uji dari administrator.',
        'Mengubah alamat email membatalkan verifikasi lama dan mengirim verifikasi baru. Gunakan tombol Kirim ulang verifikasi bila tautan sudah kedaluwarsa.',
        'Pilih mode pengiriman: Langsung (email seketika saat peristiwa terjadi), Ringkasan berkala, atau Kombinasi. Centang hanya kategori informasi yang perlu diterima penerima ini. Kategori khusus platform, yaitu aktivitas akun dan kesehatan sistem email, otomatis diabaikan untuk penerima bercakupan institusi.',
        'Untuk ringkasan, atur frekuensi: setiap N hari dihitung dari jadwal terakhir; mingguan pada hari dan jam tertentu; atau bulanan pada tanggal tertentu (dijepit ke hari terakhir bila bulannya lebih pendek), hari pertama, atau hari terakhir bulan. Jam mengikuti zona waktu penerima.',
        'Jam tenang bersifat opsional: ringkasan dan notifikasi komentar ditahan sampai jam tenang berakhir, sedangkan laporan baru dan perubahan status tetap dikirim langsung. Opsi ringkasan kosong menentukan apakah ringkasan tetap dikirim saat tidak ada aktivitas baru.',
        'Tombol Email uji pada baris penerima mengirim email uji khusus ke alamat itu dan hasilnya tercatat di Riwayat. Nonaktifkan penerima untuk menghentikan semua kiriman berikutnya; email yang sudah mengantre untuk penerima nonaktif dibatalkan otomatis oleh sistem.',
      ),
      'note' => 'Scheduler server memeriksa ringkasan yang jatuh tempo tiap 15 menit. Setiap periode hanya dikirim satu kali per penerima walaupun scheduler berjalan berulang. Jadwal berikutnya dan pengiriman terakhir selalu tampil pada daftar penerima dan tab Ringkasan.',
    ),
  ),
);
