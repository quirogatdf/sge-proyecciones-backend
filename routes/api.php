<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CargoController;
use App\Http\Controllers\Api\FuncionController;
use App\Http\Controllers\Api\InstitucionController;
use App\Http\Controllers\Api\NivelController;
use App\Http\Controllers\Api\ProyeccionController;
use App\Http\Controllers\Api\ProyeccionExportController;
use App\Http\Controllers\Api\ProyeccionFiltroController;
use App\Http\Controllers\Api\ProyeccionInstrumentoController;
use App\Http\Controllers\Api\ResolucionController;
use App\Http\Controllers\Api\TurnoController;
use Illuminate\Support\Facades\Route;

Route::post('login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('me', [AuthController::class, 'me']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::put('password', [AuthController::class, 'updatePassword'])->middleware('admin');

    Route::apiResource('niveles', NivelController::class)->only(['index', 'show', 'store', 'update', 'destroy']);
    Route::apiResource('cargos', CargoController::class)->only(['index', 'show', 'store', 'update', 'destroy']);
    Route::apiResource('turnos', TurnoController::class)->only(['index', 'show', 'store', 'update', 'destroy']);
    Route::apiResource('funciones', FuncionController::class)->only(['index', 'show', 'store', 'update', 'destroy']);
    Route::apiResource('instituciones', InstitucionController::class)->only(['index', 'show', 'store', 'update', 'destroy']);
    Route::apiResource('resoluciones', ResolucionController::class)->only(['index', 'show', 'store', 'update', 'destroy']);
    Route::get('proyecciones/export', [ProyeccionExportController::class, 'export']);
    Route::get('proyecciones/opciones-filtro', [ProyeccionFiltroController::class, 'opciones']);
    Route::get('proyecciones/stats/by-institucion', [ProyeccionController::class, 'statsByInstitucion']);
    Route::get('proyecciones/stats/por-anio', [ProyeccionController::class, 'statsPorAnio']);
    Route::get('proyecciones/nivel/{idNivel}', [ProyeccionController::class, 'byNivel']);
    Route::get('proyecciones/{proyeccion}/instrumentos', [ProyeccionInstrumentoController::class, 'index']);
    Route::post('proyecciones/{proyeccion}/instrumentos', [ProyeccionInstrumentoController::class, 'store']);
    Route::put('proyecciones/{proyeccion}/instrumentos/{instrumento}', [ProyeccionInstrumentoController::class, 'update']);
    Route::apiResource('proyecciones', ProyeccionController::class);
});
