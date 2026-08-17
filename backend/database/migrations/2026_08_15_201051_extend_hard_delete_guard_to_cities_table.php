<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * BR-11: cities is a new domain table (CR-13) — reuses the
     * prevent_hard_delete() function CR-9's migration already created,
     * same pattern as every other guarded table.
     */
    public function up(): void
    {
        DB::statement(<<<'SQL'
            CREATE TRIGGER cities_prevent_hard_delete
            BEFORE DELETE ON cities
            FOR EACH ROW EXECUTE FUNCTION prevent_hard_delete();
            SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS cities_prevent_hard_delete ON cities');
    }
};
