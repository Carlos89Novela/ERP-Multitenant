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
    </style>
</head>
<body class="min-h-screen flex antialiased">
    <aside class="w-64 shrink-0 border-r p-5 flex flex-col justify-between" style="border-color: var(--taller-border); background-color: var(--taller-card);">
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

    <main class="flex-1 p-6 md:p-8 space-y-6">
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
                    <p class="text-xs" style="color: var(--taller-muted);">Equipo activo</p>
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
                                @foreach($ultimasOrdenes as $orden)
                                    <tr>
                                        <td class="py-2.5 pr-3 font-mono">{{ $orden->folio ?? ('#' . $orden->id) }}</td>
                                        <td class="py-2.5 pr-3">{{ $orden->cliente_nombre ?? '—' }}</td>
                                        <td class="py-2.5 pr-3">{{ $orden->equipo_tipo ?? $orden->equipo ?? '—' }}</td>
                                        <td class="py-2.5 pr-3 uppercase">{{ $orden->estado ?? 'recibida' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        @endif
    </main>
</body>
</html>
