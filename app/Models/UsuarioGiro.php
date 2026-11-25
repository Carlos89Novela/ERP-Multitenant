<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class UsuarioGiro extends Authenticatable
{
    protected $connection = 'usuario_giro_runtime';

    protected $table = 'usuarios_empresa';

    protected $fillable = [
        'empresa_id',
        'name',
        'email',
        'password',
        'rol_empresa',
        'debe_cambiar_password',
        'password_temporal_generada_at',
        'activo',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'debe_cambiar_password' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public static function conexionPorGiro(string $giro): string
    {
        return match ($giro) {
            'reparacion_computadoras' => 'giro_reparacion_computadoras',
            'restaurante_cafeteria' => 'giro_restaurante_cafeteria',
            'abarrotes' => 'giro_abarrotes',
            'ferreteria' => 'giro_ferreteria',
            'reparacion_celulares' => 'giro_reparacion_celulares',
            default => 'mysql',
        };
    }
}
