<?php

namespace Database\Seeders;

use App\Models\Driver;
use App\Models\PriceBookEntry;
use App\Models\Product;
use App\Models\Tier;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;

class ReferenceDataSeeder extends Seeder
{
    /**
     * Baseline reference data: the product/tier catalog, the price book
     * grid, and a couple of sample drivers/vehicles to build against.
     * Roles/permissions are seeded separately — see PermissionsSeeder.
     *
     * Idempotent — safe to re-run (updateOrCreate/firstOrCreate throughout).
     *
     * Prices below are PLACEHOLDER XAF amounts for dev/seed purposes only —
     * §8's real price book was unavailable when this was written (see
     * CR-9's PR description). Replace with real figures once available.
     */
    public function run(): void
    {
        $products = collect([
            ['name' => 'Airport transfer', 'code' => 'airport_transfer'],
            ['name' => 'Half-day', 'code' => 'half_day'],
            ['name' => 'Full-day', 'code' => 'full_day'],
            ['name' => 'Monthly', 'code' => 'monthly'],
        ])->mapWithKeys(function (array $attrs) {
            $product = Product::updateOrCreate(['code' => $attrs['code']], $attrs);

            return [$attrs['code'] => $product];
        });

        $tiers = collect([
            ['name' => 'Standard', 'code' => 'standard', 'passenger_capacity' => 4, 'is_bookable' => true],
            ['name' => 'SUV', 'code' => 'suv', 'passenger_capacity' => 6, 'is_bookable' => true],
            ['name' => 'Van', 'code' => 'van', 'passenger_capacity' => 10, 'is_bookable' => true],
            // BR-4: Premium is never bookable on a job.
            ['name' => 'Premium', 'code' => 'premium', 'passenger_capacity' => 4, 'is_bookable' => false],
        ])->mapWithKeys(function (array $attrs) {
            $tier = Tier::updateOrCreate(['code' => $attrs['code']], $attrs);

            return [$attrs['code'] => $tier];
        });

        // product code => [tier code => price in whole XAF]
        $priceGrid = [
            'airport_transfer' => ['standard' => 15_000, 'suv' => 25_000, 'van' => 35_000, 'premium' => 50_000],
            'half_day' => ['standard' => 40_000, 'suv' => 60_000, 'van' => 80_000, 'premium' => 120_000],
            'full_day' => ['standard' => 70_000, 'suv' => 100_000, 'van' => 140_000, 'premium' => 200_000],
            'monthly' => ['standard' => 600_000, 'suv' => 900_000, 'van' => 1_200_000, 'premium' => 1_800_000],
        ];

        foreach ($priceGrid as $productCode => $tierPrices) {
            foreach ($tierPrices as $tierCode => $price) {
                PriceBookEntry::updateOrCreate(
                    [
                        'product_id' => $products[$productCode]->id,
                        'tier_id' => $tiers[$tierCode]->id,
                        'status' => 'active',
                    ],
                    ['price' => $price]
                );
            }
        }

        $driver1 = Driver::updateOrCreate(
            ['phone' => '+237670000001'],
            ['name' => 'Jean Mballa', 'default_payout_amount' => 10_000]
        );
        $driver2 = Driver::updateOrCreate(
            ['phone' => '+237670000002'],
            ['name' => 'Aminatou Bello', 'default_payout_amount' => 12_000]
        );

        Vehicle::updateOrCreate(
            ['plate_number' => 'LT 1234 A'],
            [
                'driver_id' => $driver1->id,
                'tier_id' => $tiers['standard']->id,
                'make' => 'Toyota',
                'model' => 'Camry',
            ]
        );
        Vehicle::updateOrCreate(
            ['plate_number' => 'LT 5678 B'],
            [
                'driver_id' => $driver2->id,
                'tier_id' => $tiers['van']->id,
                'make' => 'Toyota',
                'model' => 'Hiace',
            ]
        );
    }
}
