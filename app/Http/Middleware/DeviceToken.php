<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Memvalidasi header X-Device-Token terhadap config('pilahku.device_tokens'). */
class DeviceToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = (string) $request->header('X-Device-Token', '');

        $valid = $token !== '' && collect(config('pilahku.device_tokens'))
            ->contains(fn (string $registered) => hash_equals($registered, $token));

        if (! $valid) {
            return response()->json(['message' => 'Token perangkat tidak valid.'], 401);
        }

        return $next($request);
    }
}
