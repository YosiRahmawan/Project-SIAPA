<?php

namespace Database\Seeders;

use App\Models\Tarif;
use Illuminate\Database\Seeder;

class TarifSeeder extends Seeder
{
    public function run(): void
    {
        $tarif = [
            ['spp', 1, 590000], ['spp', 2, 550000],
            ['spp', 3, 540000], ['spp', 4, 540000], ['spp', 5, 540000], ['spp', 6, 540000],
            ['daftar_ulang', 2, 3500000], ['daftar_ulang', 6, 3200000],
        ];
        foreach ($tarif as [$jenis, $kelas, $nominal]) {
            Tarif::updateOrCreate(['jenis' => $jenis, 'kelas' => $kelas], ['nominal' => $nominal]);
        }
    }
}
