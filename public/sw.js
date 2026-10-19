/* Kasba Bazer service worker.
 * - Static build assets, icons and dummy images: cache-first (they are content-hashed or immutable).
 * - Page navigations: network-first, falling back to the offline page.
 * - Everything else (Inertia JSON, POSTs, auth, API) always goes to the network and is never cached,
 *   so private data is not stored in the cache.
 */
const VERSION = 'kb-v1';
const STATIC_CACHE = `${VERSION}-static`;
const PRECACHE = ['/offline.html', '/icons/icon-192.png', '/icons/icon-512.png', '/manifest.webmanifest'];

self.addEventListener('install', (event) => {
    event.waitUntil(caches.open(STATIC_CACHE).then((cache) => cache.addAll(PRECACHE)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches
            .keys()
            .then((keys) => Promise.all(keys.filter((k) => !k.startsWith(VERSION)).map((k) => caches.delete(k))))
            .then(() => self.clients.claim()),
    );
});

const isStatic = (url) =>
    url.pathname.startsWith('/build/assets/') || url.pathname.startsWith('/icons/') || url.pathname.startsWith('/dummy/') || url.pathname.startsWith('/storage/');

self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    if (request.method !== 'GET' || url.origin !== self.location.origin) {
        return;
    }

    if (isStatic(url)) {
        event.respondWith(
            caches.match(request).then(
                (hit) =>
                    hit ||
                    fetch(request).then((response) => {
                        if (response.ok) {
                            const copy = response.clone();
                            caches.open(STATIC_CACHE).then((cache) => cache.put(request, copy));
                        }

                        return response;
                    }),
            ),
        );

        return;
    }

    // Full page loads only (Inertia XHR visits carry the X-Inertia header and are left alone).
    if (request.mode === 'navigate') {
        event.respondWith(fetch(request).catch(() => caches.match('/offline.html')));
    }
});