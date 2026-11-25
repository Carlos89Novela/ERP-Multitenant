<?php

namespace Giros\ReparacionComputadoras\Controllers;

use Giros\ReparacionComputadoras\Models\UsuarioTaller;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;

class DashboardTallerController extends Controller
{
    public function index(Request $request)
    {
        View::addNamespace('taller_computadoras', base_path('giros/reparacion_computadoras/views'));

        /** @var UsuarioTaller $usuario */
        $usuario = $request->attributes->get('usuario_taller');

        $empresa = DB::connection('mysql')
            ->table('empresas')
            ->where('id', $usuario->empresa_id)
            ->first();

        $template = $empresa
            ? DB::connection('mysql')
                ->table('template_configuraciones')
                ->where('empresa_id', $empresa->id)
                ->where('activo', true)
                ->orderByDesc('id')
                ->first()
            : null;

        $conn = DB::connection('giro_reparacion_computadoras');
        $schema = Schema::connection('giro_reparacion_computadoras');

        $ordenesAbiertas = 0;
        $ordenesListas = 0;
        $ultimasOrdenes = collect();
        $refaccionesBajas = 0;
        $totalUsuarios = UsuarioTaller::query()
            ->where('empresa_id', $usuario->empresa_id)
            ->where('activo', true)
            ->count();

        if ($schema->hasTable('ordenes_reparacion')) {
            $ordenesAbiertas = $conn->table('ordenes_reparacion')
                ->where('empresa_id', $usuario->empresa_id)
                ->whereNotIn('estado', ['entregada', 'cancelada'])
                ->count();

            $ordenesListas = $conn->table('ordenes_reparacion')
                ->where('empresa_id', $usuario->empresa_id)
                ->where('estado', 'lista')
                ->count();

            $ultimasOrdenes = $conn->table('ordenes_reparacion')
                ->where('empresa_id', $usuario->empresa_id)
                ->orderByDesc('id')
                ->limit(8)
                ->get();
        }

        if ($schema->hasTable('inventario_items')) {
            $refaccionesBajas = $conn->table('inventario_items')
                ->where('empresa_id', $usuario->empresa_id)
                ->whereColumn('stock_actual', '<=', 'stock_minimo')
                ->count();
        }

        return view('taller_computadoras::dashboard', [
            'usuario' => $usuario,
            'empresa' => $empresa,
            'template' => $template,
            'metricas' => [
                'ordenes_abiertas' => $ordenesAbiertas,
                'ordenes_listas' => $ordenesListas,
                'refacciones_bajas' => $refaccionesBajas,
                'equipo_activo' => $totalUsuarios,
            ],
            'ultimasOrdenes' => $ultimasOrdenes,
        ]);
    }
}
