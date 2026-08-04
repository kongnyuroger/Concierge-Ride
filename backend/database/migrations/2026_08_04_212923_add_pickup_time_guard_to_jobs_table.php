<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * BR-6 (NFR-6 reference implementation — see /docs/adr/0003-rule-enforcement.md):
     * pickup time must be in the future at creation. App\Observers\JobObserver
     * enforces this for normal Eloquent usage; this trigger is the layer that
     * actually can't be bypassed — a raw query builder insert has no
     * Eloquent events to fire, so without this, BR-6 could be dodged
     * entirely by skipping Eloquent. This is why NFR-6 requires DB-layer
     * enforcement, not just a model guard.
     *
     * INSERT only, not UPDATE: a job legitimately becomes "in the past"
     * relative to now() as time passes after creation — that must not
     * retroactively invalidate an already-valid job.
     */
    public function up(): void
    {
        DB::statement(<<<'SQL'
            CREATE OR REPLACE FUNCTION enforce_pickup_in_future() RETURNS trigger AS $$
            BEGIN
                IF NEW.pickup_at <= now() THEN
                    RAISE EXCEPTION
                        '[BR-6] Pickup time must be in the future.';
                END IF;
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
            SQL);

        DB::statement(<<<'SQL'
            CREATE TRIGGER jobs_enforce_pickup_in_future
            BEFORE INSERT ON jobs
            FOR EACH ROW EXECUTE FUNCTION enforce_pickup_in_future();
            SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS jobs_enforce_pickup_in_future ON jobs');
        DB::statement('DROP FUNCTION IF EXISTS enforce_pickup_in_future()');
    }
};
