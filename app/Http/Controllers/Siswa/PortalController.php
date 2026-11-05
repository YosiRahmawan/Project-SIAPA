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

        $data = Tagihan::milik($request->user())->orderBy('jatuh_tempo')->get()
            ->map(fn (Tagihan $t) => $t->ringkas($hari));

        return $request->expectsJson() ? $data : view('portal.index', ['halaman' => 'tagihan', 'data' => $data]);
    }

    public function riwayat(Request $request)
    {
        $data = Pembayaran::whereHas('tagihan', fn ($q) => $q->milik($request->user()))
            ->with('tagihan:id,jenis,periode')
            ->latest('tanggal')->latest('id')
            ->get(['id', 'tagihan_id', 'metode', 'jumlah', 'status', 'alasan', 'tanggal']);

        return $request->expectsJson() ? $data : view('portal.index', ['halaman' => 'riwayat', 'data' => $data]);
    }

    public function peringatan(Request $request, AlertService $alert)
    {
        $hari = today();

        $data = $alert->peringatan($hari)->milik($request->user())->get()
            ->map(fn (Tagihan $t) => $t->ringkas($hari));

        return $request->expectsJson() ? $data : view('portal.index', ['halaman' => 'peringatan', 'data' => $data]);
    }
}
