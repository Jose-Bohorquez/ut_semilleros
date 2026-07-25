/* #archivo: frontend/modules/pwa/pwa-proposals.module.js
   Propuestas de investigación — ROL: ESTUDIANTE
   Reglas:
   - Solo ve SUS PROPIAS propuestas (GET /proposals/my)
   - Puede CREAR nuevas propuestas
   - Puede EDITAR solo si status === 'PENDIENTE'
   - NO puede aprobar, rechazar ni cambiar estado
   - La aprobación corresponde al Administrador o Líder de Semillero
   ─────────────────────────────────────────────────────────────── */

import { apiFetch }           from "../../services/api.service.js";
import { getUser }             from "../../services/storage.service.js";
import { LayoutView }          from "../../layout/layout.view.js";
import { initLayoutController }from "../../layout/layout.controller.js";

const STATUS_MAP = {
    PENDIENTE: { label: "Pendiente",  cls: "badge-pwa-warning", editable: true  },
    APROBADA:  { label: "Aprobada",   cls: "badge-pwa-success", editable: false },
    RECHAZADA: { label: "Rechazada",  cls: "badge-pwa-error",   editable: false },
};

let proposalsCache = [];

export const pwaProposalsModule = {

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
            Mis Propuestas
        </h2>
        <div class="skeleton skeleton-card"></div>
        <div class="skeleton skeleton-card"></div>
    </div>`);
}

/* ── Load & render ─────────────────────────────────────── */

async function loadAndRender() {
    try {
        const data   = await apiFetch("/proposals/my");
        proposalsCache = data.proposals || [];
    } catch (err) {
        renderError(err.message);
        return;
    }
    renderList(proposalsCache);
    bindListEvents();
}

function renderList(proposals) {
    const cards = proposals.length === 0
        ? `<div class="empty-state">
               <div class="empty-state-icon"><i class="fas fa-lightbulb"></i></div>
               <h3>Sin propuestas</h3>
               <p>Aún no has creado ninguna propuesta de investigación.</p>
           </div>`
        : proposals.map(p => {
            const st = STATUS_MAP[p.status] || { label: p.status, cls: "badge-pwa-neutral", editable: false };
            const date = p.created_at
                ? new Date(p.created_at).toLocaleDateString("es-CO", { day:"2-digit", month:"short", year:"numeric" })
                : "";
            const editBtn = st.editable
                ? `<button class="btn btn-sm btn-secondary editProposalBtn"
                           data-id="${p.id}" style="margin-top:var(--space-2)">
                       <i class="fas fa-edit"></i> Editar
                   </button>`
                : `<p style="font-size:var(--text-xs);color:var(--color-text-faint);margin-top:var(--space-1)">
                       <i class="fas fa-lock"></i> No editable (${st.label.toLowerCase()})
                   </p>`;

            return `
            <div class="pwa-card" style="flex-direction:column;align-items:flex-start;gap:var(--space-2)">
                <div style="display:flex;align-items:center;gap:var(--space-3);width:100%">
                    <div class="card-avatar avatar-purple">
                        <i class="fas fa-lightbulb"></i>
                    </div>
                    <div class="card-body">
                        <div class="card-title">${p.title}</div>
                        <div class="card-subtitle">
                            <span class="badge-pwa ${st.cls}">${st.label}</span>
                        </div>
                        <div class="card-meta">
                            <i class="fas fa-calendar-alt" style="margin-right:4px"></i>${date}
                        </div>
                    </div>
                </div>
                ${p.description ? `<p style="font-size:var(--text-sm);color:var(--color-text-2);
                    padding:0 var(--space-2);margin:0;line-height:1.5;
                    display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden">
                    ${p.description}</p>` : ""}
                <div style="padding:0 var(--space-2)">${editBtn}</div>
            </div>`;
        }).join("");

    const notice = proposals.some(p => p.status === "PENDIENTE")
        ? `<div style="display:flex;align-items:center;gap:6px;
                 background:var(--color-warning-light);border:1px solid var(--color-warning-border);
                 border-radius:var(--radius-btn);padding:var(--space-3) var(--space-4);
                 margin-bottom:var(--space-4);font-size:var(--text-sm);color:var(--color-warning-text)">
               <i class="fas fa-info-circle"></i>
               Puedes editar tus propuestas mientras estén en estado <strong>Pendiente</strong>.
           </div>`
        : "";

    const content = `
    <div style="padding:var(--space-4)" id="proposalsPage">
        <h2 style="margin:0 0 var(--space-4);font-size:var(--text-2xl);font-weight:700">
            <i class="fas fa-lightbulb" style="color:var(--color-primary);margin-right:8px"></i>
            Mis Propuestas
        </h2>
        ${proposals.length > 0 ? notice : ""}
        <div id="proposalsList">${cards}</div>
    </div>

    <!-- FAB: nueva propuesta -->
    <button class="pwa-fab" id="newProposalBtn" aria-label="Nueva propuesta">
        <i class="fas fa-plus"></i>
    </button>

    <!-- Bottom sheet: crear / editar propuesta -->
    <div id="proposalSheet" style="display:none;position:fixed;inset:0;
         background:rgba(0,0,0,0.5);z-index:1000;align-items:flex-end;justify-content:center">
        <div style="background:var(--color-surface);width:100%;max-width:600px;
                    border-radius:var(--radius-card) var(--radius-card) 0 0;
                    padding:var(--space-6);max-height:90vh;overflow-y:auto;
                    box-shadow:var(--shadow-card);animation:slideUpModal 200ms ease">

            <div style="display:flex;align-items:center;justify-content:space-between;
                         margin-bottom:var(--space-5)">
                <h3 id="sheetTitle" style="margin:0;font-size:var(--text-xl);font-weight:700;color:var(--color-text)">
                    Nueva Propuesta
                </h3>
                <button id="closeProposalSheet" style="background:none;border:none;
                        color:var(--color-text-muted);cursor:pointer;font-size:1.2rem;
                        width:44px;height:44px;display:flex;align-items:center;justify-content:center;border-radius:50%;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div id="sheetBanner" style="display:none;margin-bottom:var(--space-4)"></div>

            <form id="proposalForm">
                <input type="hidden" id="prop-id" name="id" value="">

                <div class="pwa-form-group">
                    <label class="pwa-label" for="prop-title">
                        Título <span style="color:var(--color-error)">*</span>
                    </label>
                    <input class="pwa-input" id="prop-title" name="title"
                           type="text" placeholder="Título de la propuesta" required>
                    <span class="pwa-field-error" id="err-prop-title"></span>
                </div>

                <div class="pwa-form-group">
                    <label class="pwa-label" for="prop-desc">
                        Descripción <span style="color:var(--color-error)">*</span>
                    </label>
                    <textarea class="pwa-input" id="prop-desc" name="description"
                              rows="4" placeholder="Describe tu propuesta de investigación..."
                              style="resize:vertical;height:auto" required></textarea>
                    <span class="pwa-field-error" id="err-prop-desc"></span>
                </div>

                <!-- Nota: el estudiante no puede cambiar el estado -->
                <div style="background:var(--color-surface-2);border:1px solid var(--color-border);
                             border-radius:var(--radius-btn);padding:var(--space-3) var(--space-4);
                             margin-bottom:var(--space-5);font-size:var(--text-sm);color:var(--color-text-muted)">
                    <i class="fas fa-info-circle" style="margin-right:6px"></i>
                    Las nuevas propuestas se crean en estado <strong>Pendiente</strong> para revisión.
                </div>

                <button type="submit" class="pwa-btn-primary" id="saveProposalBtn">
                    <i class="fas fa-check"></i>
                    <span id="saveBtnText">Guardar propuesta</span>
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
            <span>Error al cargar propuestas: ${msg}</span>
        </div>
    </div>`);
    initLayoutController();
}

/* ── Eventos ───────────────────────────────────────────── */

function bindListEvents() {

    const sheet    = document.getElementById("proposalSheet");
    const closeBtn = document.getElementById("closeProposalSheet");
    const form     = document.getElementById("proposalForm");

    /* Abrir para crear */
    document.getElementById("newProposalBtn")?.addEventListener("click", () => {
        openSheet(null);
    });

    /* Abrir para editar */
    document.addEventListener("click", e => {
        const btn = e.target.closest(".editProposalBtn");
        if (!btn) return;
        const id   = parseInt(btn.dataset.id);
        const prop = proposalsCache.find(p => p.id === id);
        if (prop) openSheet(prop);
    });

    closeBtn?.addEventListener("click", () => closeSheet());
    sheet?.addEventListener("click", e => { if (e.target === sheet) closeSheet(); });

    /* Submit */
    form?.addEventListener("submit", async e => {
        e.preventDefault();

        const id    = document.getElementById("prop-id").value;
        const title = document.getElementById("prop-title").value.trim();
        const desc  = document.getElementById("prop-desc").value.trim();
        const btn   = document.getElementById("saveProposalBtn");
        const banner= document.getElementById("sheetBanner");

        /* Limpiar errores */
        document.getElementById("err-prop-title").textContent = "";
        document.getElementById("err-prop-desc").textContent  = "";

        let hasErr = false;
        if (!title) { document.getElementById("err-prop-title").innerHTML =
            '<i class="fas fa-exclamation-circle"></i> El título es obligatorio'; hasErr = true; }
        if (!desc)  { document.getElementById("err-prop-desc").innerHTML  =
            '<i class="fas fa-exclamation-circle"></i> La descripción es obligatoria'; hasErr = true; }
        if (hasErr) return;

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
        banner.style.display = "none";

        const user    = getUser();
        const isEdit  = !!id;
        const payload = { user_id: user.id, title, description: desc, status: "PENDIENTE" };

        try {
            if (isEdit) {
                await apiFetch(`/proposals/${id}`, { method: "PUT", body: JSON.stringify(payload) });
            } else {
                await apiFetch("/proposals", { method: "POST", body: JSON.stringify(payload) });
            }

            closeSheet();
            Swal.fire({
                icon:             "success",
                title:            isEdit ? "Propuesta actualizada" : "Propuesta creada",
                text:             isEdit
                    ? "Tu propuesta fue actualizada correctamente."
                    : "Tu propuesta fue enviada y está en estado Pendiente.",
                timer:            2000,
                showConfirmButton: false,
            });
            await loadAndRender();

        } catch (err) {
            banner.style.cssText = `display:flex;align-items:center;gap:8px;padding:12px 14px;
                border-radius:var(--radius-btn);font-size:var(--text-sm);
                background:var(--color-error-light);border:1px solid var(--color-error-border);
                color:var(--color-error-text)`;
            banner.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${err.message}`;
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check"></i> <span id="saveBtnText">Guardar</span>';
        }
    });
}

