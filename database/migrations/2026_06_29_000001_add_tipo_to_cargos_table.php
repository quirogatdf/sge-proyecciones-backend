<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cargos', function (Blueprint $table) {
            $table->string('tipo', 1)->nullable();
        });

        // Add CHECK constraint at DB level: solo H (Honorario) o C (Contratado)
        // SQLite doesn't support ALTER TABLE ADD CONSTRAINT — skip in testing
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE cargos ADD CONSTRAINT cargos_tipo_check CHECK (tipo IS NULL OR tipo IN (\'H\', \'C\'))');
        }
    }

    public function down(): void
    {
        Schema::table('cargos', function (Blueprint $table) {
            $table->dropColumn('tipo');
        });
    }
};
