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
        $data = $this->laporan->rekap($request->validate(['periode' => 'nullable|date_format:Y-m'])['periode'] ?? null);

        return $request->expectsJson() ? $data : view('portal.index', ['halaman' => 'rekap', 'data' => $data]);
    }

    /** Tagihan belum lunas yang sudah lewat jatuh tempo. */
    public function tunggakan(Request $request)
    {
        $hari = today();

        $data = Tagihan::menunggak($hari)->with('siswa:nis,nama,kelas')->orderBy('jatuh_tempo')->paginate(50)
            ->through(fn (Tagihan $t) => $t->ringkas($hari) + ['nama' => $t->siswa->nama, 'kelas' => $t->siswa->kelas]);

        return $request->expectsJson() ? $data : view('portal.index', ['halaman' => 'tunggakan', 'data' => $data]);
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

        if (($f['format'] ?? 'json') === 'csv') {
            return $this->laporan->csv($query);
        }
        $data = $query->paginate(50)->withQueryString();

        return $request->expectsJson() || ($f['format'] ?? null) === 'json' ? $data : view('portal.index', ['halaman' => 'pembayaran', 'data' => $data]);
    }

    public function siswa(Request $request, string $nis)
    {
        $siswa = Siswa::with('tagihan.pembayaran')->find($nis);

        if (! $siswa) {
            abort(404, 'Data siswa tidak ditemukan');
        }

        return $request->expectsJson() ? $siswa : view('portal.index', ['halaman' => 'detail-siswa', 'data' => $siswa]);
    }

    /** Peringatan untuk Yayasan: pembayaran yang dibatalkan beserta alasannya. */
    public function pembatalan(Request $request)
    {
        $data = AuditLog::where('aksi', 'pembayaran.batal')->with('pelaku:id,nama')->latest('waktu')->paginate(50);

        return $request->expectsJson() ? $data : view('portal.index', ['halaman' => 'pembatalan', 'data' => $data]);
    }
}
