# Arsitektur SIAPA

> Sudah diputuskan: **Laravel 13 (PHP 8.4)** dengan database **Supabase (PostgreSQL)**. Project sudah dibuat di root repo.

## 1. Stack

| Lapisan | Pilihan |
|---|---|
| Backend | Laravel (PHP) |
| Database | Supabase (PostgreSQL terkelola, backup otomatis) |
| File bukti QRIS | Supabase Storage, bucket privat |
| Tampilan | Blade + Tailwind CSS |
| Auth | Session login bawaan Laravel (sesi disimpan di file, bukan database), role di tabel `users` |
| Jadwal alert | Laravel Scheduler (cron harian) |
| Hosting | Satu server (VPS/shared hosting) |

Satu aplikasi monolit. Tidak ada microservice, tidak ada API terpisah.

## 2. Struktur lapisan

```
Browser
  -> Route + Middleware (auth, role)
  -> Controller
  -> Service (aturan bisnis: tagihan, pembayaran, alert)
  -> Model (Eloquent)
  -> PostgreSQL (Supabase)
```

Aturan bisnis (tarif, jatuh tempo, status lunas) hanya ada di Service, tidak di Controller atau view.

## 3. Modul (mengikuti paket use case)

| Modul | Isi |
|---|---|
| Auth | Login, logout, redirect per role |
| Siswa | Data siswa, akun siswa, pengguna |
| Tagihan | SPP, daftar ulang, jemputan, tarif |
| Pembayaran | Manual, QRIS, verifikasi bukti |
| Laporan | Rekap, tunggakan, seluruh pembayaran |
| Alert | Peringatan tanggal 10 dan 10 Desember |

## 4. Model data

```
users        (id, nama, username, password, role)
siswa        (nis PK, nama, kelas, user_id FK, aktif)
tarif        (id, jenis, kelas, nominal)            -- jenis: spp | daftar_ulang
tagihan      (id, nis FK, jenis, periode, nominal, jatuh_tempo, status)
                                                    -- jenis: spp | daftar_ulang | jemputan
                                                    -- status: belum | lunas
pembayaran   (id, tagihan_id FK, metode, jumlah, bukti, status, dicatat_oleh FK, tanggal)
                                                    -- metode: manual | qris
                                                    -- status: menunggu | diterima | ditolak | dibatalkan
audit_log    (id, user_id FK, aksi, tabel, baris_id, sebelum, sesudah, ip, waktu)  -- hanya tambah, tidak diubah
```

Role: `admin`, `yayasan`, `keuangan`, `siswa`.

Catatan:
- Semua nominal bertipe `bigint` dalam rupiah penuh, tidak pernah `float`.
- `pembayaran` tidak pernah diubah atau dihapus. Salah catat dikoreksi dengan status `dibatalkan` beserta alasan, lalu input ulang.
- `nis` jadi primary key `siswa` dan foreign key di `tagihan`.
- `tarif` menyimpan Rp590.000 / 550.000 / 540.000 dan daftar ulang Rp3.500.000 (kelas 2) / Rp3.200.000 (kelas 6) supaya tidak di-hardcode.
- Nominal jemputan diinput manual saat membuat tagihan jemputan, tidak dari tabel `tarif`.
- Satu tagihan lunas bila jumlah `pembayaran` berstatus `diterima` sama dengan `nominal`.

## 5. Hak akses

| Fitur | Admin | Yayasan | Keuangan | Siswa |
|---|---|---|---|---|
| Kelola pengguna dan siswa | ya | | | |
| Kelola tagihan dan nominal | | | ya | |
| Catat dan verifikasi pembayaran | | | ya | |
| Lihat tagihan sendiri | | | | ya |
| Lihat info dan riwayat semua | ya | ya | ya | |
| Laporan dan rekap | | ya | ya | |

Siswa hanya boleh membaca baris dengan `nis` miliknya. Pengecekan dilakukan di Service, bukan hanya menyembunyikan menu.

## 6. Alert

Peringatan **dihitung saat dibaca** dari tanggal hari ini (`AlertService`), bukan ditandai oleh job harian. Tidak ada status yang basi dan tidak perlu Laravel Scheduler. Jendelanya ada di `config/siapa.php`:
- SPP dan jemputan: 5 hari sebelum tanggal 10 sampai lunas (tetap tampil bila lewat).
- Daftar ulang: 30 hari sebelum 10 Desember sampai lunas.
- Status tampil tiap tagihan: `lunas`, `menunggu_verifikasi`, `menunggak` (lewat jatuh tempo), `hampir_jatuh_tempo`, `belum_bayar`.
- Alert tampil sebagai banner di dashboard Keuangan dan Siswa. WhatsApp tidak termasuk sampai klien memutuskan.

## 7. Keamanan

