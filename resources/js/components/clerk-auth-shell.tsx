import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { clerkPublishableKey } from '@/components/clerk-provider';

export default function ClerkAuthShell({ children }: { children: ReactNode }) {
    return (
        <div className="flex min-h-svh flex-col items-center justify-center gap-6 bg-background p-6">
            <Link href="/" className="text-xl font-semibold">
                Kasba Bazer
            </Link>
            {clerkPublishableKey ? (
                children
            ) : (
                <div className="max-w-sm rounded-lg border p-6 text-sm">
                    <p className="mb-2 font-medium">Clerk is not configured</p>
                    <p className="text-muted-foreground">
                        Set <code>VITE_CLERK_PUBLISHABLE_KEY</code> in{' '}
                        <code>.env</code>, then restart <code>npm run dev</code>.
                    </p>
                </div>
            )}
        </div>
    );
}
