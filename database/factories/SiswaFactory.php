<?php

namespace Database\Factories;

use App\Models\Siswa;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Siswa> */
class SiswaFactory extends Factory
{
    protected $model = Siswa::class;

    public function definition(): array
    {
        return [
            'nis' => (string) fake()->unique()->numberBetween(2000000, 2999999),
            'nama' => fake()->name(),
            'kelas' => fake()->numberBetween(1, 6),
            'aktif' => true,
        ];
    }

    public function kelas(int $kelas): static
    {
        return $this->state(['kelas' => $kelas]);
    }
}
