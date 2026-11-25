<?php

namespace App\Providers;

use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public static function aplicarConexionGiroDesdeSesion(): void
    {
        if (session()->has('giro_conexion_activa')) {
            $connActiva = (string) session('giro_conexion_activa');
            if ($connActiva !== '' && config()->has("database.connections.{$connActiva}")) {
                Config::set('database.connections.usuario_giro_runtime', config("database.connections.{$connActiva}"));
                DB::purge('usuario_giro_runtime');
            }
        }
    }

    public function register(): void
    {
        if (file_exists(app_path('Models/UsuarioGiro.php'))) {
            require_once app_path('Models/UsuarioGiro.php');
        }
    }

    public function boot(): void
    {
        foreach (glob(base_path('giros/*/controllers/*.php')) as $controllerFile) {
            require_once $controllerFile;
        }

        foreach (glob(base_path('giros/*/routes/web.php')) as $giroRouteFile) {
            require_once $giroRouteFile;
        }

        // Aplicar conexion de giro en cada resolucion del contenedor para que Auth::guard('empresa') la tenga lista
        $this->app->resolving(function ($object) {
            static::aplicarConexionGiroDesdeSesion();
        });

        Authenticate::redirectUsing(function ($request) {
            $slugSesion = (string) session('empresa_slug_activa', '');
            if ($slugSesion !== '') {
                return route('empresa.login', ['slug' => $slugSesion]);
            }

            return route('login');
        });
    }
}
