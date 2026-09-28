/* #archivo: /frontend/core/offline-banner.js
   RNF02 — aviso de que se muestra la última información consultada sin
   conexión. role=status: se anuncia sin interrumpir. */

let banner = null;

function fmt(iso) {
    const d = new Date(iso);
    if (isNaN(d)) return "";
    return d.toLocaleString("es-CO", { day: "numeric", month: "short", hour: "2-digit", minute: "2-digit" });
}

export function showOfflineBanner(savedAt) {
    if (!banner) {
        banner = document.createElement("div");
        banner.id = "offline-banner";
        banner.className = "offline-banner";
        banner.setAttribute("role", "status");
        document.body.appendChild(banner);
        window.addEventListener("online", () => { banner.hidden = true; });
    }
    const when = fmt(savedAt);
    banner.innerHTML = '<i class="fas fa-wifi" aria-hidden="true"></i>';
    banner.append(` Sin conexión · información guardada${when ? ` el ${when}` : ""}`);
    banner.hidden = false;
}
