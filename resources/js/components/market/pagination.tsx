import { Link } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import type { Paginated } from '@/types/market';

export default function Pagination({ meta }: { meta: Paginated<unknown>['meta'] }) {
    if (meta.last_page <= 1) {
        return null;
    }

    return (
        <nav className="mt-6 flex flex-wrap justify-center gap-1" aria-label="Pagination">
            {meta.links.map((link, i) =>
                link.url ? (
                    <Link
                        key={i}
                        href={link.url}
                        preserveScroll
                        className={cn(
                            'rounded-md border px-3 py-1.5 text-sm hover:bg-accent',
                            link.active && 'bg-primary text-primary-foreground hover:bg-primary',
                        )}
                        dangerouslySetInnerHTML={{ __html: link.label }}
                    />
                ) : (
                    <span
                        key={i}
                        className="rounded-md border px-3 py-1.5 text-sm text-muted-foreground opacity-50"
                        dangerouslySetInnerHTML={{ __html: link.label }}
                    />
                ),
            )}
        </nav>
    );
}
