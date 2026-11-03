# Design SIAPA

Aturan tampilan untuk semua halaman. Acuan tata letak dan gaya: mockup **Dashboard Keuangan v3 biru** di FigCraft (`Dashboard Keuangan v3 biru`), yang berasal dari dashboard "Finora" lalu disesuaikan ke SIAPA. Warna, font, dan gaya di bawah ini menggantikan yang ada di Finora. Rasio kontras sudah dihitung.

## 1. Karakter

Segar, bulat, dan ramah, tetapi tetap rapi dan bisa dipercaya karena ini sistem uang sekolah. Dibaca orang tua di HP dan petugas keuangan seharian di desktop, jadi tidak boleh melelahkan mata.

Selaras dengan situs yayasan (navy, biru, emas). Kesan hidup datang dari sudut membulat, kartu statistik berwarna, ikon berwarna lembut, dan ilustrasi sederhana, bukan dari warna yang ramai.

## 2. Yang dilarang

- Emoji di antarmuka (☀️ ☕ 🛒 💰 🌱 dan sejenisnya). Pakai ikon garis, inisial, atau ilustrasi.
- Kalimat motivasi dan sapaan berlebihan ("Your financial journey is going great!", "Small steps build big wealth"). Sapaan pendek seperti "Selamat pagi, Yosi" boleh.
- Banner promo, tombol ajakan investasi, atau teks marketing di dalam aplikasi.
- Blur, bola cahaya yang berkilau, dan cahaya neon. Hiasan hanya bentuk datar semi transparan (lingkaran, bukit, awan).
- Gradasi pada logo, ikon, dan avatar. Gradasi hanya untuk kartu hero, batang grafik, dan latar ilustrasi.
- Bayangan berwarna terang (`shadow-blue-500/30` dan sejenisnya). Bayangan hanya navy sangat tipis.
- Teks di bawah 12px dan font lebih tebal dari Bold (700).
- Satu warna biru untuk semua data.
- Kartu di dalam kartu di dalam kartu.
- Lebih dari 3 kolom konten. Dashboard tidak boleh terasa padat.

## 3. Warna

Biru Finora diganti keluarga navy sekolah. Biru muda `#54A7EE` hanya untuk isian dan hiasan karena kontrasnya 2,6:1 di atas putih, jadi tidak boleh untuk teks.

**Merek dan antarmuka**

| Token | Hex | Pakai untuk | Kontras |
|---|---|---|---|
| `navy-900` | `#002147` | Judul, angka besar, kartu hero (ujung gelap), tombol gelap | putih di atasnya 16:1, teks pada emas 9,1:1 |
| `navy-700` | `#0E3C73` | Ujung gelap gradasi ilustrasi sidebar | putih 11:1 |
| `primary` | `#1D5FA6` | Tombol utama, menu aktif, tautan, batang terpilih, SPP | putih 6,5:1 |
| `primary-mid` | `#2C74C2` | Ujung terang gradasi, aksen ilustrasi | putih 4,8:1 |
| `primary-soft` | `#E8F1FB` | Latar ikon, tab aktif, tombol sekunder, badge info | teks `primary` 5,7:1 |
| `sky` | `#54A7EE` | Hiasan, isian grafik dan ilustrasi (bukan teks) | 2,6:1 |
| `sky-soft` | `#BBD6F2` | Batang biasa pada grafik | hiasan |
| `gold` | `#FFB606` | Logo, lencana, kartu statistik emas, matahari ilustrasi | teks navy 9,1:1 |
| `bg` | `#F3F7FC` | Latar halaman | teks `text` 14,1:1, `muted` 5,1:1 |
| `surface` | `#FFFFFF` | Kartu, tabel, form, sidebar | |
| `border` | `#E3E8EF` | Garis pemisah, garis bantu grafik | |
| `divider` | `#EEF1F5` | Garis antar baris tabel dan daftar | |
| `text` | `#1B2733` | Teks utama | 14,3:1 |
| `text-2` | `#2D3A4B` | Label menu dan teks sekunder tebal | 11,5:1 |
| `muted` | `#5B6B7B` | Teks pendukung | 5,5:1 |

Emas tidak dipakai sebagai warna teks di atas putih (2,2:1). Pada kartu hero, teks hanya putih atau `#D6E4F5`, dan label kecil diletakkan di sisi navy gradasi (teks `#C5D0DE` hanya 4,2:1 di atas `primary`).

**Status pembayaran** (selalu dengan teks, tidak hanya warna)

| Status | Teks | Latar | Kontras |
|---|---|---|---|
| Lunas | `#14703F` | `#E6F4EC` | 5,4:1 |
| Hampir jatuh tempo | `#8A4B00` | `#FFF3D6` | 6,2:1 |
| Menunggak | `#B42318` | `#FDECEA` | 5,8:1 |
| Menunggu verifikasi | `#1D5FA6` | `#E8F1FB` | 5,7:1 |
| Dibatalkan / ditolak | `#5B6B7B` | `#EEF1F5` | 4,8:1 |

Merah dan hijau hanya berarti status. Jangan dipakai untuk membedakan kategori di grafik.

