<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(TarifSeeder::class);

        // Akun contoh hanya untuk pengembangan lokal, tidak pernah dibuat di produksi.
        if (! app()->environment('local')) {
            return;
        }
        foreach (Role::cases() as $role) {
            if ($role === Role::Siswa) {
                continue;
            }
            User::firstOrCreate(
                ['username' => $role->value],
                ['nama' => ucfirst($role->value), 'password' => 'password', 'role' => $role],
            );
        }
        $akun = User::firstOrCreate(
            ['username' => '2410123'],
            ['nama' => 'Aisyah Rahma', 'password' => 'password', 'role' => Role::Siswa],
        );
        Siswa::firstOrCreate(['nis' => '2410123'], ['nama' => 'Aisyah Rahma', 'kelas' => 1])
            ->forceFill(['user_id' => $akun->id])->save();
    }
}
