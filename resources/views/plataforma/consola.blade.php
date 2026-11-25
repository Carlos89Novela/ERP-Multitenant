<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consola de Plataforma — ERP-Multitenant</title>
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
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
        <!-- Encabezado -->
        <header class="tpl-card p-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <span class="inline-flex items-center px-2.5 py-0.5 text-xs font-semibold rounded-full tpl-surface tpl-accent">
                    PROVEEDOR DE PLATAFORMA • AISLAMIENTO DE PRIVACIDAD ACTIVO
                </span>
                <h1 class="text-2xl font-bold mt-2">Consola de Plataforma, Timbres y Monitoreo</h1>
                <p class="tpl-muted text-sm mt-1">
                    Alta de clientes en renta por giro, compra/asignación de timbres al por mayor, monitoreo técnico sin acceso a datos privados y personalización visual de tu consola.
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="#seccion-monitoreo" class="px-3 py-2 text-xs font-medium tpl-surface hover:opacity-90">Monitoreo</a>
                <a href="#seccion-empresas" class="px-3 py-2 text-xs font-medium tpl-surface hover:opacity-90">Alta Empresas</a>
                <a href="#seccion-timbres" class="px-3 py-2 text-xs font-medium tpl-surface hover:opacity-90">Bolsa Timbres</a>
                <a href="#seccion-template" class="px-3 py-2 text-xs font-medium tpl-btn-primary">Personalizar Template</a>
            </div>
        </header>

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

        <!-- KPIs Globales de Plataforma (solo servicio y timbres) -->
        <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            <div class="tpl-card p-4">
                <p class="text-xs tpl-muted uppercase">Empresas Registradas</p>
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
                <p class="text-xs tpl-muted uppercase">Timbres en Clientes</p>
                <p class="text-2xl font-bold mt-1 tpl-mono">{{ number_format($resumen['timbres_asignados_empresas']) }}</p>
            </div>
            <div class="tpl-card p-4">
                <p class="text-xs tpl-muted uppercase">Alertas Saldo Bajo</p>
                <p class="text-2xl font-bold mt-1 {{ $resumen['alertas_timbres'] > 0 ? 'text-amber-400' : 'tpl-accent' }}">
                    {{ $resumen['alertas_timbres'] }}
                </p>
            </div>
        </section>

        <!-- 1. MONITOREO DE SERVICIOS (SIN ACCESO A DATOS OPERATIVOS PRIVADOS) -->
        <section id="seccion-monitoreo" class="tpl-card p-6 space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <div>
                    <h2 class="text-lg font-semibold">1. Monitoreo Técnico de Páginas y Timbres</h2>
                    <p class="text-xs tpl-muted">
                        Supervisión exclusiva de disponibilidad, latencia, páginas habilitadas y saldo de timbres. Cero visibilidad de ventas, clientes u órdenes internas.
                    </p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="border-b" style="border-color: var(--tpl-border);">
                            <th class="py-3 px-3 tpl-muted font-medium">Empresa / Giro</th>
                            <th class="py-3 px-3 tpl-muted font-medium">Estado Página</th>
                            <th class="py-3 px-3 tpl-muted font-medium">Páginas Habilitadas</th>
                            <th class="py-3 px-3 tpl-muted font-medium">Timbres</th>
                            <th class="py-3 px-3 tpl-muted font-medium">Latencia / Último Latido</th>
                            <th class="py-3 px-3 tpl-muted font-medium">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y" style="border-color: var(--tpl-border);">
                        @forelse($empresas as $emp)
                            @php
                                $mon = $emp->monitoreo;
                                $estadoPagina = $mon->estado_pagina ?? ($emp->activa ? 'activo' : 'inactivo');
                                $estadoTimbres = $mon->estado_timbres ?? ($emp->saldo_timbres <= 0 ? 'agotado' : ($emp->saldo_timbres <= $emp->umbral_alerta_timbres ? 'bajo' : 'ok'));
                            @endphp
                            <tr>
                                <td class="py-3 px-3">
                                    <div class="font-semibold">{{ $emp->nombre }}</div>
                                    <div class="text-xs tpl-muted">
                                        Giro fijo: <span class="tpl-mono">{{ $girosDisponibles[$emp->giro_comercial] ?? $emp->giro_comercial }}</span>
                                        @if($emp->rfc) • RFC: <span class="tpl-mono">{{ $emp->rfc }}</span> @endif
                                    </div>
                                </td>
                                <td class="py-3 px-3">
                                    @if($estadoPagina === 'activo')
                                        <span class="px-2 py-0.5 text-xs rounded-full bg-emerald-500/20 text-emerald-300">Activo</span>
                                    @elseif($estadoPagina === 'degradado')
                                        <span class="px-2 py-0.5 text-xs rounded-full bg-amber-500/20 text-amber-300">Degradado</span>
                                    @else
                                        <span class="px-2 py-0.5 text-xs rounded-full bg-rose-500/20 text-rose-300">Inactivo</span>
                                    @endif
                                </td>
                                <td class="py-3 px-3">
                                    <div class="flex flex-wrap gap-1">
                                        @foreach(($emp->paginas_habilitadas ?? []) as $pag)
                                            <span class="px-2 py-0.5 text-xs tpl-surface">{{ $paginasCatalogo[$pag] ?? $pag }}</span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="py-3 px-3 tpl-mono">
                                    <span class="font-semibold">{{ number_format($emp->saldo_timbres) }}</span>
                                    <span class="ml-1 text-xs {{ $estadoTimbres === 'ok' ? 'text-emerald-400' : ($estadoTimbres === 'bajo' ? 'text-amber-400' : 'text-rose-400') }}">
                                        ({{ strtoupper($estadoTimbres) }})
                                    </span>
                                </td>
                                <td class="py-3 px-3 text-xs tpl-muted">
                                    <div>{{ $mon?->latencia_ms ? $mon->latencia_ms . ' ms' : 'Sin ping' }} • {{ $mon?->ultimo_latido_at ? $mon->ultimo_latido_at->diffForHumans() : 'Nunca' }}</div>
                                    <div class="truncate max-w-xs">{{ $mon?->ultimo_evento_servicio ?? 'Sin eventos' }}</div>
                                </td>
                                <td class="py-3 px-3">
                                    <div class="flex flex-wrap gap-2">
                                        <form method="POST" action="{{ route('plataforma.empresas.ping', $emp) }}">
                                            @csrf
                                            <button type="submit" class="px-2.5 py-1 text-xs tpl-surface hover:opacity-90">Verificar Ping</button>
                                        </form>
                                        <form method="POST" action="{{ route('plataforma.empresas.servicio', $emp) }}">
                                            @csrf
                                            <input type="hidden" name="activa" value="{{ $emp->activa ? 0 : 1 }}">
                                            @foreach(($emp->paginas_habilitadas ?? []) as $p)
                                                <input type="hidden" name="paginas_habilitadas[]" value="{{ $p }}">
                                            @endforeach
                                            <button type="submit" class="px-2.5 py-1 text-xs rounded {{ $emp->activa ? 'bg-rose-600/80 text-white' : 'bg-emerald-600/80 text-white' }}">
                                                {{ $emp->activa ? 'Suspender' : 'Activar' }}
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-6 px-3 text-center tpl-muted">
                                    No hay empresas dadas de alta todavía (base limpia). Usa el formulario inferior para dar de alta tu primera empresa o instancia de prueba.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <!-- 2. ALTA DE EMPRESAS EN RENTA Y GESTION DE PAGINAS -->
        <section id="seccion-empresas" class="tpl-card p-6 space-y-4">
            <h2 class="text-lg font-semibold">2. Alta de Empresa en Renta (Giro Fijo y Páginas Habilitadas)</h2>
            <form method="POST" action="{{ route('plataforma.empresas.store') }}" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @csrf
                <div>
                    <label class="block text-xs tpl-muted mb-1">Nombre comercial de la empresa</label>
                    <input type="text" name="nombre" required placeholder="Ej. Taller Norte PC" class="w-full px-3 py-2 tpl-surface text-sm">
                </div>
                <div>
                    <label class="block text-xs tpl-muted mb-1">RFC (opcional)</label>
                    <input type="text" name="rfc" placeholder="XAXX010101000" class="w-full px-3 py-2 tpl-surface text-sm tpl-mono">
                </div>
                <div>
                    <label class="block text-xs tpl-muted mb-1">Giro comercial (queda fijo al crear)</label>
                    <select name="giro_comercial" required class="w-full px-3 py-2 tpl-surface text-sm">
                        @foreach($girosDisponibles as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs tpl-muted mb-1">Nombre de sucursal matriz</label>
                    <input type="text" name="sucursal_nombre" required value="Sucursal Matriz" class="w-full px-3 py-2 tpl-surface text-sm">
                </div>
                <div>
                    <label class="block text-xs tpl-muted mb-1">Umbral de alerta por timbres bajos</label>
                    <input type="number" name="umbral_alerta_timbres" required value="25" min="1" class="w-full px-3 py-2 tpl-surface text-sm tpl-mono">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs tpl-muted mb-2">Páginas habilitadas para este cliente</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2">
                        @foreach($paginasCatalogo as $clave => $tituloPagina)
                            <label class="flex items-center gap-2 p-2.5 tpl-surface text-xs cursor-pointer">
                                <input type="checkbox" name="paginas_habilitadas[]" value="{{ $clave }}" checked>
                                <span>{{ $tituloPagina }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
                <div class="md:col-span-2">
                    <button type="submit" class="px-4 py-2 text-sm font-medium tpl-btn-primary">Dar de alta empresa y activar monitoreo</button>
                </div>
            </form>
        </section>

        <!-- 3. BOLSA MAYORISTA DE TIMBRES Y ASIGNACION A EMPRESAS -->
        <section id="seccion-timbres" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="tpl-card p-6 space-y-4">
                <h2 class="text-lg font-semibold">3A. Comprar Lote de Timbres al por Mayor</h2>
                <form method="POST" action="{{ route('plataforma.timbres.lote') }}" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-xs tpl-muted mb-1">Folio / Factura de compra mayorista</label>
                        <input type="text" name="folio_compra" required placeholder="PAC-2026-001" class="w-full px-3 py-2 tpl-surface text-sm tpl-mono">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs tpl-muted mb-1">Proveedor PAC</label>
                            <input type="text" name="proveedor" placeholder="Finkok / SW / Diverza" class="w-full px-3 py-2 tpl-surface text-sm">
                        </div>
                        <div>
                            <label class="block text-xs tpl-muted mb-1">Cantidad de timbres</label>
                            <input type="number" name="cantidad_comprada" required min="1" placeholder="5000" class="w-full px-3 py-2 tpl-surface text-sm tpl-mono">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs tpl-muted mb-1">Costo unitario (opcional)</label>
                        <input type="number" step="0.0001" name="costo_unitario" placeholder="0.3500" class="w-full px-3 py-2 tpl-surface text-sm tpl-mono">
                    </div>
                    <button type="submit" class="px-4 py-2 text-sm font-medium tpl-btn-primary">Registrar compra en mi bolsa</button>
                </form>

                <div class="pt-3 border-t space-y-2" style="border-color: var(--tpl-border);">
                    <p class="text-xs font-semibold tpl-muted uppercase">Lotes disponibles en bolsa</p>
                    @forelse($lotesTimbres as $lote)
                        <div class="p-2.5 tpl-surface flex justify-between items-center text-xs">
                            <div>
                                <span class="font-semibold tpl-mono">{{ $lote->folio_compra }}</span>
                                <span class="tpl-muted">({{ $lote->proveedor }})</span>
                            </div>
                            <span class="tpl-mono tpl-accent font-semibold">
                                {{ number_format($lote->cantidad_disponible) }} / {{ number_format($lote->cantidad_comprada) }}
                            </span>
                        </div>
                    @empty
                        <p class="text-xs tpl-muted">Aún no has registrado lotes mayoristas.</p>
                    @endforelse
                </div>
            </div>

            <div class="tpl-card p-6 space-y-4">
                <h2 class="text-lg font-semibold">3B. Asignar Timbres a una Empresa</h2>
                <form method="POST" action="{{ route('plataforma.timbres.asignar') }}" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-xs tpl-muted mb-1">Empresa destino</label>
                        <select name="empresa_id" required class="w-full px-3 py-2 tpl-surface text-sm">
                            <option value="">Selecciona empresa...</option>
                            @foreach($empresas as $emp)
                                <option value="{{ $emp->id }}">{{ $emp->nombre }} (Saldo actual: {{ $emp->saldo_timbres }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs tpl-muted mb-1">Lote mayorista de origen</label>
                        <select name="lote_id" required class="w-full px-3 py-2 tpl-surface text-sm">
                            <option value="">Selecciona lote...</option>
                            @foreach($lotesTimbres->where('cantidad_disponible', '>', 0) as $lote)
                                <option value="{{ $lote->id }}">{{ $lote->folio_compra }} — Disponibles: {{ number_format($lote->cantidad_disponible) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs tpl-muted mb-1">Cantidad a asignar</label>
                        <input type="number" name="cantidad" required min="1" placeholder="200" class="w-full px-3 py-2 tpl-surface text-sm tpl-mono">
                    </div>
                    <div>
                        <label class="block text-xs tpl-muted mb-1">Concepto / Referencia de pago</label>
                        <input type="text" name="motivo" placeholder="Paquete 200 timbres — Transferencia #45" class="w-full px-3 py-2 tpl-surface text-sm">
                    </div>
                    <button type="submit" class="px-4 py-2 text-sm font-medium tpl-btn-primary">Asignar timbres ahora</button>
                </form>

                <div class="pt-3 border-t space-y-2" style="border-color: var(--tpl-border);">
                    <p class="text-xs font-semibold tpl-muted uppercase">Últimas asignaciones</p>
                    @forelse($asignacionesRecientes as $asig)
                        <div class="p-2.5 tpl-surface flex justify-between items-center text-xs">
                            <div>
                                <span class="font-semibold">{{ $asig->empresa?->nombre }}</span>
                                <span class="tpl-muted">• {{ $asig->motivo }}</span>
                            </div>
                            <span class="tpl-mono tpl-accent">+{{ number_format($asig->cantidad) }}</span>
                        </div>
                    @empty
                        <p class="text-xs tpl-muted">Sin asignaciones registradas todavía.</p>
                    @endforelse
                </div>
            </div>
        </section>

        <!-- 4. PERSONALIZACION DEL TEMPLATE DE TU CONSOLA DE PLATAFORMA -->
        <section id="seccion-template" class="tpl-card p-6 space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold">4. Template Administrable de tu Consola de Plataforma</h2>
                    <p class="text-xs tpl-muted">Elige un preset o ajusta colores, tipografía, tamaños, bordes y fondo para tus páginas de administración.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button type="button" onclick="aplicarPreset('indigo')" class="px-2.5 py-1 text-xs tpl-surface">Preset Índigo Oscuro</button>
                    <button type="button" onclick="aplicarPreset('esmeralda')" class="px-2.5 py-1 text-xs tpl-surface">Preset Esmeralda</button>
                    <button type="button" onclick="aplicarPreset('ejecutivo')" class="px-2.5 py-1 text-xs tpl-surface">Preset Ámbar Ejecutivo</button>
                    <button type="button" onclick="aplicarPreset('claro')" class="px-2.5 py-1 text-xs tpl-surface">Preset Claro</button>
                </div>
            </div>

            <form id="form-template-plataforma" method="POST" action="{{ route('plataforma.template.guardar') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                @csrf
                <div>
                    <label class="block text-xs tpl-muted mb-1">Fuente principal</label>
                    <input type="text" name="fuente_primaria" value="{{ $template->fuente_primaria }}" class="w-full px-3 py-2 tpl-surface text-xs">
                </div>
                <div>
                    <label class="block text-xs tpl-muted mb-1">Fuente secundaria (código/folios)</label>
                    <input type="text" name="fuente_secundaria" value="{{ $template->fuente_secundaria }}" class="w-full px-3 py-2 tpl-surface text-xs">
                </div>
                <div>
                    <label class="block text-xs tpl-muted mb-1">Tamaño base (px)</label>
                    <input type="number" name="tamano_base_px" min="11" max="22" value="{{ $template->tamano_base_px }}" class="w-full px-3 py-2 tpl-surface text-xs">
                </div>
                <div>
                    <label class="block text-xs tpl-muted mb-1">Radio de bordes</label>
                    <input type="text" name="radio_bordes" value="{{ $template->radio_bordes }}" class="w-full px-3 py-2 tpl-surface text-xs">
                </div>

                <div>
                    <label class="block text-xs tpl-muted mb-1">Color primario</label>
                    <input type="color" name="color_primario" value="{{ $template->color_primario }}" class="w-full h-9 tpl-surface">
                </div>
                <div>
                    <label class="block text-xs tpl-muted mb-1">Color secundario</label>
                    <input type="color" name="color_secundario" value="{{ $template->color_secundario }}" class="w-full h-9 tpl-surface">
                </div>
                <div>
                    <label class="block text-xs tpl-muted mb-1">Color acento</label>
                    <input type="color" name="color_acento" value="{{ $template->color_acento }}" class="w-full h-9 tpl-surface">
                </div>
                <div>
                    <label class="block text-xs tpl-muted mb-1">Color borde</label>
                    <input type="color" name="color_borde" value="{{ $template->color_borde }}" class="w-full h-9 tpl-surface">
                </div>

                <div>
                    <label class="block text-xs tpl-muted mb-1">Fondo página</label>
                    <input type="color" name="color_fondo_pagina" value="{{ $template->color_fondo_pagina }}" class="w-full h-9 tpl-surface">
                </div>
                <div>
                    <label class="block text-xs tpl-muted mb-1">Fondo superficie</label>
                    <input type="color" name="color_fondo_superficie" value="{{ $template->color_fondo_superficie }}" class="w-full h-9 tpl-surface">
                </div>
                <div>
                    <label class="block text-xs tpl-muted mb-1">Fondo tarjeta</label>
                    <input type="color" name="fondo_tarjeta" value="{{ $template->fondo_tarjeta }}" class="w-full h-9 tpl-surface">
                </div>
                <div>
                    <label class="block text-xs tpl-muted mb-1">Texto principal</label>
                    <input type="color" name="color_texto_principal" value="{{ $template->color_texto_principal }}" class="w-full h-9 tpl-surface">
                </div>

                <div>
                    <label class="block text-xs tpl-muted mb-1">Texto secundario</label>
                    <input type="color" name="color_texto_secundario" value="{{ $template->color_texto_secundario }}" class="w-full h-9 tpl-surface">
                </div>
                <div>
                    <label class="block text-xs tpl-muted mb-1">Tipo de fondo</label>
                    <select name="tipo_fondo" class="w-full px-3 py-2 tpl-surface text-xs">
                        <option value="solido" @selected($template->tipo_fondo === 'solido')>Sólido</option>
                        <option value="degradado" @selected($template->tipo_fondo === 'degradado')>Degradado CSS</option>
                        <option value="imagen" @selected($template->tipo_fondo === 'imagen')>Imagen URL</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs tpl-muted mb-1">Valor fondo (degradado o URL)</label>
                    <input type="text" name="valor_fondo" value="{{ $template->valor_fondo }}" placeholder="linear-gradient(135deg, #09090b, #1e1b4b)" class="w-full px-3 py-2 tpl-surface text-xs">
                </div>
                <div>
                    <label class="block text-xs tpl-muted mb-1">Estilo de íconos</label>
                    <select name="estilo_icono" class="w-full px-3 py-2 tpl-surface text-xs">
                        <option value="heroicons" @selected($template->estilo_icono === 'heroicons')>Heroicons Outline</option>
                        <option value="lucide" @selected($template->estilo_icono === 'lucide')>Lucide Minimal</option>
                        <option value="solid" @selected($template->estilo_icono === 'solid')>Solid Bold</option>
                    </select>
                </div>

                <div class="sm:col-span-2 lg:col-span-4">
                    <button type="submit" class="px-4 py-2 text-sm font-medium tpl-btn-primary">Guardar template de plataforma en BD</button>
                </div>
            </form>
        </section>
    </div>

    <script>
        const PRESETS = {
            indigo: {
                color_primario: '#6366f1', color_secundario: '#3b82f6', color_acento: '#10b981',
                color_fondo_pagina: '#09090b', color_fondo_superficie: '#18181b', fondo_tarjeta: '#111115',
                color_texto_principal: '#f4f4f5', color_texto_secundario: '#a1a1aa', color_borde: '#27272a',
                radio_bordes: '0.75rem'
            },
            esmeralda: {
                color_primario: '#059669', color_secundario: '#0d9488', color_acento: '#34d399',
                color_fondo_pagina: '#041311', color_fondo_superficie: '#0b221e', fondo_tarjeta: '#071916',
                color_texto_principal: '#ecfdf5', color_texto_secundario: '#a7f3d0', color_borde: '#134e4a',
                radio_bordes: '0.625rem'
            },
            ejecutivo: {
                color_primario: '#d97706', color_secundario: '#b45309', color_acento: '#fbbf24',
                color_fondo_pagina: '#0c0a09', color_fondo_superficie: '#1c1917', fondo_tarjeta: '#141210',
                color_texto_principal: '#fafaf9', color_texto_secundario: '#a8a29e', color_borde: '#292524',
                radio_bordes: '0.5rem'
            },
            claro: {
                color_primario: '#2563eb', color_secundario: '#1d4ed8', color_acento: '#059669',
                color_fondo_pagina: '#f8fafc', color_fondo_superficie: '#f1f5f9', fondo_tarjeta: '#ffffff',
                color_texto_principal: '#0f172a', color_texto_secundario: '#475569', color_borde: '#cbd5e1',
                radio_bordes: '0.75rem'
            }
        };

        function aplicarPreset(nombre) {
            const p = PRESETS[nombre];
            if (!p) return;
            const form = document.getElementById('form-template-plataforma');
            Object.entries(p).forEach(([k, v]) => {
                const input = form.querySelector(`[name="${k}"]`);
                if (input) input.value = v;
            });
        }
    </script>
</body>
</html>
