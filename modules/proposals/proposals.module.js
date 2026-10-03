/* =========================================================
   #archivo: /frontend/modules/proposals/proposals.module.js
   Evaluar propuestas (RF11 / CU27) — panel web.
   - Administrativo (actor principal): consulta y evalúa («Marcar viable» / «Archivar» con observación;
     archivar exige observación, E1) y ve los datos de contacto del estudiante en el detalle.
   - Líder de semillero (A1): solo consulta las propuestas de las áreas de sus semilleros.
   - Administrador del sistema: solo consulta.
   El estudiante usa su propia vista (pwa-proposals.module.js, CU25/CU26).
   Los estados que ve el usuario son Recibida, Viable y Archivada (puente de estados de CU26).
   ========================================================= */

import { apiFetch }             from "../../services/api.service.js";
import { getUser }              from "../../services/storage.service.js";
import { LayoutView }           from "../../layout/layout.view.js";
import { initLayoutController } from "../../layout/layout.controller.js";
import { escapeHtml }           from "../../core/escape.js";

const STATUS_BADGE = {
    PENDIENTE: "badge-pwa-warning",
    APROBADA:  "badge-pwa-success",
    RECHAZADA: "badge-pwa-error",
};

const emptyFilters = () => ({ area_id: "", program_id: "", status: "", from: "", to: "" });

let filters = emptyFilters();

const canEvaluate = () => getUser()?.role === "ADMINISTRATIVO";

const badge = (p) =>
    `<span class="badge-pwa ${STATUS_BADGE[p.status] || "badge-pwa-neutral"}">${escapeHtml(p.status_label || p.status)}</span>`;

const day = (p) => String(p.created_at_local || p.created_at || "").slice(0, 10);

export const proposalsModule = {

    async init() {
        filters = emptyFilters();

        let areas = [], programs = [];
        try {
            const [a, p] = await Promise.all([apiFetch("/areas"), apiFetch("/programs")]);
            areas    = (a.areas || []).filter(x => x.status === "ACTIVO");
            programs = (p.programs || []).filter(x => x.status === "ACTIVO");
        } catch { /* la pantalla funciona sin los selectores */ }

        renderShell(areas, programs);
        await loadList();
    }

};


function optionHtml(value, text) {
    return `<option value="${escapeHtml(value)}">${escapeHtml(text)}</option>`;
}

function renderShell(areas, programs) {

    const role = getUser()?.role;
    const note = canEvaluate() ? "" : `
        <p style="color:var(--color-text-muted);font-size:.9em">
            ${role === "LIDER_SEMILLERO"
                ? "Vista de consulta: ves las propuestas de las áreas de tus semilleros. Solo el Administrativo las evalúa."
                : "Vista de consulta: solo el Administrativo evalúa las propuestas."}
        </p>`;

    const content = `

    <h2>Propuestas recibidas</h2>

    ${note}

    <form id="proposalFilters" class="filter-bar">

        <label>Área<br>
            <select name="area_id"><option value="">Todas</option>${areas.map(a => optionHtml(a.id, a.name)).join("")}</select>
        </label>

        <label>Programa<br>
            <select name="program_id"><option value="">Todos</option>${programs.map(p => optionHtml(p.id, p.name)).join("")}</select>
        </label>

        <label>Estado<br>
            <select name="status">
                <option value="">Todos</option>
                <option value="RECIBIDA">Recibidas</option>
                <option value="VIABLE">Viables</option>
                <option value="ARCHIVADA">Archivadas</option>
            </select>
        </label>

        <label>Desde<br><input type="date" name="from"></label>
        <label>Hasta<br><input type="date" name="to"></label>

        <button type="submit" class="btn btn-primary btn-sm">Filtrar</button>
        <button type="button" id="proposalClear" class="btn btn-secondary btn-sm">Limpiar</button>

    </form>

    <div id="proposalError" role="alert" style="color:#c0392b;font-size:.9em;margin-bottom:.5rem"></div>
    <div id="proposalResults" aria-live="polite"></div>
    `;

    document.getElementById("app").innerHTML = LayoutView(content);
    initLayoutController();

    document.getElementById("proposalFilters").addEventListener("submit", async (ev) => {
        ev.preventDefault();
        filters = Object.fromEntries(new FormData(ev.target).entries());
        await loadList();
    });

    document.getElementById("proposalClear").addEventListener("click", async () => {
        document.getElementById("proposalFilters").reset();
        filters = emptyFilters();
        await loadList();
    });

    document.getElementById("proposalResults").addEventListener("click", (ev) => {
        const view = ev.target.closest(".proposalViewBtn");
        if (view) openProposal(view.dataset.id);
    });
}

function setError(message) {
    const box = document.getElementById("proposalError");
    if (box) box.textContent = message || "";
}


/* =========================================================
   LISTADO (pasos 1-3)
   ========================================================= */

