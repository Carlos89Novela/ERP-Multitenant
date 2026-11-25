<?php

namespace Giros\RestauranteCafeteria\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\TemplateConfiguracion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RestauranteCafeteriaController extends Controller
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

        if ($empresa->giro_comercial !== 'restaurante_cafeteria') {
            abort(403, 'Este módulo pertenece exclusivamente al giro de Restaurante / Cafetería.');
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
        $conn = DB::connection('giro_restaurante_cafeteria');

        $mesas = $conn->table('mesas_restaurante')
            ->where('empresa_id', $empresa->id)
            ->orderBy('id')
            ->get();

        $recetas = $conn->table('recetas_platillos')
            ->where('empresa_id', $empresa->id)
            ->orderByDesc('id')
            ->get();

        $comandas = $conn->table('comandas_restaurante')
            ->leftJoin('mesas_restaurante', 'mesas_restaurante.id', '=', 'comandas_restaurante.mesa_id')
            ->where('comandas_restaurante.empresa_id', $empresa->id)
            ->select([
                'comandas_restaurante.*',
                'mesas_restaurante.nombre_numero as mesa_nombre',
            ])
            ->orderByDesc('comandas_restaurante.id')
            ->limit(50)
            ->get();

        $promociones = $conn->table('promociones_combos_restaurante')
            ->where('empresa_id', $empresa->id)
            ->orderByDesc('id')
            ->get();

        return view('giros.restaurante_cafeteria.index', [
            'empresa' => $empresa,
            'template' => $template,
            'mesas' => $mesas,
            'recetas' => $recetas,
            'comandas' => $comandas,
            'promociones' => $promociones,
        ]);
    }

    public function storeMesa(Request $request): RedirectResponse
    {
        $empresa = $this->resolverEmpresaActiva($request);
        $data = $request->validate([
            'nombre_numero' => ['required', 'string', 'max:60'],
            'capacidad' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        DB::connection('giro_restaurante_cafeteria')->table('mesas_restaurante')->insert([
            'empresa_id' => $empresa->id,
            'nombre_numero' => trim($data['nombre_numero']),
            'capacidad' => (int) $data['capacidad'],
            'estado' => 'disponible',
            'activa' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('giro.restaurante.index')->with('status', 'Mesa registrada.');
    }

    public function storeReceta(Request $request): RedirectResponse
    {
        $empresa = $this->resolverEmpresaActiva($request);
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:140'],
            'categoria' => ['nullable', 'string', 'max:80'],
            'ingredientes_instrucciones' => ['nullable', 'string', 'max:3000'],
            'costo_insumos' => ['nullable', 'numeric', 'min:0'],
            'precio_venta' => ['required', 'numeric', 'min:0'],
            'imagen_receta' => ['nullable', 'image', 'max:4096'],
        ]);

        DB::connection('giro_restaurante_cafeteria')->table('recetas_platillos')->insert([
            'empresa_id' => $empresa->id,
            'nombre' => trim($data['nombre']),
            'categoria' => $data['categoria'] ? trim($data['categoria']) : null,
            'ingredientes_instrucciones' => $data['ingredientes_instrucciones'] ? trim($data['ingredientes_instrucciones']) : null,
            'costo_insumos' => (float) ($data['costo_insumos'] ?? 0),
            'precio_venta' => (float) $data['precio_venta'],
            'imagen_path' => $request->hasFile('imagen_receta') ? $request->file('imagen_receta')->store("empresas/{$empresa->id}/recetas", 'public') : null,
            'activa' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('giro.restaurante.index')->with('status', 'Receta / platillo guardado.');
    }

    public function toggleReceta(Request $request, int $recetaId): RedirectResponse
    {
        $empresa = $this->resolverEmpresaActiva($request);
        $conn = DB::connection('giro_restaurante_cafeteria');
        $receta = $conn->table('recetas_platillos')->where('id', $recetaId)->where('empresa_id', $empresa->id)->first();
        if (! $receta) {
            abort(404);
        }

        $conn->table('recetas_platillos')->where('id', $recetaId)->update([
            'activa' => ! (bool) $receta->activa,
            'updated_at' => now(),
        ]);

        return redirect()->route('giro.restaurante.index')->with('status', 'Estado de receta actualizado.');
    }

    public function storeComanda(Request $request): RedirectResponse
    {
        $empresa = $this->resolverEmpresaActiva($request);
        $data = $request->validate([
            'folio' => ['required', 'string', 'max:50'],
            'mesa_id' => ['nullable', 'integer'],
            'total' => ['nullable', 'numeric', 'min:0'],
            'notas' => ['nullable', 'string', 'max:1500'],
        ]);

        $conn = DB::connection('giro_restaurante_cafeteria');
        $existe = $conn->table('comandas_restaurante')
            ->where('empresa_id', $empresa->id)
            ->where('folio', trim($data['folio']))
            ->exists();

        if ($existe) {
            return back()->withInput()->withErrors(['folio' => 'Ya existe una comanda con ese folio en esta empresa.']);
        }

        $conn->table('comandas_restaurante')->insert([
            'empresa_id' => $empresa->id,
            'mesa_id' => $data['mesa_id'] ?? null,
            'folio' => trim($data['folio']),
            'estado' => 'abierta',
            'total' => (float) ($data['total'] ?? 0),
            'notas' => $data['notas'] ? trim($data['notas']) : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('giro.restaurante.index')->with('status', 'Comanda abierta en comandero/KDS.');
    }

    public function actualizarComanda(Request $request, int $comandaId): RedirectResponse
    {
        $empresa = $this->resolverEmpresaActiva($request);
        $data = $request->validate([
            'estado' => ['required', 'in:abierta,preparacion,lista,cerrada,cancelada'],
        ]);

        $actualizadas = DB::connection('giro_restaurante_cafeteria')
            ->table('comandas_restaurante')
            ->where('id', $comandaId)
            ->where('empresa_id', $empresa->id)
            ->update([
                'estado' => $data['estado'],
                'updated_at' => now(),
            ]);

        if ($actualizadas === 0) {
            abort(404);
        }

        return redirect()->route('giro.restaurante.index')->with('status', 'Estado de comanda actualizado.');
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

        DB::connection('giro_restaurante_cafeteria')->table('promociones_combos_restaurante')->insert([
            'empresa_id' => $empresa->id,
            'nombre' => trim($data['nombre']),
            'descripcion' => $data['descripcion'] ? trim($data['descripcion']) : null,
            'precio_combo' => (float) ($data['precio_combo'] ?? 0),
            'descuento_porcentaje' => (float) ($data['descuento_porcentaje'] ?? 0),
            'activa' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('giro.restaurante.index')->with('status', 'Promoción / combo de restaurante guardado.');
    }

    public function togglePromocion(Request $request, int $promocionId): RedirectResponse
    {
        $empresa = $this->resolverEmpresaActiva($request);
        $conn = DB::connection('giro_restaurante_cafeteria');
        $promo = $conn->table('promociones_combos_restaurante')->where('id', $promocionId)->where('empresa_id', $empresa->id)->first();
        if (! $promo) {
            abort(404);
        }

        $conn->table('promociones_combos_restaurante')->where('id', $promocionId)->update([
            'activa' => ! (bool) $promo->activa,
            'updated_at' => now(),
        ]);

        return redirect()->route('giro.restaurante.index')->with('status', 'Estado de promoción actualizado.');
    }
}
