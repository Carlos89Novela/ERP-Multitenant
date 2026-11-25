<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Autogestión Visual — {{ $etiquetaGiro }} ({{ $empresa->nombre }})</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --tpl-font-sans: {{ $template->fuente_primaria ?? 'Inter, system-ui, sans-serif' }};
            --tpl-font-mono: {{ $template->fuente_secundaria ?? 'JetBrains Mono, monospace' }};
            --tpl-primary: {{ $template->color_primario ?? '#6366f1' }};
            --tpl-accent: {{ $template->color_acento ?? '#10b981' }};
            --tpl-bg-page: {{ $template->color_fondo_pagina ?? '#09090b' }};
            --tpl-bg-surface: {{ $template->color_fondo_superficie ?? '#18181b' }};
            --tpl-bg-card: {{ $template->fondo_tarjeta ?? '#111115' }};
            --tpl-text: {{ $template->color_texto_principal ?? '#f4f4f5' }};
            --tpl-text-muted: {{ $template->color_texto_secundario ?? '#a1a1aa' }};
            --tpl-border: {{ $template->color_borde ?? '#27272a' }};
            --tpl-radius: {{ $template->radio_bordes ?? '0.75rem' }};
        }
        body { font-family: var(--tpl-font-sans); background-color: var(--tpl-bg-page); color: var(--tpl-text); }
        .tpl-card { background-color: var(--tpl-bg-card); border: 1px solid var(--tpl-border); border-radius: var(--tpl-radius); }
        .tpl-surface { background-color: var(--tpl-bg-surface); border: 1px solid var(--tpl-border); border-radius: var(--tpl-radius); }
        .tpl-btn { background-color: var(--tpl-primary); color: #fff; border-radius: var(--tpl-radius); }
        .tpl-muted { color: var(--tpl-text-muted); }
        .tpl-accent { color: var(--tpl-accent); }
        .tpl-mono { font-family: var(--tpl-font-mono); }
    </style>
</head>
<body class="min-h-screen antialiased">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-8 space-y-6">
        <header class="tpl-card p-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-4">
                @if(!empty($template->logo_path))
                    <img src="{{ asset('storage/' . $template->logo_path) }}" alt="{{ $empresa->nombre }}" class="h-12 w-12 object-cover rounded">
                @else
                    <span class="h-12 w-12 flex items-center justify-center rounded text-2xl tpl-surface">{{ $template->icono_predeterminado ?? $iconoDefault }}</span>
                @endif
                <div>
                    <span class="text-xs font-semibold uppercase tpl-accent">AUTOGESTIÓN VISUAL • {{ strtoupper($etiquetaGiro) }}</span>
                    <h1 class="text-2xl font-bold mt-0.5">{{ $empresa->nombre }}</h1>
                    <p class="text-xs tpl-muted">Administra identidad, íconos, fotos de productos/promociones y carrusel público.</p>
                </div>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route($giroRutaPrefix . '.index') }}" class="px-3.5 py-2 text-xs font-medium tpl-surface">Volver al módulo</a>
                <a href="{{ route('empresa.publica', $empresa->slug) }}" target="_blank" class="px-3.5 py-2 text-xs font-semibold rounded" style="background-color: var(--tpl-accent); color: #09090b;">Página pública</a>
                <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="px-3.5 py-2 text-xs font-medium tpl-surface">Cerrar sesión</button></form>
            </div>
        </header>

        @if(session('status'))
            <div class="tpl-card p-4 border-l-4" style="border-left-color: var(--tpl-accent);"><p class="text-sm font-medium tpl-accent">{{ session('status') }}</p></div>
        @endif

        <section class="tpl-card p-6 space-y-4">
            <h2 class="text-lg font-semibold">Identidad Visual, Íconos y Colores</h2>
            <form method="POST" action="{{ route($giroRutaPrefix . '.autogestion.apariencia') }}" enctype="multipart/form-data" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                @csrf
                <div><label class="block text-xs tpl-muted mb-1">Logo o foto de marca</label><input type="file" name="logo_archivo" accept="image/*" class="w-full text-xs tpl-surface p-1.5"></div>
                <div><label class="block text-xs tpl-muted mb-1">Ícono de respaldo</label><input type="text" name="icono_predeterminado" value="{{ $template->icono_predeterminado ?? $iconoDefault }}" class="w-full px-3 py-2 tpl-surface text-sm"></div>
                <div><label class="block text-xs tpl-muted mb-1">Color principal</label><input type="color" name="color_primario" value="{{ $template->color_primario ?? '#6366f1' }}" class="w-full h-10 tpl-surface p-1"></div>
                <div><label class="block text-xs tpl-muted mb-1">Color acento</label><input type="color" name="color_acento" value="{{ $template->color_acento ?? '#10b981' }}" class="w-full h-10 tpl-surface p-1"></div>
                <div><label class="block text-xs tpl-muted mb-1">Color secundario</label><input type="color" name="color_secundario" value="{{ $template->color_secundario ?? '#3b82f6' }}" class="w-full h-10 tpl-surface p-1"></div>
                <div><label class="block text-xs tpl-muted mb-1">Fondo página</label><input type="color" name="color_fondo_pagina" value="{{ $template->color_fondo_pagina ?? '#09090b' }}" class="w-full h-10 tpl-surface p-1"></div>
                <div><label class="block text-xs tpl-muted mb-1">Fondo superficie</label><input type="color" name="color_fondo_superficie" value="{{ $template->color_fondo_superficie ?? '#18181b' }}" class="w-full h-10 tpl-surface p-1"></div>
                <div><label class="block text-xs tpl-muted mb-1">Radio de bordes</label><input type="text" name="radio_bordes" value="{{ $template->radio_bordes ?? '0.75rem' }}" class="w-full px-3 py-2 tpl-surface text-sm tpl-mono"></div>
                <input type="hidden" name="color_texto_principal" value="{{ $template->color_texto_principal ?? '#f4f4f5' }}">
                <input type="hidden" name="color_texto_secundario" value="{{ $template->color_texto_secundario ?? '#a1a1aa' }}">
                <input type="hidden" name="color_borde" value="{{ $template->color_borde ?? '#27272a' }}">
                <div class="sm:col-span-2 lg:col-span-4"><button type="submit" class="px-4 py-2 text-xs font-semibold tpl-btn">Guardar apariencia</button></div>
            </form>
        </section>

        <section class="tpl-card p-6 space-y-4">
            <h2 class="text-lg font-semibold">Carrusel y Productos/Promociones Destacados con Foto</h2>
            <form method="POST" action="{{ route($giroRutaPrefix . '.autogestion.elementos.store') }}" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-4 gap-3">
                @csrf
                <select name="tipo" class="px-3 py-2 tpl-surface text-sm">
                    <option value="slide">Slide del carrusel principal</option>
                    <option value="destacado">Producto / Promoción destacado</option>
                </select>
                <input type="text" name="titulo" required placeholder="Título del producto o campaña" class="px-3 py-2 tpl-surface text-sm">
                <input type="text" name="subtitulo" placeholder="Proveedor o categoría" class="px-3 py-2 tpl-surface text-sm">
                <input type="file" name="imagen_archivo" accept="image/*" class="text-xs tpl-surface p-1.5">
                <input type="text" name="icono_texto" placeholder="Ícono o etiqueta (ej. OFERTA, 2x1)" class="px-3 py-2 tpl-surface text-sm">
                <input type="number" step="0.01" name="precio" placeholder="Precio (opcional)" min="0" class="px-3 py-2 tpl-surface text-sm tpl-mono">
                <input type="number" name="orden" value="1" min="1" max="999" class="px-3 py-2 tpl-surface text-sm tpl-mono">
                <input type="text" name="descripcion" placeholder="Descripción corta" class="px-3 py-2 tpl-surface text-sm">
                <div class="md:col-span-4"><button type="submit" class="px-4 py-2 text-xs font-semibold tpl-btn">Agregar elemento</button></div>
            </form>

            <div class="divide-y pt-2" style="border-color: var(--tpl-border);">
                @forelse($elementos as $el)
                    <div class="py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-sm">
                        <div class="flex items-center gap-3">
                            @if(!empty($el->imagen_path))
                                <img src="{{ asset('storage/' . $el->imagen_path) }}" alt="{{ $el->titulo }}" class="h-12 w-12 object-cover rounded">
                            @else
                                <span class="h-12 w-12 flex items-center justify-center rounded tpl-surface text-lg">{{ $el->icono_texto ?? $iconoDefault }}</span>
                            @endif
                            <div>
                                <p class="font-semibold">{{ $el->titulo }} <span class="text-xs tpl-muted">[{{ $el->tipo }}]</span></p>
                                <p class="text-xs tpl-muted">{{ $el->subtitulo ?? '' }} • {{ $el->activo ? 'Visible' : 'Oculto' }}</p>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <form method="POST" action="{{ route($giroRutaPrefix . '.autogestion.elementos.toggle', $el->id) }}">@csrf<button type="submit" class="px-3 py-1 text-xs tpl-surface">{{ $el->activo ? 'Ocultar' : 'Mostrar' }}</button></form>
                            <form method="POST" action="{{ route($giroRutaPrefix . '.autogestion.elementos.destroy', $el->id) }}">@csrf @method('DELETE')<button type="submit" class="px-3 py-1 text-xs rounded bg-rose-600/80 text-white">Quitar</button></form>
                        </div>
                    </div>
                @empty
                    <p class="py-3 text-xs tpl-muted">Aún no has agregado elementos visuales.</p>
                @endforelse
            </div>
        </section>
    </div>
</body>
</html>
