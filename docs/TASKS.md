# Tasks

Status: `todo`, `doing`, `done`. Kode F mengacu ke `PRD.md`.

## Fase 0: Persiapan

| ID | Task | Branch | Status |
|---|---|---|---|
| T0.1 | Database: Supabase (sudah diputuskan). Framework (usulan: Laravel) masih perlu dikonfirmasi | dev | doing |
| T0.6 | Buat project Supabase, pilih paket dengan backup harian | - | todo |
| T0.2 | Jawaban klien untuk 6 pertanyaan terbuka | - | todo |
| T0.3 | Baca sequence diagram detail, cocokkan dengan PRD | - | todo |
| T0.4 | Setup project, `.env.example`, `.gitignore` | dev | todo |
| T0.5 | Mockup UI di FigCraft (login, dashboard, form bayar) | dev | todo |

## Fase 1: Fondasi

| ID | Task | Kode | Branch | Status |
|---|---|---|---|---|
| T1.1 | Migrasi: users, siswa, tarif, tagihan, pembayaran | - | dev | todo |
| T1.2 | Seeder tarif SPP dan daftar ulang | - | dev | todo |
| T1.3 | Login, logout, redirect per role | F1 | dev | todo |
| T1.4 | Middleware role | F1 | dev | todo |
| T1.5 | Aktifkan RLS semua tabel, role database khusus aplikasi, SSL | - | dev | todo |
| T1.6 | Tabel dan pencatatan `audit_log` | - | dev | todo |
| T1.7 | Batas percobaan login, sesi kedaluwarsa, 2FA Admin dan Keuangan | F1 | dev | todo |

## Fase 2: Admin

| ID | Task | Kode | Branch | Status |
|---|---|---|---|---|
| T2.1 | CRUD pengguna | F2 | feature/admin | todo |
| T2.2 | Daftarkan siswa, NIS unik | F3 | feature/admin | todo |
| T2.3 | Buat dan aktifkan akun siswa | F4 | feature/admin | todo |

## Fase 3: Keuangan

| ID | Task | Kode | Branch | Status |
|---|---|---|---|---|
| T3.1 | Generate tagihan SPP bulanan per kelas | F6 | feature/keuangan | todo |
| T3.2 | Tagihan daftar ulang kelas 2 dan 6 | F6 | feature/keuangan | todo |
| T3.3 | Input nominal dan tagihan jemputan manual | F9 | feature/keuangan | todo |
| T3.4 | Cari siswa by NIS, tampil tagihan | F7 | feature/keuangan | todo |
| T3.5 | Catat pembayaran manual | F7 | feature/keuangan | todo |
| T3.6 | Upload dan verifikasi bukti QRIS | F8 | feature/keuangan | todo |
| T3.7 | Status lunas otomatis | F11 | feature/keuangan | todo |
| T3.8 | Daftar tunggakan | F10 | feature/keuangan | todo |
| T3.9 | Batalkan pembayaran dengan alasan, tanpa hapus data | F7 | feature/keuangan | todo |
| T3.10 | Kunci transaksi dan constraint cegah pembayaran ganda (test) | F7 | feature/keuangan | todo |

## Fase 4: Siswa

| ID | Task | Kode | Branch | Status |
|---|---|---|---|---|
| T4.1 | Dashboard siswa: tagihan dan status | F5 | feature/siswa | todo |
| T4.2 | Riwayat pembayaran | F5 | feature/siswa | todo |
| T4.3 | Batasi akses hanya ke data sendiri (test) | F5 | feature/siswa | todo |

## Fase 5: Yayasan dan laporan

| ID | Task | Kode | Branch | Status |
|---|---|---|---|---|
| T5.1 | Informasi dan riwayat pembayaran semua siswa | F5 | feature/yayasan | todo |
| T5.2 | Rekap pembayaran dan tunggakan | F12 | feature/yayasan | todo |
| T5.3 | Laporan seluruh pembayaran, ekspor | F13 | feature/yayasan | todo |

## Fase 6: Alert

| ID | Task | Kode | Branch | Status |
|---|---|---|---|---|
| T6.1 | Scheduler harian untuk SPP dan jemputan (tanggal 10) | F14 | dev | todo |
| T6.2 | Alert daftar ulang 10 Desember | F15 | dev | todo |
| T6.3 | Banner alert di dashboard | F14, F15 | dev | todo |

## Fase 7: Penutup

| ID | Task | Branch | Status |
|---|---|---|---|
| T7.1 | Uji semua alur per role | dev | todo |
| T7.2 | Audit keamanan dan upload file | dev | todo |
| T7.4 | Atur `pg_dump` bulanan ke tempat lain dan uji restore | dev | todo |
| T7.3 | Deploy dan serah terima | main | todo |
