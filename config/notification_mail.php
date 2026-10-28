<?php

/**
 * Taksonomi kategori notifikasi untuk PENERIMA MANDIRI (alamat email di luar
 * akun pengguna, misalnya kepala sekolah). Preferensi akun internal tetap
 * memakai users.notification_preferences dan tidak berubah.
 *
 * broadcast : jenis informasi kiriman manual admin (tab Kirim Informasi)
 *             yang tidak terikat langganan; target Penerima Notifikasi
 *             berarti SEMUA penerima aktif-terverifikasi.
 * realtime  : kategori peristiwa yang dapat dikirim seketika.
 *             critical=true  -> tidak tertahan jam tenang.
 *             critical=false -> digeser keluar jam tenang.
 * digest    : bagian isi ringkasan berkala.
 *             platform_only=true -> hanya untuk penerima cakupan platform
 *             (school_id null), demi least privilege antar sekolah.
 */
return [
    'realtime' => [
        'report.submitted' => ['label' => 'Laporan baru masuk', 'critical' => true],
        'report.status_changed' => ['label' => 'Perubahan status laporan', 'critical' => true],
        'report.comment_added' => ['label' => 'Komentar baru pada laporan', 'critical' => false],
    ],
    'broadcast' => [
        'report.escalation' => ['label' => 'Eskalasi & tindak lanjut laporan'],
        'report.expired' => ['label' => 'Laporan kedaluwarsa'],
        'school.proposal' => ['label' => 'Usulan institusi baru'],
        'school.registered' => ['label' => 'Institusi baru terdaftar'],
        'site.announcement' => ['label' => 'Pengumuman situs'],
        'site.maintenance' => ['label' => 'Pemeliharaan & gangguan layanan'],
    ],
    'templates' => [
        'admin.broadcast' => ['subject' => '[Judul informasi]', 'body' => "Yth. Bapak/Ibu,\n\n[Tulis isi informasi di sini.]\n\nTerima kasih.\n— Tim RuteAman"],
        'report.submitted' => ['subject' => 'Laporan baru masuk: [jenis kondisi] di [patokan lokasi]', 'body' => "Yth. Bapak/Ibu,\n\nSebuah laporan baru telah masuk ke RuteAman.\n\nJenis kondisi: [jenis kondisi]\nLokasi: [patokan lokasi / nama jalan]\nWaktu dilaporkan: [hari, tanggal, jam]\nPelapor: [jumlah] laporan pada titik ini\n\nSilakan periksa dan tindak lanjuti melalui antrean verifikasi di konsol RuteAman.\n\n— Tim RuteAman"],
        'report.status_changed' => ['subject' => 'Status laporan berubah: [jenis kondisi] kini [status baru]', 'body' => "Yth. Bapak/Ibu,\n\nStatus sebuah laporan di RuteAman telah berubah.\n\nJenis kondisi: [jenis kondisi]\nLokasi: [patokan lokasi]\nStatus sebelumnya: [status lama]\nStatus baru: [status baru]\nDiperbarui oleh: [nama petugas]\n\n— Tim RuteAman"],
        'report.comment_added' => ['subject' => 'Komentar baru pada laporan [jenis kondisi]', 'body' => "Yth. Bapak/Ibu,\n\nAda komentar baru pada laporan [jenis kondisi] di [patokan lokasi]:\n\n\"[isi komentar]\"\n\nBuka halaman kondisi untuk membaca seluruh percakapan dan membalas bila perlu.\n\n— Tim RuteAman"],
        'report.escalation' => ['subject' => 'Eskalasi laporan: [jenis kondisi] di [patokan lokasi]', 'body' => "Yth. Bapak/Ibu,\n\nLaporan berikut DITESKALASI karena [alasan: belum tertangani / mendesak / berulang].\n\nJenis kondisi: [jenis kondisi]\nLokasi: [patokan lokasi]\nDilaporkan sejak: [tanggal]\nJumlah laporan: [jumlah] · Konfirmasi warga: [jumlah]\nDitujukan ke: [instansi / pihak terkait]\n\nMohon tindak lanjutnya. Balasan dan perkembangan dapat dicatat di konsol RuteAman.\n\n— Tim RuteAman"],
        'report.expired' => ['subject' => 'Laporan kedaluwarsa: [jenis kondisi] di [patokan lokasi]', 'body' => "Yth. Bapak/Ibu,\n\nLaporan [jenis kondisi] di [patokan lokasi] telah melewati masa berlakunya dan ditandai kedaluwarsa pada [tanggal].\n\nBila kondisi di lapangan ternyata masih ada, silakan buat laporan baru agar datanya segar dan dapat diverifikasi ulang.\n\n— Tim RuteAman"],
        'digest.reports' => ['subject' => 'Ringkasan laporan RuteAman — [periode]', 'body' => "Yth. Bapak/Ibu,\n\nRingkasan laporan periode [periode]:\n\nLaporan baru: [jumlah]\nSedang diproses: [jumlah]\nSelesai: [jumlah]\nPerlu tindak lanjut: [jumlah]\n\nRincian lengkap tersedia di konsol RuteAman.\n\n— Tim RuteAman"],
        'digest.schools' => ['subject' => 'Perubahan data institusi — [periode]', 'body' => "Yth. Bapak/Ibu,\n\nPerubahan data institusi pada periode [periode]:\n\nInstitusi baru: [jumlah / nama]\nData diperbarui: [jumlah / nama]\nInstitusi diarsipkan: [jumlah / nama]\n\nPeriksa kembali data yang berubah di halaman daftar institusi bila diperlukan.\n\n— Tim RuteAman"],
        'digest.accounts' => ['subject' => 'Aktivitas akun & keamanan — [periode]', 'body' => "Yth. Bapak/Ibu,\n\nRingkasan aktivitas akun dan keamanan periode [periode]:\n\nAkun baru: [jumlah]\nAkun dinonaktifkan: [jumlah]\nPerubahan izin: [jumlah]\nPercobaan masuk gagal: [jumlah]\n\nAktivitas mencurigakan dapat ditinjau di Log Audit konsol RuteAman.\n\n— Tim RuteAman"],
        'digest.system' => ['subject' => 'Kesehatan sistem email — [periode]', 'body' => "Yth. Bapak/Ibu,\n\nRingkasan kesehatan pengiriman email periode [periode]:\n\nTerkirim: [jumlah]\nGagal: [jumlah] — penyebab terbanyak: [penyebab]\nMasih antre: [jumlah]\n\nRincian per email tersedia di tab Riwayat Pengiriman.\n\n— Tim RuteAman"],
        'school.proposal' => ['subject' => 'Usulan institusi baru: [nama institusi]', 'body' => "Yth. Bapak/Ibu,\n\nSebuah usulan institusi baru telah diajukan melalui RuteAman.\n\nNama institusi: [nama institusi]\nNomor tiket: [kode tiket]\nPengusul: [nama / peran]\nAlasan / keterangan: [keterangan]\n\nSilakan tinjau usulan ini di konsol RuteAman untuk disetujui atau ditolak.\n\n— Tim RuteAman"],
        'school.registered' => ['subject' => 'Institusi baru terdaftar: [nama institusi]', 'body' => "Yth. Bapak/Ibu,\n\nInstitusi berikut kini terdaftar dan terpantau di RuteAman:\n\nNama: [nama institusi]\nNPSN / kode resmi: [npsn]\nWilayah: [kecamatan, kabupaten]\nMulai terpantau: [tanggal]\n\nSelamat datang. Laporan kondisi di sekitar institusi ini kini dapat masuk dan diverifikasi seperti institusi lainnya.\n\n— Tim RuteAman"],
        'site.announcement' => ['subject' => 'Pengumuman: [judul pengumuman]', 'body' => "Yth. Bapak/Ibu pengguna RuteAman,\n\n[Isi pengumuman.]\n\nBerlaku mulai: [tanggal / jam, bila relevan]\n\nTerima kasih atas perhatian dan kerja samanya.\n\n— Tim RuteAman"],
        'site.maintenance' => ['subject' => 'Pemberitahuan pemeliharaan RuteAman — [tanggal]', 'body' => "Yth. Bapak/Ibu pengguna RuteAman,\n\nKami akan melakukan pemeliharaan sistem pada:\n\nHari / tanggal: [hari, tanggal]\nPukul: [jam mulai] – [jam selesai] WIB\nDampak: [mis. situs tidak dapat diakses sementara / sebagian fitur berhenti]\n\nLaporan yang sudah masuk TIDAK hilang. Mohon menunda pelaporan selama jendela pemeliharaan bila memungkinkan.\n\nMohon maaf atas ketidaknyamanannya.\n\n— Tim RuteAman"],
    ],
    'digest' => [
        'digest.reports' => ['label' => 'Ringkasan laporan (baru, diproses, selesai, perlu tindak lanjut)', 'platform_only' => false],
        'digest.schools' => ['label' => 'Perubahan data institusi', 'platform_only' => false],
        'digest.accounts' => ['label' => 'Aktivitas akun & keamanan', 'platform_only' => true],
        'digest.system' => ['label' => 'Kesehatan sistem email (kegagalan & antrean)', 'platform_only' => true],
    ],
];
