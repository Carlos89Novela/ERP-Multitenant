<?php

namespace Giros\ReparacionComputadoras\Controllers;

use Giros\ReparacionComputadoras\Models\UsuarioTaller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\View\View as ViewContract;

class OrdenesTallerController extends Controller
{
    private const ROLES_PERMITIDOS = [
        'propietario',
        'gerente',
        'subgerente',
        'admin',
        'administrador',
    ];

    protected function rolPermitido(?string $rol): bool
    {
        $limpio = mb_strtolower(trim((string) $rol));
        if ($limpio === '') {
            return false;
        }
        foreach (self::ROLES_PERMITIDOS as $permitido) {
            if ($limpio === $permitido || str_contains($limpio, $permitido)) {
                return true;
            }
        }
        return false;
    }

    protected function resolverEmpresa($usuario)
    {
        return DB::connection('mysql')
            ->table('empresas')
            ->where('id', $usuario->empresa_id)
            ->first();
    }

    protected function directorioDatosEmpresa(int $empresaId): string
    {
        $candidatos = [
            base_path("giros/reparacion_computadoras/storage/empresa_{$empresaId}"),
            storage_path("app/giros/reparacion_computadoras/empresa_{$empresaId}"),
            storage_path("app/reparacion_computadoras/empresa_{$empresaId}"),
        ];
        foreach ($candidatos as $dir) {
            if (is_dir($dir)) {
                return $dir;
            }
        }
        return $candidatos[0];
    }

    protected function rutaOrdenesEmpresa(int $empresaId): string
    {
        return storage_path("app/giros/reparacion_computadoras/empresa_{$empresaId}_ordenes.json");
    }

