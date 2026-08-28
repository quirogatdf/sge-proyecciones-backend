<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Proyeccion;
use App\Models\Resolucion;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class LinkResolucionesCommand extends Command
{
    protected $signature = 'proyecciones:link-resoluciones {--dry-run : Solo auditoría sin modificar BD} {--execute : Ejecuta linkeo real} {--clear-string : Vacía resolucion_ministerial tras linkear (default true con --execute)}';

    protected $description = 'Linkea proyecciones.resolucion_ministerial con resoluciones existentes (normaliza N° a 4 dígitos)';

    public function handle(): int
    {
        $isDryRun = (bool) $this->option('dry-run');
        $isExecute = (bool) $this->option('execute');

        $hasClearOption = $this->input->hasParameterOption('--clear-string');
        $clearRaw = $this->option('clear-string');
        if ($hasClearOption) {
            $argv = $_SERVER['argv'] ?? [];
            $clearStringArg = null;
            foreach ($argv as $arg) {
                if (str_starts_with($arg, '--clear-string')) {
                    $clearStringArg = $arg;
                    break;
                }
            }
            if ($clearStringArg !== null && str_contains($clearStringArg, '=')) {
                $val = explode('=', $clearStringArg, 2)[1];
                $shouldClear = ! in_array(strtolower($val), ['0', 'false', 'no', 'off'], true);
            } else {
                $shouldClear = (bool) $clearRaw;
            }
        } else {
            $shouldClear = $isExecute;
        }

        $resoluciones = Resolucion::all(['id', 'nombre']);
        $map = [];
        $resolucionNames = [];

        foreach ($resoluciones as $res) {
            $norm = $this->normalize($res->nombre);
            if ($norm === null) {
                $this->warn("Resolución ID {$res->id} no normalizable: '{$res->nombre}'");

                continue;
            }
            if (isset($map[$norm])) {
                $this->warn("Duplicado normalizado '{$norm}' IDs {$map[$norm]} y {$res->id} — se usa la primera.");

                continue;
            }
            $map[$norm] = $res->id;
            $resolucionNames[$res->id] = $res->nombre;
        }

        $this->info('Resoluciones cargadas: '.count($map).' normalizables de '.$resoluciones->count().' totales.');

        $proyecciones = Proyeccion::whereNotNull('resolucion_ministerial')
            ->where('resolucion_ministerial', '!=', '')
            ->get(['id', 'resolucion_ministerial', 'id_resolucion']);

        $alreadyLinked = Proyeccion::whereNotNull('id_resolucion')->count();

        $rows = [];
        $linkables = [];
        $orphans = 0;
        $invalids = 0;

        foreach ($proyecciones as $proj) {
            $raw = $proj->resolucion_ministerial ?? '';
            $norm = $this->normalize($raw);

            if ($norm === null) {
                $invalids++;
                $rows[] = [$proj->id, $raw, 'INVALIDO', 'INVALIDO', 'IGNORAR'];

                continue;
            }

            if (isset($map[$norm])) {
                $resId = $map[$norm];
                $resNombre = $resolucionNames[$resId];
                $accion = $proj->id_resolucion ? 'YA LINKEADA' : 'LINKEAR';
                $rows[] = [$proj->id, $raw, $norm, "{$resId} / {$resNombre}", $accion];
                if (! $proj->id_resolucion) {
                    $linkables[] = ['proyeccion' => $proj, 'resolucion_id' => $resId, 'normalized' => $norm];
                }
            } else {
                $orphans++;
                $rows[] = [$proj->id, $raw, $norm, 'NO EXISTE', 'ORPHAN'];
            }
        }

        if (empty($rows)) {
            $this->info('No hay proyecciones con resolucion_ministerial para procesar.');
        } else {
            $this->table(['ID', 'Raw', 'Normalizado', 'Resolución ID/Nombre', 'Acción'], $rows);
        }

        $total = $proyecciones->count();
        $linkableCount = count($linkables);

        $this->info("Resumen: total={$total}, linkables={$linkableCount}, orphans={$orphans}, invalids={$invalids}, ya linkeadas={$alreadyLinked}");

        if (! $isExecute || $isDryRun) {
            $this->info('Dry-run: no se modificó la BD. Usa --execute para aplicar.');

            return self::SUCCESS;
        }

        if ($linkableCount === 0) {
            $this->info('Nada para linkear.');

            return self::SUCCESS;
        }

        $this->warn("Se linkearán {$linkableCount} proyecciones".($shouldClear ? ' y se vaciará resolucion_ministerial' : ' (manteniendo resolucion_ministerial)').'.');

        if (! $this->confirm('¿Confirmás la ejecución?', false)) {
            $this->info('Cancelado.');

            return self::SUCCESS;
        }

        $updated = 0;
        $chunks = array_chunk($linkables, 500);

        foreach ($chunks as $index => $chunk) {
            DB::transaction(function () use ($chunk, $shouldClear, &$updated) {
                foreach ($chunk as $item) {
                    $proj = $item['proyeccion'];
                    $data = ['id_resolucion' => $item['resolucion_id']];
                    if ($shouldClear) {
                        $data['resolucion_ministerial'] = null;
                    }
                    DB::table('proyecciones')->where('id', $proj->id)->update($data);
                    $updated++;
                }
            });
            $this->info('Batch '.($index + 1).'/'.count($chunks)." procesado ({$updated}/{$linkableCount})");
        }

        $this->info("Listo: {$updated} proyecciones linkeadas.");

        return self::SUCCESS;
    }

    private function normalize(string $raw): ?string
    {
        if (preg_match('/(\d{1,4})\s*\/\s*(\d{4})/', $raw, $m)) {
            $num = str_pad($m[1], 4, '0', STR_PAD_LEFT);
            $year = $m[2];

            return "Resolución M.ED. N° {$num}/{$year}";
        }

        return null;
    }
}
