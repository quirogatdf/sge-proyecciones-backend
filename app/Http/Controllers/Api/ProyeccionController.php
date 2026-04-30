<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProyeccionRequest;
use App\Http\Requests\UpdateProyeccionRequest;
use App\Http\Resources\ProyeccionResource;
use App\Models\Proyeccion;
use Illuminate\Http\JsonResponse;
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
}