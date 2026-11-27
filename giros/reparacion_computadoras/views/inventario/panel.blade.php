<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventario — {{ $empresa->nombre ?? 'Taller de Cómputo' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --taller-font: {{ $configInternas['fuente_familia'] ?? 'Inter, system-ui, -apple-system, sans-serif' }};
            --taller-primary: {{ $configInternas['color_primario'] ?? '#6366f1' }};
            --taller-accent: {{ $configInternas['color_acento'] ?? '#10b981' }};
            --taller-bg: {{ $configInternas['color_fondo'] ?? '#09090b' }};
            --taller-card: {{ $configInternas['color_tarjeta'] ?? '#111115' }};
            --taller-surface: #18181b;
            --taller-text: #f4f4f5;
            --taller-muted: #a1a1aa;
            --taller-border: #27272a;
            --taller-radius: 0.75rem;
        }
        body { font-family: var(--taller-font); background-color: var(--taller-bg); color: var(--taller-text); }
        .taller-card { background-color: var(--taller-card); border: 1px solid var(--taller-border); border-radius: var(--taller-radius); }
        .taller-surface { background-color: var(--taller-surface); border: 1px solid var(--taller-border); border-radius: var(--taller-radius); }
    </style>
</head>
<body class="min-h-screen p-4 md:p-8 antialiased">
<div class="max-w-7xl mx-auto space-y-6">
        @php
            $esPropietarioGlobal = isset($usuario) && method_exists($usuario, 'esPropietarioGlobal') ? $usuario->esPropietarioGlobal() : true;
            $sucursalUsuario = (! $esPropietarioGlobal && isset($usuario) && method_exists($usuario, 'sucursalAsignadaId')) ? $usuario->sucursalAsignadaId() : null;
                $rolUsuarioActual = strtolower(trim((string) ($usuario->rol_empresa ?? '')));
    $esGerente = in_array($rolUsuarioActual, ['gerente', 'gerente_sucursal', 'encargado', 'encargado_sucursal'], true);
    $puedeVerTransito = $esPropietarioGlobal || $esGerente;
@endphp
@php
$almacenesVisibles = ($matriz['almacenes'] ?? collect())->filter(function ($alm) use ($esPropietarioGlobal, $sucursalUsuario, $puedeVerTransito) {
    if ($esPropietarioGlobal) {
        return true;
    }
    if ((bool) ($alm->es_virtual ?? false)) {
        return $puedeVerTransito;
    }
    return $sucursalUsuario !== null && (int) $alm->sucursal_id === (int) $sucursalUsuario;
});
            $idsAlmacenesVisibles = $almacenesVisibles->pluck('id')->map(fn ($id) => (int) $id)->all();
            $existenciasVisibles = collect($existencias ?? [])->filter(function ($ext) use ($esPropietarioGlobal, $idsAlmacenesVisibles) {
                if ($esPropietarioGlobal) return true;
                return in_array((int) ($ext['almacen_id'] ?? 0), $idsAlmacenesVisibles, true);
            });
            $transferenciasVisibles = collect($transferencias ?? [])->filter(function ($trf) use ($esPropietarioGlobal, $idsAlmacenesVisibles) {
                if ($esPropietarioGlobal) return true;
                return in_array((int) $trf->almacen_origen_id, $idsAlmacenesVisibles, true)
                    || in_array((int) $trf->almacen_destino_id, $idsAlmacenesVisibles, true);
            });
        @endphp
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b pb-4" style="border-color: var(--taller-border);">
        <div>
            <p class="text-[11px] font-semibold uppercase tracking-wider" style="color: var(--taller-accent);">
                {{ $empresa->nombre ?? 'REPARACIÓN DE COMPUTADORAS' }} — VISTA: {{ strtoupper($seccionActiva ?? 'general') }}
            </p>
            <h1 class="text-2xl font-bold">Inventario, Sucursales y Almacenes</h1>
        </div>
        @php
            $sec = $seccionActiva ?? 'general';
            $pestanas = [
                'general' => ['label' => 'Todo', 'ruta' => route('taller.computadoras.inventario')],
                'sucursales' => ['label' => 'Sucursales', 'ruta' => route('taller.computadoras.inventario.sucursales')],
                'entradas' => ['label' => 'Entradas y Catálogo', 'ruta' => route('taller.computadoras.inventario.entradas')],
                'salidas_ajustes' => ['label' => 'Salidas y Ajustes', 'ruta' => route('taller.computadoras.inventario.salidas_ajustes')],
                'traspasos' => ['label' => 'Traspasos', 'ruta' => route('taller.computadoras.inventario.traspasos')],
                'existencias' => ['label' => 'Existencias y Kardex', 'ruta' => route('taller.computadoras.inventario.existencias')],
            ];
        @endphp
        <div class="flex flex-wrap items-center gap-2 text-xs">
            <a href="{{ route('taller.computadoras.dashboard') }}" class="px-3 py-2 rounded-lg taller-surface hover:opacity-90">← Dashboard</a>
            @foreach ($pestanas as $clave => $info)
                <a
                    href="{{ $info['ruta'] }}"
                    class="px-3 py-2 rounded-lg font-medium transition hover:opacity-90 {{ $sec === $clave ? 'text-white' : 'taller-surface' }}"
                    @if ($sec === $clave) style="background-color: var(--taller-primary);" @endif
                >
                    {{ $info['label'] }}
                </a>
            @endforeach
        </div>
    </div>

    @if (session('usuarios_sucursal_creados') && is_array(session('usuarios_sucursal_creados')))
        <div class="rounded-lg border border-indigo-500/50 bg-indigo-950/30 px-4 py-3 text-xs space-y-1">
            <p class="font-semibold text-indigo-200">Usuarios creados automáticamente para la sucursal:</p>
            <ul class="list-disc pl-5 space-y-0.5 text-indigo-100">
                @foreach (session('usuarios_sucursal_creados') as $uc)
                    <li><span class="font-semibold uppercase">{{ $uc['rol'] }}</span> — {{ $uc['email'] }} (Contraseña inicial: <span class="font-mono">{{ $uc['password_temporal'] }}</span>)</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (session('success'))
        <div class="rounded-lg border border-emerald-500/50 bg-emerald-950/40 px-4 py-3 text-sm text-emerald-200">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="rounded-lg border border-rose-500/50 bg-rose-950/40 px-4 py-3 text-sm text-rose-200">
            <ul class="list-disc pl-5 space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (in_array($sec, ['general', 'sucursales'], true))
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 taller-card p-5">
            <h2 class="text-lg font-semibold mb-3">Sucursales y Almacenes</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="border-b text-xs uppercase" style="border-color: var(--taller-border); color: var(--taller-muted);">
                        <tr>
                            <th class="py-2 pr-3">Almacén</th>
                            <th class="py-2 px-3">Código</th>
                            <th class="py-2 px-3">Sucursal</th>
                            <th class="py-2 px-3">Tipo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y" style="border-color: var(--taller-border);">
                        @foreach ($almacenesVisibles as $alm)
                            <tr>
                                <td class="py-2 pr-3 font-medium">{{ $alm->nombre }}</td>
                                <td class="py-2 px-3 font-mono text-xs">{{ $alm->codigo }}</td>
                                <td class="py-2 px-3">{{ $alm->sucursal?->nombre ?? 'Traspaso / Tránsito' }}</td>
                                <td class="py-2 px-3 text-xs">{{ $alm->tipo }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="taller-card p-5">
            <h2 class="text-lg font-semibold mb-3">Crear Nueva Sucursal</h2>
            <form method="POST" action="{{ route('taller.inventario.sucursales.store') }}" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-xs mb-1" style="color: var(--taller-muted);">Nombre de sucursal</label>
                    <input type="text" name="nombre" required placeholder="Ej. Sucursal Norte" class="w-full px-3 py-2 taller-surface text-sm">
                </div>
                <div>
                    <label class="block text-xs mb-1" style="color: var(--taller-muted);">Código (opcional)</label>
                    <input type="text" name="codigo" placeholder="Ej. SUC-NORTE" class="w-full px-3 py-2 taller-surface text-sm">
                </div>
                <button type="submit" class="w-full py-2 px-4 rounded-lg text-sm font-semibold" style="background-color: var(--taller-primary); color: #fff;">
                    Crear Sucursal y Almacén
                </button>
            </form>
        </div>
    </div>
    @endif

    @if (in_array($sec, ['general', 'entradas'], true))
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="taller-card p-5 space-y-4">
            <h2 class="text-lg font-semibold">Catálogo</h2>
            <form method="POST" action="{{ route('taller.inventario.categorias.store') }}" class="space-y-2 border-b pb-4" style="border-color: var(--taller-border);">
                @csrf
                <p class="text-xs font-semibold uppercase" style="color: var(--taller-muted);">Nueva Categoría</p>
                <input type="text" name="nombre" required placeholder="Ej. SSD y NVMe" class="w-full px-3 py-1.5 taller-surface text-sm">
                <button type="submit" class="w-full py-1.5 px-3 rounded-lg text-xs font-semibold taller-surface">Guardar Categoría</button>
            </form>

            <form method="POST" action="{{ route('taller.inventario.productos.store') }}" class="space-y-2">
                @csrf
                <p class="text-xs font-semibold uppercase" style="color: var(--taller-muted);">Nuevo Producto / Refacción</p>
                <select name="categoria_id" class="w-full px-3 py-1.5 taller-surface text-sm">
                    <option value="">Sin categoría</option>
                    @foreach (($categorias ?? collect()) as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->nombre }}</option>
                    @endforeach
                </select>
                <input type="text" name="nombre" required placeholder="Nombre de refacción" class="w-full px-3 py-1.5 taller-surface text-sm">
                <input type="text" name="sku" placeholder="SKU (opcional)" class="w-full px-3 py-1.5 taller-surface text-sm">
                <div class="grid grid-cols-2 gap-2">
                    <input type="number" step="0.0001" min="0" name="costo_referencia" placeholder="Costo ref." class="w-full px-3 py-1.5 taller-surface text-sm">
                    <input type="number" step="0.0001" min="0" name="precio_venta" placeholder="Precio venta" class="w-full px-3 py-1.5 taller-surface text-sm">
                </div>
                <button type="submit" class="w-full py-2 px-3 rounded-lg text-xs font-semibold" style="background-color: var(--taller-primary); color: #fff;">
                    Registrar Producto
                </button>
            </form>
        </div>

        <div class="lg:col-span-2 taller-card p-5 space-y-4">
            <h2 class="text-lg font-semibold">Entrada de Inventario (Costo Promedio Ponderado)</h2>
            <form method="POST" action="{{ route('taller.inventario.entradas.store') }}" class="space-y-3">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs mb-1" style="color: var(--taller-muted);">Almacén</label>
                        <select name="almacen_id" required class="w-full px-3 py-2 taller-surface text-sm">
                            @foreach ($almacenesVisibles as $alm)
                                @if (! $alm->es_virtual)
                                    <option value="{{ $alm->id }}">{{ $alm->nombre }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs mb-1" style="color: var(--taller-muted);">Producto</label>
                        <select name="lineas[0][producto_id]" required class="w-full px-3 py-2 taller-surface text-sm">
                            @foreach (($productos ?? collect()) as $prod)
                                <option value="{{ $prod->id }}">#{{ $prod->id }} — {{ $prod->sku }} — {{ $prod->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs mb-1" style="color: var(--taller-muted);">Referencia externa</label>
                        <input type="text" name="referencia_externa" placeholder="Factura / Folio" class="w-full px-3 py-2 taller-surface text-sm">
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs mb-1" style="color: var(--taller-muted);">Cantidad</label>
                        <input type="number" step="0.0001" min="0.0001" name="lineas[0][cantidad]" required class="w-full px-3 py-2 taller-surface text-sm">
                    </div>
                    <div>
                        <label class="block text-xs mb-1" style="color: var(--taller-muted);">Costo Unitario</label>
                        <input type="number" step="0.0001" min="0" name="lineas[0][costo_unitario]" required class="w-full px-3 py-2 taller-surface text-sm">
                    </div>
                    <div>
                        <label class="block text-xs mb-1" style="color: var(--taller-muted);">Motivo</label>
                        <div class="flex items-center gap-2">
                            <input type="text" name="motivo" required placeholder="Compra / abastecimiento" class="w-full px-3 py-2 taller-surface text-sm">
                            
                        </div>
                    </div>
                </div>
                <button type="submit" class="px-4 py-2 rounded-lg text-sm font-semibold" style="background-color: var(--taller-accent); color: #052e16;">
                    Registrar Entrada y Recalcular CPP
                </button>
            </form>

            <div class="pt-3 border-t" style="border-color: var(--taller-border);">
                <h3 class="text-xs font-semibold uppercase mb-2" style="color: var(--taller-muted);">Productos registrados</h3>
                <div class="overflow-x-auto max-h-48">
                    <table class="min-w-full text-left text-xs">
                        <thead class="border-b uppercase" style="border-color: var(--taller-border); color: var(--taller-muted);">
                            <tr>
                                <th class="py-1.5 pr-2">SKU</th>
                                <th class="py-1.5 px-2">Producto</th>
                                <th class="py-1.5 px-2 text-right">Costo ref.</th>
                                <th class="py-1.5 pl-2 text-right">Precio venta</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y" style="border-color: var(--taller-border);">
                            @foreach (($productos ?? collect()) as $prod)
                                <tr>
                                    <td class="py-1.5 pr-2 font-mono">{{ $prod->sku }}</td>
                                    <td class="py-1.5 px-2">{{ $prod->nombre }}</td>
                                    <td class="py-1.5 px-2 text-right font-mono">{{ $prod->costo_referencia }}</td>
                                    <td class="py-1.5 pl-2 text-right font-mono">{{ $prod->precio_venta }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @endif

    @if (in_array($sec, ['general', 'movimientos', 'salidas_ajustes', 'traspasos'], true))
    <div class="space-y-6">
    @if (in_array($sec, ['general', 'movimientos', 'salidas_ajustes'], true))
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="taller-card p-5 space-y-3">
                <h2 class="text-lg font-semibold">Salida Directa</h2>
                <form method="POST" action="{{ route('taller.inventario.salidas.store') }}" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-xs mb-1" style="color: var(--taller-muted);">Almacén</label>
                        <select name="almacen_id" required class="w-full px-3 py-2 taller-surface text-sm">
                            @foreach ($almacenesVisibles as $alm)
                                @if (! ($alm->es_virtual ?? false))
                                    <option value="{{ $alm->id }}">{{ $alm->nombre }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs mb-1" style="color: var(--taller-muted);">Producto</label>
                        <select name="producto_id" required class="w-full px-3 py-2 taller-surface text-sm">
                            @foreach (($productos ?? collect()) as $prod)
                                <option value="{{ $prod->id }}">#{{ $prod->id }} — {{ $prod->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs mb-1" style="color: var(--taller-muted);">Cantidad</label>
                        <input type="number" step="0.0001" min="0.0001" name="cantidad" required placeholder="Cantidad" class="w-full px-3 py-2 taller-surface text-sm">
                    </div>
                    <div>
                        <label class="block text-xs mb-1" style="color: var(--taller-muted);">Motivo</label>
                        <input type="text" name="motivo" required placeholder="Motivo" class="w-full px-3 py-2 taller-surface text-sm">
                    </div>
                    <button type="submit" class="w-full py-2 px-4 rounded-lg text-sm font-semibold bg-amber-600 hover:bg-amber-500 text-white">
                        Registrar Salida
                    </button>
                </form>
            </div>

            <div class="taller-card p-5 space-y-3">
                <h2 class="text-lg font-semibold">Ajuste de Inventario</h2>
                <form method="POST" action="{{ route('taller.inventario.ajustes.store') }}" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-xs mb-1" style="color: var(--taller-muted);">Almacén</label>
                        <select name="almacen_id" required class="w-full px-3 py-2 taller-surface text-sm">
                            @foreach ($almacenesVisibles as $alm)
                                @if (! ($alm->es_virtual ?? false))
                                    <option value="{{ $alm->id }}">{{ $alm->nombre }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs mb-1" style="color: var(--taller-muted);">Producto</label>
                        <select name="producto_id" required class="w-full px-3 py-2 taller-surface text-sm">
                            @foreach (($productos ?? collect()) as $prod)
                                <option value="{{ $prod->id }}">#{{ $prod->id }} — {{ $prod->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs mb-1" style="color: var(--taller-muted);">Existencia física objetivo</label>
                        <input type="number" step="0.0001" min="0" name="cantidad_fisica_objetivo" required placeholder="Existencia física objetivo" class="w-full px-3 py-2 taller-surface text-sm">
                    </div>
                    <div>
                        <label class="block text-xs mb-1" style="color: var(--taller-muted);">Motivo del ajuste</label>
                        <input type="text" name="motivo" required placeholder="Motivo del ajuste" class="w-full px-3 py-2 taller-surface text-sm">
                    </div>
                    <button type="submit" class="w-full py-2 px-4 rounded-lg text-sm font-semibold taller-surface border" style="border-color: var(--taller-border);">
                        Aplicar Ajuste
                    </button>
                </form>
            </div>
        </div>

            @endif

    @if (in_array($sec, ['general', 'movimientos', 'traspasos'], true))
<div class="taller-card p-5 space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h2 class="text-lg font-semibold">Traspasos Entre Almacenes</h2>
                    <p class="text-xs" style="color: var(--taller-muted);">Solicita múltiples artículos o consulta y gestiona despachos, recepciones y cancelaciones en su vista independiente.</p>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" id="tab-trf-solicitar" class="px-3 py-1.5 rounded-lg text-xs font-semibold text-white" style="background-color: var(--taller-primary);">
                        Solicitar Traspaso
                    </button>
                <button type="button" id="tab-trf-consulta" class="px-3 py-1.5 rounded-lg text-xs font-semibold taller-surface border" style="border-color: var(--taller-border);">
                    Consulta de Traspasos ({{ ($notifs ?? collect())->count() }})
                </button>
            </div>
        </div>

        <div id="panel-trf-solicitar" class="space-y-3">
            <form id="form-solicitar-traspaso" method="POST" action="{{ route('taller.inventario.transferencias.store') }}" class="space-y-3 border-b pb-4" style="border-color: var(--taller-border);">
            @csrf
            @php
                $todosAlmacenesReales = collect($matriz['almacenes'] ?? collect())->filter(fn ($a) => ! ($a->es_virtual ?? false));
                $almacenPropio = $todosAlmacenesReales->first(fn ($a) => $sucursalUsuario !== null && (int) $a->sucursal_id === (int) $sucursalUsuario)
                    ?? $almacenesVisibles->first(fn ($a) => ! ($a->es_virtual ?? false));
                $origenesDisponibles = $esPropietarioGlobal
                    ? $todosAlmacenesReales
                    : $todosAlmacenesReales->filter(fn ($a) => ! $almacenPropio || (int) $a->id !== (int) $almacenPropio->id);
            @endphp

            <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
                <div class="md:col-span-2">
                    <label class="block text-xs mb-1" style="color: var(--taller-muted);">Almacén a quien se le pide (Origen)</label>
                    <select id="trf-origen" name="almacen_origen_id" required class="w-full px-3 py-2 taller-surface text-sm">
                        <option value="">Origen...</option>
                        @foreach ($origenesDisponibles as $alm)
                            <option value="{{ $alm->id }}">{{ $alm->nombre }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs mb-1" style="color: var(--taller-muted);">Almacén que solicita (Destino)</label>
                    @if ($esPropietarioGlobal)
                        <select id="trf-destino" name="almacen_destino_id" required class="w-full px-3 py-2 taller-surface text-sm">
                            <option value="">Destino...</option>
                            @foreach ($todosAlmacenesReales as $alm)
                                <option value="{{ $alm->id }}">{{ $alm->nombre }}</option>
                            @endforeach
                        </select>
                    @else
                        <input type="hidden" id="trf-destino" name="almacen_destino_id" value="{{ $almacenPropio?->id }}">
                        <div class="w-full px-3 py-2 taller-surface text-sm opacity-80 cursor-not-allowed font-medium truncate">
                            {{ $almacenPropio?->nombre ?? 'Almacén de sucursal' }}
                        </div>
                    @endif
                </div>

                <div class="md:col-span-3">
                    <label class="block text-xs mb-1" style="color: var(--taller-muted);">Producto</label>
                    <select id="trf-producto" class="w-full px-3 py-2 taller-surface text-sm">
                        <option value="">Selecciona producto...</option>
                        <option value="8">#8 — Cargador USB-C 65W Laptop Universal</option><option value="6">#6 — Memoria RAM DDR5 16GB 4800MHz</option><option value="5">#5 — Memoria RAM SODIMM DDR4 16GB 3200MHz</option><option value="7">#7 — Pantalla Laptop 15.6&quot; FHD IPS 30-pin</option><option value="9">#9 — Pasta Térmica Alta Conductividad 4g</option><option value="3">#3 — SSD NVMe M.2 512GB PCIe 3.0</option><option value="4">#4 — SSD SATA 2.5&quot; 1TB</option><option value="10">#10 — Ventilador Cooler Repuesto Laptop</option>
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs mb-1" style="color: var(--taller-muted);">Cantidad</label>
                    <input type="number" step="0.0001" min="0.0001" id="trf-cantidad" placeholder="Cantidad" class="w-full px-3 py-2 taller-surface text-sm">
                </div>

                <div class="md:col-span-3">
                    <label class="block text-xs mb-1" style="color: var(--taller-muted);">Motivo</label>
                    <div class="flex items-center gap-2">
                        <input type="text" name="motivo" required placeholder="Motivo" class="w-full px-3 py-2 taller-surface text-sm">
                        <button type="button" id="btn-agregar-linea-trf" class="shrink-0 w-9 h-9 rounded-lg text-base font-bold bg-indigo-600 hover:bg-indigo-500 text-white flex items-center justify-center" title="Agregar producto a la solicitud">+</button>
                    </div>
                </div>
            </div>

            <div id="trf-lista-agregados" class="flex flex-wrap gap-2"></div>
            <div id="trf-hidden-lineas"></div>

            <div class="flex flex-wrap items-center justify-between gap-3 pt-1">
                <div id="trf-existencia-info" class="text-xs" style="color: var(--taller-muted);">
                    Existencia en almacén proveedor: <span class="font-mono font-semibold">Selecciona origen y producto</span>
                </div>
                <button type="submit" class="px-4 py-2 rounded-lg text-sm font-semibold" style="background-color: var(--taller-primary); color: #fff;">
                    Solicitar Traspaso
                </button>
            </div>
        </form>
        </div>

        <div id="panel-trf-consulta" class="space-y-4 hidden">
            <div class="space-y-3">
            @php
    $trfColeccion = collect($transferenciasVisibles ?? []);
    $trfCompletados = $trfColeccion->filter(fn ($t) => strtolower((string) ($t->estado ?? '')) === 'completada')->values();
    $trfCancelados  = $trfColeccion->filter(fn ($t) => strtolower((string) ($t->estado ?? '')) === 'cancelada')->values();
    $trfParciales   = $trfColeccion->filter(function ($t) {
        $st = strtolower((string) ($t->estado ?? ''));
        return ! in_array($st, ['completada', 'cancelada'], true);
    })->values();
@endphp
<div class="flex flex-wrap items-center gap-2 pb-2 border-b" style="border-color: var(--taller-border);">
    <button type="button" data-trf-subtab="parciales" id="subtab-trf-parciales" class="trf-subtab-btn px-3 py-1 rounded-lg text-xs font-semibold bg-amber-600 text-white">
        Parciales / En Curso ({{ $trfParciales->count() }})
    </button>
    <button type="button" data-trf-subtab="completados" id="subtab-trf-completados" class="trf-subtab-btn px-3 py-1 rounded-lg text-xs font-semibold taller-surface border" style="border-color: var(--taller-border);">
        Completados ({{ $trfCompletados->count() }})
    </button>
    <button type="button" data-trf-subtab="cancelados" id="subtab-trf-cancelados" class="trf-subtab-btn px-3 py-1 rounded-lg text-xs font-semibold taller-surface border" style="border-color: var(--taller-border);">
        Cancelados ({{ $trfCancelados->count() }})
    </button>
</div>

<div id="trf-grupo-parciales" class="trf-grupo-estado space-y-3">
    @forelse ($trfParciales as $trf)

                <div class="p-4 rounded-lg taller-surface space-y-2.5 text-xs">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <span class="font-mono font-bold">{{ $trf->folio }}</span>
                            <span class="mx-2">|</span>
                            <span>Origen (proveedor): <strong>{{ $trf->almacenOrigen?->nombre }}</strong> → Destino (solicita): <strong>{{ $trf->almacenDestino?->nombre }}</strong></span>
                            <span class="ml-2 px-2 py-0.5 rounded font-semibold" style="background-color: var(--taller-card); color: var(--taller-accent);">{{ $trf->estado }}</span>
                        </div>
                        @if ($trf->motivo)
                            <div class="text-[11px]" style="color: var(--taller-muted);">Motivo: {{ $trf->motivo }}</div>
                        @endif
                    </div>

                    @if ($trf->detalles && $trf->detalles->isNotEmpty())
                        <div class="pt-2 border-t space-y-1.5" style="border-color: var(--taller-border);">
                            <p class="font-semibold" style="color: var(--taller-muted);">Detalle solicitado y stock en almacén proveedor ({{ $trf->almacenOrigen?->nombre }}):</p>
                            <div class="overflow-x-auto">
                                <table class="min-w-full text-left text-xs">
                                    <thead class="border-b uppercase" style="border-color: var(--taller-border); color: var(--taller-muted);">
                                        <tr>
                                            <th class="py-1.5 pr-2">Producto</th>
                                            <th class="py-1.5 px-2 text-right">Solicitado</th>
                                            <th class="py-1.5 px-2 text-right">Físico en origen</th>
                                            <th class="py-1.5 pl-2 text-right">Disponible en origen</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y" style="border-color: var(--taller-border);">
                                        @foreach ($trf->detalles as $detSol)
                                            @php
                                                $stockOrig = $stockProveedorMap[(int) $trf->almacen_origen_id][(int) $detSol->producto_id] ?? ['cantidad' => '0.0000', 'disponible' => '0.0000'];
                                            @endphp
                                                <td class="py-1.5 pr-2 font-medium">{{ $detSol->producto?->nombre ?? ('#' . $detSol->producto_id) }}</td>
                                                <td class="py-1.5 px-2 text-right font-mono font-semibold">{{ $detSol->cantidad_solicitada ?? $detSol->cantidad }}</td>
                                                <td class="py-1.5 px-2 text-right font-mono">{{ $stockOrig['cantidad'] }}</td>
                                                <td class="py-1.5 pl-2 text-right font-mono font-semibold" style="color: var(--taller-accent);">{{ $stockOrig['disponible'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                    @if ($trf->estado === 'solicitada')
                    @php
                        $sucOrigenId = $trf->almacenOrigen?->sucursal_id !== null ? (int) $trf->almacenOrigen->sucursal_id : null;
                        $puedeDespacharTrf = $esPropietarioGlobal || ($sucursalUsuario !== null && $sucOrigenId !== null && (int) $sucursalUsuario === $sucOrigenId);
                    @endphp
                    @if ($puedeDespacharTrf)
                        <div class="pt-3 border-t space-y-3" style="border-color: var(--taller-border);">
                            <form method="POST" action="{{ route('taller.inventario.transferencias.despachar', $trf->id) }}" class="space-y-3">
                                @csrf
                                <div class="overflow-x-auto">
<table class="min-w-full text-left text-xs">
                                        <thead class="border-b uppercase" style="border-color: var(--taller-border); color: var(--taller-muted);">
                                            <tr>
                                                <th class="py-1.5 pr-2">Producto a despachar</th>
                                                <th class="py-1.5 px-2 text-right">Solicitado</th>
                                                <th class="py-1.5 px-2 text-right">Disp. origen</th>
                                                <th class="py-1.5 pl-2 text-right">Cantidad a enviar (0 si no se envía)</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y" style="border-color: var(--taller-border);">
                                            @foreach ($trf->detalles as $idxD => $detD)
                                                @php
                                                    $stO = $stockProveedorMap[(int) $trf->almacen_origen_id][(int) $detD->producto_id] ?? ['fisico' => '0.0000', 'disponible' => '0.0000'];
                                                    $dispNum = (float) ($stO['disponible'] ?? 0);
                                                    $solNum = (float) ($detD->cantidad_solicitada ?? 0);
                                                    $sugerido = $dispNum >= $solNum ? $detD->cantidad_solicitada : max(0, $dispNum);
                                                @endphp
                                                <tr>
                                                    <td class="py-1.5 pr-2 font-medium">{{ $detD->producto?->nombre ?? ('#' . $detD->producto_id) }}</td>
                                                    <td class="py-1.5 px-2 text-right font-mono">{{ $detD->cantidad_solicitada }}</td>
                                                    <td class="py-1.5 px-2 text-right font-mono" style="color: var(--taller-accent);">{{ $stO['disponible'] }}</td>
                                                    <td class="py-1.5 pl-2 text-right">
                                                        <input type="hidden" name="lineas[{{ $idxD }}][detalle_id]" value="{{ $detD->id }}">
                                                        <input type="number" step="0.0001" min="0" name="lineas[{{ $idxD }}][cantidad_despachada]" value="{{ $sugerido }}" class="w-28 px-2 py-1 rounded bg-black/30 border border-white/15 text-xs font-mono text-right">
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <button type="submit" class="px-3 py-1.5 rounded bg-amber-600 hover:bg-amber-500 text-white font-medium text-xs">
                                        Aceptar y Despachar a Tránsito
                                    </button>
                                </div>
                            </form>

                            <form method="POST" action="{{ route('taller.inventario.transferencias.cancelar', $trf->id) }}" class="flex flex-wrap items-center gap-2 pt-2 border-t" style="border-color: var(--taller-border);">
                                @csrf
                                <input
                                    type="text"
                                    name="motivo"
                                    required
                                    placeholder="Motivo de cancelación (obligatorio)..."
                                    class="px-2.5 py-1.5 rounded bg-black/30 border border-white/15 text-xs flex-1 min-w-[220px]"
                                >
                                <button type="submit" class="px-3 py-1.5 rounded bg-rose-700 hover:bg-rose-600 text-white font-medium text-xs">
                                    Cancelar Traspaso
                                </button>
                            </form>
                        </div>
                    @else
                        <div class="pt-2 border-t text-xs" style="border-color: var(--taller-border); color: var(--taller-muted);">
                            Pendiente de despacho por <strong>{{ $trf->almacenOrigen?->sucursal?->nombre ?? $trf->almacenOrigen?->nombre }}</strong> (gerente/subgerente de origen o propietario).
                        </div>
                    @endif
                @endif

                    @if ($trf->estado === 'cancelada' && $trf->observaciones_despacho)
                        <div class="pt-2 border-t text-rose-300" style="border-color: var(--taller-border);">
                            Motivo de cancelación: <span class="font-medium">{{ $trf->observaciones_despacho }}</span>
                        </div>
                    @endif

                    @if (in_array($trf->estado, ['en_transito', 'parcial_pendiente'], true) && $trf->detalles->isNotEmpty())
                        <form method="POST" action="{{ route('taller.inventario.transferencias.recibir', $trf->id) }}" class="space-y-2 pt-2 border-t" style="border-color: var(--taller-border);">
                            @csrf
                            <p class="font-semibold" style="color: var(--taller-muted);">Verificación física de recepción por producto:</p>
                            @foreach ($trf->detalles as $idxDet => $det)
                                @php
                                    $pendiente = max(0, (float) $det->cantidad_despachada - (float) $det->cantidad_recibida);
                                @endphp
                                <div class="flex flex-wrap items-center justify-between gap-2 py-1">
                                    <div>
                                        <span class="font-medium">{{ $det->producto?->nombre ?? ('#' . $det->producto_id) }}</span>
                                        <span class="opacity-75">(Despachado: {{ $det->cantidad_despachada }} | Recibido: {{ $det->cantidad_recibida }} | Pendiente: {{ number_format($pendiente, 4) }})</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <input type="hidden" name="lineas[{{ $idxDet }}][detalle_id]" value="{{ $det->id }}">
                                        <label class="opacity-75">Recibir ahora:</label>
                                        <input type="number" step="0.0001" min="0" max="{{ number_format($pendiente, 4, '.', '') }}" name="lineas[{{ $idxDet }}][cantidad_recibida]" value="{{ number_format($pendiente, 4, '.', '') }}" class="w-24 px-2 py-1 rounded bg-black/30 border border-white/15 font-mono">
                                    </div>
                                </div>
                            @endforeach
                            <div class="flex justify-end">
                                <button type="submit" class="px-3 py-1.5 rounded bg-emerald-600 text-white font-medium">Registrar Recepción Física</button>
                            </div>
                        </form>

                        <div class="flex flex-wrap gap-2 pt-1">
                            @foreach ($trf->detalles as $det)
                                @php
                                    $pendiente = max(0, (float) $det->cantidad_despachada - (float) $det->cantidad_recibida);
                                @endphp
                                @if ($pendiente > 0)
                                    <form method="POST" action="{{ route('taller.inventario.transferencias.detalles.cancelar_faltante', [$trf->id, $det->id]) }}" class="inline">
                                        @csrf
                                        <input type="hidden" name="motivo" value="Cancelado por falta de existencia">
                                        <button type="submit" class="px-2.5 py-1 rounded bg-rose-700/80 text-white text-[11px]">
                                            Cancelar faltante de {{ $det->producto?->nombre ?? ('#' . $det->producto_id) }} ({{ number_format($pendiente, 4) }})
                                        </button>
                                    </form>
                                @endif
                            @endforeach
                        </div>
                    @endif
                </div>
                @empty
        <p class="text-xs" style="color: var(--taller-muted);">No hay traspasos parciales o en curso.</p>
    @endforelse
</div>

<div id="trf-grupo-completados" class="trf-grupo-estado space-y-3 hidden" style="display: none;">
    @forelse ($trfCompletados as $trf)

                <div class="p-4 rounded-lg taller-surface space-y-2.5 text-xs">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <span class="font-mono font-bold">{{ $trf->folio }}</span>
                            <span class="mx-2">|</span>
                            <span>Origen (proveedor): <strong>{{ $trf->almacenOrigen?->nombre }}</strong> → Destino (solicita): <strong>{{ $trf->almacenDestino?->nombre }}</strong></span>
                            <span class="ml-2 px-2 py-0.5 rounded font-semibold" style="background-color: var(--taller-card); color: var(--taller-accent);">{{ $trf->estado }}</span>
                        </div>
                        @if ($trf->motivo)
                            <div class="text-[11px]" style="color: var(--taller-muted);">Motivo: {{ $trf->motivo }}</div>
                        @endif
                    </div>

                    @if ($trf->detalles && $trf->detalles->isNotEmpty())
                        <div class="pt-2 border-t space-y-1.5" style="border-color: var(--taller-border);">
                            <p class="font-semibold" style="color: var(--taller-muted);">Detalle solicitado y stock en almacén proveedor ({{ $trf->almacenOrigen?->nombre }}):</p>
                            <div class="overflow-x-auto">
                                <table class="min-w-full text-left text-xs">
                                    <thead class="border-b uppercase" style="border-color: var(--taller-border); color: var(--taller-muted);">
                                        <tr>
                                            <th class="py-1.5 pr-2">Producto</th>
                                            <th class="py-1.5 px-2 text-right">Solicitado</th>
                                            <th class="py-1.5 px-2 text-right">Físico en origen</th>
                                            <th class="py-1.5 pl-2 text-right">Disponible en origen</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y" style="border-color: var(--taller-border);">
                                        @foreach ($trf->detalles as $detSol)
                                            @php
                                                $stockOrig = $stockProveedorMap[(int) $trf->almacen_origen_id][(int) $detSol->producto_id] ?? ['cantidad' => '0.0000', 'disponible' => '0.0000'];
                                            @endphp
                                                <td class="py-1.5 pr-2 font-medium">{{ $detSol->producto?->nombre ?? ('#' . $detSol->producto_id) }}</td>
                                                <td class="py-1.5 px-2 text-right font-mono font-semibold">{{ $detSol->cantidad_solicitada ?? $detSol->cantidad }}</td>
                                                <td class="py-1.5 px-2 text-right font-mono">{{ $stockOrig['cantidad'] }}</td>
                                                <td class="py-1.5 pl-2 text-right font-mono font-semibold" style="color: var(--taller-accent);">{{ $stockOrig['disponible'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                    @if ($trf->estado === 'solicitada')
                    @php
                        $sucOrigenId = $trf->almacenOrigen?->sucursal_id !== null ? (int) $trf->almacenOrigen->sucursal_id : null;
                        $puedeDespacharTrf = $esPropietarioGlobal || ($sucursalUsuario !== null && $sucOrigenId !== null && (int) $sucursalUsuario === $sucOrigenId);
                    @endphp
                    @if ($puedeDespacharTrf)
                        <div class="pt-3 border-t space-y-3" style="border-color: var(--taller-border);">
                            <form method="POST" action="{{ route('taller.inventario.transferencias.despachar', $trf->id) }}" class="space-y-3">
                                @csrf
                                <div class="overflow-x-auto">
<table class="min-w-full text-left text-xs">
                                        <thead class="border-b uppercase" style="border-color: var(--taller-border); color: var(--taller-muted);">
                                            <tr>
                                                <th class="py-1.5 pr-2">Producto a despachar</th>
                                                <th class="py-1.5 px-2 text-right">Solicitado</th>
                                                <th class="py-1.5 px-2 text-right">Disp. origen</th>
                                                <th class="py-1.5 pl-2 text-right">Cantidad a enviar (0 si no se envía)</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y" style="border-color: var(--taller-border);">
                                            @foreach ($trf->detalles as $idxD => $detD)
                                                @php
                                                    $stO = $stockProveedorMap[(int) $trf->almacen_origen_id][(int) $detD->producto_id] ?? ['fisico' => '0.0000', 'disponible' => '0.0000'];
                                                    $dispNum = (float) ($stO['disponible'] ?? 0);
                                                    $solNum = (float) ($detD->cantidad_solicitada ?? 0);
                                                    $sugerido = $dispNum >= $solNum ? $detD->cantidad_solicitada : max(0, $dispNum);
                                                @endphp
                                                <tr>
                                                    <td class="py-1.5 pr-2 font-medium">{{ $detD->producto?->nombre ?? ('#' . $detD->producto_id) }}</td>
                                                    <td class="py-1.5 px-2 text-right font-mono">{{ $detD->cantidad_solicitada }}</td>
                                                    <td class="py-1.5 px-2 text-right font-mono" style="color: var(--taller-accent);">{{ $stO['disponible'] }}</td>
                                                    <td class="py-1.5 pl-2 text-right">
                                                        <input type="hidden" name="lineas[{{ $idxD }}][detalle_id]" value="{{ $detD->id }}">
                                                        <input type="number" step="0.0001" min="0" name="lineas[{{ $idxD }}][cantidad_despachada]" value="{{ $sugerido }}" class="w-28 px-2 py-1 rounded bg-black/30 border border-white/15 text-xs font-mono text-right">
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <button type="submit" class="px-3 py-1.5 rounded bg-amber-600 hover:bg-amber-500 text-white font-medium text-xs">
                                        Aceptar y Despachar a Tránsito
                                    </button>
                                </div>
                            </form>

                            <form method="POST" action="{{ route('taller.inventario.transferencias.cancelar', $trf->id) }}" class="flex flex-wrap items-center gap-2 pt-2 border-t" style="border-color: var(--taller-border);">
                                @csrf
                                <input
                                    type="text"
                                    name="motivo"
                                    required
                                    placeholder="Motivo de cancelación (obligatorio)..."
                                    class="px-2.5 py-1.5 rounded bg-black/30 border border-white/15 text-xs flex-1 min-w-[220px]"
                                >
                                <button type="submit" class="px-3 py-1.5 rounded bg-rose-700 hover:bg-rose-600 text-white font-medium text-xs">
                                    Cancelar Traspaso
                                </button>
                            </form>
                        </div>
                    @else
                        <div class="pt-2 border-t text-xs" style="border-color: var(--taller-border); color: var(--taller-muted);">
                            Pendiente de despacho por <strong>{{ $trf->almacenOrigen?->sucursal?->nombre ?? $trf->almacenOrigen?->nombre }}</strong> (gerente/subgerente de origen o propietario).
                        </div>
                    @endif
                @endif

                    @if ($trf->estado === 'cancelada' && $trf->observaciones_despacho)
                        <div class="pt-2 border-t text-rose-300" style="border-color: var(--taller-border);">
                            Motivo de cancelación: <span class="font-medium">{{ $trf->observaciones_despacho }}</span>
                        </div>
                    @endif

                    @if (in_array($trf->estado, ['en_transito', 'parcial_pendiente'], true) && $trf->detalles->isNotEmpty())
                        <form method="POST" action="{{ route('taller.inventario.transferencias.recibir', $trf->id) }}" class="space-y-2 pt-2 border-t" style="border-color: var(--taller-border);">
                            @csrf
                            <p class="font-semibold" style="color: var(--taller-muted);">Verificación física de recepción por producto:</p>
                            @foreach ($trf->detalles as $idxDet => $det)
                                @php
                                    $pendiente = max(0, (float) $det->cantidad_despachada - (float) $det->cantidad_recibida);
                                @endphp
                                <div class="flex flex-wrap items-center justify-between gap-2 py-1">
                                    <div>
                                        <span class="font-medium">{{ $det->producto?->nombre ?? ('#' . $det->producto_id) }}</span>
                                        <span class="opacity-75">(Despachado: {{ $det->cantidad_despachada }} | Recibido: {{ $det->cantidad_recibida }} | Pendiente: {{ number_format($pendiente, 4) }})</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <input type="hidden" name="lineas[{{ $idxDet }}][detalle_id]" value="{{ $det->id }}">
                                        <label class="opacity-75">Recibir ahora:</label>
                                        <input type="number" step="0.0001" min="0" max="{{ number_format($pendiente, 4, '.', '') }}" name="lineas[{{ $idxDet }}][cantidad_recibida]" value="{{ number_format($pendiente, 4, '.', '') }}" class="w-24 px-2 py-1 rounded bg-black/30 border border-white/15 font-mono">
                                    </div>
                                </div>
                            @endforeach
                            <div class="flex justify-end">
                                <button type="submit" class="px-3 py-1.5 rounded bg-emerald-600 text-white font-medium">Registrar Recepción Física</button>
                            </div>
                        </form>

                        <div class="flex flex-wrap gap-2 pt-1">
                            @foreach ($trf->detalles as $det)
                                @php
                                    $pendiente = max(0, (float) $det->cantidad_despachada - (float) $det->cantidad_recibida);
                                @endphp
                                @if ($pendiente > 0)
                                    <form method="POST" action="{{ route('taller.inventario.transferencias.detalles.cancelar_faltante', [$trf->id, $det->id]) }}" class="inline">
                                        @csrf
                                        <input type="hidden" name="motivo" value="Cancelado por falta de existencia">
                                        <button type="submit" class="px-2.5 py-1 rounded bg-rose-700/80 text-white text-[11px]">
                                            Cancelar faltante de {{ $det->producto?->nombre ?? ('#' . $det->producto_id) }} ({{ number_format($pendiente, 4) }})
                                        </button>
                                    </form>
                                @endif
                            @endforeach
                        </div>
                    @endif
                </div>
                @empty
        <p class="text-xs" style="color: var(--taller-muted);">No hay traspasos completados.</p>
    @endforelse
</div>

<div id="trf-grupo-cancelados" class="trf-grupo-estado space-y-3 hidden" style="display: none;">
    @forelse ($trfCancelados as $trf)

                <div class="p-4 rounded-lg taller-surface space-y-2.5 text-xs">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <span class="font-mono font-bold">{{ $trf->folio }}</span>
                            <span class="mx-2">|</span>
                            <span>Origen (proveedor): <strong>{{ $trf->almacenOrigen?->nombre }}</strong> → Destino (solicita): <strong>{{ $trf->almacenDestino?->nombre }}</strong></span>
                            <span class="ml-2 px-2 py-0.5 rounded font-semibold" style="background-color: var(--taller-card); color: var(--taller-accent);">{{ $trf->estado }}</span>
                        </div>
                        @if ($trf->motivo)
                            <div class="text-[11px]" style="color: var(--taller-muted);">Motivo: {{ $trf->motivo }}</div>
                        @endif
                    </div>

                    @if ($trf->detalles && $trf->detalles->isNotEmpty())
                        <div class="pt-2 border-t space-y-1.5" style="border-color: var(--taller-border);">
                            <p class="font-semibold" style="color: var(--taller-muted);">Detalle solicitado y stock en almacén proveedor ({{ $trf->almacenOrigen?->nombre }}):</p>
                            <div class="overflow-x-auto">
                                <table class="min-w-full text-left text-xs">
                                    <thead class="border-b uppercase" style="border-color: var(--taller-border); color: var(--taller-muted);">
                                        <tr>
                                            <th class="py-1.5 pr-2">Producto</th>
                                            <th class="py-1.5 px-2 text-right">Solicitado</th>
                                            <th class="py-1.5 px-2 text-right">Físico en origen</th>
                                            <th class="py-1.5 pl-2 text-right">Disponible en origen</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y" style="border-color: var(--taller-border);">
                                        @foreach ($trf->detalles as $detSol)
                                            @php
                                                $stockOrig = $stockProveedorMap[(int) $trf->almacen_origen_id][(int) $detSol->producto_id] ?? ['cantidad' => '0.0000', 'disponible' => '0.0000'];
                                            @endphp
                                                <td class="py-1.5 pr-2 font-medium">{{ $detSol->producto?->nombre ?? ('#' . $detSol->producto_id) }}</td>
                                                <td class="py-1.5 px-2 text-right font-mono font-semibold">{{ $detSol->cantidad_solicitada ?? $detSol->cantidad }}</td>
                                                <td class="py-1.5 px-2 text-right font-mono">{{ $stockOrig['cantidad'] }}</td>
                                                <td class="py-1.5 pl-2 text-right font-mono font-semibold" style="color: var(--taller-accent);">{{ $stockOrig['disponible'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                    @if ($trf->estado === 'solicitada')
                    @php
                        $sucOrigenId = $trf->almacenOrigen?->sucursal_id !== null ? (int) $trf->almacenOrigen->sucursal_id : null;
                        $puedeDespacharTrf = $esPropietarioGlobal || ($sucursalUsuario !== null && $sucOrigenId !== null && (int) $sucursalUsuario === $sucOrigenId);
                    @endphp
                    @if ($puedeDespacharTrf)
                        <div class="pt-3 border-t space-y-3" style="border-color: var(--taller-border);">
                            <form method="POST" action="{{ route('taller.inventario.transferencias.despachar', $trf->id) }}" class="space-y-3">
                                @csrf
                                <div class="overflow-x-auto">
<table class="min-w-full text-left text-xs">
                                        <thead class="border-b uppercase" style="border-color: var(--taller-border); color: var(--taller-muted);">
                                            <tr>
                                                <th class="py-1.5 pr-2">Producto a despachar</th>
                                                <th class="py-1.5 px-2 text-right">Solicitado</th>
                                                <th class="py-1.5 px-2 text-right">Disp. origen</th>
                                                <th class="py-1.5 pl-2 text-right">Cantidad a enviar (0 si no se envía)</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y" style="border-color: var(--taller-border);">
                                            @foreach ($trf->detalles as $idxD => $detD)
                                                @php
                                                    $stO = $stockProveedorMap[(int) $trf->almacen_origen_id][(int) $detD->producto_id] ?? ['fisico' => '0.0000', 'disponible' => '0.0000'];
                                                    $dispNum = (float) ($stO['disponible'] ?? 0);
                                                    $solNum = (float) ($detD->cantidad_solicitada ?? 0);
                                                    $sugerido = $dispNum >= $solNum ? $detD->cantidad_solicitada : max(0, $dispNum);
                                                @endphp
                                                <tr>
                                                    <td class="py-1.5 pr-2 font-medium">{{ $detD->producto?->nombre ?? ('#' . $detD->producto_id) }}</td>
                                                    <td class="py-1.5 px-2 text-right font-mono">{{ $detD->cantidad_solicitada }}</td>
                                                    <td class="py-1.5 px-2 text-right font-mono" style="color: var(--taller-accent);">{{ $stO['disponible'] }}</td>
                                                    <td class="py-1.5 pl-2 text-right">
                                                        <input type="hidden" name="lineas[{{ $idxD }}][detalle_id]" value="{{ $detD->id }}">
                                                        <input type="number" step="0.0001" min="0" name="lineas[{{ $idxD }}][cantidad_despachada]" value="{{ $sugerido }}" class="w-28 px-2 py-1 rounded bg-black/30 border border-white/15 text-xs font-mono text-right">
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <button type="submit" class="px-3 py-1.5 rounded bg-amber-600 hover:bg-amber-500 text-white font-medium text-xs">
                                        Aceptar y Despachar a Tránsito
                                    </button>
                                </div>
                            </form>

                            <form method="POST" action="{{ route('taller.inventario.transferencias.cancelar', $trf->id) }}" class="flex flex-wrap items-center gap-2 pt-2 border-t" style="border-color: var(--taller-border);">
                                @csrf
                                <input
                                    type="text"
                                    name="motivo"
                                    required
                                    placeholder="Motivo de cancelación (obligatorio)..."
                                    class="px-2.5 py-1.5 rounded bg-black/30 border border-white/15 text-xs flex-1 min-w-[220px]"
                                >
                                <button type="submit" class="px-3 py-1.5 rounded bg-rose-700 hover:bg-rose-600 text-white font-medium text-xs">
                                    Cancelar Traspaso
                                </button>
                            </form>
                        </div>
                    @else
                        <div class="pt-2 border-t text-xs" style="border-color: var(--taller-border); color: var(--taller-muted);">
                            Pendiente de despacho por <strong>{{ $trf->almacenOrigen?->sucursal?->nombre ?? $trf->almacenOrigen?->nombre }}</strong> (gerente/subgerente de origen o propietario).
                        </div>
                    @endif
                @endif

                    @if ($trf->estado === 'cancelada' && $trf->observaciones_despacho)
                        <div class="pt-2 border-t text-rose-300" style="border-color: var(--taller-border);">
                            Motivo de cancelación: <span class="font-medium">{{ $trf->observaciones_despacho }}</span>
                        </div>
                    @endif

                    @if (in_array($trf->estado, ['en_transito', 'parcial_pendiente'], true) && $trf->detalles->isNotEmpty())
                        <form method="POST" action="{{ route('taller.inventario.transferencias.recibir', $trf->id) }}" class="space-y-2 pt-2 border-t" style="border-color: var(--taller-border);">
                            @csrf
                            <p class="font-semibold" style="color: var(--taller-muted);">Verificación física de recepción por producto:</p>
                            @foreach ($trf->detalles as $idxDet => $det)
                                @php
                                    $pendiente = max(0, (float) $det->cantidad_despachada - (float) $det->cantidad_recibida);
                                @endphp
                                <div class="flex flex-wrap items-center justify-between gap-2 py-1">
                                    <div>
                                        <span class="font-medium">{{ $det->producto?->nombre ?? ('#' . $det->producto_id) }}</span>
                                        <span class="opacity-75">(Despachado: {{ $det->cantidad_despachada }} | Recibido: {{ $det->cantidad_recibida }} | Pendiente: {{ number_format($pendiente, 4) }})</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <input type="hidden" name="lineas[{{ $idxDet }}][detalle_id]" value="{{ $det->id }}">
                                        <label class="opacity-75">Recibir ahora:</label>
                                        <input type="number" step="0.0001" min="0" max="{{ number_format($pendiente, 4, '.', '') }}" name="lineas[{{ $idxDet }}][cantidad_recibida]" value="{{ number_format($pendiente, 4, '.', '') }}" class="w-24 px-2 py-1 rounded bg-black/30 border border-white/15 font-mono">
                                    </div>
                                </div>
                            @endforeach
                            <div class="flex justify-end">
                                <button type="submit" class="px-3 py-1.5 rounded bg-emerald-600 text-white font-medium">Registrar Recepción Física</button>
                            </div>
                        </form>

                        <div class="flex flex-wrap gap-2 pt-1">
                            @foreach ($trf->detalles as $det)
                                @php
                                    $pendiente = max(0, (float) $det->cantidad_despachada - (float) $det->cantidad_recibida);
                                @endphp
                                @if ($pendiente > 0)
                                    <form method="POST" action="{{ route('taller.inventario.transferencias.detalles.cancelar_faltante', [$trf->id, $det->id]) }}" class="inline">
                                        @csrf
                                        <input type="hidden" name="motivo" value="Cancelado por falta de existencia">
                                        <button type="submit" class="px-2.5 py-1 rounded bg-rose-700/80 text-white text-[11px]">
                                            Cancelar faltante de {{ $det->producto?->nombre ?? ('#' . $det->producto_id) }} ({{ number_format($pendiente, 4) }})
                                        </button>
                                    </form>
                                @endif
                            @endforeach
                        </div>
                    @endif
                </div>
                @empty
        <p class="text-xs" style="color: var(--taller-muted);">No hay traspasos cancelados.</p>
    @endforelse
</div>
</div>

            @php
        $stockProveedorMap = [];
        try {
            \Giros\ReparacionComputadoras\Services\ContextoPropietarioTaller::conexion();
            $filasExt = \Giros\ReparacionComputadoras\Models\ExistenciaAlmacenTaller::query()->get();
            foreach ($filasExt as $fExt) {
                $aId = (int) $fExt->almacen_id;
                $pId = (int) $fExt->producto_id;
                $fis = (string) ($fExt->cantidad ?? '0.0000');
                $res = (string) ($fExt->cantidad_reservada ?? '0.0000');
                $disp = bcsub($fis, $res, 4);
                if (bccomp($disp, '0.0000', 4) === -1) {
                    $disp = '0.0000';
                }
                $stockProveedorMap[$aId][$pId] = [
                    'fisico' => $fis,
                    'disponible' => $disp,
                ];
            }
        } catch (\Throwable $e) {
        }
    @endphp

    </div>
        </div>
    </div>

    <script>
    const stockProveedorMap = @json($stockProveedorMap ?? []);
(function () {
    const form = document.getElementById('form-solicitar-traspaso');
    const btnAdd = document.getElementById('btn-agregar-linea-trf');
    const selOrigen = document.getElementById('trf-origen');
    const inpDestino = document.getElementById('trf-destino');
    const selProd = document.getElementById('trf-producto');
    const inpCant = document.getElementById('trf-cantidad');
    const listaVisual = document.getElementById('trf-lista-agregados');
    const hiddenContainer = document.getElementById('trf-hidden-lineas');
    const infoBox = document.getElementById('trf-existencia-info');
    if (!form || !btnAdd || !selProd || !inpCant || !listaVisual || !hiddenContainer || !infoBox) return;

    const submitBtn = form.querySelector('button[type="submit"]');
    let itemsAgregados = [];

    function obtenerStock(almId, prodId) {
        if (!almId || !prodId || typeof stockProveedorMap === 'undefined') {
            return { fisico: '0.0000', disponible: '0.0000' };
        }
        if (stockProveedorMap[almId] && stockProveedorMap[almId][prodId]) {
            return stockProveedorMap[almId][prodId];
        }
        return { fisico: '0.0000', disponible: '0.0000' };
    }

    function reconstruirInputsOcultos() {
        hiddenContainer.innerHTML = '';
        itemsAgregados.forEach((item, idx) => {
            const hProd = document.createElement('input');
            hProd.type = 'hidden';
            hProd.name = `lineas[${idx}][producto_id]`;
            hProd.value = String(item.productoId);

            const hCant = document.createElement('input');
            hCant.type = 'hidden';
            hCant.name = `lineas[${idx}][cantidad]`;
            hCant.value = String(item.cantidad);

            hiddenContainer.appendChild(hProd);
            hiddenContainer.appendChild(hCant);
        });
    }

    function renderizarVisual() {
        const origId = parseInt((selOrigen && selOrigen.value) || '0', 10);
        reconstruirInputsOcultos();

        if (itemsAgregados.length === 0) {
            listaVisual.innerHTML = '';
            return;
        }

        listaVisual.innerHTML = itemsAgregados.map((item, idx) => {
            const dispOrig = origId ? obtenerStock(origId, item.productoId).disponible : '—';
            return `<span class="inline-flex items-center gap-2 px-2.5 py-1 rounded-lg text-xs bg-indigo-950/70 border border-indigo-500/40 text-indigo-100">
                <span><strong>${item.nombre}</strong> × <span class="font-mono">${item.cantidad}</span> <span class="opacity-75">(Disp. origen: ${dispOrig})</span></span>
                <button type="button" data-rem="${idx}" class="text-rose-300 hover:text-rose-100 font-bold" title="Quitar">×</button>
            </span>`;
        }).join('');

        listaVisual.querySelectorAll('button[data-rem]').forEach((b) => {
            b.addEventListener('click', () => {
                const i = parseInt(b.getAttribute('data-rem') || '-1', 10);
                if (i >= 0) {
                    itemsAgregados.splice(i, 1);
                    renderizarVisual();
                    actualizarStockInfo();
                }
            });
        });
    }

    function actualizarStockInfo() {
        const origId = parseInt((selOrigen && selOrigen.value) || '0', 10);
        const destId = parseInt((inpDestino && inpDestino.value) || '0', 10);
        const prodId = parseInt((selProd && selProd.value) || '0', 10);

        if (itemsAgregados.length > 0 && origId) {
            const resumen = itemsAgregados.map((it) => `${it.nombre} (${it.cantidad})`).join(' · ');
            const dispDest0 = destId ? obtenerStock(destId, itemsAgregados[0].productoId).disponible : '0.0000';
            infoBox.innerHTML = `Agregados (${itemsAgregados.length}): <span class="font-semibold">${resumen}</span> · Disp. primer producto en solicitante: <span class="font-mono font-semibold">${dispDest0}</span>`;
            return;
        }

        if (!origId || !prodId) {
            infoBox.innerHTML = 'Existencia en almacén proveedor: <span class="font-mono font-semibold">Selecciona origen y producto</span>';
            return;
        }

        const d = obtenerStock(origId, prodId);
        const dest = destId ? obtenerStock(destId, prodId) : { fisico: '0.0000', disponible: '0.0000' };
        infoBox.innerHTML = `En proveedor — Físico: <span class="font-mono font-semibold">${d.fisico}</span> | Disponible: <span class="font-mono font-semibold">${d.disponible}</span> · En solicitante — Disponible: <span class="font-mono font-semibold">${dest.disponible}</span>`;
    }

    btnAdd.addEventListener('click', () => {
        const prodId = parseInt(selProd.value || '0', 10);
        const cantVal = parseFloat(inpCant.value || '0');
        if (!prodId || !cantVal || cantVal <= 0) {
            inpCant.focus();
            return;
        }
        const nombreProd = selProd.options[selProd.selectedIndex]?.text || ('#' + prodId);
        itemsAgregados.push({
            productoId: prodId,
            nombre: nombreProd,
            cantidad: inpCant.value,
        });

        selProd.value = '';
        inpCant.value = '';
        renderizarVisual();
        actualizarStockInfo();
        selProd.focus();
    });

    function prepararEnvio(e) {
        if (itemsAgregados.length === 0) {
            const prodId = parseInt(selProd.value || '0', 10);
            const cantVal = parseFloat(inpCant.value || '0');
            if (!prodId || !cantVal || cantVal <= 0) {
                if (e) e.preventDefault();
                inpCant.focus();
                return false;
            }
            const nombreProd = selProd.options[selProd.selectedIndex]?.text || ('#' + prodId);
            itemsAgregados.push({
                productoId: prodId,
                nombre: nombreProd,
                cantidad: inpCant.value,
            });
        }
        reconstruirInputsOcultos();
        return true;
    }

    form.addEventListener('submit', (e) => {
        if (!prepararEnvio(e)) return;
    });

    submitBtn?.addEventListener('click', (e) => {
        if (!prepararEnvio(e)) return;
        if (itemsAgregados.length > 0) {
            e.preventDefault();
            form.submit();
        }
    });

    selOrigen?.addEventListener('change', () => {
        renderizarVisual();
        actualizarStockInfo();
    });
    inpDestino?.addEventListener('change', () => {
        renderizarVisual();
        actualizarStockInfo();
    });
    selProd?.addEventListener('change', actualizarStockInfo);
    actualizarStockInfo();
})();
</script>

    <script>
    (function () {
        const tabSol = document.getElementById('tab-trf-solicitar');
        const tabCon = document.getElementById('tab-trf-consulta');
        const panSol = document.getElementById('panel-trf-solicitar');
        const panCon = document.getElementById('panel-trf-consulta');
        if (!tabSol || !tabCon || !panSol || !panCon) return;

        function activarTab(modo) {
            const esConsulta = (modo === 'consulta');
            panSol.classList.toggle('hidden', esConsulta);
            panCon.classList.toggle('hidden', !esConsulta);

            if (esConsulta) {
                tabCon.className = 'px-3 py-1.5 rounded-lg text-xs font-semibold bg-indigo-600 text-white';
                tabSol.className = 'px-3 py-1.5 rounded-lg text-xs font-semibold taller-surface border';
                tabSol.style.borderColor = 'var(--taller-border)';
            } else {
                tabSol.className = 'px-3 py-1.5 rounded-lg text-xs font-semibold bg-indigo-600 text-white';
                tabCon.className = 'px-3 py-1.5 rounded-lg text-xs font-semibold taller-surface border';
                tabCon.style.borderColor = 'var(--taller-border)';
            }
        }

        tabSol.addEventListener('click', () => activarTab('solicitar'));
        tabCon.addEventListener('click', () => activarTab('consulta'));

        // Si viene con ?trf_tab=consulta o si hay mensaje de éxito/error tras despachar/recibir/cancelar, abrir Consulta de Traspasos
        const params = new URLSearchParams(window.location.search);
        const tieneFlash = {{ (session('success') || session('error') || $errors->any()) ? 'true' : 'false' }};
        if (params.get('trf_tab') === 'consulta' || tieneFlash) {
            activarTab('consulta');
        }
    })();
    </script>
    </div>
    @endif

@if (in_array($sec, ['general', 'existencias'], true))
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="taller-card p-5">
            <h2 class="text-lg font-semibold mb-3">Existencias por Almacén</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-xs">
                    <thead class="border-b uppercase" style="border-color: var(--taller-border); color: var(--taller-muted);">
                        <tr>
                            <th class="py-2 pr-2">Producto</th>
                            <th class="py-2 px-2">Almacén</th>
                            <th class="py-2 px-2 text-right">Físico</th>
                            <th class="py-2 px-2 text-right">Disp.</th>
                            <th class="py-2 pl-2 text-right">CPP</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y" style="border-color: var(--taller-border);">
                        
@php
    $filtroAlmSel = (int) request('filtro_almacen_id', 0);
    @endphp
@php
    $filtroAlmSel = (int) request('filtro_almacen_id', 0);
    @endphp
@php
    $esPropietarioGlobal = $esPropietarioGlobal ?? (isset($usuario) && method_exists($usuario, 'esPropietarioGlobal') ? $usuario->esPropietarioGlobal() : true);
    $sucursalUsuario = $sucursalUsuario ?? ((! $esPropietarioGlobal && isset($usuario) && method_exists($usuario, 'sucursalAsignadaId')) ? $usuario->sucursalAsignadaId() : null);
    $rolUsuarioActual = strtolower(trim((string) ($usuario->rol_empresa ?? '')));
    $esGerente = in_array($rolUsuarioActual, ['gerente', 'gerente_sucursal', 'encargado', 'encargado_sucursal'], true);
    $puedeVerTransito = $esPropietarioGlobal || $esGerente;
    $almacenesVisibles = $almacenesVisibles ?? collect($matriz['almacenes'] ?? [])->filter(function ($alm) use ($esPropietarioGlobal, $sucursalUsuario, $puedeVerTransito) {
        if ($esPropietarioGlobal) return true;
        if ((bool) ($alm->es_virtual ?? false)) return $puedeVerTransito;
        return $sucursalUsuario !== null && (int) $alm->sucursal_id === (int) $sucursalUsuario;
    });
    $filtroAlmSel = (int) request('filtro_almacen_id', 0);
    $opcionesFiltroAlmacen = $almacenesVisibles->filter(function ($a) use ($esPropietarioGlobal, $puedeVerTransito, $sucursalUsuario) {
        if ($esPropietarioGlobal) return true;
        if ((bool) ($a->es_virtual ?? false)) return $puedeVerTransito;
        return $sucursalUsuario === null || (int) $a->sucursal_id === (int) $sucursalUsuario;
    });
    @endphp
<form method="GET" action="{{ url()->current() }}" class="mb-3 flex flex-wrap items-center gap-2">
    <select name="filtro_almacen_id" class="px-3 py-1.5 rounded-lg taller-surface text-xs">
        <option value="">Todos mis almacenes visibles</option>
        @foreach ($opcionesFiltroAlmacen as $opAlm)
            <option value="{{ $opAlm->id }}" @selected($filtroAlmSel === (int) $opAlm->id)>
                {{ $opAlm->nombre }}{{ ($opAlm->es_virtual ?? false) ? ' (Tránsito)' : '' }}
            </option>
        @endforeach
    </select>
    <button type="submit" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-indigo-600 hover:bg-indigo-500 text-white">
        Filtrar
    </button>
    @if ($filtroAlmSel > 0)
        <a href="{{ url()->current() }}" class="px-2.5 py-1.5 rounded-lg text-xs border" style="border-color: var(--taller-border); color: var(--taller-muted);">
            Limpiar
        </a>
    @endif
</form>
@php
    $filtroAlmActual = (int) request('filtro_almacen_id', 0);
    $filasExistenciasMostrar = collect($existenciasVisibles ?? []);
    if ($filtroAlmActual > 0) {
        $filasExistenciasMostrar = $filasExistenciasMostrar->filter(fn ($e) => (int) ($e['almacen_id'] ?? 0) === $filtroAlmActual);
    }
@endphp
@foreach ($filasExistenciasMostrar as $ext)
                            <tr>
                                <td class="py-2 pr-2">{{ $ext['producto'] }}</td>
                                <td class="py-2 px-2">{{ $ext['almacen'] }}</td>
                                <td class="py-2 px-2 text-right font-mono">{{ $ext['cantidad'] }}</td>
                                <td class="py-2 px-2 text-right font-mono">{{ $ext['cantidad_disponible'] }}</td>
                                <td class="py-2 pl-2 text-right font-mono">{{ $ext['costo_promedio'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="taller-card p-5">
            <h2 class="text-lg font-semibold mb-3">Kardex Reciente</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-xs">
                    <thead class="border-b uppercase" style="border-color: var(--taller-border); color: var(--taller-muted);">
                        <tr>
                            <th class="py-2 pr-2">Folio</th>
                            <th class="py-2 px-2">Tipo</th>
                            <th class="py-2 px-2">Producto</th>
                            <th class="py-2 pl-2 text-right">Cant.</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y" style="border-color: var(--taller-border);">
                        @php
    $idsAlmacenesPermitidosKardex = $esPropietarioGlobal
        ? null
        : collect($almacenesVisibles ?? collect())->pluck('id')->map(fn ($id) => (int) $id)->filter(fn ($id) => $id > 0)->values()->all();
    $kardexFiltrado = ($kardex ?? collect())->filter(function ($m) use ($idsAlmacenesPermitidosKardex) {
        if ($idsAlmacenesPermitidosKardex === null) {
            return true;
        }
        return in_array((int) ($m->almacen_id ?? 0), $idsAlmacenesPermitidosKardex, true);
    })->take(25);
@endphp
@php
    $filtroAlmKardex = (int) request('filtro_almacen_id', 0);
    if ($filtroAlmKardex > 0) {
        $kardexFiltrado = $kardexFiltrado->filter(fn ($m) => (int) ($m->almacen_id ?? 0) === $filtroAlmKardex);
    }
@endphp
@foreach ($kardexFiltrado as $mov)
                            <tr>
                                <td class="py-2 pr-2 font-mono">{{ $mov->folio }}</td>
                                <td class="py-2 px-2">{{ $mov->tipo }}</td>
                                <td class="py-2 px-2">{{ $mov->producto?->nombre ?? ('#' . $mov->producto_id) }}</td>
                                <td class="py-2 pl-2 text-right font-mono">{{ $mov->cantidad }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
        @endif
    @endif
</div>
<script>
(function () {
    function activarSubTab(target) {
        const btns = document.querySelectorAll('.trf-subtab-btn');
        const grupos = document.querySelectorAll('.trf-grupo-estado');
        btns.forEach(b => {
            b.classList.remove('bg-amber-600', 'bg-emerald-600', 'bg-rose-700', 'text-white');
            b.classList.add('taller-surface', 'border');
        });
        const activeBtn = document.querySelector('.trf-subtab-btn[data-trf-subtab="' + target + '"]');
        if (activeBtn) {
            activeBtn.classList.remove('taller-surface', 'border');
            if (target === 'completados') activeBtn.classList.add('bg-emerald-600', 'text-white');
            else if (target === 'cancelados') activeBtn.classList.add('bg-rose-700', 'text-white');
            else activeBtn.classList.add('bg-amber-600', 'text-white');
        }
        grupos.forEach(g => {
            if (g.id === 'trf-grupo-' + target) {
                g.classList.remove('hidden');
                g.style.display = '';
            } else {
                g.classList.add('hidden');
                g.style.display = 'none';
            }
        });
    }

    function activarVistaPrincipal(modo) {
        const panelSol = document.getElementById('panel-trf-solicitar');
        const panelCon = document.getElementById('panel-trf-consulta');
        const tabSol = document.getElementById('tab-trf-solicitar');
        const tabCon = document.getElementById('tab-trf-consulta');
        if (panelSol && panelCon) {
            if (modo === 'solicitar') {
                panelSol.classList.remove('hidden');
                panelSol.style.display = '';
                panelCon.classList.add('hidden');
                panelCon.style.display = 'none';
            } else {
                panelCon.classList.remove('hidden');
                panelCon.style.display = '';
                panelSol.classList.add('hidden');
                panelSol.style.display = 'none';
            }
        }
        if (tabSol && tabCon) {
            if (modo === 'solicitar') {
                tabSol.style.backgroundColor = 'var(--taller-primary)';
                tabSol.style.color = '#fff';
                tabCon.style.backgroundColor = '';
            } else {
                tabCon.style.backgroundColor = 'var(--taller-primary)';
                tabCon.style.color = '#fff';
                tabSol.style.backgroundColor = '';
            }
        }
    }

    document.addEventListener('click', function (e) {
        const subBtn = e.target.closest('.trf-subtab-btn');
        if (subBtn) {
            e.preventDefault();
            activarSubTab(subBtn.getAttribute('data-trf-subtab'));
            return;
        }
        if (e.target.closest('#tab-trf-solicitar')) {
            e.preventDefault();
            activarVistaPrincipal('solicitar');
            return;
        }
        if (e.target.closest('#tab-trf-consulta')) {
            e.preventDefault();
            activarVistaPrincipal('consulta');
        }
    });

    document.addEventListener('DOMContentLoaded', function () {
        activarSubTab('parciales');
    });
})();
</script>
</body>
</html>