<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambio de Contraseña Temporal — {{ $empresa->nombre ?? 'Taller' }}</title>
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
<body class="min-h-screen flex items-center justify-center p-4 antialiased">
<div class="w-full max-w-md taller-card p-6 space-y-5">
    <div>
        <p class="text-[11px] font-semibold uppercase tracking-wider" style="color: var(--taller-accent);">
            SEGURIDAD DE CUENTA — CONTRASEÑA TEMPORAL
        </p>
        <h1 class="text-xl font-bold mt-1">Establece tu nueva contraseña</h1>
        <p class="text-xs mt-1" style="color: var(--taller-muted);">
            Iniciaste sesión como <span class="font-semibold text-white">{{ $usuario->email }}</span> usando una contraseña temporal. Por seguridad debes definir una nueva contraseña antes de continuar.
        </p>
    </div>

    @if ($errors->any())
        <div class="rounded-lg border border-rose-500/50 bg-rose-950/40 px-4 py-3 text-xs text-rose-200">
            <ul class="list-disc pl-4 space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('taller.computadoras.password.temporal.guardar') }}" class="space-y-4">
        @csrf
        <div>
            <label class="block text-xs mb-1" style="color: var(--taller-muted);">Nueva contraseña (mínimo 8 caracteres)</label>
            <input type="password" name="password" required minlength="8" class="w-full px-3 py-2 taller-surface text-sm focus:outline-none">
        </div>
        <div>
            <label class="block text-xs mb-1" style="color: var(--taller-muted);">Confirmar nueva contraseña</label>
            <input type="password" name="password_confirmation" required minlength="8" class="w-full px-3 py-2 taller-surface text-sm focus:outline-none">
        </div>
        <button type="submit" class="w-full py-2.5 px-4 rounded-lg text-sm font-semibold" style="background-color: var(--taller-primary); color: #fff;">
            Guardar nueva contraseña y continuar
        </button>
    </form>

    <form method="POST" action="{{ route('taller.computadoras.logout') }}" class="pt-2 border-t" style="border-color: var(--taller-border);">
        @csrf
        <button type="submit" class="w-full py-2 px-3 rounded-lg text-xs taller-surface hover:opacity-90">
            Cerrar sesión
        </button>
    </form>
</div>
</body>
</html>