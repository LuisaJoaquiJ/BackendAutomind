<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdminController extends Controller
{
    /**
     * Dashboard - Estadísticas generales
     */
    public function dashboard()
    {
        try {
            $totalEstudiantes = DB::table('users')
                ->where('rol', 'estudiante')
                ->where('activo', 1)
                ->count();

            $totalProfesores = DB::table('users')
                ->where('rol', 'profesor')
                ->where('activo', 1)
                ->count();

            $totalCursos = Schema::hasTable('cursos')
                ? DB::table('cursos')->count()
                : (Schema::hasTable('materias') ? DB::table('materias')->count() : 0);

            $solicitudesPendientes = DB::table('solicitudes')
                ->where('estado', 'pendiente')
                ->count();

            $totalAvisos = Schema::hasTable('avisos') ? DB::table('avisos')->count() : 0;

            $promedioGeneral = DB::table('calificaciones')
                ->selectRaw('ROUND(AVG((COALESCE(corte1, 0) * 0.30) + (COALESCE(corte2, 0) * 0.30) + (COALESCE(corte3, 0) * 0.40)), 2) as promedio')
                ->first();

            return response()->json([
                'success' => true,
                'data' => [
                    'estadisticas' => [
                        'total_estudiantes' => $totalEstudiantes,
                        'total_profesores' => $totalProfesores,
                        'total_cursos' => $totalCursos,
                        'solicitudes_pendientes' => $solicitudesPendientes,
                        'total_avisos' => $totalAvisos,
                        'promedio_general' => $promedioGeneral->promedio ?? 0
                    ]
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
     * Obtener todas las solicitudes
     */
    public function obtenerSolicitudes(Request $request)
    {
        try {
            $estado = $request->query('estado', null);
            $tipo = $request->query('tipo', null);
            $limit = $request->query('limit', 50);
            $page = $request->query('page', 1);

            $query = DB::table('solicitudes')
                ->join('users', 'solicitudes.user_id', '=', 'users.id')
                ->select(
                    'solicitudes.id',
                    'solicitudes.tipo',
                    'solicitudes.descripcion',
                    'solicitudes.estado',
                    DB::raw('NULL as respuesta'),
                    'solicitudes.created_at',
                    'users.id as estudiante_id',
                    'users.name as estudiante',
                    'users.email',
                    'users.programa'
                );

            if ($estado) {
                $query->where('solicitudes.estado', $estado);
            }

            if ($tipo) {
                $query->where('solicitudes.tipo', $tipo);
            }

            $total = $query->count();
            $offset = ($page - 1) * $limit;

            $solicitudes = $query->orderBy('solicitudes.created_at', 'desc')
                ->offset($offset)
                ->limit($limit)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $solicitudes,
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
     * Cambiar estado de una solicitud
     */
    public function cambiarEstadoSolicitud(Request $request, $solicitudId)
    {
        try {
            $request->validate([
                'estado' => 'required|in:pendiente,aprobado,rechazado,entregado',
            ]);

            $actualizado = DB::table('solicitudes')
                ->where('id', $solicitudId)
                ->update([
                    'estado' => $request->estado,
                    'updated_at' => now()
                ]);

            if ($actualizado) {
                $solicitud = DB::table('solicitudes')
                    ->where('id', $solicitudId)
                    ->first();

                return response()->json([
                    'success' => true,
                    'message' => 'Solicitud actualizada correctamente',
                    'data' => $solicitud
                ], 200);
            }

            return response()->json([
                'success' => false,
                'message' => 'Solicitud no encontrada'
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener detalles de una solicitud
     */
    public function obtenerDetallesSolicitud($solicitudId)
    {
        try {
            $solicitud = DB::table('solicitudes')
                ->join('users', 'solicitudes.user_id', '=', 'users.id')
                ->where('solicitudes.id', $solicitudId)
                ->select(
                    'solicitudes.*',
                    'users.name as estudiante',
                    'users.email',
                    'users.programa',
                    'users.semestre',
                    'users.documento'
                )
                ->first();

            if (!$solicitud) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solicitud no encontrada'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $solicitud
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener resumen de solicitudes por tipo
     */
    public function resumenSolicitudes()
    {
        try {
            $resumen = DB::table('solicitudes')
                ->select(
                    'tipo',
                    'estado',
                    DB::raw('COUNT(*) as total')
                )
                ->groupBy('tipo', 'estado')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $resumen
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener reportes académicos
     */
    public function reportesAcademicos()
    {
        try {
            $reportes = [
                'estudiantes_por_programa' => DB::table('users')
                    ->where('rol', 'estudiante')
                    ->where('activo', 1)
                    ->select('programa', DB::raw('COUNT(*) as total'))
                    ->groupBy('programa')
                    ->get(),
                
                'estudiantes_por_semestre' => DB::table('users')
                    ->where('rol', 'estudiante')
                    ->where('activo', 1)
                    ->select('semestre', DB::raw('COUNT(*) as total'))
                    ->groupBy('semestre')
                    ->orderBy('semestre')
                    ->get(),
                
                'tasa_aprobacion' => DB::table('calificaciones')
                    ->selectRaw('
                        COUNT(*) as total_calificaciones,
                        SUM(CASE WHEN ((COALESCE(corte1, 0) * 0.30) + (COALESCE(corte2, 0) * 0.30) + (COALESCE(corte3, 0) * 0.40)) >= 3.0 THEN 1 ELSE 0 END) as aprobadas,
                        ROUND(SUM(CASE WHEN ((COALESCE(corte1, 0) * 0.30) + (COALESCE(corte2, 0) * 0.30) + (COALESCE(corte3, 0) * 0.40)) >= 3.0 THEN 1 ELSE 0 END) * 100 / COUNT(*), 2) as porcentaje_aprobacion
                    ')
                    ->first(),
                
                'cursos_por_semestre' => collect()
            ];

            return response()->json([
                'success' => true,
                'data' => $reportes
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
}
