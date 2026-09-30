/* =========================================================
   #archivo: /frontend/services/api.service.js
   ---------------------------------------------------------
   Servicio central para comunicación con la API Laravel
   ========================================================= */

import { getToken, getUser, rememberIntendedRoute, clearLocalSession, setLoginFlash, SESSION_EXPIRED_MSG,
         getPendingRevokes, setPendingRevokes } from "./storage.service.js";
import { showOfflineBanner } from "../core/offline-banner.js";

/* =========================================================
   URL BASE DE LA API
   ========================================================= */

const _host = window.location.hostname;
const _proto = window.location.protocol;
const _isLocal = _host === "localhost" || _host === "127.0.0.1";
const _isLAN = /^(192\.168\.|10\.|172\.(1[6-9]|2\d|3[01])\.)/.test(_host);

const API_URL = (_isLocal || _isLAN)
    ? `${_proto}//${_host}:8000/api`
    : `${_proto}//${_host}/api`;

/* =========================================================
   HELPERS
   ========================================================= */

function buildUrl(endpoint = "") {
    const cleanBase = API_URL.endsWith("/") ? API_URL.slice(0, -1) : API_URL;
    const cleanEndpoint = endpoint.startsWith("/") ? endpoint : `/${endpoint}`;
    return `${cleanBase}${cleanEndpoint}`;
}

/* CU03 E1: revoca en el servidor los tokens de cierres de sesión hechos sin
   conexión. Se llama al abrir la app y al recuperar la conexión. Un token que
   ya no sirve (401) también se descarta: ya no es un riesgo. */
export async function flushPendingRevokes() {
    const pending = getPendingRevokes();   /* ya sin los vencidos */
    setPendingRevokes(pending);
    if (!pending.length || !navigator.onLine) return;
    const left = [];
    for (const entry of pending) {
        try {
            const r = await fetch(buildUrl("/logout"), { method: "POST", headers: { Accept: "application/json", Authorization: `Bearer ${entry.t}` } });
            /* 2xx revocado · 401 ya no sirve · 5xx/429 el servidor falló: reintentar */
            if (r.status >= 500 || r.status === 429) left.push(entry);
        } catch {
            left.push(entry);   /* sigue sin red: se intenta en la próxima conexión */
        }
    }
    setPendingRevokes(left);
}

/* =========================================================
   RNF02 — consulta sin conexión
   El listado y el detalle de semilleros (con sus objetivos) se guardan al
   consultarlos; sin red se devuelve la última copia y se avisa. Se guarda por
   usuario y se borra al cerrar sesión: en un equipo compartido nadie ve lo
   que consultó otro. Se hace aquí y no en el service worker porque la
   respuesta depende del token (rol), y así la copia queda atada al usuario.
   ========================================================= */

/* CU23-E1: «Mis solicitudes» (y, por coherencia, «Mis propuestas») también se
   consultan sin conexión: /my devuelve solo lo del usuario autenticado y la
   copia queda atada a su id y se borra al cerrar sesión. */
const OFFLINE_ENDPOINTS = [/^\/seedbeds(\/\d+)?$/, /^\/objectives$/, /^\/requests\/my$/, /^\/proposals\/my$/];

function offlineKey(endpoint) {
    const uid = getUser()?.id;
    return uid ? `offline:v1:${uid}:${endpoint}` : null;
}

function isOfflineCacheable(endpoint, method) {
    return (method || "GET").toUpperCase() === "GET"
        && OFFLINE_ENDPOINTS.some(re => re.test(endpoint.split("?")[0]));
}

function saveOffline(endpoint, data) {
    const key = offlineKey(endpoint);
    if (!key) return;
    try { localStorage.setItem(key, JSON.stringify({ at: new Date().toISOString(), data })); } catch {}
}

function readOffline(endpoint) {
    const key = offlineKey(endpoint);
    if (!key) return null;
    try { return JSON.parse(localStorage.getItem(key) || "null"); } catch { return null; }
}

/* =========================================================
   FUNCIÓN BASE PARA LLAMADAS HTTP
   ========================================================= */

