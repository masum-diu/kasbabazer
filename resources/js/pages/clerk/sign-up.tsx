import { SignUp } from '@clerk/react';
import { Head } from '@inertiajs/react';
import ClerkAuthShell from '@/components/clerk-auth-shell';

export default function ClerkSignUp() {
    return (
        <ClerkAuthShell>
            <Head title="Sign up" />
            <SignUp routing="hash" signInUrl="/sign-in" />
        </ClerkAuthShell>
    );
}
