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

        if ($empresa->giro_comercial !== 'reparacion_computadoras') {
            abort(403, 'Este módulo pertenece exclusivamente al giro de Reparación de Computadoras.');
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

        $equipos = DB::table('equipos_taller')
            ->where('empresa_id', $empresa->id)
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        $ordenes = DB::table('ordenes_reparacion')
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
            'empresa' => $empresa,
            'template' => $template,
            'equipos' => $equipos,
            'ordenes' => $ordenes,
        ]);
    }

    public function storeOrden(Request $request): RedirectResponse
    {
        $empresa = $this->resolverEmpresaActiva($request);

        $data = $request->validate([
            'tipo_equipo' => ['required', 'string', 'max:80'],
            'marca' => ['nullable', 'string', 'max:80'],
            'modelo' => ['nullable', 'string', 'max:100'],
            'numero_serie' => ['nullable', 'string', 'max:120'],
            'folio' => ['required', 'string', 'max:50'],
            'falla_reportada' => ['nullable', 'string', 'max:2000'],
        ]);

        $folioExiste = DB::table('ordenes_reparacion')
            ->where('empresa_id', $empresa->id)
            ->where('folio', trim($data['folio']))
            ->exists();

        if ($folioExiste) {
            return back()->withInput()->withErrors(['folio' => 'Ya existe una orden con ese folio en esta empresa.']);
        }

        DB::transaction(function () use ($empresa, $data): void {
            $equipoId = DB::table('equipos_taller')->insertGetId([
                'empresa_id' => $empresa->id,
                'tipo_equipo' => trim($data['tipo_equipo']),
                'marca' => $data['marca'] ? trim($data['marca']) : null,
                'modelo' => $data['modelo'] ? trim($data['modelo']) : null,
                'numero_serie' => $data['numero_serie'] ? trim($data['numero_serie']) : null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('ordenes_reparacion')->insert([
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
            ->route('giro.computadoras.index')
            ->with('status', 'Equipo y orden de reparación registrados en el taller.');
    }

    public function actualizarEstadoOrden(Request $request, int $ordenId): RedirectResponse
    {
        $empresa = $this->resolverEmpresaActiva($request);

        $data = $request->validate([
            'estado' => ['required', 'in:recibido,diagnostico,reparacion,listo,entregado,cancelado'],
        ]);

        $actualizadas = DB::table('ordenes_reparacion')
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
            ->route('giro.computadoras.index')
            ->with('status', 'Estado de la orden actualizado.');
    }
}
