<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Exports\ProyeccionExport;
use App\Http\Controllers\Controller;
use App\Services\ProyeccionExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ProyeccionExportController extends Controller
{
    public function __construct(
        private readonly ProyeccionExportService $exportService,
    ) {}

    /**
     * Export proyecciones to Excel.
     *
     * Query params:
     *   - motivo: 'Continuidad' | 'Creacion' (default: 'Continuidad')
     *   - id_nivel: int (optional)
     *   - id_institucion: int (optional)
     *   - id_cargo: int (optional)
     *
     * @return StreamedResponse|JsonResponse
     */
    public function export(Request $request)
    {
        $validated = $request->validate([
            'motivo'          => 'nullable|string|in:Continuidad,Creacion,Creación',
            'id_nivel'        => 'nullable|integer|exists:niveles,id',
            'id_institucion'  => 'nullable|integer|exists:instituciones,id',
            'id_cargo'        => 'nullable|integer|exists:cargos,id',
            'anio'            => 'nullable|string|size:4',
        ]);

        try {
            $data = $this->exportService->getExportData($validated);

            $filename = $this->buildFilename($validated['motivo'] ?? 'todos');

            return Excel::download(
                new ProyeccionExport($data['records'], $this->exportService),
                $filename,
            );
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 413);
        }
    }

    private function buildFilename(string $motivo): string
    {
        $timestamp = now()->format('Y-m-d_H-i');
        $safeMotivo = str_replace(' ', '_', mb_strtolower($motivo));

        return "proyecciones_{$safeMotivo}_{$timestamp}.xlsx";
    }
}
