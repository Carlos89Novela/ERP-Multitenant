<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seguimiento {{ $orden['folio'] }} — {{ $empresa->nombre ?? 'Taller' }}</title>
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
        body { font-family: var(--taller-font); background-color: var(--taller-bg); color: var(--taller-text); color-scheme: dark; }
        .taller-card { background-color: var(--taller-card); border: 1px solid var(--taller-border); border-radius: var(--taller-radius); }
        .taller-surface { background-color: var(--taller-surface); border: 1px solid var(--taller-border); border-radius: var(--taller-radius); }
        .taller-input { background-color: var(--taller-surface); border: 1px solid var(--taller-border); border-radius: 0.5rem; color: var(--taller-text); }
    </style>
</head>
<body class="min-h-screen md:h-screen md:overflow-hidden flex flex-col md:flex-row antialiased">
    <aside class="w-full md:w-64 md:h-screen md:sticky md:top-0 shrink-0 border-b md:border-b-0 md:border-r p-5 flex flex-col justify-between overflow-y-auto" style="border-color: var(--taller-border); background-color: var(--taller-card);">
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
                <a href="{{ route('taller.computadoras.dashboard') }}" class="block px-3 py-2 rounded-lg hover:opacity-90" style="background-color: var(--taller-surface);">
                    Dashboard
                </a>
                <a href="{{ route('taller.computadoras.ordenes') }}" class="block px-3 py-2 rounded-lg font-medium" style="background-color: var(--taller-primary); color: #fff;">
                    Órdenes solicitadas
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

    <main class="flex-1 md:h-screen md:overflow-y-auto p-6 md:p-8 space-y-6">
        <header class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <span class="font-mono text-xs px-2.5 py-0.5 rounded" style="background-color: var(--taller-surface); color: var(--taller-accent);">
                        {{ $orden['folio'] }}
                    </span>
                    <span class="text-xs font-semibold uppercase px-2.5 py-0.5 rounded" style="background-color: var(--taller-primary); color: #fff;">
                        {{ $orden['estado'] }}
                    </span>
                </div>
                <h1 class="text-2xl font-bold mt-1">{{ $orden['equipo'] }}</h1>
                <p class="text-xs mt-1" style="color: var(--taller-muted);">
                    Cliente: <strong>{{ $orden['cliente_nombre'] }}</strong>
                    @if(!empty($orden['serie'])) · Serie: <strong>{{ $orden['serie'] }}</strong> @endif
                    · Última actualización: {{ $orden['actualizado_en'] }}
                </p>
            </div>

            <a href="{{ route('taller.computadoras.ordenes') }}" class="px-4 py-2 text-xs font-medium taller-surface">
                ← Volver a Órdenes solicitadas
            </a>
        </header>

                @if(session('error'))
            <div class="taller-card p-4 text-xs font-medium border-red-500 text-red-400">
                {{ session('error') }}
            </div>
        @endif

        @if($errors->any())
            <div class="taller-card p-4 text-xs font-medium border-red-500 text-red-400 space-y-1">
                @foreach($errors->all() as $err)
                    <p>{{ $err }}</p>
                @endforeach
            </div>
        @endif
        @if(session('status'))
                    @endif

