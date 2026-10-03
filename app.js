/* #archivo: /frontend/app.js */
import { renderRoute } from './core/router.js?v=4';
import { mountSia }    from './core/sia.widget.js';
import { flushPendingRevokes } from './services/api.service.js';
import { setLoginFlash } from './services/storage.service.js';

document.addEventListener("DOMContentLoaded", () => {
    renderRoute();
    mountSia();   /* SIA + WhatsApp de bugs en todas las pantallas (RF17) */
    flushPendingRevokes();   /* CU03 E1: cierres de sesión hechos sin conexión */
});

window.addEventListener("online", () => flushPendingRevokes());

/* CU03: si la sesión se cierra en otra pestaña, esta también sale (antes
   quedaba abierta con los datos en pantalla hasta la siguiente petición). */
const PUBLIC_PATHS = ["/", "/login", "/forgot-password", "/reset-password"];
window.addEventListener("storage", e => {
    if (e.key === "token" && e.oldValue && !e.newValue && !PUBLIC_PATHS.includes(location.pathname)) {
        setLoginFlash("Se cerró la sesión en otra pestaña.");
        location.href = "/";
    }
});

/**
 * Registro del Service Worker
 * Permite que la aplicación funcione como PWA
 * (cache offline, instalación en dispositivo, etc.)
 */
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/service-worker.js')
        .then(async () => {
            console.log('Service Worker registrado');
            // Pide al SW que guarde lo que la página ya cargó (módulos JS, CSS, imágenes). En la primera visita esas
            // peticiones ocurren antes de que el SW controle la página y no quedarían en caché: sin esto, reabrir
            // la app sin conexión justo después de instalarla dejaba la pantalla en blanco.
            try {
                const ready = await navigator.serviceWorker.ready;
                const urls = performance.getEntriesByType('resource')
                    .map(e => e.name)
                    .filter(u => u.startsWith(location.origin) && /\.(js|css|png|svg|json)(\?|$)/.test(u) && !u.includes('/api/') && !u.endsWith('/service-worker.js'));
                ready.active?.postMessage({ type: 'CACHE_URLS', urls: [...new Set(urls)] });
            } catch { /* sin SW activo: nada que guardar */ }
            // Solicitar permiso y suscribir al push (solo si el usuario está autenticado).
            // Se expone en window para que layout.controller.js lo llame después del login.
            // (2026-07-28: unificado con services/push.service.js — el módulo viejo
            // modules/notifications/push.service.js tenía una VAPID key placeholder
            // sin completar y apuntaba a una ruta /push/subscribe que nunca existió.)
            const { initPushOnLogin } = await import('./services/push.service.js');
            initPushOnLogin();   // solo re-sincroniza si ya hay permiso; el permiso se pide desde el perfil con un toque
        })
        .catch(err => console.error('Error SW:', err));
}