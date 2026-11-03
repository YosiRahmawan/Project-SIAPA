<?php

namespace App\Services;

use App\Enums\StatusPembayaran;
use App\Models\Pembayaran;
use App\Models\Tagihan;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LaporanService
{
    /** Rekap per jenis tagihan: jumlah tagihan, total nominal, yang sudah diterima, dan sisanya. */
    public function rekap(?string $periode = null): array
    {
        $tagihan = Tagihan::query()
            ->when($periode, fn ($q) => $q->where('periode', $periode))
            ->selectRaw('jenis, count(*) as jumlah_tagihan, sum(nominal) as total_nominal')
            ->groupBy('jenis')->get();

        $diterima = Pembayaran::query()
            ->join('tagihan', 'tagihan.id', '=', 'pembayaran.tagihan_id')
            ->where('pembayaran.status', StatusPembayaran::Diterima)
            ->when($periode, fn ($q) => $q->where('tagihan.periode', $periode))
            ->selectRaw('tagihan.jenis as jenis, sum(pembayaran.jumlah) as total')
            ->groupBy('tagihan.jenis')->pluck('total', 'jenis');

        $baris = $tagihan->map(function ($r) use ($diterima) {
            $total = (int) $r->total_nominal;
            $masuk = (int) ($diterima[$r->jenis->value] ?? 0);

            return [
                'jenis' => $r->jenis->value,
                'jumlah_tagihan' => (int) $r->jumlah_tagihan,
                'total_nominal' => $total,
                'diterima' => $masuk,
                'sisa' => $total - $masuk,
            ];
        })->values();

        return [
            'periode' => $periode,
            'per_jenis' => $baris,
            'total' => [
                'total_nominal' => $baris->sum('total_nominal'),
                'diterima' => $baris->sum('diterima'),
                'sisa' => $baris->sum('sisa'),
            ],
        ];
    }

    /** Pembayaran dengan filter nis, status, jenis, dan rentang tanggal. */
    public function pembayaran(array $f): Builder
    {
        return Pembayaran::query()
            ->with(['tagihan.siswa', 'pencatat:id,nama'])
            ->when($f['nis'] ?? null, fn ($q, $v) => $q->whereHas('tagihan', fn ($t) => $t->where('nis', $v)))
            ->when($f['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($f['jenis'] ?? null, fn ($q, $v) => $q->whereHas('tagihan', fn ($t) => $t->where('jenis', $v)))
            ->when($f['dari'] ?? null, fn ($q, $v) => $q->whereDate('tanggal', '>=', $v))
            ->when($f['sampai'] ?? null, fn ($q, $v) => $q->whereDate('tanggal', '<=', $v))
            ->latest('tanggal')->latest('id');
    }

    public function csv(Builder $query): StreamedResponse
    {
        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['tanggal', 'nis', 'nama', 'jenis', 'periode', 'metode', 'jumlah', 'status', 'alasan']);
            foreach ($query->lazyById(500, 'pembayaran.id', 'id') as $p) {
                fputcsv($out, [
                    $p->tanggal->toDateString(), $p->tagihan->nis, $p->tagihan->siswa->nama,
                    $p->tagihan->jenis->value, $p->tagihan->periode, $p->metode->value,
                    $p->jumlah, $p->status->value, self::aman($p->alasan),
                ]);
            }
            fclose($out);
        }, 'pembayaran.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Cegah rumus di Excel (=, +, -, @) dari teks yang diketik pengguna. */
    private static function aman(?string $teks): string
    {
        $teks ??= '';

        return $teks !== '' && str_contains('=+-@', $teks[0]) ? "'".$teks : $teks;
    }
}
