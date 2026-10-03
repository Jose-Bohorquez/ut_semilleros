/* =========================================================
   #archivo: /frontend/service-worker.js
   Service Worker de la PWA del Sistema de Semilleros
   ========================================================= */

const CACHE_NAME = "semilleros-v27";

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

/* Librerías de CDN que la app necesita para verse y funcionar. Se guardan al INSTALAR el SW: si solo se
   guardaran cuando se piden, una persona que abre la app, la instala y la reabre sin red (su primera
   recarga) vería la pantalla en blanco, porque esas peticiones se hicieron antes de que el SW tomara el control. */
const CDN_PRECACHE = [
    "https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css",
    "https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js",
    "https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css",
    "https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js",
    "https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js",
    "https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js",
    "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css",
    "https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js",
    "https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js",
    "https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js",
    "https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js",
    "https://cdn.jsdelivr.net/npm/sweetalert2@11",
    "https://cdn.tailwindcss.com/",
    "https://code.jquery.com/jquery-3.7.1.min.js",
    "https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap"
];

/* Extensiones de JS/módulos → network first (cambian con cada deploy) */
function isJsModule(url) {
    /* Excluir solo el propio SW. Antes era !includes("sw"), que también
       atrapaba "pa-sw-ord": reset-password y forgot-password quedaban en
       cache-first y no recibían correcciones (review 2026-09-27). */
    return url.pathname.endsWith(".js") && !url.pathname.endsWith("/service-worker.js");
}

/* RNF02: librerías de CDN (estilos, iconos, SweetAlert, jQuery…). Sin ellas la
   app abre sin conexión pero sin estilos. Se sirven de la caché y se actualizan
   en segundo plano. Son respuestas «opaque» (sin CORS): se aceptan solo de
   estos hosts. Google Identity (accounts.google.com) NO se cachea. */
const CDN_HOSTS = [
    "cdn.jsdelivr.net", "cdn.tailwindcss.com", "code.jquery.com", "cdn.datatables.net",
    "cdnjs.cloudflare.com", "fonts.googleapis.com", "fonts.gstatic.com"
];

function isCdnAsset(url) {
    return CDN_HOSTS.includes(url.hostname);
}

/* API calls → never cache (la copia sin conexión de semilleros la guarda
   api.service.js por usuario, ver RNF02) */
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
            return Promise.allSettled([
                /* cache:"reload" salta la caché HTTP/CDN: sin esto el SW nuevo podía
                   quedarse con un theme.css o index.html viejo fijado en cache-first
                   hasta la siguiente versión (review 2026-09-28). */
                ...SHELL_URLS.map(url =>
                    cache.add(new Request(url, { cache: "reload" })).catch(e =>
                        console.warn("[SW] No se pudo cachear:", url, e)
                    )
                ),
                /* CDN: respuestas «opaque» (no-cors); cache.add las rechaza, por eso fetch + put. */
                ...CDN_PRECACHE.map(url => {
                    const req = new Request(url, { mode: "no-cors" });
                    return fetch(req).then(res => cache.put(req, res)).catch(e =>
                        console.warn("[SW] No se pudo precachear CDN:", url, e)
                    );
                })
            ]);
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

    /* RNF02: abrir cualquier ruta de la SPA sin conexión (/seedbeds, /dashboard…).
       Red primero para recibir siempre el index.html actual; sin red, el shell. */
    if (request.mode === "navigate" && url.origin === self.location.origin) {
        event.respondWith(
            fetch(request, { cache: "no-cache" })
                .then(response => {
                    if (response.ok) {
                        const clone = response.clone();
                        caches.open(CACHE_NAME).then(c => c.put("/index.html", clone));
                    }
                    return response;
                })
                .catch(async () =>
                    (await caches.match("/index.html")) || (await caches.match("/")) || Response.error()
                )
        );
        return;
    }

    /* CDN → caché primero y actualización en segundo plano */
    if (isCdnAsset(url)) {
        event.respondWith(
            caches.open(CACHE_NAME).then(async cache => {
                const cached = await cache.match(request);
                const network = fetch(request).then(response => {
                    if (response.ok || response.type === "opaque") cache.put(request, response.clone());
                    return response;
                }).catch(() => cached);
                return cached || network;
            })
        );
        return;
    }

    /* Otros orígenes (Google Identity, etc.) → sin intervenir */
    if (url.origin !== self.location.origin) return;

    /* JS modules → network first (siempre versión fresca).
       cache:"no-cache" obliga a revalidar con el servidor (304 si no cambió):
       sin esto el fetch devolvía la copia de la caché HTTP del navegador
       (Cache-Control max-age=300) y una corrección desplegada tardaba hasta
       5 min en llegar — verificado 2026-09-27 con el fix de XSS. */
    if (isJsModule(url)) {
        event.respondWith(
            fetch(request, { cache: "no-cache" })
                .then(response => {
                    /* Fix #3: solo cachear respuestas exitosas */
                    if (response.ok) {
                        const clone = response.clone();
                        caches.open(CACHE_NAME).then(c => c.put(request, clone));
                    }
                    return response;
                })
                .catch(async () =>
                    /* Sin red → fallback al cache. ignoreSearch: los módulos se piden con «?v=N» (app.js?v=5)
                       pero el shell se guardó como /app.js; sin esto no coincidían y la app quedaba en blanco. */
                    (await caches.match(request)) || (await caches.match(request, { ignoreSearch: true })) || Response.error()
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


/* ── MESSAGE ──────────────────────────────────────────── */

/* La app envía las URLs que ya cargó (ver app.js) para dejarlas disponibles sin conexión. Solo mismo origen. */
self.addEventListener("message", event => {
    if (event.data?.type !== "CACHE_URLS" || !Array.isArray(event.data.urls)) return;
    event.waitUntil(
        caches.open(CACHE_NAME).then(cache =>
            Promise.allSettled(
                event.data.urls
                    .filter(u => typeof u === "string" && u.startsWith(self.location.origin))
                    .map(u => cache.match(u).then(hit => hit || cache.add(u)))
            )
        )
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
