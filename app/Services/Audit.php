<?php

namespace App\Services;

use App\Models\AuditLog;

class Audit
{
    /** Catat satu aksi penting: siapa, kapan, dari IP mana, nilai sebelum dan sesudah. */
    public static function catat(string $aksi, string $tabel, string|int|null $barisId, ?array $sebelum = null, ?array $sesudah = null): void
    {
        AuditLog::create([
            'user_id' => auth()->id(),
            'aksi' => $aksi,
            'tabel' => $tabel,
            'baris_id' => $barisId === null ? null : (string) $barisId,
            'sebelum' => $sebelum,
            'sesudah' => $sesudah,
            'ip' => request()->ip(),
        ]);
    }
}
