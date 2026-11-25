<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        foreach (glob(base_path('giros/*/routes/web.php')) as $giroRouteFile) {
            require_once $giroRouteFile;
        }

        if (session()->has('giro_conexion_activa')) {
            $connActiva = (string) session('giro_conexion_activa');
            \Illuminate\Support\Facades\Config::set('database.connections.usuario_giro_runtime', config("database.connections.{$connActiva}"));
            \Illuminate\Support\Facades\DB::purge('usuario_giro_runtime');
        }

        if (file_exists(app_path('Models/UsuarioGiro.php'))) {
            require_once app_path('Models/UsuarioGiro.php');
        }

        foreach (glob(base_path('giros/*/controllers/*.php')) as $controllerFile) {
            require_once $controllerFile;
        }

        //
    }
}
