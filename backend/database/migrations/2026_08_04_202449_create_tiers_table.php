<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tiers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->unsignedInteger('passenger_capacity');
            // BR-4: Premium is seeded false. Cross-table, so a job's tier being
            // bookable can't be a CHECK on this table alone — CR-18 enforces
            // that at creation time (model observer / trigger).
            $table->boolean('is_bookable')->default(true);
            // BR-11: no hard deletes — status instead of DELETE.
            $table->string('status')->default('active');
            $table->timestamps();
        });

        // Laravel's schema builder has no native CHECK constraint support.
        DB::statement('ALTER TABLE tiers ADD CONSTRAINT tiers_passenger_capacity_positive CHECK (passenger_capacity > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('tiers');
    }
};
