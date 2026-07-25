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