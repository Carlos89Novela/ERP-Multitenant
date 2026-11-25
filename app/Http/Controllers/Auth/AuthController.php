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
    private function templateBase(): TemplateConfiguracion
    {
        return TemplateConfiguracion::query()
            ->where('ambito', 'plataforma')
            ->whereNull('empresa_id')
            ->orderByDesc('id')
            ->first()
            ?? new TemplateConfiguracion(TemplateConfiguracion::defaultsPlataforma());
    }

    public function showLogin(): View
    {
        return view('auth.login', [
            'template' => $this->templateBase(),
        ]);
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $remember = $request->boolean('remember');

        if (! Auth::attempt($credentials, $remember)) {
            return back()
                ->withInput($request->only('email', 'remember'))
                ->withErrors(['email' => 'Las credenciales ingresadas no son válidas.']);
        }

        $request->session()->regenerate();
        $user = $request->user();

        if ($user->esOperadorPlataforma()) {
            return redirect()->intended(route('plataforma.monitoreo'));
        }

        if ($user->empresa_id) {
            $empresa = Empresa::query()->find($user->empresa_id);
            if (! $empresa || ! $empresa->activa) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()
                    ->route('login')
                    ->withErrors(['email' => 'La empresa asociada a esta cuenta se encuentra suspendida o inactiva.']);
            }

            return redirect()->intended(route('empresa.portal'));
        }

        return redirect()->intended('/');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
