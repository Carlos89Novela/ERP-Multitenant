<?php

namespace Giros\ReparacionComputadoras\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificacionTraspasoTaller extends Model
{
    use UsaConexionTallerReparacion;

    protected $table = 'notificaciones_traspaso_taller';

    protected $fillable = [
        'empresa_id',
        'transferencia_id',
        'almacen_destinatario_id',
        'evento',
        'titulo',
        'mensaje',
        'leida',
        'leida_at',
    ];

    protected $casts = [
        'leida' => 'boolean',
        'leida_at' => 'datetime',
    ];

    public function transferencia(): BelongsTo
    {
        return $this->belongsTo(TransferenciaInventarioTaller::class, 'transferencia_id');
    }

    public function almacenDestinatario(): BelongsTo
    {
        return $this->belongsTo(AlmacenTaller::class, 'almacen_destinatario_id');
    }
}