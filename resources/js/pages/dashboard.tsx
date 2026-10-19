import { Head, Link, usePage } from '@inertiajs/react';
import { Bookmark, Eye, Heart, MessageCircle, MessageSquare, Plus, Rocket, Star, Store } from 'lucide-react';
import { ListingImage } from '@/components/market/listing-card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatPrice, statusLabels, timeAgo, tone } from '@/lib/market';

type Props = {
    isSeller: boolean;
    stats: {
        active_listings: number;
        views: number;
        likes: number;
        comments: number;
        saves: number;
        unread_messages: number;
        saved_by_me: number;
        average: number;
        count: number;
    };
    plan: { name: string; listing_limit: number | null; boosts_left: number; boosts_total: number; ends_at: string | null };
    recent: { slug: string; title: string; status: string; price: number; views: number; likes: number; comments: number; thumbnail: string | null; boosted: boolean }[];
    activity: { id: string; title: string; read: boolean; created_at: string }[];
};

function Stat({ icon: Icon, label, value, href, color = 0 }: { icon: typeof Eye; label: string; value: React.ReactNode; href?: string; color?: number }) {
    const body = (
        <div className="flex items-center gap-3 rounded-xl border bg-card p-4">
            <span className={`flex size-10 items-center justify-center rounded-full ${tone(color)}`}>
                <Icon className="size-5" />
            </span>
            <div className="min-w-0">
                <p className="text-xl font-bold">{value}</p>
                <p className="truncate text-xs text-muted-foreground">{label}</p>
            </div>
        </div>
    );

    return href ? <Link href={href} className="hover:opacity-90">{body}</Link> : body;
}

