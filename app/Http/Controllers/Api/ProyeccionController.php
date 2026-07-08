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
     * Supports:
     * - ?search=term  (searches across estado, motivo, destino_nuevo, institucion.nombre, cargo.nombre)
     * - ?page=N       (default: 1)
     * - ?per_page=N   (default: 25)
     * - ?id_nivel=N   (filter by nivel)
     *
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = Proyeccion::with(['nivel', 'cargo', 'institucion']);

        // Filtro por nivel (compatibilidad con el filtro existente)
        if ($request->filled('id_nivel')) {
            $query->where('id_nivel', $request->integer('id_nivel'));
        }

        // Búsqueda server-side
        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($q) use ($search) {
                if (is_numeric($search)) {
                    $q->where('id', (int) $search);
                }

                $q->orWhere('estado', 'ILIKE', "%{$search}%")
                    ->orWhere('motivo', 'ILIKE', "%{$search}%")
                    ->orWhere('destino_nuevo', 'ILIKE', "%{$search}%")
                    ->orWhere('resolucion_ministerial', 'ILIKE', "%{$search}%")
                    ->orWhere('n_expediente', 'ILIKE', "%{$search}%")
                    ->orWhere('id_puesto', 'ILIKE', "%{$search}%")
                    ->orWhere('año', 'ILIKE', "%{$search}%")
                    ->orWhereHas('institucion', function ($q) use ($search) {
                        $q->where('nombre', 'ILIKE', "%{$search}%")
                            ->orWhere('localidad', 'ILIKE', "%{$search}%");
                    })
                    ->orWhereHas('cargo', function ($q) use ($search) {
                        $q->where('nombre', 'ILIKE', "%{$search}%")
                            ->orWhere('codigo', 'ILIKE', "%{$search}%");
                    });
            });
        }

        $perPage = min($request->integer('per_page', 25), 100);
        $proyecciones = $query->orderBy('id', 'desc')->paginate($perPage);

        return response()->json([
            'data' => ProyeccionResource::collection($proyecciones->items()),
            'meta' => [
                'current_page' => $proyecciones->currentPage(),
                'last_page' => $proyecciones->lastPage(),
                'per_page' => $proyecciones->perPage(),
                'total' => $proyecciones->total(),
            ],
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
    public function byNivel(int $idNivel, Request $request): JsonResponse
    {
        $query = Proyeccion::with(['nivel', 'cargo', 'institucion'])
            ->where('id_nivel', $idNivel);

        // Búsqueda server-side
        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($q) use ($search) {
                if (is_numeric($search)) {
                    $q->where('id', (int) $search);
                }

                $q->orWhere('estado', 'ILIKE', "%{$search}%")
                    ->orWhere('motivo', 'ILIKE', "%{$search}%")
                    ->orWhere('destino_nuevo', 'ILIKE', "%{$search}%")
                    ->orWhere('resolucion_ministerial', 'ILIKE', "%{$search}%")
                    ->orWhere('n_expediente', 'ILIKE', "%{$search}%")
                    ->orWhere('id_puesto', 'ILIKE', "%{$search}%")
                    ->orWhere('año', 'ILIKE', "%{$search}%")
                    ->orWhereHas('institucion', function ($q) use ($search) {
                        $q->where('nombre', 'ILIKE', "%{$search}%")
                            ->orWhere('localidad', 'ILIKE', "%{$search}%");
                    })
                    ->orWhereHas('cargo', function ($q) use ($search) {
                        $q->where('nombre', 'ILIKE', "%{$search}%")
                            ->orWhere('codigo', 'ILIKE', "%{$search}%");
                    });
            });
        }

        $perPage = min($request->integer('per_page', 25), 100);
        $proyecciones = $query->orderBy('id', 'desc')->paginate($perPage);

        return response()->json([
            'data' => ProyeccionResource::collection($proyecciones->items()),
            'meta' => [
                'current_page' => $proyecciones->currentPage(),
                'last_page' => $proyecciones->lastPage(),
                'per_page' => $proyecciones->perPage(),
                'total' => $proyecciones->total(),
            ],
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
        $query = Proyeccion::with('institucion')
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
                $isCreacion = $p->motivo === 'Creación';
                $horas = (int) ($p->horar ?? 0);
                $cargos = (int) ($p->cargos ?? 0);

                // Si tiene horas, va al bucket H (honorario)
                // Si tiene cargos, va al bucket No H (contratado)
                // Esto evita depender de cargo.tipo que puede ser null
                if ($isCreacion) {
                    if ($horas > 0) {
                        $creacionHorasH += $horas;
                    } else {
                        $creacionNoH += $cargos > 0 ? $cargos : 1;
                    }
                } else {
                    if ($horas > 0) {
                        $continuidadHorasH += $horas;
                    } else {
                        $continuidadNoH += $cargos > 0 ? $cargos : 1;
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