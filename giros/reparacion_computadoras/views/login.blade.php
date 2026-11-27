<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Taller — {{ $empresa->nombre ?? 'Reparación de Computadoras' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --taller-primary: {{ $template->color_primario ?? '#6366f1' }};
            --taller-accent: {{ $template->color_acento ?? '#10b981' }};
            --taller-bg: {{ $template->color_fondo_pagina ?? '#09090b' }};
            --taller-card: {{ $template->fondo_tarjeta ?? '#111115' }};
            --taller-surface: {{ $template->color_fondo_superficie ?? '#18181b' }};
            --taller-text: {{ $template->color_texto_principal ?? '#f4f4f5' }};
            --taller-muted: {{ $template->color_texto_secundario ?? '#a1a1aa' }};
            --taller-border: {{ $template->color_borde ?? '#27272a' }};
            --taller-radius: {{ $template->radio_bordes ?? '0.75rem' }};
        }
        body { background-color: var(--taller-bg); color: var(--taller-text); }
        .taller-card { background-color: var(--taller-card); border: 1px solid var(--taller-border); border-radius: var(--taller-radius); }
        .taller-input { background-color: var(--taller-surface); border: 1px solid var(--taller-border); border-radius: var(--taller-radius); }
        .taller-btn { background-color: var(--taller-primary); color: #ffffff; border-radius: var(--taller-radius); }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center px-4 antialiased">
    <div class="w-full max-w-md taller-card p-7 space-y-6 shadow-2xl">
        <div class="flex items-center gap-4">
            <div class="h-12 w-12 flex items-center justify-center rounded-lg text-2xl font-bold" style="background-color: var(--taller-primary); color: #fff;">
                {{ $template->icono_predeterminado ?? '💻' }}
            </div>
            <div>
                <span class="text-[11px] font-semibold uppercase tracking-wider" style="color: var(--taller-accent);">
                    REPARACIÓN DE COMPUTADORAS
                </span>
                <h1 class="text-xl font-bold leading-tight">
                    {{ $empresa->nombre ?? 'Taller de Cómputo' }}
                </h1>
                <p class="text-xs" style="color: var(--taller-muted);">
                    Acceso exclusivo del personal del taller
                </p>
            </div>
        </div>

        @if($errors->any())
            <div class="p-3 rounded border border-rose-500/60 bg-rose-950/30 text-xs text-rose-300 space-y-1">
                @foreach($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        @php
            $valorCorreo = old('email', $correoRecordado ?? '');
        @endphp

        <form method="POST" action="{{ route('taller.computadoras.login.submit') }}" class="space-y-4">
            @csrf

            <div>
                <label class="block text-xs mb-1" style="color: var(--taller-muted);">Correo electrónico</label>
                <input type="email" name="email" value="{{ $valorCorreo }}" required autofocus placeholder="usuario@taller.com" class="w-full px-3.5 py-2.5 taller-input text-sm">
            </div>

            <div>
                <label class="block text-xs mb-1" style="color: var(--taller-muted);">Contraseña</label>
                <input type="password" name="password" required placeholder="••••••••" class="w-full px-3.5 py-2.5 taller-input text-sm">
            </div>

            <div class="space-y-2 pt-1 text-xs" style="color: var(--taller-muted);">
                <label class="flex items-center gap-2.5 cursor-pointer select-none">
                    <input
                        type="checkbox"
                        name="recordar_usuario"
                        value="1"
                        {{ ($recordarUsuarioMarcado ?? false) ? 'checked' : '' }}
                        class="h-4 w-4 rounded border-zinc-700 bg-zinc-900 accent-indigo-500"
                    >
                    <span>Recordar usuario (prellenar correo)</span>
                </label>

                <label class="flex items-center gap-2.5 cursor-pointer select-none">
                    <input
                        type="checkbox"
                        name="mantener_sesion"
                        value="1"
                        {{ ($mantenerSesionMarcado ?? false) ? 'checked' : '' }}
                        class="h-4 w-4 rounded border-zinc-700 bg-zinc-900 accent-indigo-500"
                    >
                    <span>Mantener sesión abierta por 30 días</span>
                </label>
            </div>

            <button type="submit" class="w-full py-2.5 text-sm font-semibold taller-btn transition hover:opacity-95">
                Iniciar sesión
            </button>
        </form>
    </div>
</body>
</html>