<?php

namespace App\Http\Controllers\Keuangan;

use App\Enums\Metode;
use App\Http\Controllers\Controller;
use App\Models\Pembayaran;
use App\Models\Siswa;
use App\Models\Tagihan;
use App\Services\PembayaranService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PembayaranController extends Controller
{
    public function __construct(private PembayaranService $service) {}

    /** Langkah 1 dan 2: cari siswa dengan NIS, tampilkan info dan tagihannya. */
    public function cari(string $nis)
    {
        $siswa = Siswa::find($nis);
        if (! $siswa) {
            return response()->json(['message' => 'Data siswa tidak ditemukan'], 404);
        }
        $hari = today();

        return [
            'siswa' => $siswa,
            'tagihan' => $siswa->tagihan()->orderByRaw("status = 'lunas'")->orderBy('jatuh_tempo')->get()
                ->map(fn (Tagihan $t) => $t->ringkas($hari)),
        ];
    }

    /** Langkah 3: catat pembayaran manual, atau ajukan bukti QRIS untuk diperiksa. */
    public function store(Request $request, Tagihan $tagihan)
    {
        $data = $request->validate([
            'metode' => ['required', Rule::enum(Metode::class)],
            'jumlah' => 'required|integer|min:1',
            'bukti' => ['required_if:metode,qris', 'nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:'.config('siapa.maks_bukti_kb')],
        ]);
        $petugas = $request->user();

        $p = $data['metode'] === Metode::Qris->value
            ? $this->service->ajukanQris($tagihan, $data['jumlah'], $request->file('bukti'), $petugas)
            : $this->service->catatManual($tagihan, $data['jumlah'], $petugas);

        return response()->json($p->makeHidden('bukti'), 201);
    }

    public function putuskan(Request $request, Pembayaran $pembayaran)
    {
        $data = $request->validate(['terima' => 'required|boolean', 'alasan' => 'required_if:terima,false|nullable|string|max:500']);

        return $this->service->putuskan($pembayaran, (bool) $data['terima'], $request->user(), $data['alasan'] ?? null)->makeHidden('bukti');
    }

    public function batal(Request $request, Pembayaran $pembayaran)
    {
        $alasan = $request->validate(['alasan' => 'required|string|max:500'])['alasan'];

        return $this->service->batalkan($pembayaran, $alasan, $request->user())->makeHidden('bukti');
    }
}
