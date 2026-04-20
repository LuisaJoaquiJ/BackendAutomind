<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ContenidosAdminController extends Controller
{
    /**
     * Obtener todos los cursos para gestión
     */
    public function obtenerCursos(Request $request)
    {
        try {
            $semestre = $request->query('semestre', null);
            $limit = $request->query('limit', 50);
            $page = $request->query('page', 1);
            $search = $request->query('search', null);

            $query = DB::table('cursos')
                ->join('users', 'cursos.profesor_id', '=', 'users.id')
                ->where('cursos.activo', 1)
                ->select(
                    'cursos.id',
                    'cursos.codigo',
                    'cursos.nombre',
                    'cursos.descripcion',
                    'cursos.creditos',
                    'cursos.semestre',
                    'cursos.salon',
                    'users.name as profesor',
                    'users.id as profesor_id'
                );

            if ($semestre) {
                $query->where('cursos.semestre', $semestre);
            }

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('cursos.nombre', 'like', "%$search%")
                      ->orWhere('cursos.codigo', 'like', "%$search%");
                });
            }

            $total = $query->count();
            $offset = ($page - 1) * $limit;

            $cursos = $query->orderBy('cursos.semestre', 'asc')
                ->orderBy('cursos.nombre', 'asc')
                ->offset($offset)
                ->limit($limit)
                ->get();

            // Agregar total de estudiantes a cada curso
            foreach ($cursos as $curso) {
                $curso->total_estudiantes = DB::table('inscripciones')
                    ->where('curso_id', $curso->id)
                    ->where('estado', 'activa')
                    ->count();
            }

            return response()->json([
                'success' => true,
                'data' => $cursos,
                'pagination' => [
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit,
                    'pages' => ceil($total / $limit)
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear nuevo curso
     */
    public function crearCurso(Request $request)
    {
        try {
            $request->validate([
                'codigo' => 'required|string|unique:cursos,codigo',
                'nombre' => 'required|string|max:255',
                'profesor_id' => 'required|integer',
                'descripcion' => 'nullable|string',
                'creditos' => 'required|integer|between:1,6',
                'semestre' => 'required|integer|between:1,10',
                'salon' => 'nullable|string|max:50'
            ]);

            // Verificar que el profesor existe
            $profesor = DB::table('users')
                ->where('id', $request->profesor_id)
                ->where('rol', 'profesor')
                ->first();

            if (!$profesor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Profesor no encontrado o no es válido'
                ], 404);
            }

            $cursoId = DB::table('cursos')->insertGetId([
                'codigo' => $request->codigo,
                'nombre' => $request->nombre,
                'profesor_id' => $request->profesor_id,
                'descripcion' => $request->descripcion,
                'creditos' => $request->creditos,
                'semestre' => $request->semestre,
                'salon' => $request->salon,
                'activo' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            $curso = DB::table('cursos')
                ->join('users', 'cursos.profesor_id', '=', 'users.id')
                ->where('cursos.id', $cursoId)
                ->select(
                    'cursos.*',
                    'users.name as profesor'
                )
                ->first();

            return response()->json([
                'success' => true,
                'message' => 'Curso creado correctamente',
                'data' => $curso
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar curso
     */
    public function actualizarCurso(Request $request, $cursoId)
    {
        try {
            $curso = DB::table('cursos')->where('id', $cursoId)->first();

            if (!$curso) {
                return response()->json([
                    'success' => false,
                    'message' => 'Curso no encontrado'
                ], 404);
            }

            $request->validate([
                'nombre' => 'nullable|string|max:255',
                'descripcion' => 'nullable|string',
                'profesor_id' => 'nullable|integer',
                'creditos' => 'nullable|integer|between:1,6',
                'semestre' => 'nullable|integer|between:1,10',
                'salon' => 'nullable|string|max:50'
            ]);

            if ($request->filled('profesor_id')) {
                $profesor = DB::table('users')
                    ->where('id', $request->profesor_id)
                    ->where('rol', 'profesor')
                    ->first();

                if (!$profesor) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Profesor no encontrado o no es válido'
                    ], 404);
                }
            }

            $datos = [];
            if ($request->filled('nombre')) $datos['nombre'] = $request->nombre;
            if ($request->filled('descripcion')) $datos['descripcion'] = $request->descripcion;
            if ($request->filled('profesor_id')) $datos['profesor_id'] = $request->profesor_id;
            if ($request->filled('creditos')) $datos['creditos'] = $request->creditos;
            if ($request->filled('semestre')) $datos['semestre'] = $request->semestre;
            if ($request->filled('salon')) $datos['salon'] = $request->salon;
            $datos['updated_at'] = now();

            DB::table('cursos')
                ->where('id', $cursoId)
                ->update($datos);

            $cursoActualizado = DB::table('cursos')
                ->join('users', 'cursos.profesor_id', '=', 'users.id')
                ->where('cursos.id', $cursoId)
                ->select('cursos.*', 'users.name as profesor')
                ->first();

            return response()->json([
                'success' => true,
                'message' => 'Curso actualizado correctamente',
                'data' => $cursoActualizado
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar curso (desactivar)
     */
    public function eliminarCurso($cursoId)
    {
        try {
            $curso = DB::table('cursos')->where('id', $cursoId)->first();

            if (!$curso) {
                return response()->json([
                    'success' => false,
                    'message' => 'Curso no encontrado'
                ], 404);
            }

            // Verificar si hay estudiantes inscritos
            $estudiantesInscritos = DB::table('inscripciones')
                ->where('curso_id', $cursoId)
                ->where('estado', 'activa')
                ->count();

            if ($estudiantesInscritos > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar un curso con estudiantes inscritos'
                ], 403);
            }

            DB::table('cursos')
                ->where('id', $cursoId)
                ->update([
                    'activo' => 0,
                    'updated_at' => now()
                ]);

            return response()->json([
                'success' => true,
                'message' => 'Curso desactivado correctamente'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Agregar horario a curso
     */
    public function agregarHorario(Request $request, $cursoId)
    {
        try {
            $curso = DB::table('cursos')->where('id', $cursoId)->first();

            if (!$curso) {
                return response()->json([
                    'success' => false,
                    'message' => 'Curso no encontrado'
                ], 404);
            }

            $request->validate([
                'dia' => 'required|string|in:Lunes,Martes,Miércoles,Jueves,Viernes,Sábado,Domingo',
                'hora_inicio' => 'required|date_format:H:i:s',
                'hora_fin' => 'required|date_format:H:i:s',
                'salon' => 'nullable|string|max:50'
            ]);

            $horarioId = DB::table('horarios')->insertGetId([
                'curso_id' => $cursoId,
                'dia' => $request->dia,
                'hora_inicio' => $request->hora_inicio,
                'hora_fin' => $request->hora_fin,
                'salon' => $request->salon,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            $horario = DB::table('horarios')
                ->where('id', $horarioId)
                ->first();

            return response()->json([
                'success' => true,
                'message' => 'Horario agregado correctamente',
                'data' => $horario
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar horario
     */
    public function actualizarHorario(Request $request, $horarioId)
    {
        try {
            $horario = DB::table('horarios')->where('id', $horarioId)->first();

            if (!$horario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Horario no encontrado'
                ], 404);
            }

            $request->validate([
                'dia' => 'nullable|string|in:Lunes,Martes,Miércoles,Jueves,Viernes,Sábado,Domingo',
                'hora_inicio' => 'nullable|date_format:H:i:s',
                'hora_fin' => 'nullable|date_format:H:i:s',
                'salon' => 'nullable|string|max:50'
            ]);

            $datos = [];
            if ($request->filled('dia')) $datos['dia'] = $request->dia;
            if ($request->filled('hora_inicio')) $datos['hora_inicio'] = $request->hora_inicio;
            if ($request->filled('hora_fin')) $datos['hora_fin'] = $request->hora_fin;
            if ($request->filled('salon')) $datos['salon'] = $request->salon;
            $datos['updated_at'] = now();

            DB::table('horarios')
                ->where('id', $horarioId)
                ->update($datos);

            $horarioActualizado = DB::table('horarios')
                ->where('id', $horarioId)
                ->first();

            return response()->json([
                'success' => true,
                'message' => 'Horario actualizado correctamente',
                'data' => $horarioActualizado
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar horario
     */
    public function eliminarHorario($horarioId)
    {
        try {
            $horario = DB::table('horarios')->where('id', $horarioId)->first();

            if (!$horario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Horario no encontrado'
                ], 404);
            }

            DB::table('horarios')->where('id', $horarioId)->delete();

            return response()->json([
                'success' => true,
                'message' => 'Horario eliminado correctamente'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener horarios de un curso
     */
    public function obtenerHorariosCurso($cursoId)
    {
        try {
            $horarios = DB::table('horarios')
                ->where('curso_id', $cursoId)
                ->orderByRaw("FIELD(dia, 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo')")
                ->orderBy('hora_inicio')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $horarios
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
}