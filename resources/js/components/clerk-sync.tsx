import { useAuth, useClerk } from '@clerk/react';
import { router, usePage } from '@inertiajs/react';
import { useEffect, useRef } from 'react';
import { clerkPublishableKey } from '@/components/clerk-provider';

/**
 * When the visitor is signed in with Clerk (e.g. Google) but has no Laravel
 * session yet, exchange the Clerk token for one. Renders nothing.
 */
function ClerkSyncInner() {
    const { isLoaded, isSignedIn, getToken } = useAuth();
    const { auth } = usePage().props;
    const attempted = useRef(false);

    useEffect(() => {
        if (!isLoaded || !isSignedIn || auth.user || attempted.current) {
            return;
        }

        attempted.current = true;

        void getToken().then((token) => {
            if (token) {
                router.post('/auth/clerk', { token }, { preserveScroll: true });
            }
        });
    }, [isLoaded, isSignedIn, auth.user, getToken]);

    return null;
}

export default function ClerkSync() {
    return clerkPublishableKey ? <ClerkSyncInner /> : null;
}

/** Log out of both Clerk (if used) and the Laravel session. */
function useClerkLogout(): () => void {
    const clerk = useClerk();

    return () => {
        void clerk.signOut().finally(() => router.post('/logout'));
    };
}

function useLaravelLogout(): () => void {
    return () => router.post('/logout');
}

// The key is fixed at build time, so the chosen hook never changes between renders.
export const useLogout = clerkPublishableKey ? useClerkLogout : useLaravelLogout;
