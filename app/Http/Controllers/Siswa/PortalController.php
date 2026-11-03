<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\Pembayaran;
use App\Models\Tagihan;
use App\Services\AlertService;
use Illuminate\Http\Request;

/** Semua query memakai scope milik(): siswa hanya bisa membaca baris dengan NIS miliknya. */
class PortalController extends Controller
{
    public function tagihan(Request $request)
    {
        $hari = today();

        return Tagihan::milik($request->user())->orderBy('jatuh_tempo')->get()
            ->map(fn (Tagihan $t) => $t->ringkas($hari));
    }

    public function riwayat(Request $request)
    {
        return Pembayaran::whereHas('tagihan', fn ($q) => $q->milik($request->user()))
            ->with('tagihan:id,jenis,periode')
            ->latest('tanggal')->latest('id')
            ->get(['id', 'tagihan_id', 'metode', 'jumlah', 'status', 'alasan', 'tanggal']);
    }

    public function peringatan(Request $request, AlertService $alert)
    {
        $hari = today();

        return $alert->peringatan($hari)->milik($request->user())->get()
            ->map(fn (Tagihan $t) => $t->ringkas($hari));
    }
}
