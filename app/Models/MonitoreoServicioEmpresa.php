<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonitoreoServicioEmpresa extends Model
{
    protected $table = 'monitoreo_servicios_empresa';

    protected $fillable = [
        'empresa_id',
        'estado_pagina',
        'estado_timbres',
        'timbres_reportados',
        'latencia_ms',
        'paginas_activas_count',
        'ultimo_latido_at',
        'ultimo_evento_servicio',
    ];

    protected $casts = [
        'timbres_reportados' => 'integer',
        'latencia_ms' => 'integer',
        'paginas_activas_count' => 'integer',
        'ultimo_latido_at' => 'datetime',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }
}
