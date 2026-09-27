/* #archivo: frontend/services/push.service.js
   Gestión de suscripciones Push Notification (Web Push API)
   ──────────────────────────────────────────────────────────
   Requisitos para que funcione en producción:
   - HTTPS obligatorio (en localhost funciona sin HTTPS)
   - Service Worker registrado con handler 'push'
   - iOS: solo desde Safari con app instalada en Home Screen (iOS 16.4+)
   ────────────────────────────────────────────────────────── */

import { apiFetch } from "./api.service.js";
import { getToken } from "./storage.service.js";

/* Clave VAPID pública — debe coincidir con VAPID_PUBLIC_KEY en .env del backend
   (regenerada 2026-07-28: la anterior no tenía su PRIVATE_KEY correspondiente
   configurada en el servidor, así que ningún push podía enviarse nunca). */
const VAPID_PUBLIC_KEY = "BBHXivmRXUDJfObqhfOHKHz70mnSE6grOQVcF6XKcaL8227JWzzpCtnOuGLmLS17YV4r21OdpuBWUnQunQfIz4k";

/* ── Convierte VAPID key de base64url a Uint8Array ─────── */
function urlBase64ToUint8Array(base64String) {
    const padding = "=".repeat((4 - (base64String.length % 4)) % 4);
    const base64   = (base64String + padding).replace(/-/g, "+").replace(/_/g, "/");
    const raw      = window.atob(base64);
    return Uint8Array.from([...raw].map(c => c.charCodeAt(0)));
}

/* ── Comprueba si el navegador soporta Push ─────────────── */
export function isPushSupported() {
    return (
        "serviceWorker" in navigator &&
        "PushManager"   in window    &&
        "Notification"  in window
    );
}

/* ── Solicita permiso y suscribe al push ─────────────────── */
export async function subscribeToPush() {
    if (!isPushSupported()) {
        console.info("[Push] Web Push no soportado en este navegador/dispositivo.");
        return null;
    }

    if (!getToken()) {
        /* No hay sesión activa — no suscribir */
        return null;
    }

    try {
        const permission = await Notification.requestPermission();

        if (permission !== "granted") {
            console.info("[Push] Permiso denegado por el usuario.");
            return null;
        }

        const registration = await navigator.serviceWorker.ready;

        /* Verificar si ya hay una suscripción activa */
        let subscription = await registration.pushManager.getSubscription();

        if (!subscription) {
            subscription = await registration.pushManager.subscribe({
                userVisibleOnly:      true,
                applicationServerKey: urlBase64ToUint8Array(VAPID_PUBLIC_KEY),
            });
        }

        /* Guardar la suscripción en el backend — la ruta real es POST /push-subscriptions
           (PushSubscriptionController::store) y espera exactamente la forma de
           subscription.toJSON(): {endpoint, keys:{p256dh, auth}}. /push/subscribe
           nunca existió como ruta (404 silencioso). */
        await apiFetch("/push-subscriptions", {
            method: "POST",
            body:   JSON.stringify(subscription.toJSON()),
        });

        console.info("[Push] Suscripción registrada correctamente.");
        return subscription;

    } catch (err) {
        console.warn("[Push] Error al suscribir:", err.message);
        return null;
    }
}

/* ── Cancela la suscripción push ─────────────────────────── */
export async function unsubscribeFromPush() {
    if (!isPushSupported()) return;

    try {
        const registration = await navigator.serviceWorker.ready;
        const subscription = await registration.pushManager.getSubscription();

        if (subscription) {
            await subscription.unsubscribe();
            await apiFetch("/push-subscriptions", {
                method: "DELETE",
                body:   JSON.stringify({ endpoint: subscription.endpoint }),
            });
            console.info("[Push] Suscripción cancelada.");
        }
    } catch (err) {
        console.warn("[Push] Error al cancelar suscripción:", err.message);
    }
}

/* ── Registra el suscriptor al iniciar sesión ────────────── */
/* Expuesto como window.__subscribePush para ser llamado desde layout.controller.js */
export function initPushOnLogin() {
    window.__subscribePush = subscribeToPush;
}
