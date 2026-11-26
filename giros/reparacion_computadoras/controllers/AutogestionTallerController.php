<?php

namespace Giros\ReparacionComputadoras\Controllers;

use Giros\ReparacionComputadoras\Models\UsuarioTaller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

class AutogestionTallerController extends Controller
{
    public static function rutaConfigJson(int $empresaId): string
    {
        $dir = storage_path('app/giros/reparacion_computadoras');
        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        return "{$dir}/empresa_{$empresaId}_publica.json";
    }

    public static function catalogoDegradados(): array
    {
        return [
            'ninguno' => 'Sin degradado (limpio)',
            'oscuro_suave' => 'Degradado oscuro suave (ideal sobre imagen)',
            'diagonal_primario' => 'Diagonal con color primario y fondo',
            'horizontal_acento' => 'Horizontal sutil con color acento',
            'vertical_profundo' => 'Vertical profundo de arriba hacia abajo',
        ];
    }
    public static function catalogoFuentes(): array
    {
        return [
            'Inter, system-ui, -apple-system, sans-serif' => 'Moderna (Inter / Sistema)',
            'Arial, Helvetica, sans-serif' => 'Arial',
            '"Times New Roman", Times, serif' => 'Times New Roman',
            'Georgia, "Times New Roman", serif' => 'Georgia',
            'Verdana, Geneva, sans-serif' => 'Verdana',
            'Tahoma, Geneva, sans-serif' => 'Tahoma',
            '"Trebuchet MS", Helvetica, sans-serif' => 'Trebuchet MS',
            '"Courier New", Courier, monospace' => 'Courier New',
            'Impact, Haettenschweiler, "Arial Narrow Bold", sans-serif' => 'Impact',
        ];
    }
    public static function cargarConfiguracionPublica(int $empresaId): array
    {
        $defaults = [
            'hero_badge' => 'Servicio técnico especializado',
            'hero_titulo' => 'Diagnóstico, reparación y mantenimiento de laptops y PCs',
            'descripcion' => '',
            'telefono' => '',
            'whatsapp' => '',
            'direccion' => '',
            'horario' => 'Lun - Vie 9:00 a 18:00',
            'fuente_familia' => 'Inter, system-ui, -apple-system, sans-serif',
            'color_primario' => '#6366f1',
            'color_acento' => '#10b981',
            'icono_predeterminado' => '💻',
            'color_fondo' => '#09090b',
            'color_tarjeta' => '#111115',
            'fondo_imagen' => '',
            'fondo_degradado' => 'oscuro_suave',
            'fondo_estatico' => true,
            'transparencia_flotantes' => 25,
            'carrusel_intervalo_ms' => 4500,
            'imagenes' => [],
            'servicios' => [
                [
                    'titulo' => 'Diagnóstico y hardware',
                    'descripcion' => 'Revisión de fuente, tarjeta madre, pantallas, teclados, baterías y ventilación.',
                ],
                [
                    'titulo' => 'Almacenamiento y velocidad',
                    'descripcion' => 'Migración a SSD/NVMe, ampliación de memoria RAM y limpieza térmica preventiva.',
                ],
                [
                    'titulo' => 'Sistema y respaldo',
                    'descripcion' => 'Instalación limpia de sistema operativo, controladores, antivirus y respaldo de datos.',
                ],
            ],
            'promociones' => [],
        ];

        $path = static::rutaConfigJson($empresaId);
        if (! file_exists($path)) {
            return $defaults;
        }

        $decoded = json_decode((string) file_get_contents($path), true);
        if (! is_array($decoded)) {
            return $defaults;
        }

        return array_replace($defaults, $decoded, [
            'imagenes' => is_array($decoded['imagenes'] ?? null) ? $decoded['imagenes'] : $defaults['imagenes'],
            'servicios' => is_array($decoded['servicios'] ?? null) ? $decoded['servicios'] : $defaults['servicios'],
            'promociones' => is_array($decoded['promociones'] ?? null) ? $decoded['promociones'] : $defaults['promociones'],
        ]);
    }

