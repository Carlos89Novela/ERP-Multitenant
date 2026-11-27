<?php

namespace Giros\ReparacionComputadoras\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductoTaller extends Model
{
    use UsaConexionTallerReparacion;

    protected $table = 'productos_taller';

    protected $fillable = [
        'empresa_id',
        'categoria_id',
        'sku',
        'nombre',
        'unidad_medida',
        'costo_referencia',
        'precio_venta',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'empresa_id' => 'integer',
            'categoria_id' => 'integer',
            'costo_referencia' => 'decimal:4',
            'precio_venta' => 'decimal:4',
            'activo' => 'boolean',
        ];
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(CategoriaRefaccionTaller::class, 'categoria_id');
    }

    public function existencias(): HasMany
    {
        return $this->hasMany(ExistenciaAlmacenTaller::class, 'producto_id');
    }
}