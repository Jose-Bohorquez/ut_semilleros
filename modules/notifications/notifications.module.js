/* #archivo: frontend/modules/notifications/notifications.module.js
   Centro de notificaciones — todos los roles
   ──────────────────────────────────────────────────────────────── */

import { apiFetch }           from "../../services/api.service.js";
import { getUser }             from "../../services/storage.service.js";
import { LayoutView }          from "../../layout/layout.view.js";
import { initLayoutController }from "../../layout/layout.controller.js";
import { updateBellBadge }     from "./notifications.badge.js";

const ROLE_CAN_SEND = ["ADMIN_SISTEMA", "ADMINISTRATIVO", "LIDER_SEMILLERO"];

const TYPE_MAP = {
    ANUNCIO:       { label: "Anuncio",       icon: "fa-bullhorn",    cls: "badge-pwa-info"    },
    RECORDATORIO:  { label: "Recordatorio",  icon: "fa-bell",        cls: "badge-pwa-warning" },
};

export const notificationsModule = {
    async init() {
        renderSkeleton();
        initLayoutController();
        await loadAndRender();
    }
};

/* ── Skeleton ──────────────────────────────────────────── */

function renderSkeleton() {
    const user    = getUser();
    const canSend = ROLE_CAN_SEND.includes(user?.role);
    document.getElementById("app").innerHTML = LayoutView(`
    <div style="padding:var(--space-4)">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--space-4)">
            <h2 style="margin:0;font-size:var(--text-2xl);font-weight:700">Notificaciones</h2>
            ${canSend ? `<button class="btn btn-primary btn-sm" id="newNotifBtn">
                <i class="fas fa-plus"></i> Nueva
            </button>` : ""}
        </div>
        <div class="skeleton skeleton-card"></div>
        <div class="skeleton skeleton-card"></div>
        <div class="skeleton skeleton-card"></div>
    </div>`);
}

/* ── Main load ─────────────────────────────────────────── */

async function loadAndRender(activeTab = "received") {
    const user    = getUser();
    const canSend = ROLE_CAN_SEND.includes(user?.role);

    let received = [], sent = [];

    try {
        const r = await apiFetch("/notifications");
        received = r.notifications || [];
    } catch (e) { /* handled below */ }

    if (canSend) {
        try {
            const s = await apiFetch("/notifications/sent");
            sent = s.notifications || [];
        } catch (e) { /* handled below */ }
    }

    renderPage(received, sent, canSend, activeTab);

    /* Actualiza badge de campana */
    const unread = received.filter(n => !n.is_read).length;
    updateBellBadge(unread);
}

/* ── Render ─────────────────────────────────────────────  */