**Kartu statistik berwarna** (hanya di dashboard keuangan, teks besar 24px ke atas)

| Kartu | Latar | Teks | Kontras |
|---|---|---|---|
| Total tagihan | `surface` + lingkaran `primary-soft` | navy | 16:1 |
| Menunggak | `#B42318` | putih | 6,6:1 |
| Menunggu verifikasi | `gold` | navy | 9,1:1 |

## 4. Statistik dan grafik

Aturan utama: **satu warna tidak boleh mewakili banyak hal.** Tiap bagian data harus bisa dibedakan dengan jelas, dan bisa dibaca tanpa membedakan warna.

**Palet kategori** (jenis tagihan, kelas, dan sejenisnya). Maksimal 6, diberi urutan tetap:

| No | Nama | Hex | Kontras di putih |
|---|---|---|---|
| 1 | Biru | `#1D5FA6` | 6,5:1 |
| 2 | Emas tua | `#C28400` | 3,2:1 |
| 3 | Teal | `#12856F` | 4,6:1 |
| 4 | Jingga | `#C8481F` | 4,8:1 |
| 5 | Ungu | `#7A5AA6` | 5,5:1 |
| 6 | Abu | `#6F7D8C` | 4,2:1 |

Pemetaan tetap supaya warna yang sama selalu berarti hal yang sama di seluruh aplikasi:
- SPP = Biru, Daftar ulang = Emas tua, Jemputan = Teal.

**Aturan**
1. Nilai ditulis langsung di grafik atau di legenda (Rp dan persen). Warna bukan satu-satunya petunjuk.
2. Segmen yang bersebelahan dipisahkan garis putih 3px.
3. Lunas versus menunggak memakai warna status, bukan palet kategori.
4. Data urut (misalnya penerimaan per bulan) memakai satu warna biru: batang biasa `sky-soft` bergradasi tipis, batang tertinggi `primary` gelap dengan tooltip navy ("Tertinggi Rp189 jt").
5. Grafik batang lebih diutamakan daripada donat untuk perbandingan. Donat hanya untuk bagian dari total dengan maksimal 4 segmen, lubang tengah 70%, total ditulis di tengah.
6. Batang bersudut atas 14px dan bawah 4px. Garis bantu putus-putus (4, 4) warna `border`, garis dasar lebih tegas, sumbu tidak lebih gelap dari `muted`.
7. Setiap grafik punya judul, periode, dan satuan, serta pilihan periode berbentuk tab pil (6 bulan, bulan lalu, tahun ini).
8. Angka uang rata kanan, `tabular-nums`, format `Rp1.250.000`. Di kartu dan legenda boleh ringkas `Rp163,7 jt` asal satuannya jelas.
9. Kondisi kosong dan error punya teks yang jelas, tidak hanya grafik kosong.

## 5. Tipografi

Satu keluarga: **Inter** (self-host, cadangan `system-ui, sans-serif`). Plus Jakarta Sans tidak dipakai. Angka uang memakai Inter dengan `tabular-nums` supaya sejajar rapi.

| Level | Ukuran | Berat |
|---|---|---|
| Angka kartu hero | 38px | 700 |
| Judul halaman | 28px | 700 |
| Angka kartu statistik | 24px | 700 |
| Salam hero | 24px | 700 |
| Judul kartu | 16px sampai 18px | 700 |
| Teks isi | 14px | 400 atau 500 |
| Teks pendukung dan label | 12px sampai 13px | 400 atau 500 |

Berat maksimal 700 (Bold), dipakai untuk judul dan angka. Tinggi baris 1,5 untuk teks isi. Panjang baris teks bacaan maksimal sekitar 70 karakter.

## 6. Bentuk dan jarak

- Radius: 24px untuk kartu hero dan kartu besar, 20px untuk kartu biasa, 14px untuk menu dan tombol sekunder, **bentuk pil penuh** untuk tombol utama, kolom cari, tab, dan badge status.
- Jarak berkelipatan 4px. Padding kartu 24px, jarak antar kartu 20px, jarak antar bagian halaman 22px.
- Bayangan netral navy sangat tipis (`0 4px 18px rgba(0,33,71,.07)`). Tombol utama boleh punya bayangan biru tipis (`0 8px 16px rgba(29,95,166,.27)`). Kartu bergaris `border` boleh tanpa bayangan.
- Ikon garis 1,75px dari satu set saja (misalnya Lucide), ukuran 20px. Ikon di dalam kotak berwarna lembut 36 sampai 52px dengan sudut 12 sampai 16px.
- Avatar siswa: lingkaran dengan inisial di atas warna lembut (biru, emas, teal, merah muda, abu).
- Latar halaman terang saja untuk tahap awal. Mode gelap dikerjakan belakangan.

## 7. Ilustrasi

Ilustrasi datar, sederhana, dan hanya memakai warna palet. Tujuannya memberi kesan ramah, bukan dekorasi penuh.

