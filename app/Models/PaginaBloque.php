<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaginaBloque extends Model
{
    protected $table = 'pagina_bloques';

    protected $fillable = [
        'empresa_id',
        'giro_comercial',
        'contexto_ruta',
        'clave_bloque',
        'titulo',
        'subtitulo',
        'icono',
        'ruta_destino',
        'texto_accion',
        'orden',
        'visible',
        'roles_permitidos',
    ];

    protected $casts = [
        'orden' => 'integer',
        'visible' => 'boolean',
        'roles_permitidos' => 'array',
    ];
}
