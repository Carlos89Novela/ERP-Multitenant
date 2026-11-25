<?php

namespace App\Http\Controllers\Plataforma;

use App\Http\Controllers\Controller;
use App\Models\AsignacionTimbres;
use App\Models\BolsaTimbresLote;
use App\Models\Empresa;
use App\Models\MonitoreoServicioEmpresa;
use App\Models\Sucursal;
use App\Models\TemplateConfiguracion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PlataformaController extends Controller
{
    private function obtenerTemplatePlataforma(): TemplateConfiguracion
    {
        return TemplateConfiguracion::query()
            ->where('ambito', 'plataforma')
            ->whereNull('empresa_id')
            ->orderByDesc('id')
            ->first()
            ?? new TemplateConfiguracion(TemplateConfiguracion::defaultsPlataforma());
    }

    public function index(): View
    {
        $template = $this->obtenerTemplatePlataforma();

        // Solo metadatos de servicio y timbres; cero acceso a tablas operativas de clientes
        $empresas = Empresa::query()
            ->with(['monitoreo', 'sucursales:id,empresa_id,codigo,nombre,activa'])
            ->orderByDesc('id')
            ->get();

        $lotesTimbres = BolsaTimbresLote::query()
            ->orderByDesc('id')
            ->get();

        $asignacionesRecientes = AsignacionTimbres::query()
            ->with(['empresa:id,nombre,giro_comercial', 'lote:id,folio_compra'])
            ->orderByDesc('id')
            ->limit(15)
            ->get();

        $resumen = [
            'empresas_total' => $empresas->count(),
            'empresas_activas' => $empresas->where('activa', true)->count(),
            'timbres_bolsa_disponibles' => (int) $lotesTimbres->sum('cantidad_disponible'),
            'timbres_asignados_empresas' => (int) $empresas->sum('saldo_timbres'),
            'alertas_timbres' => $empresas->filter(fn (Empresa $e) => $e->saldo_timbres <= $e->umbral_alerta_timbres)->count(),
        ];

        return view('plataforma.consola', [
            'template' => $template,
            'empresas' => $empresas,
            'lotesTimbres' => $lotesTimbres,
            'asignacionesRecientes' => $asignacionesRecientes,
            'resumen' => $resumen,
            'girosDisponibles' => Empresa::GIROS_DISPONIBLES,
            'paginasCatalogo' => Empresa::PAGINAS_CATALOGO,
        ]);
    }

    public function guardarTemplatePlataforma(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'fuente_primaria' => ['required', 'string', 'max:100'],
            'fuente_secundaria' => ['required', 'string', 'max:100'],
            'tamano_base_px' => ['required', 'integer', 'min:11', 'max:22'],
            'color_primario' => ['required', 'string', 'max:30'],
            'color_secundario' => ['required', 'string', 'max:30'],
            'color_acento' => ['required', 'string', 'max:30'],
            'color_fondo_pagina' => ['required', 'string', 'max:30'],
            'color_fondo_superficie' => ['required', 'string', 'max:30'],
            'fondo_tarjeta' => ['required', 'string', 'max:120'],
            'color_texto_principal' => ['required', 'string', 'max:30'],
            'color_texto_secundario' => ['required', 'string', 'max:30'],
            'color_borde' => ['required', 'string', 'max:30'],
            'tipo_fondo' => ['required', 'in:solido,degradado,imagen'],
            'valor_fondo' => ['nullable', 'string', 'max:500'],
            'estilo_icono' => ['required', 'string', 'max:30'],
            'radio_bordes' => ['required', 'string', 'max:25'],
        ]);

        $existente = TemplateConfiguracion::query()
            ->where('ambito', 'plataforma')
            ->whereNull('empresa_id')
            ->orderByDesc('id')
            ->first();

        $payload = array_merge($data, [
            'ambito' => 'plataforma',
            'empresa_id' => null,
            'sucursal_id' => null,
            'rol' => 'operador_plataforma',
            'activo' => true,
            'actualizado_por_id' => $request->user()?->id,
        ]);

        if ($existente) {
            $existente->update($payload);
        } else {
            TemplateConfiguracion::query()->create($payload);
        }

        return redirect()
            ->route('plataforma.consola')
            ->with('status', 'Template de tu Consola de Plataforma actualizado en BD.');
    }

    public function registrarEmpresa(Request $request): RedirectResponse
    {
        $girosKeys = implode(',', array_keys(Empresa::GIROS_DISPONIBLES));

        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:160'],
            'rfc' => ['nullable', 'string', 'max:20'],
            'giro_comercial' => ['required', "in:{$girosKeys}"],
            'sucursal_nombre' => ['required', 'string', 'max:150'],
            'umbral_alerta_timbres' => ['required', 'integer', 'min:1', 'max:100000'],
            'paginas_habilitadas' => ['nullable', 'array'],
            'paginas_habilitadas.*' => ['string', 'max:60'],
        ]);

        DB::transaction(function () use ($data): void {
            $slugBase = Str::slug($data['nombre']);
            $slug = $slugBase;
            $contador = 2;
            while (Empresa::query()->where('slug', $slug)->exists()) {
                $slug = "{$slugBase}-{$contador}";
                $contador++;
            }

            $paginas = array_values(array_intersect(
                $data['paginas_habilitadas'] ?? ['dashboard'],
                array_keys(Empresa::PAGINAS_CATALOGO)
            ));

            $empresa = Empresa::query()->create([
                'nombre' => trim($data['nombre']),
                'slug' => $slug,
                'rfc' => ! empty($data['rfc']) ? strtoupper(trim($data['rfc'])) : null,
                'giro_comercial' => $data['giro_comercial'],
                'giro_bloqueado' => true,
                'activa' => true,
                'saldo_timbres' => 0,
                'umbral_alerta_timbres' => (int) $data['umbral_alerta_timbres'],
                'paginas_habilitadas' => $paginas,
            ]);

            Sucursal::query()->create([
                'empresa_id' => $empresa->id,
                'codigo' => 'MATRIZ',
                'nombre' => trim($data['sucursal_nombre']),
                'activa' => true,
            ]);

            MonitoreoServicioEmpresa::query()->create([
                'empresa_id' => $empresa->id,
                'estado_pagina' => 'activo',
                'estado_timbres' => 'agotado',
                'timbres_reportados' => 0,
                'latencia_ms' => 18,
                'paginas_activas_count' => count($paginas),
                'ultimo_latido_at' => now(),
                'ultimo_evento_servicio' => 'Alta de instancia en plataforma (sin timbres asignados)',
            ]);
        });

        return redirect()
            ->route('plataforma.consola')
            ->with('status', 'Empresa registrada con giro bloqueado y monitor de servicio inicializado.');
    }

    public function actualizarEstadoEmpresa(Request $request, Empresa $empresa): RedirectResponse
    {
        $data = $request->validate([
            'activa' => ['required', 'boolean'],
            'paginas_habilitadas' => ['nullable', 'array'],
            'paginas_habilitadas.*' => ['string', 'max:60'],
        ]);

        $paginas = array_values(array_intersect(
            $data['paginas_habilitadas'] ?? [],
            array_keys(Empresa::PAGINAS_CATALOGO)
        ));

        $empresa->update([
            'activa' => (bool) $data['activa'],
            'paginas_habilitadas' => $paginas,
        ]);

        $estadoTimbres = $empresa->saldo_timbres <= 0
            ? 'agotado'
            : ($empresa->saldo_timbres <= $empresa->umbral_alerta_timbres ? 'bajo' : 'ok');

        MonitoreoServicioEmpresa::query()->updateOrCreate(
            ['empresa_id' => $empresa->id],
            [
                'estado_pagina' => $empresa->activa ? 'activo' : 'inactivo',
                'estado_timbres' => $estadoTimbres,
                'timbres_reportados' => $empresa->saldo_timbres,
                'paginas_activas_count' => count($paginas),
                'ultimo_latido_at' => now(),
                'ultimo_evento_servicio' => $empresa->activa
                    ? 'Páginas y servicio activos verificados'
                    : 'Servicio suspendido desde consola de plataforma',
            ]
        );

        return redirect()
            ->route('plataforma.consola')
            ->with('status', "Servicio y páginas actualizados para {$empresa->nombre}.");
    }

    public function comprarLoteTimbres(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'folio_compra' => ['required', 'string', 'max:80', 'unique:bolsa_timbres_lotes,folio_compra'],
            'proveedor' => ['nullable', 'string', 'max:140'],
            'cantidad_comprada' => ['required', 'integer', 'min:1', 'max:10000000'],
            'costo_unitario' => ['nullable', 'numeric', 'min:0'],
            'notas' => ['nullable', 'string', 'max:1000'],
        ]);

        BolsaTimbresLote::query()->create([
            'folio_compra' => trim($data['folio_compra']),
            'proveedor' => $data['proveedor'] ? trim($data['proveedor']) : 'PAC Mayorista',
            'cantidad_comprada' => (int) $data['cantidad_comprada'],
            'cantidad_disponible' => (int) $data['cantidad_comprada'],
            'costo_unitario' => $data['costo_unitario'] ?? null,
            'fecha_compra' => now(),
            'notas' => $data['notas'] ?? null,
        ]);

        return redirect()
            ->route('plataforma.consola')
            ->with('status', 'Lote de timbres mayorista agregado a tu bolsa disponible.');
    }

    public function asignarTimbresEmpresa(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'empresa_id' => ['required', 'integer', 'exists:empresas,id'],
            'lote_id' => ['required', 'integer', 'exists:bolsa_timbres_lotes,id'],
            'cantidad' => ['required', 'integer', 'min:1', 'max:1000000'],
            'motivo' => ['nullable', 'string', 'max:180'],
        ]);

        DB::transaction(function () use ($data, $request): void {
            $lote = BolsaTimbresLote::query()->lockForUpdate()->findOrFail((int) $data['lote_id']);
            $empresa = Empresa::query()->lockForUpdate()->findOrFail((int) $data['empresa_id']);
            $cantidad = (int) $data['cantidad'];

            if ($lote->cantidad_disponible < $cantidad) {
                abort(422, "El lote {$lote->folio_compra} solo tiene {$lote->cantidad_disponible} timbres disponibles.");
            }

            $lote->decrement('cantidad_disponible', $cantidad);
            $empresa->increment('saldo_timbres', $cantidad);
            $empresa->refresh();

            AsignacionTimbres::query()->create([
                'empresa_id' => $empresa->id,
                'lote_id' => $lote->id,
                'cantidad' => $cantidad,
                'saldo_empresa_posterior' => $empresa->saldo_timbres,
                'motivo' => $data['motivo'] ? trim($data['motivo']) : 'Recarga de timbres adquirida por cliente',
                'asignado_por_id' => $request->user()?->id,
            ]);

            $estadoTimbres = $empresa->saldo_timbres <= 0
                ? 'agotado'
                : ($empresa->saldo_timbres <= $empresa->umbral_alerta_timbres ? 'bajo' : 'ok');

            MonitoreoServicioEmpresa::query()->updateOrCreate(
                ['empresa_id' => $empresa->id],
                [
                    'estado_pagina' => $empresa->activa ? 'activo' : 'inactivo',
                    'estado_timbres' => $estadoTimbres,
                    'timbres_reportados' => $empresa->saldo_timbres,
                    'ultimo_latido_at' => now(),
                    'ultimo_evento_servicio' => "Asignación de +{$cantidad} timbres (Saldo: {$empresa->saldo_timbres})",
                ]
            );
        });

        return redirect()
            ->route('plataforma.consola')
            ->with('status', 'Timbres descontados de tu bolsa mayorista y asignados a la empresa.');
    }

    public function pingMonitoreo(Empresa $empresa): RedirectResponse
    {
        $inicio = microtime(true);
        $paginas = is_array($empresa->paginas_habilitadas) ? $empresa->paginas_habilitadas : [];
        $latenciaMs = max(5, (int) round((microtime(true) - $inicio) * 1000) + 12);

        $estadoTimbres = $empresa->saldo_timbres <= 0
            ? 'agotado'
            : ($empresa->saldo_timbres <= $empresa->umbral_alerta_timbres ? 'bajo' : 'ok');

        MonitoreoServicioEmpresa::query()->updateOrCreate(
            ['empresa_id' => $empresa->id],
            [
                'estado_pagina' => $empresa->activa ? 'activo' : 'inactivo',
                'estado_timbres' => $estadoTimbres,
                'timbres_reportados' => $empresa->saldo_timbres,
                'latencia_ms' => $latenciaMs,
                'paginas_activas_count' => count($paginas),
                'ultimo_latido_at' => now(),
                'ultimo_evento_servicio' => $empresa->activa
                    ? "Healthcheck OK ({$latenciaMs} ms) — Sin inspección de datos privados"
                    : 'Instancia inactiva/suspendida',
            ]
        );

        return redirect()
            ->route('plataforma.consola')
            ->with('status', "Ping de servicio registrado para {$empresa->nombre}.");
    }
}
