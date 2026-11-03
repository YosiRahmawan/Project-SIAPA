<?php

namespace App\Models;

use App\Enums\JenisTagihan;
use App\Enums\Role;
use App\Enums\StatusPembayaran;
use Carbon\CarbonInterface;
use Database\Factories\TagihanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nis', 'jenis', 'periode', 'nominal', 'jatuh_tempo', 'status'])]
class Tagihan extends Model
{
    /** @use HasFactory<TagihanFactory> */
    use HasFactory;

    public const BELUM = 'belum';

    public const LUNAS = 'lunas';

    protected $table = 'tagihan';

    protected function casts(): array
    {
        return [
            'jenis' => JenisTagihan::class,
            'jatuh_tempo' => 'date:Y-m-d',
            'nominal' => 'integer',
        ];
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'nis', 'nis');
    }

    public function pembayaran(): HasMany
    {
        return $this->hasMany(Pembayaran::class);
    }

    /** Jumlah pembayaran yang sudah diterima. */
    public function dibayar(): int
    {
        return (int) $this->pembayaran()->where('status', StatusPembayaran::Diterima)->sum('jumlah');
    }

    /** Jumlah yang masih bisa dibayar: nominal dikurangi yang diterima dan yang menunggu verifikasi. */
    public function sisa(): int
    {
        $terpakai = (int) $this->pembayaran()
            ->whereIn('status', [StatusPembayaran::Diterima, StatusPembayaran::Menunggu])
            ->sum('jumlah');

        return max(0, $this->nominal - $terpakai);
    }

    public function lunas(): bool
    {
        return $this->status === self::LUNAS;
    }

    /** Siswa hanya melihat tagihan miliknya, peran lain melihat semua. */
    public function scopeMilik(Builder $q, User $user): Builder
    {
        if (! $user->adalah(Role::Siswa)) {
            return $q;
        }

        return $q->whereIn('nis', Siswa::where('user_id', $user->id)->select('nis'));
    }

    /** Belum lunas dan sudah lewat jatuh tempo. */
    public function scopeMenunggak(Builder $q, CarbonInterface $hari): Builder
    {
        return $q->where('status', self::BELUM)->whereDate('jatuh_tempo', '<', $hari);
    }

    /** lunas | menunggu_verifikasi | menunggak | hampir_jatuh_tempo | belum_bayar */
    public function statusTampil(CarbonInterface $hari): string
    {
        if ($this->lunas()) {
            return 'lunas';
        }
        if ($this->pembayaran()->where('status', StatusPembayaran::Menunggu)->exists()) {
            return 'menunggu_verifikasi';
        }
        if ($hari->greaterThan($this->jatuh_tempo)) {
            return 'menunggak';
        }
        $n = (int) config('siapa.hari_peringatan.'.$this->jenis->value, 0);

        return $hari->greaterThanOrEqualTo($this->jatuh_tempo->copy()->subDays($n))
            ? 'hampir_jatuh_tempo'
            : 'belum_bayar';
    }

    public function ringkas(CarbonInterface $hari): array
    {
        return [
            'id' => $this->id,
            'nis' => $this->nis,
            'jenis' => $this->jenis->value,
            'periode' => $this->periode,
            'nominal' => $this->nominal,
            'dibayar' => $this->dibayar(),
            'jatuh_tempo' => $this->jatuh_tempo->toDateString(),
            'status' => $this->status,
            'status_tampil' => $this->statusTampil($hari),
        ];
    }
}
