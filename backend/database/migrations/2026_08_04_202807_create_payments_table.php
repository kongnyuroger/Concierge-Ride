<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained();
            // Integer whole XAF — see ADR 0004.
            $table->unsignedInteger('amount');
            // mtn_momo / orange_money / cash — provider still PROPOSED, ADR 0009.
            $table->string('method');
            $table->string('provider_reference')->nullable();
            // BR-11: no hard deletes — status instead of DELETE.
            $table->string('status')->default('pending');
            $table->timestamps();
        });

        DB::statement('ALTER TABLE payments ADD CONSTRAINT payments_amount_non_negative CHECK (amount >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
