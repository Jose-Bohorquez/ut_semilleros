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
import { escapeHtml }      from "../../core/escape.js";

const STATUS_MAP = {
    PENDIENTE: { label: "Pendiente",  cls: "badge-pwa-warning" },
    APROBADA:  { label: "Aprobada",   cls: "badge-pwa-success" },
    RECHAZADA: { label: "Rechazada",  cls: "badge-pwa-error"   },
    CANCELADA: { label: "Cancelada",  cls: "badge-pwa-neutral" },
};

let loadedRequests = [];

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
    loadedRequests = requests;

    /* Regla de negocio (Jose, 2026-07-28): mientras el estudiante tenga una
       postulación PENDIENTE o APROBADA, no puede crear otra. */
    const tieneActiva = requests.some(r => r.status === "PENDIENTE" || r.status === "APROBADA");

    const cards = requests.length === 0
        ? `<div class="empty-state">
               <div class="empty-state-icon"><i class="fas fa-paper-plane"></i></div>
               <h3>Sin solicitudes</h3>
               <p>Aún no has enviado solicitudes de ingreso a ningún semillero.</p>
               <button type="button" class="pwa-btn-primary" id="goToSeedbedsBtn" style="margin-top:var(--space-3)">
                   <i class="fas fa-seedling"></i> Ver semilleros
               </button>
           </div>`
        : requests.map(req => {
            const st = STATUS_MAP[req.status] || { label: escapeHtml(req.status), cls: "badge-pwa-neutral" };
            const seedbedName = req.seedbed?.name || `Semillero #${req.seedbed_id}`;
            const date = req.created_at
                ? new Date(req.created_at).toLocaleDateString("es-CO", { day:"2-digit", month:"short", year:"numeric" })
                : "";

            return `
            <div class="pwa-card" data-request-id="${escapeHtml(req.id)}" style="cursor:pointer">
                <div class="card-avatar avatar-green">
                    <i class="fas fa-seedling"></i>
                </div>
                <div class="card-body">
                    <div class="card-title">${escapeHtml(seedbedName)}</div>
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

    /* Mientras haya una postulación PENDIENTE o APROBADA, no se puede crear otra. */
    const bloqueoActiva = tieneActiva ? `
    <div style="display:flex;align-items:center;gap:6px;
                 background:var(--color-warning-light);border:1px solid var(--color-warning-border);
                 border-radius:var(--radius-btn);padding:var(--space-3) var(--space-4);
                 margin-bottom:var(--space-4);font-size:var(--text-sm);color:var(--color-warning-text)">
        <i class="fas fa-lock"></i>
        Ya tienes una postulación ${requests.find(r => r.status === "APROBADA") ? "aprobada" : "pendiente"}.
        No puedes enviar otra mientras esa siga activa. Si te equivocaste, ábrela y cancélala.
    </div>` : "";

    const content = `
    <div style="padding:var(--space-4)" id="requestsPage">
        <h2 style="margin:0 0 var(--space-4);font-size:var(--text-2xl);font-weight:700">
            <i class="fas fa-paper-plane" style="color:var(--color-primary);margin-right:8px"></i>
            Mis Solicitudes
        </h2>
        ${requests.length > 0 ? info : ""}
        ${bloqueoActiva}
        <div id="requestsList">${cards}</div>
    </div>

    <!-- FAB: nueva solicitud (oculto si ya tiene una postulación activa) -->
    ${!tieneActiva ? `
    <button class="pwa-fab" id="newRequestBtn" aria-label="Nueva solicitud" title="Nueva solicitud">
        <i class="fas fa-plus"></i>
    </button>` : ""}

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

                <div class="pwa-form-group">
                    <label class="pwa-label" for="req-program">
                        Programa <span style="color:var(--color-error)">*</span>
                    </label>
                    <select class="pwa-input pwa-select" id="req-program" name="program_id" required disabled>
                        <option value="">Selecciona primero un semillero...</option>
                    </select>
                    <span class="pwa-field-error" id="err-req-program"></span>
                </div>

                <div class="pwa-form-group">
                    <label class="pwa-label" for="req-phone">
                        Teléfono <span style="color:var(--color-error)">*</span>
                    </label>
                    <input class="pwa-input" id="req-phone" name="phone" type="tel" placeholder="Ej: 3001234567" required>
                    <span class="pwa-field-error" id="err-req-phone"></span>
                </div>

                <div class="pwa-form-group">
                    <label class="pwa-label" for="req-message">
                        Mensaje <span style="color:var(--color-error)">*</span>
                    </label>
                    <textarea class="pwa-input" id="req-message" name="message" rows="3"
                              placeholder="Cuéntale al líder por qué quieres unirte..." required></textarea>
                    <span class="pwa-field-error" id="err-req-message"></span>
                </div>

                <button type="submit" class="pwa-btn-primary" id="submitRequestBtn">
                    <i class="fas fa-paper-plane"></i>
                    Enviar solicitud
                </button>
            </form>

        </div>
    </div>

    <!-- Sheet: detalle de solicitud (CU23 paso 4/5) -->
    <div id="requestDetailSheet" style="display:none;position:fixed;inset:0;
         background:rgba(0,0,0,0.5);z-index:1000;align-items:flex-end;justify-content:center">
        <div style="background:var(--color-surface);width:100%;max-width:600px;
                    border-radius:var(--radius-card) var(--radius-card) 0 0;
                    padding:var(--space-6);max-height:85vh;overflow-y:auto;
                    box-shadow:var(--shadow-card);animation:slideUpModal 200ms ease">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--space-5)">
                <h3 id="detailReqTitle" style="margin:0;font-size:var(--text-xl);font-weight:700;color:var(--color-text)"></h3>
                <button id="closeRequestDetailBtn" style="background:none;border:none;
                        color:var(--color-text-muted);cursor:pointer;font-size:1.2rem;
                        width:44px;height:44px;display:flex;align-items:center;justify-content:center;border-radius:50%;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="detailReqBody"></div>
        </div>
    </div>`;

    document.getElementById("app").innerHTML = LayoutView(content);
    initLayoutController();
}

function openRequestDetail(req) {
    const st = STATUS_MAP[req.status] || { label: escapeHtml(req.status), cls: "badge-pwa-neutral" };
    const date = req.created_at
        ? new Date(req.created_at).toLocaleDateString("es-CO", { day: "numeric", month: "long", year: "numeric" })
        : "—";

    document.getElementById("detailReqTitle").textContent = req.seedbed?.name || `Semillero #${req.seedbed_id}`;
    document.getElementById("detailReqBody").innerHTML = `
        <div style="display:flex;flex-direction:column;gap:var(--space-3)">
            <div style="display:flex;justify-content:space-between;align-items:center;
                         padding:var(--space-2) 0;border-bottom:1px solid var(--color-border-light)">
                <span style="font-size:var(--text-sm);color:var(--color-text-muted)">Estado</span>
                <span class="badge-pwa ${st.cls}">${st.label}</span>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center;
                         padding:var(--space-2) 0;border-bottom:1px solid var(--color-border-light)">
                <span style="font-size:var(--text-sm);color:var(--color-text-muted)">Fecha de envío</span>
                <span style="font-size:var(--text-sm);font-weight:500">${escapeHtml(date)}</span>
            </div>
            ${req.program?.name ? `
            <div style="display:flex;justify-content:space-between;align-items:center;
                         padding:var(--space-2) 0;border-bottom:1px solid var(--color-border-light)">
                <span style="font-size:var(--text-sm);color:var(--color-text-muted)">Programa</span>
                <span style="font-size:var(--text-sm);font-weight:500">${escapeHtml(req.program.name)}</span>
            </div>` : ""}

            <h4 style="font-size:var(--text-base);font-weight:600;margin:var(--space-2) 0 0;color:var(--color-text)">
                <i class="fas fa-comment-dots" style="color:var(--color-primary);margin-right:6px"></i>
                Mensaje enviado
            </h4>
            <p style="font-size:var(--text-sm);color:var(--color-text-2);line-height:1.5;margin:0">
                ${escapeHtml(req.message || "Sin mensaje.")}
            </p>

            <h4 style="font-size:var(--text-base);font-weight:600;margin:var(--space-2) 0 0;color:var(--color-text)">
                <i class="fas fa-reply" style="color:var(--color-primary);margin-right:6px"></i>
                Respuesta del líder
            </h4>
            <p style="font-size:var(--text-sm);line-height:1.5;margin:0;
                      ${req.reason ? "color:var(--color-text-2)" : "font-style:italic;color:var(--color-text-muted)"}">
                ${escapeHtml(req.reason || "Aún sin respuesta.")}
            </p>
            ${req.status === "PENDIENTE" ? `
            <button type="button" id="cancelRequestBtn" class="pwa-btn-secondary" data-cancel-id="${escapeHtml(req.id)}"
                    style="margin-top:var(--space-4);color:var(--color-error)">
                <i class="fas fa-ban"></i> Cancelar solicitud
            </button>
            <p style="margin:0;font-size:var(--text-xs);color:var(--color-text-muted)">
                Si te equivocaste, puedes cancelarla y postularte de nuevo (a este u otro semillero).
            </p>` : ""}
        </div>`;

    document.getElementById("requestDetailSheet").style.display = "flex";
}

/* El estudiante cancela SU solicitud pendiente (PUT /requests/{id}/cancel): queda como «Cancelada» y puede postularse de nuevo. */
async function cancelRequest(id) {
    const ask = await Swal.fire({
        icon: "question",
        title: "¿Cancelar tu solicitud?",
        text: "Dejará de estar pendiente y podrás postularte de nuevo, a este u otro semillero.",
        showCancelButton: true,
        confirmButtonText: "Sí, cancelar solicitud",
        cancelButtonText: "No, mantenerla",
        confirmButtonColor: "#dc2626",
    });
    if (!ask.isConfirmed) return;

    try {
        const res = await apiFetch(`/requests/${encodeURIComponent(id)}/cancel`, { method: "PUT" });
        document.getElementById("requestDetailSheet").style.display = "none";
        await Swal.fire({ icon: "success", title: "Solicitud cancelada", text: res?.message || "", timer: 2200, showConfirmButton: false });
        await loadAndRender();
    } catch (error) {
        await Swal.fire({ icon: "error", title: "No se pudo cancelar", text: error.message || "Inténtalo de nuevo." });
        await loadAndRender();
    }
}

function renderError(msg) {
    document.getElementById("app").innerHTML = LayoutView(`
    <div style="padding:var(--space-4)">
        <div class="alert-warning">
            <i class="fas fa-exclamation-triangle"></i>
            <span>Error al cargar solicitudes: ${escapeHtml(msg)}</span>
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

    /* CU22 paso 2: el programa depende del semillero elegido (solo sus
       programas activos). */
    document.getElementById("req-seedbed")?.addEventListener("change", e => {
        const seedbed = loadedSeedbeds.find(s => String(s.id) === e.target.value);
        const programSelect = document.getElementById("req-program");
        if (!seedbed) {
            programSelect.innerHTML = `<option value="">Selecciona primero un semillero...</option>`;
            programSelect.disabled = true;
            return;
        }
        const programs = seedbed.programs || [];
        programSelect.innerHTML = `<option value="">Selecciona un programa...</option>` +
            programs.map(p => `<option value="${escapeHtml(p.id)}">${escapeHtml(p.name)}</option>`).join("");
        programSelect.disabled = false;
    });

    closeBtn?.addEventListener("click", () => {
        modal.style.display = "none";
    });

    modal?.addEventListener("click", e => {
        if (e.target === modal) modal.style.display = "none";
    });

    /* CU23 paso 4/5: tocar una solicitud abre su detalle */
    document.getElementById("requestsList")?.addEventListener("click", e => {
        const card = e.target.closest("[data-request-id]");
        if (!card) return;
        const req = loadedRequests.find(r => String(r.id) === card.dataset.requestId);
        if (req) openRequestDetail(req);
    });

    document.getElementById("goToSeedbedsBtn")?.addEventListener("click", () => {
        navigateTo("/seedbeds");
    });

    const detailSheet = document.getElementById("requestDetailSheet");
    document.getElementById("closeRequestDetailBtn")?.addEventListener("click", () => {
        detailSheet.style.display = "none";
    });
    detailSheet?.addEventListener("click", e => {
        if (e.target === detailSheet) detailSheet.style.display = "none";
        const cancel = e.target.closest("#cancelRequestBtn");
        if (cancel) cancelRequest(cancel.dataset.cancelId);
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
                    program_id: document.getElementById("req-program").value,
                    phone:      document.getElementById("req-phone").value,
                    message:    document.getElementById("req-message").value,
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
            banner.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${escapeHtml(msg)}`;

            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-paper-plane"></i> Enviar solicitud';
        }
    });
}

let loadedSeedbeds = [];

async function loadSeedbedsSelect() {
    const select = document.getElementById("req-seedbed");
    if (!select) return;
    try {
        const data    = await apiFetch("/seedbeds");
        loadedSeedbeds = (data.seedbeds || []).filter(s => s.status === "ACTIVO");
        select.innerHTML = loadedSeedbeds.length
            ? `<option value="">Selecciona un semillero...</option>` +
              loadedSeedbeds.map(s => `<option value="${escapeHtml(s.id)}">${escapeHtml(s.name)}</option>`).join("")
            : `<option value="">No hay semilleros disponibles</option>`;
    } catch {
        select.innerHTML = `<option value="">Error cargando semilleros</option>`;
    }
}
