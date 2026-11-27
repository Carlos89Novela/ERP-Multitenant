<?php

namespace Giros\ReparacionComputadoras\Controllers;

use Giros\ReparacionComputadoras\Models\SucursalTaller;
use Giros\ReparacionComputadoras\Models\UsuarioTaller;
use Giros\ReparacionComputadoras\Services\ContextoPropietarioTaller;
use Giros\ReparacionComputadoras\Services\GestionAlmacenesTallerService;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Validation\ValidationException;

class CorreosSucursalTallerController
{
    public function index(Request $request, GestionAlmacenesTallerService $servicio): ViewContract
    {
        /** @var UsuarioTaller|null $usuario */
        $usuario = $request->attributes->get('usuario_taller');
        ContextoPropietarioTaller::validarPropietario($usuario);

        View::addNamespace('taller_computadoras', base_path('giros/reparacion_computadoras/views'));

        $empresaId = (int) ($usuario?->empresa_id ?? 1);
        $dominio = $servicio->resolverDominioBase($empresaId);

        $empresa = DB::connection('mysql')
            ->table('empresas')
            ->where('id', $empresaId)
            ->first();

        require_once __DIR__ . '/AutogestionTallerController.php';
        $configInternas = AutogestionTallerController::cargarConfiguracionPorAmbito($empresaId, 'internas');

        $sucursales = SucursalTaller::query()->orderByDesc('es_matriz')->orderBy('id')->get()->keyBy('id');
        $usuarios = UsuarioTaller::query()
            ->where('empresa_id', $empresaId)
            ->where('rol_empresa', '!=', 'propietario')
            ->orderBy('sucursal_id')
            ->orderBy('id')
            ->get();

        return view('taller_computadoras::correos_sucursales', [
            'usuario' => $usuario,
            'empresa' => $empresa,
            'configInternas' => $configInternas,
            'dominio' => $dominio,
            'sucursales' => $sucursales,
            'usuarios' => $usuarios,
        ]);
    }

    public function actualizar(Request $request, int $usuarioId, GestionAlmacenesTallerService $servicio): RedirectResponse
    {
        /** @var UsuarioTaller|null $usuario */
        $usuario = $request->attributes->get('usuario_taller');
        ContextoPropietarioTaller::validarPropietario($usuario);

        $datos = $request->validate([
            'correo_o_usuario' => ['required', 'string', 'max:150'],
        ]);

        $empresaId = (int) ($usuario?->empresa_id ?? 1);
        $dominio = $servicio->resolverDominioBase($empresaId);

        /** @var UsuarioTaller $objetivo */
        $objetivo = UsuarioTaller::query()
            ->where('empresa_id', $empresaId)
            ->findOrFail($usuarioId);

        if ($objetivo->esPropietarioGlobal()) {
            throw ValidationException::withMessages([
                'correo_o_usuario' => 'No se modifica el correo del propietario desde esta vista.',
            ]);
        }

        $entrada = strtolower(trim((string) $datos['correo_o_usuario']));
        if (str_contains($entrada, '@')) {
            [$parteLocal, $parteDominio] = explode('@', $entrada, 2);
            if (trim($parteDominio) !== strtolower($dominio)) {
                throw ValidationException::withMessages([
                    'correo_o_usuario' => "No está permitido cambiar el dominio del correo. Debe mantenerse @{$dominio}.",
                ]);
            }
            $entrada = trim($parteLocal);
        }

        $parteLocalLimpia = preg_replace('/[^a-z0-9._-]/', '', $entrada) ?? '';
        if ($parteLocalLimpia === '') {
            throw ValidationException::withMessages([
                'correo_o_usuario' => 'Indica una parte válida para el correo.',
            ]);
        }

        $nuevoEmail = "{$parteLocalLimpia}@{$dominio}";

        $duplicado = UsuarioTaller::query()
            ->where('email', $nuevoEmail)
            ->where('id', '!=', $objetivo->id)
            ->exists();

        if ($duplicado) {
            throw ValidationException::withMessages([
                'correo_o_usuario' => "El correo {$nuevoEmail} ya está en uso por otro usuario.",
            ]);
        }

        $objetivo->update([
            'email' => $nuevoEmail,
        ]);

        return back()->with('success', "Correo actualizado a {$nuevoEmail} conservando rol ({$objetivo->rol_empresa}) y dominio (@{$dominio}).");
    }
}