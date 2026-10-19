import { Head, Link } from '@inertiajs/react';
import { Bookmark } from 'lucide-react';
import ListingGrid from '@/components/market/listing-grid';
import { Button } from '@/components/ui/button';
import type { Listing } from '@/types/market';

export default function Saved({ listings, total }: { listings: { data: Listing[] }; total: number }) {
    return (
        <>
            <Head title="Saved products" />
            <h1 className="mb-4 text-2xl font-bold">Saved products ({total})</h1>
            {listings.data.length === 0 ? (
                <div className="flex flex-col items-center rounded-xl border border-dashed p-12 text-center">
                    <Bookmark className="mb-3 size-8 text-muted-foreground" />
                    <p className="font-medium">Nothing saved yet</p>
                    <p className="mt-1 text-sm text-muted-foreground">Tap the bookmark on any product to find it here later.</p>
                    <Button asChild className="mt-4"><Link href="/">Browse products</Link></Button>
                </div>
            ) : (
                <ListingGrid listings={listings} />
            )}
        </>
    );
}