<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\Proyeccion;
use App\Services\ProyeccionExportService;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Illuminate\Support\Collection;

final class ProyeccionExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    private int $orden = 0;

    /**
     * @var Collection<int, Proyeccion>
     */
    private Collection $records;

    private ProyeccionExportService $service;

    public function __construct(Collection $records, ProyeccionExportService $service)
    {
        $this->records = $records;
        $this->service = $service;
    }

    /**
     * @return Collection<int, Proyeccion>
     */
    public function collection(): Collection
    {
        return $this->records;
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'Orden',
            'Cantidad',
            'Codigo',
            'Denominacion',
            'Con Funcion',
            'Turno',
            'Destino 2026',
            'Instrumento Legal',
            'Destino 2027',
        ];
    }

    /**
     * @param Proyeccion $proyeccion
     * @return array<int, mixed>
     */
    public function map(mixed $proyeccion): array
    {
        $this->orden++;

        return $this->service->transformRow($proyeccion, $this->orden);
    }
}
