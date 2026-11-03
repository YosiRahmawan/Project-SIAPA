<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Pemakaian di route: ->middleware('role:keuangan,yayasan'). Diperiksa di server, bukan hanya menyembunyikan menu. */
class BatasiRole
{
    public function handle(Request $request, Closure $next, string ...$role): Response
    {
        $boleh = array_map(fn (string $r) => Role::from($r), $role);
        abort_unless($request->user()?->adalah(...$boleh), 403, 'Anda tidak berhak mengakses halaman ini.');

        return $next($request);
    }
}
