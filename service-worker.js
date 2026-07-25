/* =========================================================
   #archivo: /frontend/service-worker.js
   Service Worker de la PWA del Sistema de Semilleros
   ========================================================= */

const CACHE_NAME = "semilleros-v10";

/* Archivos del shell (raramente cambian → cache first) */
const SHELL_URLS = [
    "/",
    "/index.html",
    "/app.js",
    "/css/theme.css",
    "/css/pwa.css",
    "/style.css",
    "/manifest.json",
    "/icon-192.png",
    "/icon-512.png",
    "/apple-touch-icon-152.png",
    "/apple-touch-icon-167.png",
    "/apple-touch-icon-180.png"
];

/* Extensiones de JS/módulos → network first (cambian con cada deploy) */
function isJsModule(url) {
    return url.pathname.endsWith(".js") && !url.pathname.includes("sw");
}

/* API calls → never cache */
function isApiCall(url) {
    return url.pathname.startsWith("/api");
}


/* ── INSTALL ──────────────────────────────────────────── */

self.addEventListener("install", event => {

    /* Activa este SW inmediatamente sin esperar que se cierren las pestañas */
    self.skipWaiting();

    event.waitUntil(
        caches.open(CACHE_NAME).then(cache => {
            console.log("[SW] Cacheando shell inicial");
            /* Fix #2: manejo de errores individuales — un archivo inaccesible
               no impide la instalación del SW */
            return Promise.allSettled(
                SHELL_URLS.map(url =>
                    cache.add(url).catch(e =>
                        console.warn("[SW] No se pudo cachear:", url, e)
                    )
                )
            );
        })
    );
});


/* ── ACTIVATE ─────────────────────────────────────────── */

self.addEventListener("activate", event => {

    event.waitUntil(
        caches.keys().then(names =>
            Promise.all(
                names.map(name => {
                    if (name !== CACHE_NAME) {
                        console.log("[SW] Eliminando cache viejo:", name);
                        return caches.delete(name);
                    }
                })
            )
        ).then(() => {
            /* Toma el control de todas las pestañas abiertas inmediatamente */
            return self.clients.claim();
        })
    );
});


/* ── FETCH ────────────────────────────────────────────── */

self.addEventListener("fetch", event => {

    const request = event.request;
    if (request.method !== "GET") return;

    const url = new URL(request.url);

    /* API → nunca cachear */
    if (isApiCall(url)) return;

    /* JS modules → network first (siempre versión fresca) */
    if (isJsModule(url)) {
        event.respondWith(
            fetch(request)
                .then(response => {
                    /* Fix #3: solo cachear respuestas exitosas */
                    if (response.ok) {
                        const clone = response.clone();
                        caches.open(CACHE_NAME).then(c => c.put(request, clone));
                    }
                    return response;
                })
                .catch(() =>
                    /* Sin red → fallback al cache */
                    caches.match(request)
                )
        );
        return;
    }

    /* Shell files → cache first, luego red */
    event.respondWith(
        caches.match(request).then(cached => {
            if (cached) return cached;
            return fetch(request).then(response => {
                /* Fix #3: solo cachear respuestas exitosas */
                if (response.ok) {
                    const clone = response.clone();
                    caches.open(CACHE_NAME).then(c => c.put(request, clone));
                }
                return response;
            });
        })
    );
});


/* ── PUSH ─────────────────────────────────────────────── */

self.addEventListener('push', event => {
    if (!event.data) return;
    const data = event.data.json();
    const title   = data.title   || 'Semilleros UT';
    const options = {
        body:  data.body  || data.message || '',
        icon:  '/icon-192.png',
        badge: '/icon-192.png',
        data:  { url: data.url || '/' },
    };
    event.waitUntil(
        self.registration.showNotification(title, options)
    );
});

/* ── NOTIFICATIONCLICK ────────────────────────────────── */

self.addEventListener('notificationclick', event => {
    event.notification.close();
    const target = event.notification.data?.url || '/';
    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true })
            .then(clients => {
                const existing = clients.find(c => c.url.includes(self.location.origin));
                if (existing) return existing.focus();
                return self.clients.openWindow(target);
            })
    );
});
