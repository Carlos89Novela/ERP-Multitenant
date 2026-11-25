@extends('plataforma.layout')

@section('title', 'Template y Operador')
@section('header_title', 'Template Administrable y Cuenta de Operador')
@section('header_subtitle', 'Elige o personaliza el diseño visual de tus páginas de plataforma y administra tu cuenta de operador.')

@section('content')
<div class="space-y-6">
    <section class="tpl-card p-6 space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2 class="text-lg font-semibold">1. Personalizar Template de Plataforma</h2>
                <p class="text-xs tpl-muted">Selecciona un preset rápido o ajusta colores, tipografía, tamaños, bordes y fondo en BD.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <button type="button" onclick="aplicarPreset('indigo')" class="px-2.5 py-1 text-xs tpl-surface">Preset Índigo Oscuro</button>
                <button type="button" onclick="aplicarPreset('esmeralda')" class="px-2.5 py-1 text-xs tpl-surface">Preset Esmeralda</button>
                <button type="button" onclick="aplicarPreset('ejecutivo')" class="px-2.5 py-1 text-xs tpl-surface">Preset Ámbar Ejecutivo</button>
                <button type="button" onclick="aplicarPreset('claro')" class="px-2.5 py-1 text-xs tpl-surface">Preset Claro</button>
            </div>
        </div>

        <form id="form-template-plataforma" method="POST" action="{{ route('plataforma.template.guardar') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            @csrf
            <div>
                <label class="block text-xs tpl-muted mb-1">Fuente principal</label>
                <input type="text" name="fuente_primaria" value="{{ $template->fuente_primaria }}" class="w-full px-3 py-2 tpl-surface text-xs">
            </div>
            <div>
                <label class="block text-xs tpl-muted mb-1">Fuente secundaria (código/folios)</label>
                <input type="text" name="fuente_secundaria" value="{{ $template->fuente_secundaria }}" class="w-full px-3 py-2 tpl-surface text-xs">
            </div>
            <div>
                <label class="block text-xs tpl-muted mb-1">Tamaño base (px)</label>
                <input type="number" name="tamano_base_px" min="11" max="22" value="{{ $template->tamano_base_px }}" class="w-full px-3 py-2 tpl-surface text-xs">
            </div>
            <div>
                <label class="block text-xs tpl-muted mb-1">Radio de bordes</label>
                <input type="text" name="radio_bordes" value="{{ $template->radio_bordes }}" class="w-full px-3 py-2 tpl-surface text-xs">
            </div>

            <div>
                <label class="block text-xs tpl-muted mb-1">Color primario</label>
                <input type="color" name="color_primario" value="{{ $template->color_primario }}" class="w-full h-9 tpl-surface">
            </div>
            <div>
                <label class="block text-xs tpl-muted mb-1">Color secundario</label>
                <input type="color" name="color_secundario" value="{{ $template->color_secundario }}" class="w-full h-9 tpl-surface">
            </div>
            <div>
                <label class="block text-xs tpl-muted mb-1">Color acento</label>
                <input type="color" name="color_acento" value="{{ $template->color_acento }}" class="w-full h-9 tpl-surface">
            </div>
            <div>
                <label class="block text-xs tpl-muted mb-1">Color borde</label>
                <input type="color" name="color_borde" value="{{ $template->color_borde }}" class="w-full h-9 tpl-surface">
            </div>

            <div>
                <label class="block text-xs tpl-muted mb-1">Fondo página</label>
                <input type="color" name="color_fondo_pagina" value="{{ $template->color_fondo_pagina }}" class="w-full h-9 tpl-surface">
            </div>
            <div>
                <label class="block text-xs tpl-muted mb-1">Fondo superficie</label>
                <input type="color" name="color_fondo_superficie" value="{{ $template->color_fondo_superficie }}" class="w-full h-9 tpl-surface">
            </div>
            <div>
                <label class="block text-xs tpl-muted mb-1">Fondo tarjeta</label>
                <input type="color" name="fondo_tarjeta" value="{{ $template->fondo_tarjeta }}" class="w-full h-9 tpl-surface">
            </div>
            <div>
                <label class="block text-xs tpl-muted mb-1">Texto principal</label>
                <input type="color" name="color_texto_principal" value="{{ $template->color_texto_principal }}" class="w-full h-9 tpl-surface">
            </div>

            <div>
                <label class="block text-xs tpl-muted mb-1">Texto secundario</label>
                <input type="color" name="color_texto_secundario" value="{{ $template->color_texto_secundario }}" class="w-full h-9 tpl-surface">
            </div>
            <div>
                <label class="block text-xs tpl-muted mb-1">Tipo de fondo</label>
                <select name="tipo_fondo" class="w-full px-3 py-2 tpl-surface text-xs">
                    <option value="solido" @selected($template->tipo_fondo === 'solido')>Sólido</option>
                    <option value="degradado" @selected($template->tipo_fondo === 'degradado')>Degradado CSS</option>
                    <option value="imagen" @selected($template->tipo_fondo === 'imagen')>Imagen URL</option>
                </select>
            </div>
            <div>
                <label class="block text-xs tpl-muted mb-1">Valor fondo (degradado o URL)</label>
                <input type="text" name="valor_fondo" value="{{ $template->valor_fondo }}" placeholder="linear-gradient(135deg, #09090b, #1e1b4b)" class="w-full px-3 py-2 tpl-surface text-xs">
            </div>
            <div>
                <label class="block text-xs tpl-muted mb-1">Estilo de íconos</label>
                <select name="estilo_icono" class="w-full px-3 py-2 tpl-surface text-xs">
                    <option value="heroicons" @selected($template->estilo_icono === 'heroicons')>Heroicons Outline</option>
                    <option value="lucide" @selected($template->estilo_icono === 'lucide')>Lucide Minimal</option>
                    <option value="solid" @selected($template->estilo_icono === 'solid')>Solid Bold</option>
                </select>
            </div>

            <div class="sm:col-span-2 lg:col-span-4">
                <button type="submit" class="px-4 py-2 text-sm font-medium tpl-btn-primary">Guardar template de plataforma en BD</button>
            </div>
        </form>
    </section>

    <section class="tpl-card p-6 space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-lg font-semibold">2. Cuenta de Operador de Plataforma</h2>
                <p class="text-xs tpl-muted">Operadores registrados actualmente: <span class="tpl-mono tpl-accent font-semibold">{{ $operadoresCount }}</span> • El primer operador queda como <span class="tpl-accent font-semibold">Cuenta Principal Inmutable</span> con acceso total.</p>
            </div>
        </div>
        <form method="POST" action="{{ route('plataforma.operador.store') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @csrf
            <div>
                <label class="block text-xs tpl-muted mb-1">Nombre del operador</label>
                <input type="text" name="name" required placeholder="Carlos Márquez" class="w-full px-3 py-2 tpl-surface text-sm">
            </div>
            <div>
                <label class="block text-xs tpl-muted mb-1">Correo electrónico</label>
                <input type="email" name="email" required placeholder="operador@plataforma.local" class="w-full px-3 py-2 tpl-surface text-sm">
            </div>
            <div>
                <label class="block text-xs tpl-muted mb-1">Contraseña (mín. 8 caracteres)</label>
                <input type="password" name="password" required placeholder="********" class="w-full px-3 py-2 tpl-surface text-sm">
            </div>
            <div class="md:col-span-3">
                <button type="submit" class="px-4 py-2 text-sm font-medium tpl-btn-primary">Registrar operador de plataforma</button>
            </div>
        </form>
    </section>