<div class="space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-2 pb-2" style="border-bottom: 1px solid var(--taller-border);">
            <div class="flex items-center gap-2" role="tablist">
                <button type="button" id="tab-btn-cotizacion" onclick="activarPestanaOrden('cotizacion')" class="px-4 py-2 rounded-lg text-xs font-semibold text-white cursor-pointer" style="background: #0ea5e9;">
                    Cotización y seguimiento
                </button>
                <button type="button" id="tab-btn-historial" onclick="activarPestanaOrden('historial')" class="px-4 py-2 rounded-lg text-xs font-semibold cursor-pointer" style="background: rgba(255,255,255,0.05); color: var(--taller-muted); border: 1px solid var(--taller-border);">
                    Resumen, bitácora e historial
                </button>
            </div>
            <span class="text-xs" style="color: var(--taller-muted);">
                Responsable actual: <strong style="color: var(--taller-text);">{{ $orden['asignado_a_nombre'] ?? 'Sin asignar' }}</strong>
            </span>
        </div>

        {{-- PESTAÑA 1: COTIZACIÓN Y SEGUIMIENTO --}}
        <div id="tab-panel-cotizacion" class="taller-card p-5 space-y-5">
            <h2 class="text-base font-bold">Registrar seguimiento y cotización</h2>

            <form method="POST" action="{{ route('taller.computadoras.ordenes.actualizar', $orden['folio']) }}" class="space-y-5">
                @csrf

                <div class="p-4 rounded-xl space-y-4" style="background: rgba(255,255,255,0.02); border: 1px solid var(--taller-border);">
                    <p class="text-xs font-semibold uppercase tracking-wider" style="color: var(--taller-muted);">Estado y costos</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs mb-1" style="color: var(--taller-muted);">Estado de la orden</label>
                            <select name="estado" class="w-full px-3 py-2 taller-input text-xs">
                                @foreach(['Recepción', 'Diagnóstico', 'En espera de autorización', 'En reparación', 'En pruebas', 'Listo para entrega', 'Entregado', 'Cancelada'] as $estOp)
                                    <option value="{{ $estOp }}" {{ mb_strtolower($orden['estado']) === mb_strtolower($estOp) ? 'selected' : '' }}>{{ $estOp }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs mb-1" style="color: var(--taller-muted);">Costo estimado ($)</label>
                            <input type="number" step="0.01" min="0" id="costo_estimado_input" name="costo_estimado" value="{{ $orden['costo_estimado'] ?? '' }}" placeholder="0.00" class="w-full px-3 py-2 taller-input text-xs">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-1">
                        <div>
                            <label class="block text-xs mb-1" style="color: var(--taller-muted);">Costo final de cierre / entrega ($)</label>
                            <input type="number" step="0.01" min="0" name="costo_final" value="{{ $orden['costo_final'] ?? '' }}" placeholder="Requerido al marcar Listo para entrega / Entregado" class="w-full px-3 py-2 taller-input text-xs">
                        </div>
                        <div class="flex items-end">
                            <p class="text-[11px]" style="color: var(--taller-muted);">
                                @if(!empty($orden['fecha_entrega']))
                                    Entregado el: <strong style="color: var(--taller-text);">{{ $orden['fecha_entrega'] }}</strong>
                                @else
                                    Al pasar a Entregado se registrará la fecha y hora de entrega.
                                @endif
                            </p>
                        </div>
                    </div>
                </div>

                <details class="p-4 rounded-xl" style="background: rgba(255,255,255,0.02); border: 1px solid var(--taller-border);">
                    <summary class="text-xs font-semibold uppercase tracking-wider cursor-pointer" style="color: var(--taller-muted);">
                        Revisión interna de cotización
                    </summary>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-3">
                        <div>
                            <label class="block text-xs mb-1" style="color: var(--taller-muted);">Estado de revisión interna</label>
                            @php $revActual = $orden['revision_cotizacion'] ?? 'Sin solicitar'; @endphp
                            <select name="revision_cotizacion" class="w-full px-3 py-2 taller-input text-xs">
                                @foreach(['Sin solicitar', 'Pendiente de revisión', 'Aprobada internamente', 'Rechazada'] as $revOp)
                                    <option value="{{ $revOp }}" {{ $revActual === $revOp ? 'selected' : '' }}>{{ $revOp }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs mb-1" style="color: var(--taller-muted);">Observación de revisión / rechazo</label>
                            <input type="text" name="observacion_revision" value="{{ $orden['observacion_revision'] ?? '' }}" placeholder="Requerido si rechazas la cotización" class="w-full px-3 py-2 taller-input text-xs">
                        </div>
                    </div>
                </details>

                <details class="p-4 rounded-xl" open style="background: rgba(255,255,255,0.02); border: 1px solid var(--taller-border);">
                    <summary class="text-xs font-semibold uppercase tracking-wider cursor-pointer" style="color: var(--taller-muted);">
                        Servicios sugeridos por categoría (se suman al costo estimado)
                    </summary>
                    <div class="mt-3 space-y-2">
                        @php
                            $categoriasSugeridas = [
                                'Sistema y Software' => [
                                    'Formateo e instalación limpia de SO ($350.00)',
                                    'Optimización de rendimiento y eliminación de malware ($250.00)',
                                    'Activación/configuración de drivers y utilidades ($180.00)',
                                ],
                                'Limpieza y Mantenimiento' => [
                                    'Limpieza interna profunda y cambio de pasta térmica ($300.00)',
                                    'Mantenimiento preventivo de ventilación y bisagras ($220.00)',
                                ],
                                'Hardware y Diagnóstico' => [
                                    'Diagnóstico avanzado a nivel componente ($200.00)',
                                    'Instalación / clonación de SSD o ampliación de RAM ($250.00)',
                                    'Revisión de centro de carga / batería / pantalla ($200.00)',
                                ],
                                'Respaldo y Recuperación' => [
                                    'Respaldo seguro de información de usuario ($200.00)',
                                    'Recuperación básica de archivos ($350.00)',
                                ],
                            ];
                            $sugActuales = is_array($orden['servicios_sugeridos'] ?? null) ? $orden['servicios_sugeridos'] : [];
                            $motivosActuales = is_array($orden['motivos_servicios_sugeridos'] ?? null) ? $orden['motivos_servicios_sugeridos'] : [];
                        @endphp
                        @foreach($categoriasSugeridas as $catNombre => $itemsCat)
                            <details class="rounded-lg px-3 py-2" style="background: rgba(0,0,0,0.2); border: 1px solid var(--taller-border);">
                                <summary class="text-xs font-medium cursor-pointer flex items-center justify-between">
                                    <span>{{ $catNombre }}</span>
                                    <span class="text-[11px]" style="color: var(--taller-muted);">{{ count($itemsCat) }} servicios</span>
                                </summary>
                                <div class="mt-2 space-y-2 pt-2" style="border-top: 1px solid var(--taller-border);">
                                    @foreach($itemsCat as $srvLbl)
                                        @php
                                            $checked = in_array($srvLbl, $sugActuales, true);
                                            preg_match('/\(\$([0-9]+(?:\.[0-9]{1,2})?)\)/', $srvLbl, $mSrv);
                                            $precioSrv = isset($mSrv[1]) ? (float) $mSrv[1] : 0;
                                        @endphp
                                        <div class="space-y-1">
                                            <label class="flex items-center gap-2 text-xs cursor-pointer">
                                                <input type="checkbox" name="servicios_sugeridos[]" value="{{ $srvLbl }}" data-precio="{{ $precioSrv }}" class="srv-sugerido-chk rounded" {{ $checked ? 'checked' : '' }}>
                                                <span>{{ $srvLbl }}</span>
                                            </label>
                                            <input type="text" name="motivos_servicios[{{ $srvLbl }}]" value="{{ $motivosActuales[$srvLbl] ?? '' }}" placeholder="Motivo / justificación para el cliente (opcional)" class="w-full px-2.5 py-1.5 taller-input text-[11px]">
                                        </div>
                                    @endforeach
                                </div>
                            </details>
                        @endforeach
                    </div>
                </details>

                <div class="p-4 rounded-xl space-y-3" style="background: rgba(255,255,255,0.02); border: 1px solid var(--taller-border);">
                    <p class="text-xs font-semibold uppercase tracking-wider" style="color: var(--taller-muted);">Diagnóstico y seguimiento</p>
                    <div>
                        <label class="block text-xs mb-1" style="color: var(--taller-muted);">Diagnóstico técnico del equipo</label>
                        <textarea name="diagnostico" rows="2" class="w-full px-3 py-2 taller-input text-xs">{{ $orden['diagnostico'] ?? '' }}</textarea>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs mb-1" style="color: var(--taller-muted);">Acción realizada en la orden</label>
                            <input type="text" name="accion_orden" placeholder="Ej. Se notificó al cliente" class="w-full px-3 py-2 taller-input text-xs">
                        </div>
                        <div>
                            <label class="block text-xs mb-1" style="color: var(--taller-muted);">Intervención en el equipo</label>
                            <input type="text" name="accion_equipo" placeholder="Ej. Limpieza interna, pasta térmica, pruebas" class="w-full px-3 py-2 taller-input text-xs">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs mb-1" style="color: var(--taller-muted);">Nota interna (solo taller)</label>
                            <input type="text" name="nota" placeholder="Observaciones internas..." class="w-full px-3 py-2 taller-input text-xs">
                        </div>
                        <div>
                            <label class="block text-xs mb-1" style="color: var(--taller-muted);">Mensaje visible para el cliente</label>
                            <input type="text" name="mensaje_cliente" value="{{ $orden['mensaje_cliente'] ?? '' }}" placeholder="Avance para el portal del cliente..." class="w-full px-3 py-2 taller-input text-xs">
                        </div>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="px-5 py-2.5 rounded-lg text-xs font-semibold text-white cursor-pointer" style="background: #0ea5e9;">
                        Guardar seguimiento
                    </button>
                </div>
            </form>
        </div>

        {{-- PESTAÑA 2: RESUMEN, BITÁCORA E HISTORIAL --}}
        <div id="tab-panel-historial" class="hidden space-y-4">
            <div class="taller-card p-5 space-y-2 text-xs">
                <h2 class="text-base font-bold mb-2">Resumen de orden y equipo</h2>
                <p><span style="color: var(--taller-muted);">Responsable asignado:</span> <strong>{{ $orden['asignado_a_nombre'] ?? 'Sin asignar' }}</strong></p>
                <p><span style="color: var(--taller-muted);">Servicio solicitado:</span> {{ $orden['servicio'] ?? 'Diagnóstico y hardware' }}</p>
                <p><span style="color: var(--taller-muted);">Falla reportada:</span> {{ $orden['falla_reportada'] ?? $orden['detalle'] ?? '—' }}</p>
                <p><span style="color: var(--taller-muted);">Tipo de equipo:</span> {{ $orden['tipo_equipo'] ?? 'Equipo' }}</p>
                <p><span style="color: var(--taller-muted);">Autorización cliente:</span> {{ $orden['autorizacion'] ?? 'Pendiente' }}</p>
                <p><span style="color: var(--taller-muted);">Diagnóstico actual:</span> {{ $orden['diagnostico'] ?? 'Pendiente' }}</p>
            </div>

            <div class="taller-card p-5 space-y-3 text-xs">
                <h2 class="text-base font-bold">Bitácora de acciones en el equipo</h2>
                @if(empty($orden['bitacora_equipo']))
                    <p style="color: var(--taller-muted);">Aún no hay intervenciones registradas en el equipo.</p>
                @else
                    <div class="space-y-2">
                        @foreach(array_reverse($orden['bitacora_equipo']) as $actEq)
                            <div class="p-3 rounded-lg" style="background: rgba(255,255,255,0.02); border: 1px solid var(--taller-border);">
                                <p class="font-medium">{{ $actEq['detalle'] ?? '' }}</p>
                                <p class="text-[11px] mt-1" style="color: var(--taller-muted);">{{ $actEq['fecha'] ?? '' }} · {{ $actEq['usuario'] ?? '' }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="taller-card p-5 space-y-3 text-xs">
                <h2 class="text-base font-bold">Historial completo de seguimiento</h2>
                @if(empty($orden['historial']))
                    <p style="color: var(--taller-muted);">Sin movimientos registrados.</p>
                @else
                    <div class="space-y-2">
                        @foreach(array_reverse($orden['historial']) as $hist)
                            <div class="p-3 rounded-lg flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1" style="background: rgba(255,255,255,0.02); border: 1px solid var(--taller-border);">
                                <div>
                                    <span class="font-semibold uppercase text-[11px]" style="color: #38bdf8;">{{ $hist['estado'] ?? '' }}</span>
                                    <span class="ml-2">{{ $hist['nota'] ?? '' }}</span>
                                </div>
                                <span class="text-[11px] shrink-0" style="color: var(--taller-muted);">{{ $hist['fecha'] ?? '' }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    <script>
        function activarPestanaOrden(pestana) {
            const esCot = pestana === 'cotizacion';
            const pCot = document.getElementById('tab-panel-cotizacion');
            const pHist = document.getElementById('tab-panel-historial');
            const bCot = document.getElementById('tab-btn-cotizacion');
            const bHist = document.getElementById('tab-btn-historial');
            if (pCot) pCot.classList.toggle('hidden', !esCot);
            if (pHist) pHist.classList.toggle('hidden', esCot);
            if (bCot) {
                bCot.style.background = esCot ? '#0ea5e9' : 'rgba(255,255,255,0.05)';
                bCot.style.color = esCot ? '#ffffff' : 'var(--taller-muted)';
            }
            if (bHist) {
                bHist.style.background = !esCot ? '#0ea5e9' : 'rgba(255,255,255,0.05)';
                bHist.style.color = !esCot ? '#ffffff' : 'var(--taller-muted)';
            }
        }
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const chks = document.querySelectorAll('.srv-sugerido-chk');
            const inputCosto = document.getElementById('costo_estimado_input');
            if (!inputCosto || chks.length === 0) return;

            chks.forEach((chk) => {
                chk.addEventListener('change', () => {
                    let suma = 0;
                    chks.forEach((c) => {
                        if (c.checked) {
                            suma += parseFloat(c.dataset.precio || '0') || 0;
                        }
                    });
                    if (suma > 0) {
                        inputCosto.value = suma.toFixed(2);
                    }
                });
            });
        });
    </script>
</body>
</html>
