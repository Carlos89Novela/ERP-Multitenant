<?php

namespace App\Http\Controllers\Empresa;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\TemplateConfiguracion;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmpresaPortalController extends Controller
{
    public function portal(Request $request): View
    {
        $user = $request->user();
        $empresa = Empresa::query()
            ->with('sucursales')
            ->findOrFail($user->empresa_id);

        if (! $empresa->activa) {
            abort(403, 'Esta empresa se encuentra suspendida.');
        }

        $estadoLicenciaEfectivo = $empresa->evaluarEstadoLicenciaEfectivo();
        if ($estadoLicenciaEfectivo === 'suspendida_morosidad') {
            abort(403, 'La licencia de la empresa superó el periodo de tolerancia tras el vencimiento. Contacta a CanoDevOps para renovar.');
        }

        $template = TemplateConfiguracion::query()
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

        return view('empresa.portal', [
            'user' => $user,
            'empresa' => $empresa,
            'estadoLicenciaEfectivo' => $estadoLicenciaEfectivo,
            'template' => $template,
            'girosDisponibles' => Empresa::GIROS_DISPONIBLES,
            'paginasCatalogo' => Empresa::PAGINAS_CATALOGO,
        ]);
    }
}
