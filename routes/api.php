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
use App\Http\Controllers\MateriaActividadController;
use App\Http\Controllers\MateriaContenidoController;
use App\Http\Controllers\DocenteController;


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
    Route::get('/materias/{materia}/pantalla-dinamica',          [MateriaContenidoController::class, 'vistaEstudiante']);
    Route::get('/materias/{materia}/contenidos-dinamicos',       [MateriaContenidoController::class, 'index']);
    Route::post('/materias/{materia}/contenidos-dinamicos',      [MateriaContenidoController::class, 'store']);
    Route::put('/materias/{materia}/contenidos-dinamicos/{contenido}',    [MateriaContenidoController::class, 'update']);
    Route::delete('/materias/{materia}/contenidos-dinamicos/{contenido}', [MateriaContenidoController::class, 'destroy']);
    Route::get('/materias/{materia}/actividades',                         [MateriaActividadController::class, 'index']);
    Route::post('/materias/{materia}/actividades',                        [MateriaActividadController::class, 'store']);
    Route::post('/materias/{materia}/actividades/{actividad}/respuestas', [MateriaActividadController::class, 'responder']);
    Route::post('/materias/{materia}/retos/generar',                      [MateriaActividadController::class, 'generarReto']);

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


// ============================================
// ADMIN
// ============================================
Route::prefix('admin')->middleware('auth:sanctum')->group(function () {

    // GESTIÓN DE MATERIAS
    Route::prefix('materias')->group(function () {
        Route::get('/',        [MateriaController::class, 'adminIndex']);
        Route::post('/',       [MateriaController::class, 'adminStore']);
        Route::put('/{id}',    [MateriaController::class, 'adminUpdate']);
        Route::delete('/{id}', [MateriaController::class, 'adminDestroy']);
    });

    // DASHBOARD Y GENERAL
    Route::get('/dashboard', [AdminController::class, 'dashboard']);
    Route::get('/reportes/academicos', [AdminController::class, 'reportesAcademicos']);

    // GESTIÓN DE SOLICITUDES
    Route::get('/solicitudes',             [AdminController::class, 'obtenerSolicitudes']);
    Route::get('/solicitudes/resumen',     [AdminController::class, 'resumenSolicitudes']);
    Route::get('/solicitudes/{id}',        [AdminController::class, 'obtenerDetallesSolicitud']);
    Route::put('/solicitudes/{id}/estado', [AdminController::class, 'cambiarEstadoSolicitud']);

    // GESTIÓN DE USUARIOS
    Route::prefix('usuarios')->group(function () {
        Route::get('/',            [UsuariosAdminController::class, 'obtenerTodos']);
        Route::get('/programas',   [UsuariosAdminController::class, 'obtenerProgramas']);
        Route::post('/',           [UsuariosAdminController::class, 'crearUsuario']);
        Route::get('/{id}',        [UsuariosAdminController::class, 'obtenerDetalle']);
        Route::put('/{id}',        [UsuariosAdminController::class, 'actualizarUsuario']);
        Route::put('/{id}/rol',    [UsuariosAdminController::class, 'cambiarRol']);
        Route::put('/{id}/estado', [UsuariosAdminController::class, 'cambiarEstado']);
        Route::delete('/{id}',     [UsuariosAdminController::class, 'eliminarUsuario']);
    });

    // GESTIÓN DE AVISOS
    Route::prefix('avisos')->group(function () {
        Route::get('/',        [AvisosAdminController::class, 'obtenerTodos']);
        Route::post('/',       [AvisosAdminController::class, 'crearAviso']);
        Route::put('/{id}',    [AvisosAdminController::class, 'actualizarAviso']);
        Route::delete('/{id}', [AvisosAdminController::class, 'eliminarAviso']);
    });

    // GESTIÓN DE PAGOS
    Route::prefix('pagos')->group(function () {
        Route::get('/',             [PagosAdminController::class, 'obtenerPagos']);
        Route::put('/{id}',         [PagosAdminController::class, 'actualizarPago']);
        Route::get('/estadisticas', [PagosAdminController::class, 'estadisticasPagos']);
    });

    // HORARIOS (dentro de admin)
    Route::get('/{id}/horarios',    [ContenidosAdminController::class, 'obtenerHorariosCurso']);
    Route::post('/{id}/horarios',   [ContenidosAdminController::class, 'agregarHorario']);
    Route::put('/horarios/{id}',    [ContenidosAdminController::class, 'actualizarHorario']);
    Route::delete('/horarios/{id}', [ContenidosAdminController::class, 'eliminarHorario']);

    // GESTIÓN DE CALIFICACIONES Y NOTAS
    Route::prefix('calificaciones')->group(function () {
        Route::get('/',                [CalificacionesAdminController::class, 'obtenerTodas']);
        Route::get('/estudiante/{id}', [CalificacionesAdminController::class, 'obtenerCalificacionesEstudiante']);
        Route::get('/materias/{id}',   [CalificacionesAdminController::class, 'obtenerCalificacionesCurso']);
        Route::post('/',               [CalificacionesAdminController::class, 'crearCalificacion']);
        Route::put('/{id}',            [CalificacionesAdminController::class, 'actualizarCalificacion']);
        Route::delete('/{id}',         [CalificacionesAdminController::class, 'eliminarCalificacion']);
    });

    // GESTIÓN DE HORARIOS (Vista general)
    Route::prefix('horarios')->group(function () {
        Route::get('/estudiante/{id}', [CalificacionesAdminController::class, 'obtenerHorariosEstudiante']);
        Route::get('/curso/{id}',      [ContenidosAdminController::class, 'obtenerHorariosCurso']);
    });

    // INSCRIPCIONES
    Route::prefix('inscripciones')->group(function () {
        Route::get('/',           [CalificacionesAdminController::class, 'obtenerInscripciones']);
        Route::get('/curso/{id}', [CalificacionesAdminController::class, 'obtenerInscripcionesCurso']);
        Route::post('/',          [CalificacionesAdminController::class, 'crearInscripcion']);
        Route::delete('/{id}',    [CalificacionesAdminController::class, 'eliminarInscripcion']);
    });

});


