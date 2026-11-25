@extends('plataforma.layout')

@section('title', 'Facturación y Licencias')
@section('header_title', 'Facturación a Clientes y Plazos de Licenciamiento')
@section('header_subtitle', 'Emite cargos/facturas por renta (1, 3, 6, 9 o 12 meses) o paquetes de timbres y administra la vigencia de cada empresa.')

@section('content')
<div class="space-y-6">
    <section class="tpl-card p-6 space-y-4">
        <h2 class="text-lg font-semibold">1. Emitir / Registrar Factura a Cliente</h2>
        <form method="POST" action="{{ route('plataforma.facturacion.store') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @csrf
            <div>
                <label class="block text-xs tpl-muted mb-1">Empresa cliente</label>
                <select name="empresa_id" required class="w-full px-3 py-2 tpl-surface text-sm">
                    <option value="">Selecciona empresa...</option>
                    @foreach($empresas as $emp)
                        <option value="{{ $emp->id }}">
                            {{ $emp->nombre }} (Vence: {{ $emp->fecha_vencimiento_licencia?->format('Y-m-d') ?? 'Sin fecha' }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs tpl-muted mb-1">Folio de factura</label>
                <input type="text" name="folio" required placeholder="CDO-FAC-0001" class="w-full px-3 py-2 tpl-surface text-sm tpl-mono">
            </div>
            <div>
                <label class="block text-xs tpl-muted mb-1">Tipo de concepto</label>
                <select name="concepto_tipo" required class="w-full px-3 py-2 tpl-surface text-sm">
                    <option value="renta_plataforma">Renta de Plataforma</option>
                    <option value="paquete_timbres">Paquete de Timbres</option>
                    <option value="mixto">Renta + Paquete de Timbres</option>
                </select>
            </div>
            <div>
                <label class="block text-xs tpl-muted mb-1">Plazo de renta (si aplica)</label>
                <select name="plazo_meses" class="w-full px-3 py-2 tpl-surface text-sm">
                    @foreach($plazosRenta as $meses => $labelPlazo)
                        <option value="{{ $meses }}">{{ $labelPlazo }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs tpl-muted mb-1">Timbres incluidos (si aplica)</label>
                <input type="number" name="timbres_incluidos" value="0" min="0" class="w-full px-3 py-2 tpl-surface text-sm tpl-mono">
            </div>
            <div>
                <label class="block text-xs tpl-muted mb-1">Estatus inicial</label>
                <select name="estatus" required class="w-full px-3 py-2 tpl-surface text-sm">
                    <option value="pendiente">Pendiente de pago</option>
                    <option value="pagada">Pagada (aplica vigencia/timbres de inmediato)</option>
                </select>
            </div>
            <div>
                <label class="block text-xs tpl-muted mb-1">Subtotal</label>
                <input type="number" step="0.01" name="subtotal" required placeholder="1200.00" class="w-full px-3 py-2 tpl-surface text-sm tpl-mono">
            </div>
            <div>
                <label class="block text-xs tpl-muted mb-1">IVA (opcional, default 16%)</label>
                <input type="number" step="0.01" name="iva" placeholder="192.00" class="w-full px-3 py-2 tpl-surface text-sm tpl-mono">
            </div>
            <div>
                <label class="block text-xs tpl-muted mb-1">Fecha de emisión</label>
                <input type="date" name="fecha_emision" required value="{{ now()->toDateString() }}" class="w-full px-3 py-2 tpl-surface text-sm tpl-mono">
            </div>
            <div class="md:col-span-3">
                <label class="block text-xs tpl-muted mb-1">Notas / Observaciones</label>
                <input type="text" name="notas" placeholder="Renta semestral o paquete de timbres" class="w-full px-3 py-2 tpl-surface text-sm">
            </div>
            <div class="md:col-span-3">
                <button type="submit" class="px-4 py-2 text-sm font-medium tpl-btn-primary">Guardar factura de cliente</button>
            </div>
        </form>
    </section>

    <section class="tpl-card p-6 space-y-4">
        <h2 class="text-lg font-semibold">2. Facturas Emitidas y Vigencia de Licencias</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="border-b" style="border-color: var(--tpl-border);">
                        <th class="py-2.5 px-3 tpl-muted font-medium">Folio</th>
                        <th class="py-2.5 px-3 tpl-muted font-medium">Empresa</th>
                        <th class="py-2.5 px-3 tpl-muted font-medium">Concepto / Plazo</th>
                        <th class="py-2.5 px-3 tpl-muted font-medium">Total</th>
                        <th class="py-2.5 px-3 tpl-muted font-medium">Estatus</th>
                        <th class="py-2.5 px-3 tpl-muted font-medium">Acción</th>
                    </tr>
                </thead>
                <tbody class="divide-y" style="border-color: var(--tpl-border);">
                    @forelse($facturas as $fac)
                        <tr>
                            <td class="py-2.5 px-3 tpl-mono font-semibold">{{ $fac->folio }}</td>
                            <td class="py-2.5 px-3">{{ $fac->empresa?->nombre }}</td>
                            <td class="py-2.5 px-3 text-xs tpl-muted">
                                {{ $fac->concepto_tipo }}
                                @if($fac->plazo_meses) • {{ $fac->plazo_meses }}m @endif
                                @if($fac->timbres_incluidos > 0) • +{{ number_format($fac->timbres_incluidos) }} timbres @endif
                            </td>
                            <td class="py-2.5 px-3 tpl-mono">${{ number_format((float) $fac->total, 2) }}</td>
                            <td class="py-2.5 px-3 text-xs">
                                <span class="{{ $fac->estatus === 'pagada' ? 'tpl-accent font-semibold' : 'text-amber-400' }}">
                                    {{ strtoupper($fac->estatus) }}
                                </span>
                            </td>
                            <td class="py-2.5 px-3">
                                @if($fac->estatus !== 'pagada')
                                    <form method="POST" action="{{ route('plataforma.facturacion.pagar', $fac) }}">
                                        @csrf
                                        <button type="submit" class="px-2.5 py-1 text-xs tpl-btn-primary">Marcar pagada</button>
                                    </form>
                                @else
                                    <span class="text-xs tpl-muted">Aplicada</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-6 px-3 text-center tpl-muted">No hay facturas de clientes registradas todavía.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
