<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Autogestión — {{ $empresa->nombre ?? 'Taller' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --taller-font: {{ $config['fuente_familia'] ?? 'Inter, system-ui, -apple-system, sans-serif' }};
            --taller-primary: {{ $config['color_primario'] ?? '#6366f1' }};
            --taller-accent: {{ $config['color_acento'] ?? '#10b981' }};
            --taller-bg: {{ $config['color_fondo'] ?? '#09090b' }};
            --taller-card: {{ $config['color_tarjeta'] ?? '#111115' }};
            --taller-surface: #18181b;
            --taller-text: #f4f4f5;
            --taller-muted: #a1a1aa;
            --taller-border: #27272a;
            --taller-radius: 0.75rem;
        }
        body { font-family: var(--taller-font); background-color: var(--taller-bg); color: var(--taller-text); }
        .taller-card { background-color: var(--taller-card); border: 1px solid var(--taller-border); border-radius: var(--taller-radius); }
        .taller-input { background-color: var(--taller-surface); border: 1px solid var(--taller-border); border-radius: var(--taller-radius); }
        .taller-btn { background-color: var(--taller-primary); color: #ffffff; border-radius: var(--taller-radius); }
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
    @php
        $esInternas = ($ambito ?? 'publica') === 'internas';
        $tab = $tabActivo ?? 'general';
    @endphp

    <aside class="w-full md:w-64 md:h-screen md:sticky md:top-0 shrink-0 border-b md:border-b-0 md:border-r p-5 flex flex-col justify-between overflow-y-auto" style="border-color: var(--taller-border); background-color: var(--taller-card);">
        <div class="space-y-6">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 flex items-center justify-center rounded-lg text-lg font-bold" style="background-color: var(--taller-primary); color: #fff;">
                    {{ $config['icono_predeterminado'] ?? '💻' }}
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
                <a href="{{ route('taller.computadoras.autogestion', ['ambito' => 'publica', 'tab' => 'general']) }}" class="block px-3 py-2 rounded-lg {{ ! $esInternas ? 'font-medium' : 'hover:opacity-90' }}" style="background-color: {{ ! $esInternas ? 'var(--taller-primary)' : 'var(--taller-surface)' }}; color: #fff;">
                    Autogestión página pública
                </a>
                <a href="{{ route('taller.computadoras.autogestion', ['ambito' => 'internas']) }}" class="block px-3 py-2 rounded-lg {{ $esInternas ? 'font-medium' : 'hover:opacity-90' }}" style="background-color: {{ $esInternas ? 'var(--taller-primary)' : 'var(--taller-surface)' }}; color: #fff;">
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
                <button type="submit" class="w-full py-2 px-3 text-xs font-medium taller-input hover:opacity-90 text-left">
                    Cerrar sesión
                </button>
            </form>
        </div>
    </aside>

    <main class="flex-1 md:h-screen md:overflow-y-auto p-6 md:p-8 space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold">
                    {{ $esInternas ? 'Autogestión de Páginas Internas' : 'Autogestión de Página Pública' }}
                </h1>
                <p class="text-xs mt-1" style="color: var(--taller-muted);">
                    {{ $esInternas ? 'Elige cómo se ven el dashboard y vistas internas del taller.' : 'Selecciona la pestaña de la sección que deseas modificar.' }}
                </p>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('taller.computadoras.autogestion', ['ambito' => 'publica', 'tab' => 'general']) }}" class="px-3.5 py-2 text-xs font-semibold rounded-lg border" style="border-color: var(--taller-border); background-color: {{ ! $esInternas ? 'var(--taller-primary)' : 'var(--taller-surface)' }}; color: #fff;">
                    Página pública
                </a>
                <a href="{{ route('taller.computadoras.autogestion', ['ambito' => 'internas']) }}" class="px-3.5 py-2 text-xs font-semibold rounded-lg border" style="border-color: var(--taller-border); background-color: {{ $esInternas ? 'var(--taller-primary)' : 'var(--taller-surface)' }}; color: #fff;">
                    Páginas internas
                </a>
            </div>
        </div>

        @if(! $esInternas)
            {{-- Pestanas exclusivas para Pagina Publica --}}
            <div class="flex flex-wrap gap-2 border-b pb-3" style="border-color: var(--taller-border);">
                @php
                    $tabsPublica = [
                        'general' => 'Información general',
                        'carrusel' => 'Carrusel de imágenes',
                        'servicios' => 'Servicios',
                        'promociones' => 'Promociones',
                    ];
                @endphp
                @foreach($tabsPublica as $claveTab => $labelTab)
                    <a href="{{ route('taller.computadoras.autogestion', ['ambito' => 'publica', 'tab' => $claveTab]) }}"
                       class="px-4 py-2 text-xs font-semibold rounded-lg border transition"
                       style="border-color: var(--taller-border); background-color: {{ $tab === $claveTab ? 'var(--taller-primary)' : 'var(--taller-surface)' }}; color: #fff;">
                        {{ $labelTab }}
                    </a>
                @endforeach
            </div>
        @endif

        @if(session('status'))
            <div class="p-3 rounded border border-emerald-500/60 bg-emerald-950/30 text-xs text-emerald-300">
                {{ session('status') }}
            </div>
        @endif

        @if($errors->any())
            <div class="p-3 rounded border border-rose-500/60 bg-rose-950/30 text-xs text-rose-300 space-y-1">
                @foreach($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        @if($esInternas || $tab === 'general')
            {{-- 1. Datos generales y apariencia --}}
            <form method="POST" action="{{ route('taller.computadoras.autogestion.general') }}" enctype="multipart/form-data" class="taller-card p-6 space-y-4">
                @csrf
                <input type="hidden" name="ambito" value="{{ $ambito ?? 'publica' }}">
                <input type="hidden" name="tab" value="general">

                <h2 class="text-base font-semibold">
                    {{ $esInternas ? 'Configuración de páginas internas' : 'Información general y apariencia pública' }}
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs mb-1" style="color: var(--taller-muted);">Nombre del taller</label>
                        <input type="text" name="nombre" value="{{ old('nombre', $empresa->nombre ?? '') }}" required class="w-full px-3.5 py-2 taller-input text-sm">
                    </div>

                    @if($esInternas)
                        <div>
                            <label class="block text-xs mb-1" style="color: var(--taller-muted);">Título del panel interno</label>
                            <input type="text" name="titulo_panel" value="{{ old('titulo_panel', $config['titulo_panel'] ?? 'Panel de Control — Reparación de Computadoras') }}" class="w-full px-3.5 py-2 taller-input text-sm">
                        </div>
                    @else
                        <div>
                            <label class="block text-xs mb-1" style="color: var(--taller-muted);">Etiqueta superior (Hero)</label>
                            <input type="text" name="hero_badge" value="{{ old('hero_badge', $config['hero_badge'] ?? '') }}" class="w-full px-3.5 py-2 taller-input text-sm">
                        </div>
                    @endif
                </div>

                @if($esInternas)
                    <div>
                        <label class="block text-xs mb-1" style="color: var(--taller-muted);">Subtítulo del panel interno</label>
                        <input type="text" name="subtitulo_panel" value="{{ old('subtitulo_panel', $config['subtitulo_panel'] ?? '') }}" class="w-full px-3.5 py-2 taller-input text-sm">
                    </div>

                    <div class="flex flex-wrap gap-6 pt-1 text-xs">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="mostrar_metricas" value="1" @checked(old('mostrar_metricas', $config['mostrar_metricas'] ?? true))>
                            <span>Mostrar tarjetas de métricas en el dashboard</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="mostrar_ultimas_ordenes" value="1" @checked(old('mostrar_ultimas_ordenes', $config['mostrar_ultimas_ordenes'] ?? true))>
                            <span>Mostrar tabla de últimas órdenes en el dashboard</span>
                        </label>
                    </div>
                @else
                    <div>
                        <label class="block text-xs mb-1" style="color: var(--taller-muted);">Título principal (Hero)</label>
                        <input type="text" name="hero_titulo" value="{{ old('hero_titulo', $config['hero_titulo'] ?? '') }}" class="w-full px-3.5 py-2 taller-input text-sm">
                    </div>

                    <div>
                        <label class="block text-xs mb-1" style="color: var(--taller-muted);">Descripción pública</label>
                        <textarea name="descripcion" rows="2" class="w-full px-3.5 py-2 taller-input text-sm">{{ old('descripcion', $config['descripcion'] ?? '') }}</textarea>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-xs mb-1" style="color: var(--taller-muted);">Teléfono</label>
                            <input type="text" name="telefono" value="{{ old('telefono', $config['telefono'] ?? '') }}" class="w-full px-3.5 py-2 taller-input text-sm">
                        </div>
                        <div>
                            <label class="block text-xs mb-1" style="color: var(--taller-muted);">WhatsApp</label>
                            <input type="text" name="whatsapp" value="{{ old('whatsapp', $config['whatsapp'] ?? '') }}" class="w-full px-3.5 py-2 taller-input text-sm">
                        </div>
                        <div>
                            <label class="block text-xs mb-1" style="color: var(--taller-muted);">Horario</label>
                            <input type="text" name="horario" value="{{ old('horario', $config['horario'] ?? '') }}" class="w-full px-3.5 py-2 taller-input text-sm">
                        </div>
                        <div>
                            <label class="block text-xs mb-1" style="color: var(--taller-muted);">Dirección</label>
                            <input type="text" name="direccion" value="{{ old('direccion', $config['direccion'] ?? '') }}" class="w-full px-3.5 py-2 taller-input text-sm">
                        </div>
                    </div>
                @endif

                <div class="grid grid-cols-2 sm:grid-cols-5 gap-4">
                    <div>
                        <label class="block text-xs mb-1" style="color: var(--taller-muted);">Ícono / Emoji</label>
                        <input type="text" name="icono_predeterminado" value="{{ old('icono_predeterminado', $config['icono_predeterminado'] ?? '💻') }}" class="w-full px-3.5 py-2 taller-input text-sm">
                    </div>
                    <div>
                        <label class="block text-xs mb-1" style="color: var(--taller-muted);">Color primario</label>
                        <input type="color" name="color_primario" value="{{ old('color_primario', $config['color_primario'] ?? '#6366f1') }}" class="w-full h-10 px-1 py-1 taller-input">
                    </div>
                    <div>
                        <label class="block text-xs mb-1" style="color: var(--taller-muted);">Color acento</label>
                        <input type="color" name="color_acento" value="{{ old('color_acento', $config['color_acento'] ?? '#10b981') }}" class="w-full h-10 px-1 py-1 taller-input">
                    </div>
                    <div>
                        <label class="block text-xs mb-1" style="color: var(--taller-muted);">Fondo página</label>
                        <input type="color" name="color_fondo" value="{{ old('color_fondo', $config['color_fondo'] ?? '#09090b') }}" class="w-full h-10 px-1 py-1 taller-input">
                    </div>
                    <div>
                        <label class="block text-xs mb-1" style="color: var(--taller-muted);">Fondo tarjeta</label>
                        <input type="color" name="color_tarjeta" value="{{ old('color_tarjeta', $config['color_tarjeta'] ?? '#111115') }}" class="w-full h-10 px-1 py-1 taller-input">
                    </div>
                </div>

                <div>
                    <label class="block text-xs mb-1" style="color: var(--taller-muted);">Tipo de letra</label>
                    <select id="selectFuenteFamilia" name="fuente_familia" class="w-full px-3.5 py-2 taller-input text-sm">
                        @php $fuenteActual = old('fuente_familia', $config['fuente_familia'] ?? 'Inter, system-ui, -apple-system, sans-serif'); @endphp
                        @foreach(($fuentes ?? []) as $cssFont => $etiquetaFont)
                            <option value="{{ $cssFont }}" @selected($fuenteActual === $cssFont) style="font-family: {{ $cssFont }};">{{ $etiquetaFont }}</option>
                        @endforeach
                    </select>
                </div>

                <script>
                    (function () {
                        const sel = document.getElementById('selectFuenteFamilia');
                        if (!sel) return;
                        sel.addEventListener('change', () => { document.body.style.fontFamily = sel.value; });
                    })();
                </script>

                                @if(! $esInternas)
                    <div class="p-4 taller-input space-y-4">
                        <h3 class="text-xs font-semibold uppercase tracking-wider" style="color: var(--taller-muted);">
                            Fondo, degradado y comportamiento al hacer scroll
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs mb-1" style="color: var(--taller-muted);">Imagen de fondo (opcional)</label>
                                <input type="file" name="fondo_imagen_archivo" accept=".jpg,.jpeg,.png,.webp" class="w-full px-3 py-1.5 taller-card text-xs">
                                @if(!empty($config['fondo_imagen']))
                                    <div class="mt-2 flex items-center gap-3">
                                        <img src="{{ $config['fondo_imagen'] }}" alt="Fondo actual" class="h-14 w-24 object-cover rounded border" style="border-color: var(--taller-border);">
                                        <label class="flex items-center gap-1.5 text-xs cursor-pointer text-rose-300">
                                            <input type="checkbox" name="quitar_fondo_imagen" value="1">
                                            <span>Quitar imagen de fondo</span>
                                        </label>
                                    </div>
                                @endif
                            </div>

                            <div>
                                <label class="block text-xs mb-1" style="color: var(--taller-muted);">Estilo de degradado</label>
                                @php $degActual = old('fondo_degradado', $config['fondo_degradado'] ?? 'oscuro_suave'); @endphp
                                <select name="fondo_degradado" class="w-full px-3.5 py-2 taller-card text-sm">
                                    @foreach(($degradados ?? []) as $claveDeg => $etiquetaDeg)
                                        <option value="{{ $claveDeg }}" @selected($degActual === $claveDeg)>{{ $etiquetaDeg }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <label class="flex items-center gap-2 text-xs cursor-pointer pt-1">
                            <input type="checkbox" name="fondo_estatico" value="1" @checked(old('fondo_estatico', $config['fondo_estatico'] ?? true))>
                            <span>Mantener fondo estático al hacer scroll (solo se mueve la información)</span>
                        </label>
                    </div>
                @endif

                                @if(! $esInternas)
                    <div class="p-4 taller-input space-y-1.5">
                        @php $transpActual = (int) old('transparencia_flotantes', $config['transparencia_flotantes'] ?? 25); @endphp
                        <div class="flex items-center justify-between text-xs">
                            <label for="rangoTransparencia" style="color: var(--taller-muted);">Transparencia de tarjetas y paneles flotantes (página pública)</label>
                            <span id="valorTransparencia" class="font-semibold">{{ $transpActual }}%</span>
                        </div>
                        <input id="rangoTransparencia" type="range" min="0" max="85" step="5" name="transparencia_flotantes" value="{{ $transpActual }}" class="w-full cursor-pointer">
                        <script>
                            (function () {
                                const r = document.getElementById('rangoTransparencia');
                                const v = document.getElementById('valorTransparencia');
                                if (r && v) r.addEventListener('input', () => { v.textContent = r.value + '%'; });
                            })();
                        </script>
                    </div>
                @endif

                <button type="submit" class="px-5 py-2 text-xs font-semibold taller-btn hover:opacity-95">
                    Guardar cambios de {{ $esInternas ? 'páginas internas' : 'información general' }}
                </button>
            </form>
        @endif

        @if(! $esInternas && $tab === 'carrusel')
            {{-- 2. Carrusel de imágenes (incluye velocidad) --}}
            <section class="taller-card p-6 space-y-6">
                <div>
                    <h2 class="text-base font-semibold">Carrusel de imágenes</h2>
                    <p class="text-xs" style="color: var(--taller-muted);">Ajusta la velocidad de transición, sube banners (JPG, PNG, WEBP, máx. 5 MB) o elimina imágenes actuales.</p>
                </div>

                <form method="POST" action="{{ route('taller.computadoras.autogestion.carrusel.config') }}" class="flex flex-wrap items-end gap-3 p-4 taller-input">
                    @csrf
                    <div class="w-64">
                        <label class="block text-xs mb-1" style="color: var(--taller-muted);">Velocidad de cambio (segundos)</label>
                        @php $segActual = round(((int) ($config['carrusel_intervalo_ms'] ?? 4500)) / 1000, 1); @endphp
                        <input type="number" step="0.5" min="1.5" max="30" name="carrusel_segundos" value="{{ old('carrusel_segundos', $segActual) }}" required class="w-full px-3.5 py-2 taller-card text-sm">
                    </div>
                    <button type="submit" class="px-4 py-2 text-xs font-semibold taller-btn hover:opacity-95">
                        Guardar velocidad
                    </button>
                </form>

                <form method="POST" action="{{ route('taller.computadoras.autogestion.imagenes.store') }}" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
                    @csrf
                    <div class="md:col-span-2">
                        <label class="block text-xs mb-1" style="color: var(--taller-muted);">Archivo de imagen</label>
                        <input type="file" name="imagen" accept=".jpg,.jpeg,.png,.webp" required class="w-full px-3 py-1.5 taller-input text-xs">
                    </div>
                    <div>
                        <label class="block text-xs mb-1" style="color: var(--taller-muted);">Título interno (opcional)</label>
                        <input type="text" name="titulo" placeholder="Referencia interna: ej. Promo Windows" class="w-full px-3 py-2 taller-input text-xs">
                    </div>
                    <div>
                        <label class="block text-xs mb-1" style="color: var(--taller-muted);">Nota interna (opcional)</label>
                        <input type="text" name="subtitulo" placeholder="Nota interna (opcional)" class="w-full px-3 py-2 taller-input text-xs">
                    </div>
                    <div class="md:col-span-4">
                        <button type="submit" class="px-4 py-2 text-xs font-semibold taller-btn hover:opacity-95">
                            Subir imagen al carrusel
                        </button>
                    </div>
                </form>

                @php $imgs = array_values(array_filter($config['imagenes'] ?? [], fn($s) => is_array($s) && !empty($s['url']))); @endphp
                <div class="pt-3 border-t space-y-3" style="border-color: var(--taller-border);">
                    <h3 class="text-xs font-semibold uppercase tracking-wider" style="color: var(--taller-muted);">
                        Miniaturas actuales del carrusel ({{ count($imgs) }})
                    </h3>

                    @if(count($imgs) > 0)
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                            @foreach($imgs as $idxImg => $img)
                                <div class="taller-input p-3 space-y-2.5 flex flex-col justify-between">
                                    <div class="space-y-2">
                                        <div class="flex items-center justify-between text-[11px]" style="color: var(--taller-muted);">
                                            <span>Banner #{{ $idxImg + 1 }}</span>
                                            <a href="{{ $img['url'] }}" target="_blank" class="underline hover:opacity-80">Ver tamaño real</a>
                                        </div>
                                        <img src="{{ $img['url'] }}" alt="{{ $img['titulo'] ?? ('Banner #' . ($idxImg + 1)) }}" class="w-full h-36 object-cover rounded">
                                        <div class="text-xs">
                                            <p class="font-semibold truncate">{{ $img['titulo'] ?: 'Sin referencia interna' }}</p>
                                            @if(!empty($img['subtitulo']))
                                                <p class="truncate" style="color: var(--taller-muted);">{{ $img['subtitulo'] }}</p>
                                            @endif
                                        </div>
                                    </div>

                                    <form method="POST" action="{{ route('taller.computadoras.autogestion.imagenes.destroy', ['imagenId' => $img['id']]) }}" onsubmit="return confirm('¿Quitar este banner del carrusel?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="w-full px-3 py-1.5 text-xs font-medium rounded border border-rose-500/60 text-rose-300 hover:bg-rose-950/40 transition">
                                            Quitar este banner
                                        </button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-xs" style="color: var(--taller-muted);">
                            Todavía no hay banners cargados. Sube una imagen arriba para ver su miniatura aquí.
                        </p>
                    @endif
                </div>
            </section>
        @endif

        @if(! $esInternas && $tab === 'servicios')
            {{-- 3. Servicios --}}
            <section class="taller-card p-6 space-y-4">
                <h2 class="text-base font-semibold">Servicios mostrados en la página pública</h2>

                <form method="POST" action="{{ route('taller.computadoras.autogestion.servicios.store') }}" class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
                    @csrf
                    <div>
                        <label class="block text-xs mb-1" style="color: var(--taller-muted);">Nombre del servicio</label>
                        <input type="text" name="titulo" required placeholder="Ej. Cambio de pantalla" class="w-full px-3 py-2 taller-input text-xs">
                    </div>
                    <div>
                        <label class="block text-xs mb-1" style="color: var(--taller-muted);">Descripción</label>
                        <input type="text" name="descripcion" required placeholder="Ej. Pantallas LED/FHD originales" class="w-full px-3 py-2 taller-input text-xs">
                    </div>
                    <div>
                        <button type="submit" class="w-full px-4 py-2 text-xs font-semibold taller-btn hover:opacity-95">
                            Agregar servicio
                        </button>
                    </div>
                </form>

                <div class="space-y-2 pt-2">
                    @foreach(($config['servicios'] ?? []) as $i => $srv)
                        <div class="flex items-center justify-between gap-3 p-3 taller-input text-xs">
                            <div>
                                <p class="font-semibold">{{ $srv['titulo'] ?? $srv['nombre'] ?? 'Servicio' }}</p>
                                <p style="color: var(--taller-muted);">{{ $srv['descripcion'] }}</p>
                            </div>
                            <form method="POST" action="{{ route('taller.computadoras.autogestion.servicios.destroy', ['indice' => $i]) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="px-2.5 py-1 text-[11px] rounded border border-rose-500/60 text-rose-300 hover:bg-rose-950/30">
                                    Quitar
                                </button>
                            </form>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        @if(! $esInternas && $tab === 'promociones')
            {{-- 4. Promociones --}}
            <section class="taller-card p-6 space-y-4">
                <h2 class="text-base font-semibold">Promociones</h2>

                <form method="POST" action="{{ route('taller.computadoras.autogestion.promociones.store') }}" class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
                    @csrf
                    <div>
                        <label class="block text-xs mb-1" style="color: var(--taller-muted);">Etiqueta</label>
                        <input type="text" name="etiqueta" placeholder="Ej. 2X1 / PROMO" class="w-full px-3 py-2 taller-input text-xs">
                    </div>
                    <div>
                        <label class="block text-xs mb-1" style="color: var(--taller-muted);">Título</label>
                        <input type="text" name="titulo" required placeholder="Ej. Mantenimiento preventivo" class="w-full px-3 py-2 taller-input text-xs">
                    </div>
                    <div>
                        <label class="block text-xs mb-1" style="color: var(--taller-muted);">Descripción</label>
                        <input type="text" name="descripcion" required placeholder="Ej. Limpieza + pasta térmica" class="w-full px-3 py-2 taller-input text-xs">
                    </div>
                    <div>
                        <button type="submit" class="w-full px-4 py-2 text-xs font-semibold taller-btn hover:opacity-95">
                            Agregar promoción
                        </button>
                    </div>
                </form>

                <div class="space-y-2 pt-2">
                    @foreach(($config['promociones'] ?? []) as $j => $pr)
                        <div class="flex items-center justify-between gap-3 p-3 taller-input text-xs">
                            <div>
                                <p class="font-semibold">[{{ $pr['etiqueta'] ?? 'PROMO' }}] {{ $pr['titulo'] }}</p>
                                <p style="color: var(--taller-muted);">{{ $pr['descripcion'] }}</p>
                            </div>
                            <form method="POST" action="{{ route('taller.computadoras.autogestion.promociones.destroy', ['indice' => $j]) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="px-2.5 py-1 text-[11px] rounded border border-rose-500/60 text-rose-300 hover:bg-rose-950/30">
                                    Quitar
                                </button>
                            </form>
                        </div>
                    @endforeach
                </div>
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
