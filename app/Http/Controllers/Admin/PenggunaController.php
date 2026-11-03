<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Audit;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class PenggunaController extends Controller
{
    /** Akun siswa dibuat lewat menu siswa, bukan di sini. */
    private const ROLE_STAF = ['admin', 'yayasan', 'keuangan'];

    public function index()
    {
        return User::orderBy('nama')->paginate(25);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nama' => 'required|string|max:100',
            'username' => 'required|string|max:50|alpha_dash|unique:users,username',
            'password' => ['required', Password::min(8)->letters()->numbers()],
            'role' => ['required', Rule::in(self::ROLE_STAF)],
        ]);
        $user = User::create([...$data, 'harus_ganti_password' => true]);
        Audit::catat('pengguna.buat', 'users', $user->id, null, $user->only('nama', 'username', 'role'));

        return response()->json($user, 201);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'nama' => 'sometimes|string|max:100',
            'role' => ['sometimes', Rule::in(self::ROLE_STAF)],
            'aktif' => 'sometimes|boolean',
            'password' => ['sometimes', Password::min(8)->letters()->numbers()],
        ]);
        if ($user->adalah(Role::Siswa)) {
            throw ValidationException::withMessages(['user' => 'Akun siswa dikelola lewat menu siswa.']);
        }
        if ($user->is($request->user()) && (($data['aktif'] ?? true) === false || ($data['role'] ?? 'admin') !== 'admin')) {
            throw ValidationException::withMessages(['user' => 'Anda tidak bisa menonaktifkan atau menurunkan akun sendiri.']);
        }
        $sebelum = $user->only('nama', 'role', 'aktif');
        if (isset($data['password'])) {
            $data['harus_ganti_password'] = true;
        }
        $user->update($data);
        Audit::catat('pengguna.ubah', 'users', $user->id, $sebelum, $user->only('nama', 'role', 'aktif'));

        return $user->fresh();
    }
}
