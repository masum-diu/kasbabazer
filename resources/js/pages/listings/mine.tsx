import { Head, InfiniteScroll, Link, router } from '@inertiajs/react';
import { Package } from 'lucide-react';
import { ListingImage } from '@/components/market/listing-card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatPrice, statusLabels, timeAgo } from '@/lib/market';
import type { Listing } from '@/types/market';

export default function MyListings({ listings }: { listings: { data: Listing[] } }) {
    const setStatus = (slug: string, action: string) => router.post(`/listings/${slug}/status/${action}`, {}, { preserveScroll: true });

    return (
        <>
            <Head title="My listings" />
            <div>
                <div className="mb-4 flex items-center justify-between">
                    <h1 className="text-2xl font-bold">My listings</h1>
                    <Button asChild><Link href="/sell">New listing</Link></Button>
                </div>

                {listings.data.length === 0 ? (
                    <div className="flex flex-col items-center rounded-xl border border-dashed p-12 text-center">
                        <Package className="mb-3 size-8 text-muted-foreground" />
                        <p className="font-medium">You have not listed anything yet</p>
                        <Button asChild className="mt-4"><Link href="/sell">Sell your first product</Link></Button>
                    </div>
                ) : (
                    <InfiniteScroll data="listings" className="flex flex-col gap-3">
                        {listings.data.map((listing) => (
                            <div key={listing.id} className="flex gap-3 rounded-xl border p-3">
                                <Link href={`/listings/${listing.slug}`} className="size-20 shrink-0 overflow-hidden rounded-lg bg-muted">
                                    <ListingImage src={listing.thumbnail} alt={listing.title} className="size-full" />
                                </Link>
                                <div className="flex min-w-0 flex-1 flex-col gap-1">
                                    <div className="flex items-start justify-between gap-2">
                                        <Link href={`/listings/${listing.slug}`} className="truncate font-medium hover:underline">{listing.title}</Link>
                                        <Badge variant={listing.status === 'active' ? 'default' : 'secondary'}>{statusLabels[listing.status]}</Badge>
                                    </div>
                                    <p className="text-sm">{formatPrice(listing.price)} · {listing.views_count} views · {listing.comments_count} comments · {timeAgo(listing.published_at)}</p>
                                    <div className="mt-auto flex flex-wrap gap-1">
                                        <Button asChild size="sm" variant="outline"><Link href={`/listings/${listing.slug}/edit`}>Edit</Link></Button>
                                        {listing.status !== 'active' && <Button size="sm" variant="outline" onClick={() => setStatus(listing.slug, 'publish')}>Publish</Button>}
                                        {listing.status === 'active' && <Button size="sm" variant="outline" onClick={() => setStatus(listing.slug, 'sold')}>Mark sold</Button>}
                                        {listing.status === 'active' && <Button size="sm" variant="outline" onClick={() => setStatus(listing.slug, 'hide')}>Hide</Button>}
                                    </div>
                                </div>
                            </div>
                        ))}
                    </InfiniteScroll>
                )}
            </div>
        </>
    );
}
