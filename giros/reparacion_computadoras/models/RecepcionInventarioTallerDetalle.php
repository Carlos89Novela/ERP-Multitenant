<?php

namespace Giros\ReparacionComputadoras\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecepcionInventarioTallerDetalle extends Model
{
    use UsaConexionTallerReparacion;

    protected $table = 'recepcion_inventario_taller_detalles';

    protected $fillable = [
        'recepcion_inventario_taller_id',
        'producto_id',
        'cantidad',
        'costo_unitario',
        'subtotal',
    ];

    protected function casts(): array
    {
        return [
            'recepcion_inventario_taller_id' => 'integer',
            'producto_id' => 'integer',
            'cantidad' => 'decimal:4',
            'costo_unitario' => 'decimal:4',
            'subtotal' => 'decimal:4',
        ];
    }

    public function recepcion(): BelongsTo
    {
        return $this->belongsTo(RecepcionInventarioTaller::class, 'recepcion_inventario_taller_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(ProductoTaller::class, 'producto_id');
    }
}