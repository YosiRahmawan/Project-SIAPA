<?php

return [
    // Tanggal jatuh tempo SPP dan jemputan tiap bulan, dan daftar ulang tiap tahun.
    'tanggal_jatuh_tempo' => 10,
    'bulan_daftar_ulang' => 12,

    // Peringatan tampil sekian hari sebelum jatuh tempo sampai tagihan lunas.
    'hari_peringatan' => [
        'spp' => 5,
        'jemputan' => 5,
        'daftar_ulang' => 30,
    ],

    // Disk privat untuk bukti QRIS (ganti ke disk Supabase Storage saat produksi).
    'disk_bukti' => env('SIAPA_DISK_BUKTI', 'local'),
    'maks_bukti_kb' => 2048,
];
