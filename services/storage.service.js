/* #archivo: /frontend/services/storage.service.js */

/**
 * Guardar token
 */
export function setToken(token) {
    localStorage.setItem("token", token);
}

/**
 * Obtener token
 */
export function getToken() {
    return localStorage.getItem("token");
}

/**
 * Eliminar token
 */
export function removeToken() {
    localStorage.removeItem("token");
}

/**
 * Guardar usuario
 */
export function setUser(user) {
    localStorage.setItem("user", JSON.stringify(user));
}

/**
 * Obtener usuario
 */
export function getUser() {
    const user = localStorage.getItem("user");
    return user ? JSON.parse(user) : null;
}

/**
 * Eliminar usuario
 */
export function removeUser() {
    localStorage.removeItem("user");
}

/* =========================================================
   Ruta solicitada antes de iniciar sesión (CU01 A3)
   Si alguien abre una ruta protegida sin sesión, se guarda para
   volver a ella después del login en lugar de ir siempre al dashboard.
   ========================================================= */

const INTENDED_KEY  = "ut_after_login";
const PUBLIC_ROUTES = ["/", "/login", "/forgot-password", "/reset-password"];

export function rememberIntendedRoute() {
    const { pathname, search } = window.location;
    if (PUBLIC_ROUTES.includes(pathname)) return;
    try { sessionStorage.setItem(INTENDED_KEY, pathname + search); } catch {}
}

export function consumeIntendedRoute() {
    let path = null;
    try {
        path = sessionStorage.getItem(INTENDED_KEY);
        sessionStorage.removeItem(INTENDED_KEY);
    } catch {}
    /* Solo rutas internas: evita redirecciones abiertas (//otro-sitio) */
    if (!path || !path.startsWith("/") || path.startsWith("//")) return null;
    if (PUBLIC_ROUTES.includes(path.split("?")[0])) return null;
    return path;
}
