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
            $demo = [[
                'id' => 'ord-demo-1001',
                'folio' => 'ORD-2026-1001',
                'cliente_id' => null,
                'cliente_nombre' => 'Cliente Mostrador',
                'cliente_correo' => '',
                'equipo' => 'Laptop HP Pavilion 15',
                'tipo_equipo' => 'Laptop',
                'serie' => '5CD2148X9A',
                'falla' => 'No enciende y ventilador hace ruido al intentar arrancar',
                'estado' => 'En reparación',
                'diagnostico' => 'Limpieza térmica profunda, cambio de pasta y revisión de regulador de carga.',
                'costo_estimado' => 850.00,
                'actualizado_en' => now()->format('Y-m-d H:i'),
                'historial' => [
                    ['estado' => 'Recepción', 'nota' => 'Equipo recibido en mostrador.', 'fecha' => now()->subDays(2)->format('Y-m-d H:i')],
                    ['estado' => 'Diagnóstico', 'nota' => 'Revisión térmica y de energía.', 'fecha' => now()->subDay()->format('Y-m-d H:i')],
                    ['estado' => 'En reparación', 'nota' => 'En banco de trabajo.', 'fecha' => now()->format('Y-m-d H:i')],
                ],
            ]];
            $this->guardarOrdenes($empresaId, $demo);

            return $demo;
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
        $datos = $request->validate([
            'tipo_equipo' => ['required', 'string', 'max:50'],
            'marca' => ['required', 'string', 'max:70'],
            'modelo' => ['required', 'string', 'max:90'],
            'serie' => ['nullable', 'string', 'max:80'],
            'falla' => ['required', 'string', 'max:500'],
        ]);

        $empresa = $this->resolverEmpresa();
        $empresaId = (int) $empresa->id;
        $cliente = $this->clienteActual($request, $empresaId);
        if (! $cliente) {
            return redirect()->route('taller.computadoras.cliente.login');
        }

        $clientes = $this->cargarClientes($empresaId);
        $nombreEquipo = trim($datos['tipo_equipo'] . ' ' . $datos['marca'] . ' ' . $datos['modelo']);
        $equipoId = 'eq_' . Str::lower(Str::random(8));

        foreach ($clientes as &$c) {
            if ($c['id'] === $cliente['id']) {
                $c['equipos'] = $c['equipos'] ?? [];
                $c['equipos'][] = [
                    'id' => $equipoId,
                    'tipo' => trim($datos['tipo_equipo']),
                    'marca' => trim($datos['marca']),
                    'modelo' => trim($datos['modelo']),
                    'nombre_completo' => $nombreEquipo,
                    'serie' => trim((string) ($datos['serie'] ?? '')),
                    'registrado_en' => now()->format('Y-m-d H:i'),
                ];
                break;
            }
        }
        unset($c);
        $this->guardarClientes($empresaId, $clientes);

        $ordenes = $this->cargarOrdenes($empresaId);
        $folio = 'ORD-' . date('Ymd') . '-' . random_int(1000, 9999);
        $ahora = now()->format('Y-m-d H:i');

        $ordenes[] = [
            'id' => 'ord_' . Str::lower(Str::random(10)),
            'folio' => $folio,
            'cliente_id' => $cliente['id'],
            'cliente_nombre' => $cliente['nombre'],
            'cliente_correo' => $cliente['email'],
            'equipo_id' => $equipoId,
            'equipo' => $nombreEquipo,
            'tipo_equipo' => trim($datos['tipo_equipo']),
            'serie' => trim((string) ($datos['serie'] ?? '')),
            'falla' => trim($datos['falla']),
            'estado' => 'Recepción',
            'diagnostico' => 'Pendiente de revisión técnica en taller.',
            'costo_estimado' => null,
            'actualizado_en' => $ahora,
            'historial' => [[
                'estado' => 'Recepción',
                'nota' => 'Equipo registrado por el cliente. Falla: ' . trim($datos['falla']),
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

        $ordenes = $this->cargarOrdenes($empresaId);
        $actualizada = false;

        foreach ($ordenes as &$ord) {
            if (($ord['id'] ?? '') === $ordenId && ($ord['cliente_id'] ?? '') === $cliente['id']) {
                if (($ord['estado'] ?? '') === 'En espera de autorización') {
                    $ahora = now()->format('Y-m-d H:i');
                    $ord['estado'] = 'En reparación';
                    $ord['actualizado_en'] = $ahora;
                    $ord['historial'][] = [
                        'estado' => 'En reparación',
                        'nota' => 'Autorizado por el cliente. Continúa la reparación.',
                        'fecha' => $ahora,
                    ];
                    $actualizada = true;
                }
                break;
            }
        }
        unset($ord);

        if ($actualizada) {
            $this->guardarOrdenes($empresaId, $ordenes);

            return back()->with('status', 'Reparación autorizada.');
        }

        return back()->withErrors(['orden' => 'Solo puedes autorizar órdenes en espera de autorización.']);
    }
}
