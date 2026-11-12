<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Proyeccion;
use App\Models\ProyeccionInstrumento;
use App\Models\Resolucion;
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

    public function test_transform_row_incluye_campos_de_instrumento_y_nombres_de_rectificaciones(): void
    {
        $proyeccion = Proyeccion::factory()->create();
        $rect1 = Resolucion::factory()->create(['nombre' => 'RES-RECT-1']);
        $rect2 = Resolucion::factory()->create(['nombre' => 'RES-RECT-2']);

        $instrumento = ProyeccionInstrumento::factory()->create([
            'proyeccion_id' => $proyeccion->id,
            'anio' => '2026',
            'resolucion_ministerial_ext' => 'EXT-001',
            'disposicion_sgnij' => 'DISP-001',
            'rect_disposoco_sgnij' => 'RECT-DISP-001',
            'resolucion_previa_continuidad' => 'CONT-001',
            'id_resolucion_rect1' => $rect1->id,
            'id_resolucion_rect2' => $rect2->id,
        ]);

        $row = $this->service->transformRow($instrumento->fresh(), 1);

        $this->assertSame('EXT-001', $row[9]);
        $this->assertSame('DISP-001', $row[10]);
        $this->assertSame('RECT-DISP-001', $row[11]);
        $this->assertSame('CONT-001', $row[12]);
        $this->assertSame('RES-RECT-1', $row[13]);
        $this->assertSame('RES-RECT-2', $row[14]);
        $this->assertNull($row[15]);
    }
}
