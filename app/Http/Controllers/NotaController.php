<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Nota;

class NotaController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $notas = Nota::with('materia')
            ->where('user_id', $user->id)
            ->get()
            ->map(function ($nota) {
                return [
                    'id' => $nota->id,
                    'materia' => $nota->materia->nombre ?? null,
                    'corte1' => $nota->corte1,
                    'corte2' => $nota->corte2,
                    'corte3' => $nota->corte3,
                    'promedio' => $nota->promedio,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $notas
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required',
            'materia_id' => 'required'
        ]);

        $c1 = $request->corte1 ?? 0;
        $c2 = $request->corte2 ?? 0;
        $c3 = $request->corte3 ?? 0;

        $promedio = ($c1 * 0.3) + ($c2 * 0.3) + ($c3 * 0.4);

        $nota = Nota::create([
            'user_id' => $request->user_id,
            'materia_id' => $request->materia_id,
            'corte1' => $c1,
            'corte2' => $c2,
            'corte3' => $c3,
            'promedio' => $promedio
        ]);

        return response()->json([
            'success' => true,
            'data' => $nota
        ]);
    }
}