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
    clearLocalSession();
    setLoginFlash(SESSION_EXPIRED_MSG);   /* CU03 A1 */
    return false;
}


/* =========================================================
   RNF02 — copias sin conexión (ver api.service.js). CU03 paso 3: al cerrar
   sesión se CONSERVAN las copias públicas (listado de semilleros y sus
   objetivos, guardado por usuario); solo se borran todas al rechazar la
   autorización de datos.
   Las copias PERSONALES (mis solicitudes y mis propuestas: teléfono, mensaje y
   respuesta del evaluador) sí se borran al cerrar o vencer la sesión — CU03 paso
   3 pide borrar los datos personales guardados en el dispositivo.
   ========================================================= */

const PERSONAL_OFFLINE_SUFFIXES = ["/requests/my", "/proposals/my"];

export function clearPersonalOfflineCache() {
    try {
        Object.keys(localStorage)
            .filter(k => k.startsWith("offline:") && PERSONAL_OFFLINE_SUFFIXES.some(s => k.endsWith(s)))
            .forEach(k => localStorage.removeItem(k));
    } catch {}
}

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


/* =========================================================
   CU03 — Cerrar sesión
   ========================================================= */

export const SESSION_EXPIRED_MSG = "Su sesión expiró. Inicie sesión de nuevo.";

/* Conversación de SIA: puede tener datos personales; no debe verla quien use
   el dispositivo después (paso 3). */
const SIA_KEYS = ["sia_token", "sia_log", "sia_open", "sia_must_rate"];

/* Paso 3: borra el token y los datos personales guardados en el dispositivo
   (incluidas las copias sin conexión de mis solicitudes y propuestas).
   Conserva la caché pública de semilleros y las preferencias (tema). */
export function clearLocalSession() {
    removeToken(); removeUser(); setTokenExpiry(null);
    clearPersonalOfflineCache();
    try { SIA_KEYS.forEach(k => sessionStorage.removeItem(k)); } catch {}
}

/* Mensaje que muestra el login al llegar (A1 sesión vencida, RF16 «No acepto») */
export function setLoginFlash(msg) {
    try { sessionStorage.setItem("ut_login_flash", msg); } catch {}
}

/* E1: sin conexión el cierre local se completa igual y el token se revoca
   en el servidor en la siguiente conexión (api.service.js → flushPendingRevokes). */
const PENDING_KEY = "pending_revoke";

export function queueRevoke(token) {
    if (!token) return;
    /* Se guarda con el vencimiento del token: pasado ese momento ya no sirve
       y se descarta sin llamar al servidor (un dispositivo que no vuelve a
       tener red no conserva un token útil indefinidamente). */
    let exp = null;
    try { exp = localStorage.getItem("token_expires_at"); } catch {}
    const expMs = Date.parse(exp || "") || Date.now() + 30 * 24 * 3600e3;
    const list = getPendingRevokes().filter(e => e.t !== token);
    list.push({ t: token, exp: expMs });
    setPendingRevokes(list.slice(-5));
}

export function getPendingRevokes() {
    let list = [];
    try { list = JSON.parse(localStorage.getItem(PENDING_KEY) || "[]"); } catch {}
    return (Array.isArray(list) ? list : [])
        .filter(e => e && typeof e.t === "string" && e.exp > Date.now());
}

export function setPendingRevokes(list) {
    try { list.length ? localStorage.setItem(PENDING_KEY, JSON.stringify(list)) : localStorage.removeItem(PENDING_KEY); } catch {}
}
