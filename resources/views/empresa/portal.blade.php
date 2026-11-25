<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $empresa->nombre }} — Portal del Propietario</title>
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
        .tpl-surface {
            background-color: var(--tpl-bg-surface);
            border: 1px solid var(--tpl-border);
            border-radius: var(--tpl-radius);
        }
        .tpl-muted { color: var(--tpl-text-muted); }
        .tpl-accent { color: var(--tpl-accent); }
    </style>
</head>
<body class="min-h-screen antialiased">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 py-8 space-y-6">
        <header class="tpl-card p-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <span class="text-xs font-semibold uppercase tpl-accent">
                    PROPIETARIO • GIRO: {{ $girosDisponibles[$empresa->giro_comercial] ?? $empresa->giro_comercial }}
                </span>
                <h1 class="text-2xl font-bold mt-1">{{ $empresa->nombre }}</h1>
                <p class="text-xs tpl-muted mt-1">
                    Sesión: {{ $user->name }} ({{ $user->email }}) • Sucursales: {{ $empresa->sucursales->count() }}
                </p>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="px-4 py-2 text-xs font-medium tpl-surface hover:opacity-90">Cerrar sesión</button>
            </form>
        </header>

        <section class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="tpl-card p-4">
                <p class="text-xs tpl-muted uppercase">Giro Comercial Fijo</p>
                <p class="text-lg font-bold mt-1">{{ $girosDisponibles[$empresa->giro_comercial] ?? $empresa->giro_comercial }}</p>
            </div>
            <div class="tpl-card p-4">
                <p class="text-xs tpl-muted uppercase">Saldo de Timbres</p>
                <p class="text-2xl font-bold mt-1 tpl-accent">{{ number_format($empresa->saldo_timbres) }}</p>
            </div>
            <div class="tpl-card p-4">
                <p class="text-xs tpl-muted uppercase">Páginas Habilitadas</p>
                <p class="text-2xl font-bold mt-1">{{ count($empresa->paginas_habilitadas ?? []) }}</p>
            </div>
        </section>

        <section class="tpl-card p-6 space-y-3">
            <h2 class="text-lg font-semibold">Páginas habilitadas para tu empresa</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                @forelse(($empresa->paginas_habilitadas ?? []) as $pag)
                    <div class="p-3.5 tpl-surface text-xs font-medium">
                        {{ $paginasCatalogo[$pag] ?? $pag }}
                    </div>
                @empty
                    <p class="text-xs tpl-muted col-span-4">Aún no hay páginas asignadas a esta empresa.</p>
                @endforelse
            </div>
        </section>
    </div>
</body>
</html>
