import { InfiniteScroll } from '@inertiajs/react';
import ListingCard from '@/components/market/listing-card';
import type { Listing } from '@/types/market';

export function ListingSkeletons({ count = 8 }: { count?: number }) {
    return (
        <ul className="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-4" aria-hidden="true">
            {Array.from({ length: count }, (_, i) => (
                <li key={i} className="overflow-hidden rounded-xl border">
                    <div className="aspect-[4/3] animate-pulse bg-muted" />
                    <div className="space-y-2 p-3">
                        <div className="h-5 w-1/2 animate-pulse rounded bg-muted" />
                        <div className="h-4 w-3/4 animate-pulse rounded bg-muted" />
                    </div>
                </li>
            ))}
        </ul>
    );
}

/**
 * Product grid that loads the next page automatically as the user scrolls
 * (Inertia infinite scroll; the server prop must be wrapped in Inertia::scroll).
 */
export default function ListingGrid({ listings, prop = 'listings' }: { listings: { data: Listing[] }; prop?: string }) {
    return (
        <InfiniteScroll
            data={prop}
            className="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-4"
            loading={<div className="col-span-full"><ListingSkeletons count={4} /></div>}
        >
            {listings.data.map((listing) => (
                <ListingCard key={listing.id} listing={listing} />
            ))}
        </InfiniteScroll>
    );
}