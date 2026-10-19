import { useEffect, useState } from 'react';

type InstallEvent = Event & { prompt: () => Promise<void>; userChoice: Promise<{ outcome: 'accepted' | 'dismissed' }> };

/** Registers the service worker (production builds only, so dev HMR is never cached). */
export function registerServiceWorker(): void {
    if (!import.meta.env.PROD || !('serviceWorker' in navigator)) {
        return;
    }

    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js', { scope: '/' }).catch(() => {
            // Registration can fail (e.g. private mode); the app works fine without it.
        });
    });
}

// The browser fires this once, often before React mounts, so capture it at module load.
let deferred: InstallEvent | null = null;
const listeners = new Set<() => void>();

if (typeof window !== 'undefined') {
    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferred = e as InstallEvent;
        listeners.forEach((fn) => fn());
    });
    window.addEventListener('appinstalled', () => {
        deferred = null;
        listeners.forEach((fn) => fn());
    });
}

const isStandalone = () =>
    typeof window !== 'undefined' &&
    (window.matchMedia('(display-mode: standalone)').matches || (navigator as Navigator & { standalone?: boolean }).standalone === true);

/** Install state: `installed` when already running as an app, `canPrompt` when the browser can show its own install dialog. */
export function useInstallPrompt(): { installed: boolean; canPrompt: boolean; isIos: boolean; install: () => Promise<void> } {
    const [, rerender] = useState(0);

    useEffect(() => {
        const fn = () => rerender((n) => n + 1);
        listeners.add(fn);

        return () => {
            listeners.delete(fn);
        };
    }, []);

    return {
        installed: isStandalone(),
        canPrompt: deferred !== null,
        isIos: /iphone|ipad|ipod/i.test(navigator.userAgent),
        install: async () => {
            if (!deferred) {
                return;
            }

            const event = deferred;
            deferred = null;
            await event.prompt();
            await event.userChoice;
            listeners.forEach((fn) => fn());
        },
    };
}
