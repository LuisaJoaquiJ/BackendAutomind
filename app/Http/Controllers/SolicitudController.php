<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Solicitud;

class SolicitudController extends Controller
{
    // 📄 listar solicitudes del usuario
    public function index(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => Solicitud::where('user_id', $request->user()->id)->get()
        ]);
    }

    // ➕ crear solicitud
    public function store(Request $request)
    {
        $request->validate([
            'tipo' => 'required|in:paz_y_salvo,certificado,constancia',
            'descripcion' => 'nullable'
        ]);

        $solicitud = Solicitud::create([
            'user_id' => $request->user()->id,
            'tipo' => $request->tipo,
            'descripcion' => $request->descripcion,
            'estado' => 'pendiente'
        ]);

        return response()->json([
            'success' => true,
            'data' => $solicitud
        ]);
    }

    // 🔄 cambiar estado (admin)
    public function updateEstado(Request $request, $id)
    {
        $request->validate([
            'estado' => 'required|in:pendiente,aprobado,rechazado,entregado'
        ]);

        $solicitud = Solicitud::findOrFail($id);
        $solicitud->estado = $request->estado;
        $solicitud->save();

        return response()->json([
            'success' => true,
            'message' => 'Estado actualizado'
        ]);
    }
}