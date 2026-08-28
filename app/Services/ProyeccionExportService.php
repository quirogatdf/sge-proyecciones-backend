<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Proyeccion;
use Illuminate\Support\Collection;

final class ProyeccionExportService
{
    private const MAX_RECORDS = 10_000;

    /**
     * Build query with filters and return collection for export.
     *
     * @param array{
     *   motivo?: string,
     *   id_nivel?: int,
     *   id_institucion?: int,
     *   id_cargo?: int,
     *   id_resolucion?: int,
     *   anio?: string,
     * } $filters
     * @return array{records: Collection, total: int}
     *
     * @throws \RuntimeException When record limit exceeded
     */
    public function getExportData(array $filters): array
    {
        $query = Proyeccion::query()
            ->with(['cargo', 'funcion', 'turno', 'institucion', 'resolucion']);

        // Optional filters
        if (! empty($filters['motivo'])) {
            // Normalizar 'Creacion' -> 'Creación' para coincidir con el enum/DB
            $motivo = $filters['motivo'] === 'Creacion' ? 'Creación' : $filters['motivo'];
            $query->where('motivo', $motivo);
        }
        if (! empty($filters['id_nivel'])) {
            $query->where('id_nivel', $filters['id_nivel']);
        }
        if (! empty($filters['id_institucion'])) {
            $query->where('id_institucion', $filters['id_institucion']);
        }
        if (! empty($filters['id_cargo'])) {
            $query->where('id_cargo', $filters['id_cargo']);
        }
        if (! empty($filters['anio'])) {
            $query->where('año', $filters['anio']);
        }
        if (! empty($filters['id_resolucion'])) {
            $query->where('id_resolucion', $filters['id_resolucion']);
        }

        // Check total count before loading
        $total = $query->count();

        if ($total > self::MAX_RECORDS) {
            throw new \RuntimeException(
                'Demasiados registros para exportar. Use filtros más específicos.'
            );
        }

        // Sort by institution name then order
        $records = $query
            ->join('instituciones', 'proyecciones.id_institucion', '=', 'instituciones.id')
            ->orderBy('instituciones.nombre', 'asc')
            ->orderBy('proyecciones.orden', 'asc')
            ->select('proyecciones.*')
            ->get();

        return ['records' => $records, 'total' => $total];
    }

    /**
     * Transform a proyeccion record into export row format.
     *
     * @param  int  $orden  Sequential order number
     * @return array<int, mixed>
     */
    public function transformRow(Proyeccion $proyeccion, int $orden): array
    {
        $cargo = $proyeccion->cargo;
        $funcion = $proyeccion->funcion;
        $turno = $proyeccion->turno;
        $institucion = $proyeccion->institucion;
        $resolucion = $proyeccion->resolucion;

        // Cantidad: use horar for tipo 'H', cargos for tipo 'C', or max of both
        $cantidad = $this->calculateCantidad($proyeccion);

        // Instrumento Legal: nombre de la resolucion concatenado con " - (Orden N° {orden})"
        $nombreResolucion = $resolucion?->nombre ?? $proyeccion->resolucion_ministerial ?? null;
        $instrumentoLegal = null;
        if ($nombreResolucion !== null && $nombreResolucion !== '') {
            $ordenValue = $proyeccion->orden;
            $instrumentoLegal = $ordenValue !== null ? "{$nombreResolucion} - (Orden N° {$ordenValue})" : $nombreResolucion;
        }

        return [
            $orden,                                          // Orden (secuencial)
            $institucion?->nombre ?? '',                    // Institucion
            $cantidad,                                      // Cantidad
            $cargo?->codigo ?? '',                          // Codigo
            $cargo?->nombre ?? '',                          // Denominacion
            $funcion?->nombre ?? '',                        // Con Funcion
            $turno?->sigla ?? '',                           // Turno (inicial)
            $proyeccion->destino_nuevo ?? null,             // Destino 2026
            $instrumentoLegal,                              // Instrumento Legal (resolucion + orden)
            null,                                           // Destino 2027 (always null)
        ];
    }

    /**
     * Calculate Cantidad based on cargo type.
     */
    private function calculateCantidad(Proyeccion $proyeccion): ?int
    {
        $cargo = $proyeccion->cargo;
        $horar = $proyeccion->horar;
        $cargos = $proyeccion->cargos;

        if ($cargo?->tipo === 'H' && $horar !== null) {
            return $horar;
        }

        if ($cargo?->tipo === 'C' && $cargos !== null) {
            return $cargos;
        }

        // Fallback: use whichever is greater
        $h = $horar ?? 0;
        $c = $cargos ?? 0;

        if ($h > 0 || $c > 0) {
            return max($h, $c);
        }

        return null;
    }
}