    protected function obtenerOrdenesCombinadas(int $empresaId): array
    {
        $ordenes = [];
        $vistos = [];

        $archivoPrincipal = $this->rutaOrdenesEmpresa($empresaId);
        $archivos = [];
        if (file_exists($archivoPrincipal)) {
            $archivos[] = $archivoPrincipal;
        }

        foreach ($archivos as $archivo) {
            $raw = @file_get_contents($archivo);
            if (! $raw) {
                continue;
            }
            $decoded = json_decode($raw, true);
            if (! is_array($decoded)) {
                continue;
            }

            $listaOrdenes = isset($decoded['ordenes']) && is_array($decoded['ordenes'])
                ? $decoded['ordenes']
                : (array_is_list($decoded) ? $decoded : []);

            foreach ($listaOrdenes as $item) {
                if (! is_array($item)) {
                    continue;
                }
                $clave = (string) ($item['folio'] ?? $item['id'] ?? md5(json_encode($item)));
                if (isset($vistos[$clave])) {
                    continue;
                }
                $vistos[$clave] = true;

                $ordenes[] = [
                    'id' => (string) ($item['id'] ?? $clave),
                    'folio' => (string) ($item['folio'] ?? ('#' . ($item['id'] ?? 'ORD'))),
                    'cliente_nombre' => (string) ($item['cliente_nombre'] ?? $item['cliente'] ?? 'Cliente'),
                    'cliente_correo' => (string) ($item['cliente_correo'] ?? $item['email'] ?? ''),
                    'equipo' => (string) ($item['equipo'] ?? $item['equipo_tipo'] ?? 'Equipo'),
                    'tipo_equipo' => (string) ($item['tipo_equipo'] ?? ''),
                    'serie' => (string) ($item['serie'] ?? $item['numero_serie'] ?? ''),
                    'servicio' => (string) ($item['servicio'] ?? ''),
                    'falla' => (string) ($item['falla'] ?? $item['falla_reportada'] ?? ''),
                    'estado' => (string) ($item['estado'] ?? 'Recepción'),
                    'asignado_a_id' => isset($item['asignado_a_id']) && $item['asignado_a_id'] !== '' ? (int) $item['asignado_a_id'] : null,
                    'asignado_a_nombre' => isset($item['asignado_a_nombre']) && $item['asignado_a_nombre'] !== '' ? (string) $item['asignado_a_nombre'] : null,
                    'asignado_a_rol' => isset($item['asignado_a_rol']) && $item['asignado_a_rol'] !== '' ? (string) $item['asignado_a_rol'] : null,
                    'diagnostico' => (string) ($item['diagnostico'] ?? ''),
                    'costo_estimado' => $item['costo_estimado'] ?? null,
                    'actualizado_en' => (string) ($item['actualizado_en'] ?? $item['creado_en'] ?? now()->format('Y-m-d H:i')),
                    'estado_revision_cotizacion' => (string) ($item['estado_revision_cotizacion'] ?? 'Sin solicitar'),
                    'observacion_revision_cotizacion' => (string) ($item['observacion_revision_cotizacion'] ?? ''),
                    'autorizacion' => (string) ($item['autorizacion'] ?? 'Pendiente'),
                    'fecha_autorizacion' => $item['fecha_autorizacion'] ?? null,
                    'bitacora_orden' => is_array($item['bitacora_orden'] ?? null) ? $item['bitacora_orden'] : [],
                    'bitacora_equipo' => is_array($item['bitacora_equipo'] ?? null) ? $item['bitacora_equipo'] : [],
                    'historial' => is_array($item['historial'] ?? null) ? $item['historial'] : [],
                ];
            }
        }

        usort($ordenes, fn ($a, $b) => strcmp((string) ($b['actualizado_en'] ?? ''), (string) ($a['actualizado_en'] ?? '')));

        return $ordenes;
    }
    public function ver(Request $request, string $folio): ViewContract|RedirectResponse
    {
        View::addNamespace('taller_computadoras', base_path('giros/reparacion_computadoras/views'));

        /** @var UsuarioTaller $usuario */
        $usuario = $request->attributes->get('usuario_taller');

        if (! $this->rolPermitido($usuario->rol_empresa ?? '')) {
            return redirect()->route('taller.computadoras.dashboard')
                ->with('error', 'Solo propietario, gerente o subgerente pueden administrar órdenes.');
        }

        $empresa = $this->resolverEmpresa($usuario);
        require_once __DIR__ . '/AutogestionTallerController.php';
        $configInternas = AutogestionTallerController::cargarConfiguracionPorAmbito((int) $usuario->empresa_id, 'internas');

        $todas = $this->obtenerOrdenesCombinadas((int) $usuario->empresa_id);
        $orden = null;
        foreach ($todas as $item) {
            if ((string) ($item['folio'] ?? '') === $folio || (string) ($item['id'] ?? '') === $folio) {
                $orden = $item;
                break;
            }
        }

        if (! $orden) {
            return redirect()->route('taller.computadoras.ordenes')
                ->with('error', "No se encontró la orden {$folio}.");
        }

                $equipoTrabajo = [];
        try {
            $dbCfg = config('database.connections.mysql', []);
            $dbCfg['database'] = 'erp_reparacion_computadoras';
            config(['database.connections.taller_rep_db' => $dbCfg]);
            \Illuminate\Support\Facades\DB::purge('taller_rep_db');

            if (\Illuminate\Support\Facades\Schema::connection('taller_rep_db')->hasTable('usuarios_empresa')) {
                $filasEq = \Illuminate\Support\Facades\DB::connection('taller_rep_db')
                    ->table('usuarios_empresa')
                    ->where('empresa_id', (int) $usuario->empresa_id)
                    ->where('activo', 1)
                    ->orderBy('id')
                    ->get();

                foreach ($filasEq as $uEq) {
                    $equipoTrabajo[] = [
                        'id' => (int) ($uEq->id ?? 0),
                        'name' => (string) ($uEq->name ?? $uEq->nombre ?? 'Usuario'),
                        'email' => (string) ($uEq->email ?? ''),
                        'rol_empresa' => (string) ($uEq->rol_empresa ?? $uEq->rol ?? 'usuario'),
                    ];
                }
            }
        } catch (\Throwable $e) {
        }
return view('taller_computadoras::orden_detalle', [
            'configInternas' => $configInternas,
            'usuario' => $usuario,
            'empresa' => $empresa,
            'orden' => $orden,
            'equipoTrabajo' => $equipoTrabajo,
        ]);
    }

