<?php

namespace Giros\ReparacionComputadoras\Models;

use Illuminate\Database\Eloquent\Model;

class UsuarioTaller extends Model
{
    protected $connection = 'giro_reparacion_computadoras';

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
        ];
    }
}
