<?php

namespace Giros\ReparacionComputadoras\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransferenciaInventarioTaller extends Model
{
    use UsaConexionTallerReparacion;

    public const ESTADO_SOLICITADA = 'solicitada';

    public const ESTADO_EN_TRANSITO = 'en_transito';

    public const ESTADO_COMPLETADA = 'completada';

    public const ESTADO_CANCELADA = 'cancelada';

    protected $table = 'transferencias_inventario_taller';

    protected $fillable = [
        'empresa_id',
        'almacen_origen_id',
        'almacen_destino_id',
        'almacen_transito_id',
        'grupo_id',
        'folio',
        'estado',
        'motivo',
        'observaciones_despacho',
        'observaciones_recepcion',
        'total_lineas',
        'costo_total',
        'solicitado_por_id',
        'despachado_por_id',
        'recibido_por_id',
        'solicitado_at',
        'despachado_at',
        'recibido_at',
    ];

    protected function casts(): array
    {
        return [
            'empresa_id' => 'integer',
            'almacen_origen_id' => 'integer',
            'almacen_destino_id' => 'integer',
            'almacen_transito_id' => 'integer',
            'total_lineas' => 'integer',
            'costo_total' => 'decimal:4',
            'solicitado_por_id' => 'integer',
            'despachado_por_id' => 'integer',
            'recibido_por_id' => 'integer',
            'solicitado_at' => 'datetime',
            'despachado_at' => 'datetime',
            'recibido_at' => 'datetime',
        ];
    }

    public function almacenOrigen(): BelongsTo
    {
        return $this->belongsTo(AlmacenTaller::class, 'almacen_origen_id');
    }

    public function almacenDestino(): BelongsTo
    {
        return $this->belongsTo(AlmacenTaller::class, 'almacen_destino_id');
    }

    public function almacenTransito(): BelongsTo
    {
        return $this->belongsTo(AlmacenTaller::class, 'almacen_transito_id');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(TransferenciaInventarioTallerDetalle::class, 'transferencia_inventario_taller_id');
    }
}