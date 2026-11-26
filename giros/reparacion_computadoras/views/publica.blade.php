<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $empresa->nombre ?? 'Taller de Reparación de Computadoras' }}</title>
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

            $bgHex = trim((string) ($config['color_fondo'] ?? ($template->color_fondo_pagina ?? '#09090b')));
            if ($bgHex === '') { $bgHex = '#09090b'; }

            $cardHex = trim((string) ($config['color_tarjeta'] ?? ($template->fondo_tarjeta ?? '#111115')));
            if ($cardHex === '') { $cardHex = '#111115'; }

            $primHex = trim((string) ($config['color_primario'] ?? ($template->color_primario ?? '#6366f1')));
            if ($primHex === '') { $primHex = '#6366f1'; }

            $accHex = trim((string) ($config['color_acento'] ?? ($template->color_acento ?? '#10b981')));
            if ($accHex === '') { $accHex = '#10b981'; }

            $bgRgb = $parseHexRgb($bgHex, '9, 9, 11');
            $cardRgb = $parseHexRgb($cardHex, '17, 17, 21');
            $primRgb = $parseHexRgb($primHex, '99, 102, 241');
            $accRgb = $parseHexRgb($accHex, '16, 185, 129');

            $cardRgba = "rgba({$cardRgb}, {$alphaFloat})";
            $surfRgba = "rgba({$cardRgb}, " . max(0.25, round($alphaFloat * 0.85, 2)) . ")";

            $fondoImg = trim((string) ($config['fondo_imagen'] ?? ''));
            $degTipo = (string) ($config['fondo_degradado'] ?? 'oscuro_suave');
            $fondoEstatico = (bool) ($config['fondo_estatico'] ?? true);

            $capas = [];
            if ($degTipo === 'oscuro_suave') {
                $capas[] = "linear-gradient(180deg, rgba({$bgRgb}, 0.55) 0%, rgba({$bgRgb}, 0.78) 100%)";
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
            --taller-surface-rgba: {{ $surfRgba }};
            --taller-text: {{ $template->color_texto_principal ?? '#f4f4f5' }};
            --taller-muted: {{ $template->color_texto_secundario ?? '#a1a1aa' }};
            --taller-border: {{ $template->color_borde ?? '#27272a' }};
            --taller-radius: {{ $template->radio_bordes ?? '0.75rem' }};
        }

        html {
            background-color: #09090b;
            color-scheme: dark;
        }

        body {
            font-family: var(--taller-font);
            background-color: var(--taller-bg);
            color: var(--taller-text);
            min-height: 100vh;
            @if($bgImageCss !== '')
                background-image: {!! $bgImageCss !!};
                background-size: cover;
                background-position: center center;
                background-repeat: no-repeat;
                background-attachment: {{ $fondoEstatico ? 'fixed' : 'scroll' }};
            @endif
        }

        @if($fondoEstatico && $bgImageCss !== '')
        @media (hover: none) and (pointer: coarse) {
            body { background-attachment: scroll; }
        }
        @endif

        .taller-header {
            background-color: var(--taller-card-rgba);
            backdrop-filter: blur(8px);
        }
        .taller-card {
            background-color: var(--taller-card-rgba);
            backdrop-filter: blur(8px);
            border: 1px solid var(--taller-border);
            border-radius: var(--taller-radius);
        }
        .taller-input {
            background-color: var(--taller-surface-rgba);
            backdrop-filter: blur(6px);
            border: 1px solid var(--taller-border);
            border-radius: var(--taller-radius);
        }
        .taller-btn {
            background-color: var(--taller-primary);
            color: #ffffff;
            border-radius: var(--taller-radius);
        }
            html {
            scroll-behavior: smooth;
        }
        main, section, .taller-card, .taller-input {
            scroll-behavior: smooth;
        }
    </style>
</head>
<body class="min-h-screen antialiased">
    <header class="taller-header border-b" style="border-color: var(--taller-border);">
        <div class="max-w-6xl mx-auto px-4 py-4 flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="h-11 w-11 flex items-center justify-center rounded-lg text-xl font-bold" style="background-color: var(--taller-primary); color: #fff;">
                    {{ $config['icono_predeterminado'] ?? ($template->icono_predeterminado ?? '💻') }}
                </div>
                <div>
                    <p class="text-[10px] font-semibold uppercase tracking-wider" style="color: var(--taller-accent);">REPARACIÓN DE COMPUTADORAS</p>
                    <h1 class="text-lg font-bold leading-tight">{{ $empresa->nombre ?? 'Taller de Cómputo' }}</h1>
                </div>
            </div>
