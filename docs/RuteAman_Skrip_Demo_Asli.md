# RuteAman — Skrip Demo Lengkap (±3 menit 30 detik)

> Disesuaikan dengan daftar MUST terbaru: rute tersimpan, deteksi duplikat, layer hazard bersama (2 sekolah), 2 role (Siswa + Admin Sekolah), feed GeoJSON.
> Tanda **[ISI HASIL]** = ganti dengan data asli dari survei/audit/pilot. Jangan dibacakan kalau masih kosong.

---

## 0. Catatan Penting: Angka Berubah dari Bagian 8.6

Karena ada **deteksi duplikat** (laporan kedua otomatis jadi konfirmasi, bukan laporan baru), skenario demo sekarang memakai **1 laporan + 2 konfirmasi**. Angkanya dihitung ulang:

| Tahap | Perhitungan | Hasil |
|---|---|---|
| Awal (hanya lubang) | `h = 2 × 1.0 × 1 × 1 = 2.0` → `100 × 2.0 / 9` | **22 (Ringan)** |
| Genangan dikonfirmasi komunitas | `c = 0.10 + 0.20 + 0.20 = 0.50` → `h = 3 × 0.50 × 1 × 1.5 = 2.25`; Σh = 4.25 | **47 (Sedang)** |
| Genangan diverifikasi | `c = 0.10 + 0.20 + 0.20 + 0.40 = 0.90` → `h = 3 × 0.90 × 1 × 1.5 = 4.05`; Σh = 6.05 | **67 (Berat)** |

**Tindakan:** perbarui contoh di Bagian 8.6 dan uji unit rumus dengan angka ini (22 → 47 → 67), supaya dokumen, kode, dan demo konsisten.

---

## 1. Persiapan Sebelum Demo (Checklist)

**Akun demo (role switcher / 4 jendela browser atau tab):**

| Tab | Akun | Sekolah | Peran di demo |
|---|---|---|---|
| 1 | Pembaca publik (tanpa login) | — | Membuka peta, membandingkan rute |
| 2 | `siswa_1` | Sekolah A | Melapor genangan |
| 3 | `siswa_2` | Sekolah A | Mengonfirmasi (lewat deteksi duplikat) |
| 4 | `siswa_3` | Sekolah B | Mengonfirmasi dari sekolah lain (layer bersama) |
| 5 | `admin_A` | Sekolah A | Verifikasi + eskalasi + dashboard |

**Data seed (dari audit rute nyata, Bagian 3.3):**
- Sekolah A dan Sekolah B, catchment **tumpang tindih** di area depan pasar.
- 2 rute tersimpan dari Sekolah A: **Rute A (1,8 km)** dan **Rute B (2,1 km, +0,3 km)**.
- Hazard aktif: **Jalan berlubang, Jl. Mawar** (Rute A), terverifikasi, 2 laporan, 2 konfirmasi, ada foto, dikonfirmasi 3 hari lalu.
- **Belum ada** genangan di sistem (akan dilaporkan live).
- Rute B: tidak ada hazard aktif.
- Cuaca: mode hujan aktif (cache/manual). Jika BMKG belum diintegrasikan, gunakan penanda "hujan" bawaan seed agar `w = 1.5` berlaku untuk genangan.

**Persiapan teknis:**
- [ ] Foto genangan siap di galeri (tanpa wajah/plat nomor).
- [ ] Koordinat laporan siap di titik depan pasar (dalam catchment A dan B).
- [ ] Reset database ke seed tepat sebelum demo.
- [ ] Zoom browser 110–125% agar terbaca di layar proyektor.
- [ ] Mode **jaringan lambat** dites sekali. Tile peta sudah di-cache.
- [ ] Video rekaman cadangan siap di desktop.
- [ ] Satu orang memegang waktu (timer), satu orang menjalankan demo (kalau tim lebih dari satu).

---

## 2. Skrip Utama

### Segmen 1 — Masalah (0:00 – 0:25)

**Layar:** 2–3 foto audit nyata (genangan di depan pasar, lubang di Jl. Mawar) + satu angka survei.

