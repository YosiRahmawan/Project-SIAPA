<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Siswa;
use App\Services\AkunSiswaService;
use App\Services\Audit;
use Illuminate\Http\Request;

class SiswaController extends Controller
{
    public function index(Request $request)
    {
        $data = Siswa::query()
            ->when($request->query('q'), fn ($q, $v) => $q->where(fn ($w) => $w->where('nis', 'like', "{$v}%")->orWhere('nama', 'like', "%{$v}%")))
            ->orderBy('kelas')->orderBy('nama')->paginate(25)->withQueryString();

        return $request->expectsJson() ? $data : view('portal.index', ['halaman' => 'siswa', 'data' => $data]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nis' => 'required|digits_between:4,20|unique:siswa,nis', // NIS unik
            'nama' => 'required|string|max:100',
            'kelas' => 'required|integer|between:1,6',
        ]);
        $siswa = Siswa::create($data);
        Audit::catat('siswa.buat', 'siswa', $siswa->nis, null, $siswa->only('nis', 'nama', 'kelas'));

        return response()->json($siswa, 201);
    }

    /** NIS tidak bisa diubah karena jadi kunci tagihan. */
    public function update(Request $request, Siswa $siswa)
    {
        $data = $request->validate([
            'nama' => 'sometimes|string|max:100',
            'kelas' => 'sometimes|integer|between:1,6',
            'aktif' => 'sometimes|boolean',
        ]);
        $sebelum = $siswa->only('nama', 'kelas', 'aktif');
        $siswa->update($data);
        Audit::catat('siswa.ubah', 'siswa', $siswa->nis, $sebelum, $siswa->only('nama', 'kelas', 'aktif'));

        return $siswa;
    }

    public function buatAkun(Siswa $siswa, AkunSiswaService $akun)
    {
        [$user, $password] = $akun->buat($siswa);

        return response()->json([
            'username' => $user->username,
            'password_awal' => $password, // ditampilkan sekali, wajib diganti saat login pertama
        ], 201);
    }
}
