/* #archivo: frontend/core/app-update.js
   Actualización automática de la aplicación.

   Problema: la PWA se quedaba con la versión que tenía al abrirse (los módulos JS viven en memoria) hasta que la
   persona la cerraba del todo y la volvía a abrir; si solo se cambiaba JS, ni el service worker se enteraba.

   Cómo funciona ahora:
     1. Cada despliegue publica `/version.json` con el identificador de la versión (lo genera
        scripts/build-deploy-bundle.sh). La app guarda la que cargó y la compara al volver a primer plano, al
        recuperar la conexión y cada 10 minutos (siempre sin caché).
     2. Si hay una versión nueva se muestra «Hay una versión nueva» con el botón «Actualizar».
     3. Se aplica sola, sin avisar, en los momentos seguros: (a) al volver a la app después de un minuto en segundo
        plano y (b) en la siguiente navegación entre pantallas (así nunca se pierde lo que alguien está escribiendo).
     4. También se le pide al service worker que busque su propia actualización (cambios de caché, estilos…); cuando el
        nuevo toma el control se trata igual. */

const CHECK_EVERY_MS   = 10 * 60 * 1000;
const MIN_GAP_MS       = 60 * 1000;      // no consultar más de una vez por minuto
const AWAY_RELOAD_MS   = 60 * 1000;      // tiempo en segundo plano tras el cual se recarga sola

let loaded = null;                       // versión con la que se abrió esta pestaña
let lastCheck = 0;
let hiddenAt = 0;
let started = false;

async function fetchVersion() {
    try {
        const r = await fetch("/version.json", { cache: "no-store" });
        if (!r.ok) return null;
        const v = (await r.json())?.version;
        return typeof v === "string" && v ? v : null;
    } catch { return null; }          // sin conexión: se vuelve a intentar luego
}

function banner() {
    if (document.getElementById("updateBanner")) return;
    const el = document.createElement("div");
    el.id = "updateBanner";
    el.setAttribute("role", "status");
    el.innerHTML = `<span><i class="fas fa-circle-up" aria-hidden="true"></i> Hay una versión nueva de la aplicación.</span>
                    <button type="button" id="updateNowBtn">Actualizar</button>`;
    document.body.appendChild(el);
    el.querySelector("#updateNowBtn").addEventListener("click", reloadNow);
}

function markReady() {
    window.__updateReady = true;      // la siguiente navegación recarga (ver core/router.js)
    banner();
}

export function reloadNow() {
    window.location.reload();
}

async function check(force = false) {
    const now = Date.now();
    if (!force && now - lastCheck < MIN_GAP_MS) return;
    lastCheck = now;

    const v = await fetchVersion();
    if (v === null) return;
    if (loaded === null) { loaded = v; return; }
    if (v !== loaded) markReady();
}

export function startUpdateWatch(registration) {
    if (started) return;
    started = true;

    check(true);
    setInterval(() => { registration?.update?.().catch?.(() => {}); check(true); }, CHECK_EVERY_MS);

    document.addEventListener("visibilitychange", () => {
        if (document.visibilityState === "hidden") { hiddenAt = Date.now(); return; }
        registration?.update?.().catch?.(() => {});
        check().then(() => {
            // volvió después de un rato y hay versión nueva: es un buen momento, no hay nada a medias
            if (window.__updateReady && hiddenAt && Date.now() - hiddenAt >= AWAY_RELOAD_MS) reloadNow();
        });
    });
    window.addEventListener("online", () => check(true));

    // un service worker nuevo tomó el control: hay archivos nuevos del shell (CSS, íconos…)
    if (navigator.serviceWorker?.controller) {
        let had = true;
        navigator.serviceWorker.addEventListener("controllerchange", () => { if (had) markReady(); });
    }
}
