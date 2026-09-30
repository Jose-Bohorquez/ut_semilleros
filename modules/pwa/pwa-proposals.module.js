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
import { escapeHtml }      from "../../core/escape.js";

/* CU26 paso 3: la spec nombra los estados Recibida, Viable y Archivada. La API los
   entrega en `status_label`; este mapa es el respaldo y define el color y si la
   propuesta aún se puede editar (extensión existente: solo mientras está Recibida). */
const STATUS_MAP = {
    PENDIENTE: { label: "Recibida",   cls: "badge-pwa-warning", editable: true  },
    APROBADA:  { label: "Viable",     cls: "badge-pwa-success", editable: false },
    RECHAZADA: { label: "Archivada",  cls: "badge-pwa-error",   editable: false },
};

const statusOf = (p) => {
    const base = STATUS_MAP[p.status] || { label: p.status, cls: "badge-pwa-neutral", editable: false };
    return { ...base, label: p.status_label || base.label };
};

const fmtDate = (iso) => iso
    ? new Date(iso).toLocaleDateString("es-CO", { day: "2-digit", month: "short", year: "numeric" })
    : "";

let proposalsCache = [];
let areasCache = null;
let programsCache = null;

/* RF05: solo áreas activas, salvo las que ya tenía asignadas la propuesta que
   se edita. CU25: selección múltiple (checkboxes), antes era un <select> único. */
async function loadAreaOptions(currentAreaIds = []) {
    if (!areasCache) {
        try { areasCache = (await apiFetch("/areas")).areas || []; }
        catch { areasCache = []; }
    }
    const container = document.getElementById("prop-areas");
    if (!container) return;
    const options = areasCache
        .filter(a => a.status === "ACTIVO" || currentAreaIds.includes(a.id))
        .map(a => `
            <label class="pwa-checkbox-item">
                <input type="checkbox" name="areas" value="${a.id}" ${currentAreaIds.includes(a.id) ? "checked" : ""}>
                ${escapeHtml(a.name)}${a.status !== "ACTIVO" ? " (inactiva)" : ""}
            </label>`)
        .join("");
    container.innerHTML = options || `<p class="pwa-hint">No hay áreas disponibles.</p>`;
}

