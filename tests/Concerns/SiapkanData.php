<?php

namespace Tests\Concerns;

use App\Enums\Role;
use App\Models\Siswa;
use App\Models\Tagihan;
use App\Models\User;
use Database\Seeders\TarifSeeder;

trait SiapkanData
{
    protected function seedTarif(): void
    {
        $this->seed(TarifSeeder::class);
    }

    protected function staf(Role $role): User
    {
        return User::factory()->role($role)->create();
    }

    /** @return array{0: User, 1: Siswa} */
    protected function akunSiswa(int $kelas = 1): array
    {
        $user = User::factory()->role(Role::Siswa)->create();
        $siswa = Siswa::factory()->kelas($kelas)->create();
        $siswa->forceFill(['user_id' => $user->id])->save();

        return [$user, $siswa];
    }

    protected function tagihan(array $atribut = []): Tagihan
    {
        return Tagihan::factory()->create($atribut);
    }
}
