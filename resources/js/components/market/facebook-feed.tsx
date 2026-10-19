import { Facebook } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

/**
 * Shows a public Facebook Page's recent posts using Facebook's Page Plugin.
 * The iframe is only created once the section scrolls into view (lazy), and
 * nothing renders when no page URL is configured.
 */
export default function FacebookFeed({ pageUrl }: { pageUrl: string | null }) {
    const ref = useRef<HTMLDivElement>(null);
    const [visible, setVisible] = useState(false);

    useEffect(() => {
        const el = ref.current;

        if (!el || !pageUrl) {
            return;
        }

        const observer = new IntersectionObserver(
            ([entry]) => {
                if (entry.isIntersecting) {
                    setVisible(true);
                    observer.disconnect();
                }
            },
            { rootMargin: '300px' },
        );
        observer.observe(el);

        return () => observer.disconnect();
    }, [pageUrl]);

    if (!pageUrl) {
        return null;
    }

    const src =
        'https://www.facebook.com/plugins/page.php?' +
        new URLSearchParams({
            href: pageUrl,
            tabs: 'timeline',
            width: '340',
            height: '700',
            small_header: 'false',
            adapt_container_width: 'true',
            hide_cover: 'false',
            show_facepile: 'false',
        }).toString();

    return (
        <section ref={ref} aria-labelledby="fb-heading" className="rounded-xl border p-4">
            <h2 id="fb-heading" className="mb-3 flex items-center gap-2 text-sm font-semibold">
                <Facebook className="size-4 text-[#1877F2]" /> From our Facebook page
            </h2>
            {visible ? (
                <iframe
                    title="Facebook page posts"
                    src={src}
                    width="100%"
                    height="700"
                    loading="lazy"
                    style={{ border: 'none', overflow: 'hidden' }}
                    allow="encrypted-media"
                />
            ) : (
                <div className="h-[700px] animate-pulse rounded-lg bg-muted" aria-hidden="true" />
            )}
            <a href={pageUrl} target="_blank" rel="noopener noreferrer" className="mt-2 block text-center text-xs text-muted-foreground hover:underline">
                Open on Facebook
            </a>
        </section>
    );
}