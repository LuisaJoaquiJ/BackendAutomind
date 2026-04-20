<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UsuariosAdminController extends Controller
{
    /**
     * Obtener todos los usuarios
     */
    public function obtenerTodos(Request $request)
    {
        try {
            $rol = $request->query('rol', null);
            $activo = $request->query('activo', null);
            $programa = $request->query('programa', null);
            $limit = $request->query('limit', 50);
            $page = $request->query('page', 1);
            $search = $request->query('search', null);

            $query = DB::table('users');

            if ($rol) {
                $query->where('rol', $rol);
            }

            if ($activo !== null) {
                $query->where('activo', $activo);
            }

            if ($programa) {
                $query->where('programa', $programa);
            }

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%$search%")
                      ->orWhere('email', 'like', "%$search%")
                      ->orWhere('documento', 'like', "%$search%");
                });
            }

            $total = $query->count();
            $offset = ($page - 1) * $limit;

            $usuarios = $query->select(
                'id',
                'name',
                'email',
                'documento',
                'telefono',
                'rol',
                'programa',
                'semestre',
                'activo',
                'created_at'
            )
            ->orderBy('created_at', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get();

            return response()->json([
                'success' => true,
                'data' => $usuarios,
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
     * Obtener detalle de un usuario
     */
    public function obtenerDetalle($usuarioId)
    {
        try {
            $usuario = DB::table('users')
                ->where('id', $usuarioId)
                ->first();

            if (!$usuario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ], 404);
            }

            // Si es estudiante, obtener sus cursos y calificaciones
            if ($usuario->rol === 'estudiante') {
                $cursos = DB::table('inscripciones')
                    ->join('cursos', 'inscripciones.curso_id', '=', 'cursos.id')
                    ->where('inscripciones.estudiante_id', $usuarioId)
                    ->select('cursos.id', 'cursos.nombre', 'cursos.codigo', 'inscripciones.estado')
                    ->get();

                $usuario->cursos = $cursos;
            }

            return response()->json([
                'success' => true,
                'data' => $usuario
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear nuevo usuario
     */
    public function crearUsuario(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:6',
                'documento' => 'nullable|string|unique:users,documento',
                'telefono' => 'nullable|string',
                'rol' => 'required|in:estudiante,profesor,admin',
                'programa' => 'nullable|string',
                'semestre' => 'nullable|integer|between:1,10'
            ]);

            $usuarioId = DB::table('users')->insertGetId([
                'name' => $request->name,
                'email' => $request->email,
                'password' => $request->password,
                'documento' => $request->documento,
                'telefono' => $request->telefono,
                'rol' => $request->rol,
                'programa' => $request->programa,
                'semestre' => $request->semestre,
                'activo' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            $usuario = DB::table('users')
                ->where('id', $usuarioId)
                ->first();

            return response()->json([
                'success' => true,
                'message' => 'Usuario creado correctamente',
                'data' => $usuario
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar usuario
     */
    public function actualizarUsuario(Request $request, $usuarioId)
    {
        try {
            $usuario = DB::table('users')->where('id', $usuarioId)->first();

            if (!$usuario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ], 404);
            }

            $request->validate([
                'name' => 'nullable|string|max:255',
                'email' => 'nullable|email|unique:users,email,' . $usuarioId,
                'documento' => 'nullable|string|unique:users,documento,' . $usuarioId,
                'telefono' => 'nullable|string',
                'rol' => 'nullable|in:estudiante,profesor,admin',
                'programa' => 'nullable|string',
                'semestre' => 'nullable|integer|between:1,10',
                'activo' => 'nullable|boolean'
            ]);

            $datos = [];
            if ($request->filled('name')) $datos['name'] = $request->name;
            if ($request->filled('email')) $datos['email'] = $request->email;
            if ($request->filled('documento')) $datos['documento'] = $request->documento;
            if ($request->filled('telefono')) $datos['telefono'] = $request->telefono;
            if ($request->filled('rol')) $datos['rol'] = $request->rol;
            if ($request->filled('programa')) $datos['programa'] = $request->programa;
            if ($request->filled('semestre')) $datos['semestre'] = $request->semestre;
            if ($request->filled('activo')) $datos['activo'] = $request->activo;

            $datos['updated_at'] = now();

            DB::table('users')
                ->where('id', $usuarioId)
                ->update($datos);

            $usuarioActualizado = DB::table('users')
                ->where('id', $usuarioId)
                ->first();

            return response()->json([
                'success' => true,
                'message' => 'Usuario actualizado correctamente',
                'data' => $usuarioActualizado
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cambiar rol de usuario
     */
    public function cambiarRol(Request $request, $usuarioId)
    {
        try {
            $request->validate([
                'rol' => 'required|in:estudiante,profesor,admin'
            ]);

            $usuario = DB::table('users')->where('id', $usuarioId)->first();

            if (!$usuario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ], 404);
            }

            DB::table('users')
                ->where('id', $usuarioId)
                ->update([
                    'rol' => $request->rol,
                    'updated_at' => now()
                ]);

            $usuarioActualizado = DB::table('users')
                ->where('id', $usuarioId)
                ->first();

            return response()->json([
                'success' => true,
                'message' => 'Rol actualizado correctamente',
                'data' => $usuarioActualizado
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Activar/Desactivar usuario
     */
    public function cambiarEstado(Request $request, $usuarioId)
    {
        try {
            $request->validate([
                'activo' => 'required|boolean'
            ]);

            $usuario = DB::table('users')->where('id', $usuarioId)->first();

            if (!$usuario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ], 404);
            }

            DB::table('users')
                ->where('id', $usuarioId)
                ->update([
                    'activo' => $request->activo,
                    'updated_at' => now()
                ]);

            $usuarioActualizado = DB::table('users')
                ->where('id', $usuarioId)
                ->first();

            return response()->json([
                'success' => true,
                'message' => 'Estado del usuario actualizado',
                'data' => $usuarioActualizado
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar usuario (desactivar)
     */
    public function eliminarUsuario($usuarioId)
    {
        try {
            $usuario = DB::table('users')->where('id', $usuarioId)->first();

            if (!$usuario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ], 404);
            }

            if ($usuario->rol === 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar un usuario administrador'
                ], 403);
            }

            DB::table('users')
                ->where('id', $usuarioId)
                ->update([
                    'activo' => 0,
                    'updated_at' => now()
                ]);

            return response()->json([
                'success' => true,
                'message' => 'Usuario desactivado correctamente'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener programas disponibles
     */
    public function obtenerProgramas()
    {
        try {
            $programas = DB::table('users')
                ->where('rol', 'estudiante')
                ->where('activo', 1)
                ->select('programa')
                ->distinct()
                ->pluck('programa');

            return response()->json([
                'success' => true,
                'data' => $programas
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
}