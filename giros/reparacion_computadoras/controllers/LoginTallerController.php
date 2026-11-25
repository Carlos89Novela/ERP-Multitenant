<?php

namespace Giros\ReparacionComputadoras\Controllers;

use Giros\ReparacionComputadoras\Models\UsuarioTaller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\View;

class LoginTallerController extends Controller
{
    private function registrarVistasLocales(): void
    {
        View::addNamespace('taller_computadoras', base_path('giros/reparacion_computadoras/views'));
    }

    public function showLogin(Request $request)
    {
        if ($request->session()->has('taller_computadoras.usuario_id')) {
            return redirect()->route('taller.computadoras.dashboard');
        }

        $this->registrarVistasLocales();

        $empresa = DB::connection('mysql')
            ->table('empresas')
            ->where('giro_comercial', 'reparacion_computadoras')
            ->where('activa', true)
            ->orderBy('id')
            ->first();

        $template = $empresa
            ? DB::connection('mysql')
                ->table('template_configuraciones')
                ->where('empresa_id', $empresa->id)
                ->where('activo', true)
                ->orderByDesc('id')
                ->first()
            : null;

        return view('taller_computadoras::login', [
            'empresa' => $empresa,
            'template' => $template,
        ]);
    }

    public function login(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $email = trim(mb_strtolower($datos['email']));

        $usuario = UsuarioTaller::query()
            ->where('email', $email)
            ->first();

        if (! $usuario || ! is_string($usuario->password) || ! Hash::check($datos['password'], $usuario->password)) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Correo o contraseña incorrectos.']);
        }

        if (! $usuario->activo) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Esta cuenta está desactivada en el taller.']);
        }

        $request->session()->regenerate();
        $request->session()->put('taller_computadoras', [
            'usuario_id' => $usuario->id,
            'empresa_id' => $usuario->empresa_id,
            'rol' => $usuario->rol_empresa,
            'nombre' => $usuario->name,
        ]);

        return redirect()->route('taller.computadoras.dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget('taller_computadoras');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('taller.computadoras.login');
    }
}
