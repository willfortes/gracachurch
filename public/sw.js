/* Graça Church · service worker: abre offline, deixa o 3G mais rápido e mostra os avisos push. */
const VERSION = 'gc-v2';
const STATIC = VERSION + '-static';
const PAGES = VERSION + '-pages';
const TILES = 'gc-tiles';
const PRECACHE = [
  '/', '/programacao', '/grupos', '/como-chegar', '/offline',
  '/js/app.js', '/fonts/montserrat-latin-wght-normal.woff2', '/img/logo-claro.png',
  '/img/louvor-640.webp', '/icons/icon-192.png', '/icons/badge-96.png', '/manifest.webmanifest'
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(STATIC).then((c) => Promise.all(PRECACHE.map((u) => c.add(new Request(u, { cache: 'reload' })).catch(() => null))))
      .then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) => Promise.all(keys.filter((k) => !k.startsWith(VERSION) && k !== TILES).map((k) => caches.delete(k))))
      .then(() => self.clients.claim())
  );
});

async function trimCache(name, max) {
  const cache = await caches.open(name);
  const keys = await cache.keys();
  for (let i = 0; i < keys.length - max; i++) await cache.delete(keys[i]);
}

// Páginas: tenta a rede por até 4 s; se demorar ou falhar, usa a cópia salva.
async function networkFirst(request) {
  const cache = await caches.open(PAGES);
  try {
    const response = await Promise.race([
      fetch(request),
      new Promise((_, reject) => setTimeout(() => reject(new Error('timeout')), 4000)),
    ]);
    if (response.ok) {
      cache.put(request, response.clone());
      trimCache(PAGES, 40);
    }
    return response;
  } catch (e) {
    const cached = (await cache.match(request, { ignoreSearch: true })) || (await caches.match(request, { ignoreSearch: true }));
    if (cached) {
      // Atualiza em segundo plano para a próxima visita
      fetch(request).then((r) => r.ok && cache.put(request, r)).catch(() => {});
      return cached;
    }
    return (await caches.match('/offline')) || new Response('Sem conexão', { status: 503 });
  }
}

// Arquivos estáticos: usa o salvo; se não tiver, busca e guarda.
async function cacheFirst(request, name) {
  const cached = await caches.match(request);
  if (cached) return cached;
  const response = await fetch(request);
  if (response.ok || response.type === 'opaque') {
    const cache = await caches.open(name);
    cache.put(request, response.clone());
    if (name === TILES) trimCache(TILES, 300);
  }
  return response;
}

self.addEventListener('fetch', (event) => {
  const { request } = event;
  if (request.method !== 'GET') return;
  const url = new URL(request.url);

  if (url.hostname === 'tile.openstreetmap.org') {
    event.respondWith(cacheFirst(request, TILES));
    return;
  }
  if (url.origin !== self.location.origin) return;
  if (url.pathname.startsWith('/admin') || url.pathname.startsWith('/api/')) return;

  if (request.mode === 'navigate') {
    event.respondWith(networkFirst(request));
    return;
  }
  if (/^\/(fonts|img|icons|js|vendor)\//.test(url.pathname) || url.pathname === '/favicon.ico') {
    event.respondWith(cacheFirst(request, STATIC));
  }
});

/* ---------- Avisos push ---------- */
self.addEventListener('push', (event) => {
  let data = {};
  try { data = event.data ? event.data.json() : {}; } catch (e) { data = { title: 'Graça Church', body: event.data ? event.data.text() : '' }; }
  const title = data.title || 'Graça Church';
  event.waitUntil(self.registration.showNotification(title, {
    body: data.body || '',
    icon: '/icons/icon-192.png',
    badge: '/icons/badge-96.png',
    tag: data.tag || undefined,
    renotify: !!data.tag,
    lang: 'pt-BR',
    data: { url: data.url || '/' },
  }));
});

self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  const target = new URL(event.notification.data && event.notification.data.url || '/', self.location.origin).href;
  event.waitUntil((async () => {
    const all = await self.clients.matchAll({ type: 'window', includeUncontrolled: true });
    for (const c of all) {
      if (c.url.startsWith(self.location.origin) && 'focus' in c) {
        await c.focus();
        if ('navigate' in c) return c.navigate(target);
        return;
      }
    }
    return self.clients.openWindow(target);
  })());
});

// O navegador trocou a inscrição: renova sem a pessoa precisar fazer nada.
self.addEventListener('pushsubscriptionchange', (event) => {
  event.waitUntil((async () => {
    const res = await fetch('/api/push/key');
    const { key } = await res.json();
    if (!key) return;
    const pad = '='.repeat((4 - key.length % 4) % 4);
    const raw = atob((key + pad).replace(/-/g, '+').replace(/_/g, '/'));
    const appKey = Uint8Array.from(raw, (c) => c.charCodeAt(0));
    const sub = await self.registration.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: appKey });
    const old = event.oldSubscription;
    // O servidor copia os tópicos da inscrição antiga; os padrões só valem se ela não existir mais.
    await fetch('/api/push/subscribe', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ subscription: sub.toJSON(), topics: ['cultos', 'eventos'], oldEndpoint: old ? old.endpoint : null }),
    });
  })());
});
