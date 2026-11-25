<?php

namespace Giros\ReparacionCelulares\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\TemplateConfiguracion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TallerCelularesController extends Controller
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

        if ($empresa->giro_comercial !== 'reparacion_celulares') {
            abort(403, 'Este módulo pertenece exclusivamente al giro de Reparación de Celulares.');
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
        $conn = DB::connection('giro_reparacion_celulares');

        $ordenes = $conn->table('ordenes_reparacion_celulares')
            ->leftJoin('equipos_celulares', 'equipos_celulares.id', '=', 'ordenes_reparacion_celulares.equipo_id')
            ->where('ordenes_reparacion_celulares.empresa_id', $empresa->id)
            ->select([
                'ordenes_reparacion_celulares.*',
                'equipos_celulares.marca',
                'equipos_celulares.modelo',
                'equipos_celulares.imei',
            ])
            ->orderByDesc('ordenes_reparacion_celulares.id')
            ->limit(50)
            ->get();

        $promociones = $conn->table('promociones_combos_celulares')
            ->where('empresa_id', $empresa->id)
            ->orderByDesc('id')
            ->get();

        return view('giros.reparacion_celulares.index', [
            'empresa' => $empresa,
            'template' => $template,
            'ordenes' => $ordenes,
            'promociones' => $promociones,
        ]);
    }

    public function storeOrden(Request $request): RedirectResponse
    {
        $empresa = $this->resolverEmpresaActiva($request);
        $data = $request->validate([
            'folio' => ['required', 'string', 'max:50'],
            'marca' => ['required', 'string', 'max:80'],
            'modelo' => ['required', 'string', 'max:100'],
            'imei' => ['nullable', 'string', 'max:30'],
            'patron_pin_nota' => ['nullable', 'string', 'max:120'],
            'falla_reportada' => ['nullable', 'string', 'max:2000'],
        ]);

        $conn = DB::connection('giro_reparacion_celulares');
        $existe = $conn->table('ordenes_reparacion_celulares')
            ->where('empresa_id', $empresa->id)
            ->where('folio', trim($data['folio']))
            ->exists();

        if ($existe) {
            return back()->withInput()->withErrors(['folio' => 'Ya existe una orden con ese folio en esta empresa.']);
        }

        $conn->transaction(function () use ($conn, $empresa, $data): void {
            $equipoId = $conn->table('equipos_celulares')->insertGetId([
                'empresa_id' => $empresa->id,
                'marca' => trim($data['marca']),
                'modelo' => trim($data['modelo']),
                'imei' => $data['imei'] ? trim($data['imei']) : null,
                'patron_pin_nota' => $data['patron_pin_nota'] ? trim($data['patron_pin_nota']) : null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $conn->table('ordenes_reparacion_celulares')->insert([
                'empresa_id' => $empresa->id,
                'equipo_id' => $equipoId,
                'folio' => trim($data['folio']),
                'estado' => 'recibido',
                'falla_reportada' => $data['falla_reportada'] ? trim($data['falla_reportada']) : null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        return redirect()->route('giro.celulares.index')->with('status', 'Orden de reparación de celular registrada.');
    }

    public function actualizarEstadoOrden(Request $request, int $ordenId): RedirectResponse
    {
        $empresa = $this->resolverEmpresaActiva($request);
        $data = $request->validate([
            'estado' => ['required', 'in:recibido,diagnostico,reparacion,listo,entregado,cancelado'],
        ]);

        $actualizadas = DB::connection('giro_reparacion_celulares')
            ->table('ordenes_reparacion_celulares')
            ->where('id', $ordenId)
            ->where('empresa_id', $empresa->id)
            ->update([
                'estado' => $data['estado'],
                'updated_at' => now(),
            ]);

        if ($actualizadas === 0) {
            abort(404);
        }

        return redirect()->route('giro.celulares.index')->with('status', 'Estado de orden actualizado.');
    }

    public function storePromocion(Request $request): RedirectResponse
    {
        $empresa = $this->resolverEmpresaActiva($request);
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:140'],
            'descripcion' => ['nullable', 'string', 'max:1500'],
            'precio_combo' => ['nullable', 'numeric', 'min:0'],
            'descuento_porcentaje' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        DB::connection('giro_reparacion_celulares')->table('promociones_combos_celulares')->insert([
            'empresa_id' => $empresa->id,
            'nombre' => trim($data['nombre']),
            'descripcion' => $data['descripcion'] ? trim($data['descripcion']) : null,
            'precio_combo' => (float) ($data['precio_combo'] ?? 0),
            'descuento_porcentaje' => (float) ($data['descuento_porcentaje'] ?? 0),
            'activa' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('giro.celulares.index')->with('status', 'Promoción / combo de celulares registrada.');
    }

    public function togglePromocion(Request $request, int $promocionId): RedirectResponse
    {
        $empresa = $this->resolverEmpresaActiva($request);
        $conn = DB::connection('giro_reparacion_celulares');
        $promo = $conn->table('promociones_combos_celulares')->where('id', $promocionId)->where('empresa_id', $empresa->id)->first();
        if (! $promo) {
            abort(404);
        }

        $conn->table('promociones_combos_celulares')->where('id', $promocionId)->update([
            'activa' => ! (bool) $promo->activa,
            'updated_at' => now(),
        ]);

        return redirect()->route('giro.celulares.index')->with('status', 'Estado de promoción actualizado.');
    }
}