export default function Dashboard({ isSeller, stats, plan, recent, activity }: Props) {
    const { auth } = usePage().props;

    return (
        <>
            <Head title="Dashboard" />

            <div className="flex flex-col gap-6">
                <header className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-2xl font-bold">Hi, {auth.user.name.split(' ')[0]} 👋</h1>
                        <p className="text-sm text-muted-foreground">{isSeller ? 'Here is how your shop is doing.' : 'Your activity on Kasba Bazer.'}</p>
                    </div>
                    <div className="flex gap-2">
                        <Button asChild className="gap-1"><Link href="/sell"><Plus className="size-4" /> Post a product</Link></Button>
                        {!isSeller && <Button asChild variant="outline" className="gap-1"><Link href="/seller/apply"><Store className="size-4" /> Become a seller</Link></Button>}
                    </div>
                </header>

                {isSeller ? (
                    <>
                        <section aria-label="Shop stats" className="grid grid-cols-2 gap-3 md:grid-cols-4">
                            <Stat color={1} icon={Eye} label="Total views" value={stats.views.toLocaleString()} />
                            <Stat color={2} icon={Heart} label="Likes" value={stats.likes.toLocaleString()} />
                            <Stat color={3} icon={MessageSquare} label="Comments received" value={stats.comments.toLocaleString()} />
                            <Stat color={4} icon={Bookmark} label="Saved by buyers" value={stats.saves.toLocaleString()} />
                            <Stat color={5} icon={MessageCircle} label="Unread messages" value={stats.unread_messages} href="/inbox" />
                            <Stat color={6} icon={Star} label={`Rating (${stats.count} reviews)`} value={stats.count > 0 ? stats.average : '–'} href={`/sellers/${auth.user.id}`} />
                            <Stat color={7} icon={Store} label="Active listings" value={`${stats.active_listings} / ${plan.listing_limit ?? '∞'}`} href="/my/listings" />
                            <Stat color={8} icon={Rocket} label="Boosts left" value={`${plan.boosts_left}${plan.boosts_total ? ` / ${plan.boosts_total}` : ''}`} href="/plans" />
                        </section>

                        <section className="flex flex-wrap items-center justify-between gap-3 rounded-xl border bg-card p-4">
                            <div>
                                <p className="text-sm text-muted-foreground">Your plan</p>
                                <p className="text-lg font-bold">
                                    {plan.name}
                                    {plan.ends_at && <span className="ml-2 text-xs font-normal text-muted-foreground">until {new Date(plan.ends_at).toLocaleDateString()}</span>}
                                </p>
                            </div>
                            <Button asChild variant="outline"><Link href="/plans">{plan.name === 'Free' ? 'Upgrade for more listings & boosts' : 'Manage plan'}</Link></Button>
                        </section>
                    </>
                ) : (
                    <section aria-label="Your activity" className="grid grid-cols-2 gap-3 md:grid-cols-3">
                        <Stat color={9} icon={Bookmark} label="Saved products" value={stats.saved_by_me} href="/saved" />
                        <Stat color={10} icon={MessageCircle} label="Unread messages" value={stats.unread_messages} href="/inbox" />
                        <Stat color={11} icon={Store} label="Start selling" value="Free" href="/seller/apply" />
                    </section>
                )}

                <div className="grid gap-6 lg:grid-cols-[1fr_320px]">
                    {isSeller && (
                        <section aria-labelledby="recent-heading">
                            <div className="mb-3 flex items-center justify-between">
                                <h2 id="recent-heading" className="text-lg font-semibold">Recent listings</h2>
                                <Link href="/my/listings" className="text-sm text-primary hover:underline">View all</Link>
                            </div>
                            {recent.length === 0 ? (
                                <p className="rounded-xl border border-dashed p-8 text-center text-sm text-muted-foreground">No listings yet. Post your first product!</p>
                            ) : (
                                <ul className="flex flex-col gap-2">
                                    {recent.map((l) => (
                                        <li key={l.slug}>
                                            <Link href={`/listings/${l.slug}`} className="flex gap-3 rounded-xl border bg-card p-3 hover:bg-accent">
                                                <div className="size-14 shrink-0 overflow-hidden rounded-lg bg-muted">
                                                    <ListingImage src={l.thumbnail} alt="" className="size-full" />
                                                </div>
                                                <div className="min-w-0 flex-1">
                                                    <p className="flex items-center gap-2 text-sm font-medium">
                                                        <span className="truncate">{l.title}</span>
                                                        {l.boosted && <Badge variant="outline">Boosted</Badge>}
                                                    </p>
                                                    <p className="text-xs text-muted-foreground">
                                                        {formatPrice(l.price)} · <Eye className="inline size-3" /> {l.views} · <Heart className="inline size-3" /> {l.likes} · <MessageSquare className="inline size-3" /> {l.comments}
                                                    </p>
                                                </div>
                                                <Badge variant={l.status === 'active' ? 'default' : 'secondary'}>{statusLabels[l.status]}</Badge>
                                            </Link>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </section>
                    )}

                    <section aria-labelledby="activity-heading" className={isSeller ? '' : 'lg:col-span-2'}>
                        <div className="mb-3 flex items-center justify-between">
                            <h2 id="activity-heading" className="text-lg font-semibold">Recent activity</h2>
                            <Link href="/notifications" className="text-sm text-primary hover:underline">View all</Link>
                        </div>
                        {activity.length === 0 ? (
                            <p className="rounded-xl border border-dashed p-8 text-center text-sm text-muted-foreground">Nothing yet. Comments, likes and messages show up here.</p>
                        ) : (
                            <ul className="divide-y rounded-xl border bg-card">
                                {activity.map((a) => (
                                    <li key={a.id}>
                                        <Link href={`/notifications/${a.id}/open`} className="flex items-start gap-2 p-3 text-sm hover:bg-accent">
                                            {!a.read && <span className="mt-1.5 size-2 shrink-0 rounded-full bg-primary" aria-label="Unread" />}
                                            <span className="min-w-0 flex-1">
                                                <span className={a.read ? '' : 'font-semibold'}>{a.title}</span>
                                                <span className="block text-xs text-muted-foreground">{timeAgo(a.created_at)}</span>
                                            </span>
                                        </Link>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </section>
                </div>
            </div>
        </>
    );
}