// ============================================
// DOCENTE
// ============================================
Route::prefix('docente')->middleware('auth:sanctum')->group(function () {

    Route::get('/dashboard',                               [DocenteController::class, 'dashboard']);
    Route::get('/materias',                                [DocenteController::class, 'materias']);
    Route::get('/materias/{materiaId}/estudiantes',        [DocenteController::class, 'estudiantes']);
    Route::get('/materias/{materiaId}/notas',              [DocenteController::class, 'notas']);
    Route::post('/materias/{materiaId}/notas',             [DocenteController::class, 'registrarNota']);
    Route::put('/notas/{notaId}',                          [DocenteController::class, 'actualizarNota']);
    Route::get('/horarios',                                [DocenteController::class, 'horarios']);
    Route::get('/avisos',                                  [DocenteController::class, 'avisos']);
    Route::post('/avisos',                                 [DocenteController::class, 'crearAviso']);
    Route::delete('/avisos/{id}',                          [DocenteController::class, 'eliminarAviso']);

    // ── Contenido de materia ──────────────────────────────────────────────────
    // Se usa {materia} (no {materiaId}) para que Laravel haga model binding
    // automático igual que las rutas de admin y el método destroy pueda
    // recibirlo de forma consistente.
    Route::get('/materias/{materia}/contenido',            [MateriaContenidoController::class, 'index']);
    Route::post('/materias/{materia}/contenido',           [MateriaContenidoController::class, 'store']);
    Route::post('/materias/{materia}/contenido/generar',   [MateriaContenidoController::class, 'generar']);
    // {contenido} en lugar de {id} para que Laravel resuelva MateriaContenido automáticamente
    Route::delete('/contenido/{contenido}',                [MateriaContenidoController::class, 'destroy']);

});