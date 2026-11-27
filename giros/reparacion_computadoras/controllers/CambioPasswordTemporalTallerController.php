<?php

namespace Giros\ReparacionComputadoras\Controllers;

use Giros\ReparacionComputadoras\Models\UsuarioTaller;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\View;
use Illuminate\Validation\ValidationException;

class CambioPasswordTemporalTallerController extends Controller
{
    public function show(Request $request): ViewContract|RedirectResponse
    {
        /** @var UsuarioTaller $usuario */
        $usuario = $request->attributes->get('usuario_taller');

        if (! (bool) $usuario->debe_cambiar_password) {
            return redirect()->route('taller.computadoras.dashboard');
        }

        View::addNamespace('taller_computadoras', base_path('giros/reparacion_computadoras/views'));

        $empresa = DB::connection('mysql')
            ->table('empresas')
            ->where('id', $usuario->empresa_id)
            ->first();

        require_once __DIR__ . '/AutogestionTallerController.php';
        $configInternas = AutogestionTallerController::cargarConfiguracionPorAmbito((int) $usuario->empresa_id, 'internas');

        return view('taller_computadoras::cambiar_password_temporal', [
            'usuario' => $usuario,
            'empresa' => $empresa,
            'configInternas' => $configInternas,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        /** @var UsuarioTaller $usuario */
        $usuario = $request->attributes->get('usuario_taller');

        $datos = $request->validate([
            'password' => ['required', 'string', 'min:8', 'max:120', 'confirmed'],
        ], [
            'password.required' => 'Debes escribir una nueva contraseña.',
            'password.min' => 'La nueva contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'La confirmación de contraseña no coincide.',
        ]);

        if (is_string($usuario->password) && Hash::check($datos['password'], $usuario->password)) {
            throw ValidationException::withMessages([
                'password' => 'La nueva contraseña debe ser diferente a la contraseña temporal.',
            ]);
        }

        $usuario->update([
            'password' => Hash::make($datos['password']),
            'debe_cambiar_password' => false,
            'password_temporal_generada_at' => null,
        ]);

        return redirect()
            ->route('taller.computadoras.dashboard')
            ->with('success', 'Contraseña actualizada correctamente. Ya puedes continuar operando en el taller.');
    }
}