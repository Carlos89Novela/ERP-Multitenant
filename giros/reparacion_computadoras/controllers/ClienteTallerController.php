<?php

namespace Giros\ReparacionComputadoras\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ClienteTallerController extends Controller
{
    public const SESSION_CLIENTE_KEY = 'taller_computadoras_cliente_id';
    public const SESSION_EMPRESA_KEY = 'taller_computadoras_cliente_empresa_id';

    public const ESTADOS = [
        'Recepción' => 'Recepción',
        'Diagnóstico' => 'Diagnóstico',
        'En reparación' => 'En reparación',
        'En espera de autorización' => 'En espera de autorización',
        'Listo para entrega' => 'Listo para entrega',
        'Entregado' => 'Entregado',
    ];

    protected function resolverEmpresa(): Empresa
    {
        $empresa = Empresa::query()->where('activa', true)->orderBy('id')->first();
        if (! $empresa) {
            $empresa = Empresa::query()->orderBy('id')->first();
        }
        if (! $empresa) {
            abort(404, 'No hay una empresa configurada para el giro de reparación de computadoras.');
        }

        return $empresa;
    }

    protected function rutaClientes(int $empresaId): string
    {
        $dir = storage_path('app/giros/reparacion_computadoras');
        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        return "{$dir}/empresa_{$empresaId}_clientes.json";
    }

    protected function rutaOrdenes(int $empresaId): string
    {
        $dir = storage_path('app/giros/reparacion_computadoras');
        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        return "{$dir}/empresa_{$empresaId}_ordenes.json";
    }

    protected function cargarClientes(int $empresaId): array
    {
        $path = $this->rutaClientes($empresaId);
        if (! file_exists($path)) {
            return [];
        }
        $data = json_decode((string) file_get_contents($path), true);

        return is_array($data) ? array_values($data) : [];
    }

