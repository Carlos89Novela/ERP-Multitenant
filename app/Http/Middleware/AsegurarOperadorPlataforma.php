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
        $existeOperador = User::query()->where('es_operador_plataforma', true)->exists();
        if (! $existeOperador) {
            return $next($request);
        }

        $user = $request->user();
        if (! $user) {
            return redirect()->guest(route('login'));
        }

        if (! $user->esOperadorPlataforma()) {
            abort(403, 'Acceso restringido al operador de la plataforma.');
        }

        return $next($request);
    }
}
