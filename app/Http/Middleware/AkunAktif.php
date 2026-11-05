<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AkunAktif
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user && (! $user->aktif || ($user->adalah(Role::Siswa) && $user->siswa?->aktif === false))) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Akun dinonaktifkan. Hubungi admin sekolah.'], 401);
            }

            return redirect('/login')->withErrors(['username' => 'Akun dinonaktifkan. Hubungi admin sekolah.']);
        }

        return $next($request);
    }
}