/* CU25 paso 2: programa (de los programas activos). */
async function loadProgramOptions(currentProgramId = null) {
    if (!programsCache) {
        try { programsCache = (await apiFetch("/programs")).programs || []; }
        catch { programsCache = []; }
    }
    const select = document.getElementById("prop-program");
    if (!select) return;
    const options = programsCache
        .filter(p => p.status === "ACTIVO" || p.id === currentProgramId)
        .map(p => `<option value="${p.id}">${escapeHtml(p.name)}${p.status !== "ACTIVO" ? " (inactivo)" : ""}</option>`)
        .join("");
    select.innerHTML = '<option value="">Selecciona un programa...</option>' + options;
    select.value = currentProgramId || "";
}

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
               <h3>Aún no has registrado propuestas</h3>
               <p>Cuéntanos tu idea de investigación y sigue aquí su estado.</p>
               <button class="pwa-btn-primary" id="emptyNewProposalBtn" style="margin-top:var(--space-3)">
                   <i class="fas fa-plus"></i> Registrar una propuesta
               </button>
           </div>`
        : proposals.map(p => {
            const st = statusOf(p);
            const date = fmtDate(p.created_at);
            const editBtn = st.editable
                ? `<button class="btn btn-sm btn-secondary editProposalBtn"
                           data-id="${escapeHtml(p.id)}" style="margin-top:var(--space-2)">
                       <i class="fas fa-edit"></i> Editar
                   </button>`
                : `<p style="font-size:var(--text-xs);color:var(--color-text-faint);margin-top:var(--space-1)">
                       <i class="fas fa-lock"></i> No editable (${st.label.toLowerCase()})
                   </p>`;

            return `
            <div class="pwa-card proposalCard" data-id="${escapeHtml(p.id)}" tabindex="0" role="button"
                 aria-label="Ver detalle de la propuesta ${escapeHtml(p.title)}"
                 style="flex-direction:column;align-items:flex-start;gap:var(--space-2);cursor:pointer">
                <div style="display:flex;align-items:center;gap:var(--space-3);width:100%">
                    <div class="card-avatar avatar-purple">
                        <i class="fas fa-lightbulb"></i>
                    </div>
                    <div class="card-body">
                        <div class="card-title">${escapeHtml(p.title)}</div>
                        <div class="card-subtitle">
                            <span class="badge-pwa ${st.cls}">${escapeHtml(st.label)}</span>
                            ${(p.areas || []).map(a => `<span class="badge-pwa badge-pwa-neutral">${escapeHtml(a.name)}</span>`).join("")}
                        </div>
                        <div class="card-meta">
                            <i class="fas fa-calendar-alt" style="margin-right:4px"></i>${escapeHtml(date)}
                            ${p.program?.name ? ` · <i class="fas fa-graduation-cap" style="margin:0 4px"></i>${escapeHtml(p.program.name)}` : ""}
                        </div>
                    </div>
                </div>
                ${p.description ? `<p style="font-size:var(--text-sm);color:var(--color-text-2);
                    padding:0 var(--space-2);margin:0;line-height:1.5;
                    display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden">
                    ${escapeHtml(p.description)}</p>` : ""}
                ${p.review_note ? `<p style="font-size:var(--text-sm);color:var(--color-text-2);
                    padding:var(--space-2);margin:0;width:100%;box-sizing:border-box;
                    background:var(--color-surface-2);border-radius:var(--radius-btn)">
                    <i class="fas fa-comment-dots" style="margin-right:6px"></i><strong>Observación:</strong>
                    ${escapeHtml(p.review_note)}</p>` : ""}
                <div style="padding:0 var(--space-2)">${editBtn}</div>
            </div>`;
        }).join("");

    const notice = proposals.some(p => p.status === "PENDIENTE")
        ? `<div style="display:flex;align-items:center;gap:6px;
                 background:var(--color-warning-light);border:1px solid var(--color-warning-border);
                 border-radius:var(--radius-btn);padding:var(--space-3) var(--space-4);
                 margin-bottom:var(--space-4);font-size:var(--text-sm);color:var(--color-warning-text)">
               <i class="fas fa-info-circle"></i>
               Puedes editar tus propuestas mientras estén en estado <strong>Recibida</strong>.
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
                    <label class="pwa-label" for="prop-program">
                        Programa <span style="color:var(--color-error)">*</span>
                    </label>
                    <select class="pwa-input" id="prop-program" name="program_id">
                        <option value="">Selecciona un programa...</option>
                    </select>
                    <span class="pwa-field-error" id="err-prop-program"></span>
                </div>

                <div class="pwa-form-group">
                    <label class="pwa-label">
                        Áreas de conocimiento <span style="color:var(--color-error)">*</span>
                    </label>
                    <!-- Selección múltiple (CU25 paso 2) -->
                    <div id="prop-areas" class="pwa-checkbox-group"></div>
                    <span class="pwa-field-error" id="err-prop-areas"></span>
                </div>

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
                              rows="4" placeholder="Describe tu propuesta de investigación (mínimo 20 caracteres)..."
                              style="resize:vertical;height:auto" required></textarea>
                    <span class="pwa-field-error" id="err-prop-desc"></span>
                </div>

                <div class="pwa-form-group">
                    <label class="pwa-label" for="prop-phone">Teléfono (opcional)</label>
                    <input class="pwa-input" id="prop-phone" name="phone" type="tel" placeholder="Ej: 3001234567">
                    <span class="pwa-field-error" id="err-prop-phone"></span>
                </div>

                <!-- Nota: el estudiante no puede cambiar el estado -->
                <div style="background:var(--color-surface-2);border:1px solid var(--color-border);
                             border-radius:var(--radius-btn);padding:var(--space-3) var(--space-4);
                             margin-bottom:var(--space-5);font-size:var(--text-sm);color:var(--color-text-muted)">
                    <i class="fas fa-info-circle" style="margin-right:6px"></i>
                    Las nuevas propuestas se registran en estado <strong>Recibida</strong> para su revisión.
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
            <span>Error al cargar propuestas: ${escapeHtml(msg)}</span>
        </div>
    </div>`);
    initLayoutController();
}

/* ── Eventos ───────────────────────────────────────────── */

function bindListEvents() {

    const sheet    = document.getElementById("proposalSheet");
    const closeBtn = document.getElementById("closeProposalSheet");
    const form     = document.getElementById("proposalForm");

    /* Abrir para crear (botón «+» o el acceso de la lista vacía, CU26-A1 → CU25) */
    document.getElementById("newProposalBtn")?.addEventListener("click", () => {
        openSheet(null);
    });
    document.getElementById("emptyNewProposalBtn")?.addEventListener("click", () => {
        openSheet(null);
    });

    /* Editar o ver el detalle. El listener va en la lista (que se vuelve a crear en
       cada carga), no en `document`: antes se acumulaba uno nuevo tras cada guardado. */
    const list = document.getElementById("proposalsList");
    const onListActivate = e => {
        const editBtn = e.target.closest(".editProposalBtn");
        if (editBtn) {
            const prop = proposalsCache.find(p => p.id === parseInt(editBtn.dataset.id));
            if (prop) openSheet(prop);
            return;
        }
        const card = e.target.closest(".proposalCard");
        if (card) {
            const prop = proposalsCache.find(p => p.id === parseInt(card.dataset.id));
            if (prop) showProposalDetail(prop);
        }
    };
    list?.addEventListener("click", onListActivate);
    list?.addEventListener("keydown", e => {
        if ((e.key === "Enter" || e.key === " ") && e.target.classList?.contains("proposalCard")) {
            e.preventDefault();
            onListActivate(e);
        }
    });

    closeBtn?.addEventListener("click", () => closeSheet());
    sheet?.addEventListener("click", e => { if (e.target === sheet) closeSheet(); });

    /* Submit */
    form?.addEventListener("submit", async e => {
        e.preventDefault();

        const id      = document.getElementById("prop-id").value;
        const programId = document.getElementById("prop-program").value;
        const areaIds = Array.from(document.querySelectorAll('#prop-areas input[name="areas"]:checked')).map(c => c.value);
        const title   = document.getElementById("prop-title").value.trim();
        const desc    = document.getElementById("prop-desc").value.trim();
        const phone   = document.getElementById("prop-phone").value.trim();
        const btn     = document.getElementById("saveProposalBtn");
        const banner  = document.getElementById("sheetBanner");

        /* Limpiar errores */
        document.getElementById("err-prop-program").textContent = "";
        document.getElementById("err-prop-areas").textContent   = "";
        document.getElementById("err-prop-title").textContent   = "";
        document.getElementById("err-prop-desc").textContent    = "";

        let hasErr = false;
        if (!programId) { document.getElementById("err-prop-program").innerHTML =
            '<i class="fas fa-exclamation-circle"></i> El programa es obligatorio'; hasErr = true; }
        if (!areaIds.length) { document.getElementById("err-prop-areas").innerHTML =
            '<i class="fas fa-exclamation-circle"></i> Selecciona al menos un área'; hasErr = true; }
        if (!title) { document.getElementById("err-prop-title").innerHTML =
            '<i class="fas fa-exclamation-circle"></i> El título es obligatorio'; hasErr = true; }
        if (!desc || desc.length < 20) { document.getElementById("err-prop-desc").innerHTML =
            '<i class="fas fa-exclamation-circle"></i> La descripción debe tener al menos 20 caracteres'; hasErr = true; }
        if (hasErr) return;

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
        banner.style.display = "none";

        const user    = getUser();
        const isEdit  = !!id;
        const payload = { user_id: user.id, program_id: programId, areas: areaIds, title, description: desc, phone: phone || null, status: "PENDIENTE" };

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
                    : "Tu propuesta fue registrada y está en estado Recibida.",
                timer:            2000,
                showConfirmButton: false,
            });
            await loadAndRender();

        } catch (err) {
            banner.style.cssText = `display:flex;align-items:center;gap:8px;padding:12px 14px;
                border-radius:var(--radius-btn);font-size:var(--text-sm);
                background:var(--color-error-light);border:1px solid var(--color-error-border);
                color:var(--color-error-text)`;
            banner.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${escapeHtml(err.message)}`;
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check"></i> <span id="saveBtnText">Guardar</span>';
        }
    });
}