<div class="flex items-center gap-2 shrink-0">
                    <a href="{{ route('taller.computadoras.login') }}" class="px-4 py-2 text-xs font-semibold taller-btn hover:opacity-95">
                Acceso personal
            </a>
                    <a href="{{ route('taller.computadoras.cliente.login') }}" class="px-3.5 py-2 text-xs font-semibold taller-input hover:opacity-90">Iniciar sesión / Registrarse</a>
                </div>
        </div>
    </header>

    <main class="max-w-6xl mx-auto px-4 py-10 space-y-10">
        <section class="taller-card p-7 md:p-9 space-y-3">
            <span class="text-xs font-semibold uppercase tracking-wider" style="color: var(--taller-accent);">
                {{ $config['hero_badge'] ?? 'Servicio técnico especializado' }}
            </span>
            <h2 class="text-2xl md:text-3xl font-bold">
                {{ $config['hero_titulo'] ?? 'Diagnóstico, reparación y mantenimiento de laptops y PCs' }}
            </h2>
            <p class="text-sm max-w-3xl" style="color: var(--taller-muted);">
                {{ ($config['descripcion'] ?? '') !== '' ? $config['descripcion'] : ($empresa->descripcion ?? 'Atención rápida y transparente para equipos de escritorio, portátiles, cambio de componentes, respaldo de información y optimización del sistema.') }}
            </p>
            @if(!empty($config['horario']) || !empty($config['telefono']) || !empty($config['direccion']))
                <div class="pt-3 flex flex-wrap gap-4 text-xs" style="color: var(--taller-muted);">
                    @if(!empty($config['horario']))<span>🕒 {{ $config['horario'] }}</span>@endif
                    @if(!empty($config['telefono']))<span>📞 {{ $config['telefono'] }}</span>@endif
                    @if(!empty($config['direccion']))<span>📍 {{ $config['direccion'] }}</span>@endif
                </div>
            @endif
        </section>

                        {{-- Carrusel de imágenes --}}
        <section id="carruselTaller" class="taller-card p-5 md:p-6 space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold">Galería del taller</h3>
            </div>

            @php $slides = array_values(array_filter($config['imagenes'] ?? [], fn($s) => is_array($s) && !empty($s['url']))); @endphp
            @if(count($slides) > 0)
                <div class="relative overflow-hidden rounded-lg" style="border: 1px solid var(--taller-border); background-color: var(--taller-surface);">
                    <div id="carruselTrack" class="flex w-full transition-transform duration-500 ease-out" style="transform: translateX(0%);">
                        @foreach($slides as $slide)
                            <div class="w-full min-w-full max-w-full shrink-0">
                                <img src="{{ $slide['url'] }}" alt="{{ $slide['titulo'] ?? 'Banner del taller' }}" class="block w-full h-64 md:h-96 object-cover">
                            </div>
                        @endforeach
                    </div>
                </div>
                @if(count($slides) > 1)
                    <div class="flex justify-center gap-2 pt-1" id="carruselDots">
                        @foreach($slides as $idx => $slide)
                            <button type="button" aria-label="Banner {{ $idx + 1 }}" data-slide="{{ $idx }}" class="h-2.5 w-2.5 rounded-full transition-opacity {{ $idx === 0 ? 'opacity-100' : 'opacity-40' }}" style="background-color: var(--taller-primary);"></button>
                        @endforeach
                    </div>
                @endif
            @else
                <p class="text-xs" style="color: var(--taller-muted);">
                    Aún no hay imágenes en el carrusel. Puedes subirlas desde la sección de Autogestión.
                </p>
            @endif
        </section>
        {{-- Servicios desplegables por categoría --}}
@php
    $servicios = $config['servicios'] ?? [];
    $serviciosAgrupados = [];
    foreach ($servicios as $srvItem) {
        if (! is_array($srvItem)) {
            continue;
        }
        $cat = trim((string) ($srvItem['categoria'] ?? ''));
        if ($cat === '') {
            $texto = mb_strtolower((string) ($srvItem['titulo'] ?? $srvItem['nombre'] ?? '') . ' ' . ($srvItem['descripcion'] ?? ''));
            if (preg_match('/pantalla|bisagra|teclado|touchpad|bater[ií]a|centro de carga|parte|pieza/u', $texto)) {
                $cat = 'Cambio de partes';
            } elseif (preg_match('/venta|accesorio|cat[aá]logo|equipo nuevo|perif[eé]rico/u', $texto)) {
                $cat = 'Venta de equipos y accesorios';
            } elseif (preg_match('/ssd|ram|componente|ensamblaje|gamer|almacenamiento|velocidad|actualizaci[oó]n/u', $texto)) {
                $cat = 'Cambio / actualización de componentes';
            } elseif (preg_match('/limpieza|formateo|sistema|driver|respaldo|recuperaci[oó]n|virus|malware/u', $texto)) {
                $cat = 'Limpieza, formateo y sistema';
            } else {
                $cat = 'Diagnóstico y soporte general';
            }
        }
        $serviciosAgrupados[$cat][] = $srvItem;
    }
