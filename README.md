# SIAPA

Sistem Informasi Administrasi Pembayaran Abu Bakar, SDIT Abu Bakar Ash Shiddiq Rajapolah.

Mengelola pembayaran SPP, daftar ulang, dan jemputan siswa (manual dan QRIS), dengan peringatan tunggakan. NIS menjadi primary key siswa.

## Role

- Admin
- Kepala Sekolah / Yayasan
- Petugas Keuangan
- Siswa (orang tua)

## Aturan singkat

- SPP: kelas 1 Rp590.000, kelas 2 Rp550.000, kelas 3 sampai 6 Rp540.000.
- Daftar ulang: kelas 2 Rp3.500.000, kelas 6 Rp3.200.000.
- SPP dan jemputan jatuh tempo sebelum tanggal 10 tiap bulan. Daftar ulang 10 Desember.
- Jemputan dan pembayaran diinput manual oleh Petugas Keuangan, QRIS dikonfirmasi petugas.

## Dokumentasi

| File | Isi |
|---|---|
| [docs/PRD.md](docs/PRD.md) | Kebutuhan produk dan aturan bisnis |
| [docs/ARSITEKTUR.md](docs/ARSITEKTUR.md) | Stack, modul, model data, hak akses |
| [docs/WORKFLOW.md](docs/WORKFLOW.md) | Alur kerja, branch, commit |
| [docs/SKILLS.md](docs/SKILLS.md) | Skill Claude Code yang dipakai |
| [docs/TASKS.md](docs/TASKS.md) | Daftar task per fase |

## Branch

- `main`: stabil
- `dev`: integrasi
- `feature/admin`, `feature/yayasan`, `feature/keuangan`, `feature/siswa`: pengerjaan per role

## Status

Tahap perencanaan. Belum ada kode aplikasi.
