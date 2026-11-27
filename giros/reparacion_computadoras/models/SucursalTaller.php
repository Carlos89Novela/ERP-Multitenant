<?php

namespace Giros\ReparacionComputadoras\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SucursalTaller extends Model
{
    use UsaConexionTallerReparacion;

    protected $table = 'sucursales_taller';

    protected $fillable = [
        'empresa_id',
        'codigo',
        'nombre',
        'es_matriz',
        'activa',
    ];

    protected function casts(): array
    {
        return [
            'empresa_id' => 'integer',
            'es_matriz' => 'boolean',
            'activa' => 'boolean',
        ];
    }

    public function almacenes(): HasMany
    {
        return $this->hasMany(AlmacenTaller::class, 'sucursal_id');
    }
}