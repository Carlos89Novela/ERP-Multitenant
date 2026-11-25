<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $empresa ? "Acceso — {$empresa->nombre}" : 'Acceso a Plataforma — CanoDevOps' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --tpl-font-sans: {{ $template->fuente_primaria ?? 'Inter, system-ui, sans-serif' }};
            --tpl-font-mono: {{ $template->fuente_secundaria ?? 'JetBrains Mono, monospace' }};
            --tpl-primary: {{ $template->color_primario ?? '#6366f1' }};
            --tpl-secondary: {{ $template->color_secundario ?? '#3b82f6' }};
            --tpl-accent: {{ $template->color_acento ?? '#10b981' }};
            --tpl-bg-page: {{ $template->color_fondo_pagina ?? '#09090b' }};
            --tpl-bg-surface: {{ $template->color_fondo_superficie ?? '#18181b' }};
            --tpl-bg-card: {{ $template->fondo_tarjeta ?? '#111115' }};
            --tpl-text: {{ $template->color_texto_principal ?? '#f4f4f5' }};
            --tpl-text-muted: {{ $template->color_texto_secundario ?? '#a1a1aa' }};
            --tpl-border: {{ $template->color_borde ?? '#27272a' }};
            --tpl-radius: {{ $template->radio_bordes ?? '0.75rem' }};
        }
        body {
            font-family: var(--tpl-font-sans);
            background-color: var(--tpl-bg-page);
            color: var(--tpl-text);
        }
        .tpl-card {
            background-color: var(--tpl-bg-card);
            border: 1px solid var(--tpl-border);
            border-radius: var(--tpl-radius);
        }
        .tpl-surface {
            background-color: var(--tpl-bg-surface);
            border: 1px solid var(--tpl-border);
            border-radius: var(--tpl-radius);
        }
        .tpl-btn {
            background-color: var(--tpl-primary);
            color: #ffffff;
            border-radius: var(--tpl-radius);
        }
        .tpl-muted { color: var(--tpl-text-muted); }
        .tpl-accent { color: var(--tpl-accent); }
        .tpl-mono { font-family: var(--tpl-font-mono); }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center px-4 py-10 antialiased">
    <div class="w-full max-w-md space-y-6">
        <div class="tpl-card p-7 space-y-6 shadow-2xl">
            <div class="flex items-center gap-4">
                @if($empresa && !empty($template->logo_path))
                    <img src="{{ asset('storage/' . $template->logo_path) }}" alt="{{ $empresa->nombre }}" class="h-14 w-14 object-cover rounded-lg border" style="border-color: var(--tpl-border);">
                @else
                    <div class="h-14 w-14 flex items-center justify-center rounded-lg text-2xl font-bold" style="background-color: var(--tpl-primary); color: #ffffff;">
                        {{ $empresa ? ($template->icono_predeterminado ?? '★') : '⚙' }}
                    </div>
                @endif
                <div>
                    <span class="text-[11px] font-semibold uppercase tracking-wider tpl-accent">
                        {{ $empresa ? str_replace('_', ' ', $empresa->giro_comercial) : 'PLATAFORMA CANODEVOPS' }}
                    </span>
                    <h1 class="text-xl font-bold leading-tight mt-0.5">
                        {{ $empresa ? $empresa->nombre : 'Consola de Gestión' }}
                    </h1>
                    <p class="text-xs tpl-muted mt-0.5">
                        {{ $empresa ? 'Inicia sesión en el entorno operativo de esta empresa' : 'Acceso administrativo o selección de empresa' }}
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

            <form method="POST" action="{{ route('login.submit') }}" class="space-y-4">
                @csrf
                @if($empresa)
                    <input type="hidden" name="empresa_slug" value="{{ $empresa->slug }}">
                @endif

                <div>
                    <label class="block text-xs tpl-muted mb-1">Correo electrónico</label>
                    <input type="email" name="email" value="{{ old('email') }}" required autofocus placeholder="usuario@empresa.com" class="w-full px-3.5 py-2.5 tpl-surface text-sm">
                </div>

                <div>
                    <label class="block text-xs tpl-muted mb-1">Contraseña</label>
                    <input type="password" name="password" required placeholder="••••••••" class="w-full px-3.5 py-2.5 tpl-surface text-sm">
                </div>

                <div class="flex items-center justify-between text-xs">
                    <label class="flex items-center gap-2 tpl-muted cursor-pointer">
                        <input type="checkbox" name="remember">
                        <span>Mantener sesión</span>
                    </label>
                    @if($empresa)
                        <a href="{{ route('empresa.publica', $empresa->slug) }}" class="tpl-accent hover:underline">
                            Ver página pública
                        </a>
                    @endif
                </div>

                <button type="submit" class="w-full py-2.5 text-sm font-semibold tpl-btn transition hover:opacity-95">
                    {{ $empresa ? "Entrar a {$empresa->nombre}" : 'Iniciar sesión' }}
                </button>
            </form>

            @if(!$empresa && $empresasActivas->count() > 0)
                <div class="pt-4 border-t space-y-2" style="border-color: var(--tpl-border);">
                    <p class="text-xs tpl-muted">¿Inicias sesión en una empresa específica? Elige su portal ambientado:</p>
                    <div class="grid grid-cols-1 gap-2">
                        @foreach($empresasActivas as $empItem)
                            <a href="{{ route('empresa.login', ['slug' => $empItem->slug]) }}" class="flex items-center justify-between px-3 py-2 tpl-surface text-xs hover:opacity-90">
                                <span class="font-semibold">{{ $empItem->nombre }}</span>
                                <span class="tpl-mono tpl-accent">{{ $empItem->slug }} →</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @elseif($empresa)
                <div class="pt-3 border-t text-center" style="border-color: var(--tpl-border);">
                    <a href="{{ route('login') }}" class="text-xs tpl-muted hover:underline">
                        Cambiar de empresa o acceso plataforma
                    </a>
                </div>
            @endif
        </div>
    </div>
</body>
</html>
