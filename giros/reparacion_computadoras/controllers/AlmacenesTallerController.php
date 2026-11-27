<?php

namespace Giros\ReparacionComputadoras\Controllers;

use Giros\ReparacionComputadoras\Models\AlmacenTaller;
use Giros\ReparacionComputadoras\Models\SucursalTaller;
use Giros\ReparacionComputadoras\Models\UsuarioTaller;
use Giros\ReparacionComputadoras\Services\ContextoPropietarioTaller;
use Giros\ReparacionComputadoras\Services\GestionAlmacenesTallerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AlmacenesTallerController
{
    public function storeSucursal(Request $request, GestionAlmacenesTallerService $servicio): RedirectResponse
    {
        /** @var UsuarioTaller|null $usuario */
        $usuario = $request->attributes->get('usuario_taller');
        ContextoPropietarioTaller::validarPropietario($usuario);

        $validado = $request->validate([
            'codigo' => ['nullable', 'string', 'max:40'],
            'nombre' => ['required', 'string', 'max:150'],
            'es_matriz' => ['nullable', 'boolean'],
        ]);

        $validado['empresa_id'] = (int) ($usuario?->empresa_id ?? 1);
        $resultado = $servicio->crearSucursalConAlmacen($validado);

        $resumenUsuarios = collect($resultado['usuarios_creados'] ?? [])
            ->map(fn ($u) => "{$u['rol']}: {$u['email']} (clave: {$u['password_temporal']})")
            ->implode(' | ');

        return back()
            ->with('success', "Sucursal {$resultado['sucursal']->nombre} creada con su almacén {$resultado['almacen']->nombre} y usuarios base ({$resumenUsuarios}).")
            ->with('usuarios_sucursal_creados', $resultado['usuarios_creados'] ?? []);
    }

    /**
     * @return array{sucursales: \Illuminate\Database\Eloquent\Collection, almacenes: \Illuminate\Database\Eloquent\Collection}
     */
    public function catalogosParaVista(GestionAlmacenesTallerService $servicio, ?UsuarioTaller $actor = null): array
    {
        $servicio->asegurarAlmacenTransito();

        $esGlobal = $actor === null || $actor->esPropietarioGlobal();
        $sucursalId = $actor?->sucursalAsignadaId();

        $sucursalesQuery = SucursalTaller::query()->with('almacenes')->orderByDesc('es_matriz')->orderBy('id');
        $almacenesQuery = AlmacenTaller::query()->with('sucursal')->orderBy('es_virtual')->orderBy('id');

        if (! $esGlobal) {
            if ($sucursalId !== null) {
                $sucursalesQuery->where('id', $sucursalId);
                $almacenesQuery->where(function ($q) use ($sucursalId): void {
                    $q->where('sucursal_id', $sucursalId)
                        ->orWhere('es_virtual', true);
                });
            } else {
                $sucursalesQuery->whereRaw('1 = 0');
                $almacenesQuery->where('es_virtual', true);
            }
        }

        return [
            'sucursales' => $sucursalesQuery->get(),
            'almacenes' => $almacenesQuery->get(),
        ];
    }
}