/* #archivo: frontend/core/pwa-install.js
   Botón «Instalar app» (debajo de SIA y WhatsApp): que el usuario instale la PWA con un toque, sin tener que
   buscar la opción en el menú del navegador.

   Cómo funciona, según el dispositivo:
     · Android / Chrome / Edge (y escritorio): el navegador avisa con `beforeinstallprompt`; se guarda el evento y,
       al tocar el botón, se muestra el diálogo nativo de instalación («Instalar»).
     · iPhone / iPad: Apple no permite instalar por código; el botón explica los dos toques (Compartir →
       «Añadir a pantalla de inicio»).
     · Android sin aviso disponible (el navegador ya lo mostró y se descartó): se explican los pasos del menú.
     · Ya instalada: el botón NO aparece. Se sabe porque (a) la app se está ejecutando como app instalada, en CUALQUIER
       modo de pantalla (standalone, fullscreen, minimal-ui: el manifest pide fullscreen primero, y antes solo se
       miraba standalone, por eso el botón seguía saliendo dentro de la app instalada), (b) el navegador avisó con
       `appinstalled` o el usuario aceptó instalar, (c) la app ya se abrió alguna vez instalada (se recuerda en el
       dispositivo: así la pestaña normal del navegador tampoco ofrece instalar de nuevo), o (d) Chrome lo informa por
       `getInstalledRelatedApps()` (el manifest declara la app web en `related_applications`).
       Si la desinstalan, Chrome vuelve a lanzar `beforeinstallprompt` y se olvida la marca.
   Importante: `beforeinstallprompt` se dispara una sola vez y temprano, por eso este módulo lo escucha en cuanto se
   carga (lo importa el widget, que se monta al abrir la app). */

const FLAG = "pwa_installed";

let deferred = null;                 // evento guardado de beforeinstallprompt
const subscribers = new Set();
const notify = () => subscribers.forEach(fn => { try { fn(); } catch { /* un suscriptor roto no afecta a los demás */ } });

const store = {
    get: () => { try { return localStorage.getItem(FLAG) === "1"; } catch { return false; } },
    set: () => { try { localStorage.setItem(FLAG, "1"); } catch { /* sin almacenamiento: solo pierde la memoria */ } },
    clear: () => { try { localStorage.removeItem(FLAG); } catch { /* idem */ } },
};

/** ¿Se está ejecutando como app instalada (no en una pestaña del navegador)? Cualquier modo sin barra del navegador. */
export function runningInstalled() {
    const mm = q => window.matchMedia?.(q)?.matches === true;
    return mm("(display-mode: standalone)") || mm("(display-mode: fullscreen)") || mm("(display-mode: minimal-ui)")
        || mm("(display-mode: window-controls-overlay)") || window.navigator.standalone === true
        || (document.referrer || "").startsWith("android-app://");
}

if (runningInstalled()) store.set();                 // abierta como app: queda recordado también para la pestaña del navegador

window.addEventListener("beforeinstallprompt", (e) => {
    e.preventDefault();               // evita la mini-barra automática; se ofrece desde nuestro botón
    deferred = e;
    store.clear();                    // el navegador solo ofrece instalar si NO está instalada (p. ej. la desinstalaron)
    notify();
});

window.addEventListener("appinstalled", () => {
    deferred = null;
    store.set();
    notify();
    window.Swal?.fire({ icon: "success", title: "¡App instalada!", text: "Ya puedes abrirla desde el icono de tu pantalla de inicio.", timer: 3000, showConfirmButton: false });
});

// Chrome (Android/escritorio) puede decir si la app web ya está instalada en este dispositivo.
navigator.getInstalledRelatedApps?.().then(apps => {
    if (apps && apps.length) { store.set(); notify(); }
}).catch(() => { /* no soportado o sin permiso: se ignora */ });

const isIOS     = () => /iphone|ipad|ipod/i.test(navigator.userAgent) || (navigator.platform === "MacIntel" && navigator.maxTouchPoints > 1);
const isAndroid = () => /android/i.test(navigator.userAgent);

/** "hidden" | "prompt" (diálogo nativo) | "ios" (instrucciones de Safari) | "manual" (instrucciones del menú) */
export function installMode() {
    if (runningInstalled()) return "hidden";
    if (deferred) return "prompt";                    // el navegador confirma que se puede instalar ahora
    if (store.get()) return "hidden";                 // ya instalada (la abrió así, o aceptó instalar)
    if (isIOS()) return "ios";
    if (isAndroid()) return "manual";
    return "hidden";
}

const STEPS = {
    ios: `
        <p style="margin:0 0 10px">Para instalar la app en tu iPhone o iPad (desde <b>Safari</b>):</p>
        <ol style="text-align:left;line-height:1.7;padding-left:20px;margin:0">
            <li>Toca el botón <b>Compartir</b> <i class="fas fa-arrow-up-from-bracket"></i> (abajo, en la barra de Safari).</li>
            <li>Desliza y elige <b>«Añadir a pantalla de inicio»</b>.</li>
            <li>Toca <b>«Añadir»</b>. Ya puedes abrirla desde su icono.</li>
        </ol>
        <p style="font-size:.85em;color:#6b7280;margin:12px 0 0">Si estás en Chrome u otro navegador, abre esta página en Safari primero.</p>`,
    manual: `
        <p style="margin:0 0 10px">Para instalar la app en tu celular:</p>
        <ol style="text-align:left;line-height:1.7;padding-left:20px;margin:0">
            <li>Abre el menú del navegador <b>⋮</b> (arriba a la derecha).</li>
            <li>Elige <b>«Instalar aplicación»</b> o <b>«Añadir a pantalla de inicio»</b>.</li>
            <li>Confirma con <b>«Instalar»</b>.</li>
        </ol>
        <p style="font-size:.85em;color:#6b7280;margin:12px 0 0">¿Ya la instalaste? Ábrela desde el icono de tu pantalla de inicio y este botón dejará de aparecer.</p>`,
};

async function onClick() {
    const mode = installMode();

    if (mode === "prompt" && deferred) {
        const ev = deferred;
        deferred = null;                       // el evento solo se puede usar una vez
        notify();
        try {
            await ev.prompt();
            const choice = await ev.userChoice;
            if (choice?.outcome === "accepted") { store.set(); notify(); }   // `appinstalled` mostrará el aviso
        } catch { /* el usuario cerró el diálogo */ }
        return;
    }

    if (mode === "ios" || mode === "manual") {
        const html = STEPS[mode];
        if (window.Swal) window.Swal.fire({ title: "Instalar la app", html, confirmButtonText: "Entendido", confirmButtonColor: "#dc2626" });
        else alert(html.replace(/<[^>]+>/g, " "));
    }
}

/** Conecta el botón flotante: lo muestra u oculta según el dispositivo y atiende el toque. */
export function mountInstallButton(btn) {
    if (!btn) return;
    const sync = () => { btn.hidden = installMode() === "hidden"; };
    subscribers.add(sync);
    sync();
    btn.addEventListener("click", onClick);
    // al cambiar entre navegador y modo instalado (p. ej. tras instalar sin recargar)
    for (const q of ["standalone", "fullscreen", "minimal-ui"]) {
        window.matchMedia?.(`(display-mode: ${q})`)?.addEventListener?.("change", () => { if (runningInstalled()) store.set(); sync(); });
    }
}
