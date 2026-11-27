<?php

namespace Giros\ReparacionComputadoras\Controllers;

use Giros\ReparacionComputadoras\Models\AlmacenTaller;
use Giros\ReparacionComputadoras\Models\ProductoTaller;
use Giros\ReparacionComputadoras\Models\TransferenciaInventarioTaller;
use Giros\ReparacionComputadoras\Models\UsuarioTaller;
use Giros\ReparacionComputadoras\Services\AjusteInventarioTallerService;
use Giros\ReparacionComputadoras\Services\CatalogoInventarioTallerService;
use Giros\ReparacionComputadoras\Services\ConsultaExistenciasTallerService;
use Giros\ReparacionComputadoras\Services\ContextoPropietarioTaller;
use Giros\ReparacionComputadoras\Services\GestionAlmacenesTallerService;
use Giros\ReparacionComputadoras\Services\RegistrarEntradaTallerService;
use Giros\ReparacionComputadoras\Services\SalidasYConsumosTallerService;
use Giros\ReparacionComputadoras\Services\TraspasosInventarioTallerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class InventarioTallerController
{
    private function propietario(Request $request): UsuarioTaller
    {
        /** @var UsuarioTaller|null $usuario */
        $usuario = $request->attributes->get('usuario_taller');

        return ContextoPropietarioTaller::validarPropietario($usuario);
    }

    public function storeCategoria(Request $request, CatalogoInventarioTallerService $catalogo): RedirectResponse
    {
        $this->propietario($request);

        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:140'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'activa' => ['nullable', 'boolean'],
        ]);

        $categoria = $catalogo->crearCategoria($datos);

        return back()->with('success', "Categoría {$categoria->nombre} creada correctamente.");
    }

    public function storeProducto(Request $request, CatalogoInventarioTallerService $catalogo): RedirectResponse
    {
        $this->propietario($request);

        $datos = $request->validate([
            'categoria_id' => ['nullable', 'integer'],
            'sku' => ['nullable', 'string', 'max:80'],
            'nombre' => ['required', 'string', 'max:180'],
            'unidad_medida' => ['nullable', 'string', 'max:40'],
            'costo_referencia' => ['nullable', 'numeric', 'min:0'],
            'precio_venta' => ['nullable', 'numeric', 'min:0'],
            'activo' => ['nullable', 'boolean'],
        ]);

        $producto = $catalogo->crearProducto($datos);

        return back()->with('success', "Producto {$producto->nombre} ({$producto->sku}) registrado.");
    }

    public function storeEntrada(Request $request, RegistrarEntradaTallerService $entradas): RedirectResponse
    {
        $propietario = $this->propietario($request);

        $datos = $request->validate([
            'almacen_id' => ['required', 'integer'],
            'tipo' => ['nullable', 'string', 'max:40'],
            'referencia_externa' => ['nullable', 'string', 'max:150'],
            'motivo' => ['required', 'string', 'max:1000'],
            'lineas' => ['required', 'array', 'min:1'],
            'lineas.*.producto_id' => ['required', 'integer'],
            'lineas.*.cantidad' => ['required', 'numeric', 'gt:0'],
            'lineas.*.costo_unitario' => ['required', 'numeric', 'min:0'],
        ]);

        /** @var AlmacenTaller $almacen */
        $almacen = AlmacenTaller::query()->findOrFail((int) $datos['almacen_id']);
        $recepcion = $entradas->ejecutar($almacen, $propietario, $datos);

        return back()->with('success', "Entrada {$recepcion->folio} registrada en {$almacen->nombre}.");
    }

    public function storeSalidaDirecta(Request $request, SalidasYConsumosTallerService $salidas): RedirectResponse
    {
        $propietario = $this->propietario($request);

        $datos = $request->validate([
            'almacen_id' => ['required', 'integer'],
            'producto_id' => ['required', 'integer'],
            'cantidad' => ['required', 'numeric', 'gt:0'],
            'motivo' => ['required', 'string', 'max:1000'],
            'referencia_tipo' => ['nullable', 'string', 'max:150'],
            'referencia_id' => ['nullable', 'integer'],
        ]);

        /** @var AlmacenTaller $almacen */
        $almacen = AlmacenTaller::query()->findOrFail((int) $datos['almacen_id']);
        /** @var ProductoTaller $producto */
        $producto = ProductoTaller::query()->findOrFail((int) $datos['producto_id']);

        $salidas->registrarSalidaDirecta($almacen, $producto, $propietario, $datos);

        return back()->with('success', "Salida directa registrada para {$producto->nombre}.");
    }

    public function storeConsumoOrden(Request $request, SalidasYConsumosTallerService $salidas): RedirectResponse
    {
        $propietario = $this->propietario($request);

        $datos = $request->validate([
            'almacen_id' => ['required', 'integer'],
            'producto_id' => ['required', 'integer'],
            'orden_reparacion_id' => ['nullable', 'integer'],
            'orden_folio' => ['nullable', 'string', 'max:50'],
            'cantidad' => ['required', 'numeric', 'gt:0'],
            'motivo' => ['nullable', 'string', 'max:1000'],
        ]);

        /** @var AlmacenTaller $almacen */
        $almacen = AlmacenTaller::query()->findOrFail((int) $datos['almacen_id']);
        /** @var ProductoTaller $producto */
        $producto = ProductoTaller::query()->findOrFail((int) $datos['producto_id']);

        $mov = $salidas->consumirEnOrdenReparacion($almacen, $producto, $propietario, $datos);

        return back()->with('success', "Consumo {$mov->folio} aplicado a la orden de reparación.");
    }

    public function storeAjuste(Request $request, AjusteInventarioTallerService $ajustes): RedirectResponse
    {
        $propietario = $this->propietario($request);

        $datos = $request->validate([
            'almacen_id' => ['required', 'integer'],
            'producto_id' => ['required', 'integer'],
            'cantidad_objetivo' => ['required', 'numeric', 'min:0'],
            'motivo' => ['required', 'string', 'max:1000'],
        ]);

        /** @var AlmacenTaller $almacen */
        $almacen = AlmacenTaller::query()->findOrFail((int) $datos['almacen_id']);
        /** @var ProductoTaller $producto */
        $producto = ProductoTaller::query()->findOrFail((int) $datos['producto_id']);

        $ajustes->ejecutar($almacen, $producto, $propietario, $datos);

        return back()->with('success', "Ajuste de inventario aplicado a {$producto->nombre}.");
    }

    public function storeTransferencia(Request $request, TraspasosInventarioTallerService $traspasos): RedirectResponse
    {
        $propietario = $this->propietario($request);

        $datos = $request->validate([
            'almacen_origen_id' => ['required', 'integer'],
            'almacen_destino_id' => ['required', 'integer'],
            'motivo' => ['required', 'string', 'max:1000'],
            'lineas' => ['required', 'array', 'min:1'],
            'lineas.*.producto_id' => ['required', 'integer'],
            'lineas.*.cantidad' => ['required', 'numeric', 'gt:0'],
        ]);

        /** @var AlmacenTaller $origen */
        $origen = AlmacenTaller::query()->findOrFail((int) $datos['almacen_origen_id']);
        /** @var AlmacenTaller $destino */
        $destino = AlmacenTaller::query()->findOrFail((int) $datos['almacen_destino_id']);

        $trf = $traspasos->solicitarTraspaso($origen, $destino, $propietario, $datos);

        return back()->with('success', "Solicitud de traspaso {$trf->folio} creada.");
    }

    public function despacharTransferencia(
        Request $request,
        int $transferenciaId,
        TraspasosInventarioTallerService $traspasos
    ): RedirectResponse {
        $propietario = $this->propietario($request);

        $datos = $request->validate([
            'observaciones' => ['nullable', 'string', 'max:1000'],
            'lineas' => ['nullable', 'array'],
        ]);

        /** @var TransferenciaInventarioTaller $trf */
        $trf = TransferenciaInventarioTaller::query()->findOrFail($transferenciaId);
        $actualizada = $traspasos->despacharATransito($trf, $propietario, $datos);

        return back()->with('success', "Traspaso {$actualizada->folio} despachado a almacén de tránsito.");
    }

    public function recibirTransferencia(
        Request $request,
        int $transferenciaId,
        TraspasosInventarioTallerService $traspasos
    ): RedirectResponse {
        $propietario = $this->propietario($request);

        $datos = $request->validate([
            'observaciones' => ['nullable', 'string', 'max:1000'],
            'lineas' => ['nullable', 'array'],
        ]);

        /** @var TransferenciaInventarioTaller $trf */
        $trf = TransferenciaInventarioTaller::query()->findOrFail($transferenciaId);
        $actualizada = $traspasos->recibirEnDestino($trf, $propietario, $datos);

        return back()->with('success', "Traspaso {$actualizada->folio} recibido en almacén destino.");
    }

    public function cancelarTransferencia(
        Request $request,
        int $transferenciaId,
        TraspasosInventarioTallerService $traspasos
    ): RedirectResponse {
        $propietario = $this->propietario($request);

        $datos = $request->validate([
            'motivo' => ['required', 'string', 'max:1000'],
        ]);

        /** @var TransferenciaInventarioTaller $trf */
        $trf = TransferenciaInventarioTaller::query()->findOrFail($transferenciaId);
        $actualizada = $traspasos->cancelarSolicitud($trf, $propietario, (string) $datos['motivo']);

        return back()->with('success', "Traspaso {$actualizada->folio} cancelado.");
    }

    /**
     * Datos consolidados para la vista principal de inventario y consulta entre almacenes.
     *
     * @param  array{sucursal_id?: mixed, almacen_id?: mixed, producto_id?: mixed, buscar?: mixed}  $filtros
     * @return array<string, mixed>
     */
    public function datosPanel(
        Request $request,
        GestionAlmacenesTallerService $almacenesService,
        ConsultaExistenciasTallerService $consultaService,
        array $filtros = []
    ): array {
        $this->propietario($request);
        $estructura = $almacenesService->asegurarEstructuraBase();

        $filtrosActivos = array_merge([
            'buscar' => $request->query('buscar'),
            'sucursal_id' => $request->query('sucursal_id'),
            'almacen_id' => $request->query('almacen_id'),
            'producto_id' => $request->query('producto_id'),
        ], $filtros);

        return [
            'estructura' => $estructura,
            'matriz' => $consultaService->matrizEntreAlmacenes(
                is_string($filtrosActivos['buscar'] ?? null) ? $filtrosActivos['buscar'] : null
            ),
            'existencias' => $consultaService->listarExistencias($filtrosActivos),
            'kardex' => $consultaService->kardex($filtrosActivos),
            'transferencias' => TransferenciaInventarioTaller::query()
                ->with(['almacenOrigen.sucursal', 'almacenDestino.sucursal', 'almacenTransito', 'detalles.producto'])
                ->orderByDesc('id')
                ->limit(50)
                ->get(),
        ];
    }
}