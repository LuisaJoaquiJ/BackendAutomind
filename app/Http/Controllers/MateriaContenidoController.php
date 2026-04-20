<?php

namespace App\Http\Controllers;

use App\Models\Materia;
use App\Models\MateriaArchivo;
use App\Models\MateriaContenido;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class MateriaContenidoController extends Controller
{
    // ─────────────────────────────────────────────────────────────────────────
    // Vista estudiante
    // ─────────────────────────────────────────────────────────────────────────
    public function vistaEstudiante(Request $request, Materia $materia)
    {
        $nivel = $request->query('nivel', 'basico');

        $contenidos = $materia->contenidos()
            ->with('archivos')
            ->where('activo', true)
            ->where('nivel', $nivel)
            ->orderBy('orden')
            ->get()
            ->map(fn($c) => $this->formatearContenido($c));

        $archivos = $materia->archivos()
            ->whereDoesntHave('contenido')
            ->orderBy('created_at', 'desc')
            ->get();

        $actividades = $materia->actividades()
            ->where('activo', true)
            ->where('nivel', $nivel)
            ->orderBy('orden')
            ->get()
            ->map(function ($actividad) use ($request) {
                $ultimoIntento = $actividad->respuestas()
                    ->where('user_id', $request->user()->id)
                    ->latest()
                    ->first();

                return [
                    'id'             => $actividad->id,
                    'titulo'         => $actividad->titulo,
                    'tipo'           => $actividad->tipo,
                    'nivel'          => $actividad->nivel,
                    'descripcion'    => $actividad->descripcion,
                    'enunciado'      => $actividad->enunciado,
                    'pistas'         => $actividad->pistas ?? [],
                    'puntaje_maximo' => $actividad->puntaje_maximo,
                    'ultimo_intento' => $ultimoIntento,
                ];
            });

        return response()->json([
            'success' => true,
            'data'    => [
                'materia'                => $materia,
                'nivel_seleccionado'     => $nivel,
                'niveles_disponibles'    => ['basico', 'intermedio', 'avanzado'],
                'contenidos'             => $contenidos,
                'archivos_generales'     => $archivos,
                'actividades'            => $actividades,
                'aprendizaje_progresivo' => [
                    'nivel_actual'      => $nivel,
                    'siguiente_nivel'   => $this->obtenerSiguienteNivel($nivel),
                    'progreso_sugerido' => 'Completa el contenido y al menos una actividad antes de pasar al siguiente nivel.',
                ],
            ],
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Listado  GET /materias/{materia}/contenido          (docente)
    //          GET /materias/{materia}/contenidos-dinamicos (admin)
    // ─────────────────────────────────────────────────────────────────────────
    public function index(Materia $materia)
    {
        $contenidos = $materia->contenidos()
            ->with('archivos')
            ->orderBy('orden')
            ->get()
            ->map(fn($c) => $this->formatearContenido($c));

        return response()->json([
            'success' => true,
            'data'    => $contenidos,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Crear contenido  POST /materias/{materia}/contenido
    // Tipos soportados: pdf, slides, enlace, imagen, video, texto
    // ─────────────────────────────────────────────────────────────────────────
    public function store(Request $request, Materia $materia)
    {
        $this->autorizarGestion($request, $materia);

        Log::info('MateriaContenidoController@store request recibida', [
            'materia_id'      => $materia->id,
            'content_type'    => $request->header('Content-Type'),
            'input_keys'      => array_keys($request->except(['archivo', 'pdfs', 'imagenes'])),
            'file_keys'       => array_keys($request->allFiles()),
            'has_archivo'     => $request->hasFile('archivo'),
            'has_pdfs'        => $request->hasFile('pdfs'),
            'has_imagenes'    => $request->hasFile('imagenes'),
        ]);

        $data = $request->validate([
            'titulo'          => 'required|string|max:255',
            'tipo'            => ['required', Rule::in(['pdf', 'slides', 'enlace', 'imagen', 'video', 'texto'])],
            'nivel'           => ['nullable', Rule::in(['basico', 'intermedio', 'avanzado'])],
            'resumen'         => 'nullable|string|max:500',
            'contenido_texto' => 'nullable|string',
            'url'             => 'nullable|url|max:2048',
            'orden'           => 'nullable|integer|min:1',
            'archivo'         => 'nullable|file|max:20480',
            'pdfs.*'          => 'nullable|file|mimes:pdf|max:20480',
            'imagenes.*'      => 'nullable|image|max:10240',
        ]);

        $archivoPrincipal = $request->file('archivo');

        if (!$archivoPrincipal) {
            $todosLosArchivos = $request->allFiles();
            $archivoPrincipal = collect($todosLosArchivos)
                ->flatten()
                ->first(fn($archivo) => $archivo instanceof \Illuminate\Http\UploadedFile);
        }

        Log::info('MateriaContenidoController@store archivos detectados', [
            'tipo'                 => $data['tipo'] ?? null,
            'archivo_principal'    => $archivoPrincipal?->getClientOriginalName(),
            'archivo_principal_mime' => $archivoPrincipal?->getClientMimeType(),
            'pdfs_count'           => count($request->file('pdfs', [])),
            'imagenes_count'       => count($request->file('imagenes', [])),
        ]);

        if ($data['tipo'] === 'pdf' && !$archivoPrincipal && empty($request->file('pdfs', []))) {
            return response()->json([
                'success' => false,
                'message' => "Debes adjuntar un archivo PDF para el tipo 'pdf'.",
            ], 422);
        }

        if ($data['tipo'] === 'imagen' && !$archivoPrincipal && empty($request->file('imagenes', []))) {
            return response()->json([
                'success' => false,
                'message' => "Debes adjuntar una imagen para el tipo 'imagen'.",
            ], 422);
        }

        if ($archivoPrincipal) {
            if ($data['tipo'] === 'pdf' && strtolower($archivoPrincipal->getClientOriginalExtension()) !== 'pdf') {
                return response()->json([
                    'success' => false,
                    'message' => "El archivo adjunto debe ser un PDF para el tipo 'pdf'.",
                ], 422);
            }

            if ($data['tipo'] === 'imagen' && !str_starts_with((string) $archivoPrincipal->getClientMimeType(), 'image/')) {
                return response()->json([
                    'success' => false,
                    'message' => "El archivo adjunto debe ser una imagen para el tipo 'imagen'.",
                ], 422);
            }
        }

        // Tipos que exigen URL
        if (in_array($data['tipo'], ['slides', 'enlace', 'video']) && empty($data['url'])) {
            return response()->json([
                'success' => false,
                'message' => "El campo 'url' es obligatorio para el tipo '{$data['tipo']}'.",
            ], 422);
        }

        $contenido = MateriaContenido::create([
            'materia_id'      => $materia->id,
            'creado_por'      => $request->user()->id,
            'titulo'          => $data['titulo'],
            'tipo'            => $data['tipo'],
            'nivel'           => $data['nivel'] ?? 'basico',
            'resumen'         => $data['resumen'] ?? null,
            'contenido_texto' => $data['contenido_texto'] ?? null,
            'url'             => $data['url'] ?? null,
            'orden'           => $data['orden'] ?? (($materia->contenidos()->max('orden') ?? 0) + 1),
            'activo'          => true,
        ]);

        $archivos = collect();

        if ($archivoPrincipal) {
            $archivos->push(
                $this->guardarArchivo($materia, $contenido, $archivoPrincipal, $data['tipo'], $request->user()->id)
            );
        }

        foreach ($request->file('pdfs', []) as $archivo) {
            $archivos->push($this->guardarArchivo($materia, $contenido, $archivo, 'pdf', $request->user()->id));
        }

        foreach ($request->file('imagenes', []) as $archivo) {
            $archivos->push($this->guardarArchivo($materia, $contenido, $archivo, 'imagen', $request->user()->id));
        }

        return response()->json([
            'success'              => true,
            'message'              => 'Contenido agregado correctamente.',
            'data'                 => $this->formatearContenido($contenido->load('archivos')),
            'archivos_registrados' => $archivos,
        ], 201);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Generar con IA  POST /materias/{materia}/contenido/generar  (docente)
    // ─────────────────────────────────────────────────────────────────────────
    public function generar(Request $request, Materia $materia)
    {
        $this->autorizarGestion($request, $materia);

        $data = $request->validate([
            'tema' => 'required|string|max:500',
            'tipo' => ['required', Rule::in(['explicacion', 'resumen', 'ejercicios', 'evaluacion', 'guia', 'quiz'])],
        ]);

        try {
            $respuesta = Http::withHeaders([
                'x-api-key'         => config('services.anthropic.key'),
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ])->timeout(30)->post('https://api.anthropic.com/v1/messages', [
                'model'      => 'claude-haiku-4-5-20251001',
                'max_tokens' => 1500,
                'messages'   => [
                    ['role' => 'user', 'content' => $this->construirPrompt($data['tema'], $data['tipo'], $materia->nombre)],
                ],
            ]);

            if ($respuesta->failed()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al conectar con el servicio de IA.',
                ], 502);
            }

            $textoGenerado = $respuesta->json('content.0.text') ?? 'No se pudo generar el contenido.';

            $contenido = MateriaContenido::create([
                'materia_id'      => $materia->id,
                'creado_por'      => $request->user()->id,
                'titulo'          => ucfirst($data['tipo']) . ': ' . $data['tema'],
                'tipo'            => 'generado',
                'nivel'           => 'basico',
                'contenido_texto' => $textoGenerado,
                'orden'           => ($materia->contenidos()->max('orden') ?? 0) + 1,
                'activo'          => true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Contenido generado correctamente.',
                'data'    => $this->formatearContenido($contenido),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error interno al generar el contenido.',
            ], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Actualizar  PUT /materias/{materia}/contenidos-dinamicos/{contenido}
    // ─────────────────────────────────────────────────────────────────────────
    public function update(Request $request, Materia $materia, MateriaContenido $contenido)
    {
        $this->autorizarGestion($request, $materia);
        abort_if($contenido->materia_id !== $materia->id, 404);

        $data = $request->validate([
            'titulo'          => 'sometimes|string|max:255',
            'tipo'            => ['sometimes', Rule::in(['pdf', 'slides', 'enlace', 'imagen', 'video', 'generado', 'texto'])],
            'nivel'           => ['sometimes', Rule::in(['basico', 'intermedio', 'avanzado'])],
            'resumen'         => 'nullable|string|max:500',
            'contenido_texto' => 'nullable|string',
            'url'             => 'nullable|url|max:2048',
            'orden'           => 'sometimes|integer|min:1',
            'activo'          => 'sometimes|boolean',
        ]);

        $contenido->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Contenido actualizado correctamente.',
            'data'    => $this->formatearContenido($contenido->fresh()->load('archivos')),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Eliminar
    // DELETE /materias/{materia}/contenidos-dinamicos/{contenido}  (admin)
    // DELETE /docente/contenido/{contenido}                        (docente)
    //
    // Ambas rutas usan {contenido} → Laravel inyecta MateriaContenido.
    // Para la ruta docente $materia llega null (no está en la URL).
    // ─────────────────────────────────────────────────────────────────────────
    public function destroy(Request $request, MateriaContenido $contenido, Materia $materia = null)
    {
        // Si $materia no vino en la URL, la resolvemos desde el propio contenido
        $materia = $materia ?? Materia::findOrFail($contenido->materia_id);

        $this->autorizarGestion($request, $materia);
        abort_if($contenido->materia_id !== $materia->id, 404);

        foreach ($contenido->archivos as $archivo) {
            Storage::disk('public')->delete($archivo->ruta);
        }

        $contenido->delete();

        return response()->json([
            'success' => true,
            'message' => 'Contenido eliminado correctamente.',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers privados
    // ─────────────────────────────────────────────────────────────────────────

    private function guardarArchivo(
        Materia $materia,
        MateriaContenido $contenido,
        $archivo,
        string $tipo,
        int $userId
    ): MateriaArchivo {
        $ruta = $archivo->store("materias/{$materia->id}/{$tipo}s", 'public');

        return MateriaArchivo::create([
            'materia_id'           => $materia->id,
            'materia_contenido_id' => $contenido->id,
            'subido_por'           => $userId,
            'titulo'               => pathinfo($archivo->getClientOriginalName(), PATHINFO_FILENAME),
            'tipo'                 => $tipo,
            'ruta'                 => $ruta,
            'nombre_original'      => $archivo->getClientOriginalName(),
            'mime_type'            => $archivo->getClientMimeType(),
            'tamano'               => $archivo->getSize(),
            'descargable'          => $tipo === 'pdf',
        ]);
    }

    /**
     * Formato unificado: el campo BD 'contenido_texto' se expone como 'contenido'
     * para que coincida con DocenteContenidoItem en el app Android.
     */
    private function formatearContenido(MateriaContenido $c): array
    {
        $archivoPrincipal = $c->relationLoaded('archivos')
            ? $c->archivos->firstWhere('tipo', 'pdf')
                ?? $c->archivos->firstWhere('tipo', 'imagen')
                ?? $c->archivos->first()
            : null;

        $data = [
            'id'         => $c->id,
            'titulo'     => $c->titulo,
            'tipo'       => $c->tipo ?: $archivoPrincipal?->tipo,
            'nivel'      => $c->nivel,
            'resumen'    => $c->resumen,
            'contenido'  => $c->contenido_texto,
            'url'        => $c->url ?: ($archivoPrincipal ? Storage::disk('public')->url($archivoPrincipal->ruta) : null),
            'orden'      => $c->orden,
            'activo'     => $c->activo,
            'created_at' => $c->created_at,
        ];

        if ($c->relationLoaded('archivos')) {
            $data['archivos'] = $c->archivos->map(fn($a) => [
                'id'              => $a->id,
                'titulo'          => $a->titulo,
                'tipo'            => $a->tipo,
                'url'             => Storage::disk('public')->url($a->ruta),
                'nombre_original' => $a->nombre_original,
                'mime_type'       => $a->mime_type,
                'tamano'          => $a->tamano,
                'descargable'     => $a->descargable,
            ]);
        }

        return $data;
    }

    private function construirPrompt(string $tema, string $tipo, string $nombreMateria): string
    {
        $instrucciones = match ($tipo) {
            'explicacion' => 'Crea una explicación clara y detallada con ejemplos prácticos. Usa secciones con títulos.',
            'resumen'     => 'Crea un resumen conciso de los puntos más importantes en formato de lista estructurada.',
            'ejercicios'  => 'Crea 5 ejercicios prácticos de diferente dificultad. Incluye enunciado y una pista para cada uno.',
            'evaluacion'  => 'Crea una evaluación de 10 preguntas de opción múltiple (4 opciones). Indica la respuesta correcta al final.',
            'guia'        => 'Crea una guía de estudio paso a paso que cubra los conceptos esenciales.',
            'quiz'        => 'Crea un quiz de 5 preguntas (verdadero/falso y respuesta breve). Incluye las respuestas correctas.',
            default       => 'Desarrolla el tema de forma educativa y estructurada.',
        };

        return implode("\n\n", [
            "Eres un docente experto en la materia '{$nombreMateria}'.",
            $instrucciones,
            "Tema: {$tema}",
            'Responde directamente con el contenido educativo, sin introducciones. Usa lenguaje claro y apropiado para estudiantes universitarios.',
        ]);
    }

    private function autorizarGestion(Request $request, Materia $materia): void
    {
        $user = $request->user();
        $rol  = $user->rol ?? null;

        if ($materia->user_id !== $user->id && !in_array($rol, ['profesor', 'admin'], true)) {
            abort(403, 'No tienes permisos para gestionar el contenido de esta materia.');
        }
    }

    private function obtenerSiguienteNivel(string $nivel): ?string
    {
        return match ($nivel) {
            'basico'     => 'intermedio',
            'intermedio' => 'avanzado',
            default      => null,
        };
    }
}
