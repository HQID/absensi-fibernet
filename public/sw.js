const CACHE_NAME = 'fibernet-pwa-cache-v1';
const OFFLINE_URL = '/offline';

const urlsToCache = [
    '/',
    OFFLINE_URL,
    '/logo-fibernet.png',
    '/manifest.json'
];

self.addEventListener('install', (event) => {
    self.skipWaiting();
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => {
                return cache.addAll(urlsToCache);
            })
    );
});

self.addEventListener('fetch', (event) => {
    // Ignore non-GET requests (like POST for login)
    if (event.request.method !== 'GET') {
        return;
    }

    if (event.request.mode === 'navigate' || event.request.headers.get('accept').includes('text/html')) {
        event.respondWith(
            fetch(event.request).catch(error => {
                // Return the offline page
                return caches.match(OFFLINE_URL);
            })
        );
    } else {
        // For non-HTML requests, use cache-first strategy
        event.respondWith(
            caches.match(event.request).then((response) => {
                return response || fetch(event.request);
            })
        );
    }
});

self.addEventListener('activate', (event) => {
    const cacheAllowlist = [CACHE_NAME];
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cacheName) => {
                    if (cacheAllowlist.indexOf(cacheName) === -1) {
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
});