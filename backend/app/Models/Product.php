<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = ['name', 'code', 'status'];

    public function priceBookEntries(): HasMany
    {
        return $this->hasMany(PriceBookEntry::class);
    }

    public function jobs(): HasMany
    {
        return $this->hasMany(Job::class);
    }
}
