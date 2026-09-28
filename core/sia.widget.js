/* =========================================================
   #archivo: /frontend/core/sia.widget.js
   SIA — Sistema Integrado de Asistencia (RF17 propuesto, 2026-09-28).
   Botones flotantes en TODAS las pantallas (login, panel y PWA):
     · WhatsApp → solo reporte de errores (bugs).
     · SIA      → mini chat con IA que responde dudas de uso del sistema.
   Se monta una sola vez en <body>, fuera de #app, así sobrevive a los
   cambios de ruta del SPA. La calificación (1–5 caritas + comentario) se
   pide al finalizar la conversación.
   ========================================================= */

import { apiFetch }   from "../services/api.service.js";
import { escapeHtml } from "./escape.js";

const WHATSAPP = "573178773186";
const TOKEN_KEY = "sia_token";
const LOG_KEY   = "sia_log";
const MAX_CHARS = 500;
const FACES = [
    { v: 1, e: "😞", t: "Muy mala" },
    { v: 2, e: "😕", t: "Mala" },
    { v: 3, e: "😐", t: "Regular" },
    { v: 4, e: "🙂", t: "Buena" },
    { v: 5, e: "😄", t: "Excelente" },
];

const store = {
    get: (k, d = null) => { try { const v = sessionStorage.getItem(k); return v === null ? d : JSON.parse(v); } catch { return d; } },
    set: (k, v) => { try { sessionStorage.setItem(k, JSON.stringify(v)); } catch {} },
    del: k => { try { sessionStorage.removeItem(k); } catch {} },
};

/* Markdown mínimo y seguro: se escapa TODO y luego se permiten **negrita** y saltos de línea. */
function format(text) {
    return escapeHtml(text)
        .replace(/\*\*(.+?)\*\*/g, "<strong>$1</strong>")
        .replace(/\n/g, "<br>");
}

function whatsappUrl() {
    const msg = `Hola, quiero reportar un error en el Sistema de Semilleros IDEAD.\nPantalla: ${location.pathname}\n¿Qué estaba haciendo?: \n¿Qué pasó?: `;
    return `https://wa.me/${WHATSAPP}?text=${encodeURIComponent(msg)}`;
}

