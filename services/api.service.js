/* =========================================================
   #archivo: /frontend/services/api.service.js
   ---------------------------------------------------------
   Servicio central para comunicación con la API Laravel
   ========================================================= */

import { getToken, rememberIntendedRoute } from "./storage.service.js";

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

function clearAuthSession() {
    try {
        localStorage.removeItem("token");
        localStorage.removeItem("user");
    } catch (e) {
        console.warn("No se pudo limpiar localStorage:", e);
    }
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
            clearAuthSession();
            window.location.href = "/";
            return;
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