<?php

namespace App\Http\Controllers;

use App\Models\Materia;
use App\Models\MateriaActividad;
use App\Models\MateriaRespuesta;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MateriaActividadController extends Controller
{
    public function index(Request $request, Materia $materia)
    {
        $nivel = $request->query('nivel');

        $query = $materia->actividades()->where('activo', true)->orderBy('orden');

        if ($nivel) {
            $query->where('nivel', $nivel);
        }

        return response()->json([
            'success' => true,
            'data' => $query->get(),
        ]);
    }

    public function store(Request $request, Materia $materia)
    {
        $this->autorizarGestion($request, $materia);

        $data = $request->validate([
            'titulo' => 'required|string|max:255',
            'tipo' => ['required', Rule::in(['pregunta', 'practico', 'programacion', 'reto_dinamico'])],
            'nivel' => ['required', Rule::in(['basico', 'intermedio', 'avanzado'])],
            'descripcion' => 'nullable|string|max:500',
            'enunciado' => 'required|string',
            'solucion_referencia' => 'nullable|string',
            'pistas' => 'nullable|array',
            'pistas.*' => 'string',
            'criterios' => 'nullable|array',
            'criterios.*' => 'string',
            'orden' => 'nullable|integer|min:1',
            'puntaje_maximo' => 'nullable|integer|min:1|max:100',
        ]);

        $actividad = MateriaActividad::create([
            'materia_id' => $materia->id,
            'creado_por' => $request->user()->id,
            'titulo' => $data['titulo'],
            'tipo' => $data['tipo'],
            'nivel' => $data['nivel'],
            'descripcion' => $data['descripcion'] ?? null,
            'enunciado' => $data['enunciado'],
            'solucion_referencia' => $data['solucion_referencia'] ?? null,
            'pistas' => $data['pistas'] ?? [],
            'criterios' => $data['criterios'] ?? [],
            'orden' => $data['orden'] ?? (($materia->actividades()->max('orden') ?? 0) + 1),
            'puntaje_maximo' => $data['puntaje_maximo'] ?? 100,
            'activo' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Actividad creada correctamente.',
            'data' => $actividad,
        ], 201);
    }

    public function responder(Request $request, Materia $materia, MateriaActividad $actividad)
    {
        abort_if($actividad->materia_id !== $materia->id, 404);

        $data = $request->validate([
            'respuesta' => 'nullable|string',
            'codigo' => 'nullable|string',
            'lenguaje' => 'nullable|string|max:50',
        ]);

        if (empty($data['respuesta']) && empty($data['codigo'])) {
            return response()->json([
                'success' => false,
                'message' => 'Debes enviar una respuesta textual o c\u00f3digo.',
            ], 422);
        }

        $evaluacion = $this->evaluarRespuesta($actividad, $data);

        $intento = MateriaRespuesta::create([
            'materia_actividad_id' => $actividad->id,
            'user_id' => $request->user()->id,
            'respuesta' => $data['respuesta'] ?? null,
            'codigo' => $data['codigo'] ?? null,
            'lenguaje' => $data['lenguaje'] ?? null,
            'estado' => $evaluacion['estado'],
            'puntaje' => $evaluacion['puntaje'],
            'feedback' => $evaluacion['feedback'],
            'pistas_generadas' => $evaluacion['pistas'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Respuesta evaluada correctamente.',
            'data' => [
                'actividad_id' => $actividad->id,
                'resultado' => $intento,
                'siguiente_paso' => $evaluacion['siguiente_paso'],
            ],
        ], 201);
    }

    public function generarReto(Request $request, Materia $materia)
    {
        $data = $request->validate([
            'nivel' => ['nullable', Rule::in(['basico', 'intermedio', 'avanzado'])],
            'guardar' => 'nullable|boolean',
        ]);

        $reto = $this->construirRetoDinamico($materia, $data['nivel'] ?? 'basico');

        if (($data['guardar'] ?? false) === true) {
            $this->autorizarGestion($request, $materia);

            $actividad = MateriaActividad::create([
                'materia_id' => $materia->id,
                'creado_por' => $request->user()->id,
                'titulo' => $reto['titulo'],
                'tipo' => 'reto_dinamico',
                'nivel' => $reto['nivel'],
                'descripcion' => $reto['descripcion'],
                'enunciado' => $reto['enunciado'],
                'solucion_referencia' => $reto['solucion_referencia'],
                'pistas' => $reto['pistas'],
                'criterios' => $reto['criterios'],
                'orden' => (($materia->actividades()->max('orden') ?? 0) + 1),
                'puntaje_maximo' => 100,
                'activo' => true,
            ]);

            $reto['actividad_guardada'] = $actividad;
        }

        return response()->json([
            'success' => true,
            'data' => $reto,
        ]);
    }

    private function evaluarRespuesta(MateriaActividad $actividad, array $data): array
    {
        $respuestaUsuario = trim((string) ($data['codigo'] ?? $data['respuesta'] ?? ''));
        $referencia = trim((string) ($actividad->solucion_referencia ?? ''));
        $criterios = collect($actividad->criterios ?? [])->filter()->values();
        $pistas = collect($actividad->pistas ?? [])->filter()->values();

        $puntaje = 40;
        $estado = 'en_progreso';
        $feedback = 'Vas por buen camino, pero tu respuesta todav\u00eda puede fortalecerse.';
        $pistasGeneradas = [];

        if ($respuestaUsuario === '') {
            return [
                'estado' => 'incompleta',
                'puntaje' => 0,
                'feedback' => 'No se detect\u00f3 contenido suficiente para evaluar tu respuesta.',
                'pistas' => $pistas->take(2)->values()->all(),
                'siguiente_paso' => 'Escribe una idea inicial o una versi\u00f3n parcial de la soluci\u00f3n.',
            ];
        }

        if ($referencia !== '' && strcasecmp($respuestaUsuario, $referencia) === 0) {
            $puntaje = 100;
            $estado = 'correcta';
            $feedback = 'Resolviste la actividad correctamente. Ahora intenta explicar por qu\u00e9 tu respuesta funciona.';
        } elseif ($criterios->isNotEmpty()) {
            $coincidencias = $criterios->filter(function ($criterio) use ($respuestaUsuario) {
                return stripos($respuestaUsuario, $criterio) !== false;
            })->count();

            $puntaje = min(100, 30 + (int) round(($coincidencias / max(1, $criterios->count())) * 70));

            if ($puntaje >= 80) {
                $estado = 'casi_correcta';
                $feedback = 'Tu respuesta cubre la mayor parte de lo esperado. Revisa un ajuste final para cerrar la idea con m\u00e1s precisi\u00f3n.';
            } elseif ($puntaje >= 60) {
                $estado = 'parcial';
                $feedback = 'La base es correcta, pero a\u00fan faltan elementos importantes del procedimiento o de la explicaci\u00f3n.';
            } else {
                $estado = 'en_progreso';
                $feedback = 'Tu respuesta muestra intento, aunque todav\u00eda faltan conceptos clave. Usa las pistas para acercarte paso a paso.';
            }
        } elseif ($actividad->tipo === 'programacion') {
            $tieneFuncion = preg_match('/function|def |public static void main|=>/', $respuestaUsuario) === 1;
            $tieneControl = preg_match('/if|for|while|foreach|switch/', $respuestaUsuario) === 1;

            $puntaje = ($tieneFuncion ? 35 : 15) + ($tieneControl ? 35 : 10) + (strlen($respuestaUsuario) > 80 ? 30 : 15);

            if ($puntaje >= 80) {
                $estado = 'casi_correcta';
                $feedback = 'La estructura de tu soluci\u00f3n es s\u00f3lida. Verifica nombres, casos l\u00edmite y claridad del resultado.';
            } else {
                $feedback = 'Tu propuesta ya tiene forma de soluci\u00f3n. Refuerza la l\u00f3gica principal y agrega validaciones o ejemplos de prueba.';
            }
        }

        if ($puntaje < 100) {
            $pistasGeneradas = $pistas->take(3)->values()->all();
        }

        return [
            'estado' => $estado,
            'puntaje' => $puntaje,
            'feedback' => $feedback,
            'pistas' => $pistasGeneradas,
            'siguiente_paso' => $puntaje >= 80
                ? 'Reintenta afinando los detalles finales o pasa al siguiente nivel.'
                : 'Revisa las pistas, ajusta tu respuesta y vuelve a intentarlo.',
        ];
    }

    private function construirRetoDinamico(Materia $materia, string $nivel): array
    {
        $nombre = mb_strtolower($materia->nombre, 'UTF-8');
        $esProgramacion = str_contains($nombre, 'program') || str_contains($nombre, 'algorit') || str_contains($nombre, 'c\u00f3digo');

        if ($esProgramacion) {
            return [
                'titulo' => 'Reto de ' . $materia->nombre . ' - ' . ucfirst($nivel),
                'nivel' => $nivel,
                'descripcion' => 'Ejercicio generado din\u00e1micamente para practicar resoluci\u00f3n de problemas.',
                'enunciado' => match ($nivel) {
                    'intermedio' => 'Construye una funci\u00f3n que reciba una lista de n\u00fameros y retorne solo los valores repetidos sin duplicarlos en el resultado.',
                    'avanzado' => 'Implementa una soluci\u00f3n que procese un conjunto de estudiantes con notas y retorne el ranking final, resolviendo empates con una regla secundaria documentada por ti.',
                    default => 'Crea un programa que reciba dos n\u00fameros y muestre cu\u00e1l es mayor. Si son iguales, ind\u00edcalo claramente.',
                },
                'solucion_referencia' => null,
                'pistas' => match ($nivel) {
                    'intermedio' => [
                        'Piensa en una estructura para contar apariciones.',
                        'El resultado no debe repetir el mismo n\u00famero dos veces.',
                        'Prueba con entradas cortas antes de generalizar.',
                    ],
                    'avanzado' => [
                        'Separa el problema en c\u00e1lculo de promedio y ordenamiento.',
                        'Define con claridad la regla de desempate.',
                        'Incluye al menos un caso l\u00edmite en tu explicaci\u00f3n.',
                    ],
                    default => [
                        'Necesitas comparar ambos valores antes de mostrar el mensaje.',
                        'Considera el caso en que los dos n\u00fameros sean iguales.',
                        'Usa una salida clara para el usuario.',
                    ],
                },
                'criterios' => $nivel === 'basico'
                    ? ['comparar', 'igual', 'mayor']
                    : ['entrada', 'proceso', 'resultado'],
            ];
        }

        return [
            'titulo' => 'Reto aplicado de ' . $materia->nombre,
            'nivel' => $nivel,
            'descripcion' => 'Actividad breve orientada a comprensi\u00f3n y aplicaci\u00f3n progresiva.',
            'enunciado' => 'Explica un concepto central de la materia con tus palabras y luego apl\u00edcalo en un ejemplo de la vida real o de tu entorno acad\u00e9mico.',
            'solucion_referencia' => null,
            'pistas' => [
                'Primero define el concepto de forma sencilla.',
                'Luego muestra c\u00f3mo se aplica en una situaci\u00f3n concreta.',
                'Evita copiar definiciones literales; prioriza tu comprensi\u00f3n.',
            ],
            'criterios' => ['concepto', 'aplicacion', 'ejemplo'],
        ];
    }

    private function autorizarGestion(Request $request, Materia $materia): void
    {
        $user = $request->user();
        $rol = $user->rol ?? null;

        if ($materia->user_id !== $user->id && !in_array($rol, ['profesor', 'admin'], true)) {
            abort(403, 'No tienes permisos para gestionar actividades de esta materia.');
        }
    }
}
