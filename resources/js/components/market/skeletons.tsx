import type React from 'react';
import { Skeleton } from '@/components/ui/skeleton';

const S = ({ className = '' }: { className?: string }) => <Skeleton className={`bg-muted ${className}`} />;

/** Placeholder shaped like a FeedPost card. */
export function FeedPostSkeleton() {
    return (
        <div className="overflow-hidden rounded-2xl border bg-card" aria-hidden="true">
            <div className="flex items-center gap-3 p-4">
                <S className="size-10 rounded-full" />
                <div className="flex-1 space-y-2">
                    <S className="h-3.5 w-1/3" />
                    <S className="h-3 w-1/4" />
                </div>
            </div>
            <div className="space-y-2 px-4 pb-3">
                <S className="h-4 w-2/3" />
                <S className="h-3.5 w-full" />
            </div>
            <S className="aspect-[16/10] w-full rounded-none" />
            <div className="flex gap-3 p-3">
                <S className="h-8 flex-1" />
                <S className="h-8 flex-1" />
                <S className="h-8 flex-1" />
            </div>
        </div>
    );
}

export function FeedSkeletons({ count = 2 }: { count?: number }) {
    return (
        <div className="flex flex-col gap-4" role="status" aria-label="Loading">
            {Array.from({ length: count }, (_, i) => (
                <FeedPostSkeleton key={i} />
            ))}
        </div>
    );
}

const Stats = ({ count = 4 }: { count?: number }) => (
    <div className="grid grid-cols-2 gap-3 lg:grid-cols-4">
        {Array.from({ length: count }, (_, i) => (
            <S key={i} className="h-24 rounded-xl" />
        ))}
    </div>
);

const Rows = ({ count = 6 }: { count?: number }) => (
    <div className="divide-y rounded-xl border bg-card">
        {Array.from({ length: count }, (_, i) => (
            <div key={i} className="flex items-center gap-3 p-3">
                <S className="size-10 rounded-full" />
                <div className="flex-1 space-y-2">
                    <S className="h-3.5 w-1/2" />
                    <S className="h-3 w-1/3" />
                </div>
                <S className="h-8 w-16" />
            </div>
        ))}
    </div>
);

const Cards = ({ count = 8 }: { count?: number }) => (
    <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-4">
        {Array.from({ length: count }, (_, i) => (
            <div key={i} className="overflow-hidden rounded-xl border bg-card">
                <S className="aspect-[4/3] w-full rounded-none" />
                <div className="space-y-2 p-3">
                    <S className="h-5 w-1/2" />
                    <S className="h-4 w-3/4" />
                </div>
            </div>
        ))}
    </div>
);

const FormBlock = () => (
    <div className="grid gap-4 rounded-xl border bg-card p-4 md:grid-cols-2 md:p-6">
        {Array.from({ length: 6 }, (_, i) => (
            <div key={i} className="space-y-2">
                <S className="h-3.5 w-24" />
                <S className="h-9 w-full" />
            </div>
        ))}
        <S className="h-24 w-full md:col-span-2" />
    </div>
);

const Title = () => (
    <div className="space-y-2">
        <S className="h-8 w-56" />
        <S className="h-4 w-80 max-w-full" />
    </div>
);

export type SkeletonKind = 'home' | 'listing' | 'seller' | 'dashboard' | 'admin' | 'plans' | 'checkout' | 'inbox' | 'list' | 'cards' | 'form' | 'generic';

/** Pick the placeholder that matches the page being opened. */
export function skeletonFor(pathname: string): SkeletonKind {
    if (pathname === '/') return 'home';
    if (pathname === '/dashboard') return 'dashboard';
    if (pathname.startsWith('/admin')) return 'admin';
    if (/^\/plans\/[^/]+\/checkout/.test(pathname)) return 'checkout';
    if (pathname.startsWith('/plans')) return 'plans';
    if (pathname.startsWith('/inbox')) return 'inbox';
    if (pathname.startsWith('/sellers/')) return 'seller';
    if (pathname === '/sell' || pathname === '/seller/apply' || /^\/listings\/[^/]+\/edit/.test(pathname)) return 'form';
    if (pathname === '/my/listings' || pathname.startsWith('/notifications')) return 'list';
    if (pathname === '/saved') return 'cards';
    if (/^\/listings\/[^/]+$/.test(pathname)) return 'listing';

    return 'generic';
}

