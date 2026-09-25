<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proyeccion_instrumentos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('proyeccion_id')
                ->constrained('proyecciones')
                ->onDelete('cascade');

            // Año sin acento para evitar quoting en Postgres (proyecciones."año")
            $table->string('anio', 4);

            // Snapshot instrumento legal
            $table->foreignId('id_resolucion')
                ->nullable()
                ->constrained('resoluciones')
                ->onDelete('set null');
            $table->integer('orden')->nullable();
            $table->string('resolucion_ministerial')->nullable();

            // Snapshot cargo / función / turno
            $table->foreignId('id_cargo')
                ->nullable()
                ->constrained('cargos')
                ->onDelete('set null');
            $table->foreignId('id_funcion')
                ->nullable()
                ->constrained('funciones')
                ->onDelete('set null');
            $table->foreignId('id_turno')
                ->nullable()
                ->constrained('turnos')
                ->onDelete('set null');

            // Snapshot cantidades
            $table->integer('horar')->nullable();
            $table->integer('cargos')->nullable();

            // Snapshot destinos
            $table->string('destino_anterior')->nullable();
            $table->string('destino_nuevo')->nullable();

            $table->text('observaciones')->nullable();
            $table->timestamps();

            // Un snapshot por proyección por año
            $table->unique(['proyeccion_id', 'anio']);
            $table->index('anio');
            $table->index('proyeccion_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proyeccion_instrumentos');
    }
};