</div>
@endsection

@push('scripts')
<script>
    const PRESETS = {
        indigo: {
            color_primario: '#6366f1', color_secundario: '#3b82f6', color_acento: '#10b981',
            color_fondo_pagina: '#09090b', color_fondo_superficie: '#18181b', fondo_tarjeta: '#111115',
            color_texto_principal: '#f4f4f5', color_texto_secundario: '#a1a1aa', color_borde: '#27272a',
            radio_bordes: '0.75rem'
        },
        esmeralda: {
            color_primario: '#059669', color_secundario: '#0d9488', color_acento: '#34d399',
            color_fondo_pagina: '#041311', color_fondo_superficie: '#0b221e', fondo_tarjeta: '#071916',
            color_texto_principal: '#ecfdf5', color_texto_secundario: '#a7f3d0', color_borde: '#134e4a',
            radio_bordes: '0.625rem'
        },
        ejecutivo: {
            color_primario: '#d97706', color_secundario: '#b45309', color_acento: '#fbbf24',
            color_fondo_pagina: '#0c0a09', color_fondo_superficie: '#1c1917', fondo_tarjeta: '#141210',
            color_texto_principal: '#fafaf9', color_texto_secundario: '#a8a29e', color_borde: '#292524',
            radio_bordes: '0.5rem'
        },
        claro: {
            color_primario: '#2563eb', color_secundario: '#1d4ed8', color_acento: '#059669',
            color_fondo_pagina: '#f8fafc', color_fondo_superficie: '#f1f5f9', fondo_tarjeta: '#ffffff',
            color_texto_principal: '#0f172a', color_texto_secundario: '#475569', color_borde: '#cbd5e1',
            radio_bordes: '0.75rem'
        }
    };

    function aplicarPreset(nombre) {
        const p = PRESETS[nombre];
        if (!p) return;
        const form = document.getElementById('form-template-plataforma');
        Object.entries(p).forEach(([k, v]) => {
            const input = form.querySelector(`[name="${k}"]`);
            if (input) input.value = v;
        });
    }
</script>
@endpush
