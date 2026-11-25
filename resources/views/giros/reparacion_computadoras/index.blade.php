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
        .tpl-muted { color: var(--tpl-text-muted); }
        .tpl-accent { color: var(--tpl-accent); }
        .tpl-mono { font-family: var(--tpl-font-mono); }
    </style>
</head>
<body class="min-h-screen antialiased">
    @php $paginasEmp = $empresa->paginas_habilitadas ?? []; @endphp
    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-8 space-y-6">
        <header class="tpl-card p-6 space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <span class="text-xs font-semibold uppercase tpl-accent">GIRO: REPARACIÓN DE COMPUTADORAS • BD AISLADA</span>
                    <h1 class="text-2xl font-bold mt-1">{{ $empresa->nombre }}</h1>
                    <p class="text-xs tpl-muted mt-1">Sin datos de prueba iniciales. Se muestran solo las páginas habilitadas para esta empresa.</p>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('empresa.portal') }}" class="px-3.5 py-2 text-xs font-medium tpl-surface">Portal empresa</a>
                    <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="px-3.5 py-2 text-xs font-medium tpl-surface">Cerrar sesión</button></form>
                </div>
            </div>

            <nav class="flex flex-wrap gap-2 pt-2 border-t" style="border-color: var(--tpl-border);">
                @if(in_array('dashboard', $paginasEmp, true))
                    <a href="{{ route('giro.computadoras.index') }}" class="px-3 py-1.5 text-xs font-medium {{ $seccion === 'dashboard' ? 'tpl-btn' : 'tpl-surface' }}">Dashboard</a>
                @endif
                @if(in_array('ordenes_taller', $paginasEmp, true))
                    <a href="{{ route('giro.computadoras.ordenes') }}" class="px-3 py-1.5 text-xs font-medium {{ $seccion === 'ordenes' ? 'tpl-btn' : 'tpl-surface' }}">Órdenes</a>
                @endif
                @if(in_array('inventario', $paginasEmp, true))
                    <a href="{{ route('giro.computadoras.inventario') }}" class="px-3 py-1.5 text-xs font-medium {{ $seccion === 'inventario' ? 'tpl-btn' : 'tpl-surface' }}">Inventario</a>
                    <a href="{{ route('giro.computadoras.transferencias') }}" class="px-3 py-1.5 text-xs font-medium {{ $seccion === 'transferencias' ? 'tpl-btn' : 'tpl-surface' }}">Transferencias</a>
                @endif
                @if(in_array('facturacion', $paginasEmp, true))
                    <a href="{{ route('giro.computadoras.facturacion') }}" class="px-3 py-1.5 text-xs font-medium {{ $seccion === 'facturacion' ? 'tpl-btn' : 'tpl-surface' }}">Facturación</a>
                @endif
                @if(in_array('taller_promociones', $paginasEmp, true))
                    <a href="{{ route('giro.computadoras.promociones') }}" class="px-3 py-1.5 text-xs font-medium {{ $seccion === 'promociones' ? 'tpl-btn' : 'tpl-surface' }}">Promociones</a>
                @endif
                @if(in_array('autogestion_apariencia', $paginasEmp, true))
                    <a href="{{ route('giro.computadoras.autogestion') }}" class="px-3 py-1.5 text-xs font-medium {{ $seccion === 'autogestion' ? 'tpl-btn' : 'tpl-surface' }}">Autogestión Visual</a>
                @endif
            </nav>
        </header>

        @if(session('status'))
            <div class="tpl-card p-4 border-l-4" style="border-left-color: var(--tpl-accent);"><p class="text-sm font-medium tpl-accent">{{ session('status') }}</p></div>
        @endif

        @if($errors->any())
            <div class="tpl-card p-4 border-l-4 border-rose-500">
                <ul class="text-xs text-rose-400 list-disc list-inside space-y-1">
                    @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        @if($seccion === 'dashboard')
            <section class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="tpl-card p-4"><p class="text-xs tpl-muted">Órdenes registradas</p><p class="text-2xl font-bold tpl-mono mt-1">{{ $kpis['ordenes'] ?? 0 }}</p></div>
                <div class="tpl-card p-4"><p class="text-xs tpl-muted">Equipos en inventario</p><p class="text-2xl font-bold tpl-mono mt-1">{{ $kpis['equipos'] ?? 0 }}</p></div>
                <div class="tpl-card p-4"><p class="text-xs tpl-muted">Transferencias</p><p class="text-2xl font-bold tpl-mono mt-1">{{ $kpis['transferencias'] ?? 0 }}</p></div>
                <div class="tpl-card p-4"><p class="text-xs tpl-muted">Promociones/Combos</p><p class="text-2xl font-bold tpl-mono mt-1">{{ $kpis['promociones'] ?? 0 }}</p></div>
            </section>
        @elseif($seccion === 'ordenes')
            <section class="tpl-card p-6 space-y-4">
                <h2 class="text-lg font-semibold">Nueva Orden de Reparación</h2>
                <form method="POST" action="{{ route('giro.computadoras.ordenes.store') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @csrf
                    <input type="text" name="folio" required placeholder="Folio (ej. PC-0001)" class="px-3 py-2 tpl-surface text-sm tpl-mono">
                    <input type="text" name="tipo_equipo" required placeholder="Laptop / PC Escritorio" class="px-3 py-2 tpl-surface text-sm">
                    <input type="text" name="marca" placeholder="Marca" class="px-3 py-2 tpl-surface text-sm">
                    <input type="text" name="modelo" placeholder="Modelo" class="px-3 py-2 tpl-surface text-sm">
                    <input type="text" name="numero_serie" placeholder="Número de serie (opcional)" class="px-3 py-2 tpl-surface text-sm tpl-mono">
                    <input type="text" name="falla_reportada" placeholder="Falla reportada" class="px-3 py-2 tpl-surface text-sm">
                    <div class="md:col-span-3"><button type="submit" class="px-4 py-2 text-xs font-medium tpl-btn">Registrar orden</button></div>
                </form>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-sm">
                        <thead><tr class="border-b" style="border-color: var(--tpl-border);"><th class="py-2 px-3 tpl-muted">Folio</th><th class="py-2 px-3 tpl-muted">Equipo</th><th class="py-2 px-3 tpl-muted">Falla</th><th class="py-2 px-3 tpl-muted">Estado</th><th class="py-2 px-3 tpl-muted">Acción</th></tr></thead>
                        <tbody class="divide-y" style="border-color: var(--tpl-border);">
                            @forelse($ordenes as $ord)
                                <tr>
                                    <td class="py-2 px-3 tpl-mono font-semibold">{{ $ord->folio }}</td>
                                    <td class="py-2 px-3">{{ $ord->tipo_equipo ?? 'Equipo' }} <span class="text-xs tpl-muted">{{ trim(($ord->marca ?? '') . ' ' . ($ord->modelo ?? '')) }}</span></td>
                                    <td class="py-2 px-3 text-xs tpl-muted">{{ $ord->falla_reportada ?? 'Sin detalle' }}</td>
                                    <td class="py-2 px-3 text-xs tpl-mono uppercase tpl-accent">{{ $ord->estado }}</td>
                                    <td class="py-2 px-3">
                                        <form method="POST" action="{{ route('giro.computadoras.ordenes.estado', $ord->id) }}" class="flex gap-2">
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
        @elseif($seccion === 'inventario')
            <section class="tpl-card p-6 space-y-4">
                <h2 class="text-lg font-semibold">Inventario de Equipos y Refacciones Registradas</h2>
                <div class="divide-y" style="border-color: var(--tpl-border);">
                    @forelse($equipos as $eq)
                        <div class="py-2.5 flex items-center justify-between text-sm">
                            <div><span class="font-semibold">{{ $eq->tipo_equipo }}</span> <span class="text-xs tpl-muted">{{ trim(($eq->marca ?? '') . ' ' . ($eq->modelo ?? '')) }}</span></div>
                            <span class="text-xs tpl-mono tpl-muted">Serie: {{ $eq->numero_serie ?? 'N/A' }}</span>
                        </div>
                    @empty
                        <p class="py-4 text-sm tpl-muted">Sin registros de inventario todavía.</p>
                    @endforelse
                </div>
            </section>
        @elseif($seccion === 'transferencias')
            <section class="tpl-card p-6 space-y-4">
                <h2 class="text-lg font-semibold">Transferencias entre Sucursales</h2>
                <form method="POST" action="{{ route('giro.computadoras.transferencias.store') }}" class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    @csrf
                    <input type="text" name="folio" required placeholder="TRF-0001" class="px-3 py-2 tpl-surface text-sm tpl-mono">
                    <input type="text" name="origen_sucursal" required placeholder="Sucursal origen" class="px-3 py-2 tpl-surface text-sm">
                    <input type="text" name="destino_sucursal" required placeholder="Sucursal destino" class="px-3 py-2 tpl-surface text-sm">
                    <input type="text" name="concepto" required placeholder="Equipo o refacción a transferir" class="px-3 py-2 tpl-surface text-sm">
                    <input type="number" step="0.001" name="cantidad" required value="1" min="0.001" class="px-3 py-2 tpl-surface text-sm tpl-mono">
                    <input type="text" name="notas" placeholder="Notas" class="px-3 py-2 tpl-surface text-sm">
                    <div class="md:col-span-3"><button type="submit" class="px-4 py-2 text-xs font-medium tpl-btn">Registrar transferencia</button></div>
                </form>
                <div class="divide-y" style="border-color: var(--tpl-border);">
                    @forelse($transferencias as $trf)
                        <div class="py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 text-sm">
                            <div>
                                <span class="tpl-mono font-semibold">{{ $trf->folio }}</span> • {{ $trf->concepto }}
                                <span class="text-xs tpl-muted">({{ $trf->origen_sucursal }} → {{ $trf->destino_sucursal }} • Cant: {{ $trf->cantidad }})</span>
                            </div>
                            <form method="POST" action="{{ route('giro.computadoras.transferencias.estado', $trf->id) }}" class="flex gap-2">
                                @csrf
                                <select name="estado" class="px-2 py-1 tpl-surface text-xs">
                                    @foreach(['pendiente','en_transito','recibida','cancelada'] as $st)
                                        <option value="{{ $st }}" @selected($trf->estado === $st)>{{ $st }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="px-2.5 py-1 text-xs tpl-btn">Actualizar</button>
                            </form>
                        </div>
                    @empty
                        <p class="py-4 text-sm tpl-muted">Sin transferencias registradas aún.</p>
                    @endforelse
                </div>
            </section>
        @elseif($seccion === 'facturacion')
            <section class="tpl-card p-6 space-y-2">
                <h2 class="text-lg font-semibold">Facturación CFDI y Saldo de Timbres</h2>
                <p class="text-sm tpl-muted">Timbres asignados a la empresa: <span class="tpl-mono font-bold tpl-accent">{{ number_format((int) $empresa->saldo_timbres) }}</span></p>
                <p class="text-xs tpl-muted">Estado de licencia: <span class="tpl-mono uppercase">{{ $empresa->evaluarEstadoLicenciaEfectivo() }}</span></p>
            </section>
        @elseif($seccion === 'promociones')
            <section class="tpl-card p-6 space-y-4">
                <h2 class="text-lg font-semibold">Promociones y Combos (Reparaciones)</h2>
                <form method="POST" action="{{ route('giro.computadoras.promociones.store') }}" class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    @csrf
                    <input type="text" name="nombre" required placeholder="Combo diagnóstico + limpieza interna" class="px-3 py-2 tpl-surface text-sm">
                    <input type="number" step="0.01" name="precio_combo" placeholder="Precio combo" min="0" class="px-3 py-2 tpl-surface text-sm tpl-mono">
                    <input type="number" step="0.01" name="descuento_porcentaje" placeholder="% Descuento" min="0" max="100" class="px-3 py-2 tpl-surface text-sm tpl-mono">
                    <div class="md:col-span-3"><input type="text" name="descripcion" placeholder="Detalles del combo" class="w-full px-3 py-2 tpl-surface text-sm"></div>
                    <div class="md:col-span-3"><button type="submit" class="px-4 py-2 text-xs font-medium tpl-btn">Guardar combo</button></div>
                </form>
                <div class="divide-y" style="border-color: var(--tpl-border);">
                    @forelse($promociones as $promo)
                        <div class="py-3 flex items-center justify-between gap-4">
                            <div>
                                <p class="text-sm font-semibold">{{ $promo->nombre }}</p>
                                <p class="text-xs tpl-muted">Combo: ${{ number_format((float) $promo->precio_combo, 2) }} • Descuento: {{ number_format((float) $promo->descuento_porcentaje, 2) }}% • {{ $promo->activa ? 'Activa' : 'Inactiva' }}</p>
                            </div>
                            <form method="POST" action="{{ route('giro.computadoras.promociones.toggle', $promo->id) }}">
                                @csrf
                                <button type="submit" class="px-3 py-1 text-xs tpl-surface">{{ $promo->activa ? 'Desactivar' : 'Activar' }}</button>
                            </form>
                        </div>
                    @empty
                        <p class="py-4 text-sm tpl-muted">Sin promociones registradas aún.</p>
                    @endforelse
                </div>
            </section>
        @elseif($seccion === 'autogestion')
            <section class="tpl-card p-6 space-y-2">
                <h2 class="text-lg font-semibold">Autogestión Visual del Propietario</h2>
                <p class="text-xs tpl-muted">Template activo: <span class="tpl-mono">{{ $template->nombre ?? 'Predeterminado' }}</span> • Fuente: <span class="tpl-mono">{{ $template->fuente_primaria ?? 'Inter' }}</span></p>
            </section>
        @endif
    </div>
</body>
</html>