function renderPage(received, sent, canSend, activeTab) {
    const tabs = `
    <div style="display:flex;border-bottom:2px solid var(--color-border);
                 margin-bottom:var(--space-4);gap:0">
        <button class="tab-btn ${activeTab==="received"?"tab-active":""}"
                data-tab="received"
                style="flex:1;padding:var(--space-3);font-size:var(--text-sm);
                       font-weight:600;background:none;border:none;cursor:pointer;
                       color:${activeTab==="received"?"var(--color-primary)":"var(--color-text-muted)"};
                       border-bottom:${activeTab==="received"?"2px solid var(--color-primary)":"2px solid transparent"};
                       margin-bottom:-2px">
            <i class="fas fa-inbox" style="margin-right:6px"></i>
            Recibidas
            ${received.filter(n=>!n.is_read).length > 0
                ? `<span style="background:var(--color-error);color:#fff;border-radius:999px;
                                font-size:10px;padding:1px 6px;margin-left:4px">
                       ${received.filter(n=>!n.is_read).length}
                   </span>` : ""}
        </button>
        ${canSend ? `
        <button class="tab-btn ${activeTab==="sent"?"tab-active":""}"
                data-tab="sent"
                style="flex:1;padding:var(--space-3);font-size:var(--text-sm);
                       font-weight:600;background:none;border:none;cursor:pointer;
                       color:${activeTab==="sent"?"var(--color-primary)":"var(--color-text-muted)"};
                       border-bottom:${activeTab==="sent"?"2px solid var(--color-primary)":"2px solid transparent"};
                       margin-bottom:-2px">
            <i class="fas fa-paper-plane" style="margin-right:6px"></i>
            Enviadas (${sent.length})
        </button>` : ""}
    </div>`;

    const list = activeTab === "received" ? received : sent;

    const cards = list.length === 0
        ? `<div class="empty-state">
               <div class="empty-state-icon"><i class="fas fa-bell-slash"></i></div>
               <h3>${activeTab==="received" ? "Sin notificaciones" : "Sin envíos"}</h3>
               <p>${activeTab==="received"
                   ? "No tienes notificaciones por el momento."
                   : "Aún no has enviado ninguna notificación."}</p>
           </div>`
        : list.map(n => renderCard(n, activeTab)).join("");

    const markAllBtn = (activeTab === "received" && received.some(n => !n.is_read))
        ? `<button class="btn btn-ghost btn-sm" id="markAllBtn" style="margin-left:auto">
               <i class="fas fa-check-double"></i> Marcar todas
           </button>`
        : "";

    const content = `
    <div style="padding:var(--space-4)" id="notifPage">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--space-4)">
            <h2 style="margin:0;font-size:var(--text-2xl);font-weight:700">
                <i class="fas fa-bell" style="color:var(--color-primary);margin-right:8px"></i>
                Notificaciones
            </h2>
            <div style="display:flex;gap:var(--space-2);align-items:center">
                ${markAllBtn}
                ${canSend ? `<button class="btn btn-primary btn-sm" id="newNotifBtn">
                    <i class="fas fa-plus"></i> Nueva
                </button>` : ""}
            </div>
        </div>
        ${tabs}
        <div id="notifList">${cards}</div>
    </div>

    <!-- Bottom sheet: nueva notificación -->
    <div id="notifSheet" style="display:none;position:fixed;inset:0;
         background:rgba(0,0,0,0.5);z-index:1000;align-items:flex-end;justify-content:center">
        <div id="notifSheetInner"
             style="background:var(--color-surface);width:100%;max-width:640px;
                    border-radius:var(--radius-card) var(--radius-card) 0 0;
                    padding:var(--space-6);max-height:90vh;overflow-y:auto;
                    box-shadow:var(--shadow-card);animation:slideUpModal 200ms ease">
            ${renderComposer(getUser())}
        </div>
    </div>`;

    document.getElementById("app").innerHTML = LayoutView(content);
    initLayoutController();
    bindEvents(received, sent, canSend, activeTab);
}

/* ── Card individual ───────────────────────────────────── */

function renderCard(n, tab) {
    const tm = TYPE_MAP[n.type] || TYPE_MAP.ANUNCIO;
    const date = new Date(n.created_at).toLocaleString("es-CO", {
        day:"2-digit", month:"short", year:"numeric", hour:"2-digit", minute:"2-digit"
    });
    const unreadDot = (!n.is_read && tab === "received")
        ? `<div style="width:8px;height:8px;border-radius:50%;
                        background:var(--color-primary);flex-shrink:0;margin-top:6px"></div>`
        : "";

    const target = targetLabel(n.target_type, n.target_value);

    return `
    <div class="pwa-card notif-card ${!n.is_read && tab==="received"?"notif-unread":""}"
         data-id="${n.id}" data-read="${n.is_read}"
         style="flex-direction:column;align-items:flex-start;gap:var(--space-2);
                ${!n.is_read && tab==="received" ? "border-left:3px solid var(--color-primary);" : ""}">

        <div style="display:flex;align-items:flex-start;gap:var(--space-3);width:100%">
            <div class="card-avatar ${tm.cls === "badge-pwa-info" ? "avatar-blue" : "avatar-yellow"}"
                 style="flex-shrink:0;font-size:1rem">
                <i class="fas ${tm.icon}"></i>
            </div>
            <div style="flex:1;min-width:0">
                <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap">
                    <span style="font-weight:600;font-size:var(--text-base);color:var(--color-text)">
                        ${n.title}
                    </span>
                    <span class="badge-pwa ${tm.cls}" style="font-size:10px">${tm.label}</span>
                </div>
                <p style="margin:4px 0 0;font-size:var(--text-sm);color:var(--color-text-2);
                           line-height:1.5;word-break:break-word">
                    ${n.message}
                </p>
                ${n.link ? `
                <a href="${n.link}" target="_blank" rel="noopener"
                   style="display:inline-flex;align-items:center;gap:4px;
                          margin-top:6px;font-size:var(--text-xs);
                          color:var(--color-primary);text-decoration:none">
                    <i class="fas fa-external-link-alt"></i> Abrir enlace
                </a>` : ""}
            </div>
            ${unreadDot}
        </div>

        <div style="display:flex;align-items:center;gap:var(--space-3);
                     padding-top:var(--space-2);border-top:1px solid var(--color-border-light);
                     width:100%;font-size:var(--text-xs);color:var(--color-text-faint)">
            <span><i class="fas fa-user" style="margin-right:3px"></i>${n.sender_name}</span>
            <span><i class="fas fa-clock" style="margin-right:3px"></i>${date}</span>
            ${tab === "sent" ? `<span title="Destinatarios"><i class="fas fa-users" style="margin-right:3px"></i>${target}</span>` : ""}
        </div>
    </div>`;
}

