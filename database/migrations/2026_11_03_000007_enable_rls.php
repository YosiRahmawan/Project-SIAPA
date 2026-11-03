<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/*
 * Supabase: Data API publik tidak boleh membaca tabel apa pun.
 * RLS aktif tanpa policy untuk `anon` dan `authenticated`, aplikasi masuk lewat
 * koneksi server dengan role database sendiri. Role aplikasi harus bukan pemilik
 * tabel (pemilik tidak terkena RLS) dan diberi hak lewat GRANT, lihat docs/ARSITEKTUR.md.
 */
return new class extends Migration
{
    private array $tabel = ['users', 'siswa', 'tarif', 'tagihan', 'pembayaran', 'audit_log', 'migrations'];

    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }
        foreach ($this->tabel as $t) {
            DB::statement("ALTER TABLE {$t} ENABLE ROW LEVEL SECURITY");
        }
        // Log audit hanya boleh ditambah: cabut UPDATE/DELETE dari role aplikasi bila ada.
        DB::statement('REVOKE UPDATE, DELETE ON audit_log FROM PUBLIC');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }
        foreach ($this->tabel as $t) {
            DB::statement("ALTER TABLE {$t} DISABLE ROW LEVEL SECURITY");
        }
    }
};
