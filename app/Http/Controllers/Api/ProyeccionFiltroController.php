<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cargo;
use App\Models\Institucion;
use App\Models\Nivel;
use App\Models\ProyeccionInstrumento;
use App\Models\Resolucion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ProyeccionFiltroController extends Controller
{
    public function opciones(Request $request): JsonResponse
    {
        if ($request->has('año') && ! $request->has('anio')) {
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
        if (! empty($validated['motivo'])) {
            $motivoNorm = $validated['motivo'] === 'Creacion' ? 'Creación' : $validated['motivo'];
        }

        $anio = $validated['anio'] ?? null;
        if (empty($anio) && ! empty($validated['año'])) {
            $anio = $validated['año'];
        }

        // --- Instituciones: se filtran por resolucion, año, motivo, nivel y cargo ---
        $qInst = ProyeccionInstrumento::query();
        $this->aplicarFiltros($qInst, [
            'anio' => $anio,
            'motivo' => $motivoNorm,
            'id_resolucion' => $validated['id_resolucion'] ?? null,
            'id_cargo' => $validated['id_cargo'] ?? null,
            'id_nivel' => $validated['id_nivel'] ?? null,
        ]);
        $idsInst = $qInst
            ->join('proyecciones', 'proyecciones.id', '=', 'proyeccion_instrumentos.proyeccion_id')
            ->distinct()->pluck('proyecciones.id_institucion')->filter()->values();
        $instituciones = $idsInst->isEmpty()
            ? collect()
            : Institucion::whereIn('id', $idsInst)->orderBy('nombre')->get(['id', 'nombre', 'cuise', 'localidad']);

        // --- Cargos: filtrados por institucion / resolucion, año, motivo, nivel y cargo ---
        $qCargo = ProyeccionInstrumento::query();
        $this->aplicarFiltros($qCargo, [
            'anio' => $anio,
            'motivo' => $motivoNorm,
            'id_resolucion' => $validated['id_resolucion'] ?? null,
            'id_cargo' => $validated['id_cargo'] ?? null,
            'id_nivel' => $validated['id_nivel'] ?? null,
            'id_institucion' => $validated['id_institucion'] ?? null,
        ]);
        $idsCargo = $qCargo->distinct()->pluck('id_cargo')->filter()->values();
        $cargos = $idsCargo->isEmpty()
            ? collect()
            : Cargo::whereIn('id', $idsCargo)->orderBy('nombre')->get(['id', 'nombre', 'codigo', 'tipo']);

        // --- Resoluciones: solo se acotan si hay filtro por año o motivo ---
        $hasResFilter = ! empty($anio) || ! empty($motivoNorm);
        if ($hasResFilter) {
            $qRes = ProyeccionInstrumento::query();
            $this->aplicarFiltros($qRes, ['anio' => $anio, 'motivo' => $motivoNorm]);
            $idsRes = $qRes->distinct()->pluck('id_resolucion')->filter()->values();
            $resoluciones = $idsRes->isEmpty()
                ? collect()
                : Resolucion::whereIn('id', $idsRes)->orderBy('nombre')->get(['id', 'nombre', 'año']);
        } else {
            $resoluciones = Resolucion::orderBy('nombre')->get(['id', 'nombre', 'año']);
        }

        // --- Niveles: solo se acotan si hay filtro por año o motivo ---
        $hasNivelFilter = ! empty($anio) || ! empty($motivoNorm);
        if ($hasNivelFilter) {
            $qNivel = ProyeccionInstrumento::query();
            $this->aplicarFiltros($qNivel, ['anio' => $anio, 'motivo' => $motivoNorm]);
            $idsNivel = $qNivel
                ->join('proyecciones', 'proyecciones.id', '=', 'proyeccion_instrumentos.proyeccion_id')
                ->distinct()->pluck('proyecciones.id_nivel')->filter()->values();
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

    /**
     * Aplica los filtros no vacíos. Los de instrumento van directo; los de
     * plaza (nivel, institución) van vía la relación `proyeccion`.
     *
     * @param  array<string, mixed>  $filtros
     */
    private function aplicarFiltros(Builder $query, array $filtros): void
    {
        if (! empty($filtros['anio'])) {
            $query->where('proyeccion_instrumentos.anio', $filtros['anio']);
        }
        if (! empty($filtros['motivo'])) {
            $query->where('proyeccion_instrumentos.motivo', $filtros['motivo']);
        }
        if (! empty($filtros['id_resolucion'])) {
            $query->where('proyeccion_instrumentos.id_resolucion', $filtros['id_resolucion']);
        }
        if (! empty($filtros['id_cargo'])) {
            $query->where('proyeccion_instrumentos.id_cargo', $filtros['id_cargo']);
        }
        if (! empty($filtros['id_nivel'])) {
            $query->whereHas('proyeccion', fn ($p) => $p->where('id_nivel', $filtros['id_nivel']));
        }
        if (! empty($filtros['id_institucion'])) {
            $query->whereHas('proyeccion', fn ($p) => $p->where('id_institucion', $filtros['id_institucion']));
        }
    }
}
