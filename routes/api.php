<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\P1_GestionDeSeguridadYAcceso\CU01_GestionDeUsuariosYAutenticacion\AuthController;
use App\Http\Controllers\P1_GestionDeSeguridadYAcceso\CU01_GestionDeUsuariosYAutenticacion\PasswordResetController;

Route::post('/login', [AuthController::class, 'login']);
Route::get('/login', function () {
    return response()->json(['message' => 'Unauthenticated.'], 401);
})->name('login');

// Recuperación de contraseña (público)
Route::post('/forgot-password', [PasswordResetController::class, 'sendCode']);
Route::post('/verify-code', [PasswordResetController::class, 'verifyCode']);
Route::post('/reset-password', [PasswordResetController::class, 'resetPassword']);

// Verificar bloqueo por intentos fallidos (público)
Route::post('/check-lockout', [AuthController::class, 'checkLockoutStatus']);

// Catálogos públicos
Route::get('/public/carreras', function() {
    return response()->json(\Illuminate\Support\Facades\DB::table('carrera')->select('id', 'nombre')->where('estado', 'Activo')->get());
});

Route::get('/public/gestion-activa', function() {
    $gestion = \App\Models\P3_GestionAcademicaBase\GestionAcademica::query()
        ->leftJoin('gestion_cup', 'gestion_academica.id_gestion_cup', '=', 'gestion_cup.id')
        ->select('gestion_academica.año', 'gestion_cup.nombre as cup_nombre')
        ->where('gestion_academica.estado', 'Activo')
        ->first();
        
    if ($gestion) {
        return response()->json(['cup' => $gestion->cup_nombre . ' - ' . $gestion->año]);
    }
    return response()->json(['cup' => 'CUP ' . date('Y')]);
});


