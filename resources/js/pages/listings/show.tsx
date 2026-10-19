import { Head, Link, router, usePage, WhenVisible } from '@inertiajs/react';
import { BadgeCheck, Bookmark, Eye, Heart, MapPin, MessageCircle, Pencil, Rocket, Share2, Star, ThumbsUp } from 'lucide-react';
import { toast } from 'sonner';
import { useState } from 'react';
import CommentThread from '@/components/market/comment-thread';
import ListingCard, { ListingImage } from '@/components/market/listing-card';
import { ListingSkeletons } from '@/components/market/listing-grid';
import ReportButton from '@/components/market/report-button';
import UserAvatar from '@/components/market/user-avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { conditionLabels, formatPrice, statusLabels, timeAgo } from '@/lib/market';
import type { Comment, Listing } from '@/types/market';

type Props = {
    listing: Listing;
    comments?: Comment[];
    related?: Listing[];
    can: { manage: boolean; message: boolean; boosts_left: number };
};

const reactions: [string, string][] = [
    ['like', '👍 Like'],
    ['love', '❤️ Love'],
    ['interested', '👀 Interested'],
];

export default function ShowListing({ listing, comments, related, can }: Props) {
    const { auth } = usePage().props;
    const [active, setActive] = useState(0);
    const images = listing.images ?? [];
    const reacted = listing.viewer.reaction;

    const requireAuth = (action: () => void) => (auth.user ? action() : router.visit('/sign-in'));

    const react = (type: string) =>
        requireAuth(() => router.post(`/listings/${listing.slug}/reaction`, { type }, { preserveScroll: true, only: ['listing'] }));
    const save = () => requireAuth(() => router.post(`/listings/${listing.slug}/save`, {}, { preserveScroll: true, only: ['listing'] }));
    const message = () => requireAuth(() => router.post('/conversations', { listing_id: listing.id }));
    const setStatus = (action: string) => router.post(`/listings/${listing.slug}/status/${action}`, {}, { preserveScroll: true, only: ['listing', 'can'] });
    const share = async () => {
        const url = window.location.href;

        try {
            if (navigator.share) {
                await navigator.share({ title: listing.title, url });
            } else {
                await navigator.clipboard.writeText(url);
                toast.success('Link copied.');
            }
        } catch {
            // Sharing was cancelled; nothing to do.
        }
    };
    const boost = () => router.post(`/listings/${listing.slug}/boost`, {}, { preserveScroll: true, only: ['listing', 'can'] });
    const remove = () => {
        if (confirm('Delete this listing permanently?')) {
            router.delete(`/listings/${listing.slug}`);
        }
    };

    return (
        <>
            <Head title={listing.title} />

            <div className="grid gap-6 lg:grid-cols-[1fr_380px] lg:gap-x-8 lg:gap-y-8">
                <div className="contents">
                    <section aria-label="Photos" className="flex flex-col gap-2 lg:col-start-1">
                        <div className="aspect-[4/3] overflow-hidden rounded-2xl border bg-muted">
                            <ListingImage src={images[active]?.url} alt={listing.title} className="size-full" />
                        </div>
                        {images.length > 1 && (
                            <ul className="flex gap-2 overflow-x-auto">
                                {images.map((image, i) => (
                                    <li key={image.id}>
                                        <button
                                            type="button"
                                            onClick={() => setActive(i)}
                                            aria-label={`Photo ${i + 1}`}
                                            className={cn('size-16 overflow-hidden rounded-lg border-2', i === active ? 'border-primary' : 'border-transparent')}
                                        >
                                            <ListingImage src={image.url} alt="" className="size-full" />
                                        </button>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </section>

                    <section className="order-3 flex flex-col gap-3 lg:order-none lg:col-start-1">
                        <h2 className="text-lg font-semibold">Description</h2>
                        <p className="whitespace-pre-wrap text-sm leading-relaxed">{listing.description}</p>
                    </section>

                    <div className="order-4 lg:order-none lg:col-start-1">
                    <WhenVisible
                        data="comments"
                        buffer={300}
                        fallback={
                            <div className="space-y-3" aria-busy="true" aria-label="Loading comments">
                                <div id="comments-heading" className="h-6 w-40 animate-pulse rounded bg-muted" />
                                {[0, 1, 2].map((i) => (
                                    <div key={i} className="flex gap-2">
                                        <div className="size-8 animate-pulse rounded-full bg-muted" />
                                        <div className="h-14 flex-1 animate-pulse rounded-2xl bg-muted" />
                                    </div>
                                ))}
                            </div>
                        }
                    >
                        <CommentThread comments={comments ?? []} listingSlug={listing.slug} total={listing.comments_count} />
                    </WhenVisible>
                    </div>
                </div>

                <aside className="order-2 flex flex-col gap-4 lg:order-none lg:col-start-2 lg:row-span-3 lg:row-start-1 lg:sticky lg:top-20 lg:self-start">
                    <div className="flex flex-col gap-3 rounded-2xl border p-5">
                        <div className="flex flex-wrap items-center gap-2">
                            {listing.status !== 'active' && (
                                <Badge variant={listing.status === 'sold' ? 'destructive' : 'secondary'}>{statusLabels[listing.status]}</Badge>
                            )}
                            <Badge variant="outline">{conditionLabels[listing.condition]}</Badge>
                            {listing.category && <Badge variant="outline">{listing.category.name}</Badge>}
                        </div>
                        <h1 className="text-2xl font-bold">{listing.title}</h1>
                        <p className="text-3xl font-bold text-primary">
                            {formatPrice(listing.price)}
                            {listing.is_negotiable && <span className="ml-2 text-sm font-normal text-muted-foreground">Negotiable</span>}
                        </p>
                        <p className="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted-foreground">
                            <span className="flex items-center gap-1"><MapPin className="size-3" />{listing.location?.name ?? 'Kasba'}</span>
                            <span>Listed {timeAgo(listing.published_at)}</span>
                            <span className="flex items-center gap-1"><Eye className="size-3" />{listing.views_count} views</span>
                            {listing.quantity !== null && <span>Qty {listing.quantity}</span>}
                        </p>

                        {can.manage ? (
                            <div className="flex flex-col gap-2 border-t pt-3">
                                <p className="text-sm font-medium">Manage your listing</p>
                                <div className="flex flex-wrap gap-2">
                                    <Button asChild size="sm" variant="outline" className="gap-1"><Link href={`/listings/${listing.slug}/edit`}><Pencil className="size-3" /> Edit</Link></Button>
                                    {listing.status !== 'active' && <Button size="sm" variant="outline" onClick={() => setStatus('publish')}>Mark available</Button>}
                                    {listing.status !== 'reserved' && <Button size="sm" variant="outline" onClick={() => setStatus('reserved')}>Reserve</Button>}
                                    {listing.status !== 'sold' && <Button size="sm" variant="outline" onClick={() => setStatus('sold')}>Mark sold</Button>}
                                    {listing.status !== 'hidden' && <Button size="sm" variant="outline" onClick={() => setStatus('hide')}>Hide</Button>}
                                    {listing.status === 'active' && (
                                        listing.boosted ? (
                                            <Button size="sm" variant="outline" disabled className="gap-1"><Rocket className="size-3" /> Boosted</Button>
                                        ) : can.boosts_left > 0 ? (
                                            <Button size="sm" onClick={boost} className="gap-1"><Rocket className="size-3" /> Boost ({can.boosts_left} left)</Button>
                                        ) : (
                                            <Button asChild size="sm" variant="outline" className="gap-1"><Link href="/plans"><Rocket className="size-3" /> Get boosts</Link></Button>
                                        )
                                    )}
                                    <Button size="sm" variant="destructive" onClick={remove}>Delete</Button>
                                </div>
                            </div>
                        ) : (
                            <div className="flex flex-col gap-2 border-t pt-3">
                                <Button onClick={message} className="gap-2" disabled={listing.status === 'sold'}>
                                    <MessageCircle className="size-4" /> Message seller
                                </Button>
                                <Button variant="outline" onClick={save} className="gap-2">
                                    <Bookmark className={cn('size-4', listing.viewer.saved && 'fill-current text-primary')} />
                                    {listing.viewer.saved ? 'Saved' : 'Save'}
                                </Button>
                            </div>
                        )}

                        <div className="flex items-center justify-between border-t pt-3">
                            <div className="flex flex-wrap gap-1">
                                {reactions.map(([type, label]) => (
                                    <button
                                        key={type}
                                        type="button"
                                        onClick={() => react(type)}
                                        aria-pressed={reacted === type}
                                        className={cn('rounded-full border px-2.5 py-1 text-xs hover:bg-accent', reacted === type && 'border-primary bg-primary/10 font-medium')}
                                    >
                                        {label}
                                    </button>
                                ))}
                            </div>
                            <span className="flex items-center gap-1 text-xs text-muted-foreground"><Heart className="size-3" /> {listing.likes_count}</span>
                        </div>
                        <div className="-ml-2 flex items-center">
                            <Button type="button" variant="ghost" size="sm" onClick={share} className="gap-1 text-muted-foreground"><Share2 className="size-4" /> Share</Button>
                            {!can.manage && <ReportButton type="listing" id={listing.id} />}
                        </div>
                    </div>

                    {listing.seller && (
                        <div className="rounded-2xl border p-5">
                            <p className="mb-3 text-sm font-medium text-muted-foreground">Seller</p>
                            <Link href={`/sellers/${listing.seller.id}`} className="flex items-center gap-3">
                                <UserAvatar name={listing.seller.name} src={listing.seller.avatar} className="size-12" />
                                <div className="min-w-0">
                                    <p className="flex items-center gap-1 truncate font-semibold">
                                        {listing.seller.shop_name ?? listing.seller.name}
                                        {listing.seller.is_verified && <BadgeCheck className="size-4 shrink-0 text-primary" />}
                                    </p>
                                    <p className="flex items-center gap-1 text-xs text-muted-foreground">
                                        {listing.seller.rating != null ? (
                                            <><Star className="size-3 fill-amber-400 text-amber-400" /> {listing.seller.rating} ({listing.seller.reviews_count} reviews)</>
                                        ) : (
                                            'No reviews yet'
                                        )}
                                    </p>
                                </div>
                            </Link>
                        </div>
                    )}
                </aside>
            </div>

            <WhenVisible data="related" buffer={300} fallback={<div className="mt-12"><ListingSkeletons count={4} /></div>}>
                {related && related.length > 0 && (
                    <section className="mt-12">
                        <h2 className="mb-4 flex items-center gap-2 text-lg font-semibold"><ThumbsUp className="size-4" /> More in {listing.category?.name}</h2>
                        <ul className="grid grid-cols-2 gap-3 md:grid-cols-4">
                            {related.map((item) => (
                                <li key={item.id} className="contents"><ListingCard listing={item} /></li>
                            ))}
                        </ul>
                    </section>
                )}
            </WhenVisible>        </>
    );
}
