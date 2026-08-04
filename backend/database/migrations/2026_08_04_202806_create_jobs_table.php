<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained();
            $table->foreignId('lead_id')->nullable()->constrained();
            $table->foreignId('product_id')->constrained();
            $table->foreignId('tier_id')->constrained();
            $table->foreignId('driver_id')->nullable()->constrained();
            $table->foreignId('vehicle_id')->nullable()->constrained();
            // Traceability only — the actual price is the stored value below,
            // never re-derived from this at read time.
            $table->foreignId('price_book_entry_id')->nullable()->constrained();
            $table->foreignId('created_by')->constrained('users');

            // Stored values captured at creation — later price-book or driver
            // rate changes must NOT alter past jobs (CLAUDE.md money & history
            // integrity). Integer whole XAF, see ADR 0004.
            $table->unsignedInteger('price');
            $table->text('price_override_reason')->nullable();
            $table->unsignedInteger('driver_payout');

            $table->unsignedInteger('passenger_count');
            // Snapshot of tiers.passenger_capacity at creation, so BR-3 can be
            // a same-row CHECK instead of an unenforceable cross-table rule.
            $table->unsignedInteger('tier_capacity_at_booking');

            $table->timestamp('pickup_at');
            $table->string('pickup_location');
            $table->string('dropoff_location')->nullable();

            // Plain string for now — sized to adopt spatie/laravel-model-states'
            // state machine later; that wiring is a future ticket, this one
            // only needs the column to exist. BR-11: no hard deletes.
            $table->string('status')->default('pending');

            $table->timestamps();
        });

        // BR-3: passenger count can't exceed the tier's capacity at booking time.
        DB::statement('ALTER TABLE jobs ADD CONSTRAINT jobs_passenger_count_positive CHECK (passenger_count > 0)');
        DB::statement('ALTER TABLE jobs ADD CONSTRAINT jobs_passenger_count_within_capacity CHECK (passenger_count <= tier_capacity_at_booking)');
        DB::statement('ALTER TABLE jobs ADD CONSTRAINT jobs_price_non_negative CHECK (price >= 0)');
        DB::statement('ALTER TABLE jobs ADD CONSTRAINT jobs_driver_payout_non_negative CHECK (driver_payout >= 0)');

        // BR-6 (pickup_at must be in the future at creation) is NOT a CHECK
        // constraint here: Postgres requires CHECK expressions to be
        // IMMUTABLE, and now()/CURRENT_TIMESTAMP are only STABLE — Postgres
        // rejects a CHECK that references them. Enforced instead by
        // App\Observers\JobObserver (model layer) plus a BEFORE INSERT
        // trigger added in CR-10's add_pickup_time_guard_to_jobs_table
        // migration (the actual unbypassable layer) — see
        // /docs/adr/0003-rule-enforcement.md.
    }

    public function down(): void
    {
        Schema::dropIfExists('jobs');
    }
};
