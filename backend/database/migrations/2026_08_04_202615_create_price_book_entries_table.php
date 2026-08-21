<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_book_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('city_id')->constrained();
            $table->foreignId('product_id')->constrained();
            $table->foreignId('tier_id')->constrained();
            $table->unsignedInteger('customer_price');
            $table->unsignedInteger('margin_floor')->nullable();
            $table->unsignedInteger('included_hours')->nullable();
            $table->unsignedInteger('included_distance_km')->nullable();
            $table->unsignedInteger('overage_rate_per_hour')->nullable();
            $table->unsignedInteger('overage_rate_per_km')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        DB::statement('ALTER TABLE price_book_entries ADD CONSTRAINT price_book_entries_price_non_negative CHECK (customer_price >= 0)');

        // Only one active price per city×product×tier at a time.
        DB::statement(
            "CREATE UNIQUE INDEX price_book_entries_active_city_product_tier_unique
            ON price_book_entries (city_id, product_id, tier_id) WHERE status = 'active'"
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('price_book_entries');
    }
};
