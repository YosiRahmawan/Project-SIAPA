<?php

namespace App\Http\Controllers\Laporan;

use App\Enums\StatusPembayaran;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Siswa;
use App\Models\Tagihan;
use App\Services\AlertService;
use App\Services\LaporanService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LaporanController extends Controller
{
    public function __construct(private LaporanService $laporan) {}

    public function rekap(Request $request)
    {
        return $this->laporan->rekap($request->validate(['periode' => 'nullable|date_format:Y-m'])['periode'] ?? null);
    }

    /** Tagihan belum lunas yang sudah lewat jatuh tempo. */
    public function tunggakan()
    {
        $hari = today();

        return Tagihan::menunggak($hari)->with('siswa:nis,nama,kelas')->orderBy('jatuh_tempo')->paginate(50)
            ->through(fn (Tagihan $t) => $t->ringkas($hari) + ['nama' => $t->siswa->nama, 'kelas' => $t->siswa->kelas]);
    }

    /** Banner alert untuk dashboard Keuangan. */
    public function peringatan(AlertService $alert)
    {
        $hari = today();

        return $alert->peringatan($hari)->get()
            ->map(fn (Tagihan $t) => $t->ringkas($hari) + ['nama' => $t->siswa->nama]);
    }

    public function pembayaran(Request $request)
    {
        $f = $request->validate([
            'nis' => 'nullable|string',
            'status' => ['nullable', Rule::enum(StatusPembayaran::class)],
            'jenis' => 'nullable|in:spp,daftar_ulang,jemputan',
            'dari' => 'nullable|date',
            'sampai' => 'nullable|date',
            'format' => 'nullable|in:json,csv',
        ]);
        $query = $this->laporan->pembayaran($f);

        return ($f['format'] ?? 'json') === 'csv' ? $this->laporan->csv($query) : $query->paginate(50);
    }

    public function siswa(string $nis)
    {
        $siswa = Siswa::with('tagihan.pembayaran')->find($nis);

        return $siswa ?: response()->json(['message' => 'Data siswa tidak ditemukan'], 404);
    }

    /** Peringatan untuk Yayasan: pembayaran yang dibatalkan beserta alasannya. */
    public function pembatalan()
    {
        return AuditLog::where('aksi', 'pembayaran.batal')->with('pelaku:id,nama')->latest('waktu')->paginate(50);
    }
}
