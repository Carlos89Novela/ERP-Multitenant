<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $configInternas['titulo_panel'] ?? 'Dashboard' }} — {{ $empresa->nombre ?? 'Taller' }}</title>
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
            html, body { color-scheme: dark; }
        ::-webkit-scrollbar { width: 10px; height: 10px; }
        ::-webkit-scrollbar-track { background: var(--taller-bg); }
        ::-webkit-scrollbar-thumb {
            background: var(--taller-border);
            border-radius: 9999px;
            border: 2px solid var(--taller-bg);
        }
        ::-webkit-scrollbar-thumb:hover {
            background: var(--taller-primary);
        }
        * { scrollbar-width: thin; scrollbar-color: var(--taller-border) var(--taller-bg); }
            html {
            scroll-behavior: smooth;
        }
        main, section, .taller-card, .taller-input {
            scroll-behavior: smooth;
        }
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
                <a href="{{ route('taller.computadoras.dashboard') }}" class="block px-3 py-2 rounded-lg font-medium" style="background-color: var(--taller-primary); color: #fff;">
                    Dashboard
                </a>
                                @if(in_array(mb_strtolower((string) ($usuario->rol_empresa ?? '')), ['propietario', 'gerente', 'subgerente', 'admin', 'administrador'], true))
                    <a href="{{ route('taller.computadoras.ordenes') }}" class="block px-3 py-2 rounded-lg hover:opacity-90" style="background-color: var(--taller-surface);">
                        Órdenes solicitadas
                    </a>
                @endif
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
                <h1 class="text-2xl font-bold">{{ $configInternas['titulo_panel'] ?? 'Panel de Control — Reparación de Computadoras' }}</h1>
                <p class="text-xs mt-1" style="color: var(--taller-muted);">
                    {{ ($configInternas['subtitulo_panel'] ?? '') !== '' ? $configInternas['subtitulo_panel'] : ('Sesión activa como ' . $usuario->email . ' (' . $usuario->rol_empresa . ')') }}
                </p>
            </div>
        </header>

        @if(($configInternas['mostrar_metricas'] ?? true))
            <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="taller-card p-5">
                    <p class="text-xs" style="color: var(--taller-muted);">Órdenes en proceso</p>
                    <p class="text-3xl font-bold mt-2">{{ $metricas['ordenes_abiertas'] }}</p>
                </div>
                <div class="taller-card p-5">
                    <p class="text-xs" style="color: var(--taller-muted);">Listas para entrega</p>
                    <p class="text-3xl font-bold mt-2" style="color: var(--taller-accent);">{{ $metricas['ordenes_listas'] }}</p>
                </div>
                <div class="taller-card p-5">
                    <p class="text-xs" style="color: var(--taller-muted);">Refacciones en stock bajo</p>
                    <p class="text-3xl font-bold mt-2">{{ $metricas['refacciones_bajas'] }}</p>
                </div>
                <div class="taller-card p-5">
                    <p class="text-xs" style="color: var(--taller-muted);">Equipo de trabajo activo</p>
                    <p class="text-3xl font-bold mt-2">{{ $metricas['equipo_activo'] }}</p>
                </div>
            </section>
        @endif

        @if(($configInternas['mostrar_ultimas_ordenes'] ?? true))
            <section class="taller-card p-5 space-y-4">
                <h2 class="text-base font-semibold">Últimas órdenes de servicio</h2>
                @if($ultimasOrdenes->isEmpty())
                    <p class="text-xs" style="color: var(--taller-muted);">
                        Aún no hay órdenes registradas en este taller.
                    </p>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead>
                                <tr class="border-b" style="border-color: var(--taller-border); color: var(--taller-muted);">
                                    <th class="py-2 pr-3">Folio</th>
                                    <th class="py-2 pr-3">Cliente</th>
                                    <th class="py-2 pr-3">Equipo</th>
                                    <th class="py-2 pr-3">Estado</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y" style="border-color: var(--taller-border);">
                                @php
    $badgeEstado = function (?string $est): array {
        $k = mb_strtolower(trim((string) $est));
        return match (true) {
            str_contains($k, 'recep') || str_contains($k, 'recibid') => ['bg' => 'rgba(59, 130, 246, 0.16)', 'border' => 'rgba(59, 130, 246, 0.45)', 'text' => '#93c5fd'],
            str_contains($k, 'diagn') => ['bg' => 'rgba(245, 158, 11, 0.16)', 'border' => 'rgba(245, 158, 11, 0.45)', 'text' => '#fcd34d'],
            str_contains($k, 'repar') || str_contains($k, 'proceso') => ['bg' => 'rgba(139, 92, 246, 0.16)', 'border' => 'rgba(139, 92, 246, 0.45)', 'text' => '#c4b5fd'],
            str_contains($k, 'listo') || str_contains($k, 'lista') => ['bg' => 'rgba(16, 185, 129, 0.16)', 'border' => 'rgba(16, 185, 129, 0.45)', 'text' => '#6ee7b7'],
            str_contains($k, 'entreg') => ['bg' => 'rgba(100, 116, 139, 0.18)', 'border' => 'rgba(148, 163, 184, 0.45)', 'text' => '#cbd5e1'],
            str_contains($k, 'cancel') => ['bg' => 'rgba(239, 68, 68, 0.16)', 'border' => 'rgba(239, 68, 68, 0.45)', 'text' => '#fca5a5'],
            default => ['bg' => 'rgba(99, 102, 241, 0.16)', 'border' => 'rgba(99, 102, 241, 0.45)', 'text' => '#a5b4fc'],
        };
    };
@endphp
                                @foreach($ultimasOrdenes as $orden)
                                    <tr>
                                        <td class="py-2.5 pr-3 font-mono">{{ $orden->folio ?? ('#' . $orden->id) }}</td>
                                        <td class="py-2.5 pr-3">{{ $orden->cliente_nombre ?? '—' }}</td>
                                        <td class="py-2.5 pr-3">{{ $orden->equipo_tipo ?? $orden->equipo ?? '—' }}</td>
                                        <td class="py-2.5 pr-3">@php $stDash = $badgeEstado($orden->estado ?? 'recibida'); @endphp<span class="inline-block text-[11px] font-semibold uppercase px-2 py-0.5 rounded border" style="background-color: {{ $stDash['bg'] }}; border-color: {{ $stDash['border'] }}; color: {{ $stDash['text'] }};">{{ $orden->estado ?? 'recibida' }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        @endif
    </main>
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
