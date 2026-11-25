<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AsignacionTimbres extends Model
{
    protected $table = 'asignaciones_timbres';

    protected $fillable = [
        'empresa_id',
        'lote_id',
        'cantidad',
        'saldo_empresa_posterior',
        'motivo',
        'asignado_por_id',
    ];

    protected $casts = [
        'cantidad' => 'integer',
        'saldo_empresa_posterior' => 'integer',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function lote(): BelongsTo
    {
        return $this->belongsTo(BolsaTimbresLote::class, 'lote_id');
    }
}
