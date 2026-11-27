<?php

namespace Giros\ReparacionComputadoras\Services;

use Giros\ReparacionComputadoras\Models\AlmacenTaller;
use Giros\ReparacionComputadoras\Models\ExistenciaAlmacenTaller;
use Giros\ReparacionComputadoras\Models\MovimientoInventarioTaller;
use Giros\ReparacionComputadoras\Models\ProductoTaller;
use Giros\ReparacionComputadoras\Models\ReservaInventarioTaller;
use Giros\ReparacionComputadoras\Models\UsuarioTaller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SalidasYConsumosTallerService
{
    /**
     * Registra una salida directa (por ejemplo, venta de refacción/accesorio
     * o baja operativa fuera de orden) descontando stock disponible.
     *
     * @param  array{
     *     cantidad?: mixed,
     *     motivo?: mixed,
     *     referencia_tipo?: mixed,
     *     referencia_id?: mixed
     * }  $datos
     */
    public function registrarSalidaDirecta(
        AlmacenTaller $almacen,
        ProductoTaller $producto,
        ?UsuarioTaller $usuario,
        array $datos
    ): ExistenciaAlmacenTaller {
        ContextoPropietarioTaller::validarOperadorInventario($usuario);

        $cantidad = $this->normalizarDecimal($datos['cantidad'] ?? null);
        $motivo = is_string($datos['motivo'] ?? null) ? trim((string) $datos['motivo']) : '';
        $refTipo = is_string($datos['referencia_tipo'] ?? null) && trim((string) $datos['referencia_tipo']) !== ''
            ? trim((string) $datos['referencia_tipo'])
            : 'venta_directa';
        $refIdRaw = filter_var($datos['referencia_id'] ?? null, FILTER_VALIDATE_INT);
        $refId = $refIdRaw !== false ? (int) $refIdRaw : null;

        $this->validarOperacionBase($almacen, $producto, $cantidad, $motivo);

        return DB::connection('taller_rep_db')->transaction(function () use (
            $almacen,
            $producto,
            $usuario,
            $cantidad,
            $motivo,
            $refTipo,
            $refId
        ): ExistenciaAlmacenTaller {
            /** @var AlmacenTaller $almacenBloqueado */
            $almacenBloqueado = AlmacenTaller::query()->with('sucursal')->lockForUpdate()->findOrFail($almacen->id);
            /** @var ProductoTaller $productoBloqueado */
            $productoBloqueado = ProductoTaller::query()->lockForUpdate()->findOrFail($producto->id);

            $this->validarOperacionBase($almacenBloqueado, $productoBloqueado, $cantidad, $motivo);

            /** @var ExistenciaAlmacenTaller|null $existencia */
            $existencia = ExistenciaAlmacenTaller::query()
                ->where('almacen_id', $almacenBloqueado->id)
                ->where('producto_id', $productoBloqueado->id)
                ->lockForUpdate()
                ->first();

            if ($existencia === null) {
                throw ValidationException::withMessages([
                    'existencia' => 'El producto no tiene existencia registrada en el almacén seleccionado.',
                ]);
            }

            $cantidadAnterior = (string) $existencia->cantidad;
            $cantidadReservada = (string) $existencia->cantidad_reservada;
            $costoPromedio = (string) $existencia->costo_promedio;
            $disponible = bcsub($cantidadAnterior, $cantidadReservada, 4);

            if (bccomp($cantidad, $disponible, 4) === 1) {
                throw ValidationException::withMessages([
                    'cantidad' => "La cantidad de salida ({$cantidad}) supera la existencia disponible ({$disponible}).",
                ]);
            }

            $cantidadNueva = bcsub($cantidadAnterior, $cantidad, 4);

            $existencia->update([
                'cantidad' => $cantidadNueva,
            ]);

            MovimientoInventarioTaller::query()->create([
                'empresa_id' => 1,
                'almacen_id' => $almacenBloqueado->id,
                'producto_id' => $productoBloqueado->id,
                'grupo_id' => (string) Str::uuid(),
                'folio' => $this->generarFolioMovimiento(),
                'tipo' => MovimientoInventarioTaller::TIPO_SALIDA,
                'cantidad' => $cantidad,
                'existencia_anterior' => $cantidadAnterior,
                'existencia_nueva' => $cantidadNueva,
                'costo_unitario' => $costoPromedio,
                'referencia_tipo' => $refTipo,
                'referencia_id' => $refId,
                'motivo' => $motivo,
                'usuario_id' => $usuario?->id,
                'ocurrido_at' => now(),
            ]);

            /** @var ExistenciaAlmacenTaller $actualizada */
            $actualizada = $existencia->fresh(['almacen', 'producto']) ?? $existencia;

            return $actualizada;
        }, 3);
    }

    /**
     * Registra el consumo de refacciones/materiales para una orden de reparación.
     * Si existe reserva activa para la orden/almacén/producto, descuenta de la reserva
     * y del saldo físico; si no hay reserva previa, descuenta del stock disponible.
     *
     * @param  array{
     *     orden_reparacion_id?: mixed,
     *     orden_folio?: mixed,
     *     cantidad?: mixed,
     *     motivo?: mixed
     * }  $datos
     */
    public function consumirEnOrdenReparacion(
        AlmacenTaller $almacen,
        ProductoTaller $producto,
        ?UsuarioTaller $usuario,
        array $datos
    ): MovimientoInventarioTaller {
        ContextoPropietarioTaller::validarOperadorInventario($usuario);

        $ordenIdRaw = filter_var($datos['orden_reparacion_id'] ?? null, FILTER_VALIDATE_INT);
        $ordenId = ($ordenIdRaw !== false && (int) $ordenIdRaw > 0) ? (int) $ordenIdRaw : null;
        $ordenFolio = is_string($datos['orden_folio'] ?? null) && trim((string) $datos['orden_folio']) !== ''
            ? trim((string) $datos['orden_folio'])
            : null;

        $cantidad = $this->normalizarDecimal($datos['cantidad'] ?? null);
        $motivoRaw = is_string($datos['motivo'] ?? null) ? trim((string) $datos['motivo']) : '';
        $motivo = $motivoRaw !== ''
            ? $motivoRaw
            : ('Consumo en reparación' . ($ordenFolio ? " (Orden {$ordenFolio})" : ($ordenId ? " (Orden #{$ordenId})" : '')));

        if ($ordenId === null && $ordenFolio === null) {
            throw ValidationException::withMessages([
                'orden_reparacion_id' => 'Debes indicar la orden de reparación (ID o folio) para registrar el consumo.',
            ]);
        }

        $this->validarOperacionBase($almacen, $producto, $cantidad, $motivo);

        return DB::connection('taller_rep_db')->transaction(function () use (
            $almacen,
            $producto,
            $usuario,
            $ordenId,
            $ordenFolio,
            $cantidad,
            $motivo
        ): MovimientoInventarioTaller {
            /** @var AlmacenTaller $almacenBloqueado */
            $almacenBloqueado = AlmacenTaller::query()->with('sucursal')->lockForUpdate()->findOrFail($almacen->id);
            /** @var ProductoTaller $productoBloqueado */
            $productoBloqueado = ProductoTaller::query()->lockForUpdate()->findOrFail($producto->id);

            $this->validarOperacionBase($almacenBloqueado, $productoBloqueado, $cantidad, $motivo);

            /** @var ExistenciaAlmacenTaller|null $existencia */
            $existencia = ExistenciaAlmacenTaller::query()
                ->where('almacen_id', $almacenBloqueado->id)
                ->where('producto_id', $productoBloqueado->id)
                ->lockForUpdate()
                ->first();

            if ($existencia === null) {
                throw ValidationException::withMessages([
                    'existencia' => 'El producto no tiene existencia registrada en el almacén seleccionado.',
                ]);
            }

            /** @var ReservaInventarioTaller|null $reserva */
            $reserva = ReservaInventarioTaller::query()
                ->where('almacen_id', $almacenBloqueado->id)
                ->where('producto_id', $productoBloqueado->id)
                ->whereIn('estado', [ReservaInventarioTaller::ESTADO_ACTIVA, ReservaInventarioTaller::ESTADO_PARCIAL])
                ->when($ordenId !== null, fn ($q) => $q->where('orden_reparacion_id', $ordenId))
                ->when($ordenId === null && $ordenFolio !== null, fn ($q) => $q->where('orden_folio', $ordenFolio))
                ->orderBy('id', 'asc')
                ->lockForUpdate()
                ->first();

            $cantidadAnterior = (string) $existencia->cantidad;
            $cantidadReservadaActual = (string) $existencia->cantidad_reservada;
            $costoPromedio = (string) $existencia->costo_promedio;

            if (bccomp($cantidad, $cantidadAnterior, 4) === 1) {
                throw ValidationException::withMessages([
                    'cantidad' => "La cantidad a consumir ({$cantidad}) supera la existencia física ({$cantidadAnterior}).",
                ]);
            }

            $nuevaReservada = $cantidadReservadaActual;

            if ($reserva !== null) {
                $pendienteReserva = $reserva->cantidadPendiente();
                if (bccomp($cantidad, $pendienteReserva, 4) === 1) {
                    throw ValidationException::withMessages([
                        'cantidad' => "La cantidad a consumir ({$cantidad}) supera lo pendiente en la reserva ({$pendienteReserva}).",
                    ]);
                }

                $consumidaNueva = bcadd((string) $reserva->cantidad_consumida, $cantidad, 4);
                $finalizadaTotal = bcadd($consumidaNueva, (string) $reserva->cantidad_liberada, 4);
                $completa = bccomp($finalizadaTotal, (string) $reserva->cantidad_reservada, 4) === 0;

                $reserva->update([
                    'cantidad_consumida' => $consumidaNueva,
                    'estado' => $completa ? ReservaInventarioTaller::ESTADO_CONSUMIDA : ReservaInventarioTaller::ESTADO_PARCIAL,
                    'actualizado_por_id' => $usuario?->id,
                    'finalizado_at' => $completa ? now() : null,
                ]);

                $nuevaReservada = bcsub($cantidadReservadaActual, $cantidad, 4);
                if (bccomp($nuevaReservada, '0.0000', 4) === -1) {
                    $nuevaReservada = '0.0000';
                }
            } else {
                $disponible = bcsub($cantidadAnterior, $cantidadReservadaActual, 4);
                if (bccomp($cantidad, $disponible, 4) === 1) {
                    throw ValidationException::withMessages([
                        'cantidad' => "La cantidad a consumir ({$cantidad}) supera la existencia disponible ({$disponible}).",
                    ]);
                }
            }

            $cantidadNueva = bcsub($cantidadAnterior, $cantidad, 4);

            $existencia->update([
                'cantidad' => $cantidadNueva,
                'cantidad_reservada' => $nuevaReservada,
            ]);

            /** @var MovimientoInventarioTaller $movimiento */
            $movimiento = MovimientoInventarioTaller::query()->create([
                'empresa_id' => 1,
                'almacen_id' => $almacenBloqueado->id,
                'producto_id' => $productoBloqueado->id,
                'grupo_id' => (string) Str::uuid(),
                'folio' => $this->generarFolioMovimiento(),
                'tipo' => MovimientoInventarioTaller::TIPO_CONSUMO,
                'cantidad' => $cantidad,
                'existencia_anterior' => $cantidadAnterior,
                'existencia_nueva' => $cantidadNueva,
                'costo_unitario' => $costoPromedio,
                'referencia_tipo' => 'orden_reparacion',
                'referencia_id' => $ordenId,
                'motivo' => $motivo,
                'usuario_id' => $usuario?->id,
                'ocurrido_at' => now(),
            ]);

            return $movimiento->load(['almacen', 'producto']);
        }, 3);
    }

    private function validarOperacionBase(
        AlmacenTaller $almacen,
        ProductoTaller $producto,
        string $cantidad,
        string $motivo
    ): void {
        if (! $almacen->activo || ! $almacen->permite_existencias) {
            throw ValidationException::withMessages([
                'almacen_id' => 'El almacén está inactivo o no permite manejar existencias.',
            ]);
        }

        if (! $almacen->es_virtual) {
            $sucursal = $almacen->sucursal;
            if ($sucursal === null || ! $sucursal->activa) {
                throw ValidationException::withMessages([
                    'sucursal_id' => 'La sucursal física del almacén no existe o está inactiva.',
                ]);
            }
        }

        if (! $producto->activo) {
            throw ValidationException::withMessages([
                'producto_id' => 'El producto o refacción se encuentra inactivo.',
            ]);
        }

        if ($cantidad === '' || bccomp($cantidad, '0.0000', 4) !== 1) {
            throw ValidationException::withMessages([
                'cantidad' => 'La cantidad debe ser mayor a cero (hasta 4 decimales).',
            ]);
        }

        if ($motivo === '' || mb_strlen($motivo) > 1000) {
            throw ValidationException::withMessages([
                'motivo' => 'Debes indicar un motivo válido de hasta 1000 caracteres.',
            ]);
        }
    }

    private function normalizarDecimal(mixed $valor): string
    {
        if (! is_int($valor) && ! is_float($valor) && ! is_string($valor)) {
            return '';
        }

        $limpio = trim((string) $valor);
        if ($limpio === '' || ! is_numeric($limpio) || ! is_finite((float) $limpio)) {
            return '';
        }

        return number_format((float) $limpio, 4, '.', '');
    }

    private function generarFolioMovimiento(): string
    {
        return 'MOV-' . now()->format('YmdHisv') . '-' . Str::upper(Str::random(6));
    }
}