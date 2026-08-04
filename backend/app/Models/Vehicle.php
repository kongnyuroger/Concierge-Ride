<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehicle extends Model
{
    protected $fillable = ['driver_id', 'tier_id', 'make', 'model', 'plate_number', 'status'];

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function tier(): BelongsTo
    {
        return $this->belongsTo(Tier::class);
    }

    public function jobs(): HasMany
    {
        return $this->hasMany(Job::class);
    }
}
