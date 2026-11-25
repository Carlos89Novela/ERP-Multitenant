<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ferretería — {{ $empresa->nombre }}</title>
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
                <span class="text-xs font-semibold uppercase tpl-accent">GIRO: FERRETERÍA • BD AISLADA</span>
                <h1 class="text-2xl font-bold mt-1">{{ $empresa->nombre }} — Ferretería y Promociones</h1>
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
            <h2 class="text-lg font-semibold">Alta rápida de artículo de ferretería</h2>
            <form method="POST" action="{{ route('giro.ferreteria.productos.store') }}" class="grid grid-cols-1 md:grid-cols-4 gap-3">
                @csrf
                <input type="text" name="sku" placeholder="SKU / Clave" class="px-3 py-2 tpl-surface text-sm tpl-mono">
                <input type="text" name="nombre" required placeholder="Artículo / herramienta / material" class="px-3 py-2 tpl-surface text-sm">
                <input type="text" name="unidad_venta" required value="pieza" placeholder="pieza / metro / kilo / rollo" class="px-3 py-2 tpl-surface text-sm">
                <input type="number" step="0.01" name="precio_venta" required placeholder="Precio de venta" min="0" class="px-3 py-2 tpl-surface text-sm tpl-mono">
                <input type="number" step="0.01" name="costo_compra" placeholder="Costo compra" min="0" class="px-3 py-2 tpl-surface text-sm tpl-mono">
                <input type="number" step="0.001" name="existencia" placeholder="Existencia inicial" min="0" class="px-3 py-2 tpl-surface text-sm tpl-mono">
                <div class="md:col-span-2 flex justify-end">
                    <button type="submit" class="px-4 py-2 text-xs font-medium tpl-btn">Guardar artículo</button>
                </div>
            </form>
            <div class="divide-y" style="border-color: var(--tpl-border);">
                @forelse($productos as $p)
                    <div class="py-2.5 flex items-center justify-between text-sm">
                        <div><span class="font-semibold">{{ $p->nombre }}</span> <span class="text-xs tpl-muted">({{ $p->unidad_venta }})</span></div>
                        <div class="tpl-mono text-xs">${{ number_format((float) $p->precio_venta, 2) }} • Stock: {{ $p->existencia }}</div>
                    </div>
                @empty
                    <p class="py-3 text-sm tpl-muted">Sin artículos registrados aún.</p>
                @endforelse
            </div>
        </section>

        @if(in_array('ferreteria_promociones', $paginasEmp, true))
            <section class="tpl-card p-6 space-y-4">
                <h2 class="text-lg font-semibold">Promociones y Descuentos (Ferretería / Proveedores)</h2>
                <form method="POST" action="{{ route('giro.ferreteria.promociones.store') }}" class="grid grid-cols-1 md:grid-cols-4 gap-3">
                    @csrf
                    <input type="text" name="nombre" required placeholder="Descuento por volumen / campaña" class="px-3 py-2 tpl-surface text-sm">
                    <input type="text" name="proveedor_campana" placeholder="Proveedor o marca" class="px-3 py-2 tpl-surface text-sm">
                    <select name="tipo_promocion" class="px-3 py-2 tpl-surface text-sm">
                        <option value="porcentaje">Porcentaje (%)</option>
                        <option value="volumen">Por volumen</option>
                        <option value="precio_especial">Precio especial</option>
                    </select>
                    <div class="flex gap-2">
                        <input type="number" step="0.01" name="valor_descuento" placeholder="Valor" min="0" class="w-full px-3 py-2 tpl-surface text-sm tpl-mono">
                        <button type="submit" class="px-4 py-2 text-xs font-medium tpl-btn">Guardar</button>
                    </div>
                </form>
                <div class="divide-y" style="border-color: var(--tpl-border);">
                    @forelse($promociones as $promo)
                        <div class="py-3 flex items-center justify-between gap-4">
                            <div>
                                <p class="text-sm font-semibold">{{ $promo->nombre }} <span class="text-xs tpl-muted">({{ $promo->tipo_promocion }})</span></p>
                                <p class="text-xs tpl-muted">Proveedor: {{ $promo->proveedor_campana ?? 'General' }} • Valor: {{ $promo->valor_descuento }} • {{ $promo->activa ? 'Activa' : 'Inactiva' }}</p>
                            </div>
                            <form method="POST" action="{{ route('giro.ferreteria.promociones.toggle', $promo->id) }}">
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
