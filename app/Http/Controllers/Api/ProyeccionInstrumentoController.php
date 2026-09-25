<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProyeccionInstrumentoRequest;
use App\Http\Requests\UpdateProyeccionInstrumentoRequest;
use App\Http\Resources\ProyeccionInstrumentoResource;
use App\Models\Proyeccion;
use App\Models\ProyeccionInstrumento;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

final class ProyeccionInstrumentoController extends Controller
{
    /**
     * Historial completo de instrumentos de una proyección, ordenado
     * del año más reciente al más antiguo.
     */
    public function index(Proyeccion $proyeccion): JsonResponse
    {
        $instrumentos = $proyeccion->instrumentos()
            ->with(['resolucion', 'cargo', 'funcion', 'turno'])
            ->orderByDesc('anio')
            ->get();

        return response()->json([
            'data' => ProyeccionInstrumentoResource::collection($instrumentos),
        ]);
    }

    /**
     * Agregar un instrumento para un año nuevo. Se valida que no exista
     * otro snapshot para el mismo (proyeccion, anio).
     */
    public function store(StoreProyeccionInstrumentoRequest $request, Proyeccion $proyeccion): JsonResponse
    {
        $instrumento = $proyeccion->instrumentos()->create($request->validated());

        $instrumento->load(['resolucion', 'cargo', 'funcion', 'turno']);

        return response()->json([
            'data' => new ProyeccionInstrumentoResource($instrumento),
            'message' => 'Instrumento agregado correctamente',
        ], Response::HTTP_CREATED);
    }

    /**
     * Editar un instrumento del historial (incluye "crear en blanco": el
     * snapshot se crea vacío y se completa por acá).
     */
    public function update(
        UpdateProyeccionInstrumentoRequest $request,
        Proyeccion $proyeccion,
        ProyeccionInstrumento $instrumento,
    ): JsonResponse {
        abort_unless($instrumento->proyeccion_id === $proyeccion->id, Response::HTTP_NOT_FOUND);

        $instrumento->update($request->validated());

        $instrumento->load(['resolucion', 'cargo', 'funcion', 'turno']);

        return response()->json([
            'data' => new ProyeccionInstrumentoResource($instrumento),
            'message' => 'Instrumento actualizado correctamente',
        ]);
    }
}