use App\Http\Controllers\P1_GestionDeSeguridadYAcceso\CU01_GestionDeUsuariosYAutenticacion\UsuarioController;
use App\Http\Controllers\P1_GestionDeSeguridadYAcceso\CU16_GestionarBitacora\BitacoraController;
use App\Http\Controllers\P2_GestionDePostulantes\CU2_RegistrarPostulante\PostulanteController;
use App\Http\Controllers\P2_GestionDePostulantes\CU3_GestionarRequisitos\RequisitoController;
use App\Http\Controllers\P3_GestionAcademicaBase\CU6_GestionarMaterias\MateriaController;
use App\Http\Controllers\P3_GestionAcademicaBase\CU7_GestionarDocentes\AspiranteDocenteController;
use App\Http\Controllers\P3_GestionAcademicaBase\CU7_GestionarDocentes\DocenteController;
use App\Http\Controllers\P3_GestionAcademicaBase\CU8_GestionarGrupos\GrupoController;
use App\Http\Controllers\P3_GestionAcademicaBase\CU9_GestionarAulas\AulaController;
use App\Http\Controllers\P1_GestionDeSeguridadYAcceso\CU01_GestionDeUsuariosYAutenticacion\RolController;
use App\Http\Controllers\Pendientes\CarreraController;
use App\Http\Controllers\Pendientes\GestionAcademicaController;
use App\Http\Controllers\Reportes\ReportesController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    
    // Reportes IA
    Route::post('/reportes/generar', [ReportesController::class, 'generar']);
    
    // P1 Seguridad y Acceso
    Route::get('/usuarios', [UsuarioController::class, 'index']);
    Route::post('/usuarios', [UsuarioController::class, 'store']);
    Route::put('/usuarios/{id}', [UsuarioController::class, 'update']);
    Route::patch('/usuarios/{id}/toggle-status', [UsuarioController::class, 'toggleStatus']);
    Route::delete('/usuarios/{id}', [UsuarioController::class, 'destroy']);
    
    // Roles
    Route::get('/roles', [RolController::class, 'index']);
    Route::post('/roles', [RolController::class, 'store']);
    Route::put('/roles/{id}', [RolController::class, 'update']);
    Route::delete('/roles/{id}', [RolController::class, 'destroy']);

    // Bitácora
    Route::get('/bitacora', [BitacoraController::class, 'index']);
    Route::get('/bitacora/stats', [BitacoraController::class, 'stats']);

    // P2 Postulantes
    Route::get('/postulantes', [PostulanteController::class, 'index']);
    Route::post('/postulantes', [PostulanteController::class, 'store']);
    Route::put('/postulantes/{id}', [PostulanteController::class, 'update']);
    Route::delete('/postulantes/{id}', [PostulanteController::class, 'destroy']);
    Route::post('/postulantes/{id}/pagar', [PostulanteController::class, 'pagar']);
    Route::get('/postulantes-pago', [PostulanteController::class, 'getPendientesPago']);

    // Requisitos (Catálogo y Enlaces)
    Route::get('/catalogo-requisitos', [RequisitoController::class, 'getCatalogo']);
    Route::post('/catalogo-requisitos', [RequisitoController::class, 'storeCatalogo']);
    Route::put('/catalogo-requisitos/{id}', [RequisitoController::class, 'updateCatalogo']);
    Route::delete('/catalogo-requisitos/{id}', [RequisitoController::class, 'deleteCatalogo']);
    
    Route::get('/requisitos', [RequisitoController::class, 'index']);
    Route::post('/requisitos', [RequisitoController::class, 'store']);
    Route::delete('/requisitos/{id}', [RequisitoController::class, 'destroy']);
    Route::patch('/requisitos/{id}/estado', [RequisitoController::class, 'updateEstado']);

    // P3 Materias
    Route::get('/materias', [MateriaController::class, 'index']);
    Route::post('/materias', [MateriaController::class, 'store']);
    Route::put('/materias/{id}', [MateriaController::class, 'update']);
    Route::delete('/materias/{id}', [MateriaController::class, 'destroy']);
    Route::get('/materias/{materiaId}/requisitos', [RequisitoController::class, 'getMateriaRequisitos']);
    Route::post('/materias/{materiaId}/requisitos', [RequisitoController::class, 'syncMateriaRequisitos']);

    // P2 Aspirantes Docentes
    Route::get('/aspirantes-docentes', [AspiranteDocenteController::class, 'index']);
    Route::post('/aspirantes-docentes', [AspiranteDocenteController::class, 'createAspirante']);
    Route::get('/aspirantes-docentes/{id}/materias', [AspiranteDocenteController::class, 'getMateriasPostuladas']);
    Route::put('/aspirantes-docentes/{id}', [AspiranteDocenteController::class, 'updateAspirante']);
    Route::delete('/aspirantes-docentes/{id}', [AspiranteDocenteController::class, 'deleteAspirante']);
    Route::get('/aspirantes-docentes/{id}/materias/{idMateria}/requisitos', [AspiranteDocenteController::class, 'getRequisitosMateria']);
    Route::post('/aspirantes-docentes/requisito/toggle', [AspiranteDocenteController::class, 'toggleRequisito']);
    Route::post('/aspirantes-docentes/postular', [AspiranteDocenteController::class, 'postularMateria']);
    Route::post('/aspirantes-docentes/{id}/convertir', [AspiranteDocenteController::class, 'convertirADocente']);

    // P3 Docentes
    Route::get('/docentes', [DocenteController::class, 'index']);
    Route::post('/docentes', [DocenteController::class, 'store']);
    Route::put('/docentes/{id}', [DocenteController::class, 'update']);
    Route::delete('/docentes/{id}', [DocenteController::class, 'destroy']);

    // P3 Grupos
    Route::get('/grupos', [GrupoController::class, 'index']);
    Route::post('/grupos', [GrupoController::class, 'store']);
    Route::put('/grupos/{id}', [GrupoController::class, 'update']);
    Route::delete('/grupos/{id}', [GrupoController::class, 'destroy']);
    Route::get('/gestiones', [GrupoController::class, 'getGestiones']);

    // P3 Aulas
    Route::get('/aulas', [AulaController::class, 'index']);
    Route::post('/aulas', [AulaController::class, 'store']);
    Route::put('/aulas/{id}', [AulaController::class, 'update']);
    Route::delete('/aulas/{id}', [AulaController::class, 'destroy']);

    // P3 Carreras
    Route::get('/carreras', [CarreraController::class, 'index']);
    Route::post('/carreras', [CarreraController::class, 'store']);
    Route::put('/carreras/{id}', [CarreraController::class, 'update']);
    Route::delete('/carreras/{id}', [CarreraController::class, 'destroy']);

    // P3 Gestión Académica
    Route::get('/gestiones-academicas/cups', [GestionAcademicaController::class, 'getCups']);
    Route::get('/gestiones-academicas', [GestionAcademicaController::class, 'index']);
    Route::post('/gestiones-academicas', [GestionAcademicaController::class, 'store']);
    Route::put('/gestiones-academicas/{id}', [GestionAcademicaController::class, 'update']);
    Route::delete('/gestiones-academicas/{id}', [GestionAcademicaController::class, 'destroy']);
    
    // Rutas para Evaluaciones de una Gestión Académica
    Route::get('/gestiones-academicas/{id}/evaluaciones', [GestionAcademicaController::class, 'getEvaluaciones']);
    Route::put('/gestiones-academicas/{id}/evaluaciones', [GestionAcademicaController::class, 'updateEvaluacion']);


    Route::post('/logout', [AuthController::class, 'logout']);
});
