<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

// Importar la clase View para compartir datos con las vistas
use Illuminate\Support\Facades\View;
// Importar la clase Auth para la autenticación
use Illuminate\Support\Facades\Auth;

use App\Services\ApiConsultaService;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
        $this->app->singleton(ApiConsultaService::class, function ($app) {
            return new ApiConsultaService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Aquí puedes registrar cualquier servicio o configuración adicional que necesites
        // Por ejemplo, puedes registrar un servicio de autenticación personalizado o configurar la base de datos
        // Puedes usar el contenedor de servicios para resolver dependencias y configuraciones

        // Compartir la variable 'user' con todas las vistas
        // Esto es útil para mostrar información del usuario autenticado en la vista
        // o para realizar verificaciones de permisos
        View::composer('*', function ($view) {
            $view->with('user', Auth::guard('gusers')->user());
        });


    }
}
