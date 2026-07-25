/* =========================================================
   #archivo: /frontend/services/api.service.js
   ---------------------------------------------------------
   Servicio central para comunicación con la API Laravel
   ========================================================= */

import { getToken } from "./storage.service.js";

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
    const token = getToken();
    const url = buildUrl(endpoint);

    const headers = {
        "Accept": "application/json",
        ...(token && { Authorization: `Bearer ${token}` }),
        ...(options.headers || {})
    };

    if (!(options.body instanceof FormData)) {
        headers["Content-Type"] = "application/json";
    }

    let response;

    try {
        response = await fetch(url, {
            ...options,
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
        if (response.status === 401) {
            console.warn("Token inválido o sesión expirada");
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