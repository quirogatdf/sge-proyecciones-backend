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
        if (DB::getDriverName() === 'sqlite') {
            // SQLite doesn't support ALTER COLUMN, and it's not strict about column types,
            // so there's nothing to change. The column already accepts any type.
            return;
        }

        DB::statement("ALTER TABLE proyecciones ALTER COLUMN orden TYPE INTEGER USING (CASE WHEN orden ~ '^[0-9]+$' THEN orden::INTEGER ELSE NULL END)");
        DB::statement("ALTER TABLE proyecciones ALTER COLUMN orden DROP NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE proyecciones ALTER COLUMN orden TYPE VARCHAR(4) USING (orden::VARCHAR)");
    }
};
