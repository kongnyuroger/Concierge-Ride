<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Driver extends Model
{
    protected $fillable = ['name', 'phone', 'default_payout_amount', 'status'];

    protected function casts(): array
    {
        return [
            'default_payout_amount' => 'integer',
        ];
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
