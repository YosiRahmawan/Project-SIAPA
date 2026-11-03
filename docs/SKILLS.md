# Skill yang Dipakai

Tiga set skill untuk Claude Code di project ini.

## 1. Superpowers (proses kerja)

| Skill | Dipakai saat |
|---|---|
| `superpowers:brainstorming` | Sebelum fitur baru atau perubahan desain |
| `superpowers:writing-plans` | Memecah fitur jadi langkah |
| `superpowers:executing-plans` | Menjalankan rencana yang sudah ditulis |
| `superpowers:test-driven-development` | Logika tarif, tagihan, status lunas, alert |
| `superpowers:systematic-debugging` | Ada bug, cari akar masalah dulu |
| `superpowers:verification-before-completion` | Sebelum menyatakan selesai |
| `superpowers:requesting-code-review` | Sebelum merge ke `dev` |
| `superpowers:finishing-a-development-branch` | Menutup branch fitur |

## 2. Ponytail (ukuran perubahan)

Mode kode paling kecil yang tetap menyelesaikan masalah.

- Pakai yang sudah ada (fitur Laravel, komponen yang sudah dibuat) sebelum menulis baru.
- Jangan tambah fitur, opsi, atau abstraksi yang tidak diminta.
- Logika uang dan keamanan tidak boleh dipotong, dan wajib ada test.
- Shortcut yang disengaja diberi komentar `shortcut: <batas>, <kapan ditingkatkan>`.
- Skill terkait: `ponytail-review` (review perubahan), `ponytail-audit` (audit seluruh repo), `ponytail-debt` (daftar shortcut).

## 3. UI UX Pro Max (tampilan)

| Skill | Dipakai saat |
|---|---|
| `ui-ux-pro-max:ui-ux-pro-max` | Merancang atau memperbaiki halaman, form, tabel, dashboard |
| `ui-ux-pro-max:design-system` | Menentukan warna, tipografi, spasi, token |
| `ui-ux-pro-max:ui-styling` | Implementasi Tailwind dan komponen |

Prioritas UI untuk SIAPA:
- Dibaca di HP oleh orang tua dan di desktop oleh petugas.
- Status pembayaran (lunas, belum, terlambat) jelas lewat warna dan teks, tidak hanya warna.
- Form pembayaran dengan sedikit langkah: cari NIS, pilih tagihan, simpan.
- Kontras dan fokus keyboard memadai.

## Alat desain

FigCraft terhubung lewat MCP (`figcraft` dan `figcraft-app`) untuk membuat mockup sebelum coding halaman.

## 4. Skill tambahan (dari nexuslinkproductions/yuri-os)

Terpasang di `.claude/skills/`.

| Skill | Dipakai saat |
|---|---|
| `design-dna` | Mengekstrak "DNA desain" (token, gaya, efek) dari gambar atau URL referensi menjadi JSON, lalu membuat desain darinya. Dipakai untuk menentukan gaya mockup di FigCraft sebelum coding halaman. |
| `genjutsu-compose-multiplatform` | Pola Compose/Kotlin Multiplatform. **Tidak dipakai** untuk SIAPA selama stack-nya web (Laravel). Aktif hanya bila nanti ada aplikasi mobile Compose. |

Catatan: `design-dna` merujuk `references/schema.md` dan `references/generation-guide.md`, tetapi kedua file itu tidak ada di repo sumber. Skill tetap berjalan tanpa keduanya, hanya tanpa skema rinci.
