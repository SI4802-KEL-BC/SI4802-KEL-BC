<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Pemakaian: ->middleware('role:admin'). Perbandingan tidak peka huruf besar/kecil. */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $peran = strtolower((string) $request->user()?->peran);

        if (! in_array($peran, array_map('strtolower', $roles), true)) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        return $next($request);
    }
}
