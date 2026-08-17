<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * CR-13: completes the price-book entry shape against BR-1 (keyed by
     * city + product + tier, not just product + tier) and BR-2 (needs a
     * configured margin floor). See /docs/adr/0012-price-book.md.
     *
     * Truncates existing rows first: this table has only ever held
     * CR-9/ReferenceDataSeeder's placeholder data (no job has ever been
     * able to reference one — job creation is CR-16, not built yet), and
     * the new city_id column can't be backfilled with a real value for
     * old rows that were never seeded with a city. Not a BR-11 concern —
     * BR-11 is about the app never hard-deleting real business records;
     * this is a schema migration clearing seeder-only placeholder rows
     * before ReferenceDataSeeder re-seeds them with the completed shape.
     */
    public function up(): void
    {
        DB::table('price_book_entries')->truncate();

        Schema::table('price_book_entries', function (Blueprint $table) {
            $table->foreignId('city_id')->after('id')->constrained();
            $table->renameColumn('price', 'customer_price');
            $table->unsignedInteger('included_hours')->nullable()->after('customer_price');
            $table->unsignedInteger('included_distance_km')->nullable()->after('included_hours');
            $table->unsignedInteger('overage_rate_per_hour')->nullable()->after('included_distance_km');
            $table->unsignedInteger('overage_rate_per_km')->nullable()->after('overage_rate_per_hour');
            $table->unsignedInteger('margin_floor')->after('overage_rate_per_km');
        });

        DB::statement('ALTER TABLE price_book_entries DROP CONSTRAINT price_book_entries_price_non_negative');
        DB::statement('ALTER TABLE price_book_entries ADD CONSTRAINT price_book_entries_customer_price_non_negative CHECK (customer_price >= 0)');
        DB::statement('ALTER TABLE price_book_entries ADD CONSTRAINT price_book_entries_margin_floor_non_negative CHECK (margin_floor >= 0)');
        DB::statement('ALTER TABLE price_book_entries ADD CONSTRAINT price_book_entries_overage_rate_per_hour_non_negative CHECK (overage_rate_per_hour IS NULL OR overage_rate_per_hour >= 0)');
        DB::statement('ALTER TABLE price_book_entries ADD CONSTRAINT price_book_entries_overage_rate_per_km_non_negative CHECK (overage_rate_per_km IS NULL OR overage_rate_per_km >= 0)');
        // An overage rate only makes sense alongside the allowance it's the
        // overage FROM — keep the pair set-or-null together, not one
        // without the other.
        DB::statement('ALTER TABLE price_book_entries ADD CONSTRAINT price_book_entries_hours_pair_consistent CHECK ((included_hours IS NULL) = (overage_rate_per_hour IS NULL))');
        DB::statement('ALTER TABLE price_book_entries ADD CONSTRAINT price_book_entries_km_pair_consistent CHECK ((included_distance_km IS NULL) = (overage_rate_per_km IS NULL))');

        DB::statement('DROP INDEX price_book_entries_active_product_tier_unique');
        DB::statement(
            "CREATE UNIQUE INDEX price_book_entries_active_city_product_tier_unique
            ON price_book_entries (city_id, product_id, tier_id) WHERE status = 'active'"
        );
    }

    public function down(): void
    {
        DB::statement('DROP INDEX price_book_entries_active_city_product_tier_unique');

        // Constraints must go before the rename/column drops below — a
        // renamed-back column keeps a constraint alive under its old
        // (now-mismatched) name, which then collides with up() re-adding
        // it on the next migrate. Dropped explicitly here instead of
        // relying on column drops to take them with it.
        DB::statement('ALTER TABLE price_book_entries DROP CONSTRAINT price_book_entries_customer_price_non_negative');
        DB::statement('ALTER TABLE price_book_entries DROP CONSTRAINT price_book_entries_margin_floor_non_negative');
        DB::statement('ALTER TABLE price_book_entries DROP CONSTRAINT price_book_entries_overage_rate_per_hour_non_negative');
        DB::statement('ALTER TABLE price_book_entries DROP CONSTRAINT price_book_entries_overage_rate_per_km_non_negative');
        DB::statement('ALTER TABLE price_book_entries DROP CONSTRAINT price_book_entries_hours_pair_consistent');
        DB::statement('ALTER TABLE price_book_entries DROP CONSTRAINT price_book_entries_km_pair_consistent');

        Schema::table('price_book_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('city_id');
            $table->dropColumn([
                'included_hours',
                'included_distance_km',
                'overage_rate_per_hour',
                'overage_rate_per_km',
                'margin_floor',
            ]);
            $table->renameColumn('customer_price', 'price');
        });

        DB::statement('ALTER TABLE price_book_entries ADD CONSTRAINT price_book_entries_price_non_negative CHECK (price >= 0)');
        DB::statement(
            "CREATE UNIQUE INDEX price_book_entries_active_product_tier_unique
            ON price_book_entries (product_id, tier_id) WHERE status = 'active'"
        );
    }
};
