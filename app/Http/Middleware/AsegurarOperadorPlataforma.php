<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AsegurarOperadorPlataforma
{
    public function handle(Request $request, Closure $next): Response
    {
        // Si aun no hay ningun operador registrado, se permite el acceso inicial para configurar el sistema
        $existeOperador = User::query()->where('es_operador_plataforma', true)->exists();
        if (! $existeOperador) {
            return $next($request);
        }

        $user = $request->user();
        if (! $user || ! $user->esOperadorPlataforma()) {
            abort(403, 'Acceso restringido al operador de la plataforma.');
        }

        return $next($request);
    }
}
