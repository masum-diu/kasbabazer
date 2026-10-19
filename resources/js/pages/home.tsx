import { Head, InfiniteScroll, Link, router, usePage } from '@inertiajs/react';
import { Bell, Bookmark, Image as ImageIcon, LayoutDashboard, MessageCircle, Package, PackageSearch, Rocket, Shield, SlidersHorizontal, Star, Store, Tag, X } from 'lucide-react';
import { useState } from 'react';
import FacebookFeed from '@/components/market/facebook-feed';
import FeedPost from '@/components/market/feed-post';
import { FeedSkeletons } from '@/components/market/skeletons';
import UserAvatar from '@/components/market/user-avatar';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';
import { conditionLabels, tone } from '@/lib/market';
import type { Category, Listing } from '@/types/market';

type Filters = {
    search?: string;
    category_id?: string;
    min_price?: string;
    max_price?: string;
    condition?: string;
    sort?: string;
};

type TopSeller = { id: number; name: string; avatar: string | null; shop_name: string | null; rating: number; listings: number };

type Props = {
    listings: { data: Listing[] };
    total: number;
    topSellers?: TopSeller[];
    facebookPageUrl: string | null;
    filters: Filters;
    categories: Category[];
};

const sorts: [string, string][] = [
    ['latest', 'Newest'],
    ['popular', 'Most popular'],
    ['price_low', 'Price: low to high'],
    ['price_high', 'Price: high to low'],
    ['oldest', 'Oldest'],
];

function NavLink({ href, icon: Icon, children, badge, color = 0 }: { href: string; icon: typeof Bell; children: React.ReactNode; badge?: number; color?: number }) {
    return (
        <Link href={href} className="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium hover:bg-accent">
            <span className={cn('flex size-8 items-center justify-center rounded-full', tone(color))}>
                <Icon className="size-4" />
            </span>
            <span className="flex-1">{children}</span>
            {badge ? <span className="rounded-full bg-destructive px-1.5 text-[10px] text-white">{badge}</span> : null}
        </Link>
    );
}

