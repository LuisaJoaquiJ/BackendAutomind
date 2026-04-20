<?php

namespace App\Http\Controllers;

use App\Models\Materia;
use Illuminate\Http\Request;

class MateriaController extends Controller
{
    // 🔹 LISTAR MATERIAS
    public function index(Request $request)
    {
        $materias = Materia::where('user_id', $request->user()->id)->get();

        return response()->json([
            'success' => true,
            'data' => $materias,
            'total_creditos' => $materias->sum('creditos')
        ]);
    }

    // 🔹 CREAR MATERIA
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required',
            'codigo' => 'required',
            'creditos' => 'required|integer',
            'docente' => 'required',
            'horario' => 'required',
            'sala' => 'required',
        ]);

        $materia = Materia::create([
            'user_id' => $request->user()->id,
            'nombre' => $request->nombre,
            'codigo' => $request->codigo,
            'creditos' => $request->creditos,
            'docente' => $request->docente,
            'horario' => $request->horario,
            'sala' => $request->sala,
        ]);

        return response()->json([
            'success' => true,
            'data' => $materia
        ]);
    }
}