<?php

namespace Giros\ReparacionComputadoras\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class ReservaInventarioTaller extends Model
{
    use UsaConexionTallerReparacion;

    public const ESTADO_ACTIVA = 'activa';

    public const ESTADO_PARCIAL = 'parcial';

    public const ESTADO_CONSUMIDA = 'consumida';

    public const ESTADO_LIBERADA = 'liberada';

    protected $table = 'reservas_inventario_taller';

    protected $fillable = [
        'empresa_id',
        'almacen_id',
        'producto_id',
        'orden_reparacion_id',
        'orden_folio',
        'cantidad_reservada',
        'cantidad_consumida',
        'cantidad_liberada',
        'estado',
        'motivo',
        'reservado_por_id',
        'actualizado_por_id',
        'reservado_at',
        'finalizado_at',
    ];

    protected function casts(): array
    {
        return [
            'empresa_id' => 'integer',
            'almacen_id' => 'integer',
            'producto_id' => 'integer',
            'orden_reparacion_id' => 'integer',
            'cantidad_reservada' => 'decimal:4',
            'cantidad_consumida' => 'decimal:4',
            'cantidad_liberada' => 'decimal:4',
            'reservado_por_id' => 'integer',
            'actualizado_por_id' => 'integer',
            'reservado_at' => 'datetime',
            'finalizado_at' => 'datetime',
        ];
    }

    /**
     * @return list<string>
     */
    public static function estadosValidos(): array
    {
        return [
            self::ESTADO_ACTIVA,
            self::ESTADO_PARCIAL,
            self::ESTADO_CONSUMIDA,
            self::ESTADO_LIBERADA,
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

    public function cantidadPendiente(): string
    {
        $finalizada = bcadd((string) $this->cantidad_consumida, (string) $this->cantidad_liberada, 4);

        return bcsub((string) $this->cantidad_reservada, $finalizada, 4);
    }

    public function estaFinalizada(): bool
    {
        return bccomp($this->cantidadPendiente(), '0.0000', 4) === 0;
    }

    public function estaActiva(): bool
    {
        return in_array($this->estado, [self::ESTADO_ACTIVA, self::ESTADO_PARCIAL], true)
            && ! $this->estaFinalizada();
    }

    protected static function booted(): void
    {
        static::saving(function (ReservaInventarioTaller $reserva): void {
            if (! in_array($reserva->estado, self::estadosValidos(), true)) {
                throw new LogicException('El estado de la reserva de inventario no es válido.');
            }
            if (bccomp((string) $reserva->cantidad_reservada, '0.0000', 4) !== 1) {
                throw new LogicException('La cantidad reservada debe ser mayor que cero.');
            }
            if (bccomp((string) $reserva->cantidad_consumida, '0.0000', 4) === -1) {
                throw new LogicException('La cantidad consumida no puede ser negativa.');
            }
            if (bccomp((string) $reserva->cantidad_liberada, '0.0000', 4) === -1) {
                throw new LogicException('La cantidad liberada no puede ser negativa.');
            }
            $finalizada = bcadd((string) $reserva->cantidad_consumida, (string) $reserva->cantidad_liberada, 4);
            if (bccomp($finalizada, (string) $reserva->cantidad_reservada, 4) === 1) {
                throw new LogicException('La cantidad consumida y liberada no puede superar la cantidad reservada.');
            }
        });
    }
}