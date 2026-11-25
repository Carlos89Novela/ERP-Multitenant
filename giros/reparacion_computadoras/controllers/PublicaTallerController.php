<?php

namespace Giros\ReparacionComputadoras\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;

class PublicaTallerController extends Controller
{
    public function show(Request $request, ?string $slug = null)
    {
        View::addNamespace('taller_computadoras', base_path('giros/reparacion_computadoras/views'));

        $empresasQuery = DB::connection('mysql')
            ->table('empresas')
            ->where('giro_comercial', 'reparacion_computadoras')
            ->where('activa', true);

        $empresa = $slug
            ? (clone $empresasQuery)->where('slug', $slug)->first()
            : (clone $empresasQuery)->orderBy('id')->first();

        $template = $empresa
            ? DB::connection('mysql')
                ->table('template_configuraciones')
                ->where('empresa_id', $empresa->id)
                ->where('activo', true)
                ->orderByDesc('id')
                ->first()
            : null;

        $connGiro = DB::connection('giro_reparacion_computadoras');
        $schemaGiro = Schema::connection('giro_reparacion_computadoras');

        $promociones = collect();
        if ($empresa && $schemaGiro->hasTable('promociones')) {
            $promociones = $connGiro->table('promociones')
                ->where('empresa_id', $empresa->id)
                ->where('activa', true)
                ->orderByDesc('id')
                ->limit(6)
                ->get();
        }

        $folioBuscado = trim((string) $request->query('folio', ''));
        $ordenConsultada = null;
        if ($empresa && $folioBuscado !== '' && $schemaGiro->hasTable('ordenes_reparacion')) {
            $ordenConsultada = $connGiro->table('ordenes_reparacion')
                ->where('empresa_id', $empresa->id)
                ->where('folio', $folioBuscado)
                ->first();
        }

        return view('taller_computadoras::publica', [
            'empresa' => $empresa,
            'template' => $template,
            'promociones' => $promociones,
            'folioBuscado' => $folioBuscado,
            'ordenConsultada' => $ordenConsultada,
        ]);
    }
}