- **Kartu hero:** gedung sekolah (atap emas, jendela `sky`, pintu `primary`), bendera, pohon, koin "Rp", matahari emas, awan semi transparan, di atas gradasi navy ke `primary`.
- **Kartu Belum lunas:** kalender "NOV 10" dengan pita merah dan koin emas, latar emas lembut.
- **Sidebar bawah:** kartu tahun ajaran dengan bukit `sky` dan matahari emas.
- Maksimal satu ilustrasi per kartu, tidak lebih dari tiga per layar, dan tidak boleh menutupi teks.
- Tidak ada ilustrasi di tabel, form pembayaran, dan halaman laporan.

## 8. Komponen

- **Sidebar:** putih, lebar 252px. Logo kotak `primary` berinisial "S" berwarna emas dan tulisan SIAPA. Menu tinggi 46px, sudut 14px, ikon garis. Menu aktif berlatar `primary` dengan teks putih. Lencana jumlah tunggakan berlatar `#FDE7E3` dengan teks merah. Bagian bawah: kartu tahun ajaran berilustrasi dan **tombol Keluar** berbingkai merah lembut (teks `#B42318`, ikon pintu keluar). Tanpa kartu promo.
- **Header:** kolom cari berbentuk pil di kiri, tombol notifikasi bulat dengan titik merah, kartu avatar dan nama pengguna di kanan.
- **Kartu hero:** gradasi navy ke `primary`, salam pendek, badge status ("Sesuai target"), angka terkumpul 38px, satu chip perubahan dibanding bulan lalu, ilustrasi di kanan.
- **Kartu statistik:** tiga kartu berwarna (lihat bagian 3), ikon di kotak, label, angka 24px.
- **Aksi cepat:** lima ubin ikon berwarna lembut dengan label di bawahnya (input pembayaran, cetak kuitansi, kirim pengingat, verifikasi, ekspor laporan). Semuanya sekunder.
- **Tombol utama:** `primary`, teks putih, bentuk pil, tinggi 46px, satu per layar (+ Input pembayaran di header). Tombol gelap navy hanya di dalam kartu bernuansa terang. Tombol sekunder berlatar `primary-soft`.
- **Tabel dan daftar:** header `muted` 12 sampai 13px, baris 58 sampai 60px, garis `divider`, avatar inisial di kiri, kolom uang rata kanan, status sebagai badge pil berteks dan ikon.
- **Progres per kelas:** baris berisi ikon kelas, nama, persen, nominal, dan batang tipis 7px. Kelas terendah diberi tanda teks "terendah".
- **Form pembayaran:** cari NIS, pilih tagihan, simpan. Tidak lebih dari tiga langkah.
- **Alert jatuh tempo (10 setiap bulan, misalnya 10 Desember):** kartu `Belum lunas` bernuansa emas lembut dengan teks `#5C3200` dan kalender, memuat jumlah tagihan dan tombol "Lihat tunggakan". Pada halaman lain dipakai banner di atas konten (latar `#FFF3D6`, teks `#8A4B00`). Bisa ditutup, muncul lagi jika masih ada tunggakan.
- **Siswa (HP):** satu kolom, kartu tagihan dengan status badge dan nominal besar, area sentuh minimal 44px.

## 9. Susunan Dashboard Keuangan

Halaman website 1440px. Sidebar 252px di kiri, konten dengan padding 28px. Header di atas, lalu **tiga kolom**:

| Kolom | Lebar | Isi |
|---|---|---|
| Kiri | 480px | Kartu hero, aksi cepat, penerimaan bulanan, transaksi terbaru |
| Tengah | 352px | Komposisi tagihan (donat), progres pelunasan, pelunasan per kelas |
| Kanan | 280px | Belum lunas, tagihan jatuh tempo, aktivitas terbaru, pengingat |

Urutan prioritas jika ruang sempit: hero, statistik, grafik, transaksi. Yang lain disembunyikan dulu. Isinya semua data nyata SIAPA (tagihan, pembayaran, kelas), tidak ada konten umum seperti investasi atau anggaran pribadi.

## 10. Aksesibilitas

- Kontras teks minimal 4,5:1, elemen grafis minimal 3:1.
- Fokus keyboard terlihat jelas (garis `primary` 2px).
- Status selalu memuat teks dan ikon, tidak hanya warna.
- Tabel dan grafik punya label dan ringkasan untuk pembaca layar.
- Ilustrasi bersifat dekoratif dan diberi teks alternatif kosong.
- Hormati `prefers-reduced-motion`. Animasi hanya transisi pendek 150 sampai 200ms.

## 11. Yang dipertahankan dari Finora

- Pembagian sidebar kiri, header dengan pencarian, isi berbentuk grid.
- Kartu hero dengan ilustrasi, aksi cepat, donat komposisi, grafik batang bulanan, daftar jatuh tempo, dan tabel transaksi.
- Pilihan periode (bulan ini, bulan lalu, tahun) di kartu grafik.

## 12. Yang akan dikerjakan berikutnya

- Terjemahkan gaya ini ke halaman lain: dashboard Siswa (HP), form pembayaran, tunggakan, laporan.
- Mode gelap.
- Pasang font Inter secara self-host dan ekspor ilustrasi sebagai SVG.