export default function Home({ listings, total, topSellers = [], facebookPageUrl, filters, categories }: Props) {
    const { auth } = usePage().props;
    const [showFilters, setShowFilters] = useState(false);
    const [min, setMin] = useState(filters.min_price ?? '');
    const [max, setMax] = useState(filters.max_price ?? '');

    const apply = (patch: Partial<Filters>) => {
        const next = Object.fromEntries(Object.entries({ ...filters, ...patch }).filter(([, v]) => v !== undefined && v !== ''));
        router.get('/', next, { preserveScroll: true, preserveState: true });
    };

    const activeCategory = filters.category_id;
    const hasFilters = Object.values(filters).some((v) => v !== undefined && v !== '');
    const user = auth.user;

    const categoryList = (
        <ul className="flex flex-col gap-0.5 text-sm">
            <li>
                <button type="button" onClick={() => apply({ category_id: undefined })} className={cn('flex w-full items-center gap-3 rounded-lg px-3 py-2 text-left hover:bg-accent', !activeCategory && 'bg-accent font-medium')}>
                    <Tag className="size-4 text-primary" /> All categories
                </button>
            </li>
            {categories.map((cat) => (
                <li key={cat.id}>
                    <button type="button" onClick={() => apply({ category_id: String(cat.id) })} className={cn('w-full rounded-lg px-3 py-2 text-left hover:bg-accent', activeCategory === String(cat.id) && 'bg-accent font-medium')}>
                        {cat.name}
                    </button>
                    {cat.children && cat.children.length > 0 && (
                        <ul className="ml-4 border-l pl-2">
                            {cat.children.map((child) => (
                                <li key={child.id}>
                                    <button type="button" onClick={() => apply({ category_id: String(child.id) })} className={cn('w-full rounded-lg px-3 py-1.5 text-left text-muted-foreground hover:bg-accent', activeCategory === String(child.id) && 'bg-accent font-medium text-foreground')}>
                                        {child.name}
                                    </button>
                                </li>
                            ))}
                        </ul>
                    )}
                </li>
            ))}
        </ul>
    );

    const filterPanel = (
        <div className="flex flex-col gap-4 rounded-xl border bg-card p-4">
            <h2 className="text-sm font-semibold">Filters</h2>
            <form
                className="flex items-center gap-2"
                onSubmit={(e) => {
                    e.preventDefault();
                    apply({ min_price: min, max_price: max });
                }}
            >
                <Input type="number" min={0} value={min} onChange={(e) => setMin(e.target.value)} placeholder="Min ৳" aria-label="Minimum price" className="h-8" />
                <span>–</span>
                <Input type="number" min={0} value={max} onChange={(e) => setMax(e.target.value)} placeholder="Max ৳" aria-label="Maximum price" className="h-8" />
                <Button type="submit" size="sm">Go</Button>
            </form>
            <div className="flex flex-wrap gap-1.5">
                {Object.entries(conditionLabels).map(([value, label]) => (
                    <button
                        key={value}
                        type="button"
                        onClick={() => apply({ condition: filters.condition === value ? undefined : value })}
                        className={cn('rounded-full border px-3 py-1 text-xs hover:bg-accent', filters.condition === value && 'border-primary bg-primary text-primary-foreground hover:bg-primary')}
                    >
                        {label}
                    </button>
                ))}
            </div>
        </div>
    );

    return (
        <>
            <Head title="Buy and sell in Kasba" />

            <div className="grid gap-6 lg:grid-cols-[260px_minmax(0,1fr)] xl:grid-cols-[260px_minmax(0,1fr)_320px]">
                {/* Left column: shortcuts + categories */}
                <aside className="hidden lg:block">
                    <div className="sticky top-20 flex max-h-[calc(100svh-6rem)] flex-col gap-1 overflow-y-auto pr-1">
                        {user ? (
                            <>
                                <Link href="/settings/profile" className="flex items-center gap-3 rounded-lg px-3 py-2 hover:bg-accent">
                                    <UserAvatar name={user.name} src={user.avatar} />
                                    <span className="truncate text-sm font-semibold">{user.name}</span>
                                </Link>
                                <NavLink href="/dashboard" icon={LayoutDashboard} color={0}>Dashboard</NavLink>
                                <NavLink href="/my/listings" icon={Package} color={1}>My listings</NavLink>
                                <NavLink href="/saved" icon={Bookmark} color={3}>Saved</NavLink>
                                <NavLink href="/inbox" icon={MessageCircle} badge={auth.unread_messages} color={4}>Messages</NavLink>
                                <NavLink href="/notifications" icon={Bell} badge={auth.unread_notifications} color={2}>Notifications</NavLink>
                                <NavLink href={auth.is_seller ? `/sellers/${user.id}` : '/seller/apply'} icon={Store}>{auth.is_seller ? 'My shop' : 'Become a seller'}</NavLink>
                                <NavLink href="/plans" icon={Rocket} color={2}>Plans & boosts</NavLink>
                                {user.is_admin && <NavLink href="/admin" icon={Shield}>Admin</NavLink>}
                            </>
                        ) : (
                            <div className="rounded-xl border bg-card p-4 text-sm">
                                <p className="font-semibold">Join Kasba Bazer</p>
                                <p className="mt-1 text-muted-foreground">Sign in to like, comment, save and message sellers.</p>
                                <div className="mt-3 flex gap-2">
                                    <Button asChild size="sm"><Link href="/sign-in">Sign in</Link></Button>
                                    <Button asChild size="sm" variant="outline"><Link href="/sign-up">Sign up</Link></Button>
                                </div>
                            </div>
                        )}
                        <hr className="my-2" />
                        <h2 className="px-3 pb-1 text-xs font-semibold tracking-wide text-muted-foreground uppercase">Categories</h2>
                        {categoryList}
                    </div>
                </aside>

                {/* Center column: composer + feed */}
                <section className="flex min-w-0 flex-col gap-4">
                    <div className="flex items-center gap-3 rounded-xl border bg-card p-4 shadow-sm">
                        {user ? <UserAvatar name={user.name} src={user.avatar} /> : <UserAvatar name="K B" />}
                        <Link href="/sell" className="flex-1 rounded-full bg-muted px-4 py-2 text-sm text-muted-foreground hover:bg-accent">
                            {user ? `What are you selling, ${user.name.split(' ')[0]}?` : 'What are you selling?'}
                        </Link>
                        <Link href="/sell" aria-label="Add photos" className="rounded-full p-2 text-primary hover:bg-accent">
                            <ImageIcon className="size-5" />
                        </Link>
                    </div>

                    <div className="flex flex-wrap items-center justify-between gap-2">
                        <p className="text-sm text-muted-foreground">
                            {total} post{total === 1 ? '' : 's'}
                            {filters.search ? ` for “${filters.search}”` : ''}
                        </p>
                        <div className="flex items-center gap-2">
                            {hasFilters && (
                                <Button variant="ghost" size="sm" onClick={() => router.get('/')} className="gap-1">
                                    <X className="size-4" /> Clear
                                </Button>
                            )}
                            <Button variant="outline" size="sm" className="gap-1 xl:hidden" onClick={() => setShowFilters((v) => !v)}>
                                <SlidersHorizontal className="size-4" /> Filters
                            </Button>
                            <select
                                value={filters.sort ?? 'latest'}
                                onChange={(e) => apply({ sort: e.target.value })}
                                aria-label="Sort posts"
                                className="h-8 rounded-md border bg-background px-2 text-sm"
                            >
                                {sorts.map(([value, label]) => (
                                    <option key={value} value={value}>{label}</option>
                                ))}
                            </select>
                        </div>
                    </div>

                    {/* Category chips for small screens (the left column is hidden there) */}
                    <div className="-mx-4 flex gap-2 overflow-x-auto px-4 lg:hidden" role="list" aria-label="Categories">
                        {[{ id: 0, name: 'All' }, ...categories].map((cat) => (
                            <button
                                key={cat.id}
                                type="button"
                                role="listitem"
                                onClick={() => apply({ category_id: cat.id ? String(cat.id) : undefined })}
                                className={cn('shrink-0 rounded-full border px-3 py-1 text-sm', (cat.id ? activeCategory === String(cat.id) : !activeCategory) && 'border-primary bg-primary text-primary-foreground')}
                            >
                                {cat.name}
                            </button>
                        ))}
                    </div>

                    {showFilters && <div className="xl:hidden">{filterPanel}</div>}

                    {listings.data.length === 0 ? (
                        <div className="flex flex-col items-center rounded-xl border border-dashed bg-card p-12 text-center">
                            <PackageSearch className="mb-3 size-8 text-muted-foreground" />
                            <p className="font-medium">No posts found</p>
                            <p className="mt-1 text-sm text-muted-foreground">Try a different search or clear the filters.</p>
                        </div>
                    ) : (
                        <InfiniteScroll
                            data="listings"
                            className="flex flex-col gap-4"
                            loading={<FeedSkeletons count={2} />}
                        >
                            {listings.data.map((listing) => (
                                <FeedPost key={listing.id} listing={listing} />
                            ))}
                        </InfiniteScroll>
                    )}
                </section>

                {/* Right column: filters, top sellers, Facebook page */}
                <aside className="hidden xl:block">
                    <div className="sticky top-20 flex max-h-[calc(100svh-6rem)] flex-col gap-4 overflow-y-auto">
                        {filterPanel}

                        {topSellers.length > 0 && (
                            <div className="rounded-xl border bg-card p-4">
                                <h2 className="mb-2 text-sm font-semibold">Top sellers in Kasba</h2>
                                <ul className="flex flex-col">
                                    {topSellers.map((s) => (
                                        <li key={s.id}>
                                            <Link href={`/sellers/${s.id}`} className="flex items-center gap-3 rounded-lg px-1 py-2 hover:bg-accent">
                                                <UserAvatar name={s.name} src={s.avatar} />
                                                <div className="min-w-0 flex-1">
                                                    <p className="truncate text-sm font-medium">{s.shop_name ?? s.name}</p>
                                                    <p className="flex items-center gap-1 text-xs text-muted-foreground">
                                                        <Star className="size-3 fill-amber-400 text-amber-400" /> {s.rating || '–'} · {s.listings} posts
                                                    </p>
                                                </div>
                                            </Link>
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        )}

                        <FacebookFeed pageUrl={facebookPageUrl} />
                    </div>
                </aside>
            </div>
        </>
    );
}
