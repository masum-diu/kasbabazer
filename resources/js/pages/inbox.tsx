import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, MessageCircle, Send } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { ListingImage } from '@/components/market/listing-card';
import UserAvatar from '@/components/market/user-avatar';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { formatPrice, timeAgo } from '@/lib/market';

type Person = { id: number; name: string; avatar: string | null };

type Props = {
    conversations: {
        id: number;
        other: Person | null;
        listing: { slug: string; title: string } | null;
        last_message: string | null;
        last_at: string | null;
        unread: number;
    }[];
    active: {
        id: number;
        other: Person | null;
        listing: { slug: string; title: string; price: number; thumbnail: string | null } | null;
        messages: { id: number; message: string; mine: boolean; read: boolean; created_at: string }[];
    } | null;
};

export default function Inbox({ conversations, active }: Props) {
    const [text, setText] = useState('');
    const [busy, setBusy] = useState(false);
    const bottom = useRef<HTMLDivElement>(null);

    useEffect(() => {
        bottom.current?.scrollIntoView({ block: 'end' });
    }, [active?.messages.length, active?.id]);

    // Light polling for new messages while a conversation is open.
    useEffect(() => {
        if (!active) {
            return;
        }

        const timer = setInterval(() => router.reload({ only: ['active', 'conversations'] }), 5000);

        return () => clearInterval(timer);
    }, [active?.id]);

    const send = (e: React.FormEvent) => {
        e.preventDefault();

        if (!active || text.trim() === '') {
            return;
        }

        router.post(`/conversations/${active.id}/messages`, { message: text }, {
            preserveScroll: true,
            onStart: () => setBusy(true),
            onFinish: () => setBusy(false),
            onSuccess: () => setText(''),
        });
    };

    return (
        <>
            <Head title="Messages" />
            <div className="grid min-h-[70vh] overflow-hidden rounded-2xl border md:grid-cols-[320px_1fr]">
                <aside className={cn('border-r', active && 'hidden md:block')}>
                    <h1 className="border-b p-4 text-lg font-bold">Messages</h1>
                    {conversations.length === 0 ? (
                        <div className="flex flex-col items-center p-8 text-center text-sm text-muted-foreground">
                            <MessageCircle className="mb-2 size-6" />
                            No conversations yet. Open a product and tap “Message seller”.
                        </div>
                    ) : (
                        <ul className="max-h-[70vh] overflow-y-auto">
                            {conversations.map((c) => (
                                <li key={c.id}>
                                    <Link href={`/inbox/${c.id}`} className={cn('flex gap-3 border-b p-3 hover:bg-accent', active?.id === c.id && 'bg-accent')}>
                                        <UserAvatar name={c.other?.name ?? '?'} src={c.other?.avatar} />
                                        <div className="min-w-0 flex-1">
                                            <p className="flex justify-between gap-2 text-sm font-medium">
                                                <span className="truncate">{c.other?.name}</span>
                                                <span className="shrink-0 text-xs font-normal text-muted-foreground">{timeAgo(c.last_at)}</span>
                                            </p>
                                            {c.listing && <p className="truncate text-xs text-muted-foreground">{c.listing.title}</p>}
                                            <p className={cn('truncate text-sm', c.unread > 0 ? 'font-semibold' : 'text-muted-foreground')}>{c.last_message}</p>
                                        </div>
                                        {c.unread > 0 && <span className="mt-1 flex size-5 shrink-0 items-center justify-center rounded-full bg-primary text-[10px] text-primary-foreground">{c.unread}</span>}
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    )}
                </aside>

                <section className={cn('flex min-h-[70vh] flex-col', !active && 'hidden md:flex')}>
                    {active ? (
                        <>
                            <header className="flex items-center gap-3 border-b p-3">
                                <Link href="/inbox" className="md:hidden" aria-label="Back to conversations"><ArrowLeft className="size-5" /></Link>
                                <UserAvatar name={active.other?.name ?? '?'} src={active.other?.avatar} />
                                <p className="font-medium">{active.other?.name}</p>
                            </header>

                            {active.listing && (
                                <Link href={`/listings/${active.listing.slug}`} className="flex items-center gap-3 border-b bg-muted/40 p-3 hover:bg-muted">
                                    <ListingImage src={active.listing.thumbnail} alt="" className="size-12 rounded-md" />
                                    <div className="min-w-0">
                                        <p className="truncate text-sm font-medium">{active.listing.title}</p>
                                        <p className="text-xs text-muted-foreground">{formatPrice(active.listing.price)}</p>
                                    </div>
                                </Link>
                            )}

                            <ul className="flex flex-1 flex-col gap-2 overflow-y-auto p-4" aria-live="polite">
                                {active.messages.map((m) => (
                                    <li key={m.id} className={cn('flex flex-col', m.mine ? 'items-end' : 'items-start')}>
                                        <p className={cn('max-w-[80%] rounded-2xl px-3 py-2 text-sm break-words whitespace-pre-wrap', m.mine ? 'bg-primary text-primary-foreground' : 'bg-muted')}>
                                            {m.message}
                                        </p>
                                        <span className="px-1 text-[10px] text-muted-foreground">{timeAgo(m.created_at)}{m.mine && m.read ? ' · Seen' : ''}</span>
                                    </li>
                                ))}
                                <div ref={bottom} />
                            </ul>

                            <form onSubmit={send} className="flex gap-2 border-t p-3">
                                <input
                                    value={text}
                                    onChange={(e) => setText(e.target.value)}
                                    placeholder="Write a message…"
                                    aria-label="Message"
                                    maxLength={2000}
                                    className="h-9 flex-1 rounded-md border bg-transparent px-3 text-sm outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                />
                                <Button type="submit" size="icon" disabled={busy || text.trim() === ''} aria-label="Send"><Send className="size-4" /></Button>
                            </form>
                        </>
                    ) : (
                        <div className="m-auto text-sm text-muted-foreground">Select a conversation</div>
                    )}
                </section>
            </div>
        </>
    );
}
