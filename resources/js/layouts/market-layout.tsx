import { Link, router, usePage } from '@inertiajs/react';
import { Bell, MessageCircle, Plus, Search } from 'lucide-react';
import { useEffect, useState } from 'react';
import ClerkSync from '@/components/clerk-sync';
import InstallButton from '@/components/market/install-button';
import BottomNav from '@/components/market/bottom-nav';
import { PageSkeleton, skeletonFor, type SkeletonKind } from '@/components/market/skeletons';
import UserMenu from '@/components/market/user-menu';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useFlashToast } from '@/hooks/use-flash-toast';

/** True while navigating to a different page (not filters, likes or other in-page requests). */
function useNavigating() {
    const [navigating, setNavigating] = useState<SkeletonKind | null>(null);

    useEffect(() => {
        let timer: ReturnType<typeof setTimeout> | undefined;
        const stop = () => {
            clearTimeout(timer);
            setNavigating(null);
        };
        const offStart = router.on('start', (event) => {
            const { visit } = event.detail;
            const samePage = visit.url.pathname === window.location.pathname;

            if (visit.method !== 'get' || samePage || visit.only.length > 0 || visit.except.length > 0 || visit.preserveState === true) {
                return;
            }

            // Only show the placeholder if the page takes a moment, so quick loads do not flash.
            const kind = skeletonFor(visit.url.pathname);
            timer = setTimeout(() => setNavigating(kind), 150);
        });
        const offFinish = router.on('finish', stop);

        return () => {
            offStart();
            offFinish();
            clearTimeout(timer);
        };
    }, []);

    return navigating;
}

export default function MarketLayout({ children }: { children: React.ReactNode }) {
    const navigating = useNavigating();

    const { auth } = usePage().props;
    const [query, setQuery] = useState(() =>
        typeof window === 'undefined' ? '' : (new URLSearchParams(window.location.search).get('search') ?? ''),
    );

    useFlashToast();

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        router.get('/', query ? { search: query } : {}, { preserveState: false });
    };

    return (
        <div className="app-canvas flex min-h-svh flex-col text-foreground">
            <ClerkSync />
            <header className="sticky top-0 z-30 border-b bg-card/95 shadow-sm backdrop-blur">
                <div className="brand-stripe" aria-hidden="true" />
                <div className="mx-auto flex h-14 w-full max-w-[1400px] items-center gap-3 px-4">
                    <Link href="/" className="shrink-0 text-lg font-bold tracking-tight">
                        Kasba<span className="text-brand">Bazer</span>
                    </Link>

                    <form onSubmit={submit} className="relative mx-auto hidden w-full max-w-md sm:block" role="search">
                        <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            value={query}
                            onChange={(e) => setQuery(e.target.value)}
                            placeholder="Search products in Kasba"
                            className="rounded-full border-primary/20 bg-secondary/60 pl-9 focus-visible:border-primary"
                            aria-label="Search products"
                        />
                    </form>

                    <nav className="ml-auto flex items-center gap-1 sm:ml-0">
                        <InstallButton />
                        <Button asChild size="sm" className="hidden gap-1 md:inline-flex">
                            <Link href="/sell"><Plus className="size-4" /> <span className="hidden sm:inline">Sell</span></Link>
                        </Button>
                        {auth.user ? (
                            <>
                                <Button asChild variant="ghost" size="icon" className="relative" aria-label="Notifications">
                                    <Link href="/notifications">
                                        <Bell className="size-5" />
                                        {auth.unread_notifications > 0 && (
                                            <span className="absolute -top-0.5 -right-0.5 flex size-4 items-center justify-center rounded-full bg-destructive text-[10px] text-white">
                                                {auth.unread_notifications > 9 ? '9+' : auth.unread_notifications}
                                            </span>
                                        )}
                                    </Link>
                                </Button>
                                <Button asChild variant="ghost" size="icon" className="relative hidden md:inline-flex" aria-label="Messages">
                                    <Link href="/inbox">
                                        <MessageCircle className="size-5" />
                                        {auth.unread_messages > 0 && (
                                            <span className="absolute -top-0.5 -right-0.5 flex size-4 items-center justify-center rounded-full bg-destructive text-[10px] text-white">
                                                {auth.unread_messages > 9 ? '9+' : auth.unread_messages}
                                            </span>
                                        )}
                                    </Link>
                                </Button>
                                <div className="hidden md:block">
                                    <UserMenu />
                                </div>
                            </>
                        ) : (
                            <>
                                <Button asChild variant="ghost" size="sm">
                                    <Link href="/sign-in">Sign in</Link>
                                </Button>
                            </>
                        )}
                    </nav>
                </div>
                <form onSubmit={submit} className="px-4 pb-2 sm:hidden" role="search">
                    <Input value={query} onChange={(e) => setQuery(e.target.value)} placeholder="Search products" aria-label="Search products" />
                </form>
            </header>

            <main className="mx-auto w-full max-w-[1400px] flex-1 px-4 py-5 pb-24 md:pb-5" aria-busy={navigating !== null}>{navigating ? <PageSkeleton kind={navigating} /> : children}</main>

            <footer className="hidden border-t py-6 text-center text-xs text-muted-foreground md:block">
                © {new Date().getFullYear()} Kasba Bazer · Local marketplace for Kasba, Brahmanbaria
            </footer>

            <BottomNav />
        </div>
    );
}
