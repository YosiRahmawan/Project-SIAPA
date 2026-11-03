<?php

namespace App\Services;

use App\Enums\Role;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AkunSiswaService
{
    /**
     * Buat akun login untuk siswa: username = NIS, password awal acak yang wajib diganti.
     * Password asli hanya dikembalikan sekali ini, tidak disimpan di mana pun.
     *
     * @return array{0: User, 1: string}
     */
    public function buat(Siswa $siswa): array
    {
        if ($siswa->user_id) {
            throw ValidationException::withMessages(['siswa' => 'Siswa ini sudah punya akun.']);
        }

        return DB::transaction(function () use ($siswa) {
            $password = Str::password(12, symbols: false);
            $user = User::create([
                'nama' => $siswa->nama,
                'username' => $siswa->nis,
                'password' => $password,
                'role' => Role::Siswa,
                'harus_ganti_password' => true,
            ]);
            $siswa->forceFill(['user_id' => $user->id])->save();
            Audit::catat('akun_siswa.buat', 'users', $user->id, null, ['username' => $user->username, 'nis' => $siswa->nis]);

            return [$user, $password];
        });
    }
}
