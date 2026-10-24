/* Huta Studio PWA — cache aset statis; jangan simpan halaman privat */
const CACHE = 'huta-v3-v1';
const PRIVATE = [/\/siteman/, /\/layanan-mandiri/, /\/admin/, /\/auth/, /\/api\//];

self.addEventListener('install', (e) => { self.skipWaiting(); });
self.addEventListener('activate', (e) => {
  e.waitUntil(caches.keys().then((ks) => Promise.all(ks.filter((k) => k !== CACHE).map((k) => caches.delete(k)))).then(() => self.clients.claim()));
});
self.addEventListener('fetch', (e) => {
  const req = e.request;
  if (req.method !== 'GET') return;
  const url = new URL(req.url);
  if (url.origin !== location.origin) return;
  if (PRIVATE.some((re) => re.test(url.pathname))) return;
  if (req.mode === 'navigate') {
    e.respondWith(fetch(req).catch(() => caches.match(req)));
    return;
  }
  if (url.pathname.match(/\.(css|js|woff2?|png|jpe?g|webp|svg|ico|gif)$/)) {
    e.respondWith(
      caches.match(req).then((r) => r || fetch(req).then((res) => {
        if (res && res.ok && res.type === 'basic') {
          const copy = res.clone();
          caches.open(CACHE).then((c) => c.put(req, copy));
        }
        return res;
      }))
    );
  }
});
