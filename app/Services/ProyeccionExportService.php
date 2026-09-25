<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ProyeccionInstrumento;
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
        $query = ProyeccionInstrumento::query()
            ->with(['cargo', 'funcion', 'turno', 'resolucion', 'proyeccion.institucion']);

        // Optional filters
        if (! empty($filters['motivo'])) {
            // Normalizar 'Creacion' -> 'Creación' para coincidir con el enum/DB
            $motivo = $filters['motivo'] === 'Creacion' ? 'Creación' : $filters['motivo'];
            $query->where('proyeccion_instrumentos.motivo', $motivo);
        }
        if (! empty($filters['id_cargo'])) {
            $query->where('proyeccion_instrumentos.id_cargo', $filters['id_cargo']);
        }
        if (! empty($filters['id_resolucion'])) {
            $query->where('proyeccion_instrumentos.id_resolucion', $filters['id_resolucion']);
        }
        if (! empty($filters['anio'])) {
            $query->where('proyeccion_instrumentos.anio', $filters['anio']);
        }
        if (! empty($filters['id_nivel'])) {
            $query->whereHas('proyeccion', fn ($p) => $p->where('id_nivel', $filters['id_nivel']));
        }
        if (! empty($filters['id_institucion'])) {
            $query->whereHas('proyeccion', fn ($p) => $p->where('id_institucion', $filters['id_institucion']));
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
            ->join('proyecciones', 'proyecciones.id', '=', 'proyeccion_instrumentos.proyeccion_id')
            ->join('instituciones', 'proyecciones.id_institucion', '=', 'instituciones.id')
            ->orderBy('instituciones.nombre', 'asc')
            ->orderBy('proyeccion_instrumentos.orden', 'asc')
            ->select('proyeccion_instrumentos.*')
            ->get();

        return ['records' => $records, 'total' => $total];
    }

    /**
     * Transform an instrumento record into export row format.
     *
     * @param  int  $orden  Sequential order number
     * @return array<int, mixed>
     */
    public function transformRow(ProyeccionInstrumento $instrumento, int $orden): array
    {
        $cargo = $instrumento->cargo;
        $funcion = $instrumento->funcion;
        $turno = $instrumento->turno;
        $institucion = $instrumento->proyeccion?->institucion;
        $resolucion = $instrumento->resolucion;

        // Cantidad: use horar for tipo 'H', cargos for tipo 'C', or max of both
        $cantidad = $this->calculateCantidad($instrumento);

        // Instrumento Legal: nombre de la resolucion concatenado con " - (Orden N° {orden})"
        $nombreResolucion = $resolucion?->nombre ?? $instrumento->resolucion_ministerial ?? null;
        $instrumentoLegal = null;
        if ($nombreResolucion !== null && $nombreResolucion !== '') {
            $ordenValue = $instrumento->orden;
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
            $instrumento->destino_nuevo ?? null,            // Destino 2026
            $instrumentoLegal,                              // Instrumento Legal (resolucion + orden)
            null,                                           // Destino 2027 (always null)
        ];
    }

    /**
     * Calculate Cantidad based on cargo type.
     */
    private function calculateCantidad(ProyeccionInstrumento $instrumento): ?int
    {
        $cargo = $instrumento->cargo;
        $horar = $instrumento->horar;
        $cargos = $instrumento->cargos;

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
