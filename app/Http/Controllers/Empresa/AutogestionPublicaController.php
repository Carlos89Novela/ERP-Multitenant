<?php

namespace App\Http\Controllers\Empresa;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\TemplateConfiguracion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AutogestionPublicaController extends Controller
{
    public function guardarApariencia(Request $request): RedirectResponse
    {
        $user = $request->user();
        $empresa = Empresa::query()->findOrFail((int) $user->empresa_id);

        $data = $request->validate([
            'nombre_template' => ['nullable', 'string', 'max:120'],
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
            $template->nombre = $data['nombre_template'] ?: ('Tema ' . $empresa->nombre);
        } elseif (! empty($data['nombre_template'])) {
            $template->nombre = trim($data['nombre_template']);
        }

        if ($request->hasFile('logo_archivo')) {
            if (! empty($template->logo_path) && Storage::disk('public')->exists($template->logo_path)) {
                Storage::disk('public')->delete($template->logo_path);
            }
            $template->logo_path = $request->file('logo_archivo')->store("empresas/{$empresa->id}/marca", 'public');
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
        $template->icono_predeterminado = $data['icono_predeterminado'] ? trim($data['icono_predeterminado']) : '★';
        $template->activo = true;
        $template->save();

        return redirect()->route('empresa.portal')->with('status', 'Apariencia e identidad visual de la empresa actualizadas.');
    }

    public function storeSlide(Request $request): RedirectResponse
    {
        $user = $request->user();
        $empresa = Empresa::query()->findOrFail((int) $user->empresa_id);

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
            $imagenPath = $request->file('imagen_archivo')->store("empresas/{$empresa->id}/carrusel", 'public');
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

        return redirect()->route('empresa.portal')->with('status', 'Elemento agregado al carrusel / escaparate público.');
    }

    public function toggleSlide(Request $request, int $slideId): RedirectResponse
    {
        $user = $request->user();
        $empresa = Empresa::query()->findOrFail((int) $user->empresa_id);

        $item = DB::table('empresa_carrusel_slides')
            ->where('id', $slideId)
            ->where('empresa_id', $empresa->id)
            ->first();

        if (! $item) {
            abort(404);
        }

        DB::table('empresa_carrusel_slides')
            ->where('id', $slideId)
            ->update([
                'activo' => ! (bool) $item->activo,
                'updated_at' => now(),
            ]);

        return redirect()->route('empresa.portal')->with('status', 'Visibilidad del elemento actualizada.');
    }

    public function destroySlide(Request $request, int $slideId): RedirectResponse
    {
        $user = $request->user();
        $empresa = Empresa::query()->findOrFail((int) $user->empresa_id);

        $item = DB::table('empresa_carrusel_slides')
            ->where('id', $slideId)
            ->where('empresa_id', $empresa->id)
            ->first();

        if (! $item) {
            abort(404);
        }

        if (! empty($item->imagen_path) && Storage::disk('public')->exists($item->imagen_path)) {
            Storage::disk('public')->delete($item->imagen_path);
        }

        DB::table('empresa_carrusel_slides')
            ->where('id', $slideId)
            ->where('empresa_id', $empresa->id)
            ->delete();

        return redirect()->route('empresa.portal')->with('status', 'Elemento eliminado de la página pública.');
    }

    public function verPublica(string $slug): View
    {
        $empresa = Empresa::query()
            ->where('slug', $slug)
            ->where('activa', true)
            ->firstOrFail();

        if ($empresa->evaluarEstadoLicenciaEfectivo() === 'suspendida_morosidad') {
            abort(404, 'Página pública no disponible temporalmente.');
        }

        $template = TemplateConfiguracion::query()
            ->where('empresa_id', $empresa->id)
            ->where('activo', true)
            ->orderByDesc('id')
            ->first()
            ?? new TemplateConfiguracion(TemplateConfiguracion::defaultsPlataforma());

        $slides = DB::table('empresa_carrusel_slides')
            ->where('empresa_id', $empresa->id)
            ->where('tipo', 'slide')
            ->where('activo', true)
            ->orderBy('orden')
            ->orderBy('id')
            ->get();

        $destacados = DB::table('empresa_carrusel_slides')
            ->where('empresa_id', $empresa->id)
            ->where('tipo', 'destacado')
            ->where('activo', true)
            ->orderBy('orden')
            ->orderBy('id')
            ->get();

        return view('empresa.publica', [
            'empresa' => $empresa,
            'template' => $template,
            'slides' => $slides,
            'destacados' => $destacados,
            'slugSeguro' => Str::slug($empresa->slug ?: $empresa->nombre),
        ]);
    }
}
