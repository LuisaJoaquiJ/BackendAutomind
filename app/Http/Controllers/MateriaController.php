<?php

namespace App\Http\Controllers;

use App\Models\Materia;
use App\Services\MateriaIAService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MateriaController extends Controller
{
    // ── ESTUDIANTE: ver sus propias materias ──────────────────────────────────
    public function index(Request $request)
    {
        $materias = Materia::where('user_id', $request->user()->id)->get();

        return response()->json([
            'success'        => true,
            'data'           => $materias,
            'total_creditos' => $materias->sum('creditos')
        ]);
    }

    // ── ESTUDIANTE: crear materia (no se usa desde app, solo admin) ───────────
    public function store(Request $request)
    {
        $request->validate([
            'nombre'   => 'required',
            'codigo'   => 'required',
            'creditos' => 'required|integer',
            'docente'  => 'required',
            'horario'  => 'required',
            'sala'     => 'required',
        ]);

        $materia = Materia::create([
            'user_id'  => $request->user()->id,
            'nombre'   => $request->nombre,
            'codigo'   => $request->codigo,
            'creditos' => $request->creditos,
            'docente'  => $request->docente,
            'horario'  => $request->horario,
            'sala'     => $request->sala,
        ]);

        return response()->json(['success' => true, 'data' => $materia]);
    }

    // ── ADMIN: listar todas las materias ──────────────────────────────────────
    public function adminIndex(Request $request)
    {
        try {
            $search = $request->query('search', null);

            $query = Materia::with('user:id,name,email,programa');

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('nombre', 'like', "%$search%")
                      ->orWhere('codigo', 'like', "%$search%")
                      ->orWhere('docente', 'like', "%$search%");
                });
            }

            $materias = $query->orderBy('created_at', 'desc')->get();

            // Formatear para que Android reciba user_id y estudiante
            $data = $materias->map(function ($m) {
                return [
                    'id'       => $m->id,
                    'user_id'  => $m->user_id,
                    'nombre'   => $m->nombre,
                    'codigo'   => $m->codigo,
                    'creditos' => $m->creditos,
                    'docente'  => $m->docente,
                    'horario'  => $m->horario,
                    'sala'     => $m->sala,
                    'estudiante' => $m->user ? $m->user->name : 'Sin asignar',
                    'created_at' => $m->created_at,
                ];
            });

            return response()->json(['success' => true, 'data' => $data]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    // ── ADMIN: crear materia para un estudiante específico ────────────────────
    public function adminStore(Request $request)
    {
        try {
            $request->validate([
                'user_id'  => 'required|exists:users,id',
                'nombre'   => 'required|string',
                'codigo'   => 'required|string',
                'creditos' => 'required|integer|min:1|max:10',
                'docente'  => 'required|string',
                'horario'  => 'required|string',
                'sala'     => 'required|string',
            ]);

            $materia = Materia::create([
                'user_id'  => $request->user_id,
                'nombre'   => $request->nombre,
                'codigo'   => strtoupper($request->codigo),
                'creditos' => $request->creditos,
                'docente'  => $request->docente,
                'horario'  => $request->horario,
                'sala'     => $request->sala,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Materia creada correctamente',
                'data'    => $materia
            ], 201);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    // ── ADMIN: actualizar materia ─────────────────────────────────────────────
    public function adminUpdate(Request $request, $id)
    {
        try {
            $materia = Materia::find($id);

            if (!$materia) {
                return response()->json(['success' => false, 'message' => 'Materia no encontrada'], 404);
            }

            $request->validate([
                'user_id'  => 'nullable|exists:users,id',
                'nombre'   => 'nullable|string',
                'creditos' => 'nullable|integer|min:1|max:10',
                'docente'  => 'nullable|string',
                'horario'  => 'nullable|string',
                'sala'     => 'nullable|string',
            ]);

            if ($request->filled('user_id'))  $materia->user_id  = $request->user_id;
            if ($request->filled('nombre'))   $materia->nombre   = $request->nombre;
            if ($request->filled('creditos')) $materia->creditos = $request->creditos;
            if ($request->filled('docente'))  $materia->docente  = $request->docente;
            if ($request->filled('horario'))  $materia->horario  = $request->horario;
            if ($request->filled('sala'))     $materia->sala     = $request->sala;

            $materia->save();

            return response()->json([
                'success' => true,
                'message' => 'Materia actualizada correctamente',
                'data'    => $materia
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    // ── ADMIN: eliminar materia ───────────────────────────────────────────────
    public function adminDestroy($id)
    {
        try {
            $materia = Materia::find($id);

            if (!$materia) {
                return response()->json(['success' => false, 'message' => 'Materia no encontrada'], 404);
            }

            $materia->delete();

            return response()->json([
                'success' => true,
                'message' => 'Materia eliminada correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // 🤖 MÉTODOS DE IA - AGENTE OLLAMA LOCAL
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Obtiene contenido dinámico generado por IA para una materia
     * Soporta diferentes niveles: basico, intermedio, avanzado
     */
    public function pantallaDinamica(Request $request, $id)
    {
        $materia = $this->findAccessibleMateria($request, $id);

        if (!$materia) {
            return response()->json([
                'success' => false,
                'message' => 'Materia no encontrada'
            ], 404);
        }

        $nivel = $request->query('nivel', 'basico');
        $service = app(MateriaIAService::class);
        
        // Obtener texto del PDF si existe
        $pdfText = null;
        $archivo = $materia->archivos()->where('tipo', 'pdf')->first();
        if ($archivo) {
            $pdfText = $archivo->nombre_original ?? '';
        }

        $pack = $service->buildLearningPack($materia, $pdfText, $nivel);

        return response()->json([
            'success' => true,
            'data' => [
                'materia' => $materia,
                'nivel_seleccionado' => $nivel,
                'niveles_disponibles' => ['basico', 'intermedio', 'avanzado'],
                'introduccion' => $pack['introduccion'] ?? null,
                'contenidos' => $pack['contenidos'],
                'contenido' => $pack['contenidos'],
                'ejercicios_interactivos' => $pack['ejercicios_interactivos'] ?? [],
                'archivos_generales' => $archivo ? [[
                    'nombre' => $archivo->nombre_original,
                    'url' => Storage::disk('public')->url($archivo->ruta),
                    'tipo' => 'pdf',
                ]] : [],
                'archivos' => $archivo ? [[
                    'nombre' => $archivo->nombre_original,
                    'url' => Storage::disk('public')->url($archivo->ruta),
                    'tipo' => 'pdf',
                ]] : [],
                'actividades' => $pack['actividades'],
                'retos' => $pack['retos'],
                'preguntas_frecuentes' => $pack['preguntas_frecuentes'] ?? [],
                'aprendizaje_progresivo' => [
                    'nivel_actual' => $pack['aprendizaje_progresivo']['nivel_actual'] ?? $nivel,
                    'siguiente_nivel' => $pack['aprendizaje_progresivo']['siguiente_nivel'] ?? 'intermedio',
                    'progreso_sugerido' => $pack['aprendizaje_progresivo']['progreso_sugerido'] ?? 'Sigue practicando los conceptos de la materia.'
                ]
            ]
        ]);
    }

    /**
     * Inicia o continúa un chat interactivo con el agente IA
     * Soporta historial de conversación y diferentes niveles de aprendizaje
     */
    public function chat(Request $request, $id)
    {
        $materia = $this->findAccessibleMateria($request, $id);

        if (!$materia) {
            return response()->json([
                'success' => false,
                'message' => 'Materia no encontrada'
            ], 404);
        }

        $payload = $request->validate([
            'mensaje' => 'required|string|max:4000',
            'nivel' => 'nullable|in:basico,intermedio,avanzado',
            'historial' => 'nullable|array',
            'historial.*.role' => 'nullable|string',
            'historial.*.content' => 'nullable|string',
            'persona' => 'nullable|string',
        ]);

        $nivel = $payload['nivel'] ?? 'basico';
        $persona = $payload['persona'] ?? 'profesor_interactivo';
        
        // Obtener texto del PDF si existe
        $pdfText = null;
        $archivo = $materia->archivos()->where('tipo', 'pdf')->first();
        if ($archivo) {
            $pdfText = $archivo->nombre_original ?? '';
        }

        $respuesta = app(MateriaIAService::class)->buildChatResponse(
            $materia,
            $pdfText,
            $payload['mensaje'],
            $nivel,
            $payload['historial'] ?? [],
            $persona
        );

        return response()->json([
            'success' => true,
            'data' => $respuesta,
        ]);
    }

    /**
     * Helper: encuentra una materia accesible para el usuario actual
     * Respeta los permisos según el rol del usuario
     */
    private function findAccessibleMateria(Request $request, $id)
    {
        $user = $request->user();
        $role = strtolower((string) ($user->rol ?? ''));

        $query = Materia::where('id', $id);

        if (in_array($role, ['profesor', 'docente'], true)) {
            $query->where('user_id', $user->id);
        } elseif (in_array($role, ['estudiante', 'student'], true)) {
            $query->where('user_id', $user->id);
        }

        return $query->first();
    }
}