/* #archivo: /frontend/app.js */
import { renderRoute } from './core/router.js';

document.addEventListener("DOMContentLoaded", () => {
    renderRoute();
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
            const { requestPushPermissionAndSubscribe } = await import('./modules/notifications/push.service.js');
            window.__subscribePush = requestPushPermissionAndSubscribe;
        })
        .catch(err => console.error('Error SW:', err));
}