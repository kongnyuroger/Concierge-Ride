<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $fillable = ['name', 'phone', 'email', 'language', 'status'];

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    public function jobs(): HasMany
    {
        return $this->hasMany(Job::class);
    }
}
