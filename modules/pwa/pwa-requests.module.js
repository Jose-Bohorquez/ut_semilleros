/* #archivo: frontend/modules/pwa/pwa-requests.module.js
   Solicitudes de ingreso a semilleros — ROL: ESTUDIANTE
   Reglas:
   - Solo ve SUS PROPIAS solicitudes (GET /requests/my)
   - Puede CREAR nuevas solicitudes
   - NO puede aprobar, rechazar ni cambiar estado
   - Estado lo cambia: Administrador o Líder de Semillero
   ─────────────────────────────────────────────────────── */

import { apiFetch }           from "../../services/api.service.js";
import { getUser }             from "../../services/storage.service.js";
import { LayoutView }          from "../../layout/layout.view.js";
import { initLayoutController }from "../../layout/layout.controller.js";
import { navigateTo }          from "../../core/router.js";

const STATUS_MAP = {
    PENDIENTE: { label: "Pendiente",  cls: "badge-pwa-warning" },
    APROBADA:  { label: "Aprobada",   cls: "badge-pwa-success" },
    RECHAZADA: { label: "Rechazada",  cls: "badge-pwa-error"   },
};

export const pwaRequestsModule = {

    async init() {
        renderSkeleton();
        initLayoutController();
        await loadAndRender();
    }
};

/* ── Skeleton ──────────────────────────────────────────── */

function renderSkeleton() {
    document.getElementById("app").innerHTML = LayoutView(`
    <div style="padding:var(--space-4)">
        <h2 style="margin:0 0 var(--space-4);font-size:var(--text-2xl);font-weight:700">
            Mis Solicitudes
        </h2>
        <div class="skeleton skeleton-card"></div>
        <div class="skeleton skeleton-card"></div>
        <div class="skeleton skeleton-card"></div>
    </div>`);
}

/* ── Load & render ─────────────────────────────────────── */

async function loadAndRender() {
    let requests = [];
    try {
        const data = await apiFetch("/requests/my");
        requests = data.requests || [];
    } catch (err) {
        renderError(err.message);
        return;
    }
    renderList(requests);
    bindEvents();
}

function renderList(requests) {
    const cards = requests.length === 0
        ? `<div class="empty-state">
               <div class="empty-state-icon"><i class="fas fa-paper-plane"></i></div>
               <h3>Sin solicitudes</h3>
               <p>Aún no has enviado solicitudes de ingreso a ningún semillero.</p>
           </div>`
        : requests.map(req => {
            const st = STATUS_MAP[req.status] || { label: req.status, cls: "badge-pwa-neutral" };
            const seedbedName = req.seedbed?.name || `Semillero #${req.seedbed_id}`;
            const date = req.created_at
                ? new Date(req.created_at).toLocaleDateString("es-CO", { day:"2-digit", month:"short", year:"numeric" })
                : "";

            return `
            <div class="pwa-card">
                <div class="card-avatar avatar-green">
                    <i class="fas fa-seedling"></i>
                </div>
                <div class="card-body">
                    <div class="card-title">${seedbedName}</div>
                    <div class="card-subtitle">
                        <span class="badge-pwa ${st.cls}">${st.label}</span>
                    </div>
                    <div class="card-meta">
                        <i class="fas fa-calendar-alt" style="margin-right:4px"></i>${date}
                    </div>
                </div>
                <i class="fas fa-chevron-right card-arrow"></i>
            </div>`;
        }).join("");

    const info = `
    <div style="display:flex;align-items:center;gap:6px;
                 background:var(--color-info-light);border:1px solid var(--color-info-border);
                 border-radius:var(--radius-btn);padding:var(--space-3) var(--space-4);
                 margin-bottom:var(--space-4);font-size:var(--text-sm);color:var(--color-info)">
        <i class="fas fa-info-circle"></i>
        La aprobación o rechazo es realizada por el Administrador o Líder de Semillero.
    </div>`;

    const content = `
    <div style="padding:var(--space-4)" id="requestsPage">
        <h2 style="margin:0 0 var(--space-4);font-size:var(--text-2xl);font-weight:700">
            <i class="fas fa-paper-plane" style="color:var(--color-primary);margin-right:8px"></i>
            Mis Solicitudes
        </h2>
        ${requests.length > 0 ? info : ""}
        <div id="requestsList">${cards}</div>
    </div>

    <!-- FAB: nueva solicitud -->
    <button class="pwa-fab" id="newRequestBtn" aria-label="Nueva solicitud" title="Nueva solicitud">
        <i class="fas fa-plus"></i>
    </button>

    <!-- Modal: nueva solicitud -->
    <div id="newRequestModal" style="display:none;position:fixed;inset:0;
         background:rgba(0,0,0,0.5);z-index:1000;align-items:flex-end;justify-content:center">
        <div style="background:var(--color-surface);width:100%;max-width:600px;
                    border-radius:var(--radius-card) var(--radius-card) 0 0;
                    padding:var(--space-6);max-height:85vh;overflow-y:auto;
                    box-shadow:var(--shadow-card);animation:slideUpModal 200ms ease">

            <div style="display:flex;align-items:center;justify-content:space-between;
                         margin-bottom:var(--space-5)">
                <h3 style="margin:0;font-size:var(--text-xl);font-weight:700;color:var(--color-text)">
                    <i class="fas fa-plus-circle" style="color:var(--color-primary);margin-right:8px"></i>
                    Nueva Solicitud
                </h3>
                <button id="closeRequestModal" style="background:none;border:none;
                        color:var(--color-text-muted);cursor:pointer;font-size:1.2rem;
                        width:44px;height:44px;display:flex;align-items:center;justify-content:center;border-radius:50%;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <p style="font-size:var(--text-sm);color:var(--color-text-muted);margin:0 0 var(--space-5)">
                Selecciona el semillero al que quieres solicitar ingreso.
            </p>

            <div id="modalErrorBanner" style="display:none;margin-bottom:var(--space-4)"></div>

            <form id="newRequestForm">
                <div class="pwa-form-group">
                    <label class="pwa-label" for="req-seedbed">
                        Semillero <span style="color:var(--color-error)">*</span>
                    </label>
                    <select class="pwa-input pwa-select" id="req-seedbed" name="seedbed_id" required>
                        <option value="">Cargando semilleros...</option>
                    </select>
                    <span class="pwa-field-error" id="err-req-seedbed"></span>
                </div>

                <button type="submit" class="pwa-btn-primary" id="submitRequestBtn">
                    <i class="fas fa-paper-plane"></i>
                    Enviar solicitud
                </button>
            </form>

        </div>
    </div>`;

    document.getElementById("app").innerHTML = LayoutView(content);
    initLayoutController();
}

