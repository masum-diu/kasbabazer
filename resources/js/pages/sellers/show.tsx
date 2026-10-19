import { Head, Link, router } from '@inertiajs/react';
import { BadgeCheck, MapPin, Star } from 'lucide-react';
import { useState } from 'react';
import ListingGrid from '@/components/market/listing-grid';
import ReportButton from '@/components/market/report-button';
import UserAvatar from '@/components/market/user-avatar';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { timeAgo } from '@/lib/market';
import type { Listing } from '@/types/market';

type Props = {
    seller: {
        id: number;
        name: string;
        avatar: string | null;
        shop_name: string;
        description: string | null;
        address: string | null;
        is_verified: boolean;
        location: string | null;
        average: number;
        count: number;
    };
    listings: { data: Listing[] };
    total: number;
    reviews: { id: number; rating: number; review: string | null; created_at: string; reviewer: { id: number; name: string; avatar: string | null } }[];
    myReview: { rating: number; review: string | null } | null;
    canReview: boolean;
    isOwner: boolean;
};

function Stars({ value, onPick }: { value: number; onPick?: (n: number) => void }) {
    return (
        <span className="flex" role={onPick ? 'radiogroup' : 'img'} aria-label={`${value} out of 5 stars`}>
            {[1, 2, 3, 4, 5].map((n) => (
                <button key={n} type="button" disabled={!onPick} onClick={() => onPick?.(n)} aria-label={`${n} star${n > 1 ? 's' : ''}`} className={cn(onPick && 'cursor-pointer')}>
                    <Star className={cn('size-4', n <= Math.round(value) ? 'fill-amber-400 text-amber-400' : 'text-muted-foreground/40')} />
                </button>
            ))}
        </span>
    );
}

export default function SellerShow({ seller, listings, total, reviews, myReview, canReview, isOwner }: Props) {
    const [rating, setRating] = useState(myReview?.rating ?? 0);
    const [text, setText] = useState(myReview?.review ?? '');
    const [busy, setBusy] = useState(false);

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        router.post(`/sellers/${seller.id}/reviews`, { rating, review: text }, {
            preserveScroll: true,
            onStart: () => setBusy(true),
            onFinish: () => setBusy(false),
        });
    };

    return (
        <>
            <Head title={seller.shop_name} />

            <section className="mb-8 flex flex-col gap-4 rounded-2xl border p-4 sm:flex-row sm:flex-wrap sm:items-start sm:p-5">
                <div className="flex min-w-0 flex-1 gap-4">
                    <UserAvatar name={seller.name} src={seller.avatar} className="size-14 shrink-0 text-lg sm:size-16" />
                    <div className="min-w-0 flex-1">
                        <h1 className="flex flex-wrap items-center gap-x-1 text-xl font-bold break-words sm:text-2xl">
                            {seller.shop_name}
                            {seller.is_verified && <BadgeCheck className="size-5 shrink-0 text-primary" aria-label="Verified seller" />}
                        </h1>
                        <p className="text-sm text-muted-foreground">by {seller.name}</p>
                        <div className="mt-1 flex flex-col gap-1 text-sm sm:flex-row sm:flex-wrap sm:items-center sm:gap-3">
                            <span className="flex items-center gap-1">
                                <Stars value={seller.average} />
                                {seller.count > 0 ? `${seller.average} (${seller.count})` : 'No reviews yet'}
                            </span>
                            {(seller.address || seller.location) && (
                                <span className="flex items-center gap-1 text-muted-foreground"><MapPin className="size-3 shrink-0" />{seller.address ?? seller.location}</span>
                            )}
                        </div>
                    </div>
                </div>
                {seller.description && <p className="text-sm break-words sm:order-last sm:w-full">{seller.description}</p>}
                {isOwner ? (
                    <div className="flex flex-wrap gap-2 sm:shrink-0">
                        <Button asChild size="sm" className="flex-1 sm:flex-none"><Link href="/sell">Post a product</Link></Button>
                        <Button asChild size="sm" variant="outline" className="flex-1 sm:flex-none"><Link href="/seller/apply">Edit shop</Link></Button>
                    </div>
                ) : (
                    <div className="sm:shrink-0"><ReportButton type="user" id={seller.id} /></div>
                )}
            </section>
            <h2 className="mb-3 text-lg font-semibold">Products ({total})</h2>
            {listings.data.length === 0 ? (
                <p className="text-sm text-muted-foreground">This seller has no active products.</p>
            ) : (
                <ListingGrid listings={listings} />
            )}

            <section className="mt-10">
                <h2 className="mb-3 text-lg font-semibold">Reviews</h2>

                {canReview && (
                    <form onSubmit={submit} className="mb-6 flex flex-col gap-2 rounded-xl border p-4">
                        <p className="text-sm font-medium">{myReview ? 'Update your review' : 'Rate this seller'}</p>
                        <Stars value={rating} onPick={setRating} />
                        <textarea value={text} onChange={(e) => setText(e.target.value)} rows={2} maxLength={1000} placeholder="Share your experience (optional)" aria-label="Review" className="rounded-md border bg-transparent p-2 text-sm" />
                        <Button type="submit" size="sm" className="self-start" disabled={busy || rating === 0}>Submit review</Button>
                    </form>
                )}

                {reviews.length === 0 ? (
                    <p className="text-sm text-muted-foreground">No reviews yet.</p>
                ) : (
                    <ul className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        {reviews.map((review) => (
                            <li key={review.id} className="flex gap-3">
                                <UserAvatar name={review.reviewer.name} src={review.reviewer.avatar} />
                                <div>
                                    <p className="text-sm font-medium">{review.reviewer.name} <span className="font-normal text-muted-foreground">· {timeAgo(review.created_at)}</span></p>
                                    <Stars value={review.rating} />
                                    {review.review && <p className="mt-1 text-sm">{review.review}</p>}
                                </div>
                            </li>
                        ))}
                    </ul>
                )}
            </section>
        </>
    );
}
