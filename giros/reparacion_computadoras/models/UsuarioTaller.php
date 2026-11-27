<?php

namespace Giros\ReparacionComputadoras\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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
        'sucursal_id',
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

    public function esPropietarioGlobal(): bool
    {
        return mb_strtolower(trim((string) $this->rol_empresa)) === 'propietario';
    }

    public function sucursalAsignadaId(): ?int
    {
        if ($this->esPropietarioGlobal()) {
            return null;
        }

        if ($this->sucursal_id !== null && (int) $this->sucursal_id > 0) {
            return (int) $this->sucursal_id;
        }

        $conn = DB::connection('taller_rep_db');
        if (Schema::connection('taller_rep_db')->hasTable('sucursales_taller')) {
            $matrizId = $conn->table('sucursales_taller')
                ->where('es_matriz', true)
                ->value('id');
            if ($matrizId !== null) {
                return (int) $matrizId;
            }
        }

        return null;
    }
}