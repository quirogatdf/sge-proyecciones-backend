<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 2 del Camino 1: backfill. Copia, para cada proyección existente, los
 * valores de su año actual (`proyecciones.año`) al snapshot correspondiente
 * en `proyeccion_instrumentos`.
 *
 * Idempotente: si ya existe el snapshot (proyeccion_id, anio) lo actualiza;
 * si no, lo crea. NO toca snapshots de otros años (historial futuro).
 *
 * Usa el query builder (no Eloquent) para no depender de los modelos, que
 * van a seguir cambiando durante el Camino 1.
 */
return new class extends Migration
{
    private const CAMPOS_MOVIDOS = [
        'estado',
        'motivo',
        'n_expediente',
        'fecha_desde',
        'fecha_hasta',
        'id_resolucion',
        'resolucion_ministerial',
        'resolucion_ministerial_ext',
        'disposicion_sgnij',
        'rect_disposoco_sgnij',
        'resolucion_ministerial_rect1',
        'resolucion_ministerial_rect2',
        'resolucion_previa_continuidad',
        'id_cargo',
        'id_funcion',
        'id_turno',
        'horar',
        'cargos',
        'destino_anterior',
        'destino_nuevo',
    ];

    public function up(): void
    {
        if (! Schema::hasColumn('proyecciones', 'año')) {
            return; // Ya migrado / esquema nuevo
        }

        // Guard anti-pérdida: si existe una fila sin año, el backfill no puede
        // copiarla (anio es NOT NULL en el destino) y el drop 000004 la
        // destruiría en silencio. Fallamos con los ids para que se resuelva
        // antes de tocar producción. Como el guard corre ANTES de insertar,
        // no se escribe nada y el migrate queda en un estado consistente.
        $sinAnio = DB::table('proyecciones')
            ->whereNull('año')
            ->orWhere('año', '=', '')
            ->pluck('id')
            ->all();

        if ($sinAnio !== []) {
            throw new RuntimeException(
                'Hay ' . count($sinAnio) . ' proyecciones sin año que NO se pueden migrar. '
                . 'Ids: [' . implode(', ', $sinAnio) . ']. '
                . 'Completá el año antes de migrar (o eliminá esas filas con respaldo previo) '
                . 'para no perder datos.'
            );
        }

        $proyecciones = DB::table('proyecciones')
            ->whereNotNull('año')
            ->where('año', '!=', '')
            ->get();

        foreach ($proyecciones as $p) {
            $anio = trim((string) $p->año);

            if ($anio === '') {
                continue;
            }

            $valores = [];
            foreach (self::CAMPOS_MOVIDOS as $campo) {
                $valores[$campo] = $p->{$campo} ?? null;
            }

            // `orden` en proyecciones es varchar; en el instrumento es integer.
            $valores['orden'] = is_numeric($p->orden) ? (int) $p->orden : null;

            $clave = ['proyeccion_id' => $p->id, 'anio' => $anio];

            $existe = DB::table('proyeccion_instrumentos')->where($clave)->exists();

            if ($existe) {
                DB::table('proyeccion_instrumentos')
                    ->where($clave)
                    ->update($valores + ['updated_at' => now()]);
            } else {
                DB::table('proyeccion_instrumentos')->insert(
                    $clave + $valores + ['created_at' => now(), 'updated_at' => now()]
                );
            }
        }
    }

    public function down(): void
    {
        // El backfill no se revierte: los datos ya viven en el instrumento.
    }
};
