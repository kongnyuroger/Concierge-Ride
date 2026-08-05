<?php

/**
 * BR-6: a job's pickup time must be in the future at creation.
 *
 * This is the reference implementation for NFR-6's rule-enforcement pattern
 * — see /docs/adr/0003-rule-enforcement.md. Every future BR-* rule test
 * should follow this file's shape: one file, two tests (rule enforced at
 * the model layer, then the same violation proven still rejected at the
 * true lowest level via the bypass test) — no HTTP layer involved.
 */

use App\Exceptions\BusinessRuleException;
use App\Models\Customer;
use App\Models\Job;
use App\Models\Product;
use App\Models\Tier;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

function validJobAttributes(array $overrides = []): array
{
    $customer = Customer::first() ?? Customer::create([
        'name' => 'Test Customer',
        'phone' => '+237600000000',
    ]);
    $product = Product::first() ?? Product::create([
        'name' => 'Airport transfer',
        'code' => 'airport_transfer',
    ]);
    $tier = Tier::first() ?? Tier::create([
        'name' => 'Standard',
        'code' => 'standard',
        'passenger_capacity' => 4,
    ]);
    $user = User::first() ?? User::factory()->create();

    return array_merge([
        'customer_id' => $customer->id,
        'product_id' => $product->id,
        'tier_id' => $tier->id,
        'created_by' => $user->id,
        'price' => 15_000,
        'driver_payout' => 10_000,
        'passenger_count' => 2,
        'tier_capacity_at_booking' => $tier->passenger_capacity,
        'pickup_at' => now()->addDay(),
        'pickup_location' => 'Douala International Airport',
    ], $overrides);
}

test('creating a job with a past pickup time is rejected', function () {
    Job::create(validJobAttributes(['pickup_at' => now()->subHour()]));
})->throws(BusinessRuleException::class);

test('BYPASS: a past pickup time is still rejected via the query builder directly', function () {
    DB::table('jobs')->insert(validJobAttributes(['pickup_at' => now()->subHour()]) + [
        'created_at' => now(),
        'updated_at' => now(),
    ]);
})->throws(QueryException::class);
