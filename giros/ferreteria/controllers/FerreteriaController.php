<?php

namespace Giros\Ferreteria\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\TemplateConfiguracion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FerreteriaController extends Controller
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

        if ($empresa->giro_comercial !== 'ferreteria') {
            abort(403, 'Este módulo pertenece exclusivamente al giro de Ferretería.');
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
        $conn = DB::connection('giro_ferreteria');

        $productos = $conn->table('productos_ferreteria')
            ->where('empresa_id', $empresa->id)
            ->orderByDesc('id')
            ->limit(100)
            ->get();

        $promociones = $conn->table('promociones_descuentos_ferreteria')
            ->where('empresa_id', $empresa->id)
            ->orderByDesc('id')
            ->get();

        return view('giros.ferreteria.index', [
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
            'sku' => ['nullable', 'string', 'max:80'],
            'nombre' => ['required', 'string', 'max:160'],
            'unidad_venta' => ['required', 'string', 'max:30'],
            'costo_compra' => ['nullable', 'numeric', 'min:0'],
            'precio_venta' => ['required', 'numeric', 'min:0'],
            'existencia' => ['nullable', 'numeric', 'min:0'],
        ]);

        DB::connection('giro_ferreteria')->table('productos_ferreteria')->insert([
            'empresa_id' => $empresa->id,
            'sku' => $data['sku'] ? trim($data['sku']) : null,
            'nombre' => trim($data['nombre']),
            'unidad_venta' => trim($data['unidad_venta']),
            'costo_compra' => (float) ($data['costo_compra'] ?? 0),
            'precio_venta' => (float) $data['precio_venta'],
            'existencia' => (float) ($data['existencia'] ?? 0),
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('giro.ferreteria.index')->with('status', 'Artículo registrado en el catálogo de ferretería.');
    }

    public function storePromocion(Request $request): RedirectResponse
    {
        $empresa = $this->resolverEmpresaActiva($request);
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:140'],
            'proveedor_campana' => ['nullable', 'string', 'max:120'],
            'tipo_promocion' => ['required', 'in:porcentaje,volumen,precio_especial'],
            'valor_descuento' => ['nullable', 'numeric', 'min:0'],
        ]);

        DB::connection('giro_ferreteria')->table('promociones_descuentos_ferreteria')->insert([
            'empresa_id' => $empresa->id,
            'nombre' => trim($data['nombre']),
            'proveedor_campana' => $data['proveedor_campana'] ? trim($data['proveedor_campana']) : null,
            'tipo_promocion' => $data['tipo_promocion'],
            'valor_descuento' => (float) ($data['valor_descuento'] ?? 0),
            'activa' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('giro.ferreteria.index')->with('status', 'Promoción / descuento de ferretería registrada.');
    }

    public function togglePromocion(Request $request, int $promocionId): RedirectResponse
    {
        $empresa = $this->resolverEmpresaActiva($request);
        $conn = DB::connection('giro_ferreteria');
        $promo = $conn->table('promociones_descuentos_ferreteria')->where('id', $promocionId)->where('empresa_id', $empresa->id)->first();
        if (! $promo) {
            abort(404);
        }

        $conn->table('promociones_descuentos_ferreteria')->where('id', $promocionId)->update([
            'activa' => ! (bool) $promo->activa,
            'updated_at' => now(),
        ]);

        return redirect()->route('giro.ferreteria.index')->with('status', 'Estado de promoción actualizado.');
    }
}
