<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proyecciones', function (Blueprint $table) {
            $table->foreignId('id_resolucion')
                ->nullable()
                ->constrained('resoluciones')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('proyecciones', function (Blueprint $table) {
            $table->dropForeign(['id_resolucion']);
            $table->dropColumn('id_resolucion');
        });
    }
};
