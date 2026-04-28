<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTurnoRequest;
use App\Http\Requests\UpdateTurnoRequest;
use App\Http\Resources\TurnoResource;
use App\Models\Turno;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

final class TurnoController extends Controller
{
    public function index(): JsonResponse
    {
        $turnos = Turno::all();

        return response()->json([
            'data' => TurnoResource::collection($turnos),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $turno = Turno::findOrFail($id);

        return response()->json([
            'data' => new TurnoResource($turno),
        ]);
    }

    public function store(StoreTurnoRequest $request): JsonResponse
    {
        $turno = Turno::create($request->validated());

        return response()->json([
            'data' => new TurnoResource($turno),
            'message' => 'Turno creado exitosamente',
        ], Response::HTTP_CREATED);
    }

    public function update(UpdateTurnoRequest $request, int $id): JsonResponse
    {
        $turno = Turno::findOrFail($id);
        $turno->update($request->validated());

        return response()->json([
            'data' => new TurnoResource($turno),
            'message' => 'Turno actualizado exitosamente',
        ]);
    }

    public function destroy(int $id): Response
    {
        $turno = Turno::findOrFail($id);
        $turno->delete();

        return response()->noContent();
    }
}
