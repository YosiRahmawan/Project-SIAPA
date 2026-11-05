<?php

namespace App\Http\Controllers\Keuangan;

use App\Http\Controllers\Controller;
use App\Models\Siswa;
use App\Models\Tagihan;
use App\Services\TagihanService;
use Illuminate\Http\Request;

class TagihanController extends Controller
{
    public function __construct(private TagihanService $service) {}

    public function spp(Request $request)
    {
        $periode = $request->validate(['periode' => 'required|string'])['periode'];

        return ['dibuat' => $this->service->generateSpp($periode)];
    }

    public function daftarUlang(Request $request)
    {
        $tahun = $request->validate(['tahun' => 'required|integer|between:2020,2100'])['tahun'];

        return ['dibuat' => $this->service->generateDaftarUlang($tahun)];
    }

    public function jemputan(Request $request)
    {
        $d = $request->validate([
            'nis' => 'required|exists:siswa,nis',
            'periode' => 'required|string',
            'nominal' => 'required|integer|min:1',
        ]);

        return response()->json(
            $this->service->buatJemputan(Siswa::findOrFail($d['nis']), $d['periode'], $d['nominal']),
            201,
        );
    }

    public function ubahNominal(Request $request, Tagihan $tagihan)
    {
        $nominal = $request->validate(['nominal' => 'required|integer|min:1'])['nominal'];

        $hasil = $this->service->ubahNominalJemputan($tagihan, $nominal);
        $request->session()->flash('success', 'Nominal jemputan berhasil diperbarui.');

        return $hasil;
    }
}
