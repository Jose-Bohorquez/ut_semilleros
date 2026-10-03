/* #archivo: frontend/core/push-card.js
   Panel «Notificaciones en este dispositivo» (perfil de todos los roles).
   El permiso de notificaciones se pide AQUÍ, con un toque del usuario: iOS lo exige y Chrome bloquea en silencio
   los permisos pedidos sin gesto. Desde aquí también se envía una notificación de prueba. */

import { getPushState, subscribeToPush, unsubscribeFromPush, sendTestPush } from "../services/push.service.js";
import { escapeHtml } from "./escape.js";

const MESSAGES = {
    unsupported: { icon: "fa-bell-slash", tone: "muted",
        text: "Este navegador o dispositivo no permite notificaciones push. Puedes seguir viendo tus avisos en la campana de la app." },
    "needs-install": { icon: "fa-mobile-screen", tone: "warn",
        text: "En iPhone y iPad las notificaciones solo funcionan con la app instalada: abre esta página en Safari, toca Compartir → «Añadir a pantalla de inicio» y abre la app desde su icono. Después vuelve aquí para activarlas." },
    denied: { icon: "fa-ban", tone: "warn",
        text: "Bloqueaste las notificaciones de este sitio. Para activarlas, entra a los ajustes del navegador o del teléfono (Ajustes → Notificaciones → Semilleros), permite las notificaciones y vuelve aquí." },
    off: { icon: "fa-bell", tone: "muted",
        text: "Recibe un aviso en este dispositivo cuando respondan tu solicitud, evalúen tu propuesta o te escriban un anuncio, aunque la app esté cerrada." },
    on: { icon: "fa-bell", tone: "ok",
        text: "Activadas en este dispositivo. Te avisaremos aunque la app esté cerrada." },
};

const TONES = { ok: "var(--color-success, #15803d)", warn: "var(--color-warning, #b45309)", muted: "var(--color-text-muted)" };

export async function renderPushCard(container) {
    if (!container) return;

    const st = await getPushState();
    const m  = MESSAGES[st.state] || MESSAGES.unsupported;

    container.innerHTML = `
    <div style="background:var(--color-surface);border:1px solid var(--color-border);
                border-radius:var(--radius-card);padding:var(--space-5);margin-top:var(--space-5)">
        <h3 style="margin:0 0 var(--space-3);font-size:var(--text-lg);font-weight:600;color:var(--color-text)">
            <i class="fas fa-bell" style="color:var(--color-primary);margin-right:6px"></i>
            Notificaciones en este dispositivo
        </h3>
        <p id="pushMsg" style="display:flex;gap:10px;align-items:flex-start;margin:0 0 var(--space-4);
                                font-size:var(--text-sm);line-height:1.5;color:${TONES[m.tone]}" role="status">
            <i class="fas ${m.icon}" style="margin-top:3px"></i><span>${escapeHtml(m.text)}</span>
        </p>
        <div id="pushResult" role="status" style="display:none;margin:0 0 var(--space-3);font-size:var(--text-sm)"></div>
        <div style="display:flex;gap:var(--space-2);flex-wrap:wrap">
            ${st.state === "off" ? `<button type="button" class="pwa-btn-primary" id="pushEnableBtn"><i class="fas fa-bell"></i> Activar notificaciones</button>` : ""}
            ${st.state === "on" ? `
                <button type="button" class="pwa-btn-secondary" id="pushTestBtn"><i class="fas fa-paper-plane"></i> Enviar notificación de prueba</button>
                <button type="button" class="pwa-btn-secondary" id="pushDisableBtn"><i class="fas fa-bell-slash"></i> Desactivar</button>` : ""}
        </div>
    </div>`;

    const result = container.querySelector("#pushResult");
    const say = (ok, text) => {
        result.style.display = "";
        result.style.color = ok ? TONES.ok : "var(--color-error, #b91c1c)";
        result.textContent = text;
    };
    const busy = (btn, on, label) => { if (!btn) return; btn.disabled = on; if (label) btn.dataset.l ??= btn.innerHTML; btn.innerHTML = on ? `<i class="fas fa-spinner fa-spin"></i> ${label}` : (btn.dataset.l || btn.innerHTML); };

    container.querySelector("#pushEnableBtn")?.addEventListener("click", async (e) => {
        const btn = e.currentTarget;
        busy(btn, true, "Activando…");
        try {
            const sub = await subscribeToPush({ prompt: true });   // aquí SÍ se pide el permiso: hay un toque del usuario
            if (!sub) {
                const after = await getPushState();
                if (after.state === "denied") { await renderPushCard(container); return; }
                busy(btn, false);
                say(false, "No se concedió el permiso. Inténtalo de nuevo y elige «Permitir» cuando el navegador pregunte.");
                return;
            }
            await renderPushCard(container);
            container.querySelector("#pushResult").style.display = "";
            container.querySelector("#pushResult").style.color = TONES.ok;
            container.querySelector("#pushResult").textContent = "Notificaciones activadas. Pulsa «Enviar notificación de prueba» para comprobarlo.";
        } catch (err) {
            busy(btn, false);
            say(false, `No se pudo activar: ${err.message || "error desconocido"}`);
        }
    });

    container.querySelector("#pushTestBtn")?.addEventListener("click", async (e) => {
        const btn = e.currentTarget;
        busy(btn, true, "Enviando…");
        try {
            const res = await sendTestPush();
            say(true, res.message || "Enviada. Debería aparecer en unos segundos.");
        } catch (err) {
            const p = err.payload || {};
            say(false, [p.message || err.message, ...(p.errors || [])].filter(Boolean).join(" · "));
        } finally { busy(btn, false); }
    });

    container.querySelector("#pushDisableBtn")?.addEventListener("click", async (e) => {
        busy(e.currentTarget, true, "Desactivando…");
        await unsubscribeFromPush();
        await renderPushCard(container);
    });
}

