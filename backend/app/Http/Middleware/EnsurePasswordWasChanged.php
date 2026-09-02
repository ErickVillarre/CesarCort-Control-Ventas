<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordWasChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->must_change_password) {
            return response()->json([
                'message' => 'Debes cambiar tu contrasena antes de continuar.',
                'must_change_password' => true,
            ], 423);
        }

        return $next($request);
    }
}
