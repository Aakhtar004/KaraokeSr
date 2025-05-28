<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// Importar el Auth facade para la autenticación
use Illuminate\Support\Facades\Auth;

class CheckTypeUser
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $tipouser): Response
    {
        // Verifica si el usuario está autenticado y existe un tipo de usuario valido
        if (!Auth::guard('gusers')->check() || Auth::guard('gusers')->user()->rol != $tipouser) {
            abort(403, 'No tienes permiso para acceder a esta sección.');
        }

        return $next($request);
    }
}
