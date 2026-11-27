<?php

namespace Giros\ReparacionComputadoras\Services;

use Giros\ReparacionComputadoras\Models\AlmacenTaller;
use Giros\ReparacionComputadoras\Models\ExistenciaAlmacenTaller;
use Giros\ReparacionComputadoras\Models\MovimientoInventarioTaller;
use Giros\ReparacionComputadoras\Models\ProductoTaller;
use Illuminate\Support\Collection;

class ConsultaExistenciasTallerService
{
    /**
     * Lista existencias detalladas filtrables por sucursal, almacén, producto o búsqueda.
     *
     * @param  array{sucursal_id?: mixed, almacen_id?: mixed, producto_id?: mixed, buscar?: mixed}  $filtros
     * @return Collection<int, array<string, mixed>>
     */
    public function listarExistencias(array $filtros = []): Collection
    {
        ContextoPropietarioTaller::conexion();

        $sucursalId = filter_var($filtros['sucursal_id'] ?? null, FILTER_VALIDATE_INT);
        $almacenId = filter_var($filtros['almacen_id'] ?? null, FILTER_VALIDATE_INT);
        $productoId = filter_var($filtros['producto_id'] ?? null, FILTER_VALIDATE_INT);
        $buscar = is_string($filtros['buscar'] ?? null) ? trim((string) $filtros['buscar']) : '';

        $query = ExistenciaAlmacenTaller::query()
            ->with([
                'producto:id,sku,nombre,unidad_medida,precio_venta,activo',
                'almacen:id,sucursal_id,codigo,nombre,tipo,es_virtual,activo',
                'almacen.sucursal:id,codigo,nombre,es_matriz,activa',
            ])
            ->whereHas('almacen', function ($q) use ($sucursalId, $almacenId): void {
                $q->where('activo', true);
                if ($almacenId !== false && (int) $almacenId > 0) {
                    $q->where('id', (int) $almacenId);
                }
                if ($sucursalId !== false && (int) $sucursalId > 0) {
                    $q->where('sucursal_id', (int) $sucursalId);
                }
            });

        if ($productoId !== false && (int) $productoId > 0) {
            $query->where('producto_id', (int) $productoId);
        }

        if ($buscar !== '') {
            $query->whereHas('producto', function ($q) use ($buscar): void {
                $q->where('sku', 'like', "%{$buscar}%")
                    ->orWhere('nombre', 'like', "%{$buscar}%");
            });
        }

        return $query->orderBy('producto_id')->orderBy('almacen_id')->get()->map(function (ExistenciaAlmacenTaller $ext): array {
            $cantidad = (string) $ext->cantidad;
            $reservada = (string) $ext->cantidad_reservada;
            $disponible = bcsub($cantidad, $reservada, 4);
            $costoPromedio = (string) $ext->costo_promedio;
            $valorTotal = bcmul($cantidad, $costoPromedio, 4);

            return [
                'id' => $ext->id,
                'producto_id' => $ext->producto_id,
                'sku' => $ext->producto?->sku ?? '',
                'producto' => $ext->producto?->nombre ?? '',
                'unidad_medida' => $ext->producto?->unidad_medida ?? 'pieza',
                'almacen_id' => $ext->almacen_id,
                'almacen_codigo' => $ext->almacen?->codigo ?? '',
                'almacen' => $ext->almacen?->nombre ?? '',
                'almacen_tipo' => $ext->almacen?->tipo ?? '',
                'es_virtual' => (bool) ($ext->almacen?->es_virtual ?? false),
                'sucursal_id' => $ext->almacen?->sucursal_id,
                'sucursal' => $ext->almacen?->sucursal?->nombre ?? 'Traspaso / Tránsito',
                'es_matriz' => (bool) ($ext->almacen?->sucursal?->es_matriz ?? false),
                'cantidad' => $cantidad,
                'cantidad_reservada' => $reservada,
                'cantidad_disponible' => $disponible,
                'costo_promedio' => $costoPromedio,
                'valor_total' => $valorTotal,
            ];
        });
    }

