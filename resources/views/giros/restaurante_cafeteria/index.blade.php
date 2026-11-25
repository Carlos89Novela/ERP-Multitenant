<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurante / Cafetería — {{ $empresa->nombre }}</title>
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
        <header class="tpl-card p-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <span class="text-xs font-semibold uppercase tpl-accent">GIRO: RESTAURANTE / CAFETERÍA • BD AISLADA</span>
                <h1 class="text-2xl font-bold mt-1">{{ $empresa->nombre }} — Operación de Restaurante</h1>
                <p class="text-xs tpl-muted mt-1">Sin datos de prueba iniciales. Solo se muestran los módulos habilitados para esta empresa.</p>
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

        @if(in_array('restaurante_comandas', $paginasEmp, true))
            <section class="tpl-card p-6 space-y-4">
                <h2 class="text-lg font-semibold">Comandero, Mesas y KDS</h2>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <form method="POST" action="{{ route('giro.restaurante.mesas.store') }}" class="space-y-3">
                        @csrf
                        <h3 class="text-sm font-semibold">Nueva mesa</h3>
                        <div class="grid grid-cols-2 gap-3">
                            <input type="text" name="nombre_numero" required placeholder="Mesa 1 / Barra 2" class="px-3 py-2 tpl-surface text-sm">
                            <input type="number" name="capacidad" required value="4" min="1" max="100" class="px-3 py-2 tpl-surface text-sm tpl-mono">
                        </div>
                        <button type="submit" class="px-4 py-2 text-xs font-medium tpl-btn">Agregar mesa</button>
                    </form>

                    <form method="POST" action="{{ route('giro.restaurante.comandas.store') }}" class="space-y-3">
                        @csrf
                        <h3 class="text-sm font-semibold">Abrir comanda</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <input type="text" name="folio" required placeholder="CMD-001" class="px-3 py-2 tpl-surface text-sm tpl-mono">
                            <select name="mesa_id" class="px-3 py-2 tpl-surface text-sm">
                                <option value="">Para llevar / Sin mesa</option>
                                @foreach($mesas as $m)
                                    <option value="{{ $m->id }}">{{ $m->nombre_numero }}</option>
                                @endforeach
                            </select>
                            <input type="number" step="0.01" name="total" value="0.00" min="0" class="px-3 py-2 tpl-surface text-sm tpl-mono">
                        </div>
                        <input type="text" name="notas" placeholder="Notas de cocina / comensal" class="w-full px-3 py-2 tpl-surface text-sm">
                        <button type="submit" class="px-4 py-2 text-xs font-medium tpl-btn">Crear comanda</button>
                    </form>
                </div>

                <div class="overflow-x-auto pt-2">
                    <table class="w-full text-left border-collapse text-sm">
                        <thead>
                            <tr class="border-b" style="border-color: var(--tpl-border);">
                                <th class="py-2 px-3 tpl-muted font-medium">Folio</th>
                                <th class="py-2 px-3 tpl-muted font-medium">Mesa</th>
                                <th class="py-2 px-3 tpl-muted font-medium">Notas</th>
                                <th class="py-2 px-3 tpl-muted font-medium">Estado</th>
                                <th class="py-2 px-3 tpl-muted font-medium">Acción</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y" style="border-color: var(--tpl-border);">
                            @forelse($comandas as $cmd)
                                <tr>
                                    <td class="py-2 px-3 tpl-mono font-semibold">{{ $cmd->folio }}</td>
                                    <td class="py-2 px-3">{{ $cmd->mesa_nombre ?? 'Para llevar' }}</td>
                                    <td class="py-2 px-3 text-xs tpl-muted">{{ $cmd->notas ?? '—' }}</td>
                                    <td class="py-2 px-3 text-xs tpl-mono uppercase tpl-accent">{{ $cmd->estado }}</td>
                                    <td class="py-2 px-3">
                                        <form method="POST" action="{{ route('giro.restaurante.comandas.estado', $cmd->id) }}" class="flex gap-2">
                                            @csrf
                                            <select name="estado" class="px-2 py-1 tpl-surface text-xs">
                                                @foreach(['abierta','preparacion','lista','cerrada','cancelada'] as $st)
                                                    <option value="{{ $st }}" @selected($cmd->estado === $st)>{{ $st }}</option>
                                                @endforeach
                                            </select>
                                            <button type="submit" class="px-2.5 py-1 text-xs tpl-btn">Actualizar</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="py-4 px-3 text-center tpl-muted">Sin comandas registradas.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        @endif

        @if(in_array('restaurante_recetas', $paginasEmp, true))
            <section class="tpl-card p-6 space-y-4">
                <h2 class="text-lg font-semibold">Recetas y Platillos (Alta / Activar-Desactivar)</h2>
                <form method="POST" action="{{ route('giro.restaurante.recetas.store') }}" class="grid grid-cols-1 md:grid-cols-4 gap-3">
                    @csrf
                    <input type="text" name="nombre" required placeholder="Nombre del platillo o receta" class="px-3 py-2 tpl-surface text-sm">
                    <input type="text" name="categoria" placeholder="Categoría (ej. Desayunos, Bebidas)" class="px-3 py-2 tpl-surface text-sm">
                    <input type="number" step="0.01" name="costo_insumos" placeholder="Costo insumos" min="0" class="px-3 py-2 tpl-surface text-sm tpl-mono">
                    <input type="number" step="0.01" name="precio_venta" required placeholder="Precio de venta" min="0" class="px-3 py-2 tpl-surface text-sm tpl-mono">
                    <div class="md:col-span-4">
                        <input type="text" name="ingredientes_instrucciones" placeholder="Ingredientes y preparación" class="w-full px-3 py-2 tpl-surface text-sm">
                    </div>
                    <div class="md:col-span-4">
                        <button type="submit" class="px-4 py-2 text-xs font-medium tpl-btn">Guardar receta</button>
                    </div>
                </form>

                <div class="divide-y" style="border-color: var(--tpl-border);">
                    @forelse($recetas as $rec)
                        <div class="py-3 flex items-center justify-between gap-4">
                            <div>
                                <p class="text-sm font-semibold">{{ $rec->nombre }} <span class="text-xs tpl-muted">({{ $rec->categoria ?? 'General' }})</span></p>
                                <p class="text-xs tpl-muted">Precio: ${{ number_format((float) $rec->precio_venta, 2) }} • Estado: {{ $rec->activa ? 'Activa' : 'Inactiva' }}</p>
                            </div>
                            <form method="POST" action="{{ route('giro.restaurante.recetas.toggle', $rec->id) }}">
                                @csrf
                                <button type="submit" class="px-3 py-1 text-xs tpl-surface">
                                    {{ $rec->activa ? 'Desactivar' : 'Activar' }}
                                </button>
                            </form>
                        </div>
                    @empty
                        <p class="py-4 text-sm tpl-muted">Sin recetas registradas todavía.</p>
                    @endforelse
                </div>
            </section>
        @endif

        @if(in_array('restaurante_promociones', $paginasEmp, true))
            <section class="tpl-card p-6 space-y-4">
                <h2 class="text-lg font-semibold">Promociones y Combos (Restaurante)</h2>
                <form method="POST" action="{{ route('giro.restaurante.promociones.store') }}" class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    @csrf
                    <input type="text" name="nombre" required placeholder="Combo comida corrida / 2x1 bebidas" class="px-3 py-2 tpl-surface text-sm">
                    <input type="number" step="0.01" name="precio_combo" placeholder="Precio combo" min="0" class="px-3 py-2 tpl-surface text-sm tpl-mono">
                    <input type="number" step="0.01" name="descuento_porcentaje" placeholder="% Descuento" min="0" max="100" class="px-3 py-2 tpl-surface text-sm tpl-mono">
                    <div class="md:col-span-3">
                        <input type="text" name="descripcion" placeholder="Condiciones de la promoción o combo" class="w-full px-3 py-2 tpl-surface text-sm">
                    </div>
                    <div class="md:col-span-3">
                        <button type="submit" class="px-4 py-2 text-xs font-medium tpl-btn">Guardar promoción / combo</button>
                    </div>
                </form>

                <div class="divide-y" style="border-color: var(--tpl-border);">
                    @forelse($promociones as $promo)
                        <div class="py-3 flex items-center justify-between gap-4">
                            <div>
                                <p class="text-sm font-semibold">{{ $promo->nombre }}</p>
                                <p class="text-xs tpl-muted">Combo: ${{ number_format((float) $promo->precio_combo, 2) }} • Descuento: {{ number_format((float) $promo->descuento_porcentaje, 2) }}% • {{ $promo->activa ? 'Activa' : 'Inactiva' }}</p>
                            </div>
                            <form method="POST" action="{{ route('giro.restaurante.promociones.toggle', $promo->id) }}">
                                @csrf
                                <button type="submit" class="px-3 py-1 text-xs tpl-surface">
                                    {{ $promo->activa ? 'Desactivar' : 'Activar' }}
                                </button>
                            </form>
                        </div>
                    @empty
                        <p class="py-4 text-sm tpl-muted">Sin promociones registradas todavía.</p>
                    @endforelse
                </div>
            </section>
        @endif
    </div>
</body>
</html>
