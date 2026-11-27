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

        $rutaJsonOrdenes = storage_path("app/giros/reparacion_computadoras/empresa_{$usuario->empresa_id}_ordenes.json");
        $ordenesJson = [];
        if (file_exists($rutaJsonOrdenes)) {
            $decoded = json_decode((string) file_get_contents($rutaJsonOrdenes), true);
            if (is_array($decoded)) {
                $ordenesJson = array_values(array_filter($decoded, fn ($item) => is_array($item)));
            }
        }

        if (! $usuario->esPropietarioGlobal() && $usuario->sucursalAsignadaId() !== null && ! empty($ordenesJson)) {
            $sucUsuario = (int) $usuario->sucursalAsignadaId();
            $ordenesJson = array_values(array_filter($ordenesJson, function ($ord) use ($sucUsuario) {
                if (! array_key_exists('sucursal_id', $ord)) {
                    return true;
                }
                return (int) $ord['sucursal_id'] === $sucUsuario;
            }));
        }

        if (! empty($ordenesJson)) {
            $abiertas = 0;
            $listas = 0;
            foreach ($ordenesJson as $ord) {
                $est = mb_strtolower(trim((string) ($ord['estado'] ?? '')));
                if (! in_array($est, ['entregada', 'entregado', 'cancelada', 'cancelado'], true)) {
                    $abiertas++;
                }
                if (str_contains($est, 'lista') || str_contains($est, 'listo')) {
                    $listas++;
                }
            }
            $ordenesAbiertas = $abiertas;
            $ordenesListas = $listas;
            $ultimasOrdenes = collect(array_slice($ordenesJson, 0, 8))->map(fn ($o) => (object) $o);
        } elseif ($schema->hasTable('ordenes_reparacion')) {
            $ordenesAbiertas = $conn->table('ordenes_reparacion')
                ->where('empresa_id', $usuario->empresa_id)
                ->whereNotIn('estado', ['entregada', 'cancelada', 'entregado', 'cancelado'])
                ->count();

            $ordenesListas = $conn->table('ordenes_reparacion')
                ->where('empresa_id', $usuario->empresa_id)
                ->whereIn('estado', ['lista', 'Listo para entrega'])
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

        require_once __DIR__ . '/AutogestionTallerController.php';
        $configInternas = AutogestionTallerController::cargarConfiguracionPorAmbito((int) $usuario->empresa_id, 'internas');

        return view('taller_computadoras::dashboard', [
            'configInternas' => $configInternas,
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
