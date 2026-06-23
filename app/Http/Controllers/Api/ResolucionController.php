<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreResolucionRequest;
use App\Http\Requests\UpdateResolucionRequest;
use App\Http\Resources\ResolucionResource;
use App\Models\Resolucion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

final class ResolucionController extends Controller
{
    public function index(): JsonResponse
    {
        $resoluciones = Resolucion::all();

        return response()->json([
            'data' => ResolucionResource::collection($resoluciones),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $resolucion = Resolucion::findOrFail($id);

        return response()->json([
            'data' => new ResolucionResource($resolucion),
        ]);
    }

    public function store(StoreResolucionRequest $request): JsonResponse
    {
        $resolucion = Resolucion::create($request->validated());

        return response()->json([
            'data' => new ResolucionResource($resolucion),
            'message' => 'Resolución creada exitosamente',
        ], Response::HTTP_CREATED);
    }

    public function update(UpdateResolucionRequest $request, int $id): JsonResponse
    {
        $resolucion = Resolucion::findOrFail($id);
        $resolucion->update($request->validated());

        return response()->json([
            'data' => new ResolucionResource($resolucion),
            'message' => 'Resolución actualizada exitosamente',
        ]);
    }

    public function destroy(int $id): Response
    {
        $resolucion = Resolucion::findOrFail($id);
        $resolucion->delete();

        return response()->noContent();
    }
}