Sistem ini memegang data uang dan data anak, jadi keamanan adalah syarat rilis.

**Database (Supabase)**
- Aktifkan Row Level Security di semua tabel tanpa policy untuk `anon`, sehingga Data API publik tidak bisa membaca apa pun. Aplikasi mengakses lewat koneksi server dengan role database sendiri.
- `service_role key` dan password database hanya di `.env` server, tidak pernah di kode yang sampai ke browser. Ganti kuncinya bila pernah bocor.
- Koneksi database wajib SSL.
- Backup otomatis harian butuh paket berbayar (Pro), paket gratis tidak menyertakannya. Tambahkan `pg_dump` bulanan yang disimpan di tempat lain, dan uji restore minimal satu kali sebelum rilis.

**Integritas uang**
- Nominal `bigint` rupiah. Pembayaran tidak boleh diubah atau dihapus, hanya dibatalkan dengan alasan.
- Pencatatan pembayaran dan perubahan status tagihan dalam satu transaksi database, dengan kunci pada baris tagihan agar tidak tercatat dobel.
- Constraint unik mencegah pembayaran ganda untuk tagihan yang sama.
- Setiap aksi uang dan perubahan data penting masuk `audit_log` (siapa, kapan, dari IP mana, nilai sebelum dan sesudah).
- Pembatalan pembayaran memunculkan peringatan ke Yayasan.

**Akses dan login**
- Hak akses diperiksa di server dan per data: siswa hanya bisa membaca `nis` miliknya (cegah akses dengan mengganti ID di URL).
- Admin tidak bisa mencatat pembayaran. Pencatat bukan pengelola pengguna.
- Password di-hash (bcrypt/argon2), batas percobaan login, sesi kedaluwarsa, HTTPS wajib.
- Verifikasi dua langkah (2FA) untuk Admin dan Petugas Keuangan.
- Akun siswa dibuat Admin dengan password awal acak yang wajib diganti.

**Input dan file**
- Validasi semua input di server, query lewat ORM (cegah SQL injection), keluaran di-escape (cegah XSS), proteksi CSRF.
- Bukti QRIS: hanya gambar atau PDF, ukuran dibatasi, nama file diganti acak, disimpan di bucket privat dan dibuka lewat tautan bertanda tangan yang kedaluwarsa.

**Operasional**
- `.env` dan data siswa asli tidak pernah masuk Git. Gunakan data contoh untuk pengembangan.
- Pertimbangkan mengubah repo GitHub ke Private.
- Perbarui dependensi secara berkala dan pantau log login gagal.
- Kumpulkan data seperlunya. Data anak termasuk data pribadi yang dilindungi UU PDP.

## 8. Endpoint backend

Semua di balik login (sesi) dan middleware ganti password awal. Jawaban berupa JSON, siap dipakai tampilan Blade atau JS. Aturan bisnis ada di `app/Services`, controller hanya memvalidasi.

| Role | Endpoint |
|---|---|
| Semua | `GET /login`, `POST /login` (maks 5 per menit), `POST /logout`, `GET\|POST /ganti-password` |
| Admin | `GET\|POST /admin/pengguna`, `PATCH /admin/pengguna/{id}`, `GET\|POST /admin/siswa`, `PATCH /admin/siswa/{nis}`, `POST /admin/siswa/{nis}/akun` |
| Keuangan | `POST /keuangan/tagihan/spp`, `.../daftar-ulang`, `.../jemputan`, `PATCH /keuangan/tagihan/{id}/nominal` |
| Keuangan | `GET /keuangan/siswa/{nis}`, `POST /keuangan/tagihan/{id}/pembayaran`, `POST /keuangan/pembayaran/{id}/verifikasi`, `POST /keuangan/pembayaran/{id}/batal`, `GET /keuangan/peringatan` |
| Siswa | `GET /siswa/tagihan`, `/siswa/riwayat`, `/siswa/peringatan` (hanya data sendiri) |
| Yayasan, Keuangan | `GET /laporan/rekap`, `/laporan/tunggakan`, `/laporan/pembayaran` (filter, `?format=csv`), `/laporan/siswa/{nis}` |
| Yayasan | `GET /yayasan/pembatalan` (peringatan pembayaran yang dibatalkan beserta alasannya) |

Tambahan pada model data: `pembayaran.alasan` (alasan batal atau tolak) dan `users.aktif`, `users.harus_ganti_password`. Bukti QRIS disimpan lewat disk di `config/siapa.php` (`SIAPA_DISK_BUKTI`), bawaan `local` (privat). Ganti ke disk Supabase Storage (S3) saat produksi.

## 9. Diagram sumber

Use case, activity diagram login dan pembayaran, serta sequence diagram ada di Google Drive proyek. Dokumen ini belum memuat detail dari sequence diagram.
