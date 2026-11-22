const CACHE_NAME = 'ximple-pwa-v1';
const STATIC_ASSETS = [
  '/assets/images/pwa-icon-192.png',
  '/assets/images/pwa-icon-512.png'
];

self.addEventListener('install', function (event) {
  event.waitUntil(
    caches.open(CACHE_NAME).then(function (cache) {
      return cache.addAll(STATIC_ASSETS).catch(function () {});
    })
  );
  self.skipWaiting();
});

self.addEventListener('activate', function (event) {
  event.waitUntil(
    caches.keys().then(function (keys) {
      return Promise.all(
        keys.filter(function (k) { return k !== CACHE_NAME; })
            .map(function (k) { return caches.delete(k); })
      );
    })
  );
  self.clients.claim();
});

self.addEventListener('fetch', function (event) {
  var req = event.request;
  if (req.method !== 'GET') return;

  var url = new URL(req.url);
  if (url.origin !== self.location.origin) return;

  // Cache-first / stale-while-revalidate for static assets (images, fonts, icons)
  if (/\.(png|jpg|jpeg|svg|gif|webp|woff2?|ttf|eot|ico)$/i.test(url.pathname)) {
    event.respondWith(
      caches.match(req).then(function (cached) {
        var fetchPromise = fetch(req).then(function (networkRes) {
          if (networkRes && networkRes.status === 200) {
            var copy = networkRes.clone();
            caches.open(CACHE_NAME).then(function (cache) { cache.put(req, copy); });
          }
          return networkRes;
        }).catch(function () { return cached; });
        return cached || fetchPromise;
      })
    );
    return;
  }

  // Network-first for HTML pages & dynamic endpoints (never serve stale CRM data)
  if (req.mode === 'navigate') {
    event.respondWith(
      fetch(req).catch(function () {
        return new Response(
          '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Offline — Ximple</title><style>body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;background:#0f172a;color:#e2e8f0;font-family:Inter,system-ui,sans-serif;text-align:center;padding:24px}.card{background:#1e293b;border:1px solid #334155;border-radius:16px;padding:32px;max-width:380px}h2{margin:0 0 8px;color:#f8fafc}p{color:#94a3b8;font-size:14px;margin:0 0 20px}button{background:#3b82f6;color:#fff;border:none;padding:10px 20px;border-radius:8px;font-weight:600;cursor:pointer}</style></head><body><div class="card"><h2>You are offline</h2><p>Koneksi internet terputus. Periksa jaringan Anda lalu coba muat ulang.</p><button onclick="location.reload()">Coba Lagi</button></div></body></html>',
          { headers: { 'Content-Type': 'text/html; charset=utf-8' } }
        );
      })
    );
  }
});
