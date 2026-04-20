<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AcademicController extends Controller
{
    public function getUser()
    {
        $user = DB::table('users')->first();

        return response()->json([
            'success' => true,
            'data' => $user
        ]);
    }

    public function getHorarios()
    {
        $data = DB::table('horarios')->get();

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    public function getMaterias()
    {
        $data = DB::table('cursos')->get();

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    public function getNotas(Request $request)
{
    $data = DB::table('calificaciones')
        ->where('user_id', $request->user()->id)
        ->get()
        ->map(function ($item) {
            // Promedio ponderado: 30% + 30% + 40%
            $c1 = $item->corte1;
            $c2 = $item->corte2;
            $c3 = $item->corte3;

            $promedio = null;
            if ($c1 !== null && $c2 !== null && $c3 !== null) {
                $promedio = ($c1 * 0.30) + ($c2 * 0.30) + ($c3 * 0.40);
            }

            return [
                'id'       => $item->id,
                'materia'  => $item->materia,
                'corte1'   => $c1,
                'corte2'   => $c2,
                'corte3'   => $c3,
                'promedio' => $promedio ? round($promedio, 1) : null,
            ];
        });

    return response()->json([
        'success' => true,
        'data'    => $data,
    ]);
}

    public function getPagos()
    {
        $data = DB::table('pagos')->get();

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    public function getAvisos()
    {
        $data = DB::table('avisos')->get();

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    public function getSolicitudes()
    {
        $data = DB::table('solicitudes')->get();

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    public function crearSolicitud(Request $request)
    {
        DB::table('solicitudes')->insert([
            'tipo' => $request->tipo,
            'descripcion' => $request->descripcion,
            'estado' => 'pendiente',
            'created_at' => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Solicitud creada'
        ]);
    }
}