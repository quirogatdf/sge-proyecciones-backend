<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE proyecciones ALTER COLUMN orden TYPE INTEGER USING (CASE WHEN orden ~ '^[0-9]+$' THEN orden::INTEGER ELSE NULL END)");
        DB::statement("ALTER TABLE proyecciones ALTER COLUMN orden DROP NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE proyecciones ALTER COLUMN orden TYPE VARCHAR(4) USING (orden::VARCHAR)");
    }
};
