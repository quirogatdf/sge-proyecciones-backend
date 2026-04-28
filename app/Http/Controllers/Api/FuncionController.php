<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFuncionRequest;
use App\Http\Requests\UpdateFuncionRequest;
use App\Http\Resources\FuncionResource;
use App\Models\Funcion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

final class FuncionController extends Controller
{
    public function index(): JsonResponse
    {
        $funciones = Funcion::all();

        return response()->json([
            'data' => FuncionResource::collection($funciones),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $funcion = Funcion::findOrFail($id);

        return response()->json([
            'data' => new FuncionResource($funcion),
        ]);
    }

    public function store(StoreFuncionRequest $request): JsonResponse
    {
        $funcion = Funcion::create($request->validated());

        return response()->json([
            'data' => new FuncionResource($funcion),
            'message' => 'Función creada exitosamente',
        ], Response::HTTP_CREATED);
    }

    public function update(UpdateFuncionRequest $request, int $id): JsonResponse
    {
        $funcion = Funcion::findOrFail($id);
        $funcion->update($request->validated());

        return response()->json([
            'data' => new FuncionResource($funcion),
            'message' => 'Función actualizada exitosamente',
        ]);
    }

    public function destroy(int $id): Response
    {
        $funcion = Funcion::findOrFail($id);
        $funcion->delete();

        return response()->noContent();
    }
}
