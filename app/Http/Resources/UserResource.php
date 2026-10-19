<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin User */
class UserResource extends JsonResource
{
    /**
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isSelf = $request->user()?->is($this->resource) ?? false;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'username' => $this->username,
            'avatar' => $this->avatar
                ? (str_starts_with($this->avatar, 'http') ? $this->avatar : Storage::disk('public')->url($this->avatar))
                : null,
            'bio' => $this->bio,
            'location' => new LocationResource($this->whenLoaded('location')),
            'created_at' => $this->created_at?->toISOString(),
            // Private fields are only exposed to the account owner.
            $this->mergeWhen($isSelf, [
                'email' => $this->email,
                'phone' => $this->phone,
                'is_admin' => $this->is_admin,
                'is_active' => $this->is_active,
            ]),
        ];
    }
}
