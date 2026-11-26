<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal de Cliente — {{ $empresa->nombre ?? 'Taller' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @php
            $transpPct = max(0, min(85, (int) ($config['transparencia_flotantes'] ?? 25)));
            $alphaFloat = round((100 - $transpPct) / 100, 2);

            $parseHexRgb = static function (?string $hex, string $fallback = '17, 17, 21'): string {
                $h = ltrim(trim((string) $hex), '#');
                if (strlen($h) === 3) {
                    $h = $h[0].$h[0].$h[1].$h[1].$h[2].$h[2];
                }
                if (strlen($h) !== 6 || ! ctype_xdigit($h)) {
                    return $fallback;
                }
                return hexdec(substr($h, 0, 2)) . ', ' . hexdec(substr($h, 2, 2)) . ', ' . hexdec(substr($h, 4, 2));
            };

            $bgHex = trim((string) ($config['color_fondo'] ?? '#09090b'));
            if ($bgHex === '') { $bgHex = '#09090b'; }
            $cardHex = trim((string) ($config['color_tarjeta'] ?? '#111115'));
            if ($cardHex === '') { $cardHex = '#111115'; }
            $primHex = trim((string) ($config['color_primario'] ?? '#6366f1'));
            if ($primHex === '') { $primHex = '#6366f1'; }
            $accHex = trim((string) ($config['color_acento'] ?? '#10b981'));
            if ($accHex === '') { $accHex = '#10b981'; }

            $bgRgb = $parseHexRgb($bgHex, '9, 9, 11');
            $cardRgb = $parseHexRgb($cardHex, '17, 17, 21');
            $primRgb = $parseHexRgb($primHex, '99, 102, 241');
            $accRgb = $parseHexRgb($accHex, '16, 185, 129');

            $cardRgba = "rgba({$cardRgb}, {$alphaFloat})";
            $surfRgba = "rgba({$cardRgb}, " . max(0.35, round($alphaFloat * 0.85, 2)) . ")";

            $fondoImg = trim((string) ($config['fondo_imagen'] ?? ''));
            $degTipo = (string) ($config['fondo_degradado'] ?? 'oscuro_suave');
            $fondoEstatico = (bool) ($config['fondo_estatico'] ?? true);

            $capas = [];
            if ($degTipo === 'oscuro_suave') {
                $capas[] = "linear-gradient(180deg, rgba({$bgRgb}, 0.55) 0%, rgba({$bgRgb}, 0.80) 100%)";
            } elseif ($degTipo === 'diagonal_primario') {
                $capas[] = "linear-gradient(135deg, rgba({$primRgb}, 0.35) 0%, rgba({$bgRgb}, 0.82) 70%)";
            } elseif ($degTipo === 'horizontal_acento') {
                $capas[] = "linear-gradient(90deg, rgba({$bgRgb}, 0.82) 0%, rgba({$accRgb}, 0.28) 100%)";
            } elseif ($degTipo === 'vertical_profundo') {
                $capas[] = "linear-gradient(180deg, rgba({$bgRgb}, 0.35) 0%, rgba({$bgRgb}, 0.90) 100%)";
            }
            if ($fondoImg !== '') {
                $capas[] = 'url("' . e($fondoImg) . '")';
            }
            $bgImageCss = implode(', ', $capas);
        @endphp
        :root {
            --taller-font: {{ $config['fuente_familia'] ?? 'Inter, system-ui, -apple-system, sans-serif' }};
            --taller-primary: {{ $primHex }};
            --taller-accent: {{ $accHex }};
            --taller-bg: {{ $bgHex }};
            --taller-card: {{ $cardHex }};
            --taller-card-rgba: {{ $cardRgba }};
            --taller-surface: #18181b;
            --taller-surface-rgba: {{ $surfRgba }};
            --taller-text: #f4f4f5;
            --taller-muted: #a1a1aa;
            --taller-border: #27272a;
        }
        html { background-color: #09090b; color-scheme: dark; scroll-behavior: smooth; }
        body {
            font-family: var(--taller-font);
            background-color: var(--taller-bg);
            color: var(--taller-text);
            @if($bgImageCss !== '')
                background-image: {!! $bgImageCss !!};
                background-size: cover;
                background-position: center center;
                background-repeat: no-repeat;
                background-attachment: {{ $fondoEstatico ? 'fixed' : 'scroll' }};
            @endif
        }
        .taller-card { background-color: var(--taller-card-rgba); backdrop-filter: blur(8px); border: 1px solid var(--taller-border); border-radius: 0.75rem; }
        .taller-input { background-color: var(--taller-surface-rgba); backdrop-filter: blur(6px); border: 1px solid var(--taller-border); border-radius: 0.75rem; }
        .taller-btn { background-color: var(--taller-primary); color: #ffffff; border-radius: 0.75rem; }
        ::-webkit-scrollbar { width: 10px; height: 10px; }
        ::-webkit-scrollbar-track { background: var(--taller-bg); }
        ::-webkit-scrollbar-thumb { background: var(--taller-border); border-radius: 9999px; border: 2px solid var(--taller-bg); }
        ::-webkit-scrollbar-thumb:hover { background: var(--taller-primary); }    </style>
</head>
<body class="min-h-screen flex flex-col antialiased">
    <header class="border-b" style="border-color: var(--taller-border); background-color: var(--taller-card);">
        <div class="max-w-5xl mx-auto px-4 py-4 flex items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <span class="h-9 w-9 rounded-lg flex items-center justify-center text-lg" style="background-color: var(--taller-primary);">
                    {{ $config['icono_predeterminado'] ?? '💻' }}
                </span>
                <div>
                    <p class="text-[10px] font-semibold uppercase tracking-wider" style="color: var(--taller-accent);">PORTAL DE CLIENTES</p>
                    <h1 class="text-base font-bold leading-tight">{{ $empresa->nombre ?? 'Taller de Cómputo' }}</h1>
                </div>
            </div>
            <a href="{{ route('taller.computadoras.publica') }}" class="px-3.5 py-1.5 text-xs font-semibold taller-input hover:opacity-90">
                ← Volver a página pública
            </a>
        </div>
    </header>

    <main class="flex-1 w-full max-w-2xl mx-auto px-4 py-8 md:py-12 flex flex-col justify-center items-center space-y-5">
        <div class="flex items-center justify-center gap-2">
            <a href="{{ route('taller.computadoras.cliente.login') }}" class="px-4 py-2 text-xs font-semibold rounded-lg border" style="border-color: var(--taller-border); background-color: {{ ($modo ?? 'login') === 'login' ? 'var(--taller-primary)' : 'var(--taller-card)' }}; color: #fff;">
                Iniciar sesión
            </a>
            <a href="{{ route('taller.computadoras.cliente.registro') }}" class="px-4 py-2 text-xs font-semibold rounded-lg border" style="border-color: var(--taller-border); background-color: {{ ($modo ?? 'login') === 'registro' ? 'var(--taller-primary)' : 'var(--taller-card)' }}; color: #fff;">
                Crear cuenta de cliente
            </a>
        </div>

        @if(session('status'))
            <div class="p-3 rounded border border-emerald-500/60 bg-emerald-950/30 text-xs text-emerald-300">{{ session('status') }}</div>
        @endif

        @if($errors->any())
            <div class="p-3 rounded border border-rose-500/60 bg-rose-950/30 text-xs text-rose-300 space-y-1">
                @foreach($errors->all() as $error) <p>{{ $error }}</p> @endforeach
            </div>
        @endif

                @if(($modo ?? 'login') === 'registro')
            <form method="POST" action="{{ route('taller.computadoras.cliente.registro.store') }}" class="taller-card p-6 space-y-4 w-full max-w-xl mx-auto">
                @csrf
                <div>
                    <h2 class="text-lg font-bold">Registro de cliente</h2>
                    <p class="text-xs" style="color: var(--taller-muted);">Crea tu cuenta para registrar tus equipos y consultar el estado de tus reparaciones.</p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs mb-1" style="color: var(--taller-muted);">Nombre completo</label>
                        <input type="text" name="nombre" value="{{ old('nombre') }}" required class="w-full px-3.5 py-2 taller-input text-sm">
                    </div>
                    <div>
                        <label class="block text-xs mb-1" style="color: var(--taller-muted);">Teléfono / WhatsApp (opcional)</label>
                        <input type="text" name="telefono" value="{{ old('telefono') }}" class="w-full px-3.5 py-2 taller-input text-sm">
                    </div>
                    <div>
                        <label class="block text-xs mb-1" style="color: var(--taller-muted);">Correo electrónico</label>
                        <input type="email" name="email" value="{{ old('email') }}" required class="w-full px-3.5 py-2 taller-input text-sm">
                    </div>
                    <div>
                        <label class="block text-xs mb-1" style="color: var(--taller-muted);">Contraseña (mín. 6 caracteres)</label>
                        <input type="password" name="password" required class="w-full px-3.5 py-2 taller-input text-sm">
                    </div>
                </div>
                <div class="flex flex-wrap items-center justify-between gap-3 pt-1">
                    <button type="submit" class="px-5 py-2.5 text-xs font-semibold taller-btn hover:opacity-95">Registrarme</button>
                    <a href="{{ route('taller.computadoras.cliente.login') }}" class="text-xs underline" style="color: var(--taller-accent);">Ya tengo cuenta — Iniciar sesión</a>
                </div>
            </form>
        @else
            <form method="POST" action="{{ route('taller.computadoras.cliente.login.store') }}" class="taller-card p-6 space-y-4 w-full max-w-md mx-auto">
                @csrf
                <div>
                    <h2 class="text-lg font-bold">Iniciar sesión</h2>
                    <p class="text-xs" style="color: var(--taller-muted);">Ingresa con tu correo y contraseña para ver tus equipos y estados.</p>
                </div>
                <div>
                    <label class="block text-xs mb-1" style="color: var(--taller-muted);">Correo electrónico</label>
                    <input type="email" name="email" value="{{ old('email') }}" required class="w-full px-3.5 py-2 taller-input text-sm">
                </div>
                <div class="space-y-2">
                    <div>
                        <label class="block text-xs mb-1" style="color: var(--taller-muted);">Contraseña</label>
                        <input type="password" name="password" required class="w-full px-3.5 py-2 taller-input text-sm">
                    </div>
                    <div class="flex items-center justify-between text-xs pt-0.5">
                        <button type="button" onclick="alert('La recuperación de contraseña se implementará más adelante.')" class="underline hover:opacity-80" style="color: var(--taller-muted);">
                            ¿Olvidaste la contraseña?
                        </button>
                        <a href="{{ route('taller.computadoras.cliente.registro') }}" class="font-semibold underline" style="color: var(--taller-accent);">
                            Registrarse
                        </a>
                    </div>
                </div>
                <button type="submit" class="px-5 py-2.5 text-xs font-semibold taller-btn hover:opacity-95">Entrar</button>
            </form>
        @endif
    </main>
    <script>
        (function () {
            if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
            if ('ontouchstart' in window || navigator.maxTouchPoints > 0) return;
            let current = window.scrollY;
            let desired = current;
            let rafId = null;
            const ease = 0.065;

            function step() {
                const actual = window.scrollY;
                if (Math.abs(actual - current) > 3) { current = actual; desired = actual; }
                const maxScroll = Math.max(0, document.documentElement.scrollHeight - window.innerHeight);
                desired = Math.max(0, Math.min(maxScroll, desired));
                current += (desired - current) * ease;
                if (Math.abs(desired - current) < 0.35) {
                    window.scrollTo(0, Math.round(desired));
                    rafId = null;
                    return;
                }
                window.scrollTo(0, current);
                rafId = window.requestAnimationFrame(step);
            }

            document.addEventListener('wheel', function (e) {
                if (e.ctrlKey || e.metaKey || e.altKey) return;
                if (e.target && e.target.closest('input[type="range"], textarea, select')) return;
                const maxScroll = Math.max(0, document.documentElement.scrollHeight - window.innerHeight);
                if (maxScroll <= 2) return;
                e.preventDefault();
                if (rafId === null) { current = window.scrollY; desired = current; }
                desired = Math.max(0, Math.min(maxScroll, desired + (e.deltaY * 0.85)));
                if (rafId === null) rafId = window.requestAnimationFrame(step);
            }, { passive: false });
        })();
    </script>
</body>
</html>
