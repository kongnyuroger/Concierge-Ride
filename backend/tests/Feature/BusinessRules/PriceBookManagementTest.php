<?php

/**
 * CR-13 / BR-16: owner-only price-book management, and the money & history
 * integrity boundary from CLAUDE.md — editing an entry must only affect
 * NEW jobs, never rewrite a job's already-stored price.
 *
 * Permission enforcement is the same route `permission:` middleware +
 * owner Gate::before bypass as every other BR-16 area (ADR 0011); read
 * access is already covered generically by RolePermissionMatrixTest, this
 * file adds PUT (edit) coverage plus the data-shape and history-integrity
 * assertions specific to this ticket.
 */

use App\Enums\UserRole;
use App\Models\City;
use App\Models\Customer;
use App\Models\Job;
use App\Models\PriceBookEntry;
use App\Models\Product;
use App\Models\Tier;
use App\Models\User;
use Database\Seeders\PermissionsSeeder;
use Database\Seeders\ReferenceDataSeeder;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->seed(PermissionsSeeder::class);
    $this->seed(ReferenceDataSeeder::class);
});

function actingAsPriceBookRole(UserRole $role): User
{
    $user = User::factory()->create();
    $user->assignRole($role->value);
    Sanctum::actingAs($user);

    return $user;
}

/** A real seeded entry to exercise — Douala Half-day SUV, matching the ticket's own example. */
function douala_half_day_suv(): PriceBookEntry
{
    return PriceBookEntry::query()
        ->whereRelation('city', 'code', 'douala')
        ->whereRelation('product', 'code', 'half_day')
        ->whereRelation('tier', 'code', 'suv')
        ->where('status', 'active')
        ->firstOrFail();
}

test('owner can view the price book', function () {
    actingAsPriceBookRole(UserRole::Owner);

    $response = $this->getJson('/api/price-book');

    $response->assertOk();
    expect($response->json('data'))->not->toBeEmpty();
});

test('owner can edit a price book entry and every field round-trips correctly', function () {
    actingAsPriceBookRole(UserRole::Owner);
    $entry = douala_half_day_suv();

    $response = $this->putJson("/api/price-book/{$entry->id}", [
        'customer_price' => 65_000,
        'included_hours' => 5,
        'included_distance_km' => 40,
        'overage_rate_per_hour' => 6_000,
        'overage_rate_per_km' => 500,
        'margin_floor' => 18_000,
    ]);

    $response->assertOk();
    $response->assertJsonPath('data.customer_price', 65_000);
    $response->assertJsonPath('data.included_hours', 5);
    $response->assertJsonPath('data.included_distance_km', 40);
    $response->assertJsonPath('data.overage_rate_per_hour', 6_000);
    $response->assertJsonPath('data.overage_rate_per_km', 500);
    $response->assertJsonPath('data.margin_floor', 18_000);
    $response->assertJsonPath('data.status', 'active');
    $response->assertJsonPath('data.city.code', 'douala');
    $response->assertJsonPath('data.product.code', 'half_day');
    $response->assertJsonPath('data.tier.code', 'suv');

    // The edit superseded the old row rather than mutating it in place —
    // still only one active entry for this (city, product, tier).
    expect($entry->fresh()->status)->toBe('superseded');
    expect(
        PriceBookEntry::where('city_id', $entry->city_id)
            ->where('product_id', $entry->product_id)
            ->where('tier_id', $entry->tier_id)
            ->where('status', 'active')
            ->count()
    )->toBe(1);
});

test('editing rejects an overage rate without its matching allowance', function () {
    actingAsPriceBookRole(UserRole::Owner);
    $entry = douala_half_day_suv();

    $response = $this->putJson("/api/price-book/{$entry->id}", [
        'customer_price' => 65_000,
        'overage_rate_per_hour' => 6_000, // included_hours omitted
        'margin_floor' => 18_000,
    ]);

    $response->assertStatus(422);
});

test('dispatcher and account-manager get 403 editing a price book entry', function (UserRole $role) {
    actingAsPriceBookRole($role);
    $entry = douala_half_day_suv();

    $response = $this->putJson("/api/price-book/{$entry->id}", [
        'customer_price' => 65_000,
        'margin_floor' => 18_000,
    ]);

    $response->assertForbidden();
    expect($entry->fresh()->customer_price)->toBe($entry->customer_price);
})->with([
    'dispatcher' => [UserRole::Dispatcher],
    'account-manager' => [UserRole::AccountManager],
]);

test('editing a price book entry does not alter an existing job\'s stored price', function () {
    actingAsPriceBookRole(UserRole::Owner);
    $entry = douala_half_day_suv();
    $originalPrice = $entry->customer_price;

    $customer = Customer::create(['name' => 'Test Customer', 'phone' => '+237600000000']);
    $job = Job::create([
        'customer_id' => $customer->id,
        'product_id' => $entry->product_id,
        'tier_id' => $entry->tier_id,
        'price_book_entry_id' => $entry->id,
        'created_by' => User::factory()->create()->id,
        'price' => $originalPrice,
        'driver_payout' => 10_000,
        'passenger_count' => 2,
        'tier_capacity_at_booking' => Tier::find($entry->tier_id)->passenger_capacity,
        'pickup_at' => now()->addDay(),
        'pickup_location' => 'Douala International Airport',
    ]);

    $this->putJson("/api/price-book/{$entry->id}", [
        'customer_price' => $originalPrice + 20_000,
        'margin_floor' => $entry->margin_floor,
    ])->assertOk();

    // The job's own stored price is untouched...
    expect($job->fresh()->price)->toBe($originalPrice);
    // ...and the specific price-book row it references is untouched too —
    // it was superseded, not rewritten, so its values never changed.
    expect($entry->fresh()->customer_price)->toBe($originalPrice);
    expect($entry->fresh()->status)->toBe('superseded');
});

test('a fresh city/product/tier combination has no active price book entry to edit', function () {
    // Sanity check on the key itself: BR-1 is city + product + tier, not
    // just product + tier — a new city genuinely starts with nothing.
    $newCity = City::create(['name' => 'Bafoussam', 'code' => 'bafoussam']);
    $product = Product::first();
    $tier = Tier::first();

    expect(
        PriceBookEntry::where('city_id', $newCity->id)
            ->where('product_id', $product->id)
            ->where('tier_id', $tier->id)
            ->exists()
    )->toBeFalse();
});
