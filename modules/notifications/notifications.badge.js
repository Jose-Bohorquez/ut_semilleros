/* #archivo: frontend/modules/notifications/notifications.badge.js
   Gestión del badge (contador) de la campana en el navbar.
   Se exporta para ser llamado desde:
   - layout.controller.js (carga inicial en cada ruta)
   - notifications.module.js (después de marcar leídas)
   ─────────────────────────────────────────────────────── */

import { apiFetch } from "../../services/api.service.js";
import { getToken } from "../../services/storage.service.js";

let _pollInterval = null;

/* Actualiza el DOM del badge directamente */
export function updateBellBadge(count) {
    const badge = document.getElementById("notifBadge");
    if (!badge) return;
    const n = Math.max(0, count);
    badge.textContent = n > 99 ? "99+" : String(n);
    badge.style.display = n > 0 ? "flex" : "none";
}

/* Llama al backend y actualiza el badge */
export async function refreshBadge() {
    if (!getToken()) return;
    try {
        const data = await apiFetch("/notifications/unread-count");
        updateBellBadge(data.count || 0);
    } catch {
        /* silencioso — no bloquear la UI si la llamada falla */
    }
}

/* Inicia polling cada 60 s — llamar solo una vez al iniciar el layout */
export function startBadgePolling() {
    if (_pollInterval) return; /* ya activo */
    refreshBadge();
    _pollInterval = setInterval(refreshBadge, 60_000);
}

/* Detiene el polling (al cerrar sesión) */
export function stopBadgePolling() {
    if (_pollInterval) {
        clearInterval(_pollInterval);
        _pollInterval = null;
    }
}