/* ── Compositor de nueva notificación ────────────────────  */

function renderComposer(user) {
    const isLider = user?.role === "LIDER_SEMILLERO";
    const isAdmin = ["ADMIN_SISTEMA", "ADMINISTRATIVO"].includes(user?.role);

    const targetOptions = isLider
        ? `<option value="SEEDBED">Mi semillero</option>
           <option value="USER">Un integrante específico</option>`
        : isAdmin
        ? `<option value="ALL">Todos los usuarios</option>
           <option value="ROLE">Por rol</option>
           <option value="SEEDBED">Un semillero</option>
           <option value="USER">Un usuario específico</option>`
        : "";

    return `
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--space-5)">
        <h3 style="margin:0;font-size:var(--text-xl);font-weight:700;color:var(--color-text)">
            <i class="fas fa-paper-plane" style="color:var(--color-primary);margin-right:8px"></i>
            Nueva notificación
        </h3>
        <button id="closeNotifSheet" style="background:none;border:none;cursor:pointer;
                color:var(--color-text-muted);font-size:1.2rem;padding:var(--space-2);
                border-radius:50%;min-height:unset">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <div id="composerBanner" style="display:none;margin-bottom:var(--space-4)"></div>

    <form id="notifForm">

        <div class="pwa-form-group">
            <label class="pwa-label">Tipo <span style="color:var(--color-error)">*</span></label>
            <div style="display:flex;gap:var(--space-3)">
                <label style="flex:1;display:flex;align-items:center;gap:8px;
                               padding:var(--space-3);border:1.5px solid var(--color-border);
                               border-radius:var(--radius-btn);cursor:pointer;
                               font-size:var(--text-sm);font-weight:500" id="typeAnnounce">
                    <input type="radio" name="type" value="ANUNCIO" checked
                           style="accent-color:var(--color-primary)">
                    <i class="fas fa-bullhorn" style="color:var(--color-info)"></i> Anuncio
                </label>
                <label style="flex:1;display:flex;align-items:center;gap:8px;
                               padding:var(--space-3);border:1.5px solid var(--color-border);
                               border-radius:var(--radius-btn);cursor:pointer;
                               font-size:var(--text-sm);font-weight:500" id="typeReminder">
                    <input type="radio" name="type" value="RECORDATORIO"
                           style="accent-color:var(--color-primary)">
                    <i class="fas fa-bell" style="color:var(--color-warning)"></i> Recordatorio
                </label>
            </div>
        </div>

        <div class="pwa-form-group">
            <label class="pwa-label" for="notif-title">
                Título <span style="color:var(--color-error)">*</span>
            </label>
            <input class="pwa-input" id="notif-title" name="title"
                   type="text" placeholder="Ej: Reunión semanal del semillero" required>
            <span class="pwa-field-error" id="err-notif-title"></span>
        </div>

        <div class="pwa-form-group">
            <label class="pwa-label" for="notif-message">
                Mensaje <span style="color:var(--color-error)">*</span>
            </label>
            <textarea class="pwa-input" id="notif-message" name="message"
                      rows="4" placeholder="Escribe el contenido de la notificación..."
                      style="resize:vertical" required></textarea>
            <span class="pwa-field-error" id="err-notif-message"></span>
        </div>

        <div class="pwa-form-group">
            <label class="pwa-label" for="notif-link">
                Enlace opcional
                <span style="font-weight:400;color:var(--color-text-muted)">(Meet, Zoom, etc.)</span>
            </label>
            <input class="pwa-input" id="notif-link" name="link"
                   type="url" placeholder="https://meet.google.com/...">
        </div>

        <div class="pwa-form-group">
            <label class="pwa-label" for="notif-target-type">
                Destinatarios <span style="color:var(--color-error)">*</span>
            </label>
            <select class="pwa-input pwa-select" id="notif-target-type" name="target_type" required>
                ${targetOptions}
            </select>
        </div>

        <!-- Valor del target (dinámico) -->
        <div class="pwa-form-group" id="targetValueGroup" style="display:none">
            <label class="pwa-label" id="targetValueLabel">Valor</label>
            <select class="pwa-input pwa-select" id="notif-target-value" name="target_value">
                <option value="">Cargando...</option>
            </select>
            <span class="pwa-field-error" id="err-notif-target"></span>
        </div>

        <button type="submit" class="pwa-btn-primary" id="sendNotifBtn">
            <i class="fas fa-paper-plane"></i>
            Enviar notificación
        </button>

    </form>`;
}

