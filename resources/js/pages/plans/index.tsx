import { Head, Link, usePage } from '@inertiajs/react';
import { BadgeCheck, Check, Clock, Rocket } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { formatPrice, timeAgo } from '@/lib/market';

type Plan = {
    id: number;
    slug: string;
    name: string;
    price: number;
    listing_limit: number | null;
    photo_limit: number;
    boosts_per_period: number;
    verified_badge: boolean;
};

type Props = {
    plans: Plan[];
    current: { slug: string; name: string; ends_at: string | null } | null;
    usage: { listings: number; listing_limit: number | null; boosts_left: number; boosts_total: number | null } | null;
    pending: { id: number; amount: number; transaction_id: string } | null;
    history: { id: number; plan: string; amount: number; status: string; transaction_id: string | null; ends_at: string | null; created_at: string }[];
};

const statusVariant: Record<string, 'default' | 'secondary' | 'destructive'> = {
    active: 'default',
    pending: 'secondary',
    expired: 'secondary',
    rejected: 'destructive',
};

function features(plan: Plan): string[] {
    return [
        plan.listing_limit === null ? 'Unlimited active listings' : `${plan.listing_limit} active listings`,
        `${plan.photo_limit} photos per listing`,
        plan.boosts_per_period > 0 ? `${plan.boosts_per_period} boosts every 30 days` : 'No boosts',
        plan.verified_badge ? 'Verified seller badge' : 'Standard seller badge',
    ];
}

export default function Plans({ plans, current, usage, pending, history }: Props) {
    const { auth } = usePage().props;

    return (
        <>
            <Head title="Seller plans" />

            <div>
                <header className="mb-8 text-center">
                    <h1 className="text-3xl font-bold tracking-tight">Plans for sellers</h1>
                    <p className="mx-auto mt-2 max-w-xl text-muted-foreground">
                        Start free. Upgrade when you sell more: list more products, get boosted to the top of the feed and earn a verified badge buyers trust.
                    </p>
                </header>

                {usage && current && (
                    <section className="mb-8 grid gap-3 rounded-2xl border bg-card p-5 sm:grid-cols-3" aria-label="Your plan">
                        <div>
                            <p className="text-xs text-muted-foreground">Current plan</p>
                            <p className="text-xl font-bold">{current.name}</p>
                            {current.ends_at && <p className="text-xs text-muted-foreground">Renews by {new Date(current.ends_at).toLocaleDateString()}</p>}
                        </div>
                        <div>
                            <p className="text-xs text-muted-foreground">Active listings</p>
                            <p className="text-xl font-bold">
                                {usage.listings} / {usage.listing_limit ?? '∞'}
                            </p>
                        </div>
                        <div>
                            <p className="text-xs text-muted-foreground">Boosts left</p>
                            <p className="flex items-center gap-1 text-xl font-bold">
                                <Rocket className="size-4" /> {usage.boosts_left}
                                {usage.boosts_total ? ` / ${usage.boosts_total}` : ''}
                            </p>
                        </div>
                    </section>
                )}

                {pending && (
                    <div className="mb-6 flex items-start gap-3 rounded-xl border border-amber-500/40 bg-amber-500/10 p-4 text-sm">
                        <Clock className="mt-0.5 size-4 shrink-0" />
                        <p>
                            Your payment of <strong>{formatPrice(pending.amount)}</strong> (TrxID <strong>{pending.transaction_id}</strong>) is waiting for confirmation. Your plan starts as soon as an admin confirms it.
                        </p>
                    </div>
                )}

                <ul className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {plans.map((plan) => {
                        const isCurrent = current?.slug === plan.slug;
                        const popular = plan.slug === 'pro';

                        return (
                            <li key={plan.id} className={cn('relative flex flex-col rounded-2xl border bg-card p-5', popular && 'border-primary shadow-md', isCurrent && 'ring-2 ring-primary')}>
                                {popular && <Badge className="absolute -top-2.5 left-5">Most popular</Badge>}
                                <h2 className="text-lg font-semibold">{plan.name}</h2>
                                <p className="mt-2">
                                    <span className="text-3xl font-bold">{plan.price === 0 ? 'Free' : formatPrice(plan.price)}</span>
                                    {plan.price > 0 && <span className="text-sm text-muted-foreground"> / month</span>}
                                </p>
                                <ul className="my-4 flex flex-1 flex-col gap-2 text-sm">
                                    {features(plan).map((feature) => (
                                        <li key={feature} className="flex items-start gap-2">
                                            {feature.startsWith('Verified') ? <BadgeCheck className="mt-0.5 size-4 shrink-0 text-primary" /> : <Check className="mt-0.5 size-4 shrink-0 text-primary" />}
                                            {feature}
                                        </li>
                                    ))}
                                </ul>
                                {isCurrent ? (
                                    <Button disabled variant="outline">Current plan</Button>
                                ) : plan.price === 0 ? (
                                    <Button disabled variant="ghost">Included</Button>
                                ) : auth.user ? (
                                    <Button asChild variant={popular ? 'default' : 'outline'} disabled={!!pending}>
                                        <Link href={`/plans/${plan.slug}/checkout`}>{current && current.slug !== 'free' ? 'Switch to' : 'Choose'} {plan.name}</Link>
                                    </Button>
                                ) : (
                                    <Button asChild variant="outline"><Link href="/sign-in">Sign in to subscribe</Link></Button>
                                )}
                            </li>
                        );
                    })}
                </ul>

                <p className="mt-6 text-center text-xs text-muted-foreground">
                    Pay with bKash or Nagad. Plans last 30 days and are confirmed manually, usually within a few hours.
                </p>

                {history.length > 0 && (
                    <section className="mt-10" aria-labelledby="history">
                        <h2 id="history" className="mb-3 text-lg font-semibold">Payment history</h2>
                        <ul className="divide-y rounded-xl border bg-card">
                            {history.map((h) => (
                                <li key={h.id} className="flex flex-wrap items-center gap-3 p-3 text-sm">
                                    <span className="min-w-0 flex-1 font-medium">{h.plan} · {formatPrice(h.amount)}</span>
                                    <span className="text-xs text-muted-foreground">TrxID {h.transaction_id} · {timeAgo(h.created_at)}</span>
                                    <Badge variant={statusVariant[h.status] ?? 'secondary'}>{h.status}</Badge>
                                </li>
                            ))}
                        </ul>
                    </section>
                )}
            </div>
        </>
    );
}
