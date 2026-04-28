<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCargoRequest;
use App\Http\Requests\UpdateCargoRequest;
use App\Http\Resources\CargoResource;
use App\Models\Cargo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

final class CargoController extends Controller
{
    public function index(): JsonResponse
    {
        $cargos = Cargo::all();

        return response()->json([
            'data' => CargoResource::collection($cargos),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $cargo = Cargo::findOrFail($id);

        return response()->json([
            'data' => new CargoResource($cargo),
        ]);
    }

    public function store(StoreCargoRequest $request): JsonResponse
    {
        $cargo = Cargo::create($request->validated());

        return response()->json([
            'data' => new CargoResource($cargo),
            'message' => 'Cargo creado exitosamente',
        ], Response::HTTP_CREATED);
    }

    public function update(UpdateCargoRequest $request, int $id): JsonResponse
    {
        $cargo = Cargo::findOrFail($id);
        $cargo->update($request->validated());

        return response()->json([
            'data' => new CargoResource($cargo),
            'message' => 'Cargo actualizado exitosamente',
        ]);
    }

    public function destroy(int $id): Response
    {
        $cargo = Cargo::findOrFail($id);
        $cargo->delete();

        return response()->noContent();
    }
}