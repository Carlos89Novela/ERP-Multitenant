<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Equipos en Reparación — {{ $empresa->nombre ?? 'Taller' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @php
            $transpPct = max(0, min(85, (int) ($config['transparencia_flotantes'] ?? 25)));
            $alphaFloat = round((100 - $transpPct) / 100, 2);

            $parseHexRgb = static function (?string $hex, string $fallback = '17, 17, 21'): string {
                $h = ltrim(trim((string) $hex), '#');
                if (strlen($h) === 3) {
                    $h = $h[0].$h[0].$h[1].$h[1].$h[2].$h[2];
                }
                if (strlen($h) !== 6 || ! ctype_xdigit($h)) {
                    return $fallback;
                }
                return hexdec(substr($h, 0, 2)) . ', ' . hexdec(substr($h, 2, 2)) . ', ' . hexdec(substr($h, 4, 2));
            };

            $bgHex = trim((string) ($config['color_fondo'] ?? '#09090b'));
            if ($bgHex === '') { $bgHex = '#09090b'; }
            $cardHex = trim((string) ($config['color_tarjeta'] ?? '#111115'));
            if ($cardHex === '') { $cardHex = '#111115'; }
            $primHex = trim((string) ($config['color_primario'] ?? '#6366f1'));
            if ($primHex === '') { $primHex = '#6366f1'; }
            $accHex = trim((string) ($config['color_acento'] ?? '#10b981'));
            if ($accHex === '') { $accHex = '#10b981'; }

            $bgRgb = $parseHexRgb($bgHex, '9, 9, 11');
            $cardRgb = $parseHexRgb($cardHex, '17, 17, 21');
            $primRgb = $parseHexRgb($primHex, '99, 102, 241');
            $accRgb = $parseHexRgb($accHex, '16, 185, 129');

            $cardRgba = "rgba({$cardRgb}, {$alphaFloat})";
            $surfRgba = "rgba({$cardRgb}, " . max(0.35, round($alphaFloat * 0.85, 2)) . ")";

            $fondoImg = trim((string) ($config['fondo_imagen'] ?? ''));
            $degTipo = (string) ($config['fondo_degradado'] ?? 'oscuro_suave');
            $fondoEstatico = (bool) ($config['fondo_estatico'] ?? true);

            $capas = [];
            if ($degTipo === 'oscuro_suave') {
                $capas[] = "linear-gradient(180deg, rgba({$bgRgb}, 0.55) 0%, rgba({$bgRgb}, 0.80) 100%)";
            } elseif ($degTipo === 'diagonal_primario') {
                $capas[] = "linear-gradient(135deg, rgba({$primRgb}, 0.35) 0%, rgba({$bgRgb}, 0.82) 70%)";
            } elseif ($degTipo === 'horizontal_acento') {
                $capas[] = "linear-gradient(90deg, rgba({$bgRgb}, 0.82) 0%, rgba({$accRgb}, 0.28) 100%)";
            } elseif ($degTipo === 'vertical_profundo') {
                $capas[] = "linear-gradient(180deg, rgba({$bgRgb}, 0.35) 0%, rgba({$bgRgb}, 0.90) 100%)";
            }
            if ($fondoImg !== '') {
                $capas[] = 'url("' . e($fondoImg) . '")';
            }
            $bgImageCss = implode(', ', $capas);
        @endphp
        :root {
            --taller-font: {{ $config['fuente_familia'] ?? 'Inter, system-ui, -apple-system, sans-serif' }};
            --taller-primary: {{ $primHex }};
            --taller-accent: {{ $accHex }};
            --taller-bg: {{ $bgHex }};
            --taller-card: {{ $cardHex }};
            --taller-card-rgba: {{ $cardRgba }};
            --taller-surface: #18181b;
            --taller-surface-rgba: {{ $surfRgba }};
            --taller-text: #f4f4f5;
            --taller-muted: #a1a1aa;
            --taller-border: #27272a;
        }
        html { background-color: #09090b; color-scheme: dark; scroll-behavior: smooth; }
        body {
            font-family: var(--taller-font);
            background-color: var(--taller-bg);
            color: var(--taller-text);
            @if($bgImageCss !== '')
                background-image: {!! $bgImageCss !!};
                background-size: cover;
                background-position: center center;
                background-repeat: no-repeat;
                background-attachment: {{ $fondoEstatico ? 'fixed' : 'scroll' }};
            @endif
        }
        .taller-card { background-color: var(--taller-card-rgba); backdrop-filter: blur(8px); border: 1px solid var(--taller-border); border-radius: 0.75rem; }
        .taller-input { background-color: var(--taller-surface-rgba); backdrop-filter: blur(6px); border: 1px solid var(--taller-border); border-radius: 0.75rem; }
        .taller-btn { background-color: var(--taller-primary); color: #ffffff; border-radius: 0.75rem; }
        ::-webkit-scrollbar { width: 10px; height: 10px; }
        ::-webkit-scrollbar-track { background: var(--taller-bg); }
        ::-webkit-scrollbar-thumb { background: var(--taller-border); border-radius: 9999px; border: 2px solid var(--taller-bg); }
        ::-webkit-scrollbar-thumb:hover { background: var(--taller-primary); }    </style>
</head>
<body class="min-h-screen antialiased">
    <header class="border-b" style="border-color: var(--taller-border); background-color: var(--taller-card);">
        <div class="max-w-6xl mx-auto px-4 py-4 flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <span class="h-10 w-10 rounded-lg flex items-center justify-center text-lg" style="background-color: var(--taller-primary);">
                    {{ $config['icono_predeterminado'] ?? '💻' }}
                </span>
                <div>
                    <p class="text-[10px] font-semibold uppercase tracking-wider" style="color: var(--taller-accent);">PORTAL DE CLIENTE</p>
                    <h1 class="text-base font-bold leading-tight">{{ $empresa->nombre ?? 'Taller de Cómputo' }}</h1>
                </div>
            </div>
            <div class="flex items-center gap-2 text-xs">
                <span class="px-3 py-1.5 taller-input">{{ $cliente['nombre'] }}</span>
                <a href="{{ route('taller.computadoras.publica') }}" class="px-3 py-1.5 taller-input hover:opacity-90">Página pública</a>
                <form method="POST" action="{{ route('taller.computadoras.cliente.logout') }}">
                    @csrf
                    <button type="submit" class="px-3 py-1.5 rounded-lg border border-rose-500/60 text-rose-300 hover:bg-rose-950/30">Salir</button>
                </form>
            </div>
        </div>
    </header>

    <main class="max-w-6xl mx-auto px-4 py-8 space-y-8">
        @if(session('status'))
            <div class="p-3 rounded border border-emerald-500/60 bg-emerald-950/30 text-xs text-emerald-300">{{ session('status') }}</div>
        @endif

        @if($errors->any())
            <div class="p-3 rounded border border-rose-500/60 bg-rose-950/30 text-xs text-rose-300 space-y-1">
                @foreach($errors->all() as $error) <p>{{ $error }}</p> @endforeach
            </div>
        @endif

        <section class="taller-card p-6 space-y-4">
            <div>
                <h2 class="text-lg font-bold">Registrar equipo y solicitar reparación</h2>
                <p class="text-xs" style="color: var(--taller-muted);">Agrega tu equipo y genera una orden con folio para consultar su estado.</p>
            </div>
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- 1. Registrar equipo --}}
            <form method="POST" action="{{ route('taller.computadoras.cliente.equipos.store') }}" class="taller-card p-6 space-y-4">
                @csrf
                <input type="hidden" name="solo_registrar_equipo" value="1">
                <div>
                    <h2 class="text-lg font-bold">Registrar equipo</h2>
                    <p class="text-xs" style="color: var(--taller-muted);">Guarda tu equipo en tu perfil para solicitar servicios fácilmente.</p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs mb-1" style="color: var(--taller-muted);">Tipo de equipo</label>
                        <select name="tipo_equipo" required class="w-full px-3.5 py-2 taller-input text-sm">
                            <option value="Laptop" {{ old('tipo_equipo') === 'Laptop' ? 'selected' : '' }}>Laptop</option>
                            <option value="PC Escritorio" {{ old('tipo_equipo') === 'PC Escritorio' ? 'selected' : '' }}>PC Escritorio</option>
                            <option value="All-in-One" {{ old('tipo_equipo') === 'All-in-One' ? 'selected' : '' }}>All-in-One</option>
                            <option value="MacBook / iMac" {{ old('tipo_equipo') === 'MacBook / iMac' ? 'selected' : '' }}>MacBook / iMac</option>
                            <option value="Otro" {{ old('tipo_equipo') === 'Otro' ? 'selected' : '' }}>Otro</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs mb-1" style="color: var(--taller-muted);">Marca</label>
                        <input type="text" name="marca" value="{{ old('marca') }}" placeholder="Ej. Dell, Lenovo, HP" required class="w-full px-3.5 py-2 taller-input text-sm">
                    </div>
                    <div>
                        <label class="block text-xs mb-1" style="color: var(--taller-muted);">Modelo</label>
                        <input type="text" name="modelo" value="{{ old('modelo') }}" placeholder="Ej. Inspiron 15" required class="w-full px-3.5 py-2 taller-input text-sm">
                    </div>
                    <div>
                        <label class="block text-xs mb-1" style="color: var(--taller-muted);">Número de serie (opcional)</label>
                        <input type="text" name="numero_serie" value="{{ old('numero_serie') }}" placeholder="Ej. SN-882910" class="w-full px-3.5 py-2 taller-input text-sm">
                    </div>
                </div>
                <button type="submit" class="px-5 py-2.5 text-xs font-semibold taller-input hover:opacity-95" style="border-color: var(--taller-primary);">
                    Registrar equipo
                </button>
            </form>

            {{-- 2. Solicitar reparación con menú desplegable de servicios --}}
                        @php
                $serviciosDisponibles = $config['servicios'] ?? [];
                $serviciosPorCatPortal = [];
                foreach ($serviciosDisponibles as $srvItem) {
                    if (! is_array($srvItem)) {
                        continue;
                    }
                    $nombreSrv = trim((string) ($srvItem['titulo'] ?? $srvItem['nombre'] ?? 'Servicio'));
                    if ($nombreSrv === '') {
                        continue;
                    }
                    $descSrv = trim((string) ($srvItem['descripcion'] ?? ''));
                    $catPortal = trim((string) ($srvItem['categoria'] ?? ''));
                    if ($catPortal === '') {
                        $textoSrv = mb_strtolower($nombreSrv . ' ' . $descSrv);
                        if (preg_match('/pantalla|bisagra|teclado|touchpad|bater[ií]a|centro de carga|parte|pieza/u', $textoSrv)) {
                            $catPortal = 'Cambio de partes';
                        } elseif (preg_match('/venta|accesorio|cat[aá]logo|equipo nuevo|perif[eé]rico/u', $textoSrv)) {
                            $catPortal = 'Venta de equipos y accesorios';
                        } elseif (preg_match('/ssd|ram|componente|ensamblaje|gamer|almacenamiento|velocidad|actualizaci[oó]n/u', $textoSrv)) {
                            $catPortal = 'Cambio / actualización de componentes';
                        } elseif (preg_match('/limpieza|formateo|sistema|driver|respaldo|recuperaci[oó]n|virus|malware/u', $textoSrv)) {
                            $catPortal = 'Limpieza, formateo y sistema';
                        } else {
                            $catPortal = 'Diagnóstico y soporte general';
                        }
                    }
                    $serviciosPorCatPortal[$catPortal][] = $srvItem;
                }
            @endphp
            <form method="POST" action="{{ route('taller.computadoras.cliente.equipos.store') }}" class="taller-card p-6 space-y-4">
                @csrf
                <div>
                    <h2 class="text-lg font-bold">Solicitar reparación</h2>
                    <p class="text-xs" style="color: var(--taller-muted);">Elige el servicio que requieres y genera una orden con folio.</p>
                </div>

                <div>
                    <label class="block text-xs mb-1" style="color: var(--taller-muted);">Servicio requerido</label>
                    <select name="servicio_seleccionado" id="servicioSeleccionadoPortal" class="w-full px-3.5 py-2 taller-input text-sm">
                        <option value="">-- Selecciona un servicio del catálogo --</option>
                        @foreach($serviciosPorCatPortal as $nombreCatPortal => $itemsCatPortal)
                            <optgroup label="{{ $nombreCatPortal }}">
                                @foreach($itemsCatPortal as $srv)
                                    @php
                                        $nombreSrv = $srv['titulo'] ?? $srv['nombre'] ?? 'Servicio';
                                        $precioSrv = $srv['precio'] ?? '';
                                    @endphp
                                    <option value="{{ $nombreSrv }}" {{ old('servicio_seleccionado') === $nombreSrv ? 'selected' : '' }}>
                                        {{ $nombreSrv }}{{ $precioSrv !== '' ? ' — $' . $precioSrv : '' }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @endforeach
                        <optgroup label="Otro">
                            <option value="Otro servicio / Diagnóstico" {{ old('servicio_seleccionado') === 'Otro servicio / Diagnóstico' ? 'selected' : '' }}>Otro servicio / Diagnóstico</option>
                        </optgroup>
                    </select>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs mb-1" style="color: var(--taller-muted);">Tipo de equipo</label>
                        <select name="tipo_equipo" required class="w-full px-3.5 py-2 taller-input text-sm">
                            <option value="Laptop">Laptop</option>
                            <option value="PC Escritorio">PC Escritorio</option>
                            <option value="All-in-One">All-in-One</option>
                            <option value="MacBook / iMac">MacBook / iMac</option>
                            <option value="Otro">Otro</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs mb-1" style="color: var(--taller-muted);">Marca</label>
                        <input type="text" name="marca" value="{{ old('marca') }}" placeholder="Ej. HP, Asus" required class="w-full px-3.5 py-2 taller-input text-sm">
                    </div>
                    <div>
                        <label class="block text-xs mb-1" style="color: var(--taller-muted);">Modelo</label>
                        <input type="text" name="modelo" value="{{ old('modelo') }}" placeholder="Ej. Pavilion 14" required class="w-full px-3.5 py-2 taller-input text-sm">
                    </div>
                </div>

                <div>
                    <label class="block text-xs mb-1" style="color: var(--taller-muted);">Detalles o falla observada</label>
                    <input type="text" name="falla_reportada" id="fallaReportadaPortal" value="{{ old('falla_reportada') }}" placeholder="Ej. No enciende, va lento, detalles adicionales" required class="w-full px-3.5 py-2 taller-input text-sm">
                </div>

                <button type="submit" class="px-5 py-2.5 text-xs font-semibold taller-btn hover:opacity-95">
                    Solicitar reparación y generar orden
                </button>
            </form>
        </div>

        <script>
            (function () {
                const selSrv = document.getElementById('servicioSeleccionadoPortal');
                const inpFalla = document.getElementById('fallaReportadaPortal');
                if (!selSrv || !inpFalla) return;
                selSrv.addEventListener('change', function () {
                    const elegido = selSrv.value.trim();
                    if (!elegido) return;
                    if (inpFalla.value.trim() === '') {
                        inpFalla.value = elegido;
                    } else if (!inpFalla.value.toLowerCase().includes(elegido.toLowerCase())) {
                        inpFalla.value = elegido + ' - ' + inpFalla.value.trim();
                    }
                });
            })();
        </script>
        </section>

        <section class="space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h2 class="text-lg font-bold">Mis equipos en reparación ({{ count($ordenes) }})</h2>
                    <p class="text-xs" style="color: var(--taller-muted);">Estado actual e historial de cada equipo.</p>
                </div>
                <form method="GET" action="{{ route('taller.computadoras.cliente.portal') }}" class="flex gap-2">
                    <input type="text" name="folio" value="{{ $folioBuscado }}" placeholder="Buscar folio (ej. ORD-2026-1001)" class="px-3 py-1.5 taller-input text-xs">
                    <button type="submit" class="px-3 py-1.5 text-xs font-semibold taller-btn">Buscar</button>
                </form>
            </div>

            @if($folioBuscado !== '')
                <div class="taller-card p-4 text-xs space-y-1">
                    @if($ordenFolio)
                        <p class="font-semibold" style="color: var(--taller-accent);">
                            Folio {{ $ordenFolio['folio'] }} · {{ $ordenFolio['equipo'] }} · Estado: {{ $ordenFolio['estado'] }}
                        </p>
                        <p style="color: var(--taller-muted);">{{ $ordenFolio['diagnostico'] ?? 'En revisión' }}</p>
                    @else
                        <p class="text-amber-300">No se encontró orden con el folio "{{ $folioBuscado }}".</p>
                    @endif
                </div>
            @endif

            @if(count($ordenes) > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($ordenes as $ord)
                        @php
                            $estadoActual = $ord['estado'] ?? 'Recepción';
                            $badge = match ($estadoActual) {
                                'Listo para entrega', 'Entregado' => 'border-emerald-500/60 bg-emerald-950/30 text-emerald-300',
                                'En espera de autorización' => 'border-amber-500/60 bg-amber-950/30 text-amber-300',
                                default => 'border-indigo-500/60 bg-indigo-950/30 text-indigo-300',
                            };
                        @endphp
                        <article class="taller-card p-5 space-y-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-xs font-mono" style="color: var(--taller-muted);">{{ $ord['folio'] }}</p>
                                    <h3 class="text-base font-bold">{{ $ord['equipo'] }}</h3>
                                    @if(!empty($ord['serie']))
                                        <p class="text-[11px]" style="color: var(--taller-muted);">Serie: {{ $ord['serie'] }}</p>
                                    @endif
                                </div>
                                <span class="px-2.5 py-1 text-[11px] font-semibold rounded border {{ $badge }}">{{ $estadoActual }}</span>
                            </div>

                            <div class="text-xs space-y-1">
                                <p><span style="color: var(--taller-muted);">Falla:</span> {{ $ord['falla'] }}</p>
                                <p><span style="color: var(--taller-muted);">Diagnóstico / avance:</span> {{ $ord['diagnostico'] ?? 'En revisión' }}</p>
                                @if(isset($ord['costo_estimado']) && $ord['costo_estimado'] !== null)
                                    <p><span style="color: var(--taller-muted);">Costo estimado:</span> ${{ number_format((float) $ord['costo_estimado'], 2) }}</p>
                                @endif
                            </div>

                            @if($estadoActual === 'En espera de autorización')
                                <form method="POST" action="{{ route('taller.computadoras.cliente.ordenes.autorizar', ['ordenId' => $ord['id']]) }}">
                                    @csrf
                                    <button type="submit" class="w-full py-2 px-3 text-xs font-semibold rounded bg-emerald-600 text-white hover:bg-emerald-500 transition">
                                        Autorizar reparación
                                    </button>
                                </form>
                            @endif

                            @if(!empty($ord['historial']))
                                <div class="pt-3 border-t space-y-2" style="border-color: var(--taller-border);">
                                    <p class="text-[11px] font-semibold uppercase tracking-wider" style="color: var(--taller-muted);">Historial de estados</p>
                                    <ul class="space-y-1.5 text-xs">
                                        @foreach(array_reverse($ord['historial']) as $paso)
                                            <li class="p-2 taller-input">
                                                <div class="flex items-center justify-between font-semibold">
                                                    <span>{{ $paso['estado'] }}</span>
                                                    <span class="text-[10px]" style="color: var(--taller-muted);">{{ $paso['fecha'] ?? '' }}</span>
                                                </div>
                                                @if(!empty($paso['nota']))
                                                    <p class="text-[11px] mt-0.5" style="color: var(--taller-muted);">{{ $paso['nota'] }}</p>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </article>
                    @endforeach
                </div>
            @else
                <div class="taller-card p-6 text-xs" style="color: var(--taller-muted);">Aún no tienes órdenes registradas.</div>
            @endif
        </section>

        <section class="taller-card p-6 space-y-3">
            <h2 class="text-base font-bold">Mis equipos registrados ({{ count($equipos) }})</h2>
            @if(count($equipos) > 0)
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3 text-xs">
                    @foreach($equipos as $eq)
                        <div class="p-3 taller-input space-y-1">
                            <p class="font-semibold">{{ $eq['nombre_completo'] }}</p>
                            <p style="color: var(--taller-muted);">Tipo: {{ $eq['tipo'] }}</p>
                            @if(!empty($eq['serie']))
                                <p style="color: var(--taller-muted);">Serie: {{ $eq['serie'] }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-xs" style="color: var(--taller-muted);">Todavía no has registrado equipos.</p>
            @endif
        </section>
    </main>
    <script>
        (function () {
            if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
            if ('ontouchstart' in window || navigator.maxTouchPoints > 0) return;
            let current = window.scrollY;
            let desired = current;
            let rafId = null;
            const ease = 0.065;

            function step() {
                const actual = window.scrollY;
                if (Math.abs(actual - current) > 3) { current = actual; desired = actual; }
                const maxScroll = Math.max(0, document.documentElement.scrollHeight - window.innerHeight);
                desired = Math.max(0, Math.min(maxScroll, desired));
                current += (desired - current) * ease;
                if (Math.abs(desired - current) < 0.35) {
                    window.scrollTo(0, Math.round(desired));
                    rafId = null;
                    return;
                }
                window.scrollTo(0, current);
                rafId = window.requestAnimationFrame(step);
            }

            document.addEventListener('wheel', function (e) {
                if (e.ctrlKey || e.metaKey || e.altKey) return;
                if (e.target && e.target.closest('input[type="range"], textarea, select')) return;
                const maxScroll = Math.max(0, document.documentElement.scrollHeight - window.innerHeight);
                if (maxScroll <= 2) return;
                e.preventDefault();
                if (rafId === null) { current = window.scrollY; desired = current; }
                desired = Math.max(0, Math.min(maxScroll, desired + (e.deltaY * 0.85)));
                if (rafId === null) rafId = window.requestAnimationFrame(step);
            }, { passive: false });
        })();
    </script>
</body>
</html>