@endphp
@if(count($serviciosAgrupados) > 0)
    <section class="space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h3 class="text-lg font-semibold">Servicios</h3>
            <span class="text-xs" style="color: var(--taller-muted);">Haz clic en una categoría para desplegar sus servicios</span>
        </div>
        <div class="space-y-2.5" id="acordeonServiciosTaller">
            @foreach($serviciosAgrupados as $nombreCat => $itemsCat)
                <div class="taller-card overflow-hidden">
                    <button type="button"
                            data-acordeon-toggle
                            aria-expanded="false"
                            class="w-full px-5 py-3.5 flex items-center justify-between gap-3 text-left transition hover:opacity-95">
                        <div class="flex items-center gap-2.5">
                            <span class="text-xs font-bold uppercase tracking-wider" style="color: var(--taller-accent);">{{ $nombreCat }}</span>
                            <span class="px-2.5 py-0.5 text-[11px] rounded-full taller-input" style="color: var(--taller-muted);">
                                {{ count($itemsCat) }} {{ count($itemsCat) === 1 ? 'servicio' : 'servicios' }}
                            </span>
                        </div>
                        <span data-acordeon-icon class="text-sm transition-transform duration-200" style="color: var(--taller-muted);">▾</span>
                    </button>
                    <div data-acordeon-panel class="hidden px-5 pb-5 pt-3 border-t" style="border-color: var(--taller-border);">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            @foreach($itemsCat as $srv)
                                <div class="p-4 taller-input space-y-1.5">
                                    <div class="flex items-start justify-between gap-2">
                                        <h4 class="font-semibold text-sm">{{ $srv['titulo'] ?? $srv['nombre'] ?? 'Servicio' }}</h4>
                                        @if(!empty($srv['precio']))
                                            <span class="text-xs font-bold shrink-0" style="color: var(--taller-accent);">${{ $srv['precio'] }}</span>
                                        @endif
                                    </div>
                                    <p class="text-xs" style="color: var(--taller-muted);">{{ $srv['descripcion'] ?? '' }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </section>
    <script>
        (function () {
            const botones = document.querySelectorAll('#acordeonServiciosTaller [data-acordeon-toggle]');
            botones.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const card = btn.closest('.taller-card');
                    const panel = card ? card.querySelector('[data-acordeon-panel]') : null;
                    const icon = btn.querySelector('[data-acordeon-icon]');
                    if (!panel) return;
                    const estaAbierto = !panel.classList.contains('hidden');
                    panel.classList.toggle('hidden', estaAbierto);
                    btn.setAttribute('aria-expanded', estaAbierto ? 'false' : 'true');
                    if (icon) {
                        icon.style.transform = estaAbierto ? 'rotate(0deg)' : 'rotate(180deg)';
                    }
                });
            });
        })();
    </script>
