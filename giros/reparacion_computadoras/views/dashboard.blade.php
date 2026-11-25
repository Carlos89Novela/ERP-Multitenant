<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Taller de Cómputo — {{ $empresa->nombre }}</title>
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
<body class="min-h-screen antialiased">
    @php
        $paginasEmp = $empresa->paginas_habilitadas ?? [];
        $estadoLicencia = $empresa->evaluarEstadoLicenciaEfectivo();
    @endphp

    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-8 space-y-6">
        {{-- Encabezado principal del taller --}}
        <header class="tpl-card p-6 space-y-4">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="flex items-center gap-4">
                    @if(!empty($template->logo_path))
                        <img src="{{ asset('storage/' . $template->logo_path) }}" alt="{{ $empresa->nombre }}" class="h-12 w-12 object-cover rounded">
                    @else
                        <span class="h-12 w-12 flex items-center justify-center rounded text-2xl tpl-surface">
                            {{ $template->icono_predeterminado ?? '💻' }}
                        </span>
                    @endif
                    <div>
                        <span class="text-xs font-semibold uppercase tracking-wider tpl-accent">
                            DASHBOARD • REPARACIÓN DE COMPUTADORAS • BD AISLADA
                        </span>
                        <h1 class="text-2xl font-bold mt-0.5">{{ $empresa->nombre }}</h1>
                        <p class="text-xs tpl-muted mt-0.5">
                            Licencia: <span class="tpl-mono uppercase font-semibold">{{ $estadoLicencia }}</span>
                            • Timbres disponibles: <span class="tpl-mono font-semibold tpl-accent">{{ number_format((int) $empresa->saldo_timbres) }}</span>
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('empresa.publica', $empresa->slug) }}" target="_blank" class="px-3.5 py-2 text-xs font-semibold rounded" style="background-color: var(--tpl-accent); color: #09090b;">
                        Ver Página Pública
                    </a>
                    <a href="{{ route('empresa.portal') }}" class="px-3.5 py-2 text-xs font-medium tpl-surface">
                        Portal de Empresa
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="px-3.5 py-2 text-xs font-medium rounded bg-rose-600/80 text-white">
                            Cerrar sesión
                        </button>
                    </form>
                </div>
            </div>

            {{-- Navegación filtrada por páginas habilitadas de la empresa --}}
            <nav class="flex flex-wrap gap-2 pt-3 border-t" style="border-color: var(--tpl-border);">
                @if(in_array('dashboard', $paginasEmp, true))
                    <a href="{{ route('giro.computadoras.index') }}" class="px-3 py-1.5 text-xs font-semibold tpl-btn">
                        Dashboard
                    </a>
                @endif
                @if(in_array('ordenes_taller', $paginasEmp, true))
                    <a href="{{ route('giro.computadoras.ordenes') }}" class="px-3 py-1.5 text-xs font-medium tpl-surface">
                        Órdenes de Reparación
                    </a>
                @endif
                @if(in_array('inventario', $paginasEmp, true))
                    <a href="{{ route('giro.computadoras.inventario') }}" class="px-3 py-1.5 text-xs font-medium tpl-surface">
                        Inventario
                    </a>
                    <a href="{{ route('giro.computadoras.transferencias') }}" class="px-3 py-1.5 text-xs font-medium tpl-surface">
                        Transferencias
                    </a>
                @endif
                @if(in_array('facturacion', $paginasEmp, true))
                    <a href="{{ route('giro.computadoras.facturacion') }}" class="px-3 py-1.5 text-xs font-medium tpl-surface">
                        Facturación CFDI
                    </a>
                @endif
                @if(in_array('taller_promociones', $paginasEmp, true))
                    <a href="{{ route('giro.computadoras.promociones') }}" class="px-3 py-1.5 text-xs font-medium tpl-surface">
                        Promociones y Combos
                    </a>
                @endif
                @if(in_array('autogestion_apariencia', $paginasEmp, true))
                    <a href="{{ route('giro.computadoras.autogestion') }}" class="px-3 py-1.5 text-xs font-medium tpl-surface">
                        Autogestión Visual
                    </a>
                @endif
            </nav>
        </header>

        {{-- KPIs principales del taller --}}
        <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="tpl-card p-5">
                <p class="text-xs tpl-muted uppercase">Órdenes Activas</p>
                <p class="text-3xl font-bold tpl-mono mt-1 tpl-accent">{{ $kpis['ordenes_activas'] ?? 0 }}</p>
                <p class="text-xs tpl-muted mt-1">Total histórico: <span class="tpl-mono">{{ $kpis['ordenes_total'] ?? 0 }}</span></p>
            </div>

            <div class="tpl-card p-5">
                <p class="text-xs tpl-muted uppercase">Listas para Entrega</p>
                <p class="text-3xl font-bold tpl-mono mt-1">{{ $kpis['ordenes_listas'] ?? 0 }}</p>
                <p class="text-xs tpl-muted mt-1">Entregadas: <span class="tpl-mono">{{ $kpis['ordenes_entregadas'] ?? 0 }}</span></p>
            </div>

            <div class="tpl-card p-5">
                <p class="text-xs tpl-muted uppercase">Equipos en Inventario</p>
                <p class="text-3xl font-bold tpl-mono mt-1">{{ $kpis['equipos'] ?? 0 }}</p>
                <p class="text-xs tpl-muted mt-1">Transferencias: <span class="tpl-mono">{{ $kpis['transferencias'] ?? 0 }}</span></p>
            </div>

            <div class="tpl-card p-5">
                <p class="text-xs tpl-muted uppercase">Promociones Activas</p>
                <p class="text-3xl font-bold tpl-mono mt-1">{{ $kpis['promociones_activas'] ?? 0 }}</p>
                <p class="text-xs tpl-muted mt-1">Total combos: <span class="tpl-mono">{{ $kpis['promociones_total'] ?? 0 }}</span></p>
            </div>
        </section>

        {{-- Distribución por estado + actividad reciente --}}
        <section class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <div class="lg:col-span-4 tpl-card p-6 space-y-4">
                <h2 class="text-base font-semibold">Órdenes por Estado</h2>
                <div class="space-y-2.5 text-sm">
                    @foreach(['recibido' => 'Recibido', 'diagnostico' => 'Diagnóstico', 'reparacion' => 'En reparación', 'listo' => 'Listo para entrega', 'entregado' => 'Entregado', 'cancelado' => 'Cancelado'] as $claveEstado => $etiquetaEstado)
                        <div class="flex items-center justify-between px-3 py-2 tpl-surface">
                            <span class="text-xs">{{ $etiquetaEstado }}</span>
                            <span class="tpl-mono text-xs font-bold">{{ $estadosConteo[$claveEstado] ?? 0 }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="lg:col-span-8 tpl-card p-6 space-y-4">
                <div class="flex items-center justify-between gap-2">
                    <h2 class="text-base font-semibold">Órdenes Recientes del Taller</h2>
                    @if(in_array('ordenes_taller', $paginasEmp, true))
                        <a href="{{ route('giro.computadoras.ordenes') }}" class="px-3 py-1.5 text-xs font-semibold tpl-btn">
                            + Nueva / Ver Órdenes
                        </a>
                    @endif
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-sm">
                        <thead>
                            <tr class="border-b" style="border-color: var(--tpl-border);">
                                <th class="py-2 px-3 tpl-muted font-medium">Folio</th>
                                <th class="py-2 px-3 tpl-muted font-medium">Equipo</th>
                                <th class="py-2 px-3 tpl-muted font-medium">Falla</th>
                                <th class="py-2 px-3 tpl-muted font-medium">Estado</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y" style="border-color: var(--tpl-border);">
                            @forelse($ordenes as $ord)
                                <tr>
                                    <td class="py-2.5 px-3 tpl-mono font-semibold">{{ $ord->folio }}</td>
                                    <td class="py-2.5 px-3">
                                        {{ $ord->tipo_equipo ?? 'Equipo' }}
                                        <span class="text-xs tpl-muted">{{ trim(($ord->marca ?? '') . ' ' . ($ord->modelo ?? '')) }}</span>
                                    </td>
                                    <td class="py-2.5 px-3 text-xs tpl-muted">{{ $ord->falla_reportada ?? 'Sin detalle' }}</td>
                                    <td class="py-2.5 px-3 text-xs tpl-mono uppercase font-semibold tpl-accent">{{ $ord->estado }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-6 px-3 text-center tpl-muted">
                                        Aún no hay órdenes registradas en este taller (arranque limpio sin datos semilla).
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
</body>
</html>
