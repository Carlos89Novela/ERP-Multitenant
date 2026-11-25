@extends('plataforma.layout')

@section('title', 'Gestión de Timbres')
@section('header_title', 'Bolsa Mayorista y Asignación de Timbres')
@section('header_subtitle', 'Registra tus compras de timbres al por mayor y asígnalos a las empresas clientes conforme los adquieran.')

@section('content')
<section class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="tpl-card p-6 space-y-4">
        <h2 class="text-lg font-semibold">1. Comprar Lote de Timbres al por Mayor</h2>
        <form method="POST" action="{{ route('plataforma.timbres.lote') }}" class="space-y-3">
            @csrf
            <div>
                <label class="block text-xs tpl-muted mb-1">Folio / Factura de compra mayorista</label>
                <input type="text" name="folio_compra" required placeholder="PAC-2026-001" class="w-full px-3 py-2 tpl-surface text-sm tpl-mono">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs tpl-muted mb-1">Proveedor PAC</label>
                    <input type="text" name="proveedor" placeholder="Finkok / SW / Diverza" class="w-full px-3 py-2 tpl-surface text-sm">
                </div>
                <div>
                    <label class="block text-xs tpl-muted mb-1">Cantidad de timbres</label>
                    <input type="number" name="cantidad_comprada" required min="1" placeholder="5000" class="w-full px-3 py-2 tpl-surface text-sm tpl-mono">
                </div>
            </div>
            <div>
                <label class="block text-xs tpl-muted mb-1">Costo unitario (opcional)</label>
                <input type="number" step="0.0001" name="costo_unitario" placeholder="0.3500" class="w-full px-3 py-2 tpl-surface text-sm tpl-mono">
            </div>
            <button type="submit" class="px-4 py-2 text-sm font-medium tpl-btn-primary">Registrar compra en mi bolsa</button>
        </form>

        <div class="pt-3 border-t space-y-2" style="border-color: var(--tpl-border);">
            <p class="text-xs font-semibold tpl-muted uppercase">Lotes en bolsa mayorista</p>
            @forelse($lotesTimbres as $lote)
                <div class="p-2.5 tpl-surface flex justify-between items-center text-xs">
                    <div>
                        <span class="font-semibold tpl-mono">{{ $lote->folio_compra }}</span>
                        <span class="tpl-muted">({{ $lote->proveedor }})</span>
                    </div>
                    <span class="tpl-mono tpl-accent font-semibold">
                        {{ number_format($lote->cantidad_disponible) }} / {{ number_format($lote->cantidad_comprada) }}
                    </span>
                </div>
            @empty
                <p class="text-xs tpl-muted">Aún no has registrado lotes mayoristas.</p>
            @endforelse
        </div>
    </div>

    <div class="tpl-card p-6 space-y-4">
        <h2 class="text-lg font-semibold">2. Asignar Timbres a una Empresa</h2>
        <form method="POST" action="{{ route('plataforma.timbres.asignar') }}" class="space-y-3">
            @csrf
            <div>
                <label class="block text-xs tpl-muted mb-1">Empresa destino</label>
                <select name="empresa_id" required class="w-full px-3 py-2 tpl-surface text-sm">
                    <option value="">Selecciona empresa...</option>
                    @foreach($empresas as $emp)
                        <option value="{{ $emp->id }}">{{ $emp->nombre }} (Saldo actual: {{ $emp->saldo_timbres }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs tpl-muted mb-1">Lote mayorista de origen</label>
                <select name="lote_id" required class="w-full px-3 py-2 tpl-surface text-sm">
                    <option value="">Selecciona lote...</option>
                    @foreach($lotesTimbres->where('cantidad_disponible', '>', 0) as $lote)
                        <option value="{{ $lote->id }}">{{ $lote->folio_compra }} — Disponibles: {{ number_format($lote->cantidad_disponible) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs tpl-muted mb-1">Cantidad a asignar</label>
                <input type="number" name="cantidad" required min="1" placeholder="200" class="w-full px-3 py-2 tpl-surface text-sm tpl-mono">
            </div>
            <div>
                <label class="block text-xs tpl-muted mb-1">Concepto / Referencia de pago</label>
                <input type="text" name="motivo" placeholder="Paquete 200 timbres — Transferencia #45" class="w-full px-3 py-2 tpl-surface text-sm">
            </div>
            <button type="submit" class="px-4 py-2 text-sm font-medium tpl-btn-primary">Asignar timbres ahora</button>
        </form>

        <div class="pt-3 border-t space-y-2" style="border-color: var(--tpl-border);">
            <p class="text-xs font-semibold tpl-muted uppercase">Historial de asignaciones</p>
            @forelse($asignacionesRecientes as $asig)
                <div class="p-2.5 tpl-surface flex justify-between items-center text-xs">
                    <div>
                        <span class="font-semibold">{{ $asig->empresa?->nombre }}</span>
                        <span class="tpl-muted">• {{ $asig->motivo }}</span>
                    </div>
                    <span class="tpl-mono tpl-accent">+{{ number_format($asig->cantidad) }}</span>
                </div>
            @empty
                <p class="text-xs tpl-muted">Sin asignaciones registradas todavía.</p>
            @endforelse
        </div>
    </div>
</section>
@endsection
