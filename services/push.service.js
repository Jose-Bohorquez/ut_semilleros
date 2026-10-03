/* #archivo: frontend/services/push.service.js
   Gestión de suscripciones Push Notification (Web Push API)
   ──────────────────────────────────────────────────────────
   Requisitos para que funcione en producción:
   - HTTPS obligatorio (en localhost funciona sin HTTPS)
   - Service Worker registrado con handler 'push'
   - iOS: solo con la app INSTALADA en la pantalla de inicio (iOS 16.4+) y el permiso pedido con un TOQUE del usuario
   - Android/Chrome: el permiso también se pide con un toque; pedirlo solo al iniciar sesión suele bloquearse en silencio
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

/* ── Entorno: ¿app instalada? ¿iPhone/iPad? ─────────────── */
function isStandalone() {
    return window.matchMedia?.("(display-mode: standalone)")?.matches || window.navigator.standalone === true;
}
function isIOS() {
    return /iphone|ipad|ipod/i.test(navigator.userAgent) || (navigator.platform === "MacIntel" && navigator.maxTouchPoints > 1);
}

/* ── Estado para pintar el panel de ajustes ──────────────────
   state: "unsupported" · "needs-install" (iPhone sin instalar) · "denied" · "off" · "on" */
export async function getPushState() {
    const ios = isIOS(), installed = isStandalone();

    if (!isPushSupported()) {
        return { state: ios && !installed ? "needs-install" : "unsupported", ios, installed, permission: "unsupported" };
    }

    const permission = Notification.permission;
    if (permission === "denied") return { state: "denied", ios, installed, permission };

    let subscribed = false;
    try {
        const reg = await navigator.serviceWorker.ready;
        subscribed = !!(await reg.pushManager.getSubscription());
    } catch { /* sin SW: se trata como apagado */ }

    return { state: permission === "granted" && subscribed ? "on" : "off", ios, installed, permission };
}

/* ── Suscribe este dispositivo ───────────────────────────────
   prompt:true  → pide el permiso (¡llamar SOLO desde un toque del usuario!)
   prompt:false → silencioso: si ya hay permiso concedido, vuelve a registrar la suscripción en el servidor
                  (por si el servidor la perdió); si no hay permiso, no hace nada y no molesta. */
export async function subscribeToPush({ prompt = false } = {}) {
    if (!isPushSupported()) {
        console.info("[Push] Web Push no soportado en este navegador/dispositivo.");
        return null;
    }

    if (!getToken()) {
        /* No hay sesión activa — no suscribir */
        return null;
    }

    try {
        let permission = Notification.permission;
        if (permission === "default" && prompt) permission = await Notification.requestPermission();

        if (permission !== "granted") {
            if (prompt) console.info("[Push] Permiso no concedido:", permission);
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

        /* Guardar la suscripción en el backend — POST /push-subscriptions (PushSubscriptionController::store)
           espera exactamente la forma de subscription.toJSON(): {endpoint, keys:{p256dh, auth}}. */
        await apiFetch("/push-subscriptions", {
            method: "POST",
            body:   JSON.stringify(subscription.toJSON()),
        });

        console.info("[Push] Suscripción registrada correctamente.");
        return subscription;

    } catch (err) {
        console.warn("[Push] Error al suscribir:", err.message);
        throw err;
    }
}

/* ── Push de prueba a los dispositivos del usuario ──────────
   Devuelve la respuesta del servidor ({sent, failed, expired, errors, message}). Lanza si no se pudo entregar. */
export async function sendTestPush() {
    return apiFetch("/push-subscriptions/test", { method: "POST" });
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

/* ── Al iniciar sesión: solo re-sincroniza, NUNCA pide permiso ─
   Expuesto como window.__subscribePush para ser llamado desde layout.controller.js */
export function initPushOnLogin() {
    window.__subscribePush = () => subscribeToPush({ prompt: false }).catch(() => null);
}