export function mountSia() {
    if (document.getElementById("sia-root")) return;

    const root = document.createElement("div");
    root.id = "sia-root";
    root.innerHTML = `
      <div class="sia-fabs">
        <button type="button" class="sia-fab sia-fab-ai" id="sia-open" aria-haspopup="dialog" aria-expanded="false"
                aria-controls="sia-panel" title="SIA · Asistente del sistema">
          <i class="fas fa-robot" aria-hidden="true"></i><span>SIA</span>
        </button>
        <a class="sia-fab sia-fab-wa" id="sia-wa" href="${whatsappUrl()}" target="_blank" rel="noopener"
           aria-label="Reportar un error por WhatsApp" title="Reportar un error (solo bugs)">
          <i class="fab fa-whatsapp" aria-hidden="true"></i><span>Reportar bug</span>
        </a>
      </div>
      <section class="sia-panel" id="sia-panel" role="dialog" aria-modal="false" aria-labelledby="sia-title" hidden>
        <header class="sia-head">
          <div class="sia-head-avatar" aria-hidden="true"><i class="fas fa-robot"></i></div>
          <div class="sia-head-text">
            <h2 id="sia-title">SIA</h2>
            <p>Sistema Integrado de Asistencia</p>
          </div>
          <button type="button" class="sia-link-btn" id="sia-finish">Finalizar</button>
          <button type="button" class="sia-icon-btn" id="sia-close" aria-label="Cerrar SIA"><i class="fas fa-times" aria-hidden="true"></i></button>
        </header>
        <div class="sia-body" id="sia-body"></div>
        <form class="sia-form" id="sia-form" autocomplete="off">
          <label class="sr-only" for="sia-input">Escribe tu pregunta para SIA</label>
          <textarea id="sia-input" rows="1" maxlength="${MAX_CHARS}" placeholder="Escribe tu pregunta…"></textarea>
          <button type="submit" class="sia-send" id="sia-send" aria-label="Enviar pregunta"><i class="fas fa-paper-plane" aria-hidden="true"></i></button>
          <span class="sia-count" id="sia-count" aria-hidden="true">0/${MAX_CHARS}</span>
        </form>
      </section>`;
    document.body.appendChild(root);

    const $ = id => document.getElementById(id);
    const panel = $("sia-panel"), body = $("sia-body"), form = $("sia-form"), input = $("sia-input");
    let busy = false;

    /* ── Posición: encima de la barra inferior de la PWA cuando existe ── */
    const syncOffset = () => {
        const nav = document.querySelector(".pwa-bottom-nav");
        const withNav = !!nav && getComputedStyle(nav).display !== "none";
        root.classList.toggle("sia--with-nav", withNav);
        $("sia-wa").href = whatsappUrl();
    };
    new MutationObserver(syncOffset).observe(document.getElementById("app") || document.body, { childList: true });
    window.addEventListener("popstate", syncOffset);
    syncOffset();

    /* ── Render de la conversación ── */
    const log = () => store.get(LOG_KEY, []);
    const bubble = (m) => `<div class="sia-msg sia-msg-${m.r}">${m.r === "a" ? format(m.t) : escapeHtml(m.t)}</div>`;
    const intro = `<div class="sia-msg sia-msg-a">Hola, soy <strong>SIA</strong>. Te ayudo con dudas sobre el uso del Sistema de Semilleros: iniciar sesión, postularte, propuestas, roles y más.<br><span class="sia-hint">No compartas contraseñas ni datos personales. Para reportar un error usa el botón verde de WhatsApp.</span></div>`;

    function renderChat() {
        form.hidden = false;
        $("sia-finish").hidden = !store.get(TOKEN_KEY);
        body.setAttribute("role", "log");
        body.setAttribute("aria-live", "polite");
        body.innerHTML = intro + log().map(bubble).join("");
        body.scrollTop = body.scrollHeight;
    }

    function push(r, t) {
        const l = log(); l.push({ r, t }); store.set(LOG_KEY, l);
        body.insertAdjacentHTML("beforeend", bubble({ r, t }));
        body.scrollTop = body.scrollHeight;
    }

    /* ── Calificación al finalizar ── */
    function renderRating(note = "") {
        form.hidden = true;
        $("sia-finish").hidden = true;
        body.removeAttribute("aria-live");
        body.setAttribute("role", "region");
        body.innerHTML = `
          <div class="sia-rate">
            ${note ? `<p class="sia-rate-note">${escapeHtml(note)}</p>` : ""}
            <h3 id="sia-rate-title">¿Qué tal te respondió SIA?</h3>
            <p class="sia-rate-sub">¿Fue útil la información? ¿Te ayudó a resolver tu duda?</p>
            <div class="sia-faces" role="radiogroup" aria-labelledby="sia-rate-title">
              ${FACES.map(f => `<button type="button" class="sia-face" role="radio" aria-checked="false" data-v="${f.v}" aria-label="${f.t}" title="${f.t}"><span aria-hidden="true">${f.e}</span><small>${f.t}</small></button>`).join("")}
            </div>
            <label class="sia-rate-label" for="sia-feedback">Cuéntanos más (opcional)</label>
            <textarea id="sia-feedback" rows="4" maxlength="2000" placeholder="¿Qué estuvo bien o qué le faltó a la respuesta?"></textarea>
            <div class="sia-rate-actions">
              <button type="button" class="sia-link-btn" id="sia-skip">Omitir</button>
              <button type="button" class="btn btn-primary" id="sia-rate-send" disabled>Enviar calificación</button>
            </div>
          </div>`;
        let rating = null;
        body.querySelectorAll(".sia-face").forEach(b => b.addEventListener("click", () => {
            rating = Number(b.dataset.v);
            body.querySelectorAll(".sia-face").forEach(x => x.setAttribute("aria-checked", String(x === b)));
            $("sia-rate-send").disabled = false;
        }));
        $("sia-skip").addEventListener("click", () => finish({ skipped: true }));
        $("sia-rate-send").addEventListener("click", () => finish({ rating, feedback: $("sia-feedback").value.trim() || null }));
        body.querySelector(".sia-face")?.focus();
    }

    async function finish(payload) {
        const token = store.get(TOKEN_KEY);
        if (token) {
            try { await apiFetch("/sia/close", { method: "POST", body: JSON.stringify({ token, ...payload }), auth: true }); }
            catch { /* si falla, igual se reinicia la conversación en el cliente */ }
        }
        store.del(TOKEN_KEY); store.del(LOG_KEY);
        body.setAttribute("role", "status");
        body.innerHTML = `<div class="sia-thanks"><span aria-hidden="true">${payload.skipped ? "👋" : "💚"}</span><p>${payload.skipped ? "Conversación finalizada." : "¡Gracias! Tu opinión nos ayuda a mejorar SIA."}</p><button type="button" class="btn btn-primary" id="sia-new">Nueva pregunta</button></div>`;
        $("sia-new").addEventListener("click", () => { renderChat(); input.focus(); });
    }

    /* ── Abrir / cerrar ── */
    function open() {
        panel.hidden = false;
        $("sia-open").setAttribute("aria-expanded", "true");
        renderChat();
        input.focus();
    }
    function close() {
        /* Si hubo conversación sin calificar, se pide la calificación antes de cerrar. */
        if (store.get(TOKEN_KEY) && body.getAttribute("role") === "log") return renderRating();
        panel.hidden = true;
        $("sia-open").setAttribute("aria-expanded", "false");
        $("sia-open").focus();
    }
    $("sia-open").addEventListener("click", () => (panel.hidden ? open() : close()));
    $("sia-close").addEventListener("click", close);
    $("sia-finish").addEventListener("click", () => renderRating());
    panel.addEventListener("keydown", e => { if (e.key === "Escape") close(); });

    /* ── Enviar pregunta ── */
    input.addEventListener("input", () => {
        $("sia-count").textContent = `${input.value.length}/${MAX_CHARS}`;
        input.style.height = "auto";
        input.style.height = Math.min(input.scrollHeight, 120) + "px";
    });
    input.addEventListener("keydown", e => { if (e.key === "Enter" && !e.shiftKey) { e.preventDefault(); form.requestSubmit(); } });

    form.addEventListener("submit", async e => {
        e.preventDefault();
        const text = input.value.trim();
        if (!text || busy) return;
        busy = true; $("sia-send").disabled = true;
        push("u", text);
        input.value = ""; input.dispatchEvent(new Event("input"));
        body.insertAdjacentHTML("beforeend", `<div class="sia-msg sia-msg-a sia-typing" id="sia-typing" aria-label="SIA está escribiendo"><span></span><span></span><span></span></div>`);
        body.scrollTop = body.scrollHeight;
        try {
            const r = await apiFetch("/sia/chat", { method: "POST", body: JSON.stringify({ message: text, token: store.get(TOKEN_KEY), page: location.pathname }) });
            store.set(TOKEN_KEY, r.token);
            $("sia-typing")?.remove();
            push("a", r.answer);
            $("sia-finish").hidden = false;
            if (r.remaining === 0) renderRating("Llegaste al máximo de preguntas de esta conversación.");
        } catch (err) {
            $("sia-typing")?.remove();
            if (err.payload?.token) store.set(TOKEN_KEY, err.payload.token);
            if (err.payload?.must_close) return renderRating(err.message);
            push("a", err.status === 0 ? "No hay conexión a internet. Revisa tu red e inténtalo de nuevo." : (err.message || "SIA no pudo responder. Intenta de nuevo."));
        } finally {
            busy = false; $("sia-send").disabled = false;
        }
    });
}
