<?php

namespace App\Models;

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
        'activa',
        'saldo_timbres',
        'umbral_alerta_timbres',
        'paginas_habilitadas',
    ];

    protected $casts = [
        'giro_bloqueado' => 'boolean',
        'activa' => 'boolean',
        'saldo_timbres' => 'integer',
        'umbral_alerta_timbres' => 'integer',
        'paginas_habilitadas' => 'array',
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
}
