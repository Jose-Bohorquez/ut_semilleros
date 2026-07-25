/** # archivo: /frontend/core/guards.js **/

import { getToken, getUser } from "../services/storage.service.js";

/**
 * Verifica que el usuario esté autenticado
 */
export function requireAuth() {

    const token = getToken();

    if (!token) {

        window.location.href = "/";
        return false;
    }

    return true;
}


/**
 * Verifica que el usuario tenga un rol permitido
 */
export function requireRole(allowedRoles = []) {

    const user = getUser();

    if (!user) {
        window.location.href = "/";
        return false;
    }

    if (!allowedRoles.includes(user.role)) {

        alert("No tienes permisos para acceder a esta sección.");

        window.location.href = "/dashboard";
        return false;
    }

    return true;
}