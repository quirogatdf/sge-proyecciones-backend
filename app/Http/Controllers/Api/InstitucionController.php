<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInstitucionRequest;
use App\Http\Requests\UpdateInstitucionRequest;
use App\Http\Resources\InstitucionResource;
use App\Models\Institucion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

final class InstitucionController extends Controller
{
    public function index(): JsonResponse
    {
        $instituciones = Institucion::with('nivel')->get();

        return response()->json([
            'data' => InstitucionResource::collection($instituciones),
        ]);
    }

    public function show(int $institucion): JsonResponse
    {
        $institucion = Institucion::with('nivel')->findOrFail($institucion);

        return response()->json([
            'data' => new InstitucionResource($institucion),
        ]);
    }

    public function store(StoreInstitucionRequest $request): JsonResponse
    {
        $institucion = Institucion::create($request->validated());

        return response()->json([
            'data' => new InstitucionResource($institucion->load('nivel')),
            'message' => 'Institución creada exitosamente',
        ], Response::HTTP_CREATED);
    }

    public function update(UpdateInstitucionRequest $request, int $institucion): JsonResponse
    {
        try {
            $institucion = Institucion::findOrFail($institucion);
            $institucion->update($request->validated());
            
            return response()->json([
                'data' => new InstitucionResource($institucion->load('nivel')),
                'message' => 'Institución actualizada exitosamente',
            ]);
        } catch (\Throwable $e) {
            \Log::error('Error en update institucion: ' . $e->getMessage());
            \Log::error('Trace: ' . $e->getTraceAsString());
            throw $e;
        }
    }

    public function destroy(int $institucion): Response
    {
        $institucion = Institucion::findOrFail($institucion);
        $institucion->delete();

        return response()->noContent();
    }
}
