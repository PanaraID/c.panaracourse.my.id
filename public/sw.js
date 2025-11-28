const CACHE_NAME = 'panara-course-v2';
const urlsToCache = [
  '/',
  '/manifest.json',
  '/favicon.ico',
  '/favicon.png',
  '/favicon.svg',
  '/logo.png',
  '/apple-touch-icon.png',
  '/favicon-16x16.png',
  '/favicon-32x32.png',
  '/icons/icon-192x192.png',
  '/icons/icon-512x512.png',
  '/icons/icon-maskable-192x192.png',
  '/icons/icon-maskable-512x512.png',
  // Assets yang akan di-cache saat runtime
];

// Install Service Worker
self.addEventListener('install', event => {
  console.log('Service Worker installing...');
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        console.log('Opened cache');
        return cache.addAll(urlsToCache);
      })
  );
  self.skipWaiting();
});

// Activate Service Worker
self.addEventListener('activate', event => {
  console.log('Service Worker activating...');
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cacheName => {
          if (cacheName !== CACHE_NAME) {
            console.log('Deleting old cache:', cacheName);
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
  self.clients.claim();
});

// Fetch event - Cache First Strategy untuk static assets, Network First untuk API
self.addEventListener('fetch', event => {
  const url = new URL(event.request.url);
  
  // Skip non-GET requests
  if (event.request.method !== 'GET') {
    return;
  }

  // Skip requests to different origins
  if (url.origin !== location.origin) {
    return;
  }

  // Skip authentication pages and forms (NEVER cache these)
  if (url.pathname.includes('/login') ||
      url.pathname.includes('/register') ||
      url.pathname.includes('/password') ||
      url.pathname.includes('/auth') ||
      url.pathname.includes('/two-factor') ||
      url.pathname.includes('/settings') ||
      url.pathname.includes('/profile') ||
      url.pathname.includes('/dashboard') ||
      url.pathname.includes('/') ||
      url.pathname.includes('/logout') ||
      url.pathname.includes('/verify-email') ||
      url.searchParams.has('_token') ||
      event.request.headers.get('X-CSRF-TOKEN') ||
      event.request.headers.get('X-Livewire')) {
    // Always go to network for auth-related requests
    event.respondWith(fetch(event.request));
    return;
  }

  // Network First strategy untuk API dan halaman dinamis
  if (url.pathname.startsWith('/api/') || 
      url.pathname.includes('livewire') ||
      url.pathname.includes('chat') ||
      url.pathname.includes('notifications')) {
    event.respondWith(
      fetch(event.request)
        .then(response => {
          // Cache successful responses
          if (response.status === 200) {
            const responseToCache = response.clone();
            caches.open(CACHE_NAME)
              .then(cache => {
                cache.put(event.request, responseToCache);
              });
          }
          return response;
        })
        .catch(() => {
          // Fallback to cache if network fails
          return caches.match(event.request)
            .then(response => {
              if (response) {
                return response;
              }
              // Return offline page for navigation requests
              if (event.request.mode === 'navigate') {
                return caches.match('/offline.html');
              }
            });
        })
    );
    return;
  }

  // Cache First strategy untuk static assets
  event.respondWith(
    caches.match(event.request)
      .then(response => {
        // Return cached version if available
        if (response) {
          return response;
        }
        
        // Fetch from network and cache
        return fetch(event.request)
          .then(response => {
            // Don't cache non-successful responses
            if (!response || response.status !== 200 || response.type !== 'basic') {
              return response;
            }

            // Clone the response
            const responseToCache = response.clone();

            caches.open(CACHE_NAME)
              .then(cache => {
                cache.put(event.request, responseToCache);
              });

            return response;
          });
      })
  );
});

// Background Sync untuk notifikasi offline
self.addEventListener('sync', event => {
  if (event.tag === 'background-sync') {
    console.log('Background sync triggered');
    event.waitUntil(doBackgroundSync());
  }
});

function doBackgroundSync() {
  // Implementasi sync data yang tertunda saat offline
  return Promise.resolve();
}

// Push notifications
self.addEventListener('push', event => {
  console.log('[Service Worker] Push notification received', event);
  
  let notificationData = {
    title: 'Panara Course',
    body: 'Notifikasi baru dari Panara Course',
    icon: '/icons/icon-192x192.png',
    badge: '/icons/icon-72x72.png',
    url: '/',
  };

  // Parse the push notification payload
  if (event.data) {
    try {
      const data = event.data.json();
      notificationData = {
        title: data.title || notificationData.title,
        body: data.body || notificationData.body,
        icon: data.icon || notificationData.icon,
        badge: data.badge || notificationData.badge,
        url: data.data?.url || notificationData.url,
        tag: data.data?.chat_slug || 'default',
        data: data.data || {},
      };
    } catch (e) {
      console.error('[Service Worker] Failed to parse push data', e);
      notificationData.body = event.data.text();
    }
  }

  const options = {
    body: notificationData.body,
    icon: notificationData.icon,
    badge: notificationData.badge,
    vibrate: [200, 100, 200],
    tag: notificationData.tag,
    requireInteraction: false,
    data: {
      url: notificationData.url,
      dateOfArrival: Date.now(),
      ...notificationData.data,
    },
    actions: [
      {
        action: 'open',
        title: 'Buka',
        icon: '/icons/icon-72x72.png'
      },
      {
        action: 'close',
        title: 'Tutup',
        icon: '/icons/icon-72x72.png'
      }
    ]
  };

  event.waitUntil(
    self.registration.showNotification(notificationData.title, options)
  );
});

// Notification click handler
self.addEventListener('notificationclick', event => {
  console.log('[Service Worker] Notification click received', event);

  event.notification.close();

  const urlToOpen = event.notification.data?.url || '/';

  if (event.action === 'open' || !event.action) {
    // Open or focus the app window
    event.waitUntil(
      clients.matchAll({ type: 'window', includeUncontrolled: true })
        .then(windowClients => {
          // Check if there's already a window open
          for (let i = 0; i < windowClients.length; i++) {
            const client = windowClients[i];
            if (client.url === urlToOpen && 'focus' in client) {
              return client.focus();
            }
          }
          // If no window is open, open a new one
          if (clients.openWindow) {
            return clients.openWindow(urlToOpen);
          }
        })
    );
  } else if (event.action === 'close') {
    // Just close the notification (already done above)
    console.log('[Service Worker] Notification dismissed');
  }
});

