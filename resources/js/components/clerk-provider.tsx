import { ClerkProvider as BaseClerkProvider } from '@clerk/react';
import type { ReactNode } from 'react';

export const clerkPublishableKey = import.meta.env
    .VITE_CLERK_PUBLISHABLE_KEY as string | undefined;

/**
 * Wraps the app in Clerk when a publishable key is configured.
 * Without a key the app still renders (Clerk pages show a setup notice).
 */
export default function ClerkProvider({ children }: { children: ReactNode }) {
    if (!clerkPublishableKey) {
        return <>{children}</>;
    }

    return (
        <BaseClerkProvider
            publishableKey={clerkPublishableKey}
            afterSignOutUrl="/"
            signInUrl="/sign-in"
            signUpUrl="/sign-up"
            signInFallbackRedirectUrl="/"
            signUpFallbackRedirectUrl="/"
        >
            {children}
        </BaseClerkProvider>
    );
}
