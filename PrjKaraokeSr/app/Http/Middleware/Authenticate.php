<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
/**
 * Middleware para autenticar usuarios.
 *
 * Este middleware verifica si el usuario está autenticado y redirige a la página de inicio de sesión si no lo está.
 */


class Authenticate extends Middleware
{
   protected function redirectTo($request)
   {
        
        // Verifica si el usuario está autenticado y si la solicitud no espera una respuesta JSON 
        // Si no está autenticado y no espera una respuesta JSON, redirige a la página de inicio de sesión
        if (!$request->expectsJson()) {
            return route('login');
    
        }

   }
}
