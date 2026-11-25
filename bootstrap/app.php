<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
            ->withRouting(
            web: __DIR__.'/../routes/web.php',
            commands: __DIR__.'/../routes/console.php',
            health: '/up',
            then: function () {
                foreach (glob(base_path('giros/*/routes/web.php')) as $giroRouteFile) {
                    require $giroRouteFile;
                }
            },
        )
        ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            \App\Http\Middleware\ConfigurarConexionGiroSesion::class,
        ]);
        $middleware->prependToPriorityList(
            \Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests::class,
            \App\Http\Middleware\ConfigurarConexionGiroSesion::class
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
