<?php

namespace Giros\ReparacionComputadoras\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransferenciaInventarioTallerDetalle extends Model
{
    use UsaConexionTallerReparacion;

    protected $table = 'transferencia_inventario_taller_detalles';

    protected $fillable = [
        'transferencia_inventario_taller_id',
        'producto_id',
        'cantidad',
        'cantidad_solicitada',
        'cantidad_despachada',
        'cantidad_recibida',
        'costo_unitario',
        'subtotal',
    ];

    protected function casts(): array
    {
        return [
            'transferencia_inventario_taller_id' => 'integer',
            'producto_id' => 'integer',
            'cantidad' => 'decimal:4',
            'cantidad_solicitada' => 'decimal:4',
            'cantidad_despachada' => 'decimal:4',
            'cantidad_recibida' => 'decimal:4',
            'costo_unitario' => 'decimal:4',
            'subtotal' => 'decimal:4',
        ];
    }

    public function transferencia(): BelongsTo
    {
        return $this->belongsTo(TransferenciaInventarioTaller::class, 'transferencia_inventario_taller_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(ProductoTaller::class, 'producto_id');
    }
}