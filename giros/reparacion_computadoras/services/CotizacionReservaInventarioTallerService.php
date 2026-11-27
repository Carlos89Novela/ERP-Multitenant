<?php

namespace Giros\ReparacionComputadoras\Services;

use Giros\ReparacionComputadoras\Models\AlmacenTaller;
use Giros\ReparacionComputadoras\Models\ExistenciaAlmacenTaller;
use Giros\ReparacionComputadoras\Models\MovimientoInventarioTaller;
use Giros\ReparacionComputadoras\Models\ProductoTaller;
use Giros\ReparacionComputadoras\Models\UsuarioTaller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CotizacionReservaInventarioTallerService
{
    private function resolverAlmacenSucursal(?int $sucursalId, int $empresaId = 1): ?AlmacenTaller
    {
        ContextoPropietarioTaller::conexion();

        $query = AlmacenTaller::query()
            ->where('empresa_id', $empresaId)
            ->where('es_virtual', false)
            ->where('activo', true)
            ->where('permite_existencias', true);

        if ($sucursalId !== null && $sucursalId > 0) {
            $alm = (clone $query)->where('sucursal_id', $sucursalId)->orderBy('id')->first();
            if ($alm !== null) {
                return $alm;
            }
        }

        return $query->orderBy('id')->first();
    }

    /**
     * Reserva en inventario todas las opciones de refacción sugeridas en una cotización
     * y libera cualquier reserva previa de la misma orden que ya no aplique.
     *
     * @param  list<array{producto_id: int, cantidad: float|string, precio?: float|string, motivo?: string}>  $opciones
     * @return list<array{producto_id: int, producto_nombre: string, cantidad: string, precio: float, motivo: string, almacen_id: int, estado_reserva: string}>
     */
    public function reservarOpcionesCotizacion(
        string $folioOrden,
        array $opciones,
        ?int $sucursalId = null,
        ?UsuarioTaller $usuario = null,
        int $empresaId = 1
    ): array {
        $almacen = $this->resolverAlmacenSucursal($sucursalId, $empresaId);
        if ($almacen === null) {
            return [];
        }

        return DB::connection('taller_rep_db')->transaction(function () use ($folioOrden, $opciones, $almacen, $usuario, $empresaId): array {
            $this->liberarReservasOrdenInterna($folioOrden);

            $normalizadas = [];
            foreach ($opciones as $idx => $op) {
                $prodId = filter_var($op['producto_id'] ?? null, FILTER_VALIDATE_INT);
                $cantRaw = $op['cantidad'] ?? '1';
                $cant = (is_numeric($cantRaw) && (float) $cantRaw > 0)
                    ? number_format((float) $cantRaw, 4, '.', '')
                    : '1.0000';

                if ($prodId === false || (int) $prodId <= 0) {
                    continue;
                }

                /** @var ProductoTaller|null $producto */
                $producto = ProductoTaller::query()->lockForUpdate()->find((int) $prodId);
                if (! $producto) {
                    continue;
                }

                /** @var ExistenciaAlmacenTaller|null $existencia */
                $existencia = ExistenciaAlmacenTaller::query()
                    ->where('almacen_id', $almacen->id)
                    ->where('producto_id', $producto->id)
                    ->lockForUpdate()
                    ->first();

                $cantFisica = $existencia !== null ? (string) $existencia->cantidad : '0.0000';
                $cantReservada = $existencia !== null ? (string) $existencia->cantidad_reservada : '0.0000';
                $disponible = bcsub($cantFisica, $cantReservada, 4);

                if (bccomp($disponible, $cant, 4) === -1) {
                    throw ValidationException::withMessages([
                        "opciones_inventario.{$idx}" => "Stock disponible insuficiente en {$almacen->nombre} para {$producto->nombre}. Disponible: {$disponible}",
                    ]);
                }

                $nuevaReservada = bcadd($cantReservada, $cant, 4);
                $existencia->update(['cantidad_reservada' => $nuevaReservada]);

                if (Schema::connection('taller_rep_db')->hasTable('reservas_inventario_taller')) {
                    DB::connection('taller_rep_db')->table('reservas_inventario_taller')->insert([
                        'empresa_id' => $empresaId,
                        'almacen_id' => $almacen->id,
                        'producto_id' => $producto->id,
                        'referencia_tipo' => 'cotizacion_orden',
                        'referencia_id' => 0,
                        'folio_referencia' => $folioOrden,
                        'cantidad' => $cant,
                        'estado' => 'reservada',
                        'usuario_id' => $usuario?->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                $precio = isset($op['precio']) && is_numeric($op['precio'])
                    ? round((float) $op['precio'], 2)
                    : round((float) ($producto->precio_venta ?? 0), 2);

                $normalizadas[] = [
                    'producto_id' => (int) $producto->id,
                    'producto_nombre' => (string) $producto->nombre,
                    'cantidad' => $cant,
                    'precio' => $precio,
                    'motivo' => trim((string) ($op['motivo'] ?? '')),
                    'almacen_id' => (int) $almacen->id,
                    'estado_reserva' => 'reservada',
                ];
            }

            return $normalizadas;
        }, 3);
    }

    /**
     * Cuando el cliente responde a la cotización:
     * - Si acepta y elige uno o más productos ($productoIdElegido): mantiene la reserva del elegido
     *   y libera de inmediato las demás opciones que no eligió.
     * - Si no elige nada o rechaza la cotización: libera todas las reservas de la orden.
     *
     * @param  list<array<string, mixed>>  $opciones
     * @return list<array<string, mixed>>
     */
    public function aplicarDecisionClienteCotizacion(
        string $folioOrden,
        array $opciones,
        bool $aceptada,
        ?int $productoIdElegido = null
    ): array {
        if (empty($opciones)) {
            $this->liberarReservasOrden($folioOrden);
            return [];
        }

        return DB::connection('taller_rep_db')->transaction(function () use ($folioOrden, $opciones, $aceptada, $productoIdElegido): array {
            $actualizadas = [];

            foreach ($opciones as $op) {
                $prodId = (int) ($op['producto_id'] ?? 0);
                $almId = (int) ($op['almacen_id'] ?? 0);
                $cant = (string) ($op['cantidad'] ?? '0.0000');
                $esElegida = $aceptada && $productoIdElegido !== null && $productoIdElegido > 0 && $prodId === $productoIdElegido;

                if ($esElegida) {
                    $op['estado_reserva'] = 'reservada_elegida';
                    $actualizadas[] = $op;
                    continue;
                }

                if ($almId > 0 && $prodId > 0 && bccomp($cant, '0.0000', 4) === 1) {
                    $this->liberarCantidadProducto($almId, $prodId, $cant);
                }

                $op['estado_reserva'] = 'liberada';
                $actualizadas[] = $op;
            }

            if (Schema::connection('taller_rep_db')->hasTable('reservas_inventario_taller')) {
                $q = DB::connection('taller_rep_db')
                    ->table('reservas_inventario_taller')
                    ->where('folio_referencia', $folioOrden)
                    ->where('estado', 'reservada');

                if ($aceptada && $productoIdElegido !== null && $productoIdElegido > 0) {
                    $q->where('producto_id', '!=', $productoIdElegido);
                }

                $q->update([
                    'estado' => 'liberada',
                    'updated_at' => now(),
                ]);
            }

            return $actualizadas;
        }, 3);
    }

    /**
     * Descuenta físicamente del inventario y libera la reserva del componente elegido
     * cuando el técnico avanza la orden a reparación / pruebas / listo para entrega.
     *
     * @param  list<array<string, mixed>>  $opciones
     * @return array{opciones: list<array<string, mixed>>, consumidos: list<array<string, mixed>>}
     */
    public function consumirOpcionElegidaAlAvanzarOrden(
        string $folioOrden,
        array $opciones,
        ?UsuarioTaller $usuario = null,
        int $empresaId = 1
    ): array {
        if (empty($opciones)) {
            return ['opciones' => [], 'consumidos' => []];
        }

        return DB::connection('taller_rep_db')->transaction(function () use ($folioOrden, $opciones, $usuario, $empresaId): array {
            $ahora = now();
            $grupoId = (string) Str::uuid();
            $nuevasOpciones = [];
            $consumidos = [];

            foreach ($opciones as $op) {
                $estadoRes = (string) ($op['estado_reserva'] ?? '');
                $prodId = (int) ($op['producto_id'] ?? 0);
                $almId = (int) ($op['almacen_id'] ?? 0);
                $cant = (string) ($op['cantidad'] ?? '0.0000');

                if ($estadoRes !== 'reservada_elegida' || $prodId <= 0 || $almId <= 0 || bccomp($cant, '0.0000', 4) !== 1) {
                    $nuevasOpciones[] = $op;
                    continue;
                }

                /** @var ExistenciaAlmacenTaller|null $existencia */
                $existencia = ExistenciaAlmacenTaller::query()
                    ->where('almacen_id', $almId)
                    ->where('producto_id', $prodId)
                    ->lockForUpdate()
                    ->first();

                if ($existencia !== null) {
                    $cantAnt = (string) $existencia->cantidad;
                    $resAnt = (string) $existencia->cantidad_reservada;

                    $cantNueva = bcsub($cantAnt, $cant, 4);
                    if (bccomp($cantNueva, '0.0000', 4) === -1) {
                        $cantNueva = '0.0000';
                    }

                    $resNueva = bcsub($resAnt, $cant, 4);
                    if (bccomp($resNueva, '0.0000', 4) === -1) {
                        $resNueva = '0.0000';
                    }

                    $existencia->update([
                        'cantidad' => $cantNueva,
                        'cantidad_reservada' => $resNueva,
                    ]);

                    MovimientoInventarioTaller::query()->create([
                        'empresa_id' => $empresaId,
                        'almacen_id' => $almId,
                        'producto_id' => $prodId,
                        'grupo_id' => $grupoId,
                        'folio' => 'MOV-' . $ahora->format('YmdHisv') . '-' . Str::upper(Str::random(6)),
                        'tipo' => MovimientoInventarioTaller::TIPO_CONSUMO_ORDEN,
                        'cantidad' => $cant,
                        'existencia_anterior' => $cantAnt,
                        'existencia_nueva' => $cantNueva,
                        'costo_unitario' => (string) $existencia->costo_promedio,
                        'referencia_tipo' => 'orden_taller',
                        'referencia_id' => 0,
                        'motivo' => "Consumo técnico en orden {$folioOrden}: {$op['producto_nombre']}",
                        'usuario_id' => $usuario?->id,
                        'ocurrido_at' => $ahora,
                    ]);
                }

                if (Schema::connection('taller_rep_db')->hasTable('reservas_inventario_taller')) {
                    DB::connection('taller_rep_db')
                        ->table('reservas_inventario_taller')
                        ->where('folio_referencia', $folioOrden)
                        ->where('producto_id', $prodId)
                        ->where('estado', 'reservada')
                        ->update([
                            'estado' => 'consumida',
                            'updated_at' => $ahora,
                        ]);
                }

                $op['estado_reserva'] = 'consumida';
                $nuevasOpciones[] = $op;
                $consumidos[] = $op;
            }

            return [
                'opciones' => $nuevasOpciones,
                'consumidos' => $consumidos,
            ];
        }, 3);
    }

    public function liberarReservasOrden(string $folioOrden): void
    {
        ContextoPropietarioTaller::conexion();
        DB::connection('taller_rep_db')->transaction(function () use ($folioOrden): void {
            $this->liberarReservasOrdenInterna($folioOrden);
        }, 3);
    }

    private function liberarReservasOrdenInterna(string $folioOrden): void
    {
        if (! Schema::connection('taller_rep_db')->hasTable('reservas_inventario_taller')) {
            return;
        }

        $reservas = DB::connection('taller_rep_db')
            ->table('reservas_inventario_taller')
            ->where('folio_referencia', $folioOrden)
            ->where('estado', 'reservada')
            ->lockForUpdate()
            ->get();

        foreach ($reservas as $res) {
            $this->liberarCantidadProducto((int) $res->almacen_id, (int) $res->producto_id, (string) $res->cantidad);
        }

        DB::connection('taller_rep_db')
            ->table('reservas_inventario_taller')
            ->where('folio_referencia', $folioOrden)
            ->where('estado', 'reservada')
            ->update([
                'estado' => 'liberada',
                'updated_at' => now(),
            ]);
    }

    private function liberarCantidadProducto(int $almacenId, int $productoId, string $cantidad): void
    {
        /** @var ExistenciaAlmacenTaller|null $existencia */
        $existencia = ExistenciaAlmacenTaller::query()
            ->where('almacen_id', $almacenId)
            ->where('producto_id', $productoId)
            ->lockForUpdate()
            ->first();

        if ($existencia === null) {
            return;
        }

        $resAnt = (string) $existencia->cantidad_reservada;
        $resNueva = bcsub($resAnt, $cantidad, 4);
        if (bccomp($resNueva, '0.0000', 4) === -1) {
            $resNueva = '0.0000';
        }

        $existencia->update(['cantidad_reservada' => $resNueva]);
    }
}