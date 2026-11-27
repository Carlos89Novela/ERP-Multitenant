<div class="space-y-6 p-4 md:p-6 bg-slate-50 text-slate-800">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 border-b border-slate-200 pb-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Inventario y Sucursales — Taller de Reparación</h1>
            <p class="text-sm text-slate-600">Operación de propietario con control de sucursales, almacenes, costo promedio ponderado, consumos en orden y traspasos.</p>
        </div>
        <form method="GET" action="" class="flex items-center gap-2">
            <input
                type="text"
                name="buscar"
                value="{{ request('buscar') }}"
                placeholder="Buscar SKU o refacción..."
                class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none"
            >
            <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                Buscar
            </button>
        </form>
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="rounded-lg border border-rose-300 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            <ul class="list-disc pl-5 space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Sucursales y Almacenes --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900 mb-3">Sucursales y Almacenes Activos</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="border-b border-slate-200 text-xs uppercase text-slate-500">
                        <tr>
                            <th class="py-2 pr-3">Almacén</th>
                            <th class="py-2 px-3">Código</th>
                            <th class="py-2 px-3">Sucursal</th>
                            <th class="py-2 px-3">Tipo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach (($matriz['almacenes'] ?? collect()) as $alm)
                            <tr>
                                <td class="py-2 pr-3 font-medium text-slate-900">{{ $alm->nombre }}</td>
                                <td class="py-2 px-3 font-mono text-xs">{{ $alm->codigo }}</td>
                                <td class="py-2 px-3">{{ $alm->sucursal?->nombre ?? 'Traspaso / Tránsito' }}</td>
                                <td class="py-2 px-3">
                                    <span class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $alm->es_virtual ? 'bg-amber-100 text-amber-800' : 'bg-indigo-100 text-indigo-800' }}">
                                        {{ $alm->tipo }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900 mb-3">Nueva Sucursal (Crea Almacén)</h2>
            <form method="POST" action="{{ route('taller.inventario.sucursales.store') }}" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Nombre de sucursal</label>
                    <input type="text" name="nombre" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Código (opcional)</label>
                    <input type="text" name="codigo" placeholder="Ej. SUC-NORTE" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
                <button type="submit" class="w-full rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
                    Crear Sucursal y Almacén
                </button>
            </form>
        </div>
    </div>

    {{-- Catálogo y Entrada con Costo Promedio Ponderado --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm space-y-4">
            <h2 class="text-lg font-semibold text-slate-900">Catálogo Rápido</h2>

            <form method="POST" action="{{ route('taller.inventario.categorias.store') }}" class="space-y-2 border-b border-slate-100 pb-4">
                @csrf
                <p class="text-xs font-semibold uppercase text-slate-500">Nueva Categoría</p>
                <input type="text" name="nombre" required placeholder="Ej. Pantallas, SSD, Baterías" class="w-full rounded-lg border border-slate-300 px-3 py-1.5 text-sm">
                <button type="submit" class="w-full rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-800 hover:bg-slate-200">
                    Guardar Categoría
                </button>
            </form>

            <form method="POST" action="{{ route('taller.inventario.productos.store') }}" class="space-y-2">
                @csrf
                <p class="text-xs font-semibold uppercase text-slate-500">Nueva Refacción / Producto</p>
                <input type="text" name="nombre" required placeholder="Nombre de la refacción" class="w-full rounded-lg border border-slate-300 px-3 py-1.5 text-sm">
                <input type="text" name="sku" placeholder="SKU (opcional)" class="w-full rounded-lg border border-slate-300 px-3 py-1.5 text-sm">
                <div class="grid grid-cols-2 gap-2">
                    <input type="number" step="0.0001" min="0" name="costo_referencia" placeholder="Costo ref." class="w-full rounded-lg border border-slate-300 px-3 py-1.5 text-sm">
                    <input type="number" step="0.0001" min="0" name="precio_venta" placeholder="Precio venta" class="w-full rounded-lg border border-slate-300 px-3 py-1.5 text-sm">
                </div>
                <button type="submit" class="w-full rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-700">
                    Registrar Refacción
                </button>
            </form>
        </div>

        <div class="lg:col-span-2 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900 mb-3">Registrar Entrada de Inventario (CPP)</h2>
            <form method="POST" action="{{ route('taller.inventario.entradas.store') }}" class="space-y-3">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Almacén</label>
                        <select name="almacen_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            @foreach (($matriz['almacenes'] ?? collect()) as $alm)
                                @if (! $alm->es_virtual)
                                    <option value="{{ $alm->id }}">{{ $alm->nombre }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Tipo</label>
                        <select name="tipo" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            <option value="compra">compra</option>
                            <option value="recepcion">recepcion</option>
                            <option value="devolucion">devolucion</option>
                            <option value="ajuste_positivo">ajuste_positivo</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Referencia</label>
                        <input type="text" name="referencia_externa" placeholder="Factura / Nota" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">ID Producto</label>
                        <input type="number" name="lineas[0][producto_id]" required min="1" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Cantidad</label>
                        <input type="number" step="0.0001" min="0.0001" name="lineas[0][cantidad]" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Costo Unitario</label>
                        <input type="number" step="0.0001" min="0" name="lineas[0][costo_unitario]" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Motivo</label>
                    <input type="text" name="motivo" required placeholder="Motivo de la entrada" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>

                <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                    Registrar Entrada y Recalcular CPP
                </button>
            </form>
        </div>
    </div>

    {{-- Salidas Directas, Consumo en Orden y Ajustes --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-base font-semibold text-slate-900 mb-3">Salida Directa</h2>
            <form method="POST" action="{{ route('taller.inventario.salidas.store') }}" class="space-y-2.5">
                @csrf
                <select name="almacen_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    @foreach (($matriz['almacenes'] ?? collect()) as $alm)
                        @if (! $alm->es_virtual)
                            <option value="{{ $alm->id }}">{{ $alm->nombre }}</option>
                        @endif
                    @endforeach
                </select>
                <input type="number" name="producto_id" required min="1" placeholder="ID Producto" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <input type="number" step="0.0001" min="0.0001" name="cantidad" required placeholder="Cantidad" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <input type="text" name="motivo" required placeholder="Motivo" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <button type="submit" class="w-full rounded-lg bg-amber-600 px-4 py-2 text-sm font-medium text-white hover:bg-amber-700">
                    Registrar Salida
                </button>
            </form>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-base font-semibold text-slate-900 mb-3">Consumo en Orden de Reparación</h2>
            <form method="POST" action="{{ route('taller.inventario.consumos.store') }}" class="space-y-2.5">
                @csrf
                <select name="almacen_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    @foreach (($matriz['almacenes'] ?? collect()) as $alm)
                        @if (! $alm->es_virtual)
                            <option value="{{ $alm->id }}">{{ $alm->nombre }}</option>
                        @endif
                    @endforeach
                </select>
                <input type="number" name="producto_id" required min="1" placeholder="ID Producto" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <input type="text" name="orden_folio" placeholder="Folio de orden (ej. ORD-001)" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <input type="number" step="0.0001" min="0.0001" name="cantidad" required placeholder="Cantidad consumida" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <button type="submit" class="w-full rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                    Aplicar Consumo a Orden
                </button>
            </form>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-base font-semibold text-slate-900 mb-3">Ajuste de Inventario</h2>
            <form method="POST" action="{{ route('taller.inventario.ajustes.store') }}" class="space-y-2.5">
                @csrf
                <select name="almacen_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    @foreach (($matriz['almacenes'] ?? collect()) as $alm)
                        @if (! $alm->es_virtual)
                            <option value="{{ $alm->id }}">{{ $alm->nombre }}</option>
                        @endif
                    @endforeach
                </select>
                <input type="number" name="producto_id" required min="1" placeholder="ID Producto" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <input type="number" step="0.0001" min="0" name="cantidad_objetivo" required placeholder="Existencia física objetivo" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <input type="text" name="motivo" required placeholder="Motivo del ajuste" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <button type="submit" class="w-full rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">
                    Aplicar Ajuste
                </button>
            </form>
        </div>
    </div>

    {{-- Traspasos Entre Almacenes --}}
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm space-y-4">
        <h2 class="text-lg font-semibold text-slate-900">Traspasos Entre Almacenes (Vía Almacén de Traspaso)</h2>

        <form method="POST" action="{{ route('taller.inventario.transferencias.store') }}" class="grid grid-cols-1 md:grid-cols-5 gap-3 border-b border-slate-100 pb-4">
            @csrf
            <select name="almacen_origen_id" required class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <option value="">Origen...</option>
                @foreach (($matriz['almacenes'] ?? collect()) as $alm)
                    @if (! $alm->es_virtual)
                        <option value="{{ $alm->id }}">{{ $alm->nombre }}</option>
                    @endif
                @endforeach
            </select>
            <select name="almacen_destino_id" required class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <option value="">Destino...</option>
                @foreach (($matriz['almacenes'] ?? collect()) as $alm)
                    @if (! $alm->es_virtual)
                        <option value="{{ $alm->id }}">{{ $alm->nombre }}</option>
                    @endif
                @endforeach
            </select>
            <input type="number" name="lineas[0][producto_id]" required min="1" placeholder="ID Producto" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <input type="number" step="0.0001" min="0.0001" name="lineas[0][cantidad]" required placeholder="Cantidad" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <input type="text" name="motivo" required placeholder="Motivo del traspaso" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <div class="md:col-span-5 flex justify-end">
                <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                    Solicitar Traspaso
                </button>
            </div>
        </form>

        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b border-slate-200 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="py-2 pr-3">Folio</th>
                        <th class="py-2 px-3">Origen → Destino</th>
                        <th class="py-2 px-3">Estado</th>
                        <th class="py-2 pl-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach (($transferencias ?? collect()) as $trf)
                        <tr>
                            <td class="py-2 pr-3 font-mono text-xs font-semibold">{{ $trf->folio }}</td>
                            <td class="py-2 px-3">{{ $trf->almacenOrigen?->nombre }} → {{ $trf->almacenDestino?->nombre }}</td>
                            <td class="py-2 px-3">
                                <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-800">{{ $trf->estado }}</span>
                            </td>
                            <td class="py-2 pl-3 text-right space-x-2">
                                @if ($trf->estado === 'solicitada')
                                    <form method="POST" action="{{ route('taller.inventario.transferencias.despachar', $trf->id) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="rounded bg-amber-600 px-2.5 py-1 text-xs font-medium text-white hover:bg-amber-700">Despachar</button>
                                    </form>
                                    <form method="POST" action="{{ route('taller.inventario.transferencias.cancelar', $trf->id) }}" class="inline">
                                        @csrf
                                        <input type="hidden" name="motivo" value="Cancelado desde panel">
                                        <button type="submit" class="rounded bg-rose-600 px-2.5 py-1 text-xs font-medium text-white hover:bg-rose-700">Cancelar</button>
                                    </form>
                                @elseif ($trf->estado === 'en_transito')
                                    <form method="POST" action="{{ route('taller.inventario.transferencias.recibir', $trf->id) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="rounded bg-emerald-600 px-2.5 py-1 text-xs font-medium text-white hover:bg-emerald-700">Recibir</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Consulta Entre Almacenes y Kardex --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900 mb-3">Existencias por Refacción y Almacén</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-xs">
                    <thead class="border-b border-slate-200 uppercase text-slate-500">
                        <tr>
                            <th class="py-2 pr-2">Refacción</th>
                            <th class="py-2 px-2">Almacén</th>
                            <th class="py-2 px-2 text-right">Físico</th>
                            <th class="py-2 px-2 text-right">Disp.</th>
                            <th class="py-2 pl-2 text-right">CPP</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach (($existencias ?? collect()) as $ext)
                            <tr>
                                <td class="py-2 pr-2 font-medium">{{ $ext['producto'] }} <span class="text-slate-400">(#{{ $ext['producto_id'] }})</span></td>
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

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900 mb-3">Kardex Reciente (Inmutable)</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-xs">
                    <thead class="border-b border-slate-200 uppercase text-slate-500">
                        <tr>
                            <th class="py-2 pr-2">Folio</th>
                            <th class="py-2 px-2">Tipo</th>
                            <th class="py-2 px-2">Refacción</th>
                            <th class="py-2 pl-2 text-right">Cant.</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach (($kardex ?? collect())->take(25) as $mov)
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
</div>