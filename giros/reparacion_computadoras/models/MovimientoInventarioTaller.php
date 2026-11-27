<?php

namespace Giros\ReparacionComputadoras\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class MovimientoInventarioTaller extends Model
{
    use UsaConexionTallerReparacion;

    public const TIPO_ENTRADA = 'entrada';

    public const TIPO_SALIDA = 'salida';

    public const TIPO_CONSUMO = 'consumo';

    public const TIPO_AJUSTE_POSITIVO = 'ajuste_positivo';

    public const TIPO_AJUSTE_NEGATIVO = 'ajuste_negativo';

    public const TIPO_TRANSFERENCIA_SALIDA = 'transferencia_salida';

    public const TIPO_TRANSFERENCIA_TRANSITO = 'transferencia_transito';

    public const TIPO_TRANSFERENCIA_ENTRADA = 'transferencia_entrada';

    public const UPDATED_AT = null;

    protected $table = 'movimientos_inventario_taller';

    protected $fillable = [
        'empresa_id',
        'almacen_id',
        'producto_id',
        'grupo_id',
        'folio',
        'tipo',
        'cantidad',
        'existencia_anterior',
        'existencia_nueva',
        'costo_unitario',
        'referencia_tipo',
        'referencia_id',
        'motivo',
        'usuario_id',
        'ocurrido_at',
    ];

    protected function casts(): array
    {
        return [
            'empresa_id' => 'integer',
            'almacen_id' => 'integer',
            'producto_id' => 'integer',
            'cantidad' => 'decimal:4',
            'existencia_anterior' => 'decimal:4',
            'existencia_nueva' => 'decimal:4',
            'costo_unitario' => 'decimal:4',
            'referencia_id' => 'integer',
            'usuario_id' => 'integer',
            'ocurrido_at' => 'datetime',
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

    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new LogicException('Los movimientos de inventario no pueden modificarse.');
        });

        static::deleting(function (): never {
            throw new LogicException('Los movimientos de inventario no pueden eliminarse.');
        });
    }
}