const CACHE_NAME = 'fibernet-pwa-cache-v1';
const OFFLINE_URL = '/offline';

const urlsToCache = [
    OFFLINE_URL,
    '/logo-fibernet.png',
    '/fibernet.png',
    '/manifest.json'
];

self.addEventListener('install', (event) => {
    self.skipWaiting();
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => cache.addAll(urlsToCache))
    );
});

self.addEventListener('fetch', (event) => {
    if (event.request.method !== 'GET') {
        return;
    }

    const url = new URL(event.request.url);
    if (url.origin !== self.location.origin) {
        return;
    }

    // Never cache authenticated API streams, XLSX exports, or Next RSC payloads
    if (
        url.pathname.startsWith('/api/') ||
        url.pathname.startsWith('/_next/data/') ||
        event.request.headers.get('rsc') === '1'
    ) {
        return;
    }

    const acceptHeader = event.request.headers.get('accept') || '';
    if (event.request.mode === 'navigate' || acceptHeader.includes('text/html')) {
        event.respondWith(
            fetch(event.request).catch(() => caches.match(OFFLINE_URL))
        );
        return;
    }

    event.respondWith(
        caches.match(event.request).then((response) => response || fetch(event.request))
    );
});

self.addEventListener('activate', (event) => {
    const cacheAllowlist = [CACHE_NAME];
    event.waitUntil(
        caches.keys().then((cacheNames) =>
            Promise.all(
                cacheNames.map((cacheName) => {
                    if (!cacheAllowlist.includes(cacheName)) {
                        return caches.delete(cacheName);
                    }
                    return undefined;
                })
            )
        )
    );
});
