<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\MotivoProyeccion;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProyeccionRequest;
use App\Http\Requests\UpdateProyeccionRequest;
use App\Http\Resources\ProyeccionResource;
use App\Models\Proyeccion;
use App\Models\ProyeccionInstrumento;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class ProyeccionController extends Controller
{
    /**
     * Columnas que viven en `proyeccion_instrumentos` (varían por año).
     */
    private const COLUMNAS_INSTRUMENTO = [
        'orden', 'estado', 'motivo', 'destino_nuevo', 'n_expediente',
        'fecha_desde', 'fecha_hasta', 'id_cargo', 'id_funcion', 'id_turno',
        'id_resolucion', 'horar', 'cargos',
    ];

    /**
     * Columnas que viven en `proyecciones` (identidad de la plaza).
     */
    private const COLUMNAS_PLAZA = ['id_institucion', 'id_puesto'];

    /**
     * Listado de proyecciones para un año (por defecto, el último con datos).
     *
     * Cada fila es una plaza con los datos de su instrumento del año en foco.
     *
     * Supports:
     * - ?anio=YYYY    (default: último año con instrumentos)
     * - ?search=term
     * - ?page=N / ?per_page=N
     * - ?id_nivel=N / ?id_resolucion=N / ?id_cargo=N / ?motivo= / ?localidad=
     */
    public function index(Request $request): JsonResponse
    {
        return $this->listado($request);
    }

    /**
     * Igual que index pero acotado a un nivel.
     */
    public function byNivel(int $idNivel, Request $request): JsonResponse
    {
        return $this->listado($request, $idNivel);
    }

    private function listado(Request $request, ?int $idNivelForzado = null): JsonResponse
    {
        $anio = $this->resolverAnio($request);

        $query = Proyeccion::query()
            ->join('proyeccion_instrumentos as pi', function ($join) use ($anio): void {
                $join->on('pi.proyeccion_id', '=', 'proyecciones.id')
                    ->where('pi.anio', '=', $anio);
            })
            ->with([
                'nivel',
                'institucion',
                'instrumentos' => fn ($q) => $q->where('anio', $anio)
                    ->with(['cargo', 'funcion', 'turno', 'resolucion']),
            ])
            ->select('proyecciones.*');

        if ($idNivelForzado !== null) {
            $query->where('proyecciones.id_nivel', $idNivelForzado);
        } elseif ($request->filled('id_nivel')) {
            $query->where('proyecciones.id_nivel', $request->integer('id_nivel'));
        }

        if ($request->filled('id_resolucion')) {
            $query->where('pi.id_resolucion', $request->integer('id_resolucion'));
        }

        if ($request->filled('id_cargo')) {
            $query->where('pi.id_cargo', $request->integer('id_cargo'));
        }

        if ($request->filled('motivo')) {
            $query->where('pi.motivo', $request->string('motivo')->toString());
        }

        if ($request->filled('localidad')) {
            $query->whereHas('institucion', fn ($q) => $q->where('localidad', $request->string('localidad')->toString()));
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($q) use ($search, $anio): void {
                if (is_numeric($search)) {
                    $q->where('proyecciones.id', (int) $search);
                }

                $q->orWhere('pi.estado', 'ILIKE', "%{$search}%")
                    ->orWhere('pi.motivo', 'ILIKE', "%{$search}%")
                    ->orWhere('pi.destino_nuevo', 'ILIKE', "%{$search}%")
                    ->orWhere('pi.resolucion_ministerial', 'ILIKE', "%{$search}%")
                    ->orWhere('pi.n_expediente', 'ILIKE', "%{$search}%")
                    ->orWhere('proyecciones.id_puesto', 'ILIKE', "%{$search}%")
                    ->orWhere('pi.anio', 'ILIKE', "%{$search}%")
                    ->orWhereHas('institucion', function ($i) use ($search): void {
                        $i->where('nombre', 'ILIKE', "%{$search}%")
                            ->orWhere('localidad', 'ILIKE', "%{$search}%");
                    })
                    ->orWhereHas('instrumentos', function ($i) use ($search, $anio): void {
                        $i->where('anio', $anio)
                            ->where(function ($w) use ($search): void {
                                $w->whereHas('cargo', fn ($c) => $c->where('nombre', 'ILIKE', "%{$search}%")
                                    ->orWhere('codigo', 'ILIKE', "%{$search}%"))
                                    ->orWhereHas('resolucion', fn ($r) => $r->where('nombre', 'ILIKE', "%{$search}%"));
                            });
                    });
            });
        }

        $this->aplicarOrden($query, $request);

        $perPage = min($request->integer('per_page', 25), 100);
        $proyecciones = $query->paginate($perPage);

        return response()->json([
            'data' => ProyeccionResource::collection($proyecciones->items()),
            'meta' => [
                'current_page' => $proyecciones->currentPage(),
                'last_page' => $proyecciones->lastPage(),
                'per_page' => $proyecciones->perPage(),
                'total' => $proyecciones->total(),
                'anio' => $anio,
                'anios_disponibles' => $this->aniosDisponibles(),
            ],
        ]);
    }

    /**
     * Display the specified resource, con todo su historial de instrumentos.
     */
    public function show(int $id): JsonResponse
    {
        $proyeccion = Proyeccion::with($this->relacionesCompletas())->findOrFail($id);

        return response()->json([
            'data' => new ProyeccionResource($proyeccion),
        ]);
    }

    /**
     * Crea la plaza y, si viene, su primer instrumento (atómicamente).
     */
    public function store(StoreProyeccionRequest $request): JsonResponse
    {
        $data = $request->validated();
        $instrumentoData = $data['instrumento'] ?? null;
        unset($data['instrumento']);

        $proyeccion = DB::transaction(function () use ($data, $instrumentoData): Proyeccion {
            $proyeccion = Proyeccion::create($data);

            if ($instrumentoData !== null) {
                $proyeccion->instrumentos()->create($instrumentoData);
            }

            return $proyeccion;
        });

        $proyeccion->load($this->relacionesCompletas());

        return response()->json([
            'data' => new ProyeccionResource($proyeccion),
            'message' => 'Proyección creada exitosamente',
        ], Response::HTTP_CREATED);
    }

    /**
     * Actualiza la plaza. Los datos por año se editan vía el endpoint de instrumentos.
     */
    public function update(UpdateProyeccionRequest $request, int $id): JsonResponse
    {
        $proyeccion = Proyeccion::findOrFail($id);
        $proyeccion->update($request->validated());

        $proyeccion->load($this->relacionesCompletas());

        return response()->json([
            'data' => new ProyeccionResource($proyeccion),
            'message' => 'Proyección actualizada exitosamente',
        ]);
    }

    /**
     * Relaciones que arma el resource: plaza + historial completo.
     *
     * @return array<int|string, mixed>
     */
    private function relacionesCompletas(): array
    {
        return [
            'nivel',
            'institucion',
            'instrumentos' => fn ($q) => $q->with(['cargo', 'funcion', 'turno', 'resolucion'])
                ->orderByDesc('anio'),
        ];
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): Response
    {
        $proyeccion = Proyeccion::findOrFail($id);
        $proyeccion->delete();

        return response()->noContent();
    }

    /**
     * Stats agrupados por institución para el dashboard.
     * Acepta ?anio=XXXX (default: último con datos) y ?institucion_id=X.
     */
    public function statsByInstitucion(Request $request): JsonResponse
    {
        $anio = $this->resolverAnio($request);

        $query = ProyeccionInstrumento::query()
            ->with('proyeccion.institucion')
            ->where('anio', $anio)
            ->whereIn('motivo', [MotivoProyeccion::Creacion->value, MotivoProyeccion::Continuidad->value]);

        if ($request->filled('institucion_id')) {
            $query->whereHas('proyeccion', fn ($q) => $q->where('id_institucion', $request->integer('institucion_id')));
        }

        $grouped = $query->get()
            ->groupBy(fn (ProyeccionInstrumento $i) => $i->proyeccion?->id_institucion);

        $result = $grouped->map(function (Collection $items, $institucionId): array {
            $first = $items->first();
            $institucionName = $first?->proyeccion?->institucion?->nombre ?? "Institución #{$institucionId}";

            $creacionNoH = 0;
            $creacionHorasH = 0;
            $continuidadNoH = 0;
            $continuidadHorasH = 0;

            foreach ($items as $i) {
                $isCreacion = $i->motivo === MotivoProyeccion::Creacion;
                $horas = (int) ($i->horar ?? 0);
                $cargos = (int) ($i->cargos ?? 0);

                // Con horas -> bucket H (honorario); con cargos -> bucket No H (contratado)
                if ($isCreacion) {
                    $horas > 0 ? $creacionHorasH += $horas : $creacionNoH += $cargos > 0 ? $cargos : 1;
                } else {
                    $horas > 0 ? $continuidadHorasH += $horas : $continuidadNoH += $cargos > 0 ? $cargos : 1;
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
            'meta' => [
                'anio' => $anio,
                'anios_disponibles' => $this->aniosDisponibles(),
            ],
        ]);
    }

    /**
     * Stats por año para el dashboard (charts "cargos por año" y "horas por año").
     *
     * La agregación vive en el backend porque el listado solo expone un año
     * en foco por fila: la serie histórica necesita todos los instrumentos.
     *
     * Acepta ?institucion_id=X para filtrar una institución.
     */
    public function statsPorAnio(Request $request): JsonResponse
    {
        $institucionId = $request->integer('institucion_id') ?: null;

        $base = ProyeccionInstrumento::query()
            ->when($institucionId, function ($q) use ($institucionId): void {
                $q->whereHas('proyeccion', fn ($p) => $p->where('id_institucion', $institucionId));
            });

        // Cargos por año (solo instrumentos con cargo tipo 'C')
        $cargos = (clone $base)
            ->join('cargos as cargos_stats', 'cargos_stats.id', '=', 'proyeccion_instrumentos.id_cargo')
            ->where('cargos_stats.tipo', 'C')
            ->selectRaw('proyeccion_instrumentos.anio, COUNT(*) as total')
            ->groupBy('proyeccion_instrumentos.anio')
            ->orderBy('proyeccion_instrumentos.anio')
            ->get()
            ->map(fn ($r) => ['year' => (int) $r->anio, 'count' => (int) $r->total]);

        // Horas por año (solo instrumentos con cargo tipo 'H')
        $horas = (clone $base)
            ->join('cargos as horas_stats', 'horas_stats.id', '=', 'proyeccion_instrumentos.id_cargo')
            ->where('horas_stats.tipo', 'H')
            ->selectRaw('proyeccion_instrumentos.anio, SUM(proyeccion_instrumentos.horar) as total_horas')
            ->groupBy('proyeccion_instrumentos.anio')
            ->orderBy('proyeccion_instrumentos.anio')
            ->get()
            ->map(fn ($r) => ['year' => (int) $r->anio, 'totalHoras' => (int) ($r->total_horas ?? 0)]);

        return response()->json([
            'data' => [
                'cargos' => $cargos,
                'horas' => $horas,
            ],
            'meta' => [
                'anio' => $this->resolverAnio($request),
                'anios_disponibles' => $this->aniosDisponibles(),
            ],
        ]);
    }

    private function resolverAnio(Request $request): string
    {
        $anio = trim($request->string('anio')->toString());

        if ($anio !== '') {
            return $anio;
        }

        return (string) (ProyeccionInstrumento::query()->max('anio') ?? now()->year);
    }

    /**
     * @return Collection<int, string>
     */
    private function aniosDisponibles(): Collection
    {
        return ProyeccionInstrumento::query()
            ->distinct()
            ->orderByDesc('anio')
            ->pluck('anio');
    }

    private function aplicarOrden($query, Request $request): void
    {
        $allowedSorts = [
            'id', 'id_nivel', 'localidad', 'nombreInstitucion', 'cantidadDisplay', 'cargoDisplay',
            'año', 'anio', 'orden', 'estado', 'motivo', 'resolucionDisplay', 'destino_nuevo', 'id_puesto',
            'fecha_desde', 'fecha_hasta', 'n_expediente', 'id_institucion', 'id_cargo', 'id_funcion',
            'id_turno', 'id_resolucion', 'horar', 'cargos', 'created_at', 'updated_at',
        ];

        $sortBy = $request->string('sort_by')->toString() ?: 'id';
        if (! in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'id';
        }

        $sortDir = strtolower($request->string('sort_dir')->toString() ?: 'desc');
        if (! in_array($sortDir, ['asc', 'desc'], true)) {
            $sortDir = 'desc';
        }

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
                $query->leftJoin('cargos as cargo_sort', 'cargo_sort.id', '=', 'pi.id_cargo')
                    ->orderBy('cargo_sort.codigo', $sortDir)
                    ->orderBy('cargo_sort.nombre', $sortDir);
                break;
            case 'cantidadDisplay':
                $query->orderByRaw("COALESCE(NULLIF(pi.horar, 0), pi.cargos, 0) {$sortDir}");
                break;
            case 'resolucionDisplay':
            case 'id_resolucion':
                $query->leftJoin('resoluciones as resol_sort', 'resol_sort.id', '=', 'pi.id_resolucion')
                    ->orderBy('resol_sort.nombre', $sortDir);
                break;
            default:
                if ($sortBy === 'año' || $sortBy === 'anio') {
                    $query->orderBy('pi.anio', $sortDir);
                } elseif (in_array($sortBy, self::COLUMNAS_INSTRUMENTO, true)) {
                    $query->orderBy("pi.{$sortBy}", $sortDir);
                } elseif (in_array($sortBy, self::COLUMNAS_PLAZA, true)) {
                    $query->orderBy("proyecciones.{$sortBy}", $sortDir);
                } else {
                    $query->orderBy('proyecciones.id', $sortDir);
                }
                break;
        }
    }
}
