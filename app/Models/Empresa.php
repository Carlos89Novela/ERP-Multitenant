<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Empresa extends Model
{
    protected $table = 'empresas';

    protected $fillable = [
        'nombre',
        'slug',
        'rfc',
        'giro_comercial',
        'giro_bloqueado',
        'plazo_meses',
        'monto_renta',
        'fecha_vencimiento_licencia',
        'dias_gracia_licencia',
        'estado_licencia',
        'activa',
        'saldo_timbres',
        'umbral_alerta_timbres',
        'paginas_habilitadas',
    ];

    protected $casts = [
        'giro_bloqueado' => 'boolean',
        'plazo_meses' => 'integer',
        'monto_renta' => 'decimal:2',
        'fecha_vencimiento_licencia' => 'date',
        'dias_gracia_licencia' => 'integer',
        'activa' => 'boolean',
        'saldo_timbres' => 'integer',
        'umbral_alerta_timbres' => 'integer',
        'paginas_habilitadas' => 'array',
    ];

    public const PLAZOS_RENTA_MESES = [
        1 => '1 Mes',
        3 => '3 Meses',
        6 => '6 Meses',
        9 => '9 Meses',
        12 => '12 Meses (Anual)',
    ];

    public const GIROS_DISPONIBLES = [
        'reparacion_computadoras' => 'Reparación de Computadoras',
        'restaurante_cafeteria' => 'Restaurante / Cafetería',
        'abarrotes' => 'Abarrotes',
        'ferreteria' => 'Ferretería',
        'reparacion_celulares' => 'Reparación de Celulares',
    ];

    public const PAGINAS_CATALOGO = [
        'dashboard' => 'Dashboard Principal',
        'pos' => 'Punto de Venta (POS)',
        'inventario' => 'Inventario y Existencias',
        'compras' => 'Compras y Proveedores',
        'facturacion' => 'Facturación CFDI (Timbres)',
        'ordenes_taller' => 'Órdenes de Reparación (Cómputo/Celulares)',
        'restaurante_comandas' => 'Comandero, Mesas y KDS',
        'autogestion_apariencia' => 'Autogestión Visual del Propietario',
    ];

    public function evaluarEstadoLicenciaEfectivo(): string
    {
        if (! $this->fecha_vencimiento_licencia) {
            return $this->estado_licencia ?: 'activa';
        }

        $hoy = Carbon::today();
        $vencimiento = Carbon::parse($this->fecha_vencimiento_licencia)->startOfDay();

        if ($hoy->lte($vencimiento)) {
            return 'activa';
        }

        $diasVencidos = $vencimiento->diffInDays($hoy);
        $limiteGracia = max(1, (int) ($this->dias_gracia_licencia ?: 30));

        // Solo se suspende automaticamente si excede el periodo de tolerancia (por defecto 30 dias / mes completo)
        if ($diasVencidos > $limiteGracia) {
            return 'suspendida_morosidad';
        }

        return 'gracia';
    }

    public function sucursales(): HasMany
    {
        return $this->hasMany(Sucursal::class, 'empresa_id');
    }

    public function monitoreo(): HasOne
    {
        return $this->hasOne(MonitoreoServicioEmpresa::class, 'empresa_id');
    }

    public function asignacionesTimbres(): HasMany
    {
        return $this->hasMany(AsignacionTimbres::class, 'empresa_id');
    }

    public function facturas(): HasMany
    {
        return $this->hasMany(FacturaCliente::class, 'empresa_id');
    }
}