**Narasi:**
> "Setiap hujan, siswa di sekitar sekolah kami melewati jalan yang sama, tanpa tahu kondisinya hari ini. Dari survei **[n]** siswa, **[x]%** pernah terdampak: terlambat, memutar, atau terjebak genangan **[ISI HASIL]**. Informasinya ada, tapi tenggelam di grup WhatsApp. RuteAman mengubahnya jadi papan kondisi rute yang bisa dipercaya dan ditindaklanjuti."

**Catatan:** jika data survei belum ada, ganti dengan foto audit saja dan cukup katakan "kami temukan **[n]** hazard di **[n]** rute saat audit". Jangan mengarang persentase.

---

### Segmen 2 — Peta Publik Tanpa Login (0:25 – 0:50)

**Layar (Tab 1, tanpa login):** buka peta → pilih Sekolah A → tampil Rute A dan Rute B berdampingan.

Tampilan rute:
```text
Rute A — 1,8 km — 1 hazard aktif (Ringan)
  • Jalan berlubang, Jl. Mawar — terverifikasi, 3 hari lalu

Rute B — 2,1 km (+0,3 km) — Belum ada laporan aktif
  Ini bukan jaminan kondisi aman. Data di rute ini masih sedikit.
```

**Narasi:**
> "Peta ini bisa dibuka siapa saja tanpa login, jadi tidak ada data pribadi yang dikumpulkan. Dua rute ke sekolah ditampilkan dengan hazard aktifnya dan **umur datanya**. Perhatikan Rute B: kami tidak menulis 'aman'. Kami menulis 'belum ada laporan', karena itu memang berbeda."

**Catatan presenter:** tunjuk teks "bukan jaminan" dengan kursor. Ini poin kepercayaan dan keselamatan anak.

---

### Segmen 3 — Rincian Hazard Terverifikasi (0:50 – 1:10)

**Layar:** klik hazard lubang → kartu hazard → buka **"Kenapa tingkatnya begini?"** → tampil rincian: jenis, tingkat, jumlah laporan/konfirmasi, foto, verifikasi, umur data, Beban hazard **22**.

**Narasi:**
> "Setiap hazard punya rincian yang bisa dibuka. Ini lubang yang sudah terverifikasi, dengan dua laporan, dua konfirmasi, dan foto. Beban hazard rute ini 22, kategori ringan. Angkanya transparan: tingkat keparahan dikali keyakinan, kesegaran data, dan konteks hujan. Kami tidak mengklaim akurasi prediktif, ini heuristik yang kami kalibrasi lewat pilot."

**Catatan:** jangan berlama-lama di rumus. Cukup tunjukkan bahwa rincian **ada dan bisa dibuka**.

---

### Segmen 4 — Siswa Melapor Genangan (1:10 – 1:35)

**Layar (Tab 2, `siswa_1`):** ketuk **Lapor** → pin lokasi depan pasar → pilih ikon **Genangan** → lampirkan foto → **Kirim**.

Status langsung tampil: **"Menunggu konfirmasi"**.

**Narasi:**
> "Sekarang hujan turun dan ada genangan di depan pasar. Siswa melapor dalam empat langkah: ketuk Lapor, tandai lokasi, pilih ikon, tambah foto. Deskripsi itu opsional, karena kami ingin lebih mudah daripada mengetik di grup chat. Ada juga peringatan agar tidak melapor sambil berkendara, dan kami membuang data GPS dari foto."

**Catatan:** hitung langkahnya dengan jari ("satu, dua, tiga, empat"). Pastikan alur benar-benar ≤ 4 ketukan.

---

### Segmen 5 — Deteksi Duplikat dan Layer Bersama (1:35 – 2:10)

**Layar (Tab 3, `siswa_2`, Sekolah A):** ketuk **Lapor** di titik yang sama → muncul pesan: **"Sudah ada laporan genangan di dekat sini. Masih ada?"** → ketuk **Masih ada**.

**Layar (Tab 4, `siswa_3`, Sekolah B):** buka peta → hazard genangan terlihat di peta Sekolah B → ketuk **Masih ada**.

Status hazard berubah: **Menunggu konfirmasi → Dikonfirmasi Komunitas**. Kembali ke Tab 1 (refresh): Rute A kini menunjukkan **2 hazard aktif**, Beban hazard **47 (Sedang)**.

