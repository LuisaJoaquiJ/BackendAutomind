<?php

namespace App\Services;

use App\Models\Materia;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

class MateriaIAService
{
    /**
     * Extrae texto de un archivo PDF
     */
    public function extractPdfText(UploadedFile $file): string
    {
        try {
            // Intenta usar smalot/pdf-parser si está disponible
            if (class_exists('Smalot\PdfParser\Parser')) {
                $parser = new \Smalot\PdfParser\Parser();
                $pdf = $parser->parseFile($file->getRealPath());
                return trim(preg_replace('/\s+/u', ' ', $pdf->getText()) ?? '');
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('PDF parser failed: ' . $e->getMessage());
        }

        // Fallback: retorna solo el nombre del archivo
        return 'PDF: ' . $file->getClientOriginalName();
    }

    /**
     * Construye un pack de aprendizaje para una materia usando IA
     */
    public function buildLearningPack(Materia $materia, ?string $pdfText, string $nivel): array
    {
        $nivel = $this->normalizeNivel($nivel);
        $textoBase = $pdfText ? mb_substr($pdfText, 0, 6000) : '';
        $prompt = $this->buildPrompt($materia, $nivel, $textoBase);

        $respuesta = $this->requestOllama($prompt);
        if (is_array($respuesta)) {
            return $this->normalizePack($respuesta, $materia, $nivel);
        }

        return $this->fallbackPack($materia, $nivel, $textoBase);
    }

    /**
     * Genera contenido (ejercicios, examen, explicacion, etc.) a partir de un tema/prompt
     */
    public function generateFromPrompt(Materia $materia, string $tema, string $tipo, int $n = 5, string $nivel = 'basico'): ?array
    {
        $nivel = $this->normalizeNivel($nivel);

        $prompt = $this->buildGenerationPrompt($materia, $tema, $tipo, $n, $nivel);

        $respuesta = $this->requestOllama($prompt);
        if (is_array($respuesta)) {
            return $this->normalizeGeneratedContent($respuesta, $materia, $tema, $tipo, $n, $nivel);
        }

        // Fallback estructurado cuando no hay respuesta del modelo
        $fallback = [
            'fallback' => true,
            'tipo' => $tipo,
            'titulo' => ucfirst($tipo) . ': ' . $tema,
            'nivel' => $nivel,
            'contenido' => [],
        ];

        if ($tipo === 'ejercicios' || $tipo === 'examen') {
            $items = [];
            for ($i = 1; $i <= max(1, min(10, $n)); $i++) {
                $items[] = [
                    'id' => $i,
                    'titulo' => "Ejercicio {$i}",
                    'enunciado' => "(Fallback) Escribe un enunciado sobre {$tema}.",
                    'pista' => 'Empieza por identificar el caso base y el paso recursivo.',
                ];
            }
            $fallback['contenido'] = ['ejercicios' => $items];
        } else {
            $fallback['contenido'] = [
                'texto' => "(Fallback) No se pudo generar contenido automáticamente para '{$tema}'. Intenta refinar el tema o inténtalo más tarde.",
            ];
        }

        return $fallback;
    }

        /**
         * Construye un prompt estricto por tipo de contenido para obligar a IA a generar material útil.
         */
        private function buildGenerationPrompt(Materia $materia, string $tema, string $tipo, int $n, string $nivel): string
        {
                $tipo = strtolower(trim($tipo));

                $estructura = match ($tipo) {
                        'explicacion' => <<<TXT
{
    "tipo": "explicacion",
    "tema": "{$tema}",
    "nivel": "{$nivel}",
    "titulo": "Explicación de {$tema}",
    "contenido_json": {
        "introduccion": "...",
        "explicacion": [
            {"titulo": "...", "contenido": "..."}
        ],
        "ejemplos": [
            {"titulo": "...", "contenido": "..."}
        ],
        "ideas_clave": ["..."],
        "actividad_cierre": {
            "titulo": "...",
            "instruccion": "..."
        }
    }
}
TXT,
                        'resumen' => <<<TXT
{
    "tipo": "resumen",
    "tema": "{$tema}",
    "nivel": "{$nivel}",
    "titulo": "Resumen de {$tema}",
    "contenido_json": {
        "resumen": "...",
        "ideas_clave": ["..."],
        "palabras_clave": ["..."],
        "pregunta_repaso": "..."
    }
}
TXT,
                        'ejercicios' => <<<TXT
{
    "tipo": "ejercicios",
    "tema": "{$tema}",
    "nivel": "{$nivel}",
    "titulo": "Ejercicios de {$tema}",
    "contenido_json": {
        "ejercicios": [
            {
                "titulo": "...",
                "enunciado": "...",
                "pista": "...",
                "dificultad": "facil|medio|dificil"
            }
        ],
        "respuesta_docente": "..."
    }
}
TXT,
                        'evaluacion', 'examen' => <<<TXT
{
    "tipo": "evaluacion",
    "tema": "{$tema}",
    "nivel": "{$nivel}",
    "titulo": "Evaluación de {$tema}",
    "contenido_json": {
        "preguntas": [
            {
                "pregunta": "...",
                "opciones": ["A...", "B...", "C...", "D..."],
                "respuesta_correcta": "A",
                "justificacion": "..."
            }
        ],
        "indicaciones": "..."
    }
}
TXT,
                        'guia' => <<<TXT
{
    "tipo": "guia",
    "tema": "{$tema}",
    "nivel": "{$nivel}",
    "titulo": "Guía de {$tema}",
    "contenido_json": {
        "pasos": [
            {"titulo": "...", "descripcion": "..."}
        ],
        "recomendaciones": ["..."],
        "mini_practica": {
            "titulo": "...",
            "instruccion": "..."
        }
    }
}
TXT,
                        'quiz' => <<<TXT
{
    "tipo": "quiz",
    "tema": "{$tema}",
    "nivel": "{$nivel}",
    "titulo": "Quiz de {$tema}",
    "contenido_json": {
        "preguntas": [
            {
                "tipo": "vf|respuesta_corta|opcion_multiple",
                "pregunta": "...",
                "opciones": ["...", "...", "...", "..."],
                "respuesta_correcta": "...",
                "pista": "..."
            }
        ]
    }
}
TXT,
                        default => <<<TXT
{
    "tipo": "{$tipo}",
    "tema": "{$tema}",
    "nivel": "{$nivel}",
    "titulo": "Contenido de {$tema}",
    "contenido_json": {
        "explicacion": "...",
        "actividades": [
            {"titulo": "...", "descripcion": "...", "tipo": "practica|lectura|quiz"}
        ],
        "ejercicios": [
            {"titulo": "...", "enunciado": "...", "pista": "..."}
        ],
        "resumen": "..."
    }
}
TXT,
                };

                return <<<PROMPT
Eres un docente experto en la materia '{$materia->nombre}'.
Tema: {$tema}
Tipo de contenido: {$tipo}
Nivel: {$nivel}

Reglas obligatorias:
- No escribas introducciones vacías ni texto genérico.
- No repitas el tema con una sola frase.
- Genera contenido real, útil y específico para estudiantes.
- Si el tipo es ejercicios, crea ejercicios distintos entre sí.
- Si el tipo es resumen, incluye ideas clave, no solo un párrafo.
- Si el tipo es evaluación o examen, incluye respuestas correctas y justificación.
- Si el tipo es guía, organiza pasos accionables.
- Si el tipo es quiz, mezcla preguntas de distinto formato.
- Devuelve SOLO JSON válido, sin markdown, sin comillas triples y sin texto adicional.

Cantidad sugerida: {$n} elementos cuando aplique.

Devuelve exactamente esta estructura y respeta los campos de contenido:
{$estructura}

Si no puedes completar todos los campos, igual devuelve el JSON con la mejor respuesta posible.
PROMPT;
        }

        /**
         * Normaliza la respuesta generada para que el frontend reciba una estructura consistente.
         */
        private function normalizeGeneratedContent(array $content, Materia $materia, string $tema, string $tipo, int $n, string $nivel): array
        {
                $tipo = strtolower(trim($tipo));

                $content['tipo'] = $content['tipo'] ?? $tipo;
                $content['tema'] = $content['tema'] ?? $tema;
                $content['nivel'] = $content['nivel'] ?? $nivel;
                $content['titulo'] = $content['titulo'] ?? ucfirst($tipo) . ': ' . $tema;

                if (!array_key_exists('contenido_json', $content)) {
                        $content['contenido_json'] = $this->wrapGeneratedContentByType($content, $tipo, $tema, $n);
                }

                return $content;
        }

        /**
         * Garantiza que el contenido tenga un bloque útil por tipo aun si la IA devuelve una estructura parcial.
         */
        private function wrapGeneratedContentByType(array $content, string $tipo, string $tema, int $n): array
        {
                return match ($tipo) {
                        'explicacion' => [
                                'introduccion' => $content['introduccion'] ?? ($content['resumen'] ?? 'Introducción sobre ' . $tema . '.'),
                                'explicacion' => $content['explicacion'] ?? $content['contenido'] ?? [],
                                'ejemplos' => $content['ejemplos'] ?? [],
                                'ideas_clave' => $content['ideas_clave'] ?? [],
                                'actividad_cierre' => $content['actividad_cierre'] ?? null,
                        ],
                        'resumen' => [
                                'resumen' => $content['resumen'] ?? ($content['contenido'] ?? 'Resumen sobre ' . $tema . '.'),
                                'ideas_clave' => $content['ideas_clave'] ?? [],
                                'palabras_clave' => $content['palabras_clave'] ?? [],
                                'pregunta_repaso' => $content['pregunta_repaso'] ?? null,
                        ],
                        'ejercicios' => [
                                'ejercicios' => $content['ejercicios'] ?? $content['contenido'] ?? [],
                                'respuesta_docente' => $content['respuesta_docente'] ?? null,
                                'cantidad_objetivo' => $n,
                        ],
                        'evaluacion', 'examen' => [
                                'preguntas' => $content['preguntas'] ?? $content['contenido'] ?? [],
                                'indicaciones' => $content['indicaciones'] ?? null,
                        ],
                        'guia' => [
                                'pasos' => $content['pasos'] ?? $content['contenido'] ?? [],
                                'recomendaciones' => $content['recomendaciones'] ?? [],
                                'mini_practica' => $content['mini_practica'] ?? null,
                        ],
                        'quiz' => [
                                'preguntas' => $content['preguntas'] ?? $content['contenido'] ?? [],
                        ],
                        default => [
                                'explicacion' => $content['explicacion'] ?? $content['contenido'] ?? '',
                                'actividades' => $content['actividades'] ?? [],
                                'ejercicios' => $content['ejercicios'] ?? [],
                                'resumen' => $content['resumen'] ?? null,
                        ],
                };
        }

    /**
     * Construye un resumen de PDF
     */
    public function buildPdfSummary(Materia $materia, ?string $pdfText, string $nivel): array
    {
        return $this->buildLearningPack($materia, $pdfText, $nivel);
    }

    /**
     * Procesa un mensaje de chat con contexto de la materia
     */
    public function buildChatResponse(Materia $materia, ?string $pdfText, string $message, string $nivel = 'basico', array $history = [], string $persona = 'profesor_interactivo'): array
    {
        $nivel = $this->normalizeNivel($nivel);
        $textoBase = $pdfText ? mb_substr($pdfText, 0, 6000) : '';
        $historial = $this->normalizeChatHistory($history);
        $prompt = $this->buildChatPrompt($materia, $nivel, $message, $textoBase, $historial, $persona);

        $respuesta = $this->requestOllama($prompt);
        if (is_array($respuesta)) {
            return [
                'respuesta' => $respuesta['respuesta'] ?? 'No pude generar una respuesta en este momento.',
                'sugerencias' => $respuesta['sugerencias'] ?? [],
                'pregunta_siguiente' => $respuesta['pregunta_siguiente'] ?? null,
                'nivel' => $nivel,
            ];
        }

        return [
            'respuesta' => 'Aún no pude conectar con el modelo de IA. Mientras tanto, puedo ayudarte con ' . $materia->nombre . ' si me haces una pregunta más específica.',
            'sugerencias' => [
                'Explícame la idea principal',
                'Dame un ejercicio corto',
                'Resume el tema con palabras sencillas',
            ],
            'pregunta_siguiente' => '¿Qué parte de la materia quieres repasar?',
            'nivel' => $nivel,
        ];
    }

    /**
     * Construye el prompt para generar contenido de aprendizaje
     */
    private function buildPrompt(Materia $materia, string $nivel, string $textoBase): string
    {
        return <<<PROMPT
Eres un asistente educativo para una app escolar.
Genera SOLO JSON válido y sin texto adicional.

Materia: {$materia->nombre}
Código: {$materia->codigo}
Nivel: {$nivel}

Texto base del PDF:
{$textoBase}

Devuelve esta estructura exacta:
{
  "nivel": "{$nivel}",
  "titulo": "...",
  "introduccion": "...",
  "resumen": "...",
  "contenidos": [
    {"titulo": "...", "descripcion": "..."}
  ],
  "ejercicios_interactivos": [
    {"titulo": "...", "descripcion": "...", "tipo": "quiz|completar|respuesta_corta|arrastrar"}
  ],
  "actividades": [
    {"titulo": "...", "descripcion": "...", "tipo": "practica|lectura|quiz"}
  ],
  "retos": [
    {"titulo": "...", "descripcion": "..."}
  ],
  "preguntas_frecuentes": [
    {"pregunta": "...", "respuesta": "..."}
  ],
  "aprendizaje_progresivo": {
    "nivel_actual": "{$nivel}",
    "siguiente_nivel": "...",
    "progreso_sugerido": "..."
  }
}
PROMPT;
    }

    /**
     * Construye el prompt para chat
     */
    private function buildChatPrompt(Materia $materia, string $nivel, string $message, string $textoBase, array $history, string $persona): string
    {
        $historialTexto = '';
        foreach ($history as $item) {
            $role = $item['role'] ?? 'user';
            $content = $item['content'] ?? '';
            if ($content === '') {
                continue;
            }
            $historialTexto .= strtoupper((string) $role) . ': ' . $content . "\n";
        }

                $personaInstr = $this->personaInstructions($persona, $nivel);

                return <<<PROMPT
Eres un tutor educativo dentro de una app escolar. $personaInstr
Responde SOLO JSON válido y sin texto adicional.

Materia: {$materia->nombre}
Codigo: {$materia->codigo}
Nivel: {$nivel}

Contexto del PDF:
{$textoBase}

Historial de conversación:
{$historialTexto}

Pregunta del estudiante:
{$message}

Devuelve exactamente esta estructura:
{
    "respuesta": "...",
    "sugerencias": ["...", "..."],
    "pregunta_siguiente": "..."
}
PROMPT;
    }

        /**
         * Instrucciones según la persona seleccionada
         */
        private function personaInstructions(string $persona, string $nivel): string
        {
                $p = strtolower(trim($persona));

                return match ($p) {
                        'profesor_explicativo' => 'Adopta el rol de un profesor claro y explicativo: da definiciones, ejemplos y explicaciones paso a paso. Ajusta el lenguaje al nivel del estudiante.',
                        'profesor_examen' => 'Adopta el rol de un profesor que genera preguntas tipo examen: entrega preguntas, opciones y soluciones breves. No des explicaciones extensas salvo que se pidan.',
                        'profesor_amigable' => 'Adopta un tono amistoso y motivador: celebra aciertos, ofrece pistas cuando el estudiante falla y sugiere ejercicios prácticos.',
                        default => 'Adopta el rol de un profesor interactivo: plantea preguntas aclaratorias, solicita que el estudiante intente resolver antes de mostrar la solución, ofrece pistas progresivas y pequeños ejercicios adaptados al nivel.',
                };
        }

    /**
     * Realiza la solicitud a Ollama
     */
    private function requestOllama(string $prompt): array|string|null
    {
        $provider = strtolower((string) config('ai.provider', 'ollama'));
        if ($provider !== 'ollama') {
            return null;
        }

        $host = rtrim((string) config('ai.ollama.host', 'http://127.0.0.1:11434'), '/');
        $timeout = max(5, (int) config('ai.ollama.timeout', 25));
        $model = $this->resolveOllamaModel($host, (string) config('ai.ollama.model', 'llama3.1'), $timeout);

        // Prepara payload y registra (de forma truncada) para debug sin exponer demasiado
        $payload = [
            'model' => $model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Responde solo JSON valido y sin texto adicional.',
                ],
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ],
            'stream' => false,
            'format' => 'json',
        ];

