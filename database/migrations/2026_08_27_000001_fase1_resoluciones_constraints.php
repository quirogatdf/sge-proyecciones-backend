<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('resoluciones', function (Blueprint $table): void {
            $table->unique('nombre');
        });

        Schema::table('proyecciones', function (Blueprint $table): void {
            $table->dropForeign(['id_resolucion']);
            $table->foreign('id_resolucion')->references('id')->on('resoluciones')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::table('proyecciones', function (Blueprint $table): void {
            $table->dropForeign(['id_resolucion']);
            $table->foreign('id_resolucion')->references('id')->on('resoluciones')->onDelete('set null');
        });

        Schema::table('resoluciones', function (Blueprint $table): void {
            $table->dropUnique(['nombre']);
        });
    }
};