function renderError(msg) {
    document.getElementById("app").innerHTML = LayoutView(`
    <div style="padding:var(--space-4)">
        <div class="alert-warning">
            <i class="fas fa-exclamation-triangle"></i>
            <span>Error al cargar solicitudes: ${msg}</span>
        </div>
    </div>`);
    initLayoutController();
}

/* ── Eventos ───────────────────────────────────────────── */

function bindEvents() {

    const modal   = document.getElementById("newRequestModal");
    const openBtn = document.getElementById("newRequestBtn");
    const closeBtn= document.getElementById("closeRequestModal");

    openBtn?.addEventListener("click", async () => {
        modal.style.display = "flex";
        await loadSeedbedsSelect();
    });

    closeBtn?.addEventListener("click", () => {
        modal.style.display = "none";
    });

    modal?.addEventListener("click", e => {
        if (e.target === modal) modal.style.display = "none";
    });

    document.getElementById("newRequestForm")?.addEventListener("submit", async e => {
        e.preventDefault();
        const btn    = document.getElementById("submitRequestBtn");
        const errEl  = document.getElementById("err-req-seedbed");
        const banner = document.getElementById("modalErrorBanner");

        const seedbedId = document.getElementById("req-seedbed").value;
        if (!seedbedId) {
            errEl.innerHTML = '<i class="fas fa-exclamation-circle"></i> Selecciona un semillero';
            return;
        }
        errEl.textContent = "";

        const user = getUser();
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';

        try {
            await apiFetch("/requests", {
                method: "POST",
                body: JSON.stringify({
                    user_id:    user.id,
                    seedbed_id: parseInt(seedbedId),
                    status:     "PENDIENTE",
                }),
            });

            modal.style.display = "none";

            Swal.fire({
                icon:             "success",
                title:            "Solicitud enviada",
                text:             "Tu solicitud está en estado Pendiente. El líder o administrador la revisará.",
                confirmButtonText:"Entendido",
                confirmButtonColor:"#ef4444",
            });

            /* Recargar lista */
            await loadAndRender();

        } catch (err) {
            const msg = err.message || "Error al enviar la solicitud";
            banner.style.cssText = `display:flex;align-items:center;gap:8px;padding:12px 14px;
                border-radius:var(--radius-btn);font-size:var(--text-sm);
                background:var(--color-error-light);border:1px solid var(--color-error-border);
                color:var(--color-error-text)`;
            banner.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${msg}`;

            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-paper-plane"></i> Enviar solicitud';
        }
    });
}

async function loadSeedbedsSelect() {
    const select = document.getElementById("req-seedbed");
    if (!select) return;
    try {
        const data    = await apiFetch("/seedbeds");
        const activos = (data.seedbeds || []).filter(s => s.status === "ACTIVO");
        select.innerHTML = activos.length
            ? `<option value="">Selecciona un semillero...</option>` +
              activos.map(s => `<option value="${s.id}">${s.name}</option>`).join("")
            : `<option value="">No hay semilleros disponibles</option>`;
    } catch {
        select.innerHTML = `<option value="">Error cargando semilleros</option>`;
    }
}
