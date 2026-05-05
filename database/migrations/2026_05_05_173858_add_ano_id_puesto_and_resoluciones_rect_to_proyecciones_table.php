<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('proyecciones', function (Blueprint $table) {
            $table->string('año', 4)->nullable();
            $table->string('id_puesto')->nullable();
            $table->string('resolucion_ministerial_rect1')->nullable();
            $table->string('resolucion_ministerial_rect2')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proyecciones', function (Blueprint $table) {
            $table->dropColumn(['año', 'id_puesto', 'resolucion_ministerial_rect1', 'resolucion_ministerial_rect2']);
        });
    }
};