function openSheet(proposal) {
    const sheet  = document.getElementById("proposalSheet");
    const title  = document.getElementById("sheetTitle");
    const idInp  = document.getElementById("prop-id");
    const titInp = document.getElementById("prop-title");
    const descInp= document.getElementById("prop-desc");
    const banner = document.getElementById("sheetBanner");
    const btnTxt = document.getElementById("saveBtnText");

    if (banner) banner.style.display = "none";
    document.getElementById("err-prop-title").textContent = "";
    document.getElementById("err-prop-desc").textContent  = "";

    if (proposal) {
        title.innerHTML  = '<i class="fas fa-edit" style="color:var(--color-primary);margin-right:8px"></i>Editar Propuesta';
        idInp.value  = proposal.id;
        titInp.value = proposal.title;
        descInp.value= proposal.description;
        if (btnTxt) btnTxt.textContent = "Actualizar propuesta";
    } else {
        title.innerHTML  = '<i class="fas fa-plus-circle" style="color:var(--color-primary);margin-right:8px"></i>Nueva Propuesta';
        idInp.value  = "";
        titInp.value = "";
        descInp.value= "";
        if (btnTxt) btnTxt.textContent = "Guardar propuesta";
    }

    sheet.style.display = "flex";
    setTimeout(() => titInp?.focus(), 100);
}

function closeSheet() {
    document.getElementById("proposalSheet").style.display = "none";
}
