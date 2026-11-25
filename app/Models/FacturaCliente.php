<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacturaCliente extends Model
{
    protected $table = 'facturas_clientes';

    protected $fillable = [
        'empresa_id',
        'folio',
        'concepto_tipo',
        'plazo_meses',
        'timbres_incluidos',
        'subtotal',
        'iva',
        'total',
        'estatus',
        'fecha_emision',
        'fecha_vencimiento',
        'pagada_at',
        'xml_path',
        'pdf_path',
        'notas',
    ];

    protected $casts = [
        'plazo_meses' => 'integer',
        'timbres_incluidos' => 'integer',
        'subtotal' => 'decimal:2',
        'iva' => 'decimal:2',
        'total' => 'decimal:2',
        'fecha_emision' => 'date',
        'fecha_vencimiento' => 'date',
        'pagada_at' => 'datetime',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }
}
