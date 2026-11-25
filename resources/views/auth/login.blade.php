<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión — ERP-Multitenant</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --tpl-font-sans: {{ $template->fuente_primaria ?? 'Inter, system-ui, sans-serif' }};
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
        .tpl-input {
            background-color: var(--tpl-bg-surface);
            border: 1px solid var(--tpl-border);
            border-radius: var(--tpl-radius);
            color: var(--tpl-text);
        }
        .tpl-btn {
            background-color: var(--tpl-primary);
            color: #ffffff;
            border-radius: var(--tpl-radius);
        }
        .tpl-btn:hover {
            background-color: var(--tpl-secondary);
        }
        .tpl-muted { color: var(--tpl-text-muted); }
        .tpl-accent { color: var(--tpl-accent); }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center px-4 antialiased">
    <div class="w-full max-w-md tpl-card p-8 space-y-6">
        <div>
            <span class="text-xs font-semibold uppercase tracking-wider tpl-accent">ERP-Multitenant</span>
            <h1 class="text-2xl font-bold mt-1">Iniciar Sesión</h1>
            <p class="text-xs tpl-muted mt-1">Acceso para Operador de Plataforma y Propietarios de Empresa.</p>
        </div>

        @if($errors->any())
            <div class="p-3 rounded border border-rose-500/60 bg-rose-500/10 text-xs text-rose-300 space-y-1">
                @foreach($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('login.post') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs tpl-muted mb-1">Correo electrónico</label>
                <input type="email" name="email" value="{{ old('email') }}" required autofocus class="w-full px-3.5 py-2.5 tpl-input text-sm">
            </div>
            <div>
                <label class="block text-xs tpl-muted mb-1">Contraseña</label>
                <input type="password" name="password" required class="w-full px-3.5 py-2.5 tpl-input text-sm">
            </div>
            <label class="inline-flex items-center gap-2 text-xs tpl-muted cursor-pointer">
                <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
                <span>Recordar sesión</span>
            </label>
            <button type="submit" class="w-full py-2.5 text-sm font-semibold tpl-btn transition">Entrar al sistema</button>
        </form>
    </div>
</body>
</html>