/* CU26 pasos 4-5: al tocar una propuesta se muestra la descripción completa y la
   respuesta (observación) del evaluador. Solo lectura. */
function showProposalDetail(p) {
    const st = statusOf(p);
    const areas = (p.areas || []).map(a => escapeHtml(a.name)).join(", ") || "—";
    const note = p.review_note
        ? escapeHtml(p.review_note)
        : `<em>${p.status === "PENDIENTE" ? "Aún sin respuesta del evaluador." : "Sin observación registrada."}</em>`;

    Swal.fire({
        title: escapeHtml(p.title),
        html: `
            <div style="text-align:left;line-height:1.5;font-size:.95em">
                <p><span class="badge-pwa ${st.cls}">${escapeHtml(st.label)}</span>
                   · ${escapeHtml(fmtDate(p.created_at))}</p>
                <p><b>Programa:</b> ${escapeHtml(p.program?.name || "—")}<br>
                   <b>Áreas:</b> ${areas}</p>
                <p><b>Descripción:</b><br>${escapeHtml(p.description || "—").replace(/\n/g, "<br>")}</p>
                <p><b>Respuesta del evaluador:</b><br>${note}</p>
            </div>`,
        confirmButtonText: "Cerrar",
    });
}

function openSheet(proposal) {
    const sheet  = document.getElementById("proposalSheet");
    const title  = document.getElementById("sheetTitle");
    const idInp  = document.getElementById("prop-id");
    const titInp = document.getElementById("prop-title");
    const descInp= document.getElementById("prop-desc");
    const phoneInp= document.getElementById("prop-phone");
    const banner = document.getElementById("sheetBanner");
    const btnTxt = document.getElementById("saveBtnText");

    if (banner) banner.style.display = "none";
    document.getElementById("err-prop-title").textContent   = "";
    document.getElementById("err-prop-desc").textContent    = "";
    document.getElementById("err-prop-areas").textContent   = "";
    document.getElementById("err-prop-program").textContent = "";

    if (proposal) {
        title.innerHTML  = '<i class="fas fa-edit" style="color:var(--color-primary);margin-right:8px"></i>Editar Propuesta';
        idInp.value  = proposal.id;
        titInp.value = proposal.title;
        descInp.value= proposal.description;
        if (phoneInp) phoneInp.value = proposal.phone || "";
        if (btnTxt) btnTxt.textContent = "Actualizar propuesta";
    } else {
        title.innerHTML  = '<i class="fas fa-plus-circle" style="color:var(--color-primary);margin-right:8px"></i>Nueva Propuesta';
        idInp.value  = "";
        titInp.value = "";
        descInp.value= "";
        if (phoneInp) phoneInp.value = "";
        if (btnTxt) btnTxt.textContent = "Guardar propuesta";
    }

    loadAreaOptions((proposal?.areas || []).map(a => a.id));
    loadProgramOptions(proposal?.program_id ?? null);

    sheet.style.display = "flex";
    setTimeout(() => titInp?.focus(), 100);
}

function closeSheet() {
    document.getElementById("proposalSheet").style.display = "none";
}
