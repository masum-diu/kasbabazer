import { SignIn } from '@clerk/react';
import { Head } from '@inertiajs/react';
import ClerkAuthShell from '@/components/clerk-auth-shell';

export default function ClerkSignIn() {
    return (
        <ClerkAuthShell>
            <Head title="Sign in" />
            <SignIn routing="hash" signUpUrl="/sign-up" />
        </ClerkAuthShell>
    );
}
