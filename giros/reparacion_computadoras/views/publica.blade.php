<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $empresa->nombre ?? 'Taller de Reparación de Computadoras' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --taller-primary: {{ $template->color_primario ?? '#6366f1' }};
            --taller-accent: {{ $template->color_acento ?? '#10b981' }};
            --taller-bg: {{ $template->color_fondo_pagina ?? '#09090b' }};
            --taller-card: {{ $template->fondo_tarjeta ?? '#111115' }};
            --taller-surface: {{ $template->color_fondo_superficie ?? '#18181b' }};
            --taller-text: {{ $template->color_texto_principal ?? '#f4f4f5' }};
            --taller-muted: {{ $template->color_texto_secundario ?? '#a1a1aa' }};
            --taller-border: {{ $template->color_borde ?? '#27272a' }};
            --taller-radius: {{ $template->radio_bordes ?? '0.75rem' }};
        }
        body { background-color: var(--taller-bg); color: var(--taller-text); }
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
                    {{ $template->icono_predeterminado ?? '💻' }}
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
        <section class="taller-card p-7 md:p-9 flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
            <div class="space-y-2 max-w-2xl">
                <span class="text-xs font-semibold uppercase tracking-wider" style="color: var(--taller-accent);">Servicio técnico especializado</span>
                <h2 class="text-2xl md:text-3xl font-bold">Diagnóstico, reparación y mantenimiento de laptops y PCs</h2>
                <p class="text-sm" style="color: var(--taller-muted);">
                    {{ $empresa->descripcion ?? 'Atención rápida y transparente para equipos de escritorio, portátiles, cambio de componentes, respaldo de información y optimización del sistema.' }}
                </p>
            </div>
        </section>

        <section class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="taller-card p-5 space-y-1.5">
                <h3 class="font-semibold text-base">Diagnóstico y hardware</h3>
                <p class="text-xs" style="color: var(--taller-muted);">Revisión de fuente, tarjeta madre, pantallas, teclados, baterías y ventilación.</p>
            </div>
            <div class="taller-card p-5 space-y-1.5">
                <h3 class="font-semibold text-base">Almacenamiento y velocidad</h3>
                <p class="text-xs" style="color: var(--taller-muted);">Migración a SSD/NVMe, ampliación de memoria RAM y limpieza térmica preventiva.</p>
            </div>
            <div class="taller-card p-5 space-y-1.5">
                <h3 class="font-semibold text-base">Sistema y respaldo</h3>
                <p class="text-xs" style="color: var(--taller-muted);">Instalación limpia de sistema operativo, controladores, antivirus y respaldo de datos.</p>
            </div>
        </section>

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

        @if($promociones->isNotEmpty())
            <section class="space-y-4">
                <h3 class="text-lg font-semibold">Promociones vigentes</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @foreach($promociones as $promo)
                        <div class="taller-card p-5 space-y-1.5">
                            <p class="font-semibold text-sm">{{ $promo->titulo ?? $promo->nombre ?? 'Promoción' }}</p>
                            <p class="text-xs" style="color: var(--taller-muted);">{{ $promo->descripcion ?? '' }}</p>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif
    </main>
</body>
</html>
