import { Link, router } from '@inertiajs/react';
import { BadgeCheck, Bookmark, Heart, MessageCircle, Star } from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';
import { conditionLabels, formatPrice, statusLabels, timeAgo } from '@/lib/market';
import type { Listing } from '@/types/market';
import { usePage } from '@inertiajs/react';

export function ListingImage({ src, alt, className }: { src?: string | null; alt: string; className?: string }) {
    const [failed, setFailed] = useState(false);
    const [loaded, setLoaded] = useState(false);

    if (!src || failed) {
        return (
            <div className={cn('flex items-center justify-center bg-gradient-to-br from-muted to-muted/40 text-xs text-muted-foreground', className)}>
                No photo
            </div>
        );
    }

    return (
        <img
            src={src}
            alt={alt}
            loading="lazy"
            decoding="async"
            onLoad={() => setLoaded(true)}
            onError={() => setFailed(true)}
            className={cn('object-cover transition-opacity duration-500', loaded ? 'opacity-100' : 'opacity-0', className)}
        />
    );
}

export default function ListingCard({ listing }: { listing: Listing }) {
    const { auth } = usePage().props;

    const toggleSave = (e: React.MouseEvent) => {
        e.preventDefault();
        e.stopPropagation();

        if (!auth.user) {
            router.visit('/sign-in');

            return;
        }

        router.post(`/listings/${listing.slug}/save`, {}, { preserveScroll: true });
    };

    return (
        <Link
            href={`/listings/${listing.slug}`}
            className="group flex flex-col overflow-hidden rounded-xl border bg-card transition-shadow hover:shadow-md"
        >
            <div className="relative aspect-[4/3] overflow-hidden bg-muted">
                <ListingImage
                    src={listing.thumbnail}
                    alt={listing.title}
                    className="size-full transition-transform duration-300 group-hover:scale-105"
                />
                {listing.status !== 'active' && (
                    <Badge className="absolute top-2 left-2" variant={listing.status === 'sold' ? 'destructive' : 'secondary'}>
                        {statusLabels[listing.status] ?? listing.status}
                    </Badge>
                )}
                <button
                    type="button"
                    onClick={toggleSave}
                    aria-label={listing.viewer.saved ? 'Remove from saved' : 'Save listing'}
                    className="absolute top-2 right-2 rounded-full bg-background/90 p-1.5 shadow hover:bg-background"
                >
                    <Bookmark className={cn('size-4', listing.viewer.saved && 'fill-current text-primary')} />
                </button>
            </div>
            <div className="flex flex-1 flex-col gap-1 p-3">
                <p className="text-lg font-bold text-primary">{formatPrice(listing.price)}</p>
                <p className="line-clamp-2 text-sm">{listing.title}</p>
                <p className="text-xs text-muted-foreground">
                    {conditionLabels[listing.condition]} · {listing.location?.name ?? 'Kasba'} · {timeAgo(listing.published_at)}
                </p>
                <div className="mt-auto flex items-center justify-between gap-2 pt-2 text-xs text-muted-foreground">
                    <span className="flex min-w-0 items-center gap-1">
                        <span className="truncate">{listing.seller?.shop_name ?? listing.seller?.name}</span>
                        {listing.seller?.is_verified && <BadgeCheck className="size-3.5 shrink-0 text-sky" />}
                        {listing.seller?.rating != null && (
                            <span className="ml-1 hidden items-center gap-0.5 sm:flex">
                                <Star className="size-3 fill-amber-400 text-amber-400" />
                                {listing.seller.rating}
                            </span>
                        )}
                    </span>
                    <span className="flex shrink-0 items-center gap-2">
                        <span className="flex items-center gap-0.5"><Heart className="size-3" />{listing.likes_count}</span>
                        <span className="flex items-center gap-0.5"><MessageCircle className="size-3" />{listing.comments_count}</span>
                    </span>
                </div>
            </div>
        </Link>
    );
}
