/* #archivo: /frontend/modules/auth/auth.service.js */

import { setToken, setUser, removeToken, removeUser } from "../../services/storage.service.js";
import { login as apiLogin, logout as apiLogout } from "../../services/api.service.js";

/**
 * Servicio de autenticación del módulo AUTH
 * Maneja login y logout del usuario
 */


/**
 * #funcion: login
 * Envía credenciales al backend Laravel
 */
export async function login(email, password) {

    try {

        const data = await apiLogin(email, password);

        // Guardar token de sesión
        setToken(data.token);

        // Guardar usuario autenticado
        setUser(data.user);

        return true;

    } catch (error) {

        console.error("Error en login:", error);

        return false;
    }

}


/**
 * #funcion: logout
 * Cierra sesión del usuario
 */
export async function logout() {

    try {

        await apiLogout();

    } catch (error) {

        console.warn("Error cerrando sesión en backend:", error);

    }

    // eliminar sesión local
    removeToken();
    removeUser();

}