@endif
{{-- Promociones --}}
        @php $promos = $config['promociones'] ?? []; @endphp
        @if(count($promos) > 0)
            <section class="space-y-4">
                <h3 class="text-lg font-semibold">Promociones</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @foreach($promos as $pr)
                        <div class="taller-card p-5 space-y-2">
                            <span class="inline-block px-2 py-0.5 text-[10px] font-bold uppercase rounded" style="background-color: var(--taller-accent); color: #09090b;">
                                {{ $pr['etiqueta'] ?? 'PROMO' }}
                            </span>
                            <h4 class="font-semibold text-base">{{ $pr['titulo'] }}</h4>
                            <p class="text-xs" style="color: var(--taller-muted);">{{ $pr['descripcion'] }}</p>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Consulta por folio --}}
        <section class="taller-card p-6 space-y-4">
            <div>
                <h3 class="text-base font-semibold">Consulta el estado de tu orden</h3>
                <p class="text-xs" style="color: var(--taller-muted);">Ingresa el folio de servicio que te entregaron en recepción.</p>
            </div>
            <form method="GET" action="{{ url()->current() }}" class="flex flex-col sm:flex-row gap-3 max-w-lg">
                <input type="text" name="folio" value="{{ $folioBuscado }}" placeholder="Ej. ORD-0001" class="flex-1 px-3.5 py-2 taller-input text-sm">
                <button type="submit" class="px-5 py-2 text-sm font-semibold taller-btn hover:opacity-95">
                    Consultar
                </button>
            </form>

            @if($folioBuscado !== '')
                @if($ordenConsultada)
                    <div class="p-4 rounded-lg text-xs space-y-1" style="background-color: var(--taller-surface); border: 1px solid var(--taller-border);">
                        <p><span style="color: var(--taller-muted);">Folio:</span> <strong class="font-mono">{{ $ordenConsultada->folio ?? $folioBuscado }}</strong></p>
                        <p><span style="color: var(--taller-muted);">Estado actual:</span> <strong style="color: var(--taller-accent);">{{ strtoupper($ordenConsultada->estado ?? 'recibida') }}</strong></p>
                        @if(!empty($ordenConsultada->diagnostico))
                            <p><span style="color: var(--taller-muted);">Diagnóstico:</span> {{ $ordenConsultada->diagnostico }}</p>
                        @endif
                    </div>
                @else
                    <p class="text-xs" style="color: var(--taller-muted);">No se encontró ninguna orden con el folio "{{ $folioBuscado }}".</p>
                @endif
            @endif
        </section>
    </main>

        <script>
        (function () {
            const container = document.getElementById('carruselTaller');
            const track = document.getElementById('carruselTrack');
            if (!container || !track) return;
            const total = track.children.length;
            if (total <= 1) return;

            let idx = 0;
            let timer = null;
            const dots = Array.from(document.querySelectorAll('#carruselDots button'));

            function goTo(i) {
                idx = ((i % total) + total) % total;
                track.style.transform = `translateX(-${idx * 100}%)`;
                dots.forEach((d, k) => {
                    d.style.opacity = k === idx ? '1' : '0.4';
                });
            }

            function startAuto() {
                stopAuto();
                timer = window.setInterval(() => goTo(idx + 1), {{ (int) ($config['carrusel_intervalo_ms'] ?? 4500) }});
            }

            function stopAuto() {
                if (timer !== null) {
                    window.clearInterval(timer);
                    timer = null;
                }
            }

            dots.forEach((d) => {
                d.addEventListener('click', () => {
                    goTo(Number(d.dataset.slide || 0));
                    startAuto();
                });
            });

            container.addEventListener('mouseenter', stopAuto);
            container.addEventListener('mouseleave', startAuto);
            startAuto();
        })();
    </script>
        <script>
        (function () {
            if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                return;
            }
            if ('ontouchstart' in window || navigator.maxTouchPoints > 0) {
                return;
            }

            function getScrollContainer() {
                const main = document.querySelector('main');
                if (main && window.innerWidth >= 768) {
                    const style = window.getComputedStyle(main);
                    const canScrollMain = (style.overflowY === 'auto' || style.overflowY === 'scroll')
                        && main.scrollHeight > main.clientHeight + 2;
                    if (canScrollMain) {
                        return { el: main, isWindow: false };
                    }
                }
                return { el: window, isWindow: true };
            }

            let current = 0;
            let desired = 0;
            let rafId = null;
            let activeTarget = null;
            const ease = 0.065;
            const deltaFactor = 0.85;

            function getScrollPos(ctx) {
                return ctx.isWindow ? window.scrollY : ctx.el.scrollTop;
            }

            function setScrollPos(ctx, val) {
                if (ctx.isWindow) {
                    window.scrollTo(0, val);
                } else {
                    ctx.el.scrollTop = val;
                }
            }

            function getMaxScroll(ctx) {
                if (ctx.isWindow) {
                    return Math.max(0, document.documentElement.scrollHeight - window.innerHeight);
                }
                return Math.max(0, ctx.el.scrollHeight - ctx.el.clientHeight);
            }

            function step() {
                if (!activeTarget) {
                    rafId = null;
                    return;
                }

                const actual = getScrollPos(activeTarget);
                if (Math.abs(actual - current) > 3) {
                    current = actual;
                    desired = actual;
                }

                const maxScroll = getMaxScroll(activeTarget);
                desired = Math.max(0, Math.min(maxScroll, desired));
                current += (desired - current) * ease;

                if (Math.abs(desired - current) < 0.35) {
                    current = desired;
                    setScrollPos(activeTarget, Math.round(current));
                    rafId = null;
                    return;
                }

                setScrollPos(activeTarget, current);
                rafId = window.requestAnimationFrame(step);
            }

            document.addEventListener('wheel', function (e) {
                if (e.ctrlKey || e.metaKey || e.altKey) return;
                if (e.target && e.target.closest('input[type="range"], textarea, select, [data-no-smooth-scroll]')) return;

                const ctx = getScrollContainer();
                const maxScroll = getMaxScroll(ctx);
                if (maxScroll <= 2) return;

                let deltaY = e.deltaY;
                if (e.deltaMode === 1) deltaY *= 32;
                else if (e.deltaMode === 2) deltaY *= window.innerHeight;

                e.preventDefault();

                if (!activeTarget || activeTarget.el !== ctx.el || rafId === null) {
                    activeTarget = ctx;
                    current = getScrollPos(ctx);
                    desired = current;
                }

                desired = Math.max(0, Math.min(maxScroll, desired + (deltaY * deltaFactor)));

                if (rafId === null) {
                    rafId = window.requestAnimationFrame(step);
                }
            }, { passive: false });
        })();
    </script>
</body>
</html>