/* ── Aviso compacto en la pantalla de Notificaciones ─────────
   Solo aparece si el dispositivo puede recibir push y aún no está activado. */
export async function showPushBanner() {
    const st = await getPushState();
    if (st.state !== "off") return;

    const host = document.querySelector("#content");
    if (!host || host.querySelector("#pushBanner")) return;

    const b = document.createElement("div");
    b.id = "pushBanner";
    b.setAttribute("role", "status");
    b.style.cssText = "display:flex;gap:var(--space-3);align-items:center;justify-content:space-between;flex-wrap:wrap;" +
        "margin:0 0 var(--space-4);padding:var(--space-3) var(--space-4);border-radius:var(--radius-card);" +
        "background:var(--color-primary-light, #fee2e2);color:var(--color-text)";
    b.innerHTML = `
        <span style="font-size:var(--text-sm)"><i class="fas fa-bell" style="margin-right:6px;color:var(--color-primary)"></i>
        Activa los avisos para enterarte aunque la app esté cerrada.</span>
        <button type="button" class="pwa-btn-primary" id="pushBannerBtn" style="width:auto;padding:8px 16px"><i class="fas fa-bell"></i> Activar</button>`;
    host.prepend(b);

    b.querySelector("#pushBannerBtn").addEventListener("click", async (e) => {
        const btn = e.currentTarget;
        btn.disabled = true;
        try {
            const sub = await subscribeToPush({ prompt: true });      // toque del usuario → se puede pedir el permiso
            if (sub) { b.remove(); window.Swal?.fire({ icon: "success", title: "Notificaciones activadas", timer: 1800, showConfirmButton: false }); }
            else { btn.disabled = false; window.Swal?.fire({ icon: "info", title: "No se activaron", text: "Elige «Permitir» cuando el navegador pregunte, o revisa los permisos del sitio." }); }
        } catch (err) { btn.disabled = false; window.Swal?.fire({ icon: "error", title: "No se pudo activar", text: err.message || "" }); }
    });
}
