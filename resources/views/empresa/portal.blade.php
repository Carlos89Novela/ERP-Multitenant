<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $empresa->nombre }} — Portal del Propietario</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --tpl-font-sans: {{ $template->fuente_primaria ?? 'Inter, system-ui, sans-serif' }};
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
            background-color: var(--tpl-bg-page);
            color: var(--tpl-text);
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
        .tpl-muted { color: var(--tpl-text-muted); }
        .tpl-accent { color: var(--tpl-accent); }
    </style>
</head>
<body class="min-h-screen antialiased">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 py-8 space-y-6">
        <header class="tpl-card p-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <span class="text-xs font-semibold uppercase tpl-accent">
                    PROPIETARIO • GIRO: {{ $girosDisponibles[$empresa->giro_comercial] ?? $empresa->giro_comercial }}
                </span>
                <h1 class="text-2xl font-bold mt-1">{{ $empresa->nombre }}</h1>
                <p class="text-xs tpl-muted mt-1">
                    Sesión: {{ $user->name }} ({{ $user->email }}) • Sucursales: {{ $empresa->sucursales->count() }}
                </p>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="px-4 py-2 text-xs font-medium tpl-surface hover:opacity-90">Cerrar sesión</button>
            </form>
        </header>

        <section class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="tpl-card p-4">
                <p class="text-xs tpl-muted uppercase">Giro Comercial Fijo</p>
                <p class="text-lg font-bold mt-1">{{ $girosDisponibles[$empresa->giro_comercial] ?? $empresa->giro_comercial }}</p>
            </div>
            <div class="tpl-card p-4">
                <p class="text-xs tpl-muted uppercase">Saldo de Timbres</p>
                <p class="text-2xl font-bold mt-1 tpl-accent">{{ number_format($empresa->saldo_timbres) }}</p>
            </div>
            <div class="tpl-card p-4">
                <p class="text-xs tpl-muted uppercase">Páginas Habilitadas</p>
                <p class="text-2xl font-bold mt-1">{{ count($empresa->paginas_habilitadas ?? []) }}</p>
            </div>
        </section>

        <section class="tpl-card p-6 space-y-3">
            <h2 class="text-lg font-semibold">Páginas habilitadas para tu empresa</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                @forelse(($empresa->paginas_habilitadas ?? []) as $pag)
                    <div class="p-3.5 tpl-surface text-xs font-medium">
                        {{ $paginasCatalogo[$pag] ?? $pag }}
                    </div>
                @empty
                    <p class="text-xs tpl-muted col-span-4">Aún no hay páginas asignadas a esta empresa.</p>
                @endforelse
            </div>
        </section>
    </div>
        <section class="tpl-card p-6 space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <div>
                    <h2 class="text-lg font-semibold">Autogestión Visual y Página Pública de la Empresa</h2>
                    <p class="text-xs tpl-muted">Personaliza colores, logo/ícono, fotos de platillos o productos y el carrusel público de tu empresa.</p>
                </div>
                <a href="{{ route('empresa.publica', $empresa->slug) }}" target="_blank" class="px-3.5 py-2 text-xs font-semibold rounded" style="background-color: var(--tpl-accent); color: #09090b;">
                    Ver Página Pública (/tienda/{{ $empresa->slug }})
                </a>
            </div>

            <form method="POST" action="{{ route('empresa.autogestion.apariencia') }}" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-4 gap-3 pt-2 border-t" style="border-color: var(--tpl-border);">
                @csrf
                <div>
                    <label class="block text-xs tpl-muted mb-1">Logo de empresa (imagen)</label>
                    <input type="file" name="logo_archivo" accept="image/*" class="w-full text-xs tpl-surface p-1.5">
                </div>
                <div>
                    <label class="block text-xs tpl-muted mb-1">Ícono respaldo (ej. 🍔, 🔧, ★)</label>
                    <input type="text" name="icono_predeterminado" value="{{ $template->icono_predeterminado ?? '★' }}" class="w-full px-3 py-2 tpl-surface text-sm">
                </div>
                <div>
                    <label class="block text-xs tpl-muted mb-1">Color principal</label>
                    <input type="color" name="color_primario" value="{{ $template->color_primario ?? '#f59e0b' }}" class="w-full h-10 tpl-surface p-1">
                </div>
                <div>
                    <label class="block text-xs tpl-muted mb-1">Color acento</label>
                    <input type="color" name="color_acento" value="{{ $template->color_acento ?? '#10b981' }}" class="w-full h-10 tpl-surface p-1">
                </div>
                <div>
                    <label class="block text-xs tpl-muted mb-1">Color secundario</label>
                    <input type="color" name="color_secundario" value="{{ $template->color_secundario ?? '#d97706' }}" class="w-full h-10 tpl-surface p-1">
                </div>
                <div>
                    <label class="block text-xs tpl-muted mb-1">Fondo página</label>
                    <input type="color" name="color_fondo_pagina" value="{{ $template->color_fondo_pagina ?? '#0b0b0c' }}" class="w-full h-10 tpl-surface p-1">
                </div>
                <div>
                    <label class="block text-xs tpl-muted mb-1">Fondo superficie</label>
                    <input type="color" name="color_fondo_superficie" value="{{ $template->color_fondo_superficie ?? '#161618' }}" class="w-full h-10 tpl-surface p-1">
                </div>
                <div>
                    <label class="block text-xs tpl-muted mb-1">Radio de bordes</label>
                    <input type="text" name="radio_bordes" value="{{ $template->radio_bordes ?? '0.75rem' }}" class="w-full px-3 py-2 tpl-surface text-sm tpl-mono">
                </div>
                <input type="hidden" name="color_texto_principal" value="{{ $template->color_texto_principal ?? '#f9fafb' }}">
                <input type="hidden" name="color_texto_secundario" value="{{ $template->color_texto_secundario ?? '#a1a1aa' }}">
                <input type="hidden" name="color_borde" value="{{ $template->color_borde ?? '#27272a' }}">
                <div class="md:col-span-4">
                    <button type="submit" class="px-4 py-2 text-xs font-semibold rounded" style="background-color: var(--tpl-primary); color: #fff;">Guardar identidad y colores</button>
                </div>
            </form>

            <form method="POST" action="{{ route('empresa.autogestion.slides.store') }}" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-4 gap-3 pt-4 border-t" style="border-color: var(--tpl-border);">
                @csrf
                <div class="md:col-span-4"><h3 class="text-sm font-semibold">Agregar Slide al Carrusel o Platillo/Producto Destacado con Foto</h3></div>
                <select name="tipo" class="px-3 py-2 tpl-surface text-sm">
                    <option value="slide">Slide principal (Carrusel)</option>
                    <option value="destacado">Platillo / Producto / Servicio Destacado</option>
                </select>
                <input type="text" name="titulo" required placeholder="Título (ej. Hamburguesa Especial / Combo)" class="px-3 py-2 tpl-surface text-sm">
                <input type="text" name="subtitulo" placeholder="Subtítulo o categoría" class="px-3 py-2 tpl-surface text-sm">
                <input type="file" name="imagen_archivo" accept="image/*" class="text-xs tpl-surface p-1.5">
                <input type="text" name="icono_texto" placeholder="Ícono o etiqueta (ej. NUEVO, 🔥)" class="px-3 py-2 tpl-surface text-sm">
                <input type="number" step="0.01" name="precio" placeholder="Precio (opcional)" min="0" class="px-3 py-2 tpl-surface text-sm tpl-mono">
                <input type="number" name="orden" value="1" min="1" max="999" class="px-3 py-2 tpl-surface text-sm tpl-mono">
                <input type="text" name="descripcion" placeholder="Descripción breve" class="px-3 py-2 tpl-surface text-sm">
                <div class="md:col-span-4">
                    <button type="submit" class="px-4 py-2 text-xs font-semibold rounded" style="background-color: var(--tpl-primary); color: #fff;">Agregar a página pública</button>
                </div>
            </form>

            <div class="divide-y pt-2" style="border-color: var(--tpl-border);">
                @forelse(($elementosPublicos ?? collect()) as $el)
                    <div class="py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-sm">
                        <div class="flex items-center gap-3">
                            @if(!empty($el->imagen_path))
                                <img src="{{ asset('storage/' . $el->imagen_path) }}" alt="{{ $el->titulo }}" class="h-12 w-12 object-cover rounded">
                            @endif
                            <div>
                                <p class="font-semibold">{{ $el->titulo }} <span class="text-xs tpl-muted">[{{ $el->tipo }}]</span></p>
                                <p class="text-xs tpl-muted">{{ $el->subtitulo ?? '' }} • {{ $el->activo ? 'Visible' : 'Oculto' }}</p>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <form method="POST" action="{{ route('empresa.autogestion.slides.toggle', $el->id) }}">
                                @csrf
                                <button type="submit" class="px-3 py-1 text-xs tpl-surface">{{ $el->activo ? 'Ocultar' : 'Mostrar' }}</button>
                            </form>
                            <form method="POST" action="{{ route('empresa.autogestion.slides.destroy', $el->id) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="px-3 py-1 text-xs rounded bg-rose-600/80 text-white">Quitar</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <p class="py-3 text-xs tpl-muted">Aún no has agregado slides ni destacados a la página pública.</p>
                @endforelse
            </div>
        </section>
</body>
</html>
