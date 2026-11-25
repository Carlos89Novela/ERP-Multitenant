<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reparación de Celulares — {{ $empresa->nombre }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --tpl-font-sans: {{ $template->fuente_primaria ?? 'Inter, system-ui, sans-serif' }};
            --tpl-font-mono: {{ $template->fuente_secundaria ?? 'JetBrains Mono, monospace' }};
            --tpl-primary: {{ $template->color_primario ?? '#6366f1' }};
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
        .tpl-muted { color: var(--tpl-text-muted); }
        .tpl-accent { color: var(--tpl-accent); }
        .tpl-mono { font-family: var(--tpl-font-mono); }
    </style>
</head>
<body class="min-h-screen antialiased">
    @php $paginasEmp = $empresa->paginas_habilitadas ?? []; @endphp
    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-8 space-y-6">
        <header class="tpl-card p-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <span class="text-xs font-semibold uppercase tpl-accent">GIRO: REPARACIÓN DE CELULARES • BD AISLADA</span>
                <h1 class="text-2xl font-bold mt-1">{{ $empresa->nombre }} — Órdenes y Promociones</h1>
                <p class="text-xs tpl-muted mt-1">Sin datos de prueba iniciales.</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('empresa.portal') }}" class="px-3.5 py-2 text-xs font-medium tpl-surface">Volver al portal</a>
                <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="px-3.5 py-2 text-xs font-medium tpl-surface">Cerrar sesión</button></form>
            </div>
        </header>

        @if(session('status'))
            <div class="tpl-card p-4 border-l-4" style="border-left-color: var(--tpl-accent);"><p class="text-sm font-medium tpl-accent">{{ session('status') }}</p></div>
        @endif

        <section class="tpl-card p-6 space-y-4">
            <h2 class="text-lg font-semibold">Nueva Orden de Reparación (Celular / Tablet)</h2>
            <form method="POST" action="{{ route('giro.celulares.ordenes.store') }}" class="grid grid-cols-1 md:grid-cols-3 gap-3">
                @csrf
                <input type="text" name="folio" required placeholder="CEL-0001" class="px-3 py-2 tpl-surface text-sm tpl-mono">
                <input type="text" name="marca" required placeholder="Apple / Samsung / Xiaomi" class="px-3 py-2 tpl-surface text-sm">
                <input type="text" name="modelo" required placeholder="Modelo del equipo" class="px-3 py-2 tpl-surface text-sm">
                <input type="text" name="imei" placeholder="IMEI / Serie (opcional)" class="px-3 py-2 tpl-surface text-sm tpl-mono">
                <input type="text" name="patron_pin_nota" placeholder="PIN / Patrón / Nota" class="px-3 py-2 tpl-surface text-sm">
                <input type="text" name="falla_reportada" placeholder="Pantalla / Centro de carga / Batería" class="px-3 py-2 tpl-surface text-sm">
                <div class="md:col-span-3">
                    <button type="submit" class="px-4 py-2 text-xs font-medium tpl-btn">Registrar orden</button>
                </div>
            </form>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="border-b" style="border-color: var(--tpl-border);">
                            <th class="py-2 px-3 tpl-muted font-medium">Folio</th>
                            <th class="py-2 px-3 tpl-muted font-medium">Equipo</th>
                            <th class="py-2 px-3 tpl-muted font-medium">Falla</th>
                            <th class="py-2 px-3 tpl-muted font-medium">Estado</th>
                            <th class="py-2 px-3 tpl-muted font-medium">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y" style="border-color: var(--tpl-border);">
                        @forelse($ordenes as $ord)
                            <tr>
                                <td class="py-2 px-3 tpl-mono font-semibold">{{ $ord->folio }}</td>
                                <td class="py-2 px-3">{{ trim(($ord->marca ?? '') . ' ' . ($ord->modelo ?? '')) }}</td>
                                <td class="py-2 px-3 text-xs tpl-muted">{{ $ord->falla_reportada ?? 'Sin detalle' }}</td>
                                <td class="py-2 px-3 text-xs tpl-mono uppercase tpl-accent">{{ $ord->estado }}</td>
                                <td class="py-2 px-3">
                                    <form method="POST" action="{{ route('giro.celulares.ordenes.estado', $ord->id) }}" class="flex gap-2">
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
                            <tr><td colspan="5" class="py-4 px-3 text-center tpl-muted">Sin órdenes registradas aún.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        @if(in_array('taller_promociones', $paginasEmp, true))
            <section class="tpl-card p-6 space-y-4">
                <h2 class="text-lg font-semibold">Promociones y Combos (Reparaciones)</h2>
                <form method="POST" action="{{ route('giro.celulares.promociones.store') }}" class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    @csrf
                    <input type="text" name="nombre" required placeholder="Combo cambio pantalla + mica hidrogel" class="px-3 py-2 tpl-surface text-sm">
                    <input type="number" step="0.01" name="precio_combo" placeholder="Precio combo" min="0" class="px-3 py-2 tpl-surface text-sm tpl-mono">
                    <input type="number" step="0.01" name="descuento_porcentaje" placeholder="% Descuento" min="0" max="100" class="px-3 py-2 tpl-surface text-sm tpl-mono">
                    <div class="md:col-span-3">
                        <input type="text" name="descripcion" placeholder="Detalles del combo" class="w-full px-3 py-2 tpl-surface text-sm">
                    </div>
                    <div class="md:col-span-3">
                        <button type="submit" class="px-4 py-2 text-xs font-medium tpl-btn">Guardar combo</button>
                    </div>
                </form>
                <div class="divide-y" style="border-color: var(--tpl-border);">
                    @forelse($promociones as $promo)
                        <div class="py-3 flex items-center justify-between gap-4">
                            <div>
                                <p class="text-sm font-semibold">{{ $promo->nombre }}</p>
                                <p class="text-xs tpl-muted">Combo: ${{ number_format((float) $promo->precio_combo, 2) }} • Descuento: {{ number_format((float) $promo->descuento_porcentaje, 2) }}% • {{ $promo->activa ? 'Activa' : 'Inactiva' }}</p>
                            </div>
                            <form method="POST" action="{{ route('giro.celulares.promociones.toggle', $promo->id) }}">
                                @csrf
                                <button type="submit" class="px-3 py-1 text-xs tpl-surface">{{ $promo->activa ? 'Desactivar' : 'Activar' }}</button>
                            </form>
                        </div>
                    @empty
                        <p class="py-3 text-sm tpl-muted">Sin promociones registradas aún.</p>
                    @endforelse
                </div>
            </section>
        @endif
    </div>
</body>
</html>
