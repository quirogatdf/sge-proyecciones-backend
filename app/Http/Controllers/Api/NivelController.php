<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreNivelRequest;
use App\Http\Requests\UpdateNivelRequest;
use App\Http\Resources\NivelResource;
use App\Models\Nivel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

final class NivelController extends Controller
{
    public function index(): JsonResponse
    {
        $niveles = Nivel::all();

        return response()->json([
            'data' => NivelResource::collection($niveles),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $nivel = Nivel::findOrFail($id);

        return response()->json([
            'data' => new NivelResource($nivel),
        ]);
    }

    public function store(StoreNivelRequest $request): JsonResponse
    {
        $nivel = Nivel::create($request->validated());

        return response()->json([
            'data' => new NivelResource($nivel),
            'message' => 'Nivel creado exitosamente',
        ], Response::HTTP_CREATED);
    }

    public function update(UpdateNivelRequest $request, int $id): JsonResponse
    {
        $nivel = Nivel::findOrFail($id);
        $nivel->update($request->validated());

        return response()->json([
            'data' => new NivelResource($nivel),
            'message' => 'Nivel actualizado exitosamente',
        ]);
    }

    public function destroy(int $id): Response
    {
        $nivel = Nivel::findOrFail($id);
        $nivel->delete();

        return response()->noContent();
    }
}