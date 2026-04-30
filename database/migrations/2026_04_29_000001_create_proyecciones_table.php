<?php declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('proyecciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_nivel')->constrained('niveles');
            $table->enum('estado', ['Autorizado', 'Rechazado', 'Pendiente']);
            $table->string('n_expediente')->nullable();
            $table->enum('motivo', ['Creación', 'Continuidad', 'Baja', 'Sin definir']);
            $table->string('orden', 4)->nullable();
            $table->integer('horar')->nullable();
            $table->integer('cargos')->nullable();
            $table->foreignId('id_cargo')->constrained('cargos');
            $table->foreignId('id_funcion')->constrained('funciones');
            $table->foreignId('id_turno')->constrained('turnos');
            $table->date('fecha_desde');
            $table->date('fecha_hasta')->nullable();
            $table->foreignId('id_institucion')->constrained('instituciones');
            $table->string('resolucion_ministerial')->nullable();
            $table->string('resolucion_ministerial_ext')->nullable();
            $table->string('disposicion_sgnij')->nullable();
            $table->string('rect_disposoco_sgnij')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('proyecciones');
    }
};
