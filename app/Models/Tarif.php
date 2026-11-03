<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['jenis', 'kelas', 'nominal'])]
class Tarif extends Model
{
    protected $table = 'tarif';

    public static function nominal(string $jenis, int $kelas): ?int
    {
        $n = static::where('jenis', $jenis)->where('kelas', $kelas)->value('nominal');

        return $n === null ? null : (int) $n;
    }
}
