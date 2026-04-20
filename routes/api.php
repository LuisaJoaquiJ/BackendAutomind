<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AcademicController;
use App\Http\Controllers\HorarioController;
use App\Http\Controllers\MateriaController;
use App\Http\Controllers\NotaController;
use App\Http\Controllers\SolicitudController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\UsuariosAdminController;
use App\Http\Controllers\AvisosAdminController;
use App\Http\Controllers\PagosAdminController;
use App\Http\Controllers\ContenidosAdminController;
use App\Http\Controllers\CalificacionesAdminController;
 
// ===== AUTH =====
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);

    // ===== USER =====
    Route::get('/user', [AcademicController::class, 'getUser']);

    // ===== HORARIOS =====
    Route::get('/horarios', [HorarioController::class, 'index']);
    Route::get('/horarios/{id}', [AcademicController::class, 'getHorarioById']);

    // ===== NOTAS =====
    Route::get('/notas', [NotaController::class, 'index']);
    Route::post('/notas', [NotaController::class, 'store']);
    
    // ===== MATERIAS =====
    Route::get('/materias', [MateriaController::class, 'index']);
    Route::post('/materias', [MateriaController::class, 'store']);

    // ===== PAGOS =====
    Route::get('/pagos', [AcademicController::class, 'getPagos']);
    Route::get('/pagos/{id}', [AcademicController::class, 'getPagosById']);

    // ===== AVISOS =====
    Route::get('/avisos', [AcademicController::class, 'getAvisos']);
    Route::get('/avisos/{id}', [AcademicController::class, 'getAvisosById']);

    // ===== SOLICITUDES =====
    Route::get('/solicitudes', [SolicitudController::class, 'index']);
    Route::post('/solicitudes', [SolicitudController::class, 'store']);
    Route::put('/solicitudes/{id}', [SolicitudController::class, 'updateEstado']);

});

Route::prefix('admin')->group(function () {
 
    // ============================================
    // DASHBOARD Y GENERAL
    // ============================================
    Route::get('/dashboard', [AdminController::class, 'dashboard']);
    Route::get('/reportes/academicos', [AdminController::class, 'reportesAcademicos']);
 
    // ============================================
    // GESTIÓN DE SOLICITUDES
    // ============================================
    Route::get('/solicitudes', [AdminController::class, 'obtenerSolicitudes']);
    Route::get('/solicitudes/resumen', [AdminController::class, 'resumenSolicitudes']);
    Route::get('/solicitudes/{id}', [AdminController::class, 'obtenerDetallesSolicitud']);
    Route::put('/solicitudes/{id}/estado', [AdminController::class, 'cambiarEstadoSolicitud']);
 
    // ============================================
    // GESTIÓN DE USUARIOS
    // ============================================
    Route::prefix('usuarios')->group(function () {
        Route::get('/', [UsuariosAdminController::class, 'obtenerTodos']);
        Route::get('/programas', [UsuariosAdminController::class, 'obtenerProgramas']);
        Route::post('/', [UsuariosAdminController::class, 'crearUsuario']);
        Route::get('/{id}', [UsuariosAdminController::class, 'obtenerDetalle']);
        Route::put('/{id}', [UsuariosAdminController::class, 'actualizarUsuario']);
        Route::put('/{id}/rol', [UsuariosAdminController::class, 'cambiarRol']);
        Route::put('/{id}/estado', [UsuariosAdminController::class, 'cambiarEstado']);
        Route::delete('/{id}', [UsuariosAdminController::class, 'eliminarUsuario']);
    });
 
    // ============================================
    // GESTIÓN DE AVISOS
    // ============================================
    Route::prefix('avisos')->group(function () {
        Route::get('/', [AvisosAdminController::class, 'obtenerTodos']);
        Route::post('/', [AvisosAdminController::class, 'crearAviso']);
        Route::put('/{id}', [AvisosAdminController::class, 'actualizarAviso']);
        Route::delete('/{id}', [AvisosAdminController::class, 'eliminarAviso']);
    });
 
    // ============================================
    // GESTIÓN DE PAGOS
    // ============================================
    Route::prefix('pagos')->group(function () {
        Route::get('/', [PagosAdminController::class, 'obtenerPagos']);
        Route::put('/{id}', [PagosAdminController::class, 'actualizarPago']);
        Route::get('/estadisticas', [PagosAdminController::class, 'estadisticasPagos']);
    });
 
    // ============================================
    // GESTIÓN DE CURSOS Y CONTENIDO
    // ============================================
    Route::prefix('cursos')->group(function () {
        Route::get('/', [ContenidosAdminController::class, 'obtenerCursos']);
        Route::post('/', [ContenidosAdminController::class, 'crearCurso']);
        Route::put('/{id}', [ContenidosAdminController::class, 'actualizarCurso']);
        Route::delete('/{id}', [ContenidosAdminController::class, 'eliminarCurso']);
        
        // Horarios
        Route::get('/{id}/horarios', [ContenidosAdminController::class, 'obtenerHorariosCurso']);
        Route::post('/{id}/horarios', [ContenidosAdminController::class, 'agregarHorario']);
        Route::put('/horarios/{id}', [ContenidosAdminController::class, 'actualizarHorario']);
        Route::delete('/horarios/{id}', [ContenidosAdminController::class, 'eliminarHorario']);
    });
 
    // ============================================
    // GESTIÓN DE CALIFICACIONES Y NOTAS
    // ============================================
    Route::prefix('calificaciones')->group(function () {
        Route::get('/', [CalificacionesAdminController::class, 'obtenerTodas']);
        Route::get('/estudiante/{id}', [CalificacionesAdminController::class, 'obtenerCalificacionesEstudiante']);
        Route::get('/curso/{id}', [CalificacionesAdminController::class, 'obtenerCalificacionesCurso']);
        Route::post('/', [CalificacionesAdminController::class, 'crearCalificacion']);
        Route::put('/{id}', [CalificacionesAdminController::class, 'actualizarCalificacion']);
        Route::delete('/{id}', [CalificacionesAdminController::class, 'eliminarCalificacion']);
    });
 
    // ============================================
    // GESTIÓN DE HORARIOS (Vista general)
    // ============================================
    Route::prefix('horarios')->group(function () {
        Route::get('/estudiante/{id}', [CalificacionesAdminController::class, 'obtenerHorariosEstudiante']);
        Route::get('/curso/{id}', [ContenidosAdminController::class, 'obtenerHorariosCurso']);
    });
 
    // ============================================
    // INSCRIPCIONES
    // ============================================
    Route::prefix('inscripciones')->group(function () {
        Route::get('/', [CalificacionesAdminController::class, 'obtenerInscripciones']);
        Route::get('/curso/{id}', [CalificacionesAdminController::class, 'obtenerInscripcionesCurso']);
        Route::post('/', [CalificacionesAdminController::class, 'crearInscripcion']);
        Route::delete('/{id}', [CalificacionesAdminController::class, 'eliminarInscripcion']);
    });
});