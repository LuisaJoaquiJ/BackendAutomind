<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AvisosAdminController extends Controller
{
    /**
     * Obtener todos los avisos
     */
    public function obtenerTodos(Request $request)
    {
        try {
            $prioridad = $request->query('prioridad', null);
            $limit = $request->query('limit', 50);
            $page = $request->query('page', 1);
            $curso_id = $request->query('curso_id', null);

            $query = DB::table('avisos')
                ->join('users', 'avisos.autor_id', '=', 'users.id')
                ->select(
                    'avisos.*',
                    'users.name as autor'
                );

            if ($prioridad) {
                $query->where('avisos.prioridad', $prioridad);
            }

            if ($curso_id) {
                $query->where('avisos.curso_id', $curso_id);
            }

            $total = $query->count();
            $offset = ($page - 1) * $limit;

            $avisos = $query->orderBy('avisos.created_at', 'desc')
                ->offset($offset)
                ->limit($limit)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $avisos,
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
     * Crear nuevo aviso
     */
    public function crearAviso(Request $request)
    {
        try {
            $request->validate([
                'titulo' => 'required|string|max:255',
                'contenido' => 'required|string',
                'autor_id' => 'required|integer',
                'curso_id' => 'nullable|integer',
                'prioridad' => 'required|in:baja,media,alta'
            ]);

            $avisoId = DB::table('avisos')->insertGetId([
                'titulo' => $request->titulo,
                'contenido' => $request->contenido,
                'autor_id' => $request->autor_id,
                'curso_id' => $request->curso_id,
                'prioridad' => $request->prioridad,
                'leido' => 0,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            $aviso = DB::table('avisos')
                ->join('users', 'avisos.autor_id', '=', 'users.id')
                ->where('avisos.id', $avisoId)
                ->select('avisos.*', 'users.name as autor')
                ->first();

            return response()->json([
                'success' => true,
                'message' => 'Aviso creado correctamente',
                'data' => $aviso
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar aviso
     */
    public function actualizarAviso(Request $request, $avisoId)
    {
        try {
            $aviso = DB::table('avisos')->where('id', $avisoId)->first();

            if (!$aviso) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aviso no encontrado'
                ], 404);
            }

            $request->validate([
                'titulo' => 'nullable|string|max:255',
                'contenido' => 'nullable|string',
                'prioridad' => 'nullable|in:baja,media,alta'
            ]);

            $datos = [];
            if ($request->filled('titulo')) $datos['titulo'] = $request->titulo;
            if ($request->filled('contenido')) $datos['contenido'] = $request->contenido;
            if ($request->filled('prioridad')) $datos['prioridad'] = $request->prioridad;
            $datos['updated_at'] = now();

            DB::table('avisos')
                ->where('id', $avisoId)
                ->update($datos);

            $avisoActualizado = DB::table('avisos')
                ->join('users', 'avisos.autor_id', '=', 'users.id')
                ->where('avisos.id', $avisoId)
                ->select('avisos.*', 'users.name as autor')
                ->first();

            return response()->json([
                'success' => true,
                'message' => 'Aviso actualizado correctamente',
                'data' => $avisoActualizado
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar aviso
     */
    public function eliminarAviso($avisoId)
    {
        try {
            $aviso = DB::table('avisos')->where('id', $avisoId)->first();

            if (!$aviso) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aviso no encontrado'
                ], 404);
            }

            DB::table('avisos')->where('id', $avisoId)->delete();

            return response()->json([
                'success' => true,
                'message' => 'Aviso eliminado correctamente'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
}

/**
 * CONTROLADOR PARA GESTIONAR PAGOS
 */
