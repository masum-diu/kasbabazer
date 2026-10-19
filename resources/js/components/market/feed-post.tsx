import { Link, router, usePage } from '@inertiajs/react';
import { BadgeCheck, Bookmark, Heart, MapPin, MessageCircle, Share2, ThumbsUp } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import { ListingImage } from '@/components/market/listing-card';
import UserAvatar from '@/components/market/user-avatar';
import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';
import { conditionLabels, formatPrice, statusLabels, timeAgo, tone } from '@/lib/market';
import type { Listing } from '@/types/market';

/** Facebook-style post card for a listing: author header, text, photos, stats and action bar. */
export default function FeedPost({ listing }: { listing: Listing }) {
    const { auth } = usePage().props;
    const [liked, setLiked] = useState(listing.viewer.reaction !== null);
    const [likes, setLikes] = useState(listing.likes_count);
    const [saved, setSaved] = useState(listing.viewer.saved);
    const [expanded, setExpanded] = useState(false);

    const images = (listing.images ?? []).slice(0, 3);
    const url = `/listings/${listing.slug}`;
    const long = listing.description.length > 140;
    const requireAuth = (fn: () => void) => (auth.user ? fn() : router.visit('/sign-in'));

    // Optimistic UI; the server stays the source of truth on the next full load.
    const post = (path: string, data: Record<string, string> = {}) =>
        router.post(path, data, { preserveScroll: true, preserveState: true, only: ['auth'] });

    const like = () =>
        requireAuth(() => {
            setLiked((v) => !v);
            setLikes((n) => (liked ? Math.max(0, n - 1) : n + 1));
            post(`${url}/reaction`, { type: 'like' });
        });

    const save = () =>
        requireAuth(() => {
            setSaved((v) => !v);
            post(`${url}/save`);
        });

    const share = async () => {
        const full = `${window.location.origin}${url}`;

        try {
            if (navigator.share) {
                await navigator.share({ title: listing.title, url: full });
            } else {
                await navigator.clipboard.writeText(full);
                toast.success('Link copied.');
            }
        } catch {
            // Sharing cancelled.
        }
    };

    return (
        <article className="overflow-hidden rounded-2xl border bg-card shadow-sm transition-shadow hover:shadow-md">
            <header className="flex items-center gap-3 p-4 pb-2">
                <Link href={listing.seller ? `/sellers/${listing.seller.id}` : '#'}>
                    <UserAvatar name={listing.seller?.name ?? '?'} src={listing.seller?.avatar} className="size-10" />
                </Link>
                <div className="min-w-0 flex-1">
                    <p className="flex items-center gap-1 text-sm font-semibold">
                        <Link href={listing.seller ? `/sellers/${listing.seller.id}` : '#'} className="truncate hover:underline">
                            {listing.seller?.shop_name ?? listing.seller?.name}
                        </Link>
                        {listing.seller?.is_verified && <BadgeCheck className="size-4 shrink-0 fill-sky/20 text-sky" aria-label="Verified seller" />}
                    </p>
                    <p className="flex items-center gap-1 text-xs text-muted-foreground">
                        {timeAgo(listing.published_at)} · <MapPin className="size-3" /> {listing.location?.name ?? 'Kasba'}
                        <span className={cn('ml-1 rounded-full px-2 py-0.5 text-[11px] font-medium', tone(listing.category?.id ?? 0))}>{listing.category?.name}</span>
                    </p>
                </div>
                {listing.boosted && listing.status === 'active' && <Badge className="border-transparent bg-sun-gradient">🚀 Boosted</Badge>}
                {listing.status !== 'active' && (
                    <Badge variant={listing.status === 'sold' ? 'destructive' : 'secondary'}>{statusLabels[listing.status]}</Badge>
                )}
            </header>

            <div className="px-4 pb-3">
                <Link href={url} className="block text-base font-semibold hover:underline">
                    {listing.title}
                </Link>
                <p className="mt-0.5 text-lg font-bold text-primary">
                    {formatPrice(listing.price)}
                    <span className="ml-2 text-xs font-normal text-muted-foreground">
                        {conditionLabels[listing.condition]}
                        {listing.is_negotiable ? ' · Negotiable' : ''}
                    </span>
                </p>
                <p className="mt-1 text-sm whitespace-pre-wrap">
                    {long && !expanded ? `${listing.description.slice(0, 140)}… ` : listing.description}
                    {long && !expanded && (
                        <button type="button" onClick={() => setExpanded(true)} className="font-medium text-muted-foreground hover:underline">
                            See more
                        </button>
                    )}
                </p>
            </div>

            {images.length > 0 && (
                <Link
                    href={url}
                    aria-label={`Open ${listing.title}`}
                    className={cn('grid gap-0.5 bg-muted', images.length === 1 && 'grid-cols-1', images.length === 2 && 'grid-cols-2', images.length === 3 && 'grid-cols-2')}
                >
                    {images.map((image, i) => (
                        <div key={image.id} className={cn('overflow-hidden', images.length === 1 ? 'aspect-[16/10]' : 'aspect-square', images.length === 3 && i === 0 && 'row-span-2 aspect-auto')}>
                            <ListingImage src={image.url} alt={i === 0 ? listing.title : ''} className="size-full" />
                        </div>
                    ))}
                </Link>
            )}

            <div className="flex items-center justify-between px-4 py-2 text-xs text-muted-foreground">
                <span className="flex items-center gap-1">
                    <span className="bg-brand flex size-4 items-center justify-center rounded-full">
                        <ThumbsUp className="size-2.5" />
                    </span>
                    {likes}
                </span>
                <Link href={`${url}#comments-heading`} className="hover:underline">
                    {listing.comments_count} comment{listing.comments_count === 1 ? '' : 's'} · {listing.views_count} views
                </Link>
            </div>

            <div className="mx-4 grid grid-cols-4 border-t py-1 text-sm font-medium text-muted-foreground">
                <button type="button" onClick={like} aria-pressed={liked} className={cn('flex items-center justify-center gap-1.5 rounded-md py-2 hover:bg-accent', liked && 'text-coral')}>
                    <Heart className={cn('size-4', liked && 'fill-current')} /> Like
                </button>
                <Link href={`${url}#comments-heading`} className="flex items-center justify-center gap-1.5 rounded-md py-2 hover:bg-accent">
                    <MessageCircle className="size-4" /> Comment
                </Link>
                <button type="button" onClick={share} className="flex items-center justify-center gap-1.5 rounded-md py-2 hover:bg-accent">
                    <Share2 className="size-4" /> Share
                </button>
                <button type="button" onClick={save} aria-pressed={saved} className={cn('flex items-center justify-center gap-1.5 rounded-md py-2 hover:bg-accent', saved && 'text-primary')}>
                    <Bookmark className={cn('size-4', saved && 'fill-current')} /> Save
                </button>
            </div>
        </article>
    );
}
