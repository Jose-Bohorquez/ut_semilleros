/* #archivo: frontend/core/pwa-install.js
   Botón «Instalar app» (debajo de SIA y WhatsApp): que el usuario instale la PWA con un toque, sin tener que
   buscar la opción en el menú del navegador.

   Cómo funciona, según el dispositivo:
     · Android / Chrome / Edge (y escritorio): el navegador avisa con `beforeinstallprompt`; se guarda el evento y,
       al tocar el botón, se muestra el diálogo nativo de instalación («Instalar»).
     · iPhone / iPad: Apple no permite instalar por código; el botón explica los dos toques (Compartir →
       «Añadir a pantalla de inicio»).
     · Android sin aviso disponible (el navegador ya lo mostró y se descartó): se explican los pasos del menú.
     · Ya instalada (se abre en modo «standalone») o navegador que no instala: el botón no aparece.
   Importante: el evento `beforeinstallprompt` se dispara una sola vez y temprano, por eso este módulo lo escucha
   en cuanto se carga (lo importa el widget, que se monta al abrir la app). */

let deferred = null;                 // evento guardado de beforeinstallprompt
let installed = false;
const subscribers = new Set();

const notify = () => subscribers.forEach(fn => { try { fn(); } catch { /* un suscriptor roto no afecta a los demás */ } });

window.addEventListener("beforeinstallprompt", (e) => {
    e.preventDefault();               // evita la mini-barra automática; se ofrece desde nuestro botón
    deferred = e;
    notify();
});

window.addEventListener("appinstalled", () => {
    deferred = null;
    installed = true;
    notify();
    window.Swal?.fire({ icon: "success", title: "¡App instalada!", text: "Ya puedes abrirla desde el icono de tu pantalla de inicio.", timer: 3000, showConfirmButton: false });
});

const isStandalone = () => window.matchMedia?.("(display-mode: standalone)")?.matches === true || window.navigator.standalone === true;
const isIOS        = () => /iphone|ipad|ipod/i.test(navigator.userAgent) || (navigator.platform === "MacIntel" && navigator.maxTouchPoints > 1);
const isAndroid    = () => /android/i.test(navigator.userAgent);

/** "hidden" | "prompt" (diálogo nativo) | "ios" (instrucciones de Safari) | "manual" (instrucciones del menú) */
export function installMode() {
    if (installed || isStandalone()) return "hidden";
    if (deferred) return "prompt";
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
        </ol>`,
};

async function onClick() {
    const mode = installMode();

    if (mode === "prompt" && deferred) {
        const ev = deferred;
        deferred = null;                       // el evento solo se puede usar una vez
        notify();
        try {
            await ev.prompt();
            await ev.userChoice;               // si aceptó, `appinstalled` mostrará el aviso
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
    window.matchMedia?.("(display-mode: standalone)")?.addEventListener?.("change", sync);
}
