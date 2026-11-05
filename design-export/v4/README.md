# SIAPA v4

Konsep visual baru berdasarkan HTML Finora dan lima referensi yang diberikan pengguna. Tidak memakai `docs/DESIGN.md` sebagai arahan visual. Kebutuhan, role, tarif dan alur pembayaran tetap mengikuti SIAPA.

## Buka desain

Buka `siapa-v4.figcraft` di FigCraft. Gunakan flow **Keuangan / mulai di sini** pada mode Present. Klik tombol panel di kiri atas untuk berganti antara sidebar 240 px dan sidebar ikon 80 px.

Ada 22 frame: dashboard dengan dua keadaan sidebar, input tunai, input QRIS, verifikasi, tagihan, tunggakan, laporan dua role, data siswa dua role, tambah siswa, pengguna, ringkasan Yayasan, pembatalan, login, tagihan dan riwayat orang tua, dua hasil transaksi, serta panduan konsep. PNG tiap frame berada di folder ini.

Semua teks, bentuk, grafik dan ikon berupa layer FigCraft yang dapat diedit. Logo pada login berupa PNG transparan dari konsep logo yang sebelumnya dibuat. Ikon menggunakan Tabler Icons dari repositori resmi (MIT): https://github.com/tabler/tabler-icons

## Interaksi dan batas konsep

Navigasi utama terhubung sebagai prototype. Sidebar dashboard telah diuji dengan klik untuk menciutkan dan membuka kembali. Form, filter, unggah bukti dan ekspor CSV merupakan rancangan visual; belum menjalankan operasi data. Area bukti QRIS memakai placeholder yang diberi label, bukan bukti transfer asli.

Angka dan identitas merupakan data contoh. Ini rancangan halaman inti untuk ditinjau; belum mencakup semua keadaan CRUD, ganti password, jenis tagihan tambahan, maupun seluruh ukuran layar. Belum diterapkan ke Blade/backend.

## Reproduksi

`build_v4.py` membuat dokumen melalui control API FigCraft pada port lokal 7979, menyimpan file native, menautkan prototype, dan mengekspor PNG. Skrip hanya membaca/menulis aset proyek di folder desain; tidak mengubah aplikasi Laravel.

Verifikasi: seluruh perintah pembuatan berhasil; seluruh PNG ditinjau; pengujian klik sidebar berhasil pada kedua arah. Tidak ada font hilang atau error yang dilaporkan aplikasi pada pengujian tersebut.
