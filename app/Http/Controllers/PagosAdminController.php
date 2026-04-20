<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PagosAdminController extends Controller
{
    /**
     * Obtener registros de pagos (desde solicitudes)
     */
    public function obtenerPagos(Request $request)
    {
        try {
            $limit = $request->query('limit', 50);
            $page = $request->query('page', 1);
            $estado = $request->query('estado', null);

            $query = DB::table('solicitudes')
                ->join('users', 'solicitudes.estudiante_id', '=', 'users.id')
                ->where('solicitudes.tipo', 'pago_aranceles')
                ->select(
                    'solicitudes.id',
                    'solicitudes.descripcion',
                    'solicitudes.estado',
                    'solicitudes.respuesta',
                    'solicitudes.created_at',
                    'users.id as estudiante_id',
                    'users.name as estudiante',
                    'users.email',
                    'users.programa'
                );

            if ($estado) {
                $query->where('solicitudes.estado', $estado);
            }

            $total = $query->count();
            $offset = ($page - 1) * $limit;

            $pagos = $query->orderBy('solicitudes.created_at', 'desc')
                ->offset($offset)
                ->limit($limit)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $pagos,
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
     * Actualizar estado de pago
     */
    public function actualizarPago(Request $request, $pagoId)
    {
        try {
            $request->validate([
                'estado' => 'required|in:pendiente,aprobada,rechazada',
                'respuesta' => 'nullable|string'
            ]);

            $actualizado = DB::table('solicitudes')
                ->where('id', $pagoId)
                ->update([
                    'estado' => $request->estado,
                    'respuesta' => $request->respuesta ?? null,
                    'updated_at' => now()
                ]);

            if ($actualizado) {
                $pago = DB::table('solicitudes')
                    ->where('id', $pagoId)
                    ->first();

                return response()->json([
                    'success' => true,
                    'message' => 'Pago actualizado correctamente',
                    'data' => $pago
                ], 200);
            }

            return response()->json([
                'success' => false,
                'message' => 'Pago no encontrado'
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener estadísticas de pagos
     */
    public function estadisticasPagos()
    {
        try {
            $totalPagos = DB::table('solicitudes')
                ->where('tipo', 'pago_aranceles')
                ->count();

            $pagosAprobados = DB::table('solicitudes')
                ->where('tipo', 'pago_aranceles')
                ->where('estado', 'aprobada')
                ->count();

            $pagosPendientes = DB::table('solicitudes')
                ->where('tipo', 'pago_aranceles')
                ->where('estado', 'pendiente')
                ->count();

            $pagosRechazados = DB::table('solicitudes')
                ->where('tipo', 'pago_aranceles')
                ->where('estado', 'rechazada')
                ->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'total_pagos' => $totalPagos,
                    'pagos_aprobados' => $pagosAprobados,
                    'pagos_pendientes' => $pagosPendientes,
                    'pagos_rechazados' => $pagosRechazados,
                    'porcentaje_aprobacion' => $totalPagos > 0 
                        ? round(($pagosAprobados / $totalPagos) * 100, 2) 
                        : 0
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
}