    public function index(Request $request): ViewContract|RedirectResponse
    {
        View::addNamespace('taller_computadoras', base_path('giros/reparacion_computadoras/views'));

        /** @var UsuarioTaller $usuario */
        $usuario = $request->attributes->get('usuario_taller');

        if (! $this->rolPermitido($usuario->rol_empresa ?? '')) {
            return redirect()->route('taller.computadoras.dashboard')
                ->with('error', 'Solo propietario, gerente o subgerente pueden gestionar las órdenes solicitadas.');
        }

        $empresa = $this->resolverEmpresa($usuario);

        require_once __DIR__ . '/AutogestionTallerController.php';
        $configInternas = AutogestionTallerController::cargarConfiguracionPorAmbito((int) $usuario->empresa_id, 'internas');

        $todas = $this->obtenerOrdenesCombinadas((int) $usuario->empresa_id);
        $filtroEstado = trim((string) $request->query('estado', ''));
        $busqueda = mb_strtolower(trim((string) $request->query('q', '')));

        $ordenesFiltradas = array_values(array_filter($todas, function ($o) use ($filtroEstado, $busqueda) {
            if ($filtroEstado !== '' && mb_strtolower($o['estado']) !== mb_strtolower($filtroEstado)) {
                return false;
            }
            if ($busqueda !== '') {
                $texto = mb_strtolower(
                    ($o['folio'] ?? '') . ' ' .
                    ($o['cliente_nombre'] ?? '') . ' ' .
                    ($o['equipo'] ?? '') . ' ' .
                    ($o['servicio'] ?? '') . ' ' .
                    ($o['falla'] ?? '')
                );
                if (! str_contains($texto, $busqueda)) {
                    return false;
                }
            }
            return true;
        }));

                $equipoTrabajo = [];
        try {
            $dbCfg = config('database.connections.mysql', []);
            $dbCfg['database'] = 'erp_reparacion_computadoras';
            config(['database.connections.taller_rep_db' => $dbCfg]);
            \Illuminate\Support\Facades\DB::purge('taller_rep_db');
            if (\Illuminate\Support\Facades\Schema::connection('taller_rep_db')->hasTable('usuarios_empresa')) {
                foreach (\Illuminate\Support\Facades\DB::connection('taller_rep_db')->table('usuarios_empresa')->where('empresa_id', (int) $usuario->empresa_id)->where('activo', 1)->orderBy('id')->get() as $uEq) {
                    $equipoTrabajo[] = [
                        'id' => (int) ($uEq->id ?? 0),
                        'name' => (string) ($uEq->name ?? $uEq->nombre ?? 'Usuario'),
                        'email' => (string) ($uEq->email ?? ''),
                        'rol_empresa' => (string) ($uEq->rol_empresa ?? $uEq->rol ?? 'usuario'),
                    ];
                }
            }
        } catch (\Throwable $e) {
        }
return view('taller_computadoras::ordenes', [
            'configInternas' => $configInternas,
            'usuario' => $usuario,
            'empresa' => $empresa,
            'ordenes' => $ordenesFiltradas,
            'totalOrdenes' => count($todas),
            'filtroEstado' => $filtroEstado,
            'busqueda' => $request->query('q', ''),
            'equipoTrabajo' => $equipoTrabajo,
        ]);
    }

    private function costoCambio(mixed $actual, mixed $nuevo): bool
    {
        if ($actual === null || $actual === '') {
            return $nuevo !== null && $nuevo !== '';
        }
        if ($nuevo === null || $nuevo === '') {
            return true;
        }

        return round((float) $actual, 2) !== round((float) $nuevo, 2);
    }

