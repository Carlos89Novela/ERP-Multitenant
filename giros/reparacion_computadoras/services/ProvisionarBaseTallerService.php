<?php

namespace Giros\ReparacionComputadoras\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class ProvisionarBaseTallerService
{
    private const PREFIJO_BASE = 'taller_pc_';

    /**
     * Genera un nombre de base de datos seguro y único para una empresa
     * en MySQL (sin colisiones entre empresas del mismo giro).
     */
    public function generarNombreBaseDatos(string $nombreEmpresa, int|string|null $identificadorUnico = null): string
    {
        $slug = Str::of(Str::ascii($nombreEmpresa))
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '_')
            ->trim('_')
            ->limit(36, '')
            ->toString();

        if ($slug === '') {
            $slug = 'empresa';
        }

        $sufijo = '';
        if ($identificadorUnico !== null && trim((string) $identificadorUnico) !== '') {
            $sufijoClean = Str::of(Str::ascii((string) $identificadorUnico))
                ->lower()
                ->replaceMatches('/[^a-z0-9]+/', '_')
                ->trim('_')
                ->limit(12, '')
                ->toString();

            if ($sufijoClean !== '') {
                $sufijo = '_' . $sufijoClean;
            }
        } else {
            $sufijo = '_' . Str::lower(Str::random(6));
        }

        $candidato = self::PREFIJO_BASE . $slug . $sufijo;
        $candidato = substr($candidato, 0, 60);

        if (! preg_match('/^[a-z0-9_]+$/', $candidato)) {
            throw new InvalidArgumentException('No se pudo generar un nombre de base de datos MySQL válido.');
        }

        return $candidato;
    }

    /**
     * Crea la base de datos en MySQL si no existe y ejecuta las migraciones
     * del giro de reparación de computadoras sobre ella.
     *
     * @return array{database: string, created: bool, migrated: bool}
     */
    public function aprovisionarEmpresa(string $nombreEmpresa, int|string|null $identificadorUnico = null): array
    {
        $nombreBase = $this->generarNombreBaseDatos($nombreEmpresa, $identificadorUnico);

        $mysqlCfg = config('database.connections.mysql', []);
        $adminCfg = $mysqlCfg;
        $adminCfg['database'] = null;
        config(['database.connections.taller_mysql_admin' => $adminCfg]);
        DB::purge('taller_mysql_admin');

        $existe = DB::connection('taller_mysql_admin')
            ->select('SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ? LIMIT 1', [$nombreBase]);

        $creada = false;
        if (empty($existe)) {
            $charset = is_string($mysqlCfg['charset'] ?? null) && $mysqlCfg['charset'] !== '' ? $mysqlCfg['charset'] : 'utf8mb4';
            $collation = is_string($mysqlCfg['collation'] ?? null) && $mysqlCfg['collation'] !== '' ? $mysqlCfg['collation'] : 'utf8mb4_unicode_ci';

            DB::connection('taller_mysql_admin')->statement(
                "CREATE DATABASE IF NOT EXISTS `{$nombreBase}` CHARACTER SET {$charset} COLLATE {$collation}"
            );
            $creada = true;
        }

        $anterior = ContextoPropietarioTaller::resolverNombreBaseDatos();

        try {
            ContextoPropietarioTaller::establecerBaseDatos($nombreBase);

            $migracionesDir = dirname(__DIR__) . '/migrations';
            $archivos = glob($migracionesDir . '/*.php') ?: [];
            sort($archivos);

            foreach ($archivos as $archivo) {
                $migracion = require $archivo;
                if (is_object($migracion) && method_exists($migracion, 'up')) {
                    $migracion->up();
                } elseif (is_array($migracion) && isset($migracion['up']) && is_callable($migracion['up'])) {
                    $migracion['up']();
                }
            }

            // Asegurar estructura base de sucursal matriz y almacén de traspaso en la nueva BD
            $almacenesService = new GestionAlmacenesTallerService();
            $almacenesService->asegurarEstructuraBase('Sucursal Matriz', 'SUC-MATRIZ');

            return [
                'database' => $nombreBase,
                'created' => $creada,
                'migrated' => true,
            ];
        } catch (\Throwable $e) {
            throw new RuntimeException("Error aprovisionando la base de datos {$nombreBase}: {$e->getMessage()}", 0, $e);
        } finally {
            ContextoPropietarioTaller::establecerBaseDatos($anterior === 'erp_reparacion_computadoras' ? null : $anterior);
        }
    }
}