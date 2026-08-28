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
        $query = Proyeccion::with(['nivel', 'cargo', 'institucion', 'resolucion']);

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
                    })
                    ->orWhereHas('resolucion', function ($q) use ($search) {
                        $q->where('nombre', 'ILIKE', "%{$search}%");
                    });
            });
        }

        // Sorting server-side — soporta todas las columnas visibles en el front
        $allowedSorts = [
            'id', 'id_nivel', 'localidad', 'nombreInstitucion', 'cantidadDisplay', 'cargoDisplay',
            'año', 'orden', 'estado', 'motivo', 'resolucionDisplay', 'destino_nuevo', 'id_puesto',
            // aliases directos (compat)
            'fecha_desde', 'fecha_hasta', 'n_expediente', 'id_institucion', 'id_cargo', 'id_funcion', 'id_turno', 'id_resolucion', 'horar', 'cargos', 'created_at', 'updated_at'
        ];
        $sortBy = $request->string('sort_by')->toString() ?: 'id';
        if (!in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'id';
        }
        $sortDir = strtolower($request->string('sort_dir')->toString() ?: 'desc');
        if (!in_array($sortDir, ['asc', 'desc'], true)) {
            $sortDir = 'desc';
        }

        // Joins condicionales para ordenar por relaciones / campos computados
        $query->select('proyecciones.*');
        switch ($sortBy) {
            case 'id_nivel':
                $query->leftJoin('niveles as nivel_sort', 'nivel_sort.id', '=', 'proyecciones.id_nivel')
                      ->orderBy('nivel_sort.nombre', $sortDir);
                break;
            case 'localidad':
                $query->leftJoin('instituciones as inst_sort', 'inst_sort.id', '=', 'proyecciones.id_institucion')
                      ->orderBy('inst_sort.localidad', $sortDir);
                break;
            case 'nombreInstitucion':
                $query->leftJoin('instituciones as inst_sort2', 'inst_sort2.id', '=', 'proyecciones.id_institucion')
                      ->orderBy('inst_sort2.nombre', $sortDir);
                break;
            case 'cargoDisplay':
                $query->leftJoin('cargos as cargo_sort', 'cargo_sort.id', '=', 'proyecciones.id_cargo')
                      ->orderBy('cargo_sort.codigo', $sortDir)
                      ->orderBy('cargo_sort.nombre', $sortDir);
                break;
            case 'cantidadDisplay':
                // Cantidad = horar si >0 sino cargos — ordena por valor computado
                $query->orderByRaw("COALESCE(NULLIF(proyecciones.horar, 0), proyecciones.cargos, 0) $sortDir");
                break;
            case 'resolucionDisplay':
            case 'id_resolucion':
                $query->leftJoin('resoluciones as resol_sort', 'resol_sort.id', '=', 'proyecciones.id_resolucion')
                      ->orderBy('resol_sort.nombre', $sortDir);
                break;
            default:
                // Columnas directas de proyecciones — usar prefijo para evitar ambigüedad tras joins
                $directColumns = ['id','estado','motivo','año','orden','n_expediente','id_puesto','destino_nuevo','fecha_desde','fecha_hasta','id_institucion','id_cargo','id_funcion','id_turno','horar','cargos','created_at','updated_at'];
                $column = in_array($sortBy, $directColumns, true) ? "proyecciones.\"$sortBy\"" : "proyecciones.id";
                // Para 'año' con acento, el quoting con comillas es necesario en Postgres
                if ($sortBy === 'año') {
                    $query->orderByRaw('proyecciones."año" ' . $sortDir);
                } else {
                    $query->orderByRaw("$column $sortDir");
                }
                break;
        }

        $perPage = min($request->integer('per_page', 25), 100);
        $proyecciones = $query->paginate($perPage);

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
        $proyeccion = Proyeccion::with(['nivel', 'cargo', 'funcion', 'turno', 'institucion', 'resolucion'])
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

        $proyeccion->load(['nivel', 'cargo', 'funcion', 'turno', 'institucion', 'resolucion']);

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

        $proyeccion->load(['nivel', 'cargo', 'funcion', 'turno', 'institucion', 'resolucion']);

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
        $query = Proyeccion::with(['nivel', 'cargo', 'institucion', 'resolucion'])
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
                    })
                    ->orWhereHas('resolucion', function ($q) use ($search) {
                        $q->where('nombre', 'ILIKE', "%{$search}%");
                    });
            });
        }

        // Sorting server-side — soporta todas las columnas visibles en el front
        $allowedSorts = [
            'id', 'id_nivel', 'localidad', 'nombreInstitucion', 'cantidadDisplay', 'cargoDisplay',
            'año', 'orden', 'estado', 'motivo', 'resolucionDisplay', 'destino_nuevo', 'id_puesto',
            'fecha_desde', 'fecha_hasta', 'n_expediente', 'id_institucion', 'id_cargo', 'id_funcion', 'id_turno', 'id_resolucion', 'horar', 'cargos', 'created_at', 'updated_at'
        ];
        $sortBy = $request->string('sort_by')->toString() ?: 'id';
        if (!in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'id';
        }
        $sortDir = strtolower($request->string('sort_dir')->toString() ?: 'desc');
        if (!in_array($sortDir, ['asc', 'desc'], true)) {
            $sortDir = 'desc';
        }

        $query->select('proyecciones.*');
        switch ($sortBy) {
            case 'id_nivel':
                $query->leftJoin('niveles as nivel_sort', 'nivel_sort.id', '=', 'proyecciones.id_nivel')
                      ->orderBy('nivel_sort.nombre', $sortDir);
                break;
            case 'localidad':
                $query->leftJoin('instituciones as inst_sort', 'inst_sort.id', '=', 'proyecciones.id_institucion')
                      ->orderBy('inst_sort.localidad', $sortDir);
                break;
            case 'nombreInstitucion':
                $query->leftJoin('instituciones as inst_sort2', 'inst_sort2.id', '=', 'proyecciones.id_institucion')
                      ->orderBy('inst_sort2.nombre', $sortDir);
                break;
            case 'cargoDisplay':
                $query->leftJoin('cargos as cargo_sort', 'cargo_sort.id', '=', 'proyecciones.id_cargo')
                      ->orderBy('cargo_sort.codigo', $sortDir)
                      ->orderBy('cargo_sort.nombre', $sortDir);
                break;
            case 'cantidadDisplay':
                $query->orderByRaw("COALESCE(NULLIF(proyecciones.horar, 0), proyecciones.cargos, 0) $sortDir");
                break;
            case 'resolucionDisplay':
            case 'id_resolucion':
                $query->leftJoin('resoluciones as resol_sort', 'resol_sort.id', '=', 'proyecciones.id_resolucion')
                      ->orderBy('resol_sort.nombre', $sortDir);
                break;
            default:
                $directColumns = ['id','estado','motivo','año','orden','n_expediente','id_puesto','destino_nuevo','fecha_desde','fecha_hasta','id_institucion','id_cargo','id_funcion','id_turno','horar','cargos','created_at','updated_at'];
                $column = in_array($sortBy, $directColumns, true) ? "proyecciones.\"$sortBy\"" : "proyecciones.id";
                if ($sortBy === 'año') {
                    $query->orderByRaw('proyecciones."año" ' . $sortDir);
                } else {
                    $query->orderByRaw("$column $sortDir");
                }
                break;
        }

        $perPage = min($request->integer('per_page', 25), 100);
        $proyecciones = $query->paginate($perPage);

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