<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 5 del Camino 1: `proyecciones` pasa a ser SOLO la plaza
 * (id_nivel + id_institucion + id_puesto). Todo lo que varía por año vive
 * en `proyeccion_instrumentos` y ya fue copiado por el backfill
 * 2026_09_24_000003.
 */
return new class extends Migration
{
    /**
     * Columnas movidas al instrumento.
     */
    private const COLUMNAS_MOVIDAS = [
        'estado', 'n_expediente', 'motivo', 'orden', 'horar', 'cargos',
        'fecha_desde', 'fecha_hasta',
        'resolucion_ministerial', 'resolucion_ministerial_ext',
        'disposicion_sgnij', 'rect_disposoco_sgnij',
        'año', 'resolucion_ministerial_rect1', 'resolucion_ministerial_rect2',
        'resolucion_previa_continuidad', 'destino_anterior', 'destino_nuevo',
    ];

    /**
     * Columnas con foreign key (se dropea la constraint antes).
     */
    private const COLUMNAS_FK = ['id_cargo', 'id_funcion', 'id_turno', 'id_resolucion'];

    public function up(): void
    {
        Schema::table('proyecciones', function (Blueprint $table): void {
            foreach (self::COLUMNAS_FK as $columna) {
                $table->dropConstrainedForeignId($columna);
            }

            $table->dropColumn(self::COLUMNAS_MOVIDAS);
        });
    }

    public function down(): void
    {
        Schema::table('proyecciones', function (Blueprint $table): void {
            // Se re-crean como nullable para permitir el rollback sin pérdida.
            $table->string('estado')->nullable();
            $table->string('n_expediente')->nullable();
            $table->string('motivo')->nullable();
            $table->string('orden', 4)->nullable();
            $table->integer('horar')->nullable();
            $table->integer('cargos')->nullable();
            $table->date('fecha_desde')->nullable();
            $table->date('fecha_hasta')->nullable();
            $table->string('resolucion_ministerial')->nullable();
            $table->string('resolucion_ministerial_ext')->nullable();
            $table->string('disposicion_sgnij')->nullable();
            $table->string('rect_disposoco_sgnij')->nullable();
            $table->string('año', 4)->nullable();
            $table->string('resolucion_ministerial_rect1')->nullable();
            $table->string('resolucion_ministerial_rect2')->nullable();
            $table->string('resolucion_previa_continuidad')->nullable();
            $table->string('destino_anterior')->nullable();
            $table->string('destino_nuevo')->nullable();

            $table->foreignId('id_cargo')->nullable()->constrained('cargos');
            $table->foreignId('id_funcion')->nullable()->constrained('funciones');
            $table->foreignId('id_turno')->nullable()->constrained('turnos');
            $table->foreignId('id_resolucion')->nullable()->constrained('resoluciones')->onDelete('set null');
        });
    }
};
