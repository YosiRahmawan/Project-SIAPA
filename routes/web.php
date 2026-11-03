<?php

use App\Http\Controllers\Admin\PenggunaController;
use App\Http\Controllers\Admin\SiswaController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Keuangan\PembayaranController;
use App\Http\Controllers\Keuangan\TagihanController;
use App\Http\Controllers\Laporan\LaporanController;
use App\Http\Controllers\Siswa\PortalController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect(auth()->check() ? auth()->user()->role->beranda() : '/login'));

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'form'])->name('login');
    // Batas percobaan login: 5 kali per menit per alamat IP.
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/ganti-password', [AuthController::class, 'formGantiPassword'])->name('ganti-password');
    Route::post('/ganti-password', [AuthController::class, 'gantiPassword'])->name('ganti-password.simpan');

    Route::middleware('ganti.password')->group(function () {
        // Admin: pengguna, siswa, akun siswa. Tidak bisa mencatat pembayaran.
        Route::middleware('role:admin')->prefix('admin')->group(function () {
            Route::get('pengguna', [PenggunaController::class, 'index']);
            Route::post('pengguna', [PenggunaController::class, 'store']);
            Route::patch('pengguna/{user}', [PenggunaController::class, 'update']);
            Route::get('siswa', [SiswaController::class, 'index']);
            Route::post('siswa', [SiswaController::class, 'store']);
            Route::patch('siswa/{siswa}', [SiswaController::class, 'update']);
            Route::post('siswa/{siswa}/akun', [SiswaController::class, 'buatAkun']);
        });

        // Petugas Keuangan: tagihan dan pembayaran.
        Route::middleware('role:keuangan')->prefix('keuangan')->group(function () {
            Route::post('tagihan/spp', [TagihanController::class, 'spp']);
            Route::post('tagihan/daftar-ulang', [TagihanController::class, 'daftarUlang']);
            Route::post('tagihan/jemputan', [TagihanController::class, 'jemputan']);
            Route::patch('tagihan/{tagihan}/nominal', [TagihanController::class, 'ubahNominal']);
            Route::get('siswa/{nis}', [PembayaranController::class, 'cari']);
            Route::post('tagihan/{tagihan}/pembayaran', [PembayaranController::class, 'store']);
            Route::post('pembayaran/{pembayaran}/verifikasi', [PembayaranController::class, 'putuskan']);
            Route::post('pembayaran/{pembayaran}/batal', [PembayaranController::class, 'batal']);
            Route::get('peringatan', [LaporanController::class, 'peringatan']);
        });

        // Siswa: hanya data miliknya.
        Route::middleware('role:siswa')->prefix('siswa')->group(function () {
            Route::get('tagihan', [PortalController::class, 'tagihan']);
            Route::get('riwayat', [PortalController::class, 'riwayat']);
            Route::get('peringatan', [PortalController::class, 'peringatan']);
        });

        // Laporan dan riwayat: Yayasan dan Keuangan.
        Route::middleware('role:yayasan,keuangan')->prefix('laporan')->group(function () {
            Route::get('rekap', [LaporanController::class, 'rekap']);
            Route::get('tunggakan', [LaporanController::class, 'tunggakan']);
            Route::get('pembayaran', [LaporanController::class, 'pembayaran']);
            Route::get('siswa/{nis}', [LaporanController::class, 'siswa']);
        });
        Route::get('yayasan/pembatalan', [LaporanController::class, 'pembatalan'])->middleware('role:yayasan');
    });
});