/* ── Eventos ───────────────────────────────────────────── */

function bindEvents(received, sent, canSend, activeTab) {

    /* Tabs */
    document.querySelectorAll(".tab-btn").forEach(btn => {
        btn.addEventListener("click", () => {
            const tab = btn.dataset.tab;
            loadAndRender(tab);
        });
    });

    /* Marcar todas como leídas */
    document.getElementById("markAllBtn")?.addEventListener("click", async () => {
        try {
            await apiFetch("/notifications/read-all", { method: "PUT" });
            await loadAndRender("received");
        } catch (e) { /* silent */ }
    });

    /* Marcar una como leída al tocar */
    document.querySelectorAll(".notif-card[data-read='false']").forEach(card => {
        card.addEventListener("click", async () => {
            const id = card.dataset.id;
            if (!id) return;
            try {
                await apiFetch(`/notifications/${id}/read`, { method: "PUT" });
                card.dataset.read = "true";
                card.style.borderLeft = "";
                card.classList.remove("notif-unread");
                card.querySelector("[style*='background:var(--color-primary)']")?.remove();
                /* Actualizar badge */
                const badge = document.getElementById("notifBadge");
                if (badge) {
                    const cur = parseInt(badge.textContent) || 0;
                    const nxt = Math.max(0, cur - 1);
                    badge.textContent = nxt;
                    badge.style.display = nxt > 0 ? "flex" : "none";
                }
            } catch (e) { /* silent */ }
        });
    });

    /* Abrir sheet de nueva notificación */
    const sheet = document.getElementById("notifSheet");

    document.getElementById("newNotifBtn")?.addEventListener("click", () => {
        sheet.style.display = "flex";
        setupTargetDynamic();
    });

    document.getElementById("closeNotifSheet")?.addEventListener("click", () => {
        sheet.style.display = "none";
    });

    sheet?.addEventListener("click", e => {
        if (e.target === sheet) sheet.style.display = "none";
    });

    /* Cambio en tipo de destino → carga opciones */
    document.getElementById("notif-target-type")?.addEventListener("change", e => {
        loadTargetValues(e.target.value);
    });

    /* Submit nueva notificación */
    document.getElementById("notifForm")?.addEventListener("submit", async e => {
        e.preventDefault();
        const btn = document.getElementById("sendNotifBtn");
        const banner = document.getElementById("composerBanner");

        const title    = document.getElementById("notif-title").value.trim();
        const message  = document.getElementById("notif-message").value.trim();
        const link     = document.getElementById("notif-link").value.trim();
        const type     = document.querySelector('input[name="type"]:checked')?.value;
        const targetT  = document.getElementById("notif-target-type").value;
        const targetV  = document.getElementById("notif-target-value")?.value || null;

        /* Validar */
        let hasErr = false;
        if (!title)   { showComposerErr("title",   "El título es obligatorio");   hasErr = true; }
        if (!message) { showComposerErr("message",  "El mensaje es obligatorio");  hasErr = true; }
        if (hasErr) return;

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';
        banner.style.display = "none";

        const payload = { title, message, type, target_type: targetT };
        if (targetV) payload.target_value = targetV;
        if (link)    payload.link = link;

        try {
            await apiFetch("/notifications", { method: "POST", body: JSON.stringify(payload) });
            sheet.style.display = "none";
            Swal.fire({
                icon:  "success", title: "Notificación enviada",
                timer: 2000, showConfirmButton: false, toast: true, position: "top-end",
            });
            await loadAndRender("sent");
        } catch (err) {
            showComposerBanner("error", err.message || "Error al enviar");
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-paper-plane"></i> Enviar notificación';
        }
    });
}