**Narasi:**
> "Siswa kedua mencoba melapor di titik yang sama. Sistem mendeteksi laporan serupa dan menawarkan konfirmasi saja, jadi data tidak menumpuk dan konfirmasi jadi lebih mudah. Lalu siswa dari **sekolah lain** yang catchment-nya tumpang tindih ikut mengonfirmasi, karena hazard ini milik **wilayah**, bukan milik satu sekolah. Tiga pengguna independen, dan statusnya naik menjadi Dikonfirmasi Komunitas. Tidak ada guru yang perlu menunggu, jadi genangan yang hilang dalam dua jam tetap tertangani."

**Catatan:** ini segmen pembeda utama (layer bersama + konfirmasi independen). Pastikan tampilan Tab 4 jelas menunjukkan nama sekolah/catchment yang berbeda.

---

### Segmen 6 — Verifikasi Admin Sekolah (2:10 – 2:30)

**Layar (Tab 5, `admin_A`):** buka antrean → hazard genangan muncul (tingkat tinggi) → **Verifikasi** → tambah catatan singkat → simpan.

Beban hazard Rute A berubah: **47 → 67 (Berat)**. Tunjukkan baris baru di **audit log**.

**Narasi:**
> "Karena genangan tergolong tingkat tinggi, ia masuk antrean verifikasi Admin Sekolah. Setelah diverifikasi, keyakinan naik dan Beban hazard rute menjadi 67, kategori berat. Setiap verifikasi tercatat di audit log. Perlu dicatat, hazard ini sudah dihitung sejak dikonfirmasi komunitas, bukan menunggu verifikasi."

**Catatan:** inilah jawaban untuk pertanyaan juri "bagaimana jika guru tidak sempat?".

---

### Segmen 7 — Perbandingan Rute Setelah Update (2:30 – 2:50)

**Layar (Tab 1):** refresh → tampilan perbandingan.

```text
Rute A — 1,8 km — 2 hazard aktif (1 berat)   [Beban hazard 67, Berat]
  • Genangan, depan pasar — terverifikasi, baru saja
  • Jalan berlubang, Jl. Mawar — terverifikasi, 3 hari lalu

Rute B — 2,1 km (+0,3 km) — Belum ada laporan aktif
  Ini bukan jaminan kondisi aman. Data di rute ini masih sedikit.
```

**Narasi:**
> "Sekarang siswa bisa membandingkan: Rute A punya dua hazard aktif, satu di antaranya berat dan baru dikonfirmasi beberapa saat lalu. Rute B lebih panjang 300 meter dan belum ada laporan, tapi sekali lagi, itu bukan jaminan. Keputusan tetap di tangan siswa, kami hanya memberi informasi yang jujur."

**Catatan:** jangan menyebut Rute B "lebih baik" atau "direkomendasikan".

---

### Segmen 8 — Eskalasi dan Feed Terbuka (2:50 – 3:15)

**Layar (Tab 5, `admin_A`):** buka hazard genangan → **Buat paket eskalasi** → tampil halaman publik hazard (foto, koordinat, jumlah laporan dan konfirmasi, riwayat verifikasi) → ketuk **Salin pesan WhatsApp** → tandai **Diteruskan** (kanal: [kanal resmi wilayah], tanggal hari ini).

Beralih ke dashboard: hazard aktif, antrean, status eskalasi **Diteruskan**.

Tunjukkan sekilas URL **feed GeoJSON** di browser (data hazard aktif, tanpa data pelapor).

**Narasi:**
> "Agar laporan tidak berhenti di sekolah, Admin membuat paket eskalasi: halaman publik dengan foto, koordinat, jumlah konfirmasi, dan riwayat. Pesannya siap dikirim ke kanal resmi, dan statusnya tercatat: diteruskan, ditanggapi, atau tidak ada respons. Sekolah tidak mengklaim memperbaiki jalan, tapi menjadi penyambung yang membawa bukti terstruktur. Datanya juga tersedia lewat feed GeoJSON terbuka, tanpa identitas pelapor, supaya bisa dipakai pihak lain."

