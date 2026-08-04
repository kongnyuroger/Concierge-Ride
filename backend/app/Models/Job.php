<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Job extends Model
{
    protected $fillable = [
        'customer_id', 'lead_id', 'product_id', 'tier_id', 'driver_id', 'vehicle_id',
        'price_book_entry_id', 'created_by', 'price', 'price_override_reason',
        'driver_payout', 'passenger_count', 'tier_capacity_at_booking', 'pickup_at',
        'pickup_location', 'dropoff_location', 'status',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'driver_payout' => 'integer',
            'passenger_count' => 'integer',
            'tier_capacity_at_booking' => 'integer',
            'pickup_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function tier(): BelongsTo
    {
        return $this->belongsTo(Tier::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function priceBookEntry(): BelongsTo
    {
        return $this->belongsTo(PriceBookEntry::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
