<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Los campos de resolución de rectificación 1 y 2 dejan de ser texto libre y
 * pasan a ser FKs a `resoluciones`.
 *
 * Antes de convertir:
 *   1. se agregan las columnas nuevas (nullable);
 *   2. por cada valor de texto no vacío se busca una resolución existente por
 *      nombre (normalizado) o se crea una nueva;
 *   3. se completan las FKs;
 *   4. recién ahí se eliminan las columnas de texto.
 *
 * El backfill es conservador: nunca descarta un valor. Si no encuentra una
 * resolución por nombre, la crea. El año se toma del nombre (último año de 4
 * dígitos) o, si no hay, del año del instrumento.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proyeccion_instrumentos', function (Blueprint $table): void {
            $table->foreignId('id_resolucion_rect1')
                ->nullable()
                ->index()
                ->after('rect_disposoco_sgnij');
            $table->foreignId('id_resolucion_rect2')
                ->nullable()
                ->index()
                ->after('id_resolucion_rect1');
        });

        $this->migrarRectificaciones();

        Schema::table('proyeccion_instrumentos', function (Blueprint $table): void {
            $table->foreign('id_resolucion_rect1')
                ->references('id')
                ->on('resoluciones')
                ->nullOnDelete();
            $table->foreign('id_resolucion_rect2')
                ->references('id')
                ->on('resoluciones')
                ->nullOnDelete();
        });

        Schema::table('proyeccion_instrumentos', function (Blueprint $table): void {
            $table->dropColumn([
                'resolucion_ministerial_rect1',
                'resolucion_ministerial_rect2',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('proyeccion_instrumentos', function (Blueprint $table): void {
            $table->dropForeign(['id_resolucion_rect1']);
            $table->dropForeign(['id_resolucion_rect2']);
        });

        Schema::table('proyeccion_instrumentos', function (Blueprint $table): void {
            $table->string('resolucion_ministerial_rect1')
                ->nullable()
                ->after('rect_disposoco_sgnij');
            $table->string('resolucion_ministerial_rect2')
                ->nullable()
                ->after('resolucion_ministerial_rect1');
        });

        // Restaurar el texto desde la resolución vinculada antes de borrar los IDs.
        DB::table('proyeccion_instrumentos')->update([
            'resolucion_ministerial_rect1' => DB::raw(
                '(select nombre from resoluciones where resoluciones.id = proyeccion_instrumentos.id_resolucion_rect1)'
            ),
            'resolucion_ministerial_rect2' => DB::raw(
                '(select nombre from resoluciones where resoluciones.id = proyeccion_instrumentos.id_resolucion_rect2)'
            ),
        ]);

        Schema::table('proyeccion_instrumentos', function (Blueprint $table): void {
            $table->dropColumn(['id_resolucion_rect1', 'id_resolucion_rect2']);
        });
    }

    /**
     * Resuelve cada valor de texto a un ID de `resoluciones`, creando la
     * resolución cuando no existe.
     */
    private function migrarRectificaciones(): void
    {
        // Clave normalizada => id. Evita duplicar por mayúsculas/espacios.
        $porNombre = [];
        foreach (DB::table('resoluciones')->select('id', 'nombre')->orderBy('id')->get() as $resolucion) {
            $clave = $this->normalizar((string) $resolucion->nombre);
            $porNombre[$clave] ??= (int) $resolucion->id;
        }

        DB::table('proyeccion_instrumentos')
            ->select([
                'id',
                'anio',
                'resolucion_ministerial_rect1',
                'resolucion_ministerial_rect2',
            ])
            ->orderBy('id')
            ->chunkById(500, function ($instrumentos) use (&$porNombre): void {
                foreach ($instrumentos as $instrumento) {
                    $rect1 = $this->resolverId(
                        $instrumento->resolucion_ministerial_rect1,
                        (string) $instrumento->anio,
                        $porNombre,
                    );
                    $rect2 = $this->resolverId(
                        $instrumento->resolucion_ministerial_rect2,
                        (string) $instrumento->anio,
                        $porNombre,
                    );

                    if ($rect1 === null && $rect2 === null) {
                        continue;
                    }

                    DB::table('proyeccion_instrumentos')
                        ->where('id', $instrumento->id)
                        ->update([
                            'id_resolucion_rect1' => $rect1,
                            'id_resolucion_rect2' => $rect2,
                        ]);
                }
            });
    }

    /**
     * @param  array<string, int>  $porNombre
     */
    private function resolverId(?string $valor, string $anioInstrumento, array &$porNombre): ?int
    {
        $nombre = trim((string) $valor);
        if ($nombre === '') {
            return null;
        }

        $clave = $this->normalizar($nombre);
        if (isset($porNombre[$clave])) {
            return $porNombre[$clave];
        }

        // Compatibilidad: si el dato viejo ya era un ID numérico y esa
        // resolución existe, se reutiliza en lugar de crear "123" como nombre.
        if (ctype_digit($nombre)) {
            $id = DB::table('resoluciones')->where('id', (int) $nombre)->value('id');
            if ($id !== null) {
                return $porNombre[$clave] = (int) $id;
            }
        }

        try {
            $id = DB::table('resoluciones')->insertGetId([
                'nombre' => $nombre,
                'año' => $this->resolverAnio($nombre, $anioInstrumento),
                'tipo' => 'Sin definir',
                'observacion' => null,
                'url' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (QueryException $e) {
            // Carrera con otra inserción del mismo nombre: usar la existente.
            $id = DB::table('resoluciones')->where('nombre', $nombre)->value('id');
            if ($id === null) {
                throw $e;
            }
        }

        return $porNombre[$clave] = (int) $id;
    }

    private function resolverAnio(string $nombre, string $anioInstrumento): int
    {
        if (preg_match_all('/(19|20)\d{2}/', $nombre, $coincidencias) > 0) {
            return (int) end($coincidencias[0]);
        }

        $anio = (int) $anioInstrumento;
        if ($anio >= 1900 && $anio <= 2100) {
            return $anio;
        }

        return (int) date('Y');
    }

    private function normalizar(string $nombre): string
    {
        return mb_strtolower(trim($nombre));
    }
};
