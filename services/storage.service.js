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
const PUBLIC_ROUTES = ["/", "/login", "/forgot-password", "/reset-password", "/consent"];

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


/* =========================================================
   Vigencia de la sesión (RNF03: el token vence a las 8 h, o 30 d con
   «Recordarme»). Permite que la PWA instalada, al abrirse en "/", entre
   directo al panel si la sesión sigue vigente (CU01 en PWA), sin mostrar el
   login ni gastar un 401 contra la API cuando el token ya venció.
   ========================================================= */

const EXPIRES_KEY = "token_expires_at";

export function setTokenExpiry(isoDate) {
    try { isoDate ? localStorage.setItem(EXPIRES_KEY, isoDate) : localStorage.removeItem(EXPIRES_KEY); } catch {}
}

export function hasValidSession() {
    const token = getToken();
    if (!token || !getUser()) return false;
    let exp = null;
    try { exp = localStorage.getItem(EXPIRES_KEY); } catch {}
    /* Sesiones anteriores al vencimiento (sin fecha guardada): se confía en
       la API, que responde 401 si el token ya no sirve. */
    if (!exp) return true;
    if (Date.parse(exp) > Date.now()) return true;
    removeToken(); removeUser(); setTokenExpiry(null); clearOfflineCache();
    return false;
}


/* =========================================================
   RNF02 — copias sin conexión (ver api.service.js). Se borran todas al
   cerrar sesión o cuando la sesión vence.
   ========================================================= */

export function clearOfflineCache() {
    try {
        Object.keys(localStorage)
            .filter(k => k.startsWith("offline:"))
            .forEach(k => localStorage.removeItem(k));
    } catch {}
}

/* RF16 / RN09: el estudiante debe haber aceptado el tratamiento de datos */
export function needsDataConsent(user = getUser()) {
    return !!user && user.role === "ESTUDIANTE" && !user.data_consent_at;
}
