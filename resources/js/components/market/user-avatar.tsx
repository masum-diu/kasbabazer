import { useState } from 'react';
import { useInitials } from '@/hooks/use-initials';
import { cn } from '@/lib/utils';

export default function UserAvatar({
    name,
    src,
    className,
}: {
    name: string;
    src?: string | null;
    className?: string;
}) {
    const initials = useInitials()(name);
    const [failed, setFailed] = useState(false);

    // Fall back to initials when there is no picture or it cannot be loaded (e.g. a blocked/expired URL).
    if (src && !failed) {
        return (
            <img
                src={src}
                alt={name}
                loading="lazy"
                referrerPolicy="no-referrer"
                onError={() => setFailed(true)}
                className={cn('size-9 shrink-0 rounded-full object-cover', className)}
            />
        );
    }

    return (
        <span
            aria-label={name}
            className={cn(
                'flex size-9 shrink-0 items-center justify-center rounded-full bg-primary/10 text-xs font-semibold text-primary',
                className,
            )}
        >
            {initials}
        </span>
    );
}