const taka = new Intl.NumberFormat('en-BD', { maximumFractionDigits: 0 });

export function formatPrice(price: number): string {
    return price === 0 ? 'Free / Ask' : `৳ ${taka.format(price)}`;
}

export const conditionLabels: Record<string, string> = {
    new: 'New',
    used_like_new: 'Like new',
    used_good: 'Good',
    used_fair: 'Fair',
};

export const statusLabels: Record<string, string> = {
    draft: 'Draft',
    active: 'Available',
    reserved: 'Reserved',
    sold: 'Sold',
    hidden: 'Hidden',
    expired: 'Expired',
};

export function timeAgo(iso: string | null): string {
    if (!iso) {
        return '';
    }

    const seconds = Math.max(1, Math.round((Date.now() - new Date(iso).getTime()) / 1000));
    const units: [number, string][] = [
        [60 * 60 * 24 * 30, 'mo'],
        [60 * 60 * 24, 'd'],
        [60 * 60, 'h'],
        [60, 'm'],
    ];

    for (const [size, label] of units) {
        if (seconds >= size) {
            return `${Math.floor(seconds / size)}${label} ago`;
        }
    }

    return 'just now';
}

/** Soft background + text colour pairs used to give icons, chips and stats their own colour. */
export const tones = [
    'bg-primary/12 text-primary',
    'bg-sun/30 text-sun-foreground',
    'bg-coral/15 text-coral',
    'bg-sky/15 text-sky',
    'bg-violet-500/15 text-violet-600 dark:text-violet-300',
] as const;

export function tone(seed: number): string {
    return tones[Math.abs(seed) % tones.length];
}