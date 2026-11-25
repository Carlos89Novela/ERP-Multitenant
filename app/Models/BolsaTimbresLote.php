<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BolsaTimbresLote extends Model
{
    protected $table = 'bolsa_timbres_lotes';

    protected $fillable = [
        'folio_compra',
        'proveedor',
        'cantidad_comprada',
        'cantidad_disponible',
        'costo_unitario',
        'fecha_compra',
        'notas',
    ];

    protected $casts = [
        'cantidad_comprada' => 'integer',
        'cantidad_disponible' => 'integer',
        'costo_unitario' => 'decimal:4',
        'fecha_compra' => 'datetime',
    ];

    public function asignaciones(): HasMany
    {
        return $this->hasMany(AsignacionTimbres::class, 'lote_id');
    }
}
