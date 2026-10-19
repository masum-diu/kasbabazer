<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['slug', 'name', 'price', 'listing_limit', 'photo_limit', 'boosts_per_period', 'verified_badge', 'is_active', 'sort_order'])]
class Plan extends Model
{
    protected function casts(): array
    {
        return ['verified_badge' => 'boolean', 'is_active' => 'boolean'];
    }

    public function isFree(): bool
    {
        return $this->price === 0;
    }
}
