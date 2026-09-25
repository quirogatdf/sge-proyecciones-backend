<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 1 del Camino 1: la tabla `proyeccion_instrumentos` pasa a ser la
 * fuente de verdad de todos los datos que varían por año. Se agregan,
 * nullable, las columnas que hoy viven en `proyecciones` y que se van a
 * mudar acá (más el motivo, que varía por año).
 *
 * Todas nullable para no romper las filas existentes; la obligatoriedad
 * se valida a nivel de request.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proyeccion_instrumentos', function (Blueprint $table): void {
            $table->string('estado')->nullable()->after('anio');
            $table->string('motivo')->nullable()->after('estado');
            $table->string('n_expediente')->nullable()->after('motivo');
            $table->date('fecha_desde')->nullable()->after('n_expediente');
            $table->date('fecha_hasta')->nullable()->after('fecha_desde');
            $table->string('resolucion_ministerial_ext')->nullable()->after('resolucion_ministerial');
            $table->string('disposicion_sgnij')->nullable()->after('resolucion_ministerial_ext');
            $table->string('rect_disposoco_sgnij')->nullable()->after('disposicion_sgnij');
            $table->string('resolucion_ministerial_rect1')->nullable()->after('rect_disposoco_sgnij');
            $table->string('resolucion_ministerial_rect2')->nullable()->after('resolucion_ministerial_rect1');
            $table->string('resolucion_previa_continuidad')->nullable()->after('resolucion_ministerial_rect2');
        });
    }

    public function down(): void
    {
        Schema::table('proyeccion_instrumentos', function (Blueprint $table): void {
            $table->dropColumn([
                'estado',
                'motivo',
                'n_expediente',
                'fecha_desde',
                'fecha_hasta',
                'resolucion_ministerial_ext',
                'disposicion_sgnij',
                'rect_disposoco_sgnij',
                'resolucion_ministerial_rect1',
                'resolucion_ministerial_rect2',
                'resolucion_previa_continuidad',
            ]);
        });
    }
};
