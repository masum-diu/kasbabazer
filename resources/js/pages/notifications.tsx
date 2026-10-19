import { Head, InfiniteScroll, Link, router } from '@inertiajs/react';
import { Bell, CheckCheck, Heart, MessageCircle, MessageSquare, Star, Store } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { timeAgo } from '@/lib/market';

type Item = {
    id: string;
    kind: string;
    title: string;
    body: string | null;
    url: string;
    read: boolean;
    created_at: string;
};

const icons: Record<string, typeof Bell> = {
    comment: MessageSquare,
    reply: MessageSquare,
    reaction: Heart,
    message: MessageCircle,
    review: Star,
    seller: Store,
};

export default function Notifications({ notifications }: { notifications: { data: Item[] } }) {
    const markAll = () => router.post('/notifications/read-all', {}, { preserveScroll: true });

    return (
        <>
            <Head title="Notifications" />
            <div>
                <div className="mb-4 flex items-center justify-between">
                    <h1 className="text-2xl font-bold">Notifications</h1>
                    {notifications.data.some((n) => !n.read) && (
                        <Button variant="outline" size="sm" onClick={markAll} className="gap-1">
                            <CheckCheck className="size-4" /> Mark all read
                        </Button>
                    )}
                </div>

                {notifications.data.length === 0 ? (
                    <div className="flex flex-col items-center rounded-xl border border-dashed p-12 text-center">
                        <Bell className="mb-3 size-8 text-muted-foreground" />
                        <p className="font-medium">You are all caught up</p>
                        <p className="mt-1 text-sm text-muted-foreground">Comments, replies, reactions and messages show up here.</p>
                    </div>
                ) : (
                    <InfiniteScroll data="notifications" className="flex flex-col divide-y rounded-xl border">
                        {notifications.data.map((n) => {
                            const Icon = icons[n.kind] ?? Bell;

                            return (
                                <Link key={n.id} href={`/notifications/${n.id}/open`} className={cn('flex gap-3 p-4 hover:bg-accent', !n.read && 'bg-primary/5')}>
                                    <Icon className="mt-0.5 size-5 shrink-0 text-primary" />
                                    <div className="min-w-0 flex-1">
                                        <p className={cn('text-sm', !n.read && 'font-semibold')}>{n.title}</p>
                                        {n.body && <p className="truncate text-sm text-muted-foreground">{n.body}</p>}
                                        <p className="text-xs text-muted-foreground">{timeAgo(n.created_at)}</p>
                                    </div>
                                    {!n.read && <span className="mt-1 size-2 shrink-0 rounded-full bg-primary" aria-label="Unread" />}
                                </Link>
                            );
                        })}
                    </InfiniteScroll>
                )}
            </div>
        </>
    );
}