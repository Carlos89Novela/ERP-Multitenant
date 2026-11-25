<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\TemplateConfiguracion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(Request $request, ?string $slug = null): View
    {
        $slugEmpresa = $slug ?: trim((string) $request->query('empresa', ''));
        $empresa = null;

        if ($slugEmpresa !== '') {
            $empresa = Empresa::query()
                ->where('slug', $slugEmpresa)
                ->where('activa', true)
                ->firstOrFail();
        }

        if ($empresa) {
            $template = TemplateConfiguracion::query()
                ->where('empresa_id', $empresa->id)
                ->where('activo', true)
                ->orderByDesc('id')
                ->first()
                ?? TemplateConfiguracion::query()
                    ->where('ambito', 'plataforma')
                    ->whereNull('empresa_id')
                    ->orderByDesc('id')
                    ->first()
                ?? new TemplateConfiguracion(TemplateConfiguracion::defaultsPlataforma());
        } else {
            $template = TemplateConfiguracion::query()
                ->where('ambito', 'plataforma')
                ->whereNull('empresa_id')
                ->orderByDesc('id')
                ->first()
                ?? new TemplateConfiguracion(TemplateConfiguracion::defaultsPlataforma());
        }

        $empresasActivas = Empresa::query()
            ->where('activa', true)
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'slug', 'giro_comercial']);

        return view('auth.login', [
            'template' => $template,
            'empresa' => $empresa,
            'empresasActivas' => $empresasActivas,
        ]);
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'empresa_slug' => ['nullable', 'string', 'max:160'],
        ]);

        $empresaContexto = null;
        if (! empty($credentials['empresa_slug'])) {
            $empresaContexto = Empresa::query()
                ->where('slug', trim((string) $credentials['empresa_slug']))
                ->where('activa', true)
                ->first();

            if (! $empresaContexto) {
                return back()->withInput($request->only('email', 'empresa_slug'))
                    ->withErrors(['email' => 'La empresa seleccionada no está disponible.']);
            }
        }

        if (! Auth::attempt([
            'email' => trim(mb_strtolower($credentials['email'])),
            'password' => $credentials['password'],
        ], $request->boolean('remember'))) {
            return back()
                ->withInput($request->only('email', 'empresa_slug'))
                ->withErrors(['email' => 'Credenciales inválidas.']);
        }

        $request->session()->regenerate();
        $user = $request->user();

        if (! ($user->activo ?? true)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors(['email' => 'Tu cuenta se encuentra desactivada.']);
        }

        // Si el login se hizo desde el portal ambientado de una empresa especifica, validar pertenencia
        if ($empresaContexto !== null) {
            if ((int) $user->empresa_id !== (int) $empresaContexto->id) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()
                    ->withInput($request->only('email', 'empresa_slug'))
                    ->withErrors(['email' => "Este usuario no pertenece a {$empresaContexto->nombre}."]);
            }
        }

        if (is_null($user->empresa_id)) {
            return redirect()->intended(route('plataforma.monitoreo'));
        }

        $empresaUsuario = Empresa::query()->find((int) $user->empresa_id);
        if ($empresaUsuario && $empresaUsuario->giro_comercial === 'reparacion_computadoras') {
            if (($user->rol_empresa ?? '') === 'mostrador') {
                return redirect()->route('giro.computadoras.ordenes');
            }

            return redirect()->intended(route('giro.computadoras.index'));
        }

        return redirect()->intended(route('empresa.portal'));
    }

    public function logout(Request $request): RedirectResponse
    {
        $empresaSlug = null;
        if ($request->user() && $request->user()->empresa_id) {
            $empresaSlug = Empresa::query()->where('id', $request->user()->empresa_id)->value('slug');
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($empresaSlug) {
            return redirect()->route('empresa.login', ['slug' => $empresaSlug]);
        }

        return redirect()->route('login');
    }

    public function showCambioPasswordForzoso(Request $request): View|RedirectResponse
    {
        $usuario = $request->user('empresa');
        if (! $usuario) {
            return redirect()->route('login');
        }

        if (! (bool) ($usuario->debe_cambiar_password ?? false)) {
            return redirect()->route('giro.computadoras.index');
        }

        $empresa = Empresa::query()->find((int) $usuario->empresa_id);
        $template = $empresa
            ? (TemplateConfiguracion::query()->where('empresa_id', $empresa->id)->where('activo', true)->orderByDesc('id')->first()
                ?? new TemplateConfiguracion(TemplateConfiguracion::defaultsPlataforma()))
            : new TemplateConfiguracion(TemplateConfiguracion::defaultsPlataforma());

        return view('auth.cambio-password', [
            'usuario' => $usuario,
            'empresa' => $empresa,
            'template' => $template,
        ]);
    }

    public function actualizarPasswordForzoso(Request $request): RedirectResponse
    {
        $usuario = $request->user('empresa');
        if (! $usuario) {
            return redirect()->route('login');
        }

        $data = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $empresa = Empresa::query()->findOrFail((int) $usuario->empresa_id);
        $connGiro = UsuarioGiro::conexionPorGiro($empresa->giro_comercial);
        Config::set('database.connections.usuario_giro_runtime', config("database.connections.{$connGiro}"));
        DB::purge('usuario_giro_runtime');

        UsuarioGiro::on('usuario_giro_runtime')
            ->where('id', $usuario->id)
            ->where('empresa_id', $empresa->id)
            ->update([
                'password' => Hash::make($data['password']),
                'debe_cambiar_password' => false,
                'password_temporal_generada_at' => null,
                'updated_at' => now(),
            ]);

        if ($empresa->giro_comercial === 'reparacion_computadoras') {
            return redirect()->route('giro.computadoras.index')->with('status', 'Contraseña actualizada. Bienvenido al sistema.');
        }

        return redirect()->route('empresa.portal')->with('status', 'Contraseña actualizada. Bienvenido al sistema.');
    }
}
