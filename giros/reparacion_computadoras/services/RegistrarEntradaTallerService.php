<?php

namespace Giros\ReparacionComputadoras\Services;

use Giros\ReparacionComputadoras\Models\AlmacenTaller;
use Giros\ReparacionComputadoras\Models\ExistenciaAlmacenTaller;
use Giros\ReparacionComputadoras\Models\MovimientoInventarioTaller;
use Giros\ReparacionComputadoras\Models\ProductoTaller;
use Giros\ReparacionComputadoras\Models\RecepcionInventarioTaller;
use Giros\ReparacionComputadoras\Models\RecepcionInventarioTallerDetalle;
use Giros\ReparacionComputadoras\Models\UsuarioTaller;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RegistrarEntradaTallerService
{
    /**
     * Registra una recepción/entrada de una o varias líneas en un almacén,
     * recalculando el costo promedio ponderado y registrando movimientos inmutables.
     *
     * @param  array{
     *     tipo?: mixed,
     *     referencia_externa?: mixed,
     *     motivo?: mixed,
     *     lineas?: mixed
     * }  $datos
     */
    public function ejecutar(
        AlmacenTaller $almacen,
        ?UsuarioTaller $usuario,
        array $datos
    ): RecepcionInventarioTaller {
        ContextoPropietarioTaller::validarPropietario($usuario);

        $datosNormalizados = $this->normalizarDatos($datos);
        $this->validar($almacen, $datosNormalizados);

        return DB::connection('taller_rep_db')->transaction(function () use ($almacen, $usuario, $datosNormalizados): RecepcionInventarioTaller {
            /** @var AlmacenTaller $almacenBloqueado */
            $almacenBloqueado = AlmacenTaller::query()
                ->with('sucursal')
                ->lockForUpdate()
                ->findOrFail($almacen->id);

            $productoIds = array_column($datosNormalizados['lineas'], 'producto_id');
            sort($productoIds);

            ProductoTaller::query()
                ->whereIn('id', $productoIds)
                ->orderBy('id', 'asc')
                ->lockForUpdate()
                ->get();

            $this->validar($almacenBloqueado, $datosNormalizados);

            $grupoId = (string) Str::uuid();
            $folioRecepcion = $this->generarFolioRecepcion();
            $recibidoAt = now();
            $totalLineas = count($datosNormalizados['lineas']);
            $importeTotal = '0.0000';

            foreach ($datosNormalizados['lineas'] as $linea) {
                $importeTotal = bcadd($importeTotal, $linea['subtotal'], 4);
            }

            $tipoMovimiento = match ($datosNormalizados['tipo']) {
                RecepcionInventarioTaller::TIPO_DEVOLUCION => MovimientoInventarioTaller::TIPO_ENTRADA,
                RecepcionInventarioTaller::TIPO_AJUSTE_POSITIVO => MovimientoInventarioTaller::TIPO_AJUSTE_POSITIVO,
                default => MovimientoInventarioTaller::TIPO_ENTRADA,
            };

            /** @var RecepcionInventarioTaller $recepcion */
            $recepcion = RecepcionInventarioTaller::query()->create([
                'empresa_id' => 1,
                'almacen_id' => $almacenBloqueado->id,
                'grupo_id' => $grupoId,
                'folio' => $folioRecepcion,
                'tipo' => $datosNormalizados['tipo'],
                'referencia_externa' => $datosNormalizados['referencia_externa'],
                'motivo' => $datosNormalizados['motivo'],
                'total_lineas' => $totalLineas,
                'importe_total' => $importeTotal,
                'recibido_por_id' => $usuario?->id,
                'recibido_at' => $recibidoAt,
            ]);

            foreach ($datosNormalizados['lineas'] as $linea) {
                $productoId = $linea['producto_id'];
                $cantidadEntrada = $linea['cantidad'];
                $costoEntrada = $linea['costo_unitario'];
                $subtotal = $linea['subtotal'];

                RecepcionInventarioTallerDetalle::query()->create([
                    'recepcion_inventario_taller_id' => $recepcion->id,
                    'producto_id' => $productoId,
                    'cantidad' => $cantidadEntrada,
                    'costo_unitario' => $costoEntrada,
                    'subtotal' => $subtotal,
                ]);

                /** @var ExistenciaAlmacenTaller|null $existencia */
                $existencia = ExistenciaAlmacenTaller::query()
                    ->where('almacen_id', $almacenBloqueado->id)
                    ->where('producto_id', $productoId)
                    ->lockForUpdate()
                    ->first();

                $cantidadAnterior = $existencia !== null ? (string) $existencia->cantidad : '0.0000';
                $costoPromedioAnterior = $existencia !== null ? (string) $existencia->costo_promedio : '0.0000';

                $cantidadNueva = bcadd($cantidadAnterior, $cantidadEntrada, 4);

                // Costo Promedio Ponderado:
                // ((cantidad_anterior * costo_anterior) + (cantidad_entrada * costo_entrada)) / cantidad_nueva
                $valorAnterior = bcmul($cantidadAnterior, $costoPromedioAnterior, 8);
                $valorEntrada = bcmul($cantidadEntrada, $costoEntrada, 8);
                $valorTotal = bcadd($valorAnterior, $valorEntrada, 8);
                $costoPromedioNuevo = bccomp($cantidadNueva, '0.0000', 4) === 1
                    ? bcdiv($valorTotal, $cantidadNueva, 4)
                    : '0.0000';

                if ($existencia === null) {
                    ExistenciaAlmacenTaller::query()->create([
                        'empresa_id' => 1,
                        'almacen_id' => $almacenBloqueado->id,
                        'producto_id' => $productoId,
                        'cantidad' => $cantidadNueva,
                        'cantidad_reservada' => '0.0000',
                        'costo_promedio' => $costoPromedioNuevo,
                    ]);
                } else {
                    $existencia->update([
                        'cantidad' => $cantidadNueva,
                        'costo_promedio' => $costoPromedioNuevo,
                    ]);
                }

                MovimientoInventarioTaller::query()->create([
                    'empresa_id' => 1,
                    'almacen_id' => $almacenBloqueado->id,
                    'producto_id' => $productoId,
                    'grupo_id' => $grupoId,
                    'folio' => $this->generarFolioMovimiento(),
                    'tipo' => $tipoMovimiento,
                    'cantidad' => $cantidadEntrada,
                    'existencia_anterior' => $cantidadAnterior,
                    'existencia_nueva' => $cantidadNueva,
                    'costo_unitario' => $costoEntrada,
                    'referencia_tipo' => RecepcionInventarioTaller::class,
                    'referencia_id' => $recepcion->id,
                    'motivo' => $datosNormalizados['motivo'],
                    'usuario_id' => $usuario?->id,
                    'ocurrido_at' => $recibidoAt,
                ]);
            }

            /** @var RecepcionInventarioTaller $actualizada */
            $actualizada = $recepcion->fresh(['almacen', 'detalles.producto']) ?? $recepcion;

            return $actualizada;
        }, 3);
    }

    /**
     * @param  array<string, mixed>  $datos
     * @return array{
     *     tipo: string,
     *     referencia_externa: string|null,
     *     motivo: string,
     *     lineas: list<array{
     *         producto_id: int,
     *         cantidad: string,
     *         costo_unitario: string,
     *         subtotal: string
     *     }>
     * }
     */
    private function normalizarDatos(array $datos): array
    {
        $tipoRaw = $datos['tipo'] ?? RecepcionInventarioTaller::TIPO_COMPRA;
        $tipo = is_string($tipoRaw) ? Str::lower(trim($tipoRaw)) : '';

        $refRaw = $datos['referencia_externa'] ?? null;
        $referencia = is_string($refRaw) && trim($refRaw) !== '' ? trim($refRaw) : null;

        $motivoRaw = $datos['motivo'] ?? '';
        $motivo = is_string($motivoRaw) ? trim($motivoRaw) : '';

        $lineasNormalizadas = [];
        if (is_array($datos['lineas'] ?? null)) {
            foreach ($datos['lineas'] as $linea) {
                if (! is_array($linea)) {
                    continue;
                }

                $productoId = filter_var($linea['producto_id'] ?? null, FILTER_VALIDATE_INT);
                $cantidad = $this->normalizarDecimal($linea['cantidad'] ?? null);
                $costoUnitario = $this->normalizarDecimal($linea['costo_unitario'] ?? null);
                $subtotal = ($cantidad !== '' && $costoUnitario !== '')
                    ? bcmul($cantidad, $costoUnitario, 4)
                    : '';

                $lineasNormalizadas[] = [
                    'producto_id' => $productoId !== false ? (int) $productoId : 0,
                    'cantidad' => $cantidad,
                    'costo_unitario' => $costoUnitario,
                    'subtotal' => $subtotal,
                ];
            }
        }

        return [
            'tipo' => $tipo,
            'referencia_externa' => $referencia,
            'motivo' => $motivo,
            'lineas' => $lineasNormalizadas,
        ];
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

    /**
     * @param  array{
     *     tipo: string,
     *     referencia_externa: string|null,
     *     motivo: string,
     *     lineas: list<array{
     *         producto_id: int,
     *         cantidad: string,
     *         costo_unitario: string,
     *         subtotal: string
     *     }>
     * }  $datos
     */
    private function validar(AlmacenTaller $almacen, array $datos): void
    {
        if (! $almacen->activo || ! $almacen->permite_existencias) {
            throw ValidationException::withMessages([
                'almacen_id' => 'El almacén está inactivo o no permite manejar existencias.',
            ]);
        }

        if (! $almacen->es_virtual) {
            $sucursal = $almacen->sucursal;
            if ($sucursal === null || ! $sucursal->activa) {
                throw ValidationException::withMessages([
                    'sucursal_id' => 'La sucursal asociada al almacén físico no existe o está inactiva.',
                ]);
            }
        }

        if (! in_array($datos['tipo'], RecepcionInventarioTaller::tiposPermitidos(), true)) {
            throw ValidationException::withMessages([
                'tipo' => 'El tipo de entrada/recepción no es válido.',
            ]);
        }

        if ($datos['referencia_externa'] !== null && mb_strlen($datos['referencia_externa']) > 150) {
            throw ValidationException::withMessages([
                'referencia_externa' => 'La referencia externa no debe superar los 150 caracteres.',
            ]);
        }

        if ($datos['motivo'] === '' || mb_strlen($datos['motivo']) > 1000) {
            throw ValidationException::withMessages([
                'motivo' => 'Debes indicar un motivo válido de hasta 1000 caracteres.',
            ]);
        }

        if (empty($datos['lineas'])) {
            throw ValidationException::withMessages([
                'lineas' => 'La entrada debe incluir al menos un producto o refacción.',
            ]);
        }

        $productoIds = array_column($datos['lineas'], 'producto_id');
        if (count($productoIds) !== count(array_unique($productoIds))) {
            throw ValidationException::withMessages([
                'lineas' => 'No se pueden incluir productos duplicados en la misma entrada.',
            ]);
        }

        foreach ($datos['lineas'] as $indice => $linea) {
            if ($linea['producto_id'] <= 0) {
                throw ValidationException::withMessages([
                    "lineas.{$indice}.producto_id" => 'El producto seleccionado no es válido.',
                ]);
            }
            if ($linea['cantidad'] === '' || bccomp($linea['cantidad'], '0.0000', 4) !== 1) {
                throw ValidationException::withMessages([
                    "lineas.{$indice}.cantidad" => 'La cantidad debe ser mayor que cero (hasta 4 decimales).',
                ]);
            }
            if ($linea['costo_unitario'] === '' || bccomp($linea['costo_unitario'], '0.0000', 4) === -1) {
                throw ValidationException::withMessages([
                    "lineas.{$indice}.costo_unitario" => 'El costo unitario debe ser igual o mayor a cero (hasta 4 decimales).',
                ]);
            }
        }

        /** @var Collection<int, ProductoTaller> $productos */
        $productos = ProductoTaller::query()->whereIn('id', $productoIds)->get();

        foreach ($productoIds as $indice => $productoId) {
            /** @var ProductoTaller|null $producto */
            $producto = $productos->firstWhere('id', $productoId);
            if ($producto === null) {
                throw ValidationException::withMessages([
                    "lineas.{$indice}.producto_id" => 'Uno o más productos seleccionados no existen.',
                ]);
            }
            if (! $producto->activo) {
                throw ValidationException::withMessages([
                    "lineas.{$indice}.producto_id" => "El producto {$producto->nombre} está inactivo.",
                ]);
            }
        }
    }

    private function generarFolioRecepcion(): string
    {
        return 'REC-' . now()->format('YmdHisv') . '-' . Str::upper(Str::random(6));
    }

    private function generarFolioMovimiento(): string
    {
        return 'MOV-' . now()->format('YmdHisv') . '-' . Str::upper(Str::random(6));
    }
}