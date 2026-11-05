<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
    case Yayasan = 'yayasan';
    case Keuangan = 'keuangan';
    case Siswa = 'siswa';

    /** Halaman awal setelah login. */
    public function beranda(): string
    {
        return match ($this) {
            self::Admin => '/admin/pengguna',
            self::Yayasan => '/laporan/rekap',
            self::Keuangan => '/keuangan',
            self::Siswa => '/siswa/tagihan',
        };
    }
}
