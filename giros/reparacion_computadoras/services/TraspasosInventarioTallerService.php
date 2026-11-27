<?php

namespace Giros\ReparacionComputadoras\Services;

use Giros\ReparacionComputadoras\Models\AlmacenTaller;
use Giros\ReparacionComputadoras\Models\ExistenciaAlmacenTaller;
use Giros\ReparacionComputadoras\Models\MovimientoInventarioTaller;
use Giros\ReparacionComputadoras\Models\ProductoTaller;
use Giros\ReparacionComputadoras\Models\TransferenciaInventarioTaller;
use Giros\ReparacionComputadoras\Models\TransferenciaInventarioTallerDetalle;
use Giros\ReparacionComputadoras\Models\UsuarioTaller;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TraspasosInventarioTallerService
{
    public function __construct(
        private GestionAlmacenesTallerService $almacenesService
    ) {}

    /**
     * Solicita/registra una requisición de traspaso entre dos almacenes distintos.
     *
     * @param  array{
     *     motivo?: mixed,
     *     lineas?: mixed
     * }  $datos
     */
    public function solicitarTraspaso(
        AlmacenTaller $origen,
        AlmacenTaller $destino,
        ?UsuarioTaller $usuario,
        array $datos
    ): TransferenciaInventarioTaller {
        ContextoPropietarioTaller::validarPropietario($usuario);

        $normalizados = $this->normalizarSolicitud($datos);
        $this->validarSolicitud($origen, $destino, $normalizados);

        return DB::connection('taller_rep_db')->transaction(function () use ($origen, $destino, $usuario, $normalizados): TransferenciaInventarioTaller {
            /** @var AlmacenTaller $origenBloqueado */
            $origenBloqueado = AlmacenTaller::query()->with('sucursal')->lockForUpdate()->findOrFail($origen->id);
            /** @var AlmacenTaller $destinoBloqueado */
            $destinoBloqueado = AlmacenTaller::query()->with('sucursal')->lockForUpdate()->findOrFail($destino->id);

            $this->validarSolicitud($origenBloqueado, $destinoBloqueado, $normalizados);

            $productoIds = array_column($normalizados['lineas'], 'producto_id');
            sort($productoIds);
            ProductoTaller::query()->whereIn('id', $productoIds)->lockForUpdate()->get();

            $ahora = now();
            $costoTotal = '0.0000';

            /** @var TransferenciaInventarioTaller $transferencia */
            $transferencia = TransferenciaInventarioTaller::query()->create([
                'empresa_id' => 1,
                'almacen_origen_id' => $origenBloqueado->id,
                'almacen_destino_id' => $destinoBloqueado->id,
                'almacen_transito_id' => null,
                'grupo_id' => (string) Str::uuid(),
                'folio' => 'TRF-' . $ahora->format('YmdHisv') . '-' . Str::upper(Str::random(6)),
                'estado' => TransferenciaInventarioTaller::ESTADO_SOLICITADA,
                'motivo' => $normalizados['motivo'],
                'total_lineas' => count($normalizados['lineas']),
                'costo_total' => '0.0000',
                'solicitado_por_id' => $usuario?->id,
                'solicitado_at' => $ahora,
            ]);

            foreach ($normalizados['lineas'] as $idx => $linea) {
                $productoId = $linea['producto_id'];
                $cantidad = $linea['cantidad'];

                /** @var ExistenciaAlmacenTaller|null $existenciaOrigen */
                $existenciaOrigen = ExistenciaAlmacenTaller::query()
                    ->where('almacen_id', $origenBloqueado->id)
                    ->where('producto_id', $productoId)
                    ->first();

                $disponible = $existenciaOrigen !== null
                    ? bcsub((string) $existenciaOrigen->cantidad, (string) $existenciaOrigen->cantidad_reservada, 4)
                    : '0.0000';

                if (bccomp($disponible, $cantidad, 4) === -1) {
                    throw ValidationException::withMessages([
                        "lineas.{$idx}.cantidad" => "Stock disponible insuficiente en {$origenBloqueado->nombre}. Disponible: {$disponible}",
                    ]);
                }

                $costoUnitario = $existenciaOrigen !== null ? (string) $existenciaOrigen->costo_promedio : '0.0000';
                $subtotal = bcmul($cantidad, $costoUnitario, 4);
                $costoTotal = bcadd($costoTotal, $subtotal, 4);

                TransferenciaInventarioTallerDetalle::query()->create([
                    'transferencia_inventario_taller_id' => $transferencia->id,
                    'producto_id' => $productoId,
                    'cantidad' => $cantidad,
                    'cantidad_solicitada' => $cantidad,
                    'cantidad_despachada' => '0.0000',
                    'cantidad_recibida' => '0.0000',
                    'costo_unitario' => $costoUnitario,
                    'subtotal' => $subtotal,
                ]);
            }

            $transferencia->update(['costo_total' => $costoTotal]);

            /** @var TransferenciaInventarioTaller $actualizada */
            $actualizada = $transferencia->fresh(['almacenOrigen', 'almacenDestino', 'detalles.producto']) ?? $transferencia;

            return $actualizada;
        }, 3);
    }

    /**
     * Despacha una transferencia solicitada hacia el almacén virtual de traspaso/tránsito.
     *
     * @param  array{
     *     observaciones?: mixed,
     *     lineas?: mixed
     * }  $datos
     */
    public function despacharATransito(
        TransferenciaInventarioTaller $transferencia,
        ?UsuarioTaller $usuario,
        array $datos = []
    ): TransferenciaInventarioTaller {
        ContextoPropietarioTaller::validarPropietario($usuario);

        if ($transferencia->estado !== TransferenciaInventarioTaller::ESTADO_SOLICITADA) {
            throw ValidationException::withMessages([
                'transferencia' => 'La transferencia ya no está en estado solicitada.',
            ]);
        }

        $observaciones = is_string($datos['observaciones'] ?? null) && trim((string) $datos['observaciones']) !== ''
            ? trim((string) $datos['observaciones'])
            : null;

        $despachoPorDetalle = [];
        if (is_array($datos['lineas'] ?? null)) {
            foreach ($datos['lineas'] as $linea) {
                if (! is_array($linea)) {
                    continue;
                }
                $detId = filter_var($linea['detalle_id'] ?? null, FILTER_VALIDATE_INT);
                $cant = $this->normalizarDecimal($linea['cantidad_despachada'] ?? null);
                if ($detId !== false && (int) $detId > 0) {
                    $despachoPorDetalle[(int) $detId] = $cant;
                }
            }
        }

        return DB::connection('taller_rep_db')->transaction(function () use (
            $transferencia,
            $usuario,
            $observaciones,
            $despachoPorDetalle
        ): TransferenciaInventarioTaller {
            /** @var TransferenciaInventarioTaller $transfBloqueada */
            $transfBloqueada = TransferenciaInventarioTaller::query()->lockForUpdate()->findOrFail($transferencia->id);

            if ($transfBloqueada->estado !== TransferenciaInventarioTaller::ESTADO_SOLICITADA) {
                throw ValidationException::withMessages([
                    'transferencia' => 'La transferencia ya no está disponible para despacho.',
                ]);
            }

            /** @var AlmacenTaller $origen */
            $origen = AlmacenTaller::query()->with('sucursal')->lockForUpdate()->findOrFail($transfBloqueada->almacen_origen_id);
            $transito = $this->almacenesService->asegurarAlmacenTransito();
            /** @var AlmacenTaller $transitoBloqueado */
            $transitoBloqueado = AlmacenTaller::query()->lockForUpdate()->findOrFail($transito->id);

            /** @var Collection<int, TransferenciaInventarioTallerDetalle> $detalles */
            $detalles = $transfBloqueada->detalles()->with('producto')->lockForUpdate()->get();
            $productoIds = $detalles->pluck('producto_id')->all();
            sort($productoIds);
            ProductoTaller::query()->whereIn('id', $productoIds)->lockForUpdate()->get();

            $ahora = now();
            $grupoId = $transfBloqueada->grupo_id;
            $costoTotal = '0.0000';

            foreach ($detalles as $detalle) {
                $cantidadDespacho = array_key_exists($detalle->id, $despachoPorDetalle)
                    ? $despachoPorDetalle[$detalle->id]
                    : (string) $detalle->cantidad_solicitada;

                if ($cantidadDespacho === '' || bccomp($cantidadDespacho, '0.0000', 4) !== 1) {
                    throw ValidationException::withMessages([
                        'lineas' => "La cantidad a despachar para {$detalle->producto->nombre} debe ser mayor a cero.",
                    ]);
                }

                /** @var ExistenciaAlmacenTaller|null $existenciaOrigen */
                $existenciaOrigen = ExistenciaAlmacenTaller::query()
                    ->where('almacen_id', $origen->id)
                    ->where('producto_id', $detalle->producto_id)
                    ->lockForUpdate()
                    ->first();

                $disponible = $existenciaOrigen !== null
                    ? bcsub((string) $existenciaOrigen->cantidad, (string) $existenciaOrigen->cantidad_reservada, 4)
                    : '0.0000';

                if (bccomp($disponible, $cantidadDespacho, 4) === -1) {
                    throw ValidationException::withMessages([
                        'lineas' => "Stock insuficiente en {$origen->nombre} para {$detalle->producto->nombre}. Disponible: {$disponible}",
                    ]);
                }

                $costoUnitario = (string) $existenciaOrigen->costo_promedio;
                $subtotal = bcmul($cantidadDespacho, $costoUnitario, 4);
                $costoTotal = bcadd($costoTotal, $subtotal, 4);

                $detalle->update([
                    'cantidad' => $cantidadDespacho,
                    'cantidad_despachada' => $cantidadDespacho,
                    'costo_unitario' => $costoUnitario,
                    'subtotal' => $subtotal,
                ]);

                // 1. Salida en almacén origen
                $cantOrigenAnt = (string) $existenciaOrigen->cantidad;
                $cantOrigenNueva = bcsub($cantOrigenAnt, $cantidadDespacho, 4);
                $existenciaOrigen->update(['cantidad' => $cantOrigenNueva]);

                MovimientoInventarioTaller::query()->create([
                    'empresa_id' => 1,
                    'almacen_id' => $origen->id,
                    'producto_id' => $detalle->producto_id,
                    'grupo_id' => $grupoId,
                    'folio' => 'MOV-' . $ahora->format('YmdHisv') . '-' . Str::upper(Str::random(6)),
                    'tipo' => MovimientoInventarioTaller::TIPO_TRANSFERENCIA_SALIDA,
                    'cantidad' => $cantidadDespacho,
                    'existencia_anterior' => $cantOrigenAnt,
                    'existencia_nueva' => $cantOrigenNueva,
                    'costo_unitario' => $costoUnitario,
                    'referencia_tipo' => TransferenciaInventarioTaller::class,
                    'referencia_id' => $transfBloqueada->id,
                    'motivo' => "Despacho a traspaso ({$transfBloqueada->folio}): {$transfBloqueada->motivo}",
                    'usuario_id' => $usuario?->id,
                    'ocurrido_at' => $ahora,
                ]);

                // 2. Ingreso a almacén virtual de traspaso/tránsito
                /** @var ExistenciaAlmacenTaller|null $existenciaTransito */
                $existenciaTransito = ExistenciaAlmacenTaller::query()
                    ->where('almacen_id', $transitoBloqueado->id)
                    ->where('producto_id', $detalle->producto_id)
                    ->lockForUpdate()
                    ->first();

                $cantTrnsAnt = $existenciaTransito !== null ? (string) $existenciaTransito->cantidad : '0.0000';
                $cantTrnsNueva = bcadd($cantTrnsAnt, $cantidadDespacho, 4);

                if ($existenciaTransito === null) {
                    ExistenciaAlmacenTaller::query()->create([
                        'empresa_id' => 1,
                        'almacen_id' => $transitoBloqueado->id,
                        'producto_id' => $detalle->producto_id,
                        'cantidad' => $cantTrnsNueva,
                        'cantidad_reservada' => '0.0000',
                        'costo_promedio' => $costoUnitario,
                    ]);
                } else {
                    $existenciaTransito->update([
                        'cantidad' => $cantTrnsNueva,
                        'costo_promedio' => $costoUnitario,
                    ]);
                }

                MovimientoInventarioTaller::query()->create([
                    'empresa_id' => 1,
                    'almacen_id' => $transitoBloqueado->id,
                    'producto_id' => $detalle->producto_id,
                    'grupo_id' => $grupoId,
                    'folio' => 'MOV-' . $ahora->format('YmdHisv') . '-' . Str::upper(Str::random(6)),
                    'tipo' => MovimientoInventarioTaller::TIPO_TRANSFERENCIA_TRANSITO,
                    'cantidad' => $cantidadDespacho,
                    'existencia_anterior' => $cantTrnsAnt,
                    'existencia_nueva' => $cantTrnsNueva,
                    'costo_unitario' => $costoUnitario,
                    'referencia_tipo' => TransferenciaInventarioTaller::class,
                    'referencia_id' => $transfBloqueada->id,
                    'motivo' => "Custodia en traspaso ({$transfBloqueada->folio})",
                    'usuario_id' => $usuario?->id,
                    'ocurrido_at' => $ahora,
                ]);
            }

            $transfBloqueada->update([
                'almacen_transito_id' => $transitoBloqueado->id,
                'estado' => TransferenciaInventarioTaller::ESTADO_EN_TRANSITO,
                'costo_total' => $costoTotal,
                'despachado_por_id' => $usuario?->id,
                'despachado_at' => $ahora,
                'observaciones_despacho' => $observaciones,
            ]);

            /** @var TransferenciaInventarioTaller $actualizada */
            $actualizada = $transfBloqueada->fresh([
                'almacenOrigen',
                'almacenDestino',
                'almacenTransito',
                'detalles.producto',
            ]) ?? $transfBloqueada;

            return $actualizada;
        }, 3);
    }

    /**
     * Recibe una transferencia en tránsito en el almacén de destino,
     * liquidando el almacén de traspaso y recalculando costo promedio en destino.
     *
     * @param  array{
     *     observaciones?: mixed,
     *     lineas?: mixed
     * }  $datos
     */
    public function recibirEnDestino(
        TransferenciaInventarioTaller $transferencia,
        ?UsuarioTaller $usuario,
        array $datos = []
    ): TransferenciaInventarioTaller {
        ContextoPropietarioTaller::validarPropietario($usuario);

        if ($transferencia->estado !== TransferenciaInventarioTaller::ESTADO_EN_TRANSITO) {
            throw ValidationException::withMessages([
                'transferencia' => 'La transferencia no se encuentra en tránsito.',
            ]);
        }

        $observaciones = is_string($datos['observaciones'] ?? null) && trim((string) $datos['observaciones']) !== ''
            ? trim((string) $datos['observaciones'])
            : null;

        $recepcionPorDetalle = [];
        if (is_array($datos['lineas'] ?? null)) {
            foreach ($datos['lineas'] as $linea) {
                if (! is_array($linea)) {
                    continue;
                }
                $detId = filter_var($linea['detalle_id'] ?? null, FILTER_VALIDATE_INT);
                $cant = $this->normalizarDecimal($linea['cantidad_recibida'] ?? null);
                if ($detId !== false && (int) $detId > 0) {
                    $recepcionPorDetalle[(int) $detId] = $cant;
                }
            }
        }

        return DB::connection('taller_rep_db')->transaction(function () use (
            $transferencia,
            $usuario,
            $observaciones,
            $recepcionPorDetalle
        ): TransferenciaInventarioTaller {
            /** @var TransferenciaInventarioTaller $transfBloqueada */
            $transfBloqueada = TransferenciaInventarioTaller::query()->lockForUpdate()->findOrFail($transferencia->id);

            if ($transfBloqueada->estado !== TransferenciaInventarioTaller::ESTADO_EN_TRANSITO) {
                throw ValidationException::withMessages([
                    'transferencia' => 'La transferencia ya no está pendiente de recepción.',
                ]);
            }

            /** @var AlmacenTaller $destino */
            $destino = AlmacenTaller::query()->with('sucursal')->lockForUpdate()->findOrFail($transfBloqueada->almacen_destino_id);
            if (! $destino->activo || ! $destino->permite_existencias) {
                throw ValidationException::withMessages([
                    'almacen_destino_id' => 'El almacén de destino está inactivo o no permite existencias.',
                ]);
            }

            $transitoId = $transfBloqueada->almacen_transito_id;
            /** @var AlmacenTaller|null $transito */
            $transito = $transitoId !== null
                ? AlmacenTaller::query()->lockForUpdate()->find($transitoId)
                : null;

            /** @var Collection<int, TransferenciaInventarioTallerDetalle> $detalles */
            $detalles = $transfBloqueada->detalles()->with('producto')->lockForUpdate()->get();
            $productoIds = $detalles->pluck('producto_id')->all();
            sort($productoIds);
            ProductoTaller::query()->whereIn('id', $productoIds)->lockForUpdate()->get();

            $ahora = now();
            $grupoId = $transfBloqueada->grupo_id;

            foreach ($detalles as $detalle) {
                $cantidadDespachada = (string) $detalle->cantidad_despachada;
                $cantidadRecibida = array_key_exists($detalle->id, $recepcionPorDetalle)
                    ? $recepcionPorDetalle[$detalle->id]
                    : $cantidadDespachada;

                if ($cantidadRecibida === '' || bccomp($cantidadRecibida, '0.0000', 4) === -1) {
                    throw ValidationException::withMessages([
                        'lineas' => "La cantidad recibida para {$detalle->producto->nombre} no es válida.",
                    ]);
                }

                if (bccomp($cantidadRecibida, $cantidadDespachada, 4) === 1) {
                    throw ValidationException::withMessages([
                        'lineas' => "La cantidad recibida ({$cantidadRecibida}) no puede superar lo despachado ({$cantidadDespachada}).",
                    ]);
                }

                $costoUnitario = (string) $detalle->costo_unitario;
                $detalle->update(['cantidad_recibida' => $cantidadRecibida]);

                // 1. Descargar del almacén virtual de traspaso/tránsito
                if ($transito !== null) {
                    /** @var ExistenciaAlmacenTaller|null $existenciaTrns */
                    $existenciaTrns = ExistenciaAlmacenTaller::query()
                        ->where('almacen_id', $transito->id)
                        ->where('producto_id', $detalle->producto_id)
                        ->lockForUpdate()
                        ->first();

                    if ($existenciaTrns !== null) {
                        $cantTrnsAnt = (string) $existenciaTrns->cantidad;
                        $cantTrnsNueva = bcsub($cantTrnsAnt, $cantidadDespachada, 4);
                        if (bccomp($cantTrnsNueva, '0.0000', 4) === -1) {
                            $cantTrnsNueva = '0.0000';
                        }
                        $existenciaTrns->update(['cantidad' => $cantTrnsNueva]);
                    }
                }

                // 2. Ingresar al almacén destino y recalcular costo promedio ponderado
                /** @var ExistenciaAlmacenTaller|null $existenciaDestino */
                $existenciaDestino = ExistenciaAlmacenTaller::query()
                    ->where('almacen_id', $destino->id)
                    ->where('producto_id', $detalle->producto_id)
                    ->lockForUpdate()
                    ->first();

                $cantDestAnt = $existenciaDestino !== null ? (string) $existenciaDestino->cantidad : '0.0000';
                $costoDestAnt = $existenciaDestino !== null ? (string) $existenciaDestino->costo_promedio : '0.0000';

                $cantDestNueva = bcadd($cantDestAnt, $cantidadRecibida, 4);
                $valorAnt = bcmul($cantDestAnt, $costoDestAnt, 8);
                $valorIng = bcmul($cantidadRecibida, $costoUnitario, 8);
                $valorTot = bcadd($valorAnt, $valorIng, 8);
                $costoDestNuevo = bccomp($cantDestNueva, '0.0000', 4) === 1
                    ? bcdiv($valorTot, $cantDestNueva, 4)
                    : $costoDestAnt;

                if ($existenciaDestino === null) {
                    ExistenciaAlmacenTaller::query()->create([
                        'empresa_id' => 1,
                        'almacen_id' => $destino->id,
                        'producto_id' => $detalle->producto_id,
                        'cantidad' => $cantDestNueva,
                        'cantidad_reservada' => '0.0000',
                        'costo_promedio' => $costoDestNuevo,
                    ]);
                } else {
                    $existenciaDestino->update([
                        'cantidad' => $cantDestNueva,
                        'costo_promedio' => $costoDestNuevo,
                    ]);
                }

                MovimientoInventarioTaller::query()->create([
                    'empresa_id' => 1,
                    'almacen_id' => $destino->id,
                    'producto_id' => $detalle->producto_id,
                    'grupo_id' => $grupoId,
                    'folio' => 'MOV-' . $ahora->format('YmdHisv') . '-' . Str::upper(Str::random(6)),
                    'tipo' => MovimientoInventarioTaller::TIPO_TRANSFERENCIA_ENTRADA,
                    'cantidad' => $cantidadRecibida,
                    'existencia_anterior' => $cantDestAnt,
                    'existencia_nueva' => $cantDestNueva,
                    'costo_unitario' => $costoUnitario,
                    'referencia_tipo' => TransferenciaInventarioTaller::class,
                    'referencia_id' => $transfBloqueada->id,
                    'motivo' => "Recepción de traspaso ({$transfBloqueada->folio}): {$transfBloqueada->motivo}",
                    'usuario_id' => $usuario?->id,
                    'ocurrido_at' => $ahora,
                ]);
            }

            $transfBloqueada->update([
                'estado' => TransferenciaInventarioTaller::ESTADO_COMPLETADA,
                'recibido_por_id' => $usuario?->id,
                'recibido_at' => $ahora,
                'observaciones_recepcion' => $observaciones,
            ]);

            /** @var TransferenciaInventarioTaller $actualizada */
            $actualizada = $transfBloqueada->fresh([
                'almacenOrigen',
                'almacenDestino',
                'almacenTransito',
                'detalles.producto',
            ]) ?? $transfBloqueada;

            return $actualizada;
        }, 3);
    }

    /**
     * Cancela/rechaza una transferencia mientras aún está en estado solicitada.
     */
    public function cancelarSolicitud(
        TransferenciaInventarioTaller $transferencia,
        ?UsuarioTaller $usuario,
        string $motivoCancelacion
    ): TransferenciaInventarioTaller {
        ContextoPropietarioTaller::validarPropietario($usuario);

        if ($transferencia->estado !== TransferenciaInventarioTaller::ESTADO_SOLICITADA) {
            throw ValidationException::withMessages([
                'transferencia' => 'Solo se pueden cancelar transferencias en estado solicitada.',
            ]);
        }

        $motivoLimpio = trim($motivoCancelacion);
        if ($motivoLimpio === '') {
            throw ValidationException::withMessages([
                'observaciones_despacho' => 'Debes indicar el motivo de cancelación o rechazo.',
            ]);
        }

        return DB::connection('taller_rep_db')->transaction(function () use ($transferencia, $usuario, $motivoLimpio): TransferenciaInventarioTaller {
            /** @var TransferenciaInventarioTaller $transfBloqueada */
            $transfBloqueada = TransferenciaInventarioTaller::query()->lockForUpdate()->findOrFail($transferencia->id);

            $transfBloqueada->update([
                'estado' => TransferenciaInventarioTaller::ESTADO_CANCELADA,
                'observaciones_despacho' => $motivoLimpio,
                'despachado_por_id' => $usuario?->id,
                'despachado_at' => now(),
            ]);

            /** @var TransferenciaInventarioTaller $actualizada */
            $actualizada = $transfBloqueada->fresh(['almacenOrigen', 'almacenDestino', 'detalles.producto']) ?? $transfBloqueada;

            return $actualizada;
        }, 3);
    }

    /**
     * @param  array<string, mixed>  $datos
     * @return array{
     *     motivo: string,
     *     lineas: list<array{producto_id: int, cantidad: string}>
     * }
     */
    private function normalizarSolicitud(array $datos): array
    {
        $motivo = is_string($datos['motivo'] ?? null) ? trim((string) $datos['motivo']) : '';
        $lineas = [];

        if (is_array($datos['lineas'] ?? null)) {
            foreach ($datos['lineas'] as $linea) {
                if (! is_array($linea)) {
                    continue;
                }
                $prodId = filter_var($linea['producto_id'] ?? null, FILTER_VALIDATE_INT);
                $cant = $this->normalizarDecimal($linea['cantidad'] ?? null);
                $lineas[] = [
                    'producto_id' => $prodId !== false ? (int) $prodId : 0,
                    'cantidad' => $cant,
                ];
            }
        }

        return [
            'motivo' => $motivo,
            'lineas' => $lineas,
        ];
    }

    /**
     * @param  array{
     *     motivo: string,
     *     lineas: list<array{producto_id: int, cantidad: string}>
     * }  $datos
     */
    private function validarSolicitud(AlmacenTaller $origen, AlmacenTaller $destino, array $datos): void
    {
        if ($origen->id === $destino->id) {
            throw ValidationException::withMessages([
                'almacen_destino_id' => 'El almacén de destino debe ser distinto al almacén de origen.',
            ]);
        }

        if ($origen->es_virtual || $destino->es_virtual) {
            throw ValidationException::withMessages([
                'almacen_id' => 'No puedes usar el almacén virtual de traspaso como origen o destino directo.',
            ]);
        }

        if (! $origen->activo || ! $origen->permite_existencias) {
            throw ValidationException::withMessages([
                'almacen_origen_id' => 'El almacén de origen está inactivo o no permite existencias.',
            ]);
        }

        if (! $destino->activo || ! $destino->permite_existencias) {
            throw ValidationException::withMessages([
                'almacen_destino_id' => 'El almacén de destino está inactivo o no permite existencias.',
            ]);
        }

        if ($origen->sucursal_id === null || ! $origen->sucursal?->activa) {
            throw ValidationException::withMessages([
                'almacen_origen_id' => 'La sucursal del almacén de origen no existe o está inactiva.',
            ]);
        }

        if ($destino->sucursal_id === null || ! $destino->sucursal?->activa) {
            throw ValidationException::withMessages([
                'almacen_destino_id' => 'La sucursal del almacén de destino no existe o está inactiva.',
            ]);
        }

        if ($datos['motivo'] === '' || mb_strlen($datos['motivo']) > 1000) {
            throw ValidationException::withMessages([
                'motivo' => 'Debes indicar un motivo válido de hasta 1000 caracteres.',
            ]);
        }

        if (empty($datos['lineas'])) {
            throw ValidationException::withMessages([
                'lineas' => 'Debes incluir al menos un producto o refacción en el traspaso.',
            ]);
        }

        $ids = array_column($datos['lineas'], 'producto_id');
        if (count($ids) !== count(array_unique($ids))) {
            throw ValidationException::withMessages([
                'lineas' => 'No puedes incluir productos duplicados en el mismo traspaso.',
            ]);
        }

        foreach ($datos['lineas'] as $idx => $linea) {
            if ($linea['producto_id'] <= 0) {
                throw ValidationException::withMessages([
                    "lineas.{$idx}.producto_id" => 'El producto seleccionado no es válido.',
                ]);
            }
            if ($linea['cantidad'] === '' || bccomp($linea['cantidad'], '0.0000', 4) !== 1) {
                throw ValidationException::withMessages([
                    "lineas.{$idx}.cantidad" => 'La cantidad debe ser mayor a cero (hasta 4 decimales).',
                ]);
            }
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
}