    public static function guardarConfiguracionPublica(int $empresaId, array $data): void
    {
        $path = static::rutaConfigJson($empresaId);
        file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL);
    }

    public static function cargarConfiguracionPorAmbito(int $empresaId, string $ambito = 'publica'): array
    {
        $ambito = in_array($ambito, ['publica', 'internas'], true) ? $ambito : 'publica';
        $baseConfig = static::cargarConfiguracionPublica($empresaId);

        if ($ambito === 'publica') {
            return $baseConfig;
        }

        $internasDefaults = [
            'titulo_panel' => 'Panel de Control — Reparación de Computadoras',
            'subtitulo_panel' => 'Gestión operativa aislada del taller',
            'fuente_familia' => $baseConfig['fuente_familia'] ?? 'Inter, system-ui, -apple-system, sans-serif',
            'icono_predeterminado' => $baseConfig['icono_predeterminado'] ?? '💻',
            'color_primario' => $baseConfig['color_primario'] ?? '#6366f1',
            'color_acento' => $baseConfig['color_acento'] ?? '#10b981',
            'color_fondo' => $baseConfig['color_fondo'] ?? '#09090b',
            'color_tarjeta' => $baseConfig['color_tarjeta'] ?? '#111115',
            'mostrar_metricas' => true,
            'mostrar_ultimas_ordenes' => true,
        ];

        $guardadas = $baseConfig['internas'] ?? [];
        if (! is_array($guardadas)) {
            $guardadas = [];
        }

        return array_replace($internasDefaults, $guardadas);
    }

    public function edit(Request $request)
    {
        View::addNamespace('taller_computadoras', base_path('giros/reparacion_computadoras/views'));

        /** @var UsuarioTaller $usuario */
        $usuario = $request->attributes->get('usuario_taller');

        $empresa = DB::connection('mysql')
            ->table('empresas')
            ->where('id', $usuario->empresa_id)
            ->first();

        $template = $empresa
            ? DB::connection('mysql')
                ->table('template_configuraciones')
                ->where('empresa_id', $empresa->id)
                ->where('activo', true)
                ->orderByDesc('id')
                ->first()
            : null;

        $ambito = (string) $request->query('ambito', $request->input('ambito', 'publica'));
        $ambito = in_array($ambito, ['publica', 'internas'], true) ? $ambito : 'publica';
        $tabActivo = (string) $request->query('tab', 'general');
        $tabActivo = in_array($tabActivo, ['general', 'carrusel', 'servicios', 'promociones'], true) ? $tabActivo : 'general';

        $configPublica = static::cargarConfiguracionPublica((int) $usuario->empresa_id);
        $configInternas = static::cargarConfiguracionPorAmbito((int) $usuario->empresa_id, 'internas');
        $config = $ambito === 'internas' ? $configInternas : $configPublica;

        return view('taller_computadoras::autogestion', [
            'usuario' => $usuario,
            'empresa' => $empresa,
            'template' => $template,
            'ambito' => $ambito,
            'tabActivo' => $tabActivo,
            'config' => $config,
            'configPublica' => $configPublica,
            'configInternas' => $configInternas,
            'fuentes' => static::catalogoFuentes(),
            'degradados' => static::catalogoDegradados(),
        ]);
    }

    public function updateGeneral(Request $request): RedirectResponse
    {
        $ambito = (string) $request->input('ambito', 'publica');
        $ambito = in_array($ambito, ['publica', 'internas'], true) ? $ambito : 'publica';

        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:150'],
            'descripcion' => ['nullable', 'string', 'max:600'],
            'hero_badge' => ['nullable', 'string', 'max:100'],
            'hero_titulo' => ['nullable', 'string', 'max:180'],
            'titulo_panel' => ['nullable', 'string', 'max:160'],
            'subtitulo_panel' => ['nullable', 'string', 'max:220'],
            'mostrar_metricas' => ['nullable', 'boolean'],
            'mostrar_ultimas_ordenes' => ['nullable', 'boolean'],
            'telefono' => ['nullable', 'string', 'max:40'],
            'whatsapp' => ['nullable', 'string', 'max:40'],
            'direccion' => ['nullable', 'string', 'max:250'],
            'horario' => ['nullable', 'string', 'max:120'],
            'icono_predeterminado' => ['nullable', 'string', 'max:16'],
            'fuente_familia' => ['nullable', 'string', 'max:160'],
            'fondo_imagen_archivo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:6144'],
            'fondo_degradado' => ['nullable', 'string', 'max:60'],
            'fondo_estatico' => ['nullable', 'boolean'],
            'quitar_fondo_imagen' => ['nullable', 'boolean'],
            'transparencia_flotantes' => ['nullable', 'integer', 'min:0', 'max:85'],
            'color_primario' => ['required', 'string', 'max:30'],
            'color_acento' => ['required', 'string', 'max:30'],
            'color_fondo' => ['required', 'string', 'max:30'],
            'color_tarjeta' => ['required', 'string', 'max:30'],
        ]);

        /** @var UsuarioTaller $usuario */
        $usuario = $request->attributes->get('usuario_taller');
        $empresaId = (int) $usuario->empresa_id;

        DB::connection('mysql')
            ->table('empresas')
            ->where('id', $empresaId)
            ->update([
                'nombre' => $datos['nombre'],
                'updated_at' => now(),
            ]);

        $config = static::cargarConfiguracionPublica($empresaId);
        $fuentesValidas = array_keys(static::catalogoFuentes());
        $fuenteElegida = $datos['fuente_familia'] ?? 'Inter, system-ui, -apple-system, sans-serif';
        if (! in_array($fuenteElegida, $fuentesValidas, true)) {
            $fuenteElegida = 'Inter, system-ui, -apple-system, sans-serif';
        }

        if ($ambito === 'internas') {
            $config['internas'] = [
                'titulo_panel' => $datos['titulo_panel'] ?: 'Panel de Control — Reparación de Computadoras',
                'subtitulo_panel' => $datos['subtitulo_panel'] ?? '',
                'fuente_familia' => $fuenteElegida,
                'icono_predeterminado' => $datos['icono_predeterminado'] ?: '💻',
                'color_primario' => $datos['color_primario'],
                'color_acento' => $datos['color_acento'],
                'color_fondo' => $datos['color_fondo'],
                'color_tarjeta' => $datos['color_tarjeta'],
                'mostrar_metricas' => $request->boolean('mostrar_metricas'),
                'mostrar_ultimas_ordenes' => $request->boolean('mostrar_ultimas_ordenes'),
            ];
        } else {
            $config['color_primario'] = $datos['color_primario'];
            $config['color_acento'] = $datos['color_acento'];
            $config['icono_predeterminado'] = $datos['icono_predeterminado'] ?: '💻';
            $config['descripcion'] = $datos['descripcion'] ?? '';
            $config['hero_badge'] = $datos['hero_badge'] ?: 'Servicio técnico especializado';
            $config['hero_titulo'] = $datos['hero_titulo'] ?: 'Diagnóstico, reparación y mantenimiento de laptops y PCs';
            $config['telefono'] = $datos['telefono'] ?? '';
            $config['whatsapp'] = $datos['whatsapp'] ?? '';
            $config['direccion'] = $datos['direccion'] ?? '';
            $config['horario'] = $datos['horario'] ?? '';
            $config['fuente_familia'] = $fuenteElegida;
            $config['color_fondo'] = $datos['color_fondo'];
            $config['color_tarjeta'] = $datos['color_tarjeta'];
        if ($request->boolean('quitar_fondo_imagen')) {
            $actualImg = ltrim((string) ($config['fondo_imagen'] ?? ''), '/');
            if ($actualImg !== '' && str_starts_with($actualImg, 'uploads/giros/reparacion_computadoras/')) {
                $full = public_path($actualImg);
                if (file_exists($full)) {
                    @unlink($full);
                }
            }
            $config['fondo_imagen'] = '';
        } elseif ($request->hasFile('fondo_imagen_archivo')) {
            $actualImg = ltrim((string) ($config['fondo_imagen'] ?? ''), '/');
            if ($actualImg !== '' && str_starts_with($actualImg, 'uploads/giros/reparacion_computadoras/')) {
                $full = public_path($actualImg);
                if (file_exists($full)) {
                    @unlink($full);
                }
            }

            $relDir = "uploads/giros/reparacion_computadoras/empresa_{$empresaId}";
            $absDir = public_path($relDir);
            if (! is_dir($absDir)) {
                mkdir($absDir, 0777, true);
            }

            $archivo = $request->file('fondo_imagen_archivo');
            $ext = strtolower($archivo->getClientOriginalExtension() ?: 'jpg');
            $nombreFondo = 'fondo_' . time() . '_' . Str::random(6) . '.' . $ext;
            $archivo->move($absDir, $nombreFondo);
            $config['fondo_imagen'] = '/' . trim($relDir, '/') . '/' . $nombreFondo;
        }

        $degradadosValidos = array_keys(static::catalogoDegradados());
        $degElegido = $datos['fondo_degradado'] ?? 'oscuro_suave';
        $config['fondo_degradado'] = in_array($degElegido, $degradadosValidos, true) ? $degElegido : 'oscuro_suave';
        $config['fondo_estatico'] = $request->boolean('fondo_estatico');
        $config['transparencia_flotantes'] = max(0, min(85, (int) ($datos['transparencia_flotantes'] ?? 25)));
        }

        static::guardarConfiguracionPublica($empresaId, $config);

        return redirect()
            ->route('taller.computadoras.autogestion', ['ambito' => $ambito, 'tab' => $request->input('tab', 'general')])
            ->with('status', $ambito === 'internas'
                ? 'Configuración de las páginas internas guardada.'
                : 'Configuración de la página pública guardada.');
    }

    public function actualizarCarruselConfig(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'carrusel_segundos' => ['required', 'numeric', 'min:1.5', 'max:30'],
        ]);

        /** @var UsuarioTaller $usuario */
        $usuario = $request->attributes->get('usuario_taller');
        $empresaId = (int) $usuario->empresa_id;

        $config = static::cargarConfiguracionPublica($empresaId);
        $seg = max(1.5, min(30, (float) $datos['carrusel_segundos']));
        $config['carrusel_intervalo_ms'] = (int) round($seg * 1000);
        static::guardarConfiguracionPublica($empresaId, $config);

        return redirect()
            ->route('taller.computadoras.autogestion', ['ambito' => 'publica', 'tab' => 'carrusel'])
            ->with('status', 'Velocidad del carrusel actualizada.');
    }

    public function agregarImagen(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'imagen' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'titulo' => ['nullable', 'string', 'max:120'],
            'subtitulo' => ['nullable', 'string', 'max:200'],
        ]);

        /** @var UsuarioTaller $usuario */
        $usuario = $request->attributes->get('usuario_taller');
        $empresaId = (int) $usuario->empresa_id;

        $relDir = "uploads/giros/reparacion_computadoras/empresa_{$empresaId}";
        $absDir = public_path($relDir);
        if (! is_dir($absDir)) {
            mkdir($absDir, 0777, true);
        }

        $ext = strtolower($datos['imagen']->getClientOriginalExtension() ?: 'jpg');
        $filename = 'slide_' . time() . '_' . Str::random(6) . '.' . $ext;
        $datos['imagen']->move($absDir, $filename);

        $config = static::cargarConfiguracionPublica($empresaId);
        $config['imagenes'][] = [
            'id' => Str::uuid()->toString(),
            'url' => '/' . trim($relDir, '/') . '/' . $filename,
            'titulo' => $datos['titulo'] ?? '',
            'subtitulo' => $datos['subtitulo'] ?? '',
        ];

        static::guardarConfiguracionPublica($empresaId, $config);

        return redirect()
            ->route('taller.computadoras.autogestion', ['ambito' => 'publica', 'tab' => 'carrusel'])
            ->with('status', 'Imagen agregada al carrusel.');
    }

    public function eliminarImagen(Request $request, string $imagenId): RedirectResponse
    {
        /** @var UsuarioTaller $usuario */
        $usuario = $request->attributes->get('usuario_taller');
        $empresaId = (int) $usuario->empresa_id;

        $config = static::cargarConfiguracionPublica($empresaId);
        $restantes = [];
        foreach ($config['imagenes'] as $img) {
            if (($img['id'] ?? '') === $imagenId) {
                $url = ltrim((string) ($img['url'] ?? ''), '/');
                if ($url !== '' && str_starts_with($url, 'uploads/giros/reparacion_computadoras/')) {
                    $full = public_path($url);
                    if (file_exists($full)) {
                        @unlink($full);
                    }
                }
                continue;
            }
            $restantes[] = $img;
        }

        $config['imagenes'] = array_values($restantes);
        static::guardarConfiguracionPublica($empresaId, $config);

        return redirect()
            ->route('taller.computadoras.autogestion', ['ambito' => 'publica', 'tab' => 'carrusel'])
            ->with('status', 'Imagen eliminada del carrusel.');
    }

    public function agregarServicio(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'titulo' => ['required', 'string', 'max:120'],
            'descripcion' => ['required', 'string', 'max:300'],
        ]);

        /** @var UsuarioTaller $usuario */
        $usuario = $request->attributes->get('usuario_taller');
        $empresaId = (int) $usuario->empresa_id;

        $config = static::cargarConfiguracionPublica($empresaId);
        $config['servicios'][] = [
            'id' => Str::uuid()->toString(),
            'titulo' => $datos['titulo'],
            'descripcion' => $datos['descripcion'],
        ];
        static::guardarConfiguracionPublica($empresaId, $config);

        return redirect()
            ->route('taller.computadoras.autogestion', ['ambito' => 'publica', 'tab' => 'servicios'])
            ->with('status', 'Servicio agregado.');
    }

    public function eliminarServicio(Request $request, int $indice): RedirectResponse
    {
        /** @var UsuarioTaller $usuario */
        $usuario = $request->attributes->get('usuario_taller');
        $empresaId = (int) $usuario->empresa_id;

        $config = static::cargarConfiguracionPublica($empresaId);
        if (isset($config['servicios'][$indice])) {
            unset($config['servicios'][$indice]);
            $config['servicios'] = array_values($config['servicios']);
            static::guardarConfiguracionPublica($empresaId, $config);
        }

        return redirect()
            ->route('taller.computadoras.autogestion', ['ambito' => 'publica', 'tab' => 'servicios'])
            ->with('status', 'Servicio eliminado.');
    }

    public function agregarPromocion(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'titulo' => ['required', 'string', 'max:120'],
            'descripcion' => ['required', 'string', 'max:300'],
            'etiqueta' => ['nullable', 'string', 'max:50'],
        ]);

        /** @var UsuarioTaller $usuario */
        $usuario = $request->attributes->get('usuario_taller');
        $empresaId = (int) $usuario->empresa_id;

        $config = static::cargarConfiguracionPublica($empresaId);
        $config['promociones'][] = [
            'id' => Str::uuid()->toString(),
            'titulo' => $datos['titulo'],
            'descripcion' => $datos['descripcion'],
            'etiqueta' => $datos['etiqueta'] ?? 'PROMO',
        ];
        static::guardarConfiguracionPublica($empresaId, $config);

        return redirect()
            ->route('taller.computadoras.autogestion', ['ambito' => 'publica', 'tab' => 'promociones'])
            ->with('status', 'Promoción agregada.');
    }

    public function eliminarPromocion(Request $request, int $indice): RedirectResponse
    {
        /** @var UsuarioTaller $usuario */
        $usuario = $request->attributes->get('usuario_taller');
        $empresaId = (int) $usuario->empresa_id;

        $config = static::cargarConfiguracionPublica($empresaId);
        if (isset($config['promociones'][$indice])) {
            unset($config['promociones'][$indice]);
            $config['promociones'] = array_values($config['promociones']);
            static::guardarConfiguracionPublica($empresaId, $config);
        }

        return redirect()
            ->route('taller.computadoras.autogestion', ['ambito' => 'publica', 'tab' => 'promociones'])
            ->with('status', 'Promoción eliminada.');
    }
}
