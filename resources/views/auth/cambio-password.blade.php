<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambio Obligatorio de Contraseña</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --tpl-primary: {{ $template->color_primario ?? '#6366f1' }};
            --tpl-bg-page: {{ $template->color_fondo_pagina ?? '#09090b' }};
            --tpl-bg-surface: {{ $template->color_fondo_superficie ?? '#18181b' }};
            --tpl-bg-card: {{ $template->fondo_tarjeta ?? '#111115' }};
            --tpl-text: {{ $template->color_texto_principal ?? '#f4f4f5' }};
            --tpl-text-muted: {{ $template->color_texto_secundario ?? '#a1a1aa' }};
            --tpl-border: {{ $template->color_borde ?? '#27272a' }};
            --tpl-radius: {{ $template->radio_bordes ?? '0.75rem' }};
        }
        body { background-color: var(--tpl-bg-page); color: var(--tpl-text); }
        .tpl-card { background-color: var(--tpl-bg-card); border: 1px solid var(--tpl-border); border-radius: var(--tpl-radius); }
        .tpl-surface { background-color: var(--tpl-bg-surface); border: 1px solid var(--tpl-border); border-radius: var(--tpl-radius); }
        .tpl-btn { background-color: var(--tpl-primary); color: #fff; border-radius: var(--tpl-radius); }
        .tpl-muted { color: var(--tpl-text-muted); }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center px-4 py-10 antialiased">
    <div class="w-full max-w-md tpl-card p-7 space-y-5">
        <div>
            <span class="text-xs font-semibold uppercase text-amber-400">SEGURIDAD • CONTRASEÑA TEMPORAL</span>
            <h1 class="text-xl font-bold mt-1">Establece tu nueva contraseña</h1>
            <p class="text-xs tpl-muted mt-1">Ingresaste con una contraseña temporal{{ $empresa ? " en {$empresa->nombre}" : '' }}. Debes definir una nueva contraseña para continuar.</p>
        </div>

        @if($errors->any())
            <div class="p-3 rounded border border-rose-500/60 bg-rose-950/30 text-xs text-rose-300 space-y-1">
                @foreach($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('empresa.password.forzoso.update') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs tpl-muted mb-1">Nueva contraseña (mínimo 8 caracteres)</label>
                <input type="password" name="password" required minlength="8" class="w-full px-3.5 py-2.5 tpl-surface text-sm">
            </div>
            <div>
                <label class="block text-xs tpl-muted mb-1">Confirmar nueva contraseña</label>
                <input type="password" name="password_confirmation" required minlength="8" class="w-full px-3.5 py-2.5 tpl-surface text-sm">
            </div>
            <button type="submit" class="w-full py-2.5 text-sm font-semibold tpl-btn">Guardar nueva contraseña</button>
        </form>
    </div>
</body>
</html>
