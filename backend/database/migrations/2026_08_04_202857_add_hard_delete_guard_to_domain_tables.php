<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * BR-11 (NFR-6 floor): no hard deletes anywhere. A status/state column
     * alone can still be bypassed by a raw DELETE — this trigger makes that
     * bypass impossible at the DB layer, not just discouraged by convention.
     */
    private const GUARDED_TABLES = [
        'products',
        'tiers',
        'price_book_entries',
        'customers',
        'leads',
        'drivers',
        'vehicles',
        'jobs',
        'payments',
    ];

    public function up(): void
    {
        // CREATE OR REPLACE, not CREATE: `migrate:fresh` drops tables but not
        // standalone functions, so a plain CREATE would fail as "already
        // exists" the second time this migration runs against a DB that
        // still has the function from a prior run.
        DB::statement(<<<'SQL'
            CREATE OR REPLACE FUNCTION prevent_hard_delete() RETURNS trigger AS $$
            BEGIN
                RAISE EXCEPTION
                    'Hard deletes are not allowed on % (BR-11) — update its status column instead.',
                    TG_TABLE_NAME;
            END;
            $$ LANGUAGE plpgsql;
            SQL);

        foreach (self::GUARDED_TABLES as $table) {
            DB::statement(<<<SQL
                CREATE TRIGGER {$table}_prevent_hard_delete
                BEFORE DELETE ON {$table}
                FOR EACH ROW EXECUTE FUNCTION prevent_hard_delete();
                SQL);
        }
    }

    public function down(): void
    {
        foreach (self::GUARDED_TABLES as $table) {
            DB::statement("DROP TRIGGER IF EXISTS {$table}_prevent_hard_delete ON {$table}");
        }

        DB::statement('DROP FUNCTION IF EXISTS prevent_hard_delete()');
    }
};
