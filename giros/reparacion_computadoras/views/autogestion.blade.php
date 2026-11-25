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
    </style>
</head>
<body class="min-h-screen flex antialiased">
    @php $esInternas = ($ambito ?? 'publica') === 'internas'; @endphp

    <aside class="w-64 shrink-0 border-r p-5 flex flex-col justify-between" style="border-color: var(--taller-border); background-color: var(--taller-card);">
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
                <a href="{{ route('taller.computadoras.autogestion', ['ambito' => 'publica']) }}" class="block px-3 py-2 rounded-lg {{ ! $esInternas ? 'font-medium' : 'hover:opacity-90' }}" style="background-color: {{ ! $esInternas ? 'var(--taller-primary)' : 'var(--taller-surface)' }}; color: #fff;">
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

    <main class="flex-1 p-6 md:p-8 space-y-8 max-w-5xl">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold">
                    {{ $esInternas ? 'Autogestión de Páginas Internas' : 'Autogestión de Página Pública' }}
                </h1>
                <p class="text-xs mt-1" style="color: var(--taller-muted);">
                    {{ $esInternas ? 'Elige cómo se ven el dashboard y vistas internas del taller.' : 'Administra información, colores, fuente, carrusel, servicios y promociones de la página pública.' }}
                </p>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('taller.computadoras.autogestion', ['ambito' => 'publica']) }}" class="px-3.5 py-2 text-xs font-semibold rounded-lg border" style="border-color: var(--taller-border); background-color: {{ ! $esInternas ? 'var(--taller-primary)' : 'var(--taller-surface)' }}; color: #fff;">
                    Página pública
                </a>
                <a href="{{ route('taller.computadoras.autogestion', ['ambito' => 'internas']) }}" class="px-3.5 py-2 text-xs font-semibold rounded-lg border" style="border-color: var(--taller-border); background-color: {{ $esInternas ? 'var(--taller-primary)' : 'var(--taller-surface)' }}; color: #fff;">
                    Páginas internas
                </a>
            </div>
        </div>

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

        <form method="POST" action="{{ route('taller.computadoras.autogestion.general') }}" class="taller-card p-6 space-y-4">
            @csrf
            <input type="hidden" name="ambito" value="{{ $ambito ?? 'publica' }}">

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

            <div class="col-span-2 sm:col-span-5">
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

            <button type="submit" class="px-5 py-2 text-xs font-semibold taller-btn hover:opacity-95">
                Guardar cambios de {{ $esInternas ? 'páginas internas' : 'página pública' }}
            </button>
        </form>

        @if(! $esInternas)
            {{-- 2. Carrusel de imágenes --}}
            <section class="taller-card p-6 space-y-5">
                <div>
                    <h2 class="text-base font-semibold">Carrusel de imágenes</h2>
                    <p class="text-xs" style="color: var(--taller-muted);">Sube fotos del taller, equipos reparados o promociones visuales (JPG, PNG, WEBP, máx. 5 MB).</p>
                </div>

                <form method="POST" action="{{ route('taller.computadoras.autogestion.imagenes.store') }}" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
                    @csrf
                    <div class="md:col-span-2">
                        <label class="block text-xs mb-1" style="color: var(--taller-muted);">Archivo de imagen</label>
                        <input type="file" name="imagen" accept=".jpg,.jpeg,.png,.webp" required class="w-full px-3 py-1.5 taller-input text-xs">
                    </div>
                    <div>
                        <label class="block text-xs mb-1" style="color: var(--taller-muted);">Título (opcional)</label>
                        <input type="text" name="titulo" placeholder="Ej. Banco de pruebas" class="w-full px-3 py-2 taller-input text-xs">
                    </div>
                    <div>
                        <label class="block text-xs mb-1" style="color: var(--taller-muted);">Subtítulo (opcional)</label>
                        <input type="text" name="subtitulo" placeholder="Ej. Diagnóstico en el día" class="w-full px-3 py-2 taller-input text-xs">
                    </div>
                    <div class="md:col-span-4">
                        <button type="submit" class="px-4 py-2 text-xs font-semibold taller-btn hover:opacity-95">
                            Subir imagen al carrusel
                        </button>
                    </div>
                </form>

                @php $imgs = $config['imagenes'] ?? []; @endphp
                @if(count($imgs) > 0)
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 pt-2">
                        @foreach($imgs as $img)
                            <div class="taller-input p-3 space-y-2">
                                <img src="{{ $img['url'] }}" alt="{{ $img['titulo'] ?? 'Imagen' }}" class="w-full h-36 object-cover rounded">
                                <div class="text-xs">
                                    <p class="font-semibold truncate">{{ $img['titulo'] ?: 'Sin título' }}</p>
                                    @if(!empty($img['subtitulo']))<p class="truncate" style="color: var(--taller-muted);">{{ $img['subtitulo'] }}</p>@endif
                                </div>
                                <form method="POST" action="{{ route('taller.computadoras.autogestion.imagenes.destroy', ['imagenId' => $img['id']]) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="px-2.5 py-1 text-[11px] rounded border border-rose-500/60 text-rose-300 hover:bg-rose-950/30">
                                        Eliminar
                                    </button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>

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
                                <p class="font-semibold">{{ $srv['titulo'] }}</p>
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
</body>
</html>
