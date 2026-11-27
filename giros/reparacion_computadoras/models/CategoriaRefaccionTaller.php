<?php

namespace Giros\ReparacionComputadoras\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CategoriaRefaccionTaller extends Model
{
    use UsaConexionTallerReparacion;

    protected $table = 'categorias_refaccion_taller';

    protected $fillable = [
        'empresa_id',
        'nombre',
        'descripcion',
        'activa',
    ];

    protected function casts(): array
    {
        return [
            'empresa_id' => 'integer',
            'activa' => 'boolean',
        ];
    }

    public function productos(): HasMany
    {
        return $this->hasMany(ProductoTaller::class, 'categoria_id');
    }
}