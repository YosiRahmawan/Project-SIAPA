<?php

namespace App\Http\Controllers;

use App\Services\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function form()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate(['username' => 'required|string', 'password' => 'required|string']);

        if (! Auth::attempt([...$data, 'aktif' => true])) {
            Audit::catat('login.gagal', 'users', null, null, ['username' => $data['username']]);
            throw ValidationException::withMessages(['username' => 'Username atau password salah.']);
        }
        $request->session()->regenerate();
        Audit::catat('login', 'users', Auth::id());

        return redirect()->intended($request->user()->harus_ganti_password ? '/ganti-password' : $request->user()->role->beranda());
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }

    public function formGantiPassword()
    {
        return view('auth.ganti-password');
    }

    public function gantiPassword(Request $request)
    {
        $data = $request->validate([
            'password_lama' => 'required|current_password',
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers(), 'different:password_lama'],
        ]);
        $request->user()->update(['password' => $data['password'], 'harus_ganti_password' => false]);
        Audit::catat('password.ganti', 'users', $request->user()->id);

        return redirect($request->user()->role->beranda());
    }
}
