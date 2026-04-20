<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HorarioController extends Controller
{
    public function index(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado'
                ], 401);
            }

            // 🔥 Cargar materias con horarios
            $materias = $user->materias()->with('horarios')->get();

            $horarios = [];

            foreach ($materias as $materia) {
                foreach ($materia->horarios as $h) {
                    $horarios[] = [
                        'dia'        => $h->dia,
                        'curso'      => $materia->nombre,
                        'profesor'   => $materia->docente,
                        'horaInicio' => $h->hora_inicio,
                        'horaFin'    => $h->hora_fin,
                        'salon'      => $h->salon,
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'data'    => $horarios,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}