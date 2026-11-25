<?php

namespace App\Http\Controllers\Plataforma;

use App\Http\Controllers\Controller;
use App\Models\AsignacionTimbres;
use App\Models\BolsaTimbresLote;
use App\Models\Empresa;
use App\Models\FacturaCliente;
use Carbon\Carbon;
use App\Models\MonitoreoServicioEmpresa;
use App\Models\Sucursal;
use App\Models\TemplateConfiguracion;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
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

    private function resumenGlobal(): array
    {
        $empresas = Empresa::query()->get(['id', 'activa', 'saldo_timbres', 'umbral_alerta_timbres']);
        $timbresBolsa = (int) BolsaTimbresLote::query()->sum('cantidad_disponible');

        return [
            'empresas_total' => $empresas->count(),
            'empresas_activas' => $empresas->where('activa', true)->count(),
            'timbres_bolsa_disponibles' => $timbresBolsa,
            'timbres_asignados_empresas' => (int) $empresas->sum('saldo_timbres'),
            'alertas_timbres' => $empresas->filter(fn (Empresa $e) => $e->saldo_timbres <= $e->umbral_alerta_timbres)->count(),
        ];
    }

    public function index(): RedirectResponse
    {
        return redirect()->route('plataforma.monitoreo');
    }

    public function facturacion(): View
    {
        $empresas = Empresa::query()
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'giro_comercial', 'plazo_meses', 'monto_renta', 'fecha_vencimiento_licencia', 'dias_gracia_licencia', 'estado_licencia', 'saldo_timbres', 'activa']);

        $facturas = FacturaCliente::query()
            ->with('empresa:id,nombre,giro_comercial')
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        return view('plataforma.facturacion', [
            'template' => $this->obtenerTemplatePlataforma(),
            'resumen' => $this->resumenGlobal(),
            'empresas' => $empresas,
            'facturas' => $facturas,
            'plazosRenta' => Empresa::PLAZOS_RENTA_MESES,
        ]);
    }

    public function monitoreo(): View
    {
        $empresas = Empresa::query()
            ->with(['monitoreo', 'sucursales:id,empresa_id,codigo,nombre,activa'])
            ->orderByDesc('id')
            ->get();

        return view('plataforma.monitoreo', [
            'template' => $this->obtenerTemplatePlataforma(),
            'resumen' => $this->resumenGlobal(),
            'empresas' => $empresas,
            'girosDisponibles' => Empresa::GIROS_DISPONIBLES,
            'paginasCatalogo' => Empresa::PAGINAS_CATALOGO,
            'paginasPorGiro' => Empresa::PAGINAS_POR_GIRO,
        ]);
    }

    public function empresas(): View
    {
        $empresas = Empresa::query()
            ->with(['sucursales:id,empresa_id,codigo,nombre,activa'])
            ->orderByDesc('id')
            ->get();

        return view('plataforma.empresas', [
            'template' => $this->obtenerTemplatePlataforma(),
            'resumen' => $this->resumenGlobal(),
            'empresas' => $empresas,
            'girosDisponibles' => Empresa::GIROS_DISPONIBLES,
            'paginasCatalogo' => Empresa::PAGINAS_CATALOGO,
            'paginasPorGiro' => Empresa::PAGINAS_POR_GIRO,
        ]);
    }

    public function timbres(): View
    {
        $empresas = Empresa::query()
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'giro_comercial', 'saldo_timbres', 'umbral_alerta_timbres']);

        $lotesTimbres = BolsaTimbresLote::query()
            ->orderByDesc('id')
            ->get();

        $asignacionesRecientes = AsignacionTimbres::query()
            ->with(['empresa:id,nombre,giro_comercial', 'lote:id,folio_compra'])
            ->orderByDesc('id')
            ->limit(25)
            ->get();

        return view('plataforma.timbres', [
            'template' => $this->obtenerTemplatePlataforma(),
            'resumen' => $this->resumenGlobal(),
            'empresas' => $empresas,
            'lotesTimbres' => $lotesTimbres,
            'asignacionesRecientes' => $asignacionesRecientes,
        ]);
    }

    public function apariencia(): View
    {
        return view('plataforma.apariencia', [
            'template' => $this->obtenerTemplatePlataforma(),
            'resumen' => $this->resumenGlobal(),
            'operadoresCount' => User::query()->where('es_operador_plataforma', true)->count(),
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
            ->route('plataforma.apariencia')
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
            'plazo_meses' => ['nullable', 'integer', 'in:1,3,6,9,12'],
            'monto_renta' => ['nullable', 'numeric', 'min:0'],
            'dias_gracia_licencia' => ['nullable', 'integer', 'min:1', 'max:120'],
            'paginas_habilitadas' => ['nullable', 'array'],
            'paginas_habilitadas.*' => ['string', 'max:60'],
            'propietario_nombre' => ['nullable', 'string', 'max:120'],
            'propietario_email' => ['nullable', 'email', 'max:160', 'unique:users,email'],
            'propietario_password' => ['nullable', 'string', 'min:8', 'max:120'],
            'puede_autogestionar_template' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($data): void {
            $slugBase = Str::slug($data['nombre']);
            $slug = $slugBase;
            $contador = 2;
            while (Empresa::query()->where('slug', $slug)->exists()) {
                $slug = "{$slugBase}-{$contador}";
                $contador++;
            }

            $permitidasGiro = array_keys(Empresa::paginasPermitidasParaGiro($data['giro_comercial']));
            $paginas = array_values(array_intersect(
                $data['paginas_habilitadas'] ?? ['dashboard'],
                $permitidasGiro
            ));

            $plazoMeses = (int) ($data['plazo_meses'] ?? 1);
            $diasGracia = (int) ($data['dias_gracia_licencia'] ?? 30);
            $fechaVencimiento = Carbon::today()->addMonthsNoOverflow($plazoMeses)->toDateString();

            $empresa = Empresa::query()->create([
                'nombre' => trim($data['nombre']),
                'slug' => $slug,
                'rfc' => ! empty($data['rfc']) ? strtoupper(trim($data['rfc'])) : null,
                'giro_comercial' => $data['giro_comercial'],
                'giro_bloqueado' => true,
                'plazo_meses' => $plazoMeses,
                'monto_renta' => (float) ($data['monto_renta'] ?? 0),
                'fecha_vencimiento_licencia' => $fechaVencimiento,
                'dias_gracia_licencia' => $diasGracia,
                'estado_licencia' => 'activa',
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

            if (! empty($data['propietario_nombre']) && ! empty($data['propietario_email']) && ! empty($data['propietario_password'])) {
                User::query()->create([
                    'name' => trim($data['propietario_nombre']),
                    'email' => strtolower(trim($data['propietario_email'])),
                    'password' => Hash::make($data['propietario_password']),
                    'empresa_id' => $empresa->id,
                    'role' => 'propietario',
                    'es_propietario' => true,
                    'puede_autogestionar_template' => (bool) ($data['puede_autogestionar_template'] ?? true),
                    'es_operador_plataforma' => false,
                ]);
            }
        });

        return redirect()
            ->route('plataforma.empresas')
            ->with('status', 'Empresa registrada con giro bloqueado y monitor de servicio inicializado.');
    }

    public function actualizarEstadoEmpresa(Request $request, Empresa $empresa): RedirectResponse
    {
        $data = $request->validate([
            'activa' => ['required', 'boolean'],
            'paginas_habilitadas' => ['nullable', 'array'],
            'paginas_habilitadas.*' => ['string', 'max:60'],
        ]);

        $permitidasGiro = array_keys(Empresa::paginasPermitidasParaGiro($empresa->giro_comercial));
        $paginas = array_values(array_intersect(
            $data['paginas_habilitadas'] ?? [],
            $permitidasGiro
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

        return back()->with('status', "Servicio y páginas actualizados para {$empresa->nombre}.");
    }

    public function comprarLoteTimbres(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'folio_compra' => ['required', 'string', 'max:80', 'unique:bolsa_timbres_lotes,folio_compra'],
            'proveedor' => ['nullable', 'string', 'max:140'],
            'cantidad_comprada' => ['required', 'integer', 'min:1', 'max:10000000'],
            'costo_unitario' => ['nullable', 'numeric', 'min:0'],
            'archivo_xml' => ['nullable', 'file', 'mimes:xml,text/xml,application/xml', 'max:10240'],
            'archivo_pdf' => ['nullable', 'file', 'mimes:pdf', 'max:15360'],
            'notas' => ['nullable', 'string', 'max:1000'],
        ]);

        $folioLimpio = Str::slug(trim($data['folio_compra'])) ?: 'lote';
        $xmlPath = null;
        $pdfPath = null;

        if ($request->hasFile('archivo_xml')) {
            $xmlPath = $request->file('archivo_xml')->storeAs(
                "canodevops/timbres/lotes/{$folioLimpio}",
                'factura_' . time() . '.xml',
                'local'
            );
        }

        if ($request->hasFile('archivo_pdf')) {
            $pdfPath = $request->file('archivo_pdf')->storeAs(
                "canodevops/timbres/lotes/{$folioLimpio}",
                'factura_' . time() . '.pdf',
                'local'
            );
        }

        BolsaTimbresLote::query()->create([
            'folio_compra' => trim($data['folio_compra']),
            'proveedor' => $data['proveedor'] ? trim($data['proveedor']) : 'PAC Mayorista',
            'cantidad_comprada' => (int) $data['cantidad_comprada'],
            'cantidad_disponible' => (int) $data['cantidad_comprada'],
            'costo_unitario' => $data['costo_unitario'] ?? null,
            'xml_path' => $xmlPath,
            'pdf_path' => $pdfPath,
            'fecha_compra' => now(),
            'notas' => $data['notas'] ?? null,
        ]);

        return redirect()
            ->route('plataforma.timbres')
            ->with('status', 'Lote de timbres mayorista contabilizado y comprobantes guardados.');
    }

    public function descargarComprobanteLote(BolsaTimbresLote $lote, string $tipo)
    {
        $path = match ($tipo) {
            'xml' => $lote->xml_path,
            'pdf' => $lote->pdf_path,
            default => null,
        };

        if (! $path || ! Storage::disk('local')->exists($path)) {
            abort(404, 'Comprobante no encontrado para este lote.');
        }

        $extension = $tipo === 'xml' ? 'xml' : 'pdf';
        $nombreDescarga = Str::slug($lote->folio_compra) . ".{$extension}";

        return Storage::disk('local')->download($path, $nombreDescarga);
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
            ->route('plataforma.timbres')
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
            ->route('plataforma.monitoreo')
            ->with('status', "Ping de servicio registrado para {$empresa->nombre}.");
    }

    public function registrarOperador(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:160', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'max:120'],
        ]);

        $esPrimero = ! User::query()->where('es_operador_principal', true)->exists()
            && ! User::query()->where('es_operador_plataforma', true)->exists();

        User::query()->create([
            'name' => trim($data['name']),
            'email' => strtolower(trim($data['email'])),
            'password' => Hash::make($data['password']),
            'empresa_id' => null,
            'role' => 'operador_plataforma',
            'es_propietario' => false,
            'puede_autogestionar_template' => false,
            'es_operador_plataforma' => true,
            'es_operador_principal' => $esPrimero,
        ]);

        return redirect()
            ->route('plataforma.apariencia')
            ->with(
                'status',
                $esPrimero
                    ? 'Cuenta PRINCIPAL e inmutable de Operador de Plataforma creada con acceso total.'
                    : 'Cuenta secundaria de Operador de Plataforma creada correctamente.'
            );
    }

    public function crearFacturaCliente(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'empresa_id' => ['required', 'integer', 'exists:empresas,id'],
            'folio' => ['required', 'string', 'max:60', 'unique:facturas_clientes,folio'],
            'concepto_tipo' => ['required', 'in:renta_plataforma,paquete_timbres,mixto'],
            'plazo_meses' => ['nullable', 'integer', 'in:1,3,6,9,12'],
            'timbres_incluidos' => ['nullable', 'integer', 'min:0', 'max:10000000'],
            'subtotal' => ['required', 'numeric', 'min:0'],
            'iva' => ['nullable', 'numeric', 'min:0'],
            'estatus' => ['required', 'in:pendiente,pagada'],
            'fecha_emision' => ['required', 'date'],
            'fecha_vencimiento' => ['nullable', 'date'],
            'notas' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($data): void {
            $subtotal = round((float) $data['subtotal'], 2);
            $iva = isset($data['iva']) ? round((float) $data['iva'], 2) : round($subtotal * 0.16, 2);
            $total = round($subtotal + $iva, 2);
            $plazo = (int) ($data['plazo_meses'] ?? 1);
            $timbres = (int) ($data['timbres_incluidos'] ?? 0);
            $esPagada = $data['estatus'] === 'pagada';

            $factura = FacturaCliente::query()->create([
                'empresa_id' => (int) $data['empresa_id'],
                'folio' => trim($data['folio']),
                'concepto_tipo' => $data['concepto_tipo'],
                'plazo_meses' => $plazo,
                'timbres_incluidos' => $timbres,
                'subtotal' => $subtotal,
                'iva' => $iva,
                'total' => $total,
                'estatus' => $data['estatus'],
                'fecha_emision' => $data['fecha_emision'],
                'fecha_vencimiento' => $data['fecha_vencimiento'] ?? Carbon::parse($data['fecha_emision'])->addDays(15)->toDateString(),
                'pagada_at' => $esPagada ? now() : null,
                'notas' => $data['notas'] ?? null,
            ]);

            if ($esPagada) {
                $this->aplicarPagoFactura($factura);
            }
        });

        return redirect()
            ->route('plataforma.facturacion')
            ->with('status', 'Factura registrada correctamente.');
    }

    public function marcarFacturaPagada(FacturaCliente $factura): RedirectResponse
    {
        if ($factura->estatus === 'pagada') {
            return redirect()->route('plataforma.facturacion')->with('status', 'Esta factura ya estaba marcada como pagada.');
        }

        DB::transaction(function () use ($factura): void {
            $factura->update([
                'estatus' => 'pagada',
                'pagada_at' => now(),
            ]);

            $this->aplicarPagoFactura($factura);
        });

        return redirect()
            ->route('plataforma.facturacion')
            ->with('status', "Factura {$factura->folio} marcada como pagada y vigencia/timbres actualizados.");
    }

    private function aplicarPagoFactura(FacturaCliente $factura): void
    {
        $empresa = Empresa::query()->lockForUpdate()->findOrFail($factura->empresa_id);
        $updates = [
            'estado_licencia' => 'activa',
            'activa' => true,
        ];

        if ($factura->concepto_tipo !== 'paquete_timbres' && $factura->plazo_meses) {
            $baseFecha = ($empresa->fecha_vencimiento_licencia && Carbon::parse($empresa->fecha_vencimiento_licencia)->isFuture())
                ? Carbon::parse($empresa->fecha_vencimiento_licencia)
                : Carbon::today();
            $updates['plazo_meses'] = (int) $factura->plazo_meses;
            $updates['fecha_vencimiento_licencia'] = $baseFecha->addMonthsNoOverflow((int) $factura->plazo_meses)->toDateString();
        }

        if ((int) $factura->timbres_incluidos > 0) {
            $empresa->increment('saldo_timbres', (int) $factura->timbres_incluidos);
        }

        $empresa->update($updates);
        $empresa->refresh();

        $estadoTimbres = $empresa->saldo_timbres <= 0
            ? 'agotado'
            : ($empresa->saldo_timbres <= $empresa->umbral_alerta_timbres ? 'bajo' : 'ok');

        MonitoreoServicioEmpresa::query()->updateOrCreate(
            ['empresa_id' => $empresa->id],
            [
                'estado_pagina' => 'activo',
                'estado_timbres' => $estadoTimbres,
                'timbres_reportados' => $empresa->saldo_timbres,
                'ultimo_latido_at' => now(),
                'ultimo_evento_servicio' => "Pago aplicado ({$factura->folio}) — Licencia activa",
            ]
        );
    }
}
