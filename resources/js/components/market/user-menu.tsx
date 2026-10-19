import { Link, usePage } from '@inertiajs/react';
import { Bookmark, LayoutDashboard, LogOut, Package, Rocket, Settings, Shield, Store } from 'lucide-react';
import { useLogout } from '@/components/clerk-sync';
import UserAvatar from '@/components/market/user-avatar';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
export default function UserMenu({ side = 'bottom', trigger }: { side?: 'top' | 'bottom'; trigger?: React.ReactNode }) {
    const { auth } = usePage().props;
    const logout = useLogout();
    const user = auth.user;

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                {trigger ?? (
                    <button type="button" className="rounded-full" aria-label="Account menu">
                        <UserAvatar name={user.name} src={user.avatar} />
                    </button>
                )}
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" side={side} className="w-56">
                <div className="px-2 py-1.5 text-sm">
                    <p className="font-medium">{user.name}</p>
                    <p className="truncate text-xs text-muted-foreground">{user.email}</p>
                </div>
                <DropdownMenuSeparator />
                <DropdownMenuItem asChild>
                    <Link href="/dashboard"><LayoutDashboard className="size-4" /> Dashboard</Link>
                </DropdownMenuItem>
                <DropdownMenuItem asChild>
                    <Link href="/my/listings"><Package className="size-4" /> My listings</Link>
                </DropdownMenuItem>
                <DropdownMenuItem asChild>
                    <Link href="/saved"><Bookmark className="size-4" /> Saved</Link>
                </DropdownMenuItem>
                <DropdownMenuItem asChild>
                    <Link href={auth.is_seller ? `/sellers/${user.id}` : '/seller/apply'}><Store className="size-4" /> {auth.is_seller ? 'My shop' : 'Become a seller'}</Link>
                </DropdownMenuItem>
                <DropdownMenuItem asChild>
                    <Link href="/plans"><Rocket className="size-4" /> Plans & boosts</Link>
                </DropdownMenuItem>
                {user.is_admin && (
                    <DropdownMenuItem asChild>
                        <Link href="/admin"><Shield className="size-4" /> Admin</Link>
                    </DropdownMenuItem>
                )}
                <DropdownMenuItem asChild>
                    <Link href="/settings/profile"><Settings className="size-4" /> Settings</Link>
                </DropdownMenuItem>
                <DropdownMenuSeparator />
                <DropdownMenuItem onSelect={logout}>
                    <LogOut className="size-4" /> Log out
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
