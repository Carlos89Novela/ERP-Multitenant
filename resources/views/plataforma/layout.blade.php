<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Consola de Plataforma') — ERP-Multitenant</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style id="plataforma-theme-vars">
        :root {
            --tpl-font-sans: {{ $template->fuente_primaria ?? 'Inter, system-ui, sans-serif' }};
            --tpl-font-mono: {{ $template->fuente_secundaria ?? 'JetBrains Mono, monospace' }};
            --tpl-font-size: {{ (int) ($template->tamano_base_px ?? 14) }}px;
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
            font-size: var(--tpl-font-size);
            color: var(--tpl-text);
            @if(($template->tipo_fondo ?? 'solido') === 'degradado' && !empty($template->valor_fondo))
                background: {{ $template->valor_fondo }};
            @elseif(($template->tipo_fondo ?? 'solido') === 'imagen' && !empty($template->valor_fondo))
                background: url('{{ $template->valor_fondo }}') center/cover fixed no-repeat;
            @else
                background-color: var(--tpl-bg-page);
            @endif
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

        .tpl-btn-primary {
            background-color: var(--tpl-primary);
            color: #ffffff;
            border-radius: var(--tpl-radius);
        }

        .tpl-btn-primary:hover {
            background-color: var(--tpl-secondary);
        }

        .tpl-accent {
            color: var(--tpl-accent);
        }

        .tpl-muted {
            color: var(--tpl-text-muted);
        }

        .tpl-mono {
            font-family: var(--tpl-font-mono);
        }
    </style>
</head>
<body class="min-h-screen antialiased">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <header class="tpl-card p-6 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <span class="inline-flex items-center px-2.5 py-0.5 text-xs font-semibold rounded-full tpl-surface tpl-accent">
                    PROVEEDOR DE PLATAFORMA • AISLAMIENTO DE PRIVACIDAD ACTIVO
                </span>
                <h1 class="text-2xl font-bold mt-2">@yield('header_title', 'Consola de Plataforma')</h1>
                <p class="tpl-muted text-sm mt-1">@yield('header_subtitle')</p>
            </div>
            <nav class="flex flex-wrap gap-2">
                <a href="{{ route('plataforma.monitoreo') }}"
                   class="px-3.5 py-2 text-xs font-medium {{ request()->routeIs('plataforma.monitoreo') ? 'tpl-btn-primary' : 'tpl-surface hover:opacity-90' }}">
                    Monitoreo de Servicios
                </a>
                <a href="{{ route('plataforma.empresas') }}"
                   class="px-3.5 py-2 text-xs font-medium {{ request()->routeIs('plataforma.empresas') ? 'tpl-btn-primary' : 'tpl-surface hover:opacity-90' }}">
                    Alta de Empresas
                </a>
                <a href="{{ route('plataforma.timbres') }}"
                   class="px-3.5 py-2 text-xs font-medium {{ request()->routeIs('plataforma.timbres') ? 'tpl-btn-primary' : 'tpl-surface hover:opacity-90' }}">
                    Gestión de Timbres
                </a>
                <a href="{{ route('plataforma.apariencia') }}"
                   class="px-3.5 py-2 text-xs font-medium {{ request()->routeIs('plataforma.apariencia') ? 'tpl-btn-primary' : 'tpl-surface hover:opacity-90' }}">
                    Template y Operador
                </a>
            </nav>
        </header>

        <!-- Resumen rápido siempre visible -->
        <section class="grid grid-cols-2 sm:grid-cols-5 gap-4">
            <div class="tpl-card p-4">
                <p class="text-xs tpl-muted uppercase">Empresas</p>
                <p class="text-2xl font-bold mt-1">{{ $resumen['empresas_total'] }}</p>
            </div>
            <div class="tpl-card p-4">
                <p class="text-xs tpl-muted uppercase">Páginas Activas</p>
                <p class="text-2xl font-bold mt-1 tpl-accent">{{ $resumen['empresas_activas'] }}</p>
            </div>
            <div class="tpl-card p-4">
                <p class="text-xs tpl-muted uppercase">Tu Bolsa Timbres</p>
                <p class="text-2xl font-bold mt-1 tpl-mono">{{ number_format($resumen['timbres_bolsa_disponibles']) }}</p>
            </div>
            <div class="tpl-card p-4">
                <p class="text-xs tpl-muted uppercase">Timbres Clientes</p>
                <p class="text-2xl font-bold mt-1 tpl-mono">{{ number_format($resumen['timbres_asignados_empresas']) }}</p>
            </div>
            <div class="tpl-card p-4 col-span-2 sm:col-span-1">
                <p class="text-xs tpl-muted uppercase">Alertas Timbres</p>
                <p class="text-2xl font-bold mt-1 {{ $resumen['alertas_timbres'] > 0 ? 'text-amber-400' : 'tpl-accent' }}">
                    {{ $resumen['alertas_timbres'] }}
                </p>
            </div>
        </section>

        @if(session('status'))
            <div class="tpl-card p-4 border-l-4" style="border-left-color: var(--tpl-accent);">
                <p class="text-sm font-medium tpl-accent">{{ session('status') }}</p>
            </div>
        @endif

        @if($errors->any())
            <div class="tpl-card p-4 border-l-4 border-rose-500">
                <ul class="text-sm text-rose-400 list-disc list-inside space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <main>
            @yield('content')
        </main>
    </div>
    @stack('scripts')
</body>
</html>