async function loadList() {

    const results = document.getElementById("proposalResults");
    if (!results) return;

    setError("");
    results.innerHTML = "<p>Cargando…</p>";

    const params = new URLSearchParams();
    Object.entries(filters).forEach(([k, v]) => { if (v) params.set(k, v); });

    let data;
    try {
        data = await apiFetch(`/proposals?${params.toString()}`);
    } catch (error) {
        results.innerHTML = "";
        setError(error.message || "No se pudieron cargar las propuestas");
        return;
    }

    const rows = data.proposals || [];
    if (!rows.length) {
        results.innerHTML = `<p class="empty-state">${escapeHtml(data.message || "No hay propuestas para los filtros seleccionados")}</p>`;
        return;
    }

    results.innerHTML = `
        <table class="display mobile-card-table" style="width:100%">
            <thead>
                <tr><th>Fecha</th><th>Estudiante</th><th>Programa</th><th>Áreas</th><th>Título</th><th>Estado</th><th>Acciones</th></tr>
            </thead>
            <tbody>
                ${rows.map(p => `
                <tr>
                    <td data-label="Fecha">${escapeHtml(day(p))}</td>
                    <td data-label="Estudiante">${escapeHtml(p.student?.name || "—")}</td>
                    <td data-label="Programa">${escapeHtml(p.program?.name || "—")}</td>
                    <td data-label="Áreas">${escapeHtml((p.areas || []).map(a => a.name).join(", ") || "—")}</td>
                    <td data-label="Título">${escapeHtml(p.title)}</td>
                    <td data-label="Estado">${badge(p)}</td>
                    <td data-label="Acciones"><button type="button" class="proposalViewBtn btn btn-ghost btn-sm" data-id="${escapeHtml(p.id)}">Ver</button></td>
                </tr>`).join("")}
            </tbody>
        </table>
        <p style="color:var(--color-text-muted);font-size:.85em">${rows.length} propuesta(s)</p>
    `;
}


/* =========================================================
   DETALLE Y EVALUACIÓN (pasos 3-6)
   ========================================================= */

async function openProposal(id) {

    let p;
    try {
        p = (await apiFetch(`/proposals/${id}`)).proposal;
    } catch (error) {
        await Swal.fire({ icon: "error", title: "No se pudo abrir la propuesta", text: error.message || "" });
        return;
    }

    const evaluable = canEvaluate() && p.status === "PENDIENTE";

    // Paso 4: descripción completa y, solo para el Administrativo, los datos de contacto.
    const contact = p.contact ? `
        <p><b>Contacto del estudiante</b><br>
           Correo: ${escapeHtml(p.contact.email || "—")}<br>
           Teléfono: ${escapeHtml(p.contact.phone || "—")}</p>` : "";

    const review = p.review_note ? `
        <p><b>Observación${p.reviewer ? ` (${escapeHtml(p.reviewer.name)})` : ""}:</b><br>${escapeHtml(p.review_note)}</p>` : "";

    const result = await Swal.fire({
        title: escapeHtml(p.title),
        width: 640,
        html: `
            <div style="text-align:left;line-height:1.5;font-size:.95em">
                <p>${badge(p)} · ${escapeHtml(day(p))}</p>
                <p><b>Estudiante:</b> ${escapeHtml(p.student?.name || "—")}<br>
                   <b>Programa:</b> ${escapeHtml(p.program?.name || "—")}<br>
                   <b>Áreas:</b> ${escapeHtml((p.areas || []).map(a => a.name).join(", ") || "—")}</p>
                <p><b>Descripción:</b><br>${escapeHtml(p.description || "—").replace(/\n/g, "<br>")}</p>
                ${contact}
                ${review}
            </div>`,
        showConfirmButton: evaluable,
        confirmButtonText: "Marcar viable",
        confirmButtonColor: "#16a34a",
        showDenyButton: evaluable,
        denyButtonText: "Archivar",
        showCancelButton: true,
        cancelButtonText: "Cerrar",
    });

    if (result.isConfirmed) await evaluate(p, "VIABLE");
    else if (result.isDenied) await evaluate(p, "ARCHIVADA");
}

/* Paso 5: la observación es opcional al marcar viable y obligatoria al archivar (E1). */
async function evaluate(p, status) {

    const archiving = status === "ARCHIVADA";

    const answer = await Swal.fire({
        title: archiving ? "Archivar propuesta" : "Marcar propuesta como viable",
        input: "textarea",
        inputLabel: archiving ? "Observación (obligatoria)" : "Observación (opcional)",
        inputAttributes: { maxlength: 1000 },
        showCancelButton: true,
        confirmButtonText: archiving ? "Archivar" : "Marcar viable",
        confirmButtonColor: archiving ? "#dc2626" : "#16a34a",
        cancelButtonText: "Cancelar",
        inputValidator: (value) => {
            const text = (value || "").trim();
            if (archiving && text.length < 5) return "La observación es obligatoria para archivar la propuesta (mínimo 5 caracteres).";
            if (!archiving && text.length > 0 && text.length < 5) return "Escribe al menos 5 caracteres o deja el campo vacío.";
            return undefined;
        },
    });
    if (!answer.isConfirmed) return;

    try {
        const body = { status };
        const note = (answer.value || "").trim();
        if (note) body.review_note = note;
        const res = await apiFetch(`/proposals/${p.id}/update-status`, { method: "PUT", body: JSON.stringify(body) });
        await Swal.fire({ icon: "success", title: res.message || "Propuesta evaluada", timer: 1800, showConfirmButton: false });
    } catch (error) {
        await Swal.fire({ icon: "error", title: "No se pudo evaluar", text: error.message || "" });
    }

    await loadList();
}
