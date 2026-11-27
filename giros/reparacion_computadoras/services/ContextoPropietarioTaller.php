<?php

namespace Giros\ReparacionComputadoras\Services;

use Giros\ReparacionComputadoras\Models\UsuarioTaller;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ContextoPropietarioTaller
{
    private const BASE_RESPALDO = 'erp_reparacion_computadoras';

    private static ?string $baseDatosActiva = null;

    /**
     * Define explícitamente el nombre de base de datos para la empresa activa
     * en el contexto actual (o null para usar configuración/respaldo).
     */
    public static function establecerBaseDatos(?string $nombreBaseDatos): void
    {
        if ($nombreBaseDatos === null || trim($nombreBaseDatos) === '') {
            self::$baseDatosActiva = null;
            config(['taller_reparacion.database' => null]);
            DB::purge('taller_rep_db');

            return;
        }

        $limpia = trim($nombreBaseDatos);
        if (! preg_match('/^[A-Za-z0-9_]+$/', $limpia)) {
            throw new InvalidArgumentException('El nombre de la base de datos contiene caracteres no permitidos.');
        }

        self::$baseDatosActiva = $limpia;
        config(['taller_reparacion.database' => $limpia]);
        DB::purge('taller_rep_db');
    }

    /**
     * Resuelve el nombre de base de datos de la empresa activa, con respaldo
     * a erp_reparacion_computadoras si no hay base específica asignada.
     */
    public static function resolverNombreBaseDatos(): string
    {
        if (self::$baseDatosActiva !== null && self::$baseDatosActiva !== '') {
            return self::$baseDatosActiva;
        }

        $configDb = config('taller_reparacion.database');
        if (is_string($configDb) && preg_match('/^[A-Za-z0-9_]+$/', trim($configDb))) {
            return trim($configDb);
        }

        return self::BASE_RESPALDO;
    }

    /**
     * Valida que el usuario actual tenga rol de propietario en el giro
     * y asegura la conexión hacia la base de datos resuelta.
     */
    public static function validarPropietario(?UsuarioTaller $usuario): UsuarioTaller
    {
        if (! $usuario) {
            throw new AuthorizationException('Debes iniciar sesión en el taller.');
        }

        $rol = strtolower(trim((string) ($usuario->rol_empresa ?? '')));
        if ($rol !== 'propietario') {
            throw new AuthorizationException('En esta etapa del módulo solo el rol propietario tiene acceso.');
        }

        self::conexion();

        return $usuario;
    }


    /**
     * Valida que el usuario tenga rol propietario, gerente o subgerente
     * para operar inventario, existencias, kardex y traspasos.
     */
    public static function validarOperadorInventario(?UsuarioTaller $usuario): UsuarioTaller
    {
        if (! $usuario) {
            throw new AuthorizationException('Debes iniciar sesión en el taller.');
        }

        $rol = strtolower(trim((string) ($usuario->rol_empresa ?? '')));
        $rolesPermitidos = ['propietario', 'gerente', 'subgerente', 'sub_gerente'];

        if (! in_array($rol, $rolesPermitidos, true)) {
            throw new AuthorizationException('Solo propietario, gerente o subgerente tienen acceso a este módulo.');
        }

        self::conexion();

        return $usuario;
    }

    public static function conexion(): Connection
    {
        $nombreBd = self::resolverNombreBaseDatos();
        $dbCfg = config('database.connections.mysql', []);
        $dbCfg['database'] = $nombreBd;
        config(['database.connections.taller_rep_db' => $dbCfg]);
        DB::purge('taller_rep_db');

        return DB::connection('taller_rep_db');
    }
}