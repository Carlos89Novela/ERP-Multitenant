<?php

namespace Giros\ReparacionComputadoras\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExistenciaAlmacenTaller extends Model
{
    use UsaConexionTallerReparacion;

    protected $table = 'existencias_almacen_taller';

    protected $fillable = [
        'empresa_id',
        'almacen_id',
        'producto_id',
        'cantidad',
        'cantidad_reservada',
        'costo_promedio',
    ];

    protected function casts(): array
    {
        return [
            'empresa_id' => 'integer',
            'almacen_id' => 'integer',
            'producto_id' => 'integer',
            'cantidad' => 'decimal:4',
            'cantidad_reservada' => 'decimal:4',
            'costo_promedio' => 'decimal:4',
        ];
    }

    public function almacen(): BelongsTo
    {
        return $this->belongsTo(AlmacenTaller::class, 'almacen_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(ProductoTaller::class, 'producto_id');
    }

    public function cantidadDisponible(): string
    {
        return bcsub((string) $this->cantidad, (string) $this->cantidad_reservada, 4);
    }
}