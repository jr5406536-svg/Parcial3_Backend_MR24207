<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * @param  string  ...$roles  nombres de rol permitidos, por ejemplo administrador
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $nombre = $request->user()?->role?->nombre;

        if (! $nombre || ! in_array($nombre, $roles, true)) {
            return response()->json([
                'message' => 'No tiene permiso para esta acción.',
            ], 403);
        }

        return $next($request);
    }
}
