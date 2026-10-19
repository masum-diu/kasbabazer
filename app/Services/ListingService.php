<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Listing;
use App\Models\ListingImage;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ListingService
{
    public const SORTS = ['latest', 'oldest', 'price_low', 'price_high', 'popular'];

    /**
     * Eager-load a seller with profile and rating aggregates.
     */
    public static function sellerRelation(): \Closure
    {
        return fn ($q) => $q->with('sellerProfile')
            ->withAvg('reviewsReceived as rating_avg', 'rating')
            ->withCount('reviewsReceived');
    }

    /**
     * Search/filter public listings with pagination.
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Listing>
     */
    public function search(array $filters, int $perPage = 12): LengthAwarePaginator
    {
        $query = Listing::query()
            ->with(['images', 'category', 'location', 'user' => self::sellerRelation()])
            ->when(
                isset($filters['status']) && $filters['status'] !== '',
                fn ($q) => $q->where('status', $filters['status']),
                fn ($q) => $q->public(),
            )
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->where(
                fn ($w) => $w->whereLike('title', "%{$s}%")->orWhereLike('description', "%{$s}%"),
            ))
            ->when($filters['category_id'] ?? null, function ($q, $id) {
                $ids = Category::where('parent_id', $id)->pluck('id')->push((int) $id);
                $q->whereIn('category_id', $ids);
            })
            ->when($filters['location_id'] ?? null, fn ($q, $id) => $q->where('location_id', $id))
            ->when($filters['seller_id'] ?? null, fn ($q, $id) => $q->where('user_id', $id))
            ->when($filters['condition'] ?? null, fn ($q, $c) => $q->where('condition', $c))
            ->when(isset($filters['min_price']) && $filters['min_price'] !== '', fn ($q) => $q->where('price', '>=', $filters['min_price']))
            ->when(isset($filters['max_price']) && $filters['max_price'] !== '', fn ($q) => $q->where('price', '<=', $filters['max_price']));

        match ($filters['sort'] ?? 'latest') {
            'oldest' => $query->orderBy('published_at'),
            // Items without a price (0) go last instead of topping the cheapest-first list.
            'price_low' => $query->orderByRaw('price = 0')->orderBy('price'),
            'price_high' => $query->orderByDesc('price'),
            'popular' => $query->orderByRaw('(likes_count * 3 + comments_count * 2 + views_count) DESC'),
            // Boosted posts float to the top of the default feed while their boost lasts.
            default => $query->orderByRaw('CASE WHEN boosted_until > ? THEN 1 ELSE 0 END DESC', [now()])->orderByDesc('published_at'),
        };

        return $query->orderByDesc('id')->paginate($perPage)->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, UploadedFile>  $images
     */
    public function create(User $user, array $data, array $images = [], bool $publish = true, array $feed = []): Listing
    {
        return DB::transaction(function () use ($user, $data, $images, $publish, $feed) {
            $listing = new Listing($data);
            $listing->forceFill([
                'user_id' => $user->id,
                'slug' => $this->uniqueSlug($data['title']),
                'status' => $publish ? 'active' : 'draft',
                'published_at' => $publish ? now() : null,
            ])->save();

            $this->applyFeedPhotos($listing, $feed, $this->storeImages($listing, $images));

            return $listing;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, UploadedFile>  $images
     * @param  array<int, int>  $removeImageIds
     */
    public function update(Listing $listing, array $data, array $images = [], array $removeImageIds = [], array $feed = []): Listing
    {
        return DB::transaction(function () use ($listing, $data, $images, $removeImageIds, $feed) {
            $listing->update($data);

            foreach ($listing->images()->whereIn('id', $removeImageIds)->get() as $image) {
                if ($image->path) {
                    Storage::disk('public')->delete($image->path);
                }
                $image->delete();
            }

            $this->applyFeedPhotos($listing, $feed, $this->storeImages($listing, $images));

            return $listing;
        });
    }

    public function setStatus(Listing $listing, string $status): void
    {
        $listing->status = $status;

        if ($status === 'active' && $listing->published_at === null) {
            $listing->published_at = now();
        }

        $listing->save();
    }

    /**
     * Count a view unless the same user/IP already viewed it in the last 30 minutes.
     */
    public function recordView(Listing $listing, ?User $user, ?string $ip, ?string $userAgent): void
    {
        if ($user?->id === $listing->user_id) {
            return;
        }

        $recent = $listing->views()
            ->where('created_at', '>=', Carbon::now()->subMinutes(30))
            ->when(
                $user,
                fn ($q) => $q->where('user_id', $user->id),
                fn ($q) => $q->where('ip_address', $ip),
            )
            ->exists();

        if ($recent) {
            return;
        }

        $listing->views()->create([
            'user_id' => $user?->id,
            'ip_address' => $ip,
            'user_agent' => $userAgent ? Str::limit($userAgent, 250, '') : null,
            'created_at' => now(),
        ]);

        $listing->increment('views_count');
    }

    /**
     * @param  array<int, UploadedFile>  $images
     * @return array<int, ListingImage> the stored images, in upload order
     */
    private function storeImages(Listing $listing, array $images): array
    {
        $order = (int) $listing->images()->max('sort_order') + 1;
        $stored = [];

        foreach ($images as $file) {
            // store() generates a random filename; the client's name is never used.
            $path = $file->store('listings', 'public');
            if ($path === false) {
                continue;
            }
            $stored[] = $listing->images()->create([
                'path' => $path,
                'url' => Storage::disk('public')->url($path),
                'sort_order' => $order++,
            ]);
        }

        return $stored;
    }

    /**
     * Mark up to 3 photos to show on the home feed and move them to the front, in the order chosen.
     * The first one is the cover used on cards. Without a choice the first photos are used.
     *
     * @param  array<int, string>  $feed  keys like "e:<image id>" or "n:<index of a new upload>"
     * @param  array<int, ListingImage>  $stored
     */
    private function applyFeedPhotos(Listing $listing, array $feed, array $stored): void
    {
        $ids = $listing->images()->pluck('id')->all(); // already in sort order
        $chosen = [];

        foreach ($feed as $key) {
            if (! preg_match('/^([en]):(\d+)$/', (string) $key, $m)) {
                continue;
            }

            $id = $m[1] === 'e' ? (int) $m[2] : ($stored[(int) $m[2]]->id ?? null);

            if ($id !== null && in_array($id, $ids, true) && ! in_array($id, $chosen, true)) {
                $chosen[] = $id;
            }
        }

        $chosen = array_slice($chosen, 0, Listing::MAX_FEED_PHOTOS);

        if ($chosen === []) {
            // No explicit choice: keep what the seller had, or fall back to the first photos.
            $chosen = $listing->images()->where('on_feed', true)->pluck('id')->all() ?: array_slice($ids, 0, Listing::MAX_FEED_PHOTOS);
            $chosen = array_values(array_intersect($ids, $chosen));
        }

        foreach ([...$chosen, ...array_diff($ids, $chosen)] as $position => $id) {
            ListingImage::whereKey($id)->update(['sort_order' => $position, 'on_feed' => in_array($id, $chosen, true)]);
        }
    }

    private function uniqueSlug(string $title): string
    {
        return (Str::slug($title) ?: 'listing').'-'.Str::lower(Str::random(6));
    }
}
