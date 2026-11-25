<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $empresa->nombre }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --pub-primary: {{ $template->color_primario ?? '#f59e0b' }};
            --pub-secondary: {{ $template->color_secundario ?? '#d97706' }};
            --pub-accent: {{ $template->color_acento ?? '#10b981' }};
            --pub-bg: {{ $template->color_fondo_pagina ?? '#0b0b0c' }};
            --pub-surface: {{ $template->color_fondo_superficie ?? '#161618' }};
            --pub-text: {{ $template->color_texto_principal ?? '#f9fafb' }};
            --pub-muted: {{ $template->color_texto_secundario ?? '#a1a1aa' }};
            --pub-border: {{ $template->color_borde ?? '#27272a' }};
            --pub-radius: {{ $template->radio_bordes ?? '0.75rem' }};
        }
        body { background-color: var(--pub-bg); color: var(--pub-text); }
        .pub-surface { background-color: var(--pub-surface); border: 1px solid var(--pub-border); border-radius: var(--pub-radius); }
    </style>
</head>
<body class="min-h-screen antialiased">
    <header class="border-b sticky top-0 z-30" style="background-color: var(--pub-bg); border-color: var(--pub-border);">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                @if(!empty($template->logo_path))
                    <img src="{{ asset('storage/' . $template->logo_path) }}" alt="{{ $empresa->nombre }}" class="h-10 w-10 object-cover rounded">
                @else
                    <span class="h-10 w-10 flex items-center justify-center rounded text-lg font-bold" style="background-color: var(--pub-primary); color: #09090b;">
                        {{ $template->icono_predeterminado ?? '★' }}
                    </span>
                @endif
                <div>
                    <p class="font-bold text-base tracking-tight">{{ $empresa->nombre }}</p>
                    <p class="text-[11px] uppercase tracking-wider" style="color: var(--pub-muted);">{{ str_replace('_', ' ', $empresa->giro_comercial) }}</p>
                </div>
            </div>
            <div class="flex items-center gap-4 text-xs font-medium">
                <a href="#carrusel" class="hover:opacity-80">Destacados</a>
                <a href="#catalogo" class="hover:opacity-80">Oferta / Menú</a>
                <a href="{{ route('empresa.login', ['slug' => $empresa->slug]) }}" class="px-3 py-1.5 rounded font-semibold" style="background-color: var(--pub-primary); color: #09090b;">Acceso</a>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 py-8 space-y-12">
        {{-- Carrusel principal estilo banner --}}
        <section id="carrusel" class="relative overflow-hidden pub-surface min-h-[340px] sm:min-h-[440px] flex items-center">
            @if($slides->count() > 0)
                @foreach($slides as $idx => $s)
                    <div data-slide-index="{{ $idx }}" class="w-full grid grid-cols-1 lg:grid-cols-12 items-center gap-6 p-6 sm:p-10 {{ $idx === 0 ? '' : 'hidden' }}">
                        <div class="lg:col-span-6 space-y-3 z-10">
                            @if(!empty($s->icono_texto))
                                <span class="inline-block px-3 py-1 text-xs font-bold uppercase rounded" style="background-color: var(--pub-primary); color: #09090b;">
                                    {{ $s->icono_texto }}
                                </span>
                            @endif
                            <h1 class="text-3xl sm:text-5xl font-black uppercase leading-tight">{{ $s->titulo }}</h1>
                            @if(!empty($s->subtitulo))
                                <p class="text-base sm:text-lg font-semibold" style="color: var(--pub-primary);">{{ $s->subtitulo }}</p>
                            @endif
                            @if(!empty($s->descripcion))
                                <p class="text-sm" style="color: var(--pub-muted);">{{ $s->descripcion }}</p>
                            @endif
                        </div>
                        <div class="lg:col-span-6 flex justify-center">
                            @if(!empty($s->imagen_path))
                                <img src="{{ asset('storage/' . $s->imagen_path) }}" alt="{{ $s->titulo }}" class="max-h-72 sm:max-h-80 w-full object-cover rounded-lg shadow-lg">
                            @else
                                <div class="h-64 w-full flex items-center justify-center rounded-lg border text-5xl" style="border-color: var(--pub-border);">
                                    {{ $s->icono_texto ?? ($template->icono_predeterminado ?? '★') }}
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach

                @if($slides->count() > 1)
                    <button type="button" onclick="moverSlide(-1)" class="absolute left-3 top-1/2 -translate-y-1/2 h-10 w-10 rounded-full flex items-center justify-center bg-black/70 text-white text-xl">&lt;</button>
                    <button type="button" onclick="moverSlide(1)" class="absolute right-3 top-1/2 -translate-y-1/2 h-10 w-10 rounded-full flex items-center justify-center bg-black/70 text-white text-xl">&gt;</button>
                    <div class="absolute bottom-4 left-1/2 -translate-x-1/2 flex gap-2">
                        @foreach($slides as $idx => $s)
                            <button type="button" data-dot-index="{{ $idx }}" onclick="irASlide({{ $idx }})" class="h-2.5 w-2.5 rounded-full {{ $idx === 0 ? 'bg-white' : 'bg-white/40' }}"></button>
                        @endforeach
                    </div>
                @endif
            @else
                <div class="p-10 text-center w-full space-y-2">
                    <h1 class="text-2xl font-bold">{{ $empresa->nombre }}</h1>
                    <p class="text-sm" style="color: var(--pub-muted);">El propietario aún no ha agregado slides al carrusel público.</p>
                </div>
            @endif
        </section>

        {{-- Platillos / productos / servicios destacados con fotos e iconos --}}
        <section id="catalogo" class="space-y-5">
            <div class="flex items-center justify-between">
                <h2 class="text-xl sm:text-2xl font-extrabold uppercase tracking-wide">Destacados y Especialidades</h2>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @forelse($destacados as $item)
                    <article class="pub-surface overflow-hidden flex flex-col">
                        @if(!empty($item->imagen_path))
                            <img src="{{ asset('storage/' . $item->imagen_path) }}" alt="{{ $item->titulo }}" class="h-48 w-full object-cover">
                        @else
                            <div class="h-48 w-full flex items-center justify-center text-4xl bg-black/20">
                                {{ $item->icono_texto ?? ($template->icono_predeterminado ?? '★') }}
                            </div>
                        @endif
                        <div class="p-5 flex-1 flex flex-col justify-between space-y-3">
                            <div>
                                <div class="flex items-center justify-between gap-2">
                                    <h3 class="font-bold text-base">{{ $item->titulo }}</h3>
                                    @if(!is_null($item->precio))
                                        <span class="font-mono text-sm font-bold" style="color: var(--pub-accent);">${{ number_format((float) $item->precio, 2) }}</span>
                                    @endif
                                </div>
                                @if(!empty($item->subtitulo))
                                    <p class="text-xs mt-1" style="color: var(--pub-primary);">{{ $item->subtitulo }}</p>
                                @endif
                                @if(!empty($item->descripcion))
                                    <p class="text-xs mt-2" style="color: var(--pub-muted);">{{ $item->descripcion }}</p>
                                @endif
                            </div>
                        </div>
                    </article>
                @empty
                    <p class="text-sm col-span-full" style="color: var(--pub-muted);">Aún no hay elementos destacados publicados.</p>
                @endforelse
            </div>
        </section>
    </main>

    <script>
        let indiceActual = 0;
        const slidesNodes = Array.from(document.querySelectorAll('[data-slide-index]'));
        const dotsNodes = Array.from(document.querySelectorAll('[data-dot-index]'));

        function irASlide(nuevoIdx) {
            if (!slidesNodes.length) return;
            indiceActual = (nuevoIdx + slidesNodes.length) % slidesNodes.length;
            slidesNodes.forEach((el, i) => el.classList.toggle('hidden', i !== indiceActual));
            dotsNodes.forEach((dot, i) => {
                dot.classList.toggle('bg-white', i === indiceActual);
                dot.classList.toggle('bg-white/40', i !== indiceActual);
            });
        }

        function moverSlide(delta) {
            irASlide(indiceActual + delta);
        }
    </script>
</body>
</html>
