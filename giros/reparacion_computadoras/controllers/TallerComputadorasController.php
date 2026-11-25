<?php

namespace Giros\ReparacionComputadoras\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\TemplateConfiguracion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TallerComputadorasController extends Controller
{
    private function resolverEmpresaActiva(Request $request, ?string $paginaRequerida = null): Empresa
    {
        $user = $request->user();
        $empresa = Empresa::query()->with('sucursales')->findOrFail((int) $user->empresa_id);

        if (! $empresa->activa) {
            abort(403, 'Esta empresa se encuentra suspendida.');
        }

        if ($empresa->evaluarEstadoLicenciaEfectivo() === 'suspendida_morosidad') {
            abort(403, 'La licencia de la empresa superó el periodo de tolerancia tras el vencimiento.');
        }

        if ($empresa->giro_comercial !== 'reparacion_computadoras') {
            abort(403, 'Este módulo pertenece exclusivamente al giro de Reparación de Computadoras.');
        }

        if ($paginaRequerida !== null) {
            $habilitadas = $empresa->paginas_habilitadas ?? [];
            if (! in_array($paginaRequerida, $habilitadas, true)) {
                abort(403, 'Esta página no está habilitada para la empresa actual.');
            }
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
        $empresa = $this->resolverEmpresaActiva($request, 'dashboard');
        $template = $this->resolverTemplate($empresa);
        $conn = DB::connection('giro_reparacion_computadoras');

        $ordenesCount = $conn->table('ordenes_reparacion')->where('empresa_id', $empresa->id)->count();
        $equiposCount = $conn->table('equipos_taller')->where('empresa_id', $empresa->id)->count();
        $promosCount = $conn->table('promociones_combos_taller')->where('empresa_id', $empresa->id)->count();
        $transferenciasCount = $conn->schema()->hasTable('transferencias_taller')
            ? $conn->table('transferencias_taller')->where('empresa_id', $empresa->id)->count()
            : 0;

        $ordenes = $conn->table('ordenes_reparacion')
            ->leftJoin('equipos_taller', 'equipos_taller.id', '=', 'ordenes_reparacion.equipo_id')
            ->where('ordenes_reparacion.empresa_id', $empresa->id)
            ->select([
                'ordenes_reparacion.*',
                'equipos_taller.tipo_equipo',
                'equipos_taller.marca',
                'equipos_taller.modelo',
            ])
            ->orderByDesc('ordenes_reparacion.id')
            ->limit(25)
            ->get();

        return view('giros.reparacion_computadoras.index', [
            'seccion' => 'dashboard',
            'empresa' => $empresa,
            'template' => $template,
            'ordenes' => $ordenes,
            'equipos' => collect(),
            'promociones' => collect(),
            'transferencias' => collect(),
            'kpis' => [
                'ordenes' => $ordenesCount,
                'equipos' => $equiposCount,
                'promociones' => $promosCount,
                'transferencias' => $transferenciasCount,
            ],
        ]);
    }

    public function ordenes(Request $request): View
    {
        $empresa = $this->resolverEmpresaActiva($request, 'ordenes_taller');
        $template = $this->resolverTemplate($empresa);
        $conn = DB::connection('giro_reparacion_computadoras');

        $equipos = $conn->table('equipos_taller')
            ->where('empresa_id', $empresa->id)
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        $ordenes = $conn->table('ordenes_reparacion')
            ->leftJoin('equipos_taller', 'equipos_taller.id', '=', 'ordenes_reparacion.equipo_id')
            ->where('ordenes_reparacion.empresa_id', $empresa->id)
            ->select([
                'ordenes_reparacion.*',
                'equipos_taller.tipo_equipo',
                'equipos_taller.marca',
                'equipos_taller.modelo',
            ])
            ->orderByDesc('ordenes_reparacion.id')
            ->limit(50)
            ->get();

        return view('giros.reparacion_computadoras.index', [
            'seccion' => 'ordenes',
            'empresa' => $empresa,
            'template' => $template,
            'equipos' => $equipos,
            'ordenes' => $ordenes,
            'promociones' => collect(),
            'transferencias' => collect(),
            'kpis' => [],
        ]);
    }

    public function inventario(Request $request): View
    {
        $empresa = $this->resolverEmpresaActiva($request, 'inventario');
        $template = $this->resolverTemplate($empresa);
        $equipos = DB::connection('giro_reparacion_computadoras')
            ->table('equipos_taller')
            ->where('empresa_id', $empresa->id)
            ->orderByDesc('id')
            ->limit(100)
            ->get();

        return view('giros.reparacion_computadoras.index', [
            'seccion' => 'inventario',
            'empresa' => $empresa,
            'template' => $template,
            'equipos' => $equipos,
            'ordenes' => collect(),
            'promociones' => collect(),
            'transferencias' => collect(),
            'kpis' => [],
        ]);
    }

    public function transferencias(Request $request): View
    {
        $empresa = $this->resolverEmpresaActiva($request, 'inventario');
        $template = $this->resolverTemplate($empresa);
        $conn = DB::connection('giro_reparacion_computadoras');

        $transferencias = $conn->schema()->hasTable('transferencias_taller')
            ? $conn->table('transferencias_taller')->where('empresa_id', $empresa->id)->orderByDesc('id')->limit(50)->get()
            : collect();

        return view('giros.reparacion_computadoras.index', [
            'seccion' => 'transferencias',
            'empresa' => $empresa,
            'template' => $template,
            'equipos' => collect(),
            'ordenes' => collect(),
            'promociones' => collect(),
            'transferencias' => $transferencias,
            'kpis' => [],
        ]);
    }

    public function storeTransferencia(Request $request): RedirectResponse
    {
        $empresa = $this->resolverEmpresaActiva($request, 'inventario');
        $data = $request->validate([
            'folio' => ['required', 'string', 'max:50'],
            'origen_sucursal' => ['required', 'string', 'max:120'],
            'destino_sucursal' => ['required', 'string', 'max:120'],
            'concepto' => ['required', 'string', 'max:180'],
            'cantidad' => ['required', 'numeric', 'min:0.001'],
            'notas' => ['nullable', 'string', 'max:1000'],
        ]);

        $conn = DB::connection('giro_reparacion_computadoras');
        $existe = $conn->table('transferencias_taller')
            ->where('empresa_id', $empresa->id)
            ->where('folio', trim($data['folio']))
            ->exists();

        if ($existe) {
            return back()->withInput()->withErrors(['folio' => 'Ya existe una transferencia con ese folio en esta empresa.']);
        }

        $conn->table('transferencias_taller')->insert([
            'empresa_id' => $empresa->id,
            'folio' => trim($data['folio']),
            'origen_sucursal' => trim($data['origen_sucursal']),
            'destino_sucursal' => trim($data['destino_sucursal']),
            'concepto' => trim($data['concepto']),
            'cantidad' => (float) $data['cantidad'],
            'estado' => 'pendiente',
            'notas' => $data['notas'] ? trim($data['notas']) : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('giro.computadoras.transferencias')->with('status', 'Transferencia registrada.');
    }

    public function actualizarEstadoTransferencia(Request $request, int $transferenciaId): RedirectResponse
    {
        $empresa = $this->resolverEmpresaActiva($request, 'inventario');
        $data = $request->validate([
            'estado' => ['required', 'in:pendiente,en_transito,recibida,cancelada'],
        ]);

        $actualizadas = DB::connection('giro_reparacion_computadoras')
            ->table('transferencias_taller')
            ->where('id', $transferenciaId)
            ->where('empresa_id', $empresa->id)
            ->update([
                'estado' => $data['estado'],
                'updated_at' => now(),
            ]);

        if ($actualizadas === 0) {
            abort(404);
        }

        return redirect()->route('giro.computadoras.transferencias')->with('status', 'Estado de transferencia actualizado.');
    }

    public function facturacion(Request $request): View
    {
        $empresa = $this->resolverEmpresaActiva($request, 'facturacion');
        $template = $this->resolverTemplate($empresa);

        return view('giros.reparacion_computadoras.index', [
            'seccion' => 'facturacion',
            'empresa' => $empresa,
            'template' => $template,
            'equipos' => collect(),
            'ordenes' => collect(),
            'promociones' => collect(),
            'transferencias' => collect(),
            'kpis' => [],
        ]);
    }

    public function promociones(Request $request): View
    {
        $empresa = $this->resolverEmpresaActiva($request, 'taller_promociones');
        $template = $this->resolverTemplate($empresa);
        $promociones = DB::connection('giro_reparacion_computadoras')
            ->table('promociones_combos_taller')
            ->where('empresa_id', $empresa->id)
            ->orderByDesc('id')
            ->get();

        return view('giros.reparacion_computadoras.index', [
            'seccion' => 'promociones',
            'empresa' => $empresa,
            'template' => $template,
            'equipos' => collect(),
            'ordenes' => collect(),
            'promociones' => $promociones,
            'transferencias' => collect(),
            'kpis' => [],
        ]);
    }

    public function storePromocion(Request $request): RedirectResponse
    {
        $empresa = $this->resolverEmpresaActiva($request, 'taller_promociones');
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:140'],
            'descripcion' => ['nullable', 'string', 'max:1500'],
            'precio_combo' => ['nullable', 'numeric', 'min:0'],
            'descuento_porcentaje' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        DB::connection('giro_reparacion_computadoras')->table('promociones_combos_taller')->insert([
            'empresa_id' => $empresa->id,
            'nombre' => trim($data['nombre']),
            'descripcion' => $data['descripcion'] ? trim($data['descripcion']) : null,
            'precio_combo' => (float) ($data['precio_combo'] ?? 0),
            'descuento_porcentaje' => (float) ($data['descuento_porcentaje'] ?? 0),
            'activa' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('giro.computadoras.promociones')->with('status', 'Promoción / combo de reparaciones guardado.');
    }

    public function togglePromocion(Request $request, int $promocionId): RedirectResponse
    {
        $empresa = $this->resolverEmpresaActiva($request, 'taller_promociones');
        $conn = DB::connection('giro_reparacion_computadoras');
        $promo = $conn->table('promociones_combos_taller')->where('id', $promocionId)->where('empresa_id', $empresa->id)->first();
        if (! $promo) {
            abort(404);
        }

        $conn->table('promociones_combos_taller')->where('id', $promocionId)->update([
            'activa' => ! (bool) $promo->activa,
            'updated_at' => now(),
        ]);

        return redirect()->route('giro.computadoras.promociones')->with('status', 'Estado de promoción actualizado.');
    }

    public function autogestion(Request $request): View
    {
        $empresa = $this->resolverEmpresaActiva($request, 'autogestion_apariencia');
        $template = $this->resolverTemplate($empresa);

        return view('giros.reparacion_computadoras.index', [
            'seccion' => 'autogestion',
            'empresa' => $empresa,
            'template' => $template,
            'equipos' => collect(),
            'ordenes' => collect(),
            'promociones' => collect(),
            'transferencias' => collect(),
            'kpis' => [],
        ]);
    }

    public function storeOrden(Request $request): RedirectResponse
    {
        $empresa = $this->resolverEmpresaActiva($request, 'ordenes_taller');

        $data = $request->validate([
            'tipo_equipo' => ['required', 'string', 'max:80'],
            'marca' => ['nullable', 'string', 'max:80'],
            'modelo' => ['nullable', 'string', 'max:100'],
            'numero_serie' => ['nullable', 'string', 'max:120'],
            'folio' => ['required', 'string', 'max:50'],
            'falla_reportada' => ['nullable', 'string', 'max:2000'],
        ]);

        $conn = DB::connection('giro_reparacion_computadoras');
        $folioExiste = $conn->table('ordenes_reparacion')
            ->where('empresa_id', $empresa->id)
            ->where('folio', trim($data['folio']))
            ->exists();

        if ($folioExiste) {
            return back()->withInput()->withErrors(['folio' => 'Ya existe una orden con ese folio en esta empresa.']);
        }

        $conn->transaction(function () use ($conn, $empresa, $data): void {
            $equipoId = $conn->table('equipos_taller')->insertGetId([
                'empresa_id' => $empresa->id,
                'tipo_equipo' => trim($data['tipo_equipo']),
                'marca' => $data['marca'] ? trim($data['marca']) : null,
                'modelo' => $data['modelo'] ? trim($data['modelo']) : null,
                'numero_serie' => $data['numero_serie'] ? trim($data['numero_serie']) : null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $conn->table('ordenes_reparacion')->insert([
                'empresa_id' => $empresa->id,
                'equipo_id' => $equipoId,
                'folio' => trim($data['folio']),
                'estado' => 'recibido',
                'falla_reportada' => $data['falla_reportada'] ? trim($data['falla_reportada']) : null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        return redirect()
            ->route('giro.computadoras.ordenes')
            ->with('status', 'Equipo y orden de reparación registrados en el taller.');
    }

    public function actualizarEstadoOrden(Request $request, int $ordenId): RedirectResponse
    {
        $empresa = $this->resolverEmpresaActiva($request, 'ordenes_taller');

        $data = $request->validate([
            'estado' => ['required', 'in:recibido,diagnostico,reparacion,listo,entregado,cancelado'],
        ]);

        $actualizadas = DB::connection('giro_reparacion_computadoras')
            ->table('ordenes_reparacion')
            ->where('id', $ordenId)
            ->where('empresa_id', $empresa->id)
            ->update([
                'estado' => $data['estado'],
                'updated_at' => now(),
            ]);

        if ($actualizadas === 0) {
            abort(404, 'Orden no encontrada para esta empresa.');
        }

        return redirect()
            ->route('giro.computadoras.ordenes')
            ->with('status', 'Estado de la orden actualizado.');
    }
}
