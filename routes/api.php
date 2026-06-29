<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\NivelController;
use App\Http\Controllers\Api\CargoController;
use App\Http\Controllers\Api\TurnoController;
use App\Http\Controllers\Api\FuncionController;
use App\Http\Controllers\Api\InstitucionController;
use App\Http\Controllers\Api\ProyeccionController;
use App\Http\Controllers\Api\ResolucionController;

Route::apiResource('niveles', NivelController::class)->only(['index', 'show', 'store', 'update', 'destroy']);
Route::apiResource('cargos', CargoController::class)->only(['index', 'show', 'store', 'update', 'destroy']);
Route::apiResource('turnos', TurnoController::class)->only(['index', 'show', 'store', 'update', 'destroy']);
Route::apiResource('funciones', FuncionController::class)->only(['index', 'show', 'store', 'update', 'destroy']);
Route::apiResource('instituciones', InstitucionController::class)->only(['index', 'show', 'store', 'update', 'destroy']);
Route::apiResource('resoluciones', ResolucionController::class)->only(['index', 'show', 'store', 'update', 'destroy']);
Route::get('proyecciones/stats/by-institucion', [ProyeccionController::class, 'statsByInstitucion']);
Route::get('proyecciones/nivel/{idNivel}', [ProyeccionController::class, 'byNivel']);
Route::apiResource('proyecciones', ProyeccionController::class);