<?php

namespace Giros\ReparacionComputadoras\Models;

use Giros\ReparacionComputadoras\Services\ContextoPropietarioTaller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin Model
 */
trait UsaConexionTallerReparacion
{
    public function getConnectionName(): ?string
    {
        ContextoPropietarioTaller::conexion();

        return 'taller_rep_db';
    }

    public function scopeDeSucursal(Builder $query, int $sucursalId): Builder
    {
        return $query->where($this->getTable() . '.sucursal_id', $sucursalId);
    }
}