<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personalizar Correos por Sucursal — {{ $empresa->nombre ?? 'Taller' }}</title>
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
<body class="min-h-screen p-4 md:p-8 antialiased">
<div class="max-w-6xl mx-auto space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4 border-b pb-4" style="border-color: var(--taller-border);">
        <div>
            <p class="text-[11px] font-semibold uppercase tracking-wider" style="color: var(--taller-accent);">
                EXCLUSIVO PROPIETARIO — DOMINIO FIJO @{{ $dominio }}
            </p>
            <h1 class="text-2xl font-bold">Personalizar Correos de Usuarios por Sucursal</h1>
            <p class="text-xs mt-1" style="color: var(--taller-muted);">
                Puedes personalizar el correo de cada usuario sin modificar su rol ni el dominio @{{ $dominio }}.
            </p>
        </div>
        <a href="{{ route('taller.computadoras.dashboard') }}" class="px-3 py-2 rounded-lg text-xs taller-surface hover:opacity-90">← Dashboard</a>
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-emerald-500/50 bg-emerald-950/40 px-4 py-3 text-sm text-emerald-200">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="rounded-lg border border-rose-500/50 bg-rose-950/40 px-4 py-3 text-sm text-rose-200">
            <ul class="list-disc pl-5 space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="taller-card p-5">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-xs">
                <thead class="border-b uppercase" style="border-color: var(--taller-border); color: var(--taller-muted);">
                    <tr>
                        <th class="py-2.5 pr-3">Sucursal (ID)</th>
                        <th class="py-2.5 px-3">Usuario</th>
                        <th class="py-2.5 px-3">Rol (fijo)</th>
                        <th class="py-2.5 px-3">Correo actual</th>
                        <th class="py-2.5 pl-3">Nuevo correo (@{{ $dominio }})</th>
                    </tr>
                </thead>
                <tbody class="divide-y" style="border-color: var(--taller-border);">
                    @foreach ($usuarios as $u)
                        @php
                            $suc = $u->sucursal_id ? ($sucursales[$u->sucursal_id] ?? null) : null;
                            $localActual = str_contains((string) $u->email, '@') ? explode('@', (string) $u->email, 2)[0] : $u->email;
                        @endphp
                        <tr>
                            <td class="py-3 pr-3 font-mono">
                                @if ($u->sucursal_id)
                                    #{{ $u->sucursal_id }} — {{ $suc?->nombre ?? 'Sucursal' }}
                                @else
                                    Matriz / General
                                @endif
                            </td>
                            <td class="py-3 px-3 font-medium">{{ $u->name }}</td>
                            <td class="py-3 px-3 uppercase font-semibold" style="color: var(--taller-accent);">{{ $u->rol_empresa }}</td>
                            <td class="py-3 px-3 font-mono">{{ $u->email }}</td>
                            <td class="py-3 pl-3">
                                <form method="POST" action="{{ route('taller.computadoras.correos.actualizar', $u->id) }}" class="flex items-center gap-2">
                                    @csrf
                                    <div class="flex items-center rounded-lg overflow-hidden border" style="border-color: var(--taller-border); background-color: var(--taller-surface);">
                                        <input
                                            type="text"
                                            name="correo_o_usuario"
                                            value="{{ $localActual }}"
                                            required
                                            class="px-2.5 py-1.5 bg-transparent text-xs font-mono w-44 focus:outline-none"
                                        >
                                        <span class="px-2 py-1.5 text-xs font-mono border-l" style="border-color: var(--taller-border); color: var(--taller-muted);">@{{ $dominio }}</span>
                                    </div>
                                    <button type="submit" class="px-3 py-1.5 rounded-lg text-xs font-semibold" style="background-color: var(--taller-primary); color: #fff;">
                                        Guardar
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>