<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Órdenes solicitadas — {{ $empresa->nombre ?? 'Taller' }}</title>
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
        body { font-family: var(--taller-font); background-color: var(--taller-bg); color: var(--taller-text); color-scheme: dark; }
        .taller-card { background-color: var(--taller-card); border: 1px solid var(--taller-border); border-radius: var(--taller-radius); }
        .taller-surface { background-color: var(--taller-surface); border: 1px solid var(--taller-border); border-radius: var(--taller-radius); }
        .taller-input { background-color: var(--taller-surface); border: 1px solid var(--taller-border); border-radius: 0.5rem; color: var(--taller-text); }
    </style>
</head>
<body class="min-h-screen md:h-screen md:overflow-hidden flex flex-col md:flex-row antialiased">
    <aside class="w-full md:w-64 md:h-screen md:sticky md:top-0 shrink-0 border-b md:border-b-0 md:border-r p-5 flex flex-col justify-between overflow-y-auto" style="border-color: var(--taller-border); background-color: var(--taller-card);">
        <div class="space-y-6">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 flex items-center justify-center rounded-lg text-lg font-bold" style="background-color: var(--taller-primary); color: #fff;">
                    {{ $configInternas['icono_predeterminado'] ?? '💻' }}
                </div>
                <div>
                    <p class="text-[10px] font-semibold uppercase tracking-wider" style="color: var(--taller-accent);">TALLER DE CÓMPUTO</p>
                    <h2 class="text-sm font-bold leading-tight">{{ $empresa->nombre ?? 'Taller' }}</h2>
                </div>
            </div>

            <nav class="space-y-1 text-sm">
                <a href="{{ route('taller.computadoras.dashboard') }}" class="block px-3 py-2 rounded-lg hover:opacity-90" style="background-color: var(--taller-surface);">
                    Dashboard
                </a>
                <a href="{{ route('taller.computadoras.ordenes') }}" class="block px-3 py-2 rounded-lg font-medium" style="background-color: var(--taller-primary); color: #fff;">
                    Órdenes solicitadas
                </a>
                <a href="{{ route('taller.computadoras.autogestion', ['ambito' => 'publica']) }}" class="block px-3 py-2 rounded-lg hover:opacity-90" style="background-color: var(--taller-surface);">
                    Autogestión página pública
                </a>
                <a href="{{ route('taller.computadoras.autogestion', ['ambito' => 'internas']) }}" class="block px-3 py-2 rounded-lg hover:opacity-90" style="background-color: var(--taller-surface);">
                    Autogestión páginas internas
                </a>
                <a href="{{ route('taller.computadoras.publica') }}" target="_blank" class="block px-3 py-2 rounded-lg hover:opacity-90" style="background-color: var(--taller-surface);">
                    Ver página pública
                </a>
            </nav>
        </div>

        <div class="pt-4 border-t space-y-3" style="border-color: var(--taller-border);">
            <div class="text-xs">
                <p class="font-semibold">{{ $usuario->name }}</p>
                <p class="uppercase text-[10px]" style="color: var(--taller-accent);">{{ $usuario->rol_empresa }}</p>
            </div>
            <form method="POST" action="{{ route('taller.computadoras.logout') }}">
                @csrf
                <button type="submit" class="w-full py-2 px-3 text-xs font-medium taller-surface hover:opacity-90 text-left">
                    Cerrar sesión
                </button>
            </form>
        </div>
    </aside>

    <main class="flex-1 md:h-screen md:overflow-y-auto p-6 md:p-8 space-y-6">
        <header class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold">Órdenes solicitadas ({{ count($ordenes) }})</h1>
                <p class="text-xs mt-1" style="color: var(--taller-muted);">
                    Selecciona una orden o usa <strong>Administrar / Seguir</strong> para documentar el seguimiento de la orden y del equipo · Rol: <strong class="uppercase">{{ $usuario->rol_empresa }}</strong>
                </p>
            </div>
        </header>

        @if(session('status'))
            <div class="taller-card p-4 text-xs font-medium" style="border-color: var(--taller-accent); color: var(--taller-accent);">
                {{ session('status') }}
            </div>
        @endif

        @if(session('error'))
            <div class="taller-card p-4 text-xs font-medium border-red-500 text-red-400">
                {{ session('error') }}
            </div>
        @endif

        <form method="GET" action="{{ route('taller.computadoras.ordenes') }}" class="taller-card p-4 grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div>
                <label class="block text-xs mb-1" style="color: var(--taller-muted);">Buscar folio, cliente, equipo o servicio</label>
                <input type="text" name="q" value="{{ $busqueda }}" placeholder="Ej. ORD-, Laptop, Dell..." class="w-full px-3 py-2 taller-input text-xs">
            </div>
            <div>
                <label class="block text-xs mb-1" style="color: var(--taller-muted);">Filtrar por estado</label>
                <select name="estado" class="w-full px-3 py-2 taller-input text-xs">
                    <option value="">Todos los estados</option>
                    <option value="Recepción" {{ $filtroEstado === 'Recepción' ? 'selected' : '' }}>Recepción</option>
                    <option value="Diagnóstico" {{ $filtroEstado === 'Diagnóstico' ? 'selected' : '' }}>Diagnóstico</option>
                    <option value="En espera de autorización" {{ $filtroEstado === 'En espera de autorización' ? 'selected' : '' }}>En espera de autorización</option>
                    <option value="En reparación" {{ $filtroEstado === 'En reparación' ? 'selected' : '' }}>En reparación</option>
                    <option value="En pruebas" {{ $filtroEstado === 'En pruebas' ? 'selected' : '' }}>En pruebas</option>
                    <option value="Listo para entrega" {{ $filtroEstado === 'Listo para entrega' ? 'selected' : '' }}>Listo para entrega</option>
                    <option value="Entregado" {{ $filtroEstado === 'Entregado' ? 'selected' : '' }}>Entregado</option>
                    <option value="Cancelada" {{ $filtroEstado === 'Cancelada' ? 'selected' : '' }}>Cancelada</option>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="px-4 py-2 text-xs font-semibold rounded-lg" style="background-color: var(--taller-primary); color: #fff;">
                    Filtrar
                </button>
                <a href="{{ route('taller.computadoras.ordenes') }}" class="px-4 py-2 text-xs font-medium taller-surface">
                    Limpiar
                </a>
            
                            
                        </div>
        </form>

        @if(empty($ordenes))
            <div class="taller-card p-6 text-sm" style="color: var(--taller-muted);">
                No hay órdenes solicitadas que coincidan con el filtro actual.
            </div>
        @else
            @php
                $badgeEstado = function (?string $est): array {
                    $k = mb_strtolower(trim((string) $est));
                    return match (true) {
                        str_contains($k, 'recep') || str_contains($k, 'recibid') => ['bg' => 'rgba(59, 130, 246, 0.16)', 'border' => 'rgba(59, 130, 246, 0.45)', 'text' => '#93c5fd'],
                        str_contains($k, 'diagn') => ['bg' => 'rgba(245, 158, 11, 0.16)', 'border' => 'rgba(245, 158, 11, 0.45)', 'text' => '#fcd34d'],
                        str_contains($k, 'espera') => ['bg' => 'rgba(14, 165, 233, 0.16)', 'border' => 'rgba(14, 165, 233, 0.45)', 'text' => '#7dd3fc'],
                        str_contains($k, 'repar') || str_contains($k, 'proceso') => ['bg' => 'rgba(139, 92, 246, 0.16)', 'border' => 'rgba(139, 92, 246, 0.45)', 'text' => '#c4b5fd'],
                        str_contains($k, 'listo') || str_contains($k, 'lista') => ['bg' => 'rgba(16, 185, 129, 0.16)', 'border' => 'rgba(16, 185, 129, 0.45)', 'text' => '#6ee7b7'],
                        str_contains($k, 'entreg') => ['bg' => 'rgba(100, 116, 139, 0.18)', 'border' => 'rgba(148, 163, 184, 0.45)', 'text' => '#cbd5e1'],
                        str_contains($k, 'cancel') => ['bg' => 'rgba(239, 68, 68, 0.16)', 'border' => 'rgba(239, 68, 68, 0.45)', 'text' => '#fca5a5'],
                        default => ['bg' => 'rgba(99, 102, 241, 0.16)', 'border' => 'rgba(99, 102, 241, 0.45)', 'text' => '#a5b4fc'],
                    };
                };
            @endphp

            <div class="space-y-3">
                @foreach($ordenes as $orden)
                    @php
                        $stStyle = $badgeEstado($orden['estado'] ?? '');
                        $urlDetalle = route('taller.computadoras.ordenes.ver', ['folio' => $orden['folio']]);
                        $asigListaActual = $orden['asignado_a_id'] ?? null;
                    @endphp
                    <div class="taller-card p-5 space-y-3 hover:border-indigo-500/50 transition">
                        <div class="flex flex-wrap items-center justify-between gap-4">
                            <a href="{{ $urlDetalle }}" class="flex-1 min-w-[260px] space-y-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="font-mono text-xs px-2 py-0.5 rounded" style="background-color: var(--taller-surface); color: var(--taller-accent);">
                                        {{ $orden['folio'] }}
                                    </span>
                                    <span class="text-xs font-semibold uppercase px-2.5 py-0.5 rounded border" style="background-color: {{ $stStyle['bg'] }}; border-color: {{ $stStyle['border'] }}; color: {{ $stStyle['text'] }};">
                                        {{ $orden['estado'] }}
                                    </span>
                                    @if(!empty($orden['servicio']))
                                        <span class="text-xs" style="color: var(--taller-muted);">· {{ $orden['servicio'] }}</span>
                                    @endif
                                </div>
                                <h2 class="text-base font-bold pt-1">{{ $orden['equipo'] }}</h2>
                                <p class="text-xs" style="color: var(--taller-muted);">
                                    Cliente: <strong style="color: var(--taller-text);">{{ $orden['cliente_nombre'] }}</strong>
                                    @if(!empty($orden['falla'])) · {{ $orden['falla'] }} @endif
                                    · Actualizado: {{ $orden['actualizado_en'] }}
                                </p>
                            </a>

                            <div class="flex items-center gap-3">
                                @if(isset($orden['costo_estimado']) && $orden['costo_estimado'] !== null && $orden['costo_estimado'] !== '')
                                    <span class="text-xs font-semibold px-2.5 py-1 rounded taller-surface">
                                        ${{ number_format((float) $orden['costo_estimado'], 2) }}
                                    </span>
                                @endif
                                <a href="{{ $urlDetalle }}" class="px-4 py-2 text-xs font-semibold rounded-lg" style="background-color: var(--taller-primary); color: #fff;">
                                    Administrar / Seguir
                                </a>
                            </div>
                        </div>

                        <div class="pt-3 flex flex-wrap items-center justify-between gap-3" style="border-top: 1px solid rgba(255,255,255,0.08);">
                            <div class="text-xs flex items-center gap-2" style="color: var(--taller-muted);">
                                <span>Responsable asignado:</span>
                                <span class="px-2 py-0.5 rounded font-medium" style="background-color: var(--taller-surface); color: var(--taller-text);">
                                    {{ $orden['asignado_a_nombre'] ?? 'Sin asignar' }}
                                </span>
                            </div>

                            <form method="POST" action="{{ route('taller.computadoras.ordenes.actualizar', $orden['folio']) }}" class="flex items-center gap-2">
                                @csrf
                                <input type="hidden" name="estado" value="{{ $orden['estado'] ?? 'Recepción' }}">
                                <input type="hidden" name="solo_asignacion" value="1">
                                <select name="asignado_a_id" class="px-3 py-1.5 taller-input text-xs min-w-[230px]">
                                    <option value="">Sin asignar</option>
                                    @foreach(($equipoTrabajo ?? []) as $miembro)
                                        <option value="{{ $miembro['id'] }}" {{ (int) $asigListaActual === (int) $miembro['id'] ? 'selected' : '' }}>
                                            {{ $miembro['name'] }} ({{ strtoupper($miembro['rol_empresa']) }})
                                        </option>
                                    @endforeach
                                </select>
                                <button type="submit" class="px-3.5 py-1.5 rounded-lg text-xs font-semibold text-white cursor-pointer" style="background-color: var(--taller-accent);">
                                    Asignar
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

    </main>
</body>
</html>