        try {
            \Illuminate\Support\Facades\Log::debug('Ollama request prepared', [
                'host' => $host,
                'model' => $model,
                'prompt_preview' => mb_substr($prompt, 0, 400),
            ]);

            $response = Http::timeout($timeout)->acceptJson()->post($host . '/api/chat', $payload);

            // Registrar status y body truncado
            $bodyText = null;
            try {
                $bodyText = $response->body();
            } catch (\Throwable $_) {
                $bodyText = null;
            }

            \Illuminate\Support\Facades\Log::debug('Ollama response', [
                'status' => $response->status(),
                'body_preview' => is_string($bodyText) ? mb_substr($bodyText, 0, 2000) : null,
            ]);

            if (!$response->successful()) {
                return null;
            }

            $data = $response->json();
            $raw = $data['message']['content'] ?? $data['response'] ?? null;
            if (!is_string($raw) || $raw === '') {
                return null;
            }

            $decoded = json_decode($this->cleanJson($raw), true);
            if (is_array($decoded)) {
                return $decoded;
            }

            $decoded = json_decode($this->extractJsonFragment($raw), true);
            return is_array($decoded) ? $decoded : null;
        } catch (\Throwable $e) {
            // Registro de error más completo para diagnóstico sin alterar el flujo normal
            \Illuminate\Support\Facades\Log::error('Ollama request exception', [
                'message' => $e->getMessage(),
                'exception' => \get_class($e),
                'prompt_preview' => mb_substr($prompt, 0, 400),
            ]);

            return null;
        }
    }

    /**
     * Resuelve el modelo disponible en Ollama
     */
    private function resolveOllamaModel(string $host, string $preferredModel, int $timeout): string
    {
        $preferredModel = trim($preferredModel);

        try {
            $response = Http::timeout(min(10, $timeout))->acceptJson()->get($host . '/api/tags');
            if ($response->successful()) {
                $models = $response->json('models', []);

                if (is_array($models)) {
                    $availableModels = [];

                    foreach ($models as $model) {
                        if (!is_array($model)) {
                            continue;
                        }

                        $name = trim((string) ($model['name'] ?? ''));
                        if ($name !== '') {
                            $availableModels[] = $name;
                        }
                    }

                    if ($preferredModel !== '' && in_array($preferredModel, $availableModels, true)) {
                        return $preferredModel;
                    }

                    if (!empty($availableModels)) {
                        return $availableModels[0];
                    }
                }
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Ollama models fetch failed: ' . $e->getMessage());
        }

        return $preferredModel !== '' ? $preferredModel : 'llama3.1';
    }

    /**
     * Limpia JSON de markdown y caracteres especiales
     */
    private function cleanJson(string $raw): string
    {
        $raw = trim($raw);
        $raw = preg_replace('/^```(?:json)?/i', '', $raw);
        $raw = preg_replace('/```$/', '', $raw);

        return trim($raw);
    }

    /**
     * Extrae un fragmento JSON del texto
     */
    private function extractJsonFragment(string $raw): string
    {
        $startObject = strpos($raw, '{');
        $endObject = strrpos($raw, '}');

        if ($startObject === false || $endObject === false || $endObject <= $startObject) {
            return $this->cleanJson($raw);
        }

        return trim(substr($raw, $startObject, $endObject - $startObject + 1));
    }

    /**
     * Normaliza el pack de aprendizaje
     */
    private function normalizePack(array $pack, Materia $materia, string $nivel): array
    {
        return [
            'titulo' => $pack['titulo'] ?? 'Contenido para ' . $materia->nombre,
            'introduccion' => $pack['introduccion'] ?? 'Introducción generada para ' . $materia->nombre . '.',
            'resumen' => $pack['resumen'] ?? 'Contenido generado para el nivel seleccionado.',
            'contenidos' => $pack['contenidos'] ?? [],
            'ejercicios_interactivos' => $pack['ejercicios_interactivos'] ?? [],
            'actividades' => $pack['actividades'] ?? [],
            'retos' => $pack['retos'] ?? [],
            'preguntas_frecuentes' => $pack['preguntas_frecuentes'] ?? [],
            'aprendizaje_progresivo' => $pack['aprendizaje_progresivo'] ?? [
                'nivel_actual' => $nivel,
                'siguiente_nivel' => $this->nextNivel($nivel),
                'progreso_sugerido' => 'Sigue reforzando el tema con práctica guiada.',
            ],
        ];
    }

    /**
     * Pack de aprendizaje por defecto
     */
    private function fallbackPack(Materia $materia, string $nivel, string $textoBase): array
    {
        $fragmento = $textoBase !== '' ? mb_substr($textoBase, 0, 220) : 'Aún no hay PDF cargado para esta materia.';

        return [
            'titulo' => 'Contenido de ' . $materia->nombre,
            'introduccion' => 'Bienvenido a ' . $materia->nombre . '. Aquí encontrarás una guía breve para empezar.',
            'resumen' => 'Resumen automático para el nivel ' . $nivel . '.',
            'contenidos' => [
                [
                    'titulo' => 'Introducción a ' . $materia->nombre,
                    'descripcion' => $fragmento,
                ],
            ],
            'ejercicios_interactivos' => [
                [
                    'titulo' => 'Chequeo rápido',
                    'descripcion' => 'Responde tres preguntas de comprensión sobre el tema.',
                    'tipo' => 'quiz',
                ],
            ],
            'actividades' => [
                [
                    'titulo' => 'Practica guiada',
                    'descripcion' => 'Lee el contenido y responde tres preguntas cortas sobre el tema.',
                    'tipo' => 'practica',
                ],
            ],
            'retos' => [
                [
                    'titulo' => 'Reto inicial',
                    'descripcion' => 'Explica con tus palabras el tema principal de la materia.',
                ],
            ],
            'preguntas_frecuentes' => [
                [
                    'pregunta' => '¿Por dónde empiezo?',
                    'respuesta' => 'Lee la introducción y luego intenta el ejercicio rápido.',
                ],
            ],
            'aprendizaje_progresivo' => [
                'nivel_actual' => $nivel,
                'siguiente_nivel' => $this->nextNivel($nivel),
                'progreso_sugerido' => 'Sigue practicando los conceptos del PDF para avanzar.',
            ],
        ];
    }

    /**
     * Normaliza el historial de chat
     */
    private function normalizeChatHistory(array $history): array
    {
        $normalized = [];

        foreach ($history as $item) {
            if (!is_array($item)) {
                continue;
            }

            $role = strtolower((string) ($item['role'] ?? 'user'));
            $content = trim((string) ($item['content'] ?? ''));

            if ($content === '') {
                continue;
            }

            $normalized[] = [
                'role' => in_array($role, ['user', 'assistant', 'system'], true) ? $role : 'user',
                'content' => $content,
            ];
        }

        return array_slice($normalized, -10);
    }

    /**
     * Normaliza el nivel de aprendizaje
     */
    private function normalizeNivel(string $nivel): string
    {
        $nivel = strtolower(trim($nivel));
        return in_array($nivel, ['basico', 'intermedio', 'avanzado'], true) ? $nivel : 'basico';
    }

    /**
     * Calcula el siguiente nivel
     */
    private function nextNivel(string $nivel): string
    {
        return match ($this->normalizeNivel($nivel)) {
            'basico' => 'intermedio',
            'intermedio' => 'avanzado',
            default => 'avanzado',
        };
    }
}
