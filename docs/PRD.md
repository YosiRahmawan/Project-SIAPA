# PRD: SIAPA

Sistem Informasi Administrasi Pembayaran Abu Bakar, SDIT Abu Bakar Ash Shiddiq Rajapolah.

## 1. Tujuan

Menggantikan pencatatan pembayaran manual dengan satu sistem yang mencatat SPP, daftar ulang, dan jemputan per siswa, menampilkan tunggakan, dan memberi peringatan sebelum jatuh tempo.

## 2. Pengguna

| Role | Kebutuhan utama |
|---|---|
| Admin | Kelola pengguna, data siswa, akun siswa |
| Kepala Sekolah / Yayasan | Lihat informasi, riwayat, rekap, dan laporan |
| Petugas Keuangan | Kelola tagihan, catat pembayaran manual dan QRIS, nominal jemputan, tunggakan |
| Siswa (orang tua) | Login, lihat tagihan, riwayat, dan status pembayaran |

## 3. Aturan bisnis

**Tarif SPP per bulan**

| Kelas | Nominal |
|---|---|
| 1 | Rp590.000 |
| 2 | Rp550.000 |
| 3 sampai 6 | Rp540.000 |

**Daftar ulang:** kelas 2 Rp3.500.000, kelas 6 Rp3.200.000.

**Jemputan:** nominal diinput manual oleh Petugas Keuangan.

**Jatuh tempo**
- SPP dan jemputan: sebelum tanggal 10 setiap bulan.
- Daftar ulang: 10 Desember.

**Identitas siswa:** NIS adalah primary key.

**Pembayaran:** manual (tunai) dan QRIS. Bukti QRIS diperiksa dan dikonfirmasi Petugas Keuangan, tidak otomatis.

## 4. Kebutuhan fungsional

Mengacu ke use case di folder diagram (paket A sampai E).

**A. Autentikasi**
- F1. Login dan logout, tampilan sesuai role.

**B. Data siswa**
- F2. CRUD pengguna.
- F3. Daftarkan siswa (NIS, nama, kelas).
- F4. Buat dan aktifkan akun siswa.

**C. Informasi pembayaran**
- F5. Siswa melihat tagihan, riwayat, dan status.

**D. Transaksi**
- F6. Generate dan kelola tagihan (SPP bulanan, daftar ulang, jemputan).
- F7. Catat pembayaran manual.
- F8. Periksa bukti QRIS lalu terima atau tolak.
- F9. Kelola nominal jemputan.
- F10. Daftar tunggakan.
- F11. Pantau status pembayaran.

**E. Laporan**
- F12. Rekap pembayaran dan tunggakan.
- F13. Laporan seluruh pembayaran.

**Peringatan (dari catatan klien, belum ada di diagram)**
- F14. Alert warning bila SPP atau jemputan belum lunas mendekati atau melewati tanggal 10.
- F15. Alert daftar ulang menjelang 10 Desember.

## 5. Alur (flow)

Diagram di bawah memakai Mermaid dan tampil otomatis di GitHub. Alur login dan pembayaran mengikuti activity diagram di Google Drive. Alur tagihan, pembatalan, dan alert adalah tambahan dari catatan klien dan belum ada di diagram.

### 5.1 Login

```mermaid
flowchart TD
    A([Mulai]) --> B[Buka halaman login]
    B --> C[Isi username dan password]
    C --> D{Data benar?}
    D -- Ya --> E[Masuk dashboard sesuai role]
    D -- Tidak --> F[Pesan login gagal] --> B
    E --> G([Selesai])
```

### 5.2 Pembayaran

```mermaid
flowchart TD
    A([Petugas buka menu pembayaran]) --> B[Cari siswa dengan NIS]
    B --> C{NIS ditemukan?}
    C -- Tidak --> D[Pesan: Data siswa tidak ditemukan] --> B
    C -- Ya --> E[Tampil info siswa dan daftar tagihan]
    E --> F[Pilih tagihan]
    F --> G{Metode QRIS?}
    G -- Manual --> H[Input data pembayaran]
    G -- QRIS --> I[Periksa bukti bayar]
    I --> J{Bukti valid?}
    J -- Tidak --> K[Tolak pembayaran] --> L([Selesai])
    J -- Ya --> H
    H --> M[Simpan transaksi dan perbarui status tagihan]
    M --> N[Catat di audit log]
    N --> O[Siswa melihat status baru di akunnya]
    O --> L
```

### 5.3 Tagihan bulanan dan alert

```mermaid
flowchart TD
    A([Awal bulan]) --> B[Generate tagihan SPP per kelas]
    B --> C[Petugas input tagihan jemputan manual]
    C --> D[Tagihan tampil di akun siswa]
    D --> E{Mendekati atau lewat tanggal 10?}
    E -- Belum --> F([Tunggu])
    E -- Ya --> G{Sudah lunas?}
    G -- Ya --> H([Selesai])
    G -- Tidak --> I[Tampil alert di dashboard Keuangan dan Siswa]
    I --> J[Masuk daftar tunggakan]
    K([Menjelang 10 Desember]) --> L{Daftar ulang lunas?}
    L -- Tidak --> I
    L -- Ya --> H
```

### 5.4 Pembatalan pembayaran (salah catat)

```mermaid
flowchart TD
    A([Petugas temukan salah catat]) --> B[Pilih pembayaran dan isi alasan]
    B --> C[Status jadi dibatalkan, data lama tetap tersimpan]
    C --> D[Status tagihan dihitung ulang]
    D --> E[Catat di audit log dan beri peringatan ke Yayasan]
    E --> F[Input ulang pembayaran yang benar]
    F --> G([Selesai])
```

## 6. Di luar cakupan awal

- Verifikasi QRIS otomatis lewat payment gateway.
- Pengiriman WhatsApp ke orang tua (menunggu jawaban klien).
- Denda keterlambatan (menunggu jawaban klien).

## 7. Pertanyaan terbuka untuk klien

1. Daftar ulang hanya kelas 2 dan 6, atau kelas lain juga?
2. Jemputan: nominal tetap atau berbeda per siswa/jarak? Siapa yang menentukan?
3. Lewat tanggal 10: ada denda atau hanya peringatan?
4. Apakah sekolah punya QRIS sendiri?
5. Pengingat: tampil di sistem saja atau juga WhatsApp?
6. Orang tua login sendiri (diasumsikan ya, sesuai diagram).

## 8. Kriteria selesai

- Semua F1 sampai F13 berjalan sesuai role.
- Alert F14 dan F15 tampil sesuai tanggal.
- NIS unik dan tidak bisa duplikat.
- Siswa hanya bisa melihat data miliknya sendiri.
