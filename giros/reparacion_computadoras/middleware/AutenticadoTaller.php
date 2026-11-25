<?php

namespace Giros\ReparacionComputadoras\Middleware;

use Closure;
use Giros\ReparacionComputadoras\Models\UsuarioTaller;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AutenticadoTaller
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuarioId = $request->session()->get('taller_computadoras.usuario_id');

        if (! $usuarioId) {
            return redirect()->route('taller.computadoras.login');
        }

        $usuario = UsuarioTaller::query()
            ->where('id', $usuarioId)
            ->where('activo', true)
            ->first();

        if (! $usuario) {
            $request->session()->forget('taller_computadoras');
            return redirect()->route('taller.computadoras.login');
        }

        $request->attributes->set('usuario_taller', $usuario);

        return $next($request);
    }
}
