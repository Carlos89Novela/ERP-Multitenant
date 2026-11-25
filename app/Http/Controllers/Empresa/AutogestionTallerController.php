<?php

namespace App\Http\Controllers\Empresa;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\TemplateConfiguracion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AutogestionTallerController extends Controller
{
    public function resolverTaller(Request $request, string $giroEsperado): Empresa
    {
        $user = $request->user();
        $empresa = Empresa::query()->findOrFail((int) $user->empresa_id);

        if (! $empresa->activa) {
            abort(403, 'Esta empresa se encuentra suspendida.');
        }

        if ($empresa->evaluarEstadoLicenciaEfectivo() === 'suspendida_morosidad') {
            abort(403, 'La licencia de la empresa superó el periodo de tolerancia tras el vencimiento.');
        }

        if ($empresa->giro_comercial !== $giroEsperado) {
            abort(403, 'Este panel de autogestión no corresponde al giro de tu empresa.');
        }

        return $empresa;
    }

    public function resolverTemplate(Empresa $empresa): TemplateConfiguracion
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

    public function vistaComputadoras(Request $request): View
    {
        $empresa = $this->resolverTaller($request, 'reparacion_computadoras');
        $template = $this->resolverTemplate($empresa);
        $elementos = DB::table('empresa_carrusel_slides')
            ->where('empresa_id', $empresa->id)
            ->orderBy('orden')
            ->orderByDesc('id')
            ->get();

        return view('giros.reparacion_computadoras.autogestion', [
            'empresa' => $empresa,
            'template' => $template,
            'elementos' => $elementos,
            'giroRutaPrefix' => 'giro.computadoras',
            'etiquetaGiro' => 'Reparación de Computadoras',
        ]);
    }

    public function vistaCelulares(Request $request): View
    {
        $empresa = $this->resolverTaller($request, 'reparacion_celulares');
        $template = $this->resolverTemplate($empresa);
        $elementos = DB::table('empresa_carrusel_slides')
            ->where('empresa_id', $empresa->id)
            ->orderBy('orden')
            ->orderByDesc('id')
            ->get();

        return view('giros.reparacion_celulares.autogestion', [
            'empresa' => $empresa,
            'template' => $template,
            'elementos' => $elementos,
            'giroRutaPrefix' => 'giro.celulares',
            'etiquetaGiro' => 'Reparación de Celulares',
        ]);
    }

    public function guardarAparienciaTaller(Request $request, string $giroEsperado): RedirectResponse
    {
        $empresa = $this->resolverTaller($request, $giroEsperado);

        $data = $request->validate([
            'fuente_primaria' => ['nullable', 'string', 'max:100'],
            'color_primario' => ['required', 'string', 'max:20'],
            'color_secundario' => ['required', 'string', 'max:20'],
            'color_acento' => ['required', 'string', 'max:20'],
            'color_fondo_pagina' => ['required', 'string', 'max:20'],
            'color_fondo_superficie' => ['required', 'string', 'max:20'],
            'color_texto_principal' => ['required', 'string', 'max:20'],
            'color_texto_secundario' => ['required', 'string', 'max:20'],
            'color_borde' => ['required', 'string', 'max:20'],
            'radio_bordes' => ['required', 'string', 'max:25'],
            'icono_predeterminado' => ['nullable', 'string', 'max:40'],
            'logo_archivo' => ['nullable', 'image', 'max:3072'],
        ]);

        $template = TemplateConfiguracion::query()
            ->where('empresa_id', $empresa->id)
            ->orderByDesc('id')
            ->first();

        if (! $template) {
            $template = new TemplateConfiguracion(TemplateConfiguracion::defaultsPlataforma());
            $template->ambito = 'empresa';
            $template->empresa_id = $empresa->id;
            $template->nombre = 'Tema Taller ' . $empresa->nombre;
        }

        if ($request->hasFile('logo_archivo')) {
            if (! empty($template->logo_path) && Storage::disk('public')->exists($template->logo_path)) {
                Storage::disk('public')->delete($template->logo_path);
            }
            $template->logo_path = $request->file('logo_archivo')->store("empresas/{$empresa->id}/marca", 'public');
        }

        if (! empty($data['fuente_primaria'])) {
            $template->fuente_primaria = trim($data['fuente_primaria']);
        }
        $template->color_primario = $data['color_primario'];
        $template->color_secundario = $data['color_secundario'];
        $template->color_acento = $data['color_acento'];
        $template->color_fondo_pagina = $data['color_fondo_pagina'];
        $template->color_fondo_superficie = $data['color_fondo_superficie'];
        $template->color_texto_principal = $data['color_texto_principal'];
        $template->color_texto_secundario = $data['color_texto_secundario'];
        $template->color_borde = $data['color_borde'];
        $template->radio_bordes = $data['radio_bordes'];
        $template->icono_predeterminado = $data['icono_predeterminado'] ? trim($data['icono_predeterminado']) : '🔧';
        $template->activo = true;
        $template->save();

        $rutaDestino = $giroEsperado === 'reparacion_computadoras'
            ? route('giro.computadoras.autogestion')
            : route('giro.celulares.autogestion');

        return redirect($rutaDestino)->with('status', 'Identidad visual y apariencia del taller guardadas.');
    }

