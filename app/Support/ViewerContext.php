<?php

namespace App\Support;

use App\Models\Comment;
use App\Models\Listing;
use App\Models\Reaction;
use Illuminate\Http\Request;

/**
 * Pre-computes "what has the signed-in user saved / reacted to" for a page in 2 queries,
 * so resources can stay free of per-row queries.
 */
class ViewerContext
{
    /**
     * @param  iterable<int, int>  $listingIds
     */
    public static function forListings(Request $request, iterable $listingIds): void
    {
        $ids = collect($listingIds)->all();
        $user = $request->user();

        if (! $user || $ids === []) {
            return;
        }

        $request->attributes->set('saved_ids', $user->savedListings()->whereIn('listings.id', $ids)->pluck('listings.id')->all());
        $request->attributes->set('reacted_listings', Reaction::where('user_id', $user->id)
            ->where('reactable_type', (new Listing)->getMorphClass())
            ->whereIn('reactable_id', $ids)->pluck('type', 'reactable_id')->all());
    }

    /**
     * @param  iterable<int, int>  $commentIds
     */
    public static function forComments(Request $request, iterable $commentIds, int $ownerId): void
    {
        $request->attributes->set('listing_owner_id', $ownerId);
        $ids = collect($commentIds)->all();
        $user = $request->user();

        if (! $user || $ids === []) {
            return;
        }

        $request->attributes->set('reacted_comments', Reaction::where('user_id', $user->id)
            ->where('reactable_type', (new Comment)->getMorphClass())
            ->whereIn('reactable_id', $ids)->pluck('type', 'reactable_id')->all());
    }
}