    protected function guardarClientes(int $empresaId, array $clientes): void
    {
        file_put_contents(
            $this->rutaClientes($empresaId),
            json_encode(array_values($clientes), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }

    protected function cargarOrdenes(int $empresaId): array
    {
        $path = $this->rutaOrdenes($empresaId);
        if (! file_exists($path)) {
            $this->guardarOrdenes($empresaId, []);

            return [];
        }

        $data = json_decode((string) file_get_contents($path), true);

        return is_array($data) ? array_values($data) : [];
    }
    protected function guardarOrdenes(int $empresaId, array $ordenes): void
    {
        file_put_contents(
            $this->rutaOrdenes($empresaId),
            json_encode(array_values($ordenes), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }

    protected function clienteActual(Request $request, int $empresaId): ?array
    {
        $clienteId = (string) $request->session()->get(self::SESSION_CLIENTE_KEY, '');
        $sesionEmpresa = (int) $request->session()->get(self::SESSION_EMPRESA_KEY, 0);
        if ($clienteId === '' || $sesionEmpresa !== $empresaId) {
            return null;
        }
        foreach ($this->cargarClientes($empresaId) as $cliente) {
            if (($cliente['id'] ?? '') === $clienteId) {
                return $cliente;
            }
        }

        return null;
    }

    public function mostrarAcceso(Request $request, string $modo = 'login'): View|RedirectResponse
    {
        $empresa = $this->resolverEmpresa();
        if ($this->clienteActual($request, (int) $empresa->id)) {
            return redirect()->route('taller.computadoras.cliente.portal');
        }
        $config = AutogestionTallerController::cargarConfiguracionPublica((int) $empresa->id);

        return view('taller_computadoras::cliente.auth', [
            'empresa' => $empresa,
            'config' => $config,
            'modo' => $modo === 'registro' ? 'registro' : 'login',
        ]);
    }

    public function registrar(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:160'],
            'telefono' => ['nullable', 'string', 'max:40'],
            'password' => ['required', 'string', 'min:6', 'max:100'],
        ]);

        $empresa = $this->resolverEmpresa();
        $empresaId = (int) $empresa->id;
        $clientes = $this->cargarClientes($empresaId);
        $emailNorm = mb_strtolower(trim($datos['email']));

        foreach ($clientes as $c) {
            if (mb_strtolower((string) ($c['email'] ?? '')) === $emailNorm) {
                return back()->withInput($request->except('password'))
                    ->withErrors(['email' => 'Ya existe una cuenta de cliente registrada con ese correo en este taller.']);
            }
        }

        $nuevo = [
            'id' => 'cli_' . Str::lower(Str::random(10)),
            'nombre' => trim($datos['nombre']),
            'email' => $emailNorm,
            'telefono' => trim((string) ($datos['telefono'] ?? '')),
            'password' => Hash::make($datos['password']),
            'equipos' => [],
            'creado_en' => now()->format('Y-m-d H:i'),
        ];

        $clientes[] = $nuevo;
        $this->guardarClientes($empresaId, $clientes);

        $request->session()->put(self::SESSION_CLIENTE_KEY, $nuevo['id']);
        $request->session()->put(self::SESSION_EMPRESA_KEY, $empresaId);

        return redirect()->route('taller.computadoras.cliente.portal')
            ->with('status', 'Cuenta de cliente creada. Ya puedes registrar tus equipos y consultar el estado de tus reparaciones.');
    }

    public function iniciarSesion(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $empresa = $this->resolverEmpresa();
        $empresaId = (int) $empresa->id;
        $emailNorm = mb_strtolower(trim($datos['email']));

        foreach ($this->cargarClientes($empresaId) as $cliente) {
            if (mb_strtolower((string) ($cliente['email'] ?? '')) === $emailNorm
                && Hash::check($datos['password'], (string) ($cliente['password'] ?? ''))) {
                $request->session()->put(self::SESSION_CLIENTE_KEY, $cliente['id']);
                $request->session()->put(self::SESSION_EMPRESA_KEY, $empresaId);

                return redirect()->route('taller.computadoras.cliente.portal')
                    ->with('status', 'Bienvenido, ' . $cliente['nombre'] . '.');
            }
        }

        return back()->withInput($request->except('password'))
            ->withErrors(['email' => 'Correo o contraseña incorrectos.']);
    }

    public function cerrarSesion(Request $request): RedirectResponse
    {
        $request->session()->forget([self::SESSION_CLIENTE_KEY, self::SESSION_EMPRESA_KEY]);

        return redirect()->route('taller.computadoras.cliente.login')
            ->with('status', 'Sesión de cliente cerrada.');
    }

    public function portal(Request $request): View|RedirectResponse
    {
        $empresa = $this->resolverEmpresa();
        $empresaId = (int) $empresa->id;
        $cliente = $this->clienteActual($request, $empresaId);
        if (! $cliente) {
            return redirect()->route('taller.computadoras.cliente.login');
        }

        $config = AutogestionTallerController::cargarConfiguracionPublica($empresaId);
        $todas = $this->cargarOrdenes($empresaId);

        $misOrdenes = array_values(array_filter($todas, static function ($ord) use ($cliente) {
            $mismoId = ! empty($ord['cliente_id']) && $ord['cliente_id'] === $cliente['id'];
            $mismoCorreo = ! empty($ord['cliente_correo'])
                && mb_strtolower((string) $ord['cliente_correo']) === mb_strtolower((string) $cliente['email']);

            return $mismoId || $mismoCorreo;
        }));

        $folioBuscado = strtoupper(trim((string) $request->query('folio', '')));
        $ordenFolio = null;
        if ($folioBuscado !== '') {
            foreach ($todas as $ord) {
                if (strtoupper((string) ($ord['folio'] ?? '')) === $folioBuscado) {
                    $ordenFolio = $ord;
                    break;
                }
            }
        }

        return view('taller_computadoras::cliente.portal', [
            'empresa' => $empresa,
            'config' => $config,
            'cliente' => $cliente,
            'equipos' => $cliente['equipos'] ?? [],
            'ordenes' => $misOrdenes,
            'folioBuscado' => $folioBuscado,
            'ordenFolio' => $ordenFolio,
            'estados' => self::ESTADOS,
        ]);
    }

    public function registrarEquipoYOrden(Request $request): RedirectResponse
    {
        $soloEquipo = $request->boolean('solo_registrar_equipo');

        $datos = $request->validate([
            'tipo_equipo' => ['required', 'string', 'max:70'],
            'marca' => ['required', 'string', 'max:70'],
            'modelo' => ['required', 'string', 'max:90'],
            'serie' => ['nullable', 'string', 'max:80'],
            'numero_serie' => ['nullable', 'string', 'max:80'],
            'servicio_seleccionado' => ['nullable', 'string', 'max:150'],
            'falla_reportada' => ['nullable', 'string', 'max:500'],
            'falla' => ['nullable', 'string', 'max:500'],
        ]);

        $servicioElegido = trim((string) ($datos['servicio_seleccionado'] ?? ''));
        $fallaTexto = trim((string) ($datos['falla_reportada'] ?? $datos['falla'] ?? ''));

        if ($servicioElegido !== '' && $fallaTexto !== '' && ! str_contains(mb_strtolower($fallaTexto), mb_strtolower($servicioElegido))) {
            $fallaTexto = $servicioElegido . ' — ' . $fallaTexto;
        } elseif ($servicioElegido !== '' && $fallaTexto === '') {
            $fallaTexto = $servicioElegido;
        }

        if (! $soloEquipo && $fallaTexto === '') {
            return back()
                ->withInput()
                ->withErrors(['falla' => 'Selecciona un servicio o describe la falla para solicitar reparación.']);
        }

        $empresa = $this->resolverEmpresa();
        $empresaId = (int) $empresa->id;
        $cliente = $this->clienteActual($request, $empresaId);
        if (! $cliente) {
            return redirect()->route('taller.computadoras.cliente.login');
        }

        $serieLimpia = trim((string) ($datos['numero_serie'] ?? $datos['serie'] ?? ''));
        $nombreEquipo = trim($datos['tipo_equipo'] . ' ' . $datos['marca'] . ' ' . $datos['modelo']);
        $equipoId = 'eq_' . Str::lower(Str::random(8));

        $clientes = $this->cargarClientes($empresaId);
        foreach ($clientes as &$c) {
            if (($c['id'] ?? null) === ($cliente['id'] ?? null)) {
                $c['equipos'] = $c['equipos'] ?? [];
                $encontrado = false;
                foreach ($c['equipos'] as $eqExistente) {
                    $mismoNombre = mb_strtolower(trim((string) ($eqExistente['nombre'] ?? ''))) === mb_strtolower($nombreEquipo);
                    $mismaSerie = $serieLimpia !== '' && mb_strtolower(trim((string) ($eqExistente['serie'] ?? ''))) === mb_strtolower($serieLimpia);
                    if ($mismaSerie || ($mismoNombre && $serieLimpia === '')) {
                        $equipoId = (string) ($eqExistente['id'] ?? $equipoId);
                        $encontrado = true;
                        break;
                    }
                }
                if (! $encontrado) {
                    $c['equipos'][] = [
                        'id' => $equipoId,
                        'nombre' => $nombreEquipo,
                        'tipo_equipo' => trim($datos['tipo_equipo']),
                        'marca' => trim($datos['marca']),
                        'modelo' => trim($datos['modelo']),
                        'serie' => $serieLimpia,
                        'registrado_en' => now()->format('Y-m-d H:i'),
                    ];
                }
                break;
            }
        }
        unset($c);
        $this->guardarClientes($empresaId, $clientes);

        if ($soloEquipo) {
            return redirect()->route('taller.computadoras.cliente.portal')
                ->with('status', 'Equipo "' . $nombreEquipo . '" registrado en tus equipos. Ya puedes solicitar reparación cuando lo requieras.');
        }

        $ordenes = $this->cargarOrdenes($empresaId);
        $folio = 'ORD-' . now()->format('Y') . '-' . strtoupper(Str::random(5));
        $ahora = now()->format('Y-m-d H:i');

        $ordenes[] = [
            'id' => 'ord_' . Str::lower(Str::random(10)),
            'folio' => $folio,
            'cliente_id' => $cliente['id'],
            'cliente_nombre' => $cliente['nombre'] ?? 'Cliente',
            'equipo_id' => $equipoId,
            'equipo' => $nombreEquipo,
            'tipo_equipo' => trim($datos['tipo_equipo']),
            'serie' => $serieLimpia,
            'servicio' => $servicioElegido,
            'falla' => $fallaTexto,
            'estado' => 'Recepción',
            'diagnostico' => 'Pendiente de revisión técnica en taller.',
            'costo_estimado' => null,
            'actualizado_en' => $ahora,
            'historial' => [[
                'estado' => 'Recepción',
                'nota' => 'Orden generada por el cliente. Detalle: ' . $fallaTexto,
                'fecha' => $ahora,
            ]],
        ];

        $this->guardarOrdenes($empresaId, $ordenes);

        return redirect()->route('taller.computadoras.cliente.portal')
            ->with('status', 'Equipo "' . $nombreEquipo . '" registrado y orden ' . $folio . ' creada en estado Recepción.');
    }
    public function autorizarOrden(Request $request, string $ordenId): RedirectResponse
    {
        $empresa = $this->resolverEmpresa();
        $empresaId = (int) $empresa->id;
        $cliente = $this->clienteActual($request, $empresaId);
        if (! $cliente) {
            return redirect()->route('taller.computadoras.cliente.login');
        }

        $decisiones = (array) $request->input('decisiones_servicios', []);
        $ordenes = $this->cargarOrdenes($empresaId);
        $actualizada = false;
        $mensajeEstado = 'Reparación autorizada.';

        foreach ($ordenes as &$ord) {
            if (($ord['id'] ?? '') === $ordenId && ($ord['cliente_id'] ?? '') === $cliente['id']) {
                if (($ord['estado'] ?? '') === 'En espera de autorización') {
                    $ahora = now()->format('Y-m-d H:i');
                    $itemsCot = is_array($ord['cotizacion_items'] ?? null) ? $ord['cotizacion_items'] : [];
                    $sugeridos = is_array($ord['servicios_sugeridos'] ?? null) ? $ord['servicios_sugeridos'] : [];

                    if (empty($itemsCot) && ! empty($sugeridos)) {
                        foreach ($sugeridos as $srvLbl) {
                            $pr = 0.0;
                            if (preg_match('/\(\$([0-9]+(?:\.[0-9]{1,2})?)\)/', (string) $srvLbl, $mP)) {
                                $pr = (float) $mP[1];
                            }
                            $itemsCot[] = [
                                'servicio' => (string) $srvLbl,
                                'precio' => $pr,
                                'motivo' => (string) ($ord['motivos_servicios_sugeridos'][$srvLbl] ?? ''),
                                'decision' => 'pendiente',
                            ];
                        }
                    }

                    if (! empty($itemsCot)) {
                        $aceptados = [];
                        $rechazados = [];
                        $totalAceptado = 0.0;

                        foreach ($itemsCot as $idx => &$it) {
                            $srvNombre = (string) ($it['servicio'] ?? '');
                            $dec = strtolower(trim((string) ($decisiones[$idx] ?? $decisiones[$srvNombre] ?? 'aceptar')));
                            if (str_starts_with($dec, 'rechaz')) {
                                $it['decision'] = 'rechazada';
                                $rechazados[] = $srvNombre;
                            } else {
                                $it['decision'] = 'aceptada';
                                $aceptados[] = $srvNombre;
                                $totalAceptado += (float) ($it['precio'] ?? 0);
                            }
                        }
                        unset($it);

                        $ord['cotizacion_items'] = $itemsCot;
                        $ord['servicios_aceptados'] = $aceptados;
                        $ord['servicios_rechazados'] = $rechazados;
                        $ord['actualizado_en'] = $ahora;

                        if (count($aceptados) === 0) {
                            // Rechazo total de servicios: NO se cancela automaticamente la orden; queda para retiro en mostrador
                            $ord['autorizacion'] = 'Rechazada';
                            $ord['estado'] = 'Listo para entrega';
                            $ord['costo_estimado'] = 0.0;
                            $ord['cotizacion_resumen'] = 'Cliente rechazó todos los servicios sugeridos. Equipo listo para retiro/entrega en mostrador (cancelación y liberación por mostrador).';
                            $mensajeEstado = 'Rechazaste los servicios sugeridos. La orden no se cancela automáticamente: el equipo queda disponible para retiro/entrega en mostrador.';
                            $ord['historial'][] = [
                                'estado' => $ord['estado'],
                                'nota' => 'Cliente rechazó todos los servicios sugeridos (' . implode(', ', $rechazados) . '). Equipo disponible para retiro en mostrador; cancelación y liberación a cargo de mostrador.',
                                'fecha' => $ahora,
                            ];
                        } else {
                            // Al menos un servicio aceptado (parcial o total)
                            $ord['autorizacion'] = 'Autorizada';
                            $ord['fecha_autorizacion'] = $ahora;
                            $ord['estado'] = 'En reparación';
                            $ord['costo_estimado'] = round($totalAceptado, 2);
                            $ord['cotizacion_resumen'] = 'Aceptados: ' . implode(', ', $aceptados)
                                . (! empty($rechazados) ? ' | Rechazados: ' . implode(', ', $rechazados) : '');
                            $mensajeEstado = count($rechazados) > 0
                                ? 'Cotización respondida parcialmente: se autorizaron los servicios aceptados y se descartaron los rechazados.'
                                : 'Cotización autorizada. Continúa la reparación con los servicios seleccionados.';
                            $ord['historial'][] = [
                                'estado' => 'En reparación',
                                'nota' => 'Decisión del cliente — Aceptados: ' . implode(', ', $aceptados)
                                    . (! empty($rechazados) ? ' · Rechazados: ' . implode(', ', $rechazados) : '')
                                    . ' (Total autorizado: $' . number_format($totalAceptado, 2) . ').',
                                'fecha' => $ahora,
                            ];
                        }
                        $actualizada = true;
                    } else {
                        $ord['estado'] = 'En reparación';
                        $ord['autorizacion'] = 'Autorizada';
                        $ord['fecha_autorizacion'] = $ahora;
                        $ord['actualizado_en'] = $ahora;
                        $ord['historial'][] = [
                            'estado' => 'En reparación',
                            'nota' => 'Autorizado por el cliente. Continúa la reparación.',
                            'fecha' => $ahora,
                        ];
                        $actualizada = true;
                    }
                }
                break;
            }
        }
        unset($ord);

        if ($actualizada) {
            $this->guardarOrdenes($empresaId, $ordenes);

            return back()->with('status', $mensajeEstado);
        }

        return back()->with('error', 'No fue posible actualizar la autorización de la orden.');
    }
}
