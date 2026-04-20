<?php

namespace App\Http\Controllers;

use App\Models\Materia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
}