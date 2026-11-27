<?php

namespace Giros\ReparacionComputadoras\Services;

use Giros\ReparacionComputadoras\Models\AlmacenTaller;
use Giros\ReparacionComputadoras\Models\SucursalTaller;
use Giros\ReparacionComputadoras\Models\UsuarioTaller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class GestionAlmacenesTallerService
{
    public const ROLES_SUCURSAL = [
        'gerente' => 'Gerente',
        'subgerente' => 'Subgerente',
        'tecnico' => 'Técnico',
        'mostrador' => 'Mostrador',
    ];

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

            UsuarioTaller::query()
                ->whereNull('sucursal_id')
                ->update(['sucursal_id' => (int) $matriz->id]);

            return [
                'matriz' => $matriz->fresh(),
                'almacen_matriz' => $almacenMatriz->fresh(),
                'almacen_transito' => $almacenTransito->fresh(),
            ];
        }, 3);
    }

    /**
     * Crea una sucursal, su almacén y automáticamente los usuarios con roles:
     * gerente, subgerente, tecnico y mostrador vinculados a esa sucursal.
     *
     * @param  array{codigo?: mixed, nombre?: mixed, es_matriz?: mixed, empresa_id?: mixed}  $datos
     * @return array{sucursal: SucursalTaller, almacen: AlmacenTaller, usuarios_creados: list<array{rol: string, name: string, email: string, password_temporal: string}>}
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
        $empresaId = filter_var($datos['empresa_id'] ?? null, FILTER_VALIDATE_INT);
        $empresaIdLimpio = ($empresaId !== false && (int) $empresaId > 0) ? (int) $empresaId : 1;

        return DB::connection('taller_rep_db')->transaction(function () use ($nombre, $codigoRaw, $esMatriz, $empresaIdLimpio): array {
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
                'empresa_id' => $empresaIdLimpio,
                'codigo' => $codigoRaw,
                'nombre' => $nombre,
                'es_matriz' => $esMatriz,
                'activa' => true,
            ]);

            $tipoAlmacen = $esMatriz ? AlmacenTaller::TIPO_PRINCIPAL : AlmacenTaller::TIPO_SECUNDARIO;
            $almacen = $this->asegurarAlmacenDeSucursal($sucursal, $tipoAlmacen);
            $this->asegurarAlmacenTransito();

            $usuariosCreados = $this->crearUsuariosBaseSucursal($sucursal, $empresaIdLimpio);

            return [
                'sucursal' => $sucursal->fresh(),
                'almacen' => $almacen->fresh(),
                'usuarios_creados' => $usuariosCreados,
            ];
        }, 3);
    }

    /**
     * @return list<array{rol: string, name: string, email: string, password_temporal: string}>
     */
    public function crearUsuariosBaseSucursal(SucursalTaller $sucursal, int $empresaId = 1): array
    {
        $dominio = $this->resolverDominioBase($empresaId);
        $sucId = (int) $sucursal->id;
        $passwordTemporal = 'Taller2026!';
        $ahora = now();

        $creados = [];
        foreach (self::ROLES_SUCURSAL as $rol => $etiquetaRol) {
            $emailBase = "{$rol}.suc{$sucId}@{$dominio}";
            $email = $emailBase;
            $contador = 1;
            while (UsuarioTaller::query()->where('email', $email)->exists()) {
                $email = "{$rol}.suc{$sucId}.{$contador}@{$dominio}";
                $contador++;
            }

            $nombreUsuario = "{$etiquetaRol} — {$sucursal->nombre}";

            UsuarioTaller::query()->create([
                'empresa_id' => $empresaId,
                'name' => $nombreUsuario,
                'email' => $email,
                'password' => Hash::make($passwordTemporal),
                'rol_empresa' => $rol,
                'sucursal_id' => $sucId,
                'debe_cambiar_password' => true,
                'password_temporal_generada_at' => $ahora,
                'activo' => true,
            ]);

            $creados[] = [
                'rol' => $rol,
                'name' => $nombreUsuario,
                'email' => $email,
                'password_temporal' => $passwordTemporal,
            ];
        }

        return $creados;
    }

    public function resolverDominioBase(int $empresaId = 1): string
    {
        $prop = UsuarioTaller::query()
            ->where('empresa_id', $empresaId)
            ->where('rol_empresa', 'propietario')
            ->whereNotNull('email')
            ->first();

        if ($prop && str_contains((string) $prop->email, '@')) {
            $partes = explode('@', (string) $prop->email, 2);
            $dom = strtolower(trim($partes[1] ?? ''));
            if ($dom !== '') {
                return $dom;
            }
        }

        return 'canodevops.local';
    }

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