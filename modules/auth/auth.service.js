/* #archivo: /frontend/modules/auth/auth.service.js */

import { setToken, setUser, getToken, clearLocalSession, queueRevoke } from "../../services/storage.service.js";
import { unsubscribeFromPush } from "../../services/push.service.js";
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
 * CU03 — Cerrar sesión.
 * Paso 2: cancela las notificaciones push de ESTE dispositivo (si no, quien lo
 *         use después seguiría recibiendo las del usuario anterior) y revoca
 *         el token en el servidor.
 * Paso 3: borra token y datos personales del dispositivo; conserva la caché
 *         pública de semilleros.
 * E1:     sin conexión, el cierre local se completa igual y el token queda en
 *         cola para revocarse en la siguiente conexión.
 */
export async function logout() {

    const token = getToken();

    /* Máximo 3 s: un service worker que no responde no puede bloquear la salida */
    await Promise.race([unsubscribeFromPush(), new Promise(r => setTimeout(r, 3000))]).catch(() => {});

    try {
        await apiLogout();
    } catch (error) {
        if (error.status === 0) queueRevoke(token);
        else console.warn("Error cerrando sesión en backend:", error);
    }

    clearLocalSession();
}