    public function actualizar(Request $request, string $folio): RedirectResponse
    {
        /** @var UsuarioTaller $usuario */
        $usuario = $request->attributes->get('usuario_taller');

        if (! $this->rolPermitido($usuario->rol_empresa ?? '')) {
            return back()->with('error', 'No tienes permiso para actualizar órdenes.');
        }

        $datos = $request->validate([
            'estado' => ['required', 'string', 'max:60'],
            'asignado_a_id' => ['nullable'],
            'estado_revision_cotizacion' => ['nullable', 'string', 'max:60'],
            'observacion_revision_cotizacion' => ['nullable', 'string', 'max:1000'],
            'diagnostico' => ['nullable', 'string', 'max:1500'],
            'costo_estimado' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'accion_orden' => ['nullable', 'string', 'max:500'],
            'accion_equipo' => ['nullable', 'string', 'max:500'],
            'nota' => ['nullable', 'string', 'max:500'],
        ]);

        $empresaId = (int) $usuario->empresa_id;
        $ahora = now()->format('Y-m-d H:i');
        $archivoPrincipal = $this->rutaOrdenesEmpresa($empresaId);

        if (! file_exists($archivoPrincipal)) {
            return back()->with('error', "No se encontró el archivo de órdenes para la orden {$folio}.");
        }

        $raw = @file_get_contents($archivoPrincipal);
        $decoded = $raw ? json_decode($raw, true) : null;
        if (! is_array($decoded)) {
            return back()->with('error', 'No fue posible leer las órdenes registradas.');
        }

        $esListaRaiz = array_is_list($decoded);
        $lista = $esListaRaiz ? $decoded : ($decoded['ordenes'] ?? []);
        if (! is_array($lista)) {
            return back()->with('error', 'Estructura de órdenes inválida.');
        }

        $encontrada = false;
        $errorRegla = null;
        $huboCambios = false;

                $equipoTrabajo = [];
        try {
            $dbCfg = config('database.connections.mysql', []);
            $dbCfg['database'] = 'erp_reparacion_computadoras';
            config(['database.connections.taller_rep_db' => $dbCfg]);
            \Illuminate\Support\Facades\DB::purge('taller_rep_db');
            if (\Illuminate\Support\Facades\Schema::connection('taller_rep_db')->hasTable('usuarios_empresa')) {
                foreach (\Illuminate\Support\Facades\DB::connection('taller_rep_db')->table('usuarios_empresa')->where('empresa_id', $empresaId)->where('activo', 1)->get() as $uEq) {
                    $equipoTrabajo[] = [
                        'id' => (int) ($uEq->id ?? 0),
                        'name' => (string) ($uEq->name ?? $uEq->nombre ?? 'Usuario'),
                        'rol_empresa' => (string) ($uEq->rol_empresa ?? $uEq->rol ?? 'usuario'),
                    ];
                }
            }
        } catch (\Throwable $e) {
        }
foreach ($lista as &$ord) {
            if (! is_array($ord)) {
                continue;
            }
            if ((string) ($ord['folio'] ?? '') !== $folio && (string) ($ord['id'] ?? '') !== $folio) {
                continue;
            }

            $encontrada = true;
            $rawAsig = $request->input('asignado_a_id', $datos['asignado_a_id'] ?? null);
            $nuevoAsigId = ($rawAsig !== null && $rawAsig !== '') ? (int) $rawAsig : null;
            $asigActualId = (isset($ord['asignado_a_id']) && $ord['asignado_a_id'] !== '') ? (int) $ord['asignado_a_id'] : null;
            if ($nuevoAsigId !== $asigActualId || $request->has('asignado_a_id')) {
                $ord['asignado_a_id'] = $nuevoAsigId;
                $nomAsig = null;
                $rolAsig = null;
                if ($nuevoAsigId !== null) {
                    foreach ($equipoTrabajo as $mEq) {
                        if ((int) ($mEq['id'] ?? 0) === $nuevoAsigId) {
                            $nomAsig = (string) ($mEq['name'] ?? '');
                            $rolAsig = (string) ($mEq['rol_empresa'] ?? '');
                            break;
                        }
                    }
                }
                $ord['asignado_a_nombre'] = $nomAsig;
                $ord['asignado_a_rol'] = $rolAsig;
                $ord['actualizado_en'] = $ahora;
                if ($request->boolean('solo_asignacion')) {
                    $ord['historial'] = is_array($ord['historial'] ?? null) ? $ord['historial'] : [];
                    $ord['historial'][] = [
                        'estado' => $ord['estado'] ?? 'Recepción',
                        'nota' => 'Responsable asignado: ' . ($nomAsig ?: 'Sin asignar') . ' (' . ($usuario->name ?? 'Taller') . ')',
                        'fecha' => $ahora,
                    ];
                    $payload = $esListaRaiz ? $lista : array_merge($decoded, ['ordenes' => $lista]);
                    file_put_contents($archivoPrincipal, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                    return back()->with('status', 'Responsable actualizado: ' . ($nomAsig ?: 'Sin asignar') . '.');
                }
                $huboCambios = true;
            }
            $estadoActual = (string) ($ord['estado'] ?? 'Recepción');
            $estadoActualNorm = mb_strtolower(trim($estadoActual));
            $estadoNuevo = trim((string) $datos['estado']);
            $estadoNuevoNorm = mb_strtolower($estadoNuevo);

            // Inmutabilidad terminal: órdenes entregadas o canceladas no se modifican
            if (in_array($estadoActualNorm, ['entregado', 'entregada', 'cancelado', 'cancelada'], true)) {
                $errorRegla = 'No se puede modificar una orden en estado final (' . $estadoActual . ').';
                break;
            }

            $diagnosticoNuevo = array_key_exists('diagnostico', $datos)
                ? trim((string) ($datos['diagnostico'] ?? ''))
                : trim((string) ($ord['diagnostico'] ?? ''));

            $costoNuevo = (array_key_exists('costo_estimado', $datos) && $datos['costo_estimado'] !== null && $datos['costo_estimado'] !== '')
                ? round((float) $datos['costo_estimado'], 2)
                : ($ord['costo_estimado'] ?? null);

            $esListo = str_contains($estadoNuevoNorm, 'listo') || str_contains($estadoNuevoNorm, 'lista');
            $esEntregado = str_contains($estadoNuevoNorm, 'entreg');
            $esCancelada = str_contains($estadoNuevoNorm, 'cancel');
            $esReparacion = str_contains($estadoNuevoNorm, 'repar');
            $esEsperaAut = str_contains($estadoNuevoNorm, 'espera de autorizaci');

            $revActual = (string) ($ord['estado_revision_cotizacion'] ?? 'Sin solicitar');
            $revNueva = trim((string) ($datos['estado_revision_cotizacion'] ?? $revActual));
            $revNuevaNorm = mb_strtolower($revNueva);
            $obsRevNueva = trim((string) ($datos['observacion_revision_cotizacion'] ?? ($ord['observacion_revision_cotizacion'] ?? '')));

            if ($revNuevaNorm === 'rechazada' && $obsRevNueva === '') {
                $errorRegla = 'Debes indicar la observación al rechazar la revisión de cotización.';
                break;
            }

            if (in_array($revNuevaNorm, ['pendiente', 'aprobada'], true)) {
                if ($diagnosticoNuevo === '' || mb_strtolower($diagnosticoNuevo) === 'pendiente de revisión técnica en taller.') {
                    $errorRegla = 'Debes registrar el diagnóstico antes de enviar o aprobar la revisión de cotización.';
                    break;
                }
                if ($costoNuevo === null || $costoNuevo === '') {
                    $errorRegla = 'Debes registrar el costo estimado antes de enviar o aprobar la revisión de cotización.';
                    break;
                }
            }


            $accionOrdenTxt = trim((string) ($datos['accion_orden'] ?? ''));
            $accionEquipoTxt = trim((string) ($datos['accion_equipo'] ?? ''));
            $notaTxt = trim((string) ($datos['nota'] ?? ''));
            $tieneTexto = ($accionOrdenTxt !== '' || $accionEquipoTxt !== '' || $notaTxt !== '');

            if ($esCancelada && ! $tieneTexto) {
                $errorRegla = 'Debes indicar el motivo de la cancelación (en acción de orden, equipo o nota) antes de cancelar la orden.';
                break;
            }

            if (($esReparacion || $esListo || $esEntregado) && isset($ord['autorizacion'])) {
                $autNorm = mb_strtolower(trim((string) $ord['autorizacion']));
                if (in_array($autNorm, ['rechazada', 'rechazado', 'pendiente'], true)) {
                    $errorRegla = 'La orden requiere autorización vigente del cliente antes de pasar a "' . $estadoNuevo . '" (estado de autorización: ' . $ord['autorizacion'] . ').';
                    break;
                }
            }

            if ($esListo || $esEntregado) {
                if ($diagnosticoNuevo === '' || mb_strtolower($diagnosticoNuevo) === 'pendiente de revisión técnica en taller.') {
                    $errorRegla = 'Debes registrar un diagnóstico técnico antes de marcar la orden como "' . $estadoNuevo . '".';
                    break;
                }
                if ($costoNuevo === null || $costoNuevo === '') {
                    $errorRegla = 'Debes registrar el costo estimado/final antes de marcar la orden como "' . $estadoNuevo . '".';
                    break;
                }
            }

            if ($esEntregado) {
                $estActualEsListo = str_contains($estadoActualNorm, 'listo') || str_contains($estadoActualNorm, 'lista');
                if (! $estActualEsListo) {
                    $errorRegla = 'Solo una orden en estado "Listo para entrega" puede marcarse como "' . $estadoNuevo . '".';
                    break;
                }
            }


            $cambioEstado = $estadoActualNorm !== $estadoNuevoNorm;
            $cambioDiag = (string) ($ord['diagnostico'] ?? '') !== $diagnosticoNuevo;
            $cambioCosto = $this->costoCambio($ord['costo_estimado'] ?? null, $costoNuevo);
            $cambioRev = mb_strtolower($revActual) !== $revNuevaNorm || (string) ($ord['observacion_revision_cotizacion'] ?? '') !== $obsRevNueva;
            $sugeridosSel = array_values(array_unique(array_filter(array_map('trim', (array) ($datos['servicios_sugeridos'] ?? [])))));
            $sugeridosPrev = array_values(array_unique(array_filter(array_map('trim', (array) ($ord['servicios_sugeridos'] ?? [])))));
            $motivosRaw = is_array($datos['motivos_servicios_sugeridos'] ?? null) ? $datos['motivos_servicios_sugeridos'] : [];
            $motivosLimpios = [];
            foreach ($motivosRaw as $kSrv => $mTxt) {
                $mTrim = trim((string) $mTxt);
                if ($mTrim !== '') {
                    $motivosLimpios[(string) $kSrv] = $mTrim;
                }
            }
            $motivosPrev = is_array($ord['motivos_servicios_sugeridos'] ?? null) ? $ord['motivos_servicios_sugeridos'] : [];
            $cambioSugeridos = ($sugeridosSel !== $sugeridosPrev) || ($motivosLimpios !== $motivosPrev);

            if ($cambioSugeridos) {
                $ord['servicios_sugeridos'] = $sugeridosSel;
                $ord['motivos_servicios_sugeridos'] = $motivosLimpios;
                $itemsCot = [];
                foreach ($sugeridosSel as $srvLabel) {
                    $precioParse = 0.0;
                    if (preg_match('/\(\$([0-9]+(?:\.[0-9]{1,2})?)\)/', $srvLabel, $mPr)) {
                        $precioParse = (float) $mPr[1];
                    }
                    $motivoSrv = $motivosLimpios[$srvLabel] ?? '';
                    $itemsCot[] = [
                        'servicio' => $srvLabel,
                        'precio' => $precioParse,
                        'motivo' => $motivoSrv,
                        'decision' => 'pendiente',
                    ];
                }
                $ord['cotizacion_items'] = $itemsCot;
                $ord['cotizacion_resumen'] = '';

                $resumenSrv = [];
                foreach ($sugeridosSel as $srvLabel) {
                    $mDet = $motivosLimpios[$srvLabel] ?? '';
                    $resumenSrv[] = $mDet !== '' ? "{$srvLabel} [Motivo: {$mDet}]" : $srvLabel;
                }
                $partesHistorial[] = 'Servicios sugeridos: ' . (! empty($resumenSrv) ? implode(' | ', $resumenSrv) : 'ninguno');

                $huboCambios = true;
            }

            // Siempre que tenga servicios sugeridos y este en Recepcion o Diagnostico (sin elegir Cancelada/Listo/Entregado/En reparacion), pasa a En espera de autorización
            $esRecepcionODiag = str_contains($estadoActualNorm, 'recep') || str_contains($estadoActualNorm, 'diagn');
            if (! empty($sugeridosSel) && $esRecepcionODiag && ! $esCancelada && ! $esEntregado && ! $esListo && ! $esReparacion && ! $esPruebas) {
                $estadoNuevo = 'En espera de autorización';
                $estadoNuevoNorm = mb_strtolower($estadoNuevo);
                $cambioEstado = ($estadoActualNorm !== $estadoNuevoNorm);
                $ord['estado'] = $estadoNuevo;
                $ord['autorizacion'] = 'Pendiente';
                $esPruebas = str_contains($estadoNuevoNorm, 'prueba');
            $esRecepcionODiagAuto = str_contains($estadoActualNorm, 'recep') || str_contains($estadoActualNorm, 'diagn');
            $tieneCostoSugerido = ($costoNuevo !== null && $costoNuevo !== '' && (float) $costoNuevo > 0);
            $disparaEsperaAut = $esRecepcionODiagAuto
                && ! $esCancelada
                && ! $esEntregado
                && ! $esListo
                && ! $esReparacion
                && ! $esPruebas
                && (! empty($sugeridosSel) || ($cambioCosto && $tieneCostoSugerido));

            if ($disparaEsperaAut) {
                if (! empty($sugeridosSel)) {
                    $itemsTmp = [];
                    foreach ($sugeridosSel as $sLbl) {
                        $prTmp = 0.0;
                        if (preg_match('/\($([0-9]+(?:\.[0-9]{1,2})?)\)/', $sLbl, $mTmp)) {
                            $prTmp = (float) $mTmp[1];
                        }
                        $itemsTmp[] = [
                            'servicio' => $sLbl,
                            'precio' => $prTmp,
                            'motivo' => $motivosLimpios[$sLbl] ?? '',
                            'decision' => 'pendiente',
                        ];
                    }
                    $ord['servicios_sugeridos'] = $sugeridosSel;
                    $ord['motivos_servicios_sugeridos'] = $motivosLimpios;
                    $ord['cotizacion_items'] = $itemsTmp;
                } elseif (empty($ord['cotizacion_items']) && $tieneCostoSugerido) {
                    $ord['cotizacion_items'] = [[
                        'servicio' => 'Servicio técnico sugerido ($' . number_format((float) $costoNuevo, 2) . ')',
                        'precio' => round((float) $costoNuevo, 2),
                        'motivo' => $diagnosticoNuevo !== '' ? $diagnosticoNuevo : 'Cotización sugerida tras diagnóstico técnico',
                        'decision' => 'pendiente',
                    ]];
                }

                $estadoNuevo = 'En espera de autorización';
                $estadoNuevoNorm = mb_strtolower($estadoNuevo);
                $cambioEstado = ($estadoActualNorm !== $estadoNuevoNorm);
                $ord['estado'] = $estadoNuevo;
                $ord['autorizacion'] = 'Pendiente';
                $ord['cotizacion_resumen'] = '';
                $huboCambios = true;
            }

            if ($cambioEstado) {
                $ord['estado'] = $estadoNuevo;
                $huboCambios = true;
            }
            }

            if (! $cambioEstado && ! $cambioDiag && ! $cambioCosto && ! $cambioRev && ! $tieneTexto) {
                return back()->with('status', 'Sin cambios detectados en la orden ' . $folio . '.');
            }

            if ($cambioRev) {
                $ord['estado_revision_cotizacion'] = $revNueva;
                $ord['observacion_revision_cotizacion'] = $obsRevNueva;
                if ($revNuevaNorm === 'aprobada' && ! $cambioEstado && ! $esEntregado && ! $esCancelada) {
                    $ord['estado'] = 'En espera de autorización';
                    $ord['autorizacion'] = 'Pendiente';
                    $cambioEstado = true;
                }
                $huboCambios = true;
            }

            if ($esEsperaAut && ($ord['autorizacion'] ?? '') !== 'Autorizada') {
                $ord['autorizacion'] = 'Pendiente';
                $huboCambios = true;
            }

            $esPruebas = str_contains($estadoNuevoNorm, 'prueba');
            $esRecepcionODiagAuto = str_contains($estadoActualNorm, 'recep') || str_contains($estadoActualNorm, 'diagn');
            $tieneCostoSugerido = ($costoNuevo !== null && $costoNuevo !== '' && (float) $costoNuevo > 0);
            $disparaEsperaAut = $esRecepcionODiagAuto
                && ! $esCancelada
                && ! $esEntregado
                && ! $esListo
                && ! $esReparacion
                && ! $esPruebas
                && (! empty($sugeridosSel) || ($cambioCosto && $tieneCostoSugerido));

            if ($disparaEsperaAut) {
                if (! empty($sugeridosSel)) {
                    $itemsTmp = [];
                    foreach ($sugeridosSel as $sLbl) {
                        $prTmp = 0.0;
                        if (preg_match('/\(\$([0-9]+(?:\.[0-9]{1,2})?)\)/', $sLbl, $mTmp)) {
                            $prTmp = (float) $mTmp[1];
                        }
                        $itemsTmp[] = [
                            'servicio' => $sLbl,
                            'precio' => $prTmp,
                            'motivo' => $motivosLimpios[$sLbl] ?? '',
                            'decision' => 'pendiente',
                        ];
                    }
                    $ord['servicios_sugeridos'] = $sugeridosSel;
                    $ord['motivos_servicios_sugeridos'] = $motivosLimpios;
                    $ord['cotizacion_items'] = $itemsTmp;
                } elseif (empty($ord['cotizacion_items']) && $tieneCostoSugerido) {
                    $ord['cotizacion_items'] = [[
                        'servicio' => 'Servicio técnico sugerido ($' . number_format((float) $costoNuevo, 2) . ')',
                        'precio' => round((float) $costoNuevo, 2),
                        'motivo' => $diagnosticoNuevo !== '' ? $diagnosticoNuevo : 'Cotización sugerida tras diagnóstico técnico',
                        'decision' => 'pendiente',
                    ]];
                }

                $estadoNuevo = 'En espera de autorización';
                $estadoNuevoNorm = mb_strtolower($estadoNuevo);
                $cambioEstado = ($estadoActualNorm !== $estadoNuevoNorm);
                $ord['estado'] = $estadoNuevo;
                $ord['autorizacion'] = 'Pendiente';
                $ord['cotizacion_resumen'] = '';
                $huboCambios = true;
            }

            if ($cambioEstado) {
                $ord['estado'] = $estadoNuevo;
                $huboCambios = true;
            }
            if ($cambioDiag) {
                $ord['diagnostico'] = $diagnosticoNuevo;
                $huboCambios = true;
            }
            if ($cambioCosto) {
                $ord['costo_estimado'] = $costoNuevo;
                if ($esListo || $esEntregado) {
                    $ord['costo_final'] = $costoNuevo;
                }
                $huboCambios = true;
            }
            if ($esEntregado && empty($ord['fecha_entrega'])) {
                $ord['fecha_entrega'] = now()->format('Y-m-d H:i');
                $huboCambios = true;
            }

                        $accionOrdenTxt = trim((string) ($datos['accion_orden'] ?? ''));
            $accionEquipoTxt = trim((string) ($datos['accion_equipo'] ?? ''));
            $notaTxt = trim((string) ($datos['nota'] ?? ''));
            $tieneTexto = ($accionOrdenTxt !== '' || $accionEquipoTxt !== '' || $notaTxt !== '');

            $responsable = (string) ($usuario->name ?? $usuario->rol_empresa ?? 'Usuario');

            if ($accionOrdenTxt !== '') {
                $ord['bitacora_orden'] = is_array($ord['bitacora_orden'] ?? null) ? $ord['bitacora_orden'] : [];
                $ord['bitacora_orden'][] = [
                    'detalle' => $accionOrdenTxt,
                    'estado' => $ord['estado'],
                    'usuario' => $responsable,
                    'fecha' => $ahora,
                ];
                $huboCambios = true;
            }

            if ($accionEquipoTxt !== '') {
                $ord['bitacora_equipo'] = is_array($ord['bitacora_equipo'] ?? null) ? $ord['bitacora_equipo'] : [];
                $ord['bitacora_equipo'][] = [
                    'detalle' => $accionEquipoTxt,
                    'estado' => $ord['estado'],
                    'usuario' => $responsable,
                    'fecha' => $ahora,
                ];
                $huboCambios = true;
            }

            $partesHistorial = [];
            if ($cambioEstado) {
                $partesHistorial[] = 'Estado: ' . $estadoActual . ' → ' . $estadoNuevo;
            }
            if ($cambioDiag) {
                $partesHistorial[] = 'Diagnóstico actualizado';
            }
            if ($cambioCosto) {
                $partesHistorial[] = 'Costo: $' . number_format((float) $costoNuevo, 2);
            }
            if ($accionOrdenTxt !== '') {
                $partesHistorial[] = 'Orden: ' . $accionOrdenTxt;
            }
            if ($accionEquipoTxt !== '') {
                $partesHistorial[] = 'Equipo: ' . $accionEquipoTxt;
            }
            if ($notaTxt !== '') {
                $partesHistorial[] = 'Nota: ' . $notaTxt;
            }

            $notaFinal = ! empty($partesHistorial)
                ? implode(' · ', $partesHistorial) . ' (' . $responsable . ')'
                : 'Seguimiento registrado por ' . $responsable . '.';

            $ord['actualizado_en'] = $ahora;
            $ord['historial'] = is_array($ord['historial'] ?? null) ? $ord['historial'] : [];
            $ord['historial'][] = [
                'estado' => $ord['estado'],
                'nota' => $notaFinal,
                'fecha' => $ahora,
            ];
            $huboCambios = true;

            break;
        }
        unset($ord);

        if (! $encontrada) {
            return back()->with('error', "No se encontró la orden {$folio} para actualizar.");
        }

        if ($errorRegla !== null) {
            return back()->with('error', $errorRegla);
        }

        if ($huboCambios) {
            $payloadGuardar = $esListaRaiz
                ? array_values($lista)
                : array_merge($decoded, ['ordenes' => array_values($lista)]);

            file_put_contents(
                $archivoPrincipal,
                json_encode($payloadGuardar, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            );
        }

        return back()->with('status', "Seguimiento guardado para la orden {$folio}.");
    }
}