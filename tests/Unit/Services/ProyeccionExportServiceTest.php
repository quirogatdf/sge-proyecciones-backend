<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Proyeccion;
use App\Models\ProyeccionInstrumento;
use App\Services\ProyeccionExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProyeccionExportServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProyeccionExportService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ProyeccionExportService;
    }

    public function test_get_export_data_filtra_por_anio(): void
    {
        $proyeccion = Proyeccion::factory()->create();
        ProyeccionInstrumento::factory()->create(['proyeccion_id' => $proyeccion->id, 'anio' => '2025']);
        ProyeccionInstrumento::factory()->create(['proyeccion_id' => $proyeccion->id, 'anio' => '2026']);

        $data = $this->service->getExportData(['anio' => '2026']);

        $this->assertSame(1, $data['total']);
        $this->assertSame('2026', $data['records']->first()->anio);
    }

    public function test_get_export_data_filtra_por_motivo_y_nivel(): void
    {
        $proyeccion = Proyeccion::factory()->create();
        ProyeccionInstrumento::factory()->create([
            'proyeccion_id' => $proyeccion->id,
            'anio' => '2026',
            'motivo' => 'Continuidad',
        ]);
        ProyeccionInstrumento::factory()->create([
            'proyeccion_id' => $proyeccion->id,
            'anio' => '2025',
            'motivo' => 'Creación',
        ]);

        $data = $this->service->getExportData([
            'anio' => '2026',
            'motivo' => 'Continuidad',
            'id_nivel' => $proyeccion->id_nivel,
        ]);

        $this->assertSame(1, $data['total']);
    }

    public function test_transform_row_arma_el_instrumento_legal(): void
    {
        $proyeccion = Proyeccion::factory()->create();
        $instrumento = ProyeccionInstrumento::factory()->create([
            'proyeccion_id' => $proyeccion->id,
            'anio' => '2026',
            'orden' => 7,
            'id_resolucion' => null,
            'resolucion_ministerial' => 'RES-007',
        ]);

        $row = $this->service->transformRow($instrumento->fresh(), 1);

        // Columna "Instrumento Legal" = resolucion + orden
        $this->assertStringContainsString('RES-007', $row[8]);
        $this->assertStringContainsString('Orden N° 7', $row[8]);
    }
}
