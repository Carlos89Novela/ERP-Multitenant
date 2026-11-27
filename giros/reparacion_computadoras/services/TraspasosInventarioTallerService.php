<?php

namespace Giros\ReparacionComputadoras\Services;

use Giros\ReparacionComputadoras\Models\AlmacenTaller;
use Giros\ReparacionComputadoras\Models\ExistenciaAlmacenTaller;
use Giros\ReparacionComputadoras\Models\MovimientoInventarioTaller;
use Giros\ReparacionComputadoras\Models\NotificacionTraspasoTaller;
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
    public const ESTADO_PARCIAL_PENDIENTE = 'parcial_pendiente';

    public function __construct(
        private GestionAlmacenesTallerService $almacenesService
    ) {}

    private function crearNotificacion(
        int $transferenciaId,
        int $almacenDestinatarioId,
        string $evento,
        string $titulo,
        string $mensaje
    ): void {
        NotificacionTraspasoTaller::query()->create([
            'empresa_id' => 1,
            'transferencia_id' => $transferenciaId,
            'almacen_destinatario_id' => $almacenDestinatarioId,
            'evento' => $evento,
            'titulo' => $titulo,
            'mensaje' => $mensaje,
            'leida' => false,
        ]);
    }

    public function solicitarTraspaso(
        AlmacenTaller $origen,
        AlmacenTaller $destino,
        ?UsuarioTaller $usuario,
        array $datos
    ): TransferenciaInventarioTaller {
        ContextoPropietarioTaller::validarOperadorInventario($usuario);

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

            $this->crearNotificacion(
                $transferencia->id,
                $origenBloqueado->id,
                'solicitud_traspaso',
                "Nueva solicitud de traspaso {$transferencia->folio}",
                "{$destinoBloqueado->nombre} solicita traspaso desde {$origenBloqueado->nombre}. Motivo: {$transferencia->motivo}"
            );

            /** @var TransferenciaInventarioTaller $actualizada */
            $actualizada = $transferencia->fresh(['almacenOrigen', 'almacenDestino', 'detalles.producto']) ?? $transferencia;

            return $actualizada;
        }, 3);
    }

    public function despacharATransito(
        TransferenciaInventarioTaller $transferencia,
        ?UsuarioTaller $usuario,
        array $datos = []
    ): TransferenciaInventarioTaller {
        ContextoPropietarioTaller::validarOperadorInventario($usuario);

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
            /** @var AlmacenTaller $destino */
            $destino = AlmacenTaller::query()->with('sucursal')->lockForUpdate()->findOrFail($transfBloqueada->almacen_destino_id);
            $transito = $this->almacenesService->asegurarAlmacenTransito();
            /** @var AlmacenTaller $transitoBloqueado */
            $transitoBloqueado = AlmacenTaller::query()->lockForUpdate()->findOrFail($transito->id);

            /** @var Collection<int, TransferenciaInventarioTallerDetalle> $detalles */
            $detalles = $transfBloqueada->detalles()->with('producto')->lockForUpdate()->get();
            $productoIds = $detalles->pluck('producto_id')->all();
            sort($productoIds);
            ProductoTaller::query()->whereIn('id', $productoIds)->lockForUpdate()->get();

                        $rolUsuario = strtolower(trim((string) ($usuario->rol_empresa ?? '')));
            $esPropGlobal = ($rolUsuario === 'propietario');
            $sucursalOrigenId = $origen->sucursal_id !== null ? (int) $origen->sucursal_id : null;
            $sucursalUsuarioId = $usuario?->sucursal_id !== null ? (int) $usuario->sucursal_id : null;

            if (! $esPropGlobal && ($sucursalOrigenId === null || $sucursalUsuarioId !== $sucursalOrigenId)) {
                throw new AuthorizationException('Solo el gerente/subgerente de la sucursal origen o el propietario pueden despachar este traspaso.');
            }

            $totalDespachadoGlobal = '0.0000';
            foreach ($detalles as $detChk) {
                $cChk = array_key_exists($detChk->id, $despachoPorDetalle)
                    ? $despachoPorDetalle[$detChk->id]
                    : (string) $detChk->cantidad_solicitada;
                if (bccomp($cChk, '0.0000', 4) === -1) {
                    throw ValidationException::withMessages([
                        'lineas' => "La cantidad a despachar para {$detChk->producto->nombre} no puede ser negativa.",
                    ]);
                }
                $totalDespachadoGlobal = bcadd($totalDespachadoGlobal, $cChk, 4);
            }

            if (bccomp($totalDespachadoGlobal, '0.0000', 4) !== 1) {
                throw ValidationException::withMessages([
                    'lineas' => 'Debes despachar al menos una cantidad mayor a cero en alguno de los productos.',
                ]);
            }

            $ahora = now();
            $grupoId = $transfBloqueada->grupo_id;
            $costoTotal = '0.0000';

            foreach ($detalles as $detalle) {
                $cantidadDespacho = array_key_exists($detalle->id, $despachoPorDetalle)
                    ? $despachoPorDetalle[$detalle->id]
                    : (string) $detalle->cantidad_solicitada;

                if (bccomp($cantidadDespacho, '0.0000', 4) === 0) {
                    $detalle->update([
                        'cantidad' => '0.0000',
                        'cantidad_despachada' => '0.0000',
                        'costo_unitario' => '0.0000',
                        'subtotal' => '0.0000',
                    ]);
                    continue;
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

            $this->crearNotificacion(
                $transfBloqueada->id,
                $destino->id,
                'traspaso_en_transito',
                "Traspaso {$transfBloqueada->folio} aceptado y en tránsito",
                "{$origen->nombre} despachó el traspaso hacia almacén de tránsito. Verifica físicamente al recibir en {$destino->nombre}."
            );

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

    public function recibirEnDestino(
        TransferenciaInventarioTaller $transferencia,
        ?UsuarioTaller $usuario,
        array $datos = []
    ): TransferenciaInventarioTaller {
        ContextoPropietarioTaller::validarOperadorInventario($usuario);

        if (! in_array($transferencia->estado, [
            TransferenciaInventarioTaller::ESTADO_EN_TRANSITO,
            self::ESTADO_PARCIAL_PENDIENTE,
        ], true)) {
            throw ValidationException::withMessages([
                'transferencia' => 'La transferencia no tiene mercancía pendiente de recepción.',
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

            if (! in_array($transfBloqueada->estado, [
                TransferenciaInventarioTaller::ESTADO_EN_TRANSITO,
                self::ESTADO_PARCIAL_PENDIENTE,
            ], true)) {
                throw ValidationException::withMessages([
                    'transferencia' => 'La transferencia ya no está pendiente de recepción.',
                ]);
            }

            /** @var AlmacenTaller $origen */
            $origen = AlmacenTaller::query()->with('sucursal')->lockForUpdate()->findOrFail($transfBloqueada->almacen_origen_id);
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
            $huboRecepcionEnEstaOperacion = false;
            $resumenFaltantes = [];
            $resumenRecibidos = [];
            $hayPendientesRestantes = false;

            foreach ($detalles as $detalle) {
                $cantidadDespachada = (string) $detalle->cantidad_despachada;
                $cantidadRecibidaPrevia = (string) $detalle->cantidad_recibida;
                $pendienteAntes = bcsub($cantidadDespachada, $cantidadRecibidaPrevia, 4);
                if (bccomp($pendienteAntes, '0.0000', 4) === -1) {
                    $pendienteAntes = '0.0000';
                }

                $cantidadRecibidaAhora = array_key_exists($detalle->id, $recepcionPorDetalle)
                    ? $recepcionPorDetalle[$detalle->id]
                    : $pendienteAntes;

                if ($cantidadRecibidaAhora === '' || bccomp($cantidadRecibidaAhora, '0.0000', 4) === -1) {
                    throw ValidationException::withMessages([
                        'lineas' => "La cantidad recibida para {$detalle->producto->nombre} no es válida.",
                    ]);
                }

                if (bccomp($cantidadRecibidaAhora, $pendienteAntes, 4) === 1) {
                    throw ValidationException::withMessages([
                        'lineas' => "No puedes recibir ({$cantidadRecibidaAhora}) más de lo pendiente ({$pendienteAntes}) para {$detalle->producto->nombre}.",
                    ]);
                }

                $nuevaCantidadRecibidaTotal = bcadd($cantidadRecibidaPrevia, $cantidadRecibidaAhora, 4);
                $pendienteDespues = bcsub($cantidadDespachada, $nuevaCantidadRecibidaTotal, 4);
                if (bccomp($pendienteDespues, '0.0000', 4) === -1) {
                    $pendienteDespues = '0.0000';
                }

                $costoUnitario = (string) $detalle->costo_unitario;
                $detalle->update(['cantidad_recibida' => $nuevaCantidadRecibidaTotal]);

                if (bccomp($cantidadRecibidaAhora, '0.0000', 4) === 1) {
                    $huboRecepcionEnEstaOperacion = true;
                    $resumenRecibidos[] = "{$detalle->producto->nombre}: +{$cantidadRecibidaAhora} (total {$nuevaCantidadRecibidaTotal}/{$cantidadDespachada})";

                    if ($transito !== null) {
                        /** @var ExistenciaAlmacenTaller|null $existenciaTrns */
                        $existenciaTrns = ExistenciaAlmacenTaller::query()
                            ->where('almacen_id', $transito->id)
                            ->where('producto_id', $detalle->producto_id)
                            ->lockForUpdate()
                            ->first();

                        if ($existenciaTrns !== null) {
                            $cantTrnsAnt = (string) $existenciaTrns->cantidad;
                            $cantTrnsNueva = bcsub($cantTrnsAnt, $cantidadRecibidaAhora, 4);
                            if (bccomp($cantTrnsNueva, '0.0000', 4) === -1) {
                                $cantTrnsNueva = '0.0000';
                            }
                            $existenciaTrns->update(['cantidad' => $cantTrnsNueva]);
                        }
                    }

                    /** @var ExistenciaAlmacenTaller|null $existenciaDestino */
                    $existenciaDestino = ExistenciaAlmacenTaller::query()
                        ->where('almacen_id', $destino->id)
                        ->where('producto_id', $detalle->producto_id)
                        ->lockForUpdate()
                        ->first();

                    $cantDestAnt = $existenciaDestino !== null ? (string) $existenciaDestino->cantidad : '0.0000';
                    $costoDestAnt = $existenciaDestino !== null ? (string) $existenciaDestino->costo_promedio : '0.0000';

                    $cantDestNueva = bcadd($cantDestAnt, $cantidadRecibidaAhora, 4);
                    $valorAnt = bcmul($cantDestAnt, $costoDestAnt, 8);
                    $valorIng = bcmul($cantidadRecibidaAhora, $costoUnitario, 8);
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
                        'cantidad' => $cantidadRecibidaAhora,
                        'existencia_anterior' => $cantDestAnt,
                        'existencia_nueva' => $cantDestNueva,
                        'costo_unitario' => $costoUnitario,
                        'referencia_tipo' => TransferenciaInventarioTaller::class,
                        'referencia_id' => $transfBloqueada->id,
                        'motivo' => "Recepción en traspaso ({$transfBloqueada->folio}): {$transfBloqueada->motivo}",
                        'usuario_id' => $usuario?->id,
                        'ocurrido_at' => $ahora,
                    ]);
                }

                if (bccomp($pendienteDespues, '0.0000', 4) === 1) {
                    $hayPendientesRestantes = true;
                    $resumenFaltantes[] = "{$detalle->producto->nombre}: faltan {$pendienteDespues}";
                }
            }

            if (! $huboRecepcionEnEstaOperacion && ! $hayPendientesRestantes) {
                throw ValidationException::withMessages([
                    'lineas' => 'Debes indicar una cantidad recibida mayor a cero.',
                ]);
            }

            $estadoFinal = $hayPendientesRestantes
                ? self::ESTADO_PARCIAL_PENDIENTE
                : TransferenciaInventarioTaller::ESTADO_COMPLETADA;

            $transfBloqueada->update([
                'estado' => $estadoFinal,
                'recibido_por_id' => $usuario?->id,
                'recibido_at' => $ahora,
                'observaciones_recepcion' => $observaciones,
            ]);

            if ($hayPendientesRestantes) {
                $textoRec = empty($resumenRecibidos) ? 'Sin ingreso en este paso.' : implode(' | ', $resumenRecibidos);
                $textoFalt = implode(' | ', $resumenFaltantes);
                $this->crearNotificacion(
                    $transfBloqueada->id,
                    $origen->id,
                    'recepcion_parcial_pendiente',
                    "Recepción parcial en {$transfBloqueada->folio} (faltante pendiente)",
                    "{$destino->nombre} registró recepción parcial ({$textoRec}). Queda pendiente: {$textoFalt}. El traspaso sigue abierto hasta concluir o cancelar el faltante."
                );
            } else {
                $this->crearNotificacion(
                    $transfBloqueada->id,
                    $origen->id,
                    'traspaso_completado',
                    "Traspaso {$transfBloqueada->folio} completado y liberado",
                    "{$destino->nombre} concluyó la recepción del traspaso {$transfBloqueada->folio}."
                );
            }

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

    public function cancelarDetalleFaltante(
        TransferenciaInventarioTaller $transferencia,
        int $detalleId,
        ?UsuarioTaller $usuario,
        string $motivo = 'Cancelado por falta de existencia'
    ): TransferenciaInventarioTaller {
        ContextoPropietarioTaller::validarOperadorInventario($usuario);

        if (! in_array($transferencia->estado, [
            TransferenciaInventarioTaller::ESTADO_EN_TRANSITO,
            self::ESTADO_PARCIAL_PENDIENTE,
        ], true)) {
            throw ValidationException::withMessages([
                'transferencia' => 'Solo se pueden cancelar faltantes en transferencias en tránsito o parciales.',
            ]);
        }

        $motivoLimpio = trim($motivo) !== '' ? trim($motivo) : 'Cancelado por falta de existencia';

        return DB::connection('taller_rep_db')->transaction(function () use (
            $transferencia,
            $detalleId,
            $usuario,
            $motivoLimpio
        ): TransferenciaInventarioTaller {
            /** @var TransferenciaInventarioTaller $transfBloqueada */
            $transfBloqueada = TransferenciaInventarioTaller::query()->lockForUpdate()->findOrFail($transferencia->id);
            /** @var AlmacenTaller $origen */
            $origen = AlmacenTaller::query()->lockForUpdate()->findOrFail($transfBloqueada->almacen_origen_id);
            /** @var AlmacenTaller $destino */
            $destino = AlmacenTaller::query()->lockForUpdate()->findOrFail($transfBloqueada->almacen_destino_id);
            $transito = $transfBloqueada->almacen_transito_id !== null
                ? AlmacenTaller::query()->lockForUpdate()->find($transfBloqueada->almacen_transito_id)
                : null;

            /** @var TransferenciaInventarioTallerDetalle $detalle */
            $detalle = $transfBloqueada->detalles()->with('producto')->lockForUpdate()->findOrFail($detalleId);

            $faltante = bcsub((string) $detalle->cantidad_despachada, (string) $detalle->cantidad_recibida, 4);
            if (bccomp($faltante, '0.0000', 4) !== 1) {
                throw ValidationException::withMessages([
                    'detalle' => "El producto {$detalle->producto->nombre} no tiene faltante pendiente por cancelar.",
                ]);
            }

            $ahora = now();
            $grupoId = $transfBloqueada->grupo_id;
            $costoUnitario = (string) $detalle->costo_unitario;

            $nuevoDespachado = (string) $detalle->cantidad_recibida;
            $detalle->update([
                'cantidad_despachada' => $nuevoDespachado,
                'cantidad' => $nuevoDespachado,
                'subtotal' => bcmul($nuevoDespachado, $costoUnitario, 4),
            ]);

            if ($transito !== null) {
                /** @var ExistenciaAlmacenTaller|null $existenciaTrns */
                $existenciaTrns = ExistenciaAlmacenTaller::query()
                    ->where('almacen_id', $transito->id)
                    ->where('producto_id', $detalle->producto_id)
                    ->lockForUpdate()
                    ->first();

                if ($existenciaTrns !== null) {
                    $cantTrnsAnt = (string) $existenciaTrns->cantidad;
                    $cantTrnsNueva = bcsub($cantTrnsAnt, $faltante, 4);
                    if (bccomp($cantTrnsNueva, '0.0000', 4) === -1) {
                        $cantTrnsNueva = '0.0000';
                    }
                    $existenciaTrns->update(['cantidad' => $cantTrnsNueva]);
                }
            }

            /** @var ExistenciaAlmacenTaller|null $existenciaOrigen */
            $existenciaOrigen = ExistenciaAlmacenTaller::query()
                ->where('almacen_id', $origen->id)
                ->where('producto_id', $detalle->producto_id)
                ->lockForUpdate()
                ->first();

            $cantOrigAnt = $existenciaOrigen !== null ? (string) $existenciaOrigen->cantidad : '0.0000';
            $cantOrigNueva = bcadd($cantOrigAnt, $faltante, 4);
            if ($existenciaOrigen === null) {
                ExistenciaAlmacenTaller::query()->create([
                    'empresa_id' => 1,
                    'almacen_id' => $origen->id,
                    'producto_id' => $detalle->producto_id,
                    'cantidad' => $cantOrigNueva,
                    'cantidad_reservada' => '0.0000',
                    'costo_promedio' => $costoUnitario,
                ]);
            } else {
                $existenciaOrigen->update(['cantidad' => $cantOrigNueva]);
            }

            MovimientoInventarioTaller::query()->create([
                'empresa_id' => 1,
                'almacen_id' => $origen->id,
                'producto_id' => $detalle->producto_id,
                'grupo_id' => $grupoId,
                'folio' => 'MOV-' . $ahora->format('YmdHisv') . '-' . Str::upper(Str::random(6)),
                'tipo' => MovimientoInventarioTaller::TIPO_TRANSFERENCIA_ENTRADA,
                'cantidad' => $faltante,
                'existencia_anterior' => $cantOrigAnt,
                'existencia_nueva' => $cantOrigNueva,
                'costo_unitario' => $costoUnitario,
                'referencia_tipo' => TransferenciaInventarioTaller::class,
                'referencia_id' => $transfBloqueada->id,
                'motivo' => "Retorno de faltante cancelado ({$transfBloqueada->folio}): {$motivoLimpio}",
                'usuario_id' => $usuario?->id,
                'ocurrido_at' => $ahora,
            ]);

            $todosDetalles = $transfBloqueada->detalles()->lockForUpdate()->get();
            $quedanFaltantes = false;
            foreach ($todosDetalles as $detItem) {
                if (bccomp((string) $detItem->cantidad_despachada, (string) $detItem->cantidad_recibida, 4) === 1) {
                    $quedanFaltantes = true;
                    break;
                }
            }

            $estadoFinal = $quedanFaltantes
                ? self::ESTADO_PARCIAL_PENDIENTE
                : TransferenciaInventarioTaller::ESTADO_COMPLETADA;

            $transfBloqueada->update([
                'estado' => $estadoFinal,
                'observaciones_recepcion' => trim(($transfBloqueada->observaciones_recepcion ? $transfBloqueada->observaciones_recepcion . ' | ' : '') . "Faltante {$detalle->producto->nombre} ({$faltante}) cancelado: {$motivoLimpio}"),
            ]);

            $this->crearNotificacion(
                $transfBloqueada->id,
                $destino->id,
                'faltante_cancelado',
                "Faltante cancelado en {$transfBloqueada->folio}: {$detalle->producto->nombre}",
                "Se canceló el faltante de {$faltante} de {$detalle->producto->nombre} ({$motivoLimpio}) sin cancelar todo el traspaso. Estado actual: {$estadoFinal}."
            );

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

    public function cancelarSolicitud(
        TransferenciaInventarioTaller $transferencia,
        ?UsuarioTaller $usuario,
        string $motivoCancelacion
    ): TransferenciaInventarioTaller {
        ContextoPropietarioTaller::validarOperadorInventario($usuario);

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

            $this->crearNotificacion(
                $transfBloqueada->id,
                (int) $transfBloqueada->almacen_destino_id,
                'traspaso_cancelado',
                "Solicitud de traspaso {$transfBloqueada->folio} cancelada",
                "Motivo: {$motivoLimpio}"
            );

            /** @var TransferenciaInventarioTaller $actualizada */
            $actualizada = $transfBloqueada->fresh(['almacenOrigen', 'almacenDestino', 'detalles.producto']) ?? $transfBloqueada;

            return $actualizada;
        }, 3);
    }

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