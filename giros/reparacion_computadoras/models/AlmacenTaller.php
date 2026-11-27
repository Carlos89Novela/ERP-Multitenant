<?php

namespace Giros\ReparacionComputadoras\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AlmacenTaller extends Model
{
    use UsaConexionTallerReparacion;

    public const TIPO_PRINCIPAL = 'principal';

    public const TIPO_SECUNDARIO = 'secundario';

    public const TIPO_TRANSITO = 'transito';

    protected $table = 'almacenes_taller';

    protected $fillable = [
        'empresa_id',
        'sucursal_id',
        'codigo',
        'nombre',
        'tipo',
        'es_virtual',
        'permite_existencias',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'empresa_id' => 'integer',
            'sucursal_id' => 'integer',
            'es_virtual' => 'boolean',
            'permite_existencias' => 'boolean',
            'activo' => 'boolean',
        ];
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(SucursalTaller::class, 'sucursal_id');
    }

    public function existencias(): HasMany
    {
        return $this->hasMany(ExistenciaAlmacenTaller::class, 'almacen_id');
    }
}