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

        $resultado = $servicio->crearSucursalConAlmacen($validado);

        return back()->with(
            'success',
            "Sucursal {$resultado['sucursal']->nombre} creada con su almacén {$resultado['almacen']->nombre}."
        );
    }

    /**
     * @return array{sucursales: \Illuminate\Database\Eloquent\Collection, almacenes: \Illuminate\Database\Eloquent\Collection}
     */
    public function catalogosParaVista(GestionAlmacenesTallerService $servicio): array
    {
        $servicio->asegurarAlmacenTransito();

        return [
            'sucursales' => SucursalTaller::query()->with('almacenes')->orderByDesc('es_matriz')->orderBy('id')->get(),
            'almacenes' => AlmacenTaller::query()->with('sucursal')->orderBy('es_virtual')->orderBy('id')->get(),
        ];
    }
}