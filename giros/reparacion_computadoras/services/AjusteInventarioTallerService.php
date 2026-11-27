<?php

namespace Giros\ReparacionComputadoras\Services;

use Giros\ReparacionComputadoras\Models\AlmacenTaller;
use Giros\ReparacionComputadoras\Models\ExistenciaAlmacenTaller;
use Giros\ReparacionComputadoras\Models\MovimientoInventarioTaller;
use Giros\ReparacionComputadoras\Models\ProductoTaller;
use Giros\ReparacionComputadoras\Models\UsuarioTaller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AjusteInventarioTallerService
{
    /**
     * Ajusta la existencia física de un producto en un almacén hacia una cantidad
     * objetivo, registrando únicamente la diferencia como ajuste positivo o negativo.
     *
     * @param  array{cantidad_objetivo?: mixed, motivo?: mixed}  $datos
     */
    public function ejecutar(
        AlmacenTaller $almacen,
        ProductoTaller $producto,
        ?UsuarioTaller $usuario,
        array $datos
    ): ExistenciaAlmacenTaller {
        ContextoPropietarioTaller::validarOperadorInventario($usuario);

        $cantidadObjetivo = $this->normalizarDecimal($datos['cantidad_objetivo'] ?? null);
        $motivo = is_string($datos['motivo'] ?? null) ? trim((string) $datos['motivo']) : '';

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
                'producto_id' => 'El producto o refacción está inactivo.',
            ]);
        }

        if ($cantidadObjetivo === '' || bccomp($cantidadObjetivo, '0.0000', 4) === -1) {
            throw ValidationException::withMessages([
                'cantidad_objetivo' => 'La cantidad objetivo debe ser igual o mayor a cero (hasta 4 decimales).',
            ]);
        }

        if ($motivo === '' || mb_strlen($motivo) > 1000) {
            throw ValidationException::withMessages([
                'motivo' => 'Debes indicar un motivo válido de hasta 1000 caracteres.',
            ]);
        }

        return DB::connection('taller_rep_db')->transaction(function () use (
            $almacen,
            $producto,
            $usuario,
            $cantidadObjetivo,
            $motivo
        ): ExistenciaAlmacenTaller {
            /** @var AlmacenTaller $almacenBloqueado */
            $almacenBloqueado = AlmacenTaller::query()->with('sucursal')->lockForUpdate()->findOrFail($almacen->id);
            /** @var ProductoTaller $productoBloqueado */
            $productoBloqueado = ProductoTaller::query()->lockForUpdate()->findOrFail($producto->id);

            /** @var ExistenciaAlmacenTaller|null $existencia */
            $existencia = ExistenciaAlmacenTaller::query()
                ->where('almacen_id', $almacenBloqueado->id)
                ->where('producto_id', $productoBloqueado->id)
                ->lockForUpdate()
                ->first();

            if ($existencia === null) {
                throw ValidationException::withMessages([
                    'existencia' => 'El producto no tiene existencia registrada en el almacén.',
                ]);
            }

            $cantidadAnterior = (string) $existencia->cantidad;
            $cantidadReservada = (string) $existencia->cantidad_reservada;
            $costoPromedio = (string) $existencia->costo_promedio;

            $cmp = bccomp($cantidadObjetivo, $cantidadAnterior, 4);
            if ($cmp === 0) {
                throw ValidationException::withMessages([
                    'cantidad_objetivo' => 'La cantidad objetivo debe ser diferente de la existencia actual.',
                ]);
            }

            if (bccomp($cantidadObjetivo, $cantidadReservada, 4) === -1) {
                throw ValidationException::withMessages([
                    'cantidad_objetivo' => "La cantidad objetivo ({$cantidadObjetivo}) no puede ser menor que la cantidad reservada ({$cantidadReservada}).",
                ]);
            }

            if ($cmp === 1) {
                $tipo = MovimientoInventarioTaller::TIPO_AJUSTE_POSITIVO;
                $diferencia = bcsub($cantidadObjetivo, $cantidadAnterior, 4);
            } else {
                $tipo = MovimientoInventarioTaller::TIPO_AJUSTE_NEGATIVO;
                $diferencia = bcsub($cantidadAnterior, $cantidadObjetivo, 4);
            }

            $existencia->update([
                'cantidad' => $cantidadObjetivo,
            ]);

            MovimientoInventarioTaller::query()->create([
                'empresa_id' => 1,
                'almacen_id' => $almacenBloqueado->id,
                'producto_id' => $productoBloqueado->id,
                'grupo_id' => (string) Str::uuid(),
                'folio' => 'MOV-' . now()->format('YmdHisv') . '-' . Str::upper(Str::random(6)),
                'tipo' => $tipo,
                'cantidad' => $diferencia,
                'existencia_anterior' => $cantidadAnterior,
                'existencia_nueva' => $cantidadObjetivo,
                'costo_unitario' => $costoPromedio,
                'referencia_tipo' => 'ajuste_inventario',
                'referencia_id' => null,
                'motivo' => $motivo,
                'usuario_id' => $usuario?->id,
                'ocurrido_at' => now(),
            ]);

            /** @var ExistenciaAlmacenTaller $actualizada */
            $actualizada = $existencia->fresh(['almacen', 'producto']) ?? $existencia;

            return $actualizada;
        }, 3);
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