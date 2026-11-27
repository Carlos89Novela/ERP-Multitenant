<?php

namespace Giros\ReparacionComputadoras\Services;

use Giros\ReparacionComputadoras\Models\AlmacenTaller;
use Giros\ReparacionComputadoras\Models\SucursalTaller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class GestionAlmacenesTallerService
{
    /**
     * Obtiene o crea la sucursal matriz y su almacén principal,
     * además de garantizar el almacén virtual de traspaso/tránsito.
     *
     * @return array{matriz: SucursalTaller, almacen_matriz: AlmacenTaller, almacen_transito: AlmacenTaller}
     */
    public function asegurarEstructuraBase(string $nombreMatriz = 'Sucursal Matriz', string $codigoMatriz = 'SUC-MATRIZ'): array
    {
        ContextoPropietarioTaller::conexion();

        return DB::connection('taller_rep_db')->transaction(function () use ($nombreMatriz, $codigoMatriz): array {
            /** @var SucursalTaller|null $matriz */
            $matriz = SucursalTaller::query()
                ->where('es_matriz', true)
                ->lockForUpdate()
                ->first();

            if ($matriz === null) {
                $matriz = SucursalTaller::query()
                    ->orderBy('id', 'asc')
                    ->lockForUpdate()
                    ->first();
            }

            if ($matriz === null) {
                $codigoLimpio = strtoupper(trim($codigoMatriz)) ?: 'SUC-MATRIZ';
                if (SucursalTaller::query()->where('codigo', $codigoLimpio)->exists()) {
                    $codigoLimpio .= '-' . strtoupper(Str::random(4));
                }

                $matriz = SucursalTaller::query()->create([
                    'empresa_id' => 1,
                    'codigo' => $codigoLimpio,
                    'nombre' => trim($nombreMatriz) !== '' ? trim($nombreMatriz) : 'Sucursal Matriz',
                    'es_matriz' => true,
                    'activa' => true,
                ]);
            } elseif (! $matriz->es_matriz) {
                $matriz->update(['es_matriz' => true]);
            }

            $almacenMatriz = $this->asegurarAlmacenDeSucursal($matriz, AlmacenTaller::TIPO_PRINCIPAL);
            $almacenTransito = $this->asegurarAlmacenTransito();

            return [
                'matriz' => $matriz->fresh(),
                'almacen_matriz' => $almacenMatriz->fresh(),
                'almacen_transito' => $almacenTransito->fresh(),
            ];
        }, 3);
    }

    /**
     * Crea una nueva sucursal (por ejemplo, una segunda sucursal) y genera
     * automáticamente su almacén asociado.
     *
     * @param  array{codigo?: mixed, nombre?: mixed, es_matriz?: mixed}  $datos
     * @return array{sucursal: SucursalTaller, almacen: AlmacenTaller}
     */
    public function crearSucursalConAlmacen(array $datos): array
    {
        ContextoPropietarioTaller::conexion();

        $nombre = is_string($datos['nombre'] ?? null) ? trim((string) $datos['nombre']) : '';
        if ($nombre === '' || mb_strlen($nombre) > 150) {
            throw ValidationException::withMessages([
                'nombre' => 'Debes indicar un nombre válido para la sucursal (máximo 150 caracteres).',
            ]);
        }

        $codigoRaw = is_string($datos['codigo'] ?? null) ? strtoupper(trim((string) $datos['codigo'])) : '';
        if ($codigoRaw === '') {
            $slug = strtoupper(preg_replace('/[^A-Za-z0-9]+/', '-', Str::ascii($nombre)) ?? 'SUC');
            $slug = trim($slug, '-');
            $codigoRaw = 'SUC-' . ($slug !== '' ? substr($slug, 0, 24) : strtoupper(Str::random(5)));
        }

        if (mb_strlen($codigoRaw) > 40) {
            throw ValidationException::withMessages([
                'codigo' => 'El código de sucursal no puede exceder los 40 caracteres.',
            ]);
        }

        $esMatriz = filter_var($datos['es_matriz'] ?? false, FILTER_VALIDATE_BOOLEAN);

        return DB::connection('taller_rep_db')->transaction(function () use ($nombre, $codigoRaw, $esMatriz): array {
            if (SucursalTaller::query()->where('codigo', $codigoRaw)->lockForUpdate()->exists()) {
                throw ValidationException::withMessages([
                    'codigo' => "Ya existe una sucursal con el código {$codigoRaw}.",
                ]);
            }

            if ($esMatriz) {
                SucursalTaller::query()->where('es_matriz', true)->update(['es_matriz' => false]);
            }

            /** @var SucursalTaller $sucursal */
            $sucursal = SucursalTaller::query()->create([
                'empresa_id' => 1,
                'codigo' => $codigoRaw,
                'nombre' => $nombre,
                'es_matriz' => $esMatriz,
                'activa' => true,
            ]);

            $tipoAlmacen = $esMatriz ? AlmacenTaller::TIPO_PRINCIPAL : AlmacenTaller::TIPO_SECUNDARIO;
            $almacen = $this->asegurarAlmacenDeSucursal($sucursal, $tipoAlmacen);
            $this->asegurarAlmacenTransito();

            return [
                'sucursal' => $sucursal->fresh(),
                'almacen' => $almacen->fresh(),
            ];
        }, 3);
    }

    /**
     * Garantiza que la sucursal tenga su almacén creado y activo.
     */
    public function asegurarAlmacenDeSucursal(SucursalTaller $sucursal, string $tipo = AlmacenTaller::TIPO_SECUNDARIO): AlmacenTaller
    {
        /** @var AlmacenTaller|null $existente */
        $existente = AlmacenTaller::query()
            ->where('sucursal_id', $sucursal->id)
            ->where('es_virtual', false)
            ->lockForUpdate()
            ->first();

        if ($existente !== null) {
            return $existente;
        }

        $prefijo = $sucursal->es_matriz ? 'ALM-MAT' : 'ALM-SUC';
        $codigoBase = strtoupper(preg_replace('/[^A-Za-z0-9]+/', '-', (string) $sucursal->codigo) ?? 'SUC');
        $codigo = substr($prefijo . '-' . $codigoBase, 0, 40);

        if (AlmacenTaller::query()->where('codigo', $codigo)->exists()) {
            $codigo = substr($codigo, 0, 34) . '-' . strtoupper(Str::random(4));
        }

        $nombreAlmacen = $sucursal->es_matriz
            ? 'Almacén Matriz - ' . $sucursal->nombre
            : 'Almacén ' . $sucursal->nombre;

        /** @var AlmacenTaller $almacen */
        $almacen = AlmacenTaller::query()->create([
            'empresa_id' => 1,
            'sucursal_id' => $sucursal->id,
            'codigo' => $codigo,
            'nombre' => mb_substr($nombreAlmacen, 0, 150),
            'tipo' => $sucursal->es_matriz ? AlmacenTaller::TIPO_PRINCIPAL : $tipo,
            'es_virtual' => false,
            'permite_existencias' => true,
            'activo' => true,
        ]);

        return $almacen;
    }

    /**
     * Garantiza que exista el almacén virtual de traspaso/tránsito.
     */
    public function asegurarAlmacenTransito(): AlmacenTaller
    {
        /** @var AlmacenTaller|null $transito */
        $transito = AlmacenTaller::query()
            ->where('tipo', AlmacenTaller::TIPO_TRANSITO)
            ->where('es_virtual', true)
            ->lockForUpdate()
            ->first();

        if ($transito !== null) {
            return $transito;
        }

        $codigo = 'ALM-TRASPASO';
        if (AlmacenTaller::query()->where('codigo', $codigo)->exists()) {
            $codigo .= '-' . strtoupper(Str::random(4));
        }

        /** @var AlmacenTaller $creado */
        $creado = AlmacenTaller::query()->create([
            'empresa_id' => 1,
            'sucursal_id' => null,
            'codigo' => $codigo,
            'nombre' => 'Almacén de Traspaso / Tránsito',
            'tipo' => AlmacenTaller::TIPO_TRANSITO,
            'es_virtual' => true,
            'permite_existencias' => true,
            'activo' => true,
        ]);

        return $creado;
    }
}