<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TemplateConfiguracion extends Model
{
    protected $table = 'template_configuraciones';

    protected $fillable = [
        'mostrar_carrusel_publico',
        'icono_predeterminado',
        'logo_path',
        'ambito',
        'empresa_id',
        'sucursal_id',
        'giro_comercial',
        'rol',
        'fuente_primaria',
        'fuente_secundaria',
        'tamano_base_px',
        'color_primario',
        'color_secundario',
        'color_acento',
        'color_fondo_pagina',
        'color_fondo_superficie',
        'fondo_tarjeta',
        'color_texto_principal',
        'color_texto_secundario',
        'color_borde',
        'tipo_fondo',
        'valor_fondo',
        'estilo_icono',
        'radio_bordes',
        'activo',
        'actualizado_por_id',
    ];

    protected $casts = [
        'tamano_base_px' => 'integer',
        'activo' => 'boolean',
    ];

    public static function defaultsPlataforma(): array
    {
        return [
            'ambito' => 'plataforma',
            'fuente_primaria' => 'Inter, system-ui, sans-serif',
            'fuente_secundaria' => 'JetBrains Mono, monospace',
            'tamano_base_px' => 14,
            'color_primario' => '#6366f1',
            'color_secundario' => '#3b82f6',
            'color_acento' => '#10b981',
            'color_fondo_pagina' => '#09090b',
            'color_fondo_superficie' => '#18181b',
            'fondo_tarjeta' => '#111115',
            'color_texto_principal' => '#f4f4f5',
            'color_texto_secundario' => '#a1a1aa',
            'color_borde' => '#27272a',
            'tipo_fondo' => 'solido',
            'valor_fondo' => null,
            'estilo_icono' => 'heroicons',
            'radio_bordes' => '0.75rem',
        ];
    }
}
