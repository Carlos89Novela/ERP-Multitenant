<?php

namespace Giros\ReparacionComputadoras\Services;

use Giros\ReparacionComputadoras\Models\CategoriaRefaccionTaller;
use Giros\ReparacionComputadoras\Models\ProductoTaller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CatalogoInventarioTallerService
{
    /**
     * Crea una categoría de refacciones/productos en la BD del taller.
     *
     * @param  array{nombre?: mixed, descripcion?: mixed, activa?: mixed}  $datos
     */
    public function crearCategoria(array $datos): CategoriaRefaccionTaller
    {
        ContextoPropietarioTaller::conexion();

        $nombre = is_string($datos['nombre'] ?? null) ? trim((string) $datos['nombre']) : '';
        if ($nombre === '' || mb_strlen($nombre) > 140) {
            throw ValidationException::withMessages([
                'nombre' => 'Debes indicar un nombre de categoría válido (máximo 140 caracteres).',
            ]);
        }

        $descripcion = is_string($datos['descripcion'] ?? null) && trim((string) $datos['descripcion']) !== ''
            ? trim((string) $datos['descripcion'])
            : null;

        $activa = array_key_exists('activa', $datos)
            ? filter_var($datos['activa'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true
            : true;

        return DB::connection('taller_rep_db')->transaction(function () use ($nombre, $descripcion, $activa): CategoriaRefaccionTaller {
            return CategoriaRefaccionTaller::query()->create([
                'empresa_id' => 1,
                'nombre' => $nombre,
                'descripcion' => $descripcion,
                'activa' => $activa,
            ]);
        }, 3);
    }

    /**
     * Crea un producto o refacción con SKU único y precisión decimal de 4 dígitos.
     *
     * @param  array{
     *     categoria_id?: mixed,
     *     sku?: mixed,
     *     nombre?: mixed,
     *     unidad_medida?: mixed,
     *     costo_referencia?: mixed,
     *     precio_venta?: mixed,
     *     activo?: mixed
     * }  $datos
     */
    public function crearProducto(array $datos): ProductoTaller
    {
        ContextoPropietarioTaller::conexion();

        $nombre = is_string($datos['nombre'] ?? null) ? trim((string) $datos['nombre']) : '';
        if ($nombre === '' || mb_strlen($nombre) > 180) {
            throw ValidationException::withMessages([
                'nombre' => 'Debes indicar un nombre de refacción o producto válido (máximo 180 caracteres).',
            ]);
        }

        $skuRaw = is_string($datos['sku'] ?? null) ? strtoupper(trim((string) $datos['sku'])) : '';
        if ($skuRaw === '') {
            $slug = strtoupper(preg_replace('/[^A-Za-z0-9]+/', '-', Str::ascii($nombre)) ?? 'REF');
            $slug = trim($slug, '-');
            $skuRaw = 'REF-' . ($slug !== '' ? substr($slug, 0, 24) : strtoupper(Str::random(6)));
        }

        if (mb_strlen($skuRaw) > 80) {
            throw ValidationException::withMessages([
                'sku' => 'El SKU no puede exceder los 80 caracteres.',
            ]);
        }

        $unidad = is_string($datos['unidad_medida'] ?? null) && trim((string) $datos['unidad_medida']) !== ''
            ? mb_substr(trim((string) $datos['unidad_medida']), 0, 40)
            : 'pieza';

        $costoRef = $this->normalizarDecimal($datos['costo_referencia'] ?? '0');
        $precioVenta = $this->normalizarDecimal($datos['precio_venta'] ?? '0');

        if ($costoRef === '' || bccomp($costoRef, '0.0000', 4) === -1) {
            throw ValidationException::withMessages([
                'costo_referencia' => 'El costo de referencia debe ser igual o mayor a cero.',
            ]);
        }

        if ($precioVenta === '' || bccomp($precioVenta, '0.0000', 4) === -1) {
            throw ValidationException::withMessages([
                'precio_venta' => 'El precio de venta debe ser igual o mayor a cero.',
            ]);
        }

        $categoriaId = filter_var($datos['categoria_id'] ?? null, FILTER_VALIDATE_INT);
        $categoriaIdValido = ($categoriaId !== false && (int) $categoriaId > 0) ? (int) $categoriaId : null;

        $activo = array_key_exists('activo', $datos)
            ? filter_var($datos['activo'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true
            : true;

        return DB::connection('taller_rep_db')->transaction(function () use (
            $categoriaIdValido,
            $skuRaw,
            $nombre,
            $unidad,
            $costoRef,
            $precioVenta,
            $activo
        ): ProductoTaller {
            if (ProductoTaller::query()->where('sku', $skuRaw)->lockForUpdate()->exists()) {
                throw ValidationException::withMessages([
                    'sku' => "Ya existe un producto o refacción con el SKU {$skuRaw}.",
                ]);
            }

            if ($categoriaIdValido !== null && ! CategoriaRefaccionTaller::query()->where('id', $categoriaIdValido)->exists()) {
                throw ValidationException::withMessages([
                    'categoria_id' => 'La categoría seleccionada no existe.',
                ]);
            }

            return ProductoTaller::query()->create([
                'empresa_id' => 1,
                'categoria_id' => $categoriaIdValido,
                'sku' => $skuRaw,
                'nombre' => $nombre,
                'unidad_medida' => $unidad,
                'costo_referencia' => $costoRef,
                'precio_venta' => $precioVenta,
                'activo' => $activo,
            ]);
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