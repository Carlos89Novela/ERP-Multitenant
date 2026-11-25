<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $empresa->nombre ?? 'Taller de Reparación de Computadoras' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --taller-font: {{ $config['fuente_familia'] ?? 'Inter, system-ui, -apple-system, sans-serif' }};
            --taller-primary: {{ $config['color_primario'] ?? ($template->color_primario ?? '#6366f1') }};
            --taller-accent: {{ $config['color_acento'] ?? ($template->color_acento ?? '#10b981') }};
            --taller-bg: {{ $config['color_fondo'] ?? ($template->color_fondo_pagina ?? '#09090b') }};
            --taller-card: {{ $config['color_tarjeta'] ?? ($template->fondo_tarjeta ?? '#111115') }};
            --taller-surface: {{ $template->color_fondo_superficie ?? '#18181b' }};
            --taller-text: {{ $template->color_texto_principal ?? '#f4f4f5' }};
            --taller-muted: {{ $template->color_texto_secundario ?? '#a1a1aa' }};
            --taller-border: {{ $template->color_borde ?? '#27272a' }};
            --taller-radius: {{ $template->radio_bordes ?? '0.75rem' }};
        }
        body { font-family: var(--taller-font); background-color: var(--taller-bg); color: var(--taller-text); }
        .taller-card { background-color: var(--taller-card); border: 1px solid var(--taller-border); border-radius: var(--taller-radius); }
        .taller-input { background-color: var(--taller-surface); border: 1px solid var(--taller-border); border-radius: var(--taller-radius); }
        .taller-btn { background-color: var(--taller-primary); color: #ffffff; border-radius: var(--taller-radius); }
    </style>
</head>
<body class="min-h-screen antialiased">
    <header class="border-b" style="border-color: var(--taller-border); background-color: var(--taller-card);">
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
            <a href="{{ route('taller.computadoras.login') }}" class="px-4 py-2 text-xs font-semibold taller-btn hover:opacity-95">
                Acceso personal
            </a>
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
        <section class="taller-card p-5 md:p-6 space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold">Galería del taller</h3>
                @if(count($config['imagenes'] ?? []) > 1)
                    <div class="flex items-center gap-2">
                        <button type="button" id="carruselPrev" class="px-3 py-1.5 text-xs taller-input hover:opacity-90">Anterior</button>
                        <button type="button" id="carruselNext" class="px-3 py-1.5 text-xs taller-input hover:opacity-90">Siguiente</button>
                    </div>
                @endif
            </div>

            @php $slides = $config['imagenes'] ?? []; @endphp
            @if(count($slides) > 0)
                <div class="relative overflow-hidden rounded-lg" style="border: 1px solid var(--taller-border); background-color: var(--taller-surface);">
                    <div id="carruselTrack" class="flex transition-transform duration-300">
                        @foreach($slides as $slide)
                            <div class="min-w-full">
                                <img src="{{ $slide['url'] }}" alt="{{ $slide['titulo'] ?? 'Imagen del taller' }}" class="w-full h-64 md:h-96 object-cover">
                                @if(!empty($slide['titulo']) || !empty($slide['subtitulo']))
                                    <div class="p-4 border-t" style="border-color: var(--taller-border);">
                                        @if(!empty($slide['titulo']))<p class="font-semibold text-sm">{{ $slide['titulo'] }}</p>@endif
                                        @if(!empty($slide['subtitulo']))<p class="text-xs mt-0.5" style="color: var(--taller-muted);">{{ $slide['subtitulo'] }}</p>@endif
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
                @if(count($slides) > 1)
                    <div class="flex justify-center gap-2 pt-1" id="carruselDots">
                        @foreach($slides as $idx => $slide)
                            <button type="button" data-slide="{{ $idx }}" class="h-2.5 w-2.5 rounded-full {{ $idx === 0 ? 'opacity-100' : 'opacity-40' }}" style="background-color: var(--taller-primary);"></button>
                        @endforeach
                    </div>
                @endif
            @else
                <p class="text-xs" style="color: var(--taller-muted);">
                    Aún no hay imágenes en el carrusel. Puedes subirlas desde la sección de Autogestión.
                </p>
            @endif
        </section>

        {{-- Servicios --}}
        @php $servicios = $config['servicios'] ?? []; @endphp
        @if(count($servicios) > 0)
            <section class="space-y-4">
                <h3 class="text-lg font-semibold">Servicios</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @foreach($servicios as $srv)
                        <div class="taller-card p-5 space-y-1.5">
                            <h4 class="font-semibold text-base">{{ $srv['titulo'] }}</h4>
                            <p class="text-xs" style="color: var(--taller-muted);">{{ $srv['descripcion'] }}</p>
                        </div>
                    @endforeach
                </div>
            </section>
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
            const track = document.getElementById('carruselTrack');
            if (!track) return;
            const total = track.children.length;
            if (total <= 1) return;
            let idx = 0;
            const dots = Array.from(document.querySelectorAll('#carruselDots button'));
            function goTo(i) {
                idx = (i + total) % total;
                track.style.transform = `translateX(-${idx * 100}%)`;
                dots.forEach((d, k) => {
                    d.style.opacity = k === idx ? '1' : '0.4';
                });
            }
            document.getElementById('carruselPrev')?.addEventListener('click', () => goTo(idx - 1));
            document.getElementById('carruselNext')?.addEventListener('click', () => goTo(idx + 1));
            dots.forEach((d) => d.addEventListener('click', () => goTo(Number(d.dataset.slide || 0))));
        })();
    </script>
</body>
</html>
