/* #archivo: /frontend/app.js */
import { renderRoute } from './core/router.js?v=2';
import { mountSia }    from './core/sia.widget.js';

document.addEventListener("DOMContentLoaded", () => {
    renderRoute();
    mountSia();   /* SIA + WhatsApp de bugs en todas las pantallas (RF17) */
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