<?php

/**
 * BR-1: Price Book Supersede Rule
 *
 * A job captures its price as stored VALUES at creation time; later price-book
 * or rate changes must NOT alter past jobs. Therefore, we never edit a price
 * book entry. We supersede it by setting the old row's status to 'superseded'
 * and inserting a new active row.
 *
 * Only one 'active' price book entry can exist per product + tier.
 */

use App\Exceptions\BusinessRuleException;
use App\Models\City;
use App\Models\PriceBookEntry;
use App\Models\Product;
use App\Models\Tier;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

function createBaseProductAndTier(): array
{
    $city = City::first() ?? City::create([
        'name' => 'Douala',
        'code' => 'DLA',
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

    return [$city->id, $product->id, $tier->id];
}

test('creating multiple active prices for the same product and tier is rejected', function () {
    [$cityId, $productId, $tierId] = createBaseProductAndTier();

    // First active price is fine
    PriceBookEntry::create([
        'city_id'        => $cityId,
        'product_id'     => $productId,
        'tier_id'        => $tierId,
        'customer_price' => 10000,
        'margin_floor'   => 5000,
        'status'         => 'active',
    ]);

    // Second active price without superseding first should fail
    PriceBookEntry::create([
        'city_id'        => $cityId,
        'product_id'     => $productId,
        'tier_id'        => $tierId,
        'customer_price' => 15000,
        'margin_floor'   => 5000,
        'status'         => 'active',
    ]);
})->throws(QueryException::class);

test('BYPASS: creating multiple active prices is rejected via the query builder directly', function () {
    [$cityId, $productId, $tierId] = createBaseProductAndTier();

    DB::table('price_book_entries')->insert([
        'city_id'        => $cityId,
        'product_id'     => $productId,
        'tier_id'        => $tierId,
        'customer_price' => 10000,
        'margin_floor'   => 5000,
        'status'         => 'active',
        'created_at'     => now(),
        'updated_at'     => now(),
    ]);

    DB::table('price_book_entries')->insert([
        'city_id'        => $cityId,
        'product_id'     => $productId,
        'tier_id'        => $tierId,
        'customer_price' => 15000,
        'margin_floor'   => 5000,
        'status'         => 'active',
        'created_at'     => now(),
        'updated_at'     => now(),
    ]);
})->throws(QueryException::class);

test('superseding correctly allows the new active price', function () {
    [$cityId, $productId, $tierId] = createBaseProductAndTier();

    // First active price
    PriceBookEntry::create([
        'city_id'        => $cityId,
        'product_id'     => $productId,
        'tier_id'        => $tierId,
        'customer_price' => 10000,
        'margin_floor'   => 5000,
        'status'         => 'active',
    ]);

    // Properly supersede it
    PriceBookEntry::where([
        'city_id'    => $cityId,
        'product_id' => $productId,
        'tier_id'    => $tierId,
        'status'     => 'active',
    ])->update(['status' => 'superseded']);

    // Now adding a new active price should succeed
    $newEntry = PriceBookEntry::create([
        'city_id'        => $cityId,
        'product_id'     => $productId,
        'tier_id'        => $tierId,
        'customer_price' => 15000,
        'margin_floor'   => 7500,
        'status'         => 'active',
    ]);

    expect($newEntry->status)->toBe('active');
    expect(PriceBookEntry::where('status', 'active')->count())->toBe(1);
    expect(PriceBookEntry::where('status', 'superseded')->count())->toBe(1);
});
