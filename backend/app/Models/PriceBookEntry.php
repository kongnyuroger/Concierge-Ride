<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class PriceBookEntry extends Model
{
    protected $fillable = [
        'city_id',
        'product_id',
        'tier_id',
        'customer_price',
        'included_hours',
        'included_distance_km',
        'overage_rate_per_hour',
        'overage_rate_per_km',
        'margin_floor',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'customer_price' => 'integer',
            'included_hours' => 'integer',
            'included_distance_km' => 'integer',
            'overage_rate_per_hour' => 'integer',
            'overage_rate_per_km' => 'integer',
            'margin_floor' => 'integer',
        ];
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function tier(): BelongsTo
    {
        return $this->belongsTo(Tier::class);
    }

    /**
     * "Editing" a price-book entry, correctly: mark the current active
     * entry for this (city, product, tier) as superseded, then insert a
     * brand new active row with the given values. Never an UPDATE to an
     * existing row's money/allowance fields — a job's price_book_entry_id
     * FK keeps pointing at a row whose values never change, so editing a
     * price only ever affects jobs created after the edit. See CLAUDE.md's
     * money & history integrity rule and /docs/adr/0012-price-book.md.
     *
     * @param  array<string, mixed>  $attributes
     */
    public static function createNewVersion(array $attributes): self
    {
        return DB::transaction(function () use ($attributes) {
            static::query()
                ->where('city_id', $attributes['city_id'])
                ->where('product_id', $attributes['product_id'])
                ->where('tier_id', $attributes['tier_id'])
                ->where('status', 'active')
                ->update(['status' => 'superseded']);

            return static::create($attributes + ['status' => 'active']);
        });
    }
}