export async function apiFetch(endpoint, options = {}) {
    /* auth:false → no enviar el token ni tratar un 401 como sesión vencida.
       Lo usa el login: si quedó un token vencido en localStorage (lo normal
       desde que los tokens vencen a las 8 h), mandarlo hacía que el 401 de
       «Credenciales incorrectas» recargara la página (CU01 E2, review 2026-09-28). */
    const { auth = true, ...fetchOptions } = options;
    const token = auth ? getToken() : null;
    const url = buildUrl(endpoint);

    const headers = {
        "Accept": "application/json",
        ...(token && { Authorization: `Bearer ${token}` }),
        ...(fetchOptions.headers || {})
    };

    if (!(fetchOptions.body instanceof FormData)) {
        headers["Content-Type"] = "application/json";
    }

    let response;

    try {
        response = await fetch(url, {
            ...fetchOptions,
            headers
        });
    } catch (networkError) {
        const offline = token && isOfflineCacheable(endpoint, fetchOptions.method) && readOffline(endpoint);
        if (offline) {
            showOfflineBanner(offline.at);
            return offline.data;
        }
        console.error("[apiFetch] Error de red:", networkError);
        const err = new Error("No se pudo conectar con el servidor");
        err.status = 0;
        throw err;
    }

    let data = null;
    const contentType = response.headers.get("content-type") || "";

    try {
        if (contentType.includes("application/json")) {
            data = await response.json();
        } else {
            data = await response.text();
        }
    } catch (parseError) {
        console.warn("[apiFetch] No se pudo parsear la respuesta:", parseError);
        data = null;
    }

    if (!response.ok) {
        /* 401 con sesión = token vencido o revocado → cerrar sesión.
           401 sin sesión (p.ej. POST /login con clave errada) es un error
           normal que el formulario debe mostrar: antes recargaba la página
           y el mensaje «Credenciales incorrectas» nunca se veía (CU01 E2). */
        if (response.status === 401 && token) {
            console.warn("Token inválido o sesión expirada");
            rememberIntendedRoute();
            clearLocalSession();
            /* CU03 A1: «Su sesión expiró»; si el servidor explica otra causa
               (usuario inactivado, C-04) se muestra esa. */
            const why = typeof data === "object" && data?.message;
            setLoginFlash(why && /inactiv/i.test(why) ? why : SESSION_EXPIRED_MSG);
            window.location.href = "/";
            return;
        }

        /* RF16: estudiante sin autorización de datos → pantalla del aviso */
        if (response.status === 403 && data?.code === "CONSENT_REQUIRED" && token) {
            if (window.location.pathname !== "/consent") {
                window.location.href = "/consent";
                return;
            }
        }

        const serverMessage =
            (typeof data === "object" && data?.message) ||
            (typeof data === "string" && data) ||
            `Error HTTP ${response.status}`;

        console.error("[apiFetch] Error backend:", {
            url,
            status: response.status,
            data
        });

        const err = new Error(serverMessage);
        err.status = response.status;
        err.payload = data;
        throw err;
    }

    if (token && isOfflineCacheable(endpoint, fetchOptions.method)) saveOffline(endpoint, data);

    return data;
}

/* =========================================================
   USUARIOS
   ========================================================= */

export async function createUserApi(payload) {
    return apiFetch("/users", {
        method: "POST",
        body: JSON.stringify(payload)
    });
}

export async function updateUser(id, payload) {
    return apiFetch(`/users/${id}`, {
        method: "PUT",
        body: JSON.stringify(payload)
    });
}

export async function toggleUserStatus(userId) {
    return apiFetch(`/users/${userId}/toggle-status`, {
        method: "PUT"
    });
}

export async function getUsers() {
    return apiFetch("/users", {
        method: "GET"
    });
}

/* =========================================================
   AUTENTICACIÓN
   ========================================================= */

export async function login(email, password) {
    return apiFetch("/login", {
        method: "POST",
        body: JSON.stringify({
            email,
            password
        })
    });
}

export async function getMe() {
    return apiFetch("/me", {
        method: "GET"
    });
}

export async function logout() {
    return apiFetch("/logout", {
        method: "POST"
    });
}