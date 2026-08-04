<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone');
            // A convenience default for populating a new job's stored payout —
            // NOT a live rate. Individually negotiated per CLAUDE.md; the
            // actual payout is always a value captured on the job itself.
            $table->unsignedInteger('default_payout_amount')->nullable();
            // BR-11: no hard deletes — status instead of DELETE.
            $table->string('status')->default('active');
            $table->timestamps();
        });

        DB::statement('ALTER TABLE drivers ADD CONSTRAINT drivers_default_payout_non_negative CHECK (default_payout_amount IS NULL OR default_payout_amount >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('drivers');
    }
};
