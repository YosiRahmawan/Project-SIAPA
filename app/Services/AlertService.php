<?php

namespace App\Services;

use App\Models\Tagihan;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

/**
 * Peringatan dihitung saat dibaca dari tanggal hari ini, jadi tidak ada status yang basi
 * dan tidak perlu job terjadwal. Jendela waktunya ada di config/siapa.php.
 */
class AlertService
{
    /** Tagihan belum lunas yang jatuh temponya sudah dekat atau sudah lewat. */
    public function peringatan(?CarbonInterface $hari = null): Builder
    {
        $hari ??= today();

        return Tagihan::with('siswa')
            ->where('status', Tagihan::BELUM)
            ->where(function (Builder $q) use ($hari) {
                foreach (config('siapa.hari_peringatan') as $jenis => $hariSebelum) {
                    $q->orWhere(fn (Builder $w) => $w
                        ->where('jenis', $jenis)
                        ->whereDate('jatuh_tempo', '<=', $hari->copy()->addDays($hariSebelum)));
                }
            })
            ->orderBy('jatuh_tempo');
    }
}
