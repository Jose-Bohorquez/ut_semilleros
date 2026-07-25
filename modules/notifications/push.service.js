/* #archivo: frontend/modules/notifications/push.service.js
   Gestión de Web Push Notifications (suscripción y permiso)
   --------------------------------------------------------- */

import { apiFetch } from '../../services/api.service.js';

// Obtener la VAPID public key desde el backend (o hardcodearla como constante)
const VAPID_PUBLIC_KEY = '<TU_VAPID_PUBLIC_KEY_BASE64_URL_SAFE>';

function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - base64String.length % 4) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const rawData = atob(base64);
    return Uint8Array.from([...rawData].map(c => c.charCodeAt(0)));
}

export async function requestPushPermissionAndSubscribe() {
    if (!('Notification' in window) || !('PushManager' in window)) return;

    const permission = await Notification.requestPermission();
    if (permission !== 'granted') return;

    const reg = await navigator.serviceWorker.ready;
    let subscription = await reg.pushManager.getSubscription();

    if (!subscription) {
        subscription = await reg.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: urlBase64ToUint8Array(VAPID_PUBLIC_KEY),
        });
    }

    await apiFetch('/push-subscriptions', {
        method: 'POST',
        body: JSON.stringify(subscription.toJSON()),
    });
}
