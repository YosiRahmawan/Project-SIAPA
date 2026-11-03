<?php

namespace Database\Factories;

use App\Enums\JenisTagihan;
use App\Models\Siswa;
use App\Models\Tagihan;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Tagihan> */
class TagihanFactory extends Factory
{
    protected $model = Tagihan::class;

    public function definition(): array
    {
        return [
            'nis' => Siswa::factory(),
            'jenis' => JenisTagihan::Spp,
            'periode' => '2026-11',
            'nominal' => 540000,
            'jatuh_tempo' => '2026-11-10',
            'status' => Tagihan::BELUM,
        ];
    }
}
