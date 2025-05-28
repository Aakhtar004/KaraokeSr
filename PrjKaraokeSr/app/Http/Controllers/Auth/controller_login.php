<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\usuarios;
use Illuminate\Support\Facades\Auth;  // <--- Importa Auth aquí


class controller_login extends Controller
{
    //
    public function showLoginForm()
    {
        
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->only('usuario', 'contrasena');

        $user = usuarios::where('usuario', $credentials['usuario'])->first();

        if ($user && password_verify($credentials['contrasena'], $user->contrasena)) {
            Auth::guard('gusers')->login($user);

            // Siempre vamos a 'dashboard', sin importar intentos previos
            return redirect()->route('vista.user_menu');
        }

        return back()->withErrors([
            'usuario' => 'Las credenciales no coinciden con nuestros registros.',
        ]);
    }

    public function logout(Request $request)
    {
        Auth::guard('gusers')->logout();
        // Invalida la sesión y regenera el token CSRF
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('login');
    }
}
