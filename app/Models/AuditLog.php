<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/** Hanya tambah, tidak pernah diubah atau dihapus. */
class AuditLog extends Model
{
    protected $table = 'audit_log';

    public $timestamps = false;

    protected $guarded = [];

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Audit log tidak boleh diubah.'));
        static::deleting(fn () => throw new LogicException('Audit log tidak boleh dihapus.'));
    }

    public function pelaku(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    protected function casts(): array
    {
        return ['sebelum' => 'array', 'sesudah' => 'array', 'waktu' => 'datetime'];
    }
}
