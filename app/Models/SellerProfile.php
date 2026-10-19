<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'shop_name', 'description', 'shop_image', 'phone', 'address', 'location_id', 'status', 'is_verified'])]
class SellerProfile extends Model
{
    public const STATUSES = ['pending', 'verified', 'rejected', 'suspended'];

    protected function casts(): array
    {
        return ['is_verified' => 'boolean'];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Location, $this> */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }
}
