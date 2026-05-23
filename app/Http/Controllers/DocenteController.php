<?php

namespace App\Http\Controllers;

use App\Models\Aviso;
use App\Models\Materia;
use App\Models\MateriaContenido;
use App\Models\Nota;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DocenteController extends Controller
{
    // ─── Verifica que el usuario sea profesor ────────────────────────────────
    private function verificarDocente()
    {
        $user = Auth::user();

        if (!$user) {
            abort(403, 'Usuario no autenticado.');
        }

        // ✅ Columna en BD se llama "rol", valor del profesor: "profesor"
        if (!in_array($user->rol, ['profesor', 'docente', 'admin'])) {
            abort(403, 'Rol no permitido: ' . $user->rol);
        }

        return $user;
    }

    // ─── Materias asignadas al docente por nombre ─────────────────────────────
    private function materiasDelDocente($docente)
    {
        return Materia::whereRaw('LOWER(docente) = ?', [strtolower($docente->name)]);
    }

    // ─── Promedio: corte1*30% + corte2*30% + corte3*40% ──────────────────────
    private function calcularPromedio($c1, $c2, $c3): float
    {
        return round((($c1 ?? 0) * 0.3) + (($c2 ?? 0) * 0.3) + (($c3 ?? 0) * 0.4), 2);
    }

    // =========================================================================
    // DASHBOARD — GET /api/docente/dashboard
    // =========================================================================
    public function dashboard(Request $request)
    {
        try {
            $docente  = $this->verificarDocente();
            $materias = $this->materiasDelDocente($docente)->get();
            $ids      = $materias->pluck('id');

            $totalEstudiantes = $ids->isEmpty() ? 0 :
                DB::table('materias')
                    ->whereIn('id', $ids)
                    ->whereNotNull('user_id')
                    ->distinct()
                    ->count('user_id');

            $notas           = $ids->isEmpty() ? collect() : Nota::whereIn('materia_id', $ids)->get();
            $promedioGeneral = $notas->count()
                ? round($notas->map(fn($n) => $this->calcularPromedio($n->corte1, $n->corte2, $n->corte3))->avg(), 2)
                : 0.0;

            $totalAvisos = 0;
            if ($ids->isNotEmpty()) {
                try {
                    $totalAvisos = Aviso::whereIn('materia_id', $ids)->count();
                } catch (\Exception $e) {
                    $totalAvisos = 0;
                }
            }

            $totalContenidos = 0;
            if ($ids->isNotEmpty()) {
                try {
                    $totalContenidos = MateriaContenido::whereIn('materia_id', $ids)->count();
                } catch (\Exception $e) {
                    $totalContenidos = 0;
                }
            }

            return response()->json([
                'success' => true,
                'data'    => [
                    'estadisticas' => [
                        'total_cursos'      => $materias->groupBy('nombre')->count(),
                        'total_estudiantes' => $totalEstudiantes,
                        'total_avisos'      => $totalAvisos,
                        'promedio_general'  => $promedioGeneral,
                        'total_contenidos'  => $totalContenidos,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('DocenteController::dashboard - ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // MATERIAS — GET /api/docente/materias
    // =========================================================================
    public function materias(Request $request)
    {
        try {
            $docente = $this->verificarDocente();

            $agrupadas = $this->materiasDelDocente($docente)
                ->get()
                ->groupBy('nombre')
                ->map(fn($grupo) => [
                    'id'                => $grupo->first()->id,
                    'codigo'            => $grupo->first()->codigo ?? null,
                    'nombre'            => $grupo->first()->nombre,
                    'descripcion'       => $grupo->first()->descripcion ?? null,
                    'creditos'          => $grupo->first()->creditos ?? 0,
                    'semestre'          => $grupo->first()->semestre ?? 1,
                    'salon'             => $grupo->first()->sala ?? null,
                    'total_estudiantes' => $grupo->count(),
                    'horarios'          => $grupo->first()->horario ? [[
                        'id'          => $grupo->first()->id,
                        'dia'         => $this->extraerDia($grupo->first()->horario),
                        'hora_inicio' => $this->extraerHoraInicio($grupo->first()->horario),
                        'hora_fin'    => $this->extraerHoraFin($grupo->first()->horario),
                        'salon'       => $grupo->first()->sala ?? null,
                    ]] : [],
                ])
                ->values();

            return response()->json(['success' => true, 'data' => $agrupadas]);
        } catch (\Exception $e) {
            Log::error('DocenteController::materias - ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // ESTUDIANTES — GET /api/docente/materias/{materiaId}/estudiantes
    // =========================================================================
    public function estudiantes(Request $request, $materiaId)
    {
        try {
            $docente    = $this->verificarDocente();
            $materiaRef = $this->materiasDelDocente($docente)->where('id', $materiaId)->firstOrFail();

            $todasLasMaterias = $this->materiasDelDocente($docente)
                ->where('nombre', $materiaRef->nombre)->get();

            $userIds = $todasLasMaterias->pluck('user_id')->filter()->unique();

            if ($userIds->isEmpty()) {
                return response()->json(['success' => true, 'data' => []]);
            }

            $estudiantes = DB::table('users')
                ->whereIn('id', $userIds)
                ->select('id', 'name', 'email', 'documento', 'programa', 'semestre')
                ->get()
                ->map(function ($user) use ($todasLasMaterias) {
                    $mat  = $todasLasMaterias->firstWhere('user_id', $user->id);
                    $nota = $mat
                        ? Nota::where('user_id', $user->id)->where('materia_id', $mat->id)->first()
                        : null;

                    return [
                        'id'              => $user->id,
                        'name'            => $user->name,
                        'email'           => $user->email,
                        'documento'       => $user->documento,
                        'programa'        => $user->programa,
                        'semestre'        => $user->semestre,
                        'nota_definitiva' => $nota
                            ? $this->calcularPromedio($nota->corte1, $nota->corte2, $nota->corte3)
                            : null,
                        'notas_parciales' => $nota ? [
                            ['id' => $nota->id, 'tipo' => 'corte1', 'valor' => $nota->corte1, 'porcentaje' => 30],
                            ['id' => $nota->id, 'tipo' => 'corte2', 'valor' => $nota->corte2, 'porcentaje' => 30],
                            ['id' => $nota->id, 'tipo' => 'corte3', 'valor' => $nota->corte3, 'porcentaje' => 40],
                        ] : [],
                    ];
                });

            return response()->json(['success' => true, 'data' => $estudiantes]);
        } catch (\Exception $e) {
            Log::error('DocenteController::estudiantes - ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // NOTAS — GET /api/docente/materias/{materiaId}/notas
    // =========================================================================
    public function notas(Request $request, $materiaId)
    {
        try {
            $docente    = $this->verificarDocente();
            $materiaRef = $this->materiasDelDocente($docente)->where('id', $materiaId)->firstOrFail();

            $ids = $this->materiasDelDocente($docente)
                ->where('nombre', $materiaRef->nombre)->pluck('id');

            $notas = Nota::whereIn('materia_id', $ids)
                ->join('users', 'notas.user_id', '=', 'users.id')
                ->select(
                    'notas.id',
                    'notas.user_id as estudiante_id',
                    'users.name as estudiante',
                    'users.email',
                    'notas.materia_id as curso_id',
                    'notas.corte1',
                    'notas.corte2',
                    'notas.corte3',
                    'notas.created_at'
                )
                ->get()
                ->map(fn($n) => [
                    'id'            => $n->id,
                    'estudiante_id' => $n->estudiante_id,
                    'estudiante'    => $n->estudiante,
                    'email'         => $n->email,
                    'curso_id'      => $n->curso_id,
                    'nota'          => $this->calcularPromedio($n->corte1, $n->corte2, $n->corte3),
                    'corte1'        => $n->corte1,
                    'corte2'        => $n->corte2,
                    'corte3'        => $n->corte3,
                    'tipo'          => 'definitiva',
                    'porcentaje'    => 100,
                    'fecha'         => $n->created_at,
                ]);

            return response()->json(['success' => true, 'data' => $notas]);
        } catch (\Exception $e) {
            Log::error('DocenteController::notas - ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // REGISTRAR NOTA — POST /api/docente/materias/{materiaId}/notas
    // =========================================================================
    public function registrarNota(Request $request, $materiaId)
    {
        try {
            $docente = $this->verificarDocente();
            $request->validate([
                'estudiante_id' => 'required|integer|exists:users,id',
                'corte1'        => 'nullable|numeric|min:0|max:5',
                'corte2'        => 'nullable|numeric|min:0|max:5',
                'corte3'        => 'nullable|numeric|min:0|max:5',
                'nota'          => 'nullable|numeric|min:0|max:5',
            ]);

            $materiaRef = $this->materiasDelDocente($docente)->where('id', $materiaId)->firstOrFail();

            // Busca la materia específica del estudiante por nombre
            $mat = Materia::whereRaw('LOWER(nombre) = ?', [strtolower($materiaRef->nombre)])
                ->where('user_id', $request->estudiante_id)
                ->first();
            $idMateria = $mat?->id ?? $materiaId;

            $nota = Nota::updateOrCreate(
                ['user_id' => $request->estudiante_id, 'materia_id' => $idMateria],
                [
                    'corte1' => $request->corte1 ?? $request->nota,
                    'corte2' => $request->corte2,
                    'corte3' => $request->corte3,
                ]
            );

            $estudiante = DB::table('users')->where('id', $nota->user_id)->first();

            return response()->json([
                'success' => true,
                'message' => 'Nota registrada correctamente.',
                'data'    => [
                    'id'            => $nota->id,
                    'estudiante_id' => $nota->user_id,
                    'estudiante'    => $estudiante?->name ?? 'Desconocido',
                    'email'         => $estudiante?->email ?? null,
                    'curso_id'      => $nota->materia_id,
                    'nota'          => $this->calcularPromedio($nota->corte1, $nota->corte2, $nota->corte3),
                    'corte1'        => $nota->corte1,
                    'corte2'        => $nota->corte2,
                    'corte3'        => $nota->corte3,
                    'tipo'          => 'definitiva',
                    'porcentaje'    => 100,
                    'fecha'         => now()->toDateString(),
                ],
            ], 201);
        } catch (\Exception $e) {
            Log::error('DocenteController::registrarNota - ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // ACTUALIZAR NOTA — PUT /api/docente/notas/{notaId}
    // =========================================================================
    public function actualizarNota(Request $request, $notaId)
    {
        try {
            $docente = $this->verificarDocente();
            $request->validate([
                'corte1'        => 'nullable|numeric|min:0|max:5',
                'corte2'        => 'nullable|numeric|min:0|max:5',
                'corte3'        => 'nullable|numeric|min:0|max:5',
                'nota'          => 'nullable|numeric|min:0|max:5',
                'estudiante_id' => 'nullable|integer',
            ]);

            $nota = Nota::findOrFail($notaId);
            $data = [];

            if ($request->has('corte1') || $request->has('nota')) {
                $data['corte1'] = $request->corte1 ?? $request->nota ?? $nota->corte1;
            }
            if ($request->has('corte2')) $data['corte2'] = $request->corte2;
            if ($request->has('corte3')) $data['corte3'] = $request->corte3;

            $nota->update($data);
            $estudiante = DB::table('users')->where('id', $nota->user_id)->first();

            return response()->json([
                'success' => true,
                'message' => 'Nota actualizada correctamente.',
                'data'    => [
                    'id'            => $nota->id,
                    'estudiante_id' => $nota->user_id,
                    'estudiante'    => $estudiante?->name ?? 'Desconocido',
                    'email'         => $estudiante?->email ?? null,
                    'curso_id'      => $nota->materia_id,
                    'nota'          => $this->calcularPromedio($nota->corte1, $nota->corte2, $nota->corte3),
                    'corte1'        => $nota->corte1,
                    'corte2'        => $nota->corte2,
                    'corte3'        => $nota->corte3,
                    'tipo'          => 'definitiva',
                    'porcentaje'    => 100,
                    'fecha'         => now()->toDateString(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('DocenteController::actualizarNota - ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // HORARIOS — GET /api/docente/horarios
    // =========================================================================
    public function horarios(Request $request)
    {
        try {
            $docente  = $this->verificarDocente();
            $horarios = $this->materiasDelDocente($docente)
                ->get()
                ->groupBy('nombre')
                ->map(function ($grupo) {
                    $m = $grupo->first();
                    if (!$m->horario) return null;
                    return [
                        'id'          => $m->id,
                        'dia'         => $this->extraerDia($m->horario),
                        'hora_inicio' => $this->extraerHoraInicio($m->horario),
                        'hora_fin'    => $this->extraerHoraFin($m->horario),
                        'salon'       => $m->sala ?? null,
                        'curso'       => $m->nombre,
                        'curso_id'    => $m->id,
                        'codigo'      => $m->codigo ?? null,
                    ];
                })
                ->filter()
                ->values();

            return response()->json(['success' => true, 'data' => $horarios]);
        } catch (\Exception $e) {
            Log::error('DocenteController::horarios - ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // AVISOS — GET /api/docente/avisos
    // ✅ Filtra por materias del docente (no hay docente_id en la tabla)
    // =========================================================================
    public function avisos(Request $request)
    {
        try {
            $docente = $this->verificarDocente();
            $ids     = $this->materiasDelDocente($docente)->pluck('id');

            $avisos = $ids->isEmpty() ? collect() :
                Aviso::whereIn('materia_id', $ids)
                    ->orderByDesc('created_at')
                    ->get()
                    ->map(fn($a) => [
                        'id'                => $a->id,
                        'titulo'            => $a->titulo,
                        'contenido'         => $a->contenido ?? $a->descripcion ?? '',
                        'prioridad'         => $a->prioridad ?? 'media',
                        'materia_id'        => $a->materia_id ?? null,
                        'fecha_publicacion' => $a->created_at?->toDateTimeString(),
                    ]);

            return response()->json(['success' => true, 'data' => $avisos]);
        } catch (\Exception $e) {
            Log::error('DocenteController::avisos - ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // CREAR AVISO — POST /api/docente/avisos
    // =========================================================================
    public function crearAviso(Request $request)
    {
        try {
            $docente = $this->verificarDocente();
            $request->validate([
                'titulo'     => 'required|string|max:255',
                'contenido'  => 'required|string',
                'materia_id' => 'required|integer|exists:materias,id',
                'prioridad'  => 'nullable|in:baja,media,alta',
            ]);

            // Verifica que la materia pertenezca al docente
            $this->materiasDelDocente($docente)->where('id', $request->materia_id)->firstOrFail();

            $aviso = Aviso::create([
                'titulo'     => $request->titulo,
                'contenido'  => $request->contenido,
                'materia_id' => $request->materia_id,
                'prioridad'  => $request->prioridad ?? 'media',
                'autor_id'   => $docente->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Aviso publicado.',
                'data'    => [
                    'id'                => $aviso->id,
                    'titulo'            => $aviso->titulo,
                    'contenido'         => $aviso->contenido,
                    'prioridad'         => $aviso->prioridad,
                    'materia_id'        => $aviso->materia_id,
                    'fecha_publicacion' => $aviso->created_at?->toDateTimeString(),
                ],
            ], 201);
        } catch (\Exception $e) {
            Log::error('DocenteController::crearAviso - ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // ELIMINAR AVISO — DELETE /api/docente/avisos/{id}
    // =========================================================================
    public function eliminarAviso(Request $request, $id)
    {
        try {
            $docente = $this->verificarDocente();
            $ids     = $this->materiasDelDocente($docente)->pluck('id');

            Aviso::where('id', $id)
                ->whereIn('materia_id', $ids)
                ->firstOrFail()
                ->delete();

            return response()->json(['success' => true, 'message' => 'Aviso eliminado.']);
        } catch (\Exception $e) {
            Log::error('DocenteController::eliminarAviso - ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ─── Parsear horario string ───────────────────────────────────────────────
    private function extraerDia(string $h): string
    {
        foreach (['Lunes', 'Martes', 'Miércoles', 'Miercoles', 'Jueves', 'Viernes', 'Sábado', 'Sabado', 'Domingo'] as $dia) {
            if (stripos($h, $dia) !== false) {
                return match (strtolower($dia)) {
                    'miercoles' => 'Miércoles',
                    'sabado'    => 'Sábado',
                    default     => ucfirst(strtolower($dia)),
                };
            }
        }
        return 'Lunes';
    }

    private function extraerHoraInicio(string $h): string
    {
        preg_match('/(\d{1,2})(?::(\d{2}))?/', $h, $m);
        if (!isset($m[1])) return '07:00:00';
        $min = $m[2] ?? '00';
        return sprintf('%02d:%s:00', (int)$m[1], $min);
    }

    private function extraerHoraFin(string $h): string
    {
        preg_match_all('/(\d{1,2})(?::(\d{2}))?/', $h, $m);
        if (!isset($m[1][1])) return '09:00:00';
        $min = (isset($m[2][1]) && $m[2][1] !== '') ? $m[2][1] : '00';
        return sprintf('%02d:%s:00', (int)$m[1][1], $min);
    }
}