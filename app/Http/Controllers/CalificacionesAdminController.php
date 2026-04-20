<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CalificacionesAdminController extends Controller
{
    /**
     * Obtener todas las calificaciones
     */
    public function obtenerTodas(Request $request)
    {
        try {
            $limit = $request->query('limit', 50);
            $page = $request->query('page', 1);
            $estudiante_id = $request->query('estudiante_id', null);
            $curso_id = $request->query('curso_id', null);

            $query = DB::table('calificaciones')
                ->join('users', 'calificaciones.estudiante_id', '=', 'users.id')
                ->join('cursos', 'calificaciones.curso_id', '=', 'cursos.id')
                ->select(
                    'calificaciones.*',
                    'users.name as estudiante',
                    'cursos.nombre as curso',
                    'cursos.codigo as codigo_curso'
                );

            if ($estudiante_id) {
                $query->where('calificaciones.estudiante_id', $estudiante_id);
            }

            if ($curso_id) {
                $query->where('calificaciones.curso_id', $curso_id);
            }

            $total = $query->count();
            $offset = ($page - 1) * $limit;

            $calificaciones = $query->orderBy('calificaciones.fecha_calificacion', 'desc')
                ->offset($offset)
                ->limit($limit)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $calificaciones,
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
     * Obtener calificaciones de un estudiante
     */
    public function obtenerCalificacionesEstudiante($estudianteId)
    {
        try {
            $calificaciones = DB::table('calificaciones')
                ->join('cursos', 'calificaciones.curso_id', '=', 'cursos.id')
                ->where('calificaciones.estudiante_id', $estudianteId)
                ->select(
                    'calificaciones.*',
                    'cursos.nombre as curso',
                    'cursos.codigo as codigo_curso'
                )
                ->orderBy('calificaciones.fecha_calificacion', 'desc')
                ->get();

            // Calcular promedio por curso
            $promedios = DB::table('calificaciones')
                ->where('estudiante_id', $estudianteId)
                ->select(
                    'curso_id',
                    DB::raw('ROUND(SUM(valor * peso) / SUM(peso), 2) as promedio')
                )
                ->groupBy('curso_id')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'calificaciones' => $calificaciones,
                    'promedios_por_curso' => $promedios
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
     * Obtener calificaciones de un curso
     */
    public function obtenerCalificacionesCurso($cursoId)
    {
        try {
            $calificaciones = DB::table('calificaciones')
                ->join('users', 'calificaciones.estudiante_id', '=', 'users.id')
                ->where('calificaciones.curso_id', $cursoId)
                ->select(
                    'calificaciones.*',
                    'users.name as estudiante',
                    'users.documento'
                )
                ->orderBy('users.name', 'asc')
                ->get();

            // Estadísticas del curso
            $estadisticas = DB::table('calificaciones')
                ->where('curso_id', $cursoId)
                ->selectRaw('
                    COUNT(*) as total_calificaciones,
                    ROUND(AVG(valor), 2) as promedio_curso,
                    MIN(valor) as nota_minima,
                    MAX(valor) as nota_maxima,
                    SUM(CASE WHEN valor >= 3.0 THEN 1 ELSE 0 END) as aprobadas,
                    SUM(CASE WHEN valor < 3.0 THEN 1 ELSE 0 END) as reprobadas
                ')
                ->first();

            return response()->json([
                'success' => true,
                'data' => [
                    'calificaciones' => $calificaciones,
                    'estadisticas' => $estadisticas
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
     * Crear nueva calificación
     */
    public function crearCalificacion(Request $request)
    {
        try {
            $request->validate([
                'estudiante_id' => 'required|integer',
                'curso_id' => 'required|integer',
                'tipo' => 'required|in:examen,tarea,participacion,proyecto,taller',
                'valor' => 'required|numeric|between:0,5',
                'peso' => 'nullable|numeric|between:0,2',
                'descripcion' => 'nullable|string',
                'fecha_calificacion' => 'nullable|date'
            ]);

            // Verificar inscripción
            $inscripcion = DB::table('inscripciones')
                ->where('estudiante_id', $request->estudiante_id)
                ->where('curso_id', $request->curso_id)
                ->first();

            if (!$inscripcion) {
                return response()->json([
                    'success' => false,
                    'message' => 'El estudiante no está inscrito en este curso'
                ], 404);
            }

            $calificacionId = DB::table('calificaciones')->insertGetId([
                'estudiante_id' => $request->estudiante_id,
                'curso_id' => $request->curso_id,
                'tipo' => $request->tipo,
                'valor' => $request->valor,
                'peso' => $request->peso ?? 1.0,
                'descripcion' => $request->descripcion,
                'fecha_calificacion' => $request->fecha_calificacion ?? now(),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            $calificacion = DB::table('calificaciones')
                ->where('id', $calificacionId)
                ->first();

            return response()->json([
                'success' => true,
                'message' => 'Calificación creada correctamente',
                'data' => $calificacion
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar calificación
     */
    public function actualizarCalificacion(Request $request, $calificacionId)
    {
        try {
            $calificacion = DB::table('calificaciones')->where('id', $calificacionId)->first();

            if (!$calificacion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Calificación no encontrada'
                ], 404);
            }

            $request->validate([
                'tipo' => 'nullable|in:examen,tarea,participacion,proyecto,taller',
                'valor' => 'nullable|numeric|between:0,5',
                'peso' => 'nullable|numeric|between:0,2',
                'descripcion' => 'nullable|string',
                'fecha_calificacion' => 'nullable|date'
            ]);

            $datos = [];
            if ($request->filled('tipo')) $datos['tipo'] = $request->tipo;
            if ($request->filled('valor')) $datos['valor'] = $request->valor;
            if ($request->filled('peso')) $datos['peso'] = $request->peso;
            if ($request->filled('descripcion')) $datos['descripcion'] = $request->descripcion;
            if ($request->filled('fecha_calificacion')) $datos['fecha_calificacion'] = $request->fecha_calificacion;
            $datos['updated_at'] = now();

            DB::table('calificaciones')
                ->where('id', $calificacionId)
                ->update($datos);

            $calificacionActualizada = DB::table('calificaciones')
                ->where('id', $calificacionId)
                ->first();

            return response()->json([
                'success' => true,
                'message' => 'Calificación actualizada correctamente',
                'data' => $calificacionActualizada
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar calificación
     */
    public function eliminarCalificacion($calificacionId)
    {
        try {
            $calificacion = DB::table('calificaciones')->where('id', $calificacionId)->first();

            if (!$calificacion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Calificación no encontrada'
                ], 404);
            }

            DB::table('calificaciones')->where('id', $calificacionId)->delete();

            return response()->json([
                'success' => true,
                'message' => 'Calificación eliminada correctamente'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener horarios de un estudiante
     */
    public function obtenerHorariosEstudiante($estudianteId)
    {
        try {
            $horarios = DB::table('inscripciones')
                ->join('cursos', 'inscripciones.curso_id', '=', 'cursos.id')
                ->join('horarios', 'horarios.curso_id', '=', 'cursos.id')
                ->where('inscripciones.estudiante_id', $estudianteId)
                ->where('inscripciones.estado', 'activa')
                ->select(
                    'cursos.id as curso_id',
                    'cursos.nombre as curso',
                    'cursos.codigo',
                    'horarios.dia',
                    'horarios.hora_inicio',
                    'horarios.hora_fin',
                    'horarios.salon'
                )
                ->orderByRaw("FIELD(horarios.dia, 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo')")
                ->orderBy('horarios.hora_inicio')
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

    /**
     * Obtener inscripciones
     */
    public function obtenerInscripciones(Request $request)
    {
        try {
            $limit = $request->query('limit', 50);
            $page = $request->query('page', 1);
            $estado = $request->query('estado', null);

            $query = DB::table('inscripciones')
                ->join('users', 'inscripciones.estudiante_id', '=', 'users.id')
                ->join('cursos', 'inscripciones.curso_id', '=', 'cursos.id')
                ->select(
                    'inscripciones.*',
                    'users.name as estudiante',
                    'cursos.nombre as curso',
                    'cursos.codigo'
                );

            if ($estado) {
                $query->where('inscripciones.estado', $estado);
            }

            $total = $query->count();
            $offset = ($page - 1) * $limit;

            $inscripciones = $query->orderBy('inscripciones.created_at', 'desc')
                ->offset($offset)
                ->limit($limit)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $inscripciones,
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
     * Obtener inscripciones de un curso
     */
    public function obtenerInscripcionesCurso($cursoId)
    {
        try {
            $inscripciones = DB::table('inscripciones')
                ->join('users', 'inscripciones.estudiante_id', '=', 'users.id')
                ->where('inscripciones.curso_id', $cursoId)
                ->select(
                    'inscripciones.id',
                    'inscripciones.estado',
                    'inscripciones.fecha_inscripcion',
                    'users.id as estudiante_id',
                    'users.name as estudiante',
                    'users.documento',
                    'users.programa'
                )
                ->orderBy('users.name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $inscripciones
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear inscripción
     */
    public function crearInscripcion(Request $request)
    {
        try {
            $request->validate([
                'estudiante_id' => 'required|integer',
                'curso_id' => 'required|integer'
            ]);

            // Verificar que no exista inscripción duplicada
            $existe = DB::table('inscripciones')
                ->where('estudiante_id', $request->estudiante_id)
                ->where('curso_id', $request->curso_id)
                ->first();

            if ($existe) {
                return response()->json([
                    'success' => false,
                    'message' => 'El estudiante ya está inscrito en este curso'
                ], 409);
            }

            $inscripcionId = DB::table('inscripciones')->insertGetId([
                'estudiante_id' => $request->estudiante_id,
                'curso_id' => $request->curso_id,
                'estado' => 'activa',
                'fecha_inscripcion' => now(),
                'created_at' => now()
            ]);

            $inscripcion = DB::table('inscripciones')
                ->where('id', $inscripcionId)
                ->first();

            return response()->json([
                'success' => true,
                'message' => 'Inscripción creada correctamente',
                'data' => $inscripcion
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar inscripción
     */
    public function eliminarInscripcion($inscripcionId)
    {
        try {
            $inscripcion = DB::table('inscripciones')->where('id', $inscripcionId)->first();

            if (!$inscripcion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Inscripción no encontrada'
                ], 404);
            }

            DB::table('inscripciones')
                ->where('id', $inscripcionId)
                ->update([
                    'estado' => 'retirada',
                    'updated_at' => now()
                ]);

            return response()->json([
                'success' => true,
                'message' => 'Inscripción retirada correctamente'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
}