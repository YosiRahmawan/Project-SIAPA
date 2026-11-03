<?php

namespace App\Models;

use App\Enums\Metode;
use App\Enums\StatusPembayaran;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

#[Fillable(['tagihan_id', 'metode', 'jumlah', 'bukti', 'status', 'alasan', 'dicatat_oleh', 'tanggal'])]
class Pembayaran extends Model
{
    protected $table = 'pembayaran';

    /** Hanya dua kolom ini yang boleh berubah setelah dibuat. */
    private const BOLEH_BERUBAH = ['status', 'alasan', 'updated_at'];

    protected static function booted(): void
    {
        static::updating(function (self $m) {
            foreach (array_keys($m->getDirty()) as $kolom) {
                if (! in_array($kolom, self::BOLEH_BERUBAH, true)) {
                    throw new LogicException("Kolom {$kolom} pada pembayaran tidak boleh diubah.");
                }
            }
        });
        static::deleting(fn () => throw new LogicException('Pembayaran tidak boleh dihapus, batalkan dengan alasan.'));
    }

    protected function casts(): array
    {
        return [
            'status' => StatusPembayaran::class,
            'metode' => Metode::class,
            'jumlah' => 'integer',
            'tanggal' => 'date:Y-m-d',
        ];
    }

    public function tagihan(): BelongsTo
    {
        return $this->belongsTo(Tagihan::class);
    }

    public function pencatat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dicatat_oleh');
    }
}
