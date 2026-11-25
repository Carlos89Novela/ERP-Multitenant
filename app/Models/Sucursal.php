<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sucursal extends Model
{
    protected $table = 'sucursales';

    protected $fillable = [
        'empresa_id',
        'codigo',
        'nombre',
        'activa',
    ];

    protected $casts = [
        'activa' => 'boolean',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }
}
