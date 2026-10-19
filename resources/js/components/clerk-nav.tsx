import { Show, UserButton } from '@clerk/react';
import { Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { clerkPublishableKey } from '@/components/clerk-provider';

/** Sign-in / sign-up buttons, or the Clerk user menu when signed in. */
export default function ClerkNav() {
    if (!clerkPublishableKey) {
        return (
            <Button asChild size="sm">
                <Link href="/sign-in">Sign in</Link>
            </Button>
        );
    }

    return (
        <>
            <Show when="signed-out">
                <Button asChild variant="ghost" size="sm">
                    <Link href="/sign-in">Sign in</Link>
                </Button>
                <Button asChild size="sm">
                    <Link href="/sign-up">Sign up</Link>
                </Button>
            </Show>
            <Show when="signed-in">
                <UserButton />
            </Show>
        </>
    );
}
