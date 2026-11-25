@extends('plataforma.layout')

@section('title', 'Alta de Empresas en Renta')
@section('header_title', 'Alta y Configuración de Empresas en Renta')
@section('header_subtitle', 'Crea nuevas empresas con su giro comercial fijo, define qué páginas tendrán habilitadas y genera su cuenta inicial de Propietario.')

@section('content')
<div class="space-y-6">
    <section class="tpl-card p-6 space-y-4">
        <h2 class="text-lg font-semibold">Registrar Nueva Empresa</h2>
        <form method="POST" action="{{ route('plataforma.empresas.store') }}" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @csrf
            <div>
                <label class="block text-xs tpl-muted mb-1">Nombre comercial de la empresa</label>
                <input type="text" name="nombre" required placeholder="Ej. Taller Norte PC" class="w-full px-3 py-2 tpl-surface text-sm">
            </div>
            <div>
                <label class="block text-xs tpl-muted mb-1">RFC (opcional)</label>
                <input type="text" name="rfc" placeholder="XAXX010101000" class="w-full px-3 py-2 tpl-surface text-sm tpl-mono">
            </div>
            <div>
                <label class="block text-xs tpl-muted mb-1">Giro comercial (queda fijo al crear)</label>
                <select id="select-giro-comercial" name="giro_comercial" required onchange="filtrarPaginasPorGiro()" class="w-full px-3 py-2 tpl-surface text-sm">
                    @foreach($girosDisponibles as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs tpl-muted mb-1">Nombre de sucursal matriz</label>
                <input type="text" name="sucursal_nombre" required value="Sucursal Matriz" class="w-full px-3 py-2 tpl-surface text-sm">
            </div>
            <div>
                <label class="block text-xs tpl-muted mb-1">Umbral de alerta por timbres bajos</label>
                <input type="number" name="umbral_alerta_timbres" required value="25" min="1" class="w-full px-3 py-2 tpl-surface text-sm tpl-mono">
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs tpl-muted mb-2">Páginas habilitadas para este cliente</label>
                <div id="grid-paginas-giro" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2">
                    @foreach($paginasCatalogo as $clave => $tituloPagina)
                        <label data-pagina-clave="{{ $clave }}" class="flex items-center gap-2 p-2.5 tpl-surface text-xs cursor-pointer">
                            <input type="checkbox" name="paginas_habilitadas[]" value="{{ $clave }}" checked>
                            <span>{{ $tituloPagina }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="md:col-span-2 pt-3 border-t space-y-3" style="border-color: var(--tpl-border);">
                <p class="text-xs font-semibold tpl-muted uppercase">Cuenta Propietario inicial del cliente (opcional al dar de alta)</p>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs tpl-muted mb-1">Nombre del propietario</label>
                        <input type="text" name="propietario_nombre" placeholder="Ej. Ana López" class="w-full px-3 py-2 tpl-surface text-sm">
                    </div>
                    <div>
                        <label class="block text-xs tpl-muted mb-1">Correo del propietario</label>
                        <input type="email" name="propietario_email" placeholder="ana@empresa.com" class="w-full px-3 py-2 tpl-surface text-sm">
                    </div>
                    <div>
                        <label class="block text-xs tpl-muted mb-1">Contraseña inicial (mín. 8 caracteres)</label>
                        <input type="password" name="propietario_password" placeholder="********" class="w-full px-3 py-2 tpl-surface text-sm">
                    </div>
                </div>
                <label class="inline-flex items-center gap-2 text-xs tpl-muted cursor-pointer">
                    <input type="checkbox" name="puede_autogestionar_template" value="1" checked>
                    <span>Permitir al propietario autogestionar apariencia/bloques de su empresa</span>
                </label>
            </div>

            <div class="md:col-span-2">
                <button type="submit" class="px-4 py-2 text-sm font-medium tpl-btn-primary">Dar de alta empresa y activar monitoreo</button>
            </div>
        </form>
    </section>

    <section class="tpl-card p-6 space-y-3">
        <h2 class="text-lg font-semibold">Empresas dadas de alta</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="border-b" style="border-color: var(--tpl-border);">
                        <th class="py-2.5 px-3 tpl-muted font-medium">Empresa</th>
                        <th class="py-2.5 px-3 tpl-muted font-medium">Giro Fijo</th>
                        <th class="py-2.5 px-3 tpl-muted font-medium">Sucursales</th>
                        <th class="py-2.5 px-3 tpl-muted font-medium">Páginas Habilitadas</th>
                        <th class="py-2.5 px-3 tpl-muted font-medium">Estatus</th>
                    </tr>
                </thead>
                <tbody class="divide-y" style="border-color: var(--tpl-border);">
                    @forelse($empresas as $emp)
                        <tr>
                            <td class="py-2.5 px-3 font-semibold">{{ $emp->nombre }}</td>
                            <td class="py-2.5 px-3 text-xs tpl-mono">{{ $girosDisponibles[$emp->giro_comercial] ?? $emp->giro_comercial }}</td>
                            <td class="py-2.5 px-3 text-xs tpl-muted">{{ $emp->sucursales->count() }}</td>
                            <td class="py-2.5 px-3 text-xs tpl-muted">{{ count($emp->paginas_habilitadas ?? []) }} páginas</td>
                            <td class="py-2.5 px-3 text-xs">
                                <span class="{{ $emp->activa ? 'tpl-accent' : 'text-rose-400' }}">
                                    {{ $emp->activa ? 'Activa' : 'Suspendida' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-6 px-3 text-center tpl-muted">No hay empresas registradas todavía.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script>
    const PAGINAS_POR_GIRO = @json($paginasPorGiro);

    function filtrarPaginasPorGiro() {
        const select = document.getElementById('select-giro-comercial');
        const grid = document.getElementById('grid-paginas-giro');
        if (!select || !grid) return;

        const giro = select.value;
        const permitidas = PAGINAS_POR_GIRO[giro] || [];

        grid.querySelectorAll('[data-pagina-clave]').forEach((item) => {
            const clave = item.getAttribute('data-pagina-clave');
            const checkbox = item.querySelector('input[type="checkbox"]');
            const esPermitida = permitidas.includes(clave);

            if (esPermitida) {
                item.style.display = '';
                if (checkbox) {
                    checkbox.disabled = false;
                    checkbox.checked = true;
                }
            } else {
                item.style.display = 'none';
                if (checkbox) {
                    checkbox.checked = false;
                    checkbox.disabled = true;
                }
            }
        });
    }

    document.addEventListener('DOMContentLoaded', filtrarPaginasPorGiro);
</script>
@endpush
