import { Link, usePage } from '@inertiajs/react';
import { Bookmark, Home, LogIn, MessageCircle, Plus } from 'lucide-react';
import UserMenu from '@/components/market/user-menu';
import UserAvatar from '@/components/market/user-avatar';
import { cn } from '@/lib/utils';

type Tab = { href: string; label: string; icon: typeof Home; badge?: number; match: (path: string) => boolean };

/**
 * Mobile tab bar (hidden from md up): Home, Saved, Sell, Messages, Me.
 * Respects the iPhone home-indicator safe area.
 */
export default function BottomNav() {
    const { url, props } = usePage();
    const path = url.split('?')[0];
    const user = props.auth.user;

    const tabs: Tab[] = [
        { href: '/', label: 'Home', icon: Home, match: (p) => p === '/' },
        { href: '/saved', label: 'Saved', icon: Bookmark, match: (p) => p.startsWith('/saved') },
    ];
    const right: Tab[] = [
        { href: '/inbox', label: 'Messages', icon: MessageCircle, badge: user ? props.auth.unread_messages : 0, match: (p) => p.startsWith('/inbox') },
    ];

    const item = (tab: Tab) => {
        const active = tab.match(path);

        return (
            <Link
                key={tab.href}
                href={tab.href}
                aria-current={active ? 'page' : undefined}
                className={cn('relative flex flex-1 flex-col items-center justify-center gap-0.5 py-2 text-[11px]', active ? 'font-semibold text-primary' : 'text-muted-foreground')}
            >
                <tab.icon className={cn('size-5', active && 'fill-primary/15')} />
                {tab.label}
                {tab.badge ? (
                    <span className="absolute top-1 left-1/2 ml-2 flex size-4 items-center justify-center rounded-full bg-destructive text-[10px] text-white">
                        {tab.badge > 9 ? '9+' : tab.badge}
                    </span>
                ) : null}
            </Link>
        );
    };

    return (
        <nav
            aria-label="Main"
            className="fixed inset-x-0 bottom-0 z-40 flex items-stretch border-t bg-card pb-[env(safe-area-inset-bottom)] shadow-[0_-2px_8px_rgba(0,0,0,0.06)] md:hidden"
        >
            {tabs.map(item)}

            <Link href="/sell" aria-label="Sell a product" className="flex flex-1 items-center justify-center">
                <span className="-mt-5 flex size-12 items-center justify-center rounded-full bg-brand shadow-lg shadow-primary/30 ring-4 ring-background">
                    <Plus className="size-6" />
                </span>
            </Link>

            {right.map(item)}

            {user ? (
                <div className="flex flex-1 items-stretch">
                    <UserMenu
                        side="top"
                        trigger={
                            <button type="button" className="flex flex-1 flex-col items-center justify-center gap-0.5 py-2 text-[11px] text-muted-foreground">
                                <UserAvatar name={user.name} src={user.avatar} className="size-5 text-[8px]" />
                                Me
                            </button>
                        }
                    />
                </div>
            ) : (
                <Link href="/sign-in" className="flex flex-1 flex-col items-center justify-center gap-0.5 py-2 text-[11px] text-muted-foreground">
                    <LogIn className="size-5" />
                    Sign in
                </Link>
            )}
        </nav>
    );
}
