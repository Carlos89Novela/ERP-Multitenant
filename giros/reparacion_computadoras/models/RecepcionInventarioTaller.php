<?php

namespace Giros\ReparacionComputadoras\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecepcionInventarioTaller extends Model
{
    use UsaConexionTallerReparacion;

    public const TIPO_COMPRA = 'compra';

    public const TIPO_RECEPCION = 'recepcion';

    public const TIPO_DEVOLUCION = 'devolucion';

    public const TIPO_AJUSTE_POSITIVO = 'ajuste_positivo';

    protected $table = 'recepciones_inventario_taller';

    protected $fillable = [
        'empresa_id',
        'almacen_id',
        'grupo_id',
        'folio',
        'tipo',
        'referencia_externa',
        'motivo',
        'total_lineas',
        'importe_total',
        'recibido_por_id',
        'recibido_at',
    ];

    protected function casts(): array
    {
        return [
            'empresa_id' => 'integer',
            'almacen_id' => 'integer',
            'total_lineas' => 'integer',
            'importe_total' => 'decimal:4',
            'recibido_por_id' => 'integer',
            'recibido_at' => 'datetime',
        ];
    }

    /**
     * @return list<string>
     */
    public static function tiposPermitidos(): array
    {
        return [
            self::TIPO_COMPRA,
            self::TIPO_RECEPCION,
            self::TIPO_DEVOLUCION,
            self::TIPO_AJUSTE_POSITIVO,
        ];
    }

    public function almacen(): BelongsTo
    {
        return $this->belongsTo(AlmacenTaller::class, 'almacen_id');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(RecepcionInventarioTallerDetalle::class, 'recepcion_inventario_taller_id');
    }
}