# Workflow Pengembangan

## Branch

| Branch | Fungsi |
|---|---|
| `main` | Stabil, hanya menerima merge dari `dev` |
| `dev` | Integrasi semua fitur |
| `feature/admin` | Pengguna, siswa, akun siswa |
| `feature/keuangan` | Tagihan, pembayaran, jemputan, tunggakan |
| `feature/yayasan` | Informasi, riwayat, laporan |
| `feature/siswa` | Dashboard dan tagihan siswa |

Alur: `feature/*` -> pull request ke `dev` -> merge ke `main` saat rilis.

Fitur lintas role (login, tarif, alert) dikerjakan di `dev` atau branch `feature/<nama>` baru.

## Siklus satu fitur

1. **Ambil task** dari `docs/TASKS.md`, ubah statusnya jadi `doing`.
2. **Rancang** dengan skill `superpowers:brainstorming` bila fitur belum jelas, lalu `superpowers:writing-plans`.
3. **Tulis test dulu** (`superpowers:test-driven-development`) untuk logika uang: tarif, status lunas, jatuh tempo.
4. **Implementasi** sekecil mungkin (`ponytail`).
5. **UI** mengikuti `ui-ux-pro-max`.
6. **Verifikasi** dengan `superpowers:verification-before-completion`: jalankan test dan buka halamannya.
7. **Commit** pesan singkat, satu perubahan per commit.
8. **Pull request** ke `dev`, review dengan `superpowers:requesting-code-review`.
9. Ubah task jadi `done`.

## Pesan commit

```
<tipe>: <ringkasan singkat>
```

Tipe: `feat`, `fix`, `docs`, `refactor`, `test`, `chore`.

## Definisi selesai

- Test lulus.
- Halaman sudah dicoba untuk role yang bersangkutan.
- Siswa tidak bisa membuka data siswa lain.
- Tidak ada rahasia atau data asli di commit.

## Aturan

- Jangan push langsung ke `main`.
- Perubahan aturan bisnis (tarif, tanggal) diubah di `tarif` atau konfigurasi, bukan di banyak tempat di kode.
- Pertanyaan yang belum dijawab klien (lihat `PRD.md` bagian 7) dicatat, jangan ditebak diam-diam.
