@extends('plataforma.layout')

@section('title', 'Monitoreo de Servicios')
@section('header_title', 'Monitoreo Técnico de Páginas y Timbres')
@section('header_subtitle', 'Supervisión exclusiva de disponibilidad, latencia, páginas habilitadas y saldo de timbres. Cero visibilidad de ventas, clientes u órdenes internas.')

@section('content')
<section class="tpl-card p-6 space-y-4">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
        <div>
            <h2 class="text-lg font-semibold">Estado en tiempo real de instancias en renta</h2>
            <p class="text-xs tpl-muted">Verifica disponibilidad de página, timbres reportados y latencia sin acceder a los datos privados de cada negocio.</p>
        </div>
        <a href="{{ route('plataforma.empresas') }}" class="px-3.5 py-2 text-xs font-medium tpl-btn-primary">
            + Dar de alta nueva empresa
        </a>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse text-sm">
            <thead>
                <tr class="border-b" style="border-color: var(--tpl-border);">
                    <th class="py-3 px-3 tpl-muted font-medium">Empresa / Giro</th>
                    <th class="py-3 px-3 tpl-muted font-medium">Estado Página</th>
                    <th class="py-3 px-3 tpl-muted font-medium">Páginas Habilitadas</th>
                    <th class="py-3 px-3 tpl-muted font-medium">Timbres</th>
                    <th class="py-3 px-3 tpl-muted font-medium">Latencia / Último Latido</th>
                    <th class="py-3 px-3 tpl-muted font-medium">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y" style="border-color: var(--tpl-border);">
                @forelse($empresas as $emp)
                    @php
                        $mon = $emp->monitoreo;
                        $estadoPagina = $mon->estado_pagina ?? ($emp->activa ? 'activo' : 'inactivo');
                        $estadoTimbres = $mon->estado_timbres ?? ($emp->saldo_timbres <= 0 ? 'agotado' : ($emp->saldo_timbres <= $emp->umbral_alerta_timbres ? 'bajo' : 'ok'));
                    @endphp
                    <tr>
                        <td class="py-3 px-3">
                            <div class="font-semibold">{{ $emp->nombre }}</div>
                            <div class="text-xs tpl-muted">
                                Giro fijo: <span class="tpl-mono">{{ $girosDisponibles[$emp->giro_comercial] ?? $emp->giro_comercial }}</span>
                                @if($emp->rfc) • RFC: <span class="tpl-mono">{{ $emp->rfc }}</span> @endif
                            </div>
                        </td>
                        <td class="py-3 px-3">
                            @if($estadoPagina === 'activo')
                                <span class="px-2 py-0.5 text-xs rounded-full bg-emerald-500/20 text-emerald-300">Activo</span>
                            @elseif($estadoPagina === 'degradado')
                                <span class="px-2 py-0.5 text-xs rounded-full bg-amber-500/20 text-amber-300">Degradado</span>
                            @else
                                <span class="px-2 py-0.5 text-xs rounded-full bg-rose-500/20 text-rose-300">Inactivo</span>
                            @endif
                        </td>
                        <td class="py-3 px-3">
                            <div class="flex flex-wrap gap-1">
                                @foreach(($emp->paginas_habilitadas ?? []) as $pag)
                                    <span class="px-2 py-0.5 text-xs tpl-surface">{{ $paginasCatalogo[$pag] ?? $pag }}</span>
                                @endforeach
                            </div>
                        </td>
                        <td class="py-3 px-3 tpl-mono">
                            <span class="font-semibold">{{ number_format($emp->saldo_timbres) }}</span>
                            <span class="ml-1 text-xs {{ $estadoTimbres === 'ok' ? 'text-emerald-400' : ($estadoTimbres === 'bajo' ? 'text-amber-400' : 'text-rose-400') }}">
                                ({{ strtoupper($estadoTimbres) }})
                            </span>
                        </td>
                        <td class="py-3 px-3 text-xs tpl-muted">
                            <div>{{ $mon?->latencia_ms ? $mon->latencia_ms . ' ms' : 'Sin ping' }} • {{ $mon?->ultimo_latido_at ? $mon->ultimo_latido_at->diffForHumans() : 'Nunca' }}</div>
                            <div class="truncate max-w-xs">{{ $mon?->ultimo_evento_servicio ?? 'Sin eventos' }}</div>
                        </td>
                        <td class="py-3 px-3">
                            <div class="flex flex-wrap gap-2">
                                <form method="POST" action="{{ route('plataforma.empresas.ping', $emp) }}">
                                    @csrf
                                    <button type="submit" class="px-2.5 py-1 text-xs tpl-surface hover:opacity-90">Verificar Ping</button>
                                </form>
                                <form method="POST" action="{{ route('plataforma.empresas.servicio', $emp) }}">
                                    @csrf
                                    <input type="hidden" name="activa" value="{{ $emp->activa ? 0 : 1 }}">
                                    @foreach(($emp->paginas_habilitadas ?? []) as $p)
                                        <input type="hidden" name="paginas_habilitadas[]" value="{{ $p }}">
                                    @endforeach
                                    <button type="submit" class="px-2.5 py-1 text-xs rounded {{ $emp->activa ? 'bg-rose-600/80 text-white' : 'bg-emerald-600/80 text-white' }}">
                                        {{ $emp->activa ? 'Suspender' : 'Activar' }}
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-8 px-3 text-center tpl-muted">
                            No hay empresas registradas todavía (base limpia). Ve a <a href="{{ route('plataforma.empresas') }}" class="underline tpl-accent">Alta de Empresas</a> para crear la primera.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
