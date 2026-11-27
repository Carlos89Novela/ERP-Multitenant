<?php

namespace Giros\ReparacionComputadoras\Controllers;

use Giros\ReparacionComputadoras\Models\UsuarioTaller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\View;

class LoginTallerController extends Controller
{
    private const COOKIE_CORREO = 'taller_rep_correo_recordado';

    private const COOKIE_SESION_ABIERTA = 'taller_rep_sesion_persistente';

    private const MINUTOS_TREINTA_DIAS = 43200;

    private function registrarVistasLocales(): void
    {
        View::addNamespace('taller_computadoras', base_path('giros/reparacion_computadoras/views'));
    }

    private function restaurarSesionPersistenteSiAplica(Request $request): bool
    {
        if ($request->session()->has('taller_computadoras.usuario_id')) {
            return true;
        }

        $rawCookie = $request->cookie(self::COOKIE_SESION_ABIERTA);
        if (! is_string($rawCookie) || $rawCookie === '') {
            return false;
        }

        $datosCookie = json_decode($rawCookie, true);
        if (! is_array($datosCookie)) {
            return false;
        }

        $usuarioId = filter_var($datosCookie['usuario_id'] ?? null, FILTER_VALIDATE_INT);
        $expiraEn = filter_var($datosCookie['expira_en'] ?? null, FILTER_VALIDATE_INT);

        if ($usuarioId === false || (int) $usuarioId <= 0 || $expiraEn === false || time() > (int) $expiraEn) {
            return false;
        }

        /** @var UsuarioTaller|null $usuario */
        $usuario = UsuarioTaller::query()->find((int) $usuarioId);
        if (! $usuario || ! $usuario->activo) {
            return false;
        }

        $request->session()->regenerate();
        $request->session()->put('taller_computadoras', [
            'usuario_id' => $usuario->id,
            'empresa_id' => $usuario->empresa_id,
            'rol' => $usuario->rol_empresa,
            'nombre' => $usuario->name,
        ]);

        return true;
    }

    public function showLogin(Request $request)
    {
        if ($this->restaurarSesionPersistenteSiAplica($request)) {
            $uAct = UsuarioTaller::query()->find($request->session()->get('taller_computadoras.usuario_id'));
            if ($uAct && (bool) $uAct->debe_cambiar_password) {
                return redirect()->route('taller.computadoras.password.temporal');
            }
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

        $correoRecordadoRaw = $request->cookie(self::COOKIE_CORREO);
        $correoRecordado = (is_string($correoRecordadoRaw) && filter_var(trim($correoRecordadoRaw), FILTER_VALIDATE_EMAIL))
            ? trim(mb_strtolower($correoRecordadoRaw))
            : null;

        $sesionAbiertaCookie = $request->cookie(self::COOKIE_SESION_ABIERTA);
        $mantenerSesionMarcado = old('_submitted')
            ? (bool) old('mantener_sesion', false)
            : (is_string($sesionAbiertaCookie) && $sesionAbiertaCookie !== '');

        $recordarUsuarioMarcado = old('_submitted')
            ? (bool) old('recordar_usuario', false)
            : ($correoRecordado !== null);

        return view('taller_computadoras::login', [
            'empresa' => $empresa,
            'template' => $template,
            'correoRecordado' => $correoRecordado,
            'recordarUsuarioMarcado' => $recordarUsuarioMarcado,
            'mantenerSesionMarcado' => $mantenerSesionMarcado,
        ]);
    }

    public function login(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'recordar_usuario' => ['nullable', 'boolean'],
            'mantener_sesion' => ['nullable', 'boolean'],
        ]);

        $email = trim(mb_strtolower($datos['email']));
        $recordarUsuario = $request->boolean('recordar_usuario');
        $mantenerSesion = $request->boolean('mantener_sesion');

        $usuario = UsuarioTaller::query()
            ->where('email', $email)
            ->first();

        if (! $usuario || ! is_string($usuario->password) || ! Hash::check($datos['password'], $usuario->password)) {
            return back()
                ->withInput($request->only('email', 'recordar_usuario', 'mantener_sesion') + ['_submitted' => '1'])
                ->withErrors(['email' => 'Correo o contraseña incorrectos.']);
        }

        if (! $usuario->activo) {
            return back()
                ->withInput($request->only('email', 'recordar_usuario', 'mantener_sesion') + ['_submitted' => '1'])
                ->withErrors(['email' => 'Esta cuenta está desactivada en el taller.']);
        }

        $request->session()->regenerate();
        $request->session()->put('taller_computadoras', [
            'usuario_id' => $usuario->id,
            'empresa_id' => $usuario->empresa_id,
            'rol' => $usuario->rol_empresa,
            'nombre' => $usuario->name,
        ]);

        $respuesta = (bool) $usuario->debe_cambiar_password
            ? redirect()->route('taller.computadoras.password.temporal')
            : redirect()->route('taller.computadoras.dashboard');

        if ($recordarUsuario) {
            $respuesta->withCookie(Cookie::make(
                self::COOKIE_CORREO,
                $email,
                self::MINUTOS_TREINTA_DIAS
            ));
        } else {
            $respuesta->withCookie(Cookie::forget(self::COOKIE_CORREO));
        }

        if ($mantenerSesion) {
            $payload = json_encode([
                'usuario_id' => (int) $usuario->id,
                'expira_en' => time() + (self::MINUTOS_TREINTA_DIAS * 60),
            ], JSON_THROW_ON_ERROR);

            $respuesta->withCookie(Cookie::make(
                self::COOKIE_SESION_ABIERTA,
                $payload,
                self::MINUTOS_TREINTA_DIAS
            ));
        } else {
            $respuesta->withCookie(Cookie::forget(self::COOKIE_SESION_ABIERTA));
        }

        return $respuesta;
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget('taller_computadoras');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('taller.computadoras.login')
            ->withCookie(Cookie::forget(self::COOKIE_SESION_ABIERTA));
    }
}