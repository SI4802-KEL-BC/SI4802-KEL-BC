<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Unauthenticated.'], 401)
                : redirect('/')->with('error', 'Silakan masuk untuk mengelola jadwal.');
        }

        if ($user->role !== 'admin') {
            return $request->expectsJson()
                ? response()->json(['message' => 'Akses hanya untuk admin.'], 403)
                : abort(403, 'Akses hanya untuk admin.');
        }

        return $next($request);
    }
}
