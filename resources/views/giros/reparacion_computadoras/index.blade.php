<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reparación de Computadoras — {{ $empresa->nombre }}</title>
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
        body { font-family: var(--tpl-font-sans); background-color: var(--tpl-bg-page); color: var(--tpl-text); }
        .tpl-card { background-color: var(--tpl-bg-card); border: 1px solid var(--tpl-border); border-radius: var(--tpl-radius); }
        .tpl-surface { background-color: var(--tpl-bg-surface); border: 1px solid var(--tpl-border); border-radius: var(--tpl-radius); }
        .tpl-btn { background-color: var(--tpl-primary); color: #fff; border-radius: var(--tpl-radius); }
        .tpl-btn:hover { background-color: var(--tpl-secondary); }
        .tpl-muted { color: var(--tpl-text-muted); }
        .tpl-accent { color: var(--tpl-accent); }
        .tpl-mono { font-family: var(--tpl-font-mono); }
    </style>
</head>
<body class="min-h-screen antialiased">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-8 space-y-6">
        <header class="tpl-card p-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <span class="text-xs font-semibold uppercase tpl-accent">GIRO: REPARACIÓN DE COMPUTADORAS</span>
                <h1 class="text-2xl font-bold mt-1">{{ $empresa->nombre }} — Órdenes de Taller</h1>
                <p class="text-xs tpl-muted mt-1">Módulo aislado por giro y empresa. Sin datos de prueba pre-cargados.</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('empresa.portal') }}" class="px-3.5 py-2 text-xs font-medium tpl-surface">Volver al portal</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="px-3.5 py-2 text-xs font-medium tpl-surface">Cerrar sesión</button>
                </form>
            </div>
        </header>

        @if(session('status'))
            <div class="tpl-card p-4 border-l-4" style="border-left-color: var(--tpl-accent);">
                <p class="text-sm font-medium tpl-accent">{{ session('status') }}</p>
            </div>
        @endif

        @if($errors->any())
            <div class="tpl-card p-4 border-l-4 border-rose-500">
                <ul class="text-xs text-rose-400 list-disc list-inside space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="tpl-card p-6 space-y-4">
            <h2 class="text-lg font-semibold">Nueva Orden de Reparación (Equipo de Cómputo)</h2>
            <form method="POST" action="{{ route('giro.computadoras.ordenes.store') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @csrf
                <div>
                    <label class="block text-xs tpl-muted mb-1">Folio de orden</label>
                    <input type="text" name="folio" required placeholder="PC-0001" class="w-full px-3 py-2 tpl-surface text-sm tpl-mono">
                </div>
                <div>
                    <label class="block text-xs tpl-muted mb-1">Tipo de equipo</label>
                    <input type="text" name="tipo_equipo" required placeholder="Laptop / PC Escritorio / All-in-One" class="w-full px-3 py-2 tpl-surface text-sm">
                </div>
                <div>
                    <label class="block text-xs tpl-muted mb-1">Marca</label>
                    <input type="text" name="marca" placeholder="Lenovo / Dell / HP / Asus" class="w-full px-3 py-2 tpl-surface text-sm">
                </div>
                <div>
                    <label class="block text-xs tpl-muted mb-1">Modelo</label>
                    <input type="text" name="modelo" placeholder="ThinkPad T14" class="w-full px-3 py-2 tpl-surface text-sm">
                </div>
                <div>
                    <label class="block text-xs tpl-muted mb-1">Número de serie (opcional)</label>
                    <input type="text" name="numero_serie" class="w-full px-3 py-2 tpl-surface text-sm tpl-mono">
                </div>
                <div>
                    <label class="block text-xs tpl-muted mb-1">Falla reportada</label>
                    <input type="text" name="falla_reportada" placeholder="No enciende / Pantalla dañada / Lentitud" class="w-full px-3 py-2 tpl-surface text-sm">
                </div>
                <div class="md:col-span-3">
                    <button type="submit" class="px-4 py-2 text-sm font-medium tpl-btn">Registrar orden de reparación</button>
                </div>
            </form>
        </section>

        <section class="tpl-card p-6 space-y-4">
            <h2 class="text-lg font-semibold">Órdenes registradas en esta empresa ({{ $ordenes->count() }})</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="border-b" style="border-color: var(--tpl-border);">
                            <th class="py-2.5 px-3 tpl-muted font-medium">Folio</th>
                            <th class="py-2.5 px-3 tpl-muted font-medium">Equipo</th>
                            <th class="py-2.5 px-3 tpl-muted font-medium">Falla reportada</th>
                            <th class="py-2.5 px-3 tpl-muted font-medium">Estado</th>
                            <th class="py-2.5 px-3 tpl-muted font-medium">Cambiar estado</th>
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
                                <td class="py-2.5 px-3 text-xs tpl-mono uppercase tpl-accent">{{ $ord->estado }}</td>
                                <td class="py-2.5 px-3">
                                    <form method="POST" action="{{ route('giro.computadoras.ordenes.estado', $ord->id) }}" class="flex items-center gap-2">
                                        @csrf
                                        <select name="estado" class="px-2 py-1 tpl-surface text-xs">
                                            @foreach(['recibido','diagnostico','reparacion','listo','entregado','cancelado'] as $st)
                                                <option value="{{ $st }}" @selected($ord->estado === $st)>{{ $st }}</option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="px-2.5 py-1 text-xs tpl-btn">Guardar</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-6 px-3 text-center tpl-muted">Aún no hay órdenes de reparación en esta empresa.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</body>
</html>
