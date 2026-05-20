<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('proyecciones', function (Blueprint $table) {
            // Hacer año obligatorio
            $table->string('año', 4)->nullable(false)->change();

            // Hacer orden obligatorio
            $table->string('orden', 4)->nullable(false)->change();

            // Agregar nuevos campos destino_anterior y destino_nuevo
            $table->string('destino_anterior')->nullable();
            $table->string('destino_nuevo')->nullable();
        });
    }

    public function down(): void {
        Schema::table('proyecciones', function (Blueprint $table) {
            // Revertir año a nullable
            $table->string('año', 4)->nullable()->change();

            // Revertir orden a nullable
            $table->string('orden', 4)->nullable()->change();

            // Eliminar los nuevos campos
            $table->dropColumn(['destino_anterior', 'destino_nuevo']);
        });
    }
};