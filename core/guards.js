/** # archivo: /frontend/core/guards.js **/

import { getToken, getUser, rememberIntendedRoute, needsDataConsent } from "../services/storage.service.js";

/**
 * Verifica que el usuario esté autenticado
 */
export function requireAuth() {

    const token = getToken();

    if (!token) {

        rememberIntendedRoute();   /* CU01 A3: volver aquí tras el login */
        window.location.href = "/";
        return false;
    }

    /* RF16 / CU02 A1: sin autorización de datos no se usa la aplicación */
    if (needsDataConsent()) {
        window.location.href = "/consent";
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

        /* alert() nativo bloqueaba el hilo del navegador esperando que
           alguien le diera clic en "Aceptar" — en la práctica se sentía
           como que la pantalla se congelaba (Jose, 2026-07-28). Un toast
           no bloqueante + redirección inmediata resuelve lo mismo. */
        if (typeof Swal !== "undefined") {
            Swal.fire({
                icon: "warning",
                title: "Sin permisos",
                text: "No tienes permisos para acceder a esta sección.",
                timer: 2500,
                showConfirmButton: false,
                toast: true,
                position: "top-end",
            });
        }

        window.location.href = "/dashboard";
        return false;
    }

    return true;
}