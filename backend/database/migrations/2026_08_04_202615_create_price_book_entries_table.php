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
            $table->foreignId('product_id')->constrained();
            $table->foreignId('tier_id')->constrained();
            // Integer whole XAF — see ADR 0004. XAF isn't subdivided in everyday
            // Cameroon commerce, so no decimal/minor-unit column.
            $table->unsignedInteger('price');
            // No date-range versioning: a price change supersedes the old
            // entry (BR-11 — never deleted) rather than the row being edited
            // or time-bounded.
            $table->string('status')->default('active');
            $table->timestamps();
        });

        DB::statement('ALTER TABLE price_book_entries ADD CONSTRAINT price_book_entries_price_non_negative CHECK (price >= 0)');

        // Only one active price per product×tier at a time.
        DB::statement(
            "CREATE UNIQUE INDEX price_book_entries_active_product_tier_unique
            ON price_book_entries (product_id, tier_id) WHERE status = 'active'"
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('price_book_entries');
    }
};