/* ── Carga opciones del target dinámicamente ────────────── */

function setupTargetDynamic() {
    const select = document.getElementById("notif-target-type");
    if (select) loadTargetValues(select.value);
}

async function loadTargetValues(targetType) {
    const group = document.getElementById("targetValueGroup");
    const label = document.getElementById("targetValueLabel");
    const sel   = document.getElementById("notif-target-value");
    if (!group || !sel) return;

    if (targetType === "ALL") {
        group.style.display = "none";
        return;
    }

    group.style.display = "block";

    if (targetType === "ROLE") {
        label.textContent = "Rol";
        sel.innerHTML = `
            <option value="ESTUDIANTE">Estudiantes</option>
            <option value="LIDER_SEMILLERO">Líderes de Semillero</option>
            <option value="ADMINISTRATIVO">Administrativos</option>
            <option value="ADMIN_SISTEMA">Administradores del Sistema</option>`;
        return;
    }

    if (targetType === "SEEDBED") {
        label.textContent = "Semillero";
        sel.innerHTML = `<option value="">Cargando...</option>`;
        try {
            const user = getUser();
            let seedbeds = (await apiFetch("/seedbeds")).seedbeds || [];
            /* LIDER_SEMILLERO solo puede ver sus propios semilleros (backend valida también) */
            sel.innerHTML = seedbeds.map(s =>
                `<option value="${s.id}">${s.name}</option>`
            ).join("") || `<option value="">Sin semilleros disponibles</option>`;
        } catch { sel.innerHTML = `<option value="">Error cargando</option>`; }
        return;
    }

    if (targetType === "USER") {
        label.textContent = "Usuario";
        sel.innerHTML = `<option value="">Cargando...</option>`;
        try {
            const users = (await apiFetch("/users")).users || [];
            sel.innerHTML = users.map(u =>
                `<option value="${u.id}">${u.name} (${u.role})</option>`
            ).join("") || `<option value="">Sin usuarios</option>`;
        } catch { sel.innerHTML = `<option value="">Sin acceso a la lista</option>`; }
    }
}

/* ── Helpers ───────────────────────────────────────────── */

function targetLabel(type, value) {
    const labels = {
        ALL:     "Todos los usuarios",
        ROLE:    `Rol: ${value || "—"}`,
        SEEDBED: `Semillero #${value}`,
        USER:    `Usuario #${value}`,
    };
    return labels[type] || type;
}

function showComposerErr(field, msg) {
    const el = document.getElementById(`err-notif-${field}`);
    if (el) el.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${msg}`;
}

function showComposerBanner(type, html) {
    const el = document.getElementById("composerBanner");
    if (!el) return;
    const c = type === "error"
        ? { bg: "var(--color-error-light)",   border: "var(--color-error-border)",   color: "var(--color-error-text)"   }
        : { bg: "var(--color-success-light)", border: "var(--color-success-border)", color: "var(--color-success-text)" };
    el.style.cssText = `display:flex;align-items:center;gap:8px;padding:12px 14px;
        border-radius:var(--radius-btn);font-size:var(--text-sm);
        background:${c.bg};border:1px solid ${c.border};color:${c.color}`;
    el.innerHTML = html;
}
