<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\TemplateConfiguracion;
use App\Models\UsuarioGiro;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class AuthController extends Controller
{
    private function resolverEmpresaPorContexto(Request $request, ?string $slug = null): ?Empresa
    {
        $slugEmpresa = trim((string) ($slug ?: $request->query('empresa', '')));
        if ($slugEmpresa !== '') {
            return Empresa::query()
                ->where('slug', $slugEmpresa)
                ->where('activa', true)
                ->firstOrFail();
        }

        $host = preg_replace('/:\d+$/', '', mb_strtolower((string) $request->getHost()));
        if ($host !== '' && ! in_array($host, ['127.0.0.1', 'localhost', '::1'], true)) {
            if (Schema::hasColumn('empresas', 'dominio')) {
                $porDominio = Empresa::query()
                    ->where('activa', true)
                    ->whereRaw('LOWER(dominio) = ?', [$host])
                    ->first();
                if ($porDominio) {
                    return $porDominio;
                }
            }
        }

        return null;
    }

    private function redirigirPostLoginEmpresa(Empresa $empresa, UsuarioGiro $usuarioGiro): RedirectResponse
    {
        if ((bool) ($usuarioGiro->debe_cambiar_password ?? false)) {
            return redirect()->route('empresa.password.forzoso');
        }

        if ($empresa->giro_comercial === 'reparacion_computadoras') {
            if (($usuarioGiro->rol_empresa ?? '') === 'mostrador') {
                return redirect()->route('giro.computadoras.ordenes');
            }

            return redirect()->route('giro.computadoras.index');
        }

        return redirect()->route('empresa.portal');
    }

    public function showLogin(Request $request, ?string $slug = null): View
    {
        $empresa = $this->resolverEmpresaPorContexto($request, $slug);

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

        return view('auth.login', [
            'template' => $template,
            'empresa' => $empresa,
        ]);
    }

    public function login(Request $request, ?string $slug = null): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'empresa_slug' => ['nullable', 'string', 'max:160'],
        ]);

        $emailNormalizado = trim(mb_strtolower($credentials['email']));
        $slugObjetivo = trim((string) ($slug ?: ($credentials['empresa_slug'] ?? '')));

        if ($slugObjetivo === '') {
            $empresaContexto = $this->resolverEmpresaPorContexto($request, null);
            if ($empresaContexto) {
                $slugObjetivo = $empresaContexto->slug;
            }
        }

        // A) Login en contexto de empresa (dominio propio, /tienda/{slug}/login o empresa_slug)
        if ($slugObjetivo !== '') {
            $empresa = Empresa::query()
                ->where('slug', $slugObjetivo)
                ->where('activa', true)
                ->first();

            if (! $empresa) {
                return back()->withInput($request->only('email', 'empresa_slug'))
                    ->withErrors(['email' => 'La empresa seleccionada no está disponible.']);
            }

            $connGiro = UsuarioGiro::conexionPorGiro($empresa->giro_comercial);
            Config::set('database.connections.usuario_giro_runtime', config("database.connections.{$connGiro}"));
            DB::purge('usuario_giro_runtime');

            $usuarioGiro = UsuarioGiro::on('usuario_giro_runtime')
                ->where('empresa_id', $empresa->id)
                ->where('email', $emailNormalizado)
                ->first();

            if (! $usuarioGiro || ! is_string($usuarioGiro->password) || ! Hash::check($credentials['password'], $usuarioGiro->password)) {
                return back()->withInput($request->only('email', 'empresa_slug'))
                    ->withErrors(['email' => "Credenciales inválidas para {$empresa->nombre}."]);
            }

            if (! $usuarioGiro->activo) {
                return back()->withInput($request->only('email', 'empresa_slug'))
                    ->withErrors(['email' => 'Tu cuenta en esta empresa se encuentra desactivada.']);
            }

            Auth::guard('empresa')->login($usuarioGiro, $request->boolean('remember'));
            $request->session()->regenerate();
            $request->session()->put('giro_conexion_activa', $connGiro);
            $request->session()->put('empresa_slug_activa', $empresa->slug);

            return $this->redirigirPostLoginEmpresa($empresa, $usuarioGiro);
        }

        // B) Login desde /login sin slug: primero probar operador de plataforma (erp_plataforma.users)
        if (Auth::guard('web')->attempt([
            'email' => $emailNormalizado,
            'password' => $credentials['password'],
        ], $request->boolean('remember'))) {
            $request->session()->regenerate();
            $user = $request->user('web');

            if (($user->activo ?? true) && is_null($user->empresa_id)) {
                return redirect()->intended(route('plataforma.monitoreo'));
            }

            Auth::guard('web')->logout();
        }

        // C) Si no es operador de plataforma, buscar el correo en las BDs de giros de empresas activas
        $empresasActivas = Empresa::query()->where('activa', true)->get();
        foreach ($empresasActivas as $emp) {
            $connGiro = UsuarioGiro::conexionPorGiro($emp->giro_comercial);
            Config::set('database.connections.usuario_giro_runtime', config("database.connections.{$connGiro}"));
            DB::purge('usuario_giro_runtime');

            $ug = UsuarioGiro::on('usuario_giro_runtime')
                ->where('empresa_id', $emp->id)
                ->where('email', $emailNormalizado)
                ->first();

            if ($ug && is_string($ug->password) && Hash::check($credentials['password'], $ug->password)) {
                if (! $ug->activo) {
                    return back()->withInput($request->only('email'))
                        ->withErrors(['email' => 'Tu cuenta en esta empresa se encuentra desactivada.']);
                }

                Auth::guard('empresa')->login($ug, $request->boolean('remember'));
                $request->session()->regenerate();
                $request->session()->put('giro_conexion_activa', $connGiro);
            $request->session()->put('empresa_slug_activa', $empresa->slug);

                return $this->redirigirPostLoginEmpresa($emp, $ug);
            }
        }

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => 'Credenciales inválidas.']);
    }

    public function logout(Request $request): RedirectResponse
    {
        $empresaSlug = null;
        if (Auth::guard('empresa')->check()) {
            $empId = Auth::guard('empresa')->user()->empresa_id;
            $empresaSlug = Empresa::query()->where('id', $empId)->value('slug');
            Auth::guard('empresa')->logout();
        }

        if (Auth::guard('web')->check()) {
            Auth::guard('web')->logout();
        }

        $request->session()->forget(['giro_conexion_activa', 'empresa_slug_activa']);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($empresaSlug) {
            return redirect()->route('empresa.login', ['slug' => $empresaSlug]);
        }

        return redirect()->route('login');
    }
}