    public function storeElementoTaller(Request $request, string $giroEsperado): RedirectResponse
    {
        $empresa = $this->resolverTaller($request, $giroEsperado);

        $data = $request->validate([
            'tipo' => ['required', 'in:slide,destacado'],
            'titulo' => ['required', 'string', 'max:160'],
            'subtitulo' => ['nullable', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string', 'max:2000'],
            'precio' => ['nullable', 'numeric', 'min:0'],
            'icono_texto' => ['nullable', 'string', 'max:40'],
            'orden' => ['nullable', 'integer', 'min:1', 'max:999'],
            'imagen_archivo' => ['nullable', 'image', 'max:4096'],
        ]);

        $imagenPath = null;
        if ($request->hasFile('imagen_archivo')) {
            $imagenPath = $request->file('imagen_archivo')->store("empresas/{$empresa->id}/taller", 'public');
        }

        DB::table('empresa_carrusel_slides')->insert([
            'empresa_id' => $empresa->id,
            'tipo' => $data['tipo'],
            'titulo' => trim($data['titulo']),
            'subtitulo' => $data['subtitulo'] ? trim($data['subtitulo']) : null,
            'descripcion' => $data['descripcion'] ? trim($data['descripcion']) : null,
            'precio' => isset($data['precio']) && $data['precio'] !== '' ? (float) $data['precio'] : null,
            'imagen_path' => $imagenPath,
            'icono_texto' => $data['icono_texto'] ? trim($data['icono_texto']) : null,
            'orden' => (int) ($data['orden'] ?? 1),
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $rutaDestino = $giroEsperado === 'reparacion_computadoras'
            ? route('giro.computadoras.autogestion')
            : route('giro.celulares.autogestion');

        return redirect($rutaDestino)->with('status', 'Elemento con foto/ícono agregado al taller y escaparate público.');
    }

    public function toggleElementoTaller(Request $request, string $giroEsperado, int $elementoId): RedirectResponse
    {
        $empresa = $this->resolverTaller($request, $giroEsperado);

        $item = DB::table('empresa_carrusel_slides')
            ->where('id', $elementoId)
            ->where('empresa_id', $empresa->id)
            ->first();

        if (! $item) {
            abort(404);
        }

        DB::table('empresa_carrusel_slides')
            ->where('id', $elementoId)
            ->update([
                'activo' => ! (bool) $item->activo,
                'updated_at' => now(),
            ]);

        $rutaDestino = $giroEsperado === 'reparacion_computadoras'
            ? route('giro.computadoras.autogestion')
            : route('giro.celulares.autogestion');

        return redirect($rutaDestino)->with('status', 'Estado del elemento actualizado.');
    }

    public function destroyElementoTaller(Request $request, string $giroEsperado, int $elementoId): RedirectResponse
    {
        $empresa = $this->resolverTaller($request, $giroEsperado);

        $item = DB::table('empresa_carrusel_slides')
            ->where('id', $elementoId)
            ->where('empresa_id', $empresa->id)
            ->first();

        if (! $item) {
            abort(404);
        }

        if (! empty($item->imagen_path) && Storage::disk('public')->exists($item->imagen_path)) {
            Storage::disk('public')->delete($item->imagen_path);
        }

        DB::table('empresa_carrusel_slides')
            ->where('id', $elementoId)
            ->where('empresa_id', $empresa->id)
            ->delete();

        $rutaDestino = $giroEsperado === 'reparacion_computadoras'
            ? route('giro.computadoras.autogestion')
            : route('giro.celulares.autogestion');

        return redirect($rutaDestino)->with('status', 'Elemento eliminado.');
    }
}
