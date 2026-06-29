<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProyeccionRequest;
use App\Http\Requests\UpdateProyeccionRequest;
use App\Http\Resources\ProyeccionResource;
use App\Models\Proyeccion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class ProyeccionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $proyecciones = Proyeccion::with(['nivel', 'cargo', 'funcion', 'turno', 'institucion'])->get();

        return response()->json([
            'data' => ProyeccionResource::collection($proyecciones),
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $proyeccion = Proyeccion::with(['nivel', 'cargo', 'funcion', 'turno', 'institucion'])
            ->findOrFail($id);

        return response()->json([
            'data' => new ProyeccionResource($proyeccion),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreProyeccionRequest $request
     * @return JsonResponse
     */
    public function store(StoreProyeccionRequest $request): JsonResponse
    {
        $proyeccion = Proyeccion::create($request->validated());

        $proyeccion->load(['nivel', 'cargo', 'funcion', 'turno', 'institucion']);

        return response()->json([
            'data' => new ProyeccionResource($proyeccion),
            'message' => 'Proyección creada exitosamente',
        ], Response::HTTP_CREATED);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateProyeccionRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(UpdateProyeccionRequest $request, int $id): JsonResponse
    {
        $proyeccion = Proyeccion::findOrFail($id);
        $proyeccion->update($request->validated());

        $proyeccion->load(['nivel', 'cargo', 'funcion', 'turno', 'institucion']);

        return response()->json([
            'data' => new ProyeccionResource($proyeccion),
            'message' => 'Proyección actualizada exitosamente',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return Response
     */
    public function destroy(int $id): Response
    {
        $proyeccion = Proyeccion::findOrFail($id);
        $proyeccion->delete();

        return response()->noContent();
    }

    /**
     * Display proyecciones by nivel.
     *
     * @param int $idNivel
     * @return JsonResponse
     */
    public function byNivel(int $idNivel): JsonResponse
    {
        $proyecciones = Proyeccion::with(['nivel', 'cargo', 'funcion', 'turno', 'institucion'])
            ->where('id_nivel', $idNivel)
            ->get();

        return response()->json([
            'data' => ProyeccionResource::collection($proyecciones),
        ]);
    }

    /**
     * Get stats grouped by institution for the dashboard chart.
     * Accepts optional ?anio=XXXX and ?institucion_id=X filters.
     * Returns aggregation by institution with break-down by motivo and cargo type.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function statsByInstitucion(Request $request): JsonResponse
    {
        $query = Proyeccion::with(['cargo', 'institucion'])
            ->whereIn('motivo', ['Creación', 'Continuidad']);

        if ($request->filled('institucion_id')) {
            $query->where('id_institucion', $request->integer('institucion_id'));
        }

        if ($request->filled('anio')) {
            $query->where('año', $request->string('anio'));
        }

        $proyecciones = $query->get();

        // Group by institution first
        $grouped = $proyecciones->groupBy('id_institucion');

        $result = $grouped->map(function ($items, $institucionId) {
            $first = $items->first();
            $institucionName = $first?->institucion?->nombre ?? "Institución #{$institucionId}";

            $creacionNoH = 0;
            $creacionHorasH = 0;
            $continuidadNoH = 0;
            $continuidadHorasH = 0;

            foreach ($items as $p) {
                $isH = $p->cargo && $p->cargo->tipo === 'H';
                $isCreacion = $p->motivo === 'Creación';

                if ($isCreacion) {
                    if ($isH) {
                        $creacionHorasH += $p->horar ?? 0;
                    } else {
                        $creacionNoH += $p->cargos ?? 1;
                    }
                } else {
                    // Continuidad
                    if ($isH) {
                        $continuidadHorasH += $p->horar ?? 0;
                    } else {
                        $continuidadNoH += $p->cargos ?? 1;
                    }
                }
            }

            return [
                'institucion_id' => (int) $institucionId,
                'institucion' => $institucionName,
                'creacion_no_h' => $creacionNoH,
                'creacion_horas_h' => $creacionHorasH,
                'continuidad_no_h' => $continuidadNoH,
                'continuidad_horas_h' => $continuidadHorasH,
            ];
        })->values();

        return response()->json([
            'data' => $result,
        ]);
    }
}