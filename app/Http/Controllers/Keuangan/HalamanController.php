<?php

namespace App\Http\Controllers\Keuangan;

use App\Enums\StatusPembayaran;
use App\Http\Controllers\Controller;
use App\Models\Pembayaran;
use App\Models\Siswa;
use App\Models\Tagihan;
use App\Services\LaporanService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class HalamanController extends Controller
{
    public function index(Request $request, string $halaman = 'dashboard')
    {
        $filter = $request->validate([
            'q' => 'nullable|string|max:100', 'kelas' => 'nullable|integer|between:1,6',
            'jenis' => 'nullable|in:spp,daftar_ulang,jemputan', 'periode' => 'nullable|date_format:Y-m',
            'status' => 'nullable|in:belum,lunas,menunggu,diterima,ditolak,dibatalkan',
            'pilih' => 'nullable|integer', 'nis' => 'nullable|string|max:30',
        ]);
        $periode = $filter['periode'] ?? today()->format('Y-m');
        $laporan = app(LaporanService::class);
        $rekap = $laporan->rekap($periode);
        $pembayaran = Pembayaran::with('tagihan.siswa')->latest('tanggal')->latest('id');
        $tagihan = Tagihan::with('siswa')->withExists('pembayaran')->withSum(['pembayaran as dibayar_total' => fn ($q) => $q->where('status', StatusPembayaran::Diterima)], 'jumlah')
            ->when($filter['q'] ?? null, fn ($q, $v) => $q->whereHas('siswa', fn ($s) => $s->where(fn ($s) => $s->where('nama', 'like', "%{$v}%")->orWhere('nis', 'like', "%{$v}%"))))
            ->when($filter['kelas'] ?? null, fn ($q, $v) => $q->whereHas('siswa', fn ($s) => $s->where('kelas', $v)))
            ->when($filter['jenis'] ?? null, fn ($q, $v) => $q->where('jenis', $v));
        $baris = null;
        $detail = null;
        if (in_array($halaman, ['tagihan', 'tunggakan'])) {
            $baris = $tagihan->when($halaman === 'tunggakan', fn ($q) => $q->menunggak(today()))
                ->when($filter['periode'] ?? null, fn ($q, $v) => $q->where('periode', $v))
                ->when(in_array($filter['status'] ?? '', ['belum', 'lunas']), fn ($q) => $q->where('status', $filter['status']))
                ->orderBy('jatuh_tempo')->paginate(15)->withQueryString();
        } elseif ($halaman === 'data-siswa') {
            $baris = Siswa::query()->when($filter['q'] ?? null, fn ($q, $v) => $q->where(fn ($q) => $q->where('nama', 'like', "%{$v}%")->orWhere('nis', 'like', "%{$v}%")))
                ->when($filter['kelas'] ?? null, fn ($q, $v) => $q->where('kelas', $v))->orderBy('nama')->paginate(20)->withQueryString();
        } elseif ($halaman === 'verifikasi') {
            $baris = (clone $pembayaran)->where('status', StatusPembayaran::Menunggu)->paginate(15)->withQueryString();
            $detail = isset($filter['pilih']) ? (clone $pembayaran)->where('status', StatusPembayaran::Menunggu)->findOrFail($filter['pilih']) : $baris->first();
        } elseif (in_array($halaman, ['laporan', 'penerimaan'])) {
            $baris = $laporan->pembayaran([
                'nis' => $filter['nis'] ?? null, 'jenis' => $filter['jenis'] ?? null,
                'status' => $halaman === 'penerimaan' ? 'diterima' : (in_array($filter['status'] ?? '', ['menunggu', 'diterima', 'ditolak', 'dibatalkan']) ? $filter['status'] : null),
                'dari' => $periode.'-01', 'sampai' => CarbonImmutable::parse($periode.'-01')->endOfMonth()->toDateString(),
            ])->paginate(20)->withQueryString();
        }
        $bulanan = collect(range(5, 0))->map(function ($n) {
            $bulan = today()->startOfMonth()->subMonths($n);

            return ['label' => $bulan->locale('id')->translatedFormat('M'), 'jumlah' => (int) Pembayaran::where('status', StatusPembayaran::Diterima)->whereBetween('tanggal', [$bulan->toDateString(), $bulan->copy()->endOfMonth()->toDateString()])->sum('jumlah')];
        });
        $metode = Pembayaran::where('status', StatusPembayaran::Diterima)->whereHas('tagihan', fn ($q) => $q->where('periode', $periode))->selectRaw('metode, sum(jumlah) as total')->groupBy('metode')->pluck('total', 'metode');

        return view('keuangan.index', compact('halaman', 'baris', 'detail', 'periode', 'rekap', 'bulanan', 'metode') + [
            'terbaru' => (clone $pembayaran)->limit(5)->get(),
            'menunggu' => Pembayaran::where('status', StatusPembayaran::Menunggu)->count(),
            'jatuhTempo' => Tagihan::where('status', Tagihan::BELUM)->whereDate('jatuh_tempo', '>=', today())->orderBy('jatuh_tempo')->first(),
        ]);
    }

    public function bukti(Pembayaran $pembayaran)
    {
        abort_unless($pembayaran->bukti, 404);
        $disk = Storage::disk(config('siapa.disk_bukti'));
        abort_unless($disk->exists($pembayaran->bukti), 404);

        return $disk->response($pembayaran->bukti, 'bukti-pembayaran.'.pathinfo($pembayaran->bukti, PATHINFO_EXTENSION), ['X-Content-Type-Options' => 'nosniff']);
    }

    public function kuitansi(Pembayaran $pembayaran)
    {
        abort_unless($pembayaran->status === StatusPembayaran::Diterima, 404);
        $pembayaran->load('tagihan.siswa', 'pencatat');

        return view('keuangan.kuitansi', compact('pembayaran'));
    }
}
