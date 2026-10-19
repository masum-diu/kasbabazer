<?php

namespace App\Http\Resources;

use App\Models\Listing;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Listing */
class ListingResource extends JsonResource
{
    /**
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        $saved = (array) $request->attributes->get('saved_ids', []);
        $reacted = (array) $request->attributes->get('reacted_listings', []);
        $user = $this->user;

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'description' => $this->description,
            'price' => (float) $this->price,
            'is_negotiable' => $this->is_negotiable,
            'condition' => $this->condition,
            'status' => $this->status,
            'quantity' => $this->quantity,
            // The number is only revealed to signed-in users, so it cannot be scraped from public pages.
            'has_contact' => $this->contact_phone !== null,
            'contact_phone' => $request->user() ? $this->contact_phone : null,
            'views_count' => $this->views_count,
            'likes_count' => $this->likes_count,
            'comments_count' => $this->comments_count,
            'published_at' => $this->published_at?->toISOString(),
            'boosted' => $this->boosted_until !== null && $this->boosted_until->isFuture(),
            'category' => $this->whenLoaded('category', fn () => ['id' => $this->category->id, 'name' => $this->category->name]),
            'location' => $this->whenLoaded('location', fn () => $this->location ? ['id' => $this->location->id, 'name' => $this->location->name] : null),
            'images' => $this->whenLoaded('images', fn () => $this->images->map(fn ($i) => ['id' => $i->id, 'url' => $i->url, 'on_feed' => (bool) $i->on_feed])->values()),
            // Photos the seller picked for the home feed (cover first); the first few when none were picked.
            'feed_images' => $this->whenLoaded('images', fn () => ($this->images->where('on_feed', true)->isNotEmpty() ? $this->images->where('on_feed', true) : $this->images->take(Listing::MAX_FEED_PHOTOS))->map(fn ($i) => ['id' => $i->id, 'url' => $i->url])->values()),
            'thumbnail' => $this->whenLoaded('images', fn () => $this->images->first()?->url),
            'seller' => $this->whenLoaded('user', fn () => [
                'id' => $user->id,
                'name' => $user->name,
                'avatar' => $user->avatar,
                'shop_name' => $user->sellerProfile?->shop_name,
                'is_verified' => (bool) $user->sellerProfile?->is_verified,
                'rating' => $user->getAttribute('rating_avg') !== null ? round((float) $user->getAttribute('rating_avg'), 1) : null,
                'reviews_count' => (int) $user->getAttribute('reviews_received_count'),
            ]),
            'viewer' => [
                'saved' => in_array($this->id, $saved, true),
                'reaction' => $reacted[$this->id] ?? null,
            ],
        ];
    }
}