**Catatan:** nyatakan terus terang bahwa integrasi dua arah dengan sistem pemerintah belum ada di MVP (Bagian 10.5).

---

### Segmen 9 — Penutup (3:15 – 3:30)

**Layar:** kembali ke peta publik, tampilkan ringkasan dampak pilot jika ada.

**Narasi:**
> "RuteAman bukan aplikasi navigasi dan tidak menjanjikan rute aman. Ia menunjukkan hazard aktif, seberapa baru datanya, dan apa yang belum diketahui, lalu membawanya ke pihak yang bisa bertindak. Di pilot kami, median waktu dari laporan ke konfirmasi adalah **[ISI HASIL]** menit. Terima kasih."

**Catatan:** jika pilot belum ada, ganti kalimat metrik dengan: "Berikutnya, kami akan mengukur waktu lapor ke konfirmasi lewat pilot di sekolah." Jangan mengklaim hasil yang belum ada.

---

## 3. Ringkasan Waktu

| Waktu | Segmen | Poin yang harus tertangkap juri |
|---|---|---|
| 0:00–0:25 | Masalah | Bukti nyata, bukan asumsi |
| 0:25–0:50 | Peta publik | Tanpa login, umur data, "bukan jaminan aman" |
| 0:50–1:10 | Rincian hazard | Transparan, tidak arbitrer |
| 1:10–1:35 | Lapor | Mudah, ≤ 4 langkah |
| 1:35–2:10 | Duplikat + layer bersama | **Pembeda utama** |
| 2:10–2:30 | Verifikasi | Cepat, audit log, tidak menunggu guru |
| 2:30–2:50 | Perbandingan rute | Informasi jujur, bukan rekomendasi |
| 2:50–3:15 | Eskalasi + feed | Loop tertutup, interconnected |
| 3:15–3:30 | Penutup | Posisi jujur + metrik pilot |

---

## 4. Rencana Cadangan

| Masalah | Tindakan |
|---|---|
| Internet mati | Putar video rekaman cadangan, jalankan build lokal |
| Tile peta lambat | Pakai area demo kecil dengan tile ter-cache |
| Jendela role gagal/refresh lambat | Gunakan role switcher, atau lompat ke tab berikutnya sambil menunggu |
| Layer bersama gagal tampil | Tunjukkan hazard di dua catchment lewat screenshot, lalu lanjutkan |
| Waktu mepet | Potong Segmen 3 (rincian) dan Segmen 8 bagian feed GeoJSON |
| Waktu berlebih | Perluas Segmen 5 dengan tampilan notifikasi status laporan |

**Aturan:** kalau satu segmen gagal lebih dari 10 detik, **lanjut ke segmen berikutnya** dan jelaskan lewat narasi. Jangan memperbaiki di depan juri.

---

## 5. Jawaban Cepat untuk Pertanyaan Setelah Demo

1. **"Apa bedanya dengan Waze/Maps?"** → Kami uji: dari **[n]** hazard hasil audit, **[x]** tidak ada di platform tersebut **[ISI HASIL]**. Kami melengkapi, bukan menggantikan.
2. **"Kenapa tidak WhatsApp/Google Form saja?"** → Tidak ada kedaluwarsa otomatis, konfirmasi independen, overlay ke rute, atau status eskalasi.
3. **"Apakah skornya arbitrer?"** → Heuristik transparan (tingkat × keyakinan × kesegaran × konteks hujan), rinciannya terbuka, bobot dikalibrasi lewat pilot.
4. **"Rute B tanpa laporan berarti aman?"** → Tidak. UI menyatakan itu eksplisit, dan tidak ada kata "aman" di mana pun.
5. **"Bagaimana privasi anak?"** → Tanpa pelacakan lokasi, lokasi hanya di titik hazard, pelapor tampil samaran, GPS foto dibuang, persetujuan orang tua.
6. **"Apa yang berubah jika sekolah tidak berwenang atas jalan?"** → Kecepatan informasi sampai ke siswa, bukti terstruktur yang diteruskan, dan status respons yang tercatat.
7. **"Bagaimana setelah lomba?"** → Pengelola sekolah, jadwal moderasi, dokumentasi serah terima, dan mode arsip baca-saja.
