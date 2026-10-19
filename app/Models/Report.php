<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['user_id', 'reason', 'description'])]
class Report extends Model
{
    public const REASONS = ['scam', 'fake_product', 'spam', 'harassment', 'illegal', 'wrong_information', 'other'];

    /** @return MorphTo<Model, $this> */
    public function reportable(): MorphTo
    {
        return $this->morphTo();
    }
}
