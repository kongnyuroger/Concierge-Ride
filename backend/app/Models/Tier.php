<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tier extends Model
{
    protected $fillable = ['name', 'code', 'passenger_capacity', 'is_bookable', 'status'];

    protected function casts(): array
    {
        return [
            'passenger_capacity' => 'integer',
            'is_bookable' => 'boolean',
        ];
    }

    public function priceBookEntries(): HasMany
    {
        return $this->hasMany(PriceBookEntry::class);
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    public function jobs(): HasMany
    {
        return $this->hasMany(Job::class);
    }
}
