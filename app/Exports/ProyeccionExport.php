<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\ProyeccionInstrumento;
use App\Services\ProyeccionExportService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

final class ProyeccionExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    private int $orden = 0;

    /**
     * @var Collection<int, ProyeccionInstrumento>
     */
    private Collection $records;

    private ProyeccionExportService $service;

    public function __construct(Collection $records, ProyeccionExportService $service)
    {
        $this->records = $records;
        $this->service = $service;
    }

    /**
     * @return Collection<int, ProyeccionInstrumento>
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
            'Institucion',
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
     * @param  ProyeccionInstrumento  $proyeccion
     * @return array<int, mixed>
     */
    public function map(mixed $proyeccion): array
    {
        $this->orden++;

        return $this->service->transformRow($proyeccion, $this->orden);
    }
}