    /**
     * Construye una matriz comparativa de existencias por producto across todas las sucursales/almacenes
     * para ubicar fácilmente dónde hay stock disponible y dónde hacer traspasos.
     *
     * @return array{
     *     almacenes: Collection<int, AlmacenTaller>,
     *     matriz: Collection<int, array<string, mixed>>
     * }
     */
    public function matrizEntreAlmacenes(?string $buscar = null): array
    {
        ContextoPropietarioTaller::conexion();

        $almacenes = AlmacenTaller::query()
            ->with('sucursal:id,codigo,nombre,es_matriz,activa')
            ->where('activo', true)
            ->where('permite_existencias', true)
            ->orderBy('es_virtual')
            ->orderBy('id')
            ->get();

        $productosQuery = ProductoTaller::query()
            ->with(['existencias.almacen.sucursal'])
            ->where('activo', true)
            ->orderBy('nombre');

        $buscarLimpio = is_string($buscar) ? trim($buscar) : '';
        if ($buscarLimpio !== '') {
            $productosQuery->where(function ($q) use ($buscarLimpio): void {
                $q->where('sku', 'like', "%{$buscarLimpio}%")
                    ->orWhere('nombre', 'like', "%{$buscarLimpio}%");
            });
        }

        $productos = $productosQuery->get();

        $matriz = $productos->map(function (ProductoTaller $prod) use ($almacenes): array {
            $porAlmacen = [];
            $totalFisico = '0.0000';
            $totalDisponible = '0.0000';

            foreach ($almacenes as $alm) {
                /** @var ExistenciaAlmacenTaller|null $ext */
                $ext = $prod->existencias->firstWhere('almacen_id', $alm->id);
                $cant = $ext !== null ? (string) $ext->cantidad : '0.0000';
                $res = $ext !== null ? (string) $ext->cantidad_reservada : '0.0000';
                $disp = bcsub($cant, $res, 4);

                $porAlmacen[$alm->id] = [
                    'almacen_id' => $alm->id,
                    'almacen_codigo' => $alm->codigo,
                    'almacen_nombre' => $alm->nombre,
                    'sucursal_nombre' => $alm->sucursal?->nombre ?? 'Traspaso / Tránsito',
                    'es_matriz' => (bool) ($alm->sucursal?->es_matriz ?? false),
                    'es_virtual' => (bool) $alm->es_virtual,
                    'cantidad' => $cant,
                    'reservada' => $res,
                    'disponible' => $disp,
                ];

                $totalFisico = bcadd($totalFisico, $cant, 4);
                $totalDisponible = bcadd($totalDisponible, $disp, 4);
            }

            return [
                'producto_id' => $prod->id,
                'sku' => $prod->sku,
                'nombre' => $prod->nombre,
                'unidad_medida' => $prod->unidad_medida,
                'total_fisico' => $totalFisico,
                'total_disponible' => $totalDisponible,
                'por_almacen' => $porAlmacen,
            ];
        });

        return [
            'almacenes' => $almacenes,
            'matriz' => $matriz,
        ];
    }

    /**
     * Consulta movimientos inmutables del kardex filtrables por producto, almacén o tipo.
     *
     * @param  array{producto_id?: mixed, almacen_id?: mixed, tipo?: mixed, limite?: mixed}  $filtros
     * @return Collection<int, MovimientoInventarioTaller>
     */
    public function kardex(array $filtros = []): Collection
    {
        ContextoPropietarioTaller::conexion();

        $productoId = filter_var($filtros['producto_id'] ?? null, FILTER_VALIDATE_INT);
        $almacenId = filter_var($filtros['almacen_id'] ?? null, FILTER_VALIDATE_INT);
        $tipo = is_string($filtros['tipo'] ?? null) ? trim((string) $filtros['tipo']) : '';
        $limiteRaw = filter_var($filtros['limite'] ?? 100, FILTER_VALIDATE_INT);
        $limite = ($limiteRaw !== false && (int) $limiteRaw > 0) ? min(500, (int) $limiteRaw) : 100;

        return MovimientoInventarioTaller::query()
            ->with(['producto:id,sku,nombre,unidad_medida', 'almacen.sucursal'])
            ->when($productoId !== false && (int) $productoId > 0, fn ($q) => $q->where('producto_id', (int) $productoId))
            ->when($almacenId !== false && (int) $almacenId > 0, fn ($q) => $q->where('almacen_id', (int) $almacenId))
            ->when($tipo !== '', fn ($q) => $q->where('tipo', $tipo))
            ->orderByDesc('ocurrido_at')
            ->orderByDesc('id')
            ->limit($limite)
            ->get();
    }
}