<?php

namespace Giros\Abarrotes\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\TemplateConfiguracion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AbarrotesController extends Controller
{
    private function resolverEmpresaActiva(Request $request): Empresa
    {
        $user = $request->user();
        $empresa = Empresa::query()->findOrFail((int) $user->empresa_id);

        if (! $empresa->activa) {
            abort(403, 'Esta empresa se encuentra suspendida.');
        }

        if ($empresa->evaluarEstadoLicenciaEfectivo() === 'suspendida_morosidad') {
            abort(403, 'La licencia de la empresa superó el periodo de tolerancia tras el vencimiento.');
        }

        if ($empresa->giro_comercial !== 'abarrotes') {
            abort(403, 'Este módulo pertenece exclusivamente al giro de Abarrotes.');
        }

        return $empresa;
    }

    private function resolverTemplate(Empresa $empresa): TemplateConfiguracion
    {
        return TemplateConfiguracion::query()
            ->where('empresa_id', $empresa->id)
            ->where('activo', true)
            ->orderByDesc('id')
            ->first()
            ?? TemplateConfiguracion::query()
                ->where('ambito', 'plataforma')
                ->whereNull('empresa_id')
                ->orderByDesc('id')
                ->first()
            ?? new TemplateConfiguracion(TemplateConfiguracion::defaultsPlataforma());
    }

    public function index(Request $request): View
    {
        $empresa = $this->resolverEmpresaActiva($request);
        $template = $this->resolverTemplate($empresa);
        $conn = DB::connection('giro_abarrotes');

        $productos = $conn->table('productos_abarrotes')
            ->where('empresa_id', $empresa->id)
            ->orderByDesc('id')
            ->limit(100)
            ->get();

        $promociones = $conn->table('promociones_descuentos_abarrotes')
            ->where('empresa_id', $empresa->id)
            ->orderByDesc('id')
            ->get();

        return view('giros.abarrotes.index', [
            'empresa' => $empresa,
            'template' => $template,
            'productos' => $productos,
            'promociones' => $promociones,
        ]);
    }

    public function storeProducto(Request $request): RedirectResponse
    {
        $empresa = $this->resolverEmpresaActiva($request);
        $data = $request->validate([
            'codigo_barras' => ['nullable', 'string', 'max:80'],
            'nombre' => ['required', 'string', 'max:160'],
            'unidad_medida' => ['required', 'string', 'max:30'],
            'costo_compra' => ['nullable', 'numeric', 'min:0'],
            'precio_venta' => ['required', 'numeric', 'min:0'],
            'existencia' => ['nullable', 'numeric', 'min:0'],
            'fecha_caducidad_proxima' => ['nullable', 'date'],
        ]);

        DB::connection('giro_abarrotes')->table('productos_abarrotes')->insert([
            'empresa_id' => $empresa->id,
            'codigo_barras' => $data['codigo_barras'] ? trim($data['codigo_barras']) : null,
            'nombre' => trim($data['nombre']),
            'unidad_medida' => trim($data['unidad_medida']),
            'costo_compra' => (float) ($data['costo_compra'] ?? 0),
            'precio_venta' => (float) $data['precio_venta'],
            'existencia' => (float) ($data['existencia'] ?? 0),
            'fecha_caducidad_proxima' => $data['fecha_caducidad_proxima'] ?? null,
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('giro.abarrotes.index')->with('status', 'Producto registrado en el catálogo de abarrotes.');
    }

    public function storePromocion(Request $request): RedirectResponse
    {
        $empresa = $this->resolverEmpresaActiva($request);
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:140'],
            'proveedor_campana' => ['nullable', 'string', 'max:120'],
            'tipo_promocion' => ['required', 'in:porcentaje,2x1,precio_especial,volumen'],
            'valor_descuento' => ['nullable', 'numeric', 'min:0'],
        ]);

        DB::connection('giro_abarrotes')->table('promociones_descuentos_abarrotes')->insert([
            'empresa_id' => $empresa->id,
            'nombre' => trim($data['nombre']),
            'proveedor_campana' => $data['proveedor_campana'] ? trim($data['proveedor_campana']) : null,
            'tipo_promocion' => $data['tipo_promocion'],
            'valor_descuento' => (float) ($data['valor_descuento'] ?? 0),
            'activa' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('giro.abarrotes.index')->with('status', 'Promoción / descuento de abarrotes registrada.');
    }

    public function togglePromocion(Request $request, int $promocionId): RedirectResponse
    {
        $empresa = $this->resolverEmpresaActiva($request);
        $conn = DB::connection('giro_abarrotes');
        $promo = $conn->table('promociones_descuentos_abarrotes')->where('id', $promocionId)->where('empresa_id', $empresa->id)->first();
        if (! $promo) {
            abort(404);
        }

        $conn->table('promociones_descuentos_abarrotes')->where('id', $promocionId)->update([
            'activa' => ! (bool) $promo->activa,
            'updated_at' => now(),
        ]);

        return redirect()->route('giro.abarrotes.index')->with('status', 'Estado de promoción actualizado.');
    }
}
