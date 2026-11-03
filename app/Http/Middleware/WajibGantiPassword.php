<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Akun dengan password awal harus menggantinya dulu sebelum memakai fitur lain. */
class WajibGantiPassword
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->harus_ganti_password && ! $request->routeIs('ganti-password*', 'logout')) {
            abort_if($request->expectsJson(), 403, 'Ganti password awal Anda terlebih dahulu.');

            return redirect()->route('ganti-password');
        }

        return $next($request);
    }
}
