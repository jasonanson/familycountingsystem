const CACHE_NAME = 'homesync-v1.0.0';
const STATIC_ASSETS = [
  '/',
  '/manifest.json',
  '/data-exchange',
  'https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap',
  'https://fonts.googleapis.com/css2?family=Microsoft+JhengHei:wght@100..900&display=swap',
  'https://cdn.jsdelivr.net/npm/alpinejs@3.13.5/dist/cdn.min.js'
];

// Offline HTML Template Response
const OFFLINE_HTML = `
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>離線模式 - HomeSync Finance</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; background-color: #FAFAF9; color: #1e1b17; }
    </style>
</head>
<body class="flex flex-col items-center justify-center min-h-screen p-6 text-center">
    <div class="bg-white border border-[#E7E5E4] rounded-2xl p-8 max-w-md shadow-lg space-y-4">
        <div class="w-16 h-16 bg-[#006b5f]/10 text-[#006b5f] rounded-full flex items-center justify-center mx-auto">
            <span class="material-symbols-outlined text-4xl">wifi_off</span>
        </div>
        <h1 class="text-2xl font-bold text-[#006b5f]">離線存取模式</h1>
        <p class="text-gray-600 text-sm leading-relaxed">
            您目前無網路連線，HomeSync 已自動切換至離線模式。先前的快取資料可繼續檢視，連線恢復後將自動同步最新記帳資訊。
        </p>
        <div class="pt-4">
            <button onclick="window.location.reload()" class="px-6 py-2.5 bg-[#006b5f] text-white font-bold rounded-xl hover:bg-[#006b5f]/90 transition-all shadow">
                重新連線嘗試
            </button>
        </div>
    </div>
</body>
</html>
`;

// Install Event
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      console.log('[Service Worker] Pre-caching offline pages and assets');
      return cache.addAll(STATIC_ASSETS).catch((err) => console.warn('[SW Precache Warning]', err));
    })
  );
  self.skipWaiting();
});

// Activate Event (Cleanup Old Caches)
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cache) => {
          if (cache !== CACHE_NAME) {
            console.log('[Service Worker] Deleting old cache:', cache);
            return caches.delete(cache);
          }
        })
      );
    })
  );
  return self.clients.claim();
});

// Fetch Event Handler with Network-First strategy for dynamic pages and Cache-First for static assets
self.addEventListener('fetch', (event) => {
  // Skip non-GET requests
  if (event.request.method !== 'GET') return;

  const requestUrl = new URL(event.request.url);

  // Network-First strategy for HTML navigation requests
  if (event.request.mode === 'navigate') {
    event.respondWith(
      fetch(event.request)
        .then((response) => {
          // Clone & put clean copy into cache
          const responseClone = response.clone();
          caches.open(CACHE_NAME).then((cache) => cache.put(event.request, responseClone));
          return response;
        })
        .catch(() => {
          return caches.match(event.request).then((cachedResponse) => {
            if (cachedResponse) {
              return cachedResponse;
            }
            return new Response(OFFLINE_HTML, {
              headers: { 'Content-Type': 'text/html; charset=utf-8' }
            });
          });
        })
    );
    return;
  }

  // Cache-First strategy for static assets
  event.respondWith(
    caches.match(event.request).then((cachedResponse) => {
      if (cachedResponse) {
        // Fetch background update for cache
        fetch(event.request).then((response) => {
          if (response && response.status === 200) {
            caches.open(CACHE_NAME).then((cache) => cache.put(event.request, response));
          }
        }).catch(() => {});
        return cachedResponse;
      }

      return fetch(event.request).then((response) => {
        if (!response || response.status !== 200 || response.type !== 'basic') {
          return response;
        }
        const responseClone = response.clone();
        caches.open(CACHE_NAME).then((cache) => cache.put(event.request, responseClone));
        return response;
      }).catch(() => {
        // Fallback for image requests if offline
        if (event.request.destination === 'image') {
          return new Response(
            '<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 24 24"><text y="12" font-size="10">Offline Image</text></svg>',
            { headers: { 'Content-Type': 'image/svg+xml' } }
          );
        }
      });
    })
  );
});
