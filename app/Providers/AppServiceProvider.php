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
        if (file_exists(app_path('Models/UsuarioGiro.php'))) {
            require_once app_path('Models/UsuarioGiro.php');
        }

        foreach (glob(base_path('giros/*/controllers/*.php')) as $controllerFile) {
            require_once $controllerFile;
        }

        //
    }
}
