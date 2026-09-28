/* #archivo: /frontend/app.js */
import { renderRoute } from './core/router.js?v=2';
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
            // Solicitar permiso y suscribir al push (solo si el usuario está autenticado).
            // Se expone en window para que layout.controller.js lo llame después del login.
            // (2026-07-28: unificado con services/push.service.js — el módulo viejo
            // modules/notifications/push.service.js tenía una VAPID key placeholder
            // sin completar y apuntaba a una ruta /push/subscribe que nunca existió.)
            const { subscribeToPush } = await import('./services/push.service.js');
            window.__subscribePush = subscribeToPush;
        })
        .catch(err => console.error('Error SW:', err));
}