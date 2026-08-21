// sw.js - Service Worker for Family Debt Manager PWA

const CACHE_NAME = 'family-debt-v2';
const OFFLINE_URL = '/offline.php';

// Files to cache on install
const urlsToCache = [
    '/',
    '/index.php',
    '/login.php',
    '/register.php',
    '/add-debt.php',
    '/add-payment.php',
    '/family-dashboard.php',
    '/logout.php',
    '/delete-debt.php',
    '/style.css',
    '/manifest.json',
    'https://cdn.tailwindcss.com'
];

// ===== INSTALL: Cache files =====
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => {
                console.log('[Service Worker] Caching app files');
                return cache.addAll(urlsToCache).catch(error => {
                    console.error('[Service Worker] Failed to cache:', error);
                });
            })
            .then(() => {
                console.log('[Service Worker] Skip waiting');
                return self.skipWaiting();
            })
    );
});

// ===== ACTIVATE: Clean up old caches =====
self.addEventListener('activate', event => {
    const cacheWhitelist = [CACHE_NAME];
    
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.map(cacheName => {
                    if (cacheWhitelist.indexOf(cacheName) === -1) {
                        console.log('[Service Worker] Deleting old cache:', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        })
        .then(() => {
            console.log('[Service Worker] Claiming clients');
            return self.clients.claim();
        })
    );
});

// ===== FETCH: Network first, fallback to cache =====
self.addEventListener('fetch', event => {
    const request = event.request;
    const url = new URL(request.url);
    
    // Skip cross-origin requests (except Tailwind CDN)
    if (url.origin !== location.origin && !url.href.includes('cdn.tailwindcss.com')) {
        return;
    }
    
    // Skip POST requests (we don't want to cache form submissions)
    if (request.method === 'POST') {
        return fetch(request);
    }
    
    // Skip PHP requests that need fresh data (like dashboard)
    if (request.method === 'GET' && 
        (url.pathname === '/index.php' || 
         url.pathname === '/family-dashboard.php' ||
         url.pathname === '/')) {
        // Network first, fallback to cache
        event.respondWith(
            fetch(request)
                .then(response => {
                    // Cache the fresh response
                    const responseClone = response.clone();
                    caches.open(CACHE_NAME).then(cache => {
                        cache.put(request, responseClone);
                    });
                    return response;
                })
                .catch(() => {
                    // If network fails, try cache
                    return caches.match(request).then(cachedResponse => {
                        if (cachedResponse) {
                            return cachedResponse;
                        }
                        // If not in cache, show offline page
                        return caches.match('/offline.php');
                    });
                })
        );
        return;
    }
    
    // For all other GET requests: Cache first, fallback to network
    event.respondWith(
        caches.match(request)
            .then(cachedResponse => {
                if (cachedResponse) {
                    // Return cached version
                    return cachedResponse;
                }
                
                // Not in cache, fetch from network
                return fetch(request)
                    .then(response => {
                        // Cache the response for future use
                        if (response && response.status === 200) {
                            const responseClone = response.clone();
                            caches.open(CACHE_NAME).then(cache => {
                                cache.put(request, responseClone);
                            });
                        }
                        return response;
                    })
                    .catch(() => {
                        // If network fails, show offline page for HTML requests
                        if (request.headers.get('accept').includes('text/html')) {
                            return caches.match('/offline.php');
                        }
                        // For other resources, just return error
                        return new Response('Offline', {
                            status: 503,
                            statusText: 'Service Unavailable'
                        });
                    });
            })
    );
});

// ===== BACKGROUND SYNC (for offline payments) =====
// This allows queuing payments when offline and syncing when online

// Listen for sync events
self.addEventListener('sync', event => {
    if (event.tag === 'sync-payments') {
        console.log('[Service Worker] Syncing pending payments');
        event.waitUntil(syncPendingPayments());
    }
});

// Function to sync pending payments
function syncPendingPayments() {
    return new Promise((resolve, reject) => {
        // We'll use IndexedDB to store pending payments
        // This is a placeholder - we can implement full offline sync later
        console.log('[Service Worker] Syncing payments...');
        resolve();
    });
}

// ===== PUSH NOTIFICATIONS (optional) =====
self.addEventListener('push', event => {
    const data = event.data ? event.data.json() : {};
    const title = data.title || 'Family Debt Manager';
    const options = {
        body: data.body || 'Time to check your debts!',
        icon: '/icons/icon-192x192.png',
        badge: '/icons/icon-72x72.png',
        vibrate: [200, 100, 200],
        data: {
            url: data.url || '/index.php'
        }
    };
    
    event.waitUntil(
        self.registration.showNotification(title, options)
    );
});

// Handle notification click
self.addEventListener('notificationclick', event => {
    event.notification.close();
    
    event.waitUntil(
        clients.matchAll({ type: 'window' })
            .then(clientsArr => {
                const url = event.notification.data.url || '/index.php';
                
                // Check if there's already a window open
                for (const client of clientsArr) {
                    if (client.url === url && 'focus' in client) {
                        return client.focus();
                    }
                }
                
                // If not, open a new window
                if (clients.openWindow) {
                    return clients.openWindow(url);
                }
            })
    );
});

// ===== MESSAGE HANDLING =====
self.addEventListener('message', event => {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});

console.log('[Service Worker] Service Worker loaded successfully!');