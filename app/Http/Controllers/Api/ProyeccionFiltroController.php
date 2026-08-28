<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cargo;
use App\Models\Institucion;
use App\Models\Nivel;
use App\Models\Proyeccion;
use App\Models\Resolucion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ProyeccionFiltroController extends Controller
{
    public function opciones(Request $request): JsonResponse
    {
        if ($request->has('año') && !$request->has('anio')) {
            $request->merge(['anio' => $request->input('año')]);
        } elseif ($request->has('año') && $request->has('anio') && empty($request->input('anio'))) {
            $request->merge(['anio' => $request->input('año')]);
        }

        $validated = $request->validate([
            'id_resolucion' => ['nullable', 'integer', 'exists:resoluciones,id'],
            'id_institucion' => ['nullable', 'integer', 'exists:instituciones,id'],
            'id_nivel' => ['nullable', 'integer', 'exists:niveles,id'],
            'id_cargo' => ['nullable', 'integer', 'exists:cargos,id'],
            'motivo' => ['nullable', 'string', 'in:Creación,Creacion,Continuidad'],
            'anio' => ['nullable', 'string', 'size:4'],
            'año' => ['nullable', 'string', 'size:4'],
        ]);

        $motivoNorm = null;
        if (!empty($validated['motivo'])) {
            $motivoNorm = $validated['motivo'] === 'Creacion' ? 'Creación' : $validated['motivo'];
        }

        $anio = $validated['anio'] ?? null;
        if (empty($anio) && !empty($validated['año'])) {
            $anio = $validated['año'];
        }

        $qInst = Proyeccion::query();
        if (!empty($validated['id_resolucion'])) {
            $qInst->where('id_resolucion', $validated['id_resolucion']);
        }
        if (!empty($anio)) {
            $qInst->where('año', $anio);
        }
        if (!empty($motivoNorm)) {
            $qInst->where('motivo', $motivoNorm);
        }
        if (!empty($validated['id_nivel'])) {
            $qInst->where('id_nivel', $validated['id_nivel']);
        }
        if (!empty($validated['id_cargo'])) {
            $qInst->where('id_cargo', $validated['id_cargo']);
        }
        $idsInst = $qInst->distinct()->pluck('id_institucion')->filter()->values();
        $instituciones = $idsInst->isEmpty()
            ? collect()
            : Institucion::whereIn('id', $idsInst)->orderBy('nombre')->get(['id', 'nombre', 'cuise', 'localidad']);

        $qCargo = Proyeccion::query();
        if (!empty($validated['id_institucion'])) {
            $qCargo->where('id_institucion', $validated['id_institucion']);
        } elseif (!empty($validated['id_resolucion'])) {
            $qCargo->where('id_resolucion', $validated['id_resolucion']);
        }
        if (!empty($validated['id_resolucion'])) {
            $qCargo->where('id_resolucion', $validated['id_resolucion']);
        }
        if (!empty($anio)) {
            $qCargo->where('año', $anio);
        }
        if (!empty($motivoNorm)) {
            $qCargo->where('motivo', $motivoNorm);
        }
        if (!empty($validated['id_nivel'])) {
            $qCargo->where('id_nivel', $validated['id_nivel']);
        }
        if (!empty($validated['id_cargo'])) {
            $qCargo->where('id_cargo', $validated['id_cargo']);
        }
        $idsCargo = $qCargo->distinct()->pluck('id_cargo')->filter()->values();
        $cargos = $idsCargo->isEmpty()
            ? collect()
            : Cargo::whereIn('id', $idsCargo)->orderBy('nombre')->get(['id', 'nombre', 'codigo', 'tipo']);

        $qRes = Proyeccion::query();
        if (!empty($anio)) {
            $qRes->where('año', $anio);
        }
        if (!empty($motivoNorm)) {
            $qRes->where('motivo', $motivoNorm);
        }
        $hasResFilter = !empty($anio) || !empty($motivoNorm);
        if ($hasResFilter) {
            $idsRes = $qRes->distinct()->pluck('id_resolucion')->filter()->values();
            $resoluciones = $idsRes->isEmpty()
                ? collect()
                : Resolucion::whereIn('id', $idsRes)->orderBy('nombre')->get(['id', 'nombre', 'año']);
        } else {
            $resoluciones = Resolucion::orderBy('nombre')->get(['id', 'nombre', 'año']);
        }

        $qNivel = Proyeccion::query();
        if (!empty($anio)) {
            $qNivel->where('año', $anio);
        }
        if (!empty($motivoNorm)) {
            $qNivel->where('motivo', $motivoNorm);
        }
        $hasNivelFilter = !empty($anio) || !empty($motivoNorm);
        if ($hasNivelFilter) {
            $idsNivel = $qNivel->distinct()->pluck('id_nivel')->filter()->values();
            $niveles = $idsNivel->isEmpty()
                ? collect()
                : Nivel::whereIn('id', $idsNivel)->orderBy('nombre')->get(['id', 'nombre']);
        } else {
            $niveles = Nivel::orderBy('nombre')->get(['id', 'nombre']);
        }

        return response()->json([
            'data' => [
                'instituciones' => $instituciones,
                'cargos' => $cargos,
                'resoluciones' => $resoluciones,
                'niveles' => $niveles,
            ],
        ]);
    }
}
