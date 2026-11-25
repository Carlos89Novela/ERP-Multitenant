<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class ConfigurarConexionGiroSesion
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->hasSession() && $request->session()->has('giro_conexion_activa')) {
            $connActiva = (string) $request->session()->get('giro_conexion_activa');
            if ($connActiva !== '' && config()->has("database.connections.{$connActiva}")) {
                Config::set('database.connections.usuario_giro_runtime', config("database.connections.{$connActiva}"));
                DB::purge('usuario_giro_runtime');
            }
        }

        return $next($request);
    }
}