export function PageSkeleton({ kind = 'generic' }: { kind?: SkeletonKind }) {
    let body: React.ReactNode;

    switch (kind) {
        case 'home':
            body = (
                <div className="grid gap-5 lg:grid-cols-[260px_minmax(0,1fr)] xl:grid-cols-[260px_minmax(0,1fr)_320px]">
                    <div className="hidden space-y-3 lg:block">
                        {Array.from({ length: 7 }, (_, i) => (
                            <S key={i} className="h-10 w-full rounded-lg" />
                        ))}
                    </div>
                    <div className="space-y-4">
                        <S className="h-24 w-full rounded-2xl" />
                        <FeedPostSkeleton />
                        <FeedPostSkeleton />
                    </div>
                    <div className="hidden space-y-3 xl:block">
                        <S className="h-44 w-full rounded-xl" />
                        <S className="h-64 w-full rounded-xl" />
                    </div>
                </div>
            );
            break;
        case 'listing':
            body = (
                <div className="grid gap-6 lg:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)]">
                    <div className="space-y-3">
                        <S className="aspect-[4/3] w-full rounded-2xl" />
                        <div className="flex gap-2">
                            {[0, 1, 2, 3].map((i) => (
                                <S key={i} className="size-16 rounded-lg" />
                            ))}
                        </div>
                    </div>
                    <div className="space-y-4">
                        <S className="h-8 w-3/4" />
                        <S className="h-9 w-32" />
                        <S className="h-24 w-full rounded-xl" />
                        <S className="h-16 w-full rounded-xl" />
                        <S className="h-11 w-full" />
                    </div>
                </div>
            );
            break;
        case 'seller':
            body = (
                <div className="space-y-5">
                    <div className="flex items-center gap-4 rounded-2xl border bg-card p-5">
                        <S className="size-16 rounded-full" />
                        <div className="flex-1 space-y-2">
                            <S className="h-6 w-48" />
                            <S className="h-4 w-64 max-w-full" />
                        </div>
                    </div>
                    <Cards count={4} />
                </div>
            );
            break;
        case 'dashboard':
            body = (
                <div className="space-y-5">
                    <Title />
                    <Stats count={4} />
                    <Stats count={4} />
                    <div className="grid gap-4 lg:grid-cols-2">
                        <Rows count={4} />
                        <Rows count={4} />
                    </div>
                </div>
            );
            break;
        case 'admin':
            body = (
                <div className="space-y-4">
                    <S className="h-8 w-32" />
                    <Stats count={4} />
                    <S className="h-9 w-full max-w-md" />
                    <Rows count={5} />
                </div>
            );
            break;
        case 'plans':
            body = (
                <div className="space-y-6">
                    <div className="mx-auto max-w-xl space-y-2 text-center">
                        <S className="mx-auto h-9 w-64" />
                        <S className="mx-auto h-4 w-full" />
                    </div>
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        {[0, 1, 2, 3].map((i) => (
                            <S key={i} className="h-72 rounded-2xl" />
                        ))}
                    </div>
                </div>
            );
            break;
        case 'checkout':
            body = (
                <div className="space-y-4">
                    <Title />
                    <div className="grid gap-4 rounded-2xl border bg-card p-5 lg:grid-cols-3">
                        {[0, 1, 2].map((i) => (
                            <S key={i} className="h-40 rounded-xl" />
                        ))}
                    </div>
                </div>
            );
            break;
        case 'inbox':
            body = (
                <div className="grid gap-4 md:grid-cols-[320px_minmax(0,1fr)]">
                    <Rows count={6} />
                    <div className="hidden space-y-3 rounded-xl border bg-card p-4 md:block">
                        <S className="h-12 w-full" />
                        <S className="ml-auto h-10 w-2/3" />
                        <S className="h-10 w-1/2" />
                        <S className="ml-auto h-10 w-1/2" />
                    </div>
                </div>
            );
            break;
        case 'list':
            body = (
                <div className="space-y-4">
                    <Title />
                    <Rows count={7} />
                </div>
            );
            break;
        case 'cards':
            body = (
                <div className="space-y-4">
                    <Title />
                    <Cards />
                </div>
            );
            break;
        case 'form':
            body = (
                <div className="space-y-4">
                    <Title />
                    <FormBlock />
                    <S className="h-10 w-36" />
                </div>
            );
            break;
        default:
            body = (
                <div className="space-y-5">
                    <Title />
                    <Stats />
                    <Rows count={4} />
                </div>
            );
    }

    return (
        <div role="status" aria-label="Loading page">
            {body}
        </div>
    );
}
